<?php
// pages/pelanggan/perpanjangan.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php'); exit;
}

$page_title = 'Ajukan Perpanjangan';
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// Ambil reservasi aktif user
$activeReservasi = Reservasi::getByUser($_SESSION['user_id'], 'aktif');
$active_reservations = [];
foreach ($activeReservasi as $r) {
    $details = Reservasi::getDetail($r['id']);
    $barangNames = array_map(function($d) { return $d['barang_nama']; }, $details);
    $active_reservations[] = [
        'id' => $r['kode_reservasi'],
        'reservasi_id' => $r['id'],
        'barang' => implode(', ', $barangNames),
        'kembali' => $r['tanggal_selesai']
    ];
}
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
    <style>
    .payment-opt{display:flex;align-items:center;gap:12px;padding:14px 18px;border:1.5px solid #e0e0e0;border-radius:12px;cursor:pointer;transition:all .3s;background:#fff}
    .payment-opt:hover{border-color:#52B788}
    .payment-opt.selected{border-color:#52B788;background:rgba(82,183,136,0.04);box-shadow:0 2px 8px rgba(82,183,136,0.1)}
    .payment-opt input[type="radio"]{accent-color:#2D6A4F;width:18px;height:18px;flex-shrink:0}
    .pay-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
    </style>
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
            <h2 class="fw-bold mb-2">Ajukan Perpanjangan Sewa</h2>
            <p class="text-secondary mb-4">Perpanjang masa sewa peralatan Anda sebelum masa sewa berakhir untuk menghindari denda keterlambatan.</p>

            <div class="sc-card p-4">
                <form id="perpanjanganForm" onsubmit="return submitPerpanjangan(event)">
                    <div class="mb-4">
                        <label class="form-label fw-medium">Pilih Reservasi Aktif</label>
                        <select class="form-select" id="reservasi_id" required>
                            <option value="">-- Pilih Reservasi --</option>
                            <?php foreach($active_reservations as $r): ?>
                            <option value="<?= $r['reservasi_id'] ?>" data-kembali="<?= $r['kembali'] ?>">#<?= $r['id'] ?> - <?= $r['barang'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tanggal Kembali Saat Ini</label>
                            <input type="date" class="form-control bg-light" id="tgl_kembali_saat_ini" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tanggal Baru yang Diminta</label>
                            <input type="date" class="form-control" id="tgl_baru" required>
                        </div>
                    </div>

                    <div class="sc-card p-3 mb-4 bg-light border-0" id="estimasiBox" style="display: none;">
                        <h6 class="fw-bold mb-3"><i class="bi bi-calculator me-2"></i>Estimasi Biaya Tambahan</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Tambahan Hari</span>
                            <span class="fw-medium" id="tambahanHari">0 Hari</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Biaya Tambahan</span>
                            <span class="fs-4 fw-bold mono-font" style="color:var(--primary);" id="biayaTambahan">Rp 0</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Alasan Perpanjangan</label>
                        <textarea class="form-control" rows="3" id="alasan" placeholder="Contoh: Terkendala cuaca buruk di gunung, butuh tambahan 2 hari."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium"><i class="bi bi-credit-card me-1"></i>Metode Pembayaran</label>
                        <div class="d-flex flex-column gap-2 mt-2">
                            <label class="payment-opt selected" onclick="selectPay(this)">
                                <input type="radio" name="metode_bayar" value="transfer" checked>
                                <div class="pay-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6"><i class="bi bi-bank"></i></div>
                                <div><div class="fw-semibold" style="font-size:.9rem">Transfer Bank</div><div style="font-size:.78rem;color:#9ca3af">BCA / Mandiri / BRI</div></div>
                            </label>
                            <label class="payment-opt" onclick="selectPay(this)">
                                <input type="radio" name="metode_bayar" value="ewallet">
                                <div class="pay-icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6"><i class="bi bi-phone"></i></div>
                                <div><div class="fw-semibold" style="font-size:.9rem">E-Wallet</div><div style="font-size:.78rem;color:#9ca3af">OVO / GoPay / DANA</div></div>
                            </label>
                            <label class="payment-opt" onclick="selectPay(this)">
                                <input type="radio" name="metode_bayar" value="qris">
                                <div class="pay-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b"><i class="bi bi-qr-code"></i></div>
                                <div><div class="fw-semibold" style="font-size:.9rem">QRIS</div><div style="font-size:.78rem;color:#9ca3af">Scan QR untuk pembayaran</div></div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-sc-primary w-100 btn-lg"><i class="bi bi-send me-2"></i>Ajukan Perpanjangan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

document.addEventListener('DOMContentLoaded', function() {
    const reservasiSelect = document.getElementById('reservasi_id');
    const tglKembaliSaatIni = document.getElementById('tgl_kembali_saat_ini');
    const tglBaru = document.getElementById('tgl_baru');
    const estimasiBox = document.getElementById('estimasiBox');
    const tambahanHariEl = document.getElementById('tambahanHari');
    const biayaTambahanEl = document.getElementById('biayaTambahan');

    // Tarif per hari per reservasi (dihitung dari data reservasi aktif)
    const tarifPerHari = <?php
        $tarif_map = [];
        foreach ($active_reservations as $r) {
            // Calculate total daily rate from reservation details
            $details = Reservasi::getDetail($r['reservasi_id']);
            $daily_total = 0;
            foreach ($details as $d) {
                $daily_total += (int)($d['harga_satuan'] ?? 0);
            }
            $tarif_map[$r['reservasi_id']] = $daily_total;
        }
        echo json_encode($tarif_map, JSON_FORCE_OBJECT);
    ?>;

    reservasiSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            tglKembaliSaatIni.value = selectedOption.getAttribute('data-kembali');
            tglBaru.min = tglKembaliSaatIni.value;
            calculateExtension();
        } else {
            tglKembaliSaatIni.value = '';
            estimasiBox.style.display = 'none';
        }
    });

    tglBaru.addEventListener('change', calculateExtension);

    function calculateExtension() {
        if (reservasiSelect.value && tglBaru.value && tglKembaliSaatIni.value) {
            const d1 = new Date(tglKembaliSaatIni.value);
            const d2 = new Date(tglBaru.value);
            const days = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));

            if (days > 0) {
                const tarif = tarifPerHari[reservasiSelect.value] || 0;
                const total = tarif * days;
                
                tambahanHariEl.textContent = days + ' Hari';
                biayaTambahanEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
                estimasiBox.style.display = 'block';
            } else {
                estimasiBox.style.display = 'none';
            }
        } else {
            estimasiBox.style.display = 'none';
        }
    }
});

