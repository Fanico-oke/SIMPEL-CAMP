<?php
// pages/superadmin/dashboard.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/Kategori.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';
require_once dirname(__DIR__, 2) . '/classes/LogAktivitas.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'superadmin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Super Admin Dashboard';
$current_page = 'dashboard';

// --- Stats ---
$totalRevenue = Transaksi::totalPendapatan();
$totalTransaksi = Transaksi::count();
$totalPelanggan = User::countByRole('pelanggan');
$avgPerTransaksi = $totalTransaksi > 0 ? $totalRevenue / $totalTransaksi : 0;

// --- Monthly revenue for bar chart (current year) ---
$currentYear = date('Y');
$currentMonth = (int)date('m');
$monthlyRevenue = [];
$monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT MONTH(created_at) as bulan, COALESCE(SUM(total_bayar), 0) as total
        FROM transaksi
        WHERE status = 'selesai' AND YEAR(created_at) = ?
        GROUP BY MONTH(created_at)
        ORDER BY bulan ASC
    ");
    $stmt->execute([$currentYear]);
    $rows = $stmt->fetchAll();
    $revenueByMonth = [];
    foreach ($rows as $row) {
        $revenueByMonth[(int)$row['bulan']] = (float)$row['total'];
    }
    // Build data for months up to current month
    for ($m = 1; $m <= $currentMonth; $m++) {
        $monthlyRevenue[] = [
            'month' => $monthNames[$m - 1],
            'value' => $revenueByMonth[$m] ?? 0,
        ];
    }
} catch (Exception $e) {
    for ($m = 1; $m <= $currentMonth; $m++) {
        $monthlyRevenue[] = ['month' => $monthNames[$m - 1], 'value' => 0];
    }
}
$maxRevenue = max(array_column($monthlyRevenue, 'value') ?: [1]);
if ($maxRevenue == 0) $maxRevenue = 1;

// --- Category distribution (donut chart) ---
$kategoriList = Kategori::getAll();
$totalBarangAll = Barang::count();
$categoryData = [];
$categoryColors = ['#D4A373', '#52B788', '#3B82F6', '#8B5CF6', '#EF4444', '#F59E0B', '#06B6D4', '#EC4899'];
$otherCount = 0;
$categorySlices = [];
foreach ($kategoriList as $i => $kat) {
    $jumlah = (int)$kat['jumlah_barang'];
    if ($i < 4) {
        $categorySlices[] = [
            'nama' => htmlspecialchars($kat['nama']),
            'jumlah' => $jumlah,
            'color' => $categoryColors[$i] ?? '#9CA3AF',
        ];
    } else {
        $otherCount += $jumlah;
    }
}
if ($otherCount > 0) {
    $categorySlices[] = ['nama' => 'Lainnya', 'jumlah' => $otherCount, 'color' => '#EF4444'];
}
// Calculate donut percentages and conic-gradient
$totalKatBarang = array_sum(array_column($categorySlices, 'jumlah'));
if ($totalKatBarang == 0) $totalKatBarang = 1;
$conicParts = [];
$degOffset = 0;
foreach ($categorySlices as &$slice) {
    $pct = round(($slice['jumlah'] / $totalKatBarang) * 100);
    $slice['pct'] = $pct;
    $deg = round(($slice['jumlah'] / $totalKatBarang) * 360);
    $endDeg = $degOffset + $deg;
    $conicParts[] = $slice['color'] . " {$degOffset}deg {$endDeg}deg";
    $degOffset = $endDeg;
}
unset($slice);
$conicGradient = implode(",\n                ", $conicParts);

// --- Top 5 Barang ---
$topBarang = Barang::getPopuler(5);

