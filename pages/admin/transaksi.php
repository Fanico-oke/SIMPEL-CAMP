<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/Perpanjangan.php';
require_once dirname(__DIR__, 2) . '/classes/Pengembalian.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . '/login.php'); exit; }
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$page_title = 'Manajemen Transaksi';
$current_page = 'transaksi';

$trxSelesai = Transaksi::count('selesai');
$rsvPending = Reservasi::count('pending');
$trxAktif = Transaksi::count('aktif');
$pendapatan = Transaksi::totalPendapatan(date('Y-m-01'), date('Y-m-t'));
$pendapatanStr = $pendapatan >= 1000000 ? 'Rp ' . number_format($pendapatan/1000000, 1, ',', '.') . 'jt' : 'Rp ' . number_format($pendapatan, 0, ',', '.');
$adminName = $_SESSION['nama'] ?? 'Admin';

// Data untuk Tab 1: Reservasi
$allReservasi = Reservasi::getAll();
$rsvList = [];
foreach ($allReservasi as $r) {
    $details = Reservasi::getDetail($r['id']);
    $barangNames = array_map(function($d) { return $d['barang_nama']; }, $details);
    $tglMulai = strtotime($r['tanggal_mulai']);
    $tglSelesai = strtotime($r['tanggal_selesai']);
    $durasi = max(1, round(($tglSelesai - $tglMulai) / 86400));
    $statusMap = ['pending'=>['Menunggu','badge-pending'],'disetujui'=>['Dikonfirmasi','badge-approved'],'aktif'=>['Aktif','badge-aktif'],'selesai'=>['Selesai','badge-selesai'],'batal'=>['Batal','badge-rejected'],'ditolak'=>['Ditolak','badge-rejected']];
    $st = $statusMap[$r['status']] ?? ['Unknown','badge-pending'];
    $rsvList[] = ['kode'=>$r['kode_reservasi'],'pelanggan'=>$r['user_nama'] ?? 'Unknown','barang'=>implode(', ',$barangNames),'tgl'=>date('d M Y',$tglMulai),'durasi'=>$durasi.' hari','total'=>$r['total_biaya'],'status_label'=>$st[0],'status_class'=>$st[1]];
}

// Data untuk Tab 2: Barang tersedia (POS form)
$semuaBarang = Barang::getAll(['status' => 'tersedia', 'limit' => 12]);

// Data untuk riwayat transaksi
$recentTrx = Transaksi::getAll(['limit' => 5]);

// Data untuk Tab 3: Perpanjangan
$perpPending = Perpanjangan::getAll(['status' => 'pending']);
$perpHistory = Perpanjangan::getAll(['limit' => 5]);

