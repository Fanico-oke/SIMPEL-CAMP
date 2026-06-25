<?php
// classes/Pengaturan.php
// Model untuk mengelola pengaturan/konfigurasi sistem

require_once dirname(__DIR__) . '/config/database.php';

class Pengaturan
{
    /**
     * Ambil nilai pengaturan berdasarkan key
     *
     * @param string      $key     Key pengaturan
     * @param mixed       $default Nilai default jika key tidak ditemukan
     * @return mixed      Nilai pengaturan atau default
     */
    public static function get($key, $default = null)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetchColumn();

            return $result !== false ? $result : $default;
        } catch (PDOException $e) {
            error_log("Pengaturan::get error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Update nilai pengaturan berdasarkan key
     *
     * @param string $key   Key pengaturan
     * @param string $value Nilai baru
     * @return bool
     */
    public static function set($key, $value)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE pengaturan SET `value` = ? WHERE `key` = ?");
            $stmt->execute([$value, $key]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Pengaturan::set error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil semua pengaturan dalam satu kategori
     *
     * @param string $kategori Nama kategori (umum, sewa, member, sistem)
     * @return array Array of pengaturan rows
     */
    public static function getByKategori($kategori)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM pengaturan WHERE kategori = ? ORDER BY `key` ASC
            ");
            $stmt->execute([$kategori]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Pengaturan::getByKategori error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil semua pengaturan, dikelompokkan berdasarkan kategori
     *
     * @return array Associative array [kategori => [rows...]]
     */
    public static function getAll()
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM pengaturan ORDER BY kategori ASC, `key` ASC");
            $stmt->execute();
            $rows = $stmt->fetchAll();

            // Kelompokkan berdasarkan kategori
            $grouped = [];
            foreach ($rows as $row) {
                $grouped[$row['kategori']][] = $row;
            }

            return $grouped;
        } catch (PDOException $e) {
            error_log("Pengaturan::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buat pengaturan baru
     *
     * @param string      $key       Key pengaturan (unik)
     * @param string      $value     Nilai pengaturan
     * @param string      $kategori  Kategori (default: umum)
     * @param string|null $deskripsi Deskripsi pengaturan
     * @return array Hasil operasi
     */
    public static function create($key, $value, $kategori = 'umum', $deskripsi = null)
    {
        try {
            $db = Database::getInstance();

            // Cek duplikasi key
            $existing = self::get($key);
            if ($existing !== null) {
                return ['success' => false, 'message' => 'Key pengaturan sudah ada'];
            }

            $stmt = $db->prepare("
                INSERT INTO pengaturan (`key`, `value`, kategori, deskripsi)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$key, $value, $kategori, $deskripsi]);

            return [
                'success' => true,
                'message' => 'Pengaturan berhasil ditambahkan',
                'data' => ['id' => $db->lastInsertId()]
            ];
        } catch (PDOException $e) {
            error_log("Pengaturan::create error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menambahkan pengaturan'];
        }
    }
}
