<?php
// classes/Transaksi.php
// Model untuk tabel transaksi

require_once dirname(__DIR__) . '/config/database.php';

class Transaksi {

    /**
     * Buat transaksi dari reservasi yang disetujui
     */
    public static function createFromReservasi($reservasiId) {
        try {
            $db = Database::getInstance();

            // Ambil data reservasi
            $stmt = $db->prepare("SELECT * FROM reservasi WHERE id = :id AND status = 'disetujui'");
            $stmt->execute([':id' => $reservasiId]);
            $reservasi = $stmt->fetch();

            if (!$reservasi) return false;

            // Generate kode transaksi
            $kodeTransaksi = self::generateKode();

            $stmt = $db->prepare("
                INSERT INTO transaksi (kode_transaksi, reservasi_id, user_id, tipe, total_bayar, status, created_at)
                VALUES (:kode, :reservasi_id, :user_id, 'online', :total, 'menunggu_bayar', NOW())
            ");
            $stmt->execute([
                ':kode'         => $kodeTransaksi,
                ':reservasi_id' => $reservasiId,
                ':user_id'      => $reservasi['user_id'],
                ':total'        => $reservasi['total_biaya']
            ]);

            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Transaksi::createFromReservasi error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buat transaksi walk-in (langsung di toko)
     */
    public static function createWalkIn($userId, $data, $items) {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // Buat reservasi dulu
            require_once __DIR__ . '/Reservasi.php';
            $reservasiId = Reservasi::create($userId, [
                'tanggal_mulai'  => $data['tanggal_mulai'],
                'tanggal_selesai'=> $data['tanggal_selesai'],
                'catatan'        => $data['catatan'] ?? 'Transaksi langsung (walk-in)'
            ], $items);

            if (!$reservasiId) {
                $db->rollBack();
                return false;
            }

            // Langsung approve reservasi
            $stmt = $db->prepare("UPDATE reservasi SET status = 'aktif' WHERE id = :id");
            $stmt->execute([':id' => $reservasiId]);

            // Ambil total biaya
            $stmt = $db->prepare("SELECT total_biaya FROM reservasi WHERE id = :id");
            $stmt->execute([':id' => $reservasiId]);
            $reservasi = $stmt->fetch();

            // Generate kode transaksi
            $kodeTransaksi = self::generateKode();

            // Buat transaksi langsung aktif
            $stmt = $db->prepare("
                INSERT INTO transaksi (kode_transaksi, reservasi_id, user_id, tipe, total_bayar, status, created_at)
                VALUES (:kode, :reservasi_id, :user_id, 'walk_in', :total, 'aktif', NOW())
            ");
            $stmt->execute([
                ':kode'         => $kodeTransaksi,
                ':reservasi_id' => $reservasiId,
                ':user_id'      => $userId,
                ':total'        => $reservasi['total_biaya']
            ]);

            $transaksiId = $db->lastInsertId();
            $db->commit();
            return $transaksiId;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Transaksi::createWalkIn error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate kode transaksi unik
     */
    private static function generateKode() {
        $db = Database::getInstance();
        do {
            $kode = 'TRX-' . strtoupper(substr(uniqid(), -8));
            $stmt = $db->prepare("SELECT COUNT(*) as c FROM transaksi WHERE kode_transaksi = :kode");
            $stmt->execute([':kode' => $kode]);
        } while ($stmt->fetch()['c'] > 0);
        return $kode;
    }

    /**
     * Ambil transaksi berdasarkan ID
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT t.*, u.nama AS user_nama, u.email AS user_email, u.no_telp AS user_telp,
                       r.kode_reservasi, r.tanggal_mulai, r.tanggal_selesai, r.deposit
                FROM transaksi t
                JOIN users u ON t.user_id = u.id
                LEFT JOIN reservasi r ON t.reservasi_id = r.id
                WHERE t.id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Transaksi::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil transaksi berdasarkan user
     */
    public static function getByUser($userId, $status = null) {
        try {
            $db = Database::getInstance();
            $params = [':user_id' => $userId];
            $statusClause = '';

            if ($status) {
                $statusClause = "AND t.status = :status";
                $params[':status'] = $status;
            }

            $stmt = $db->prepare("
                SELECT t.*, r.kode_reservasi, r.tanggal_mulai, r.tanggal_selesai
                FROM transaksi t
                LEFT JOIN reservasi r ON t.reservasi_id = r.id
                WHERE t.user_id = :user_id {$statusClause}
                ORDER BY t.created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Transaksi::getByUser error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil semua transaksi dengan filter
     */
    public static function getAll($filters = []) {
        try {
            $db = Database::getInstance();
            $where = [];
            $params = [];

            if (!empty($filters['tipe'])) {
                $where[] = "t.tipe = :tipe";
                $params[':tipe'] = $filters['tipe'];
            }
            if (!empty($filters['status'])) {
                $where[] = "t.status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['search'])) {
                $where[] = "(t.kode_transaksi LIKE :search OR u.nama LIKE :search2)";
                $params[':search'] = "%{$filters['search']}%";
                $params[':search2'] = "%{$filters['search']}%";
            }
            if (!empty($filters['start_date'])) {
                $where[] = "t.created_at >= :start_date";
                $params[':start_date'] = $filters['start_date'] . ' 00:00:00';
            }
            if (!empty($filters['end_date'])) {
                $where[] = "t.created_at <= :end_date";
                $params[':end_date'] = $filters['end_date'] . ' 23:59:59';
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $limit  = (int)($filters['limit'] ?? 20);
            $offset = (int)($filters['offset'] ?? 0);

            $stmt = $db->prepare("
                SELECT t.*, u.nama AS user_nama, u.email AS user_email,
                       r.kode_reservasi, r.tanggal_mulai, r.tanggal_selesai
                FROM transaksi t
                JOIN users u ON t.user_id = u.id
                LEFT JOIN reservasi r ON t.reservasi_id = r.id
                {$whereClause}
                ORDER BY t.created_at DESC
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
            error_log("Transaksi::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update status transaksi
     */
    public static function updateStatus($id, $status) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE transaksi SET status = :status, updated_at = NOW() WHERE id = :id");
            return $stmt->execute([':status' => $status, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Transaksi::updateStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hitung jumlah transaksi
     */
    public static function count($status = null, $tipe = null) {
        try {
            $db = Database::getInstance();
            $where = [];
            $params = [];

            if ($status) {
                $where[] = "status = :status";
                $params[':status'] = $status;
            }
            if ($tipe) {
                $where[] = "tipe = :tipe";
                $params[':tipe'] = $tipe;
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $db->prepare("SELECT COUNT(*) as total FROM transaksi {$whereClause}");
            $stmt->execute($params);
            return (int)$stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Transaksi::count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Total pendapatan (transaksi selesai)
     */
    public static function totalPendapatan($startDate = null, $endDate = null) {
        try {
            $db = Database::getInstance();
            $where = ["status = 'selesai'"];
            $params = [];

            if ($startDate) {
                $where[] = "created_at >= :start";
                $params[':start'] = $startDate . ' 00:00:00';
            }
            if ($endDate) {
                $where[] = "created_at <= :end";
                $params[':end'] = $endDate . ' 23:59:59';
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);

            $stmt = $db->prepare("SELECT COALESCE(SUM(total_bayar), 0) as total FROM transaksi {$whereClause}");
            $stmt->execute($params);
            return (float)$stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Transaksi::totalPendapatan error: " . $e->getMessage());
            return 0;
        }
    }
}
