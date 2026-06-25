<?php
// classes/Promo.php
// Model untuk mengelola kode promo dan diskon

require_once dirname(__DIR__) . '/config/database.php';

class Promo
{
    /**
     * Ambil semua promo, dengan filter status opsional
     *
     * @param string|null $status Filter: aktif, nonaktif, expired
     * @return array
     */
    public static function getAll($status = null)
    {
        try {
            $db = Database::getInstance();

            $sql = "SELECT * FROM promo";
            $params = [];

            if ($status !== null) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Promo::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil promo berdasarkan ID
     *
     * @param int $id
     * @return array|null
     */
    public static function getById($id)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM promo WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Promo::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil promo berdasarkan kode
     *
     * @param string $kode
     * @return array|null
     */
    public static function getByKode($kode)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM promo WHERE kode = ?");
            $stmt->execute([strtoupper(trim($kode))]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Promo::getByKode error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buat promo baru
     *
     * @param array $data [kode, nama, tipe, nilai, min_transaksi, mulai, selesai, kuota]
     * @return array Hasil operasi
     */
    public static function create($data)
    {
        try {
            $db = Database::getInstance();

            // Cek duplikasi kode
            $existing = self::getByKode($data['kode']);
            if ($existing) {
                return ['success' => false, 'message' => 'Kode promo sudah digunakan'];
            }

            $stmt = $db->prepare("
                INSERT INTO promo (kode, nama, tipe, nilai, min_transaksi, mulai, selesai, kuota, gambar)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                strtoupper(trim($data['kode'])),
                $data['nama'],
                $data['tipe'] ?? 'persentase',
                $data['nilai'] ?? 0,
                $data['min_transaksi'] ?? 0,
                $data['mulai'],
                $data['selesai'],
                $data['kuota'] ?? 0,
                $data['gambar'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Promo berhasil dibuat',
                'data' => ['id' => $db->lastInsertId()]
            ];
        } catch (PDOException $e) {
            error_log("Promo::create error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal membuat promo'];
        }
    }

    /**
     * Update promo
     *
     * @param int   $id
     * @param array $data
     * @return array
     */
    public static function update($id, $data)
    {
        try {
            $db = Database::getInstance();

            // Cek promo ada
            $promo = self::getById($id);
            if (!$promo) {
                return ['success' => false, 'message' => 'Promo tidak ditemukan'];
            }

            // Cek duplikasi kode jika diubah
            if (isset($data['kode']) && strtoupper(trim($data['kode'])) !== $promo['kode']) {
                $existing = self::getByKode($data['kode']);
                if ($existing) {
                    return ['success' => false, 'message' => 'Kode promo sudah digunakan'];
                }
            }

            // Bangun query update dinamis
            $fields = [];
            $params = [];
            $allowed = ['kode', 'nama', 'tipe', 'nilai', 'min_transaksi', 'mulai', 'selesai', 'kuota', 'status', 'gambar'];

            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $value = $data[$field];
                    if ($field === 'kode') {
                        $value = strtoupper(trim($value));
                    }
                    $fields[] = "`{$field}` = ?";
                    $params[] = $value;
                }
            }

            if (empty($fields)) {
                return ['success' => false, 'message' => 'Tidak ada data yang diubah'];
            }

            $params[] = $id;
            $sql = "UPDATE promo SET " . implode(', ', $fields) . " WHERE id = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Promo berhasil diperbarui'];
        } catch (PDOException $e) {
            error_log("Promo::update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memperbarui promo'];
        }
    }

    /**
     * Hapus promo
     *
     * @param int $id
     * @return array
     */
    public static function delete($id)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM promo WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Promo tidak ditemukan'];
            }

            return ['success' => true, 'message' => 'Promo berhasil dihapus'];
        } catch (PDOException $e) {
            error_log("Promo::delete error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus promo'];
        }
    }

    /**
     * Validasi apakah promo dapat digunakan
     * Cek: status aktif, tanggal dalam range, kuota tersedia, min transaksi terpenuhi
     * Hitung diskon berdasarkan tipe (persentase/nominal)
     *
     * @param string $kode          Kode promo
     * @param float  $totalBelanja  Total belanja sebelum diskon
     * @return array Promo data + diskon terhitung, atau pesan error
     */
    public static function validatePromo($kode, $totalBelanja)
    {
        try {
            $promo = self::getByKode($kode);

            if (!$promo) {
                return ['success' => false, 'message' => 'Kode promo tidak ditemukan'];
            }

            // Cek status aktif
            if ($promo['status'] !== 'aktif') {
                return ['success' => false, 'message' => 'Promo sudah tidak aktif'];
            }

            // Cek tanggal berlaku
            $today = date('Y-m-d');
            if ($today < $promo['mulai']) {
                return ['success' => false, 'message' => 'Promo belum berlaku'];
            }
            if ($today > $promo['selesai']) {
                return ['success' => false, 'message' => 'Promo sudah berakhir'];
            }

            // Cek kuota
            if ($promo['kuota'] > 0 && $promo['terpakai'] >= $promo['kuota']) {
                return ['success' => false, 'message' => 'Kuota promo sudah habis'];
            }

            // Cek minimum transaksi
            if ((float)$totalBelanja < (float)$promo['min_transaksi']) {
                return [
                    'success' => false,
                    'message' => 'Minimum transaksi untuk promo ini adalah Rp ' .
                                 number_format($promo['min_transaksi'], 0, ',', '.')
                ];
            }

            // Hitung diskon
            $diskon = 0;
            if ($promo['tipe'] === 'persentase') {
                $diskon = ($totalBelanja * (float)$promo['nilai']) / 100;
            } else {
                // Tipe nominal
                $diskon = (float)$promo['nilai'];
            }

            // Diskon tidak boleh melebihi total belanja
            if ($diskon > $totalBelanja) {
                $diskon = $totalBelanja;
            }

            return [
                'success' => true,
                'message' => 'Promo valid',
                'data' => [
                    'promo' => $promo,
                    'diskon' => $diskon,
                    'total_setelah_diskon' => $totalBelanja - $diskon
                ]
            ];
        } catch (PDOException $e) {
            error_log("Promo::validatePromo error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memvalidasi promo'];
        }
    }

    /**
     * Increment jumlah pemakaian promo (terpakai + 1)
     *
     * @param int $id ID promo
     * @return bool
     */
    public static function usePromo($id)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE promo SET terpakai = terpakai + 1 WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Promo::usePromo error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update status promo yang sudah melewati tanggal selesai menjadi 'expired'
     *
     * @return int Jumlah promo yang di-expire
     */
    public static function checkExpired()
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                UPDATE promo
                SET status = 'expired'
                WHERE status = 'aktif' AND selesai < CURDATE()
            ");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Promo::checkExpired error: " . $e->getMessage());
            return 0;
        }
    }
}
