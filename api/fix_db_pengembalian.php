<?php
require_once dirname(__DIR__) . '/config/database.php';

try {
    $db = Database::getInstance();
    
    // Check if foto_bukti column exists
    $stmt = $db->query("SHOW COLUMNS FROM pengembalian LIKE 'foto_bukti'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE pengembalian ADD COLUMN foto_bukti VARCHAR(255) NULL AFTER kondisi_barang");
        echo "Added column foto_bukti.<br>";
    } else {
        echo "Column foto_bukti already exists.<br>";
    }
    
    // Check if bukti_denda column exists
    $stmt = $db->query("SHOW COLUMNS FROM pengembalian LIKE 'bukti_denda'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE pengembalian ADD COLUMN bukti_denda VARCHAR(255) NULL AFTER foto_bukti");
        echo "Added column bukti_denda.<br>";
    } else {
        echo "Column bukti_denda already exists.<br>";
    }

    // Check if status column exists
    $stmt = $db->query("SHOW COLUMNS FROM pengembalian LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE pengembalian ADD COLUMN status VARCHAR(50) DEFAULT 'menunggu_cek' AFTER denda");
        echo "Added column status.<br>";
    } else {
        echo "Column status already exists.<br>";
    }

    echo "Migration completed successfully!";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
