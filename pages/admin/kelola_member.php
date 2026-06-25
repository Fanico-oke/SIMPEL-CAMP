<?php
// pages/admin/kelola_member.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/MemberLevel.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

requireRole(['admin', 'superadmin']);

$page_title = 'Kelola Member & Poin';
$current_page = 'kelola_member';

// Load members from database
$memberLevels = MemberLevel::getAll();
$members = [];
$allPelanggan = User::getAll('pelanggan', null, 200, 0);

$avatarColors = ['#8B5CF6','#F97316','#3B82F6','#EC4899','#22C55E','#D4A373','#1B4332','#2D6A4F'];

foreach ($allPelanggan as $i => $plg) {
    $memberInfo = MemberLevel::getByUser($plg['id']);
    $tier = $memberInfo['level'] ?? 'Bronze';
    $poin = (int)($memberInfo['total_poin'] ?? 0);
    $trx = (int)($memberInfo['total_transaksi'] ?? 0);
    $members[] = [
        'id' => $plg['id'],
        'nama' => $plg['nama'],
        'email' => $plg['email'],
        'tier' => $tier,
        'poin' => $poin,
        'trx' => $trx,
        'tgl' => date('d M Y', strtotime($plg['created_at'])),
        'avatar_bg' => $avatarColors[$i % count($avatarColors)],
    ];
}