function selectPay(el){document.querySelectorAll('.payment-opt').forEach(o=>{o.classList.remove('selected');});el.classList.add('selected');el.querySelector('input').checked=true;}

function submitPerpanjangan(e) {
    e.preventDefault();

    const reservasiId = document.getElementById('reservasi_id').value;
    const tglBaru = document.getElementById('tgl_baru').value;
    const alasan = document.getElementById('alasan').value;
    const metode = document.querySelector('input[name="metode_bayar"]:checked');

    if (!reservasiId) { alert('Silakan pilih reservasi aktif'); return false; }
    if (!tglBaru) { alert('Silakan isi tanggal baru'); return false; }
    if (!metode) { alert('Silakan pilih metode pembayaran'); return false; }

    const btn = e.target.querySelector('button[type="submit"]');
    const origText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
    btn.disabled = true;

    fetch(BASE_URL + '/api/perpanjangan.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            reservasi_id: parseInt(reservasiId),
            tanggal_baru: tglBaru,
            alasan: alasan,
            metode_bayar: metode.value
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = origText;
        btn.disabled = false;
        if (data.success) {
            alert('Permintaan perpanjangan berhasil dikirim! Tambahan ' + (data.data.tambahan_hari || 0) + ' hari dengan biaya Rp ' + (data.data.biaya_tambahan || 0).toLocaleString('id-ID'));
            window.location.href = BASE_URL + '/pages/pelanggan/transaksi.php';
        } else {
            alert(data.message || 'Gagal mengajukan perpanjangan');
        }
    })
    .catch(err => {
        btn.innerHTML = origText;
        btn.disabled = false;
        alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
        console.error('submitPerpanjangan error:', err);
    });

    return false;
}
</script>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
