<?php
// pages/pelanggan/reservasi.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/Kategori.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Buat Reservasi';
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// Load available items for reservation form
$availableBarang = Barang::getAll(['status' => 'tersedia', 'limit' => 50]);
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
    <h2 class="fw-bold mb-2">Buat Reservasi</h2>
    <p class="text-secondary mb-4">Tentukan jadwal sewa dan konfirmasi pesanan Anda.</p>

    <!-- Stepper -->
    <div class="d-flex justify-content-center mb-5">
        <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width:36px;height:36px;background:var(--success);"><i class="bi bi-check"></i></div>
                <span class="fw-medium text-success d-none d-md-inline">Pilih Barang</span>
            </div>
            <div style="width:60px;height:3px;background:var(--primary);"></div>
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width:36px;height:36px;background:var(--primary);">2</div>
                <span class="fw-bold" style="color:var(--primary);">Atur Jadwal</span>
            </div>
            <div style="width:60px;height:3px;background:var(--border);"></div>
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;background:var(--border);color:var(--text-secondary);">3</div>
                <span class="text-secondary d-none d-md-inline">Konfirmasi</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Form -->
        <div class="col-lg-8">
            <div class="sc-card p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-box-seam me-2"></i>Barang yang Disewa</h5>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="itemsTable">
                        <thead class="table-light"><tr><th>Barang</th><th>Harga/Hari</th><th style="width:100px">Jumlah</th><th>Subtotal</th></tr></thead>
                        <tbody>
                            <tr data-harga="50000" data-barang-id="1">
                                <td><div class="d-flex align-items-center gap-2"><img src="https://images.unsplash.com/photo-1537225228614-56cc3556d7ed?w=60&q=80" class="rounded" width="48" height="48" style="object-fit:cover;"><span class="fw-medium">Tenda Dome Eiger 4P</span></div></td>
                                <td class="mono-font">Rp 50.000</td>
                                <td><input type="number" class="form-control form-control-sm item-qty" value="1" min="1" max="5"></td>
                                <td class="mono-font fw-bold item-subtotal">Rp 50.000</td>
                            </tr>
                            <tr data-harga="35000" data-barang-id="2">
                                <td><div class="d-flex align-items-center gap-2"><img src="https://images.unsplash.com/photo-1622260614153-03223fb72052?w=60&q=80" class="rounded" width="48" height="48" style="object-fit:cover;"><span class="fw-medium">Carrier Consina 60L</span></div></td>
                                <td class="mono-font">Rp 35.000</td>
                                <td><input type="number" class="form-control form-control-sm item-qty" value="1" min="1" max="8"></td>
                                <td class="mono-font fw-bold item-subtotal">Rp 35.000</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sc-card p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-calendar3 me-2"></i>Jadwal Penyewaan</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="tanggal_mulai">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="tanggal_selesai">
                    </div>
                </div>
                <div class="mt-3 p-3 rounded" style="background:rgba(27,67,50,0.05);">
                    <i class="bi bi-info-circle me-2 text-primary-theme"></i>Durasi sewa: <strong id="durasiText">0 hari</strong>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="col-lg-4">
            <div class="sc-card p-4 position-sticky" style="top:100px;border:2px solid var(--primary);">
                <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Ringkasan Biaya</h5>
                <div id="summaryItems">
                    <div class="d-flex justify-content-between mb-2"><span>Tenda Dome Eiger 4P</span><span class="mono-font">Rp 50.000</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Carrier Consina 60L</span><span class="mono-font">Rp 35.000</span></div>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2 text-secondary"><span>Durasi</span><span id="summaryDurasi">0 hari</span></div>
                <div class="d-flex justify-content-between mb-2 text-secondary"><span>Subtotal/Hari</span><span class="mono-font" id="summarySubHari">Rp 85.000</span></div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5">Total</span>
                    <span class="fw-bold fs-4 mono-font" style="color:var(--primary);" id="grandTotal">Rp 0</span>
                </div>
                <button class="btn btn-sc-primary w-100 btn-lg mt-4" id="btnKonfirmasi">
                    <i class="bi bi-check-circle me-2"></i>Konfirmasi Reservasi
                </button>
                <p class="text-center text-secondary small mt-2 mb-0">Dengan mengkonfirmasi, Anda menyetujui syarat & ketentuan penyewaan.</p>
            </div>
        </div>
    </div>
