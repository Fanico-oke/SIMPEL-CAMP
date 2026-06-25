<?php
// pages/admin/notifikasi.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Notifikasi.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

requireRole(['admin', 'superadmin']);

$page_title = 'Pusat Notifikasi';
$current_page = 'notifikasi';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_all_read') {
        Notifikasi::markAllRead($_SESSION['user_id']);
        $_SESSION['flash_success'] = 'Semua notifikasi ditandai sudah dibaca.';
        header('Location: ' . BASE_URL . '/pages/admin/notifikasi.php');
        exit;
    }
    
    if ($action === 'send') {
        $judul = $_POST['judul'] ?? '';
        $pesan = $_POST['pesan'] ?? '';
        $tipe = $_POST['tipe'] ?? 'info';
        $recipientType = $_POST['recipient_type'] ?? 'all';
        
        if (!empty($judul) && !empty($pesan)) {
            if ($recipientType === 'all') {
                // Send to all pelanggan
                $allUsers = User::getAll('pelanggan', null, 1000, 0);
                foreach ($allUsers as $user) {
                    Notifikasi::create($user['id'], $judul, $pesan, $tipe);
                }
                $_SESSION['flash_success'] = 'Notifikasi berhasil dikirim ke ' . count($allUsers) . ' pelanggan!';
            } else {
                // Send to specific user IDs
                $userIds = $_POST['user_ids'] ?? [];
                foreach ($userIds as $uid) {
                    Notifikasi::create((int)$uid, $judul, $pesan, $tipe);
                }
                $_SESSION['flash_success'] = 'Notifikasi berhasil dikirim ke ' . count($userIds) . ' penerima!';
            }
        } else {
            $_SESSION['flash_error'] = 'Judul dan pesan wajib diisi.';
        }
        header('Location: ' . BASE_URL . '/pages/admin/notifikasi.php');
        exit;
    }
}

// Load notifications for current admin
$notifList = Notifikasi::getByUser($_SESSION['user_id'], 50, 0);
$unreadCount = Notifikasi::countUnread($_SESSION['user_id']);

// Load pelanggan list for send form
$pelangganList = User::getAll('pelanggan', null, 100, 0);

// Map notification type to visual styles
function getNotifStyle($tipe) {
    $styles = [
        'info'    => ['icon' => 'bi-info-circle-fill', 'color' => '#3B82F6', 'bg' => 'rgba(59,130,246,0.1)', 'badge' => 'Info', 'badge_class' => 'bg-info'],
        'success' => ['icon' => 'bi-check-circle-fill', 'color' => '#22C55E', 'bg' => 'rgba(34,197,94,0.1)', 'badge' => 'Sukses', 'badge_class' => 'bg-success'],
        'warning' => ['icon' => 'bi-exclamation-triangle-fill', 'color' => '#F97316', 'bg' => 'rgba(249,115,22,0.1)', 'badge' => 'Warning', 'badge_class' => 'bg-warning text-dark'],
        'danger'  => ['icon' => 'bi-x-circle-fill', 'color' => '#EF4444', 'bg' => 'rgba(239,68,68,0.1)', 'badge' => 'Urgent', 'badge_class' => 'bg-danger'],
    ];
    return $styles[$tipe] ?? $styles['info'];
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}

