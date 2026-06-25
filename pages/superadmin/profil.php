<?php
// pages/superadmin/profil.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';
require_once dirname(__DIR__, 2) . '/classes/LogAktivitas.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'superadmin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Profil Super Admin';
$current_page = 'profil';

// Load current user data from DB
$user = User::getById($_SESSION['user_id']);
if (!$user) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Handle profile update
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama   = trim($_POST['nama'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $noTelp = trim($_POST['no_telp'] ?? '');

        if (empty($nama) || empty($email)) {
            $errorMsg = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Format email tidak valid.';
        } elseif (User::emailExists($email, $_SESSION['user_id'])) {
            $errorMsg = 'Email sudah digunakan oleh akun lain.';
        } else {
            $result = User::update($_SESSION['user_id'], [
                'nama'   => $nama,
                'email'  => $email,
                'no_telp' => $noTelp,
            ]);
            if ($result) {
                // Update session
                $_SESSION['nama'] = $nama;
                $_SESSION['email'] = $email;
                $successMsg = 'Profil berhasil diperbarui.';
                // Refresh user data
                $user = User::getById($_SESSION['user_id']);
                LogAktivitas::log($_SESSION['user_id'], 'update_profile', 'Memperbarui informasi profil');
            } else {
                $errorMsg = 'Gagal memperbarui profil. Silakan coba lagi.';
            }
        }
    } elseif ($action === 'update_password') {
        $oldPass    = $_POST['old_password'] ?? '';
        $newPass    = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($oldPass) || empty($newPass) || empty($confirmPass)) {
            $errorMsg = 'Semua field password harus diisi.';
        } elseif (strlen($newPass) < 8) {
            $errorMsg = 'Password baru minimal 8 karakter.';
        } elseif ($newPass !== $confirmPass) {
            $errorMsg = 'Konfirmasi password tidak cocok.';
        } else {
            $result = User::updatePassword($_SESSION['user_id'], $oldPass, $newPass);
            if ($result) {
                $successMsg = 'Password berhasil diperbarui.';
                LogAktivitas::log($_SESSION['user_id'], 'update_password', 'Mengubah password akun');
            } else {
                $errorMsg = 'Password lama salah atau gagal memperbarui.';
            }
        }
    }
}

// Get recent login activity from log_aktivitas
$recentLogins = LogAktivitas::getAll([
    'user_id' => $_SESSION['user_id'],
    'aksi'    => 'login',
    'limit'   => 4,
]);

// Count total logins
$totalLogins = LogAktivitas::count([
    'user_id' => $_SESSION['user_id'],
    'aksi'    => 'login',
]);

