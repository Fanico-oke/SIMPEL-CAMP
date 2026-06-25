<?php
// pages/pelanggan/member.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/MemberLevel.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Member & Poin';
$current_page = 'member';
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// Load member data
$member = MemberLevel::getByUser($_SESSION['user_id']);
$user_poin = $member ? (int)$member['poin'] : 0;
$member_level = $member ? strtolower($member['level']) : 'regular';
$member_total_trx = $member ? (int)$member['total_transaksi'] : 0;

// Tier levels and thresholds (based on transactions)
$tiers = [
    'regular' => ['name' => 'Regular', 'min' => 0, 'icon' => 'bi-person-fill'],
    'bronze'  => ['name' => 'Bronze', 'min' => 5, 'icon' => 'bi-award'],
    'silver'  => ['name' => 'Silver', 'min' => 15, 'icon' => 'bi-star-fill'],
    'gold'    => ['name' => 'Gold', 'min' => 30, 'icon' => 'bi-gem'],
];
$tier_keys = array_keys($tiers);
$current_tier_idx = array_search($member_level, $tier_keys);
if ($current_tier_idx === false) $current_tier_idx = 0;

// Next tier calculation
if ($current_tier_idx < count($tier_keys) - 1) {
    $next_tier_key = $tier_keys[$current_tier_idx + 1];
    $next_tier_name = $tiers[$next_tier_key]['name'];
    $next_tier_min = $tiers[$next_tier_key]['min'];
    $progress_pct = $next_tier_min > 0 ? min(100, round(($member_total_trx / $next_tier_min) * 100)) : 100;
} else {
    $next_tier_name = 'Max';
    $next_tier_min = $member_total_trx;
    $progress_pct = 100;
}

