<?php
// api/pengembalian.php
// API endpoint untuk manajemen pengembalian barang

require_once dirname(__DIR__) . '/includes/auth.php';

// Cek autentikasi
if (!isLoggedIn()) jsonError('Unauthorized', 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($method) {
    // =============================================
    // GET: Ambil data pengembalian
    // =============================================
    case 'GET':
        $db = Database::getInstance();

        if (isset($_GET['transaksi_id'])) {
            // Detail pengembalian by transaksi_id
            $transaksi_id = intval($_GET['transaksi_id']);
            if ($transaksi_id <= 0) jsonError('ID transaksi tidak valid');

            try {
                $stmt = $db->prepare("
                    SELECT pg.*, t.kode_transaksi, t.total_bayar,
                           r.kode_reservasi, r.tanggal_mulai, r.tanggal_selesai,
                           u.nama AS nama_user
                    FROM pengembalian pg
                    JOIN transaksi t ON pg.transaksi_id = t.id
                    LEFT JOIN reservasi r ON t.reservasi_id = r.id
                    JOIN users u ON t.user_id = u.id
                    WHERE pg.transaksi_id = ?
                ");
                $stmt->execute([$transaksi_id]);
                $pengembalian = $stmt->fetch();

                if (!$pengembalian) jsonError('Data pengembalian tidak ditemukan', 404);

                jsonSuccess($pengembalian, 'Detail pengembalian berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Pengembalian Detail Error: " . $e->getMessage());
                jsonError('Gagal mengambil detail pengembalian', 500);
            }
        } else {
            // List semua pengembalian (admin only)
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Akses ditolak', 403);
            }

            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;

            try {
                $countStmt = $db->query("SELECT COUNT(*) FROM pengembalian");
                $total = $countStmt->fetchColumn();

                $stmt = $db->prepare("
                    SELECT pg.*, t.kode_transaksi, t.total_bayar,
                           r.kode_reservasi, r.tanggal_selesai,
                           u.nama AS nama_user
                    FROM pengembalian pg
                    JOIN transaksi t ON pg.transaksi_id = t.id
                    LEFT JOIN reservasi r ON t.reservasi_id = r.id
                    JOIN users u ON t.user_id = u.id
                    ORDER BY pg.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$limit, $offset]);

                jsonSuccess([
                    'pengembalian' => $stmt->fetchAll(),
                    'pagination' => [
                        'total' => (int) $total,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($total / $limit)
                    ]
                ], 'Data pengembalian berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Pengembalian List Error: " . $e->getMessage());
                jsonError('Gagal mengambil data pengembalian', 500);
            }
        }
        break;

    // =============================================
    // POST: Create pengembalian, Hitung denda
    // =============================================
    case 'POST':
        $db = Database::getInstance();

        // === HITUNG DENDA (tanpa simpan) ===
        if ($action === 'hitung_denda') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $transaksi_id = intval($input['transaksi_id'] ?? 0);
            $tanggal_kembali = sanitize($input['tanggal_kembali'] ?? date('Y-m-d'));
            $kondisi_barang = sanitize($input['kondisi_barang'] ?? 'baik');

            if ($transaksi_id <= 0) jsonError('ID transaksi tidak valid');

            try {
                $stmt = $db->prepare("
                    SELECT t.*, r.tanggal_selesai, r.total_biaya
                    FROM transaksi t
                    LEFT JOIN reservasi r ON t.reservasi_id = r.id
                    WHERE t.id = ?
                ");
                $stmt->execute([$transaksi_id]);
                $transaksi = $stmt->fetch();

                if (!$transaksi) jsonError('Transaksi tidak ditemukan', 404);

                $tanggal_selesai = $transaksi['tanggal_selesai'] ?? date('Y-m-d');
                $hari_terlambat = max(0, hitungHari($tanggal_selesai, $tanggal_kembali));

                // Ambil persentase denda dari pengaturan
                $stmtDenda = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = 'denda_per_hari_persen'");
                $stmtDenda->execute();
                $dendaPersen = floatval($stmtDenda->fetchColumn() ?: 10);

                // Denda keterlambatan
                $denda_telat = 0;
                if ($hari_terlambat > 0) {
                    $denda_telat = ($transaksi['total_biaya'] ?? $transaksi['total_bayar']) * ($dendaPersen / 100) * $hari_terlambat;
                }

                // Ambil pengaturan denda kerusakan dari pengaturan (baru)
                $stmtSet = $db->query("SELECT `key`, `value` FROM pengaturan WHERE `key` IN ('denda_rusak_ringan_persen', 'denda_rusak_berat_persen', 'denda_hilang_persen')");
                $settings = [];
                while ($row = $stmtSet->fetch()) {
                    $settings[$row['key']] = floatval($row['value']);
                }
                $pct_ringan = $settings['denda_rusak_ringan_persen'] ?? 25;
                $pct_berat  = $settings['denda_rusak_berat_persen'] ?? 50;
                $pct_hilang = $settings['denda_hilang_persen'] ?? 100;

                // Denda kondisi barang (Berdasarkan harga ganti/denda barang, bukan harga sewa)
                $denda_kondisi = 0;
                
                // Hitung total harga denda (harga ganti) dari semua barang di reservasi ini
                $stmtGanti = $db->prepare("
                    SELECT SUM(b.harga_denda * dr.jumlah) as total_ganti
                    FROM detail_reservasi dr
                    JOIN barang b ON dr.barang_id = b.id
                    WHERE dr.reservasi_id = ?
                ");
                $stmtGanti->execute([$transaksi['reservasi_id'] ?? 0]);
                $total_ganti = (float) $stmtGanti->fetchColumn();

                switch ($kondisi_barang) {
                    case 'rusak_ringan':
                        $denda_kondisi = $total_ganti * ($pct_ringan / 100);
                        break;
                    case 'rusak_berat':
                        $denda_kondisi = $total_ganti * ($pct_berat / 100);
                        break;
                    case 'hilang':
                        $denda_kondisi = $total_ganti * ($pct_hilang / 100);
                        break;
                }

                $total_denda = $denda_telat + $denda_kondisi;

                jsonSuccess([
                    'tanggal_selesai' => $tanggal_selesai,
                    'tanggal_kembali' => $tanggal_kembali,
                    'hari_terlambat' => $hari_terlambat,
                    'kondisi_barang' => $kondisi_barang,
                    'denda_keterlambatan' => $denda_telat,
                    'denda_kondisi' => $denda_kondisi,
                    'total_denda' => $total_denda
                ], 'Perhitungan denda pengembalian');
            } catch (PDOException $e) {
                error_log("API Pengembalian Hitung Error: " . $e->getMessage());
                jsonError('Gagal menghitung denda', 500);
            }
        }

        // === AJUKAN PENGEMBALIAN (pelanggan upload bukti foto) ===
        elseif ($action === 'ajukan') {
            // Pelanggan bisa mengajukan pengembalian
            $reservasi_id = intval($_POST['reservasi_id'] ?? 0);
            if ($reservasi_id <= 0) jsonError('ID reservasi tidak valid');

            try {
                // Cek reservasi milik user ini dan statusnya aktif
                $stmtRsv = $db->prepare("SELECT * FROM reservasi WHERE id = ? AND user_id = ? AND status = 'aktif'");
                $stmtRsv->execute([$reservasi_id, $user['id']]);
                $rsv = $stmtRsv->fetch();
                if (!$rsv) jsonError('Reservasi tidak ditemukan, bukan milik Anda, atau statusnya tidak aktif');

                // Upload foto bukti
                if (!isset($_FILES['bukti_foto']) || $_FILES['bukti_foto']['error'] !== UPLOAD_ERR_OK) {
                    jsonError('Foto bukti kondisi barang wajib diunggah');
                }

                $file = $_FILES['bukti_foto'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($realMime, $allowedTypes)) {
                    jsonError('Format file harus JPG, PNG, atau WEBP');
                }
                if ($file['size'] > 5 * 1024 * 1024) {
                    jsonError('Ukuran file maksimal 5MB');
                }

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts)) jsonError('Ekstensi file tidak valid');

                $filename = 'kembali_' . $reservasi_id . '_' . time() . '.' . $ext;
                $uploadDir = dirname(__DIR__) . '/uploads/pengembalian/';
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    jsonError('Gagal mengunggah file');
                }

                // Get transaksi_id for this reservasi
                $stmtTrx = $db->prepare("SELECT id FROM transaksi WHERE reservasi_id = ? ORDER BY id DESC LIMIT 1");
                $stmtTrx->execute([$reservasi_id]);
                $trx_id = $stmtTrx->fetchColumn();
                
                if (!$trx_id) jsonError('Transaksi tidak ditemukan untuk reservasi ini');

                // Update reservasi status
                $db->prepare("
                    UPDATE reservasi SET status = 'menunggu_cek'
                    WHERE id = ?
                ")->execute([$reservasi_id]);
                
                // Update transaksi status
                $db->prepare("
                    UPDATE transaksi SET status = 'menunggu_cek'
                    WHERE id = ?
                ")->execute([$trx_id]);

                // Insert into pengembalian
                $db->prepare("
                    INSERT INTO pengembalian (transaksi_id, tanggal_kembali, kondisi_barang, foto_bukti, status)
                    VALUES (?, NOW(), 'baik', ?, 'menunggu_cek')
                ")->execute([$trx_id, $filename]);

                // Notifikasi ke admin
                $stmtAdmins = $db->query("SELECT id FROM users WHERE role IN ('admin','superadmin')");
                foreach ($stmtAdmins->fetchAll() as $admin) {
                    $db->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                        VALUES (?, 'Pengajuan Pengembalian', ?, 'pengembalian', ?)
                    ")->execute([
                        $admin['id'],
                        "Pelanggan {$user['nama']} mengajukan pengembalian untuk reservasi {$rsv['kode_reservasi']}",
                        "?page=detail_reservasi&id=$reservasi_id"
                    ]);
                }

                jsonSuccess(['id' => $reservasi_id], 'Pengajuan pengembalian berhasil dikirim. Menunggu pengecekan admin.');
            } catch (PDOException $e) {
                error_log("API Pengembalian Ajukan Error: " . $e->getMessage());
                jsonError('Gagal mengajukan pengembalian', 500);
            }
        }

        // === CREATE PENGEMBALIAN (admin only - verifikasi) ===
        elseif ($action === 'create') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Hanya admin yang bisa memproses pengembalian', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $transaksi_id = intval($input['transaksi_id'] ?? 0);
            $tanggal_kembali = sanitize($input['tanggal_kembali'] ?? date('Y-m-d'));
            $kondisi_barang = sanitize($input['kondisi_barang'] ?? 'baik');
            $catatan = sanitize($input['catatan'] ?? '');

            // Validasi
            if ($transaksi_id <= 0) jsonError('ID transaksi tidak valid');
            if (!in_array($kondisi_barang, ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])) {
                jsonError('Kondisi barang tidak valid');
            }

            try {
                // Cek transaksi aktif
                $stmtTrx = $db->prepare("
                    SELECT t.*, r.tanggal_selesai, r.total_biaya, r.id AS rsv_id
                    FROM transaksi t
                    LEFT JOIN reservasi r ON t.reservasi_id = r.id
                    WHERE t.id = ? AND t.status IN ('aktif', 'dibayar')
                ");
                $stmtTrx->execute([$transaksi_id]);
                $transaksi = $stmtTrx->fetch();

                if (!$transaksi) jsonError('Transaksi tidak ditemukan atau sudah selesai');

                // Cek sudah pernah dikembalikan belum
                $stmtExist = $db->prepare("SELECT id, status FROM pengembalian WHERE transaksi_id = ?");
                $stmtExist->execute([$transaksi_id]);
                $pengembalianExist = $stmtExist->fetch();
                if ($pengembalianExist && $pengembalianExist['status'] !== 'menunggu_cek') {
                    jsonError('Pengembalian untuk transaksi ini sudah pernah diproses');
                }

                $db->beginTransaction();

                // Hitung denda
                $tanggal_selesai = $transaksi['tanggal_selesai'] ?? date('Y-m-d');
                $hari_terlambat = max(0, hitungHari($tanggal_selesai, $tanggal_kembali));

                $stmtDendaPersen = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = 'denda_per_hari_persen'");
                $stmtDendaPersen->execute();
                $dendaPersen = floatval($stmtDendaPersen->fetchColumn() ?: 10);

                $total_biaya = $transaksi['total_biaya'] ?? $transaksi['total_bayar'];
                $denda_telat = 0;
                if ($hari_terlambat > 0) {
                    $denda_telat = $total_biaya * ($dendaPersen / 100) * $hari_terlambat;
                }

                // Ambil pengaturan denda kerusakan
                $stmtSet = $db->query("SELECT `key`, `value` FROM pengaturan WHERE `key` IN ('denda_rusak_ringan_persen', 'denda_rusak_berat_persen', 'denda_hilang_persen')");
                $settings = [];
                while ($row = $stmtSet->fetch()) {
                    $settings[$row['key']] = floatval($row['value']);
                }
                $pct_ringan = $settings['denda_rusak_ringan_persen'] ?? 25;
                $pct_berat  = $settings['denda_rusak_berat_persen'] ?? 50;
                $pct_hilang = $settings['denda_hilang_persen'] ?? 100;

                // Hitung total harga denda (harga ganti) dari semua barang
                $stmtGanti = $db->prepare("
                    SELECT SUM(b.harga_denda * dr.jumlah) as total_ganti
                    FROM detail_reservasi dr
                    JOIN barang b ON dr.barang_id = b.id
                    WHERE dr.reservasi_id = ?
                ");
                $stmtGanti->execute([$transaksi['rsv_id'] ?? 0]);
                $total_ganti = (float) $stmtGanti->fetchColumn();

                $denda_kondisi = 0;
                switch ($kondisi_barang) {
                    case 'rusak_ringan': $denda_kondisi = $total_ganti * ($pct_ringan / 100); break;
                    case 'rusak_berat':  $denda_kondisi = $total_ganti * ($pct_berat / 100); break;
                    case 'hilang':       $denda_kondisi = $total_ganti * ($pct_hilang / 100); break;
                }

                $total_denda = $denda_telat + $denda_kondisi;

                $metode_denda = sanitize($input['metode_denda'] ?? 'transfer');

                // Tentukan status
                $status_akhir = 'selesai';
                if ($total_denda > 0 && $metode_denda === 'transfer') {
                    $status_akhir = 'menunggu_denda';
                }

                // Insert or Update pengembalian
                if ($pengembalianExist) {
                    $db->prepare("
                        UPDATE pengembalian 
                        SET tanggal_kembali = ?, hari_terlambat = ?, kondisi_barang = ?, denda = ?, catatan = ?, status = ?
                        WHERE id = ?
                    ")->execute([
                        $tanggal_kembali, $hari_terlambat, $kondisi_barang, $total_denda, $catatan, 
                        $status_akhir, $pengembalianExist['id']
                    ]);
                    $pengembalian_id = $pengembalianExist['id'];
                } else {
                    $db->prepare("
                        INSERT INTO pengembalian (transaksi_id, tanggal_kembali, hari_terlambat, kondisi_barang, denda, catatan, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $transaksi_id, $tanggal_kembali, $hari_terlambat,
                        $kondisi_barang, $total_denda, $catatan,
                        $status_akhir
                    ]);
                    $pengembalian_id = $db->lastInsertId();
                }

                // Update denda di transaksi
                $db->prepare("UPDATE transaksi SET denda = ?, status = ? WHERE id = ?")
                    ->execute([$total_denda, $status_akhir, $transaksi_id]);
                if ($transaksi['rsv_id']) {
                    $db->prepare("UPDATE reservasi SET status = ? WHERE id = ?")
                        ->execute([$status_akhir, $transaksi['rsv_id']]);
                }

                // Kembalikan stok barang (kecuali hilang)
                // Stok selalu dikembalikan saat barang fisik diterima, terlepas dari denda belum lunas (Opsi B)
                if ($transaksi['rsv_id'] && $kondisi_barang !== 'hilang') {
                    $stmtItems = $db->prepare("SELECT barang_id, jumlah FROM detail_reservasi WHERE reservasi_id = ?");
                    $stmtItems->execute([$transaksi['rsv_id']]);

                    foreach ($stmtItems->fetchAll() as $item) {
                        $db->prepare("UPDATE barang SET stok_tersedia = stok_tersedia + ? WHERE id = ?")
                            ->execute([$item['jumlah'], $item['barang_id']]);
                    }
                }

                // Update member level
                $poin_dapat = floor($transaksi['total_bayar'] / 10000);
                $db->prepare("
                    UPDATE member_level SET
                        total_transaksi = total_transaksi + 1,
                        total_sewa = total_sewa + ?,
                        poin = poin + ?
                    WHERE user_id = ?
                ")->execute([
                    $transaksi['total_bayar'],
                    $poin_dapat, // 1 poin per 10rb
                    $transaksi['user_id']
                ]);
                
                if ($poin_dapat > 0) {
                    $db->prepare("INSERT INTO riwayat_poin (user_id, jenis, jumlah, keterangan) VALUES (?, 'masuk', ?, ?)")
                       ->execute([$transaksi['user_id'], $poin_dapat, "Poin dari transaksi " . $transaksi['kode_transaksi']]);
                }
                // Auto-upgrade member level
                $stmtMember = $db->prepare("SELECT total_transaksi FROM member_level WHERE user_id = ?");
                $stmtMember->execute([$transaksi['user_id']]);
                $memberData = $stmtMember->fetch();

                if ($memberData) {
                    $stmtLevels = $db->prepare("
                        SELECT `key`, `value` FROM pengaturan
                        WHERE `key` IN ('bronze_min_transaksi', 'silver_min_transaksi', 'gold_min_transaksi')
                    ");
                    $stmtLevels->execute();
                    $levels = [];
                    foreach ($stmtLevels->fetchAll() as $l) {
                        $levels[$l['key']] = intval($l['value']);
                    }

                    $newLevel = 'regular';
                    if ($memberData['total_transaksi'] >= ($levels['gold_min_transaksi'] ?? 30)) {
                        $newLevel = 'gold';
                    } elseif ($memberData['total_transaksi'] >= ($levels['silver_min_transaksi'] ?? 15)) {
                        $newLevel = 'silver';
                    } elseif ($memberData['total_transaksi'] >= ($levels['bronze_min_transaksi'] ?? 5)) {
                        $newLevel = 'bronze';
                    }

                    $db->prepare("UPDATE member_level SET level = ? WHERE user_id = ?")
                        ->execute([$newLevel, $transaksi['user_id']]);
                }

                // Notifikasi ke pelanggan
                if ($total_denda > 0) {
                    $db->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                        VALUES (?, 'Ada Denda Pengembalian', ?, 'pengembalian', ?)
                    ")->execute([
                        $transaksi['user_id'],
                        "Barang untuk transaksi {$transaksi['kode_transaksi']} telah dicek. Ada denda sebesar " . formatRupiah($total_denda) . ". Silakan lakukan pembayaran denda.",
                        "?page=pesanan"
                    ]);
                } else {
                    $db->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                        VALUES (?, 'Barang Dikembalikan', ?, 'pengembalian', ?)
                    ")->execute([
                        $transaksi['user_id'],
                        "Pengembalian untuk transaksi {$transaksi['kode_transaksi']} telah diproses. Tidak ada denda. Terima kasih!",
                        "?page=pesanan"
                    ]);
                }

                $db->commit();

                jsonSuccess([
                    'id' => $pengembalian_id,
                    'hari_terlambat' => $hari_terlambat,
                    'denda' => $total_denda
                ], 'Pengembalian berhasil diproses');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Pengembalian Create Error: " . $e->getMessage());
                jsonError('Gagal memproses pengembalian', 500);
            }
        } 
        
        // === BAYAR DENDA (Upload Bukti oleh Pelanggan) ===
        elseif ($action === 'bayar_denda_transfer') {
            $transaksi_id = intval($_POST['transaksi_id'] ?? 0);
            if ($transaksi_id <= 0) jsonError('ID transaksi tidak valid');

            try {
                // Cek kepemilikan dan status
                $stmt = $db->prepare("SELECT id FROM transaksi WHERE id = ? AND user_id = ? AND status = 'menunggu_denda'");
                $stmt->execute([$transaksi_id, $user['id']]);
                if (!$stmt->fetch()) jsonError('Transaksi tidak ditemukan atau tidak dalam status menunggu denda');

                // Upload bukti
                if (!isset($_FILES['bukti_denda']) || $_FILES['bukti_denda']['error'] !== UPLOAD_ERR_OK) {
                    jsonError('Foto bukti transfer wajib diunggah');
                }

                $file = $_FILES['bukti_denda'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($realMime, $allowedTypes)) jsonError('Format file harus JPG, PNG, atau WEBP');
                if ($file['size'] > 5 * 1024 * 1024) jsonError('Ukuran maksimal 5MB');

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts)) jsonError('Ekstensi file tidak valid');

                $filename = 'denda_' . $transaksi_id . '_' . time() . '.' . $ext;
                $uploadDir = dirname(__DIR__) . '/uploads/pengembalian/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    jsonError('Gagal mengunggah file');
                }

                $db->beginTransaction();

                // Update pengembalian
                $db->prepare("UPDATE pengembalian SET bukti_denda = ?, status = 'menunggu_verifikasi_denda' WHERE transaksi_id = ?")
                   ->execute([$filename, $transaksi_id]);

                // Update transaksi & reservasi
                $db->prepare("UPDATE transaksi SET status = 'menunggu_verifikasi_denda' WHERE id = ?")->execute([$transaksi_id]);
                
                $stmtRsv = $db->prepare("SELECT reservasi_id FROM transaksi WHERE id = ?");
                $stmtRsv->execute([$transaksi_id]);
                $rsv_id = $stmtRsv->fetchColumn();
                if ($rsv_id) {
                    $db->prepare("UPDATE reservasi SET status = 'menunggu_verifikasi_denda' WHERE id = ?")->execute([$rsv_id]);
                }

                // Notifikasi ke Admin
                $stmtAdmins = $db->query("SELECT id FROM users WHERE role IN ('admin','superadmin')");
                foreach ($stmtAdmins->fetchAll() as $admin) {
                    $db->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                        VALUES (?, 'Bukti Denda Diunggah', ?, 'pengembalian', ?)
                    ")->execute([
                        $admin['id'],
                        "Pelanggan {$user['nama']} telah mengunggah bukti pembayaran denda untuk transaksi #{$transaksi_id}",
                        "?page=detail_reservasi&id=$rsv_id"
                    ]);
                }

                $db->commit();
                jsonSuccess([], 'Bukti denda berhasil diunggah. Menunggu verifikasi admin.');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Pengembalian Bayar Denda Error: " . $e->getMessage());
                jsonError('Gagal memproses pembayaran denda', 500);
            }
        }
        
        // === VERIFIKASI DENDA (Admin) ===
        elseif ($action === 'verifikasi_denda') {
            if (!in_array($user['role'], ['admin', 'superadmin'])) {
                jsonError('Hanya admin yang bisa memverifikasi denda', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $transaksi_id = intval($input['transaksi_id'] ?? 0);
            if ($transaksi_id <= 0) jsonError('ID transaksi tidak valid');

            try {
                $db->beginTransaction();
                
                // Update pengembalian
                $db->prepare("UPDATE pengembalian SET status = 'selesai' WHERE transaksi_id = ?")->execute([$transaksi_id]);
                
                // Update transaksi
                $db->prepare("UPDATE transaksi SET status = 'selesai' WHERE id = ?")->execute([$transaksi_id]);
                
                $stmtRsv = $db->prepare("SELECT reservasi_id, user_id FROM transaksi WHERE id = ?");
                $stmtRsv->execute([$transaksi_id]);
                $trxInfo = $stmtRsv->fetch();
                
                if ($trxInfo && $trxInfo['reservasi_id']) {
                    $db->prepare("UPDATE reservasi SET status = 'selesai' WHERE id = ?")->execute([$trxInfo['reservasi_id']]);
                    
                    // Notifikasi ke pelanggan
                    $db->prepare("
                        INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                        VALUES (?, 'Pembayaran Denda Dikonfirmasi', ?, 'pengembalian', ?)
                    ")->execute([
                        $trxInfo['user_id'],
                        "Pembayaran denda untuk transaksi Anda telah dikonfirmasi dan selesai. Terima kasih!",
                        "?page=pesanan"
                    ]);
                }

                $db->commit();
                jsonSuccess([], 'Pembayaran denda berhasil diverifikasi dan transaksi selesai.');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("API Verifikasi Denda Error: " . $e->getMessage());
                jsonError('Gagal memverifikasi denda', 500);
            }
        } else {
            jsonError('Action tidak valid. Gunakan: ajukan, create, hitung_denda, bayar_denda_transfer, verifikasi_denda');
        }
        break;

    default:
        jsonError('Method tidak diizinkan', 405);
}
