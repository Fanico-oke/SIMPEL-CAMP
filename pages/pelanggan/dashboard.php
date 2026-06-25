<?php
// pages/pelanggan/dashboard.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/Kategori.php';
require_once dirname(__DIR__, 2) . '/classes/MemberLevel.php';
require_once dirname(__DIR__, 2) . '/classes/Notifikasi.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Dashboard';
$current_page = 'dashboard';
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// --- Fetch real data ---
// Active rentals (status 'aktif') + upcoming (status 'disetujui')
$activeRentals = Reservasi::getByUser($user_id, 'aktif');
$upcomingRentalsEarly = Reservasi::getByUser($user_id, 'disetujui');
$sewaAktifCount = count($activeRentals);
$sewaAkanDatang = count($upcomingRentalsEarly);
$totalSewaCount = $sewaAktifCount + $sewaAkanDatang;

// Total spending from completed transactions
$allTransaksi = Transaksi::getByUser($user_id);
$totalBelanja = 0;
$belanjaBulanIni = 0;
$currentMonth = date('Y-m');
foreach ($allTransaksi as $trx) {
    if ($trx['status'] === 'selesai' || $trx['status'] === 'aktif' || $trx['status'] === 'dibayar') {
        $totalBelanja += (float)$trx['total_bayar'];
        if (substr($trx['created_at'], 0, 7) === $currentMonth) {
            $belanjaBulanIni += (float)$trx['total_bayar'];
        }
    }
}

// Member level info
$memberInfo = MemberLevel::getByUser($user_id);
$memberPoin = $memberInfo ? (int)$memberInfo['poin'] : 0;
$memberLevel = $memberInfo ? ucfirst($memberInfo['level']) : 'Regular';
// Progress to next tier
$nextTierPts = 750; // Default platinum threshold
$progressPercent = $nextTierPts > 0 ? min(100, round($memberPoin / $nextTierPts * 100)) : 0;

// Active rentals details for stat sub-items
$activeDetails = [];
foreach ($activeRentals as $rental) {
    $details = Reservasi::getDetail($rental['id']);
    $daysLeft = max(0, (int)(new DateTime())->diff(new DateTime($rental['tanggal_selesai']))->format('%r%a'));
    $itemNames = [];
    foreach ($details as $d) {
        $itemNames[] = $d['barang_nama'];
    }
    $activeDetails[] = [
        'names' => implode(', ', $itemNames),
        'days_left' => $daysLeft,
        'tanggal_mulai' => $rental['tanggal_mulai'],
        'tanggal_selesai' => $rental['tanggal_selesai'],
        'kode' => $rental['kode_reservasi'],
        'status' => $rental['status']
    ];
}

// Schedule items: active + upcoming (disetujui) reservations
$upcomingRentals = Reservasi::getByUser($user_id, 'disetujui');
$scheduleItems = [];
foreach ($activeRentals as $r) {
    $details = Reservasi::getDetail($r['id']);
    $names = array_map(function($d) { return $d['barang_nama']; }, $details);
    $scheduleItems[] = [
        'tanggal_selesai' => $r['tanggal_selesai'],
        'tanggal_mulai' => $r['tanggal_mulai'],
        'names' => implode(', ', $names),
        'status' => 'aktif'
    ];
}
foreach ($upcomingRentals as $r) {
    $details = Reservasi::getDetail($r['id']);
    $names = array_map(function($d) { return $d['barang_nama']; }, $details);
    $scheduleItems[] = [
        'tanggal_selesai' => $r['tanggal_selesai'],
        'tanggal_mulai' => $r['tanggal_mulai'],
        'names' => implode(', ', $names),
        'status' => 'upcoming'
    ];
}
// Limit to 3
$scheduleItems = array_slice($scheduleItems, 0, 3);

// Recommendations: popular items
$rekomendasi = Barang::getPopuler(3);
if (empty($rekomendasi)) {
    $rekomendasi = Barang::getAll(['limit' => 3, 'status' => 'tersedia']);
}



