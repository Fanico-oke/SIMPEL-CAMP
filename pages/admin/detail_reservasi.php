<?php
// pages/admin/detail_reservasi.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . '/login.php'); exit; }
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$page_title = 'Detail Reservasi';
$current_page = 'reservasi';

// Fetch reservation by ID
$rsvId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$reservasi = null;
$detail = [];
$pelanggan = null;

if ($rsvId > 0) {
    $reservasi = Reservasi::getById($rsvId);
    if ($reservasi) {
        $detail = Reservasi::getDetail($rsvId);
        if (!empty($reservasi['user_id'])) {
            $pelanggan = User::getById($reservasi['user_id']);
        }
    }
}

// Fallback values
$rsvKode = $reservasi['kode_reservasi'] ?? $reservasi['id'] ?? 'N/A';
$status = $reservasi['status'] ?? 'pending';
$tglSewa = isset($reservasi['tanggal_mulai']) ? date('d M Y', strtotime($reservasi['tanggal_mulai'])) : '-';
$tglKembali = isset($reservasi['tanggal_selesai']) ? date('d M Y', strtotime($reservasi['tanggal_selesai'])) : '-';
$durasi = 0;
if (!empty($reservasi['tanggal_mulai']) && !empty($reservasi['tanggal_selesai'])) {
    $durasi = max(1, (int)((strtotime($reservasi['tanggal_selesai']) - strtotime($reservasi['tanggal_mulai'])) / 86400));
}
$totalBiaya = $reservasi['total_biaya'] ?? 0;

$plgNama = $pelanggan['nama'] ?? $reservasi['user_nama'] ?? '-';
$plgEmail = $pelanggan['email'] ?? '-';
$plgTelp = $pelanggan['no_telp'] ?? '-';
$plgAlamat = $pelanggan['alamat'] ?? '-';

$statusMap = [
    'pending' => ['status-confirmed', 'Pending', 'bi-hourglass-split'],
    'dikonfirmasi' => ['status-confirmed', 'Dikonfirmasi', 'bi-check-circle-fill'],
    'aktif' => ['status-confirmed', 'Aktif', 'bi-play-circle-fill'],
    'selesai' => ['status-confirmed', 'Selesai', 'bi-check-circle-fill'],
    'batal' => ['status-confirmed', 'Batal', 'bi-x-circle-fill'],
];
$stInfo = $statusMap[$status] ?? $statusMap['pending'];

