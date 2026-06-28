<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/Pengeluaran.php';

requireRole(['admin', 'superadmin']);

$page_title = 'Laporan';
$current_page = 'laporan';

// Handle Pengeluaran CRUD
$pMessage = '';
$pMsgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_expense') {
        $r = Pengeluaran::create([
            'kategori_pengeluaran' => $_POST['kategori_pengeluaran'],
            'deskripsi' => $_POST['deskripsi'],
            'jumlah' => (float)str_replace(['.', ','], ['', '.'], $_POST['jumlah']),
            'tanggal' => $_POST['tanggal'],
        ]);
        $pMessage = $r ? 'Pengeluaran berhasil ditambahkan!' : 'Gagal menambahkan.';
        $pMsgType = $r ? 'success' : 'danger';
    } elseif ($action === 'update_expense') {
        $r = Pengeluaran::update((int)$_POST['id'], [
            'kategori_pengeluaran' => $_POST['kategori_pengeluaran'],
            'deskripsi' => $_POST['deskripsi'],
            'jumlah' => (float)str_replace(['.', ','], ['', '.'], $_POST['jumlah']),
            'tanggal' => $_POST['tanggal'],
        ]);
        $pMessage = $r ? 'Pengeluaran berhasil diperbarui!' : 'Gagal memperbarui.';
        $pMsgType = $r ? 'success' : 'danger';
    } elseif ($action === 'delete_expense') {
        $r = Pengeluaran::delete((int)$_POST['id']);
        $pMessage = $r ? 'Pengeluaran berhasil dihapus!' : 'Gagal menghapus.';
        $pMsgType = $r ? 'success' : 'danger';
    }
}

// Stats from database
$totalRevenue = Transaksi::totalPendapatan();
$totalBarangSewa = Reservasi::count(['status' => 'selesai']);
$totalPelangganAktif = User::countByRole('pelanggan');

// Fetch recent transactions for table
$laporanTrx = Transaksi::getAll(['limit' => 10]);
$laporanTotal = 0;
foreach ($laporanTrx as $lt) { $laporanTotal += ($lt['total_bayar'] ?? 0); }

// Monthly revenue data for charts
$currentYear = date('Y');
$currentMonth = (int)date('m');
$monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$monthlyRevenue = [];
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

// Expense data for Keuangan tab
$expenseByMonth = Pengeluaran::monthlyByYear($currentYear);
$monthlyExpense = [];
for ($m = 1; $m <= $currentMonth; $m++) {
    $monthlyExpense[$m] = $expenseByMonth[$m] ?? 0;
}
$totalPemasukan = array_sum(array_column($monthlyRevenue, 'value'));
$totalPengeluaran = array_sum($monthlyExpense);
$labaBersih = $totalPemasukan - $totalPengeluaran;
$expenseSummary = Pengeluaran::summaryByKategori($currentYear . '-01-01', $currentYear . '-12-31');
$allExpenses = Pengeluaran::getAll(['start_date' => $currentYear . '-01-01', 'end_date' => $currentYear . '-12-31']);
$expKatOptions = ['Pembelian Stok', 'Perawatan', 'Operasional', 'Lainnya'];
// Max for hbar chart
$maxBarVal = max(max(array_column($monthlyRevenue, 'value') ?: [1]), max($monthlyExpense ?: [1]));
if ($maxBarVal == 0) $maxBarVal = 1;

