<?php
$db = new PDO('mysql:host=localhost;dbname=simpelcamp', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "
CREATE TABLE IF NOT EXISTS member_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_reward VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    poin_dibutuhkan INT NOT NULL DEFAULT 0,
    icon VARCHAR(100) DEFAULT 'bi-gift',
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS member_benefits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_benefit VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    icon VARCHAR(100) DEFAULT 'bi-star',
    warna VARCHAR(50) DEFAULT 'blue',
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS riwayat_poin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    jenis ENUM('masuk', 'keluar') NOT NULL,
    jumlah INT NOT NULL DEFAULT 0,
    keterangan VARCHAR(255) NOT NULL,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $db->exec($sql);
    echo 'Tables member_rewards, member_benefits, riwayat_poin created successfully.';
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
