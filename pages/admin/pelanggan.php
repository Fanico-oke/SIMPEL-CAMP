<?php
// pages/admin/pelanggan.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . '/login.php'); exit; }
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$page_title = 'Data Pelanggan';
$current_page = 'pelanggan';

// Fetch real data
$allUsers = User::getAll('pelanggan');
$totalPelanggan = count($allUsers);
$pelangganAktif = 0;
foreach ($allUsers as $u) {
    if (($u['status'] ?? 'aktif') === 'aktif') $pelangganAktif++;
}

$pelanggan = [];
foreach ($allUsers as $u) {
    $pelanggan[] = [
        'id' => $u['id'] ?? 0,
        'nama' => $u['nama'] ?? '-',
        'email' => $u['email'] ?? '-',
        'telp' => $u['no_telp'] ?? $u['telepon'] ?? '-',
        'alamat' => $u['alamat'] ?? '-',
        'status' => ucfirst($u['status'] ?? 'aktif'),
        'tier' => 'Bronze',
        'tierColor' => '#CD7F32',
        'trx' => 0,
        'lastActive' => isset($u['updated_at']) ? date('d M Y', strtotime($u['updated_at'])) : '-',
        'memberSince' => isset($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '-',
    ];
}

$adminName = $_SESSION['nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781677663">
    <style>
        .plg-toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:12px}
        .plg-toast{background:rgba(27,67,50,0.95);backdrop-filter:blur(20px);color:#fff;padding:14px 24px;border-radius:var(--radius-md);box-shadow:0 20px 60px rgba(0,0,0,0.3);display:flex;align-items:center;gap:12px;transform:translateX(120%);transition:transform 0.5s cubic-bezier(0.34,1.56,0.64,1);border-left:4px solid var(--primary-lighter);min-width:300px}
        .plg-toast.show{transform:translateX(0)}
        .plg-toast i{font-size:1.3rem;color:var(--primary-lighter)}
        .plg-header{background:linear-gradient(135deg,#1B4332 0%,#2D6A4F 50%,#1B4332 100%);border-radius:var(--radius-lg);padding:2rem 2.5rem;color:#fff;margin-bottom:2rem;position:relative;overflow:hidden;animation:plgFadeIn 0.7s ease forwards}
        .plg-header::before{content:'';position:absolute;top:-50%;right:-20%;width:400px;height:400px;background:radial-gradient(circle,rgba(82,183,136,0.15) 0%,transparent 70%);border-radius:50%}
        .plg-header h2{font-family:'Outfit',sans-serif;font-weight:800;margin:0;position:relative}
        .plg-header p{opacity:0.8;margin:0.5rem 0 0;position:relative}
        .plg-stat{background:rgba(255,255,255,0.92);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.5);border-radius:var(--radius-md);padding:1.5rem;transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);box-shadow:0 4px 15px rgba(0,0,0,0.06);opacity:0;transform:translateY(25px)}
        .plg-stat.visible{opacity:1;transform:translateY(0)}
        .plg-stat:hover{transform:translateY(-6px);box-shadow:0 20px 40px rgba(27,67,50,0.15)}
        .plg-stat-icon{width:52px;height:52px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:1.4rem}
        .plg-stat-icon.green{background:rgba(82,183,136,0.12);color:var(--primary-lighter)}
        .plg-stat-icon.blue{background:rgba(59,130,246,0.12);color:#3B82F6}
        .plg-stat-icon.gold{background:rgba(212,163,115,0.12);color:var(--accent)}
        .plg-stat-value{font-family:'Outfit',sans-serif;font-weight:800;font-size:1.8rem;line-height:1.2;color:var(--text-primary)}
        .plg-stat-label{font-size:0.85rem;color:var(--text-secondary);font-weight:500}
        .plg-filter{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.25rem;margin-bottom:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,0.04)}
        .plg-form-control{border:2px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px;transition:all 0.3s ease}
        .plg-form-control:focus{border-color:var(--primary-lighter);box-shadow:0 0 0 4px rgba(82,183,136,0.15)}
        .plg-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.5rem;transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);box-shadow:0 2px 10px rgba(0,0,0,0.04);cursor:pointer;opacity:0;transform:translateY(25px);height:100%}
        .plg-card.visible{opacity:1;transform:translateY(0)}
        .plg-card:hover{transform:translateY(-6px);box-shadow:0 15px 40px rgba(27,67,50,0.12);border-color:var(--primary-lighter)}
        .plg-avatar{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1.3rem;flex-shrink:0;border:3px solid transparent}
        .plg-name{font-weight:700;font-size:1.05rem}
        .plg-email{font-size:0.8rem;color:var(--text-secondary)}
        .plg-phone{font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:var(--text-secondary)}
        .status-dot{width:10px;height:10px;border-radius:50%;display:inline-block}
        .status-dot.aktif{background:var(--success);box-shadow:0 0 8px rgba(16,185,129,0.5)}
        .status-dot.nonaktif{background:var(--danger);box-shadow:0 0 8px rgba(239,68,68,0.4)}
        .plg-meta{display:flex;gap:16px;margin-top:12px;flex-wrap:wrap}
        .plg-meta-item{font-size:0.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:5px}
        .plg-meta-item i{color:var(--primary-lighter);font-size:0.9rem}
        .plg-meta-item strong{color:var(--text-primary)}
        .modal-content{border:none;border-radius:var(--radius-lg);box-shadow:0 25px 60px rgba(0,0,0,0.2)}
        .modal-header{border-bottom:1px solid var(--border);padding:1.5rem}
        .modal-header .modal-title{font-family:'Outfit',sans-serif;font-weight:700}
        .modal-body{padding:1.5rem}
        .modal-avatar{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:2rem;margin:0 auto 1rem;border:4px solid}
        .detail-section{background:var(--bg-body);border-radius:var(--radius-sm);padding:1rem 1.25rem;margin-bottom:1rem}
        .detail-section-title{font-weight:700;font-size:0.85rem;color:var(--primary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;display:flex;align-items:center;gap:8px}
        .detail-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)}
        .detail-row:last-child{border-bottom:none}
        .detail-label{color:var(--text-secondary);font-size:0.85rem}
        .detail-value{font-weight:600;font-size:0.9rem}
        @keyframes plgFadeIn{from{opacity:0;transform:translateY(-15px)}to{opacity:1;transform:translateY(0)}}
        @media(max-width:768px){.plg-header{padding:1.5rem}.plg-meta{gap:10px}}
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


        <div class="admin-content">

            <!-- Stat Cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-4">
                    <div class="plg-stat" style="transition-delay:0s">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="plg-stat-icon green"><i class="bi bi-people"></i></div>
                            <span class="plg-stat-label">Total Pelanggan</span>
                        </div>
                        <div class="plg-stat-value" data-target="<?= $totalPelanggan ?>"><?= $totalPelanggan ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="plg-stat" style="transition-delay:0.15s">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="plg-stat-icon blue"><i class="bi bi-check-circle"></i></div>
                            <span class="plg-stat-label">Pelanggan Aktif</span>
                        </div>
                        <div class="plg-stat-value" data-target="<?= $pelangganAktif ?>"><?= $pelangganAktif ?></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="plg-stat" style="transition-delay:0.3s">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="plg-stat-icon gold"><i class="bi bi-star"></i></div>
                            <span class="plg-stat-label">Baru Bulan Ini</span>
                        </div>
                        <div class="plg-stat-value" data-target="<?= max(0, $totalPelanggan - $pelangganAktif) ?>">0</div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="plg-filter">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text" style="border:2px solid var(--border);border-right:none;background:var(--bg-card);"><i class="bi bi-search text-secondary"></i></span>
                            <input type="text" class="form-control plg-form-control" id="searchInput" placeholder="Cari nama, email, atau telepon..." style="border-left:none;">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select plg-form-control" id="statusFilter">
                            <option value="">Semua Status</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Pelanggan Cards -->
            <div class="row g-4" id="pelangganGrid">
                <?php foreach($pelanggan as $i => $p):
                    $avatarBg = 'linear-gradient(135deg, var(--primary), var(--primary-lighter))';
                ?>
                <div class="col-lg-6 plg-card-wrapper" data-name="<?= strtolower(htmlspecialchars($p['nama'])) ?>" data-email="<?= strtolower(htmlspecialchars($p['email'])) ?>" data-phone="<?= htmlspecialchars($p['telp']) ?>" data-status="<?= htmlspecialchars($p['status']) ?>">
                    <div class="plg-card" data-bs-toggle="modal" data-bs-target="#plgModal<?= $p['id'] ?>" style="transition-delay:<?= $i * 0.1 ?>s">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="plg-avatar" style="background:<?= $avatarBg ?>;">
                                <?= htmlspecialchars(substr($p['nama'],0,1)) ?>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <div class="plg-name"><?= htmlspecialchars($p['nama']) ?></div>
                                        <div class="plg-email"><?= htmlspecialchars($p['email']) ?></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="status-dot <?= strtolower($p['status']) ?>"></span>
                                    </div>
                                </div>
                                <div class="plg-phone"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($p['telp']) ?></div>
                                <div class="plg-meta">
                                    <div class="plg-meta-item">
                                        <i class="bi bi-clock-history"></i>
                                        <?= htmlspecialchars($p['lastActive']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($pelanggan)): ?>
                <div class="col-12">
                    <p class="text-muted text-center py-4">Belum ada data pelanggan</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Pelanggan Detail Modals -->
