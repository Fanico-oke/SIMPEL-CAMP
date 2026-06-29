<?php
// api/pembayaran.php
// API endpoint untuk manajemen pembayaran

require_once dirname(__DIR__) . '/includes/auth.php';

// Cek autentikasi
if (!isLoggedIn()) jsonError('Unauthorized', 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($method) {
    // =============================================
    // GET: Ambil data pembayaran
    // =============================================
    case 'GET':
        $db = Database::getInstance();

        if (isset($_GET['transaksi_id'])) {
            // List pembayaran by transaksi_id
            $transaksi_id = intval($_GET['transaksi_id']);
            if ($transaksi_id <= 0) jsonError('ID transaksi tidak valid');

            try {
                // Verifikasi akses: pelanggan hanya bisa lihat transaksi miliknya
                if ($user['role'] === 'pelanggan') {
                    $stmtCheck = $db->prepare("SELECT user_id FROM transaksi WHERE id = ?");
                    $stmtCheck->execute([$transaksi_id]);
                    $trx = $stmtCheck->fetch();
                    if (!$trx || $trx['user_id'] != $user['id']) {
                        jsonError('Akses ditolak', 403);
                    }
                }

                $stmt = $db->prepare("
                    SELECT p.*, t.kode_transaksi
                    FROM pembayaran p
                    JOIN transaksi t ON p.transaksi_id = t.id
                    WHERE p.transaksi_id = ?
                    ORDER BY p.tanggal_bayar DESC
                ");
                $stmt->execute([$transaksi_id]);

                jsonSuccess($stmt->fetchAll(), 'Data pembayaran berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Pembayaran List Error: " . $e->getMessage());
                jsonError('Gagal mengambil data pembayaran', 500);
            }
        } else {
            // List semua pembayaran (admin only)
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

                if (!empty($status) && in_array($status, ['pending', 'dikonfirmasi', 'ditolak'])) {
                    $where[] = 'p.status = ?';
                    $params[] = $status;
                }

                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

                $countStmt = $db->prepare("SELECT COUNT(*) FROM pembayaran p $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                $stmt = $db->prepare("
                    SELECT p.*, t.kode_transaksi, t.total_bayar AS total_transaksi,
                           u.nama AS nama_user
                    FROM pembayaran p
                    JOIN transaksi t ON p.transaksi_id = t.id
                    JOIN users u ON t.user_id = u.id
                    $whereClause
                    ORDER BY p.tanggal_bayar DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute(array_merge($params, [$limit, $offset]));

                jsonSuccess([
                    'pembayaran' => $stmt->fetchAll(),
                    'pagination' => [
                        'total' => (int) $total,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($total / $limit)
                    ]
                ], 'Data pembayaran berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Pembayaran List All Error: " . $e->getMessage());
                jsonError('Gagal mengambil data pembayaran', 500);
            }
        }
        break;

    // =============================================
    // POST: Create, Confirm, Reject pembayaran
    // =============================================
    case 'POST':
        $db = Database::getInstance();

        // === CREATE PEMBAYARAN (upload bukti bayar) ===
        if ($action === 'create') {
            $transaksi_id = intval($_POST['transaksi_id'] ?? 0);
            $metode = sanitize($_POST['metode'] ?? '');
            $jumlah = floatval($_POST['jumlah'] ?? 0);
            $catatan = sanitize($_POST['catatan'] ?? '');

            // Validasi input
            if ($transaksi_id <= 0) jsonError('ID transaksi tidak valid');
            if (!in_array($metode, ['transfer', 'ewallet', 'qris', 'cash'])) {
                jsonError('Metode pembayaran tidak valid');
            }
            if ($jumlah <= 0) jsonError('Jumlah pembayaran harus lebih dari 0');

            try {
                // Cek transaksi
                $stmtTrx = $db->prepare("SELECT * FROM transaksi WHERE id = ?");
                $stmtTrx->execute([$transaksi_id]);
                $transaksi = $stmtTrx->fetch();

                if (!$transaksi) jsonError('Transaksi tidak ditemukan', 404);

                // Pelanggan hanya bisa bayar transaksi milik sendiri
                if ($user['role'] === 'pelanggan' && $transaksi['user_id'] != $user['id']) {
                    jsonError('Akses ditolak', 403);
                }

                if ($transaksi['status'] !== 'menunggu_bayar') {
                    jsonError('Transaksi ini tidak dalam status menunggu pembayaran');
                }

                // Handle upload bukti bayar
                $bukti_bayar = null;
                if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] === UPLOAD_ERR_OK) {
                    $bukti_bayar = uploadBuktiBayar($_FILES['bukti_bayar']);
                    if ($bukti_bayar === false) {
                        jsonError('Gagal mengupload bukti bayar. Pastikan format JPG/PNG/WebP dan ukuran max 2MB.');
                    }
                } elseif ($metode !== 'cash') {
                    jsonError('Bukti bayar wajib diupload untuk metode non-tunai');
                }

                $stmt = $db->prepare("
                    INSERT INTO pembayaran (transaksi_id, metode, jumlah, bukti_bayar, status, catatan)
                    VALUES (?, ?, ?, ?, 'pending', ?)
                ");
                $stmt->execute([$transaksi_id, $metode, $jumlah, $bukti_bayar, $catatan]);

                // Notifikasi ke admin
                $stmtNotif = $db->prepare("
                    INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                    SELECT id, ?, ?, 'pembayaran', ?
                    FROM users WHERE role IN ('admin', 'superadmin') AND status = 'aktif'
                ");
                $stmtNotif->execute([
                    'Pembayaran Baru',
                    "Pembayaran untuk transaksi {$transaksi['kode_transaksi']} menunggu konfirmasi.",
                    "?page=pembayaran"
                ]);

                jsonSuccess(['id' => $db->lastInsertId()], 'Bukti pembayaran berhasil dikirim');
            } catch (PDOException $e) {
                error_log("API Pembayaran Create Error: " . $e->getMessage());
                jsonError('Gagal mengirim bukti pembayaran', 500);
            }
        }

        // === CONFIRM PEMBAYARAN (admin only) ===
        elseif ($action === 'confirm') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) jsonError('ID pembayaran tidak valid');

            try {
                $db->beginTransaction();

                $stmtCheck = $db->prepare("SELECT * FROM pembayaran WHERE id = ? AND status = 'pending'");
                $stmtCheck->execute([$id]);
                $pembayaran = $stmtCheck->fetch();
                if (!$pembayaran) jsonError('Pembayaran tidak ditemukan atau sudah diproses');

                // Update status pembayaran
                $db->prepare("
                    UPDATE pembayaran SET status = 'dikonfirmasi', confirmed_at = NOW() WHERE id = ?
                ")->execute([$id]);

                // Update status transaksi ke 'dibayar'
                $db->prepare("
                    UPDATE transaksi SET status = 'dibayar' WHERE id = ? AND status = 'menunggu_bayar'
                ")->execute([$pembayaran['transaksi_id']]);

                // Notifikasi ke pelanggan
                $stmtTrx = $db->prepare("SELECT user_id, kode_transaksi FROM transaksi WHERE id = ?");
                $stmtTrx->execute([$pembayaran['transaksi_id']]);
                $trx = $stmtTrx->fetch();

                if ($trx) {
                    $db->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                        VALUES (?, 'Pembayaran Dikonfirmasi', ?, 'pembayaran', ?)
                    ")->execute([
                        $trx['user_id'],
                        "Pembayaran untuk transaksi {$trx['kode_transaksi']} telah dikonfirmasi.",
                        "?page=transaksi&id={$pembayaran['transaksi_id']}"
                    ]);
                }

                $db->commit();

                jsonSuccess(['id' => $id], 'Pembayaran berhasil dikonfirmasi');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Pembayaran Confirm Error: " . $e->getMessage());
                jsonError('Gagal mengkonfirmasi pembayaran', 500);
            }
        }

        // === REJECT PEMBAYARAN (admin only) ===
        elseif ($action === 'reject') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $id = intval($input['id'] ?? 0);
            $catatan = sanitize($input['catatan'] ?? '');

            if ($id <= 0) jsonError('ID pembayaran tidak valid');

            try {
                $stmtCheck = $db->prepare("SELECT * FROM pembayaran WHERE id = ? AND status = 'pending'");
                $stmtCheck->execute([$id]);
                $pembayaran = $stmtCheck->fetch();
                if (!$pembayaran) jsonError('Pembayaran tidak ditemukan atau sudah diproses');

                $db->prepare("
                    UPDATE pembayaran SET status = 'ditolak', catatan = ? WHERE id = ?
                ")->execute([$catatan, $id]);

                // Notifikasi ke pelanggan
                $stmtTrx = $db->prepare("SELECT user_id, kode_transaksi FROM transaksi WHERE id = ?");
                $stmtTrx->execute([$pembayaran['transaksi_id']]);
                $trx = $stmtTrx->fetch();

                if ($trx) {
                    $db->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                        VALUES (?, 'Pembayaran Ditolak', ?, 'pembayaran', ?)
                    ")->execute([
                        $trx['user_id'],
                        "Pembayaran untuk transaksi {$trx['kode_transaksi']} ditolak. Silakan upload ulang.",
                        "?page=transaksi&id={$pembayaran['transaksi_id']}"
                    ]);
                }

                jsonSuccess(['id' => $id], 'Pembayaran berhasil ditolak');
            } catch (PDOException $e) {
                error_log("API Pembayaran Reject Error: " . $e->getMessage());
                jsonError('Gagal menolak pembayaran', 500);
            }
        }

        // === BAYAR DENDA (pelanggan upload bukti transfer denda) ===
        elseif ($action === 'bayar_denda') {
            $transaksi_id = intval($_POST['transaksi_id'] ?? 0);
            $metode = sanitize($_POST['metode'] ?? '');
            $catatan = sanitize($_POST['catatan'] ?? '');

            if ($transaksi_id <= 0) jsonError('ID transaksi tidak valid');
            if (!in_array($metode, ['transfer', 'ewallet', 'qris'])) {
                jsonError('Metode pembayaran denda tidak valid');
            }

            try {
                // Cek transaksi dan pastikan ada denda
                $stmtTrx = $db->prepare("
                    SELECT t.*, r.id AS rsv_id, r.status AS rsv_status
                    FROM transaksi t
                    LEFT JOIN reservasi r ON t.reservasi_id = r.id
                    WHERE t.id = ?
                ");
                $stmtTrx->execute([$transaksi_id]);
                $transaksi = $stmtTrx->fetch();

                if (!$transaksi) jsonError('Transaksi tidak ditemukan', 404);
                if ($user['role'] === 'pelanggan' && $transaksi['user_id'] != $user['id']) {
                    jsonError('Akses ditolak', 403);
                }
                if ($transaksi['denda'] <= 0) jsonError('Tidak ada denda pada transaksi ini');
                if ($transaksi['rsv_status'] !== 'menunggu_denda') {
                    jsonError('Transaksi ini tidak dalam status menunggu pembayaran denda');
                }

                // Upload bukti bayar denda
                if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_OK) {
                    jsonError('Bukti transfer denda wajib diunggah');
                }
                $bukti_bayar = uploadBuktiBayar($_FILES['bukti_bayar']);
                if ($bukti_bayar === false) {
                    jsonError('Gagal mengupload bukti bayar. Pastikan format JPG/PNG/WebP dan ukuran max 2MB.');
                }

                // Insert pembayaran denda
                $db->prepare("
                    INSERT INTO pembayaran (transaksi_id, jenis_pembayaran, metode, jumlah, bukti_bayar, status, catatan)
                    VALUES (?, 'denda', ?, ?, ?, 'pending', ?)
                ")->execute([$transaksi_id, $metode, $transaksi['denda'], $bukti_bayar, $catatan]);

                // Notifikasi ke admin
                $stmtNotif = $db->prepare("
                    INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                    SELECT id, ?, ?, 'pembayaran', ?
                    FROM users WHERE role IN ('admin', 'superadmin') AND status = 'aktif'
                ");
                $stmtNotif->execute([
                    'Pembayaran Denda Masuk',
                    "Pelanggan {$user['nama']} mengirim bukti pembayaran denda untuk transaksi {$transaksi['kode_transaksi']}.",
                    "?page=pembayaran"
                ]);

                jsonSuccess(['id' => $db->lastInsertId()], 'Bukti pembayaran denda berhasil dikirim. Menunggu verifikasi admin.');
            } catch (PDOException $e) {
                error_log("API Pembayaran Bayar Denda Error: " . $e->getMessage());
                jsonError('Gagal mengirim bukti pembayaran denda', 500);
            }
        }

        // === KONFIRMASI DENDA (admin: verifikasi online / terima tunai) ===
        elseif ($action === 'konfirmasi_denda') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $transaksi_id = intval($input['transaksi_id'] ?? 0);
            $metode_denda = sanitize($input['metode_denda'] ?? 'cash'); // cash = tunai, online = verif bukti

            if ($transaksi_id <= 0) jsonError('ID transaksi tidak valid');

            try {
                $db->beginTransaction();

                // Cek transaksi
                $stmtTrx = $db->prepare("
                    SELECT t.*, r.id AS rsv_id, r.status AS rsv_status
                    FROM transaksi t
                    LEFT JOIN reservasi r ON t.reservasi_id = r.id
                    WHERE t.id = ?
                ");
                $stmtTrx->execute([$transaksi_id]);
                $transaksi = $stmtTrx->fetch();

                if (!$transaksi) jsonError('Transaksi tidak ditemukan', 404);
                if ($transaksi['rsv_status'] !== 'menunggu_denda') {
                    jsonError('Transaksi ini tidak dalam status menunggu pembayaran denda');
                }

                if ($metode_denda === 'cash') {
                    // Admin menerima pembayaran tunai langsung
                    $db->prepare("
                        INSERT INTO pembayaran (transaksi_id, jenis_pembayaran, metode, jumlah, status, catatan, confirmed_at)
                        VALUES (?, 'denda', 'cash', ?, 'dikonfirmasi', 'Diterima tunai oleh admin', NOW())
                    ")->execute([$transaksi_id, $transaksi['denda']]);
                } else {
                    // Verifikasi pembayaran denda online (update yang pending jadi dikonfirmasi)
                    $db->prepare("
                        UPDATE pembayaran SET status = 'dikonfirmasi', confirmed_at = NOW()
                        WHERE transaksi_id = ? AND jenis_pembayaran = 'denda' AND status = 'pending'
                    ")->execute([$transaksi_id]);
                }

                // Selesaikan transaksi dan reservasi
                $db->prepare("UPDATE transaksi SET status = 'selesai' WHERE id = ?")->execute([$transaksi_id]);
                if ($transaksi['rsv_id']) {
                    $db->prepare("UPDATE reservasi SET status = 'selesai' WHERE id = ?")->execute([$transaksi['rsv_id']]);
                }

                // Kembalikan stok barang
                if ($transaksi['rsv_id']) {
                    // Cek kondisi barang dari pengembalian
                    $stmtPg = $db->prepare("SELECT kondisi_barang FROM pengembalian WHERE transaksi_id = ?");
                    $stmtPg->execute([$transaksi_id]);
                    $pgData = $stmtPg->fetch();
                    $kondisi = $pgData['kondisi_barang'] ?? 'baik';

                    if ($kondisi !== 'hilang') {
                        $stmtItems = $db->prepare("SELECT barang_id, jumlah FROM detail_reservasi WHERE reservasi_id = ?");
                        $stmtItems->execute([$transaksi['rsv_id']]);
                        foreach ($stmtItems->fetchAll() as $item) {
                            $db->prepare("UPDATE barang SET stok_tersedia = stok_tersedia + ? WHERE id = ?")
                                ->execute([$item['jumlah'], $item['barang_id']]);
                        }
                    }
                }

                // Notifikasi ke pelanggan
                $db->prepare("
                    INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                    VALUES (?, 'Denda Lunas - Pesanan Selesai', ?, 'pembayaran', ?)
                ")->execute([
                    $transaksi['user_id'],
                    "Pembayaran denda untuk transaksi {$transaksi['kode_transaksi']} telah dikonfirmasi. Pesanan Anda selesai. Terima kasih!",
                    "?page=pesanan"
                ]);

                $db->commit();
                jsonSuccess(['id' => $transaksi_id], 'Denda berhasil dikonfirmasi. Pesanan selesai.');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Pembayaran Konfirmasi Denda Error: " . $e->getMessage());
                jsonError('Gagal mengkonfirmasi pembayaran denda', 500);
            }
        } else {
            jsonError('Action tidak valid. Gunakan: create, confirm, reject, bayar_denda, konfirmasi_denda');
        }
        break;

    default:
        jsonError('Method tidak diizinkan', 405);
}

// =============================================
// Helper: Upload bukti bayar
// =============================================
function uploadBuktiBayar($file) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowedTypes)) return false;
    if ($file['size'] > $maxSize) return false;

    $uploadDir = dirname(__DIR__) . '/frontend/img/pembayaran/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('pay_') . '.' . strtolower($ext);

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }

    return false;
}
