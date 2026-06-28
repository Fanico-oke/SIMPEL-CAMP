<?php
// classes/Barang.php
// Model untuk tabel barang

require_once dirname(__DIR__) . '/config/database.php';

class Barang {

    /**
     * Ambil semua barang dengan filter opsional
     */
    public static function getAll($filters = []) {
        try {
            $db = Database::getInstance();
            $where = [];
            $params = [];

            if (!empty($filters['kategori_id'])) {
                $where[] = "b.kategori_id = :kategori_id";
                $params[':kategori_id'] = $filters['kategori_id'];
            }
            if (!empty($filters['status'])) {
                $where[] = "b.status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['search'])) {
                $where[] = "(b.nama LIKE :search OR b.deskripsi LIKE :search2)";
                $params[':search'] = "%{$filters['search']}%";
                $params[':search2'] = "%{$filters['search']}%";
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $limit  = (int)($filters['limit'] ?? 20);
            $offset = (int)($filters['offset'] ?? 0);

            $stmt = $db->prepare("
                SELECT b.*, k.nama AS kategori_nama, k.icon AS kategori_icon
                FROM barang b
                LEFT JOIN kategori k ON b.kategori_id = k.id
                {$whereClause}
                ORDER BY b.created_at DESC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Barang::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil barang berdasarkan ID
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT b.*, k.nama AS kategori_nama, k.icon AS kategori_icon
                FROM barang b
                LEFT JOIN kategori k ON b.kategori_id = k.id
                WHERE b.id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Barang::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Tambah barang baru
     */
    public static function create($data) {
        try {
            $db = Database::getInstance();
            $stokTotal = (int)($data['stok_total'] ?? 0);
            $stmt = $db->prepare("
                INSERT INTO barang (kategori_id, nama, deskripsi, gambar, harga_per_hari, stok_total, stok_tersedia, status, created_at)
                VALUES (:kategori_id, :nama, :deskripsi, :gambar, :harga, :stok_total, :stok_tersedia, :status, NOW())
            ");
            $stmt->execute([
                ':kategori_id'    => $data['kategori_id'],
                ':nama'           => $data['nama'],
                ':deskripsi'      => $data['deskripsi'] ?? null,
                ':gambar'         => $data['gambar'] ?? null,
                ':harga'          => $data['harga_per_hari'],
                ':stok_total'     => $stokTotal,
                ':stok_tersedia'  => $stokTotal, // Stok tersedia = stok total saat pertama dibuat
                ':status'         => $data['status'] ?? 'tersedia'
            ]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Barang::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update data barang
     */
    public static function update($id, $data) {
        try {
            $db = Database::getInstance();
            $fields = [];
            $params = [':id' => $id];

            $allowed = ['kategori_id', 'nama', 'deskripsi', 'gambar', 'harga_per_hari', 'stok_total', 'stok_tersedia', 'status'];
            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $fields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }

            if (empty($fields)) return false;

            $stmt = $db->prepare("UPDATE barang SET " . implode(', ', $fields) . " WHERE id = :id");
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Barang::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus barang (hanya jika tidak ada di reservasi aktif)
     */
    public static function delete($id) {
        try {
            $db = Database::getInstance();

            // Cek apakah ada di reservasi aktif
            $stmt = $db->prepare("
                SELECT COUNT(*) as total FROM detail_reservasi dr
                JOIN reservasi r ON dr.reservasi_id = r.id
                WHERE dr.barang_id = :id AND r.status IN ('pending','disetujui','aktif')
            ");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();

            if ((int)$result['total'] > 0) {
                return false; // Tidak bisa hapus, masih ada reservasi aktif
            }

            $stmt = $db->prepare("DELETE FROM barang WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Barang::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update stok tersedia (kurang/tambah)
     */
    public static function updateStok($id, $qty, $operasi = 'kurang') {
        try {
            $db = Database::getInstance();
            if ($operasi === 'kurang') {
                $stmt = $db->prepare("UPDATE barang SET stok_tersedia = GREATEST(stok_tersedia - :qty, 0) WHERE id = :id");
            } else {
                $stmt = $db->prepare("UPDATE barang SET stok_tersedia = LEAST(stok_tersedia + :qty, stok_total) WHERE id = :id");
            }
            $result = $stmt->execute([':qty' => (int)$qty, ':id' => $id]);

            // Update status berdasarkan stok
            $stmt = $db->prepare("
                UPDATE barang SET status = CASE 
                    WHEN stok_tersedia = 0 THEN 'habis'
                    ELSE 'tersedia'
                END WHERE id = :id AND status != 'maintenance'
            ");
            $stmt->execute([':id' => $id]);

            return $result;
        } catch (PDOException $e) {
            error_log("Barang::updateStok error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cari barang berdasarkan keyword
     */
    public static function search($keyword) {
        return self::getAll(['search' => $keyword, 'limit' => 50]);
    }

    /**
     * Hitung jumlah barang
     */
    public static function count($status = null) {
        try {
            $db = Database::getInstance();
            if ($status) {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM barang WHERE status = :status");
                $stmt->execute([':status' => $status]);
            } else {
                $stmt = $db->query("SELECT COUNT(*) as total FROM barang");
            }
            return (int)$stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Barang::count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Barang paling populer (paling sering disewa)
     */
    public static function getPopuler($limit = 5) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT b.*, k.nama AS kategori_nama, SUM(dr.jumlah) AS total_sewa
                FROM barang b
                JOIN detail_reservasi dr ON b.id = dr.barang_id
                JOIN reservasi r ON dr.reservasi_id = r.id
                LEFT JOIN kategori k ON b.kategori_id = k.id
                WHERE r.status IN ('aktif', 'selesai')
                GROUP BY b.id
                ORDER BY total_sewa DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Barang::getPopuler error: " . $e->getMessage());
            return [];
        }
    }
}