$adminName = $_SESSION['nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin <?= APP_NAME ?></title>
    <meta name="description" content="Detail informasi reservasi penyewaan peralatan camping">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781677663">
    <style>
        .detail-header{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:1.5rem}
        .btn-back{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:10px;border:1.5px solid var(--border,#e5e7eb);background:var(--bg-card,#fff);color:#374151;font-weight:600;font-size:0.85rem;text-decoration:none;transition:all 0.3s ease}
        .btn-back:hover{border-color:#2D6A4F;color:#2D6A4F;background:rgba(82,183,136,0.05)}
        .detail-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:1.3rem;margin:0}
        .detail-title .rsv-id{color:#2D6A4F}
        .status-badge-lg{padding:6px 18px;border-radius:20px;font-weight:600;font-size:0.85rem;display:inline-flex;align-items:center;gap:6px;margin-left:auto}
        .status-badge-lg.status-confirmed{background:rgba(59,130,246,0.1);color:#2563eb}
        .mini-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
        @media(max-width:768px){.mini-stats{grid-template-columns:1fr}}
        .mini-stat{background:var(--bg-card,#fff);border:1px solid var(--border,#e5e7eb);border-radius:14px;padding:1.25rem;display:flex;align-items:center;gap:12px;transition:box-shadow 0.3s ease}
        .mini-stat:hover{box-shadow:0 4px 20px rgba(0,0,0,0.05)}
        .mini-stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
        .mini-stat-icon.ms-green{background:rgba(82,183,136,0.12);color:#2D6A4F}
        .mini-stat-icon.ms-blue{background:rgba(59,130,246,0.12);color:#3b82f6}
        .mini-stat-icon.ms-gold{background:rgba(212,163,115,0.12);color:#D4A373}
        .mini-stat-label{font-size:0.8rem;color:#6b7280}
        .mini-stat-value{font-weight:700;font-size:0.95rem}
        .detail-section-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:1rem;display:flex;align-items:center;gap:8px;margin-bottom:1rem}
        .detail-section-title i{color:#2D6A4F}
        .customer-row{display:flex;padding:10px 0;border-bottom:1px solid var(--border,#f3f4f6);font-size:0.9rem}
        .customer-row:last-child{border-bottom:none}
        .customer-row .c-label{width:120px;color:#6b7280;font-weight:500;flex-shrink:0}
        .customer-row .c-value{font-weight:600;color:#1f2937}
        .items-table{width:100%;font-size:0.9rem}
        .items-table thead th{background:#f8faf9;padding:10px 12px;font-weight:600;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:2px solid var(--border,#e5e7eb)}
        .items-table tbody td{padding:12px;border-bottom:1px solid var(--border,#f3f4f6)}
        .items-table .summary-row td{border-bottom:none;padding:6px 12px}
        .items-table .total-row td{border-top:2px solid #2D6A4F;font-weight:700;font-size:1rem;color:#2D6A4F}
        .pay-info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border,#f3f4f6);font-size:0.9rem}
        .pay-info-row:last-child{border-bottom:none}
        .pay-info-row .pi-label{color:#6b7280}
        .pay-info-row .pi-value{font-weight:600}
        .timeline{position:relative;padding-left:30px}
        .timeline::before{content:'';position:absolute;left:11px;top:8px;bottom:8px;width:2px;background:var(--border,#e5e7eb)}
        .timeline-item{position:relative;padding-bottom:1.5rem}
        .timeline-item:last-child{padding-bottom:0}
        .timeline-dot{position:absolute;left:-24px;top:4px;width:16px;height:16px;border-radius:50%;border:3px solid;background:var(--bg-card,#fff);z-index:1}
        .timeline-dot.dot-completed{border-color:#059669;background:#059669}
        .timeline-dot.dot-current{border-color:#3b82f6;background:#fff;box-shadow:0 0 0 4px rgba(59,130,246,0.2)}
        .timeline-dot.dot-upcoming{border-color:#d1d5db;background:#fff}
        .timeline-title{font-weight:700;font-size:0.9rem;margin-bottom:2px}
        .timeline-title.completed{color:#059669}
        .timeline-title.current{color:#3b82f6}
        .timeline-title.upcoming{color:#9ca3af}
        .timeline-time{font-size:0.8rem;color:#6b7280}
        .action-buttons{display:flex;gap:12px;flex-wrap:wrap;margin-top:1.5rem}
        .btn-action{padding:10px 24px;border-radius:10px;font-weight:600;font-size:0.9rem;border:none;display:inline-flex;align-items:center;gap:8px;transition:all 0.3s ease;cursor:pointer}
        .btn-action:hover{transform:translateY(-2px)}
        .btn-approve{background:linear-gradient(135deg,#059669,#10b981);color:#fff}
        .btn-approve:hover{box-shadow:0 6px 20px rgba(5,150,105,0.3);color:#fff}
        .btn-reject{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff}
        .btn-reject:hover{box-shadow:0 6px 20px rgba(220,38,38,0.3);color:#fff}
        .btn-return{background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff}
        .btn-return:hover{box-shadow:0 6px 20px rgba(37,99,235,0.3);color:#fff}
        .btn-nota{background:var(--bg-card,#fff);border:1.5px solid var(--border,#e5e7eb);color:#374151}
        .btn-nota:hover{border-color:#2D6A4F;color:#2D6A4F}
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


        <!-- Content -->
        <div class="admin-content">

            <div class="detail-header">
                <a href="<?= BASE_URL ?>/pages/admin/reservasi.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <h2 class="detail-title">Detail Reservasi <span class="rsv-id">#<?= htmlspecialchars($rsvKode) ?></span></h2>
                <span class="status-badge-lg <?= $stInfo[0] ?>">
                    <i class="bi <?= $stInfo[2] ?>"></i> <?= htmlspecialchars($stInfo[1]) ?>
                </span>
            </div>

            <div class="mini-stats">
                <div class="mini-stat">
                    <div class="mini-stat-icon ms-green"><i class="bi bi-calendar-range"></i></div>
                    <div>
                        <div class="mini-stat-label">Tanggal Sewa</div>
                        <div class="mini-stat-value"><?= $tglSewa ?> – <?= $tglKembali ?></div>
                    </div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-icon ms-blue"><i class="bi bi-clock"></i></div>
                    <div>
                        <div class="mini-stat-label">Durasi</div>
                        <div class="mini-stat-value"><?= $durasi ?> Hari</div>
                    </div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-icon ms-gold"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <div class="mini-stat-label">Total</div>
                        <div class="mini-stat-value mono-font" style="color:#2D6A4F;">Rp <?= number_format($totalBiaya, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- LEFT COLUMN -->
                <div class="col-lg-8">
                    <!-- Customer Info -->
                    <div class="sc-card p-4 mb-4">
                        <div class="detail-section-title"><i class="bi bi-person"></i> Informasi Pelanggan</div>
                        <div class="customer-row"><span class="c-label">Nama</span><span class="c-value"><?= htmlspecialchars($plgNama) ?></span></div>
                        <div class="customer-row"><span class="c-label">Email</span><span class="c-value"><?= htmlspecialchars($plgEmail) ?></span></div>
                        <div class="customer-row"><span class="c-label">No. Telp</span><span class="c-value mono-font"><?= htmlspecialchars($plgTelp) ?></span></div>
                        <div class="customer-row"><span class="c-label">Alamat</span><span class="c-value"><?= htmlspecialchars($plgAlamat) ?></span></div>
                    </div>

                    <!-- Items Table -->
                    <div class="sc-card p-4">
                        <div class="detail-section-title"><i class="bi bi-box-seam"></i> Daftar Barang Sewa</div>
                        <div class="table-responsive">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>No</th><th>Barang</th><th class="text-center">Jumlah</th>
                                        <th class="text-end">Harga/Hari</th><th class="text-center">Durasi</th><th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($detail)):
                                        $subtotal = 0;
                                        foreach ($detail as $idx => $d):
                                            $harga = $d['harga_satuan'] ?? 0;
                                            $jumlah = $d['jumlah'] ?? 1;
                                            $sub = $harga * $jumlah * $durasi;
                                            $subtotal += $sub;
                                    ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td class="fw-medium"><?= htmlspecialchars($d['barang_nama'] ?? '-') ?></td>
                                        <td class="text-center"><?= (int)$jumlah ?></td>
                                        <td class="text-end mono-font">Rp <?= number_format($harga, 0, ',', '.') ?></td>
                                        <td class="text-center"><?= $durasi ?> hari</td>
                                        <td class="text-end mono-font fw-medium">Rp <?= number_format($sub, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="summary-row">
                                        <td colspan="5" class="text-end text-secondary">Subtotal</td>
                                        <td class="text-end mono-font fw-medium">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                    </tr>
                                    <tr class="total-row">
                                        <td colspan="5" class="text-end">Total</td>
                                        <td class="text-end mono-font">Rp <?= number_format($totalBiaya, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada detail barang</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="col-lg-4">
                    <!-- Payment Info -->
                    <div class="sc-card p-4 mb-4">
                        <div class="detail-section-title"><i class="bi bi-credit-card"></i> Informasi Pembayaran</div>
                        <div class="pay-info-row">
                            <span class="pi-label">Metode</span>
                            <span class="pi-value">
                                <?php
                                    // Payment method comes from Pembayaran table, not Reservasi
                                    $metode = '-';
                                    if (!empty($reservasi['id'])) {
                                        $dbPay = Database::getInstance();
                                        $stmtPay = $dbPay->prepare("SELECT p.metode FROM pembayaran p JOIN transaksi t ON p.transaksi_id = t.id WHERE t.reservasi_id = ? ORDER BY p.tanggal_bayar DESC LIMIT 1");
                                        $stmtPay->execute([$reservasi['id']]);
                                        $payRow = $stmtPay->fetch();
                                        if ($payRow) $metode = ucfirst($payRow['metode']);
                                    }
                                ?>
                                <?= htmlspecialchars($metode) ?>
                            </span>
                        </div>
                        <div class="pay-info-row">
                            <span class="pi-label">Status</span>
                            <span class="pi-value">
                                <span class="badge bg-<?= ($status == 'selesai' || $status == 'dikonfirmasi') ? 'success' : 'warning' ?> bg-opacity-10 text-<?= ($status == 'selesai' || $status == 'dikonfirmasi') ? 'success' : 'warning' ?>">
                                    <i class="bi bi-<?= ($status == 'selesai' || $status == 'dikonfirmasi') ? 'check-circle-fill' : 'hourglass-split' ?> me-1"></i><?= ($status == 'selesai' || $status == 'dikonfirmasi') ? 'Lunas' : 'Menunggu' ?>
                                </span>
                            </span>
                        </div>
                        <div class="pay-info-row">
                            <span class="pi-label">Tgl Reservasi</span>
                            <span class="pi-value" style="font-size:0.85rem;"><?= isset($reservasi['created_at']) ? date('d M Y, H:i', strtotime($reservasi['created_at'])) : '-' ?></span>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="sc-card p-4">
                        <div class="detail-section-title"><i class="bi bi-clock-history"></i> Timeline Reservasi</div>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-dot dot-completed"></div>
                                <div class="timeline-title completed"><i class="bi bi-check-circle-fill me-1"></i>Reservasi Dibuat</div>
                                <div class="timeline-time"><?= isset($reservasi['created_at']) ? date('d M Y, H:i', strtotime($reservasi['created_at'])) : '-' ?></div>
                            </div>
                            <?php if (in_array($status, ['dikonfirmasi', 'aktif', 'selesai'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot dot-completed"></div>
                                <div class="timeline-title completed"><i class="bi bi-check-circle-fill me-1"></i>Dikonfirmasi</div>
                                <div class="timeline-time"><?= isset($reservasi['updated_at']) ? date('d M Y, H:i', strtotime($reservasi['updated_at'])) : '-' ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if ($status == 'aktif'): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot dot-current"></div>
                                <div class="timeline-title current"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;vertical-align:middle;"></i>Barang Diambil</div>
                                <div class="timeline-time">Sedang disewa</div>
                            </div>
                            <?php endif; ?>
                            <?php if ($status == 'selesai'): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot dot-completed"></div>
                                <div class="timeline-title completed"><i class="bi bi-check-circle-fill me-1"></i>Dikembalikan</div>
                                <div class="timeline-time">Selesai</div>
                            </div>
                            <?php elseif ($status != 'batal'): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot dot-upcoming"></div>
                                <div class="timeline-title upcoming"><i class="bi bi-circle me-1" style="font-size:0.6rem;vertical-align:middle;"></i>Dikembalikan</div>
                                <div class="timeline-time">Belum</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <?php if ($status == 'pending'): ?>
                <button class="btn-action btn-approve" onclick="confirmAction('approve')"><i class="bi bi-check-circle"></i> Approve</button>
                <button class="btn-action btn-reject" onclick="confirmAction('reject')"><i class="bi bi-x-circle"></i> Reject</button>
                <?php endif; ?>
                <?php if ($status == 'dikonfirmasi' || $status == 'disetujui'): ?>
                <button class="btn-action btn-approve" onclick="confirmAction('activate')"><i class="bi bi-cash-coin"></i> Lunas & Barang Diambil</button>
                <?php endif; ?>
                <?php if ($status == 'aktif'): ?>
                <button class="btn-action btn-return" onclick="confirmAction('return')"><i class="bi bi-box-arrow-in-left"></i> Tandai Dikembalikan</button>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/pages/admin/transaksi.php" class="btn-action btn-nota"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmAction(action) {
    const messages = {
        approve: 'Apakah Anda yakin ingin meng-approve reservasi #<?= htmlspecialchars($rsvKode) ?>?',
        reject: 'Apakah Anda yakin ingin menolak reservasi #<?= htmlspecialchars($rsvKode) ?>?',
        return: 'Tandai reservasi #<?= htmlspecialchars($rsvKode) ?> sebagai dikembalikan?',
        activate: 'Konfirmasi pembayaran lunas dan barang telah diambil oleh pelanggan?'
    };

    if (!confirm(messages[action])) return;

    let body = { id: <?= (int)$rsvId ?> };

    if (action === 'reject') {
        const alasan = prompt('Masukkan alasan penolakan:');
        if (!alasan) { alert('Alasan penolakan wajib diisi'); return; }
        body.alasan = alasan;
    }

    const apiAction = (action === 'return') ? 'complete' : action;

    fetch('<?= BASE_URL ?>/api/reservasi.php?action=' + apiAction, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Berhasil!');
            location.reload();
        } else {
            alert(data.message || 'Gagal memproses aksi');
        }
    })
    .catch(() => alert('Terjadi kesalahan jaringan'));
}
</script>
</body>
</html>