// Data untuk Tab 4: Pengembalian
$pengembalianList = Pengembalian::getAll(5); // Fix argument type
$stmtPgPending = Database::getInstance()->prepare("
    SELECT p.*, t.kode_transaksi, u.nama AS nama_user, t.id AS trx_id, r.id AS rsv_id
    FROM pengembalian p
    JOIN transaksi t ON p.transaksi_id = t.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN reservasi r ON t.reservasi_id = r.id
    WHERE p.status = 'menunggu_cek'
    ORDER BY p.created_at ASC
");
$stmtPgPending->execute();
$pengembalianPending = $stmtPgPending->fetchAll();
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
:root{--t-dark:#1B4332;--t-mid:#2D6A4F;--t-light:#52B788;--t-gold:#D4A373;--t-bg:#f0f4f1}
body{font-family:'Inter',sans-serif;background:var(--t-bg)}
h1,h2,h3,h4,h5,h6,.heading{font-family:'Outfit',sans-serif}
.mono{font-family:'JetBrains Mono',monospace}
.tr-tabs{display:flex;gap:6px;background:#fff;border-radius:14px;padding:6px;box-shadow:0 2px 12px rgba(27,67,50,0.06);margin-bottom:28px;flex-wrap:wrap}
.tr-tab{padding:10px 22px;border-radius:10px;border:none;background:transparent;font-weight:600;color:#6c757d;cursor:pointer;transition:all .3s;font-family:'Inter',sans-serif;font-size:.88rem;white-space:nowrap}
.tr-tab.active{background:linear-gradient(135deg,var(--t-mid),var(--t-light));color:#fff;box-shadow:0 4px 16px rgba(45,106,79,0.3)}
.tr-tab:hover:not(.active){background:rgba(82,183,136,0.08);color:var(--t-mid)}
.tr-tab .badge{font-size:.7rem;padding:2px 8px;border-radius:50px;margin-left:6px}
.stat-mini{background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.05);border-left:4px solid;display:flex;align-items:center;gap:14px;transition:all .3s}
.stat-mini:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.08)}
.stat-mini-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.stat-mini-val{font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;color:var(--t-dark);line-height:1}
.stat-mini-label{font-size:.78rem;color:#6c757d;font-weight:500}
.stat-mini-trend{font-size:.72rem;font-weight:600;margin-left:auto;padding:3px 8px;border-radius:6px}
.stat-mini-trend.up{background:rgba(16,185,129,0.1);color:#10b981}
.stat-mini-trend.down{background:rgba(239,68,68,0.1);color:#ef4444}
.rsv-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 14px rgba(0,0,0,0.05);transition:all .35s;height:100%;display:flex;flex-direction:column}
.rsv-card:hover{transform:translateY(-6px);box-shadow:0 12px 32px rgba(27,67,50,0.1)}
.avatar-circle{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--t-mid),var(--t-light));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-family:'Outfit',sans-serif;flex-shrink:0}
.rsv-id{font-family:'JetBrains Mono',monospace;font-size:.78rem;background:rgba(82,183,136,0.1);color:var(--t-mid);padding:2px 10px;border-radius:50px;font-weight:600}
.badge-status{padding:4px 14px;border-radius:50px;font-size:.75rem;font-weight:600}
.badge-pending{background:rgba(245,158,11,0.1);color:#d97706}
.badge-approved{background:rgba(82,183,136,0.1);color:#16a34a}
.badge-rejected{background:rgba(239,68,68,0.1);color:#dc2626}
.badge-aktif{background:rgba(59,130,246,0.1);color:#3b82f6}
.badge-selesai{background:rgba(82,183,136,0.1);color:#16a34a}
.badge-overdue{background:rgba(239,68,68,0.1);color:#dc2626;animation:pulse-badge 2s infinite}
@keyframes pulse-badge{0%,100%{opacity:1}50%{opacity:.5}}
.rsv-total{font-family:'JetBrains Mono',monospace;font-size:1.2rem;font-weight:700;color:var(--t-mid)}
.card-actions-row{margin-top:auto;padding-top:16px;display:flex;gap:8px}
.btn-approve{background:linear-gradient(135deg,var(--t-mid),var(--t-light));color:#fff;border:none;border-radius:10px;padding:9px 20px;font-weight:600;font-size:.85rem;transition:all .3s}
.btn-approve:hover{box-shadow:0 6px 18px rgba(45,106,79,0.3);color:#fff}
.btn-reject{border:1.5px solid #ef4444;color:#ef4444;background:transparent;border-radius:10px;padding:9px 20px;font-weight:600;font-size:.85rem;transition:all .3s}
.btn-reject:hover{background:#ef4444;color:#fff}
.info-row{display:flex;align-items:center;gap:8px;padding:6px 0;font-size:.88rem;color:#555}
.info-row i{color:var(--t-light);width:18px;text-align:center}
.trx-table{background:#fff;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,0.05);overflow:hidden}
.trx-table .table{margin-bottom:0}
.trx-table .table th{background:rgba(82,183,136,0.06);font-size:.82rem;font-weight:600;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;padding:14px 16px;border-bottom:2px solid #e8e8e8}
.trx-table .table td{padding:14px 16px;vertical-align:middle;border-color:#f0f0f0;font-size:.88rem}
.trx-table .table tbody tr:hover{background:rgba(82,183,136,0.03)}
.form-section{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 14px rgba(0,0,0,0.05);margin-bottom:24px}
.form-section-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:1.1rem;color:var(--t-dark);margin-bottom:20px;display:flex;align-items:center;gap:10px}
.form-section-title i{color:var(--t-light)}
.item-checkbox{background:#fff;border:1.5px solid #e8e8e8;border-radius:12px;padding:16px;transition:all .3s;cursor:pointer}
.item-checkbox:hover{border-color:var(--t-light);box-shadow:0 4px 12px rgba(82,183,136,0.1)}
.item-checkbox.selected{border-color:var(--t-light);background:rgba(82,183,136,0.04)}
.item-checkbox .form-check-input:checked{background-color:var(--t-mid);border-color:var(--t-mid)}
.item-name{font-weight:600;font-size:.9rem;color:var(--t-dark)}
.item-price-tag{font-family:'JetBrains Mono',monospace;font-size:.82rem;color:var(--t-mid);font-weight:500}
.qty-input{width:65px;text-align:center;border-radius:8px;border:1.5px solid #e0e0e0;padding:5px;font-size:.85rem}
.qty-input:focus{border-color:var(--t-light);outline:none;box-shadow:0 0 0 3px rgba(82,183,136,0.15)}
.payment-option{border:1.5px solid #e8e8e8;border-radius:12px;padding:14px 18px;cursor:pointer;transition:all .3s;display:flex;align-items:center;gap:10px}
.payment-option:hover{border-color:var(--t-light)}
.payment-option.selected{border-color:var(--t-light);background:rgba(82,183,136,0.04)}
.payment-option input[type="radio"]{accent-color:var(--t-mid)}
.calc-section{border:2px dashed rgba(82,183,136,0.3);background:rgba(82,183,136,0.02);border-radius:14px;padding:20px;margin-top:20px}
.calc-row{display:flex;justify-content:space-between;padding:6px 0;font-size:.9rem}
.calc-total{border-top:2px solid var(--t-light);padding-top:12px;margin-top:8px}
.calc-total .calc-val{font-family:'JetBrains Mono',monospace;font-size:1.3rem;font-weight:700;color:var(--t-mid)}
.return-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 14px rgba(0,0,0,0.05);margin-bottom:16px;transition:all .3s;height:100%}
.return-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.08)}
.ext-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 14px rgba(0,0,0,0.05);transition:all .3s;height:100%}
.ext-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.08);transform:translateY(-4px)}
.ext-arrow{font-size:1.2rem;color:var(--t-gold);margin:0 6px}
.ext-cost{font-family:'JetBrains Mono',monospace;font-size:1.1rem;font-weight:700;color:var(--t-mid)}
.timeline{position:relative;padding-left:28px}
.timeline::before{content:'';position:absolute;left:9px;top:0;bottom:0;width:2px;background:linear-gradient(180deg,var(--t-light),#e8e8e8)}
.timeline-item{position:relative;margin-bottom:20px}
.timeline-item::before{content:'';position:absolute;left:-23px;top:4px;width:12px;height:12px;border-radius:50%;background:var(--t-light);border:2px solid #fff;box-shadow:0 0 0 3px rgba(82,183,136,0.2)}
.timeline-item.completed::before{background:var(--t-mid)}
.timeline-item.pending::before{background:#f59e0b}
.timeline-time{font-size:.75rem;color:#9ca3af;font-family:'JetBrains Mono',monospace}
.timeline-text{font-size:.88rem;color:#333;font-weight:500}
.modal-content{border-radius:16px;border:none;overflow:hidden}
.modal-header.green-header{background:linear-gradient(135deg,var(--t-mid),var(--t-light));color:#fff;border:none;padding:18px 24px}
.modal-header.green-header .btn-close{filter:brightness(0) invert(1)}
.modal-header.red-header{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;padding:18px 24px}
.modal-header.red-header .btn-close{filter:brightness(0) invert(1)}
.success-checkmark{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--t-mid),var(--t-light));display:flex;align-items:center;justify-content:center;margin:0 auto 20px;animation:scaleIn .5s cubic-bezier(.4,0,.2,1)}
.success-checkmark i{color:#fff;font-size:2.2rem}
@keyframes scaleIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.custom-toast{background:#fff;border-radius:12px;padding:14px 20px;box-shadow:0 8px 30px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;border-left:4px solid var(--t-light);animation:slideInToast .4s forwards}
@keyframes slideInToast{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}
@keyframes slideOutToast{from{opacity:1}to{opacity:0;transform:translateX(100%)}}
.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px}
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.stagger-item{opacity:0;animation:fadeInUp .5s ease forwards}
@media(max-width:768px){.tr-tabs{padding:4px}.tr-tab{padding:8px 14px;font-size:.8rem}}
</style></head><body>
<div class="admin-wrapper">
<?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>

    <div class="admin-content"><div class="container-fluid">

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#10b981"><div class="stat-mini-icon" style="background:rgba(16,185,129,0.1);color:#10b981"><i class="bi bi-check-circle"></i></div><div><div class="stat-mini-val"><?= $trxSelesai ?></div><div class="stat-mini-label">Selesai Bulan Ini</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#f59e0b"><div class="stat-mini-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-mini-val"><?= $rsvPending ?></div><div class="stat-mini-label">Menunggu Konfirmasi</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#3b82f6"><div class="stat-mini-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6"><i class="bi bi-arrow-repeat"></i></div><div><div class="stat-mini-val"><?= $trxAktif ?></div><div class="stat-mini-label">Sedang Disewa</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#8b5cf6"><div class="stat-mini-icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6"><i class="bi bi-currency-dollar"></i></div><div><div class="stat-mini-val"><?= $pendapatanStr ?></div><div class="stat-mini-label">Pendapatan Bulan Ini</div></div></div></div>
        </div>

        <!-- Tabs -->
        <div class="tr-tabs">
            <button class="tr-tab active" id="tabBtnReservasi" onclick="switchTab('reservasi')"><i class="bi bi-calendar-check me-1"></i>Reservasi <span class="badge bg-warning text-dark"><?= $rsvPending ?></span></button>
            <button class="tr-tab" id="tabBtnTransaksi" onclick="switchTab('transaksi')"><i class="bi bi-cart-plus me-1"></i>Transaksi Baru</button>
            <button class="tr-tab" id="tabBtnPerpanjangan" onclick="switchTab('perpanjangan')"><i class="bi bi-arrow-clockwise me-1"></i>Perpanjangan <?= count($perpPending) > 0 ? '<span class="badge bg-danger ms-1">'.count($perpPending).'</span>' : '' ?></button>
            <button class="tr-tab" id="tabBtnPengembalian" onclick="switchTab('pengembalian')"><i class="bi bi-box-arrow-in-left me-1"></i>Pengembalian</button>
        </div>

        <!-- TAB 1: RESERVASI -->
        <div id="tab-reservasi" class="tab-pane-tr">
            <div class="row g-4 mb-4">
                <?php
                $pendingReservasi = array_filter($allReservasi, fn($r) => in_array($r['status'], ['pending', 'disetujui']));
                if (empty($pendingReservasi)): ?>
                <div class="col-12"><div class="text-center py-5 text-muted"><i class="bi bi-calendar-check" style="font-size:2.5rem"></i><p class="mt-3">Tidak ada reservasi yang menunggu persetujuan</p></div></div>
                <?php else: ?>
                <?php foreach (array_slice($pendingReservasi, 0, 6) as $rsv):
                    $rsvDetails = Reservasi::getDetail($rsv['id']);
                    $rsvBarangNames = array_map(fn($d) => $d['barang_nama'], $rsvDetails);
                    $rsvTglMulai = strtotime($rsv['tanggal_mulai']);
                    $rsvTglSelesai = strtotime($rsv['tanggal_selesai']);
                    $rsvDurasi = max(1, round(($rsvTglSelesai - $rsvTglMulai) / 86400));
                    $rsvNama = $rsv['user_nama'] ?? 'Unknown';
                    $rsvInitial = strtoupper(substr($rsvNama, 0, 1)) . strtoupper(substr(explode(' ', $rsvNama)[1] ?? '', 0, 1));
                    $isPending = $rsv['status'] === 'pending';
                    $statusLabel = $isPending ? 'Menunggu' : 'Dikonfirmasi';
                    $statusClass = $isPending ? 'badge-pending' : 'badge-approved';
                ?>
                <div class="col-lg-4 stagger-item"><div class="rsv-card" id="rsv-<?= $rsv['id'] ?>">
                    <div class="d-flex align-items-center gap-3 mb-3"><div class="avatar-circle"><?= $rsvInitial ?></div><div><h6 class="heading fw-bold mb-0"><?= htmlspecialchars($rsvNama) ?></h6><span class="rsv-id"><?= htmlspecialchars($rsv['kode_reservasi']) ?></span></div><span class="badge-status <?= $statusClass ?> ms-auto" id="status-<?= $rsv['id'] ?>"><?= $statusLabel ?></span></div>
                    <div class="info-row"><i class="bi bi-box-seam"></i> <?= htmlspecialchars(mb_strimwidth(implode(', ', $rsvBarangNames), 0, 40, '...')) ?></div>
                    <div class="info-row"><i class="bi bi-calendar3"></i> <?= date('d', $rsvTglMulai) ?> - <?= date('d M Y', $rsvTglSelesai) ?> (<?= $rsvDurasi ?> hari)</div>
                    <div class="rsv-total my-2">Rp <?= number_format($rsv['total_biaya'], 0, ',', '.') ?></div>
                    <?php if ($isPending): ?>
                    <div class="card-actions-row" id="actions-<?= $rsv['id'] ?>"><button class="btn btn-approve flex-fill" onclick="approveReservation(<?= $rsv['id'] ?>)"><i class="bi bi-check-lg me-1"></i>Setujui</button><button class="btn btn-reject flex-fill" onclick="rejectReservation(<?= $rsv['id'] ?>)"><i class="bi bi-x-lg me-1"></i>Tolak</button></div>
                    <?php else: ?>
                    <div class="card-actions-row">
                        <a href="<?= BASE_URL ?>/pages/admin/detail_reservasi.php?id=<?= $rsv['id'] ?>" class="btn btn-approve flex-fill text-center text-decoration-none" style="background:var(--t-mid)"><i class="bi bi-box-arrow-up-right me-1"></i>Lihat Detail</a>
                    </div>
                    <?php endif; ?>
                </div></div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- All Reservations Table -->
            <div class="trx-table stagger-item">
                <div class="p-3 d-flex justify-content-between align-items-center"><h5 class="heading fw-bold mb-0"><i class="bi bi-list-ul me-2 text-muted"></i>Semua Reservasi</h5><span class="badge rounded-pill" style="background:rgba(82,183,136,0.12);color:#2D6A4F;font-weight:600;padding:6px 14px"><?= count($rsvList) ?> Total</span></div>
                <div class="table-responsive"><table class="table align-middle">
                    <thead><tr><th>ID</th><th>Pelanggan</th><th>Barang</th><th>Tanggal Sewa</th><th>Durasi</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if (empty($rsvList)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada reservasi</td></tr>
                        <?php else: ?>
                        <?php foreach ($rsvList as $rv): ?>
                        <tr>
                            <td class="mono fw-semibold"><?= htmlspecialchars($rv['kode']) ?></td>
                            <td><?= htmlspecialchars($rv['pelanggan']) ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($rv['barang'], 0, 40, '...')) ?></td>
                            <td><?= $rv['tgl'] ?></td>
                            <td><?= $rv['durasi'] ?></td>
                            <td class="mono fw-bold" style="color:var(--t-mid)">Rp <?= number_format($rv['total'], 0, ',', '.') ?></td>
                            <td><span class="badge-status <?= $rv['status_class'] ?>"><?= $rv['status_label'] ?></span></td>
                            <td><a href="<?= BASE_URL ?>/pages/admin/detail_reservasi.php?id=<?= $rsvList[array_search($rv, $rsvList)]['id'] ?? (int)$allReservasi[array_search($rv, $rsvList)]['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:0.75rem;font-weight:600">Detail</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table></div>
            </div>
        </div>

        <!-- TAB 2: TRANSAKSI BARU -->
        <div id="tab-transaksi" class="tab-pane-tr" style="display:none">
            <div class="form-section stagger-item"><div class="form-section-title"><i class="bi bi-person-badge"></i>Data Pelanggan</div><div class="row g-3"><div class="col-md-6"><label class="form-label fw-semibold">Nama Pelanggan</label><input type="text" class="form-control" placeholder="Cari nama pelanggan..." id="custName"></div><div class="col-md-3"><label class="form-label fw-semibold">No. Telepon</label><input type="text" class="form-control" placeholder="08xx-xxxx-xxxx" id="custPhone"></div><div class="col-md-3"><label class="form-label fw-semibold">No. KTP</label><input type="text" class="form-control" placeholder="35xxxxxxxxxx" id="custKtp"></div></div></div>
            <div class="form-section stagger-item"><div class="form-section-title"><i class="bi bi-box-seam"></i>Pilih Barang Sewa</div><div class="row g-3">
                <?php foreach ($semuaBarang as $idx => $brg): ?>
                <div class="col-md-6 col-lg-3"><div class="item-checkbox" onclick="toggleItem(this,'<?= htmlspecialchars($brg['nama']) ?>',<?= (int)$brg['harga_per_hari'] ?>)"><div class="form-check d-flex align-items-center gap-2"><input class="form-check-input" type="checkbox" id="itm<?= $idx+1 ?>"><label class="item-name" for="itm<?= $idx+1 ?>"><?= htmlspecialchars($brg['nama']) ?></label></div><div class="d-flex align-items-center justify-content-between mt-2"><span class="item-price-tag">Rp <?= number_format($brg['harga_per_hari'], 0, ',', '.') ?>/hari</span><input type="number" class="qty-input" value="1" min="1" max="<?= (int)$brg['stok_tersedia'] ?>" onchange="updateCalc()"></div></div></div>
                <?php endforeach; ?>
            </div></div>
            <div class="row g-4">
                <div class="col-lg-6"><div class="form-section stagger-item"><div class="form-section-title"><i class="bi bi-calendar3"></i>Durasi Sewa</div><div class="row g-3"><div class="col-6"><label class="form-label fw-semibold">Tanggal Mulai</label><input type="date" class="form-control" id="startDate" value="2026-06-17" onchange="updateCalc()"></div><div class="col-6"><label class="form-label fw-semibold">Tanggal Selesai</label><input type="date" class="form-control" id="endDate" value="2026-06-19" onchange="updateCalc()"></div></div><div class="mt-3 p-3 rounded-3" style="background:rgba(82,183,136,0.06)"><div class="d-flex justify-content-between"><span class="text-muted">Durasi:</span><span class="fw-bold" id="durasiLabel">2 hari</span></div></div></div></div>
                <div class="col-lg-6"><div class="form-section stagger-item"><div class="form-section-title"><i class="bi bi-credit-card"></i>Metode Pembayaran</div><div class="d-flex flex-column gap-3"><div class="payment-option selected" onclick="selectPayment(this)"><input type="radio" name="payment" checked> <i class="bi bi-cash-stack" style="color:var(--t-light);font-size:1.2rem"></i><div><div class="fw-semibold">Cash</div><div style="font-size:.78rem;color:#9ca3af">Bayar langsung di toko</div></div></div><div class="payment-option" onclick="selectPayment(this)"><input type="radio" name="payment"> <i class="bi bi-bank" style="color:#3b82f6;font-size:1.2rem"></i><div><div class="fw-semibold">Transfer Bank</div><div style="font-size:.78rem;color:#9ca3af">BCA / Mandiri / BRI</div></div></div><div class="payment-option" onclick="selectPayment(this)"><input type="radio" name="payment"> <i class="bi bi-phone" style="color:#8b5cf6;font-size:1.2rem"></i><div><div class="fw-semibold">E-Wallet</div><div style="font-size:.78rem;color:#9ca3af">OVO / GoPay / DANA</div></div></div></div></div></div>
            </div>
            <div class="form-section stagger-item mt-4"><div class="form-section-title"><i class="bi bi-calculator"></i>Rincian Biaya</div><div class="calc-section" id="calcSection"><div class="calc-row"><span class="text-muted">Belum ada barang dipilih</span></div></div><div class="mt-4 d-flex gap-3"><button class="btn btn-approve flex-fill" onclick="confirmTransaction()"><i class="bi bi-check-circle me-2"></i>Proses Transaksi</button><button class="btn btn-reject" onclick="resetForm()"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button></div></div>
            <div class="trx-table stagger-item mt-4"><div class="p-3"><h5 class="heading fw-bold mb-0"><i class="bi bi-clock-history me-2 text-muted"></i>Riwayat Transaksi Terakhir</h5></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>ID</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead><tbody>
                <?php if (empty($recentTrx)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
                <?php else: ?>
                <?php foreach ($recentTrx as $trx):
                    $trxStatusMap = ['pending'=>['Menunggu','badge-pending'],'dibayar'=>['Dibayar','badge-approved'],'aktif'=>['Aktif','badge-aktif'],'selesai'=>['Selesai','badge-selesai'],'batal'=>['Batal','badge-rejected']];
                    $trxSt = $trxStatusMap[$trx['status']] ?? ['Unknown','badge-pending'];
                ?>
                <tr><td class="mono fw-semibold"><?= htmlspecialchars($trx['kode_transaksi'] ?? 'TRX-'.$trx['id']) ?></td><td><?= htmlspecialchars($trx['user_nama'] ?? '-') ?></td><td class="mono fw-bold" style="color:var(--t-mid)">Rp <?= number_format($trx['total_bayar'] ?? 0, 0, ',', '.') ?></td><td><span class="badge-status <?= $trxSt[1] ?>"><?= $trxSt[0] ?></span></td><td><?= date('d M', strtotime($trx['created_at'])) ?></td></tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody></table></div></div>
        </div>

        <!-- TAB 3: PERPANJANGAN -->
        <div id="tab-perpanjangan" class="tab-pane-tr" style="display:none">
            <div class="row g-4 mb-4">
                <?php if (empty($perpPending)): ?>
                <div class="col-12"><div class="text-center py-5 text-muted"><i class="bi bi-clock-history" style="font-size:2.5rem"></i><p class="mt-3">Tidak ada permintaan perpanjangan</p></div></div>
                <?php else: ?>
                <?php foreach ($perpPending as $pp):
                    $ppUser = $pp['user_nama'] ?? 'Unknown';
                    $ppInitial = strtoupper(substr($ppUser, 0, 1)) . strtoupper(substr(explode(' ', $ppUser)[1] ?? '', 0, 1));
                ?>
                <div class="col-lg-6 stagger-item">
                    <div class="ext-card">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-circle"><?= $ppInitial ?></div>
                            <div>
                                <h6 class="heading fw-bold mb-0"><?= htmlspecialchars($ppUser) ?></h6>
                                <span class="rsv-id">EXT-<?= $pp['id'] ?> (<?= strtoupper($pp['metode_bayar'] ?? 'CASH') ?>)</span>
                            </div>
                        </div>
                        <div class="info-row"><i class="bi bi-calendar3"></i> <?= date('d M', strtotime($pp['tanggal_lama'])) ?> → <?= date('d M Y', strtotime($pp['tanggal_baru'])) ?></div>
                        <div class="ext-cost mb-3">Biaya Tambahan: Rp <?= number_format($pp['biaya_tambahan'] ?? 0, 0, ',', '.') ?></div>
                        <?php if (!empty($pp['bukti_bayar'])): ?>
                        <button type="button" onclick="openImageModal('<?= BASE_URL ?>/assets/img/pembayaran/<?= $pp['bukti_bayar'] ?>')" class="btn btn-sm btn-outline-primary w-100 mb-3"><i class="bi bi-image me-1"></i>Lihat Bukti Transfer</button>
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            <button class="btn btn-approve flex-fill" onclick="approvePerpanjangan(<?= $pp['id'] ?>)"><i class="bi bi-check-lg me-1"></i>Setujui</button>
                            <button class="btn btn-reject" onclick="rejectPerpanjangan(<?= $pp['id'] ?>)"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="trx-table stagger-item"><div class="p-3"><h5 class="heading fw-bold mb-0"><i class="bi bi-clock-history me-2 text-muted"></i>Riwayat Perpanjangan</h5></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>ID</th><th>Tgl Lama</th><th>Tgl Baru</th><th>Biaya</th><th>Status</th></tr></thead><tbody>
                <?php if (empty($perpHistory)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat</td></tr>
                <?php else: ?>
                <?php foreach ($perpHistory as $ph):
                    $phStatusMap = ['pending'=>['Menunggu','badge-pending'],'disetujui'=>['Disetujui','badge-approved'],'ditolak'=>['Ditolak','badge-rejected']];
                    $phSt = $phStatusMap[$ph['status']] ?? ['Unknown','badge-pending'];
                ?>
                <tr><td class="mono fw-semibold">EXT-<?= $ph['id'] ?></td><td><?= date('d M', strtotime($ph['tanggal_lama'])) ?></td><td><?= date('d M', strtotime($ph['tanggal_baru'])) ?></td><td class="mono fw-bold" style="color:var(--t-mid)">Rp <?= number_format($ph['biaya_tambahan'] ?? 0, 0, ',', '.') ?></td><td><span class="badge-status <?= $phSt[1] ?>"><?= $phSt[0] ?></span></td></tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody></table></div></div>
        </div>

        <!-- TAB 4: PENGEMBALIAN -->
        <div id="tab-pengembalian" class="tab-pane-tr" style="display:none">
            <div class="row g-4 mb-4">
                <?php if (empty($pengembalianList)): ?>
                <div class="col-12"><div class="text-center py-5 text-muted"><i class="bi bi-box-arrow-in-left" style="font-size:2.5rem"></i><p class="mt-3">Tidak ada data pengembalian</p></div></div>
                <?php else: ?>
                <?php foreach (array_slice($pengembalianList, 0, 2) as $pg): ?>
                <div class="col-lg-6 stagger-item"><div class="return-card"><div class="d-flex align-items-center gap-3 mb-3"><div class="avatar-circle"><?= strtoupper(substr($pg['nama_user'] ?? 'U', 0, 1)) ?></div><div><h6 class="heading fw-bold mb-0"><?= htmlspecialchars($pg['nama_user'] ?? '-') ?></h6><span class="rsv-id">RTN-<?= $pg['id'] ?></span></div><span class="badge-status <?= ($pg['denda'] ?? 0) > 0 ? 'badge-overdue' : 'badge-approved' ?> ms-auto"><?= ($pg['denda'] ?? 0) > 0 ? 'Ada Denda' : 'Tepat Waktu' ?></span></div><div class="info-row"><i class="bi bi-calendar3"></i> Dikembalikan: <?= date('d M Y', strtotime($pg['tanggal_kembali'])) ?></div><div class="info-row"><i class="bi bi-cash"></i> Denda: Rp <?= number_format($pg['denda'] ?? 0, 0, ',', '.') ?></div></div></div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Section Menunggu Dicek -->
            <div class="trx-table stagger-item mb-4"><div class="p-3"><h5 class="heading fw-bold mb-0" style="color: #d97706;"><i class="bi bi-hourglass-split me-2"></i>Menunggu Dicek</h5></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>ID Transaksi</th><th>Pelanggan</th><th>Tgl Pengajuan</th><th>Foto Bukti</th><th>Aksi</th></tr></thead><tbody>
                <?php if (empty($pengembalianPending)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada pengembalian yang menunggu dicek</td></tr>
                <?php else: ?>
                <?php foreach ($pengembalianPending as $pend): ?>
                <tr>
                    <td class="mono fw-semibold"><?= htmlspecialchars($pend['kode_transaksi']) ?></td>
                    <td><?= htmlspecialchars($pend['nama_user']) ?></td>
                    <td><?= date('d M Y H:i', strtotime($pend['created_at'])) ?></td>
                    <td>
                        <?php if(!empty($pend['foto_bukti'])): ?>
                            <button type="button" onclick="openImageModal('<?= BASE_URL ?>/uploads/pengembalian/<?= $pend['foto_bukti'] ?>')" class="btn btn-sm btn-light" style="border:1px solid #ddd"><i class="bi bi-image me-1"></i>Lihat Foto</button>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="?page=detail_reservasi&id=<?= $pend['rsv_id'] ?>" class="btn btn-sm btn-approve" style="padding:5px 12px; font-size:0.8rem">Proses</a></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody></table></div></div>

            <div class="trx-table stagger-item"><div class="p-3"><h5 class="heading fw-bold mb-0"><i class="bi bi-clock-history me-2 text-muted"></i>Riwayat Pengembalian</h5></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>ID</th><th>Tgl Kembali</th><th>Kondisi</th><th>Denda</th><th>Status</th></tr></thead><tbody>
                <?php if (empty($pengembalianList)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat</td></tr>
                <?php else: ?>
                <?php foreach ($pengembalianList as $rtn): ?>
                <tr><td class="mono fw-semibold">RTN-<?= $rtn['id'] ?></td><td><?= date('d M', strtotime($rtn['tanggal_kembali'])) ?></td><td><span class="badge-status <?= ($rtn['kondisi_barang'] ?? 'baik') === 'baik' ? 'badge-approved' : 'badge-pending' ?>"><?= ucfirst($rtn['kondisi_barang'] ?? 'Baik') ?></span></td><td class="mono <?= ($rtn['denda'] ?? 0) > 0 ? 'text-danger' : '' ?>">Rp <?= number_format($rtn['denda'] ?? 0, 0, ',', '.') ?></td><td><span class="badge-status badge-selesai">Selesai</span></td></tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody></table></div></div>
        </div>

    </div></div>
</div></div>

<!-- Modals -->
<div class="modal fade" id="modalApprove" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header green-header"><h5 class="modal-title heading"><i class="bi bi-check-circle me-2"></i>Setujui Reservasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Yakin ingin menyetujui reservasi ini?</p><div class="mb-3"><label class="form-label fw-semibold">Catatan (opsional)</label><textarea class="form-control" rows="2" id="approveNote" placeholder="Tambahkan catatan..."></textarea></div></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-approve" onclick="confirmApprove()"><i class="bi bi-check-lg me-1"></i>Setujui</button></div></div></div></div>
<div class="modal fade" id="modalReject" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header red-header"><h5 class="modal-title heading"><i class="bi bi-x-circle me-2"></i>Tolak Reservasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Yakin ingin menolak reservasi ini?</p><div class="mb-3"><label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label><textarea class="form-control" rows="2" id="rejectAlasan" placeholder="Berikan alasan..."></textarea></div></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-reject" onclick="confirmReject()" style="background:#ef4444;color:#fff;border:none"><i class="bi bi-x-lg me-1"></i>Tolak</button></div></div></div></div>
<div class="modal fade" id="modalConfirmTrx" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header green-header"><h5 class="modal-title heading"><i class="bi bi-cart-check me-2"></i>Konfirmasi Transaksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="confirmBody"><p>Detail transaksi akan ditampilkan di sini.</p></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-approve" onclick="finalizeTrx()"><i class="bi bi-check-circle me-1"></i>Konfirmasi</button></div></div></div></div>
<div class="modal fade" id="modalSuccessTrx" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-body text-center py-5"><div class="success-checkmark"><i class="bi bi-check-lg"></i></div><h5 class="heading fw-bold">Transaksi Berhasil!</h5><p class="text-muted">Transaksi baru telah dicatat.</p><button class="btn btn-approve" data-bs-dismiss="modal" onclick="location.reload()">OK</button></div></div></div></div>
<div class="modal fade" id="modalReturn" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header green-header"><h5 class="modal-title heading"><i class="bi bi-box-arrow-in-left me-2"></i>Proses Pengembalian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" id="returnTransaksiId"><div class="mb-3"><label class="form-label fw-semibold">Kondisi Barang</label><select class="form-select" id="returnKondisi"><option value="baik">Baik</option><option value="rusak_ringan">Rusak Ringan</option><option value="rusak_berat">Rusak Berat</option><option value="hilang">Hilang</option></select></div><div class="mb-3"><label class="form-label fw-semibold">Catatan</label><textarea class="form-control" rows="2" id="returnCatatan" placeholder="Catatan kondisi barang..."></textarea></div></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-approve" onclick="confirmReturn()"><i class="bi bi-check-circle me-1"></i>Konfirmasi</button></div></div></div></div>
<div class="modal fade" id="modalReturnSuccess" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-body text-center py-5"><div class="success-checkmark"><i class="bi bi-check-lg"></i></div><h5 class="heading fw-bold">Pengembalian Berhasil!</h5><p class="text-muted">Barang telah dicatat kembali ke stok.</p><button class="btn btn-approve" data-bs-dismiss="modal" onclick="location.reload()">OK</button></div></div></div></div>

<div class="toast-container" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
let modalApprove,modalReject,modalConfirmTrx,modalSuccessTrx,modalReturn,modalReturnSuccess;
let activeRsvId=null,selectedItems=[];

// Map barang data from PHP for walk-in transaction
const barangPosData = <?= json_encode(array_map(function($b) {
    return ['id' => (int)$b['id'], 'nama' => $b['nama'], 'harga' => (int)$b['harga_per_hari']];
}, $semuaBarang)) ?>;

document.addEventListener('DOMContentLoaded',function(){
    modalApprove=new bootstrap.Modal(document.getElementById('modalApprove'));
    modalReject=new bootstrap.Modal(document.getElementById('modalReject'));
    modalConfirmTrx=new bootstrap.Modal(document.getElementById('modalConfirmTrx'));
    modalSuccessTrx=new bootstrap.Modal(document.getElementById('modalSuccessTrx'));
    modalReturn=new bootstrap.Modal(document.getElementById('modalReturn'));
    modalReturnSuccess=new bootstrap.Modal(document.getElementById('modalReturnSuccess'));
    triggerStagger(document.getElementById('tab-reservasi'));
});
function switchTab(n){document.querySelectorAll('.tr-tab').forEach(t=>t.classList.remove('active'));document.getElementById('tabBtn'+n.charAt(0).toUpperCase()+n.slice(1)).classList.add('active');document.querySelectorAll('.tab-pane-tr').forEach(p=>p.style.display='none');const el=document.getElementById('tab-'+n);el.style.display='block';triggerStagger(el);}
function triggerStagger(c){c.querySelectorAll('.stagger-item').forEach((item,i)=>{item.style.animation='none';item.offsetHeight;item.style.animation='';item.style.animationDelay=(i*0.1)+'s'});}
function approveReservation(id){activeRsvId=id;document.getElementById('approveNote').value='';modalApprove.show();}
function rejectReservation(id){activeRsvId=id;document.getElementById('rejectAlasan').value='';modalReject.show();}

// ── Approve Reservasi → API ──
function confirmApprove(){
    const fd = new FormData();
    fd.append('id', activeRsvId);
    fetch(BASE_URL+'/api/reservasi.php?action=approve',{method:'POST',body:fd})
    .then(r=>r.json()).then(res=>{
        modalApprove.hide();
        if(res.success){
            const s=document.getElementById('status-'+activeRsvId),a=document.getElementById('actions-'+activeRsvId);
            if(s){s.className='badge-status badge-approved ms-auto';s.textContent='Disetujui';}
            if(a)a.innerHTML='<span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill flex-fill text-center"><i class="bi bi-check-circle me-1"></i>Disetujui</span>';
            showToast('Reservasi disetujui!');
            setTimeout(()=>location.reload(),1500);
        } else {
            showToast(res.message||'Gagal menyetujui reservasi','error');
        }
    }).catch(()=>{modalApprove.hide();showToast('Terjadi kesalahan jaringan','error');});
}

// ── Reject Reservasi → API ──
function confirmReject(){
    const alasan = document.getElementById('rejectAlasan').value.trim();
    if(!alasan){showToast('Alasan penolakan wajib diisi!','error');return;}
    const fd = new FormData();
    fd.append('id', activeRsvId);
    fd.append('alasan', alasan);
    fetch(BASE_URL+'/api/reservasi.php?action=reject',{method:'POST',body:fd})
    .then(r=>r.json()).then(res=>{
        modalReject.hide();
        if(res.success){
            const s=document.getElementById('status-'+activeRsvId),a=document.getElementById('actions-'+activeRsvId);
            if(s){s.className='badge-status badge-rejected ms-auto';s.textContent='Ditolak';}
            if(a)a.innerHTML='<span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill flex-fill text-center"><i class="bi bi-x-circle me-1"></i>Ditolak</span>';
            showToast('Reservasi ditolak.');
            setTimeout(()=>location.reload(),1500);
        } else {
            showToast(res.message||'Gagal menolak reservasi','error');
        }
    }).catch(()=>{modalReject.hide();showToast('Terjadi kesalahan jaringan','error');});
}

function toggleItem(el,name,price){el.classList.toggle('selected');const cb=el.querySelector('.form-check-input');cb.checked=!cb.checked;const idx=selectedItems.findIndex(i=>i.name===name);if(idx>=0)selectedItems.splice(idx,1);else selectedItems.push({name,price,el});updateCalc();}
function updateCalc(){const calc=document.getElementById('calcSection'),s=document.getElementById('startDate'),e=document.getElementById('endDate');let days=1;if(s&&e&&s.value&&e.value)days=Math.max(1,Math.round((new Date(e.value)-new Date(s.value))/86400000));const dl=document.getElementById('durasiLabel');if(dl)dl.textContent=days+' hari';if(selectedItems.length===0){calc.innerHTML='<div class="calc-row"><span class="text-muted">Belum ada barang dipilih</span></div>';return;}let total=0,html='';selectedItems.forEach(item=>{const qty=item.el.querySelector('.qty-input').value||1;const sub=item.price*qty*days;total+=sub;html+='<div class="calc-row"><span>'+item.name+' &times; '+qty+' &times; '+days+' hari</span><span class="mono fw-semibold">Rp '+sub.toLocaleString('id-ID')+'</span></div>';});html+='<div class="calc-row calc-total"><span class="fw-bold">TOTAL</span><span class="calc-val">Rp '+total.toLocaleString('id-ID')+'</span></div>';calc.innerHTML=html;}
function selectPayment(el){document.querySelectorAll('.payment-option').forEach(o=>{o.classList.remove('selected');o.querySelector('input').checked=false});el.classList.add('selected');el.querySelector('input').checked=true;}
function confirmTransaction(){if(selectedItems.length===0){showToast('Pilih minimal 1 barang!','error');return;}const name=document.getElementById('custName').value||'Pelanggan';const body=document.getElementById('confirmBody');const s=document.getElementById('startDate'),e=document.getElementById('endDate');let days=1;if(s&&e&&s.value&&e.value)days=Math.max(1,Math.round((new Date(e.value)-new Date(s.value))/86400000));let total=0,items='';selectedItems.forEach(item=>{const qty=item.el.querySelector('.qty-input').value||1;const sub=item.price*qty*days;total+=sub;items+='<li>'+item.name+' &times; '+qty+'</li>';});body.innerHTML='<p class="fw-semibold">Pelanggan: '+name+'</p><ul>'+items+'</ul><p>Durasi: '+days+' hari</p><p class="mono fw-bold" style="font-size:1.2rem;color:var(--t-mid)">Total: Rp '+total.toLocaleString('id-ID')+'</p>';modalConfirmTrx.show();}

// ── Finalize Walk-in Transaction → API ──
function finalizeTrx(){
    const custName = document.getElementById('custName').value.trim()||'Pelanggan Walk-in';
    const custPhone = document.getElementById('custPhone').value.trim();
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    // Determine metode_bayar from selected payment option
    const paymentLabels = document.querySelectorAll('.payment-option');
    let metode = 'cash';
    paymentLabels.forEach(opt => {
        if(opt.classList.contains('selected')){
            const label = opt.querySelector('.fw-semibold');
            if(label){
                const txt = label.textContent.toLowerCase();
                if(txt.includes('transfer')) metode='transfer';
                else if(txt.includes('wallet')) metode='ewallet';
                else metode='cash';
            }
        }
    });

    // Build items array — match barang by name to get real barang_id
    const itemsPayload = selectedItems.map(item => {
        const qty = parseInt(item.el.querySelector('.qty-input').value)||1;
        const matched = barangPosData.find(b => b.nama === item.name);
        return { barang_id: matched ? matched.id : 0, jumlah: qty };
    });

    const payload = {
        guest_nama: custName,
        guest_telp: custPhone,
        tanggal_mulai: startDate,
        tanggal_selesai: endDate,
        metode_bayar: metode,
        items: itemsPayload
    };

    fetch(BASE_URL+'/api/transaksi.php?action=create_walkin',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    })
    .then(r=>r.json()).then(res=>{
        modalConfirmTrx.hide();
        if(res.success){
            setTimeout(()=>modalSuccessTrx.show(),400);
        } else {
            showToast(res.message||'Gagal membuat transaksi','error');
        }
    }).catch(()=>{modalConfirmTrx.hide();showToast('Terjadi kesalahan jaringan','error');});
}

function resetForm(){selectedItems=[];document.querySelectorAll('.item-checkbox').forEach(el=>{el.classList.remove('selected');el.querySelector('.form-check-input').checked=false});document.getElementById('custName').value='';document.getElementById('custPhone').value='';document.getElementById('custKtp').value='';updateCalc();showToast('Form direset.');}

function processReturn(id){
    document.getElementById('returnTransaksiId').value=id;
    document.getElementById('returnKondisi').value='baik';
    document.getElementById('returnCatatan').value='';
    modalReturn.show();
}

// ── Confirm Return → API ──
function confirmReturn(){
    const transaksiId = document.getElementById('returnTransaksiId').value;
    if(!transaksiId){showToast('ID transaksi tidak ditemukan','error');return;}
    const kondisi = document.getElementById('returnKondisi').value;
    const catatan = document.getElementById('returnCatatan').value.trim();

    const fd = new FormData();
    fd.append('transaksi_id', transaksiId);
    fd.append('tanggal_kembali', new Date().toISOString().slice(0,10));
    fd.append('kondisi_barang', kondisi);
    fd.append('catatan', catatan);

    fetch(BASE_URL+'/api/pengembalian.php?action=create',{method:'POST',body:fd})
    .then(r=>r.json()).then(res=>{
        modalReturn.hide();
        if(res.success){
            setTimeout(()=>modalReturnSuccess.show(),400);
        } else {
            showToast(res.message||'Gagal memproses pengembalian','error');
        }
    }).catch(()=>{modalReturn.hide();showToast('Terjadi kesalahan jaringan','error');});
}

// ── Approve Perpanjangan → API ──
function approvePerpanjangan(id){
    if(!confirm('Setujui perpanjangan ini?')) return;
    fetch(BASE_URL+'/api/perpanjangan.php?action=approve',{
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({id: id})
    }).then(r=>r.json()).then(res=>{
        if(res.success){ showToast('Perpanjangan disetujui!'); setTimeout(()=>location.reload(),1500); }
        else showToast(res.message||'Gagal menyetujui','error');
    }).catch(()=>showToast('Terjadi kesalahan','error'));
}

function rejectPerpanjangan(id){
    const alasan = prompt('Alasan penolakan:');
    if(!alasan) return;
    fetch(BASE_URL+'/api/perpanjangan.php?action=reject',{
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({id: id, alasan: alasan})
    }).then(r=>r.json()).then(res=>{
        if(res.success){ showToast('Perpanjangan ditolak.'); setTimeout(()=>location.reload(),1500); }
        else showToast(res.message||'Gagal menolak','error');
    }).catch(()=>showToast('Terjadi kesalahan','error'));
}

function showToast(msg,type){
    const c=document.getElementById('toastContainer'),t=document.createElement('div');
    const isErr = type==='error';
    t.className='custom-toast';
    if(isErr) t.style.borderLeftColor='#ef4444';
    t.innerHTML='<div style="color:'+(isErr?'#ef4444':'var(--t-light)')+';font-size:1.2rem"><i class="bi '+(isErr?'bi-x-circle-fill':'bi-check-circle-fill')+'"></i></div><div style="font-weight:500;font-size:.9rem">'+msg+'</div>';
    c.appendChild(t);setTimeout(()=>{t.style.animation='slideOutToast .4s forwards';setTimeout(()=>t.remove(),400)},3000);
}
</script>
</body></html>