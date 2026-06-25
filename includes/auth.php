<?php
// includes/auth.php
// Authentication & Authorization Helper Functions

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Wajib login — redirect ke login jika belum
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

/**
 * Wajib role tertentu — redirect ke 403 jika role tidak sesuai
 * @param string|array $roles Role yang diizinkan
 */
function requireRole($roles) {
    requireLogin();
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        header("Location: " . BASE_URL . "/404.php?code=403");
        exit;
    }
}

/**
 * Ambil data user saat ini dari session
 * @return array|null
 */
function currentUser() {
    if (!isLoggedIn()) return null;
    
    return [
        'id'    => $_SESSION['user_id'],
        'nama'  => $_SESSION['nama'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role'  => $_SESSION['role'] ?? '',
        'foto'  => $_SESSION['foto'] ?? null
    ];
}

/**
 * Ambil data user lengkap dari database
 * @return array|null
 */
function currentUserFull() {
    if (!isLoggedIn()) return null;
    
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND status = 'aktif'");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            unset($user['password']); // Jangan expose password
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log("currentUserFull error: " . $e->getMessage());
        return null;
    }
}

/**
 * Set flash message ke session
 */
function setFlash($key, $message) {
    $_SESSION['flash_' . $key] = $message;
}

/**
 * Ambil dan hapus flash message
 */
function getFlash($key) {
    $fullKey = 'flash_' . $key;
    if (isset($_SESSION[$fullKey])) {
        $msg = $_SESSION[$fullKey];
        unset($_SESSION[$fullKey]);
        return $msg;
    }
    return null;
}

/**
 * Cek apakah request adalah AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Response JSON untuk API
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Response JSON error
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

/**
 * Response JSON success
 */
function jsonSuccess($data = [], $message = 'Berhasil') {
    $response = ['success' => true, 'message' => $message];
    if (!empty($data)) {
        $response['data'] = $data;
    }
    jsonResponse($response);
}

/**
 * Sanitize input string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate random string untuk kode reservasi/transaksi
 */
function generateKode($prefix = 'RSV', $length = 8) {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $kode = $prefix . '-';
    for ($i = 0; $i < $length; $i++) {
        $kode .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $kode;
}

/**
 * Format harga ke Rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Hitung selisih hari antara 2 tanggal
 */
function hitungHari($tanggalMulai, $tanggalSelesai) {
    $start = new DateTime($tanggalMulai);
    $end   = new DateTime($tanggalSelesai);
    return $end->diff($start)->days;
}
