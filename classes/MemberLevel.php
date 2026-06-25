<?php
// classes/MemberLevel.php
// Model untuk mengelola member level dan loyalty program

require_once dirname(__DIR__) . '/config/database.php';

class MemberLevel
{
    /**
     * Ambil data member level berdasarkan user ID
     *
     * @param int $userId
     * @return array|null
     */
    public static function getByUser($userId)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT ml.*, u.nama, u.email
                FROM member_level ml
                JOIN users u ON ml.user_id = u.id
                WHERE ml.user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("MemberLevel::getByUser error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buat record member level awal (level = regular)
     *
     * @param int $userId
     * @return int|false ID record baru atau false
     */
    public static function create($userId)
    {
        try {
            $db = Database::getInstance();

            // Cek apakah sudah ada
            $existing = self::getByUser($userId);
            if ($existing) {
                return $existing['id'];
            }

            $stmt = $db->prepare("
                INSERT INTO member_level (user_id, level, total_transaksi, total_sewa, poin)
                VALUES (?, 'regular', 0, 0, 0)
            ");
            $stmt->execute([$userId]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("MemberLevel::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tambahkan data transaksi ke member:
     * - Increment total_transaksi
     * - Tambahkan total_sewa
     * - Hitung poin (1 poin per Rp 10.000)
     *
     * @param int   $userId
     * @param float $totalSewa Nilai sewa dari transaksi
     * @return bool
     */
    public static function addTransaksi($userId, $totalSewa)
    {
        try {
            $db = Database::getInstance();

            // Pastikan record member ada
            self::create($userId);

            // Hitung poin: 1 poin per Rp 10.000
            $poinBaru = (int)floor($totalSewa / 10000);

            $stmt = $db->prepare("
                UPDATE member_level
                SET total_transaksi = total_transaksi + 1,
                    total_sewa = total_sewa + ?,
                    poin = poin + ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$totalSewa, $poinBaru, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("MemberLevel::addTransaksi error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cek apakah user berhak naik level berdasarkan threshold di pengaturan
     * Urutan: gold > silver > bronze > regular
     *
     * @param int $userId
     * @return string|null Level baru jika naik, null jika tidak berubah
     */
    public static function checkLevelUp($userId)
    {
        try {
            $db = Database::getInstance();

            // Ambil data member saat ini
            $member = self::getByUser($userId);
            if (!$member) {
                return null;
            }

            $totalTrx = (int)$member['total_transaksi'];
            $levelLama = $member['level'];

            // Ambil threshold dari pengaturan
            $thresholds = [];
            $stmt = $db->prepare("
                SELECT `key`, `value` FROM pengaturan
                WHERE `key` IN ('bronze_min_transaksi', 'silver_min_transaksi', 'gold_min_transaksi')
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll();

            foreach ($rows as $row) {
                $thresholds[$row['key']] = (int)$row['value'];
            }

            $bronzeMin = $thresholds['bronze_min_transaksi'] ?? 5;
            $silverMin = $thresholds['silver_min_transaksi'] ?? 15;
            $goldMin   = $thresholds['gold_min_transaksi'] ?? 30;

            // Tentukan level baru berdasarkan total transaksi
            $levelBaru = 'regular';
            if ($totalTrx >= $goldMin) {
                $levelBaru = 'gold';
            } elseif ($totalTrx >= $silverMin) {
                $levelBaru = 'silver';
            } elseif ($totalTrx >= $bronzeMin) {
                $levelBaru = 'bronze';
            }

            // Update jika level berubah
            if ($levelBaru !== $levelLama) {
                $stmt = $db->prepare("
                    UPDATE member_level SET level = ?, updated_at = NOW() WHERE user_id = ?
                ");
                $stmt->execute([$levelBaru, $userId]);
                return $levelBaru;
            }

            return null;
        } catch (PDOException $e) {
            error_log("MemberLevel::checkLevelUp error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil persentase diskon berdasarkan level member dari pengaturan
     *
     * @param int $userId
     * @return float Persentase diskon (0 jika regular)
     */
    public static function getDiskon($userId)
    {
        try {
            $db = Database::getInstance();

            // Ambil level member
            $member = self::getByUser($userId);
            if (!$member || $member['level'] === 'regular') {
                return 0;
            }

            // Ambil diskon dari pengaturan sesuai level
            $key = $member['level'] . '_diskon';
            $stmt = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = ?");
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();

            return $value !== false ? (float)$value : 0;
        } catch (PDOException $e) {
            error_log("MemberLevel::getDiskon error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ambil semua data member level dengan info user
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getAll($limit = 20, $offset = 0)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT ml.*, u.nama, u.email, u.no_telp, u.foto
                FROM member_level ml
                JOIN users u ON ml.user_id = u.id
                ORDER BY ml.total_sewa DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([(int)$limit, (int)$offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("MemberLevel::getAll error: " . $e->getMessage());
            return [];
        }
    }
}