$userInitial = strtoupper(substr($user['nama'] ?? 'S', 0, 1));
$avatarText = strtoupper(substr($user['nama'] ?? 'SA', 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Profil Super Administrator - SIMPEL-CAMP Management System">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=<?= time() ?>">
    <style>
        /* ============================================================
           PROFIL SUPER ADMIN — Premium Profile Styles
           ============================================================ */

        /* --- Profile Header --- */
        .sa-profile-header {
            background: linear-gradient(135deg, #081C15 0%, #1B4332 40%, #2D6A4F 80%, #40916C 100%);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(212, 163, 115, 0.15);
            margin-bottom: 1.5rem;
        }

        .sa-profile-header::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -60px;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(212, 163, 115, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .sa-profile-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #D4A373, #E9C46A, #D4A373);
        }

        .sa-profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #D4A373, #E9C46A);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #081C15;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(212, 163, 115, 0.35);
            border: 3px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }

        .sa-profile-info {
            position: relative;
            z-index: 1;
        }

        .sa-profile-info h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 0.15rem;
        }

        .sa-profile-info .profile-role {
            font-size: 0.92rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.5rem;
        }

        .sa-profile-info .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #D4A373, #E9C46A);
            color: #081C15;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 20px;
            letter-spacing: 0.05em;
        }

        .sa-profile-info .profile-email {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.35rem;
        }

        /* --- Section Card --- */
        .sa-prof-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .sa-prof-card .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: transparent;
        }

        .sa-prof-card .card-header h5 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            margin: 0;
        }

        .sa-prof-card .card-header i {
            color: #D4A373;
            font-size: 1.1rem;
        }

        .sa-prof-card .card-body {
            padding: 1.5rem;
        }

        /* --- Form Styles --- */
        .sa-form-group {
            margin-bottom: 1.25rem;
        }

        .sa-form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
        }

        .sa-form-group input {
            width: 100%;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            padding: 0.65rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg-body);
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        .sa-form-group input:focus {
            outline: none;
            border-color: #D4A373;
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.1);
        }

        .sa-form-group .input-hint {
            font-size: 0.72rem;
            color: var(--text-secondary);
            margin-top: 0.3rem;
        }

        .btn-sa-save {
            background: linear-gradient(135deg, #1B4332, #2D6A4F);
            color: #fff;
            border: none;
            padding: 0.65rem 2rem;
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-sa-save:hover {
            background: linear-gradient(135deg, #2D6A4F, #40916C);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(27, 67, 50, 0.2);
        }

        .btn-sa-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            padding: 0.65rem 1.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-sa-secondary:hover {
            background: var(--bg-body);
            color: var(--text-primary);
        }

        /* --- Password Section --- */
        .sa-password-section {
            border-top: 1px solid var(--border);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }

        .sa-password-section h6 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.92rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-primary);
        }

        .sa-password-section h6 i {
            color: #D4A373;
        }

        /* --- Account Info Sidebar --- */
        .sa-account-info-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 0;
            border-bottom: 1px solid var(--border);
        }

        .sa-account-info-item:last-child {
            border-bottom: none;
        }

        .sa-account-info-item .info-label {
            font-size: 0.78rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .sa-account-info-item .info-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .sa-account-info-item .info-value.role-badge {
            background: linear-gradient(135deg, rgba(212, 163, 115, 0.15), rgba(233, 196, 106, 0.15));
            color: #D4A373;
            padding: 3px 12px;
            border-radius: 6px;
            border: 1px solid rgba(212, 163, 115, 0.3);
            font-size: 0.72rem;
        }

        /* --- 2FA Toggle --- */
        .sa-toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
        }

        .sa-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .sa-toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: var(--border);
            border-radius: 26px;
            transition: all 0.3s ease;
        }

        .sa-toggle-slider::before {
            content: '';
            position: absolute;
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .sa-toggle-switch input:checked + .sa-toggle-slider {
            background: linear-gradient(135deg, #52B788, #2D6A4F);
        }

        .sa-toggle-switch input:checked + .sa-toggle-slider::before {
            transform: translateX(22px);
        }

        /* --- Security Log Mini --- */
        .sa-security-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.6rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            font-size: 0.8rem;
        }

        .sa-security-item:last-child {
            border-bottom: none;
        }

        .sa-security-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .sa-security-dot.success { background: #10B981; }
        .sa-security-dot.warning { background: #F59E0B; }

        .sa-security-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-left: auto;
            white-space: nowrap;
        }

        /* --- Alert styles --- */
        .sa-alert {
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sa-alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .sa-alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* --- Responsive --- */
        @media (max-width: 767.98px) {
            .sa-profile-header {
                padding: 1.5rem;
            }
            .sa-profile-avatar {
                width: 72px;
                height: 72px;
                font-size: 1.6rem;
            }
            .sa-profile-info h1 {
                font-size: 1.3rem;
            }
        }
    
/* Stagger Animation */
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.stagger-item{opacity:0;animation:fadeInUp .5s ease forwards}
    </style>
</head>
<body>
<div class="superadmin-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_superadmin.php'; ?>
    <div class="superadmin-main">
        <?php $_header_role = 'superadmin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


        <!-- Content -->
        <div class="admin-content" style="padding:1.5rem;">

            <?php if ($successMsg): ?>
            <div class="sa-alert sa-alert-success stagger-item"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
            <div class="sa-alert sa-alert-error stagger-item"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="sa-profile-header stagger-item">
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div class="sa-profile-avatar"><?= htmlspecialchars($avatarText) ?></div>
                    <div class="sa-profile-info">
                        <h1><?= htmlspecialchars($user['nama']) ?></h1>
                        <div class="profile-role">Super Administrator — Full System Access</div>
                        <div class="profile-badge">⚡ SUPER ADMIN</div>
                        <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="row g-3">
                <!-- Left: Edit Form -->
                <div class="col-lg-8">
                    <!-- Personal Info -->
                    <div class="sa-prof-card mb-3">
                        <div class="card-header">
                            <i class="bi bi-person"></i>
                            <h5>Informasi Pribadi</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="sa-form-group">
                                            <label>Nama Lengkap</label>
                                            <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" placeholder="Nama lengkap" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="sa-form-group">
                                            <label>Email</label>
                                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Email" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="sa-form-group">
                                            <label>No. Telepon</label>
                                            <input type="tel" name="no_telp" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" placeholder="No. telepon">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="sa-form-group">
                                            <label>Username</label>
                                            <input type="text" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:0.6;cursor:not-allowed;">
                                            <div class="input-hint">Username tidak dapat diubah</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-2">
                                    <button type="submit" class="btn-sa-save"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                                    <a href="<?= BASE_URL ?>/pages/superadmin/profil.php" class="btn-sa-secondary">Batal</a>
                                </div>
                            </form>

                            <!-- Change Password -->
                            <div class="sa-password-section">
                                <h6><i class="bi bi-shield-lock"></i> Ubah Password</h6>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_password">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="sa-form-group">
                                                <label>Password Lama</label>
                                                <input type="password" name="old_password" placeholder="••••••••" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="sa-form-group">
                                                <label>Password Baru</label>
                                                <input type="password" name="new_password" placeholder="••••••••" required>
                                                <div class="input-hint">Minimal 8 karakter, huruf besar, angka, simbol</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="sa-form-group">
                                                <label>Konfirmasi Password</label>
                                                <input type="password" name="confirm_password" placeholder="••••••••" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-sa-save" style="background:linear-gradient(135deg,#D4A373,#c49566);"><i class="bi bi-key"></i> Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Account Info Sidebar -->
                <div class="col-lg-4">
                    <!-- Account Info -->
                    <div class="sa-prof-card mb-3">
                        <div class="card-header">
                            <i class="bi bi-info-circle"></i>
                            <h5>Informasi Akun</h5>
                        </div>
                        <div class="card-body">
                            <div class="sa-account-info-item">
                                <span class="info-label">Role</span>
                                <span class="info-value role-badge">⚡ Super Admin</span>
                            </div>
                            <div class="sa-account-info-item">
                                <span class="info-label">Last Login</span>
                                <span class="info-value"><?= $user['last_login'] ? date('d M Y, H:i', strtotime($user['last_login'])) : '—' ?></span>
                            </div>
                            <div class="sa-account-info-item">
                                <span class="info-label">Total Logins</span>
                                <span class="info-value"><?= number_format($totalLogins) ?></span>
                            </div>
                            <div class="sa-account-info-item">
                                <span class="info-label">Akun Dibuat</span>
                                <span class="info-value"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
                            </div>
                            <div class="sa-account-info-item">
                                <span class="info-label">Status</span>
                                <span class="info-value" style="color:<?= $user['status'] === 'aktif' ? '#10B981' : '#EF4444' ?>;">● <?= ucfirst(htmlspecialchars($user['status'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 2FA -->
                    <div class="sa-prof-card mb-3">
                        <div class="card-header">
                            <i class="bi bi-shield-check"></i>
                            <h5>Keamanan</h5>
                        </div>
                        <div class="card-body">
                            <div class="sa-account-info-item">
                                <div>
                                    <div class="info-label" style="font-weight:700;color:var(--text-primary);font-size:0.85rem;">Two-Factor Auth (2FA)</div>
                                    <div class="info-label" style="font-size:0.7rem;margin-top:2px;">Keamanan tambahan saat login</div>
                                </div>
                                <label class="sa-toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="sa-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="sa-account-info-item">
                                <div>
                                    <div class="info-label" style="font-weight:700;color:var(--text-primary);font-size:0.85rem;">Notifikasi Login</div>
                                    <div class="info-label" style="font-size:0.7rem;margin-top:2px;">Email notifikasi saat login baru</div>
                                </div>
                                <label class="sa-toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="sa-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Login Activity -->
                    <div class="sa-prof-card">
                        <div class="card-header">
                            <i class="bi bi-clock-history"></i>
                            <h5>Login Terakhir</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentLogins)): ?>
                            <div class="sa-security-item">
                                <span class="sa-security-dot success"></span>
                                <span class="text-secondary">Belum ada riwayat login.</span>
                            </div>
                            <?php else: ?>
                            <?php foreach ($recentLogins as $login):
                                $loginDate = new DateTime($login['created_at']);
                                $today = new DateTime('today');
                                $yesterday = new DateTime('yesterday');
                                if ($loginDate >= $today) {
                                    $timeLabel = 'Hari ini';
                                } elseif ($loginDate >= $yesterday) {
                                    $timeLabel = 'Kemarin';
                                } else {
                                    $timeLabel = date('d M', strtotime($login['created_at']));
                                }
                                // Parse user agent for browser/OS info
                                $ua = $login['user_agent'] ?? '';
                                $browser = 'Unknown';
                                $os = 'Unknown';
                                if (stripos($ua, 'chrome') !== false) $browser = 'Chrome';
                                elseif (stripos($ua, 'firefox') !== false) $browser = 'Firefox';
                                elseif (stripos($ua, 'safari') !== false) $browser = 'Safari';
                                elseif (stripos($ua, 'edge') !== false) $browser = 'Edge';
                                if (stripos($ua, 'windows') !== false) $os = 'Windows';
                                elseif (stripos($ua, 'mac') !== false) $os = 'macOS';
                                elseif (stripos($ua, 'linux') !== false) $os = 'Linux';
                                elseif (stripos($ua, 'android') !== false) $os = 'Android';
                                elseif (stripos($ua, 'iphone') !== false) $os = 'iOS';
                                $isSuccess = stripos($login['aksi'] ?? '', 'gagal') === false;
                            ?>
                            <div class="sa-security-item">
                                <span class="sa-security-dot <?= $isSuccess ? 'success' : 'warning' ?>"></span>
                                <span><?= $isSuccess ? 'Login' : 'Gagal Login' ?> — <?= htmlspecialchars($browser) ?>, <?= htmlspecialchars($os) ?></span>
                                <span class="sa-security-time"><?= htmlspecialchars($timeLabel) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.stagger-item').forEach(function(item, i){
        item.style.animationDelay = (i * 0.08) + 's';
    });
});
</script>
</body>
</html>