$flash_success = getFlash('success');
$flash_error = getFlash('error');
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
        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.15rem 1.25rem;
            border-radius: 14px;
            border: 1.5px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        .notif-item:hover {
            background: #f9fafb;
            border-color: #e5e7eb;
            transform: translateX(4px);
        }
        .notif-item.unread {
            background: linear-gradient(135deg, #f0fdf4, #f5f3ff06);
            border-color: rgba(82, 183, 136, 0.2);
        }
        .notif-item.unread::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: linear-gradient(180deg, #52B788, #2D6A4F);
            border-radius: 0 4px 4px 0;
        }
        .notif-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .notif-title {
            font-weight: 600;
            font-size: 0.92rem;
            color: #1f2937;
            margin-bottom: 0.15rem;
        }
        .notif-desc {
            font-size: 0.82rem;
            color: #6b7280;
            line-height: 1.4;
        }
        .notif-time {
            font-size: 0.75rem;
            color: #9ca3af;
            white-space: nowrap;
        }
        .notif-badge {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f3f4f6;
        }
        .section-header h5 {
            font-weight: 700;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .unread-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: #fff;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0 6px;
        }
        .send-form .form-label-custom {
            font-weight: 600;
            font-size: 0.85rem;
            color: #374151;
            margin-bottom: 0.4rem;
        }
        .send-form .form-control-send,
        .send-form .form-select-send {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
            background-color: #fafafa;
            transition: all 0.2s ease;
        }
        .send-form .form-control-send:focus,
        .send-form .form-select-send:focus {
            border-color: #52B788;
            box-shadow: 0 0 0 3px rgba(82,183,136,0.15);
            background: #fff;
        }
        .btn-send-notif {
            background: linear-gradient(135deg, #1B4332, #2D6A4F);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(27, 67, 50, 0.25);
        }
        .btn-send-notif:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(27, 67, 50, 0.35);
            background: linear-gradient(135deg, #2D6A4F, #52B788);
            color: #fff;
        }
        .type-selector {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .type-selector .type-option {
            padding: 0.45rem 1rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            background: #fafafa;
        }
        .type-selector .type-option:hover {
            border-color: #52B788;
        }
        .type-selector .type-option.active {
            border-color: #2D6A4F;
            background: rgba(45, 106, 79, 0.08);
            color: #1B4332;
            font-weight: 600;
        }
        .notif-divider {
            height: 1px;
            background: #f3f4f6;
            margin: 0 1.25rem;
        }
        .btn-mark-read {
            font-size: 0.82rem;
            color: #6b7280;
            font-weight: 500;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.3rem 0.8rem;
            transition: all 0.2s ease;
            background: transparent;
        }
        .btn-mark-read:hover {
            border-color: #52B788;
            color: #2D6A4F;
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
            <?php if ($flash_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash_success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if ($flash_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Pusat Notifikasi</h2>
                    <p class="text-secondary mb-0">Kelola notifikasi masuk dan kirim pengumuman</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- ═══════════════════════════════════════
                     NOTIFIKASI MASUK (Inbox)
                     ═══════════════════════════════════════ -->
                <div class="col-lg-7">
                    <div class="sc-card p-4">
                        <div class="section-header">
                            <h5>
                                <i class="bi bi-inbox text-primary"></i>
                                Notifikasi Masuk
                                <?php if ($unreadCount > 0): ?>
                                <span class="unread-count"><?= $unreadCount ?></span>
                                <?php endif; ?>
                            </h5>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="mark_all_read">
                                <button type="submit" class="btn-mark-read">
                                    <i class="bi bi-check2-all me-1"></i>Tandai Semua Dibaca
                                </button>
                            </form>
                        </div>

                        <div class="d-flex flex-column gap-1" id="notifList">
                            <?php if (empty($notifList)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-bell-slash display-5"></i>
                                <p class="mt-2">Belum ada notifikasi.</p>
                            </div>
                            <?php else: ?>
                            <?php foreach($notifList as $i => $n): 
                                $style = getNotifStyle($n['tipe'] ?? 'info');
                                $isUnread = empty($n['is_read']);
                                $timeLabel = timeAgo($n['created_at']);
                            ?>
                                <?php if($i > 0): ?><div class="notif-divider"></div><?php endif; ?>
                                <div class="notif-item <?= $isUnread ? 'unread' : '' ?>">
                                    <div class="notif-icon" style="background:<?= $style['bg'] ?>;color:<?= $style['color'] ?>;">
                                        <i class="bi <?= $style['icon'] ?>"></i>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                            <span class="notif-title"><?= htmlspecialchars($n['judul']) ?></span>
                                            <span class="badge notif-badge <?= $style['badge_class'] ?>"><?= $style['badge'] ?></span>
                                        </div>
                                        <p class="notif-desc mb-1"><?= htmlspecialchars($n['pesan']) ?></p>
                                        <span class="notif-time"><i class="bi bi-clock me-1"></i><?= $timeLabel ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════
                     KIRIM NOTIFIKASI
                     ═══════════════════════════════════════ -->
                <div class="col-lg-5">
                    <div class="sc-card p-4 send-form">
                        <div class="section-header">
                            <h5>
                                <i class="bi bi-send text-success"></i>
                                Kirim Notifikasi
                            </h5>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="send">
                            <input type="hidden" name="tipe" id="notifTipeInput" value="info">

                            <!-- Type -->
                            <div class="mb-3">
                                <label class="form-label form-label-custom">Tipe Notifikasi</label>
                                <div class="type-selector">
                                    <div class="type-option active" onclick="selectType(this, 'info')">
                                        <i class="bi bi-megaphone me-1"></i>Pengumuman
                                    </div>
                                    <div class="type-option" onclick="selectType(this, 'success')">
                                        <i class="bi bi-tag me-1"></i>Promo
                                    </div>
                                    <div class="type-option" onclick="selectType(this, 'warning')">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Warning
                                    </div>
                                </div>
                            </div>

                            <!-- Recipient -->
                            <div class="mb-3">
                                <label class="form-label form-label-custom">Penerima</label>
                                <select class="form-select form-select-send" name="recipient_type" id="recipientType" onchange="toggleRecipientInput()">
                                    <option value="all">Semua Pelanggan</option>
                                    <option value="manual">Pilih Manual</option>
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="manualRecipient">
                                <label class="form-label form-label-custom">Pilih Penerima</label>
                                <select class="form-select form-select-send" name="user_ids[]" multiple style="height:120px;">
                                    <?php foreach ($pelangganList as $plg): ?>
                                    <option value="<?= (int)$plg['id'] ?>"><?= htmlspecialchars($plg['nama']) ?> (<?= htmlspecialchars($plg['email']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Tahan Ctrl untuk memilih beberapa penerima</small>
                            </div>

                            <!-- Subject -->
                            <div class="mb-3">
                                <label class="form-label form-label-custom">Judul</label>
                                <input type="text" class="form-control form-control-send" name="judul" placeholder="Judul notifikasi..." required>
                            </div>

                            <!-- Message -->
                            <div class="mb-4">
                                <label class="form-label form-label-custom">Isi Pesan</label>
                                <textarea class="form-control form-control-send" name="pesan" rows="5" placeholder="Tulis isi pesan notifikasi..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-send-notif w-100">
                                <i class="bi bi-send-fill me-2"></i>Kirim Notifikasi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Success Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080;">
    <div id="sendToast" class="toast align-items-center text-white border-0" role="alert" style="background:linear-gradient(135deg,#1B4332,#2D6A4F);border-radius:14px;">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <span>Notifikasi berhasil dikirim!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function selectType(el, tipe) {
        document.querySelectorAll('.type-option').forEach(o => o.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('notifTipeInput').value = tipe;
    }

    function toggleRecipientInput() {
        const manual = document.getElementById('manualRecipient');
        const type = document.getElementById('recipientType').value;
        manual.classList.toggle('d-none', type !== 'manual');
    }
</script>
</body>
</html>