// Load recent transactions for point history
$recent_transactions = Transaksi::getByUser($_SESSION['user_id']);
$history_items = [];
$running_poin = $user_poin;
foreach (array_slice($recent_transactions, 0, 6) as $trx) {
    $trx_poin = (int)floor((float)$trx['total_bayar'] / 10000);
    if ($trx_poin > 0) {
        $history_items[] = [
            'title' => 'Transaksi ' . htmlspecialchars($trx['kode_transaksi'] ?? '#' . $trx['id']),
            'date' => date('d M Y · H:i', strtotime($trx['created_at'])),
            'points' => '+' . $trx_poin,
            'type' => 'earn',
            'balance' => $running_poin,
        ];
        $running_poin -= $trx_poin;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Member & Poin - Kelola poin loyalitas dan tukar reward eksklusif di SIMPEL-CAMP.">
    <title><?= htmlspecialchars($page_title) ?> â€” SIMPEL-CAMP</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Project CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">

    <style>
        /* ========== Design Tokens ========== */
        :root {
            --page-bg: #F2F7F4;
            --card-bg: #FFFFFF;
            --card-radius: 20px;
            --card-shadow: 0 2px 20px rgba(0,0,0,0.04);
            --primary: #2D6A4F;
            --primary-light: #52B788;
            --primary-lighter: #95D5B2;
            --accent-gold: #D4A373;
            --accent-gold-light: #E9C89B;
            --text-dark: #1A1A2E;
            --text-muted: #6B7280;
            --font-body: 'Inter', sans-serif;
            --font-display: 'Outfit', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --pill-radius: 50px;
            --input-radius: 12px;
        }

        /* ========== Page Base ========== */
        .member-page-wrapper {
            background: var(--page-bg);
            min-height: 100vh;
            font-family: var(--font-body);
            color: var(--text-dark);
        }

        .member-page-wrapper .page-header-title {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.85rem;
            color: var(--text-dark);
        }
        .member-page-wrapper .page-header-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 400;
        }

        /* ========== Card Base ========== */
        .mp-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            border: none;
            padding: 28px;
            position: relative;
            overflow: hidden;
        }
        .mp-card-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--text-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mp-card-title i {
            font-size: 1.2rem;
            color: var(--primary-light);
        }

        /* ========== 1. Hero Poin Card ========== */
        .hero-poin-card {
            padding: 0;
            overflow: hidden;
        }
        .hero-poin-inner {
            background: linear-gradient(135deg, #2D6A4F 0%, #40916C 40%, #52B788 100%);
            border-radius: var(--card-radius);
            padding: 36px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .hero-poin-inner::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-poin-inner::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: 30%;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(212,163,115,0.12) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-poin-left {
            position: relative;
            z-index: 2;
        }
        .hero-poin-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .hero-poin-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-light));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.3rem;
            box-shadow: 0 4px 16px rgba(212,163,115,0.35);
        }
        .hero-poin-label span {
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
            font-weight: 500;
        }
        .hero-poin-value {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 3.5rem;
            line-height: 1.1;
            background: linear-gradient(135deg, #FFFFFF 0%, #E9C89B 60%, #D4A373 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 6px;
        }
        .hero-poin-tier {
            color: rgba(255,255,255,0.75);
            font-size: 0.9rem;
            font-weight: 400;
        }
        .hero-poin-tier strong {
            color: var(--accent-gold-light);
            font-weight: 600;
        }
        .hero-poin-right {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .progress-ring-container {
            position: relative;
            width: 130px;
            height: 130px;
        }
        .progress-ring-container svg {
            transform: rotate(-90deg);
        }
        .progress-ring-bg {
            fill: none;
            stroke: rgba(255,255,255,0.15);
            stroke-width: 8;
        }
        .progress-ring-fill {
            fill: none;
            stroke: url(#goldGradient);
            stroke-width: 8;
            stroke-linecap: round;
            stroke-dasharray: 339.292;
            stroke-dashoffset: 339.292;
            transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .progress-ring-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .progress-ring-percent {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.6rem;
            color: #fff;
            line-height: 1;
        }
        .progress-ring-sub {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.7);
            font-weight: 500;
        }
        .progress-ring-label {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.7);
            font-weight: 500;
            text-align: center;
        }
        .progress-ring-label strong {
            color: var(--accent-gold-light);
        }

        /* ========== 2. Tier Roadmap ========== */
        .tier-roadmap {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
            padding: 10px 0;
        }
        .tier-connector {
            position: absolute;
            top: 30px;
            left: 40px;
            right: 40px;
            height: 3px;
            background: #E5E7EB;
            z-index: 0;
        }
        .tier-connector-fill {
            height: 100%;
            width: 66%;
            background: linear-gradient(90deg, var(--primary-light), var(--accent-gold));
            border-radius: 3px;
            transition: width 1.2s ease;
        }
        .tier-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .tier-step-dot {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 10px;
            transition: all 0.4s ease;
            position: relative;
        }
        .tier-step.completed .tier-step-dot {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            box-shadow: 0 4px 14px rgba(45,106,79,0.25);
        }
        .tier-step.current .tier-step-dot {
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-light));
            color: #fff;
            box-shadow: 0 0 0 6px rgba(212,163,115,0.2), 0 4px 20px rgba(212,163,115,0.35);
            animation: tierGlow 2.5s ease-in-out infinite;
        }
        .tier-step.upcoming .tier-step-dot {
            background: #F3F4F6;
            color: #9CA3AF;
            border: 2px dashed #D1D5DB;
        }
        @keyframes tierGlow {
            0%, 100% { box-shadow: 0 0 0 6px rgba(212,163,115,0.2), 0 4px 20px rgba(212,163,115,0.35); }
            50% { box-shadow: 0 0 0 10px rgba(212,163,115,0.12), 0 4px 28px rgba(212,163,115,0.45); }
        }
        .tier-step-name {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 2px;
        }
        .tier-step.upcoming .tier-step-name {
            color: #9CA3AF;
        }
        .tier-step-pts {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .tier-badge-current {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-gold-light));
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: var(--pill-radius);
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ========== 3a. Point History Timeline ========== */
        .history-timeline {
            position: relative;
            padding-left: 24px;
        }
        .history-timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 4px;
            bottom: 4px;
            width: 2px;
            background: #E5E7EB;
            border-radius: 2px;
        }
        .history-item {
            position: relative;
            padding: 0 0 22px 16px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        .history-item:last-child { padding-bottom: 0; }
        .history-dot {
            position: absolute;
            left: -20px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .history-dot.earn {
            background: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(82,183,136,0.15);
        }
        .history-dot.spend {
            background: #EF4444;
            box-shadow: 0 0 0 4px rgba(239,68,68,0.12);
        }
        .history-info {
            flex: 1;
            min-width: 0;
        }
        .history-info .h-title {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--text-dark);
            margin-bottom: 2px;
        }
        .history-info .h-date {
            font-size: 0.78rem;
            color: var(--text-muted);
        }
        .history-points {
            font-family: var(--font-mono);
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .history-points.earn { color: var(--primary-light); }
        .history-points.spend { color: #EF4444; }
        .history-balance {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-align: right;
        }

        /* ========== 3b. Reward Cards ========== */
        .reward-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .reward-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(0,0,0,0.08);
        }
        .reward-card.locked {
            opacity: 0.7;
        }
        .reward-card.locked::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.3);
            pointer-events: none;
            border-radius: 16px;
        }
        .reward-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .reward-icon.discount {
            background: linear-gradient(135deg, #FEF3C7, #FDE68A);
            color: #D97706;
        }
        .reward-icon.freebie {
            background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
            color: var(--primary);
        }
        .reward-icon.upgrade {
            background: linear-gradient(135deg, #E0E7FF, #C7D2FE);
            color: #4F46E5;
        }
        .reward-info { flex: 1; min-width: 0; }
        .reward-info .r-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 2px;
        }
        .reward-info .r-cost {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .reward-info .r-cost i { color: var(--accent-gold); font-size: 0.8rem; }
        .btn-tukar {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            border: none;
            border-radius: var(--pill-radius);
            padding: 7px 20px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            font-family: var(--font-body);
        }
        .btn-tukar:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(45,106,79,0.3);
            color: #fff;
        }
        .btn-tukar:disabled {
            background: #D1D5DB;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
        .btn-tukar .bi-lock-fill {
            font-size: 0.75rem;
        }

        /* ========== 4. Benefits Grid ========== */
        .benefit-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.04);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .benefit-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .benefit-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .benefit-card:hover::before { opacity: 1; }
        .benefit-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 14px;
        }
        .benefit-icon.b1 { background: linear-gradient(135deg, #D1FAE5, #A7F3D0); color: var(--primary); }
        .benefit-icon.b2 { background: linear-gradient(135deg, #DBEAFE, #BFDBFE); color: #2563EB; }
        .benefit-icon.b3 { background: linear-gradient(135deg, #FEF3C7, #FDE68A); color: #D97706; }
        .benefit-icon.b4 { background: linear-gradient(135deg, #FCE7F3, #FBCFE8); color: #DB2777; }
        .benefit-name {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        .benefit-desc {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* ========== Stagger Animation ========== */
        .stagger-item {
            opacity: 0;
            transform: translateY(24px);
            animation: staggerIn 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        .stagger-item:nth-child(1) { animation-delay: 0.08s; }
        .stagger-item:nth-child(2) { animation-delay: 0.16s; }
        .stagger-item:nth-child(3) { animation-delay: 0.24s; }
        .stagger-item:nth-child(4) { animation-delay: 0.32s; }
        @keyframes staggerIn {
            to { opacity: 1; transform: translateY(0); }
        }

        /* ========== Toast ========== */
        .toast-container-custom {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .custom-toast {
            background: var(--card-bg);
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 420px;
            transform: translateX(110%);
            animation: toastSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            font-family: var(--font-body);
        }
        .custom-toast.toast-hide {
            animation: toastSlideOut 0.35s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        @keyframes toastSlideIn {
            to { transform: translateX(0); }
        }
        @keyframes toastSlideOut {
            to { transform: translateX(110%); opacity: 0; }
        }
        .custom-toast .toast-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .custom-toast .toast-icon.success {
            background: #D1FAE5;
            color: var(--primary);
        }
        .custom-toast .toast-icon.error {
            background: #FEE2E2;
            color: #EF4444;
        }
        .custom-toast .toast-icon.info {
            background: #DBEAFE;
            color: #2563EB;
        }
        .custom-toast .toast-body-text {
            flex: 1;
        }
        .custom-toast .toast-body-text .t-title {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--text-dark);
        }
        .custom-toast .toast-body-text .t-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
        }
        .custom-toast .toast-close {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            font-size: 1rem;
            transition: color 0.2s;
        }
        .custom-toast .toast-close:hover { color: var(--text-dark); }

        /* ========== Modals ========== */
        .modal-content.mp-modal {
            border-radius: var(--card-radius);
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .mp-modal .modal-header {
            border-bottom: 1px solid #F3F4F6;
            padding: 20px 28px;
        }
        .mp-modal .modal-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-dark);
        }
        .mp-modal .modal-body {
            padding: 24px 28px;
        }
        .mp-modal .modal-footer {
            border-top: 1px solid #F3F4F6;
            padding: 16px 28px;
        }
        .btn-modal-confirm {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            border: none;
            border-radius: var(--pill-radius);
            padding: 10px 32px;
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.3s ease;
            font-family: var(--font-body);
        }
        .btn-modal-confirm:hover {
            box-shadow: 0 4px 16px rgba(45,106,79,0.3);
            transform: translateY(-1px);
            color: #fff;
        }
        .btn-modal-cancel {
            background: #F3F4F6;
            color: var(--text-muted);
            border: none;
            border-radius: var(--pill-radius);
            padding: 10px 24px;
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.3s ease;
            font-family: var(--font-body);
        }
        .btn-modal-cancel:hover {
            background: #E5E7EB;
            color: var(--text-dark);
        }

        /* Success modal */
        .success-check-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 2rem;
            color: var(--primary);
        }
        .coupon-code-box {
            background: #F9FAFB;
            border-radius: 12px;
            padding: 14px 20px;
            text-align: center;
            margin-top: 16px;
        }
        .coupon-code-box .coupon-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .coupon-code-box .coupon-value {
            font-family: var(--font-mono);
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--primary);
            letter-spacing: 2px;
        }
        .coupon-copy-btn {
            background: none;
            border: none;
            color: var(--primary-light);
            cursor: pointer;
            font-size: 1rem;
            margin-left: 8px;
            transition: color 0.2s;
        }
        .coupon-copy-btn:hover { color: var(--primary); }

        /* ========== Section Label ========== */
        .section-label {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-label i { color: var(--primary-light); }

        /* ========== Responsive ========== */
        @media (max-width: 991px) {
            .hero-poin-inner {
                flex-direction: column;
                text-align: center;
                padding: 28px 24px;
                gap: 24px;
            }
            .tier-roadmap {
                gap: 6px;
            }
            .tier-step-dot {
                width: 44px;
                height: 44px;
                font-size: 1.1rem;
            }
            .tier-connector {
                top: 24px;
                left: 28px;
                right: 28px;
            }
        }
        @media (max-width: 767px) {
            .hero-poin-value {
                font-size: 2.6rem;
            }
            .mp-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="pelanggan-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>
    <div class="pelanggan-main">
        <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>
        <div class="pelanggan-content">
<div class="member-page-wrapper">
    <div class="container-fluid px-4 py-4">

        <!-- ============ 1. HERO POIN CARD ============ -->
        <div class="mp-card hero-poin-card mb-4 stagger-item">
            <div class="hero-poin-inner">
                <!-- SVG Gradient Defs -->
                <svg width="0" height="0" style="position:absolute;">
                    <defs>
                        <linearGradient id="goldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#D4A373"/>
                            <stop offset="100%" style="stop-color:#E9C89B"/>
                        </linearGradient>
                    </defs>
                </svg>

                <div class="hero-poin-left">
                    <div class="hero-poin-label">
                        <div class="hero-poin-icon">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <span>Total Poin Anda</span>
                    </div>
                    <div class="hero-poin-value"><?= number_format($user_poin, 0, ',', '.') ?></div>
                    <div class="hero-poin-tier">
                        Tier saat ini: <strong><?= htmlspecialchars($tiers[$member_level]['name'] ?? 'Regular') ?> Member</strong>
                    </div>
                </div>

                <div class="hero-poin-right">
                    <div class="progress-ring-container" id="progressRingContainer">
                        <svg width="130" height="130" viewBox="0 0 130 130">
                            <circle class="progress-ring-bg" cx="65" cy="65" r="54"/>
                            <circle class="progress-ring-fill" cx="65" cy="65" r="54" id="progressRing"/>
                        </svg>
                        <div class="progress-ring-text">
                            <div class="progress-ring-percent"><?= $progress_pct ?>%</div>
                            <div class="progress-ring-sub">tercapai</div>
                        </div>
                    </div>
                    <div class="progress-ring-label">
                        Menuju <strong><?= htmlspecialchars($next_tier_name) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ 2. TIER ROADMAP ============ -->
        <div class="mp-card mb-4 stagger-item">
            <div class="mp-card-title">
                <i class="bi bi-trophy"></i> Perjalanan Tier Anda
            </div>
            <div class="tier-roadmap">
                <div class="tier-connector">
                    <div class="tier-connector-fill"></div>
                </div>

                <?php foreach ($tiers as $tier_key => $tier_info): 
                    $tier_idx = array_search($tier_key, $tier_keys);
                    if ($tier_idx < $current_tier_idx) {
                        $step_class = 'completed';
                        $dot_icon = '<i class="bi bi-check-lg"></i>';
                    } elseif ($tier_idx === $current_tier_idx) {
                        $step_class = 'current';
                        $dot_icon = '<i class="bi ' . $tier_info['icon'] . '"></i>';
                    } else {
                        $step_class = 'upcoming';
                        $dot_icon = '<i class="bi ' . $tier_info['icon'] . '"></i>';
                    }
                ?>
                <div class="tier-step <?= $step_class ?>">
                    <div class="tier-step-dot">
                        <?= $dot_icon ?>
                    </div>
                    <div class="tier-step-name"><?= htmlspecialchars($tier_info['name']) ?></div>
                    <div class="tier-step-pts"><?= $tier_info['min'] ?> trx</div>
                    <?php if ($tier_idx === $current_tier_idx): ?>
                    <span class="tier-badge-current">
                        <i class="bi bi-geo-alt-fill"></i> Saat Ini
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ============ 3. TWO COLUMN: HISTORY + REWARDS ============ -->
        <div class="row g-4 mb-4">

            <!-- LEFT: Point History Timeline -->
            <div class="col-lg-7">
                <div class="mp-card h-100 stagger-item">
                    <div class="mp-card-title">
                        <i class="bi bi-clock-history"></i> Riwayat Poin
                    </div>
                    <div class="history-timeline">
                        <?php if (!empty($history_items)): ?>
                        <?php foreach ($history_items as $hi): ?>
                        <div class="history-item">
                            <div class="history-dot <?= $hi['type'] ?>"></div>
                            <div class="history-info">
                                <div class="h-title"><?= $hi['title'] ?></div>
                                <div class="h-date"><?= htmlspecialchars($hi['date']) ?></div>
                            </div>
                            <div class="text-end">
                                <div class="history-points <?= $hi['type'] ?>"><?= $hi['points'] ?></div>
                                <div class="history-balance">Saldo: <?= number_format($hi['balance'], 0, ',', '.') ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history" style="font-size:2rem;color:var(--text-muted);opacity:0.4;"></i>
                            <p class="mt-2" style="color:var(--text-muted);font-size:0.88rem;">Belum ada riwayat poin</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Reward Cards -->
            <div class="col-lg-5">
                <div class="mp-card h-100 stagger-item">
                    <div class="mp-card-title">
                        <i class="bi bi-gift"></i> Tukar Reward
                    </div>
                    <div class="d-flex flex-column gap-3">
                        <!-- Reward 1: Diskon 10% -->
                        <div class="reward-card" id="reward-diskon">
                            <div class="reward-icon discount">
                                <i class="bi bi-percent"></i>
                            </div>
                            <div class="reward-info">
                                <div class="r-name">Diskon 10%</div>
                                <div class="r-cost"><i class="bi bi-star-fill"></i> 100 poin</div>
                            </div>
                            <button class="btn-tukar" onclick="openRedeemModal('Diskon 10%', 100, 'DSK10')">
                                Tukar
                            </button>
                        </div>

                        <!-- Reward 2: Gratis Sleeping Bag -->
                        <div class="reward-card" id="reward-sleepingbag">
                            <div class="reward-icon freebie">
                                <i class="bi bi-moon-stars"></i>
                            </div>
                            <div class="reward-info">
                                <div class="r-name">Gratis Sleeping Bag</div>
                                <div class="r-cost"><i class="bi bi-star-fill"></i> 200 poin</div>
                            </div>
                            <button class="btn-tukar" onclick="openRedeemModal('Gratis Sleeping Bag', 200, 'SLP200')">
                                Tukar
                            </button>
                        </div>

                        <!-- Reward 3: Upgrade Tenda (Locked) -->
                        <div class="reward-card locked" id="reward-upgrade">
                            <div class="reward-icon upgrade">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                            <div class="reward-info">
                                <div class="r-name">Upgrade Tenda</div>
                                <div class="r-cost"><i class="bi bi-star-fill"></i> 500 poin</div>
                            </div>
                            <button class="btn-tukar" disabled>
                                <i class="bi bi-lock-fill me-1"></i> Terkunci
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ 4. BENEFITS GRID ============ -->
        <div class="section-label">
            <i class="bi bi-shield-check"></i> Keuntungan Member
        </div>
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="benefit-card stagger-item">
                    <div class="benefit-icon b1">
                        <i class="bi bi-tag"></i>
                    </div>
                    <div class="benefit-name">Diskon Member</div>
                    <div class="benefit-desc">Dapatkan potongan harga spesial hingga 15% untuk setiap booking.</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="benefit-card stagger-item">
                    <div class="benefit-icon b2">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="benefit-name">Prioritas Booking</div>
                    <div class="benefit-desc">Akses lebih awal untuk booking di tanggal-tanggal populer dan libur.</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="benefit-card stagger-item">
                    <div class="benefit-icon b3">
                        <i class="bi bi-coin"></i>
                    </div>
                    <div class="benefit-name">Poin Reward</div>
                    <div class="benefit-desc">Kumpulkan poin dari setiap transaksi dan tukar dengan reward menarik.</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="benefit-card stagger-item">
                    <div class="benefit-icon b4">
                        <i class="bi bi-headset"></i>
                    </div>
                    <div class="benefit-name">Support Prioritas</div>
                    <div class="benefit-desc">Layanan customer support prioritas dengan respons lebih cepat.</div>
                </div>
            </div>
        </div>

    </div><!-- /.container-fluid -->
</div><!-- /.member-page-wrapper -->
        </div><!-- /.pelanggan-content -->
    </div><!-- /.pelanggan-main -->
</div><!-- /.pelanggan-wrapper -->
<div class="toast-container-custom" id="toastContainer"></div>

<!-- ============ CONFIRM REDEEM MODAL ============ -->
<div class="modal fade" id="redeemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content mp-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gift me-2" style="color: var(--accent-gold);"></i>Konfirmasi Penukaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" style="color: var(--text-muted); font-size: 0.92rem;">
                    Apakah Anda yakin ingin menukarkan poin untuk:
                </p>
                <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background: #F9FAFB;">
                    <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <div>
                        <div style="font-weight:600;color:var(--text-dark);" id="redeemRewardName">-</div>
                        <div style="font-size:0.82rem;color:var(--text-muted);">
                            <i class="bi bi-star-fill" style="color:var(--accent-gold);font-size:0.75rem;"></i>
                            <span id="redeemRewardCost">0</span> poin akan dikurangi
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-2 rounded-2 text-center" style="background: #FEF3C7; font-size: 0.82rem; color: #92400E;">
                    <i class="bi bi-exclamation-triangle me-1"></i> Poin yang sudah ditukar tidak dapat dikembalikan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-modal-confirm" id="confirmRedeemBtn" onclick="confirmRedeem()">
                    <i class="bi bi-check2-circle me-1"></i> Ya, Tukarkan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ SUCCESS MODAL ============ -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content mp-modal">
            <div class="modal-body text-center py-4">
                <div class="success-check-circle">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h5 style="font-family:var(--font-display);font-weight:700;color:var(--text-dark);margin-bottom:4px;">Berhasil!</h5>
                <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:0;">Reward Anda telah ditukarkan.</p>
                <div class="coupon-code-box">
                    <div class="coupon-label">Kode Kupon</div>
                    <div class="d-flex align-items-center justify-content-center">
                        <span class="coupon-value" id="couponCodeValue">-</span>
                        <button class="coupon-copy-btn" onclick="copyCoupon()" title="Salin kode">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                <button class="btn-modal-confirm mt-3 w-100" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== SVG Progress Ring Animation =====
    const ring = document.getElementById('progressRing');
    if (ring) {
        const circumference = 2 * Math.PI * 54; // r=54
        const percent = <?= $progress_pct ?>;
        const offset = circumference - (percent / 100) * circumference;

        ring.style.strokeDasharray = circumference;
        ring.style.strokeDashoffset = circumference;

        setTimeout(function() {
            ring.style.strokeDashoffset = offset;
        }, 400);
    }

    // ===== Intersection Observer for Stagger =====
    const staggerItems = document.querySelectorAll('.stagger-item');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        staggerItems.forEach(function(item) {
            item.style.animationPlayState = 'paused';
            observer.observe(item);
        });
    }
});

// ===== Redeem Variables =====
let currentReward = { name: '', cost: 0, code: '' };

function openRedeemModal(name, cost, codePrefix) {
    currentReward = { name: name, cost: cost, code: codePrefix };
    document.getElementById('redeemRewardName').textContent = name;
    document.getElementById('redeemRewardCost').textContent = cost;
    const modal = new bootstrap.Modal(document.getElementById('redeemModal'));
    modal.show();
}

function confirmRedeem() {
    // Close confirm modal
    const redeemModal = bootstrap.Modal.getInstance(document.getElementById('redeemModal'));
    if (redeemModal) redeemModal.hide();

    // Generate coupon code
    const randomSuffix = Math.random().toString(36).substring(2, 8).toUpperCase();
    const couponCode = currentReward.code + '-' + randomSuffix;
    document.getElementById('couponCodeValue').textContent = couponCode;

    // Show success modal after short delay
    setTimeout(function() {
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
        showToast('success', 'Penukaran Berhasil', currentReward.name + ' telah ditukarkan!');
    }, 350);
}

function copyCoupon() {
    const code = document.getElementById('couponCodeValue').textContent;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(function() {
            showToast('info', 'Kode Disalin', 'Kode kupon telah disalin ke clipboard.');
        });
    } else {
        // Fallback
        const textArea = document.createElement('textarea');
        textArea.value = code;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('info', 'Kode Disalin', 'Kode kupon telah disalin ke clipboard.');
    }
}

// ===== Toast System =====
function showToast(type, title, desc) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'custom-toast';

    const iconMap = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        info: 'bi-info-circle-fill'
    };

    toast.innerHTML = `
        <div class="toast-icon ${type}">
            <i class="bi ${iconMap[type] || iconMap.info}"></i>
        </div>
        <div class="toast-body-text">
            <div class="t-title">${title}</div>
            <div class="t-desc">${desc}</div>
        </div>
        <button class="toast-close" onclick="dismissToast(this)">
            <i class="bi bi-x"></i>
        </button>
    `;

    container.appendChild(toast);

    // Auto dismiss after 4s
    setTimeout(function() {
        if (toast.parentNode) {
            toast.classList.add('toast-hide');
            setTimeout(function() {
                if (toast.parentNode) toast.remove();
            }, 350);
        }
    }, 4000);
}

function dismissToast(btn) {
    const toast = btn.closest('.custom-toast');
    toast.classList.add('toast-hide');
    setTimeout(function() {
        if (toast.parentNode) toast.remove();
    }, 350);
}
</script>
</body>
</html>
