<?php
// api/barang.php
// API endpoint untuk manajemen barang (peralatan camping)

require_once dirname(__DIR__) . '/includes/auth.php';

// Cek autentikasi
if (!isLoggedIn()) jsonError('Unauthorized', 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($method) {
    // =============================================
    // GET: Ambil data barang
    // =============================================
    case 'GET':
        $db = Database::getInstance();

        if ($action === 'detail' || isset($_GET['id'])) {
            // Detail barang berdasarkan ID
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) jsonError('ID barang tidak valid');

            try {
                $stmt = $db->prepare("
                    SELECT b.*, k.nama AS kategori_nama, k.icon AS kategori_icon
                    FROM barang b
                    JOIN kategori k ON b.kategori_id = k.id
                    WHERE b.id = ?
                ");
                $stmt->execute([$id]);
                $barang = $stmt->fetch();

                if (!$barang) jsonError('Barang tidak ditemukan', 404);

                jsonSuccess($barang, 'Detail barang berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Barang Detail Error: " . $e->getMessage());
                jsonError('Gagal mengambil detail barang', 500);
            }
        } else {
            // List barang dengan filter, search, dan pagination
            $kategori_id = intval($_GET['kategori_id'] ?? 0);
            $status = sanitize($_GET['status'] ?? '');
            $search = sanitize($_GET['search'] ?? '');
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;

            try {
                // Bangun query dengan filter
                $where = [];
                $params = [];

                if ($kategori_id > 0) {
                    $where[] = 'b.kategori_id = ?';
                    $params[] = $kategori_id;
                }
                if (in_array($status, ['tersedia', 'habis', 'maintenance'])) {
                    $where[] = 'b.status = ?';
                    $params[] = $status;
                }
                if (!empty($search)) {
                    $where[] = '(b.nama LIKE ? OR b.deskripsi LIKE ?)';
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }

                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

                // Hitung total data
                $countStmt = $db->prepare("SELECT COUNT(*) FROM barang b $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                // Ambil data
                $stmt = $db->prepare("
                    SELECT b.*, k.nama AS kategori_nama, k.icon AS kategori_icon
                    FROM barang b
                    JOIN kategori k ON b.kategori_id = k.id
                    $whereClause
                    ORDER BY b.created_at DESC
                    LIMIT ? OFFSET ?
                ");

                $dataParams = array_merge($params, [$limit, $offset]);
                $stmt->execute($dataParams);
                $barang = $stmt->fetchAll();

                jsonSuccess([
                    'barang' => $barang,
                    'pagination' => [
                        'total' => (int) $total,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($total / $limit)
                    ]
                ], 'Data barang berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Barang List Error: " . $e->getMessage());
                jsonError('Gagal mengambil data barang', 500);
            }
        }
        break;

    // =============================================
    // POST: Create, Update, Delete barang
    // =============================================
    case 'POST':
        // Hanya admin/superadmin yang boleh
        if (!in_array($user['role'], ['admin', 'superadmin'])) {
            jsonError('Akses ditolak. Hanya admin yang bisa mengelola barang.', 403);
        }

        $db = Database::getInstance();

        // === CREATE ===
        if ($action === 'create') {
            $nama = sanitize($_POST['nama'] ?? '');
            $kategori_id = intval($_POST['kategori_id'] ?? 0);
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');
            $harga_per_hari = floatval($_POST['harga_per_hari'] ?? 0);
            $harga_denda = floatval($_POST['harga_denda'] ?? 0);
            $stok_total = intval($_POST['stok_total'] ?? 0);
            $status = sanitize($_POST['status'] ?? 'tersedia');

            // Validasi input
            if (empty($nama)) jsonError('Nama barang wajib diisi');
            if ($kategori_id <= 0) jsonError('Kategori wajib dipilih');
            if ($harga_per_hari <= 0) jsonError('Harga per hari harus lebih dari 0');
            if ($stok_total < 0) jsonError('Stok tidak boleh negatif');
            if (!in_array($status, ['tersedia', 'habis', 'maintenance'])) {
                $status = 'tersedia';
            }

            // Handle upload gambar
            $gambar = null;
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $gambar = uploadGambarBarang($_FILES['gambar']);
                if ($gambar === false) jsonError('Gagal mengupload gambar. Pastikan format JPG/PNG/WebP dan ukuran max 2MB.');
            }

            try {
                $stmt = $db->prepare("
                    INSERT INTO barang (kategori_id, nama, deskripsi, gambar, harga_per_hari, harga_denda, stok_total, stok_tersedia, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $kategori_id, $nama, $deskripsi, $gambar,
                    $harga_per_hari, $harga_denda, $stok_total, $stok_total, $status
                ]);

                $newId = $db->lastInsertId();

                // Log aktivitas
                logAktivitas($db, $user['id'], 'Tambah Barang', "Menambah barang: $nama", 'barang', $newId);

                jsonSuccess(['id' => $newId], 'Barang berhasil ditambahkan');
            } catch (PDOException $e) {
                error_log("API Barang Create Error: " . $e->getMessage());
                jsonError('Gagal menambahkan barang', 500);
            }
        }

        // === UPDATE ===
        elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) jsonError('ID barang tidak valid');

            $nama = sanitize($_POST['nama'] ?? '');
            $kategori_id = intval($_POST['kategori_id'] ?? 0);
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');
            $harga_per_hari = floatval($_POST['harga_per_hari'] ?? 0);
            $harga_denda = floatval($_POST['harga_denda'] ?? 0);
            $stok_total = intval($_POST['stok_total'] ?? 0);
            $status = sanitize($_POST['status'] ?? 'tersedia');

            // Validasi input
            if (empty($nama)) jsonError('Nama barang wajib diisi');
            if ($kategori_id <= 0) jsonError('Kategori wajib dipilih');
            if ($harga_per_hari <= 0) jsonError('Harga per hari harus lebih dari 0');
            if ($stok_total < 0) jsonError('Stok tidak boleh negatif');
            if (!in_array($status, ['tersedia', 'habis', 'maintenance'])) {
                $status = 'tersedia';
            }

            try {
                // Ambil data lama untuk hitung stok tersedia
                $stmtOld = $db->prepare("SELECT stok_total, stok_tersedia, gambar FROM barang WHERE id = ?");
                $stmtOld->execute([$id]);
                $old = $stmtOld->fetch();
                if (!$old) jsonError('Barang tidak ditemukan', 404);

                // Hitung stok tersedia: stok_baru - (stok_lama - tersedia_lama)
                $stok_terpakai = $old['stok_total'] - $old['stok_tersedia'];
                $stok_tersedia = max(0, $stok_total - $stok_terpakai);

                // Handle upload gambar baru
                $gambar = $old['gambar'];
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                    $newGambar = uploadGambarBarang($_FILES['gambar']);
                    if ($newGambar !== false) {
                        // Hapus gambar lama jika ada
                        if ($gambar) {
                            $oldPath = dirname(__DIR__) . '/frontend/img/barang/' . $gambar;
                            if (file_exists($oldPath)) unlink($oldPath);
                        }
                        $gambar = $newGambar;
                    }
                }

                $stmt = $db->prepare("
                    UPDATE barang SET
                        kategori_id = ?, nama = ?, deskripsi = ?, gambar = ?,
                        harga_per_hari = ?, harga_denda = ?, stok_total = ?, stok_tersedia = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $kategori_id, $nama, $deskripsi, $gambar,
                    $harga_per_hari, $harga_denda, $stok_total, $stok_tersedia, $status, $id
                ]);

                logAktivitas($db, $user['id'], 'Update Barang', "Mengupdate barang: $nama (ID: $id)", 'barang', $id);

                jsonSuccess(['id' => $id], 'Barang berhasil diperbarui');
            } catch (PDOException $e) {
                error_log("API Barang Update Error: " . $e->getMessage());
                jsonError('Gagal memperbarui barang', 500);
            }
        }

        // === DELETE ===
        elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) jsonError('ID barang tidak valid');

            try {
                // Cek apakah barang sedang digunakan di reservasi aktif
                $stmtCheck = $db->prepare("
                    SELECT COUNT(*) FROM detail_reservasi dr
                    JOIN reservasi r ON dr.reservasi_id = r.id
                    WHERE dr.barang_id = ? AND r.status IN ('pending', 'disetujui', 'aktif')
                ");
                $stmtCheck->execute([$id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    jsonError('Barang tidak bisa dihapus karena sedang digunakan dalam reservasi aktif');
                }

                // Ambil data untuk log dan hapus gambar
                $stmtGet = $db->prepare("SELECT nama, gambar FROM barang WHERE id = ?");
                $stmtGet->execute([$id]);
                $barang = $stmtGet->fetch();
                if (!$barang) jsonError('Barang tidak ditemukan', 404);

                // Hapus dari database
                $stmt = $db->prepare("DELETE FROM barang WHERE id = ?");
                $stmt->execute([$id]);

                // Hapus file gambar jika ada
                if ($barang['gambar']) {
                    $imgPath = dirname(__DIR__) . '/frontend/img/barang/' . $barang['gambar'];
                    if (file_exists($imgPath)) unlink($imgPath);
                }

                logAktivitas($db, $user['id'], 'Hapus Barang', "Menghapus barang: {$barang['nama']} (ID: $id)", 'barang', $id);

                jsonSuccess([], 'Barang berhasil dihapus');
            } catch (PDOException $e) {
                error_log("API Barang Delete Error: " . $e->getMessage());
                jsonError('Gagal menghapus barang', 500);
            }
        } else {
            jsonError('Action tidak valid. Gunakan: create, update, delete');
        }
        break;

    default:
        jsonError('Method tidak diizinkan', 405);
}

// =============================================
// Helper: Upload gambar barang
// =============================================
function uploadGambarBarang($file) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    // Validasi tipe file (server-side, tidak bisa di-spoof)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }

    // Validasi ukuran
    if ($file['size'] > $maxSize) {
        return false;
    }

    // Buat direktori jika belum ada
    $uploadDir = dirname(__DIR__) . '/frontend/img/barang/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate nama unik
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('brg_') . '.' . strtolower($ext);

    // Pindahkan file
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }

    return false;
}

// =============================================
// Helper: Log aktivitas
// =============================================
function logAktivitas($db, $userId, $aksi, $detail, $tabel = null, $recordId = null) {
    try {
        $stmt = $db->prepare("
            INSERT INTO log_aktivitas (user_id, aksi, detail, tabel, record_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $aksi, $detail, $tabel, $recordId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Log Aktivitas Error: " . $e->getMessage());
    }
}
