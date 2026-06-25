<?php
// Migration: Add gambar column to promo table
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    
    // Check if column already exists
    $cols = $db->query('DESCRIBE promo')->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'Field');
    
    if (in_array('gambar', $colNames)) {
        echo "Column 'gambar' already exists in promo table.\n";
    } else {
        $db->exec("ALTER TABLE promo ADD COLUMN gambar VARCHAR(255) DEFAULT NULL AFTER kuota");
        echo "Column 'gambar' added to promo table successfully.\n";
    }
    
    // Show current structure
    echo "\nCurrent promo table structure:\n";
    $cols = $db->query('DESCRIBE promo')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} - {$c['Type']}" . ($c['Null'] === 'YES' ? ' (nullable)' : '') . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
