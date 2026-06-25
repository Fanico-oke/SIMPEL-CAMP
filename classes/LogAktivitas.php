<?php
// classes/LogAktivitas.php
// Model untuk mencatat dan mengelola log aktivitas sistem

require_once dirname(__DIR__) . '/config/database.php';

class LogAktivitas
{
    /**
     * Catat log aktivitas baru
     * Otomatis mengambil IP address dan user agent dari $_SERVER
     *
     * @param int|null    $userId   ID user (null untuk guest/system)
     * @param string      $aksi     Nama aksi (login, logout, create_reservasi, dll)
     * @param string|null $detail   Detail tambahan
     * @param string|null $tabel    Nama tabel terkait
     * @param int|null    $recordId ID record terkait di tabel
     * @return int|false  ID log baru atau false
     */
    public static function log($userId, $aksi, $detail = null, $tabel = null, $recordId = null)
    {
        try {
            $db = Database::getInstance();

            // Ambil IP address & user agent dari request
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
                ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
                : null;

            $stmt = $db->prepare("
                INSERT INTO log_aktivitas (user_id, aksi, detail, tabel, record_id, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $aksi, $detail, $tabel, $recordId, $ipAddress, $userAgent]);

            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("LogAktivitas::log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil semua log dengan info user, mendukung berbagai filter
     *
     * @param array $filters [user_id, aksi, date_from, date_to, search, limit, offset]
     * @return array
     */
    public static function getAll($filters = [])
    {
        try {
            $db = Database::getInstance();

            $sql = "
                SELECT la.*, u.nama AS nama_user, u.email
                FROM log_aktivitas la
                LEFT JOIN users u ON la.user_id = u.id
                WHERE 1=1
            ";
            $params = [];

            // Filter berdasarkan user_id
            if (!empty($filters['user_id'])) {
                $sql .= " AND la.user_id = ?";
                $params[] = $filters['user_id'];
            }

            // Filter berdasarkan aksi
            if (!empty($filters['aksi'])) {
                $sql .= " AND la.aksi = ?";
                $params[] = $filters['aksi'];
            }

            // Filter berdasarkan rentang tanggal
            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(la.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(la.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            // Filter pencarian umum (di aksi, detail, nama user)
            if (!empty($filters['search'])) {
                $sql .= " AND (la.aksi LIKE ? OR la.detail LIKE ? OR u.nama LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }

            $sql .= " ORDER BY la.created_at DESC";

            // Limit & offset
            $limit  = isset($filters['limit']) ? (int)$filters['limit'] : 20;
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("LogAktivitas::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil log aktivitas berdasarkan user
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
                SELECT * FROM log_aktivitas
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, (int)$limit, (int)$offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("LogAktivitas::getByUser error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Hitung jumlah log dengan filter (untuk pagination)
     *
     * @param array $filters [user_id, aksi, date_from, date_to, search]
     * @return int
     */
    public static function count($filters = [])
    {
        try {
            $db = Database::getInstance();

            $sql = "
                SELECT COUNT(*) FROM log_aktivitas la
                LEFT JOIN users u ON la.user_id = u.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filters['user_id'])) {
                $sql .= " AND la.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['aksi'])) {
                $sql .= " AND la.aksi = ?";
                $params[] = $filters['aksi'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(la.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(la.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (la.aksi LIKE ? OR la.detail LIKE ? OR u.nama LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("LogAktivitas::count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Hapus log yang lebih lama dari N hari (pembersihan data lama)
     *
     * @param int $days Jumlah hari threshold
     * @return int Jumlah log yang dihapus
     */
    public static function cleanup($days = 90)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                DELETE FROM log_aktivitas
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([(int)$days]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("LogAktivitas::cleanup error: " . $e->getMessage());
            return 0;
        }
    }
}
