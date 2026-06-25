<?php
// api/promo.php — Validate promo codes for checkout
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/classes/Promo.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'validate') {
    $input = json_decode(file_get_contents('php://input'), true);
    $kode = trim($input['kode'] ?? '');
    $subtotal = (float)($input['subtotal'] ?? 0);

    if (empty($kode)) {
        echo json_encode(['success' => false, 'message' => 'Masukkan kode promo']);
        exit;
    }

    $promo = Promo::getByKode($kode);

    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Kode promo tidak ditemukan']);
        exit;
    }

    // Check status
    if ($promo['status'] !== 'aktif') {
        echo json_encode(['success' => false, 'message' => 'Promo sudah tidak aktif']);
        exit;
    }

    // Check date range
    $now = date('Y-m-d');
    if ($now < $promo['mulai'] || $now > $promo['selesai']) {
        echo json_encode(['success' => false, 'message' => 'Promo sudah berakhir atau belum dimulai']);
        exit;
    }

    // Check quota
    if ((int)$promo['kuota'] > 0) {
        $db = Database::getInstance();
        $used = $db->prepare("SELECT COUNT(*) FROM reservasi WHERE promo_id = ?");
        $used->execute([$promo['id']]);
        $usedCount = (int)$used->fetchColumn();
        if ($usedCount >= (int)$promo['kuota']) {
            echo json_encode(['success' => false, 'message' => 'Kuota promo sudah habis']);
            exit;
        }
    }

    // Check minimum transaction
    if ($subtotal < (float)$promo['min_transaksi']) {
        echo json_encode([
            'success' => false,
            'message' => 'Minimum transaksi Rp ' . number_format($promo['min_transaksi'], 0, ',', '.')
        ]);
        exit;
    }

    // Calculate discount
    if ($promo['tipe'] === 'persentase') {
        $diskon = round($subtotal * $promo['nilai'] / 100);
    } else {
        $diskon = (float)$promo['nilai'];
    }

    // Cap discount at subtotal
    if ($diskon > $subtotal) $diskon = $subtotal;

    echo json_encode([
        'success' => true,
        'message' => 'Promo berhasil diterapkan!',
        'data' => [
            'promo_id' => (int)$promo['id'],
            'kode' => $promo['kode'],
            'nama' => $promo['nama'],
            'tipe' => $promo['tipe'],
            'nilai' => (float)$promo['nilai'],
            'diskon' => $diskon,
            'label' => $promo['tipe'] === 'persentase'
                ? $promo['nilai'] . '% OFF'
                : 'Rp ' . number_format($promo['nilai'], 0, ',', '.') . ' OFF'
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action not found']);
