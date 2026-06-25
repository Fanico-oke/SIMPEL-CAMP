<?php
// classes/Pengeluaran.php
// Model untuk tabel pengeluaran

require_once dirname(__DIR__) . '/config/database.php';

class Pengeluaran {

    /**
     * Ambil semua pengeluaran dengan filter
     */
    public static function getAll($filters = []) {
        try {
            $db = Database::getInstance();
            $where = [];
            $params = [];

            if (!empty($filters['start_date'])) {
                $where[] = "tanggal >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }
            if (!empty($filters['end_date'])) {
                $where[] = "tanggal <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }
            if (!empty($filters['kategori'])) {
                $where[] = "kategori_pengeluaran = :kategori";
                $params[':kategori'] = $filters['kategori'];
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $limit = (int)($filters['limit'] ?? 100);

            $stmt = $db->prepare("
                SELECT * FROM pengeluaran
                {$whereClause}
                ORDER BY tanggal DESC
                LIMIT :limit
            ");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Pengeluaran::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Total pengeluaran dengan filter tanggal opsional
     */
    public static function total($startDate = null, $endDate = null) {
        try {
            $db = Database::getInstance();
            $where = [];
            $params = [];
            if ($startDate) { $where[] = "tanggal >= ?"; $params[] = $startDate; }
            if ($endDate) { $where[] = "tanggal <= ?"; $params[] = $endDate; }
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah), 0) as total FROM pengeluaran {$whereClause}");
            $stmt->execute($params);
            return (float)$stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Pengeluaran::total error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Pengeluaran bulanan per tahun
     */
    public static function monthlyByYear($year) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT MONTH(tanggal) as bulan, COALESCE(SUM(jumlah), 0) as total
                FROM pengeluaran
                WHERE YEAR(tanggal) = ?
                GROUP BY MONTH(tanggal)
                ORDER BY bulan
            ");
            $stmt->execute([$year]);
            $result = [];
            foreach ($stmt->fetchAll() as $row) {
                $result[(int)$row['bulan']] = (float)$row['total'];
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Pengeluaran::monthlyByYear error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Summary per kategori pengeluaran
     */
    public static function summaryByKategori($startDate = null, $endDate = null) {
        try {
            $db = Database::getInstance();
            $where = [];
            $params = [];
            if ($startDate) { $where[] = "tanggal >= ?"; $params[] = $startDate; }
            if ($endDate) { $where[] = "tanggal <= ?"; $params[] = $endDate; }
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $stmt = $db->prepare("
                SELECT kategori_pengeluaran, COUNT(*) as jumlah_item, SUM(jumlah) as total
                FROM pengeluaran {$whereClause}
                GROUP BY kategori_pengeluaran
                ORDER BY total DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Pengeluaran::summaryByKategori error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tambah pengeluaran baru
     */
    public static function create($data) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO pengeluaran (kategori_pengeluaran, deskripsi, jumlah, tanggal) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['kategori_pengeluaran'],
                $data['deskripsi'] ?? '',
                $data['jumlah'],
                $data['tanggal'],
            ]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Pengeluaran::create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update pengeluaran
     */
    public static function update($id, $data) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE pengeluaran SET kategori_pengeluaran=?, deskripsi=?, jumlah=?, tanggal=? WHERE id=?");
            return $stmt->execute([
                $data['kategori_pengeluaran'],
                $data['deskripsi'] ?? '',
                $data['jumlah'],
                $data['tanggal'],
                $id,
            ]);
        } catch (PDOException $e) {
            error_log("Pengeluaran::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus pengeluaran
     */
    public static function delete($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM pengeluaran WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Pengeluaran::delete error: " . $e->getMessage());
            return false;
        }
    }
}
