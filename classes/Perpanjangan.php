<?php
// classes/Perpanjangan.php
// Model untuk tabel perpanjangan

require_once dirname(__DIR__) . '/config/database.php';

class Perpanjangan {

    /**
     * Buat permintaan perpanjangan sewa
     */
    public static function create($reservasiId, $data) {
        try {
            $db = Database::getInstance();

            // Ambil data reservasi untuk tanggal lama
            $stmt = $db->prepare("SELECT tanggal_selesai FROM reservasi WHERE id = :id");
            $stmt->execute([':id' => $reservasiId]);
            $reservasi = $stmt->fetch();

            if (!$reservasi) return false;

            // Hitung biaya tambahan
            $biayaTambahan = self::hitungBiaya($reservasiId, $data['tambahan_hari']);

            $stmt = $db->prepare("
                INSERT INTO perpanjangan (reservasi_id, tanggal_lama, tanggal_baru, tambahan_hari, biaya_tambahan, metode_bayar, bukti_bayar, alasan, status, created_at)
                VALUES (:reservasi_id, :lama, :baru, :hari, :biaya, :metode, :bukti, :alasan, 'pending', NOW())
            ");

            $tanggalBaru = date('Y-m-d', strtotime($reservasi['tanggal_selesai'] . " + {$data['tambahan_hari']} days"));

            $stmt->execute([
                ':reservasi_id' => $reservasiId,
                ':lama'         => $reservasi['tanggal_selesai'],
                ':baru'         => $tanggalBaru,
                ':hari'         => $data['tambahan_hari'],
                ':biaya'        => $biayaTambahan,
                ':metode'       => $data['metode_bayar'],
                ':bukti'        => $data['bukti_bayar'] ?? null,
                ':alasan'       => $data['alasan'] ?? null
            ]);

            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Perpanjangan::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil perpanjangan berdasarkan reservasi
     */
    public static function getByReservasi($reservasiId) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM perpanjangan WHERE reservasi_id = :id ORDER BY created_at DESC");
            $stmt->execute([':id' => $reservasiId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Perpanjangan::getByReservasi error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil semua perpanjangan (untuk admin)
     */
    public static function getAll($filters = null, $limit = 20, $offset = 0) {
        try {
            $db = Database::getInstance();
            $where = '';
            $params = [];

            // Support both array filters and legacy string parameter
            $status = null;
            if (is_array($filters)) {
                $status = $filters['status'] ?? null;
                $limit  = $filters['limit'] ?? $limit;
                $offset = $filters['offset'] ?? $offset;
            } elseif (is_string($filters) && !empty($filters)) {
                $status = $filters;
            }

            if ($status) {
                $where = "WHERE p.status = :status";
                $params[':status'] = $status;
            }

            $stmt = $db->prepare("
                SELECT p.*, r.kode_reservasi, u.nama AS user_nama, u.email AS user_email
                FROM perpanjangan p
                JOIN reservasi r ON p.reservasi_id = r.id
                JOIN users u ON r.user_id = u.id
                {$where}
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Perpanjangan::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Setujui perpanjangan + update tanggal_selesai di reservasi
     */
    public static function approve($id) {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // Ambil data perpanjangan
            $stmt = $db->prepare("SELECT * FROM perpanjangan WHERE id = :id AND status = 'pending'");
            $stmt->execute([':id' => $id]);
            $perpanjangan = $stmt->fetch();

            if (!$perpanjangan) {
                $db->rollBack();
                return false;
            }

            // Update status perpanjangan
            $stmt = $db->prepare("UPDATE perpanjangan SET status = 'disetujui', updated_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Update tanggal selesai di reservasi
            $stmt = $db->prepare("UPDATE reservasi SET tanggal_selesai = :tanggal, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                ':tanggal' => $perpanjangan['tanggal_baru'],
                ':id'      => $perpanjangan['reservasi_id']
            ]);

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Perpanjangan::approve error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tolak perpanjangan
     */
    public static function reject($id, $alasan) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE perpanjangan SET status = 'ditolak', alasan_tolak = :alasan, updated_at = NOW() WHERE id = :id");
            return $stmt->execute([':alasan' => $alasan, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Perpanjangan::reject error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil perpanjangan berdasarkan ID
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT p.*, r.kode_reservasi, u.nama AS user_nama
                FROM perpanjangan p
                JOIN reservasi r ON p.reservasi_id = r.id
                JOIN users u ON r.user_id = u.id
                WHERE p.id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Perpanjangan::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Hitung biaya perpanjangan tanpa menyimpan
     * biaya = sum(harga_satuan * jumlah) * tambahan_hari
     */
    public static function hitungBiaya($reservasiId, $tambahanHari) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT SUM(harga_satuan * jumlah) as biaya_per_hari
                FROM detail_reservasi
                WHERE reservasi_id = :id
            ");
            $stmt->execute([':id' => $reservasiId]);
            $result = $stmt->fetch();

            $biayaPerHari = (float)($result['biaya_per_hari'] ?? 0);
            return $biayaPerHari * (int)$tambahanHari;
        } catch (PDOException $e) {
            error_log("Perpanjangan::hitungBiaya error: " . $e->getMessage());
            return 0;
        }
    }
}