// Format helper (short format with M/K suffix for charts)
function formatRupiahShort($n) {
    if ($n >= 1000000) return 'Rp ' . number_format($n / 1000000, 1, ',', '.') . 'M';
    if ($n >= 1000) return 'Rp ' . number_format($n / 1000, 0, ',', '.') . 'K';
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function formatShort($n) {
    if ($n >= 1000000) return number_format($n / 1000000, 1, ',', '.') . 'M';
    if ($n >= 1000) return number_format($n / 1000, 0, ',', '.') . 'K';
    return number_format($n, 0, ',', '.');
}
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?> - <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=<?= time() ?>">
<style>
:root{--lp-dark:#1B4332;--lp-mid:#2D6A4F;--lp-light:#52B788;--lp-gold:#D4A373;--lp-bg:#f0f4f1}
body{font-family:'Inter',sans-serif;background:var(--lp-bg)}
h1,h2,h3,h4,h5,h6,.heading{font-family:'Outfit',sans-serif}
.mono{font-family:'JetBrains Mono',monospace}

/* Tabs */
.lp-tabs{display:flex;gap:6px;background:#fff;border-radius:14px;padding:6px;box-shadow:0 2px 12px rgba(27,67,50,0.06);flex-wrap:wrap}
.lp-tab{padding:10px 28px;border-radius:10px;border:none;background:transparent;font-weight:600;color:#6c757d;cursor:pointer;transition:all .3s;font-family:'Inter',sans-serif;font-size:.93rem}
.lp-tab.active{background:linear-gradient(135deg,var(--lp-mid),var(--lp-light));color:#fff;box-shadow:0 4px 16px rgba(45,106,79,0.3)}
.lp-tab:hover:not(.active){background:rgba(82,183,136,0.08);color:var(--lp-mid)}

/* Stat Cards */
.stat-card{background:#fff;border-radius:16px;padding:22px;box-shadow:0 2px 14px rgba(0,0,0,0.05);transition:all .35s;border-left:4px solid var(--lp-light);height:100%;display:block !important;}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 10px 28px rgba(0,0,0,0.08)}
.stat-card .stat-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.stat-card .stat-icon.green{background:rgba(82,183,136,0.12);color:var(--lp-light)}
.stat-card .stat-icon.blue{background:rgba(59,130,246,0.1);color:#3b82f6}
.stat-card .stat-icon.purple{background:rgba(139,92,246,0.1);color:#8b5cf6}
.stat-card .stat-icon.red{background:rgba(239,68,68,0.1);color:#ef4444}
.stat-value{font-family:'Outfit',sans-serif;font-size:1.6rem;font-weight:800;color:var(--lp-dark)}
.stat-value .mono-num{font-family:'JetBrains Mono',monospace}
.stat-label{font-size:.82rem;color:#6c757d;font-weight:500}

/* Buttons */
.btn-filter{background:linear-gradient(135deg,var(--lp-mid),var(--lp-light));color:#fff;border:none;border-radius:10px;padding:9px 20px;font-weight:600;font-size:.88rem;transition:all .3s}
.btn-filter:hover{box-shadow:0 4px 14px rgba(45,106,79,0.3);color:#fff}
.btn-export{background:linear-gradient(135deg,var(--lp-gold),#c4956a);color:#fff;border:none;border-radius:10px;padding:9px 20px;font-weight:600;font-size:.88rem;transition:all .3s}
.btn-export:hover{box-shadow:0 4px 14px rgba(212,163,115,0.4);color:#fff}
.btn-print{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:10px;padding:9px 20px;font-weight:600;font-size:.88rem;transition:all .3s}
.btn-print:hover{box-shadow:0 4px 14px rgba(59,130,246,0.4);color:#fff}
.btn-gradient{background:linear-gradient(135deg,var(--lp-mid),var(--lp-light));color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:600}

/* Chart */
.chart-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 14px rgba(0,0,0,0.05);margin-bottom:24px;overflow:hidden;height:100%}
.chart-container{display:flex;align-items:flex-end;justify-content:space-between;height:250px;padding:20px 0 0;position:relative;border-left:2px solid #e8e8e8;border-bottom:2px solid #e8e8e8;margin-left:48px}
.chart-bar-wrapper{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;position:relative;z-index:1}
.chart-bar{width:60%;max-width:50px;border-radius:8px 8px 0 0;background:linear-gradient(180deg,var(--lp-light),var(--lp-mid));position:relative;cursor:pointer;min-width:24px}
.chart-bar.gold{background:linear-gradient(180deg,var(--lp-gold),#c4956a)}
.chart-bar:hover{opacity:.85}
.chart-bar .bar-tooltip{position:absolute;top:-36px;left:50%;transform:translateX(-50%);background:var(--lp-dark);color:#fff;padding:4px 10px;border-radius:6px;font-size:.75rem;font-family:'JetBrains Mono',monospace;white-space:nowrap;opacity:0;transition:opacity .3s;pointer-events:none}
.chart-bar:hover .bar-tooltip{opacity:1}
.chart-month{font-size:.78rem;font-weight:600;color:#6c757d;margin-top:8px}
.chart-month.active{color:var(--lp-gold);font-weight:700}
.y-axis{position:absolute;left:0;top:0;bottom:0;display:flex;flex-direction:column;justify-content:space-between;align-items:flex-end;width:44px;padding-right:8px}
.y-axis span{font-size:.7rem;color:#adb5bd;font-family:'JetBrains Mono',monospace}

/* Horizontal Bar Chart */
.hbar-chart{margin-bottom:24px}
.hbar-row{display:flex;align-items:center;margin-bottom:14px;gap:10px}
.hbar-label{width:70px;font-size:.82rem;font-weight:600;color:#6c757d;text-align:right;flex-shrink:0}
.hbar-bars{flex:1;display:flex;flex-direction:column;gap:4px}
.hbar{height:22px;border-radius:4px;position:relative;display:flex;align-items:center;padding-left:8px}
.hbar.income{background:linear-gradient(90deg,var(--lp-mid),var(--lp-light))}
.hbar.expense{background:linear-gradient(90deg,#dc2626,#ef4444)}
.hbar-val{font-size:.7rem;font-family:'JetBrains Mono',monospace;color:#fff;font-weight:600;white-space:nowrap}
.hbar-legend{display:flex;gap:20px;margin-bottom:20px}
.hbar-legend-item{display:flex;align-items:center;gap:6px;font-size:.82rem;font-weight:600;color:#555}
.hbar-legend-dot{width:14px;height:14px;border-radius:4px}
.hbar-legend-dot.green{background:var(--lp-light)}
.hbar-legend-dot.red{background:#ef4444}

/* Tables */
.report-table{background:#fff;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,0.05);overflow:hidden}
.report-table .table{margin-bottom:0}
.report-table .table th{background:rgba(82,183,136,0.06);font-size:.82rem;font-weight:600;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;padding:14px 16px;border-bottom:2px solid #e8e8e8}
.report-table .table td{padding:14px 16px;vertical-align:middle;border-color:#f0f0f0}
.report-table .table tbody tr:hover{background:rgba(82,183,136,0.03)}
.report-table .table tfoot td{background:rgba(82,183,136,0.08);font-weight:700;border-top:2px solid #d0d0d0}
.rank-circle{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--lp-mid),var(--lp-light));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.82rem}
.badge-cat{padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:600}
.badge-cat.tenda{background:rgba(59,130,246,0.1);color:#3b82f6}
.badge-cat.tas{background:rgba(139,92,246,0.1);color:#8b5cf6}
.badge-cat.tidur{background:rgba(82,183,136,0.1);color:var(--lp-light)}
.badge-cat.masak{background:rgba(245,158,11,0.1);color:#f59e0b}
.badge-cat.penerangan{background:rgba(239,68,68,0.1);color:#ef4444}
.text-income{color:var(--lp-light)}
.text-expense{color:#ef4444}
.text-profit{color:#3b82f6}

/* Filter Bar */
.filter-bar{background:#fff;border-radius:14px;padding:16px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.04);display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:24px}
.filter-bar .form-control,.filter-bar .form-select{border-radius:10px;padding:9px 14px;border:1.5px solid #e0e0e0;font-size:.88rem}
.filter-bar .form-control:focus,.filter-bar .form-select:focus{border-color:var(--lp-light);box-shadow:0 0 0 3px rgba(82,183,136,0.15)}

/* Modal */
.modal-content{border-radius:16px;border:none;overflow:hidden}
.modal-header.green-header{background:linear-gradient(135deg,var(--lp-mid),var(--lp-light));color:#fff;border:none;padding:20px 24px}
.modal-header.green-header .btn-close{filter:brightness(0) invert(1)}
.modal-body{padding:24px}
.modal-footer{border-top:1px solid #f0f0f0;padding:16px 24px}
.export-card{border:2px solid #e8e8e8;border-radius:14px;padding:20px;text-align:center;cursor:pointer;transition:all .3s}
.export-card:hover{border-color:var(--lp-light);box-shadow:0 4px 14px rgba(82,183,136,0.15)}
.export-card.selected{border-color:var(--lp-light);background:rgba(82,183,136,0.06)}
.export-card .export-icon{font-size:2.2rem;margin-bottom:8px}
.export-card .export-label{font-weight:600;font-size:.9rem;color:#333}
.check-mark{position:absolute;top:8px;right:8px;display:none;color:var(--lp-light)}
.export-card.selected .check-mark{display:block}

/* Toast */
.custom-toast{background:#fff;border-radius:12px;padding:14px 20px;box-shadow:0 8px 30px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;border-left:4px solid var(--lp-light);animation:slideInToast .4s forwards}
.toast-icon{color:var(--lp-light);font-size:1.2rem}
.toast-msg{font-weight:500;font-size:.9rem}
.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px}
@keyframes slideInToast{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}
@keyframes slideOutToast{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(100%)}}

/* Animations */
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.stagger-item{opacity:0;animation:fadeInUp .5s ease forwards}

/* Status badges */
.badge-status{padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:600}
.badge-status.selesai{background:rgba(82,183,136,0.12);color:#2D6A4F}
.badge-status.aktif{background:rgba(59,130,246,0.1);color:#3b82f6}
.badge-status.batal{background:rgba(239,68,68,0.1);color:#ef4444}

/* Print Styles */
@media print {
    body{background:#fff !important}
    .admin-sidebar,.pelanggan-topbar,.lp-tabs,.filter-bar,.btn-print,.btn-export,.btn-filter,.topbar-actions,.toast-container,.modal{display:none !important}
    .admin-main{margin-left:0 !important}
    .admin-content{padding:0 !important}
    .stat-card,.chart-card,.report-table{box-shadow:none !important;border:1px solid #ddd !important;break-inside:avoid}
    .stat-card:hover{transform:none !important}
    .stagger-item{opacity:1 !important;animation:none !important}
    .chart-bar{height:var(--print-h) !important}
    .hbar{width:var(--print-w) !important}
    .print-header{display:block !important}
    @page{margin:1.5cm;size:A4}
}
.print-header{display:none;text-align:center;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid var(--lp-mid)}
.print-header h2{font-family:'Outfit',sans-serif;color:var(--lp-dark);margin:0}
.print-header p{color:#6c757d;margin:4px 0 0}
</style>
</head><body>
<div class="admin-wrapper">
<?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


    <div class="admin-content">
        <div class="container-fluid stagger-in">

            <!-- Print Header (only shows when printing) -->
            <div class="print-header">
                <h2>📊 Laporan SIMPEL-CAMP</h2>
                <p>Periode: Januari - Juni 2026 | Dicetak: <span id="printDate"></span></p>
            </div>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-4 stagger-item">
                    <div class="stat-card" style="border-left-color:#3b82f6">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stat-icon blue"><i class="bi bi-cash-coin"></i></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-value"><span class="mono-num">Rp </span><span class="counter mono-num" data-target="<?= (int)$totalRevenue ?>" data-format="currency">0</span></div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 stagger-item">
                    <div class="stat-card" style="border-left-color:#8b5cf6">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stat-icon purple"><i class="bi bi-box-seam"></i></div>
                            <div class="stat-label">Barang Tersewa</div>
                        </div>
                        <div class="stat-value"><span class="counter" data-target="<?= $totalBarangSewa ?>">0</span> <span style="font-size:.9rem;font-weight:500;color:#999">unit</span></div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 stagger-item">
                    <div class="stat-card" style="border-left-color:#10b981">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stat-icon green"><i class="bi bi-people"></i></div>
                            <div class="stat-label">Pelanggan Aktif</div>
                        </div>
                        <div class="stat-value"><span class="counter" data-target="<?= $totalPelangganAktif ?>">0</span> <span style="font-size:.9rem;font-weight:500;color:#999">orang</span></div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="lp-tabs mb-4">
                <button class="lp-tab active" id="tabBtnPenjualan" onclick="switchLpTab('penjualan')"><i class="bi bi-graph-up me-2"></i>Penjualan</button>
                <button class="lp-tab" id="tabBtnKeuangan" onclick="switchLpTab('keuangan')"><i class="bi bi-wallet2 me-2"></i>Keuangan</button>
            </div>

            <!-- ══════════════════════════════════════════════ -->
            <!-- TAB 1: PENJUALAN                              -->
            <!-- ══════════════════════════════════════════════ -->
            <div id="tab-penjualan" class="tab-pane-lp">

                <!-- Filter + Actions -->
                <div class="filter-bar">
                    <i class="bi bi-funnel text-muted"></i>
                    <input type="date" class="form-control" id="dateFrom" value="2026-01-01" style="max-width:160px">
                    <span class="text-muted">—</span>
                    <input type="date" class="form-control" id="dateTo" value="2026-06-15" style="max-width:160px">
                    <select class="form-select" style="max-width:150px" id="filterKategori">
                        <option value="">Semua Kategori</option>
                        <option>Tenda</option><option>Tas</option><option>Tidur</option><option>Masak</option><option>Penerangan</option>
                    </select>
                    <button class="btn btn-filter" onclick="applyFilter()"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-print btn-sm" onclick="printReport('penjualan')"><i class="bi bi-printer me-1"></i>Cetak</button>
                        <button class="btn btn-export btn-sm" onclick="openExportModal('penjualan')"><i class="bi bi-download me-1"></i>Export</button>
                    </div>
                </div>

                <!-- Chart + Top 5 Side by Side -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-7">
                        <div class="chart-card stagger-item">
                            <h5 class="heading fw-bold mb-3">Pendapatan Bulanan <?= $currentYear ?></h5>
                            <?php
                            $lpChartH = 220;
                            $ySteps = 5;
                            $yLabels = [];
                            for ($i = $ySteps; $i >= 0; $i--) {
                                $yLabels[] = formatShort(($maxRevenue / $ySteps) * $i);
                            }
                            ?>
                            <div style="display:flex;gap:0">
                                <div style="display:flex;flex-direction:column;justify-content:space-between;height:<?= $lpChartH ?>px;padding-right:8px;min-width:40px">
                                    <?php foreach ($yLabels as $yl): ?>
                                    <span style="font-size:.7rem;color:#9ca3af;text-align:right;font-family:'JetBrains Mono',monospace"><?= $yl ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div style="flex:1;display:flex;align-items:flex-end;justify-content:space-around;height:<?= $lpChartH ?>px;border-left:2px solid #e8e8e8;border-bottom:2px solid #e8e8e8;padding:0 8px">
                                    <?php foreach ($monthlyRevenue as $i => $mr):
                                        $barH = $maxRevenue > 0 ? round(($mr['value'] / $maxRevenue) * ($lpChartH - 20)) : 0;
                                        if ($barH < 4 && $mr['value'] > 0) $barH = 4;
                                        $isLast = ($i === count($monthlyRevenue) - 1);
                                        $bg = $isLast ? 'linear-gradient(180deg,#D4A373,#c4956a)' : 'linear-gradient(180deg,#52B788,#2D6A4F)';
                                        $lblColor = $isLast ? '#D4A373' : '#9ca3af';
                                        $lblWeight = $isLast ? '700' : '600';
                                    ?>
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex:1">
                                        <div style="width:60%;max-width:50px;min-width:24px;height:<?= $barH ?>px;border-radius:8px 8px 0 0;background:<?= $bg ?>;cursor:pointer" title="<?= formatRupiahShort($mr['value']) ?>"></div>
                                        <span style="font-size:.75rem;font-weight:<?= $lblWeight ?>;color:<?= $lblColor ?>"><?= $mr['month'] ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="report-table stagger-item" style="height:100%">
                            <div class="p-3 pb-0"><h5 class="heading fw-bold">Top 5 Barang Disewa</h5></div>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>#</th><th>Nama Barang</th><th>Kategori</th><th>Total</th><th>Pendapatan</th></tr></thead>
                                    <tbody>
                                        <?php 
                                        $topBarang = Barang::getPopuler(5);
                                        $hasData = false;
                                        foreach ($topBarang as $tb) {
                                            if ((int)$tb['total_sewa'] > 0) { $hasData = true; break; }
                                        }
                                        if (!$hasData): ?>
                                        <tr><td colspan="5" class="text-center text-secondary py-3">Belum ada barang yang disewa</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($topBarang as $rank => $item): 
                                            if ((int)$item['total_sewa'] == 0) continue;
                                            $r = $rank + 1;
                                            $katClass = strtolower(str_replace(' ', '', $item['kategori_nama']));
                                        ?>
                                        <tr>
                                            <td><div class="rank-circle"><?= $r ?></div></td>
                                            <td class="fw-semibold"><?= htmlspecialchars($item['nama']) ?></td>
                                            <td><span class="badge-cat <?= $katClass ?>"><?= htmlspecialchars($item['kategori_nama']) ?></span></td>
                                            <td><?= (int)$item['total_sewa'] ?>x</td>
                                            <td class="mono fw-bold text-income">Rp <?= number_format($item['harga_per_hari'], 0, ',', '.') ?> /hari</td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Transaction Table -->
                <div class="report-table stagger-item">
                    <div class="p-3 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="heading fw-bold mb-0">Riwayat Transaksi Penyewaan</h5>
                        <span class="badge rounded-pill" style="background:rgba(82,183,136,0.12);color:#2D6A4F;font-weight:600;padding:6px 14px"><?= count($laporanTrx) ?> Transaksi</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr><th>No</th><th>ID Transaksi</th><th>Pelanggan</th><th>Tgl</th><th>Total</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($laporanTrx)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
                                <?php else: ?>
                                <?php foreach ($laporanTrx as $i => $lt):
                                    $ltStatusMap = ['pending'=>'Menunggu','dibayar'=>'Dibayar','aktif'=>'Aktif','selesai'=>'Selesai','batal'=>'Batal'];
                                    $ltStatusClass = ['pending'=>'pending','dibayar'=>'aktif','aktif'=>'aktif','selesai'=>'selesai','batal'=>'batal'];
                                ?>
                                <tr><td><?= $i+1 ?></td><td class="mono fw-semibold"><?= htmlspecialchars($lt['kode_transaksi'] ?? 'TRX-'.$lt['id']) ?></td><td><?= htmlspecialchars($lt['user_nama'] ?? '-') ?></td><td><?= date('d M Y', strtotime($lt['created_at'])) ?></td><td class="mono fw-bold text-income">Rp <?= number_format($lt['total_bayar'] ?? 0, 0, ',', '.') ?></td><td><span class="badge-status <?= $ltStatusClass[$lt['status']] ?? 'pending' ?>"><?= $ltStatusMap[$lt['status']] ?? ucfirst($lt['status']) ?></span></td></tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr><td colspan="4" class="fw-bold">TOTAL PENDAPATAN</td><td class="mono fw-bold text-income">Rp <?= number_format($laporanTotal, 0, ',', '.') ?></td><td></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════ -->
            <!-- TAB 2: KEUANGAN                               -->
            <!-- ══════════════════════════════════════════════ -->
            <div id="tab-keuangan" class="tab-pane-lp" style="display:none">

                <!-- Filter + Actions -->
                <div class="filter-bar">
                    <i class="bi bi-funnel text-muted"></i>
                    <input type="date" class="form-control" value="2026-01-01" style="max-width:160px">
                    <span class="text-muted">—</span>
                    <input type="date" class="form-control" value="2026-06-15" style="max-width:160px">
                    <button class="btn btn-filter" onclick="applyFilter()"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-print btn-sm" onclick="printReport('keuangan')"><i class="bi bi-printer me-1"></i>Cetak</button>
                        <button class="btn btn-export btn-sm" onclick="openExportModal('keuangan')"><i class="bi bi-download me-1"></i>Export</button>
                    </div>
                </div>

                <!-- Keuangan Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-4 stagger-item">
                        <div class="stat-card" style="border-left-color:#10b981">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="stat-icon green"><i class="bi bi-arrow-down-circle"></i></div>
                                <div class="stat-label">Total Pemasukan</div>
                            </div>
                            <div class="stat-value text-income"><span class="mono-num">Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></span></div>
                        </div>
                    </div>
                    <div class="col-lg-4 stagger-item">
                        <div class="stat-card" style="border-left-color:#ef4444">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="stat-icon red"><i class="bi bi-arrow-up-circle"></i></div>
                                <div class="stat-label">Total Pengeluaran</div>
                            </div>
                            <div class="stat-value text-expense"><span class="mono-num">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></span></div>
                        </div>
                    </div>
                    <div class="col-lg-4 stagger-item">
                        <div class="stat-card" style="border-left-color:#3b82f6">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="stat-icon blue"><i class="bi bi-graph-up-arrow"></i></div>
                                <div class="stat-label">Laba Bersih</div>
                            </div>
                            <div class="stat-value text-profit"><span class="mono-num">Rp <?= number_format($labaBersih, 0, ',', '.') ?></span></div>
                        </div>
                    </div>
                </div>

                <!-- Horizontal Bar Chart -->
                <div class="chart-card stagger-item mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="heading fw-bold mb-0">Perbandingan Pemasukan vs Pengeluaran</h5>
                    </div>
                    <div class="hbar-legend">
                        <div class="hbar-legend-item"><div class="hbar-legend-dot green"></div>Pemasukan</div>
                        <div class="hbar-legend-item"><div class="hbar-legend-dot red"></div>Pengeluaran</div>
                    </div>
                    <div class="hbar-chart" id="hbarChart">
                        <?php foreach ($monthlyRevenue as $i => $mr):
                            $mIdx = $i + 1;
                            $incVal = $mr['value'];
                            $expVal = $monthlyExpense[$mIdx] ?? 0;
                            $incW = $maxBarVal > 0 ? round(($incVal / $maxBarVal) * 100) : 0;
                            $expW = $maxBarVal > 0 ? round(($expVal / $maxBarVal) * 100) : 0;
                            if ($incW < 3 && $incVal > 0) $incW = 3;
                            if ($expW < 3 && $expVal > 0) $expW = 3;
                        ?>
                        <div class="hbar-row"><div class="hbar-label"><?= $mr['month'] ?></div><div class="hbar-bars"><div class="hbar income" style="width:<?= $incW ?>%"><span class="hbar-val"><?= formatShort($incVal) ?></span></div><div class="hbar expense" style="width:<?= $expW ?>%"><span class="hbar-val"><?= formatShort($expVal) ?></span></div></div></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Monthly Financial Breakdown Table -->
                <div class="report-table stagger-item">
                    <div class="p-3 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="heading fw-bold mb-0">Rincian Keuangan Bulanan</h5>
                        <span class="badge rounded-pill" style="background:rgba(59,130,246,0.1);color:#3b82f6;font-weight:600;padding:6px 14px"><?= $currentYear ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr><th>Bulan</th><th>Pemasukan</th><th>Tren</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $fullMonthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                $grandTotal = 0;
                                $prevValue = 0;
                                foreach ($monthlyRevenue as $i => $mr):
                                    $grandTotal += $mr['value'];
                                    $tren = '';
                                    if ($i > 0 && $prevValue > 0) {
                                        $change = (($mr['value'] - $prevValue) / $prevValue) * 100;
                                        if ($change >= 0) {
                                            $tren = '<i class="bi bi-arrow-up text-success"></i> +' . round($change, 1) . '%';
                                        } else {
                                            $tren = '<i class="bi bi-arrow-down text-danger"></i> ' . round($change, 1) . '%';
                                        }
                                    } elseif ($i === 0) {
                                        $tren = '<i class="bi bi-arrow-up text-success"></i>';
                                    }
                                    $prevValue = $mr['value'];
                                    $mIdx = array_search($mr['month'], $monthNames);
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= $fullMonthNames[$mIdx] ?? $mr['month'] ?></td>
                                    <td class="mono text-income"><?= formatRupiahShort($mr['value']) ?></td>
                                    <td><?= $tren ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="fw-bold">TOTAL</td>
                                    <td class="mono fw-bold text-income"><?= formatRupiahShort($grandTotal) ?></td>
                                    <td><i class="bi bi-graph-up-arrow text-success fw-bold"></i></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Expense Breakdown -->
                <div class="report-table stagger-item mt-4">
                    <div class="p-3 pb-0"><h5 class="heading fw-bold">Detail Pengeluaran per Kategori</h5></div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>No</th><th>Kategori Pengeluaran</th><th>Jumlah Item</th><th>Total</th><th>% dari Total</th></tr></thead>
                            <tbody>
                                <?php if (empty($expenseSummary)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data pengeluaran</td></tr>
                                <?php else: ?>
                                <?php foreach ($expenseSummary as $no => $es):
                                    $persen = $totalPengeluaran > 0 ? round(($es['total'] / $totalPengeluaran) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?= $no + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($es['kategori_pengeluaran']) ?></td>
                                    <td><?= (int)$es['jumlah_item'] ?> transaksi</td>
                                    <td class="mono fw-bold text-expense">Rp <?= number_format($es['total'], 0, ',', '.') ?></td>
                                    <td><?= $persen ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr><td colspan="3" class="fw-bold">TOTAL PENGELUARAN</td><td class="mono fw-bold text-expense">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></td><td class="fw-bold">100%</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- CRUD Daftar Pengeluaran -->
                <?php if ($pMessage): ?>
                <div class="alert alert-<?= $pMsgType ?> alert-dismissible fade show mt-3" style="border-radius:12px">
                    <i class="bi bi-<?= $pMsgType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i><?= htmlspecialchars($pMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="report-table stagger-item mt-4">
                    <div class="p-3 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="heading fw-bold mb-0">Daftar Pengeluaran <?= $currentYear ?></h5>
                        <button class="btn btn-sm text-white" style="background:linear-gradient(135deg,#2D6A4F,#52B788);border-radius:10px;font-weight:600" onclick="openExpModal()">
                            <i class="bi bi-plus-lg me-1"></i>Tambah
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" style="font-size:.88rem">
                            <thead><tr><th>No</th><th>Tanggal</th><th>Kategori</th><th>Deskripsi</th><th class="text-end">Jumlah</th><th class="text-center" style="width:100px">Aksi</th></tr></thead>
                            <tbody>
                                <?php if (empty($allExpenses)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data pengeluaran</td></tr>
                                <?php else: ?>
                                <?php foreach ($allExpenses as $ei => $ex): ?>
                                <tr>
                                    <td><?= $ei + 1 ?></td>
                                    <td style="font-family:'JetBrains Mono',monospace;font-size:.8rem"><?= date('d M Y', strtotime($ex['tanggal'])) ?></td>
                                    <td><span class="badge rounded-pill" style="<?php
                                        $kat = strtolower($ex['kategori_pengeluaran']);
                                        if (strpos($kat,'stok')!==false) echo 'background:rgba(59,130,246,0.1);color:#3b82f6';
                                        elseif (strpos($kat,'perawatan')!==false) echo 'background:rgba(245,158,11,0.1);color:#d97706';
                                        elseif (strpos($kat,'operasional')!==false) echo 'background:rgba(16,185,129,0.1);color:#059669';
                                        else echo 'background:rgba(139,92,246,0.1);color:#7c3aed';
                                    ?>"><?= htmlspecialchars($ex['kategori_pengeluaran']) ?></span></td>
                                    <td><?= htmlspecialchars($ex['deskripsi'] ?: '-') ?></td>
                                    <td class="text-end" style="font-family:'JetBrains Mono',monospace;font-weight:600;color:#dc2626">Rp <?= number_format($ex['jumlah'], 0, ',', '.') ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary py-0 px-2" style="border-radius:6px" onclick="editExp(<?= $ex['id'] ?>,'<?= htmlspecialchars(addslashes($ex['kategori_pengeluaran'])) ?>','<?= htmlspecialchars(addslashes($ex['deskripsi'])) ?>',<?= (float)$ex['jumlah'] ?>,'<?= $ex['tanggal'] ?>')"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-danger py-0 px-2" style="border-radius:6px" onclick="delExp(<?= $ex['id'] ?>,'<?= htmlspecialchars(addslashes($ex['deskripsi'] ?: 'Item #'.$ex['id'])) ?>')"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /container-fluid -->
    </div><!-- /admin-content -->
</div><!-- /admin-main -->
</div><!-- /admin-wrapper -->

<!-- PENGELUARAN ADD/EDIT MODAL -->
<div class="modal fade" id="modalExp" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content" style="border-radius:20px;border:none">
    <div class="modal-header" style="background:linear-gradient(135deg,#1B4332,#2D6A4F);color:#fff;border-radius:20px 20px 0 0">
        <h5 class="modal-title" id="expModalTitle"><i class="bi bi-plus-circle me-2"></i>Tambah Pengeluaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>/pages/admin/laporan.php">
        <input type="hidden" name="action" id="expAction" value="create_expense">
        <input type="hidden" name="id" id="expId" value="">
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">Kategori</label>
                <select class="form-select" name="kategori_pengeluaran" id="expKat" required>
                    <option value="">-- Pilih --</option>
                    <?php foreach ($expKatOptions as $eo): ?>
                    <option value="<?= $eo ?>"><?= $eo ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Deskripsi</label>
                <textarea class="form-control" name="deskripsi" id="expDesk" rows="2" placeholder="Contoh: Beli tenda dome 4P (2 unit)"></textarea>
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Jumlah (Rp)</label>
                    <input type="number" class="form-control" name="jumlah" id="expJml" required min="1" placeholder="500000">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Tanggal</label>
                    <input type="date" class="form-control" name="tanggal" id="expTgl" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn text-white" style="background:linear-gradient(135deg,#2D6A4F,#52B788);border-radius:10px" id="expBtn"><i class="bi bi-check-lg me-1"></i>Simpan</button>
        </div>
    </form>
</div>
</div>
</div>

<!-- PENGELUARAN DELETE MODAL -->
<div class="modal fade" id="modalDelExp" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-sm">
<div class="modal-content" style="border-radius:20px;border:none">
    <div class="modal-header bg-danger text-white" style="border-radius:20px 20px 0 0">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Hapus?</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body text-center py-4">
        <p>Yakin hapus pengeluaran:</p>
        <p class="fw-bold" id="delExpDesc"></p>
    </div>
    <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <form method="POST" action="<?= BASE_URL ?>/pages/admin/laporan.php">
            <input type="hidden" name="action" value="delete_expense">
            <input type="hidden" name="id" id="delExpId">
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
        </form>
    </div>
</div>
</div>
</div>

<!-- EXPORT MODAL -->
<div class="modal fade" id="modalExport" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
    <div class="modal-header green-header">
        <h5 class="modal-title heading" id="exportTitle"><i class="bi bi-download me-2"></i>Export Laporan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <p class="text-muted mb-3">Pilih format file untuk export:</p>
        <div class="row g-3">
            <div class="col-4">
                <div class="export-card selected position-relative" onclick="selectFormat(this,'pdf')">
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                    <div class="export-icon" style="color:#dc2626"><i class="bi bi-file-earmark-pdf"></i></div>
                    <div class="export-label">PDF</div>
                </div>
            </div>
            <div class="col-4">
                <div class="export-card position-relative" onclick="selectFormat(this,'excel')">
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                    <div class="export-icon" style="color:#16a34a"><i class="bi bi-file-earmark-excel"></i></div>
                    <div class="export-label">Excel</div>
                </div>
            </div>
            <div class="col-4">
                <div class="export-card position-relative" onclick="selectFormat(this,'csv')">
                    <div class="check-mark"><i class="bi bi-check"></i></div>
                    <div class="export-icon" style="color:#3b82f6"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="export-label">CSV</div>
                </div>
            </div>
        </div>
        <input type="hidden" id="selectedFormat" value="pdf">
        <input type="hidden" id="exportType" value="penjualan">
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-gradient" onclick="exportReport()"><i class="bi bi-download me-2"></i>Export</button>
    </div>
</div></div></div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let modalExport;
document.addEventListener('DOMContentLoaded', function() {
    modalExport = new bootstrap.Modal(document.getElementById('modalExport'));
    triggerStagger(document.getElementById('tab-penjualan'));
    animateCounters(document.querySelector('.container-fluid'));
    setTimeout(() => animateChartBars(), 600);
});

// Tab Switching
function switchLpTab(tabName) {
    document.querySelectorAll('.lp-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tabBtn' + tabName.charAt(0).toUpperCase() + tabName.slice(1)).classList.add('active');
    document.querySelectorAll('.tab-pane-lp').forEach(p => p.style.display = 'none');
    const tabEl = document.getElementById('tab-' + tabName);
    tabEl.style.display = 'block';
    triggerStagger(tabEl);
    animateCounters(tabEl);
    if (tabName === 'keuangan') {
        setTimeout(() => animateHBars(), 400);
    } else {
        setTimeout(() => animateChartBars(), 400);
    }
}

function triggerStagger(container) {
    container.querySelectorAll('.stagger-item').forEach((item, i) => {
        item.style.animation = 'none';
        item.offsetHeight;
        item.style.animation = '';
        item.style.animationDelay = (i * 0.1) + 's';
    });
}

// Counters
function animateCounters(container) {
    container.querySelectorAll('.counter').forEach(counter => {
        const target = parseInt(counter.dataset.target);
        const format = counter.dataset.format;
        const duration = 2000, startTime = performance.now();
        function easeOutQuad(t) { return t * (2 - t); }
        function update(currentTime) {
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const current = Math.floor(easeOutQuad(progress) * target);
            counter.textContent = current.toLocaleString('id-ID');
            if (progress < 1) requestAnimationFrame(update);
            else counter.textContent = target.toLocaleString('id-ID');
        }
        requestAnimationFrame(update);
    });
}

// Charts
function animateChartBars() {
    document.querySelectorAll('#barChart .chart-bar').forEach(bar => {
        const h = bar.dataset.height;
        bar.style.height = '0%';
        const delay = parseFloat(bar.style.animationDelay) * 1000 || 0;
        setTimeout(() => { bar.style.height = h + '%'; }, delay);
    });
}

function animateHBars() {
    document.querySelectorAll('#hbarChart .hbar').forEach((bar, i) => {
        const w = bar.dataset.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = w + '%'; }, i * 100);
    });
}

// Print
function printReport(type) {
    const title = type === 'penjualan' ? 'Laporan Penjualan' : 'Laporan Keuangan';
    document.querySelector('.print-header h2').textContent = '📊 ' + title + ' — SIMPEL-CAMP';
    document.getElementById('printDate').textContent = new Date().toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'});

    // Set bar heights for print
    document.querySelectorAll('.chart-bar').forEach(b => b.style.setProperty('--print-h', b.style.height));
    document.querySelectorAll('.hbar').forEach(b => b.style.setProperty('--print-w', b.style.width));

    window.print();
}

// Toast
function showToast(message) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'custom-toast';
    toast.innerHTML = '<div class="toast-icon"><i class="bi bi-check-circle-fill"></i></div><div class="toast-msg">' + message + '</div>';
    container.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'slideOutToast .4s forwards'; setTimeout(() => toast.remove(), 400); }, 3000);
}

// Filter
function applyFilter() { showToast('Filter berhasil diterapkan!'); }

// Export
function openExportModal(type) {
    document.getElementById('exportType').value = type;
    const title = type === 'penjualan' ? 'Export Laporan Penjualan' : 'Export Laporan Keuangan';
    document.getElementById('exportTitle').innerHTML = '<i class="bi bi-download me-2"></i>' + title;
    document.querySelectorAll('.export-card').forEach(c => c.classList.remove('selected'));
    document.querySelector('.export-card').classList.add('selected');
    document.getElementById('selectedFormat').value = 'pdf';
    modalExport.show();
}

function selectFormat(el, format) {
    document.querySelectorAll('.export-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selectedFormat').value = format;
}

function exportReport() {
    const format = document.getElementById('selectedFormat').value.toUpperCase();
    modalExport.hide();
    showToast('Laporan berhasil diexport sebagai ' + format + '! 📄');
}

// ── Pengeluaran CRUD ──
const modalExpBS = new bootstrap.Modal(document.getElementById('modalExp'));
const modalDelExpBS = new bootstrap.Modal(document.getElementById('modalDelExp'));

function openExpModal() {
    document.getElementById('expModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Tambah Pengeluaran';
    document.getElementById('expAction').value = 'create_expense';
    document.getElementById('expId').value = '';
    document.getElementById('expKat').value = '';
    document.getElementById('expDesk').value = '';
    document.getElementById('expJml').value = '';
    document.getElementById('expTgl').value = '<?= date('Y-m-d') ?>';
    document.getElementById('expBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Simpan';
    modalExpBS.show();
}

function editExp(id, kat, desk, jml, tgl) {
    document.getElementById('expModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Pengeluaran';
    document.getElementById('expAction').value = 'update_expense';
    document.getElementById('expId').value = id;
    document.getElementById('expKat').value = kat;
    document.getElementById('expDesk').value = desk;
    document.getElementById('expJml').value = jml;
    document.getElementById('expTgl').value = tgl;
    document.getElementById('expBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Perbarui';
    modalExpBS.show();
}

function delExp(id, desc) {
    document.getElementById('delExpId').value = id;
    document.getElementById('delExpDesc').textContent = desc;
    modalDelExpBS.show();
}

// Auto-switch to Keuangan tab if CRUD action was done
<?php if ($pMessage): ?>
document.addEventListener('DOMContentLoaded', function() {
    const keuBtn = document.querySelector('[onclick*="keuangan"]') || document.querySelectorAll('.lp-tab')[1];
    if (keuBtn) keuBtn.click();
});
<?php endif; ?>
</script>
</body></html>