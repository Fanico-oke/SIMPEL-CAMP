<?php
// pages/admin/profil.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

requireRole(['admin', 'superadmin']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $result = User::update($_SESSION['user_id'], [
            'nama' => $_POST['nama'] ?? '',
            'email' => $_POST['email'] ?? '',
            'no_telp' => $_POST['no_telp'] ?? '',
        ]);
        if ($result['success']) {
            $_SESSION['nama'] = $_POST['nama'];
            $_SESSION['email'] = $_POST['email'];
            $_SESSION['flash_success'] = 'Profil berhasil diperbarui!';
        } else {
            $_SESSION['flash_error'] = $result['message'] ?? 'Gagal memperbarui profil.';
        }
        header('Location: ' . BASE_URL . '/pages/admin/profil.php');
        exit;
    }
}

$adminUser = User::getById($_SESSION['user_id']);
$adminNama = $adminUser['nama'] ?? $_SESSION['nama'] ?? 'Admin';
$adminEmail = $adminUser['email'] ?? $_SESSION['email'] ?? '';
$adminTelp = $adminUser['no_telp'] ?? '';
$adminRole = $_SESSION['role'] ?? 'admin';
$adminCreated = !empty($adminUser['created_at']) ? date('F Y', strtotime($adminUser['created_at'])) : 'N/A';

