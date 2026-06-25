<?php
// classes/Kategori.php
// Model untuk tabel kategori

require_once dirname(__DIR__) . '/config/database.php';

class Kategori {

    /**
     * Ambil semua kategori dengan jumlah barang
     */
    public static function getAll() {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("
                SELECT k.*, COUNT(b.id) AS jumlah_barang
                FROM kategori k
                LEFT JOIN barang b ON k.id = b.kategori_id
                GROUP BY k.id
                ORDER BY k.nama ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Kategori::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil kategori berdasarkan ID
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM kategori WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Kategori::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Tambah kategori baru
     */
    public static function create($data) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO kategori (nama, deskripsi, icon) VALUES (:nama, :deskripsi, :icon)");
            $stmt->execute([
                ':nama'      => $data['nama'],
                ':deskripsi' => $data['deskripsi'] ?? null,
                ':icon'      => $data['icon'] ?? 'bi-box'
            ]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Kategori::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update kategori
     */
    public static function update($id, $data) {
        try {
            $db = Database::getInstance();
            $fields = [];
            $params = [':id' => $id];

            $allowed = ['nama', 'deskripsi', 'icon'];
            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $fields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }

            if (empty($fields)) return false;

            $stmt = $db->prepare("UPDATE kategori SET " . implode(', ', $fields) . " WHERE id = :id");
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Kategori::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus kategori (hanya jika tidak ada barang terkait)
     */
    public static function delete($id) {
        try {
            $db = Database::getInstance();

            // Cek apakah ada barang terkait
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM barang WHERE kategori_id = :id");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();

            if ((int)$result['total'] > 0) {
                return false; // Tidak bisa hapus, masih ada barang terkait
            }

            $stmt = $db->prepare("DELETE FROM kategori WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Kategori::delete error: " . $e->getMessage());
            return false;
        }
    }
}
