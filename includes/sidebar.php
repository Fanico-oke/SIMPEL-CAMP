<?php
/**
 * SIMPEL-CAMP — Admin Sidebar (Floating Capsule)
 * Same design as pelanggan — dark green floating pill
 */

// Auto-detect: if super admin, load superadmin sidebar
if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
    include __DIR__ . '/sidebar_superadmin.php';
    return;
}

$currentPage = basename($_SERVER['PHP_SELF']);

if (!function_exists('isActive')) {
    function isActive($pages, $current) {
        if (is_array($pages)) return in_array($current, $pages) ? 'active' : '';
        return ($current === $pages) ? 'active' : '';
    }
}
?>

<style>
/* ═══ FLOATING CAPSULE — Admin ═══ */
.admin-wrapper, .admin-wrapper {
    display: flex !important; min-height: 100vh !important; background: var(--bg-body, #F2F7F4) !important;
}
.admin-main, .admin-main {
    margin-left: 0 !important; min-height: 100vh !important; flex: 1 !important;
    display: flex !important; flex-direction: column !important;
    padding-left: 82px !important; transition: padding-left 0.4s cubic-bezier(0.4,0,0.2,1) !important;
}
.admin-sidebar, .admin-sidebar {
    position: fixed !important; top: 50% !important; left: 16px !important;
    transform: translateY(-50%) !important; width: 58px !important;
    height: auto !important; max-height: calc(100vh - 40px) !important;
    background: linear-gradient(180deg, #0F2B1E 0%, #122E21 50%, #0D2518 100%) !important;
    border-radius: 30px !important; z-index: 1050 !important;
    display: flex !important; flex-direction: column !important; align-items: center !important;
    padding: 12px 0 !important;
    box-shadow: 0 10px 40px rgba(15,43,30,0.35), 0 4px 12px rgba(0,0,0,0.15), 0 0 0 1px rgba(82,183,136,0.08), inset 0 1px 0 rgba(255,255,255,0.04) !important;
    overflow: visible !important; border: 1px solid rgba(82,183,136,0.1) !important;
    bottom: auto !important;
    animation: adminFloat 0.6s cubic-bezier(0.34,1.56,0.64,1) forwards, adminGlow 4s ease-in-out infinite 1s !important;
}
.admin-sidebar:hover, .admin-sidebar:hover {
    box-shadow: 0 14px 48px rgba(15,43,30,0.45), 0 6px 16px rgba(0,0,0,0.2), 0 0 0 1px rgba(82,183,136,0.15) !important;
}
@keyframes adminFloat {
    0% { opacity:0; transform: translateY(-50%) translateX(-20px) scale(0.95); }
    100% { opacity:1; transform: translateY(-50%) translateX(0) scale(1); }
}
@keyframes adminGlow {
    0%,100% { box-shadow: 0 10px 40px rgba(15,43,30,0.35), 0 4px 12px rgba(0,0,0,0.15), 0 0 0 1px rgba(82,183,136,0.08); }
    50% { box-shadow: 0 10px 40px rgba(15,43,30,0.4), 0 4px 12px rgba(0,0,0,0.18), 0 0 8px rgba(82,183,136,0.12); }
}

/* Brand */
.admin-sidebar .sidebar-brand, .admin-sidebar .sidebar-brand {
    display:flex !important; align-items:center !important; justify-content:center !important;
    padding:4px 0 8px !important; min-height:auto !important; text-decoration:none !important; color:#fff !important; width:100% !important;
}
.admin-sidebar .brand-icon, .admin-sidebar .brand-icon {
    width:38px !important; height:38px !important; min-width:38px !important; border-radius:14px !important;
    background:linear-gradient(135deg,#52B788,#D4A373) !important;
    display:flex !important; align-items:center !important; justify-content:center !important;
    font-size:1.05rem !important; box-shadow:0 4px 14px rgba(82,183,136,0.35) !important;
    transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s ease !important;
}
.admin-sidebar .sidebar-brand:hover .brand-icon, .admin-sidebar .sidebar-brand:hover .brand-icon { transform:scale(1.12) rotate(5deg) !important; box-shadow:0 6px 20px rgba(82,183,136,0.5) !important; }
.admin-sidebar .brand-text, .admin-sidebar .brand-text { display:none !important; }

/* Nav */
.admin-sidebar .sidebar-nav, .admin-sidebar .sidebar-nav { flex:1 !important; overflow:visible !important; padding:0 !important; width:100% !important; display:flex !important; flex-direction:column !important; align-items:center !important; }
.admin-sidebar .nav-section-label, .admin-sidebar .nav-section-label { display:none !important; }
.admin-sidebar .nav-list, .admin-sidebar .nav-list { list-style:none !important; margin:0 !important; padding:2px 0 !important; width:100% !important; display:flex !important; flex-direction:column !important; align-items:center !important; }
.admin-sidebar .nav-item, .admin-sidebar .nav-item { margin:2px 0 !important; position:relative !important; width:100% !important; display:flex !important; justify-content:center !important; }

/* Nav Link */
.admin-sidebar .nav-link, .admin-sidebar .nav-link {
    display:flex !important; align-items:center !important; justify-content:center !important;
    width:42px !important; height:42px !important; border-radius:14px !important;
    text-decoration:none !important; color:rgba(255,255,255,0.4) !important;
    transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1) !important;
    padding:0 !important; position:relative !important; background:transparent !important;
    border: none !important;
}
.admin-sidebar .nav-link:hover, .admin-sidebar .nav-link:hover { color:rgba(255,255,255,0.95) !important; background:rgba(82,183,136,0.12) !important; transform:scale(1.1) !important; }
.admin-sidebar .nav-link i, .admin-sidebar .nav-link i { font-size:1.18rem !important; width:auto !important; min-width:auto !important; height:auto !important; color:inherit !important; background:transparent !important; transition:all 0.25s ease !important; display:flex !important; align-items:center !important; justify-content:center !important; }
.admin-sidebar .nav-text, .admin-sidebar .nav-text { display:none !important; }

/* Active */
.admin-sidebar .nav-item.active .nav-link, .admin-sidebar .nav-item.active .nav-link {
    color:#52B788 !important; background:rgba(82,183,136,0.18) !important;
    box-shadow:0 0 18px rgba(82,183,136,0.25), inset 0 0 12px rgba(82,183,136,0.08) !important;
    animation: adminActivePulse 3s ease-in-out infinite !important;
}
.admin-sidebar .nav-item.active .nav-link i, .admin-sidebar .nav-item.active .nav-link i { color:#52B788 !important; }
.admin-sidebar .nav-item.active::before, .admin-sidebar .nav-item.active::before { display:none !important; }
@keyframes adminActivePulse {
    0%,100% { box-shadow:0 0 18px rgba(82,183,136,0.25), inset 0 0 12px rgba(82,183,136,0.08); }
    50% { box-shadow:0 0 24px rgba(82,183,136,0.35), inset 0 0 16px rgba(82,183,136,0.12); }
}

/* Stagger entrance */
.admin-sidebar .nav-item, .admin-sidebar .nav-item { opacity:0; animation: adminNavAppear 0.4s cubic-bezier(0.34,1.56,0.64,1) forwards; }
.admin-sidebar .nav-item:nth-child(1), .admin-sidebar .nav-item:nth-child(1) { animation-delay:0.1s; }
.admin-sidebar .nav-item:nth-child(2), .admin-sidebar .nav-item:nth-child(2) { animation-delay:0.15s; }
.admin-sidebar .nav-item:nth-child(3), .admin-sidebar .nav-item:nth-child(3) { animation-delay:0.2s; }
.admin-sidebar .nav-item:nth-child(4), .admin-sidebar .nav-item:nth-child(4) { animation-delay:0.25s; }
.admin-sidebar .nav-item:nth-child(5), .admin-sidebar .nav-item:nth-child(5) { animation-delay:0.3s; }
@keyframes adminNavAppear { 0% { opacity:0; transform:translateX(-8px) scale(0.8); } 100% { opacity:1; transform:translateX(0) scale(1); } }

/* Divider */
.admin-sidebar .sidebar-divider, .admin-sidebar .sidebar-divider { width:5px !important; height:5px !important; border-radius:50% !important; background:rgba(212,163,115,0.3) !important; margin:5px auto !important; box-shadow:0 0 6px rgba(212,163,115,0.15) !important; }

/* Logout */
.admin-sidebar .nav-logout .nav-link:hover, .admin-sidebar .nav-logout .nav-link:hover { color:#f87171 !important; background:rgba(239,68,68,0.12) !important; box-shadow:0 0 12px rgba(239,68,68,0.15) !important; }

/* Pop-up Tooltip */
.admin-sidebar .nav-link[data-tooltip], .admin-sidebar .nav-link[data-tooltip] { position:relative !important; }
.admin-sidebar .nav-link[data-tooltip]::after, .admin-sidebar .nav-link[data-tooltip]::after {
    content:attr(data-tooltip) !important; position:absolute !important;
    left:calc(100% + 16px) !important; top:50% !important;
    transform:translateY(-50%) translateX(-6px) scale(0.85) !important;
    background:linear-gradient(135deg,#0F2B1E,#1a3a2a) !important; color:#fff !important;
    padding:8px 16px !important; border-radius:12px !important;
    font-size:0.75rem !important; font-family:'Inter',sans-serif !important; font-weight:600 !important;
    white-space:nowrap !important; opacity:0 !important; pointer-events:none !important;
    transition:all 0.25s cubic-bezier(0.34,1.56,0.64,1) !important;
    z-index:9999 !important; box-shadow:0 8px 24px rgba(15,43,30,0.4), 0 0 0 1px rgba(82,183,136,0.12) !important;
    border:1px solid rgba(82,183,136,0.15) !important;
}
.admin-sidebar .nav-link[data-tooltip]::before, .admin-sidebar .nav-link[data-tooltip]::before {
    content:'' !important; position:absolute !important; left:calc(100% + 10px) !important; top:50% !important;
    transform:translateY(-50%) rotate(45deg) scale(0) !important; width:10px !important; height:10px !important;
    background:#0F2B1E !important; border-left:1px solid rgba(82,183,136,0.15) !important; border-bottom:1px solid rgba(82,183,136,0.15) !important;
    opacity:0 !important; transition:all 0.25s cubic-bezier(0.34,1.56,0.64,1) !important; z-index:9998 !important;
}
.admin-sidebar .nav-link[data-tooltip]:hover::after, .admin-sidebar .nav-link[data-tooltip]:hover::after { opacity:1 !important; transform:translateY(-50%) translateX(0) scale(1) !important; }
.admin-sidebar .nav-link[data-tooltip]:hover::before, .admin-sidebar .nav-link[data-tooltip]:hover::before { opacity:1 !important; transform:translateY(-50%) rotate(45deg) scale(1) !important; }

/* Toggle hidden */
.admin-sidebar .sidebar-toggle-btn, .admin-sidebar .sidebar-toggle-btn { display:none !important; }
.sidebar-collapsed .admin-main, .sidebar-collapsed .admin-main { padding-left:82px !important; }
.sidebar-collapsed .admin-sidebar, .sidebar-collapsed .admin-sidebar { width:58px !important; }

/* Backdrop */
.sidebar-backdrop { display:none !important; position:fixed !important; inset:0 !important; background:rgba(15,43,30,0.6) !important; backdrop-filter:blur(6px) !important; z-index:1040 !important; opacity:0 !important; transition:opacity 0.3s ease !important; }
.sidebar-backdrop.show { display:block !important; opacity:1 !important; }

/* Responsive */
@media (max-width:768px) {
    .admin-sidebar, .admin-sidebar { left:-80px !important; transition:left 0.4s cubic-bezier(0.34,1.56,0.64,1) !important; animation:none !important; }
    .admin-sidebar.sidebar-open, .admin-sidebar.sidebar-open { left:12px !important; }
    .admin-main, .admin-main { padding-left:0 !important; }
}
@media (min-width:769px) and (max-width:992px) {
    .admin-sidebar, .admin-sidebar { left:10px !important; width:52px !important; border-radius:26px !important; }
    .admin-main, .admin-main { padding-left:74px !important; }
}
.admin-sidebar::-webkit-scrollbar, .admin-sidebar::-webkit-scrollbar { display:none !important; }
.admin-sidebar, .admin-sidebar { scrollbar-width:none !important; }
</style>

<!-- Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

<!-- Admin Floating Capsule Sidebar -->
<aside class="admin-sidebar pelanggan-sidebar" id="adminSidebar">
    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu</div>
        <ul class="nav-list">
            <li class="nav-item <?= isActive('dashboard.php', $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/admin/dashboard.php" class="nav-link" data-tooltip="Dashboard">
                    <i class="bi bi-speedometer2"></i><span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?= isActive('kelola_barang.php', $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/admin/kelola_barang.php" class="nav-link" data-tooltip="Kelola Barang">
                    <i class="bi bi-box-seam"></i><span class="nav-text">Kelola Barang</span>
                </a>
            </li>
            <li class="nav-item <?= isActive('pelanggan.php', $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/admin/pelanggan.php" class="nav-link" data-tooltip="Data Pelanggan">
                    <i class="bi bi-people"></i><span class="nav-text">Data Pelanggan</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-divider"></div>
        <ul class="nav-list">
            <li class="nav-item <?= isActive(['transaksi.php','detail_reservasi.php','nota.php'], $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/admin/transaksi.php" class="nav-link" data-tooltip="Transaksi">
                    <i class="bi bi-receipt-cutoff"></i><span class="nav-text">Transaksi</span>
                </a>
            </li>
            <li class="nav-item <?= isActive('laporan.php', $currentPage) ?>">
                <a href="<?= BASE_URL ?>/pages/admin/laporan.php" class="nav-link" data-tooltip="Laporan">
                    <i class="bi bi-graph-up-arrow"></i><span class="nav-text">Laporan</span>
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
</aside>

<script>
function toggleSidebar() {}
function openSidebar() {
    const s=document.getElementById('adminSidebar'),b=document.getElementById('sidebarBackdrop');
    if(s)s.classList.add('sidebar-open');if(b)b.classList.add('show');document.body.style.overflow='hidden';
}
function closeSidebar() {
    const s=document.getElementById('adminSidebar'),b=document.getElementById('sidebarBackdrop');
    if(s)s.classList.remove('sidebar-open');if(b)b.classList.remove('show');document.body.style.overflow='';
}
(function(){
    localStorage.removeItem('sc_sidebar_collapsed');
    localStorage.removeItem('sc_sidebar_expanded');
    document.querySelectorAll('.admin-sidebar .nav-link, .admin-sidebar .nav-link').forEach(l=>l.addEventListener('click',()=>{if(window.innerWidth<=768)closeSidebar();}));
    window.addEventListener('resize',()=>{if(window.innerWidth>768)closeSidebar();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape')closeSidebar();});
})();
</script>
