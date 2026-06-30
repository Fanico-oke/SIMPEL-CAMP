<?php
// api/transaksi.php
// API endpoint untuk manajemen transaksi penyewaan

require_once dirname(__DIR__) . '/includes/auth.php';

// Cek autentikasi
if (!isLoggedIn()) jsonError('Unauthorized', 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($method) {
    // =============================================
    // GET: Ambil data transaksi
    // =============================================
    case 'GET':
        $db = Database::getInstance();

        if ($action === 'detail' || isset($_GET['id'])) {
            // Detail transaksi
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) jsonError('ID transaksi tidak valid');

            try {
                $stmt = $db->prepare("
                    SELECT t.*, u.nama AS nama_user, u.email AS email_user, u.no_telp AS telp_user,
                           r.kode_reservasi, r.tanggal_mulai, r.tanggal_selesai, r.catatan AS catatan_reservasi
                    FROM transaksi t
                    JOIN users u ON t.user_id = u.id
                    LEFT JOIN reservasi r ON t.reservasi_id = r.id
                    WHERE t.id = ?
                ");
                $stmt->execute([$id]);
                $transaksi = $stmt->fetch();

                if (!$transaksi) jsonError('Transaksi tidak ditemukan', 404);

                // Pelanggan hanya bisa lihat milik sendiri
                if ($user['role'] === 'pelanggan' && $transaksi['user_id'] != $user['id']) {
                    jsonError('Akses ditolak', 403);
                }

                // Ambil detail item dari reservasi terkait
                if ($transaksi['reservasi_id']) {
                    $stmtDetail = $db->prepare("
                        SELECT dr.*, b.nama AS nama_barang, b.gambar
                        FROM detail_reservasi dr
                        JOIN barang b ON dr.barang_id = b.id
                        WHERE dr.reservasi_id = ?
                    ");
                    $stmtDetail->execute([$transaksi['reservasi_id']]);
                    $transaksi['items'] = $stmtDetail->fetchAll();
                }

                // Ambil data pembayaran
                $stmtBayar = $db->prepare("
                    SELECT * FROM pembayaran WHERE transaksi_id = ? ORDER BY tanggal_bayar DESC
                ");
                $stmtBayar->execute([$id]);
                $transaksi['pembayaran'] = $stmtBayar->fetchAll();

                // Ambil data pengembalian
                $stmtKembali = $db->prepare("SELECT * FROM pengembalian WHERE transaksi_id = ?");
                $stmtKembali->execute([$id]);
                $transaksi['pengembalian'] = $stmtKembali->fetch();

                jsonSuccess($transaksi, 'Detail transaksi berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Transaksi Detail Error: " . $e->getMessage());
                jsonError('Gagal mengambil detail transaksi', 500);
            }
        } else {
            // List transaksi
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            $status = sanitize($_GET['status'] ?? '');
            $tipe = sanitize($_GET['tipe'] ?? '');
            $search = sanitize($_GET['search'] ?? '');

            try {
                $where = [];
                $params = [];

                // Pelanggan hanya lihat milik sendiri
                if ($user['role'] === 'pelanggan') {
                    $where[] = 't.user_id = ?';
                    $params[] = $user['id'];
                }

                if (!empty($status)) {
                    $where[] = 't.status = ?';
                    $params[] = $status;
                }
                if (!empty($tipe) && in_array($tipe, ['online', 'walk_in'])) {
                    $where[] = 't.tipe = ?';
                    $params[] = $tipe;
                }
                if (!empty($search)) {
                    $where[] = '(t.kode_transaksi LIKE ? OR u.nama LIKE ?)';
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }

                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

                // Count
                $countStmt = $db->prepare("
                    SELECT COUNT(*) FROM transaksi t
                    JOIN users u ON t.user_id = u.id
                    $whereClause
                ");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                // Data
                $stmt = $db->prepare("
                    SELECT t.*, u.nama AS nama_user, u.email AS email_user,
                           r.kode_reservasi, r.tanggal_mulai, r.tanggal_selesai
                    FROM transaksi t
                    JOIN users u ON t.user_id = u.id
                    LEFT JOIN reservasi r ON t.reservasi_id = r.id
                    $whereClause
                    ORDER BY t.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute(array_merge($params, [$limit, $offset]));

                jsonSuccess([
                    'transaksi' => $stmt->fetchAll(),
                    'pagination' => [
                        'total' => (int) $total,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($total / $limit)
                    ]
                ], 'Data transaksi berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Transaksi List Error: " . $e->getMessage());
                jsonError('Gagal mengambil data transaksi', 500);
            }
        }
        break;

    // =============================================
    // POST: Create walk-in, Update status
    // =============================================
    case 'POST':
        $db = Database::getInstance();

        // === CREATE WALK-IN (admin only) ===
        if ($action === 'create_walkin') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Hanya admin yang bisa membuat transaksi walk-in', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $user_id = intval($input['user_id'] ?? 0);
            $items = $input['items'] ?? [];
            $tanggal_mulai = sanitize($input['tanggal_mulai'] ?? date('Y-m-d'));
            $tanggal_selesai = sanitize($input['tanggal_selesai'] ?? '');
            $metode_bayar = sanitize($input['metode_bayar'] ?? 'cash');

            // Validasi
            if (empty($tanggal_selesai)) jsonError('Tanggal selesai wajib diisi');
            if (empty($items) || !is_array($items)) jsonError('Minimal pilih 1 barang');
            if (!in_array($metode_bayar, ['transfer', 'ewallet', 'qris', 'cash'])) {
                $metode_bayar = 'cash';
            }

            // Jika user_id = 0, buat guest user
            if ($user_id <= 0) {
                $guest_nama = sanitize($input['guest_nama'] ?? 'Pelanggan Walk-in');
                $guest_telp = sanitize($input['guest_telp'] ?? '');

                try {
                    $guestEmail = 'walkin_' . uniqid() . '@simpelcamp.com';
                    $db->prepare("
                        INSERT INTO users (nama, email, password, no_telp, role, status)
                        VALUES (?, ?, ?, ?, 'pelanggan', 'aktif')
                    ")->execute([
                        $guest_nama, $guestEmail,
                        password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT),
                        $guest_telp
                    ]);
                    $user_id = $db->lastInsertId();

                    // Buat member level untuk guest
                    $db->prepare("INSERT INTO member_level (user_id) VALUES (?)")->execute([$user_id]);
                } catch (PDOException $e) {
                    error_log("API Create Guest Error: " . $e->getMessage());
                    jsonError('Gagal membuat data pelanggan', 500);
                }
            }

            $jumlah_hari = hitungHari($tanggal_mulai, $tanggal_selesai);
            if ($jumlah_hari < 1) $jumlah_hari = 1;

            try {
                $db->beginTransaction();

                // Buat reservasi terlebih dahulu
                $kode_reservasi = generateKode('RSV');
                $total_biaya = 0;
                $detail_items = [];

                foreach ($items as $item) {
                    $barang_id = intval($item['barang_id'] ?? 0);
                    $jumlah = intval($item['jumlah'] ?? 1);

                    $stmtBarang = $db->prepare("SELECT * FROM barang WHERE id = ? AND stok_tersedia >= ?");
                    $stmtBarang->execute([$barang_id, $jumlah]);
                    $barang = $stmtBarang->fetch();

                    if (!$barang) {
                        $db->rollBack();
                        jsonError("Barang ID $barang_id tidak tersedia atau stok tidak cukup");
                    }

                    $subtotal = $barang['harga_per_hari'] * $jumlah * $jumlah_hari;
                    $total_biaya += $subtotal;
                    $detail_items[] = [
                        'barang_id' => $barang_id,
                        'jumlah' => $jumlah,
                        'harga_satuan' => $barang['harga_per_hari'],
                        'subtotal' => $subtotal
                    ];

                    // Kurangi stok (dengan proteksi race condition)
                    $stmtStok = $db->prepare("UPDATE barang SET stok_tersedia = stok_tersedia - ? WHERE id = ? AND stok_tersedia >= ?");
                    $stmtStok->execute([$jumlah, $barang_id, $jumlah]);
                    if ($stmtStok->rowCount() === 0) {
                        $db->rollBack();
                        jsonError("Stok barang ID $barang_id tidak mencukupi saat proses");
                    }
                }

                // Insert reservasi
                $db->prepare("
                    INSERT INTO reservasi (kode_reservasi, user_id, tanggal_mulai, tanggal_selesai,
                        total_biaya, deposit, status)
                    VALUES (?, ?, ?, ?, ?, 0, 'aktif')
                ")->execute([$kode_reservasi, $user_id, $tanggal_mulai, $tanggal_selesai, $total_biaya]);
                $reservasi_id = $db->lastInsertId();

                // Insert detail reservasi
                $stmtDI = $db->prepare("
                    INSERT INTO detail_reservasi (reservasi_id, barang_id, jumlah, harga_satuan, subtotal)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($detail_items as $di) {
                    $stmtDI->execute([$reservasi_id, $di['barang_id'], $di['jumlah'], $di['harga_satuan'], $di['subtotal']]);
                }

                // Buat transaksi
                $kode_transaksi = generateKode('TRX');
                $db->prepare("
                    INSERT INTO transaksi (kode_transaksi, reservasi_id, user_id, tipe, total_bayar, status)
                    VALUES (?, ?, ?, 'walk_in', ?, 'aktif')
                ")->execute([$kode_transaksi, $reservasi_id, $user_id, $total_biaya]);
                $transaksi_id = $db->lastInsertId();

                // Buat pembayaran langsung (walk-in biasanya cash)
                $db->prepare("
                    INSERT INTO pembayaran (transaksi_id, metode, jumlah, status)
                    VALUES (?, ?, ?, 'dikonfirmasi')
                ")->execute([$transaksi_id, $metode_bayar, $total_biaya]);

                $db->commit();

                jsonSuccess([
                    'transaksi_id' => $transaksi_id,
                    'kode_transaksi' => $kode_transaksi,
                    'kode_reservasi' => $kode_reservasi,
                    'total_bayar' => $total_biaya
                ], 'Transaksi walk-in berhasil dibuat');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Transaksi Walk-in Error: " . $e->getMessage());
                jsonError('Gagal membuat transaksi walk-in', 500);
            }
        }

        // === UPDATE STATUS (admin only) ===
        elseif ($action === 'update_status') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? 0);
            $status = sanitize($input['status'] ?? '');

            if ($id <= 0) jsonError('ID transaksi tidak valid');
            if (!in_array($status, ['menunggu_bayar', 'dibayar', 'aktif', 'selesai'])) {
                jsonError('Status tidak valid');
            }

            try {
                $stmt = $db->prepare("SELECT * FROM transaksi WHERE id = ?");
                $stmt->execute([$id]);
                $transaksi = $stmt->fetch();
                if (!$transaksi) jsonError('Transaksi tidak ditemukan', 404);

                $db->prepare("UPDATE transaksi SET status = ? WHERE id = ?")->execute([$status, $id]);

                // Jika status selesai, update reservasi juga
                if ($status === 'selesai' && $transaksi['reservasi_id']) {
                    $db->prepare("UPDATE reservasi SET status = 'selesai' WHERE id = ?")
                        ->execute([$transaksi['reservasi_id']]);
                }
                // Jika status aktif, update reservasi juga
                if ($status === 'aktif' && $transaksi['reservasi_id']) {
                    $db->prepare("UPDATE reservasi SET status = 'aktif' WHERE id = ?")
                        ->execute([$transaksi['reservasi_id']]);
                }

                jsonSuccess(['id' => $id, 'status' => $status], 'Status transaksi berhasil diperbarui');
            } catch (PDOException $e) {
                error_log("API Transaksi Update Status Error: " . $e->getMessage());
                jsonError('Gagal memperbarui status transaksi', 500);
            }
        } else {
            jsonError('Action tidak valid. Gunakan: create_walkin, update_status');
        }
        break;

    default:
        jsonError('Method tidak diizinkan', 405);
}
