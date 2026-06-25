<?php
/**
 * SIMPEL-CAMP — Premium Glassmorphism Top Bar v2
 * Rich visual design with patterns, decorative elements & animations.
 */

$_hdr_role = $_header_role ?? 'pelanggan';
$_hdr_user = $user_name ?? ($_SESSION['nama'] ?? 'Pengguna');
$_hdr_initial = strtoupper(substr($_hdr_user, 0, 1));
$_hdr_title = $page_title ?? 'Dashboard';
$_hdr_dashboard = BASE_URL . '/pages/' . $_hdr_role . '/dashboard.php';

$_hdr_notif_count = 0;
if ($_hdr_role === 'pelanggan') {
    try {
        $_hdr_db = Database::getInstance();
        $_hdr_stmt = $_hdr_db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0");
        $_hdr_stmt->execute([$_SESSION['user_id']]);
        $_hdr_notif_count = (int) $_hdr_stmt->fetchColumn();
    } catch (Exception $e) {
        $_hdr_notif_count = 0;
    }
}

// Role badge
$_hdr_role_label = $_hdr_role === 'pelanggan' ? 'Pelanggan' : ($_hdr_role === 'admin' ? 'Admin' : 'Super Admin');
$_hdr_role_icon = $_hdr_role === 'pelanggan' ? 'bi-person-check' : ($_hdr_role === 'admin' ? 'bi-shield-check' : 'bi-gear-wide-connected');
?>

<style>
/* ═══ Premium Glass Topbar v2 ═══ */
.pelanggan-topbar.glass-theme {
    display: flex;
    align-items: center;
    gap: 14px;
    position: sticky;
    top: 0;
    z-index: 100;
    margin-left: -82px;
    margin-right: 0;
    padding: 1.2rem 28px 1.2rem calc(82px + 20px);
    width: calc(100% + 82px);
    box-sizing: border-box;
    background: linear-gradient(135deg, 
        rgba(15, 43, 30, 0.94) 0%, 
        rgba(22, 58, 40, 0.92) 40%, 
        rgba(13, 37, 24, 0.95) 100%);
    background-size: 300% 300%;
    animation: gtGradient 10s ease infinite, gtEntrance 0.5s ease-out;
    backdrop-filter: blur(28px); -webkit-backdrop-filter: blur(28px);
    border-bottom: none;
    box-shadow: 0 6px 32px rgba(0,0,0,0.15), 0 1px 0 rgba(82,183,136,0.06) inset;
    overflow: hidden;
}
@keyframes gtGradient { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
@keyframes gtEntrance { from{opacity:0;transform:translateY(-100%)} to{opacity:1;transform:translateY(0)} }

/* ─── Background pattern (subtle grid) ─── */
.glass-theme .gt-pattern {
    position: absolute; inset: 0; z-index: 0; pointer-events: none;
    background-image: 
        radial-gradient(circle at 1px 1px, rgba(82,183,136,0.06) 1px, transparent 0);
    background-size: 24px 24px;
    opacity: 0.6;
}

/* ─── Decorative blobs ─── */
.glass-theme .gt-blob {
    position: absolute; border-radius: 50%; pointer-events: none; z-index: 0;
    filter: blur(30px);
}
.glass-theme .gt-blob-1 {
    width: 120px; height: 120px; top: -40px; right: 15%;
    background: rgba(82, 183, 136, 0.12);
    animation: gtBlobFloat 6s ease-in-out infinite;
}
.glass-theme .gt-blob-2 {
    width: 80px; height: 80px; top: -20px; right: 40%;
    background: rgba(212, 163, 115, 0.08);
    animation: gtBlobFloat 8s ease-in-out 2s infinite;
}
.glass-theme .gt-blob-3 {
    width: 60px; height: 60px; bottom: -20px; left: 25%;
    background: rgba(82, 183, 136, 0.08);
    animation: gtBlobFloat 7s ease-in-out 1s infinite;
}
@keyframes gtBlobFloat {
    0%,100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-10px) scale(1.15); }
}

/* ─── Shimmer sweep ─── */
.glass-theme .gt-shimmer {
    position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.03), rgba(82,183,136,0.05), rgba(255,255,255,0.03), transparent);
    animation: gtShimmer 7s ease-in-out infinite;
    pointer-events: none; z-index: 0;
}
@keyframes gtShimmer { 0%{left:-100%} 50%{left:120%} 100%{left:120%} }

