<?php
// katalog.php (root) — Redirect to pelanggan katalog
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn() && $_SESSION['role'] === 'pelanggan') {
    header('Location: ' . BASE_URL . '/pages/pelanggan/katalog.php');
} else {
    // Guest user: redirect to login
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
