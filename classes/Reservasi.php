<?php
// classes/Reservasi.php
// Model untuk tabel reservasi & detail_reservasi

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/Barang.php';

class Reservasi {

    /**
     * Buat reservasi baru dengan detail items
     * @param int $userId
     * @param array $data [tanggal_mulai, tanggal_selesai, catatan, promo_id, diskon]
     * @param array $items [{barang_id, jumlah}]
     * @return int|false Reservasi ID
     */
    public static function create($userId, $data, $items) {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // Hitung durasi hari
            $start = new DateTime($data['tanggal_mulai']);
            $end   = new DateTime($data['tanggal_selesai']);
            $durasi = $end->diff($start)->days;
            if ($durasi < 1) $durasi = 1;

            // Generate kode reservasi unik
            $kodeReservasi = self::generateKode();

            // Hitung total biaya dari items
            $totalBiaya = 0;
            $itemDetails = [];

            foreach ($items as $item) {
                $barang = Barang::getById($item['barang_id']);
                if (!$barang) {
                    $db->rollBack();
                    return false;
                }

                // Cek stok tersedia
                if ($barang['stok_tersedia'] < $item['jumlah']) {
                    $db->rollBack();
                    return false;
                }

                $hargaSatuan = $barang['harga_per_hari'];
                $subtotal = $hargaSatuan * $item['jumlah'] * $durasi;
                $totalBiaya += $subtotal;

                $itemDetails[] = [
                    'barang_id'    => $item['barang_id'],
                    'jumlah'       => $item['jumlah'],
                    'harga_satuan' => $hargaSatuan,
                    'subtotal'     => $subtotal
                ];
            }

            // Hitung deposit (dari pengaturan)
            $depositPersen = 30; // Default 30%
            try {
                $stmtDep = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = 'deposit_persen'");
                $stmtDep->execute();
                $depRow = $stmtDep->fetch();
                if ($depRow) $depositPersen = (int)$depRow['value'];
            } catch (PDOException $e) {
                // Gunakan default
            }
            $deposit = $totalBiaya * $depositPersen / 100;

            // Hitung diskon
            $diskon = (float)($data['diskon'] ?? 0);
            $totalBiaya -= $diskon;
            if ($totalBiaya < 0) $totalBiaya = 0;

            // Insert reservasi
            $stmt = $db->prepare("
                INSERT INTO reservasi (kode_reservasi, user_id, tanggal_mulai, tanggal_selesai, total_biaya, deposit, promo_id, diskon, status, catatan, created_at)
                VALUES (:kode, :user_id, :mulai, :selesai, :total, :deposit, :promo_id, :diskon, 'pending', :catatan, NOW())
            ");
            $stmt->execute([
                ':kode'     => $kodeReservasi,
                ':user_id'  => $userId,
                ':mulai'    => $data['tanggal_mulai'],
                ':selesai'  => $data['tanggal_selesai'],
                ':total'    => $totalBiaya,
                ':deposit'  => $deposit,
                ':promo_id' => $data['promo_id'] ?? null,
                ':diskon'   => $diskon,
                ':catatan'  => $data['catatan'] ?? null
            ]);
            $reservasiId = $db->lastInsertId();

            // Insert detail reservasi & kurangi stok
            $stmtDetail = $db->prepare("
                INSERT INTO detail_reservasi (reservasi_id, barang_id, jumlah, harga_satuan, subtotal)
                VALUES (:reservasi_id, :barang_id, :jumlah, :harga_satuan, :subtotal)
            ");

            foreach ($itemDetails as $detail) {
                $stmtDetail->execute([
                    ':reservasi_id' => $reservasiId,
                    ':barang_id'    => $detail['barang_id'],
                    ':jumlah'       => $detail['jumlah'],
                    ':harga_satuan' => $detail['harga_satuan'],
                    ':subtotal'     => $detail['subtotal']
                ]);

                // Kurangi stok tersedia
                Barang::updateStok($detail['barang_id'], $detail['jumlah'], 'kurang');
            }

            $db->commit();
            return $reservasiId;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Reservasi::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate kode reservasi unik
     */
    private static function generateKode() {
        $db = Database::getInstance();
        do {
            $kode = 'RSV-' . strtoupper(substr(uniqid(), -8));
            $stmt = $db->prepare("SELECT COUNT(*) as c FROM reservasi WHERE kode_reservasi = :kode");
            $stmt->execute([':kode' => $kode]);
        } while ($stmt->fetch()['c'] > 0);
        return $kode;
    }

    /**
     * Ambil reservasi berdasarkan ID (dengan user info)
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT r.*, u.nama AS user_nama, u.email AS user_email, u.no_telp AS user_telp
                FROM reservasi r
                JOIN users u ON r.user_id = u.id
                WHERE r.id = :id
            ");
            $stmt->execute([':id' => $id]);
            $reservasi = $stmt->fetch();

            if ($reservasi) {
                $reservasi['details'] = self::getDetail($id);
            }

            return $reservasi ?: null;
        } catch (PDOException $e) {
            error_log("Reservasi::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil semua reservasi untuk user tertentu
     */
    public static function getByUser($userId, $status = null) {
        try {
            $db = Database::getInstance();
            $params = [':user_id' => $userId];
            $statusClause = '';

            if ($status) {
                $statusClause = "AND r.status = :status";
                $params[':status'] = $status;
            }

            $stmt = $db->prepare("
                SELECT r.*, u.nama AS user_nama
                FROM reservasi r
                JOIN users u ON r.user_id = u.id
                WHERE r.user_id = :user_id {$statusClause}
                ORDER BY r.created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Reservasi::getByUser error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil semua reservasi (untuk admin)
     */
    public static function getAll($status = null, $search = null, $limit = 20, $offset = 0) {
        try {
            $db = Database::getInstance();
            $where = [];
            $params = [];

            if ($status) {
                $where[] = "r.status = :status";
                $params[':status'] = $status;
            }
            if ($search) {
                $where[] = "(r.kode_reservasi LIKE :search OR u.nama LIKE :search2)";
                $params[':search'] = "%{$search}%";
                $params[':search2'] = "%{$search}%";
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $db->prepare("
                SELECT r.*, u.nama AS user_nama, u.email AS user_email
                FROM reservasi r
                JOIN users u ON r.user_id = u.id
                {$whereClause}
                ORDER BY r.created_at DESC
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
            error_log("Reservasi::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Setujui reservasi
     */
    public static function approve($id) {
        return self::updateStatus($id, 'disetujui');
    }

    /**
     * Tolak reservasi dengan alasan
     */
    public static function reject($id, $alasan) {
        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Update status
            $stmt = $db->prepare("UPDATE reservasi SET status = 'ditolak', alasan_tolak = :alasan, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':alasan' => $alasan, ':id' => $id]);

            // Kembalikan stok
            self::restoreStok($id);

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Reservasi::reject error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update status reservasi
     */
    public static function updateStatus($id, $status) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE reservasi SET status = :status, updated_at = NOW() WHERE id = :id");
            return $stmt->execute([':status' => $status, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Reservasi::updateStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Batalkan reservasi dan kembalikan stok
     */
    public static function cancel($id) {
        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE reservasi SET status = 'batal', updated_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $id]);

            self::restoreStok($id);

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Reservasi::cancel error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kembalikan stok barang dari detail reservasi
     */
    private static function restoreStok($reservasiId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT barang_id, jumlah FROM detail_reservasi WHERE reservasi_id = :id");
        $stmt->execute([':id' => $reservasiId]);
        $details = $stmt->fetchAll();

        foreach ($details as $detail) {
            Barang::updateStok($detail['barang_id'], $detail['jumlah'], 'tambah');
        }
    }

    /**
     * Hitung jumlah reservasi
     */
    public static function count($filters = null) {
        try {
            $db = Database::getInstance();
            // Support both array and string parameter
            $status = null;
            if (is_array($filters)) {
                $status = $filters['status'] ?? null;
            } elseif (is_string($filters) && !empty($filters)) {
                $status = $filters;
            }
            
            if ($status) {
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM reservasi WHERE status = :status");
                $stmt->execute([':status' => $status]);
            } else {
                $stmt = $db->query("SELECT COUNT(*) as total FROM reservasi");
            }
            return (int)$stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Reservasi::count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ambil detail reservasi (items barang)
     */
    public static function getDetail($reservasiId) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT dr.*, b.nama AS barang_nama, b.gambar AS barang_gambar, b.harga_per_hari, k.nama AS kategori_nama
                FROM detail_reservasi dr
                JOIN barang b ON dr.barang_id = b.id
                LEFT JOIN kategori k ON b.kategori_id = k.id
                WHERE dr.reservasi_id = :id
            ");
            $stmt->execute([':id' => $reservasiId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Reservasi::getDetail error: " . $e->getMessage());
            return [];
        }
    }
}