// Recent history: last 3 transactions
$recentTransaksi = array_slice($allTransaksi, 0, 3);
$historyItems = [];
foreach ($recentTransaksi as $trx) {
    $reservasiDetail = [];
    if (!empty($trx['reservasi_id'])) {
        $reservasiDetail = Reservasi::getDetail($trx['reservasi_id']);
    }
    $itemNames = array_map(function($d) { return $d['barang_nama']; }, $reservasiDetail);
    $historyItems[] = [
        'kode' => $trx['kode_reservasi'] ?? $trx['kode_transaksi'],
        'items' => implode(', ', $itemNames),
        'date' => date('d M Y', strtotime($trx['created_at'])),
        'amount' => $trx['total_bayar'],
        'status' => $trx['status']
    ];
}

// Unread notifications count
$unreadNotif = Notifikasi::countUnread($user_id);

// Progress ring percentage for active rentals
$ringProgress = $totalSewaCount > 0 ? min(100, $totalSewaCount * 33) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <meta name="description" content="Dashboard pelanggan SIMPEL-CAMP - Kelola penyewaan peralatan camping Anda dengan mudah">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">

<style>
/* ═══════════════════════════════════════════════════════
   DASHBOARD — Modern SaaS Clean Design
   ═══════════════════════════════════════════════════════ */
:root {
    --primary: #2D6A4F;
    --primary-light: #52B788;
    --accent-gold: #D4A373;
    --accent-gold-light: #E9C46A;
    --bg-page: #F2F7F4;
    --bg-card: #FFFFFF;
    --shadow-card: 0 2px 20px rgba(0,0,0,0.04);
    --radius-card: 20px;
    --radius-pill: 50px;
    --radius-input: 12px;
    --text-primary: #1A1A2E;
    --text-secondary: #6B7280;
    --font-body: 'Inter', sans-serif;
    --font-heading: 'Outfit', sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
}

body {
    font-family: var(--font-body);
    background: var(--bg-page);
    color: var(--text-primary);
    margin: 0;
}

/* ─── Topbar & content padding now handled by pelanggan-system.css */

.topbar-greeting h1 {
    font-family: var(--font-heading);
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
}
.topbar-greeting p {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 2px 0 0;
}
.topbar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}
.topbar-icon-btn {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: none;
    background: var(--bg-card);
    box-shadow: var(--shadow-card);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    color: var(--text-secondary);
    transition: all 0.2s ease;
    font-size: 1.1rem;
}
.topbar-icon-btn:hover {
    background: var(--primary);
    color: #fff;
    transform: translateY(-1px);
}
.topbar-icon-btn .notif-dot {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid var(--bg-page);
}
.topbar-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    font-family: var(--font-heading);
    margin-left: 4px;
    cursor: pointer;
}

/* ─── Stat Cards ─── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--bg-card);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: 24px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.08);
}
.stat-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 16px;
}
.stat-info .stat-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    font-weight: 500;
    display: block;
    margin-bottom: 6px;
}
.stat-info .stat-value {
    font-family: var(--font-heading);
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1;
}
.stat-info .stat-value small {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--primary-light);
}
.stat-info .stat-value.mono {
    font-family: var(--font-mono);
    font-size: 1.4rem;
    font-weight: 600;
}
.stat-visual {
    flex-shrink: 0;
}

/* Progress ring */
.progress-ring {
    width: 56px;
    height: 56px;
}
.progress-ring circle {
    fill: none;
    stroke-width: 5;
    stroke-linecap: round;
    transform: rotate(-90deg);
    transform-origin: center;
}
.progress-ring .ring-bg {
    stroke: #E8F5E9;
}
.progress-ring .ring-fill {
    stroke: var(--primary-light);
    stroke-dasharray: 138.2;
    stroke-dashoffset: 138.2;
    transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Mini chart SVG */
.mini-chart {
    width: 80px;
    height: 40px;
}
.mini-chart path {
    fill: none;
    stroke: var(--primary-light);
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.mini-chart .chart-area {
    fill: url(#chartGradient);
    stroke: none;
}

/* Stat sub items */
.stat-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 4px;
}
.stat-sub-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78rem;
    color: var(--text-secondary);
}
.stat-sub-item .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--primary-light);
    flex-shrink: 0;
}
.stat-sub-item strong {
    color: var(--text-primary);
    font-weight: 600;
}

