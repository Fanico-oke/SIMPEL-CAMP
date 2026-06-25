<?php
// pages/pelanggan/status_perpanjangan.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';
require_once dirname(__DIR__, 2) . '/classes/Perpanjangan.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php'); exit;
}

$page_title = 'Status Perpanjangan';

// Ambil semua reservasi user, lalu ambil perpanjangan dari masing-masing
$userReservasi = Reservasi::getByUser($_SESSION['user_id']);
$extensions = [];
foreach ($userReservasi as $r) {
    $perps = Perpanjangan::getByReservasi($r['id']);
    foreach ($perps as $p) {
        $statusMap = ['pending' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
        $extensions[] = [
            'rsv_id' => $r['kode_reservasi'],
            'tgl_awal' => date('d M Y', strtotime($p['tanggal_lama'])),
            'tgl_baru' => date('d M Y', strtotime($p['tanggal_baru'])),
            'biaya' => $p['biaya_tambahan'],
            'status' => $statusMap[$p['status']] ?? ucfirst($p['status']),
            'diajukan_pada' => date('d M Y, H:i', strtotime($p['created_at'])) . ' WIB',
            'diproses_pada' => $p['updated_at'] ? date('d M Y, H:i', strtotime($p['updated_at'])) . ' WIB' : null,
            'alasan_tolak' => $p['alasan_tolak'] ?? null
        ];
    }
}

$status_colors = ['Menunggu' => 'warning', 'Disetujui' => 'success', 'Ditolak' => 'danger'];
$status_icons = ['Menunggu' => 'bi-hourglass-split', 'Disetujui' => 'bi-check-circle', 'Ditolak' => 'bi-x-circle'];
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
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Status Perpanjangan</h2>
                <a href="<?= BASE_URL ?>/pages/pelanggan/perpanjangan.php" class="btn btn-sc-primary"><i class="bi bi-plus-circle me-2"></i>Ajukan Baru</a>
            </div>
            
            <?php foreach($extensions as $ext): 
                $color = $status_colors[$ext['status']];
                $icon = $status_icons[$ext['status']];
            ?>
            <div class="sc-card mb-4 overflow-hidden border-0" style="box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <!-- Card Header -->
                <div class="bg-<?= $color ?> bg-opacity-10 px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary small d-block mb-1">ID Reservasi</span>
                        <h5 class="fw-bold mb-0">#<?= $ext['rsv_id'] ?></h5>
                    </div>
                    <span class="badge bg-<?= $color ?> px-3 py-2 fs-6"><i class="bi <?= $icon ?> me-2"></i><?= $ext['status'] ?></span>
                </div>
                
                <!-- Card Body -->
                <div class="p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-sm-4">
                            <span class="text-secondary small d-block mb-1">Tanggal Kembali Awal</span>
                            <span class="fw-medium"><i class="bi bi-calendar me-2 text-secondary"></i><?= $ext['tgl_awal'] ?></span>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-secondary small d-block mb-1">Permintaan Tanggal Baru</span>
                            <span class="fw-bold" style="color:var(--primary);"><i class="bi bi-calendar-event me-2"></i><?= $ext['tgl_baru'] ?></span>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-secondary small d-block mb-1">Biaya Tambahan</span>
                            <span class="fw-bold mono-font"><i class="bi bi-cash me-2 text-success"></i>Rp <?= number_format($ext['biaya'],0,',','.') ?></span>
                        </div>
                    </div>
                    
                    <hr class="text-muted opacity-25">
                    
                    <!-- Timeline -->
                    <div class="position-relative mt-4 ps-4">
                        <!-- Vertical line -->
                        <div class="position-absolute top-0 bottom-0 start-0 ms-2 bg-light border-start border-2 border-<?= $color ?>"></div>
                        
                        <!-- Step 1 -->
                        <div class="position-relative mb-4">
                            <div class="position-absolute top-0 start-0 translate-middle-x rounded-circle bg-<?= $color ?> border border-white border-2" style="width:14px;height:14px;margin-left:-16px;"></div>
                            <h6 class="fw-bold mb-1">Pengajuan Dikirim</h6>
                            <p class="text-secondary small mb-0"><?= $ext['diajukan_pada'] ?></p>
                        </div>
                        
                        <!-- Step 2 -->
                        <div class="position-relative">
                            <div class="position-absolute top-0 start-0 translate-middle-x rounded-circle <?= $ext['status'] == 'Menunggu' ? 'bg-warning' : 'bg-'.$color ?> border border-white border-2" style="width:14px;height:14px;margin-left:-16px;"></div>
                            
                            <?php if($ext['status'] == 'Menunggu'): ?>
                                <h6 class="fw-bold mb-1 text-warning">Menunggu Konfirmasi Admin</h6>
                                <p class="text-secondary small mb-0">Pengajuan sedang ditinjau. Silakan cek kembali secara berkala.</p>
                            <?php else: ?>
                                <h6 class="fw-bold mb-1 text-<?= $color ?>"><?= $ext['status'] == 'Disetujui' ? 'Perpanjangan Disetujui' : 'Perpanjangan Ditolak' ?></h6>
                                <p class="text-secondary small mb-1"><?= $ext['diproses_pada'] ?></p>
                                
                                <?php if($ext['status'] == 'Ditolak'): ?>
                                <div class="bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded p-3 mt-2">
                                    <span class="fw-bold text-danger d-block mb-1">Alasan Penolakan:</span>
                                    <span class="text-danger small"><?= $ext['alasan_tolak'] ?></span>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
