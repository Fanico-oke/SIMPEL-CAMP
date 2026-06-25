<?php
// login.php
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/LogAktivitas.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/pages/" . $_SESSION['role'] . "/dashboard.php");
    exit;
}

$page_title = "Login";
$error = '';
$success = '';

// Flash messages
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input
    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        // Login via database
        $user = User::login($email, $password);

        if ($user) {
            if ($user['status'] !== 'aktif') {
                $error = 'Akun Anda telah dinonaktifkan. Hubungi admin untuk informasi lebih lanjut.';
            } else {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['nama']    = $user['nama'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['foto']    = $user['foto'];

                // Update last login
                User::updateLastLogin($user['id']);

                // Log aktivitas
                try {
                    LogAktivitas::log($user['id'], 'LOGIN', 'User login berhasil');
                } catch (Exception $e) {
                    // Jangan gagalkan login jika log error
                }

                // Redirect sesuai role
                header("Location: " . BASE_URL . "/pages/" . $user['role'] . "/dashboard.php");
                exit;
            }
        } else {
            $error = 'Email atau password salah.';
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
    <meta name="description" content="Login ke SIMPEL-CAMP untuk melakukan reservasi dan menyewa peralatan camping.">

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
        <div class="auth-card">

            <!-- Brand Logo -->
            <a href="<?= BASE_URL ?>/" class="auth-brand">
                <span class="brand-icon">⛺</span> SIMPEL-<span>CAMP</span>
            </a>

            <!-- Heading -->
            <h1 class="auth-title">Selamat Datang Kembali! 👋</h1>
            <p class="auth-subtitle">Silakan masukkan detail login Anda untuk melanjutkan petualangan.</p>

            <!-- Alert Messages -->
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size: 0.85rem; border-radius: 12px;">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.7rem;"></button>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 0.85rem; border-radius: 12px;">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.7rem;"></button>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="" method="POST" id="loginForm" autocomplete="off">

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control border-start-0" id="email" name="email" placeholder="contoh@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label mb-0">Password</label>
                        <a href="#" class="auth-forgot">Lupa password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" placeholder="••••••••" required>
                        <button class="btn-toggle-pw" type="button" id="togglePassword" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-btn-gold mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                </button>

                <!-- Register Link -->
                <div class="auth-footer">
                    <p class="auth-text-muted mb-0">
                        Belum punya akun? <a href="<?= BASE_URL ?>/register.php" class="auth-link">Daftar sekarang</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
