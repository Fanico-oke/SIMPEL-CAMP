<?php
/**
 * Sync member_level data from completed transactions
 * Run once to fix out-of-sync member records
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/MemberLevel.php';

$db = Database::getInstance();

// Get all users with completed transactions
$stmt = $db->query("
    SELECT t.user_id, 
           COUNT(*) as total_trx, 
           SUM(t.total_bayar) as total_sewa,
           SUM(FLOOR(t.total_bayar / 10000)) as total_poin
    FROM transaksi t
    WHERE t.status = 'selesai'
    GROUP BY t.user_id
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Syncing member_level for " . count($users) . " users...\n";

foreach ($users as $u) {
    $userId = $u['user_id'];
    $totalTrx = (int)$u['total_trx'];
    $totalSewa = (float)$u['total_sewa'];
    $totalPoin = (int)$u['total_poin'];
    
    // Ensure member_level record exists
    MemberLevel::create($userId);
    
    // Update with real data
    $db->prepare("
        UPDATE member_level SET 
            total_transaksi = ?, 
            total_sewa = ?, 
            poin = ?,
            updated_at = NOW()
        WHERE user_id = ?
    ")->execute([$totalTrx, $totalSewa, $totalPoin, $userId]);
    
    // Auto-upgrade level
    MemberLevel::checkLevelUp($userId);
    
    echo "  User #$userId: $totalTrx trx, Rp " . number_format($totalSewa) . ", $totalPoin poin\n";
}

echo "\nDone! Member levels synced.\n";