/* ─── Animated bottom border ─── */
.glass-theme .gt-border {
    position: absolute; bottom: 0; left: 0; right: 0; height: 2px; z-index: 1;
    background: linear-gradient(90deg, 
        transparent 0%, rgba(82,183,136,0.6) 15%, 
        rgba(212,163,115,0.5) 35%, rgba(82,183,136,0.6) 55%, 
        rgba(233,196,106,0.3) 75%, rgba(82,183,136,0.5) 90%, 
        transparent 100%);
    background-size: 200% 100%;
    animation: gtBorder 5s linear infinite;
}
@keyframes gtBorder { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

/* ─── Logo (sejajar sidebar) ─── */
.glass-theme .gt-logo {
    position: absolute;
    left: 26px; top: 50%; transform: translateY(-50%);
    z-index: 10;
    text-decoration: none;
    display: flex; align-items: center; justify-content: center;
    width: 38px; height: 38px; border-radius: 12px;
    background: linear-gradient(135deg, #52B788, #D4A373);
    font-size: 1.1rem;
    box-shadow: 0 3px 14px rgba(82,183,136,0.35);
    transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s ease;
    animation: gtLogoGlow 3s ease-in-out infinite;
}
@keyframes gtLogoGlow {
    0%,100% { box-shadow: 0 3px 14px rgba(82,183,136,0.35); }
    50% { box-shadow: 0 4px 22px rgba(82,183,136,0.55), 0 0 10px rgba(82,183,136,0.2); }
}
.glass-theme .gt-logo:hover {
    transform: translateY(-50%) scale(1.12) rotate(8deg);
    box-shadow: 0 6px 28px rgba(82,183,136,0.5);
}
@media (max-width: 992px) { .glass-theme .gt-logo { display: none; } }

/* ─── Title pill ─── */
.glass-theme .gt-title-pill {
    display: flex; align-items: center; gap: 10px;
    position: relative; z-index: 1;
}
.glass-theme .gt-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1rem; font-weight: 700;
    color: #fff; margin: 0; white-space: nowrap;
    padding: 6px 16px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    letter-spacing: 0.2px;
    backdrop-filter: blur(4px);
}
.glass-theme .gt-role-badge {
    display: flex; align-items: center; gap: 5px;
    padding: 4px 10px;
    background: rgba(82,183,136,0.15);
    border: 1px solid rgba(82,183,136,0.15);
    border-radius: 8px;
    font-size: 0.68rem; font-weight: 600;
    color: #52B788;
    font-family: 'Inter', sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.glass-theme .gt-role-badge i { font-size: 0.72rem; }
@media (max-width: 768px) { .glass-theme .gt-role-badge { display: none; } }

/* ─── Actions ─── */
.glass-theme .gt-actions {
    display: flex; align-items: center; gap: 8px;
    margin-left: auto; flex-shrink: 0;
    position: relative; z-index: 1;
}
.glass-theme .gt-icon-btn {
    position: relative; display: flex; align-items: center; justify-content: center;
    width: 38px; height: 38px; border-radius: 11px;
    background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.6);
    text-decoration: none; transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
    font-size: 1.1rem;
    border: 1px solid rgba(255,255,255,0.06);
}
.glass-theme .gt-icon-btn:hover {
    background: rgba(255,255,255,0.14); color: #fff;
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    border-color: rgba(82,183,136,0.25);
}
.glass-theme .gt-icon-btn:active { transform: translateY(0) scale(0.95); }
.glass-theme .gt-notif-badge {
    position: absolute; top: -4px; right: -4px;
    min-width: 18px; height: 18px; border-radius: 9px;
    background: linear-gradient(135deg, #52B788, #40916C);
    color: #fff; font-size: 0.6rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    padding: 0 4px;
    border: 2px solid rgba(15,43,30,0.95);
    animation: gtBadgePop 0.4s cubic-bezier(0.34,1.56,0.64,1), gtPulse 2s ease-in-out 0.5s infinite;
    font-family: 'Inter', sans-serif;
}
@keyframes gtBadgePop { from{transform:scale(0)} to{transform:scale(1)} }
@keyframes gtPulse { 
    0%,100% { box-shadow: 0 0 0 0 rgba(82,183,136,0.4); } 
    50% { box-shadow: 0 0 0 5px rgba(82,183,136,0); } 
}

.glass-theme .gt-divider {
    width: 1px; height: 28px;
    background: linear-gradient(180deg, transparent, rgba(255,255,255,0.12), transparent);
    margin: 0 4px;
}

/* ─── User card (glass mini) ─── */
.glass-theme .gt-user-card {
    display: flex; align-items: center; gap: 10px;
    padding: 5px 12px 5px 6px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px;
    transition: all 0.2s ease;
    cursor: default;
}
.glass-theme .gt-user-card:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(82,183,136,0.15);
}
.glass-theme .gt-avatar {
    width: 34px; height: 34px; border-radius: 10px;
    background: linear-gradient(135deg, #52B788, #2D6A4F);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 0.8rem;
    font-family: 'Inter', sans-serif;
    border: 2px solid rgba(82,183,136,0.2);
    transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    flex-shrink: 0;
}
.glass-theme .gt-user-card:hover .gt-avatar {
    transform: scale(1.08);
    border-color: rgba(82,183,136,0.4);
}
.glass-theme .gt-user-info {
    display: flex; flex-direction: column; line-height: 1.2;
}
.glass-theme .gt-user-name {
    font-size: 0.78rem; font-weight: 600;
    color: rgba(255,255,255,0.85);
    font-family: 'Inter', sans-serif;
    white-space: nowrap;
}
.glass-theme .gt-user-role {
    font-size: 0.62rem; font-weight: 500;
    color: rgba(82,183,136,0.7);
    font-family: 'Inter', sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ─── Mobile ─── */
.glass-theme .gt-hamburger {
    display: none; background: none; border: none;
    color: rgba(255,255,255,0.8); font-size: 1.3rem;
    cursor: pointer; padding: 4px;
    position: relative; z-index: 1;
    transition: transform 0.2s;
}
.glass-theme .gt-hamburger:hover { transform: scale(1.1); }
@media (max-width: 992px) {
    .glass-theme .gt-hamburger { display: flex; align-items: center; }
    .glass-theme .gt-user-info { display: none; }
    .glass-theme .gt-user-card { padding: 3px; }
    .pelanggan-topbar.glass-theme { margin-left: 0; padding: 1rem 16px; width: 100%; }
}

/* ═══ Content entrance ═══ */
.pelanggan-content { animation: gtFadeUp 0.5s ease-out 0.2s both; }
@keyframes gtFadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
</style>

<!-- Premium Glass Topbar -->
<div class="pelanggan-topbar glass-theme">
    <!-- Decorative layers -->
    <div class="gt-pattern"></div>
    <div class="gt-shimmer"></div>
    <div class="gt-blob gt-blob-1"></div>
    <div class="gt-blob gt-blob-2"></div>
    <div class="gt-blob gt-blob-3"></div>
    <div class="gt-border"></div>

    <!-- Mobile hamburger -->
    <button class="gt-hamburger"
            onclick="typeof openPelangganSidebar==='function'?openPelangganSidebar():typeof openSidebar==='function'?openSidebar():null">
        <i class="bi bi-list"></i>
    </button>

    <!-- Logo -->
    <a href="<?= $_hdr_dashboard ?>" class="gt-logo" title="SIMPEL-CAMP">⛺</a>

    <!-- Title + Role Badge -->
    <div class="gt-title-pill">
        <h1 class="gt-title"><?= htmlspecialchars($_hdr_title) ?></h1>
        <span class="gt-role-badge">
            <i class="bi <?= $_hdr_role_icon ?>"></i>
            <?= $_hdr_role_label ?>
        </span>
    </div>

    <!-- Actions -->
    <div class="gt-actions">
        <?php if ($_hdr_role === 'pelanggan'): ?>
        <a href="<?= BASE_URL ?>/pages/pelanggan/notifikasi.php" class="gt-icon-btn" title="Notifikasi">
            <i class="bi bi-bell"></i>
            <?php if ($_hdr_notif_count > 0): ?>
            <span class="gt-notif-badge"><?= $_hdr_notif_count > 9 ? '9+' : $_hdr_notif_count ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/pages/pelanggan/wishlist.php" class="gt-icon-btn" title="Keranjang Sewa">
            <i class="bi bi-heart"></i>
        </a>
        <?php endif; ?>

        <div class="gt-divider"></div>

        <!-- User card -->
        <div class="gt-user-card">
            <div class="gt-avatar" title="<?= htmlspecialchars($_hdr_user) ?>"><?= $_hdr_initial ?></div>
            <div class="gt-user-info">
                <span class="gt-user-name"><?= htmlspecialchars($_hdr_user) ?></span>
                <span class="gt-user-role"><?= $_hdr_role_label ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Floating Particles -->
<script>
(function(){
    const bar = document.querySelector('.pelanggan-topbar.glass-theme');
    if (!bar) return;
    for (let i = 0; i < 8; i++) {
        const p = document.createElement('span');
        const size = 2 + Math.random() * 4;
        const left = 8 + Math.random() * 84;
        const dur = 5 + Math.random() * 7;
        const delay = Math.random() * 6;
        p.style.cssText = `
            position:absolute; width:${size}px; height:${size}px; border-radius:50%;
            background:rgba(82,183,136,${0.1 + Math.random()*0.15});
            left:${left}%; top:${15 + Math.random()*70}%;
            animation: gtFloat ${dur}s ease-in-out ${delay}s infinite;
            pointer-events:none; z-index:0;
        `;
        bar.appendChild(p);
    }
    if (!document.getElementById('gtFloatStyle')) {
        const s = document.createElement('style');
        s.id = 'gtFloatStyle';
        s.textContent = `@keyframes gtFloat {
            0%,100% { transform: translateY(0) translateX(0) scale(1); opacity:0.5; }
            25% { transform: translateY(-10px) translateX(5px) scale(1.3); opacity:1; }
            50% { transform: translateY(-5px) translateX(-7px) scale(0.8); opacity:0.7; }
            75% { transform: translateY(-12px) translateX(4px) scale(1.1); opacity:0.4; }
        }`;
        document.head.appendChild(s);
    }
})();
</script>