/* Member progress bar */
.member-progress {
    margin-top: 12px;
}
.member-progress .progress-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    color: var(--text-secondary);
    margin-bottom: 6px;
}
.member-progress .progress-bar-track {
    width: 100%;
    height: 8px;
    background: #E8F5E9;
    border-radius: 50px;
    overflow: hidden;
}
.member-progress .progress-bar-fill {
    height: 100%;
    border-radius: 50px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    width: 0%;
    transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.member-tier {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--accent-gold);
    background: rgba(212,163,115,0.1);
    padding: 3px 10px;
    border-radius: var(--radius-pill);
    margin-top: 2px;
}

/* ─── Two Column Section ─── */
.two-col-section {
    display: grid;
    grid-template-columns: 7fr 5fr;
    gap: 20px;
    margin-bottom: 24px;
}

/* Dashboard Card (generic) */
.dash-card {
    background: var(--bg-card);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: 24px;
    transition: all 0.3s ease;
}
.dash-card:hover {
    box-shadow: 0 6px 28px rgba(0,0,0,0.07);
}
.dash-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.dash-card-header h3 {
    font-family: var(--font-heading);
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
}
.dash-card-header .view-all {
    font-size: 0.78rem;
    color: var(--primary-light);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}
.dash-card-header .view-all:hover {
    color: var(--primary);
}

/* ─── Schedule Items ─── */
.schedule-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.schedule-item {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    padding: 16px;
    border-radius: 14px;
    background: #F8FAF9;
    transition: all 0.2s ease;
}
.schedule-item:hover {
    background: #EFF6F1;
}
.schedule-date {
    width: 52px;
    min-width: 52px;
    height: 56px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #fff;
}
.schedule-date .day {
    font-family: var(--font-heading);
    font-size: 1.3rem;
    font-weight: 800;
    line-height: 1;
}
.schedule-date .month {
    font-size: 0.6rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.85;
}
.schedule-info {
    flex: 1;
}
.schedule-info h4 {
    font-family: var(--font-heading);
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 4px;
    color: var(--text-primary);
}
.schedule-info .schedule-range {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 6px;
}
.schedule-info .schedule-range i {
    margin-right: 4px;
    font-size: 0.7rem;
}
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: var(--radius-pill);
    font-size: 0.7rem;
    font-weight: 600;
}
.status-pill.active {
    background: rgba(82,183,136,0.12);
    color: #2D6A4F;
}
.status-pill.upcoming {
    background: rgba(59,130,246,0.1);
    color: #2563EB;
}
.status-pill.completed {
    background: rgba(107,114,128,0.1);
    color: #6B7280;
}
.status-pill i {
    font-size: 0.55rem;
}

