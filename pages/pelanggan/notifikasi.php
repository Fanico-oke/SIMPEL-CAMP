<?php
// pages/pelanggan/notifikasi.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Notifikasi.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Handle mark all read action
if (isset($_POST['mark_all_read'])) {
    Notifikasi::markAllRead($_SESSION['user_id']);
    header('Location: ' . BASE_URL . '/pages/pelanggan/notifikasi.php');
    exit;
}

// Handle delete action
if (isset($_POST['delete_notif_id'])) {
    Notifikasi::delete((int)$_POST['delete_notif_id']);
    header('Location: ' . BASE_URL . '/pages/pelanggan/notifikasi.php');
    exit;
}

// Handle mark single read
if (isset($_POST['mark_read_id'])) {
    Notifikasi::markRead((int)$_POST['mark_read_id']);
    header('Location: ' . BASE_URL . '/pages/pelanggan/notifikasi.php');
    exit;
}

$page_title = 'Notifikasi';
$current_page = 'notifikasi';
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// Load notifications from database
$notifications = Notifikasi::getByUser($_SESSION['user_id'], 50);
$unread_count = Notifikasi::countUnread($_SESSION['user_id']);
$total_count = count($notifications);

// Count by category (tipe maps to category)
$count_transaksi = 0;
$count_promo = 0;
$count_sistem = 0;
foreach ($notifications as $notif) {
    $tipe = $notif['tipe'] ?? 'info';
    if (in_array($tipe, ['success', 'warning'])) {
        $count_transaksi++;
    } elseif ($tipe === 'danger') {
        $count_promo++;
    } else {
        $count_sistem++;
    }
}

// Helper: map notification tipe to visual style
function getNotifStyle($tipe) {
    $styles = [
        'success' => ['icon' => 'bi-check-circle-fill', 'ic_class' => 'ic-success', 'bar' => 'bar-success', 'tag' => 'transaksi', 'tag_label' => 'Transaksi', 'category' => 'transaksi'],
        'warning' => ['icon' => 'bi-exclamation-triangle-fill', 'ic_class' => 'ic-warning', 'bar' => 'bar-warning', 'tag' => 'transaksi', 'tag_label' => 'Transaksi', 'category' => 'transaksi'],
        'danger'  => ['icon' => 'bi-lightning-charge-fill', 'ic_class' => 'ic-promo', 'bar' => 'bar-promo', 'tag' => 'promo', 'tag_label' => 'Promo', 'category' => 'promo'],
        'info'    => ['icon' => 'bi-info-circle-fill', 'ic_class' => 'ic-info', 'bar' => 'bar-info', 'tag' => 'sistem', 'tag_label' => 'Sistem', 'category' => 'sistem'],
    ];
    return $styles[$tipe] ?? $styles['info'];
}

