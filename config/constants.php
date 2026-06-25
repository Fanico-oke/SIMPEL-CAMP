<?php
// config/constants.php

// Application Constants
define('APP_NAME', 'SIMPEL-CAMP');
define('APP_VERSION', '1.0.0');

// ============================================
// Database Configuration
// Ubah sesuai hosting Anda
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'simpelcamp');
define('DB_USER', 'root');
define('DB_PASS', '');

// ============================================
// URL Configuration (Auto-detect)
// ============================================
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
         || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $is_https ? 'https' : 'http';

if ($http_host === 'localhost' || strpos($http_host, 'localhost:') === 0) {
    // Development (Laragon/XAMPP)
    define('BASE_URL', $protocol . '://' . $http_host . '/pemweb');
} else {
    // Production hosting (domain langsung)
    define('BASE_URL', $protocol . '://' . $http_host);
}
define('ASSETS_URL', BASE_URL . '/frontend');

// Path Configuration
define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/frontend/img/barang');

// ============================================
// Session Security
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters before starting
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);

    // Enable secure cookie only on HTTPS
    if ($is_https) {
        ini_set('session.cookie_secure', 1);
    }

    session_start();
}