/* ─── Recommendation Cards ─── */
.reko-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.reko-card {
    display: flex;
    gap: 14px;
    padding: 12px;
    border-radius: 14px;
    background: #F8FAF9;
    transition: all 0.25s ease;
    cursor: pointer;
    text-decoration: none;
}
.reko-card:hover {
    background: #EFF6F1;
    transform: translateX(4px);
}
.reko-img {
    width: 72px;
    height: 72px;
    min-width: 72px;
    border-radius: 12px;
    overflow: hidden;
}
.reko-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.reko-card:hover .reko-img img {
    transform: scale(1.08);
}
.reko-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.reko-info h4 {
    font-family: var(--font-heading);
    font-size: 0.85rem;
    font-weight: 600;
    margin: 0 0 4px;
    color: var(--text-primary);
}
.reko-info .reko-price {
    font-family: var(--font-mono);
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 4px;
}
.reko-info .reko-rating {
    font-size: 0.72rem;
    color: var(--accent-gold);
}
.reko-info .reko-rating span {
    color: var(--text-secondary);
    margin-left: 2px;
}
.reko-action {
    display: flex;
    align-items: center;
}
.btn-sewa-sm {
    padding: 6px 16px;
    border-radius: var(--radius-pill);
    border: none;
    background: var(--primary);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 600;
    font-family: var(--font-body);
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.btn-sewa-sm:hover {
    background: var(--primary-light);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(45,106,79,0.25);
}

/* ─── History Section ─── */
.history-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
.history-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border-radius: 14px;
    background: #F8FAF9;
    transition: all 0.2s ease;
}
.history-item:hover {
    background: #EFF6F1;
    transform: translateY(-2px);
}
.history-dot {
    width: 10px;
    height: 10px;
    min-width: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.history-dot.green { background: var(--primary-light); }
.history-dot.blue { background: #3B82F6; }
.history-dot.gray { background: #9CA3AF; }
.history-details {
    flex: 1;
    min-width: 0;
}
.history-details .history-id {
    font-family: var(--font-mono);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-primary);
}
.history-details .history-items-text {
    font-size: 0.75rem;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.history-details .history-date {
    font-size: 0.68rem;
    color: #9CA3AF;
}
.history-meta {
    text-align: right;
    flex-shrink: 0;
}
.history-meta .history-amount {
    font-family: var(--font-mono);
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-primary);
    display: block;
    margin-bottom: 4px;
}

/* ─── Welcome Toast ─── */
.welcome-toast {
    position: fixed;
    bottom: 28px;
    right: 28px;
    background: var(--bg-card);
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 1100;
    transform: translateY(120%);
    opacity: 0;
    animation: toastIn 0.5s cubic-bezier(0.34,1.56,0.64,1) 1s forwards;
    max-width: 340px;
    border-left: 4px solid var(--primary-light);
}
.welcome-toast .toast-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.welcome-toast .toast-text h4 {
    font-family: var(--font-heading);
    font-size: 0.85rem;
    font-weight: 700;
    margin: 0 0 2px;
    color: var(--text-primary);
}
.welcome-toast .toast-text p {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin: 0;
}
.welcome-toast .toast-close {
    position: absolute;
    top: 8px;
    right: 10px;
    background: none;
    border: none;
    color: #9CA3AF;
    cursor: pointer;
    font-size: 0.85rem;
    padding: 2px;
    line-height: 1;
}
.welcome-toast .toast-close:hover {
    color: var(--text-primary);
}

/* ─── Animations ─── */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes toastIn {
    from { transform: translateY(120%); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
}
.animate-in {
    opacity: 0;
    animation: fadeInUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }
.delay-5 { animation-delay: 0.5s; }

/* ─── Responsive ─── */
@media (max-width: 992px) {
    .stats-grid { grid-template-columns: 1fr; }
    .two-col-section { grid-template-columns: 1fr; }
    .history-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .topbar-greeting h1 { font-size: 1.2rem; }
    .stat-info .stat-value { font-size: 1.4rem; }
    .stat-info .stat-value.mono { font-size: 1.1rem; }
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

            <!-- Stats Grid -->
            <div class="stats-grid animate-in delay-2">

                <!-- Stat 1: Sewa Aktif -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-info">
                            <span class="stat-label">Sewa Aktif</span>
                            <div class="stat-value" data-counter="<?= $totalSewaCount ?>"><?= $totalSewaCount ?></div>
                        </div>
                        <div class="stat-visual">
                            <svg class="progress-ring" viewBox="0 0 56 56">
                                <circle class="ring-bg" cx="28" cy="28" r="22"/>
                                <circle class="ring-fill" cx="28" cy="28" r="22" data-progress="<?= $ringProgress ?>"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-items">
                        <?php if ($totalSewaCount === 0): ?>
                            <div class="stat-sub-item"><span class="dot"></span> Tidak ada sewa aktif</div>
                        <?php else: ?>
                            <?php if ($sewaAktifCount > 0): ?>
                            <?php foreach (array_slice($activeDetails, 0, 2) as $ad): ?>
                            <div class="stat-sub-item"><span class="dot" style="background:#52B788"></span> <strong><?= htmlspecialchars(mb_strimwidth($ad['names'], 0, 25, '...')) ?></strong> — <?= $ad['days_left'] ?> hari lagi</div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($sewaAkanDatang > 0): ?>
                            <div class="stat-sub-item"><span class="dot" style="background:#3B82F6"></span> <?= $sewaAkanDatang ?> sewa akan datang</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stat 2: Total Pengeluaran -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-info">
                            <span class="stat-label">Total Belanja</span>
                            <div class="stat-value mono">Rp <?= number_format($totalBelanja, 0, ',', '.') ?></div>
                        </div>
                        <div class="stat-visual">
                            <svg class="mini-chart" viewBox="0 0 80 40">
                                <defs>
                                    <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="rgba(82,183,136,0.25)"/>
                                        <stop offset="100%" stop-color="rgba(82,183,136,0)"/>
                                    </linearGradient>
                                </defs>
                                <path class="chart-area" d="M0,35 Q10,30 16,28 T32,22 T48,18 T64,10 T80,8 L80,40 L0,40 Z"/>
                                <path d="M0,35 Q10,30 16,28 T32,22 T48,18 T64,10 T80,8"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-items">
                        <div class="stat-sub-item"><span class="dot" style="background:#3B82F6"></span> <strong>+Rp <?= number_format($belanjaBulanIni, 0, ',', '.') ?></strong> bulan ini</div>
                    </div>
                </div>

                <!-- Stat 3: Poin Member -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-info">
                            <span class="stat-label">Poin Member</span>
                            <div class="stat-value"><?= $memberPoin ?> <small><?= htmlspecialchars($memberLevel) ?></small></div>
                        </div>
                        <div class="stat-visual">
                            <span class="member-tier"><i class="bi bi-award-fill"></i> <?= htmlspecialchars($memberLevel) ?></span>
                        </div>
                    </div>
                    <div class="member-progress">
                        <div class="progress-labels">
                            <span><?= htmlspecialchars($memberLevel) ?></span>
                            <span>Platinum (<?= $nextTierPts ?> pts)</span>
                        </div>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill" data-width="<?= $progressPercent ?>"></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Two Column Section -->
            <div class="two-col-section animate-in delay-3">

                <!-- Jadwal Sewa -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-calendar-event me-2"></i>Jadwal Sewa</h3>
                        <a href="<?= BASE_URL ?>/pages/pelanggan/transaksi.php" class="view-all">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="schedule-list">
                        <?php if (empty($scheduleItems)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x" style="font-size:2rem;"></i>
                                <p class="mt-2 mb-0" style="font-size:0.85rem;">Belum ada jadwal sewa</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($scheduleItems as $si):
                                $endDate = new DateTime($si['tanggal_selesai']);
                                $isActive = $si['status'] === 'aktif';
                                $bgStyle = $isActive ? '' : 'style="background:linear-gradient(135deg, #3B82F6, #60A5FA);"';
                                $statusClass = $isActive ? 'active' : 'upcoming';
                                $statusLabel = $isActive ? 'Aktif' : 'Akan Datang';
                            ?>
                            <div class="schedule-item">
                                <div class="schedule-date" <?= $bgStyle ?>>
                                    <span class="day"><?= $endDate->format('d') ?></span>
                                    <span class="month"><?= $endDate->format('M') ?></span>
                                </div>
                                <div class="schedule-info">
                                    <h4><?= htmlspecialchars(mb_strimwidth($si['names'], 0, 40, '...')) ?></h4>
                                    <div class="schedule-range"><i class="bi bi-calendar3"></i> <?= date('d M', strtotime($si['tanggal_mulai'])) ?> – <?= date('d M Y', strtotime($si['tanggal_selesai'])) ?></div>
                                    <span class="status-pill <?= $statusClass ?>"><i class="bi bi-circle-fill"></i> <?= $statusLabel ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Rekomendasi -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="bi bi-stars me-2"></i>Rekomendasi</h3>
                        <a href="<?= BASE_URL ?>/pages/pelanggan/katalog.php" class="view-all">Katalog <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="reko-list">
                        <?php if (empty($rekomendasi)): ?>
                            <div class="text-center py-4 text-muted">
                                <p class="mb-0" style="font-size:0.85rem;">Belum ada rekomendasi</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($rekomendasi as $reko):
                                $rekoImg = !empty($reko['gambar']) ? ASSETS_URL . '/img/barang/' . $reko['gambar'] : 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=200&q=80';
                            ?>
                            <a class="reko-card" href="<?= BASE_URL ?>/pages/pelanggan/detail_barang.php?id=<?= $reko['id'] ?>">
                                <div class="reko-img">
                                    <img src="<?= htmlspecialchars($rekoImg) ?>" alt="<?= htmlspecialchars($reko['nama']) ?>">
                                </div>
                                <div class="reko-info">
                                    <h4><?= htmlspecialchars($reko['nama']) ?></h4>
                                    <div class="reko-price">Rp <?= number_format($reko['harga_per_hari'], 0, ',', '.') ?>/hari</div>
                                    <div class="reko-rating">⭐⭐⭐⭐⭐ <span><?= isset($reko['total_sewa']) ? $reko['total_sewa'] : 0 ?>x disewa</span></div>
                                </div>
                                <div class="reko-action">
                                    <button class="btn-sewa-sm">Sewa</button>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Riwayat Terakhir -->
            <div class="dash-card animate-in delay-4">
                <div class="dash-card-header">
                    <h3><i class="bi bi-clock-history me-2"></i>Riwayat Terakhir</h3>
                    <a href="<?= BASE_URL ?>/pages/pelanggan/transaksi.php" class="view-all">Semua Transaksi <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="history-grid">
                    <?php if (empty($historyItems)): ?>
                        <div class="text-center py-4 text-muted" style="grid-column:1/-1;">
                            <i class="bi bi-clock" style="font-size:2rem;"></i>
                            <p class="mt-2 mb-0" style="font-size:0.85rem;">Belum ada riwayat transaksi</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($historyItems as $hi):
                            $dotClass = 'gray';
                            $statusPillClass = 'completed';
                            $statusLabel = 'Selesai';
                            $statusIcon = 'bi-check-circle-fill';
                            if (in_array($hi['status'], ['aktif', 'dibayar'])) {
                                $dotClass = 'green';
                                $statusPillClass = 'active';
                                $statusLabel = 'Aktif';
                                $statusIcon = 'bi-circle-fill';
                            } elseif ($hi['status'] === 'menunggu_bayar') {
                                $dotClass = 'blue';
                                $statusPillClass = 'upcoming';
                                $statusLabel = 'Menunggu Bayar';
                                $statusIcon = 'bi-circle-fill';
                            }
                        ?>
                        <div class="history-item">
                            <span class="history-dot <?= $dotClass ?>"></span>
                            <div class="history-details">
                                <div class="history-id">#<?= htmlspecialchars($hi['kode']) ?></div>
                                <div class="history-items-text"><?= htmlspecialchars($hi['items'] ?: 'Peralatan camping') ?></div>
                                <div class="history-date"><?= htmlspecialchars($hi['date']) ?></div>
                            </div>
                            <div class="history-meta">
                                <span class="history-amount">Rp <?= number_format($hi['amount'], 0, ',', '.') ?></span>
                                <span class="status-pill <?= $statusPillClass ?>"><i class="bi <?= $statusIcon ?>"></i> <?= $statusLabel ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.pelanggan-content -->
    </div><!-- /.pelanggan-main -->
</div><!-- /.pelanggan-wrapper -->

<!-- Welcome Toast -->
<div class="welcome-toast" id="welcomeToast">
    <div class="toast-icon">⛺</div>
    <div class="toast-text">
        <h4>Selamat datang kembali!</h4>
        <p>Kamu punya <?= $totalSewaCount ?> sewa aktif saat ini. Cek jadwalmu!</p>
    </div>
    <button class="toast-close" onclick="this.parentElement.style.display='none'">
        <i class="bi bi-x-lg"></i>
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Counter Animation ───
    document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseInt(el.dataset.counter);
        let current = 0;
        const increment = Math.max(1, Math.floor(target / 30));
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            el.textContent = current;
        }, 50);
    });

    // ─── Progress Ring Animation ───
    setTimeout(() => {
        document.querySelectorAll('.ring-fill').forEach(ring => {
            const progress = parseInt(ring.dataset.progress);
            const circumference = 2 * Math.PI * 22; // r=22
            const offset = circumference - (progress / 100) * circumference;
            ring.style.strokeDashoffset = offset;
        });
    }, 500);

    // ─── Progress Bar Animation ───
    setTimeout(() => {
        document.querySelectorAll('.progress-bar-fill').forEach(bar => {
            bar.style.width = bar.dataset.width + '%';
        });
    }, 600);

    // ─── Auto-hide Toast ───
    setTimeout(() => {
        const toast = document.getElementById('welcomeToast');
        if (toast) {
            toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.style.display = 'none', 400);
        }
    }, 8000);
});
</script>
</body>
</html>
