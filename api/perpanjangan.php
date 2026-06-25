<?php
// api/perpanjangan.php
// API endpoint untuk manajemen perpanjangan sewa

require_once dirname(__DIR__) . '/includes/auth.php';

// Cek autentikasi
if (!isLoggedIn()) jsonError('Unauthorized', 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($method) {
    // =============================================
    // GET: Ambil data perpanjangan
    // =============================================
    case 'GET':
        $db = Database::getInstance();

        if (isset($_GET['reservasi_id'])) {
            // List perpanjangan by reservasi_id
            $reservasi_id = intval($_GET['reservasi_id']);
            if ($reservasi_id <= 0) jsonError('ID reservasi tidak valid');

            try {
                // Cek akses pelanggan
                if ($user['role'] === 'pelanggan') {
                    $stmtCheck = $db->prepare("SELECT user_id FROM reservasi WHERE id = ?");
                    $stmtCheck->execute([$reservasi_id]);
                    $rsv = $stmtCheck->fetch();
                    if (!$rsv || $rsv['user_id'] != $user['id']) {
                        jsonError('Akses ditolak', 403);
                    }
                }

                $stmt = $db->prepare("
                    SELECT pp.*, r.kode_reservasi
                    FROM perpanjangan pp
                    JOIN reservasi r ON pp.reservasi_id = r.id
                    WHERE pp.reservasi_id = ?
                    ORDER BY pp.created_at DESC
                ");
                $stmt->execute([$reservasi_id]);

                jsonSuccess($stmt->fetchAll(), 'Data perpanjangan berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Perpanjangan List Error: " . $e->getMessage());
                jsonError('Gagal mengambil data perpanjangan', 500);
            }
        } else {
            // List semua perpanjangan (admin only)
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            $status = sanitize($_GET['status'] ?? '');

            try {
                $where = [];
                $params = [];

                if (!empty($status) && in_array($status, ['pending', 'disetujui', 'ditolak'])) {
                    $where[] = 'pp.status = ?';
                    $params[] = $status;
                }

                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

                $countStmt = $db->prepare("SELECT COUNT(*) FROM perpanjangan pp $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                $stmt = $db->prepare("
                    SELECT pp.*, r.kode_reservasi, u.nama AS nama_user
                    FROM perpanjangan pp
                    JOIN reservasi r ON pp.reservasi_id = r.id
                    JOIN users u ON r.user_id = u.id
                    $whereClause
                    ORDER BY pp.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute(array_merge($params, [$limit, $offset]));

                jsonSuccess([
                    'perpanjangan' => $stmt->fetchAll(),
                    'pagination' => [
                        'total' => (int) $total,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($total / $limit)
                    ]
                ], 'Data perpanjangan berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Perpanjangan List All Error: " . $e->getMessage());
                jsonError('Gagal mengambil data perpanjangan', 500);
            }
        }
        break;

    // =============================================
    // POST: Create, Approve, Reject, Hitung
    // =============================================
    case 'POST':
        $db = Database::getInstance();

        // === HITUNG BIAYA PERPANJANGAN (tanpa simpan) ===
        if ($action === 'hitung') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $reservasi_id = intval($input['reservasi_id'] ?? 0);
            $tanggal_baru = sanitize($input['tanggal_baru'] ?? '');

            if ($reservasi_id <= 0) jsonError('ID reservasi tidak valid');
            if (empty($tanggal_baru)) jsonError('Tanggal baru wajib diisi');

            try {
                $stmt = $db->prepare("
                    SELECT r.*, SUM(dr.harga_satuan * dr.jumlah) AS biaya_per_hari
                    FROM reservasi r
                    JOIN detail_reservasi dr ON r.id = dr.reservasi_id
                    WHERE r.id = ?
                    GROUP BY r.id
                ");
                $stmt->execute([$reservasi_id]);
                $reservasi = $stmt->fetch();

                if (!$reservasi) jsonError('Reservasi tidak ditemukan', 404);

                $tanggal_lama = $reservasi['tanggal_selesai'];
                $tambahan_hari = hitungHari($tanggal_lama, $tanggal_baru);

                if ($tambahan_hari <= 0) {
                    jsonError('Tanggal baru harus setelah tanggal selesai saat ini');
                }

                // Cek batas maksimal perpanjangan
                $stmtMax = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = 'max_perpanjangan'");
                $stmtMax->execute();
                $maxHari = intval($stmtMax->fetchColumn() ?: 7);

                if ($tambahan_hari > $maxHari) {
                    jsonError("Perpanjangan maksimal $maxHari hari");
                }

                $biaya_tambahan = $reservasi['biaya_per_hari'] * $tambahan_hari;

                jsonSuccess([
                    'tanggal_lama' => $tanggal_lama,
                    'tanggal_baru' => $tanggal_baru,
                    'tambahan_hari' => $tambahan_hari,
                    'biaya_per_hari' => $reservasi['biaya_per_hari'],
                    'biaya_tambahan' => $biaya_tambahan
                ], 'Perhitungan biaya perpanjangan');
            } catch (PDOException $e) {
                error_log("API Perpanjangan Hitung Error: " . $e->getMessage());
                jsonError('Gagal menghitung biaya perpanjangan', 500);
            }
        }

        // === CREATE PERPANJANGAN (pelanggan) ===
        elseif ($action === 'create') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $reservasi_id = intval($input['reservasi_id'] ?? 0);
            $tanggal_baru = sanitize($input['tanggal_baru'] ?? '');
            $metode_bayar = sanitize($input['metode_bayar'] ?? '');
            $alasan = sanitize($input['alasan'] ?? '');

            // Validasi input
            if ($reservasi_id <= 0) jsonError('ID reservasi tidak valid');
            if (empty($tanggal_baru)) jsonError('Tanggal baru wajib diisi');
            if (!in_array($metode_bayar, ['transfer', 'ewallet', 'qris'])) {
                jsonError('Metode bayar tidak valid');
            }

            try {
                // Cek reservasi
                $stmtRsv = $db->prepare("
                    SELECT r.*, SUM(dr.harga_satuan * dr.jumlah) AS biaya_per_hari
                    FROM reservasi r
                    JOIN detail_reservasi dr ON r.id = dr.reservasi_id
                    WHERE r.id = ? AND r.status = 'aktif'
                    GROUP BY r.id
                ");
                $stmtRsv->execute([$reservasi_id]);
                $reservasi = $stmtRsv->fetch();

                if (!$reservasi) jsonError('Reservasi tidak ditemukan atau tidak aktif');

                // Pelanggan hanya bisa perpanjang milik sendiri
                if ($user['role'] === 'pelanggan' && $reservasi['user_id'] != $user['id']) {
                    jsonError('Akses ditolak', 403);
                }

                // Cek apakah ada perpanjangan pending
                $stmtPending = $db->prepare("
                    SELECT COUNT(*) FROM perpanjangan WHERE reservasi_id = ? AND status = 'pending'
                ");
                $stmtPending->execute([$reservasi_id]);
                if ($stmtPending->fetchColumn() > 0) {
                    jsonError('Masih ada permintaan perpanjangan yang belum diproses');
                }

                $tanggal_lama = $reservasi['tanggal_selesai'];
                $tambahan_hari = hitungHari($tanggal_lama, $tanggal_baru);

                if ($tambahan_hari <= 0) {
                    jsonError('Tanggal baru harus setelah tanggal selesai saat ini');
                }

                // Cek batas maksimal
                $stmtMax = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = 'max_perpanjangan'");
                $stmtMax->execute();
                $maxHari = intval($stmtMax->fetchColumn() ?: 7);
                if ($tambahan_hari > $maxHari) {
                    jsonError("Perpanjangan maksimal $maxHari hari");
                }

                $biaya_tambahan = $reservasi['biaya_per_hari'] * $tambahan_hari;

                // Insert perpanjangan
                $stmt = $db->prepare("
                    INSERT INTO perpanjangan (reservasi_id, tanggal_lama, tanggal_baru,
                        tambahan_hari, biaya_tambahan, metode_bayar, alasan, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $reservasi_id, $tanggal_lama, $tanggal_baru,
                    $tambahan_hari, $biaya_tambahan, $metode_bayar, $alasan
                ]);

                // Notifikasi ke admin
                $stmtNotif = $db->prepare("
                    INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                    SELECT id, ?, ?, 'perpanjangan', ?
                    FROM users WHERE role IN ('admin', 'superadmin') AND status = 'aktif'
                ");
                $stmtNotif->execute([
                    'Permintaan Perpanjangan',
                    "Permintaan perpanjangan {$tambahan_hari} hari untuk reservasi {$reservasi['kode_reservasi']}",
                    "?page=perpanjangan"
                ]);

                jsonSuccess([
                    'id' => $db->lastInsertId(),
                    'tambahan_hari' => $tambahan_hari,
                    'biaya_tambahan' => $biaya_tambahan
                ], 'Permintaan perpanjangan berhasil dikirim');
            } catch (PDOException $e) {
                error_log("API Perpanjangan Create Error: " . $e->getMessage());
                jsonError('Gagal membuat permintaan perpanjangan', 500);
            }
        }

        // === APPROVE PERPANJANGAN (admin only) ===
        elseif ($action === 'approve') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) jsonError('ID perpanjangan tidak valid');

            try {
                $db->beginTransaction();

                $stmtCheck = $db->prepare("SELECT * FROM perpanjangan WHERE id = ? AND status = 'pending'");
                $stmtCheck->execute([$id]);
                $perpanjangan = $stmtCheck->fetch();
                if (!$perpanjangan) jsonError('Perpanjangan tidak ditemukan atau sudah diproses');

                // Update status perpanjangan
                $db->prepare("UPDATE perpanjangan SET status = 'disetujui' WHERE id = ?")->execute([$id]);

                // Update tanggal selesai di reservasi
                $db->prepare("
                    UPDATE reservasi SET tanggal_selesai = ?,
                    total_biaya = total_biaya + ?
                    WHERE id = ?
                ")->execute([
                    $perpanjangan['tanggal_baru'],
                    $perpanjangan['biaya_tambahan'],
                    $perpanjangan['reservasi_id']
                ]);

                // Update total_bayar di transaksi
                $db->prepare("
                    UPDATE transaksi SET total_bayar = total_bayar + ?
                    WHERE reservasi_id = ?
                ")->execute([$perpanjangan['biaya_tambahan'], $perpanjangan['reservasi_id']]);

                // Notifikasi ke pelanggan
                $stmtRsv = $db->prepare("SELECT user_id, kode_reservasi FROM reservasi WHERE id = ?");
                $stmtRsv->execute([$perpanjangan['reservasi_id']]);
                $rsv = $stmtRsv->fetch();

                if ($rsv) {
                    $db->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                        VALUES (?, 'Perpanjangan Disetujui', ?, 'perpanjangan', ?)
                    ")->execute([
                        $rsv['user_id'],
                        "Perpanjangan untuk reservasi {$rsv['kode_reservasi']} disetujui hingga {$perpanjangan['tanggal_baru']}.",
                        "?page=reservasi&id={$perpanjangan['reservasi_id']}"
                    ]);
                }

                $db->commit();

                jsonSuccess(['id' => $id], 'Perpanjangan berhasil disetujui');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Perpanjangan Approve Error: " . $e->getMessage());
                jsonError('Gagal menyetujui perpanjangan', 500);
            }
        }

        // === REJECT PERPANJANGAN (admin only) ===
        elseif ($action === 'reject') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? 0);
            $alasan_tolak = sanitize($input['alasan'] ?? '');

            if ($id <= 0) jsonError('ID perpanjangan tidak valid');
            if (empty($alasan_tolak)) jsonError('Alasan penolakan wajib diisi');

            try {
                $stmtCheck = $db->prepare("SELECT * FROM perpanjangan WHERE id = ? AND status = 'pending'");
                $stmtCheck->execute([$id]);
                $perpanjangan = $stmtCheck->fetch();
                if (!$perpanjangan) jsonError('Perpanjangan tidak ditemukan atau sudah diproses');

                $db->prepare("
                    UPDATE perpanjangan SET status = 'ditolak', alasan_tolak = ? WHERE id = ?
                ")->execute([$alasan_tolak, $id]);

                // Notifikasi ke pelanggan
                $stmtRsv = $db->prepare("SELECT user_id, kode_reservasi FROM reservasi WHERE id = ?");
                $stmtRsv->execute([$perpanjangan['reservasi_id']]);
                $rsv = $stmtRsv->fetch();

                if ($rsv) {
                    $db->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                        VALUES (?, 'Perpanjangan Ditolak', ?, 'perpanjangan', ?)
                    ")->execute([
                        $rsv['user_id'],
                        "Perpanjangan untuk reservasi {$rsv['kode_reservasi']} ditolak. Alasan: $alasan_tolak",
                        "?page=reservasi&id={$perpanjangan['reservasi_id']}"
                    ]);
                }

                jsonSuccess(['id' => $id], 'Perpanjangan berhasil ditolak');
            } catch (PDOException $e) {
                error_log("API Perpanjangan Reject Error: " . $e->getMessage());
                jsonError('Gagal menolak perpanjangan', 500);
            }
        } else {
            jsonError('Action tidak valid. Gunakan: create, approve, reject, hitung');
        }
        break;

    default:
        jsonError('Method tidak diizinkan', 405);
}
