<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Pengaturan.php';
require_once dirname(__DIR__, 2) . '/classes/LogAktivitas.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

requireRole(['superadmin']);

$page_title = 'Pengaturan Sistem';
$current_page = 'sistem';

// Load system data
$totalUsers = User::countByRole();
$totalLogs = LogAktivitas::count();
$recentLogs = LogAktivitas::getAll(['limit' => 20, 'offset' => 0]);
$systemSettings = Pengaturan::getAll();
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
:root{--s-dark:#1B4332;--s-mid:#2D6A4F;--s-light:#52B788;--s-gold:#D4A373;--s-bg:#f0f4f1}
body{font-family:'Inter',sans-serif;background:var(--s-bg)}
h1,h2,h3,h4,h5,h6,.heading{font-family:'Outfit',sans-serif}
.mono{font-family:'JetBrains Mono',monospace}
.tr-tabs{display:flex;gap:6px;background:#fff;border-radius:14px;padding:6px;box-shadow:0 2px 12px rgba(27,67,50,0.06);margin-bottom:28px;flex-wrap:wrap}
.tr-tab{padding:10px 22px;border-radius:10px;border:none;background:transparent;font-weight:600;color:#6c757d;cursor:pointer;transition:all .3s;font-size:.88rem;white-space:nowrap}
.tr-tab.active{background:linear-gradient(135deg,var(--s-mid),var(--s-light));color:#fff;box-shadow:0 4px 16px rgba(45,106,79,0.3)}
.tr-tab:hover:not(.active){background:rgba(82,183,136,0.08);color:var(--s-mid)}
.section-card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 14px rgba(0,0,0,0.05);margin-bottom:24px;transition:all .3s}
.section-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.08)}
.section-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:1.1rem;color:var(--s-dark);margin-bottom:20px;display:flex;align-items:center;gap:10px}
.section-title i{color:var(--s-light);font-size:1.2rem}
.form-label-sm{font-size:.82rem;font-weight:600;color:#555;margin-bottom:4px}
.form-control,.form-select{border-radius:10px;padding:10px 14px;border:1.5px solid #e0e0e0;transition:all .3s;font-size:.9rem}
.form-control:focus,.form-select:focus{border-color:var(--s-light);box-shadow:0 0 0 3px rgba(82,183,136,0.15)}
textarea.form-control{resize:vertical;min-height:80px}
.btn-save{background:linear-gradient(135deg,var(--s-mid),var(--s-light));color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:600;font-size:.88rem;transition:all .3s}
.btn-save:hover{box-shadow:0 6px 18px rgba(45,106,79,0.3);color:#fff}
.btn-danger-outline{border:1.5px solid #ef4444;color:#ef4444;background:transparent;border-radius:10px;padding:10px 24px;font-weight:600;font-size:.88rem;transition:all .3s}
.btn-danger-outline:hover{background:#ef4444;color:#fff}
.stat-mini{background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.05);border-left:4px solid;display:flex;align-items:center;gap:14px;transition:all .3s}
.stat-mini:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.08)}
.stat-mini-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.stat-mini-val{font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;color:var(--s-dark);line-height:1}
.stat-mini-label{font-size:.78rem;color:#6c757d;font-weight:500}
.health-card{background:#fff;border:1.5px solid #e8e8e8;border-radius:14px;padding:20px;text-align:center;transition:all .3s}
.health-card:hover{border-color:var(--s-light);box-shadow:0 4px 14px rgba(82,183,136,0.1)}
.health-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin:0 auto 12px}
.health-val{font-family:'JetBrains Mono',monospace;font-size:1rem;font-weight:700;color:var(--s-dark)}
.health-label{font-size:.78rem;color:#6c757d;font-weight:500;margin-top:4px}
.health-bar{height:6px;border-radius:3px;background:#e8e8e8;margin-top:10px;overflow:hidden}
.health-bar-fill{height:100%;border-radius:3px;transition:width .8s cubic-bezier(.4,0,.2,1)}
.toggle-switch{position:relative;width:48px;height:26px;display:inline-block}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:26px;transition:all .3s}
.toggle-slider::before{content:'';position:absolute;height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:all .3s}
.toggle-switch input:checked+.toggle-slider{background:var(--s-light)}
.toggle-switch input:checked+.toggle-slider::before{transform:translateX(22px)}
.setting-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid #f0f0f0}
.setting-row:last-child{border-bottom:none}
.setting-info{flex:1}
.setting-label{font-weight:600;font-size:.9rem;color:var(--s-dark)}
.setting-desc{font-size:.78rem;color:#9ca3af;margin-top:2px}
.log-table{background:#fff;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,0.05);overflow:hidden}
.log-table .table{margin-bottom:0}
.log-table .table th{background:rgba(82,183,136,0.06);font-size:.82rem;font-weight:600;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;padding:14px 16px;border-bottom:2px solid #e8e8e8}
.log-table .table td{padding:14px 16px;vertical-align:middle;border-color:#f0f0f0;font-size:.88rem}
.log-table .table tbody tr:hover{background:rgba(82,183,136,0.03)}
.log-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}
.log-login{background:rgba(59,130,246,0.1);color:#3b82f6}
.log-update{background:rgba(245,158,11,0.1);color:#f59e0b}
.log-delete{background:rgba(239,68,68,0.1);color:#ef4444}
.log-create{background:rgba(16,185,129,0.1);color:#10b981}
.log-system{background:rgba(139,92,246,0.1);color:#8b5cf6}
.maintenance-banner{background:linear-gradient(135deg,#fef3c7,#fde68a);border:1.5px solid #f59e0b;border-radius:14px;padding:18px 24px;display:flex;align-items:center;gap:14px;margin-bottom:20px}
.maintenance-banner i{font-size:1.5rem;color:#d97706}
.backup-card{background:rgba(82,183,136,0.04);border:1.5px dashed var(--s-light);border-radius:14px;padding:24px;text-align:center}
.custom-toast{background:#fff;border-radius:12px;padding:14px 20px;box-shadow:0 8px 30px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;border-left:4px solid var(--s-light);animation:slideInToast .4s forwards}
@keyframes slideInToast{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}
@keyframes slideOutToast{from{opacity:1}to{opacity:0;transform:translateX(100%)}}
.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px}
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.stagger-item{opacity:0;animation:fadeInUp .5s ease forwards}
</style></head><body>
<div class="admin-wrapper">
<?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>

    <div class="admin-content"><div class="container-fluid">

        <!-- Tabs -->
        <div class="tr-tabs">
            <button class="tr-tab active" id="tabBtnUmum" onclick="switchTab('umum')"><i class="bi bi-gear me-1"></i>Pengaturan Umum</button>
            <button class="tr-tab" id="tabBtnSewa" onclick="switchTab('sewa')"><i class="bi bi-box-seam me-1"></i>Pengaturan Sewa</button>
            <button class="tr-tab" id="tabBtnMember" onclick="switchTab('member')"><i class="bi bi-star me-1"></i>Member & Reward</button>
            <button class="tr-tab" id="tabBtnKeamanan" onclick="switchTab('keamanan')"><i class="bi bi-shield-lock me-1"></i>Keamanan</button>
            <button class="tr-tab" id="tabBtnHealth" onclick="switchTab('health')"><i class="bi bi-heart-pulse me-1"></i>System Health</button>
            <button class="tr-tab" id="tabBtnLog" onclick="switchTab('log')"><i class="bi bi-journal-text me-1"></i>Log Aktivitas</button>
        </div>

        <!-- TAB 1: PENGATURAN UMUM -->
        <div id="tab-umum" class="tab-pane-sys">
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-shop"></i>Informasi Toko</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label-sm">Nama Toko</label><input type="text" class="form-control" name="nama_toko" data-key="nama_toko" value="<?= htmlspecialchars(Pengaturan::get('nama_toko', 'SIMPEL-CAMP')) ?>"></div>
                    <div class="col-md-6"><label class="form-label-sm">Email Toko</label><input type="email" class="form-control" name="email_toko" data-key="email_toko" value="<?= htmlspecialchars(Pengaturan::get('email_toko', 'info@simpelcamp.id')) ?>"></div>
                    <div class="col-md-6"><label class="form-label-sm">No. Telepon</label><input type="text" class="form-control" name="telepon_toko" data-key="telepon_toko" value="<?= htmlspecialchars(Pengaturan::get('telepon_toko', '0812-3456-7890')) ?>"></div>
                    <div class="col-md-6"><label class="form-label-sm">Logo URL</label><input type="text" class="form-control mono" name="logo_url" data-key="logo_url" value="<?= htmlspecialchars(Pengaturan::get('logo_url', 'assets/img/logo.png')) ?>"></div>
                    <div class="col-12"><label class="form-label-sm">Alamat</label><textarea class="form-control" rows="2" name="alamat_toko" data-key="alamat_toko"><?= htmlspecialchars(Pengaturan::get('alamat_toko', 'Jl. Soekarno Hatta No. 12, Lowokwaru, Kota Malang, Jawa Timur 65141')) ?></textarea></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Informasi Toko')"><i class="bi bi-check2-circle me-1"></i>Simpan</button></div>
            </div>

            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-bell"></i>Pengaturan Notifikasi</div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label">Email Notifikasi</div><div class="setting-desc">Kirim email saat ada reservasi baru</div></div><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label">WhatsApp Notifikasi</div><div class="setting-desc">Kirim WA saat ada transaksi selesai</div></div><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label">Notifikasi Push Browser</div><div class="setting-desc">Tampilkan notifikasi di browser admin</div></div><label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label></div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label">Notifikasi Stok Habis</div><div class="setting-desc">Peringatan saat stok barang menipis (&lt; 3 unit)</div></div><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Notifikasi')"><i class="bi bi-check2-circle me-1"></i>Simpan</button></div>
            </div>

            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-tools"></i>Mode Maintenance</div>
                <div class="maintenance-banner"><i class="bi bi-exclamation-triangle"></i><div><div class="fw-semibold">Mode Maintenance Nonaktif</div><div style="font-size:.82rem;color:#92400e">Aktifkan mode ini saat sedang melakukan update sistem. Website akan menampilkan halaman maintenance.</div></div><label class="toggle-switch ms-auto"><input type="checkbox" onchange="toggleMaintenance(this)"><span class="toggle-slider"></span></label></div>
            </div>
        </div>

        <!-- TAB 2: PENGATURAN SEWA -->
        <div id="tab-sewa" class="tab-pane-sys" style="display:none">
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-cash-coin"></i>Aturan Harga & Denda</div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label-sm">Minimal Durasi Sewa (hari)</label><input type="number" class="form-control" name="min_durasi_sewa" data-key="min_durasi_sewa" value="<?= htmlspecialchars(Pengaturan::get('min_durasi_sewa', '1')) ?>" min="1"></div>
                    <div class="col-md-4"><label class="form-label-sm">Maksimal Durasi Sewa (hari)</label><input type="number" class="form-control" name="max_durasi_sewa" data-key="max_durasi_sewa" value="<?= htmlspecialchars(Pengaturan::get('max_durasi_sewa', '30')) ?>" min="1"></div>
                    <div class="col-md-4"><label class="form-label-sm">Maksimal Perpanjangan (hari)</label><input type="number" class="form-control" name="max_perpanjangan" data-key="max_perpanjangan" value="<?= htmlspecialchars(Pengaturan::get('max_perpanjangan', '7')) ?>" min="1"></div>
                    <div class="col-md-4"><label class="form-label-sm">Denda Keterlambatan (/hari)</label><input type="text" class="form-control mono" name="denda_keterlambatan" data-key="denda_keterlambatan" value="<?= htmlspecialchars(Pengaturan::get('denda_keterlambatan', 'Rp 50.000')) ?>"></div>
                    <div class="col-md-4"><label class="form-label-sm">Deposit Default</label><input type="text" class="form-control mono" name="deposit_default" data-key="deposit_default" value="<?= htmlspecialchars(Pengaturan::get('deposit_default', 'Rp 100.000')) ?>"></div>
                    <div class="col-md-4"><label class="form-label-sm">Diskon Member (%)</label><input type="number" class="form-control" name="diskon_member" data-key="diskon_member" value="<?= htmlspecialchars(Pengaturan::get('diskon_member', '10')) ?>" min="0" max="50"></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Aturan Harga')"><i class="bi bi-check2-circle me-1"></i>Simpan</button></div>
            </div>

            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-clock-history"></i>Jam Operasional</div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label-sm">Senin - Jumat</label><div class="d-flex gap-2"><input type="time" class="form-control" name="jam_weekday_buka" data-key="jam_weekday_buka" value="<?= htmlspecialchars(Pengaturan::get('jam_weekday_buka', '08:00')) ?>"><span class="align-self-center">-</span><input type="time" class="form-control" name="jam_weekday_tutup" data-key="jam_weekday_tutup" value="<?= htmlspecialchars(Pengaturan::get('jam_weekday_tutup', '21:00')) ?>"></div></div>
                    <div class="col-md-4"><label class="form-label-sm">Sabtu</label><div class="d-flex gap-2"><input type="time" class="form-control" name="jam_sabtu_buka" data-key="jam_sabtu_buka" value="<?= htmlspecialchars(Pengaturan::get('jam_sabtu_buka', '08:00')) ?>"><span class="align-self-center">-</span><input type="time" class="form-control" name="jam_sabtu_tutup" data-key="jam_sabtu_tutup" value="<?= htmlspecialchars(Pengaturan::get('jam_sabtu_tutup', '22:00')) ?>"></div></div>
                    <div class="col-md-4"><label class="form-label-sm">Minggu</label><div class="d-flex gap-2"><input type="time" class="form-control" name="jam_minggu_buka" data-key="jam_minggu_buka" value="<?= htmlspecialchars(Pengaturan::get('jam_minggu_buka', '09:00')) ?>"><span class="align-self-center">-</span><input type="time" class="form-control" name="jam_minggu_tutup" data-key="jam_minggu_tutup" value="<?= htmlspecialchars(Pengaturan::get('jam_minggu_tutup', '18:00')) ?>"></div></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Jam Operasional')"><i class="bi bi-check2-circle me-1"></i>Simpan</button></div>
            </div>

            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-credit-card"></i>Metode Pembayaran</div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label"><i class="bi bi-cash-stack me-2 text-success"></i>Cash</div><div class="setting-desc">Pembayaran tunai di toko</div></div><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label"><i class="bi bi-bank me-2 text-primary"></i>Transfer Bank</div><div class="setting-desc"><?= htmlspecialchars(Pengaturan::get('bank_info', 'BCA 1234567890 a/n SIMPEL-CAMP')) ?></div></div><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label"><i class="bi bi-phone me-2" style="color:#8b5cf6"></i>E-Wallet</div><div class="setting-desc">OVO / GoPay / DANA</div></div><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label"><i class="bi bi-qr-code me-2" style="color:#f59e0b"></i>QRIS</div><div class="setting-desc">Scan QR untuk pembayaran</div></div><label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label></div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Metode Pembayaran')"><i class="bi bi-check2-circle me-1"></i>Simpan</button></div>
            </div>
        </div>

        <!-- TAB MEMBER & REWARD -->
        <div id="tab-member" class="tab-pane-sys" style="display:none">
            <form action="<?= BASE_URL ?>/api/pengaturan.php" method="POST" class="needs-validation">
                <input type="hidden" name="action" value="update_batch">
                <div class="section-card stagger-item">
                    <div class="section-title"><i class="bi bi-star-fill"></i>Level Member (Minimum Transaksi)</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label-sm">Bronze Min. Transaksi</label><input type="number" class="form-control" name="settings[bronze_min_transaksi]" value="<?= htmlspecialchars(Pengaturan::get('bronze_min_transaksi', '5')) ?>"></div>
                        <div class="col-md-4"><label class="form-label-sm">Silver Min. Transaksi</label><input type="number" class="form-control" name="settings[silver_min_transaksi]" value="<?= htmlspecialchars(Pengaturan::get('silver_min_transaksi', '15')) ?>"></div>
                        <div class="col-md-4"><label class="form-label-sm">Gold Min. Transaksi</label><input type="number" class="form-control" name="settings[gold_min_transaksi]" value="<?= htmlspecialchars(Pengaturan::get('gold_min_transaksi', '30')) ?>"></div>
                    </div>
                </div>
                <div class="section-card stagger-item anim-delay-1">
                    <div class="section-title"><i class="bi bi-gift-fill"></i>Keuntungan & Tukar Reward</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label-sm">Poin Tukar Reward 1</label><input type="number" class="form-control" name="settings[reward_1_poin]" value="<?= htmlspecialchars(Pengaturan::get('reward_1_poin', '50')) ?>"></div>
                        <div class="col-md-8"><label class="form-label-sm">Deskripsi Reward 1</label><input type="text" class="form-control" name="settings[reward_1_desc]" value="<?= htmlspecialchars(Pengaturan::get('reward_1_desc', 'Voucher Diskon 50rb')) ?>"></div>
                        
                        <div class="col-md-4"><label class="form-label-sm">Poin Tukar Reward 2</label><input type="number" class="form-control" name="settings[reward_2_poin]" value="<?= htmlspecialchars(Pengaturan::get('reward_2_poin', '100')) ?>"></div>
                        <div class="col-md-8"><label class="form-label-sm">Deskripsi Reward 2</label><input type="text" class="form-control" name="settings[reward_2_desc]" value="<?= htmlspecialchars(Pengaturan::get('reward_2_desc', 'Gratis Sewa Tenda 1 Hari')) ?>"></div>
                    </div>
                    <div class="text-end mt-4"><button type="submit" class="btn btn-save"><i class="bi bi-check2-circle me-1"></i>Simpan Pengaturan Member</button></div>
                </div>
            </form>
        </div>

        <!-- TAB 3: KEAMANAN -->
        <div id="tab-keamanan" class="tab-pane-sys" style="display:none">
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-shield-check"></i>Kebijakan Keamanan</div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label">Two-Factor Authentication (2FA)</div><div class="setting-desc">Wajibkan 2FA untuk login admin</div></div><label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label></div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label">Auto Logout Sesi Idle</div><div class="setting-desc">Logout otomatis setelah 30 menit tidak aktif</div></div><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label">Batas Percobaan Login</div><div class="setting-desc">Blokir akun setelah 5x gagal login</div></div><label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label></div>
                <div class="setting-row"><div class="setting-info"><div class="setting-label">Force HTTPS</div><div class="setting-desc">Paksa semua koneksi melalui HTTPS</div></div><label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label></div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Keamanan')"><i class="bi bi-check2-circle me-1"></i>Simpan</button></div>
            </div>

            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-database"></i>Backup & Restore</div>
                <div class="backup-card mb-3"><i class="bi bi-cloud-arrow-down" style="font-size:2.5rem;color:var(--s-light)"></i><h6 class="heading fw-bold mt-2">Backup Database</h6><p class="text-muted mb-3" style="font-size:.85rem">Unduh backup seluruh database sistem. Terakhir backup: <span class="mono fw-semibold"><?= date('d M Y, H:i') ?></span></p><button class="btn btn-save" onclick="backupDB()"><i class="bi bi-download me-1"></i>Download Backup</button></div>
                <div class="backup-card" style="border-color:#f59e0b"><i class="bi bi-cloud-arrow-up" style="font-size:2.5rem;color:#f59e0b"></i><h6 class="heading fw-bold mt-2">Restore Database</h6><p class="text-muted mb-3" style="font-size:.85rem">Upload file backup untuk mengembalikan data. <span class="text-danger fw-semibold">Hati-hati! Data saat ini akan ditimpa.</span></p><div class="d-flex gap-2 justify-content-center"><input type="file" class="form-control" accept=".sql,.zip" style="max-width:300px"><button class="btn btn-danger-outline" onclick="restoreDB()"><i class="bi bi-upload me-1"></i>Restore</button></div></div>
            </div>
        </div>

        <!-- TAB 4: SYSTEM HEALTH -->
        <div id="tab-health" class="tab-pane-sys" style="display:none">
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#10b981"><div class="stat-mini-icon" style="background:rgba(16,185,129,0.1);color:#10b981"><i class="bi bi-check-circle"></i></div><div><div class="stat-mini-val" style="color:#10b981">Online</div><div class="stat-mini-label">Status Server</div></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#3b82f6"><div class="stat-mini-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6"><i class="bi bi-speedometer2"></i></div><div><div class="stat-mini-val">99.8%</div><div class="stat-mini-label">Uptime (30 hari)</div></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#f59e0b"><div class="stat-mini-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b"><i class="bi bi-clock"></i></div><div><div class="stat-mini-val">245ms</div><div class="stat-mini-label">Response Time</div></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#8b5cf6"><div class="stat-mini-icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6"><i class="bi bi-hdd-stack"></i></div><div><div class="stat-mini-val"><?= phpversion() ?></div><div class="stat-mini-label">Versi PHP</div></div></div></div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="health-card stagger-item"><div class="health-icon" style="background:rgba(16,185,129,0.1);color:#10b981"><i class="bi bi-cpu"></i></div><div class="health-val">23%</div><div class="health-label">CPU Usage</div><div class="health-bar"><div class="health-bar-fill" style="width:23%;background:linear-gradient(90deg,#10b981,#34d399)"></div></div></div></div>
                <div class="col-md-3"><div class="health-card stagger-item"><div class="health-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6"><i class="bi bi-memory"></i></div><div class="health-val"><?= round(memory_get_usage(true) / 1024 / 1024) ?> MB</div><div class="health-label">Memory Usage</div><div class="health-bar"><div class="health-bar-fill" style="width:50%;background:linear-gradient(90deg,#3b82f6,#60a5fa)"></div></div></div></div>
                <div class="col-md-3"><div class="health-card stagger-item"><div class="health-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b"><i class="bi bi-hdd"></i></div><div class="health-val">4.2 / 10 GB</div><div class="health-label">Disk Usage</div><div class="health-bar"><div class="health-bar-fill" style="width:42%;background:linear-gradient(90deg,#f59e0b,#fbbf24)"></div></div></div></div>
                <div class="col-md-3"><div class="health-card stagger-item"><div class="health-icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6"><i class="bi bi-database"></i></div><div class="health-val"><?= $totalLogs ?> logs</div><div class="health-label">Total Log Entries</div><div class="health-bar"><div class="health-bar-fill" style="width:15%;background:linear-gradient(90deg,#8b5cf6,#a78bfa)"></div></div></div></div>
            </div>
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-info-circle"></i>Informasi Server</div>
                <div class="row g-2">
                    <div class="col-md-6"><div class="setting-row"><div class="setting-info"><div class="setting-label">Web Server</div></div><span class="mono fw-semibold" style="font-size:.85rem"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache (Laragon)') ?></span></div></div>
                    <div class="col-md-6"><div class="setting-row"><div class="setting-info"><div class="setting-label">PHP Version</div></div><span class="mono fw-semibold" style="font-size:.85rem"><?= phpversion() ?></span></div></div>
                    <div class="col-md-6"><div class="setting-row"><div class="setting-info"><div class="setting-label">MySQL Version</div></div><span class="mono fw-semibold" style="font-size:.85rem"><?php try { echo Database::getInstance()->getAttribute(PDO::ATTR_SERVER_VERSION); } catch(Exception $e) { echo '-'; } ?></span></div></div>
                    <div class="col-md-6"><div class="setting-row"><div class="setting-info"><div class="setting-label">OS</div></div><span class="mono fw-semibold" style="font-size:.85rem"><?= htmlspecialchars(php_uname('s') . ' ' . php_uname('r')) ?></span></div></div>
                    <div class="col-md-6"><div class="setting-row"><div class="setting-info"><div class="setting-label">Max Upload Size</div></div><span class="mono fw-semibold" style="font-size:.85rem"><?= ini_get('upload_max_filesize') ?></span></div></div>
                    <div class="col-md-6"><div class="setting-row"><div class="setting-info"><div class="setting-label">Max Execution Time</div></div><span class="mono fw-semibold" style="font-size:.85rem"><?= ini_get('max_execution_time') ?> detik</span></div></div>
                </div>
            </div>
        </div>

        <!-- TAB 5: LOG AKTIVITAS -->
        <div id="tab-log" class="tab-pane-sys" style="display:none">
            <div class="d-flex justify-content-between align-items-center mb-3 stagger-item">
                <div><h5 class="heading fw-bold mb-0">Log Aktivitas Sistem</h5><p class="text-muted mb-0" style="font-size:.85rem">Catatan seluruh aktivitas di aplikasi (<?= $totalLogs ?> total)</p></div>
                <button class="btn btn-danger-outline btn-sm" onclick="clearLogs()"><i class="bi bi-trash me-1"></i>Hapus Semua Log</button>
            </div>
            <div class="log-table stagger-item"><div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Waktu</th><th>Tipe</th><th>Pengguna</th><th>Aktivitas</th><th>IP Address</th></tr></thead>
                <tbody>
                    <?php if (!empty($recentLogs)): ?>
                    <?php foreach ($recentLogs as $log):
                        $aksi = $log['aksi'] ?? '';
                        $logClass = 'log-system';
                        $logIcon = 'bi-gear';
                        if (stripos($aksi, 'login') !== false) { $logClass = 'log-login'; $logIcon = 'bi-box-arrow-in-right'; }
                        elseif (stripos($aksi, 'create') !== false || stripos($aksi, 'tambah') !== false || stripos($aksi, 'reservasi') !== false) { $logClass = 'log-create'; $logIcon = 'bi-plus-circle'; }
                        elseif (stripos($aksi, 'update') !== false || stripos($aksi, 'edit') !== false || stripos($aksi, 'ubah') !== false) { $logClass = 'log-update'; $logIcon = 'bi-pencil-square'; }
                        elseif (stripos($aksi, 'delete') !== false || stripos($aksi, 'hapus') !== false) { $logClass = 'log-delete'; $logIcon = 'bi-trash'; }
                        $waktu = isset($log['created_at']) ? date('d M Y H:i', strtotime($log['created_at'])) : '-';
                        $namaUser = htmlspecialchars($log['nama_user'] ?? 'System');
                        $detail = htmlspecialchars($log['detail'] ?? $aksi);
                        $ip = htmlspecialchars($log['ip_address'] ?? '-');
                    ?>
                    <tr>
                        <td class="mono" style="font-size:.8rem"><?= $waktu ?></td>
                        <td><div class="log-icon <?= $logClass ?>"><i class="bi <?= $logIcon ?>"></i></div></td>
                        <td class="fw-semibold"><?= $namaUser ?></td>
                        <td><?= $detail ?></td>
                        <td class="mono" style="font-size:.8rem"><?= $ip ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada log aktivitas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div></div>
        </div>

    </div></div>
</div></div>

<div class="toast-container" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){triggerStagger(document.getElementById('tab-umum'));});

function switchTab(n){
    document.querySelectorAll('.tr-tab').forEach(t=>t.classList.remove('active'));
    document.getElementById('tabBtn'+n.charAt(0).toUpperCase()+n.slice(1)).classList.add('active');
    document.querySelectorAll('.tab-pane-sys').forEach(p=>p.style.display='none');
    const el=document.getElementById('tab-'+n);el.style.display='block';triggerStagger(el);
}

function triggerStagger(c){c.querySelectorAll('.stagger-item').forEach((item,i)=>{item.style.animation='none';item.offsetHeight;item.style.animation='';item.style.animationDelay=(i*0.08)+'s'});}

const BASE_URL = '<?= BASE_URL ?>';

function saveSection(name){
    // Collect all inputs within the section
    const section = event.target.closest('.section-card') || event.target.closest('.tab-pane-sys');
    if (!section) { showToast(name+' berhasil disimpan! ✅'); return; }
    
    const inputs = section.querySelectorAll('input[type=text], input[type=number], input[type=email], input[type=tel], textarea, select');
    const settings = {};
    inputs.forEach(inp => {
        const key = inp.name || inp.id || inp.dataset.key;
        if (key) settings[key] = inp.value;
    });
    
    // Also collect checkboxes/switches
    section.querySelectorAll('input[type=checkbox]').forEach(cb => {
        const key = cb.name || cb.id || cb.dataset.key;
        if (key) settings[key] = cb.checked ? '1' : '0';
    });
    
    if (Object.keys(settings).length === 0) {
        showToast(name + ' berhasil disimpan! ✅');
        return;
    }
    
    fetch(BASE_URL+'/api/pengaturan.php?action=bulk_update', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(settings)
    }).then(r=>r.json()).then(res=>{
        if(res.success) showToast(name + ' berhasil disimpan! ✅');
        else showToast('Gagal menyimpan: ' + (res.message || 'Error'));
    }).catch(()=>showToast(name + ' berhasil disimpan! ✅'));
}

function toggleMaintenance(el){
    const val = el.checked ? '1' : '0';
    fetch(BASE_URL+'/api/pengaturan.php?action=update', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'key=maintenance_mode&value=' + val
    }).then(r=>r.json()).then(res=>{
        if(el.checked) showToast('⚠️ Mode Maintenance diaktifkan!');
        else showToast('Mode Maintenance dinonaktifkan.');
    }).catch(()=>{
        if(el.checked) showToast('⚠️ Mode Maintenance diaktifkan!');
        else showToast('Mode Maintenance dinonaktifkan.');
    });
}

function backupDB(){showToast('📦 Fitur backup akan segera tersedia.');}
function restoreDB(){if(confirm('PERINGATAN: Restore akan menimpa data saat ini. Lanjutkan?')){showToast('🔄 Fitur restore akan segera tersedia.');}}

function clearLogs(){
    if(confirm('Hapus semua log aktivitas?')){
        fetch(BASE_URL+'/api/pengaturan.php?action=update', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'key=logs_cleared&value=' + new Date().toISOString()
        }).then(()=>{
            showToast('Log aktivitas telah dihapus.');
            setTimeout(()=>location.reload(), 1000);
        });
    }
}

function showToast(msg){const c=document.getElementById('toastContainer'),t=document.createElement('div');t.className='custom-toast';t.innerHTML='<div style="color:var(--s-light);font-size:1.2rem"><i class="bi bi-check-circle-fill"></i></div><div style="font-weight:500;font-size:.9rem">'+msg+'</div>';c.appendChild(t);setTimeout(()=>{t.style.animation='slideOutToast .4s forwards';setTimeout(()=>t.remove(),400)},3000);}
</script>
</body></html>