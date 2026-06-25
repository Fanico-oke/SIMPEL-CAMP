<?php
// classes/Pembayaran.php
// Model untuk tabel pembayaran

require_once dirname(__DIR__) . '/config/database.php';

class Pembayaran {

    /**
     * Buat pembayaran baru
     */
    public static function create($transaksiId, $data) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO pembayaran (transaksi_id, metode, jumlah, bukti_bayar, status, tanggal_bayar)
                VALUES (:transaksi_id, :metode, :jumlah, :bukti, 'pending', NOW())
            ");
            $stmt->execute([
                ':transaksi_id' => $transaksiId,
                ':metode'       => $data['metode'],
                ':jumlah'       => $data['jumlah'],
                ':bukti'        => $data['bukti_bayar'] ?? null
            ]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Pembayaran::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil pembayaran berdasarkan transaksi
     */
    public static function getByTransaksi($transaksiId) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM pembayaran WHERE transaksi_id = :id ORDER BY tanggal_bayar DESC");
            $stmt->execute([':id' => $transaksiId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Pembayaran::getByTransaksi error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil pembayaran berdasarkan ID
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM pembayaran WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Pembayaran::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Konfirmasi pembayaran + update status transaksi
     */
    public static function confirm($id) {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // Update status pembayaran
            $stmt = $db->prepare("UPDATE pembayaran SET status = 'dikonfirmasi', confirmed_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Ambil transaksi_id
            $stmt = $db->prepare("SELECT transaksi_id FROM pembayaran WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $pembayaran = $stmt->fetch();

            if ($pembayaran) {
                // Update status transaksi menjadi dibayar
                $stmt = $db->prepare("UPDATE transaksi SET status = 'dibayar', updated_at = NOW() WHERE id = :id");
                $stmt->execute([':id' => $pembayaran['transaksi_id']]);

                // Update status reservasi menjadi aktif
                $stmt = $db->prepare("
                    UPDATE reservasi SET status = 'aktif', updated_at = NOW()
                    WHERE id = (SELECT reservasi_id FROM transaksi WHERE id = :tid)
                ");
                $stmt->execute([':tid' => $pembayaran['transaksi_id']]);
            }

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Pembayaran::confirm error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tolak pembayaran
     */
    public static function reject($id, $catatan = null) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE pembayaran SET status = 'ditolak', catatan = :catatan WHERE id = :id");
            return $stmt->execute([':catatan' => $catatan, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Pembayaran::reject error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil semua pembayaran (untuk admin)
     */
    public static function getAll($status = null, $limit = 20, $offset = 0) {
        try {
            $db = Database::getInstance();
            $where = '';
            $params = [];

            if ($status) {
                $where = "WHERE p.status = :status";
                $params[':status'] = $status;
            }

            $stmt = $db->prepare("
                SELECT p.*, t.kode_transaksi, t.total_bayar AS total_transaksi, u.nama AS user_nama
                FROM pembayaran p
                JOIN transaksi t ON p.transaksi_id = t.id
                JOIN users u ON t.user_id = u.id
                {$where}
                ORDER BY p.tanggal_bayar DESC
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
            error_log("Pembayaran::getAll error: " . $e->getMessage());
            return [];
        }
    }
}