<?php foreach($pelanggan as $p):
    $avatarBg = 'linear-gradient(135deg, var(--primary), var(--primary-lighter))';
?>
<div class="modal fade" id="plgModal<?= $p['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-vcard me-2" style="color:var(--primary-lighter)"></i>Detail Pelanggan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="modal-avatar" style="background:<?= $avatarBg ?>;border-color:var(--primary-lighter);">
                        <?= htmlspecialchars(substr($p['nama'],0,1)) ?>
                    </div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($p['nama']) ?></h4>
                    <div class="text-secondary mb-2"><?= htmlspecialchars($p['email']) ?></div>
                    <span class="ms-2 small"><span class="status-dot <?= strtolower($p['status']) ?>"></span> <?= htmlspecialchars($p['status']) ?></span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-section">
                            <div class="detail-section-title"><i class="bi bi-telephone"></i>Kontak</div>
                            <div class="detail-row"><span class="detail-label">Telepon</span><span class="detail-value mono-font"><?= htmlspecialchars($p['telp']) ?></span></div>
                            <div class="detail-row"><span class="detail-label">Alamat</span><span class="detail-value"><?= htmlspecialchars($p['alamat']) ?></span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-section">
                            <div class="detail-section-title"><i class="bi bi-award"></i>Info</div>
                            <div class="detail-row"><span class="detail-label">Member Sejak</span><span class="detail-value"><?= htmlspecialchars($p['memberSince']) ?></span></div>
                            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><?= htmlspecialchars($p['status']) ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Toast Container -->
