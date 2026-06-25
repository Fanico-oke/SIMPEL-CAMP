<?php
// classes/Konten.php
// Model untuk mengelola konten website (CMS sederhana)

require_once dirname(__DIR__) . '/config/database.php';

class Konten
{
    /**
     * Ambil nilai konten berdasarkan section dan key
     *
     * @param string      $section Section konten (hero, about, footer, dll)
     * @param string      $key     Key konten
     * @param mixed       $default Nilai default jika tidak ditemukan
     * @return mixed      Nilai konten atau default
     */
    public static function get($section, $key, $default = null)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT `value` FROM konten WHERE section = ? AND `key` = ?
            ");
            $stmt->execute([$section, $key]);
            $result = $stmt->fetchColumn();

            return $result !== false ? $result : $default;
        } catch (PDOException $e) {
            error_log("Konten::get error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Update atau insert (upsert) konten
     * Jika sudah ada = update, belum ada = insert
     *
     * @param string $section
     * @param string $key
     * @param string $value
     * @return bool
     */
    public static function set($section, $key, $value)
    {
        try {
            $db = Database::getInstance();

            // Gunakan INSERT ... ON DUPLICATE KEY UPDATE (upsert)
            $stmt = $db->prepare("
                INSERT INTO konten (section, `key`, `value`, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()
            ");
            $stmt->execute([$section, $key, $value]);

            return true;
        } catch (PDOException $e) {
            error_log("Konten::set error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil semua konten untuk section tertentu sebagai key => value array
     *
     * @param string $section
     * @return array Associative array [key => value]
     */
    public static function getBySection($section)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT `key`, `value` FROM konten WHERE section = ? ORDER BY `key` ASC
            ");
            $stmt->execute([$section]);
            $rows = $stmt->fetchAll();

            // Konversi menjadi key => value array
            $result = [];
            foreach ($rows as $row) {
                $result[$row['key']] = $row['value'];
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Konten::getBySection error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil semua konten, dikelompokkan berdasarkan section
     *
     * @return array Associative array [section => [key => value, ...]]
     */
    public static function getAll()
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT section, `key`, `value` FROM konten ORDER BY section ASC, `key` ASC
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll();

            // Kelompokkan berdasarkan section
            $grouped = [];
            foreach ($rows as $row) {
                $grouped[$row['section']][$row['key']] = $row['value'];
            }

            return $grouped;
        } catch (PDOException $e) {
            error_log("Konten::getAll error: " . $e->getMessage());
            return [];
        }
    }
}
