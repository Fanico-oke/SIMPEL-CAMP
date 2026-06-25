<?php
/**
 * ============================================================
 * SIMPEL-CAMP — Logout Handler
 * Destroys the current session and redirects to login page
 * ============================================================
 */

// Load application constants (also auto-starts session)
require_once __DIR__ . '/config/constants.php';

// Unset all session variables
$_SESSION = [];

// Delete the session cookie if it exists
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ' . BASE_URL . '/login.php');
exit;