// --- Top 5 Pelanggan (custom query) ---
$topPelanggan = [];
try {
    $db = Database::getInstance();
    $stmt = $db->query("
        SELECT u.id, u.nama, COUNT(t.id) AS total_transaksi, COALESCE(SUM(t.total_bayar), 0) AS total_spent
        FROM users u
        JOIN transaksi t ON u.id = t.user_id AND t.status = 'selesai'
        WHERE u.role = 'pelanggan'
        GROUP BY u.id
        ORDER BY total_spent DESC
        LIMIT 5
    ");
    $topPelanggan = $stmt->fetchAll();
} catch (Exception $e) {
    $topPelanggan = [];
}

// --- Recent Activity ---
$recentActivities = LogAktivitas::getAll(['limit' => 8]);

// --- System Health ---
$totalBarang = Barang::count();
$totalLogs = LogAktivitas::count();
$totalAdmins = User::countByRole('admin');
$totalSuperAdmins = User::countByRole('superadmin');
$dbSize = '—';
try {
    $db = Database::getInstance();
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    $stmt = $db->prepare("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS size_mb
        FROM information_schema.TABLES
        WHERE table_schema = ?
    ");
    $stmt->execute([$dbName]);
    $row = $stmt->fetch();
    $dbSize = ($row && $row['size_mb']) ? $row['size_mb'] . ' MB' : '—';
} catch (Exception $e) {
    $dbSize = '—';
}

// Helper: time ago in Indonesian
function timeAgoId($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    if ($diff->d > 0) {
        return $diff->d == 1 ? '1 hari lalu' : $diff->d . ' hari lalu';
    }
    if ($diff->h > 0) {
        return $diff->h . ' jam lalu';
    }
    if ($diff->i > 0) {
        return $diff->i . ' menit lalu';
    }
    return 'Baru saja';
}

// Helper: tier class
function getTierClass($totalSpent) {
    if ($totalSpent >= 5000000) return ['tier-gold', 'Gold'];
    if ($totalSpent >= 3000000) return ['tier-silver', 'Silver'];
    if ($totalSpent >= 1000000) return ['tier-bronze', 'Bronze'];
    return ['tier-regular', 'Regular'];
}

$userName = htmlspecialchars($_SESSION['nama'] ?? 'Super Admin');
$userInitial = strtoupper(substr($_SESSION['nama'] ?? 'S', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Super Admin Dashboard - SIMPEL-CAMP Management System">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=<?= time() ?>">
    <style>
        /* ============================================================
           SUPER ADMIN DASHBOARD — Premium Styles
           ============================================================ */

        /* --- Welcome Banner --- */
        .sa-welcome-banner {
            background: linear-gradient(135deg, #081C15 0%, #1B4332 40%, #2D6A4F 80%, #40916C 100%);
            border-radius: var(--radius-lg);
            padding: 2rem 2.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(212, 163, 115, 0.15);
        }

        .sa-welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(212, 163, 115, 0.12) 0%, transparent 70%);
            border-radius: 50%;
        }

        .sa-welcome-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #D4A373, #E9C46A, #D4A373);
        }

        .sa-welcome-banner h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            position: relative;
            z-index: 1;
        }

        .sa-welcome-banner h1 span {
            background: linear-gradient(135deg, #D4A373, #E9C46A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sa-welcome-banner .sa-datetime {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.55);
            position: relative;
            z-index: 1;
        }

        .sa-welcome-banner .sa-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(212, 163, 115, 0.15);
            border: 1px solid rgba(212, 163, 115, 0.3);
            color: #E9C46A;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: 0.05em;
            position: relative;
            z-index: 1;
        }

        /* --- Revenue Cards --- */
        .sa-stat-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sa-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
        }

        .sa-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(27, 67, 50, 0.1);
        }

        .sa-stat-card.card-revenue::before { background: linear-gradient(90deg, #D4A373, #E9C46A); }
        .sa-stat-card.card-transaksi::before { background: linear-gradient(90deg, #3B82F6, #60A5FA); }
        .sa-stat-card.card-pelanggan::before { background: linear-gradient(90deg, #52B788, #40916C); }
        .sa-stat-card.card-average::before { background: linear-gradient(90deg, #8B5CF6, #A78BFA); }

        .sa-stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .sa-stat-card .stat-icon.icon-revenue {
            background: linear-gradient(135deg, rgba(212, 163, 115, 0.15), rgba(233, 196, 106, 0.15));
            color: #D4A373;
        }
        .sa-stat-card .stat-icon.icon-transaksi {
            background: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
        }
        .sa-stat-card .stat-icon.icon-pelanggan {
            background: rgba(82, 183, 136, 0.1);
            color: #52B788;
        }
        .sa-stat-card .stat-icon.icon-average {
            background: rgba(139, 92, 246, 0.1);
            color: #8B5CF6;
        }

        .sa-stat-card .stat-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .sa-stat-card .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .sa-stat-card .stat-trend {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .sa-stat-card .stat-trend.up {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }

        /* --- Premium Section Card --- */
        .sa-section-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .sa-section-card .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .sa-section-card .section-header h5 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sa-section-card .section-body {
            padding: 1.5rem;
        }

        /* --- Revenue Chart (CSS Bar Chart) --- */
        .sa-chart-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 220px;
            padding: 1rem 0.5rem 0;
            border-bottom: 2px solid var(--border);
            border-left: 2px solid var(--border);
            position: relative;
        }

        .sa-chart-bar-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            flex: 1;
            max-width: 60px;
        }

        .sa-chart-bar {
            width: 100%;
            max-width: 36px;
            border-radius: 6px 6px 0 0;
            position: relative;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: barGrow 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            transform-origin: bottom;
        }

        .sa-chart-bar.bar-default {
            background: linear-gradient(180deg, #2D6A4F, #1B4332);
        }

        .sa-chart-bar.bar-current {
            background: linear-gradient(180deg, #E9C46A, #D4A373);
            box-shadow: 0 -4px 12px rgba(212, 163, 115, 0.3);
        }

        .sa-chart-bar:hover {
            opacity: 0.85;
            transform: scaleX(1.1);
        }

        .sa-chart-bar .bar-tooltip {
            position: absolute;
            top: -28px;
            left: 50%;
            transform: translateX(-50%);
            background: #081C15;
            color: #E9C46A;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 4px;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        .sa-chart-bar:hover .bar-tooltip {
            opacity: 1;
        }

        .sa-chart-label {
            font-size: 0.72rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .sa-chart-label.current {
            color: #D4A373;
            font-weight: 700;
        }

        @keyframes barGrow {
            from { height: 0 !important; }
        }

        /* --- Donut Chart (CSS conic-gradient) --- */
        .sa-donut-chart {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            position: relative;
            margin: 0 auto;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .sa-donut-chart::after {
            content: '';
            position: absolute;
            inset: 35px;
            background: var(--bg-card);
            border-radius: 50%;
        }

        .sa-donut-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        .sa-donut-center .donut-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .sa-donut-center .donut-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .sa-legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            font-size: 0.82rem;
        }

        .sa-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 3px;
            flex-shrink: 0;
        }

        .sa-legend-value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            font-size: 0.78rem;
            margin-left: auto;
        }

        /* --- Premium Table --- */
        .sa-table {
            width: 100%;
            font-size: 0.85rem;
        }

        .sa-table thead th {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            padding: 0.75rem 1rem;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        .sa-table tbody td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .sa-table tbody tr:last-child td {
            border-bottom: none;
        }

        .sa-table tbody tr:hover {
            background: rgba(27, 67, 50, 0.03);
        }

        .sa-rank-badge {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 0.75rem;
        }

        .sa-rank-badge.rank-1 {
            background: linear-gradient(135deg, #D4A373, #E9C46A);
            color: #081C15;
        }
        .sa-rank-badge.rank-2 {
            background: rgba(156, 163, 175, 0.2);
            color: #6B7280;
        }
        .sa-rank-badge.rank-3 {
            background: rgba(180, 130, 90, 0.15);
            color: #B4825A;
        }
        .sa-rank-badge.rank-4, .sa-rank-badge.rank-5 {
            background: rgba(107, 114, 128, 0.1);
            color: #9CA3AF;
        }

        .sa-member-tier {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 12px;
            letter-spacing: 0.03em;
        }

        .tier-gold {
            background: linear-gradient(135deg, rgba(212, 163, 115, 0.15), rgba(233, 196, 106, 0.15));
            color: #D4A373;
            border: 1px solid rgba(212, 163, 115, 0.3);
        }

        .tier-silver {
            background: rgba(156, 163, 175, 0.1);
            color: #6B7280;
            border: 1px solid rgba(156, 163, 175, 0.3);
        }

        .tier-bronze {
            background: rgba(180, 130, 90, 0.1);
            color: #B4825A;
            border: 1px solid rgba(180, 130, 90, 0.3);
        }

        .tier-regular {
            background: rgba(107, 114, 128, 0.08);
            color: #9CA3AF;
            border: 1px solid rgba(107, 114, 128, 0.2);
        }

        /* --- Activity Timeline --- */
        .sa-timeline {
            position: relative;
            padding-left: 28px;
        }

        .sa-timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, #D4A373, var(--border));
            border-radius: 2px;
        }

        .sa-timeline-item {
            position: relative;
            padding: 0.65rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }

        .sa-timeline-item:last-child {
            border-bottom: none;
        }

        .sa-timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 1rem;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 2px solid #D4A373;
        }

        .sa-timeline-item:first-child::before {
            background: #D4A373;
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.2);
        }

        .sa-timeline-text {
            font-size: 0.82rem;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .sa-timeline-text strong {
            color: var(--primary);
        }

        .sa-timeline-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* --- System Health --- */
        .sa-health-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            border: 1px solid var(--border);
            text-align: center;
            transition: all 0.3s ease;
        }

        .sa-health-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
        }

        .sa-health-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
        }

        .sa-health-value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .sa-health-label {
            font-size: 0.72rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .sa-status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 2s infinite;
        }

        .sa-status-dot.online {
            background: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* --- Responsive --- */
        @media (max-width: 767.98px) {
            .sa-welcome-banner {
                padding: 1.5rem;
            }
            .sa-welcome-banner h1 {
                font-size: 1.35rem;
            }
            .sa-stat-card .stat-value {
                font-size: 1.3rem;
            }
            .sa-chart-container {
                height: 160px;
            }
        }
    
/* Stagger Animation */
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.stagger-item{opacity:0;animation:fadeInUp .5s ease forwards}
    </style>
</head>
<body>
<div class="superadmin-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_superadmin.php'; ?>
    <div class="superadmin-main">
        <?php $_header_role = 'superadmin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


        <!-- Content -->
        <div class="admin-content" style="padding:1.5rem;">

            <!-- Welcome Banner -->
            <div class="sa-welcome-banner mb-4 stagger-item">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h1>Selamat Datang, <span><?= $userName ?></span> 👋</h1>
                        <div class="sa-datetime" id="saDateTime"></div>
                    </div>
                    <div class="sa-badge">
                        <span class="sa-status-dot online"></span> Sistem Online
                    </div>
                </div>
            </div>

            <!-- Revenue Overview Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="sa-stat-card card-revenue stagger-item">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon icon-revenue"><i class="bi bi-cash-stack"></i></div>
                            <span class="stat-label">Total Revenue</span>
                        </div>
                        <div class="stat-value">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></div>
                        <div class="mt-2">
                            <span class="stat-trend up"><i class="bi bi-arrow-up-right"></i> All time</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="sa-stat-card card-transaksi stagger-item">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon icon-transaksi"><i class="bi bi-receipt"></i></div>
                            <span class="stat-label">Total Transaksi</span>
                        </div>
                        <div class="stat-value"><?= number_format($totalTransaksi) ?></div>
                        <div class="mt-2">
                            <span class="stat-trend up"><i class="bi bi-arrow-up-right"></i> All time</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="sa-stat-card card-pelanggan stagger-item">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon icon-pelanggan"><i class="bi bi-people"></i></div>
                            <span class="stat-label">Pelanggan Aktif</span>
                        </div>
                        <div class="stat-value"><?= number_format($totalPelanggan) ?></div>
                        <div class="mt-2">
                            <span class="stat-trend up"><i class="bi bi-arrow-up-right"></i> Terdaftar</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="sa-stat-card card-average stagger-item">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon icon-average"><i class="bi bi-calculator"></i></div>
                            <span class="stat-label">Rata-rata / Transaksi</span>
                        </div>
                        <div class="stat-value">Rp <?= number_format($avgPerTransaksi, 0, ',', '.') ?></div>
                        <div class="mt-2">
                            <span class="stat-trend up"><i class="bi bi-arrow-up-right"></i> Per transaksi</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row g-3 mb-4">
                <!-- Revenue Bar Chart -->
                <div class="col-lg-7">
                    <div class="sa-section-card h-100 stagger-item">
                        <div class="section-header">
                            <h5><i class="bi bi-bar-chart-line text-primary-theme"></i> Revenue Bulanan (<?= $currentYear ?>)</h5>
                            <span class="text-secondary" style="font-size:0.75rem;">Dalam jutaan Rupiah</span>
                        </div>
                        <div class="section-body">
                            <div class="sa-chart-container">
                                <?php foreach ($monthlyRevenue as $i => $mData):
                                    $barPct = ($mData['value'] / $maxRevenue) * 100;
                                    $barPct = max($barPct, 2); // minimum bar height
                                    $isCurrentMonth = ($i === count($monthlyRevenue) - 1);
                                    $barClass = $isCurrentMonth ? 'bar-current' : 'bar-default';
                                    $labelClass = $isCurrentMonth ? 'current' : '';
                                    $displayValue = $mData['value'] >= 1000000
                                        ? 'Rp ' . number_format($mData['value'] / 1000000, 1, '.', '') . 'M'
                                        : 'Rp ' . number_format($mData['value'] / 1000, 0) . 'K';
                                ?>
                                <div class="sa-chart-bar-group">
                                    <div class="sa-chart-bar <?= $barClass ?>" style="height: <?= round($barPct) ?>%;">
                                        <span class="bar-tooltip"><?= $displayValue ?></span>
                                    </div>
                                    <span class="sa-chart-label <?= $labelClass ?>"><?= htmlspecialchars($mData['month']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Donut Chart -->
                <div class="col-lg-5">
                    <div class="sa-section-card h-100 stagger-item">
                        <div class="section-header">
                            <h5><i class="bi bi-pie-chart text-accent-theme"></i> Distribusi Kategori</h5>
                        </div>
                        <div class="section-body">
                            <div class="sa-donut-chart mb-4" style="background: conic-gradient(<?= $conicGradient ?>);">
                                <div class="sa-donut-center">
                                    <span class="donut-value"><?= number_format($totalBarangAll) ?></span>
                                    <span class="donut-label">Barang</span>
                                </div>
                            </div>
                            <div class="px-2">
                                <?php foreach ($categorySlices as $slice): ?>
                                <div class="sa-legend-item">
                                    <span class="sa-legend-dot" style="background:<?= $slice['color'] ?>;"></span>
                                    <span><?= $slice['nama'] ?></span>
                                    <span class="sa-legend-value"><?= $slice['pct'] ?>%</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Items & Top Pelanggan -->
            <div class="row g-3 mb-4">
                <!-- Top 5 Barang -->
                <div class="col-lg-6">
                    <div class="sa-section-card h-100 stagger-item">
                        <div class="section-header">
                            <h5><i class="bi bi-trophy text-warning"></i> Top 5 Barang Disewa</h5>
                            <span class="text-secondary" style="font-size:0.72rem;">All time</span>
                        </div>
                        <div class="section-body p-0">
                            <div class="table-responsive">
                                <table class="sa-table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Nama Barang</th>
                                            <th>Kali Disewa</th>
                                            <th>Harga/Hari</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($topBarang)): ?>
                                        <tr><td colspan="4" class="text-center text-secondary">Belum ada data</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($topBarang as $rank => $item):
                                            $r = $rank + 1;
                                            $rankClass = $r <= 5 ? "rank-{$r}" : "rank-5";
                                        ?>
                                        <tr>
                                            <td><span class="sa-rank-badge <?= $rankClass ?>"><?= $r ?></span></td>
                                            <td class="fw-medium"><?= htmlspecialchars($item['nama']) ?></td>
                                            <td class="mono-font"><?= (int)$item['total_sewa'] ?></td>
                                            <td class="mono-font fw-bold">Rp <?= number_format($item['harga_per_hari'], 0, ',', '.') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Pelanggan -->
                <div class="col-lg-6">
                    <div class="sa-section-card h-100 stagger-item">
                        <div class="section-header">
                            <h5><i class="bi bi-star text-warning"></i> Top 5 Pelanggan</h5>
                            <span class="text-secondary" style="font-size:0.72rem;">All time</span>
                        </div>
                        <div class="section-body p-0">
                            <div class="table-responsive">
                                <table class="sa-table">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Nama</th>
                                            <th>Transaksi</th>
                                            <th>Total Spent</th>
                                            <th>Tier</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($topPelanggan)): ?>
                                        <tr><td colspan="5" class="text-center text-secondary">Belum ada data</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($topPelanggan as $rank => $cust):
                                            $r = $rank + 1;
                                            $rankClass = $r <= 5 ? "rank-{$r}" : "rank-5";
                                            $tier = getTierClass((float)$cust['total_spent']);
                                        ?>
                                        <tr>
                                            <td><span class="sa-rank-badge <?= $rankClass ?>"><?= $r ?></span></td>
                                            <td class="fw-medium"><?= htmlspecialchars($cust['nama']) ?></td>
                                            <td class="mono-font"><?= (int)$cust['total_transaksi'] ?></td>
                                            <td class="mono-font fw-bold">Rp <?= number_format($cust['total_spent'], 0, ',', '.') ?></td>
                                            <td><span class="sa-member-tier <?= $tier[0] ?>"><?= $tier[1] ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Feed & System Health -->
            <div class="row g-3 mb-4">
                <!-- Recent Activity Feed -->
                <div class="col-lg-7">
                    <div class="sa-section-card h-100 stagger-item">
                        <div class="section-header">
                            <h5><i class="bi bi-activity" style="color:#D4A373;"></i> Aktivitas Terbaru</h5>
                            <a href="<?= BASE_URL ?>/pages/superadmin/log_aktivitas.php" class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;">Lihat Semua</a>
                        </div>
                        <div class="section-body">
                            <div class="sa-timeline">
                                <?php if (empty($recentActivities)): ?>
                                <div class="sa-timeline-item">
                                    <div class="sa-timeline-text text-secondary">Belum ada aktivitas tercatat.</div>
                                </div>
                                <?php else: ?>
                                <?php foreach ($recentActivities as $act): ?>
                                <div class="sa-timeline-item">
                                    <div class="sa-timeline-text">
                                        <strong><?= htmlspecialchars($act['nama_user'] ?? 'System') ?></strong>
                                        — <?= htmlspecialchars($act['aksi'] ?? '') ?>
                                        <?php if (!empty($act['detail'])): ?>
                                            : <?= htmlspecialchars($act['detail']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sa-timeline-time"><?= timeAgoId($act['created_at']) ?></div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="col-lg-5">
                    <div class="sa-section-card h-100 stagger-item">
                        <div class="section-header">
                            <h5><i class="bi bi-heart-pulse" style="color:#10B981;"></i> System Health</h5>
                            <span class="sa-status-dot online"></span>
                        </div>
                        <div class="section-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="sa-health-card">
                                        <div class="sa-health-icon" style="background:rgba(16,185,129,0.1);color:#10B981;">
                                            <i class="bi bi-hdd-rack"></i>
                                        </div>
                                        <div class="sa-health-value"><span class="sa-status-dot online"></span>Online</div>
                                        <div class="sa-health-label">Server Status</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="sa-health-card">
                                        <div class="sa-health-icon" style="background:rgba(59,130,246,0.1);color:#3B82F6;">
                                            <i class="bi bi-database"></i>
                                        </div>
                                        <div class="sa-health-value"><?= htmlspecialchars($dbSize) ?></div>
                                        <div class="sa-health-label">Database Size</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="sa-health-card">
                                        <div class="sa-health-icon" style="background:rgba(212,163,115,0.1);color:#D4A373;">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <div class="sa-health-value"><?= number_format($totalBarang) ?></div>
                                        <div class="sa-health-label">Total Barang</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="sa-health-card">
                                        <div class="sa-health-icon" style="background:rgba(139,92,246,0.1);color:#8B5CF6;">
                                            <i class="bi bi-person-check"></i>
                                        </div>
                                        <div class="sa-health-value"><?= $totalAdmins + $totalSuperAdmins ?></div>
                                        <div class="sa-health-label">Admin Aktif</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
<script>
    // Live date/time display
    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const date = now.toLocaleDateString('id-ID', options);
        const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const el = document.getElementById('saDateTime');
        if (el) el.textContent = date + ' • ' + time + ' WIB';
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.stagger-item').forEach(function(item, i){
        item.style.animationDelay = (i * 0.08) + 's';
    });
});
</script>
</body>
</html>
