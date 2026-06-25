<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . '/login.php'); exit; }
if ($_SESSION['role'] !== 'superadmin') {
    if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) { header('Location: ' . BASE_URL . '/index.php'); exit; }
}

$page_title = 'Kelola Pengguna';
$current_page = 'kelola_pengguna';

$allUsers = User::getAll();
$totalUsers = count($allUsers);
$adminCount = User::countByRole('admin') + User::countByRole('superadmin');
$pelangganCount = User::countByRole('pelanggan');
$nonaktifCount = 0;
foreach ($allUsers as $u) {
    if (($u['status'] ?? 'aktif') === 'nonaktif') $nonaktifCount++;
}
$adminName = $_SESSION['nama'] ?? 'Admin';

// Separate users by role
$adminUsers = array_filter($allUsers, function($u) { return in_array($u['role'] ?? '', ['admin', 'superadmin']); });
$pelangganUsers = array_filter($allUsers, function($u) { return ($u['role'] ?? '') === 'pelanggan'; });
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
:root{--kp-dark:#1B4332;--kp-mid:#2D6A4F;--kp-light:#52B788;--kp-gold:#D4A373;--kp-bg:#f0f4f1}
body{font-family:'Inter',sans-serif;background:var(--kp-bg)}
h1,h2,h3,h4,h5,h6,.heading{font-family:'Outfit',sans-serif}
.mono{font-family:'JetBrains Mono',monospace}
.tr-tabs{display:flex;gap:6px;background:#fff;border-radius:14px;padding:6px;box-shadow:0 2px 12px rgba(27,67,50,0.06);margin-bottom:28px;flex-wrap:wrap}
.tr-tab{padding:10px 22px;border-radius:10px;border:none;background:transparent;font-weight:600;color:#6c757d;cursor:pointer;transition:all .3s;font-size:.88rem;white-space:nowrap}
.tr-tab.active{background:linear-gradient(135deg,var(--kp-mid),var(--kp-light));color:#fff;box-shadow:0 4px 16px rgba(45,106,79,0.3)}
.tr-tab:hover:not(.active){background:rgba(82,183,136,0.08);color:var(--kp-mid)}
.tr-tab .badge{font-size:.7rem;padding:2px 8px;border-radius:50px;margin-left:6px}
.stat-mini{background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.05);border-left:4px solid;display:flex;align-items:center;gap:14px;transition:all .3s}
.stat-mini:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.08)}
.stat-mini-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.stat-mini-val{font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;color:var(--kp-dark);line-height:1}
.stat-mini-label{font-size:.78rem;color:#6c757d;font-weight:500}
.stat-mini-trend{font-size:.72rem;font-weight:600;margin-left:auto;padding:3px 8px;border-radius:6px}
.stat-mini-trend.up{background:rgba(16,185,129,0.1);color:#10b981}
.stat-mini-trend.down{background:rgba(239,68,68,0.1);color:#ef4444}
.filter-bar{background:#fff;border-radius:14px;padding:16px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.05);margin-bottom:20px;display:flex;align-items:center;flex-wrap:wrap;gap:12px}
.filter-bar .form-control,.filter-bar .form-select{border-radius:10px;padding:8px 14px;border:1.5px solid #e0e0e0;font-size:.88rem;max-width:220px}
.filter-bar .form-control:focus,.filter-bar .form-select:focus{border-color:var(--kp-light);box-shadow:0 0 0 3px rgba(82,183,136,0.15)}
.trx-table{background:#fff;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,0.05);overflow:hidden}
.trx-table .table{margin-bottom:0}
.trx-table .table th{background:rgba(82,183,136,0.06);font-size:.82rem;font-weight:600;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;padding:14px 16px;border-bottom:2px solid #e8e8e8}
.trx-table .table td{padding:14px 16px;vertical-align:middle;border-color:#f0f0f0;font-size:.88rem}
.trx-table .table tbody tr:hover{background:rgba(82,183,136,0.03)}
.badge-role{padding:4px 14px;border-radius:50px;font-size:.75rem;font-weight:600}
.badge-admin{background:rgba(59,130,246,0.1);color:#3b82f6}
.badge-pelanggan{background:rgba(82,183,136,0.1);color:#16a34a}
.badge-sa{background:rgba(139,92,246,0.1);color:#8b5cf6}
.badge-aktif{background:rgba(16,185,129,0.1);color:#10b981}
.badge-nonaktif{background:rgba(239,68,68,0.1);color:#ef4444}
.avatar-sm{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--kp-mid),var(--kp-light));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0}
.btn-action{width:32px;height:32px;border-radius:8px;border:none;display:inline-flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .3s;cursor:pointer}
.btn-action-edit{background:rgba(59,130,246,0.1);color:#3b82f6}.btn-action-edit:hover{background:#3b82f6;color:#fff}
.btn-action-toggle{background:rgba(245,158,11,0.1);color:#f59e0b}.btn-action-toggle:hover{background:#f59e0b;color:#fff}
.btn-action-delete{background:rgba(239,68,68,0.1);color:#ef4444}.btn-action-delete:hover{background:#ef4444;color:#fff}
.form-section{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 14px rgba(0,0,0,0.05)}
.form-section-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:1.1rem;color:var(--kp-dark);margin-bottom:20px;display:flex;align-items:center;gap:10px}
.form-section-title i{color:var(--kp-light)}
.form-label-sm{font-size:.82rem;font-weight:600;color:#555;margin-bottom:4px}
.form-control,.form-select{border-radius:10px;padding:10px 14px;border:1.5px solid #e0e0e0;transition:all .3s;font-size:.9rem}
.form-control:focus,.form-select:focus{border-color:var(--kp-light);box-shadow:0 0 0 3px rgba(82,183,136,0.15)}
.btn-save{background:linear-gradient(135deg,var(--kp-mid),var(--kp-light));color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:600;font-size:.88rem;transition:all .3s}
.btn-save:hover{box-shadow:0 6px 18px rgba(45,106,79,0.3);color:#fff}
.pagination-row{display:flex;justify-content:space-between;align-items:center;padding:16px 20px}
.pagination-info{font-size:.82rem;color:#6c757d}
.pagination .page-link{border-radius:8px;margin:0 2px;border:1.5px solid #e0e0e0;color:#555;font-size:.85rem;padding:6px 12px}
.pagination .page-item.active .page-link{background:var(--kp-mid);border-color:var(--kp-mid);color:#fff}
.modal-content{border-radius:16px;border:none;overflow:hidden}
.modal-header.green-header{background:linear-gradient(135deg,var(--kp-mid),var(--kp-light));color:#fff;border:none;padding:18px 24px}
.modal-header.green-header .btn-close{filter:brightness(0) invert(1)}
.modal-header.red-header{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;padding:18px 24px}
.modal-header.red-header .btn-close{filter:brightness(0) invert(1)}
.success-checkmark{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--kp-mid),var(--kp-light));display:flex;align-items:center;justify-content:center;margin:0 auto 20px;animation:scaleIn .5s cubic-bezier(.4,0,.2,1)}
.success-checkmark i{color:#fff;font-size:2.2rem}
@keyframes scaleIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.custom-toast{background:#fff;border-radius:12px;padding:14px 20px;box-shadow:0 8px 30px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;border-left:4px solid var(--kp-light);animation:slideInToast .4s forwards}
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

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#3b82f6"><div class="stat-mini-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6"><i class="bi bi-people"></i></div><div><div class="stat-mini-val"><?= $totalUsers ?></div><div class="stat-mini-label">Total Pengguna</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#8b5cf6"><div class="stat-mini-icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6"><i class="bi bi-person-gear"></i></div><div><div class="stat-mini-val"><?= $adminCount ?></div><div class="stat-mini-label">Admin Aktif</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#10b981"><div class="stat-mini-icon" style="background:rgba(16,185,129,0.1);color:#10b981"><i class="bi bi-person-check"></i></div><div><div class="stat-mini-val"><?= $pelangganCount ?></div><div class="stat-mini-label">Pelanggan Aktif</div></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat-mini stagger-item" style="border-left-color:#ef4444"><div class="stat-mini-icon" style="background:rgba(239,68,68,0.1);color:#ef4444"><i class="bi bi-person-x"></i></div><div><div class="stat-mini-val"><?= $nonaktifCount ?></div><div class="stat-mini-label">Nonaktif</div></div></div></div>
        </div>

        <!-- Tabs -->
        <div class="tr-tabs">
            <button class="tr-tab active" id="tabBtnSemua" onclick="switchTab('semua')"><i class="bi bi-people me-1"></i>Semua Pengguna <span class="badge bg-secondary"><?= $totalUsers ?></span></button>
            <button class="tr-tab" id="tabBtnAdmin" onclick="switchTab('admin')"><i class="bi bi-person-gear me-1"></i>Admin</button>
            <button class="tr-tab" id="tabBtnPelanggan" onclick="switchTab('pelanggan')"><i class="bi bi-person me-1"></i>Pelanggan</button>
            <button class="tr-tab" id="tabBtnTambah" onclick="switchTab('tambah')"><i class="bi bi-person-plus me-1"></i>Tambah Pengguna</button>
        </div>

        <!-- TAB 1: SEMUA PENGGUNA -->
        <div id="tab-semua" class="tab-pane-kp">
            <div class="filter-bar stagger-item">
                <i class="bi bi-funnel text-muted"></i>
                <input type="text" class="form-control" placeholder="Cari nama atau email..." style="flex:1;max-width:280px">
                <select class="form-select"><option value="">Semua Role</option><option>Admin</option><option>Pelanggan</option></select>
                <select class="form-select"><option value="">Semua Status</option><option>Aktif</option><option>Nonaktif</option></select>
                <button class="btn btn-save btn-sm"><i class="bi bi-search me-1"></i>Cari</button>
            </div>
            <div class="trx-table stagger-item"><div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>No</th><th>Pengguna</th><th>Email</th><th>Role</th><th>No. Telepon</th><th>Tgl Daftar</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($allUsers as $idx => $user):
                        $nama = htmlspecialchars($user['nama'] ?? '-');
                        $email = htmlspecialchars($user['email'] ?? '-');
                        $role = $user['role'] ?? 'pelanggan';
                        $telp = htmlspecialchars($user['no_telp'] ?? '-');
                        $tglDaftar = isset($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '-';
                        $status = $user['status'] ?? 'aktif';
                        $initials = strtoupper(substr($user['nama'] ?? 'U', 0, 1) . (strpos($user['nama'] ?? '', ' ') !== false ? substr($user['nama'], strpos($user['nama'], ' ') + 1, 1) : ''));
                        $isNonaktif = $status === 'nonaktif';
                        $roleBadge = $role === 'superadmin' ? 'badge-sa' : ($role === 'admin' ? 'badge-admin' : 'badge-pelanggan');
                        $roleLabel = $role === 'superadmin' ? 'Super Admin' : ucfirst($role);
                        $isAdmin = in_array($role, ['admin', 'superadmin']);
                    ?>
                    <tr data-user-id="<?= $user['id'] ?>">
                        <td><?= $idx + 1 ?></td>
                        <td><div class="d-flex align-items-center gap-2"><div class="avatar-sm" <?= $isNonaktif ? 'style="background:linear-gradient(135deg,#ef4444,#dc2626)"' : '' ?>><?= htmlspecialchars($initials) ?></div><span class="fw-semibold <?= $isNonaktif ? 'text-muted' : '' ?>"><?= $nama ?></span></div></td>
                        <td class="mono <?= $isNonaktif ? 'text-muted' : '' ?>" style="font-size:.82rem"><?= $email ?></td>
                        <td><span class="badge-role <?= $roleBadge ?>"><?= $roleLabel ?></span></td>
                        <td><?= $telp ?></td>
                        <td><?= $tglDaftar ?></td>
                        <td><span class="badge-role <?= $isNonaktif ? 'badge-nonaktif' : 'badge-aktif' ?>"><?= $isNonaktif ? 'Nonaktif' : 'Aktif' ?></span></td>
                        <td><div class="d-flex gap-1">
                            <button class="btn-action btn-action-edit" onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($nama)) ?>', '<?= $email ?>', '<?= $telp ?>', '<?= htmlspecialchars(addslashes($user['alamat'] ?? '')) ?>', '<?= $role ?>')"><i class="bi bi-pencil"></i></button>
                            <button class="btn-action btn-action-toggle" data-user-id="<?= $user['id'] ?>" onclick="toggleStatus(this)"><i class="bi bi-toggle-<?= $isNonaktif ? 'off' : 'on' ?>"></i></button>
                            <?php if (!$isAdmin): ?><button class="btn-action btn-action-delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($nama)) ?>')"><i class="bi bi-trash"></i></button><?php endif; ?>
                        </div></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($allUsers)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data pengguna</td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div>
            <div class="pagination-row"><div class="pagination-info">Menampilkan 1-<?= $totalUsers ?> dari <?= $totalUsers ?> pengguna</div></div>
            </div>
        </div>

        <!-- TAB 2: ADMIN -->
        <div id="tab-admin" class="tab-pane-kp" style="display:none">
            <div class="trx-table stagger-item">
                <div class="p-3"><h5 class="heading fw-bold mb-0"><i class="bi bi-person-gear me-2 text-muted"></i>Daftar Admin (<?= $adminCount ?> orang)</h5></div>
                <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Nama</th><th>Email</th><th>Terakhir Login</th><th>Hak Akses</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($adminUsers as $admin):
                        $nama = htmlspecialchars($admin['nama'] ?? '-');
                        $email = htmlspecialchars($admin['email'] ?? '-');
                        $role = $admin['role'] ?? 'admin';
                        $status = $admin['status'] ?? 'aktif';
                        $initials = strtoupper(substr($admin['nama'] ?? 'A', 0, 1) . (strpos($admin['nama'] ?? '', ' ') !== false ? substr($admin['nama'], strpos($admin['nama'], ' ') + 1, 1) : ''));
                        $lastLogin = isset($admin['last_login']) ? date('d M Y, H:i', strtotime($admin['last_login'])) : '-';
                        $hakAkses = $role === 'superadmin' ? 'Full Access' : 'Admin';
                        $hakBadge = $role === 'superadmin' ? 'badge-sa' : 'badge-admin';
                    ?>
                    <tr>
                        <td><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><?= htmlspecialchars($initials) ?></div><div><span class="fw-semibold"><?= $nama ?></span><div class="text-muted" style="font-size:.75rem"><?= ucfirst($role) ?></div></div></div></td>
                        <td class="mono" style="font-size:.82rem"><?= $email ?></td>
                        <td><div class="mono" style="font-size:.82rem"><?= $lastLogin ?></div></td>
                        <td><span class="badge-role <?= $hakBadge ?>"><?= $hakAkses ?></span></td>
                        <td><span class="badge-role <?= $status === 'nonaktif' ? 'badge-nonaktif' : 'badge-aktif' ?>"><?= $status === 'nonaktif' ? 'Nonaktif' : 'Aktif' ?></span></td>
                        <td><button class="btn-action btn-action-edit" onclick="editUser('<?= htmlspecialchars(addslashes($nama)) ?>')"><i class="bi bi-pencil"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($adminUsers)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data admin</td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div></div>
        </div>

        <!-- TAB 3: PELANGGAN -->
        <div id="tab-pelanggan" class="tab-pane-kp" style="display:none">
            <div class="trx-table stagger-item">
                <div class="p-3 d-flex justify-content-between align-items-center"><h5 class="heading fw-bold mb-0"><i class="bi bi-person me-2 text-muted"></i>Daftar Pelanggan</h5><span class="badge rounded-pill" style="background:rgba(82,183,136,0.12);color:#2D6A4F;font-weight:600;padding:6px 14px"><?= $pelangganCount ?> Pelanggan</span></div>
                <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Nama</th><th>Email</th><th>No. Telepon</th><th>Tgl Daftar</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($pelangganUsers as $plg):
                        $nama = htmlspecialchars($plg['nama'] ?? '-');
                        $email = htmlspecialchars($plg['email'] ?? '-');
                        $telp = htmlspecialchars($plg['no_telp'] ?? '-');
                        $tglDaftar = isset($plg['created_at']) ? date('d M Y', strtotime($plg['created_at'])) : '-';
                        $status = $plg['status'] ?? 'aktif';
                        $isNonaktif = $status === 'nonaktif';
                        $initials = strtoupper(substr($plg['nama'] ?? 'U', 0, 1) . (strpos($plg['nama'] ?? '', ' ') !== false ? substr($plg['nama'], strpos($plg['nama'], ' ') + 1, 1) : ''));
                    ?>
                    <tr>
                        <td><div class="d-flex align-items-center gap-2"><div class="avatar-sm" <?= $isNonaktif ? 'style="background:linear-gradient(135deg,#ef4444,#dc2626)"' : '' ?>><?= htmlspecialchars($initials) ?></div><span class="fw-semibold <?= $isNonaktif ? 'text-muted' : '' ?>"><?= $nama ?></span></div></td>
                        <td class="mono <?= $isNonaktif ? 'text-muted' : '' ?>" style="font-size:.82rem"><?= $email ?></td>
                        <td <?= $isNonaktif ? 'class="text-muted"' : '' ?>><?= $telp ?></td>
                        <td <?= $isNonaktif ? 'class="text-muted"' : '' ?>><?= $tglDaftar ?></td>
                        <td><span class="badge-role <?= $isNonaktif ? 'badge-nonaktif' : 'badge-aktif' ?>"><?= $isNonaktif ? 'Nonaktif' : 'Aktif' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pelangganUsers)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data pelanggan</td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div></div>
        </div>

        <!-- TAB 4: TAMBAH PENGGUNA -->
        <div id="tab-tambah" class="tab-pane-kp" style="display:none">
            <div class="form-section stagger-item">
                <div class="form-section-title"><i class="bi bi-person-plus"></i>Form Tambah Pengguna Baru</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label-sm">Nama Lengkap <span class="text-danger">*</span></label><input type="text" class="form-control" id="addName" placeholder="Masukkan nama lengkap"></div>
                    <div class="col-md-6"><label class="form-label-sm">Email <span class="text-danger">*</span></label><input type="email" class="form-control" id="addEmail" placeholder="email@contoh.com"></div>
                    <div class="col-md-6"><label class="form-label-sm">Password <span class="text-danger">*</span></label><input type="password" class="form-control" id="addPass" placeholder="Min. 8 karakter"></div>
                    <div class="col-md-6"><label class="form-label-sm">Konfirmasi Password <span class="text-danger">*</span></label><input type="password" class="form-control" id="addPassConf" placeholder="Ulangi password"></div>
                    <div class="col-md-4"><label class="form-label-sm">No. Telepon</label><input type="text" class="form-control" id="addPhone" placeholder="08xx-xxxx-xxxx"></div>
                    <div class="col-md-4"><label class="form-label-sm">Role <span class="text-danger">*</span></label><select class="form-select" id="addRole"><option value="">Pilih Role</option><option value="admin">Admin</option><option value="pelanggan">Pelanggan</option></select></div>
                    <div class="col-md-4"><label class="form-label-sm">Status</label><select class="form-select" id="addStatus"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select></div>
                    <div class="col-12"><label class="form-label-sm">Alamat</label><textarea class="form-control" id="addAlamat" rows="2" placeholder="Alamat lengkap..."></textarea></div>
                </div>
                <div class="d-flex gap-3 mt-4"><button class="btn btn-save flex-fill" onclick="submitAddUser()"><i class="bi bi-check-circle me-1"></i>Simpan Pengguna</button><button class="btn btn-outline-secondary" onclick="resetAddForm()"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button></div>
            </div>
        </div>

    </div></div>
</div></div>

<!-- Edit Modal -->
<div class="modal fade" id="modalEdit" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header green-header"><h5 class="modal-title heading"><i class="bi bi-pencil-square me-2"></i>Edit Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label-sm">Nama</label><input type="text" class="form-control" id="editName"></div><div class="col-md-6"><label class="form-label-sm">Email</label><input type="email" class="form-control" id="editEmail"></div><div class="col-md-6"><label class="form-label-sm">No. Telepon</label><input type="text" class="form-control" id="editPhone"></div><div class="col-md-6"><label class="form-label-sm">Role</label><select class="form-select" id="editRole"><option>Admin</option><option>Pelanggan</option></select></div></div></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-save" onclick="confirmEdit()"><i class="bi bi-check-lg me-1"></i>Simpan</button></div></div></div></div>

<!-- Delete Modal -->
<div class="modal fade" id="modalDelete" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header red-header"><h5 class="modal-title heading"><i class="bi bi-exclamation-triangle me-2"></i>Hapus Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Yakin ingin menghapus pengguna <strong id="deleteName"></strong>?</p><p class="text-muted" style="font-size:.85rem">Tindakan ini tidak bisa dibatalkan.</p></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger" onclick="confirmDelete()"><i class="bi bi-trash me-1"></i>Hapus</button></div></div></div></div>

<!-- Success Modal -->
<div class="modal fade" id="modalSuccess" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-body text-center py-5"><div class="success-checkmark"><i class="bi bi-check-lg"></i></div><h5 class="heading fw-bold" id="successTitle">Berhasil!</h5><p class="text-muted" id="successMsg">Perubahan telah disimpan.</p><button class="btn btn-save" data-bs-dismiss="modal">OK</button></div></div></div></div>

<div class="toast-container" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let modalEdit,modalDelete,modalSuccess;
document.addEventListener('DOMContentLoaded',function(){
    modalEdit=new bootstrap.Modal(document.getElementById('modalEdit'));
    modalDelete=new bootstrap.Modal(document.getElementById('modalDelete'));
    modalSuccess=new bootstrap.Modal(document.getElementById('modalSuccess'));
    triggerStagger(document.getElementById('tab-semua'));
});

function switchTab(n){
    document.querySelectorAll('.tr-tab').forEach(t=>t.classList.remove('active'));
    document.getElementById('tabBtn'+n.charAt(0).toUpperCase()+n.slice(1)).classList.add('active');
    document.querySelectorAll('.tab-pane-kp').forEach(p=>p.style.display='none');
    const el=document.getElementById('tab-'+n);el.style.display='block';triggerStagger(el);
}

function triggerStagger(c){c.querySelectorAll('.stagger-item').forEach((item,i)=>{item.style.animation='none';item.offsetHeight;item.style.animation='';item.style.animationDelay=(i*0.08)+'s'});}

const BASE_URL = '<?= BASE_URL ?>';
let currentEditId = 0;
let currentDeleteId = 0;

function editUser(id, name, email, telp, alamat, role){
    currentEditId = id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    const editPhone = document.getElementById('editPhone'); if(editPhone) editPhone.value = telp || '';
    const editAlamat = document.getElementById('editAlamat'); if(editAlamat) editAlamat.value = alamat || '';
    const editRole = document.getElementById('editRole'); if(editRole) editRole.value = role || 'pelanggan';
    modalEdit.show();
}
function confirmEdit(){
    const data = {
        id: currentEditId,
        nama: document.getElementById('editName').value,
        email: document.getElementById('editEmail').value,
        no_telp: document.getElementById('editPhone')?.value || '',
        alamat: document.getElementById('editAlamat')?.value || '',
        role: document.getElementById('editRole')?.value || ''
    };
    fetch(BASE_URL+'/api/users.php?action=update', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify(data)
    }).then(r=>r.json()).then(res=>{
        modalEdit.hide();
        if(res.success){ showSuccess('Data Diperbarui', res.message || 'Perubahan berhasil disimpan.'); setTimeout(()=>location.reload(), 1500); }
        else { showToast(res.message || 'Gagal memperbarui'); }
    }).catch(()=>showToast('Terjadi kesalahan'));
}

function deleteUser(id, name){
    currentDeleteId = id;
    document.getElementById('deleteName').textContent = name;
    modalDelete.show();
}
function confirmDelete(){
    fetch(BASE_URL+'/api/users.php?action=toggle_status', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id: currentDeleteId})
    }).then(r=>r.json()).then(res=>{
        modalDelete.hide();
        if(res.success){ showSuccess('Pengguna Dinonaktifkan', res.message || 'Status berhasil diubah.'); setTimeout(()=>location.reload(), 1500); }
        else { showToast(res.message || 'Gagal menghapus'); }
    }).catch(()=>showToast('Terjadi kesalahan'));
}

function toggleStatus(btn){
    const userId = btn.dataset.userId;
    fetch(BASE_URL+'/api/users.php?action=toggle_status', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id: parseInt(userId)})
    }).then(r=>r.json()).then(res=>{
        if(res.success){ showToast(res.message || 'Status berhasil diubah'); setTimeout(()=>location.reload(), 1000); }
        else { showToast(res.message || 'Gagal mengubah status'); }
    }).catch(()=>showToast('Terjadi kesalahan'));
}

function submitAddUser(){
    const name = document.getElementById('addName').value;
    const email = document.getElementById('addEmail').value;
    const password = document.getElementById('addPass').value;
    const role = document.getElementById('addRole').value;
    const phone = document.getElementById('addPhone')?.value || '';
    const alamat = document.getElementById('addAlamat')?.value || '';
    
    if(!name){showToast('Nama wajib diisi!');return;}
    if(!email){showToast('Email wajib diisi!');return;}
    if(!password || password.length < 6){showToast('Password minimal 6 karakter!');return;}
    
    fetch(BASE_URL+'/api/users.php?action=create', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({nama:name, email:email, password:password, role:role, no_telp:phone, alamat:alamat})
    }).then(r=>r.json()).then(res=>{
        if(res.success){ showSuccess('Pengguna Ditambahkan', res.message || 'Pengguna baru berhasil ditambahkan.'); resetAddForm(); setTimeout(()=>location.reload(), 1500); }
        else { showToast(res.message || 'Gagal menambahkan pengguna'); }
    }).catch(()=>showToast('Terjadi kesalahan'));
}

function showSuccess(title,msg){document.getElementById('successTitle').textContent=title;document.getElementById('successMsg').textContent=msg;setTimeout(()=>modalSuccess.show(),400);}

function showToast(msg){const c=document.getElementById('toastContainer'),t=document.createElement('div');t.className='custom-toast';t.innerHTML='<div style="color:var(--kp-light);font-size:1.2rem"><i class="bi bi-check-circle-fill"></i></div><div style="font-weight:500;font-size:.9rem">'+msg+'</div>';c.appendChild(t);setTimeout(()=>{t.style.animation='slideOutToast .4s forwards';setTimeout(()=>t.remove(),400)},3000);}
</script>
</body></html>