<div class="plg-toast-container" id="plgToastContainer"></div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function animateCounter(el) {
        const target = parseInt(el.dataset.target);
        const duration = 1500;
        const start = performance.now();
        function update(now) {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.floor(eased * target);
            if (p < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.plg-stat').forEach((stat, i) => {
            setTimeout(() => {
                stat.classList.add('visible');
                const counter = stat.querySelector('.plg-stat-value');
                if (counter) animateCounter(counter);
            }, 150 * i);
        });
        document.querySelectorAll('.plg-card').forEach((card, i) => {
            setTimeout(() => card.classList.add('visible'), 300 + (100 * i));
        });
    });

    function filterCards() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        document.querySelectorAll('.plg-card-wrapper').forEach(wrapper => {
            const name = wrapper.dataset.name;
            const email = wrapper.dataset.email;
            const phone = wrapper.dataset.phone;
            const cardStatus = wrapper.dataset.status;
            const matchSearch = !search || name.includes(search) || email.includes(search) || phone.includes(search);
            const matchStatus = !status || cardStatus === status;
            wrapper.style.display = (matchSearch && matchStatus) ? '' : 'none';
        });
    }

    document.getElementById('searchInput').addEventListener('input', filterCards);
    document.getElementById('statusFilter').addEventListener('change', filterCards);
</script>
</body>
</html>
