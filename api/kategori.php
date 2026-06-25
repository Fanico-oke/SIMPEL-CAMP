<?php
// api/kategori.php
// API endpoint untuk manajemen kategori barang

require_once dirname(__DIR__) . '/includes/auth.php';

// Cek autentikasi
if (!isLoggedIn()) jsonError('Unauthorized', 401);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = currentUser();

switch ($method) {
    // =============================================
    // GET: Ambil data kategori
    // =============================================
    case 'GET':
        $db = Database::getInstance();

        if (isset($_GET['id'])) {
            // Detail kategori berdasarkan ID
            $id = intval($_GET['id']);
            if ($id <= 0) jsonError('ID kategori tidak valid');

            try {
                $stmt = $db->prepare("
                    SELECT k.*, COUNT(b.id) AS jumlah_barang
                    FROM kategori k
                    LEFT JOIN barang b ON k.id = b.kategori_id
                    WHERE k.id = ?
                    GROUP BY k.id
                ");
                $stmt->execute([$id]);
                $kategori = $stmt->fetch();

                if (!$kategori) jsonError('Kategori tidak ditemukan', 404);

                jsonSuccess($kategori, 'Detail kategori berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Kategori Detail Error: " . $e->getMessage());
                jsonError('Gagal mengambil detail kategori', 500);
            }
        } else {
            // List semua kategori dengan jumlah barang
            try {
                $stmt = $db->query("
                    SELECT k.*, COUNT(b.id) AS jumlah_barang
                    FROM kategori k
                    LEFT JOIN barang b ON k.id = b.kategori_id
                    GROUP BY k.id
                    ORDER BY k.nama ASC
                ");
                $kategori = $stmt->fetchAll();

                jsonSuccess($kategori, 'Data kategori berhasil diambil');
            } catch (PDOException $e) {
                error_log("API Kategori List Error: " . $e->getMessage());
                jsonError('Gagal mengambil data kategori', 500);
            }
        }
        break;

    // =============================================
    // POST: Create, Update, Delete kategori
    // =============================================
    case 'POST':
        // Hanya admin/superadmin
        if (!in_array($user['role'], ['admin', 'superadmin'])) {
            jsonError('Akses ditolak. Hanya admin yang bisa mengelola kategori.', 403);
        }

        $db = Database::getInstance();

        // === CREATE ===
        if ($action === 'create') {
            $nama = sanitize($_POST['nama'] ?? '');
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');
            $icon = sanitize($_POST['icon'] ?? 'bi-box');

            if (empty($nama)) jsonError('Nama kategori wajib diisi');

            try {
                // Cek duplikat nama
                $stmtCheck = $db->prepare("SELECT id FROM kategori WHERE nama = ?");
                $stmtCheck->execute([$nama]);
                if ($stmtCheck->fetch()) jsonError('Nama kategori sudah ada');

                $stmt = $db->prepare("INSERT INTO kategori (nama, deskripsi, icon) VALUES (?, ?, ?)");
                $stmt->execute([$nama, $deskripsi, $icon]);

                jsonSuccess(['id' => $db->lastInsertId()], 'Kategori berhasil ditambahkan');
            } catch (PDOException $e) {
                error_log("API Kategori Create Error: " . $e->getMessage());
                jsonError('Gagal menambahkan kategori', 500);
            }
        }

        // === UPDATE ===
        elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) jsonError('ID kategori tidak valid');

            $nama = sanitize($_POST['nama'] ?? '');
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');
            $icon = sanitize($_POST['icon'] ?? 'bi-box');

            if (empty($nama)) jsonError('Nama kategori wajib diisi');

            try {
                // Cek duplikat nama (kecuali diri sendiri)
                $stmtCheck = $db->prepare("SELECT id FROM kategori WHERE nama = ? AND id != ?");
                $stmtCheck->execute([$nama, $id]);
                if ($stmtCheck->fetch()) jsonError('Nama kategori sudah ada');

                $stmt = $db->prepare("UPDATE kategori SET nama = ?, deskripsi = ?, icon = ? WHERE id = ?");
                $stmt->execute([$nama, $deskripsi, $icon, $id]);

                if ($stmt->rowCount() === 0) jsonError('Kategori tidak ditemukan', 404);

                jsonSuccess(['id' => $id], 'Kategori berhasil diperbarui');
            } catch (PDOException $e) {
                error_log("API Kategori Update Error: " . $e->getMessage());
                jsonError('Gagal memperbarui kategori', 500);
            }
        }

        // === DELETE ===
        elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) jsonError('ID kategori tidak valid');

            try {
                // Cek apakah ada barang di kategori ini
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM barang WHERE kategori_id = ?");
                $stmtCheck->execute([$id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    jsonError('Kategori tidak bisa dihapus karena masih memiliki barang');
                }

                $stmt = $db->prepare("DELETE FROM kategori WHERE id = ?");
                $stmt->execute([$id]);

                if ($stmt->rowCount() === 0) jsonError('Kategori tidak ditemukan', 404);

                jsonSuccess([], 'Kategori berhasil dihapus');
            } catch (PDOException $e) {
                error_log("API Kategori Delete Error: " . $e->getMessage());
                jsonError('Gagal menghapus kategori', 500);
            }
        } else {
            jsonError('Action tidak valid. Gunakan: create, update, delete');
        }
        break;

    default:
        jsonError('Method tidak diizinkan', 405);
}
