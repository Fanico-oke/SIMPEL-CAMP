<?php
// fix_auto.php (Modified for Data Wipe)
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // 1. Hapus semua data transaksi dan reservasi (termasuk pengembalian & detail)
    $db->exec("DELETE FROM pengembalian");
    $db->exec("DELETE FROM transaksi");
    $db->exec("DELETE FROM detail_reservasi");
    $db->exec("DELETE FROM reservasi");
    $db->exec("DELETE FROM notifikasi WHERE tipe IN ('reservasi', 'pengembalian', 'sistem')");

    // Reset Auto Increment agar mulai dari 1 lagi (Opsional, tapi bagus untuk kebersihan)
    $db->exec("ALTER TABLE pengembalian AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE transaksi AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE detail_reservasi AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE reservasi AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE notifikasi AUTO_INCREMENT = 1");

    // 2. Kembalikan semua stok barang menjadi penuh (stok_tersedia = stok)
    $db->exec("UPDATE barang SET stok_tersedia = stok");

    // 3. Hapus keranjang/wishlist agar bersih
    $db->exec("DELETE FROM wishlist");

    $db->commit();
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
}
