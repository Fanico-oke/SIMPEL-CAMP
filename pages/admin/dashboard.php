<?php
// pages/admin/dashboard.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';
require_once dirname(__DIR__, 2) . '/classes/Kategori.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . '/login.php'); exit; }
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$page_title = 'Dashboard';
$current_page = 'dashboard';

// Fetch real stats
$totalBarang = Barang::count();
$totalPelanggan = User::countByRole('pelanggan');
$reservasiPending = Reservasi::count('pending');
$transaksiAktif = Transaksi::count('aktif');
$pendapatanBulanIni = Transaksi::totalPendapatan(date('Y-m-01'), date('Y-m-t'));

// Fetch recent reservations
$allReservasi = Reservasi::getAll();
$recentReservasi = array_slice($allReservasi, 0, 3);

// Low stock items (stok <= 3)
$allBarang = Barang::getAll();
$lowStockItems = [];
foreach ($allBarang as $b) {
    if (isset($b['stok_tersedia']) && $b['stok_tersedia'] <= 3) {
        $lowStockItems[] = $b;
    }
}

// Monthly revenue for chart
$currentYear = date('Y');
$currentMonth = (int)date('m');
$monthLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$monthlyRev = [];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT MONTH(created_at) as bulan, COALESCE(SUM(total_bayar), 0) as total
        FROM transaksi WHERE status = 'selesai' AND YEAR(created_at) = ?
        GROUP BY MONTH(created_at) ORDER BY bulan ASC
    ");
    $stmt->execute([$currentYear]);
    $revByMonth = [];
    foreach ($stmt->fetchAll() as $row) {
        $revByMonth[(int)$row['bulan']] = (float)$row['total'];
    }
    for ($m = 1; $m <= $currentMonth; $m++) {
        $monthlyRev[] = ['month' => $monthLabels[$m-1], 'value' => $revByMonth[$m] ?? 0];
    }
} catch (Exception $e) {
    for ($m = 1; $m <= $currentMonth; $m++) {
        $monthlyRev[] = ['month' => $monthLabels[$m-1], 'value' => 0];
    }
}
$maxRev = max(array_column($monthlyRev, 'value') ?: [1]);
if ($maxRev == 0) $maxRev = 1;

