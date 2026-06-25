<?php
// classes/Wishlist.php

require_once dirname(__DIR__) . '/config/database.php';

class Wishlist {

    /**
     * Tambah barang ke wishlist (atau update jumlah jika sudah ada)
     */
    public static function add($user_id, $barang_id, $jumlah = 1) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO wishlist (user_id, barang_id, jumlah)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE jumlah = jumlah + VALUES(jumlah), updated_at = NOW()
        ");
        return $stmt->execute([$user_id, $barang_id, $jumlah]);
    }

    /**
     * Update jumlah item di wishlist
     */
    public static function updateQty($user_id, $barang_id, $jumlah) {
        $db = Database::getInstance();
        if ($jumlah <= 0) {
            return self::remove($user_id, $barang_id);
        }
        $stmt = $db->prepare("UPDATE wishlist SET jumlah = ?, updated_at = NOW() WHERE user_id = ? AND barang_id = ?");
        return $stmt->execute([$jumlah, $user_id, $barang_id]);
    }

    /**
     * Update tanggal sewa per item
     */
    public static function updateDates($user_id, $barang_id, $tanggal_mulai, $tanggal_selesai) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE wishlist SET tanggal_mulai = ?, tanggal_selesai = ?, updated_at = NOW() WHERE user_id = ? AND barang_id = ?");
        return $stmt->execute([$tanggal_mulai, $tanggal_selesai, $user_id, $barang_id]);
    }

    /**
     * Hapus item dari wishlist
     */
    public static function remove($user_id, $barang_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND barang_id = ?");
        return $stmt->execute([$user_id, $barang_id]);
    }

    /**
     * Ambil semua item wishlist user (dengan data barang)
     */
    public static function getByUser($user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT w.*, b.nama AS barang_nama, b.gambar, b.harga_per_hari,
                   b.stok_tersedia, b.stok_total, b.status AS barang_status,
                   b.deskripsi,
                   k.nama AS kategori_nama
            FROM wishlist w
            JOIN barang b ON w.barang_id = b.id
            LEFT JOIN kategori k ON b.kategori_id = k.id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hitung jumlah item di wishlist
     */
    public static function count($user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cek apakah barang sudah ada di wishlist
     */
    public static function exists($user_id, $barang_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND barang_id = ?");
        $stmt->execute([$user_id, $barang_id]);
        return $stmt->fetch() ? true : false;
    }

    /**
     * Kosongkan wishlist user (setelah checkout)
     */
    public static function clearByUser($user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    /**
     * Hapus item tertentu dari wishlist (by IDs)
     */
    public static function removeByIds($user_id, $barang_ids) {
        if (empty($barang_ids)) return false;
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($barang_ids), '?'));
        $params = array_merge([$user_id], $barang_ids);
        $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND barang_id IN ($placeholders)");
        return $stmt->execute($params);
    }
}
