<?php
// pages/admin/kelola_member.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/MemberLevel.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';
require_once dirname(__DIR__, 2) . '/classes/MemberReward.php';
require_once dirname(__DIR__, 2) . '/classes/MemberBenefit.php';

requireRole(['admin', 'superadmin']);

$page_title = 'Kelola Member & Poin';
$current_page = 'kelola_member';

// Load members from database
$memberLevels = MemberLevel::getAll();
$members = [];
$allPelanggan = User::getAll('pelanggan', null, 200, 0);
$rewards = MemberReward::getAll();
$benefits = MemberBenefit::getAll();

// Load redemption history
$stmtRiwayat = Database::getInstance()->prepare("
    SELECT rp.*, u.nama AS user_nama, mr.nama_reward 
    FROM riwayat_poin rp 
    LEFT JOIN users u ON rp.user_id = u.id 
    LEFT JOIN member_rewards mr ON rp.keterangan LIKE CONCAT('%', mr.nama_reward, '%')
    WHERE rp.jenis = 'keluar' 
    ORDER BY rp.tanggal DESC 
    LIMIT 50
");
$stmtRiwayat->execute();
$riwayatPenukaran = $stmtRiwayat->fetchAll();

$avatarColors = ['#8B5CF6','#F97316','#3B82F6','#EC4899','#22C55E','#D4A373','#1B4332','#2D6A4F'];

$stats = [
    'Total' => count($allPelanggan),
    'Bronze' => 0,
    'Silver' => 0,
    'Gold' => 0,
    'Platinum' => 0
];

foreach ($allPelanggan as $i => $plg) {
    $memberInfo = MemberLevel::getByUser($plg['id']);
    $tier = ucfirst($memberInfo['level'] ?? 'bronze');
    if (!in_array($tier, ['Bronze','Silver','Gold','Platinum'])) $tier = 'Bronze';
    $poin = (int)($memberInfo['poin'] ?? 0);
    $trx = (int)($memberInfo['total_transaksi'] ?? 0);
    
    if (isset($stats[$tier])) $stats[$tier]++;
    
    $members[] = [
        'id' => $plg['id'],
        'nama' => $plg['nama'],
        'email' => $plg['email'],
        'telp' => $plg['no_telp'] ?? $plg['telepon'] ?? '-',
        'alamat' => $plg['alamat'] ?? '-',
        'status' => ucfirst($plg['status'] ?? 'aktif'),
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
        /* -- Stat Cards (Matching transaksi.php) -- */
        .stat-mini {
            background: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all .3s;
        }
        .stat-mini:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .stat-mini-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .stat-mini-val {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: #1B4332;
            line-height: 1;
        }
        .stat-mini-label {
            font-size: .78rem;
            color: #6c757d;
            font-weight: 500;
            margin-top: 4px;
        }

        /* -- Tier Badge -- */
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

        /* -- Member Avatar -- */
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

        /* -- Action Buttons -- */
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

        /* -- Tier Settings Card -- */
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

        /* -- Poin Modal -- */
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


            <!-- Stats Bar -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg">
                    <div class="stat-mini stagger-item" style="border-left-color:#1B4332;">
                        <div class="stat-mini-icon" style="background:rgba(27,67,50,0.1);color:#1B4332;"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="stat-mini-val"><?= $stats['Total'] ?></div>
                            <div class="stat-mini-label">Total Pelanggan</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-mini stagger-item" style="border-left-color:#D4A373;">
                        <div class="stat-mini-icon" style="background:rgba(212,163,115,0.15);color:#92400E;"><i class="bi bi-award"></i></div>
                        <div>
                            <div class="stat-mini-val"><?= $stats['Bronze'] ?></div>
                            <div class="stat-mini-label">Bronze</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-mini stagger-item" style="border-left-color:#6B7280;">
                        <div class="stat-mini-icon" style="background:rgba(107,114,128,0.12);color:#6B7280;"><i class="bi bi-award-fill"></i></div>
                        <div>
                            <div class="stat-mini-val"><?= $stats['Silver'] ?></div>
                            <div class="stat-mini-label">Silver</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-mini stagger-item" style="border-left-color:#F97316;">
                        <div class="stat-mini-icon" style="background:rgba(249,115,22,0.12);color:#F97316;"><i class="bi bi-trophy"></i></div>
                        <div>
                            <div class="stat-mini-val"><?= $stats['Gold'] ?></div>
                            <div class="stat-mini-label">Gold</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-mini stagger-item" style="border-left-color:#8B5CF6;">
                        <div class="stat-mini-icon" style="background:rgba(139,92,246,0.12);color:#8B5CF6;"><i class="bi bi-gem"></i></div>
                        <div>
                            <div class="stat-mini-val"><?= $stats['Platinum'] ?></div>
                            <div class="stat-mini-label">Platinum</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tabs -->
            <style>
                :root {
                    --t-dark: #1B4332;
                    --t-mid: #2D6A4F;
                    --t-light: #52B788;
                }
                
                /* Tabs matching transaksi.php */
                #memberTabs {
                    display: flex; gap: 6px; background: #fff; border-radius: 14px; padding: 6px; box-shadow: 0 2px 12px rgba(27,67,50,0.06); margin-bottom: 28px; flex-wrap: wrap; border: none;
                }
                #memberTabs .nav-item { margin: 0; }
                #memberTabs .nav-link {
                    padding: 10px 22px;
                    border-radius: 10px;
                    border: none;
                    background: transparent;
                    font-weight: 600;
                    color: #6c757d;
                    cursor: pointer;
                    transition: all .3s;
                    font-family: 'Inter', sans-serif;
                    font-size: .88rem;
                    white-space: nowrap;
                    display: flex;
                    align-items: center;
                }
                #memberTabs .nav-link:hover:not(.active) {
                    background: rgba(82,183,136,0.08);
                    color: var(--t-mid);
                }
                #memberTabs .nav-link.active {
                    background: linear-gradient(135deg, var(--t-mid), var(--t-light)) !important;
                    color: #fff !important;
                    box-shadow: 0 4px 16px rgba(45,106,79,0.3) !important;
                }
                #memberTabs .nav-link i { margin-right: 6px; }
                #memberTabs .nav-link.active i { color: #fff !important; }
                
                /* Table matching transaksi.php */
                .tab-content .sc-card {
                    background: #fff;
                    border-radius: 16px;
                    box-shadow: 0 2px 14px rgba(0,0,0,0.05);
                    overflow: hidden;
                    padding: 0 !important;
                    border: none;
                }
                .tab-content .sc-card > .d-flex {
                    padding: 1rem 1.25rem;
                    border-bottom: 1px solid rgba(0,0,0,0.05);
                    margin-bottom: 0 !important;
                }
                .tab-content .sc-card > .d-flex h5 {
                    font-family: 'Outfit', sans-serif;
                    font-weight: 700;
                    font-size: 1.1rem;
                    color: var(--t-dark);
                }
                .tab-content .sc-card > .d-flex h5 i { color: var(--t-mid) !important; }
                .tab-content .admin-table th {
                    background: rgba(82,183,136,0.06) !important;
                    font-size: .82rem !important;
                    font-weight: 600 !important;
                    color: #6c757d !important;
                    text-transform: uppercase !important;
                    letter-spacing: .5px !important;
                    padding: 14px 16px !important;
                    border-bottom: 2px solid #e8e8e8 !important;
                }
                .tab-content .admin-table td {
                    padding: 14px 16px !important;
                    vertical-align: middle !important;
                    border-color: #f0f0f0 !important;
                    font-size: .88rem !important;
                }
                .tab-content .admin-table tbody tr:hover {
                    background: rgba(82,183,136,0.03) !important;
                }
            </style>
            <ul class="nav nav-pills mb-4" id="memberTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="member-tab" data-bs-toggle="pill" data-bs-target="#tab-member" type="button" role="tab"><i class="bi bi-people"></i>Daftar Member</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reward-tab" data-bs-toggle="pill" data-bs-target="#tab-reward" type="button" role="tab"><i class="bi bi-gift"></i>Kelola Reward</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="benefit-tab" data-bs-toggle="pill" data-bs-target="#tab-benefit" type="button" role="tab"><i class="bi bi-star"></i>Kelola Keuntungan</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="riwayat-tab" data-bs-toggle="pill" data-bs-target="#tab-riwayat" type="button" role="tab"><i class="bi bi-clock-history"></i>Riwayat Penukaran</button>
                </li>
            </ul>

            <div class="tab-content" id="memberTabsContent">
                <!-- Member Table Tab -->
                <div class="tab-pane fade show active" id="tab-member" role="tabpanel">
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
                                <th>Kontak & Alamat</th>
                                <th>Status</th>
                                <th>Tier</th>
                                <th>Total Poin</th>
                                <th>Terdaftar</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($members as $i => $m):
                                $tc = $tierColors[$m['tier']] ?? $tierColors['Bronze'];
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
                                    <div class="small">
                                        <div class="mb-1"><i class="bi bi-telephone-fill text-muted me-1"></i><?= $m['telp'] ?></div>
                                        <div><i class="bi bi-geo-alt-fill text-muted me-1"></i><span class="text-truncate d-inline-block" style="max-width:150px;vertical-align:bottom;" title="<?= htmlspecialchars($m['alamat']) ?>"><?= $m['alamat'] ?></span></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if(strtolower($m['status']) === 'aktif'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill me-1"></i>Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle-fill me-1"></i>Nonaktif</span>
                                    <?php endif; ?>
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
                                    <div class="small mt-1 text-muted"><i class="bi bi-arrow-left-right me-1"></i><?= $m['trx'] ?> trx</div>
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
                            <span class="text-secondary fw-medium">-</span>
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
                            <span class="text-secondary fw-medium">-</span>
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
                            <span class="text-secondary fw-medium">-</span>
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
                <!-- End Member Table Tab -->

                <!-- Reward Tab -->
                <div class="tab-pane fade" id="tab-reward" role="tabpanel">
                    <div class="sc-card p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0"><i class="bi bi-gift me-2 text-primary"></i>Kelola Reward</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rewardModal" onclick="resetRewardModal()">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Reward
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table admin-table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Reward</th>
                                        <th>Poin</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($rewards as $r): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="member-avatar" style="background:var(--primary);color:#fff;">
                                                    <i class="bi <?= htmlspecialchars($r['icon']) ?>"></i>
                                                </div>
                                                <div>
                                                    <span class="fw-bold d-block"><?= htmlspecialchars($r['nama_reward']) ?></span>
                                                    <span class="text-secondary small"><?= htmlspecialchars($r['deskripsi']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-warning text-dark"><?= $r['poin_dibutuhkan'] ?> Poin</span></td>
                                        <td>
                                            <?php if($r['status'] == 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editReward(<?= htmlspecialchars(json_encode($r)) ?>)"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteReward(<?= $r['id'] ?>)"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Benefit Tab -->
                <div class="tab-pane fade" id="tab-benefit" role="tabpanel">
                    <div class="sc-card p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0"><i class="bi bi-star me-2 text-warning"></i>Kelola Keuntungan</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#benefitModal" onclick="resetBenefitModal()">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Keuntungan
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table admin-table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Keuntungan</th>
                                        <th>Warna Tema</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($benefits as $b): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="member-avatar" style="background:<?= htmlspecialchars($b['warna']) ?>;color:#fff;">
                                                    <i class="bi <?= htmlspecialchars($b['icon']) ?>"></i>
                                                </div>
                                                <div>
                                                    <span class="fw-bold d-block"><?= htmlspecialchars($b['nama_benefit']) ?></span>
                                                    <span class="text-secondary small"><?= htmlspecialchars($b['deskripsi']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><code><?= htmlspecialchars($b['warna']) ?></code></td>
                                        <td>
                                            <?php if($b['status'] == 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editBenefit(<?= htmlspecialchars(json_encode($b)) ?>)"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteBenefit(<?= $b['id'] ?>)"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

<!-- Riwayat Penukaran Tab -->
<div class="tab-pane fade" id="tab-riwayat" role="tabpanel">
    <div class="sc-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-info"></i>Riwayat Penukaran</h5>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Pelanggan</th>
                        <th>Keterangan</th>
                        <th>Poin Digunakan</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riwayatPenukaran)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox display-6 d-block mb-2 opacity-25"></i>Belum ada riwayat penukaran</td></tr>
                    <?php else: ?>
                    <?php foreach($riwayatPenukaran as $idx => $rw): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <span class="fw-bold"><?= htmlspecialchars($rw['user_nama'] ?? 'User #'.$rw['user_id']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($rw['keterangan']) ?></td>
                        <td><span class="badge bg-danger bg-opacity-10 text-danger">-<?= $rw['jumlah'] ?> Poin</span></td>
                        <td><span class="text-secondary"><?= date('d M Y H:i', strtotime($rw['tanggal'])) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

            </div> <!-- end tab-content -->

        </div> <!-- end admin-content -->
    </div>
</div>

<!-- Reward Modal -->
<div class="modal fade" id="rewardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/actions/admin_member_action.php" method="POST">
                <input type="hidden" name="action" id="rewardAction" value="add_reward">
                <input type="hidden" name="id" id="rewardId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="rewardModalTitle">Tambah Reward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Reward</label>
                        <input type="text" class="form-control" name="nama_reward" id="rewardNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poin Dibutuhkan</label>
                        <input type="number" class="form-control" name="poin_dibutuhkan" id="rewardPoin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" id="rewardDesc" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Bootstrap Icon Class)</label>
                        <input type="text" class="form-control" name="icon" id="rewardIcon" value="bi-gift" placeholder="bi-gift">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="rewardStatus">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Benefit Modal -->
<div class="modal fade" id="benefitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/actions/admin_member_action.php" method="POST">
                <input type="hidden" name="action" id="benefitAction" value="add_benefit">
                <input type="hidden" name="id" id="benefitId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="benefitModalTitle">Tambah Keuntungan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Keuntungan</label>
                        <input type="text" class="form-control" name="nama_benefit" id="benefitNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" id="benefitDesc" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Bootstrap Icon Class)</label>
                        <input type="text" class="form-control" name="icon" id="benefitIcon" value="bi-star" placeholder="bi-star">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warna CSS (red, #f00, dsb)</label>
                        <input type="text" class="form-control" name="warna" id="benefitWarna" value="blue">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="benefitStatus">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetRewardModal() {
    document.getElementById('rewardAction').value = 'add_reward';
    document.getElementById('rewardId').value = '';
    document.getElementById('rewardModalTitle').innerText = 'Tambah Reward';
    document.getElementById('rewardNama').value = '';
    document.getElementById('rewardPoin').value = '';
    document.getElementById('rewardDesc').value = '';
    document.getElementById('rewardIcon').value = 'bi-gift';
    document.getElementById('rewardStatus').value = 'aktif';
}
function editReward(r) {
    document.getElementById('rewardAction').value = 'edit_reward';
    document.getElementById('rewardId').value = r.id;
    document.getElementById('rewardModalTitle').innerText = 'Edit Reward';
    document.getElementById('rewardNama').value = r.nama_reward;
    document.getElementById('rewardPoin').value = r.poin_dibutuhkan;
    document.getElementById('rewardDesc').value = r.deskripsi;
    document.getElementById('rewardIcon').value = r.icon;
    document.getElementById('rewardStatus').value = r.status;
    new bootstrap.Modal(document.getElementById('rewardModal')).show();
}
function deleteReward(id) {
    if(confirm('Yakin ingin menghapus reward ini?')) {
        window.location.href = '<?= BASE_URL ?>/actions/admin_member_action.php?action=delete_reward&id=' + id;
    }
}
function resetBenefitModal() {
    document.getElementById('benefitAction').value = 'add_benefit';
    document.getElementById('benefitId').value = '';
    document.getElementById('benefitModalTitle').innerText = 'Tambah Keuntungan';
    document.getElementById('benefitNama').value = '';
    document.getElementById('benefitDesc').value = '';
    document.getElementById('benefitIcon').value = 'bi-star';
    document.getElementById('benefitWarna').value = 'blue';
    document.getElementById('benefitStatus').value = 'aktif';
}
function editBenefit(b) {
    document.getElementById('benefitAction').value = 'edit_benefit';
    document.getElementById('benefitId').value = b.id;
    document.getElementById('benefitModalTitle').innerText = 'Edit Keuntungan';
    document.getElementById('benefitNama').value = b.nama_benefit;
    document.getElementById('benefitDesc').value = b.deskripsi;
    document.getElementById('benefitIcon').value = b.icon;
    document.getElementById('benefitWarna').value = b.warna;
    document.getElementById('benefitStatus').value = b.status;
    new bootstrap.Modal(document.getElementById('benefitModal')).show();
}
function deleteBenefit(id) {
    if(confirm('Yakin ingin menghapus keuntungan ini?')) {
        window.location.href = '<?= BASE_URL ?>/actions/admin_member_action.php?action=delete_benefit&id=' + id;
    }
}
</script>

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