// Admin name
$adminName = $_SESSION['nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781677663">
    <style>
        /* ═══════════════════════════════════════════
           DASHBOARD PREMIUM REDESIGN
           ═══════════════════════════════════════════ */

        /* Toast */
        .dash-toast-container {
            position: fixed; top: 24px; right: 24px; z-index: 9999;
            display: flex; flex-direction: column; gap: 12px;
        }
        .dash-toast {
            background: rgba(27,67,50,0.95); backdrop-filter: blur(20px);
            color: #fff; padding: 14px 24px; border-radius: var(--radius-md);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: flex; align-items: center; gap: 12px;
            transform: translateX(120%);
            transition: transform 0.5s cubic-bezier(0.34,1.56,0.64,1);
            border-left: 4px solid var(--primary-lighter); min-width: 320px;
        }
        .dash-toast.show { transform: translateX(0); }
        .dash-toast i { font-size: 1.3rem; color: var(--primary-lighter); }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #1B4332 0%, #2D6A4F 40%, #1B4332 100%);
            border-radius: var(--radius-lg); padding: 2.5rem;
            color: #fff; margin-bottom: 2rem; position: relative; overflow: hidden;
            animation: bannerFadeIn 0.8s ease forwards;
        }
        @keyframes bannerFadeIn { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
        .welcome-banner::before {
            content: ''; position: absolute; top: -60%; right: -15%;
            width: 450px; height: 450px;
            background: radial-gradient(circle, rgba(82,183,136,0.15) 0%, transparent 70%);
            border-radius: 50%; animation: floatShape 8s ease-in-out infinite;
        }
        .welcome-banner::after {
            content: ''; position: absolute; bottom: -50%; left: -10%;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(212,163,115,0.1) 0%, transparent 70%);
            border-radius: 50%; animation: floatShape 10s ease-in-out infinite reverse;
        }
        @keyframes floatShape {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, -20px); }
        }
        .welcome-banner h2 { font-family:'Outfit',sans-serif; font-weight:800; font-size:1.8rem; margin:0 0 0.4rem; position:relative; z-index:1; }
        .welcome-banner p { opacity:0.8; margin:0; position:relative; z-index:1; }
        .welcome-clock {
            position: relative; z-index:1;
            font-family: 'JetBrains Mono', monospace; font-size: 2rem; font-weight: 700;
            color: var(--accent); margin-top: 0.5rem;
        }
        .welcome-date {
            position: relative; z-index:1;
            font-size: 0.9rem; opacity: 0.7;
        }

        /* Stat Cards */
        .stat-card {
            background: rgba(255,255,255,0.9); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: var(--radius-md); padding: 1.5rem;
            transition: all 0.4s cubic-bezier(0.34,1.56,0.64,1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            opacity: 0; transform: translateY(30px);
        }
        .stat-card.visible { opacity: 1; transform: translateY(0); }
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(27,67,50,0.15);
        }
        .stat-icon {
            width: 52px; height: 52px; border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .stat-icon.green { background: rgba(82,183,136,0.12); color: var(--primary-lighter); }
        .stat-icon.blue { background: rgba(59,130,246,0.12); color: #3B82F6; }
        .stat-icon.orange { background: rgba(249,115,22,0.12); color: #F97316; }
        .stat-icon.gold { background: rgba(212,163,115,0.12); color: var(--accent); }

        .stat-value {
            font-family: 'Outfit', sans-serif; font-weight: 800;
            font-size: 1.8rem; line-height: 1.2; color: var(--text-primary);
        }
        .stat-label {
            font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;
        }

        /* Section Cards */
        .dash-section {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-md); padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
        }
        .dash-section:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .section-title {
            font-family: 'Outfit', sans-serif; font-weight: 700;
            font-size: 1.1rem; margin-bottom: 1rem;
            display: flex; align-items: center; gap: 8px;
        }
        .section-title i { color: var(--primary-lighter); }

        /* Reservation Cards */
        .rsv-card {
            border: 1px solid var(--border); border-radius: var(--radius-md);
            padding: 1rem 1.25rem; margin-bottom: 10px; cursor: pointer;
            transition: all 0.3s ease; background: var(--bg-card);
        }
        .rsv-card:hover {
            border-color: var(--primary-lighter); transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(27,67,50,0.1);
        }
        .rsv-id {
            font-family: 'JetBrains Mono', monospace; font-weight: 700;
            font-size: 0.85rem; color: var(--primary);
        }
        .rsv-name { font-weight: 700; font-size: 0.95rem; }
        .rsv-items { font-size: 0.8rem; color: var(--text-secondary); }
        .rsv-meta {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 8px; font-size: 0.8rem;
        }
        .rsv-price { font-family: 'JetBrains Mono', monospace; font-weight: 600; }

        /* Status Badges */
        .badge-status {
            padding: 4px 12px; border-radius: 20px; font-size: 0.72rem;
            font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .badge-confirmed { background: rgba(16,185,129,0.12); color: #10B981; }
        .badge-pending { background: rgba(245,158,11,0.12); color: #F59E0B; }
        .badge-active { background: rgba(59,130,246,0.12); color: #3B82F6; }

        /* Quick Actions */
        .quick-action {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: var(--radius-sm);
            text-decoration: none; color: #fff; font-weight: 600;
            font-size: 0.85rem; transition: all 0.3s ease;
            border: none; width: 100%;
        }
        .quick-action:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.2); color: #fff; }
        .quick-action i { font-size: 1.1rem; }
        .qa-green { background: linear-gradient(135deg, #1B4332, #2D6A4F); }
        .qa-blue { background: linear-gradient(135deg, #1E40AF, #3B82F6); }
        .qa-orange { background: linear-gradient(135deg, #9A3412, #F97316); }
        .qa-gold { background: linear-gradient(135deg, #8B6914, #D4A373); }

        /* Low Stock Alert */
        .stock-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 14px; border-radius: var(--radius-sm);
            margin-bottom: 8px; cursor: pointer; transition: all 0.3s ease;
            border: 1px solid var(--border); background: var(--bg-card);
        }
        .stock-item:hover {
            border-color: var(--danger); background: rgba(239,68,68,0.03);
            transform: translateX(4px);
        }
        .stock-item .stock-name { font-weight: 600; font-size: 0.9rem; }
        .stock-count {
            font-family: 'JetBrains Mono', monospace; font-weight: 700;
            font-size: 0.85rem; padding: 4px 12px; border-radius: 20px;
        }
        .stock-danger { background: rgba(239,68,68,0.1); color: var(--danger); }
        .stock-warning { background: rgba(245,158,11,0.1); color: var(--warning); }

        /* Pulse animation for critical stock */
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.3); }
            50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
        }
        .pulse-danger { animation: pulse 2s ease-in-out infinite; }

        /* Mini Calendar */
        .mini-cal { font-family: 'Inter', sans-serif; }
        .mini-cal .cal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 12px;
        }
        .mini-cal .cal-header h6 { margin: 0; font-weight: 700; font-family: 'Outfit', sans-serif; }
        .mini-cal .cal-grid {
            display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;
            text-align: center;
        }
        .mini-cal .cal-day-name {
            font-size: 0.7rem; font-weight: 700; color: var(--text-secondary);
            text-transform: uppercase; padding: 4px 0;
        }
        .mini-cal .cal-day {
            width: 36px; height: 36px; display: flex; align-items: center;
            justify-content: center; border-radius: 50%; font-size: 0.8rem;
            font-weight: 500; cursor: default; transition: all 0.2s ease;
            margin: 0 auto; position: relative;
        }
        .mini-cal .cal-day.today {
            background: linear-gradient(135deg, var(--primary), var(--primary-lighter));
            color: #fff; font-weight: 700;
        }
        .mini-cal .cal-day.has-event::after {
            content: ''; position: absolute; bottom: 3px;
            width: 5px; height: 5px; border-radius: 50%;
            background: var(--accent);
        }
        .mini-cal .cal-day.empty { visibility: hidden; }

        /* Modal */
        .modal-content {
            border: none; border-radius: var(--radius-lg);
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
        }
        .modal-header { border-bottom: 1px solid var(--border); padding: 1.5rem; }
        .modal-header .modal-title { font-family:'Outfit',sans-serif; font-weight:700; }
        .modal-body { padding: 1.5rem; }

        .detail-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid var(--border);
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: var(--text-secondary); font-size: 0.85rem; }
        .detail-value { font-weight: 600; }

        @keyframes dashFadeIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .dash-animate { animation: dashFadeIn 0.6s ease forwards; }

        /* Dashboard Chart */
        .dash-chart-container {
            display:flex; align-items:flex-end; justify-content:space-around;
            height:220px; padding:10px 0 0; position:relative;
            border-left:2px solid #e8e8e8; border-bottom:2px solid #e8e8e8;
            margin-left:40px;
        }
        .dash-chart-bar-wrapper { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px }
        .dash-chart-bar {
            width:55%; max-width:44px; min-width:20px; border-radius:8px 8px 0 0;
            background:linear-gradient(180deg, #52B788, #2D6A4F);
            position:relative; cursor:pointer;
        }
        .dash-chart-bar:hover { filter:brightness(1.15); }
        .dash-bar-active { background:linear-gradient(180deg, #D4A373, #c4956a) !important; }
        .dash-bar-tip {
            position:absolute; top:-32px; left:50%; transform:translateX(-50%);
            background:#1B4332; color:#fff; padding:3px 8px; border-radius:6px;
            font-size:.7rem; font-family:'JetBrains Mono',monospace; white-space:nowrap;
            opacity:0; transition:opacity .3s; pointer-events:none;
        }
        .dash-chart-bar:hover .dash-bar-tip { opacity:1; }
        .dash-chart-label { font-size:.75rem; font-weight:600; color:#9ca3af; margin-top:6px }
        .dash-label-active { color:#D4A373; font-weight:700 }
        .dash-y-axis {
            position:absolute; left:0; top:0; bottom:0;
            display:flex; flex-direction:column; justify-content:space-between;
            align-items:flex-end; width:36px; padding-right:6px;
        }
        .dash-y-axis span { font-size:.68rem; color:#adb5bd; font-family:'JetBrains Mono',monospace }

        /* Donut */
        .dash-donut-wrap { position:relative; width:140px; height:140px; margin:10px auto 16px }
        .dash-donut { width:100%; height:100%; transform:rotate(-90deg) }
        .dash-donut-seg { transition:stroke-dasharray .8s ease, stroke-dashoffset .8s ease }
        .dash-donut-center { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; display:flex; flex-direction:column }
        .dash-donut-val { font-family:'Outfit',sans-serif; font-size:1.6rem; font-weight:800; color:#1B4332; line-height:1 }
        .dash-donut-lbl { font-size:.7rem; color:#9ca3af; font-weight:500 }
        .dash-legend { display:flex; flex-direction:column; gap:6px }
        .dash-legend-item { display:flex; align-items:center; gap:8px; font-size:.8rem; color:#555 }
        .dash-legend-item b { margin-left:auto; color:#1B4332 }
        .dash-legend-dot { width:10px; height:10px; border-radius:3px; flex-shrink:0 }

        @media (max-width: 768px) {
            .welcome-banner { padding: 1.5rem; }
            .welcome-banner h2 { font-size: 1.3rem; }
            .welcome-clock { font-size: 1.5rem; }
            .dash-chart-container { height:160px; margin-left:32px }
            .dash-donut-wrap { width:110px; height:110px }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


        <div class="admin-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h2>👋 Selamat datang kembali, <?= htmlspecialchars($adminName) ?>!</h2>
                        <p>Berikut ringkasan aktivitas toko Anda hari ini</p>
                    </div>
                    <div class="col-md-5 text-md-end mt-3 mt-md-0">
                        <div class="welcome-clock" id="liveClock">00:00:00</div>
                        <div class="welcome-date" id="liveDate"></div>
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card" style="transition-delay:0s">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon green"><i class="bi bi-box-seam"></i></div>
                            <span class="stat-label">Total Barang</span>
                        </div>
                        <div class="stat-value" data-target="<?= (int)$totalBarang ?>"><?= (int)$totalBarang ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card" style="transition-delay:0.15s">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon blue"><i class="bi bi-calendar-check"></i></div>
                            <span class="stat-label">Reservasi Pending</span>
                        </div>
                        <div class="stat-value" data-target="<?= (int)$reservasiPending ?>"><?= (int)$reservasiPending ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card" style="transition-delay:0.3s">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon orange"><i class="bi bi-bag-check"></i></div>
                            <span class="stat-label">Transaksi Aktif</span>
                        </div>
                        <div class="stat-value" data-target="<?= (int)$transaksiAktif ?>"><?= (int)$transaksiAktif ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card" style="transition-delay:0.45s">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon gold"><i class="bi bi-cash-stack"></i></div>
                            <span class="stat-label">Pendapatan Bulan Ini</span>
                        </div>
                        <div class="stat-value" data-target="<?= (int)$pendapatanBulanIni ?>" data-prefix="Rp " data-format="currency"><?= 'Rp ' . number_format($pendapatanBulanIni, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="dash-section" style="padding:24px">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="section-title mb-0"><i class="bi bi-bar-chart-line"></i>Pendapatan Bulanan</div>
                            <div class="d-flex gap-2">
                                <span class="badge rounded-pill" style="background:rgba(82,183,136,0.12);color:#2D6A4F;font-weight:600;padding:6px 14px"><?= date('Y') ?></span>
                            </div>
                        </div>
                        <?php
                        // Chart config
                        $chartHeight = 200; // px
                        $ySteps = 4;
                        $yLabels = [];
                        for ($i = $ySteps; $i >= 0; $i--) {
                            $val = ($maxRev / $ySteps) * $i;
                            if ($val >= 1000000) $yLabels[] = round($val / 1000000, 1) . 'jt';
                            elseif ($val >= 1000) $yLabels[] = round($val / 1000) . 'rb';
                            else $yLabels[] = (int)$val;
                        }
                        ?>
                        <div style="display:flex;gap:0">
                            <!-- Y-axis -->
                            <div style="display:flex;flex-direction:column;justify-content:space-between;height:<?= $chartHeight ?>px;padding-right:8px;min-width:40px">
                                <?php foreach ($yLabels as $yl): ?>
                                <span style="font-size:.7rem;color:#9ca3af;text-align:right;font-family:'JetBrains Mono',monospace"><?= $yl ?></span>
                                <?php endforeach; ?>
                            </div>
                            <!-- Bars -->
                            <div style="flex:1;display:flex;align-items:flex-end;justify-content:space-around;height:<?= $chartHeight ?>px;border-left:2px solid #e8e8e8;border-bottom:2px solid #e8e8e8;padding:0 8px">
                                <?php foreach ($monthlyRev as $i => $mr):
                                    $barH = $maxRev > 0 ? round(($mr['value'] / $maxRev) * ($chartHeight - 20)) : 0;
                                    if ($barH < 4 && $mr['value'] > 0) $barH = 4;
                                    $isLast = ($i === count($monthlyRev) - 1);
                                    $bg = $isLast ? 'linear-gradient(180deg,#D4A373,#c4956a)' : 'linear-gradient(180deg,#52B788,#2D6A4F)';
                                    $tipText = 'Rp ' . number_format($mr['value'], 0, ',', '.');
                                    $lblColor = $isLast ? '#D4A373' : '#9ca3af';
                                    $lblWeight = $isLast ? '700' : '600';
                                ?>
                                <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex:1">
                                    <div style="width:60%;max-width:44px;min-width:20px;height:<?= $barH ?>px;border-radius:8px 8px 0 0;background:<?= $bg ?>;position:relative;cursor:pointer" title="<?= $tipText ?>">
                                        <div style="position:absolute;top:-28px;left:50%;transform:translateX(-50%);background:#1B4332;color:#fff;padding:2px 8px;border-radius:6px;font-size:.65rem;font-family:'JetBrains Mono',monospace;white-space:nowrap;opacity:0;pointer-events:none" class="dash-tip"><?= $tipText ?></div>
                                    </div>
                                    <span style="font-size:.75rem;font-weight:<?= $lblWeight ?>;color:<?= $lblColor ?>"><?= $mr['month'] ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="dash-section" style="padding:24px;height:100%">
                        <div class="section-title"><i class="bi bi-pie-chart"></i>Kategori Populer</div>
                        <div class="dash-donut-wrap">
                            <svg viewBox="0 0 36 36" class="dash-donut">
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e8e8e8" stroke-width="3.5"/>
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#2D6A4F" stroke-width="3.5" stroke-dasharray="35 65" stroke-dashoffset="25" class="dash-donut-seg"/>
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#52B788" stroke-width="3.5" stroke-dasharray="25 75" stroke-dashoffset="-10" class="dash-donut-seg"/>
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#D4A373" stroke-width="3.5" stroke-dasharray="20 80" stroke-dashoffset="-35" class="dash-donut-seg"/>
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#3b82f6" stroke-width="3.5" stroke-dasharray="12 88" stroke-dashoffset="-55" class="dash-donut-seg"/>
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#8b5cf6" stroke-width="3.5" stroke-dasharray="8 92" stroke-dashoffset="-67" class="dash-donut-seg"/>
                            </svg>
                            <div class="dash-donut-center">
                                <span class="dash-donut-val"><?= (int)$totalBarang ?></span>
                                <span class="dash-donut-lbl">Barang</span>
                            </div>
                        </div>
                        <div class="dash-legend">
                            <div class="dash-legend-item"><span class="dash-legend-dot" style="background:#2D6A4F"></span>Tenda <b>35%</b></div>
                            <div class="dash-legend-item"><span class="dash-legend-dot" style="background:#52B788"></span>Tas <b>25%</b></div>
                            <div class="dash-legend-item"><span class="dash-legend-dot" style="background:#D4A373"></span>Tidur <b>20%</b></div>
                            <div class="dash-legend-item"><span class="dash-legend-dot" style="background:#3b82f6"></span>Masak <b>12%</b></div>
                            <div class="dash-legend-item"><span class="dash-legend-dot" style="background:#8b5cf6"></span>Lainnya <b>8%</b></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="row g-4">
                <!-- LEFT COLUMN -->
                <div class="col-lg-8">
                    <!-- Recent Reservations -->
                    <div class="dash-section mb-4">
                        <div class="section-title">
                            <i class="bi bi-clipboard-data"></i>Reservasi Terbaru
                            <a href="<?= BASE_URL ?>/pages/admin/transaksi.php" class="ms-auto text-decoration-none small fw-semibold" style="color:var(--primary-lighter)">Lihat Semua →</a>
                        </div>

                        <?php if (empty($recentReservasi)): ?>
                            <p class="text-muted text-center py-3">Belum ada reservasi</p>
                        <?php else: ?>
                            <?php foreach ($recentReservasi as $idx => $r):
                                $statusMap = [
                                    'pending' => ['badge-pending', 'Pending'],
                                    'dikonfirmasi' => ['badge-confirmed', 'Dikonfirmasi'],
                                    'aktif' => ['badge-active', 'Aktif'],
                                    'selesai' => ['badge-confirmed', 'Selesai'],
                                    'batal' => ['badge-pending', 'Batal'],
                                ];
                                $st = $r['status'] ?? 'pending';
                                $badgeClass = $statusMap[$st][0] ?? 'badge-pending';
                                $badgeText = $statusMap[$st][1] ?? ucfirst($st);
                                $rsvId = $r['id'] ?? $r['kode_reservasi'] ?? '-';
                                $plgNama = $r['user_nama'] ?? 'Pelanggan';
                                $tglSewa = isset($r['tanggal_mulai']) ? date('d M Y', strtotime($r['tanggal_mulai'])) : '-';
                                $tglKembali = isset($r['tanggal_selesai']) ? date('d M Y', strtotime($r['tanggal_selesai'])) : '-';
                                $total = $r['total_biaya'] ?? $r['total'] ?? 0;
                            ?>
                            <div class="rsv-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="rsv-id">#<?= htmlspecialchars($rsvId) ?></span>
                                        <div class="rsv-name"><?= htmlspecialchars($plgNama) ?></div>
                                        <div class="rsv-items"><?php $dtl = Reservasi::getDetail($r['id']); $brgNames = array_map(fn($d)=>$d['barang_nama'], $dtl); echo htmlspecialchars(implode(', ', $brgNames) ?: 'Peralatan camping'); ?></div>
                                    </div>
                                    <span class="badge-status <?= $badgeClass ?>"><?= htmlspecialchars($badgeText) ?></span>
                                </div>
                                <div class="rsv-meta">
                                    <span><i class="bi bi-calendar3 me-1"></i><?= $tglSewa ?> - <?= $tglKembali ?></span>
                                    <span class="rsv-price">Rp <?= number_format($total, 0, ',', '.') ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dash-section">
                        <div class="section-title"><i class="bi bi-lightning-charge"></i>Aksi Cepat</div>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <a href="<?= BASE_URL ?>/pages/admin/kelola_barang.php" class="quick-action qa-green">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>Tambah Barang</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="<?= BASE_URL ?>/pages/admin/transaksi.php" class="quick-action qa-blue">
                                    <i class="bi bi-clipboard-plus"></i>
                                    <span>Buat Reservasi</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="<?= BASE_URL ?>/pages/admin/laporan.php" class="quick-action qa-orange">
                                    <i class="bi bi-graph-up"></i>
                                    <span>Lihat Laporan</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="<?= BASE_URL ?>/pages/admin/pelanggan.php" class="quick-action qa-gold">
                                    <i class="bi bi-people"></i>
                                    <span>Pelanggan</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="col-lg-4">
                    <!-- Low Stock Alert -->
                    <div class="dash-section mb-4">
                        <div class="section-title">
                            <i class="bi bi-exclamation-triangle text-danger"></i>Stok Menipis
                        </div>

                        <?php if (empty($lowStockItems)): ?>
                            <p class="text-muted text-center py-3">Semua stok aman</p>
                        <?php else: ?>
                            <?php foreach ($lowStockItems as $item):
                                $stok = (int)($item['stok_tersedia'] ?? 0);
                                $stockClass = $stok <= 1 ? 'stock-danger' : 'stock-warning';
                                $pulseClass = $stok <= 1 ? 'pulse-danger' : '';
                            ?>
                            <div class="stock-item <?= $pulseClass ?>">
                                <div>
                                    <div class="stock-name"><?= htmlspecialchars($item['nama'] ?? '-') ?></div>
                                    <small class="text-secondary"><?= $stok <= 1 ? 'Segera restok' : 'Stok rendah' ?></small>
                                </div>
                                <span class="stock-count <?= $stockClass ?>"><?= $stok ?> unit</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Mini Calendar -->
                    <div class="dash-section">
                        <div class="section-title"><i class="bi bi-calendar3"></i>Kalender</div>
                        <div class="mini-cal" id="miniCalendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="dash-toast-container" id="dashToastContainer"></div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ═══════════════════════════════════════════
    // LIVE CLOCK
    // ═══════════════════════════════════════════
    function updateClock() {
        const now = new Date();
        const time = now.toLocaleTimeString('id-ID', { hour12: false });
        document.getElementById('liveClock').textContent = time;

        const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        document.getElementById('liveDate').textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    }
    updateClock();
    setInterval(updateClock, 1000);

    // ═══════════════════════════════════════════
    // ANIMATED COUNTERS
    // ═══════════════════════════════════════════
    function animateCounter(el) {
        const target = parseInt(el.dataset.target);
        const prefix = el.dataset.prefix || '';
        const isCurrency = el.dataset.format === 'currency';
        const duration = 1500;
        const startTime = performance.now();

        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            const current = Math.floor(eased * target);

            if (isCurrency) {
                el.textContent = prefix + current.toLocaleString('id-ID');
            } else {
                el.textContent = prefix + current;
            }

            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    // ═══════════════════════════════════════════
    // STAGGER ANIMATION
    // ═══════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.stat-card');
        cards.forEach((card, i) => {
            setTimeout(() => {
                card.classList.add('visible');
                const counter = card.querySelector('.stat-value');
                if (counter) animateCounter(counter);
            }, 150 * i);
        });
    });

    // ═══════════════════════════════════════════
    // MINI CALENDAR
    // ═══════════════════════════════════════════
    function renderCalendar() {
        const container = document.getElementById('miniCalendar');
        const now = new Date();
        const year = now.getFullYear(), month = now.getMonth();
        const today = now.getDate();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDay = new Date(year, month, 1).getDay();
        const dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
        const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        let html = '<div class="cal-header"><h6>' + monthNames[month] + ' ' + year + '</h6></div>';
        html += '<div class="cal-grid">';
        dayNames.forEach(d => { html += `<div class="cal-day-name">${d}</div>`; });

        for (let i = 0; i < firstDay; i++) { html += '<div class="cal-day empty"></div>'; }
        for (let d = 1; d <= daysInMonth; d++) {
            let cls = 'cal-day';
            if (d === today) cls += ' today';
            html += `<div class="${cls}">${d}</div>`;
        }
        html += '</div>';
        container.innerHTML = html;
    }
    renderCalendar();

    // ═══════════════════════════════════════════
    // TOAST
    // ═══════════════════════════════════════════
    function dashToast(msg, icon = 'bi-check-circle-fill') {
        const c = document.getElementById('dashToastContainer');
        const t = document.createElement('div');
        t.className = 'dash-toast';
        t.innerHTML = `<i class="bi ${icon}"></i><span>${msg}</span>`;
        c.appendChild(t);
        requestAnimationFrame(() => t.classList.add('show'));
        setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 500); }, 4000);
    }

    // Welcome toast
    setTimeout(() => {
        dashToast('Selamat datang kembali! Ada <?= (int)$reservasiPending ?> reservasi pending.', 'bi-bell-fill');
    }, 1000);

    // Animate dashboard chart bars
    setTimeout(() => {
        document.querySelectorAll('#dashBarChart .dash-chart-bar').forEach((bar, i) => {
            const h = bar.dataset.height;
            setTimeout(() => { bar.style.height = h + '%'; }, i * 150);
        });
    }, 800);
</script>
</body>
</html>
