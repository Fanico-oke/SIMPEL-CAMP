<?php
// pages/pelanggan/riwayat.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php'); exit;
}

$page_title = 'Riwayat Penyewaan';

// Ambil semua reservasi user
$allReservasi = Reservasi::getByUser($_SESSION['user_id']);
$riwayat = [];
$statusDbToDisplay = ['pending'=>'Pending','disetujui'=>'Disetujui','aktif'=>'Aktif','selesai'=>'Selesai','batal'=>'Batal','ditolak'=>'Ditolak'];
foreach ($allReservasi as $r) {
    $details = Reservasi::getDetail($r['id']);
    $barangNames = array_map(function($d) { return $d['barang_nama']; }, $details);
    $riwayat[] = [
        'id' => $r['kode_reservasi'],
        'sewa' => date('d M Y', strtotime($r['tanggal_mulai'])),
        'kembali' => $r['tanggal_selesai'] ? date('d M Y', strtotime($r['tanggal_selesai'])) : '—',
        'barang' => implode(', ', $barangNames),
        'total' => $r['total_biaya'],
        'status' => $statusDbToDisplay[$r['status']] ?? ucfirst($r['status'])
    ];
}
$status_map = ['Aktif'=>'info','Selesai'=>'success','Batal'=>'danger','Pending'=>'warning','Disetujui'=>'primary','Ditolak'=>'danger'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">
</head>
<body>
<div class="pelanggan-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>
    <div class="pelanggan-main">
        <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>
        <div class="pelanggan-content">

<div class="container pb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h2 class="fw-bold mb-0">Riwayat Penyewaan</h2>
        <select class="form-select" style="width:auto;" id="filterStatus">
            <option value="">Semua Status</option>
            <option>Pending</option><option>Aktif</option><option>Selesai</option><option>Batal</option>
        </select>
    </div>

    <div class="sc-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>No</th><th>ID Reservasi</th><th>Tgl Sewa</th><th>Tgl Kembali</th><th>Barang</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach($riwayat as $i => $r): $sc = $status_map[$r['status']]; ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td class="fw-medium">#<?= $r['id'] ?></td>
                        <td><?= $r['sewa'] ?></td>
                        <td><?= $r['kembali'] ?></td>
                        <td><?= $r['barang'] ?></td>
                        <td class="mono-font fw-bold">Rp <?= number_format($r['total'],0,',','.') ?></td>
                        <td><span class="badge bg-<?= $sc ?> bg-opacity-10 text-<?= $sc ?> px-2 py-1"><?= $r['status'] ?></span></td>
                        <td><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailModal-<?= $i ?>"><i class="bi bi-eye"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail Modals -->
<?php foreach($riwayat as $i => $r): ?>
<div class="modal fade" id="detailModal-<?= $i ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Detail #<?= $r['id'] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><span class="text-secondary small">Tanggal Sewa</span><p class="fw-medium mb-0"><?= $r['sewa'] ?></p></div>
                    <div class="col-6"><span class="text-secondary small">Tanggal Kembali</span><p class="fw-medium mb-0"><?= $r['kembali'] ?></p></div>
                    <div class="col-12"><span class="text-secondary small">Barang</span><p class="fw-medium mb-0"><?= $r['barang'] ?></p></div>
                    <div class="col-6"><span class="text-secondary small">Total Biaya</span><p class="fw-bold mono-font mb-0" style="color:var(--primary);">Rp <?= number_format($r['total'],0,',','.') ?></p></div>
                    <div class="col-6"><span class="text-secondary small">Status</span><p class="mb-0"><span class="badge bg-<?= $status_map[$r['status']] ?> bg-opacity-10 text-<?= $status_map[$r['status']] ?>"><?= $r['status'] ?></span></p></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