$page_title = 'Profil Admin';
$current_page = 'profil';
$flash_success = getFlash('success');
$flash_error = getFlash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin <?= APP_NAME ?></title>
    <meta name="description" content="Kelola profil akun administrator SIMPEL-CAMP">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781677663">
    <style>
        /* ═══════════════════════════════════════════
           ADMIN PROFILE PAGE STYLES
           ═══════════════════════════════════════════ */
        .admin-profile-header {
            background: linear-gradient(135deg, #1B4332 0%, #2D6A4F 50%, #52B788 100%);
            border-radius: 16px;
            padding: 2.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .admin-profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -5%;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(255,255,255,0.07) 0%, transparent 70%);
            border-radius: 50%;
        }
        .admin-profile-header::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: 15%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(212,163,115,0.12) 0%, transparent 70%);
            border-radius: 50%;
        }
        .admin-avatar-lg {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: 4px solid rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #fff;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }
        .admin-profile-info h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .admin-profile-info p {
            opacity: 0.85;
            margin-bottom: 0.2rem;
            font-size: 0.9rem;
        }
        .admin-role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.2);
            color: #fff;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
            backdrop-filter: blur(5px);
        }

        /* Form Styles */
        .form-label-admin {
            font-weight: 600;
            font-size: 0.85rem;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-control-admin {
            border: 1.5px solid var(--border, #e5e7eb);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: var(--bg-card, #fff);
        }
        .form-control-admin:focus {
            border-color: #52B788;
            box-shadow: 0 0 0 3px rgba(82,183,136,0.15);
            outline: none;
        }
        .section-title-admin {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: #1B4332;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
        }
        .btn-save-admin {
            background: linear-gradient(135deg, #2D6A4F, #52B788);
            border: none;
            color: #fff;
            padding: 12px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-save-admin:hover {
            background: linear-gradient(135deg, #1B4332, #2D6A4F);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45,106,79,0.3);
        }

        /* Info Card Items */
        .admin-info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border, #f3f4f6);
        }
        .admin-info-item:last-child {
            border-bottom: none;
        }
        .admin-info-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .admin-info-icon.ic-green { background: rgba(82,183,136,0.12); color: #2D6A4F; }
        .admin-info-icon.ic-blue { background: rgba(59,130,246,0.12); color: #3b82f6; }
        .admin-info-icon.ic-gold { background: rgba(212,163,115,0.12); color: #D4A373; }
        .admin-info-icon.ic-purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
        .admin-info-label {
            font-size: 0.8rem;
            color: #6b7280;
        }
        .admin-info-value {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1f2937;
        }

        /* Security Section */
        .security-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border, #f3f4f6);
        }
        .security-item:last-child { border-bottom: none; }
        .form-switch .form-check-input:checked {
            background-color: #2D6A4F;
            border-color: #2D6A4F;
        }

        /* Toast */
        .toast-admin {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: linear-gradient(135deg, #2D6A4F, #52B788);
            color: #fff;
            padding: 14px 24px;
            border-radius: 12px;
            display: none;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            box-shadow: 0 8px 30px rgba(45,106,79,0.3);
            animation: slideInRight 0.4s ease;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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
            <h2 class="fw-bold mb-4">Profil Admin</h2>

            <!-- ════════════════════════════════════════
                 PROFILE HEADER
                 ════════════════════════════════════════ -->
            <div class="admin-profile-header">
                <div class="d-flex align-items-center gap-4 position-relative" style="z-index:1;">
                    <div class="admin-avatar-lg"><?= strtoupper(substr($adminNama, 0, 1)) ?></div>
                    <div class="admin-profile-info">
                        <h2><?= htmlspecialchars($adminNama) ?></h2>
                        <p><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($adminEmail) ?></p>
                        <p><i class="bi bi-calendar3 me-1"></i> Bergabung sejak <?= htmlspecialchars($adminCreated) ?></p>
                        <div class="admin-role-badge">
                            <i class="bi bi-shield-fill-check"></i> <?= ucfirst(htmlspecialchars($adminRole)) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 TWO COLUMN LAYOUT
                 ════════════════════════════════════════ -->
            <div class="row g-4">
                <!-- Left: Edit Profile -->
                <div class="col-lg-8">
                    <div class="sc-card p-4">
                        <h5 class="fw-bold mb-4"><i class="bi bi-pencil-square me-2 text-success"></i>Edit Profil</h5>
                        <form id="adminProfileForm" onsubmit="return saveAdminProfile(event)">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-admin" for="adminNama">Nama Lengkap</label>
                                    <input type="text" class="form-control form-control-admin" id="adminNama" name="nama" value="<?= htmlspecialchars($adminNama) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-admin" for="adminEmail">Email</label>
                                    <input type="email" class="form-control form-control-admin" id="adminEmail" name="email" value="<?= htmlspecialchars($adminEmail) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-admin" for="adminWhatsapp">No. WhatsApp</label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="border-radius:10px 0 0 10px; border:1.5px solid var(--border,#e5e7eb); border-right:0; background:var(--bg-card,#f9fafb);">
                                            <i class="bi bi-whatsapp text-success"></i>
                                        </span>
                                        <input type="tel" class="form-control form-control-admin" id="adminWhatsapp" name="no_telp" value="<?= htmlspecialchars($adminTelp) ?>" style="border-radius:0 10px 10px 0;">
                                    </div>
                                </div>
                            </div>

                            <hr style="border-top: 1px dashed var(--border, #e5e7eb); margin: 1.5rem 0;">

                            <div class="section-title-admin">
                                <i class="bi bi-shield-lock"></i>
                                Ubah Password
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label-admin" for="adminOldPass">Password Lama</label>
                                    <input type="password" class="form-control form-control-admin" id="adminOldPass" placeholder="••••••••">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-admin" for="adminNewPass">Password Baru</label>
                                    <input type="password" class="form-control form-control-admin" id="adminNewPass" placeholder="••••••••">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-admin" for="adminConfirmPass">Konfirmasi Password</label>
                                    <input type="password" class="form-control form-control-admin" id="adminConfirmPass" placeholder="••••••••">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn-save-admin">
                                    <i class="bi bi-check-circle"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Info & Security -->
                <div class="col-lg-4">
                    <div class="sc-card p-4 mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-success"></i>Info Akun</h5>
                        <div class="admin-info-item">
                            <div class="admin-info-icon ic-green">
                                <i class="bi bi-shield-fill-check"></i>
                            </div>
                            <div>
                                <div class="admin-info-label">Role</div>
                                <div class="admin-info-value">Super Admin</div>
                            </div>
                        </div>
                        <div class="admin-info-item">
                            <div class="admin-info-icon ic-blue">
                                <i class="bi bi-circle-fill" style="font-size:0.7rem;"></i>
                            </div>
                            <div>
                                <div class="admin-info-label">Status</div>
                                <div class="admin-info-value">
                                    <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                                </div>
                            </div>
                        </div>
                        <div class="admin-info-item">
                            <div class="admin-info-icon ic-gold">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <div class="admin-info-label">Login Terakhir</div>
                                <div class="admin-info-value" style="font-size:0.85rem;">15 Jun 2026, 09:30</div>
                            </div>
                        </div>
                        <div class="admin-info-item">
                            <div class="admin-info-icon ic-purple">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </div>
                            <div>
                                <div class="admin-info-label">Total Login</div>
                                <div class="admin-info-value">245 <small class="text-secondary fw-normal">kali</small></div>
                            </div>
                        </div>
                    </div>

                    <div class="sc-card p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2 text-success"></i>Keamanan</h5>
                        <div class="security-item">
                            <div>
                                <div class="fw-medium" style="font-size:0.9rem;">Two-Factor Auth</div>
                                <small class="text-secondary">Verifikasi dua langkah</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="twoFactorToggle" checked>
                            </div>
                        </div>
                        <div class="security-item">
                            <div>
                                <div class="fw-medium" style="font-size:0.9rem;">Session Timeout</div>
                                <small class="text-secondary">Otomatis logout setelah idle</small>
                            </div>
                            <span class="mono-font fw-bold text-secondary" style="font-size:0.85rem;">30 menit</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-admin" id="toastAdmin">
    <i class="bi bi-check-circle-fill"></i>
    Profil berhasil diperbarui!
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function saveAdminProfile(e) {
    e.preventDefault();
    const toast = document.getElementById('toastAdmin');
    toast.style.display = 'flex';
    setTimeout(function() { toast.style.display = 'none'; }, 3000);
    return false;
}
</script>
</body>
</html>
SESSION['nama'] : 'Admin' ?></span>
            </div>
        </div>

        <!-- Content -->
        <div class="admin-content">
            <h2 class="fw-bold mb-4">Profil Admin</h2>

            <!-- ════════════════════════════════════════
                 PROFILE HEADER
                 ════════════════════════════════════════ -->
            <div class="admin-profile-header">
                <div class="d-flex align-items-center gap-4 position-relative" style="z-index:1;">
                    <div class="admin-avatar-lg">A</div>
                    <div class="admin-profile-info">
                        <h2>Administrator</h2>
                        <p><i class="bi bi-envelope me-1"></i> admin@simpelcamp.com</p>
                        <p><i class="bi bi-calendar3 me-1"></i> Bergabung sejak Maret 2024</p>
                        <div class="admin-role-badge">
                            <i class="bi bi-shield-fill-check"></i> Super Admin
                        </div>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 TWO COLUMN LAYOUT
                 ════════════════════════════════════════ -->
            <div class="row g-4">
                <!-- Left: Edit Profile -->
                <div class="col-lg-8">
                    <div class="sc-card p-4">
                        <h5 class="fw-bold mb-4"><i class="bi bi-pencil-square me-2 text-success"></i>Edit Profil</h5>
                        <form id="adminProfileForm" onsubmit="return saveAdminProfile(event)">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-admin" for="adminNama">Nama Lengkap</label>
                                    <input type="text" class="form-control form-control-admin" id="adminNama" value="Administrator">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-admin" for="adminEmail">Email</label>
                                    <input type="email" class="form-control form-control-admin" id="adminEmail" value="admin@simpelcamp.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-admin" for="adminWhatsapp">No. WhatsApp</label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="border-radius:10px 0 0 10px; border:1.5px solid var(--border,#e5e7eb); border-right:0; background:var(--bg-card,#f9fafb);">
                                            <i class="bi bi-whatsapp text-success"></i>
                                        </span>
                                        <input type="tel" class="form-control form-control-admin" id="adminWhatsapp" value="081234567890" style="border-radius:0 10px 10px 0;">
                                    </div>
                                </div>
                            </div>

                            <hr style="border-top: 1px dashed var(--border, #e5e7eb); margin: 1.5rem 0;">

                            <div class="section-title-admin">
                                <i class="bi bi-shield-lock"></i>
                                Ubah Password
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label-admin" for="adminOldPass">Password Lama</label>
                                    <input type="password" class="form-control form-control-admin" id="adminOldPass" placeholder="••••••••">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-admin" for="adminNewPass">Password Baru</label>
                                    <input type="password" class="form-control form-control-admin" id="adminNewPass" placeholder="••••••••">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-admin" for="adminConfirmPass">Konfirmasi Password</label>
                                    <input type="password" class="form-control form-control-admin" id="adminConfirmPass" placeholder="••••••••">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn-save-admin">
                                    <i class="bi bi-check-circle"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Info & Security -->
                <div class="col-lg-4">
                    <div class="sc-card p-4 mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-success"></i>Info Akun</h5>
                        <div class="admin-info-item">
                            <div class="admin-info-icon ic-green">
                                <i class="bi bi-shield-fill-check"></i>
                            </div>
                            <div>
                                <div class="admin-info-label">Role</div>
                                <div class="admin-info-value">Super Admin</div>
                            </div>
                        </div>
                        <div class="admin-info-item">
                            <div class="admin-info-icon ic-blue">
                                <i class="bi bi-circle-fill" style="font-size:0.7rem;"></i>
                            </div>
                            <div>
                                <div class="admin-info-label">Status</div>
                                <div class="admin-info-value">
                                    <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                                </div>
                            </div>
                        </div>
                        <div class="admin-info-item">
                            <div class="admin-info-icon ic-gold">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <div class="admin-info-label">Login Terakhir</div>
                                <div class="admin-info-value" style="font-size:0.85rem;">15 Jun 2026, 09:30</div>
                            </div>
                        </div>
                        <div class="admin-info-item">
                            <div class="admin-info-icon ic-purple">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </div>
                            <div>
                                <div class="admin-info-label">Total Login</div>
                                <div class="admin-info-value">245 <small class="text-secondary fw-normal">kali</small></div>
                            </div>
                        </div>
                    </div>

                    <div class="sc-card p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2 text-success"></i>Keamanan</h5>
                        <div class="security-item">
                            <div>
                                <div class="fw-medium" style="font-size:0.9rem;">Two-Factor Auth</div>
                                <small class="text-secondary">Verifikasi dua langkah</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="twoFactorToggle" checked>
                            </div>
                        </div>
                        <div class="security-item">
                            <div>
                                <div class="fw-medium" style="font-size:0.9rem;">Session Timeout</div>
                                <small class="text-secondary">Otomatis logout setelah idle</small>
                            </div>
                            <span class="mono-font fw-bold text-secondary" style="font-size:0.85rem;">30 menit</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-admin" id="toastAdmin">
    <i class="bi bi-check-circle-fill"></i>
    Profil berhasil diperbarui!
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function saveAdminProfile(e) {
    e.preventDefault();
    const toast = document.getElementById('toastAdmin');
    toast.style.display = 'flex';
    setTimeout(function() { toast.style.display = 'none'; }, 3000);
    return false;
}
</script>
</body>
</html>
