<?php
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/classes/Ulasan.php';
require_once dirname(__DIR__) . '/classes/Database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'submit') {
        $transaksiId = isset($_POST['transaksi_id']) ? (int)$_POST['transaksi_id'] : 0;
        $reviews = isset($_POST['reviews']) ? $_POST['reviews'] : [];
        $userId = $_SESSION['user_id'];
        
        if (!$transaksiId || empty($reviews)) {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
            exit;
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        
        try {
            $successCount = 0;
            foreach ($reviews as $rev) {
                $barangId = (int)$rev['barang_id'];
                $rating = (int)$rev['rating'];
                $komentar = sanitize($rev['komentar'] ?? '');
                
                if ($rating < 1 || $rating > 5) continue;
                
                // Cek apakah sudah pernah review
                if (!Ulasan::canReview($userId, $barangId, $transaksiId)) {
                    continue; // skip if already reviewed or not valid
                }
                
                if (Ulasan::create($userId, $barangId, $transaksiId, $rating, $komentar)) {
                    $successCount++;
                }
            }
            
            $db->commit();
            
            if ($successCount > 0) {
                echo json_encode(['status' => 'success', 'message' => "$successCount ulasan berhasil disimpan"]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Tidak ada ulasan baru yang disimpan. Mungkin Anda sudah mengulas barang ini sebelumnya.']);
            }
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Action invalid']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
