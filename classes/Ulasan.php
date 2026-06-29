<?php
require_once __DIR__ . '/../config/database.php';

class Ulasan {
    public static function create($userId, $barangId, $transaksiId, $rating, $komentar) {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("
                INSERT INTO ulasan (user_id, barang_id, transaksi_id, rating, komentar) 
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$userId, $barangId, $transaksiId, $rating, $komentar]);
        } catch (PDOException $e) {
            error_log("Gagal membuat ulasan: " . $e->getMessage());
            return false;
        }
    }

    public static function getByBarang($barangId, $limit = 50) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.*, us.nama as user_name 
            FROM ulasan u
            JOIN users us ON u.user_id = us.id
            WHERE u.barang_id = ?
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        // Needs PDO::PARAM_INT for limit
        $stmt->bindValue(1, $barangId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAverageRating($barangId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
            FROM ulasan 
            WHERE barang_id = ?
        ");
        $stmt->execute([$barangId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'avg' => $res['total_reviews'] > 0 ? round($res['avg_rating'], 1) : 0,
            'total' => $res['total_reviews']
        ];
    }

    public static function canReview($userId, $barangId, $transaksiId) {
        $db = Database::getInstance();
        // Check if already reviewed
        $stmt = $db->prepare("SELECT id FROM ulasan WHERE user_id = ? AND barang_id = ? AND transaksi_id = ?");
        $stmt->execute([$userId, $barangId, $transaksiId]);
        if ($stmt->fetch()) {
            return false;
        }
        
        // Check if transaction is completed and contains the item
        $stmt = $db->prepare("
            SELECT t.id 
            FROM transaksi t
            JOIN reservasi r ON t.reservasi_id = r.id
            JOIN detail_reservasi dr ON r.id = dr.reservasi_id
            WHERE t.id = ? AND t.user_id = ? AND t.status = 'selesai' AND dr.barang_id = ?
        ");
        $stmt->execute([$transaksiId, $userId, $barangId]);
        return (bool)$stmt->fetch();
    }
}