// Helper: time ago
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' tahun lalu';
    if ($diff->m > 0) return $diff->m . ' bulan lalu';
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pusat Notifikasi - Kelola semua pemberitahuan dan update terbaru Anda">
    <title><?= htmlspecialchars($page_title) ?> - SIMPEL-CAMP</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">

    <style>
        :root {
            --page-bg: #F2F7F4;
            --card-bg: #FFFFFF;
            --card-radius: 20px;
            --card-shadow: 0 2px 20px rgba(0,0,0,0.04);
            --primary: #2D6A4F;
            --primary-light: #52B788;
            --accent-gold: #D4A373;
            --text-dark: #1A1A2E;
            --text-muted: #6B7280;
            --pill-radius: 50px;
            --input-radius: 12px;
        }

        .notif-page-wrapper {
            padding: 1rem 0;
            min-height: 100vh;
        }

        /* ── Header ── */
        .notif-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
            background: var(--card-bg);
            padding: 20px 24px;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }
        .notif-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .notif-header-left .bell-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.5rem;
            box-shadow: 0 4px 16px rgba(45,106,79,0.22);
            position: relative;
        }
        .notif-header-left .bell-icon .pulse-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 16px;
            border: 2px solid var(--primary-light);
            animation: pulseRing 2s ease-out infinite;
        }
        @keyframes pulseRing {
            0% { transform: scale(1); opacity: .7; }
            100% { transform: scale(1.35); opacity: 0; }
        }
        .notif-header-left h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }
        .unread-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #FEF3C7, #FDE68A);
            color: #92400E;
            font-family: 'Inter', sans-serif;
            font-size: .75rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: var(--pill-radius);
            margin-left: .25rem;
        }
        .unread-badge .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #F59E0B;
            animation: pulseDot 1.4s ease-in-out infinite;
        }
        @keyframes pulseDot {
            0%,100% { opacity: 1; transform: scale(1); }
            50% { opacity: .4; transform: scale(.7); }
        }
        .btn-mark-all {
            font-family: 'Inter', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            padding: 10px 26px;
            border-radius: var(--pill-radius);
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            border: none;
            cursor: pointer;
            transition: all .3s ease;
            display: flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 4px 14px rgba(45,106,79,0.18);
        }
        .btn-mark-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45,106,79,0.28);
            background: linear-gradient(135deg, #245A42, #47A078);
        }
        .btn-mark-all:active { transform: translateY(0); }

        /* ── Filter Pills ── */
        .filter-pills-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
            background: var(--card-bg);
            padding: 16px 24px;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }
        .filter-pill {
            font-family: 'Inter', sans-serif;
            font-size: .8rem;
            font-weight: 600;
            padding: 9px 22px;
            border-radius: var(--pill-radius);
            background: #F3F4F6;
            color: var(--text-muted);
            border: none;
            cursor: pointer;
            transition: all .3s ease;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .filter-pill .pill-count {
            font-family: 'JetBrains Mono', monospace;
            font-size: .7rem;
            font-weight: 600;
            background: #F3F4F6;
            color: var(--text-muted);
            padding: 2px 9px;
            border-radius: var(--pill-radius);
            transition: all .3s ease;
        }
        .filter-pill:hover {
            background: #E8F5EE;
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(45,106,79,0.1);
        }
        .filter-pill.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            box-shadow: 0 4px 16px rgba(45,106,79,0.22);
            transform: translateY(-1px);
        }
        .filter-pill.active .pill-count {
            background: rgba(255,255,255,0.25);
            color: #fff;
        }

        /* â”€â”€ Notification Cards â”€â”€ */
        .notif-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .notif-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            padding: 0;
            position: relative;
            overflow: hidden;
            transition: all .35s cubic-bezier(.4,0,.2,1);
            opacity: 0;
            transform: translateY(24px);
            cursor: pointer;
        }
        .notif-card.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .notif-card:hover {
            box-shadow: 0 6px 28px rgba(0,0,0,0.08);
            transform: translateY(-3px);
        }
        .notif-card.unread { background: #F8FDF9; }
        .notif-card .color-bar {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            border-radius: var(--card-radius) 0 0 var(--card-radius);
        }
        .notif-card .color-bar.bar-success { background: linear-gradient(180deg, var(--primary), var(--primary-light)); }
        .notif-card .color-bar.bar-warning { background: linear-gradient(180deg, #F59E0B, #FBBF24); }
        .notif-card .color-bar.bar-info    { background: linear-gradient(180deg, #3B82F6, #60A5FA); }
        .notif-card .color-bar.bar-promo   { background: linear-gradient(180deg, var(--accent-gold), #E8B98A); }

        .notif-card-inner {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.15rem 1.25rem 1.15rem 1.75rem;
            position: relative;
            transition: transform .3s ease;
        }
        .notif-card .icon-circle {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .icon-circle.ic-success { background: #E8F5EE; color: var(--primary); }
        .icon-circle.ic-warning { background: #FEF3C7; color: #92400E; }
        .icon-circle.ic-info    { background: #DBEAFE; color: #1D4ED8; }
        .icon-circle.ic-promo   { background: #FFF7ED; color: #9A6831; }

        .notif-body { flex: 1; min-width: 0; }
        .notif-title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .notif-title {
            font-family: 'Inter', sans-serif;
            font-size: .9rem;
            font-weight: 650;
            color: var(--text-dark);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notif-card.unread .notif-title { color: var(--primary); }
        .unread-indicator {
            width: 9px;
            height: 9px;
            min-width: 9px;
            border-radius: 50%;
            background: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(82,183,136,0.2);
            animation: pulseDot 1.4s ease-in-out infinite;
        }
        .notif-desc {
            font-family: 'Inter', sans-serif;
            font-size: .8rem;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .notif-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .notif-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: .7rem;
            color: #9CA3AF;
        }
        .notif-tag {
            font-family: 'Inter', sans-serif;
            font-size: .65rem;
            font-weight: 600;
            padding: 3px 12px;
            border-radius: var(--pill-radius);
        }
        .tag-transaksi { background: #E8F5EE; color: var(--primary); }
        .tag-promo     { background: #FFF7ED; color: #9A6831; }
        .tag-sistem    { background: #DBEAFE; color: #1D4ED8; }

        .notif-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
            flex-shrink: 0;
        }
        .btn-notif-action {
            font-family: 'Inter', sans-serif;
            font-size: .72rem;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: var(--pill-radius);
            border: none;
            cursor: pointer;
            transition: all .25s ease;
        }
        .btn-notif-detail {
            background: #E8F5EE;
            color: var(--primary);
        }
        .btn-notif-detail:hover {
            background: var(--primary);
            color: #fff;
        }
        .btn-notif-delete {
            background: #FEE2E2;
            color: #DC2626;
        }
        .btn-notif-delete:hover {
            background: #DC2626;
            color: #fff;
        }

        /* â”€â”€ Slide to delete â”€â”€ */
        .notif-card .slide-delete-zone {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 90px;
            background: linear-gradient(135deg, #EF4444, #DC2626);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.3rem;
            border-radius: 0 var(--card-radius) var(--card-radius) 0;
            opacity: 0;
            transition: opacity .25s ease;
        }
        .notif-card.swiping .slide-delete-zone { opacity: 1; }
        .notif-card.deleting {
            animation: slideOutRight .4s forwards;
        }
        @keyframes slideOutRight {
            to {
                transform: translateX(120%);
                opacity: 0;
                max-height: 0;
                padding: 0;
                margin: 0;
            }
        }

        /* ── Empty State ── */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem 6rem;
            display: none;
            background: var(--card-bg);
            border-radius: var(--card-radius);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .empty-state.show { display: block; }
        .empty-state-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #E8F5EE, #D1FAE5);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: var(--primary-light);
            animation: emptyPulse 2s ease-in-out infinite;
        }
        @keyframes emptyPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .empty-state h3 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--text-dark);
            margin-bottom: .5rem;
        }
        .empty-state p {
            font-family: 'Inter', sans-serif;
            color: var(--text-muted);
            font-size: .88rem;
            max-width: 360px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ── Toast ── */
        .toast-container-custom {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .custom-toast {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--text-dark);
            color: #fff;
            padding: 14px 22px;
            border-radius: 16px;
            font-family: 'Inter', sans-serif;
            font-size: .84rem;
            font-weight: 500;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateX(120%);
            transition: transform .4s cubic-bezier(.4,0,.2,1);
            min-width: 300px;
        }
        .custom-toast.show { transform: translateX(0); }
        .custom-toast .btn-undo {
            font-family: 'Inter', sans-serif;
            font-size: .78rem;
            font-weight: 700;
            color: var(--primary-light);
            background: none;
            border: none;
            cursor: pointer;
            margin-left: auto;
            white-space: nowrap;
        }
        .custom-toast .btn-undo:hover { color: #fff; }
        .custom-toast .toast-timer {
            width: 100%;
            height: 3px;
            background: rgba(255,255,255,0.15);
            position: absolute;
            bottom: 0;
            left: 0;
            border-radius: 0 0 16px 16px;
            overflow: hidden;
        }
        .custom-toast .toast-timer-bar {
            height: 100%;
            background: var(--primary-light);
            border-radius: 0 0 16px 16px;
            animation: timerShrink 5s linear forwards;
        }
        @keyframes timerShrink {
            from { width: 100%; }
            to { width: 0; }
        }

        /* â”€â”€ Detail Modal â”€â”€ */
        .modal-detail .modal-content {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .modal-detail .modal-header {
            border-bottom: 1px solid #F3F4F6;
            padding: 1.25rem 1.5rem;
        }
        .modal-detail .modal-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.1rem;
        }
        .modal-detail .modal-body {
            padding: 1.5rem;
        }
        .modal-detail .detail-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            margin-bottom: 1rem;
        }
        .modal-detail .detail-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--text-dark);
            margin-bottom: .5rem;
        }
        .modal-detail .detail-desc {
            font-family: 'Inter', sans-serif;
            font-size: .88rem;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 1rem;
        }
        .modal-detail .detail-meta-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .modal-detail .detail-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Inter', sans-serif;
            font-size: .8rem;
            color: var(--text-muted);
        }
        .modal-detail .detail-meta-item i { font-size: 1rem; color: var(--primary-light); }
        .modal-detail .modal-footer {
            border-top: 1px solid #F3F4F6;
            padding: 1rem 1.5rem;
            gap: 8px;
        }
        .modal-detail .btn-modal-pill {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: .82rem;
            padding: 10px 26px;
            border-radius: var(--pill-radius);
            border: none;
            cursor: pointer;
            transition: all .3s ease;
        }
        .btn-modal-close {
            background: #F3F4F6;
            color: var(--text-muted);
        }
        .btn-modal-close:hover { background: #E5E7EB; }
        .btn-modal-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            box-shadow: 0 4px 14px rgba(45,106,79,0.18);
        }
        .btn-modal-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45,106,79,0.28);
        }

        /* â”€â”€ Confirm Delete Modal â”€â”€ */
        .modal-confirm .modal-content {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            text-align: center;
            padding: 2rem 1.5rem;
        }
        .modal-confirm .confirm-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #FEE2E2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 2rem;
            color: #DC2626;
        }
        .modal-confirm h5 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: .5rem;
        }
        .modal-confirm p {
            font-family: 'Inter', sans-serif;
            font-size: .85rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        .modal-confirm .confirm-btns {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .modal-confirm .btn-confirm-cancel,
        .modal-confirm .btn-confirm-delete {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: .82rem;
            padding: 10px 30px;
            border-radius: var(--pill-radius);
            border: none;
            cursor: pointer;
            transition: all .3s ease;
        }
        .modal-confirm .btn-confirm-cancel {
            background: #F3F4F6;
            color: var(--text-muted);
        }
        .modal-confirm .btn-confirm-cancel:hover { background: #E5E7EB; }
        .modal-confirm .btn-confirm-delete {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: #fff;
            box-shadow: 0 4px 14px rgba(220,38,38,0.2);
        }
        .modal-confirm .btn-confirm-delete:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(220,38,38,0.3);
        }

        /* â”€â”€ Responsive â”€â”€ */
        @media (max-width: 768px) {
            .notif-header { flex-direction: column; align-items: flex-start; }
            .notif-actions { margin-left: 0; margin-top: 8px; }
            .notif-card-inner { flex-wrap: wrap; }
            .custom-toast { min-width: 260px; }
        }
    </style>
</head>
<body>
<div class="pelanggan-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>

    <div class="pelanggan-main">

        <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>

        <div class="pelanggan-content">
        <div class="notif-page-wrapper">
            <div class="container-fluid px-lg-4">

                <!-- â•â•â• Header â•â•â• -->
                <div class="notif-header">
                    <div class="notif-header-left">
                        <div class="bell-icon">
                            <span class="pulse-ring"></span>
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div>
                            <h1>Pusat Notifikasi</h1>
                            <span class="unread-badge" id="unreadBadge">
                                <span class="pulse-dot"></span>
                                <span id="unreadCount"><?= $unread_count ?></span> belum dibaca
                            </span>
                        </div>
                    </div>
                    <button class="btn-mark-all" id="btnMarkAll" onclick="markAllRead()">
                        <i class="bi bi-check2-all"></i> Tandai Semua Dibaca
                    </button>
                </div>

                <!-- â•â•â• Filter Pills â•â•â• -->
                <div class="filter-pills-row" id="filterPills">
                    <button class="filter-pill active" data-filter="semua" onclick="filterNotif(this, 'semua')">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Semua <span class="pill-count" id="countSemua"><?= $total_count ?></span>
                    </button>
                    <button class="filter-pill" data-filter="unread" onclick="filterNotif(this, 'unread')">
                        <i class="bi bi-envelope-fill"></i> Belum Dibaca <span class="pill-count" id="countUnread"><?= $unread_count ?></span>
                    </button>
                    <button class="filter-pill" data-filter="transaksi" onclick="filterNotif(this, 'transaksi')">
                        <i class="bi bi-receipt"></i> Transaksi <span class="pill-count" id="countTransaksi"><?= $count_transaksi ?></span>
                    </button>
                    <button class="filter-pill" data-filter="promo" onclick="filterNotif(this, 'promo')">
                        <i class="bi bi-gift-fill"></i> Promo <span class="pill-count" id="countPromo"><?= $count_promo ?></span>
                    </button>
                    <button class="filter-pill" data-filter="sistem" onclick="filterNotif(this, 'sistem')">
                        <i class="bi bi-gear-fill"></i> Sistem <span class="pill-count" id="countSistem"><?= $count_sistem ?></span>
                    </button>
                </div>

                <!-- â•â•â• Notification List â•â•â• -->
                <div class="notif-list" id="notifList">

                    <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notif): 
                        $style = getNotifStyle($notif['tipe'] ?? 'info');
                        $is_read = !empty($notif['is_read']);
                        $notif_time = timeAgo($notif['created_at']);
                        $notif_title = htmlspecialchars($notif['judul']);
                        $notif_desc = htmlspecialchars($notif['pesan']);
                    ?>
                    <div class="notif-card<?= !$is_read ? ' unread' : '' ?>" data-id="<?= $notif['id'] ?>" data-category="<?= $style['category'] ?>" data-read="<?= $is_read ? 'true' : 'false' ?>"
                         data-title="<?= $notif_title ?>"
                         data-desc="<?= $notif_desc ?>"
                         data-time="<?= htmlspecialchars($notif_time) ?>"
                         data-icon="<?= $style['icon'] ?>"
                         data-icon-class="<?= $style['ic_class'] ?>"
                         data-bar="<?= $style['bar'] ?>"
                         data-tag="<?= $style['tag'] ?>">
                        <div class="color-bar <?= $style['bar'] ?>"></div>
                        <div class="slide-delete-zone"><i class="bi bi-trash3-fill"></i></div>
                        <div class="notif-card-inner">
                            <div class="icon-circle <?= $style['ic_class'] ?>"><i class="bi <?= $style['icon'] ?>"></i></div>
                            <div class="notif-body">
                                <div class="notif-title-row">
                                    <h4 class="notif-title"><?= $notif_title ?></h4>
                                    <?php if (!$is_read): ?><span class="unread-indicator"></span><?php endif; ?>
                                </div>
                                <p class="notif-desc"><?= mb_strlen($notif_desc) > 120 ? mb_substr($notif_desc, 0, 120) . '...' : $notif_desc ?></p>
                                <div class="notif-meta">
                                    <span class="notif-time"><i class="bi bi-clock"></i> <?= htmlspecialchars($notif_time) ?></span>
                                    <span class="notif-tag tag-<?= $style['tag'] ?>"><?= $style['tag_label'] ?></span>
                                </div>
                            </div>
                            <div class="notif-actions">
                                <button class="btn-notif-action btn-notif-detail" onclick="event.stopPropagation(); showDetail(this.closest('.notif-card'))">Detail</button>
                                <button class="btn-notif-action btn-notif-delete" onclick="event.stopPropagation(); confirmDelete(this.closest('.notif-card'))">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Remaining hardcoded notification cards removed - all rendered dynamically above -->

                </div>

                <!-- â•â•â• Empty State â•â•â• -->
                <div class="empty-state" id="emptyState">
                    <div class="empty-state-icon">
                        <i class="bi bi-bell-slash"></i>
                    </div>
                    <h3>Belum Ada Notifikasi</h3>
                    <p>Notifikasi akan muncul di sini saat ada update pesanan, promo baru, atau informasi penting lainnya.</p>
                    <a href="<?= BASE_URL ?>/pages/pelanggan/katalog.php" style="display:inline-flex; align-items:center; gap:8px; margin-top:1.5rem; padding:12px 24px; background:linear-gradient(135deg,var(--primary),var(--primary-light)); color:#fff; border-radius:12px; text-decoration:none; font-weight:600; font-size:0.88rem; transition:all 0.25s;">
                        <i class="bi bi-compass"></i> Jelajahi Katalog
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- â•â•â• Detail Modal â•â•â• -->
    <div class="modal fade modal-detail" id="modalDetail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Detail Notifikasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="detail-icon-wrap" id="detailIconWrap">
                        <i id="detailIcon"></i>
                    </div>
                    <h4 class="detail-title" id="detailTitle"></h4>
                    <p class="detail-desc" id="detailDesc"></p>
                    <div class="detail-meta-row">
                        <div class="detail-meta-item">
                            <i class="bi bi-clock"></i>
                            <span id="detailTime"></span>
                        </div>
                        <div class="detail-meta-item">
                            <i class="bi bi-tag"></i>
                            <span id="detailTag"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-pill btn-modal-close" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn-modal-pill btn-modal-primary" data-bs-dismiss="modal">
                        <i class="bi bi-check2 me-1"></i>Tandai Dibaca
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- â•â•â• Confirm Delete Modal â•â•â• -->
    <div class="modal fade modal-confirm" id="modalConfirm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="confirm-icon">
                    <i class="bi bi-trash3-fill"></i>
                </div>
                <h5>Hapus Notifikasi?</h5>
                <p>Notifikasi ini akan dihapus secara permanen. Anda dapat membatalkan dalam beberapa detik setelah penghapusan.</p>
                <div class="confirm-btns">
                    <button class="btn-confirm-cancel" data-bs-dismiss="modal">Batal</button>
                    <button class="btn-confirm-delete" id="btnConfirmDelete">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- â•â•â• Toast Container â•â•â• -->
    <div class="toast-container-custom" id="toastContainer"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // â”€â”€ State â”€â”€
        let currentFilter = 'semua';
        let pendingDeleteCard = null;
        let deletedCards = [];
        let activeToastTimeout = null;

        // â”€â”€ Stagger Animation on Load â”€â”€
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.notif-card');
            cards.forEach((card, i) => {
                setTimeout(() => card.classList.add('visible'), 80 * i);
            });
            updateCounts();
            initSwipeGestures();
        });

        // â”€â”€ Filter Logic â”€â”€
        function filterNotif(btn, filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');

            const cards = document.querySelectorAll('.notif-card');
            let visibleCount = 0;

            cards.forEach((card, i) => {
                let show = false;
                if (filter === 'semua') show = true;
                else if (filter === 'unread') show = card.dataset.read === 'false';
                else show = card.dataset.category === filter;

                card.style.display = show ? '' : 'none';
                if (show) {
                    visibleCount++;
                    card.classList.remove('visible');
                    setTimeout(() => card.classList.add('visible'), 60 * visibleCount);
                }
            });

            document.getElementById('emptyState').classList.toggle('show', visibleCount === 0);
        }

        // â”€â”€ Update Counts â”€â”€
        function updateCounts() {
            const cards = document.querySelectorAll('.notif-card');
            let total = 0, unread = 0, transaksi = 0, promo = 0, sistem = 0;

            cards.forEach(c => {
                total++;
                if (c.dataset.read === 'false') unread++;
                if (c.dataset.category === 'transaksi') transaksi++;
                if (c.dataset.category === 'promo') promo++;
                if (c.dataset.category === 'sistem') sistem++;
            });

            document.getElementById('countSemua').textContent = total;
            document.getElementById('countUnread').textContent = unread;
            document.getElementById('countTransaksi').textContent = transaksi;
            document.getElementById('countPromo').textContent = promo;
            document.getElementById('countSistem').textContent = sistem;
            document.getElementById('unreadCount').textContent = unread;

            const badge = document.getElementById('unreadBadge');
            badge.style.display = unread > 0 ? 'inline-flex' : 'none';
        }

        // â”€â”€ Mark All Read â”€â”€
        function markAllRead() {
            const cards = document.querySelectorAll('.notif-card.unread');
            cards.forEach(card => {
                card.classList.remove('unread');
                card.dataset.read = 'true';
                const dot = card.querySelector('.unread-indicator');
                if (dot) dot.remove();
            });
            updateCounts();
            showToast('Semua notifikasi ditandai sebagai dibaca', false);

            // Re-apply filter
            if (currentFilter === 'unread') {
                const activeBtn = document.querySelector('.filter-pill[data-filter="unread"]');
                filterNotif(activeBtn, 'unread');
            }
        }

        // â”€â”€ Show Detail Modal â”€â”€
        function showDetail(card) {
            // Mark as read
            if (card.dataset.read === 'false') {
                card.classList.remove('unread');
                card.dataset.read = 'true';
                const dot = card.querySelector('.unread-indicator');
                if (dot) dot.remove();
                updateCounts();
            }

            const iconWrap = document.getElementById('detailIconWrap');
            const icon = document.getElementById('detailIcon');
            const iconClass = card.dataset.iconClass;

            iconWrap.className = 'detail-icon-wrap ' + iconClass;
            icon.className = card.dataset.icon;

            document.getElementById('detailTitle').textContent = card.dataset.title;
            document.getElementById('detailDesc').textContent = card.dataset.desc;
            document.getElementById('detailTime').textContent = card.dataset.time;

            const tagMap = { transaksi: 'Transaksi', promo: 'Promo', sistem: 'Sistem' };
            document.getElementById('detailTag').textContent = tagMap[card.dataset.tag] || card.dataset.tag;

            new bootstrap.Modal(document.getElementById('modalDetail')).show();
        }

        // â”€â”€ Confirm Delete â”€â”€
        function confirmDelete(card) {
            pendingDeleteCard = card;
            new bootstrap.Modal(document.getElementById('modalConfirm')).show();
        }

        document.getElementById('btnConfirmDelete').addEventListener('click', function() {
            if (pendingDeleteCard) {
                deleteCard(pendingDeleteCard);
                pendingDeleteCard = null;
            }
            bootstrap.Modal.getInstance(document.getElementById('modalConfirm')).hide();
        });

        // â”€â”€ Delete Card â”€â”€
        function deleteCard(card) {
            const cardData = {
                element: card,
                html: card.outerHTML,
                nextSibling: card.nextElementSibling,
                parent: card.parentElement
            };
            deletedCards.push(cardData);

            card.classList.add('deleting');
            setTimeout(() => {
                card.style.display = 'none';
                updateCounts();
                checkEmpty();
            }, 400);

            showToast('Notifikasi dihapus', true);
        }

        // â”€â”€ Toast â”€â”€
        function showToast(message, showUndo) {
            const container = document.getElementById('toastContainer');

            // Clear existing toasts
            container.innerHTML = '';
            if (activeToastTimeout) clearTimeout(activeToastTimeout);

            const toast = document.createElement('div');
            toast.className = 'custom-toast';
            toast.innerHTML = `
                <i class="bi bi-check-circle-fill" style="color: var(--primary-light); font-size: 1.1rem;"></i>
                <span>${message}</span>
                ${showUndo ? '<button class="btn-undo" onclick="undoDelete()">Batalkan</button>' : ''}
                <div class="toast-timer"><div class="toast-timer-bar"></div></div>
            `;
            container.appendChild(toast);

            requestAnimationFrame(() => {
                requestAnimationFrame(() => toast.classList.add('show'));
            });

            activeToastTimeout = setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                    // If undo wasn't clicked, actually remove the element
                    if (showUndo && deletedCards.length > 0) {
                        const last = deletedCards.pop();
                        if (last.element.parentElement) {
                            last.element.remove();
                        }
                        updateCounts();
                        checkEmpty();
                    }
                }, 400);
            }, 5000);
        }

        // â”€â”€ Undo Delete â”€â”€
        function undoDelete() {
            if (deletedCards.length === 0) return;

            const last = deletedCards.pop();
            const card = last.element;

            card.classList.remove('deleting');
            card.style.display = '';

            // Re-animate
            card.classList.remove('visible');
            requestAnimationFrame(() => {
                requestAnimationFrame(() => card.classList.add('visible'));
            });

            updateCounts();
            checkEmpty();

            // Remove toast
            const container = document.getElementById('toastContainer');
            container.innerHTML = '';
            if (activeToastTimeout) clearTimeout(activeToastTimeout);

            showToast('Notifikasi dikembalikan', false);
        }

        // â”€â”€ Check Empty â”€â”€
        function checkEmpty() {
            const visibleCards = document.querySelectorAll('.notif-card');
            let hasVisible = false;
            visibleCards.forEach(c => {
                if (c.style.display !== 'none') {
                    let matchFilter = true;
                    if (currentFilter === 'unread') matchFilter = c.dataset.read === 'false';
                    else if (currentFilter !== 'semua') matchFilter = c.dataset.category === currentFilter;
                    if (matchFilter) hasVisible = true;
                }
            });
            document.getElementById('emptyState').classList.toggle('show', !hasVisible);
        }

        // â”€â”€ Swipe / Slide to Delete (Touch) â”€â”€
        function initSwipeGestures() {
            const cards = document.querySelectorAll('.notif-card');
            cards.forEach(card => {
                let startX = 0;
                let currentX = 0;
                let isDragging = false;
                const inner = card.querySelector('.notif-card-inner');

                card.addEventListener('touchstart', e => {
                    startX = e.touches[0].clientX;
                    isDragging = true;
                }, { passive: true });

                card.addEventListener('touchmove', e => {
                    if (!isDragging) return;
                    currentX = e.touches[0].clientX;
                    const diff = startX - currentX;

                    if (diff > 10) {
                        card.classList.add('swiping');
                        const translateX = Math.min(diff, 90);
                        inner.style.transform = `translateX(-${translateX}px)`;
                    }
                }, { passive: true });

                card.addEventListener('touchend', () => {
                    isDragging = false;
                    const diff = startX - currentX;

                    if (diff > 70) {
                        confirmDelete(card);
                    }

                    inner.style.transform = '';
                    card.classList.remove('swiping');
                });

                // Click card to show detail (desktop)
                card.addEventListener('click', (e) => {
                    if (e.target.closest('.btn-notif-action')) return;
                    showDetail(card);
                });
            });
        }
    </script>
</body>
</html>
