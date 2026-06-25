<?php
// classes/Notifikasi.php
// Model untuk mengelola notifikasi pengguna

require_once dirname(__DIR__) . '/config/database.php';

class Notifikasi
{
    /**
     * Buat notifikasi baru
     *
     * @param int         $userId ID user penerima
     * @param string      $judul  Judul notifikasi
     * @param string      $pesan  Isi pesan notifikasi
     * @param string      $tipe   Tipe: info, success, warning, danger
     * @param string|null $link   URL terkait (opsional)
     * @return int|false  ID notifikasi baru atau false jika gagal
     */
    public static function create($userId, $judul, $pesan, $tipe = 'info', $link = null)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO notifikasi (user_id, judul, pesan, tipe, link)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $judul, $pesan, $tipe, $link]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Notifikasi::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil semua notifikasi milik user, terbaru terlebih dahulu
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getByUser($userId, $limit = 20, $offset = 0)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM notifikasi
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, (int)$limit, (int)$offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Notifikasi::getByUser error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca
     *
     * @param int $id ID notifikasi
     * @return bool
     */
    public static function markRead($id)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE notifikasi SET is_read = 1 WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Notifikasi::markRead error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tandai semua notifikasi user sebagai sudah dibaca
     *
     * @param int $userId
     * @return bool
     */
    public static function markAllRead($userId)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE notifikasi SET is_read = 1 WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            return true;
        } catch (PDOException $e) {
            error_log("Notifikasi::markAllRead error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hitung jumlah notifikasi belum dibaca untuk user
     *
     * @param int $userId
     * @return int
     */
    public static function countUnread($userId)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Notifikasi::countUnread error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Hapus satu notifikasi
     *
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM notifikasi WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Notifikasi::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus semua notifikasi yang sudah dibaca milik user
     *
     * @param int $userId
     * @return int Jumlah notifikasi yang dihapus
     */
    public static function deleteAllRead($userId)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                DELETE FROM notifikasi WHERE user_id = ? AND is_read = 1
            ");
            $stmt->execute([$userId]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Notifikasi::deleteAllRead error: " . $e->getMessage());
            return 0;
        }
    }
}
