<?php
// includes/navbar.php
$is_logged_in = isset($_SESSION['user_id']);
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User';

// Determine if navbar should be transparent initially
$is_transparent = isset($transparent_navbar) && $transparent_navbar ? 'data-transparent="true" class="navbar sc-navbar navbar-expand-lg fixed-top navbar-transparent"' : 'class="navbar sc-navbar navbar-expand-lg fixed-top shadow-sm"';
?>
<nav <?= $is_transparent ?>>
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/">
            ⛺ SIMPEL-<span>CAMP</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-1 text-white"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Public / Pelanggan Menu -->
            <?php if (!$is_logged_in || $user_role == 'pelanggan'): ?>
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'katalog.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/<?= $is_logged_in ? 'pages/pelanggan/katalog.php' : 'katalog.php' ?>">Katalog</a>
                </li>
                
                <?php if ($is_logged_in && $user_role == 'pelanggan'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'reservasi.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/pelanggan/riwayat.php">Reservasi Saya</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'perpanjangan.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/pelanggan/status_perpanjangan.php">Perpanjangan</a>
                </li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>

            <!-- Auth / User Menu -->
            <ul class="navbar-nav ms-auto">
                <?php if (!$is_logged_in): ?>
                <li class="nav-item d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <a class="nav-link" href="<?= BASE_URL ?>/login.php">Login</a>
                    <a class="btn btn-sc-accent ms-lg-2" href="<?= BASE_URL ?>/register.php">Daftar</a>
                </li>
                <?php else: ?>
                <li class="nav-item dropdown mt-3 mt-lg-0">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-accent-theme text-primary-theme rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background-color: var(--accent);">
                            <?= htmlspecialchars(substr($user_name, 0, 1)) ?>
                        </div>
                        <?= htmlspecialchars($user_name) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                        <?php if ($user_role == 'admin'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/pages/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <?php else: ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/pages/pelanggan/dashboard.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/pages/pelanggan/riwayat.php"><i class="bi bi-clock-history me-2"></i>Riwayat</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
