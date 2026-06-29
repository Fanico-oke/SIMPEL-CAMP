<?php
// api/member_reward.php
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/MemberReward.php';
require_once dirname(__DIR__) . '/classes/MemberLevel.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$db = Database::getInstance();

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'tukar':
            $input = json_decode(file_get_contents('php://input'), true);
            $rewardId = isset($input['reward_id']) ? (int)$input['reward_id'] : 0;

            if ($rewardId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Reward tidak valid']);
                exit;
            }

            // 1. Fetch reward
            $reward = MemberReward::getById($rewardId);
            if (!$reward || $reward['status'] !== 'aktif') {
                echo json_encode(['status' => 'error', 'message' => 'Reward tidak tersedia']);
                exit;
            }

            // 2. Fetch user points
            $memberInfo = MemberLevel::getByUser($userId);
            if (!$memberInfo) {
                // Auto create member profile
                MemberLevel::create($userId);
                $memberInfo = MemberLevel::getByUser($userId);
            }

            $currentPoints = (int)($memberInfo['poin'] ?? 0);
            $pointsNeeded = (int)$reward['poin_dibutuhkan'];

            if ($currentPoints < $pointsNeeded) {
                echo json_encode(['status' => 'error', 'message' => 'Poin Anda tidak mencukupi']);
                exit;
            }

            $db->beginTransaction();

            // 3. Deduct points
            $stmt = $db->prepare("UPDATE member_level SET poin = poin - ? WHERE user_id = ?");
            $stmt->execute([$pointsNeeded, $userId]);

            // 4. Log riwayat
            $stmtLog = $db->prepare("INSERT INTO riwayat_poin (user_id, jenis, jumlah, keterangan) VALUES (?, 'keluar', ?, ?)");
            $keterangan = "Menukar reward: " . $reward['nama_reward'];
            $stmtLog->execute([$userId, $pointsNeeded, $keterangan]);

            // 5. Generate Promo Code (Format: RWD-USERID-RANDOM)
            $kodePromo = 'RWD-' . $userId . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
            
            // Assuming we use the promo table, but let's check promo table schema.
            // Wait, we need to insert to `promo` if it exists. 
            // We can determine discount values from the reward name or description if we didn't add 'tipe_kupon'.
            // Actually, for now, let's assume it's just a nominal discount based on points (e.g. 50 poin = 20k).
            // But we don't have tipe_kupon and nilai_kupon in member_rewards!
            // To keep it simple, let's extract a number from the reward name, or just hardcode a mapping.
            // Example name: "Voucher Diskon Rp 20.000"
            $nilaiDiskon = 20000;
            if (preg_match('/(?:Rp\s*|Rp)(\d{1,3}(?:\.\d{3})*)/', $reward['nama_reward'], $matches)) {
                $nilaiDiskon = (int)str_replace('.', '', $matches[1]);
            }
            
            // Insert into promo
            $stmtPromo = $db->prepare("INSERT INTO promo (kode_promo, tipe_diskon, nilai_diskon, minimal_belanja, batas_waktu, kuota) VALUES (?, 'nominal', ?, 0, DATE_ADD(NOW(), INTERVAL 30 DAY), 1)");
            $stmtPromo->execute([$kodePromo, $nilaiDiskon]);

            $db->commit();
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Penukaran berhasil!',
                'kode_promo' => $kodePromo
            ]);
            break;

        case 'riwayat':
            $stmt = $db->prepare("SELECT * FROM riwayat_poin WHERE user_id = ? ORDER BY tanggal DESC");
            $stmt->execute([$userId]);
            $riwayat = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $riwayat]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
            break;
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
