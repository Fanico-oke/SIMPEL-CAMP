<?php
// api/reservasi.php
// API endpoint untuk manajemen reservasi penyewaan

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/classes/Wishlist.php';

// Cek autentikasi
if (!isLoggedIn()) jsonError('Unauthorized', 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($method) {
    // =============================================
    // GET: Ambil data reservasi
    // =============================================
    case 'GET':
        $db = Database::getInstance();

        if ($action === 'detail' || isset($_GET['id'])) {
            // Detail reservasi berdasarkan ID
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) jsonError('ID reservasi tidak valid');

            try {
                $stmt = $db->prepare("
                    SELECT r.*, u.nama AS nama_user, u.email AS email_user, u.no_telp AS telp_user,
                           p.kode AS promo_kode, p.nama AS promo_nama
                    FROM reservasi r
                    JOIN users u ON r.user_id = u.id
                    LEFT JOIN promo p ON r.promo_id = p.id
                    WHERE r.id = ?
                ");
                $stmt->execute([$id]);
                $reservasi = $stmt->fetch();

                if (!$reservasi) jsonError('Reservasi tidak ditemukan', 404);

                // Pelanggan hanya bisa lihat milik sendiri
                if ($user['role'] === 'pelanggan' && $reservasi['user_id'] != $user['id']) {
                    jsonError('Akses ditolak', 403);
                }

                // Ambil detail item reservasi
                $stmtDetail = $db->prepare("
                    SELECT dr.*, b.nama AS nama_barang, b.gambar, b.harga_per_hari
                    FROM detail_reservasi dr
                    JOIN barang b ON dr.barang_id = b.id
                    WHERE dr.reservasi_id = ?
                ");
                $stmtDetail->execute([$id]);
                $reservasi['items'] = $stmtDetail->fetchAll();

                jsonSuccess($reservasi, 'Detail reservasi berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Reservasi Detail Error: " . $e->getMessage());
                jsonError('Gagal mengambil detail reservasi', 500);
            }
        } else {
            // List reservasi (filter by user untuk pelanggan, semua untuk admin)
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            $status = sanitize($_GET['status'] ?? '');
            $search = sanitize($_GET['search'] ?? '');

            try {
                $where = [];
                $params = [];

                // Pelanggan hanya lihat milik sendiri
                if ($user['role'] === 'pelanggan') {
                    $where[] = 'r.user_id = ?';
                    $params[] = $user['id'];
                }

                if (!empty($status)) {
                    $where[] = 'r.status = ?';
                    $params[] = $status;
                }

                if (!empty($search)) {
                    $where[] = '(r.kode_reservasi LIKE ? OR u.nama LIKE ?)';
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }

                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

                // Count total
                $countStmt = $db->prepare("
                    SELECT COUNT(*) FROM reservasi r
                    JOIN users u ON r.user_id = u.id
                    $whereClause
                ");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                // Ambil data
                $stmt = $db->prepare("
                    SELECT r.*, u.nama AS nama_user, u.email AS email_user
                    FROM reservasi r
                    JOIN users u ON r.user_id = u.id
                    $whereClause
                    ORDER BY r.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute(array_merge($params, [$limit, $offset]));
                $reservasi = $stmt->fetchAll();

                jsonSuccess([
                    'reservasi' => $reservasi,
                    'pagination' => [
                        'total' => (int) $total,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($total / $limit)
                    ]
                ], 'Data reservasi berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Reservasi List Error: " . $e->getMessage());
                jsonError('Gagal mengambil data reservasi', 500);
            }
        }
        break;

    // =============================================
    // POST: Create, Approve, Reject, Cancel
    // =============================================
    case 'POST':
        $db = Database::getInstance();

        // === CREATE RESERVASI (pelanggan) ===
        if ($action === 'create') {
            if ($user['role'] !== 'pelanggan') {
                jsonError('Hanya pelanggan yang bisa membuat reservasi', 403);
            }

            // Parse input (support JSON body)
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $tanggal_mulai = sanitize($input['tanggal_mulai'] ?? '');
            $tanggal_selesai = sanitize($input['tanggal_selesai'] ?? '');
            $items = $input['items'] ?? [];
            $catatan = sanitize($input['catatan'] ?? '');
            $promo_kode = sanitize($input['promo_kode'] ?? '');

            // Validasi input
            if (empty($tanggal_mulai) || empty($tanggal_selesai)) {
                jsonError('Tanggal mulai dan selesai wajib diisi');
            }
            if (strtotime($tanggal_mulai) < strtotime(date('Y-m-d'))) {
                jsonError('Tanggal mulai tidak boleh di masa lalu');
            }
            if (strtotime($tanggal_selesai) <= strtotime($tanggal_mulai)) {
                jsonError('Tanggal selesai harus setelah tanggal mulai');
            }
            if (empty($items) || !is_array($items)) {
                jsonError('Minimal pilih 1 barang untuk disewa');
            }

            // Hitung jumlah hari sewa
            $jumlah_hari = hitungHari($tanggal_mulai, $tanggal_selesai);
            if ($jumlah_hari < 1) $jumlah_hari = 1;

            try {
                $db->beginTransaction();

                // Validasi stok dan hitung total
                $total_biaya = 0;
                $detail_items = [];

                foreach ($items as $item) {
                    $barang_id = intval($item['barang_id'] ?? 0);
                    $jumlah = intval($item['jumlah'] ?? 1);

                    if ($barang_id <= 0 || $jumlah <= 0) {
                        $db->rollBack();
                        jsonError('Data barang tidak valid');
                    }

                    // Cek barang dan stok
                    $stmtBarang = $db->prepare("
                        SELECT id, nama, harga_per_hari, stok_tersedia, status
                        FROM barang WHERE id = ?
                    ");
                    $stmtBarang->execute([$barang_id]);
                    $barang = $stmtBarang->fetch();

                    if (!$barang) {
                        $db->rollBack();
                        jsonError("Barang dengan ID $barang_id tidak ditemukan");
                    }
                    if ($barang['status'] !== 'tersedia') {
                        $db->rollBack();
                        jsonError("Barang '{$barang['nama']}' sedang tidak tersedia");
                    }
                    if ($barang['stok_tersedia'] < $jumlah) {
                        $db->rollBack();
                        jsonError("Stok '{$barang['nama']}' tidak mencukupi. Tersedia: {$barang['stok_tersedia']}");
                    }

                    $subtotal = $barang['harga_per_hari'] * $jumlah * $jumlah_hari;
                    $total_biaya += $subtotal;

                    $detail_items[] = [
                        'barang_id' => $barang_id,
                        'jumlah' => $jumlah,
                        'harga_satuan' => $barang['harga_per_hari'],
                        'subtotal' => $subtotal
                    ];
                }

                // Proses promo jika ada
                $promo_id = null;
                $diskon = 0;
                if (!empty($promo_kode)) {
                    $stmtPromo = $db->prepare("
                        SELECT * FROM promo
                        WHERE kode = ? AND status = 'aktif'
                        AND mulai <= CURDATE() AND selesai >= CURDATE()
                        AND (kuota = 0 OR terpakai < kuota)
                    ");
                    $stmtPromo->execute([$promo_kode]);
                    $promo = $stmtPromo->fetch();

                    if ($promo) {
                        if ($total_biaya >= $promo['min_transaksi']) {
                            $promo_id = $promo['id'];
                            if ($promo['tipe'] === 'persentase') {
                                $diskon = $total_biaya * ($promo['nilai'] / 100);
                            } else {
                                $diskon = $promo['nilai'];
                            }
                            // Diskon tidak boleh melebihi total
                            $diskon = min($diskon, $total_biaya);
                        }
                    }
                }

                // Hitung deposit (ambil dari pengaturan)
                $stmtDeposit = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = 'deposit_persen'");
                $stmtDeposit->execute();
                $depositPersen = floatval($stmtDeposit->fetchColumn() ?: 30);

                $total_final = $total_biaya - $diskon;
                $deposit = $total_final * ($depositPersen / 100);

                // Generate kode reservasi
                $kode_reservasi = generateKode('RSV');

                // Insert reservasi
                $stmtInsert = $db->prepare("
                    INSERT INTO reservasi (kode_reservasi, user_id, tanggal_mulai, tanggal_selesai,
                        total_biaya, deposit, promo_id, diskon, status, catatan)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
                ");
                $stmtInsert->execute([
                    $kode_reservasi, $user['id'], $tanggal_mulai, $tanggal_selesai,
                    $total_final, $deposit, $promo_id, $diskon, $catatan
                ]);
                $reservasi_id = $db->lastInsertId();

                // Insert detail reservasi
                $stmtDetailInsert = $db->prepare("
                    INSERT INTO detail_reservasi (reservasi_id, barang_id, jumlah, harga_satuan, subtotal)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($detail_items as $di) {
                    $stmtDetailInsert->execute([
                        $reservasi_id, $di['barang_id'], $di['jumlah'],
                        $di['harga_satuan'], $di['subtotal']
                    ]);
                }

                // Update terpakai promo
                if ($promo_id) {
                    $db->prepare("UPDATE promo SET terpakai = terpakai + 1 WHERE id = ?")->execute([$promo_id]);
                }

                // Buat notifikasi untuk admin
                $stmtNotif = $db->prepare("
                    INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                    SELECT id, ?, ?, 'reservasi', ?
                    FROM users WHERE role IN ('admin', 'superadmin') AND status = 'aktif'
                ");
                $stmtNotif->execute([
                    'Reservasi Baru',
                    "Reservasi baru $kode_reservasi dari {$user['nama']}",
                    "?page=reservasi&id=$reservasi_id"
                ]);

                $db->commit();

                jsonSuccess([
                    'id' => $reservasi_id,
                    'kode_reservasi' => $kode_reservasi,
                    'total_biaya' => $total_final,
                    'deposit' => $deposit,
                    'diskon' => $diskon
                ], 'Reservasi berhasil dibuat');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Reservasi Create Error: " . $e->getMessage());
                jsonError('Gagal membuat reservasi', 500);
            }
        }

        // === CREATE AND PAY (combined: create + auto-approve) ===
        elseif ($action === 'create_and_pay') {
            if ($user['role'] !== 'pelanggan') jsonError('Akses ditolak', 403);

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $items = $input['items'] ?? [];
            $catatan = sanitize($input['catatan'] ?? '');
            $metode_bayar = sanitize($input['metode_bayar'] ?? 'cash');

            if (empty($items)) jsonError('Minimal 1 item harus dipilih');

            try {
                $db->beginTransaction();

                $total_biaya = 0;
                $validated_items = [];
                $earliest_start = null;
                $latest_end = null;

                // Validate items & calc total (per-item dates)
                foreach ($items as $item) {
                    $barang_id = intval($item['barang_id'] ?? 0);
                    $jumlah = intval($item['jumlah'] ?? 1);
                    $t_mulai = sanitize($item['tanggal_mulai'] ?? '');
                    $t_selesai = sanitize($item['tanggal_selesai'] ?? '');

                    if ($barang_id <= 0 || $jumlah <= 0) continue;
                    if (empty($t_mulai) || empty($t_selesai)) { $db->rollBack(); jsonError('Tanggal sewa wajib diisi untuk semua item'); }
                    if (strtotime($t_selesai) <= strtotime($t_mulai)) { $db->rollBack(); jsonError('Tanggal selesai harus setelah tanggal mulai'); }

                    $stmtBarang = $db->prepare("SELECT * FROM barang WHERE id = ? AND status = 'tersedia'");
                    $stmtBarang->execute([$barang_id]);
                    $barang = $stmtBarang->fetch();
                    if (!$barang) { $db->rollBack(); jsonError("Barang ID $barang_id tidak tersedia"); }
                    if ($barang['stok_tersedia'] < $jumlah) { $db->rollBack(); jsonError("Stok {$barang['nama']} tidak mencukupi"); }

                    $jumlah_hari = hitungHari($t_mulai, $t_selesai);
                    $subtotal = $barang['harga_per_hari'] * $jumlah * $jumlah_hari;
                    $total_biaya += $subtotal;
                    $validated_items[] = [
                        'barang_id' => $barang_id, 'jumlah' => $jumlah,
                        'harga' => $barang['harga_per_hari'], 'subtotal' => $subtotal,
                        'tanggal_mulai' => $t_mulai, 'tanggal_selesai' => $t_selesai
                    ];

                    // Track overall date range
                    if ($earliest_start === null || strtotime($t_mulai) < strtotime($earliest_start)) $earliest_start = $t_mulai;
                    if ($latest_end === null || strtotime($t_selesai) > strtotime($latest_end)) $latest_end = $t_selesai;
                }

                if (empty($validated_items)) { $db->rollBack(); jsonError('Tidak ada item valid'); }

                // Deposit
                $stmtDep = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = 'deposit_persen'");
                $stmtDep->execute();
                $depRow = $stmtDep->fetch();
                $deposit_persen = $depRow ? (float)$depRow['value'] : 30;
                $deposit = round($total_biaya * $deposit_persen / 100);

                // Create reservation (use earliest/latest dates as overall range)
                $kode_reservasi = generateKode('RSV');
                $stmtRsv = $db->prepare("
                    INSERT INTO reservasi (kode_reservasi, user_id, tanggal_mulai, tanggal_selesai, total_biaya, deposit, diskon, status, catatan)
                    VALUES (?, ?, ?, ?, ?, ?, 0, 'disetujui', ?)
                ");
                $stmtRsv->execute([$kode_reservasi, $user['id'], $earliest_start, $latest_end, $total_biaya, $deposit, $catatan]);
                $reservasi_id = $db->lastInsertId();

                // Insert detail items + kurangi stok
                foreach ($validated_items as $vi) {
                    $db->prepare("INSERT INTO detail_reservasi (reservasi_id, barang_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)")
                       ->execute([$reservasi_id, $vi['barang_id'], $vi['jumlah'], $vi['harga'], $vi['subtotal']]);

                    $stmtStok = $db->prepare("UPDATE barang SET stok_tersedia = stok_tersedia - ? WHERE id = ? AND stok_tersedia >= ?");
                    $stmtStok->execute([$vi['jumlah'], $vi['barang_id'], $vi['jumlah']]);
                    if ($stmtStok->rowCount() === 0) { $db->rollBack(); jsonError('Stok tidak mencukupi'); }
                }

                // Create transaction (menunggu_bayar)
                $kode_transaksi = generateKode('TRX');
                $tipe = in_array($metode_bayar, ['transfer', 'qris', 'ewallet']) ? 'online' : 'walk_in';
                $db->prepare("
                    INSERT INTO transaksi (kode_transaksi, reservasi_id, user_id, tipe, total_bayar, status)
                    VALUES (?, ?, ?, ?, ?, 'menunggu_bayar')
                ")->execute([$kode_transaksi, $reservasi_id, $user['id'], $tipe, $total_biaya]);
                $transaksi_id = $db->lastInsertId();

                // Clear checked items from wishlist
                $checkedIds = array_column($validated_items, 'barang_id');
                Wishlist::removeByIds($user['id'], $checkedIds);

                // Notify admin
                $db->prepare("
                    INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                    SELECT id, ?, ?, 'reservasi', ?
                    FROM users WHERE role IN ('admin', 'superadmin') AND status = 'aktif'
                ")->execute([
                    'Pesanan Baru + Pembayaran',
                    "Pesanan $kode_reservasi dari {$user['nama']} menunggu konfirmasi pembayaran.",
                    "?page=pembayaran"
                ]);

                $db->commit();

                jsonSuccess([
                    'id' => $reservasi_id,
                    'kode_reservasi' => $kode_reservasi,
                    'kode_transaksi' => $kode_transaksi,
                    'transaksi_id' => $transaksi_id,
                    'total_biaya' => $total_biaya,
                    'deposit' => $deposit,
                ], 'Pesanan berhasil dibuat');
            } catch (PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("API Reservasi CreateAndPay Error: " . $e->getMessage());
                jsonError('Gagal membuat pesanan: ' . $e->getMessage(), 500);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("API Reservasi CreateAndPay General Error: " . $e->getMessage());
                jsonError('Gagal membuat pesanan: ' . $e->getMessage(), 500);
            }
        }

        // === APPROVE RESERVASI (admin) ===
        elseif ($action === 'approve') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) jsonError('ID reservasi tidak valid');

            try {
                $db->beginTransaction();

                // Cek status reservasi
                $stmtCheck = $db->prepare("SELECT * FROM reservasi WHERE id = ? AND status = 'pending'");
                $stmtCheck->execute([$id]);
                $reservasi = $stmtCheck->fetch();
                if (!$reservasi) jsonError('Reservasi tidak ditemukan atau sudah diproses');

                // Kurangi stok barang
                $stmtItems = $db->prepare("SELECT barang_id, jumlah FROM detail_reservasi WHERE reservasi_id = ?");
                $stmtItems->execute([$id]);
                $items = $stmtItems->fetchAll();

                foreach ($items as $item) {
                    $stmtUpdateStok = $db->prepare("
                        UPDATE barang SET stok_tersedia = stok_tersedia - ?
                        WHERE id = ? AND stok_tersedia >= ?
                    ");
                    $stmtUpdateStok->execute([$item['jumlah'], $item['barang_id'], $item['jumlah']]);

                    if ($stmtUpdateStok->rowCount() === 0) {
                        $db->rollBack();
                        jsonError('Stok barang tidak mencukupi untuk disetujui');
                    }
                }

                // Update status reservasi
                $db->prepare("UPDATE reservasi SET status = 'disetujui' WHERE id = ?")->execute([$id]);

                // Buat transaksi otomatis
                $kode_transaksi = generateKode('TRX');
                $db->prepare("
                    INSERT INTO transaksi (kode_transaksi, reservasi_id, user_id, tipe, total_bayar, status)
                    VALUES (?, ?, ?, 'online', ?, 'menunggu_bayar')
                ")->execute([$kode_transaksi, $id, $reservasi['user_id'], $reservasi['total_biaya']]);

                // Notifikasi ke pelanggan
                $db->prepare("
                    INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                    VALUES (?, 'Reservasi Disetujui', ?, 'reservasi', ?)
                ")->execute([
                    $reservasi['user_id'],
                    "Reservasi {$reservasi['kode_reservasi']} telah disetujui. Silakan lakukan pembayaran.",
                    "?page=reservasi&id=$id"
                ]);

                $db->commit();

                jsonSuccess(['id' => $id, 'kode_transaksi' => $kode_transaksi], 'Reservasi berhasil disetujui');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Reservasi Approve Error: " . $e->getMessage());
                jsonError('Gagal menyetujui reservasi', 500);
            }
        }

        // === REJECT RESERVASI (admin) ===
        elseif ($action === 'reject') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? 0);
            $alasan = sanitize($input['alasan'] ?? '');

            if ($id <= 0) jsonError('ID reservasi tidak valid');
            if (empty($alasan)) jsonError('Alasan penolakan wajib diisi');

            try {
                $stmtCheck = $db->prepare("SELECT * FROM reservasi WHERE id = ? AND status = 'pending'");
                $stmtCheck->execute([$id]);
                $reservasi = $stmtCheck->fetch();
                if (!$reservasi) jsonError('Reservasi tidak ditemukan atau sudah diproses');

                $db->prepare("
                    UPDATE reservasi SET status = 'ditolak', alasan_tolak = ? WHERE id = ?
                ")->execute([$alasan, $id]);

                // Notifikasi ke pelanggan
                $db->prepare("
                    INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                    VALUES (?, 'Reservasi Ditolak', ?, 'reservasi', ?)
                ")->execute([
                    $reservasi['user_id'],
                    "Reservasi {$reservasi['kode_reservasi']} ditolak. Alasan: $alasan",
                    "?page=reservasi&id=$id"
                ]);

                jsonSuccess(['id' => $id], 'Reservasi berhasil ditolak');
            } catch (PDOException $e) {
                error_log("API Reservasi Reject Error: " . $e->getMessage());
                jsonError('Gagal menolak reservasi', 500);
            }
        }

        // === CANCEL RESERVASI (pelanggan - milik sendiri) ===
        elseif ($action === 'cancel') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) jsonError('ID reservasi tidak valid');

            try {
                $stmtCheck = $db->prepare("
                    SELECT * FROM reservasi WHERE id = ? AND status IN ('pending', 'disetujui')
                ");
                $stmtCheck->execute([$id]);
                $reservasi = $stmtCheck->fetch();
                if (!$reservasi) jsonError('Reservasi tidak ditemukan atau tidak bisa dibatalkan');

                // Pelanggan hanya bisa batal milik sendiri
                if ($user['role'] === 'pelanggan' && $reservasi['user_id'] != $user['id']) {
                    jsonError('Anda tidak bisa membatalkan reservasi milik orang lain', 403);
                }

                $db->beginTransaction();

                // Jika sudah disetujui, kembalikan stok
                if ($reservasi['status'] === 'disetujui') {
                    $stmtItems = $db->prepare("SELECT barang_id, jumlah FROM detail_reservasi WHERE reservasi_id = ?");
                    $stmtItems->execute([$id]);
                    foreach ($stmtItems->fetchAll() as $item) {
                        $db->prepare("UPDATE barang SET stok_tersedia = stok_tersedia + ? WHERE id = ?")
                            ->execute([$item['jumlah'], $item['barang_id']]);
                    }
                }

                $db->prepare("UPDATE reservasi SET status = 'batal' WHERE id = ?")->execute([$id]);

                // Batalkan transaksi terkait jika ada
                $db->prepare("DELETE FROM transaksi WHERE reservasi_id = ? AND status = 'menunggu_bayar'")
                    ->execute([$id]);

                $db->commit();

                jsonSuccess(['id' => $id], 'Reservasi berhasil dibatalkan');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Reservasi Cancel Error: " . $e->getMessage());
                jsonError('Gagal membatalkan reservasi', 500);
            }
        } else {
            jsonError('Action tidak valid. Gunakan: create, approve, reject, cancel');
        }
        break;

    default:
        jsonError('Method tidak diizinkan', 405);
}
