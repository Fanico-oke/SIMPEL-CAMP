<?php
// classes/Pengembalian.php
// Model untuk mengelola pengembalian barang sewaan

require_once dirname(__DIR__) . '/config/database.php';

class Pengembalian
{
    /**
     * Buat record pengembalian barang
     * - Hitung hari terlambat dari tanggal_selesai vs tanggal_kembali
     * - Hitung denda keterlambatan & denda kondisi barang
     * - Update status transaksi menjadi 'selesai'
     * - Kembalikan stok barang
     *
     * @param int   $transaksiId ID transaksi
     * @param array $data        [tanggal_kembali, kondisi_barang, catatan]
     * @return array Hasil operasi
     */
    public static function create($transaksiId, $data)
    {
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // Ambil data transaksi beserta reservasi
            $stmt = $db->prepare("
                SELECT t.*, r.tanggal_selesai, r.total_biaya, r.id AS reservasi_id
                FROM transaksi t
                LEFT JOIN reservasi r ON t.reservasi_id = r.id
                WHERE t.id = ?
            ");
            $stmt->execute([$transaksiId]);
            $transaksi = $stmt->fetch();

            if (!$transaksi) {
                return ['success' => false, 'message' => 'Transaksi tidak ditemukan'];
            }

            if ($transaksi['status'] === 'selesai') {
                return ['success' => false, 'message' => 'Transaksi sudah selesai'];
            }

            // Hitung denda
            $tanggalKembali = $data['tanggal_kembali'] ?? date('Y-m-d');
            $kondisi = $data['kondisi_barang'] ?? 'baik';
            $catatan = $data['catatan'] ?? null;

            $dendaInfo = self::hitungDenda($transaksiId, $tanggalKembali, $kondisi);

            if (!$dendaInfo['success']) {
                return $dendaInfo;
            }

            $hariTerlambat = $dendaInfo['data']['hari_terlambat'];
            $totalDenda = $dendaInfo['data']['total_denda'];

            // Insert record pengembalian
            $stmt = $db->prepare("
                INSERT INTO pengembalian (transaksi_id, tanggal_kembali, hari_terlambat, kondisi_barang, denda, catatan)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $transaksiId,
                $tanggalKembali,
                $hariTerlambat,
                $kondisi,
                $totalDenda,
                $catatan
            ]);
            $pengembalianId = $db->lastInsertId();

            // Update denda & status transaksi menjadi 'selesai'
            $stmt = $db->prepare("
                UPDATE transaksi SET denda = ?, status = 'selesai', updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$totalDenda, $transaksiId]);

