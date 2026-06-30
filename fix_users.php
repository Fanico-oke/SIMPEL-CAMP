<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = Database::getInstance();
    
    // Check if points/trx count is stored in users table or somewhere else
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'poin'");
    if ($stmt->rowCount() > 0) {
        $db->exec("UPDATE users SET poin = 0");
    }
    
    // Check points history table if exists
    $stmt = $db->query("SHOW TABLES LIKE 'poin_history'");
    if ($stmt->rowCount() > 0) {
        $db->exec("TRUNCATE TABLE poin_history");
    }

    echo "Points reset done!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
