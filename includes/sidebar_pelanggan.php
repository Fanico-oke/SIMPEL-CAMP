<?php
/**
 * SIMPEL-CAMP — Pelanggan Sidebar (Floating Capsule)
 * Dark green palette, animated, with pop-up tooltips
 */

$currentPage = isset($current_page) ? $current_page : basename($_SERVER['PHP_SELF'], '.php');

if (!function_exists('isActive')) {
    function isActive($pages, $current) {
        if (is_array($pages)) return in_array($current, $pages) ? 'active' : '';
        return ($current === $pages) ? 'active' : '';
    }
}
?>

<style>
/* ═══════════════════════════════════════════════════
   FLOATING CAPSULE SIDEBAR — Green/Gold Palette
   ═══════════════════════════════════════════════════ */

/* Wrapper */
.pelanggan-wrapper {
    display: flex !important;
    min-height: 100vh !important;
    background: var(--bg-body, #F2F7F4) !important;
}
.pelanggan-main {
    margin-left: 0 !important;
    min-height: 100vh !important;
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    padding-left: 82px !important;
    transition: padding-left 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

/* ─── Floating Capsule ─── */
.pelanggan-sidebar {
    position: fixed !important;
    top: 50% !important;
    left: 16px !important;
    transform: translateY(-50%) !important;
    width: 58px !important;
    height: auto !important;
    max-height: calc(100vh - 40px) !important;
    background: linear-gradient(180deg, #0F2B1E 0%, #122E21 50%, #0D2518 100%) !important;
    border-radius: 30px !important;
    z-index: 1050 !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    padding: 12px 0 !important;
    box-shadow:
        0 10px 40px rgba(15,43,30,0.35),
        0 4px 12px rgba(0,0,0,0.15),
        0 0 0 1px rgba(82,183,136,0.08),
        inset 0 1px 0 rgba(255,255,255,0.04) !important;
    overflow: visible !important;
    gap: 0 !important;
    border: 1px solid rgba(82,183,136,0.1) !important;
    transition: box-shadow 0.3s ease !important;
    bottom: auto !important;
    animation: sidebarFloat 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards !important;
}
.pelanggan-sidebar:hover {
    box-shadow:
        0 14px 48px rgba(15,43,30,0.45),
        0 6px 16px rgba(0,0,0,0.2),
        0 0 0 1px rgba(82,183,136,0.15),
        inset 0 1px 0 rgba(255,255,255,0.06) !important;
}

/* Entrance animation */
@keyframes sidebarFloat {
    0% { opacity: 0; transform: translateY(-50%) translateX(-20px) scale(0.95); }
    100% { opacity: 1; transform: translateY(-50%) translateX(0) scale(1); }
}

/* Subtle breathing glow */
@keyframes sidebarGlow {
    0%, 100% { box-shadow: 0 10px 40px rgba(15,43,30,0.35), 0 4px 12px rgba(0,0,0,0.15), 0 0 0 1px rgba(82,183,136,0.08); }
    50% { box-shadow: 0 10px 40px rgba(15,43,30,0.4), 0 4px 12px rgba(0,0,0,0.18), 0 0 8px rgba(82,183,136,0.12); }
}
.pelanggan-sidebar {
    animation: sidebarFloat 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards, sidebarGlow 4s ease-in-out infinite 1s !important;
}

/* ─── Brand Logo ─── */
.pelanggan-sidebar .sidebar-brand {
    display: flex !important; align-items: center !important; justify-content: center !important;
    width: 42px !important; height: 42px !important;
    border-radius: 14px !important;
    background: linear-gradient(135deg, #52B788, #D4A373) !important;
    text-decoration: none !important;
    font-size: 1.15rem !important;
    box-shadow: 0 3px 12px rgba(82,183,136,0.3) !important;
    transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s ease !important;
    margin-bottom: 4px !important;
    flex-shrink: 0 !important;
}
.pelanggan-sidebar .sidebar-brand:hover {
    transform: scale(1.1) rotate(5deg) !important;
    box-shadow: 0 5px 18px rgba(82,183,136,0.45) !important;
}

/* ─── Nav ─── */
.pelanggan-sidebar .sidebar-nav {
    flex: 1 !important; overflow: visible !important; padding: 0 !important;
    width: 100% !important; display: flex !important; flex-direction: column !important; align-items: center !important;
}
.pelanggan-sidebar .nav-section-label { display: none !important; }
.pelanggan-sidebar .nav-list {
    list-style: none !important; margin: 0 !important; padding: 2px 0 !important;
    width: 100% !important; display: flex !important; flex-direction: column !important; align-items: center !important;
}
.pelanggan-sidebar .nav-item {
    margin: 2px 0 !important; position: relative !important;
    width: 100% !important; display: flex !important; justify-content: center !important;
}

/* ─── Nav Link — icon circle ─── */
.pelanggan-sidebar .nav-link {
    display: flex !important; align-items: center !important; justify-content: center !important;
    width: 42px !important; height: 42px !important; border-radius: 14px !important;
    text-decoration: none !important; color: rgba(255,255,255,0.4) !important;
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    padding: 0 !important; position: relative !important; background: transparent !important;
}
.pelanggan-sidebar .nav-link:hover {
    color: rgba(255,255,255,0.95) !important;
    background: rgba(82,183,136,0.12) !important;
    transform: scale(1.1) !important;
}
.pelanggan-sidebar .nav-link i {
    font-size: 1.18rem !important;
    width: auto !important; min-width: auto !important; height: auto !important;
    display: flex !important; align-items: center !important; justify-content: center !important;
    color: inherit !important; background: transparent !important;
    border-radius: 0 !important; transition: all 0.25s ease !important;
}
.pelanggan-sidebar .nav-text { display: none !important; }

/* ─── Active — green glow ─── */
.pelanggan-sidebar .nav-item.active .nav-link {
    color: #52B788 !important;
    background: rgba(82,183,136,0.18) !important;
    box-shadow: 0 0 18px rgba(82,183,136,0.25), inset 0 0 12px rgba(82,183,136,0.08) !important;
}
.pelanggan-sidebar .nav-item.active .nav-link i { color: #52B788 !important; }
.pelanggan-sidebar .nav-item.active::before { display: none !important; }

/* Active pulse animation */
@keyframes activePulse {
    0%, 100% { box-shadow: 0 0 18px rgba(82,183,136,0.25), inset 0 0 12px rgba(82,183,136,0.08); }
    50% { box-shadow: 0 0 24px rgba(82,183,136,0.35), inset 0 0 16px rgba(82,183,136,0.12); }
}
.pelanggan-sidebar .nav-item.active .nav-link {
    animation: activePulse 3s ease-in-out infinite !important;
}

/* Stagger entrance animation for nav items */
.pelanggan-sidebar .nav-item {
    opacity: 0;
    animation: navItemAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.pelanggan-sidebar .nav-item:nth-child(1) { animation-delay: 0.1s; }
.pelanggan-sidebar .nav-item:nth-child(2) { animation-delay: 0.15s; }
.pelanggan-sidebar .nav-item:nth-child(3) { animation-delay: 0.2s; }
.pelanggan-sidebar .nav-item:nth-child(4) { animation-delay: 0.25s; }
.pelanggan-sidebar .nav-item:nth-child(5) { animation-delay: 0.3s; }
.pelanggan-sidebar .nav-item:nth-child(6) { animation-delay: 0.35s; }
.pelanggan-sidebar .nav-item:nth-child(7) { animation-delay: 0.4s; }

@keyframes navItemAppear {
    0% { opacity: 0; transform: translateX(-8px) scale(0.8); }
    100% { opacity: 1; transform: translateX(0) scale(1); display: flex; }
}

/* ─── Divider — gold dot ─── */
.pelanggan-sidebar .sidebar-divider {
    width: 5px !important; height: 5px !important; border-radius: 50% !important;
    background: rgba(212,163,115,0.3) !important; margin: 5px auto !important;
    box-shadow: 0 0 6px rgba(212,163,115,0.15) !important;
}

/* ─── Logout ─── */
.pelanggan-sidebar .nav-logout .nav-link:hover {
    color: #f87171 !important; background: rgba(239,68,68,0.12) !important;
    box-shadow: 0 0 12px rgba(239,68,68,0.15) !important;
}

/* ─── Badge ─── */
.pelanggan-sidebar .nav-badge-dot {
    position: absolute !important; top: 4px !important; right: 4px !important;
    width: 8px !important; height: 8px !important; background: #ef4444 !important;
    border-radius: 50% !important; border: 2px solid #0F2B1E !important; z-index: 3 !important;
    animation: badgePing 2s ease-in-out infinite !important;
}
.pelanggan-sidebar .nav-badge { display: none !important; }

@keyframes badgePing {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.3); }
}

/* ─── Pop-up Tooltip ─── */
.pelanggan-sidebar .nav-link[data-tooltip] { position: relative !important; }
.pelanggan-sidebar .nav-link[data-tooltip]::after {
    content: attr(data-tooltip) !important;
    position: absolute !important;
    left: calc(100% + 16px) !important;
    top: 50% !important;
    transform: translateY(-50%) translateX(-6px) scale(0.85) !important;
    background: linear-gradient(135deg, #0F2B1E, #1a3a2a) !important;
    color: #fff !important;
    padding: 8px 16px !important;
    border-radius: 12px !important;
    font-size: 0.75rem !important;
    font-family: 'Inter', sans-serif !important;
    font-weight: 600 !important;
    white-space: nowrap !important;
    opacity: 0 !important;
    pointer-events: none !important;
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    z-index: 9999 !important;
    box-shadow: 0 8px 24px rgba(15,43,30,0.4), 0 0 0 1px rgba(82,183,136,0.12) !important;
    letter-spacing: 0.4px !important;
    border: 1px solid rgba(82,183,136,0.15) !important;
}
/* Pop-up arrow */
.pelanggan-sidebar .nav-link[data-tooltip]::before {
    content: '' !important;
    position: absolute !important;
    left: calc(100% + 10px) !important;
    top: 50% !important;
    transform: translateY(-50%) rotate(45deg) scale(0) !important;
    width: 10px !important; height: 10px !important;
    background: #0F2B1E !important;
    border-left: 1px solid rgba(82,183,136,0.15) !important;
    border-bottom: 1px solid rgba(82,183,136,0.15) !important;
    opacity: 0 !important;
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    z-index: 9998 !important;
}
.pelanggan-sidebar .nav-link[data-tooltip]:hover::after {
    opacity: 1 !important;
    transform: translateY(-50%) translateX(0) scale(1) !important;
}
.pelanggan-sidebar .nav-link[data-tooltip]:hover::before {
    opacity: 1 !important;
    transform: translateY(-50%) rotate(45deg) scale(1) !important;
}

/* ─── Toggle — hidden ─── */
.pelanggan-sidebar .sidebar-toggle-btn { display: none !important; }

/* ─── Backdrop ─── */
.sidebar-backdrop {
    display: none !important; position: fixed !important; inset: 0 !important;
    background: rgba(15,43,30,0.6) !important; backdrop-filter: blur(6px) !important;
    z-index: 1040 !important; opacity: 0 !important; transition: opacity 0.3s ease !important;
}
.sidebar-backdrop.show { display: block !important; opacity: 1 !important; }

/* ─── Responsive ─── */
@media (max-width: 768px) {
    .pelanggan-sidebar {
        left: -80px !important;
        transition: left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        animation: none !important;
    }
    .pelanggan-sidebar.sidebar-open { left: 12px !important; }
    .pelanggan-main { padding-left: 0 !important; }
}
@media (min-width: 769px) and (max-width: 992px) {
    .pelanggan-sidebar { left: 10px !important; width: 52px !important; border-radius: 26px !important; }
    .pelanggan-sidebar .brand-icon { width: 34px !important; height: 34px !important; min-width: 34px !important; }
    .pelanggan-sidebar .nav-link { width: 38px !important; height: 38px !important; }
    .pelanggan-main { padding-left: 74px !important; }
}

/* Scrollbar hide */
.pelanggan-sidebar::-webkit-scrollbar { display: none !important; }
.pelanggan-sidebar { scrollbar-width: none !important; }

/* Expanded override — not used for floating */
.sidebar-expanded .pelanggan-sidebar { width: 58px !important; }
.sidebar-expanded .pelanggan-main { padding-left: 82px !important; }
</style>

<!-- Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closePelangganSidebar()"></div>

<!-- Floating Capsule Sidebar -->
<aside class="pelanggan-sidebar" id="pelangganSidebar">
    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu</div>        <ul class="nav-list">
            <li class="nav-item <?= isActive('dashboard', $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/pelanggan/dashboard.php" class="nav-link" data-tooltip="Dashboard">
                    <i class="bi bi-grid-1x2"></i><span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?= isActive('katalog', $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/pelanggan/katalog.php" class="nav-link" data-tooltip="Katalog">
                    <i class="bi bi-shop"></i><span class="nav-text">Katalog</span>
                </a>
            </li>
            </ul>
        <div class="sidebar-divider"></div>
        <ul class="nav-list">
            <li class="nav-item <?= isActive(['transaksi','reservasi','perpanjangan','riwayat','pembayaran','nota','status_perpanjangan','pemesanan'], $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/pelanggan/transaksi.php" class="nav-link" data-tooltip="Transaksi">
                    <i class="bi bi-receipt"></i><span class="nav-text">Transaksi</span>
                </a>
            </li>
            <li class="nav-item <?= isActive('notifikasi', $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/pelanggan/notifikasi.php" class="nav-link" data-tooltip="Notifikasi">
                    <i class="bi bi-bell"></i><span class="nav-text">Notifikasi</span>
                    <span class="nav-badge-dot"></span>
                </a>
            </li>
        </ul>
        <div class="sidebar-divider"></div>
        <ul class="nav-list">
            <li class="nav-item <?= isActive('profil', $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/pelanggan/profil.php" class="nav-link" data-tooltip="Profil">
                    <i class="bi bi-person"></i><span class="nav-text">Profil</span>
                </a>
            </li>
            <li class="nav-item <?= isActive('member', $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/pelanggan/member.php" class="nav-link" data-tooltip="Member">
                    <i class="bi bi-award"></i><span class="nav-text">Member</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-divider"></div>
        <ul class="nav-list">
            <li class="nav-item nav-logout">
                <a href="<?= BASE_URL ?>/logout.php" class="nav-link" data-tooltip="Logout"
                   onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                    <i class="bi bi-box-arrow-right"></i><span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
    <button class="sidebar-toggle-btn" style="display:none"><i class="bi bi-chevron-double-right"></i></button>
</aside>

<script>
function togglePelangganSidebar() {}
function openPelangganSidebar() {
    const s = document.getElementById('pelangganSidebar'), b = document.getElementById('sidebarBackdrop');
    if (s) s.classList.add('sidebar-open'); if (b) b.classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closePelangganSidebar() {
    const s = document.getElementById('pelangganSidebar'), b = document.getElementById('sidebarBackdrop');
    if (s) s.classList.remove('sidebar-open'); if (b) b.classList.remove('show');
    document.body.style.overflow = '';
}
(function(){
    localStorage.removeItem('sc_pelanggan_sidebar_collapsed');
    localStorage.removeItem('sc_sidebar_version');
    localStorage.removeItem('sc_pelanggan_sidebar_expanded');
    document.querySelectorAll('.pelanggan-sidebar .nav-link').forEach(l => l.addEventListener('click', () => { if(window.innerWidth<=768) closePelangganSidebar(); }));
    window.addEventListener('resize', () => { if(window.innerWidth>768) closePelangganSidebar(); });
    document.addEventListener('keydown', e => { if(e.key==='Escape') closePelangganSidebar(); });
})();
</script>
