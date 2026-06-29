<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS ulasan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            barang_id INT NOT NULL,
            transaksi_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            komentar TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
            FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
            UNIQUE KEY unique_review (user_id, barang_id, transaksi_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "Tabel ulasan berhasil dibuat.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
