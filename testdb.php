<?php
$db = new PDO('mysql:host=localhost;dbname=simpel_camp', 'root', '');
$stmt = $db->query("
    SELECT r.id, r.kode_reservasi, r.status, dr.barang_id, dr.jumlah, b.nama, b.stok_total, b.stok_tersedia 
    FROM reservasi r 
    JOIN detail_reservasi dr ON r.id = dr.reservasi_id 
    JOIN barang b ON dr.barang_id = b.id 
    ORDER BY r.id DESC 
    LIMIT 5
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