            // Update status reservasi menjadi 'selesai' jika ada
            if ($transaksi['reservasi_id']) {
                $stmt = $db->prepare("
                    UPDATE reservasi SET status = 'selesai', updated_at = NOW() WHERE id = ?
                ");
                $stmt->execute([$transaksi['reservasi_id']]);
            }

            // Kembalikan stok barang
            self::restoreStok($db, $transaksi['reservasi_id']);

            $db->commit();

            return [
                'success' => true,
                'message' => 'Pengembalian berhasil dicatat',
                'data' => [
                    'id' => $pengembalianId,
                    'hari_terlambat' => $hariTerlambat,
                    'denda' => $totalDenda,
                    'kondisi_barang' => $kondisi
                ]
            ];
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Pengembalian::create error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal memproses pengembalian'];
        }
    }

    /**
     * Kembalikan stok barang berdasarkan detail reservasi
     *
     * @param PDO $db           Instance database
     * @param int $reservasiId  ID reservasi
     */
    private static function restoreStok($db, $reservasiId)
    {
        if (!$reservasiId) return;

        $stmt = $db->prepare("
            SELECT barang_id, jumlah FROM detail_reservasi WHERE reservasi_id = ?
        ");
        $stmt->execute([$reservasiId]);
        $items = $stmt->fetchAll();

        foreach ($items as $item) {
            $stmt = $db->prepare("
                UPDATE barang
                SET stok_tersedia = stok_tersedia + ?,
                    status = CASE
                        WHEN (stok_tersedia + ?) > 0 THEN 'tersedia'
                        ELSE status
                    END
                WHERE id = ?
            ");
            $stmt->execute([$item['jumlah'], $item['jumlah'], $item['barang_id']]);
        }
    }

    /**
     * Ambil pengembalian berdasarkan ID transaksi
     *
     * @param int $transaksiId
     * @return array|null
     */
    public static function getByTransaksi($transaksiId)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT p.*, t.kode_transaksi, t.total_bayar, t.user_id,
                       r.tanggal_mulai, r.tanggal_selesai, r.total_biaya
                FROM pengembalian p
                JOIN transaksi t ON p.transaksi_id = t.id
                LEFT JOIN reservasi r ON t.reservasi_id = r.id
                WHERE p.transaksi_id = ?
            ");
            $stmt->execute([$transaksiId]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Pengembalian::getByTransaksi error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil semua data pengembalian dengan info transaksi & user
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
                SELECT p.*, t.kode_transaksi, t.total_bayar, t.user_id,
                       u.nama AS nama_user, u.email,
                       r.tanggal_mulai, r.tanggal_selesai, r.total_biaya
                FROM pengembalian p
                JOIN transaksi t ON p.transaksi_id = t.id
                JOIN users u ON t.user_id = u.id
                LEFT JOIN reservasi r ON t.reservasi_id = r.id
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([(int)$limit, (int)$offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Pengembalian::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Hitung denda tanpa menyimpan — untuk preview/simulasi
     *
     * Denda keterlambatan = hari_terlambat * (denda_per_hari_persen/100) * total_biaya
     * Denda kondisi:
     *   - rusak_berat: +50% dari total_biaya
     *   - hilang: +100% dari total_biaya
     *
     * @param int    $transaksiId    ID transaksi
     * @param string $tanggalKembali Tanggal pengembalian (Y-m-d)
     * @param string $kondisi        Kondisi barang
     * @return array
     */
    public static function hitungDenda($transaksiId, $tanggalKembali, $kondisi = 'baik')
    {
        try {
            $db = Database::getInstance();

            // Ambil data transaksi + reservasi
            $stmt = $db->prepare("
                SELECT t.*, r.tanggal_selesai, r.total_biaya
                FROM transaksi t
                LEFT JOIN reservasi r ON t.reservasi_id = r.id
                WHERE t.id = ?
            ");
            $stmt->execute([$transaksiId]);
            $transaksi = $stmt->fetch();

            if (!$transaksi) {
                return ['success' => false, 'message' => 'Transaksi tidak ditemukan'];
            }

            $tanggalSelesai = $transaksi['tanggal_selesai'];
            $totalBiaya = (float)$transaksi['total_biaya'];

            // Hitung hari terlambat
            $selesai = new DateTime($tanggalSelesai);
            $kembali = new DateTime($tanggalKembali);
            $selisih = $kembali->diff($selesai)->days;
            $hariTerlambat = ($kembali > $selesai) ? $selisih : 0;

            // Ambil persentase denda dari pengaturan
            $stmt = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = 'denda_per_hari_persen'");
            $stmt->execute();
            $dendaPersen = (float)($stmt->fetchColumn() ?: 10);

            // Denda keterlambatan
            $dendaTerlambat = $hariTerlambat * ($dendaPersen / 100) * $totalBiaya;

            // Denda berdasarkan kondisi barang
            $dendaKondisi = 0;
            switch ($kondisi) {
                case 'rusak_berat':
                    $dendaKondisi = $totalBiaya * 0.50; // 50% dari total biaya
                    break;
                case 'hilang':
                    $dendaKondisi = $totalBiaya * 1.00; // 100% dari total biaya
                    break;
                case 'rusak_ringan':
                    $dendaKondisi = $totalBiaya * 0.10; // 10% dari total biaya
                    break;
                default:
                    $dendaKondisi = 0;
                    break;
            }

            $totalDenda = $dendaTerlambat + $dendaKondisi;

            return [
                'success' => true,
                'data' => [
                    'tanggal_selesai' => $tanggalSelesai,
                    'tanggal_kembali' => $tanggalKembali,
                    'hari_terlambat' => $hariTerlambat,
                    'denda_per_hari_persen' => $dendaPersen,
                    'total_biaya' => $totalBiaya,
                    'denda_terlambat' => $dendaTerlambat,
                    'kondisi_barang' => $kondisi,
                    'denda_kondisi' => $dendaKondisi,
                    'total_denda' => $totalDenda
                ]
            ];
        } catch (PDOException $e) {
            error_log("Pengembalian::hitungDenda error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghitung denda'];
        }
    }
}
