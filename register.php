<?php
// register.php
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/MemberLevel.php';
require_once 'classes/LogAktivitas.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/pages/" . $_SESSION['role'] . "/dashboard.php");
    exit;
}

$page_title = "Daftar Akun";
$error = '';
$success = '';
$old = []; // Untuk mengisi kembali form jika error

// Proses Registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $no_telp  = trim($_POST['no_telp'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Simpan data lama untuk re-fill form
    $old = compact('nama', 'email', 'no_telp', 'alamat');

    // Validasi
    if (empty($nama) || empty($email) || empty($no_telp) || empty($alamat) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (User::emailExists($email)) {
        $error = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
    } else {
        // Cek apakah registrasi aktif
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT `value` FROM pengaturan WHERE `key` = 'registrasi_aktif'");
            $stmt->execute();
            $reg = $stmt->fetch();
            if ($reg && $reg['value'] === '0') {
                $error = 'Registrasi sedang ditutup sementara. Silakan hubungi admin.';
            }
        } catch (PDOException $e) {
            // Lanjutkan jika tabel belum ada
        }

        if (empty($error)) {
            try {
                // Register user
                $userId = User::register([
                    'nama'     => $nama,
                    'email'    => $email,
                    'password' => $password,
                    'no_telp'  => $no_telp,
                    'alamat'   => $alamat
                ]);

                if ($userId) {
                    // Buat member level
                    try {
                        MemberLevel::create($userId);
                    } catch (Exception $e) {
                        // Non-critical
                    }

                    // Log aktivitas
                    try {
                        LogAktivitas::log($userId, 'REGISTER', 'Pendaftaran akun baru: ' . $email);
                    } catch (Exception $e) {
                        // Non-critical
                    }

                    $_SESSION['flash_success'] = 'Registrasi berhasil! Silakan login dengan akun baru Anda.';
                    header("Location: " . BASE_URL . "/login.php");
                    exit;
                } else {
                    $error = 'Gagal mendaftarkan akun. Silakan coba lagi.';
                }
            } catch (Exception $e) {
                error_log("Register error: " . $e->getMessage());
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <meta name="description" content="Daftar akun SIMPEL-CAMP untuk mulai menyewa peralatan camping dengan mudah.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/auth.css">
</head>
<body class="auth-page">

    <div class="auth-wrapper">
        <div class="auth-card auth-card--register">

            <!-- Brand Logo -->
            <a href="<?= BASE_URL ?>/" class="auth-brand">
                <span class="brand-icon">⛺</span> SIMPEL-<span>CAMP</span>
            </a>

            <!-- Heading -->
            <h1 class="auth-title">Buat Akun Baru 🏕️</h1>
            <p class="auth-subtitle">Lengkapi form di bawah untuk bergabung dan mulai petualangan Anda.</p>

            <!-- Alert Messages -->
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size: 0.85rem; border-radius: 12px;">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.7rem;"></button>
            </div>
            <?php endif; ?>

            <!-- Register Form -->
            <form action="" method="POST" id="registerForm" autocomplete="off">
                <div class="row">

                    <!-- Nama Lengkap -->
                    <div class="col-12 mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control border-start-0" id="nama" name="nama" placeholder="Budi Santoso" value="<?= htmlspecialchars($old['nama'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" placeholder="email@contoh.com" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- No. WhatsApp -->
                    <div class="col-md-6 mb-3">
                        <label for="no_telp" class="form-label">No. WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" class="form-control border-start-0" id="no_telp" name="no_telp" placeholder="08123456789" value="<?= htmlspecialchars($old['no_telp'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="col-12 mb-3">
                        <label for="alamat" class="form-label">Alamat Lengkap</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                            <textarea class="form-control border-start-0" id="alamat" name="alamat" rows="2" placeholder="Jl. Raya Pendaki No. 123, Kota" required><?= htmlspecialchars($old['alamat'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                            <button class="btn-toggle-pw" type="button" id="togglePassword1" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div class="col-md-6 mb-3">
                        <label for="password_confirm" class="form-label">Konfirmasi Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control border-start-0 border-end-0" id="password_confirm" name="password_confirm" placeholder="Ulangi password" required minlength="6">
                            <button class="btn-toggle-pw" type="button" id="togglePassword2" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="passwordMatchError" style="display: none;">
                            Password tidak cocok.
                        </div>
                    </div>

                </div>

                <!-- Terms Checkbox -->
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        Saya setuju dengan <a href="#" class="auth-link">Syarat &amp; Ketentuan</a> serta <a href="#" class="auth-link">Kebijakan Privasi</a> SIMPEL-CAMP.
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-btn-gold mb-3" id="btnSubmit">
                    <i class="bi bi-person-plus me-2"></i>Daftar Akun
                </button>

                <!-- Login Link -->
                <div class="auth-footer">
                    <p class="auth-text-muted mb-0">
                        Sudah punya akun? <a href="<?= BASE_URL ?>/login.php" class="auth-link">Login di sini</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility 1
            document.getElementById('togglePassword1').addEventListener('click', function() {
                toggleVisibility('password', this);
            });

            // Toggle password visibility 2
            document.getElementById('togglePassword2').addEventListener('click', function() {
                toggleVisibility('password_confirm', this);
            });

            function toggleVisibility(inputId, btn) {
                const input = document.getElementById(inputId);
                const icon = btn.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }

            // Password match validation
            const form = document.getElementById('registerForm');
            const pass = document.getElementById('password');
            const confirmPass = document.getElementById('password_confirm');
            const errorMsg = document.getElementById('passwordMatchError');

            function validatePassword() {
                if (pass.value !== confirmPass.value && confirmPass.value !== '') {
                    confirmPass.classList.add('is-invalid');
                    errorMsg.style.display = 'block';
                    return false;
                } else {
                    confirmPass.classList.remove('is-invalid');
                    errorMsg.style.display = 'none';
                    return true;
                }
            }

            pass.addEventListener('input', validatePassword);
            confirmPass.addEventListener('input', validatePassword);

            form.addEventListener('submit', function(e) {
                if (!validatePassword()) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