$tierColors = [
    'Platinum' => ['bg'=>'linear-gradient(135deg,#6366F1,#8B5CF6)','text'=>'#fff','light'=>'rgba(139,92,246,0.1)','color'=>'#8B5CF6'],
    'Gold'     => ['bg'=>'linear-gradient(135deg,#F59E0B,#F97316)','text'=>'#fff','light'=>'rgba(249,115,22,0.1)','color'=>'#F97316'],
    'Silver'   => ['bg'=>'linear-gradient(135deg,#9CA3AF,#6B7280)','text'=>'#fff','light'=>'rgba(107,114,128,0.1)','color'=>'#6B7280'],
    'Bronze'   => ['bg'=>'linear-gradient(135deg,#D4A373,#92400E)','text'=>'#fff','light'=>'rgba(212,163,115,0.1)','color'=>'#D4A373'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781677663">
    <style>
        /* ── Stat Cards ── */
        .member-stat {
            padding: 1.25rem;
            border-radius: 14px;
            text-align: center;
            border: 1.5px solid #e5e7eb;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        .member-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.07);
        }
        .member-stat .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.15rem;
        }
        .member-stat .stat-number {
            font-size: 1.6rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            line-height: 1;
        }
        .member-stat .stat-label {
            font-size: 0.78rem;
            color: #6b7280;
            font-weight: 500;
            margin-top: 0.35rem;
        }

        /* ── Tier Badge ── */
        .tier-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.85rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.02em;
        }
        .tier-badge i { font-size: 0.7rem; }

        /* ── Member Avatar ── */
        .member-avatar {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        /* ── Action Buttons ── */
        .btn-action {
            padding: 0.3rem 0.6rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1.5px solid;
            transition: all 0.2s ease;
        }
        .btn-action:hover { transform: translateY(-1px); }
        .btn-action-view { border-color: #3B82F6; color: #3B82F6; }
        .btn-action-view:hover { background: #3B82F6; color: #fff; }
        .btn-action-poin { border-color: #8B5CF6; color: #8B5CF6; }
        .btn-action-poin:hover { background: #8B5CF6; color: #fff; }
        .btn-action-tier { border-color: #F97316; color: #F97316; }
        .btn-action-tier:hover { background: #F97316; color: #fff; }

        /* ── Tier Settings Card ── */
        .tier-settings-card {
            border-radius: 16px;
            border: 1.5px solid #e5e7eb;
            overflow: hidden;
        }
        .tier-settings-header {
            background: linear-gradient(135deg, #1B4332, #2D6A4F);
            color: #fff;
            padding: 1.25rem 1.5rem;
        }
        .tier-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s ease;
        }
        .tier-row:last-child { border-bottom: none; }
        .tier-row:hover { background: #f9fafb; }
        .tier-icon-lg {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
            flex-shrink: 0;
        }
        .tier-input {
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            width: 80px;
            text-align: center;
            transition: all 0.2s ease;
        }
        .tier-input:focus {
            border-color: #52B788;
            box-shadow: 0 0 0 3px rgba(82,183,136,0.12);
            outline: none;
        }
        .discount-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
        }

        /* ── Poin Modal ── */
        .poin-adjust-btn {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff;
        }
        .poin-adjust-btn:hover { border-color: #52B788; color: #2D6A4F; }
        .poin-adjust-btn.minus:hover { border-color: #ef4444; color: #ef4444; }
        .poin-display {
            font-size: 2rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            min-width: 80px;
            text-align: center;
        }
        .modal-content { border-radius: 16px; border: none; }
        .modal-header { border-bottom: 1px solid #f3f4f6; }
        .modal-footer { border-top: 1px solid #f3f4f6; }
        .form-control-modal {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
        }
        .form-control-modal:focus {
            border-color: #52B788;
            box-shadow: 0 0 0 3px rgba(82,183,136,0.15);
        }
        .btn-save-tier {
            background: linear-gradient(135deg, #1B4332, #2D6A4F);
            border: none;
            border-radius: 10px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(27,67,50,0.2);
        }
        .btn-save-tier:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(27,67,50,0.3);
            background: linear-gradient(135deg, #2D6A4F, #52B788);
            color: #fff;
        }
        .poin-bar {
            height: 6px;
            border-radius: 3px;
            background: #e5e7eb;
            overflow: hidden;
        }
        .poin-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


        <!-- Content -->
        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Kelola Member & Poin</h2>
                    <p class="text-secondary mb-0">Manajemen tier membership dan poin pelanggan</p>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(27,67,50,0.1);color:#1B4332;"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-number" style="color:#1B4332;">23</div>
                        <div class="stat-label">Total Member</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(212,163,115,0.15);color:#92400E;"><i class="bi bi-award"></i></div>
                        <div class="stat-number" style="color:#D4A373;">8</div>
                        <div class="stat-label">Bronze</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(107,114,128,0.12);color:#6B7280;"><i class="bi bi-award-fill"></i></div>
                        <div class="stat-number" style="color:#6B7280;">7</div>
                        <div class="stat-label">Silver</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(249,115,22,0.12);color:#F97316;"><i class="bi bi-trophy"></i></div>
                        <div class="stat-number" style="color:#F97316;">5</div>
                        <div class="stat-label">Gold</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(139,92,246,0.12);color:#8B5CF6;"><i class="bi bi-gem"></i></div>
                        <div class="stat-number" style="color:#8B5CF6;">3</div>
                        <div class="stat-label">Platinum</div>
                    </div>
                </div>
            </div>

            <!-- Member Table -->
            <div class="sc-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-table me-2 text-secondary"></i>Daftar Member</h5>
                    <div class="input-group" style="max-width:300px;">
                        <span class="input-group-text bg-white" style="border-radius:10px 0 0 10px;"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Cari member..." style="border-radius:0 10px 10px 0;" id="searchMember" onkeyup="filterMembers()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0" id="memberTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Nama</th>
                                <th>Tier</th>
                                <th>Total Poin</th>
                                <th>Total Transaksi</th>
                                <th>Terdaftar</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($members as $i => $m):
                                $tc = $tierColors[$m['tier']];
                                $maxPoin = $m['tier']==='Platinum' ? 1000 : ($m['tier']==='Gold' ? 700 : ($m['tier']==='Silver' ? 400 : 200));
                                $poinPct = min(100, ($m['poin'] / $maxPoin) * 100);
                            ?>
                            <tr data-name="<?= strtolower($m['nama']) ?>">
                                <td><?= $i+1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="member-avatar" style="background:<?= $m['avatar_bg'] ?>;">
                                            <?= strtoupper(substr($m['nama'],0,1)) . strtoupper(substr(explode(' ',$m['nama'])[1] ?? '',0,1)) ?>
                                        </div>
                                        <div>
                                            <span class="fw-bold d-block"><?= $m['nama'] ?></span>
                                            <span class="text-secondary small"><?= $m['email'] ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="tier-badge" style="background:<?= $tc['bg'] ?>;">
                                        <i class="bi bi-<?= $m['tier']==='Platinum' ? 'gem' : ($m['tier']==='Gold' ? 'trophy' : ($m['tier']==='Silver' ? 'award-fill' : 'award')) ?>"></i>
                                        <?= $m['tier'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-bold mono-font"><?= number_format($m['poin']) ?></span>
                                        <span class="text-secondary small"> poin</span>
                                    </div>
                                    <div class="poin-bar mt-1" style="width:100px;">
                                        <div class="poin-bar-fill" style="width:<?= $poinPct ?>%;background:<?= $tc['bg'] ?>;"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info px-2 py-1"><?= $m['trx'] ?> Transaksi</span>
                                </td>
                                <td><span class="text-secondary"><?= $m['tgl'] ?></span></td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button class="btn btn-action btn-action-view" title="Lihat Detail"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-action btn-action-poin" title="Adjust Poin" data-bs-toggle="modal" data-bs-target="#poinModal-<?= $m['id'] ?>"><i class="bi bi-plus-slash-minus"></i></button>
                                        <button class="btn btn-action btn-action-tier" title="Ubah Tier"><i class="bi bi-arrow-up-down"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="mt-4">
                    <ul class="pagination justify-content-end mb-0">
                        <li class="page-item disabled"><a class="page-link" href="#">Sebelumnya</a></li>
                        <li class="page-item active"><a class="page-link" href="#" style="background-color:var(--primary);border-color:var(--primary);">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">Selanjutnya</a></li>
                    </ul>
                </nav>
            </div>

            <!-- Tier Settings Card -->
            <div class="tier-settings-card">
                <div class="tier-settings-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-sliders me-2"></i>Pengaturan Tier</h5>
                            <small class="opacity-75">Konfigurasi threshold poin dan diskon untuk setiap tier</small>
                        </div>
                        <button class="btn btn-sm text-white fw-medium" style="background:rgba(255,255,255,0.15);border-radius:8px;padding:0.4rem 1rem;" onclick="showSaveTierToast()">
                            <i class="bi bi-check2 me-1"></i>Simpan
                        </button>
                    </div>
                </div>
                <div class="bg-white">
                    <!-- Bronze -->
                    <div class="tier-row">
                        <div class="tier-icon-lg" style="background:linear-gradient(135deg,#D4A373,#92400E);"><i class="bi bi-award"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold">Bronze</span>
                                <span class="discount-badge" style="background:rgba(212,163,115,0.15);color:#92400E;">Tier Dasar</span>
                            </div>
                            <small class="text-secondary">Level awal untuk semua member baru</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="tier-input" value="0" readonly style="background:#f3f4f6;">
                            <span class="text-secondary fw-medium">—</span>
                            <input type="number" class="tier-input" value="199" id="bronzeMax">
                            <span class="text-secondary small fw-medium">poin</span>
                        </div>
                        <div style="min-width:85px;text-align:right;">
                            <span class="discount-badge" style="background:rgba(107,114,128,0.1);color:#6b7280;">Tanpa diskon</span>
                        </div>
                    </div>
                    <!-- Silver -->
                    <div class="tier-row">
                        <div class="tier-icon-lg" style="background:linear-gradient(135deg,#9CA3AF,#6B7280);"><i class="bi bi-award-fill"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold">Silver</span>
                                <span class="discount-badge" style="background:rgba(107,114,128,0.12);color:#6B7280;">Diskon 3%</span>
                            </div>
                            <small class="text-secondary">Member dengan aktivitas regular</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="tier-input" value="200" id="silverMin">
                            <span class="text-secondary fw-medium">—</span>
                            <input type="number" class="tier-input" value="399" id="silverMax">
                            <span class="text-secondary small fw-medium">poin</span>
                        </div>
                        <div style="min-width:85px;text-align:right;">
                            <div class="input-group input-group-sm" style="width:85px;">
                                <input type="number" class="tier-input" value="3" style="width:45px;border-radius:8px 0 0 8px;">
                                <span class="input-group-text bg-white" style="border-radius:0 8px 8px 0;font-size:0.8rem;">%</span>
                            </div>
                        </div>
                    </div>
                    <!-- Gold -->
                    <div class="tier-row">
                        <div class="tier-icon-lg" style="background:linear-gradient(135deg,#F59E0B,#F97316);"><i class="bi bi-trophy"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold">Gold</span>
                                <span class="discount-badge" style="background:rgba(249,115,22,0.12);color:#F97316;">Diskon 5%</span>
                            </div>
                            <small class="text-secondary">Member loyal dengan banyak transaksi</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="tier-input" value="400" id="goldMin">
                            <span class="text-secondary fw-medium">—</span>
                            <input type="number" class="tier-input" value="699" id="goldMax">
                            <span class="text-secondary small fw-medium">poin</span>
                        </div>
                        <div style="min-width:85px;text-align:right;">
                            <div class="input-group input-group-sm" style="width:85px;">
                                <input type="number" class="tier-input" value="5" style="width:45px;border-radius:8px 0 0 8px;">
                                <span class="input-group-text bg-white" style="border-radius:0 8px 8px 0;font-size:0.8rem;">%</span>
                            </div>
                        </div>
                    </div>
                    <!-- Platinum -->
                    <div class="tier-row">
                        <div class="tier-icon-lg" style="background:linear-gradient(135deg,#6366F1,#8B5CF6);"><i class="bi bi-gem"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold">Platinum</span>
                                <span class="discount-badge" style="background:rgba(139,92,246,0.12);color:#8B5CF6;">Diskon 10%</span>
                            </div>
                            <small class="text-secondary">Member VIP dengan benefit eksklusif</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="tier-input" value="700" id="platMin">
                            <span class="text-secondary fw-medium">+</span>
                            <span class="text-secondary small fw-medium ms-1">poin</span>
                        </div>
                        <div style="min-width:85px;text-align:right;">
                            <div class="input-group input-group-sm" style="width:85px;">
                                <input type="number" class="tier-input" value="10" style="width:45px;border-radius:8px 0 0 8px;">
                                <span class="input-group-text bg-white" style="border-radius:0 8px 8px 0;font-size:0.8rem;">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Adjust Poin Modals -->
<?php foreach($members as $m): ?>
<div class="modal fade" id="poinModal-<?= $m['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg">
            <div class="modal-header px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(139,92,246,0.12);">
                        <i class="bi bi-plus-slash-minus" style="color:#8B5CF6;"></i>
                    </div>
                    <h6 class="modal-title fw-bold mb-0">Adjust Poin</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3 text-center">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                    <div class="member-avatar" style="background:<?= $m['avatar_bg'] ?>;width:36px;height:36px;font-size:0.75rem;">
                        <?= strtoupper(substr($m['nama'],0,1)) ?>
                    </div>
                    <span class="fw-bold"><?= $m['nama'] ?></span>
                </div>
                <small class="text-secondary d-block mb-3">Poin saat ini: <span class="fw-bold mono-font"><?= number_format($m['poin']) ?></span></small>

                <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                    <div class="poin-adjust-btn minus" onclick="adjustPoin(this, -10, <?= $m['id'] ?>)"><i class="bi bi-dash-lg"></i></div>
                    <div class="poin-display" id="poinValue-<?= $m['id'] ?>">0</div>
                    <div class="poin-adjust-btn" onclick="adjustPoin(this, 10, <?= $m['id'] ?>)"><i class="bi bi-plus-lg"></i></div>
                </div>

                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold small">Jumlah Manual</label>
                    <input type="number" class="form-control form-control-modal text-center" value="0" id="poinInput-<?= $m['id'] ?>" onchange="document.getElementById('poinValue-<?= $m['id'] ?>').textContent = this.value">
                </div>

                <div class="text-start">
                    <label class="form-label fw-semibold small">Alasan</label>
                    <select class="form-select form-control-modal" id="poinReason-<?= $m['id'] ?>">
                        <option>Bonus transaksi</option>
                        <option>Koreksi admin</option>
                        <option>Hadiah event</option>
                        <option>Penalti keterlambatan</option>
                        <option>Lainnya</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer px-4 py-3">
                <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal" style="border-radius:10px;">Batal</button>
                <button type="button" class="btn text-white fw-semibold" data-bs-dismiss="modal" onclick="showSaveTierToast()" style="background:linear-gradient(135deg,#1B4332,#2D6A4F);border-radius:10px;padding:0.5rem 1.5rem;">
                    <i class="bi bi-check2 me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080;">
    <div id="saveToast" class="toast align-items-center text-white border-0" role="alert" style="background:linear-gradient(135deg,#1B4332,#2D6A4F);border-radius:14px;">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <span>Perubahan berhasil disimpan!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function adjustPoin(btn, amount, memberId) {
        const display = document.getElementById('poinValue-' + memberId);
        const input = document.getElementById('poinInput-' + memberId);
        let current = parseInt(display.textContent) || 0;
        current += amount;
        display.textContent = current;
        input.value = current;

        // Color feedback
        if (current > 0) display.style.color = '#22c55e';
        else if (current < 0) display.style.color = '#ef4444';
        else display.style.color = '#1f2937';
    }

    function filterMembers() {
        const search = document.getElementById('searchMember').value.toLowerCase();
        document.querySelectorAll('#memberTable tbody tr').forEach(row => {
            row.style.display = row.dataset.name.includes(search) ? '' : 'none';
        });
    }

    function showSaveTierToast() {
        const toast = new bootstrap.Toast(document.getElementById('saveToast'), { delay: 3000 });
        toast.show();
    }
</script>
</body>
</html>
SESSION['nama'] : 'Admin' ?></span>
            </div>
        </div>

        <!-- Content -->
        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Kelola Member & Poin</h2>
                    <p class="text-secondary mb-0">Manajemen tier membership dan poin pelanggan</p>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(27,67,50,0.1);color:#1B4332;"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-number" style="color:#1B4332;">23</div>
                        <div class="stat-label">Total Member</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(212,163,115,0.15);color:#92400E;"><i class="bi bi-award"></i></div>
                        <div class="stat-number" style="color:#D4A373;">8</div>
                        <div class="stat-label">Bronze</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(107,114,128,0.12);color:#6B7280;"><i class="bi bi-award-fill"></i></div>
                        <div class="stat-number" style="color:#6B7280;">7</div>
                        <div class="stat-label">Silver</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(249,115,22,0.12);color:#F97316;"><i class="bi bi-trophy"></i></div>
                        <div class="stat-number" style="color:#F97316;">5</div>
                        <div class="stat-label">Gold</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="member-stat">
                        <div class="stat-icon" style="background:rgba(139,92,246,0.12);color:#8B5CF6;"><i class="bi bi-gem"></i></div>
                        <div class="stat-number" style="color:#8B5CF6;">3</div>
                        <div class="stat-label">Platinum</div>
                    </div>
                </div>
            </div>

            <!-- Member Table -->
            <div class="sc-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-table me-2 text-secondary"></i>Daftar Member</h5>
                    <div class="input-group" style="max-width:300px;">
                        <span class="input-group-text bg-white" style="border-radius:10px 0 0 10px;"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Cari member..." style="border-radius:0 10px 10px 0;" id="searchMember" onkeyup="filterMembers()">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0" id="memberTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Nama</th>
                                <th>Tier</th>
                                <th>Total Poin</th>
                                <th>Total Transaksi</th>
                                <th>Terdaftar</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($members as $i => $m):
                                $tc = $tierColors[$m['tier']];
                                $maxPoin = $m['tier']==='Platinum' ? 1000 : ($m['tier']==='Gold' ? 700 : ($m['tier']==='Silver' ? 400 : 200));
                                $poinPct = min(100, ($m['poin'] / $maxPoin) * 100);
                            ?>
                            <tr data-name="<?= strtolower($m['nama']) ?>">
                                <td><?= $i+1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="member-avatar" style="background:<?= $m['avatar_bg'] ?>;">
                                            <?= strtoupper(substr($m['nama'],0,1)) . strtoupper(substr(explode(' ',$m['nama'])[1] ?? '',0,1)) ?>
                                        </div>
                                        <div>
                                            <span class="fw-bold d-block"><?= $m['nama'] ?></span>
                                            <span class="text-secondary small"><?= $m['email'] ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="tier-badge" style="background:<?= $tc['bg'] ?>;">
                                        <i class="bi bi-<?= $m['tier']==='Platinum' ? 'gem' : ($m['tier']==='Gold' ? 'trophy' : ($m['tier']==='Silver' ? 'award-fill' : 'award')) ?>"></i>
                                        <?= $m['tier'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-bold mono-font"><?= number_format($m['poin']) ?></span>
                                        <span class="text-secondary small"> poin</span>
                                    </div>
                                    <div class="poin-bar mt-1" style="width:100px;">
                                        <div class="poin-bar-fill" style="width:<?= $poinPct ?>%;background:<?= $tc['bg'] ?>;"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info px-2 py-1"><?= $m['trx'] ?> Transaksi</span>
                                </td>
                                <td><span class="text-secondary"><?= $m['tgl'] ?></span></td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button class="btn btn-action btn-action-view" title="Lihat Detail"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-action btn-action-poin" title="Adjust Poin" data-bs-toggle="modal" data-bs-target="#poinModal-<?= $m['id'] ?>"><i class="bi bi-plus-slash-minus"></i></button>
                                        <button class="btn btn-action btn-action-tier" title="Ubah Tier"><i class="bi bi-arrow-up-down"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="mt-4">
                    <ul class="pagination justify-content-end mb-0">
                        <li class="page-item disabled"><a class="page-link" href="#">Sebelumnya</a></li>
                        <li class="page-item active"><a class="page-link" href="#" style="background-color:var(--primary);border-color:var(--primary);">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">Selanjutnya</a></li>
                    </ul>
                </nav>
            </div>

            <!-- Tier Settings Card -->
            <div class="tier-settings-card">
                <div class="tier-settings-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-sliders me-2"></i>Pengaturan Tier</h5>
                            <small class="opacity-75">Konfigurasi threshold poin dan diskon untuk setiap tier</small>
                        </div>
                        <button class="btn btn-sm text-white fw-medium" style="background:rgba(255,255,255,0.15);border-radius:8px;padding:0.4rem 1rem;" onclick="showSaveTierToast()">
                            <i class="bi bi-check2 me-1"></i>Simpan
                        </button>
                    </div>
                </div>
                <div class="bg-white">
                    <!-- Bronze -->
                    <div class="tier-row">
                        <div class="tier-icon-lg" style="background:linear-gradient(135deg,#D4A373,#92400E);"><i class="bi bi-award"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold">Bronze</span>
                                <span class="discount-badge" style="background:rgba(212,163,115,0.15);color:#92400E;">Tier Dasar</span>
                            </div>
                            <small class="text-secondary">Level awal untuk semua member baru</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="tier-input" value="0" readonly style="background:#f3f4f6;">
                            <span class="text-secondary fw-medium">—</span>
                            <input type="number" class="tier-input" value="199" id="bronzeMax">
                            <span class="text-secondary small fw-medium">poin</span>
                        </div>
                        <div style="min-width:85px;text-align:right;">
                            <span class="discount-badge" style="background:rgba(107,114,128,0.1);color:#6b7280;">Tanpa diskon</span>
                        </div>
                    </div>
                    <!-- Silver -->
                    <div class="tier-row">
                        <div class="tier-icon-lg" style="background:linear-gradient(135deg,#9CA3AF,#6B7280);"><i class="bi bi-award-fill"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold">Silver</span>
                                <span class="discount-badge" style="background:rgba(107,114,128,0.12);color:#6B7280;">Diskon 3%</span>
                            </div>
                            <small class="text-secondary">Member dengan aktivitas regular</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="tier-input" value="200" id="silverMin">
                            <span class="text-secondary fw-medium">—</span>
                            <input type="number" class="tier-input" value="399" id="silverMax">
                            <span class="text-secondary small fw-medium">poin</span>
                        </div>
                        <div style="min-width:85px;text-align:right;">
                            <div class="input-group input-group-sm" style="width:85px;">
                                <input type="number" class="tier-input" value="3" style="width:45px;border-radius:8px 0 0 8px;">
                                <span class="input-group-text bg-white" style="border-radius:0 8px 8px 0;font-size:0.8rem;">%</span>
                            </div>
                        </div>
                    </div>
                    <!-- Gold -->
                    <div class="tier-row">
                        <div class="tier-icon-lg" style="background:linear-gradient(135deg,#F59E0B,#F97316);"><i class="bi bi-trophy"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold">Gold</span>
                                <span class="discount-badge" style="background:rgba(249,115,22,0.12);color:#F97316;">Diskon 5%</span>
                            </div>
                            <small class="text-secondary">Member loyal dengan banyak transaksi</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="tier-input" value="400" id="goldMin">
                            <span class="text-secondary fw-medium">—</span>
                            <input type="number" class="tier-input" value="699" id="goldMax">
                            <span class="text-secondary small fw-medium">poin</span>
                        </div>
                        <div style="min-width:85px;text-align:right;">
                            <div class="input-group input-group-sm" style="width:85px;">
                                <input type="number" class="tier-input" value="5" style="width:45px;border-radius:8px 0 0 8px;">
                                <span class="input-group-text bg-white" style="border-radius:0 8px 8px 0;font-size:0.8rem;">%</span>
                            </div>
                        </div>
                    </div>
                    <!-- Platinum -->
                    <div class="tier-row">
                        <div class="tier-icon-lg" style="background:linear-gradient(135deg,#6366F1,#8B5CF6);"><i class="bi bi-gem"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold">Platinum</span>
                                <span class="discount-badge" style="background:rgba(139,92,246,0.12);color:#8B5CF6;">Diskon 10%</span>
                            </div>
                            <small class="text-secondary">Member VIP dengan benefit eksklusif</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="tier-input" value="700" id="platMin">
                            <span class="text-secondary fw-medium">+</span>
                            <span class="text-secondary small fw-medium ms-1">poin</span>
                        </div>
                        <div style="min-width:85px;text-align:right;">
                            <div class="input-group input-group-sm" style="width:85px;">
                                <input type="number" class="tier-input" value="10" style="width:45px;border-radius:8px 0 0 8px;">
                                <span class="input-group-text bg-white" style="border-radius:0 8px 8px 0;font-size:0.8rem;">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Adjust Poin Modals -->
<?php foreach($members as $m): ?>
<div class="modal fade" id="poinModal-<?= $m['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg">
            <div class="modal-header px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(139,92,246,0.12);">
                        <i class="bi bi-plus-slash-minus" style="color:#8B5CF6;"></i>
                    </div>
                    <h6 class="modal-title fw-bold mb-0">Adjust Poin</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3 text-center">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                    <div class="member-avatar" style="background:<?= $m['avatar_bg'] ?>;width:36px;height:36px;font-size:0.75rem;">
                        <?= strtoupper(substr($m['nama'],0,1)) ?>
                    </div>
                    <span class="fw-bold"><?= $m['nama'] ?></span>
                </div>
                <small class="text-secondary d-block mb-3">Poin saat ini: <span class="fw-bold mono-font"><?= number_format($m['poin']) ?></span></small>

                <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                    <div class="poin-adjust-btn minus" onclick="adjustPoin(this, -10, <?= $m['id'] ?>)"><i class="bi bi-dash-lg"></i></div>
                    <div class="poin-display" id="poinValue-<?= $m['id'] ?>">0</div>
                    <div class="poin-adjust-btn" onclick="adjustPoin(this, 10, <?= $m['id'] ?>)"><i class="bi bi-plus-lg"></i></div>
                </div>

                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold small">Jumlah Manual</label>
                    <input type="number" class="form-control form-control-modal text-center" value="0" id="poinInput-<?= $m['id'] ?>" onchange="document.getElementById('poinValue-<?= $m['id'] ?>').textContent = this.value">
                </div>

                <div class="text-start">
                    <label class="form-label fw-semibold small">Alasan</label>
                    <select class="form-select form-control-modal" id="poinReason-<?= $m['id'] ?>">
                        <option>Bonus transaksi</option>
                        <option>Koreksi admin</option>
                        <option>Hadiah event</option>
                        <option>Penalti keterlambatan</option>
                        <option>Lainnya</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer px-4 py-3">
                <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal" style="border-radius:10px;">Batal</button>
                <button type="button" class="btn text-white fw-semibold" data-bs-dismiss="modal" onclick="showSaveTierToast()" style="background:linear-gradient(135deg,#1B4332,#2D6A4F);border-radius:10px;padding:0.5rem 1.5rem;">
                    <i class="bi bi-check2 me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080;">
    <div id="saveToast" class="toast align-items-center text-white border-0" role="alert" style="background:linear-gradient(135deg,#1B4332,#2D6A4F);border-radius:14px;">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <span>Perubahan berhasil disimpan!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function adjustPoin(btn, amount, memberId) {
        const display = document.getElementById('poinValue-' + memberId);
        const input = document.getElementById('poinInput-' + memberId);
        let current = parseInt(display.textContent) || 0;
        current += amount;
        display.textContent = current;
        input.value = current;

        // Color feedback
        if (current > 0) display.style.color = '#22c55e';
        else if (current < 0) display.style.color = '#ef4444';
        else display.style.color = '#1f2937';
    }

    function filterMembers() {
        const search = document.getElementById('searchMember').value.toLowerCase();
        document.querySelectorAll('#memberTable tbody tr').forEach(row => {
            row.style.display = row.dataset.name.includes(search) ? '' : 'none';
        });
    }

    function showSaveTierToast() {
        const toast = new bootstrap.Toast(document.getElementById('saveToast'), { delay: 3000 });
        toast.show();
    }
</script>
</body>
</html>