</div>

        </div>
    </div>
</div>
<!-- Toast container -->
<div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:10001;display:flex;flex-direction:column;gap:8px;"></div>

<!-- Success Overlay -->
<div id="successOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:24px;padding:2.5rem 2rem;text-align:center;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
        <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#2D6A4F,#52B788);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
            <i class="bi bi-check-lg" style="font-size:2rem;color:#fff;"></i>
        </div>
        <h4 style="font-family:'Outfit',sans-serif;font-weight:800;margin-bottom:0.5rem;">Reservasi Berhasil! 🎉</h4>
        <p style="color:#6B7280;margin-bottom:1rem;">Reservasi Anda telah berhasil dibuat. Silakan tunggu konfirmasi admin.</p>
        <div id="rsvCodeDisplay" style="font-family:'JetBrains Mono',monospace;background:rgba(82,183,136,0.08);border-radius:10px;padding:0.6rem 1.5rem;font-weight:600;color:#2D6A4F;display:inline-block;margin-bottom:1.5rem;font-size:1.1rem;letter-spacing:1px;">-</div>
        <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
            <a href="<?= BASE_URL ?>/pages/pelanggan/transaksi.php" class="btn btn-sc-primary"><i class="bi bi-list-check me-1"></i>Lihat Transaksi</a>
            <a href="<?= BASE_URL ?>/pages/pelanggan/katalog.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/reservasi.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';

document.getElementById('btnKonfirmasi').addEventListener('click', function() {
    const btn = this;
    const tanggalMulai = document.getElementById('tanggal_mulai').value;
    const tanggalSelesai = document.getElementById('tanggal_selesai').value;

    if (!tanggalMulai || !tanggalSelesai) {
        showToast('Silakan isi tanggal mulai dan selesai', 'warning');
        return;
    }
    if (new Date(tanggalSelesai) <= new Date(tanggalMulai)) {
        showToast('Tanggal selesai harus setelah tanggal mulai', 'warning');
        return;
    }

    // Collect items from the table
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    const items = [];
    rows.forEach(function(row) {
        const barangId = parseInt(row.getAttribute('data-barang-id'));
        const qtyInput = row.querySelector('.item-qty');
        const jumlah = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
        if (barangId > 0) {
            items.push({ barang_id: barangId, jumlah: jumlah });
        }
    });

    if (items.length === 0) {
        showToast('Tidak ada barang yang dipilih', 'warning');
        return;
    }

    const origText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
    btn.disabled = true;

    fetch(BASE_URL + '/api/reservasi.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tanggal_mulai: tanggalMulai,
            tanggal_selesai: tanggalSelesai,
            items: items,
            catatan: ''
        })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        btn.innerHTML = origText;
        btn.disabled = false;
        if (data.success) {
            document.getElementById('rsvCodeDisplay').textContent = data.data.kode_reservasi || '-';
            const overlay = document.getElementById('successOverlay');
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        } else {
            showToast(data.message || 'Gagal membuat reservasi', 'warning');
        }
    })
    .catch(function(err) {
        btn.innerHTML = origText;
        btn.disabled = false;
        showToast('Terjadi kesalahan jaringan. Silakan coba lagi.', 'warning');
        console.error('submitReservasi error:', err);
    });
});

function showToast(message, type) {
    var container = document.getElementById('toastContainer');
    var toast = document.createElement('div');
    toast.style.cssText = 'background:' + (type === 'warning' ? '#d97706' : '#2D6A4F') + ';color:#fff;padding:12px 20px;border-radius:12px;font-size:0.88rem;font-weight:600;box-shadow:0 8px 25px rgba(0,0,0,0.2);animation:fadeIn 0.3s ease;display:flex;align-items:center;gap:8px;';
    toast.innerHTML = '<i class="bi ' + (type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill') + '"></i>' + message;
    container.appendChild(toast);
    setTimeout(function() { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(function() { toast.remove(); }, 300); }, 3000);
}
</script>
</body>
</html>
