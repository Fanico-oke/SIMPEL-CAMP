<?php
// pages/pelanggan/profil.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';
require_once dirname(__DIR__, 2) . '/classes/MemberLevel.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Handle POST requests for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $db = Database::getInstance();
    
    if ($action === 'update_profile') {
        $nama = sanitize($_POST['nama'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $no_telp = sanitize($_POST['no_telp'] ?? '');
        $alamat = sanitize($_POST['alamat'] ?? '');
        $password = $_POST['confirm_password'] ?? '';
        
        // Verify current password
        $currentUser = $db->prepare("SELECT password FROM users WHERE id = ?");
        $currentUser->execute([$_SESSION['user_id']]);
        $userData = $currentUser->fetch();
        
        if (!$userData || !password_verify($password, $userData['password'])) {
            $flash_error = 'Password konfirmasi salah.';
        } else {
            try {
                $stmt = $db->prepare("UPDATE users SET nama=?, email=?, no_telp=?, alamat=? WHERE id=?");
                $stmt->execute([$nama, $email, $no_telp, $alamat, $_SESSION['user_id']]);
                $_SESSION['nama'] = $nama;
                $_SESSION['email'] = $email;
                $flash_success = 'Profil berhasil diperbarui.';
            } catch (Exception $e) {
                $flash_error = 'Gagal memperbarui profil: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPwd = $_POST['new_password'] ?? '';
        $confirmPwd = $_POST['confirm_new_password'] ?? '';
        
        if ($newPwd !== $confirmPwd) {
            $flash_error = 'Konfirmasi password tidak cocok.';
        } elseif (strlen($newPwd) < 6) {
            $flash_error = 'Password baru minimal 6 karakter.';
        } else {
            $currentUser = $db->prepare("SELECT password FROM users WHERE id = ?");
            $currentUser->execute([$_SESSION['user_id']]);
            $userData = $currentUser->fetch();
            
            if (!$userData || !password_verify($current, $userData['password'])) {
                $flash_error = 'Password saat ini salah.';
            } else {
                $hashed = password_hash($newPwd, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, $_SESSION['user_id']]);
                $flash_success = 'Password berhasil diubah.';
            }
        }
    }
    
    // Reload user data after update
    header('Location: ' . BASE_URL . '/pages/pelanggan/profil.php' . (isset($flash_success) ? '?success=' . urlencode($flash_success) : (isset($flash_error) ? '?error=' . urlencode($flash_error) : '')));
    exit;
}

$flash_success = $_GET['success'] ?? '';
$flash_error = $_GET['error'] ?? '';

$page_title = 'Profil Saya';
$current_page = 'profil';

// Load user data from database
$user = User::getById($_SESSION['user_id']);
$user_name = $user ? $user['nama'] : ($_SESSION['nama'] ?? 'Pelanggan');
$user_email = $user ? $user['email'] : ($_SESSION['email'] ?? '');
$user_phone = $user ? ($user['no_telp'] ?? '') : '';
$user_address = $user ? ($user['alamat'] ?? '') : '';
$user_joined = $user ? date('M Y', strtotime($user['created_at'])) : '-';
$user_joined_months = $user ? max(1, (int)((time() - strtotime($user['created_at'])) / (30 * 24 * 3600))) : 1;

// Load member data
$member = MemberLevel::getByUser($_SESSION['user_id']);
$member_level = $member ? ucfirst($member['level']) : 'Regular';
$member_poin = $member ? (int)$member['poin'] : 0;
$member_total_trx = $member ? (int)$member['total_transaksi'] : 0;
$member_total_sewa = $member ? (float)$member['total_sewa'] : 0;

// Format total sewa for display
if ($member_total_sewa >= 1000000) {
    $total_sewa_display = 'Rp ' . number_format($member_total_sewa / 1000000, 2, ',', '.') . 'M';
} elseif ($member_total_sewa >= 1000) {
    $total_sewa_display = 'Rp ' . number_format($member_total_sewa / 1000, 0, ',', '.') . 'K';
} else {
    $total_sewa_display = 'Rp ' . number_format($member_total_sewa, 0, ',', '.');
}

// Next level calculation
$level_thresholds = ['regular' => 0, 'bronze' => 5, 'silver' => 15, 'gold' => 30];
$level_order = ['regular', 'bronze', 'silver', 'gold'];
$current_level_idx = array_search(strtolower($member_level), $level_order);
if ($current_level_idx !== false && $current_level_idx < count($level_order) - 1) {
    $next_level = ucfirst($level_order[$current_level_idx + 1]);
    $next_threshold = $level_thresholds[$level_order[$current_level_idx + 1]] ?? 999;
    $progress_pct = min(100, round(($member_total_trx / $next_threshold) * 100));
    $remaining_trx = max(0, $next_threshold - $member_total_trx);
} else {
    $next_level = 'Max';
    $next_threshold = $member_total_trx;
    $progress_pct = 100;
    $remaining_trx = 0;
}

// Split name for first/last name fields
$name_parts = explode(' ', $user_name, 2);
$first_name = $name_parts[0] ?? '';
$last_name = $name_parts[1] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kelola profil dan pengaturan akun SIMPEL-CAMP">
    <title><?= htmlspecialchars($page_title) ?> —  SIMPEL-CAMP</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">

    <style>
        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
           PROFIL PAGE Ã¢â‚¬â€ DESIGN SYSTEM
           Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */

        :root {
            --page-bg: #F2F7F4;
            --card-bg: #FFFFFF;
            --card-radius: 20px;
            --card-shadow: 0 2px 20px rgba(0,0,0,0.04);
            --primary: #2D6A4F;
            --primary-light: #52B788;
            --primary-lighter: rgba(82,183,136,0.12);
            --accent-gold: #D4A373;
            --accent-gold-light: rgba(212,163,115,0.15);
            --text-dark: #1A1A2E;
            --text-muted: #6B7280;
            --text-light: #9CA3AF;
            --danger: #EF4444;
            --danger-light: rgba(239,68,68,0.08);
            --warning: #F59E0B;
            --info: #3B82F6;
            --success: #10B981;
            --font-body: 'Inter', sans-serif;
            --font-heading: 'Outfit', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Page Background Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .pelanggan-content {
            background: var(--page-bg);
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Animations Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes shimmer {
            0%   { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(212,163,115,0.45); }
            50%      { box-shadow: 0 0 18px 6px rgba(212,163,115,0.18); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideOutRight {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(40px); }
        }
        @keyframes toastProgress {
            from { width: 100%; }
            to   { width: 0%; }
        }
        @keyframes ringPulse {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.04); }
        }
        @keyframes tabSlide {
            from { opacity: 0; transform: translateX(12px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes strengthPulse {
            0%, 100% { opacity: 1; }
            50%      { opacity: 0.6; }
        }

        .fade-up {
            animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        .fade-up-d1 { animation-delay: 0.06s; }
        .fade-up-d2 { animation-delay: 0.12s; }
        .fade-up-d3 { animation-delay: 0.18s; }
        .fade-up-d4 { animation-delay: 0.24s; }
        .fade-up-d5 { animation-delay: 0.30s; }

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
           PROFILE HERO CARD
           Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        .profil-hero {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            padding: 36px 40px;
            display: flex;
            align-items: center;
            gap: 32px;
            position: relative;
            overflow: hidden;
            border: none;
        }
        .profil-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--accent-gold));
            border-radius: var(--card-radius) var(--card-radius) 0 0;
        }

        .profil-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .profil-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 2rem;
            color: #fff;
            letter-spacing: 1px;
            border: 3px solid var(--accent-gold);
            animation: pulseGlow 2.8s ease-in-out infinite;
        }
        .profil-avatar-edit {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--accent-gold);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(212,163,115,0.3);
        }
        .profil-avatar-edit:hover {
            transform: scale(1.12);
            box-shadow: 0 4px 14px rgba(212,163,115,0.45);
        }

        .profil-hero-info {
            flex: 1;
            min-width: 0;
        }
        .profil-hero-name {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.55rem;
            color: var(--text-dark);
            margin: 0 0 2px;
            line-height: 1.2;
        }
        .profil-hero-email {
            font-family: var(--font-body);
            font-size: 0.88rem;
            color: var(--text-muted);
            margin: 0 0 10px;
        }
        .profil-hero-email i {
            color: var(--primary-light);
            margin-right: 4px;
        }

        /* Gold Member Badge */
        .profil-badge-gold {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 16px;
            border-radius: 50px;
            background: linear-gradient(90deg, #D4A373, #E8C9A0, #D4A373);
            background-size: 200% auto;
            animation: shimmer 3s linear infinite;
            color: #5C3D1E;
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
        }
        .profil-badge-gold i {
            font-size: 0.82rem;
        }

        /* Stats Row */
        .profil-stats-row {
            display: flex;
            align-items: stretch;
            gap: 0;
            margin-left: auto;
            flex-shrink: 0;
        }
        .profil-stat {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 14px 28px;
            position: relative;
        }
        .profil-stat:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0; top: 18%;
            height: 64%;
            width: 1px;
            background: #E5E7EB;
        }
        .profil-stat-value {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 4px;
        }
        .profil-stat-label {
            font-family: var(--font-body);
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
           TWO COLUMN LAYOUT
           Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        .profil-columns {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            margin-top: 24px;
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ CARD BASE Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .profil-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            border: none;
            overflow: hidden;
        }

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
           TABBED FORM CARD
           Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        .profil-tabs-header {
            display: flex;
            gap: 8px;
            padding: 24px 28px 0;
        }
        .profil-tab-pill {
            padding: 9px 22px;
            border-radius: 50px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--text-muted);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.22,1,0.36,1);
            position: relative;
        }
        .profil-tab-pill:hover {
            color: var(--primary);
            background: var(--primary-lighter);
        }
        .profil-tab-pill.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            box-shadow: 0 4px 14px rgba(45,106,79,0.25);
        }

        .profil-tab-content {
            padding: 28px;
            display: none;
        }
        .profil-tab-content.active {
            display: block;
            animation: tabSlide 0.35s ease both;
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Form Styles Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .profil-form-group {
            margin-bottom: 20px;
        }
        .profil-form-label {
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.82rem;
            color: var(--text-dark);
            margin-bottom: 7px;
            display: block;
        }
        .profil-input-wrap {
            position: relative;
        }
        .profil-input-wrap i.input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 0.92rem;
            pointer-events: none;
            transition: color 0.2s;
        }
        .profil-input {
            width: 100%;
            padding: 11px 14px 11px 42px;
            border-radius: 12px;
            border: 1.5px solid #E5E7EB;
            font-family: var(--font-body);
            font-size: 0.88rem;
            color: var(--text-dark);
            background: #FAFBFC;
            transition: all 0.25s;
            outline: none;
        }
        .profil-input:focus {
            border-color: var(--primary-light);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(82,183,136,0.10);
        }
        .profil-input:focus + i.input-icon,
        .profil-input:focus ~ i.input-icon {
            color: var(--primary-light);
        }
        .profil-input-wrap .input-icon ~ .profil-input {
            padding-left: 42px;
        }

        /* no-icon variant */
        .profil-input.no-icon {
            padding-left: 14px;
        }

        .profil-input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Password Strength Meter Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .pwd-strength-bar {
            height: 4px;
            border-radius: 4px;
            background: #E5E7EB;
            margin-top: 8px;
            overflow: hidden;
        }
        .pwd-strength-fill {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: width 0.4s, background 0.4s;
        }
        .pwd-strength-fill.weak    { width: 25%;  background: var(--danger); }
        .pwd-strength-fill.fair    { width: 50%;  background: var(--warning); }
        .pwd-strength-fill.good    { width: 75%;  background: var(--info); }
        .pwd-strength-fill.strong  { width: 100%; background: var(--success); }

        .pwd-strength-label {
            font-size: 0.72rem;
            margin-top: 4px;
            font-family: var(--font-body);
            color: var(--text-muted);
        }

        /* Toggle visibility */
        .pwd-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            transition: color 0.2s;
        }
        .pwd-toggle:hover {
            color: var(--primary);
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Toggle Switch Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .profil-toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
        }
        .profil-toggle-row:not(:last-child) {
            border-bottom: 1px solid #F3F4F6;
        }
        .profil-toggle-info h6 {
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--text-dark);
            margin: 0 0 2px;
        }
        .profil-toggle-info p {
            font-size: 0.76rem;
            color: var(--text-muted);
            margin: 0;
        }
        .toggle-switch {
            position: relative;
            width: 46px;
            height: 26px;
            flex-shrink: 0;
        }
        .toggle-switch input { display: none; }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: #D1D5DB;
            border-radius: 50px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            left: 3px; top: 3px;
            width: 20px; height: 20px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.12);
            transition: transform 0.3s cubic-bezier(0.22,1,0.36,1);
        }
        .toggle-switch input:checked + .toggle-slider {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }
        .toggle-switch input:checked + .toggle-slider::before {
            transform: translateX(20px);
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Theme Selector Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .theme-selector {
            display: flex;
            gap: 10px;
            margin-top: 6px;
        }
        .theme-option {
            width: 52px; height: 52px;
            border-radius: 14px;
            cursor: pointer;
            position: relative;
            transition: transform 0.25s, box-shadow 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-option:hover { transform: translateY(-2px); }
        .theme-option.active {
            box-shadow: 0 0 0 2.5px var(--primary-light);
            transform: translateY(-2px);
        }
        .theme-option.active::after {
            content: '\F26A';
            font-family: 'bootstrap-icons';
            position: absolute;
            bottom: -4px; right: -4px;
            width: 18px; height: 18px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-size: 0.55rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-light  { background: linear-gradient(135deg, #fff, #F2F7F4); border: 1.5px solid #E5E7EB; }
        .theme-dark   { background: linear-gradient(135deg, #1A1A2E, #2D2D44); }
        .theme-nature { background: linear-gradient(135deg, #2D6A4F, #52B788); }

        /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Language Dropdown Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .profil-select {
            width: 100%;
            padding: 11px 14px 11px 42px;
            border-radius: 12px;
            border: 1.5px solid #E5E7EB;
            font-family: var(--font-body);
            font-size: 0.88rem;
            color: var(--text-dark);
            background: #FAFBFC;
            outline: none;
            appearance: none;
            cursor: pointer;
            transition: all 0.25s;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }
        .profil-select:focus {
            border-color: var(--primary-light);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(82,183,136,0.10);
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Buttons Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .btn-profil-primary {
            padding: 11px 32px;
            border-radius: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.88rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(45,106,79,0.22);
        }
        .btn-profil-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45,106,79,0.32);
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: #fff;
        }
        .btn-profil-secondary {
            padding: 11px 28px;
            border-radius: 50px;
            background: transparent;
            color: var(--text-muted);
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.88rem;
            border: 1.5px solid #E5E7EB;
            cursor: pointer;
            transition: all 0.25s;
        }
        .btn-profil-secondary:hover {
            border-color: var(--primary-light);
            color: var(--primary);
            background: var(--primary-lighter);
        }
        .btn-profil-danger {
            padding: 11px 28px;
            border-radius: 50px;
            background: transparent;
            color: var(--danger);
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.88rem;
            border: 1.5px solid rgba(239,68,68,0.25);
            cursor: pointer;
            transition: all 0.25s;
        }
        .btn-profil-danger:hover {
            background: var(--danger-light);
            border-color: var(--danger);
        }

        .profil-form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #F3F4F6;
        }

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
           MEMBER STATUS CARD (Dark Green)
           Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        .member-card {
            background: linear-gradient(135deg, #1B4332, #2D6A4F, #40916C);
            border-radius: var(--card-radius);
            padding: 28px;
            color: #fff;
            position: relative;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 24px rgba(27,67,50,0.25);
        }
        .member-card::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 140px; height: 140px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .member-card::after {
            content: '';
            position: absolute;
            bottom: -30px; left: -30px;
            width: 100px; height: 100px;
            border-radius: 50%;
            background: rgba(212,163,115,0.08);
        }

        .member-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .member-card-header i {
            font-size: 1.2rem;
            color: var(--accent-gold);
        }
        .member-card-header span {
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        .member-tier {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.6rem;
            margin-bottom: 4px;
            background: linear-gradient(90deg, #D4A373, #F0D9B5, #D4A373);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
        }
        .member-tier-sub {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.6);
            margin-bottom: 20px;
        }

        .member-progress-wrap {
            margin-bottom: 8px;
        }
        .member-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .member-progress-header span {
            font-size: 0.76rem;
            color: rgba(255,255,255,0.7);
        }
        .member-progress-header strong {
            font-size: 0.82rem;
            color: var(--accent-gold);
            font-family: var(--font-mono);
        }
        .member-progress-bar {
            height: 8px;
            border-radius: 8px;
            background: rgba(255,255,255,0.12);
            overflow: hidden;
        }
        .member-progress-fill {
            height: 100%;
            border-radius: 8px;
            background: linear-gradient(90deg, var(--accent-gold), #F0D9B5);
            width: 65%;
            transition: width 1s ease;
        }

        .member-next-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(255,255,255,0.07);
        }
        .member-next-info i {
            color: var(--accent-gold);
            font-size: 0.88rem;
        }
        .member-next-info span {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.75);
        }

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
           QUICK ACTIONS CARD
           Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        .quick-actions-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            padding: 24px 24px 16px;
            border: none;
            margin-top: 24px;
        }
        .quick-actions-title {
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-dark);
            margin-bottom: 16px;
        }
        .quick-action-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 16px;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.25s;
            margin-bottom: 6px;
            text-decoration: none;
        }
        .quick-action-item:hover {
            background: var(--primary-lighter);
        }
        .quick-action-icon {
            width: 38px; height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .quick-action-icon.green  { background: var(--primary-lighter); color: var(--primary); }
        .quick-action-icon.blue   { background: rgba(59,130,246,0.10); color: var(--info); }
        .quick-action-icon.gold   { background: var(--accent-gold-light); color: #B8863A; }
        .quick-action-icon.red    { background: var(--danger-light); color: var(--danger); }

        .quick-action-info {
            flex: 1;
            min-width: 0;
        }
        .quick-action-info h6 {
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-dark);
            margin: 0 0 1px;
        }
        .quick-action-info p {
            font-size: 0.73rem;
            color: var(--text-muted);
            margin: 0;
        }
        .quick-action-item .bi-chevron-right {
            color: var(--text-light);
            font-size: 0.78rem;
            flex-shrink: 0;
        }
        .quick-action-item.danger-action:hover {
            background: var(--danger-light);
        }
        .quick-action-item.danger-action h6 { color: var(--danger); }

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
           MODALS
           Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        .modal-content {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: 0 8px 40px rgba(0,0,0,0.12);
        }
        .modal-header {
            border-bottom: 1px solid #F3F4F6;
            padding: 20px 28px;
        }
        .modal-header .modal-title {
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--text-dark);
        }
        .modal-body { padding: 24px 28px; }
        .modal-footer {
            border-top: 1px solid #F3F4F6;
            padding: 16px 28px;
            gap: 10px;
        }

        /* Danger Modal */
        .modal-danger .modal-header {
            background: linear-gradient(135deg, var(--danger), #DC2626);
            color: #fff;
            border-bottom: none;
        }
        .modal-danger .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .modal-danger .modal-header .modal-title { color: #fff; }

        .danger-warning-box {
            background: var(--danger-light);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .danger-warning-box i {
            color: var(--danger);
            font-size: 1.2rem;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .danger-warning-box p {
            font-size: 0.84rem;
            color: var(--text-dark);
            margin: 0;
            line-height: 1.5;
        }

        .danger-confirm-input {
            font-family: var(--font-mono);
            letter-spacing: 2px;
            text-align: center;
        }

        /* OTP Digits */
        .otp-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        .otp-digit {
            width: 48px; height: 56px;
            border-radius: 12px;
            border: 1.5px solid #E5E7EB;
            text-align: center;
            font-family: var(--font-mono);
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            outline: none;
            transition: all 0.25s;
        }
        .otp-digit:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(82,183,136,0.12);
        }

        .otp-timer {
            text-align: center;
            font-size: 0.82rem;
            color: var(--text-muted);
        }
        .otp-timer strong {
            color: var(--primary);
            font-family: var(--font-mono);
        }
        .otp-resend {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            font-size: 0.82rem;
            text-decoration: underline;
            transition: color 0.2s;
        }
        .otp-resend:hover { color: var(--primary-light); }
        .otp-resend:disabled { color: var(--text-light); cursor: not-allowed; text-decoration: none; }

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
           TOAST SYSTEM
           Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .profil-toast {
            min-width: 320px;
            max-width: 420px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            overflow: hidden;
            animation: slideInRight 0.35s ease both;
            position: relative;
        }
        .profil-toast.removing {
            animation: slideOutRight 0.3s ease both;
        }
        .profil-toast-body {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 18px;
        }
        .profil-toast-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .profil-toast.success .profil-toast-icon { background: rgba(16,185,129,0.12); color: var(--success); }
        .profil-toast.error   .profil-toast-icon { background: var(--danger-light); color: var(--danger); }
        .profil-toast.info    .profil-toast-icon { background: rgba(59,130,246,0.10); color: var(--info); }

        .profil-toast-text h6 {
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-dark);
            margin: 0 0 1px;
        }
        .profil-toast-text p {
            font-size: 0.76rem;
            color: var(--text-muted);
            margin: 0;
        }
        .profil-toast-close {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1rem;
            margin-left: auto;
            padding: 4px;
            transition: color 0.2s;
        }
        .profil-toast-close:hover { color: var(--text-dark); }
        .profil-toast-progress {
            height: 3px;
            background: #F3F4F6;
        }
        .profil-toast-progress-fill {
            height: 100%;
            animation: toastProgress 3s linear forwards;
        }
        .profil-toast.success .profil-toast-progress-fill { background: var(--success); }
        .profil-toast.error   .profil-toast-progress-fill { background: var(--danger); }
        .profil-toast.info    .profil-toast-progress-fill { background: var(--info); }

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
           RESPONSIVE
           Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        @media (max-width: 1100px) {
            .profil-hero { flex-wrap: wrap; gap: 20px; padding: 28px 24px; }
            .profil-stats-row { margin-left: 0; width: 100%; justify-content: center; }
            .profil-columns { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .profil-stats-row { flex-wrap: wrap; }
            .profil-stat { padding: 10px 18px; }
            .profil-input-row { grid-template-columns: 1fr; }
            .profil-hero-name { font-size: 1.25rem; }
            .profil-tabs-header { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<div class="pelanggan-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>

    <div class="pelanggan-main">
        <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>

        <!-- Content -->
        <div class="pelanggan-content">
            <div class="container-fluid p-4">

                <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â PROFILE HERO CARD Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
                <div class="profil-hero fade-up" id="profil-hero-card">
                    <div class="profil-avatar-wrap">
                        <div class="profil-avatar" id="profil-avatar">
                            <?php
                                $parts = explode(' ', $user_name);
                                $initials = strtoupper(substr($parts[0], 0, 1));
                                if (isset($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));
                                echo $initials;
                            ?>
                        </div>
                        <div class="profil-avatar-edit" title="Ganti foto profil" id="btn-change-avatar">
                            <i class="bi bi-camera-fill"></i>
                        </div>
                    </div>

                    <div class="profil-hero-info">
                        <h1 class="profil-hero-name"><?= htmlspecialchars($user_name) ?></h1>
                        <p class="profil-hero-email">
                            <i class="bi bi-envelope-fill"></i>
                            <?= htmlspecialchars($user_email) ?>
                        </p>
                        <span class="profil-badge-gold">
                            <i class="bi bi-gem"></i>
                            <?= htmlspecialchars($member_level) ?> Member
                        </span>
                    </div>

                    <div class="profil-stats-row">
                        <div class="profil-stat">
                            <div class="profil-stat-value"><?= $member_total_trx ?></div>
                            <div class="profil-stat-label">Transaksi</div>
                        </div>
                        <div class="profil-stat">
                            <div class="profil-stat-value"><?= $total_sewa_display ?></div>
                            <div class="profil-stat-label">Total Belanja</div>
                        </div>
                        <div class="profil-stat">
                            <div class="profil-stat-value"><?= $user_joined_months ?></div>
                            <div class="profil-stat-label">Bulan</div>
                        </div>
                    </div>
                </div>

                <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â TWO COLUMN LAYOUT Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
                <div class="profil-columns">

                    <!-- LEFT: Tabbed Form Card -->
                    <div class="profil-card fade-up fade-up-d2" id="profil-form-card">
                        <div class="profil-tabs-header" id="profil-tabs-header">
                            <button class="profil-tab-pill active" data-tab="info" id="tab-btn-info">
                                <i class="bi bi-person-fill me-1"></i> Info Pribadi
                            </button>
                            <button class="profil-tab-pill" data-tab="keamanan" id="tab-btn-keamanan">
                                <i class="bi bi-shield-lock-fill me-1"></i> Keamanan
                            </button>
                            <button class="profil-tab-pill" data-tab="preferensi" id="tab-btn-preferensi">
                                <i class="bi bi-sliders me-1"></i> Preferensi
                            </button>
                        </div>

                        <!-- Tab: Info Pribadi -->
                        <div class="profil-tab-content active" id="tab-info">
                            <form id="form-info-pribadi">
                                <div class="profil-input-row">
                                    <div class="profil-form-group">
                                        <label class="profil-form-label">Nama Depan</label>
                                        <div class="profil-input-wrap">
                                            <i class="bi bi-person input-icon"></i>
                                            <input type="text" class="profil-input" value="<?= htmlspecialchars($first_name) ?>" id="input-first-name">
                                        </div>
                                    </div>
                                    <div class="profil-form-group">
                                        <label class="profil-form-label">Nama Belakang</label>
                                        <div class="profil-input-wrap">
                                            <i class="bi bi-person input-icon"></i>
                                            <input type="text" class="profil-input" value="<?= htmlspecialchars($last_name) ?>" id="input-last-name">
                                        </div>
                                    </div>
                                </div>

                                <div class="profil-form-group">
                                    <label class="profil-form-label">Alamat Email</label>
                                    <div class="profil-input-wrap">
                                        <i class="bi bi-envelope input-icon"></i>
                                        <input type="email" class="profil-input" value="<?= htmlspecialchars($user_email) ?>" id="input-email">
                                    </div>
                                </div>

                                <div class="profil-form-group">
                                    <label class="profil-form-label">Nomor Telepon</label>
                                    <div class="profil-input-wrap">
                                        <i class="bi bi-phone input-icon"></i>
                                        <input type="tel" class="profil-input" value="<?= htmlspecialchars($user_phone) ?>" id="input-phone">
                                    </div>
                                </div>

                                <div class="profil-input-row">
                                    <div class="profil-form-group">
                                        <label class="profil-form-label">Tanggal Lahir</label>
                                        <div class="profil-input-wrap">
                                            <i class="bi bi-calendar3 input-icon"></i>
                                            <input type="date" class="profil-input" value="" id="input-dob">
                                        </div>
                                    </div>
                                    <div class="profil-form-group">
                                        <label class="profil-form-label">Jenis Kelamin</label>
                                        <div class="profil-input-wrap">
                                            <i class="bi bi-gender-ambiguous input-icon"></i>
                                            <select class="profil-select" id="input-gender">
                                                <option value="" selected>Pilih</option>
                                                <option value="L">Laki-laki</option>
                                                <option value="P">Perempuan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="profil-form-group">
                                    <label class="profil-form-label">Alamat</label>
                                    <div class="profil-input-wrap">
                                        <i class="bi bi-geo-alt input-icon" style="top: 20px; transform: none;"></i>
                                        <textarea class="profil-input" rows="2" style="resize: none; padding-left: 42px;" id="input-address"><?= htmlspecialchars($user_address) ?></textarea>
                                    </div>
                                </div>

                                <div class="profil-form-actions">
                                    <button type="button" class="btn-profil-secondary" id="btn-reset-info">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn-profil-primary" id="btn-save-info">
                                        <i class="bi bi-check2 me-1"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Tab: Keamanan -->
                        <div class="profil-tab-content" id="tab-keamanan">
                            <form id="form-keamanan">
                                <div class="profil-form-group">
                                    <label class="profil-form-label">Password Saat Ini</label>
                                    <div class="profil-input-wrap">
                                        <i class="bi bi-lock input-icon"></i>
                                        <input type="password" class="profil-input" placeholder="Masukkan password saat ini" id="input-current-pwd">
                                        <button type="button" class="pwd-toggle" data-target="input-current-pwd" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="profil-form-group">
                                    <label class="profil-form-label">Password Baru</label>
                                    <div class="profil-input-wrap">
                                        <i class="bi bi-lock-fill input-icon"></i>
                                        <input type="password" class="profil-input" placeholder="Minimal 8 karakter" id="input-new-pwd">
                                        <button type="button" class="pwd-toggle" data-target="input-new-pwd" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="pwd-strength-bar">
                                        <div class="pwd-strength-fill" id="pwd-strength-fill"></div>
                                    </div>
                                    <div class="pwd-strength-label" id="pwd-strength-label">Masukkan password untuk melihat kekuatan</div>
                                </div>

                                <div class="profil-form-group">
                                    <label class="profil-form-label">Konfirmasi Password Baru</label>
                                    <div class="profil-input-wrap">
                                        <i class="bi bi-lock-fill input-icon"></i>
                                        <input type="password" class="profil-input" placeholder="Ulangi password baru" id="input-confirm-pwd">
                                        <button type="button" class="pwd-toggle" data-target="input-confirm-pwd" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="profil-form-actions">
                                    <button type="button" class="btn-profil-secondary" id="btn-reset-keamanan">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Batal
                                    </button>
                                    <button type="submit" class="btn-profil-primary" id="btn-save-keamanan">
                                        <i class="bi bi-shield-check me-1"></i> Perbarui Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Tab: Preferensi -->
                        <div class="profil-tab-content" id="tab-preferensi">
                            <div class="profil-toggle-row">
                                <div class="profil-toggle-info">
                                    <h6><i class="bi bi-bell-fill me-2" style="color: var(--primary-light);"></i>Notifikasi Email</h6>
                                    <p>Terima pembaruan promo & transaksi via email</p>
                                </div>
                                <label class="toggle-switch" id="toggle-email-notif">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="profil-toggle-row">
                                <div class="profil-toggle-info">
                                    <h6><i class="bi bi-chat-dots-fill me-2" style="color: var(--info);"></i>Notifikasi SMS</h6>
                                    <p>Terima notifikasi penting via SMS</p>
                                </div>
                                <label class="toggle-switch" id="toggle-sms-notif">
                                    <input type="checkbox">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="profil-toggle-row">
                                <div class="profil-toggle-info">
                                    <h6><i class="bi bi-shield-fill-check me-2" style="color: var(--accent-gold);"></i>Verifikasi 2 Langkah</h6>
                                    <p>Tingkatkan keamanan akun Anda</p>
                                </div>
                                <label class="toggle-switch" id="toggle-2fa">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div style="margin-top: 24px;">
                                <label class="profil-form-label">Tema Tampilan</label>
                                <div class="theme-selector" id="theme-selector">
                                    <div class="theme-option theme-light active" data-theme="light" title="Light">
                                        <i class="bi bi-sun-fill" style="color: #F59E0B; font-size: 1.1rem;"></i>
                                    </div>
                                    <div class="theme-option theme-dark" data-theme="dark" title="Dark">
                                        <i class="bi bi-moon-stars-fill" style="color: #A5B4FC; font-size: 1rem;"></i>
                                    </div>
                                    <div class="theme-option theme-nature" data-theme="nature" title="Nature">
                                        <i class="bi bi-tree-fill" style="color: #fff; font-size: 1rem;"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="profil-form-group" style="margin-top: 24px;">
                                <label class="profil-form-label">Bahasa</label>
                                <div class="profil-input-wrap">
                                    <i class="bi bi-translate input-icon"></i>
                                    <select class="profil-select" id="input-language">
                                        <option value="id" selected>Ã°Å¸â€¡Â®Ã°Å¸â€¡Â©  Bahasa Indonesia</option>
                                        <option value="en">Ã°Å¸â€¡Â¬Ã°Å¸â€¡Â§  English</option>
                                        <option value="ja">Ã°Å¸â€¡Â¯Ã°Å¸â€¡Âµ  Ã¦â€”Â¥Ã¦Å“Â¬Ã¨ÂªÅ¾</option>
                                    </select>
                                </div>
                            </div>

                            <div class="profil-form-actions">
                                <button type="button" class="btn-profil-primary" id="btn-save-preferensi">
                                    <i class="bi bi-check2 me-1"></i> Simpan Preferensi
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="profil-right-col">
                        <!-- Member Status Card -->
                        <div class="member-card fade-up fade-up-d3" id="member-status-card">
                            <div class="member-card-header">
                                <i class="bi bi-award-fill"></i>
                                <span>Status Keanggotaan</span>
                            </div>
                            <div class="member-tier"><?= htmlspecialchars($member_level) ?> Member</div>
                            <div class="member-tier-sub">Bergabung sejak <?= htmlspecialchars($user_joined) ?></div>

                            <div class="member-progress-wrap">
                                <div class="member-progress-header">
                                    <span>Menuju <?= htmlspecialchars($next_level) ?></span>
                                    <strong><?= number_format($member_total_trx, 0, ',', '.') ?> / <?= number_format($next_threshold, 0, ',', '.') ?> trx</strong>
                                </div>
                                <div class="member-progress-bar">
                                    <div class="member-progress-fill" id="member-progress-fill"></div>
                                </div>
                            </div>

                            <div class="member-next-info">
                                <i class="bi bi-info-circle-fill"></i>
                                <span>Butuh <strong><?= $remaining_trx ?> transaksi</strong> lagi untuk naik ke <strong><?= htmlspecialchars($next_level) ?></strong></span>
                            </div>
                        </div>

                        <!-- Quick Actions Card -->
                        <div class="quick-actions-card fade-up fade-up-d4" id="quick-actions-card">
                            <div class="quick-actions-title">
                                <i class="bi bi-lightning-fill me-1" style="color: var(--accent-gold);"></i>
                                Aksi Cepat
                            </div>

                            <div class="quick-action-item" id="action-riwayat">
                                <div class="quick-action-icon green">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div class="quick-action-info">
                                    <h6>Riwayat Transaksi</h6>
                                    <p>Lihat semua aktivitas pesanan</p>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>

                            <div class="quick-action-item" id="action-bantuan">
                                <div class="quick-action-icon blue">
                                    <i class="bi bi-headset"></i>
                                </div>
                                <div class="quick-action-info">
                                    <h6>Pusat Bantuan</h6>
                                    <p>Hubungi customer service</p>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>

                            <div class="quick-action-item" id="action-download">
                                <div class="quick-action-icon gold">
                                    <i class="bi bi-download"></i>
                                </div>
                                <div class="quick-action-info">
                                    <h6>Download Data</h6>
                                    <p>Unduh salinan data pribadi Anda</p>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>

                            <div class="quick-action-item danger-action" id="action-delete-account">
                                <div class="quick-action-icon red">
                                    <i class="bi bi-person-x-fill"></i>
                                </div>
                                <div class="quick-action-info">
                                    <h6>Nonaktifkan Akun</h6>
                                    <p>Nonaktifkan sementara akun Anda</p>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
     MODAL: Password Confirmation
     Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
<div class="modal fade" id="modalPasswordConfirm" tabindex="-1" aria-labelledby="modalPasswordConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPasswordConfirmLabel">
                    <i class="bi bi-shield-lock me-2" style="color: var(--primary);"></i>
                    Konfirmasi Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 18px;">
                    Untuk keamanan, masukkan password Anda saat ini untuk melanjutkan perubahan.
                </p>
                <div class="profil-form-group mb-0">
                    <div class="profil-input-wrap">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" class="profil-input" placeholder="Password Anda" id="modal-confirm-pwd">
                        <button type="button" class="pwd-toggle" data-target="modal-confirm-pwd" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-profil-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-profil-primary" id="btn-modal-confirm-pwd">
                    <i class="bi bi-check2 me-1"></i> Konfirmasi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
     MODAL: Delete Account (Danger)
     Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
<div class="modal fade" id="modalDeleteAccount" tabindex="-1" aria-labelledby="modalDeleteAccountLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-danger">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDeleteAccountLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Hapus Akun Permanen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="danger-warning-box">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <p>
                        Tindakan ini <strong>tidak dapat dibatalkan</strong>. Semua data Anda termasuk riwayat transaksi,
                        poin member, dan informasi pribadi akan dihapus secara permanen.
                    </p>
                </div>

                <div class="form-check mb-3" style="padding-left: 28px;">
                    <input class="form-check-input" type="checkbox" id="delete-confirm-check" style="border-radius: 4px;">
                    <label class="form-check-label" for="delete-confirm-check" style="font-size: 0.84rem; color: var(--text-dark);">
                        Saya memahami bahwa semua data akan dihapus permanen
                    </label>
                </div>

                <div class="profil-form-group mb-0">
                    <label class="profil-form-label">Ketik <strong style="color: var(--danger); font-family: var(--font-mono);">HAPUS</strong> untuk konfirmasi</label>
                    <input type="text" class="profil-input no-icon danger-confirm-input" placeholder="HAPUS" id="delete-confirm-text">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-profil-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-profil-danger" id="btn-confirm-delete" disabled>
                    <i class="bi bi-trash3 me-1"></i> Hapus Akun Saya
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
     MODAL: Email Verification (6-digit OTP)
     Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
<div class="modal fade" id="modalEmailVerify" tabindex="-1" aria-labelledby="modalEmailVerifyLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEmailVerifyLabel">
                    <i class="bi bi-envelope-check me-2" style="color: var(--primary);"></i>
                    Verifikasi Email
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--primary-lighter); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="bi bi-envelope-paper-fill" style="font-size: 1.6rem; color: var(--primary);"></i>
                </div>
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 4px;">
                    Kami telah mengirim kode verifikasi ke
                </p>
                <p style="font-size: 0.92rem; font-weight: 600; color: var(--text-dark); margin-bottom: 20px;">
                    <?= htmlspecialchars($user_email) ?>
                </p>

                <div class="otp-group" id="otp-group">
                    <input type="text" maxlength="1" class="otp-digit" data-index="0" id="otp-0" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-digit" data-index="1" id="otp-1" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-digit" data-index="2" id="otp-2" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-digit" data-index="3" id="otp-3" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-digit" data-index="4" id="otp-4" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-digit" data-index="5" id="otp-5" inputmode="numeric">
                </div>

                <div class="otp-timer" id="otp-timer">
                    Kirim ulang dalam <strong id="otp-countdown">60</strong> detik
                </div>
                <button class="otp-resend" id="btn-otp-resend" disabled>Kirim Ulang Kode</button>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn-profil-primary" id="btn-verify-otp" style="min-width: 200px;">
                    <i class="bi bi-patch-check me-1"></i> Verifikasi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Download Data -->
<div class="modal fade" id="modalDownloadData" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none; border-radius:20px; overflow:hidden; box-shadow:0 25px 80px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:#F8FAF9; border-bottom:1px solid rgba(0,0,0,0.04); padding:1.5rem;">
                <h5 class="modal-title fw-bold" style="font-family:'Outfit',sans-serif; color:var(--text-primary);">
                    <i class="bi bi-cloud-arrow-down me-2" style="color:var(--primary);"></i>Unduh Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4" style="font-size:0.9rem;">Pilih format file untuk mengunduh laporan data pribadi dan riwayat transaksi Anda:</p>
                <div class="d-grid gap-3">
                    <button type="button" class="btn text-start" style="border-radius:12px; padding:1rem 1.2rem; border:1.5px solid #dcfce7; color:#166534; background:#f0fdf4;" onclick="executeDownload('csv')">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:42px;height:42px;border-radius:12px;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-file-earmark-spreadsheet fs-4" style="color:#16a34a;"></i>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:0.95rem;">Format Excel / CSV (.csv)</div>
                                <div style="font-size:0.78rem;color:#6b7280;margin-top:2px;">Bisa dibuka di Excel / Google Sheets</div>
                            </div>
                        </div>
                    </button>
                    <button type="button" class="btn text-start" style="border-radius:12px; padding:1rem 1.2rem; border:1.5px solid #fee2e2; color:#991b1b; background:#fef2f2;" onclick="executeDownload('pdf')">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:42px;height:42px;border-radius:12px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-file-earmark-pdf fs-4" style="color:#dc2626;"></i>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:0.95rem;">Format Lengkap (.pdf)</div>
                                <div style="font-size:0.78rem;color:#6b7280;margin-top:2px;">Langsung diunduh sebagai dokumen PDF</div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  TAB SYSTEM
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    const tabPills = document.querySelectorAll('.profil-tab-pill');
    const tabContents = document.querySelectorAll('.profil-tab-content');

    tabPills.forEach(pill => {
        pill.addEventListener('click', function () {
            const target = this.dataset.tab;

            tabPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            tabContents.forEach(tc => tc.classList.remove('active'));
            document.getElementById('tab-' + target).classList.add('active');
        });
    });

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  PASSWORD TOGGLE VISIBILITY
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    document.querySelectorAll('.pwd-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  PASSWORD STRENGTH METER
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    const newPwdInput = document.getElementById('input-new-pwd');
    const strengthFill = document.getElementById('pwd-strength-fill');
    const strengthLabel = document.getElementById('pwd-strength-label');

    if (newPwdInput) {
        newPwdInput.addEventListener('input', function () {
            const val = this.value;
            let score = 0;
            let label = '';

            if (val.length === 0) {
                strengthFill.className = 'pwd-strength-fill';
                strengthLabel.textContent = 'Masukkan password untuk melihat kekuatan';
                return;
            }

            if (val.length >= 8) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;

            if (score <= 1)      { label = 'Ã°Å¸â€Â´ Lemah Ã¢â‚¬â€ Tambahkan huruf besar, angka, dan simbol'; strengthFill.className = 'pwd-strength-fill weak'; }
            else if (score === 2) { label = 'Ã°Å¸Å¸Â¡ Cukup Ã¢â‚¬â€ Tambahkan variasi karakter'; strengthFill.className = 'pwd-strength-fill fair'; }
            else if (score === 3) { label = 'Ã°Å¸â€Âµ Bagus Ã¢â‚¬â€ Hampir kuat!'; strengthFill.className = 'pwd-strength-fill good'; }
            else                  { label = 'Ã°Å¸Å¸Â¢ Kuat Ã¢â‚¬â€ Password sangat aman!'; strengthFill.className = 'pwd-strength-fill strong'; }

            strengthLabel.textContent = label;
        });
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  THEME SELECTOR
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    document.querySelectorAll('.theme-option').forEach(opt => {
        opt.addEventListener('click', function () {
            document.querySelectorAll('.theme-option').forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            showToast('success', 'Tema Diperbarui', 'Tema ' + this.dataset.theme + ' telah diterapkan.');
        });
    });

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  TOAST SYSTEM
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    function showToast(type, title, message) {
        const container = document.getElementById('toast-container');
        const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };

        const toast = document.createElement('div');
        toast.className = 'profil-toast ' + type;
        toast.innerHTML = `
            <div class="profil-toast-body">
                <div class="profil-toast-icon"><i class="bi ${icons[type]}"></i></div>
                <div class="profil-toast-text">
                    <h6>${title}</h6>
                    <p>${message}</p>
                </div>
                <button class="profil-toast-close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="profil-toast-progress"><div class="profil-toast-progress-fill"></div></div>
        `;

        container.appendChild(toast);

        const closeBtn = toast.querySelector('.profil-toast-close');
        closeBtn.addEventListener('click', () => dismissToast(toast));

        setTimeout(() => dismissToast(toast), 3000);
    }

    function dismissToast(toast) {
        if (toast.classList.contains('removing')) return;
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  FORM SUBMISSIONS
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    document.getElementById('form-info-pribadi').addEventListener('submit', function (e) {
        e.preventDefault();
        // Show password confirmation modal
        const modal = new bootstrap.Modal(document.getElementById('modalPasswordConfirm'));
        modal.show();
    });

    document.getElementById('btn-modal-confirm-pwd').addEventListener('click', function () {
        const pwd = document.getElementById('modal-confirm-pwd').value;
        if (!pwd) {
            showToast('error', 'Gagal', 'Silakan masukkan password Anda.');
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('modalPasswordConfirm')).hide();
        
        // Submit profile update via hidden form
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const fields = {
            'action': 'update_profile',
            'nama': document.getElementById('input-nama')?.value || '',
            'email': document.getElementById('input-email')?.value || '',
            'no_telp': document.getElementById('input-phone')?.value || '',
            'alamat': document.getElementById('input-alamat')?.value || '',
            'confirm_password': pwd
        };
        
        for (const [key, val] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = val;
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
    });

    document.getElementById('form-keamanan').addEventListener('submit', function (e) {
        e.preventDefault();
        const newPwd = document.getElementById('input-new-pwd').value;
        const confirmPwd = document.getElementById('input-confirm-pwd').value;
        const currentPwd = document.getElementById('input-current-pwd').value;

        if (!currentPwd) {
            showToast('error', 'Gagal', 'Masukkan password saat ini.');
            return;
        }
        if (newPwd.length < 8) {
            showToast('error', 'Gagal', 'Password baru minimal 8 karakter.');
            return;
        }
        if (newPwd !== confirmPwd) {
            showToast('error', 'Gagal', 'Konfirmasi password tidak cocok.');
            return;
        }

        // Submit password change via hidden form
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const fields = {
            'action': 'change_password',
            'current_password': currentPwd,
            'new_password': newPwd,
            'confirm_new_password': confirmPwd
        };
        
        for (const [key, val] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = val;
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
        return;
        document.getElementById('pwd-strength-fill').className = 'pwd-strength-fill';
        document.getElementById('pwd-strength-label').textContent = 'Masukkan password untuk melihat kekuatan';
    });

    document.getElementById('btn-save-preferensi').addEventListener('click', function () {
        showToast('success', 'Preferensi Disimpan', 'Pengaturan preferensi Anda telah diperbarui.');
    });

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  RESET BUTTONS
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    document.getElementById('btn-reset-info').addEventListener('click', function () {
        document.getElementById('form-info-pribadi').reset();
        showToast('info', 'Form Direset', 'Semua perubahan telah dikembalikan.');
    });

    document.getElementById('btn-reset-keamanan').addEventListener('click', function () {
        document.getElementById('form-keamanan').reset();
        document.getElementById('pwd-strength-fill').className = 'pwd-strength-fill';
        document.getElementById('pwd-strength-label').textContent = 'Masukkan password untuk melihat kekuatan';
    });

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  DELETE ACCOUNT MODAL
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    document.getElementById('action-delete-account').addEventListener('click', function () {
        const modal = new bootstrap.Modal(document.getElementById('modalDeleteAccount'));
        modal.show();
    });

    const deleteCheck = document.getElementById('delete-confirm-check');
    const deleteText = document.getElementById('delete-confirm-text');
    const deleteBtn = document.getElementById('btn-confirm-delete');

    function validateDelete() {
        const checked = deleteCheck.checked;
        const typed = deleteText.value.trim() === 'HAPUS';
        deleteBtn.disabled = !(checked && typed);
    }

    deleteCheck.addEventListener('change', validateDelete);
    deleteText.addEventListener('input', validateDelete);

    deleteBtn.addEventListener('click', function () {
        bootstrap.Modal.getInstance(document.getElementById('modalDeleteAccount')).hide();
        showToast('error', 'Akun Dihapus', 'Akun Anda telah dijadwalkan untuk dihapus dalam 30 hari.');
        // Reset modal
        deleteCheck.checked = false;
        deleteText.value = '';
        deleteBtn.disabled = true;
    });

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  EMAIL VERIFICATION / OTP
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    const otpDigits = document.querySelectorAll('.otp-digit');

    otpDigits.forEach((digit, index) => {
        digit.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
            if (this.value && index < 5) {
                otpDigits[index + 1].focus();
            }
        });
        digit.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                otpDigits[index - 1].focus();
            }
        });
        digit.addEventListener('paste', function (e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            paste.split('').forEach((ch, i) => {
                if (otpDigits[i]) otpDigits[i].value = ch;
            });
            if (paste.length > 0 && paste.length <= 5) otpDigits[paste.length].focus();
        });
    });

    // OTP Timer
    let otpSeconds = 60;
    let otpInterval = null;

    function startOtpTimer() {
        otpSeconds = 60;
        const countdownEl = document.getElementById('otp-countdown');
        const timerEl = document.getElementById('otp-timer');
        const resendBtn = document.getElementById('btn-otp-resend');
        resendBtn.disabled = true;
        timerEl.style.display = '';

        if (otpInterval) clearInterval(otpInterval);
        otpInterval = setInterval(() => {
            otpSeconds--;
            countdownEl.textContent = otpSeconds;
            if (otpSeconds <= 0) {
                clearInterval(otpInterval);
                timerEl.style.display = 'none';
                resendBtn.disabled = false;
            }
        }, 1000);
    }

    // Open email verify modal (e.g., when changing email)
    document.getElementById('input-email').addEventListener('change', function () {
        const modal = new bootstrap.Modal(document.getElementById('modalEmailVerify'));
        modal.show();
        startOtpTimer();
    });

    document.getElementById('btn-otp-resend').addEventListener('click', function () {
        startOtpTimer();
        showToast('info', 'Kode Dikirim', 'Kode verifikasi baru telah dikirim ke email Anda.');
    });

    document.getElementById('btn-verify-otp').addEventListener('click', function () {
        let code = '';
        otpDigits.forEach(d => code += d.value);
        if (code.length < 6) {
            showToast('error', 'Gagal', 'Masukkan 6 digit kode verifikasi.');
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('modalEmailVerify')).hide();
        otpDigits.forEach(d => d.value = '');
        if (otpInterval) clearInterval(otpInterval);
        showToast('success', 'Email Diverifikasi', 'Alamat email baru Anda telah berhasil diverifikasi.');
    });

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  QUICK ACTIONS
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    document.getElementById('action-riwayat').addEventListener('click', function () {
        window.location.href = '<?= BASE_URL ?>/pages/pelanggan/transaksi.php';
    });

    document.getElementById('action-bantuan').addEventListener('click', function () {
        window.open('https://wa.me/6281333715914?text=Halo%20Admin%20SIMPEL-CAMP,%20saya%20butuh%20bantuan.', '_blank');
    });

    document.getElementById('action-download').addEventListener('click', function () {
        new bootstrap.Modal(document.getElementById('modalDownloadData')).show();
    });

    window.executeDownload = function(format) {
        bootstrap.Modal.getInstance(document.getElementById('modalDownloadData')).hide();
        if (format === 'pdf') {
            window.open('<?= BASE_URL ?>/pages/pelanggan/download_data.php?format=pdf', '_blank');
        } else {
            window.location.href = '<?= BASE_URL ?>/pages/pelanggan/download_data.php?format=' + format;
        }
    };

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  AVATAR CHANGE
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    document.getElementById('btn-change-avatar').addEventListener('click', function () {
        showToast('info', 'Ganti Foto', 'Fitur upload foto profil akan segera tersedia.');
    });

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  MEMBER PROGRESS ANIMATION
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    setTimeout(() => {
        const fill = document.getElementById('member-progress-fill');
        if (fill) fill.style.width = '<?= $progress_pct ?>%';
    }, 600);

});
</script>

</body>
</html>
