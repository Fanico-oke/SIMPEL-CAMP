<?php
// includes/topbar_pelanggan.php
// Unified topbar for all pelanggan pages
// Requires: $page_title, $current_page (set by parent page)
// Optional: $user_name (falls back to session)

$_tb_user_name = $user_name ?? ($_SESSION['nama'] ?? 'Pelanggan');
$_tb_initial = strtoupper(substr($_tb_user_name, 0, 1));
$_tb_page_title = $page_title ?? 'Dashboard';

// Notification count & Cart count
$_tb_notif_count = 0;
$_tb_cart_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $_tb_db = Database::getInstance();
        $_tb_notif_stmt = $_tb_db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0");
        $_tb_notif_stmt->execute([$_SESSION['user_id']]);
        $_tb_notif_count = (int) $_tb_notif_stmt->fetchColumn();

        $_tb_cart_stmt = $_tb_db->prepare("SELECT COUNT(*) FROM keranjang WHERE user_id = ?");
        $_tb_cart_stmt->execute([$_SESSION['user_id']]);
        $_tb_cart_count = (int) $_tb_cart_stmt->fetchColumn();
    } catch (Exception $e) {}
}
?>
<div class="pelanggan-topbar">
    <!-- Mobile hamburger -->
    <button class="btn btn-link text-dark d-lg-none p-0 border-0" onclick="openPelangganSidebar()" style="font-size:1.3rem;">
        <i class="bi bi-list"></i>
    </button>

    <!-- Logo (visible on topbar) -->
    <div class="topbar-logo d-none d-lg-block">
        <a href="<?= BASE_URL ?>/pages/pelanggan/dashboard.php" title="SIMPEL-CAMP">⛺</a>
    </div>

    <!-- Page Title -->
    <div class="topbar-greeting">
        <h1><?= htmlspecialchars($_tb_page_title) ?></h1>
    </div>

    <!-- Actions -->
    <div class="topbar-actions">
        <!-- Notification Bell -->
        <a href="<?= BASE_URL ?>/pages/pelanggan/notifikasi.php" class="topbar-icon-btn" title="Notifikasi">
            <i class="bi bi-bell"></i>
            <?php if ($_tb_notif_count > 0): ?>
            <span class="notif-dot"></span>
            <?php endif; ?>
        </a>

        <!-- Wishlist Heart -->
        <a href="<?= BASE_URL ?>/pages/pelanggan/wishlist.php" class="topbar-icon-btn" title="Keranjang Sewa">
            <i class="bi bi-heart"></i>
            <?php if ($_tb_cart_count > 0): ?>
            <span class="notif-dot"></span>
            <?php endif; ?>
        </a>

        <!-- User Name (desktop only) -->
        <span class="text-secondary small d-none d-md-inline" style="font-weight:600; margin-left:4px;"><?= htmlspecialchars($_tb_user_name) ?></span>

        <!-- Avatar -->
        <div class="topbar-avatar" title="<?= htmlspecialchars($_tb_user_name) ?>">
            <?= $_tb_initial ?>
        </div>
    </div>
</div>
