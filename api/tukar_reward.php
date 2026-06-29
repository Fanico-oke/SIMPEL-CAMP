<?php
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/classes/MemberLevel.php';
require_once dirname(__DIR__) . '/classes/Database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $rewardName = $_POST['reward_name'] ?? '';
    $cost = isset($_POST['cost']) ? (int)$_POST['cost'] : 0;
    $codePrefix = $_POST['code_prefix'] ?? 'RWD';

    if ($cost <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cost tidak valid']);
        exit;
    }

    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // Ambil member level dengan lock for update
        $stmt = $db->prepare("SELECT id, poin FROM member_level WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $member = $stmt->fetch();

        if (!$member) {
            throw new Exception("Data member tidak ditemukan");
        }

        $currentPoin = (int)$member['poin'];

        if ($currentPoin < $cost) {
            throw new Exception("Poin tidak cukup");
        }

        // Kurangi poin
        $newPoin = $currentPoin - $cost;
        $updateStmt = $db->prepare("UPDATE member_level SET poin = ? WHERE id = ?");
        $updateStmt->execute([$newPoin, $member['id']]);

        // Generate coupon code
        $randomSuffix = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $couponCode = $codePrefix . '-' . $randomSuffix;

        // (Opsional) Insert ke tabel promo agar bisa dipakai. 
        // Disini kita insert nilai nominal 0 (karena kita belum tahu pasti isinya apa),
        // Sehingga hanya berlaku sebagai tanda bukti, atau admin yang mengubah nilainya.
        $stmtPromo = $db->prepare("INSERT INTO promo (kode, nama, tipe, nilai, min_transaksi, kuota, status) VALUES (?, ?, 'nominal', 0, 0, 1, 'aktif')");
        $stmtPromo->execute([$couponCode, $rewardName]);

        $db->commit();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Reward berhasil ditukar',
            'coupon' => $couponCode,
            'new_poin' => $newPoin
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
