<?php
// pages/pelanggan/katalog.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/Kategori.php';
require_once dirname(__DIR__, 2) . '/classes/Wishlist.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'E-Katalog Peralatan';
$current_page = 'katalog';
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// Fetch real data from database
$allBarang = Barang::getAll(['limit' => 100]);
$allKategori = Kategori::getAll();

// Build products array from database
$products = [];
$fallbackImages = [
    'https://images.unsplash.com/photo-1504280390467-336c1e55b4bc?auto=format&fit=crop&w=500&q=80',
    'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=500&q=80',
    'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=500&q=80',
];
foreach ($allBarang as $idx => $b) {
    $imgUrl = !empty($b['gambar']) ? ASSETS_URL . '/img/barang/' . $b['gambar'] : $fallbackImages[$idx % count($fallbackImages)];
    $products[] = [
        'id'    => $b['id'],
        'name'  => $b['nama'],
        'price' => (int)$b['harga_per_hari'],
        'cat'   => $b['kategori_nama'] ?? 'Lainnya',
        'stock' => (int)$b['stok_tersedia'],
        'img'   => $imgUrl,
    ];
}

// Build categories array from database
$categories = [
    ['label' => 'Semua', 'icon' => 'bi-grid-3x3-gap-fill'],
];
foreach ($allKategori as $kat) {
    $categories[] = [
        'label' => $kat['nama'],
        'icon'  => !empty($kat['icon']) ? $kat['icon'] : 'bi-box',
    ];
}

// Load active promos from DB
$db = Database::getInstance();
$stmtPromo = $db->query("SELECT * FROM promo WHERE status = 'aktif' AND mulai <= CURDATE() AND selesai >= CURDATE() ORDER BY created_at DESC LIMIT 6");
$promos = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);

// Wishlist count for badge
$wishlistCount = Wishlist::count($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <meta name="description" content="E-Katalog peralatan camping SIMPEL-CAMP - Sewa tenda, carrier, sleeping bag dan peralatan outdoor terbaik dengan harga terjangkau.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">

<style>
/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   E-KATALOG â€” Full-Featured Catalog with New Design System
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
:root {
    --primary: #2D6A4F;
    --primary-light: #52B788;
    --primary-dark: #1B4332;
    --accent-gold: #D4A373;
    --bg-page: #F2F7F4;
    --bg-card: #FFFFFF;
    --shadow-card: 0 2px 20px rgba(0,0,0,0.04);
    --radius-card: 20px;
    --radius-pill: 50px;
    --radius-input: 12px;
    --text-primary: #1A1A2E;
    --text-secondary: #6B7280;
    --font-body: 'Inter', sans-serif;
    --font-heading: 'Outfit', sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
}
body {
    font-family: var(--font-body);
    background: var(--bg-page);
    color: var(--text-primary);
    margin: 0;
}
/* Content padding handled by pelanggan-system.css */

/* Topbar styles now handled by pelanggan-system.css */


/* â”€â”€â”€ Full-Width Search Bar â”€â”€â”€ */
.catalog-search-bar {
    background: var(--bg-card);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    gap: 12px;
    align-items: center;
    animation: fadeInUp 0.5s ease forwards;
}
.catalog-search-bar .search-input-wrap {
    flex: 1;
    position: relative;
}
.catalog-search-bar .search-input-wrap i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF;
    font-size: 1rem;
    pointer-events: none;
}
.catalog-search-bar input[type="text"] {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border-radius: var(--radius-input);
    border: 1.5px solid #E5E7EB;
    background: var(--bg-page);
    font-family: var(--font-body);
    font-size: 0.9rem;
    color: var(--text-primary);
    transition: all 0.25s ease;
    outline: none;
}
.catalog-search-bar input[type="text"]::placeholder { color: #9CA3AF; }
.catalog-search-bar input[type="text"]:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(82,183,136,0.15);
    background: #fff;
}
.catalog-search-bar .search-btn {
    padding: 12px 28px;
    border-radius: var(--radius-pill);
    border: none;
    background: var(--primary);
    color: #fff;
    font-family: var(--font-body);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}
.catalog-search-bar .search-btn:hover {
    background: var(--primary-light);
    box-shadow: 0 4px 16px rgba(82,183,136,0.3);
    transform: translateY(-1px);
}

/* â”€â”€â”€ Main Layout: Sidebar + Content â”€â”€â”€ */
.catalog-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 24px;
    animation: fadeInUp 0.5s ease 0.1s both;
}

/* â”€â”€â”€ Left Sidebar â”€â”€â”€ */
.catalog-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.sidebar-section {
    background: var(--bg-card);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: 22px;
}
.sidebar-section-title {
    font-family: var(--font-heading);
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sidebar-section-title i {
    color: var(--primary);
    font-size: 1rem;
}

/* Category Filter */
.cat-filter-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.cat-filter-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: var(--radius-input);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-secondary);
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
}
.cat-filter-item i {
    font-size: 1rem;
    width: 20px;
    text-align: center;
}
.cat-filter-item:hover {
    background: rgba(82,183,136,0.08);
    color: var(--primary);
}
.cat-filter-item.active {
    background: rgba(82,183,136,0.12);
    color: var(--primary);
    font-weight: 600;
}
.cat-filter-item .cat-count {
    margin-left: auto;
    background: var(--bg-page);
    padding: 2px 8px;
    border-radius: var(--radius-pill);
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-secondary);
}
.cat-filter-item.active .cat-count {
    background: rgba(82,183,136,0.15);
    color: var(--primary);
}

/* Price Range Filter */
.price-range-wrap {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.price-inputs {
    display: flex;
    gap: 8px;
    align-items: center;
}
.price-inputs input {
    flex: 1;
    padding: 10px 12px;
    border-radius: var(--radius-input);
    border: 1.5px solid #E5E7EB;
    background: var(--bg-page);
    font-family: var(--font-mono);
    font-size: 0.78rem;
    color: var(--text-primary);
    outline: none;
    transition: border-color 0.2s;
    min-width: 0;
}
.price-inputs input:focus {
    border-color: var(--primary-light);
}
.price-inputs .separator {
    color: var(--text-secondary);
    font-size: 0.8rem;
    font-weight: 600;
}
.price-range-slider {
    -webkit-appearance: none;
    width: 100%;
    height: 4px;
    border-radius: 4px;
    background: #E5E7EB;
    outline: none;
    margin-top: 4px;
}
.price-range-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--primary);
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(45,106,79,0.3);
}
.price-apply-btn {
    width: 100%;
    padding: 10px;
    border-radius: var(--radius-pill);
    border: none;
    background: var(--primary);
    color: #fff;
    font-family: var(--font-body);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}
.price-apply-btn:hover {
    background: var(--primary-light);
}

/* Availability Toggle */
.availability-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 0;
}
.availability-toggle label {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-secondary);
    cursor: pointer;
}
.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #D1D5DB;
    border-radius: 24px;
    transition: 0.3s;
}
.toggle-slider:before {
    content: "";
    position: absolute;
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: 0.3s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.15);
}
.toggle-switch input:checked + .toggle-slider {
    background: var(--primary-light);
}
.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

/* â”€â”€â”€ Right Content Area â”€â”€â”€ */
.catalog-main { min-width: 0; }

/* Banner Promo */
.promo-banner {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, var(--primary-light) 100%);
    border-radius: var(--radius-card);
    padding: 28px 32px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}
.promo-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
}
.promo-banner::after {
    content: '';
    position: absolute;
    bottom: -60%;
    right: 20%;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
}
.promo-text { position: relative; z-index: 2; }
.promo-badge {
    display: inline-flex;
    padding: 4px 14px;
    border-radius: var(--radius-pill);
    background: rgba(212,163,115,0.25);
    color: var(--accent-gold);
    font-size: 0.7rem;
    font-weight: 700;
    margin-bottom: 10px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.promo-title {
    font-family: var(--font-heading);
    font-size: 1.35rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 6px;
}
.promo-desc {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.75);
    margin: 0;
}
.promo-cta {
    position: relative;
    z-index: 2;
    padding: 12px 28px;
    border-radius: var(--radius-pill);
    border: none;
    background: var(--accent-gold);
    color: var(--primary-dark);
    font-family: var(--font-body);
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
}
.promo-cta:hover {
    background: #E8BA8E;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212,163,115,0.4);
}

/* ═══ Promo Carousel ═══ */
.promo-carousel {
    position: relative; margin-bottom: 28px; border-radius: 20px;
    overflow: hidden; box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    min-height: 220px;
}
.promo-track {
    position: relative; width: 100%; min-height: 220px;
}
.promo-slide {
    position: absolute; top: 0; left: 0; width: 100%; min-height: 220px;
    overflow: hidden; display: flex; align-items: stretch;
    opacity: 0; transform: scale(0.88) translateY(12px);
    transition: all 0.7s cubic-bezier(0.22,0.61,0.36,1);
    z-index: 0; pointer-events: none; border-radius: 20px;
}
.promo-slide.active {
    opacity: 1; transform: scale(1) translateY(0);
    z-index: 2; pointer-events: auto;
}
.promo-slide.leaving {
    opacity: 0; transform: scale(0.92) translateY(-8px);
    z-index: 1;
}
/* Background image support */
.promo-slide.has-image {
    background-size: cover !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
}
.promo-slide.has-image .promo-overlay {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.35) 50%, rgba(0,0,0,0.55) 100%);
    z-index: 1;
}
/* Alternate slide colors — dark base with subtle accents */
.promo-slide:nth-child(1) { background: linear-gradient(135deg, #0F2B1E 0%, #1B4332 50%, #1F5C3C 100%); }
.promo-slide:nth-child(2) { background: linear-gradient(135deg, #141428 0%, #1A1F3D 50%, #252B5C 100%); }
.promo-slide:nth-child(3) { background: linear-gradient(135deg, #1E1209 0%, #2D1E10 50%, #3D2A18 100%); }
.promo-slide:nth-child(4) { background: linear-gradient(135deg, #0E1C2E 0%, #162D4A 50%, #1E3A5C 100%); }
.promo-slide:nth-child(5) { background: linear-gradient(135deg, #1A2520 0%, #243530 50%, #2E4540 100%); }
.promo-slide:nth-child(6) { background: linear-gradient(135deg, #1E1422 0%, #2D1F35 50%, #3C2A48 100%); }

/* Decorative circles */
.promo-slide::before {
    content: ''; position: absolute; right: -40px; top: -40px;
    width: 240px; height: 240px; border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.04), transparent 60%);
    z-index: 0;
}
.promo-slide::after {
    content: ''; position: absolute; left: 15%; bottom: -60px;
    width: 180px; height: 180px; border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.03), transparent 60%);
    z-index: 0;
}
/* Subtle glow on right */
.promo-deco {
    position: absolute; right: 60px; top: 50%; transform: translateY(-50%);
    width: 120px; height: 120px; border-radius: 50%;
    background: radial-gradient(circle, rgba(233,196,106,0.06), transparent 70%);
    z-index: 0;
}

.promo-slide-inner {
    display: flex; align-items: center; justify-content: space-between;
    gap: 28px; padding: 36px 44px; width: 100%; position: relative; z-index: 2;
}
.promo-icon-wrap {
    width: 88px; height: 88px; border-radius: 22px; flex-shrink: 0;
    background: rgba(255,255,255,0.1); backdrop-filter: blur(12px);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; border: 1px solid rgba(255,255,255,0.12);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
.promo-content { flex: 1; }
.promo-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: linear-gradient(135deg, #E9C46A, #D4A373); color: #1A1A2E;
    padding: 6px 16px; border-radius: 20px; font-size: 0.72rem;
    font-weight: 800; letter-spacing: 0.5px; margin-bottom: 12px;
    text-transform: uppercase; box-shadow: 0 4px 12px rgba(212,163,115,0.35);
}
.promo-title {
    font-family: 'Outfit', sans-serif; font-size: 1.6rem; font-weight: 800;
    color: #fff; margin: 0 0 10px; line-height: 1.25;
    text-shadow: 0 2px 12px rgba(0,0,0,0.2);
}
.promo-meta {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    font-size: 0.82rem; color: rgba(255,255,255,0.7);
}
.promo-kode-tag {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,0.1); border: 1px dashed rgba(255,255,255,0.25);
    padding: 5px 14px; border-radius: 8px; font-family: 'JetBrains Mono', monospace;
    font-size: 0.82rem; color: #E9C46A; font-weight: 700;
    letter-spacing: 1px; backdrop-filter: blur(4px);
}
.promo-date { display: inline-flex; align-items: center; gap: 4px; }
.promo-urgent {
    display: inline-flex; align-items: center; gap: 4px;
    color: #ff6b6b; font-weight: 700; font-size: 0.78rem;
    animation: urgentPulse 1.5s ease-in-out infinite;
}
@keyframes urgentPulse { 0%,100%{opacity:1} 50%{opacity:0.6} }

.promo-cta {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 16px 32px; border-radius: 16px; border: none; cursor: pointer;
    background: linear-gradient(135deg, #E9C46A, #D4A373); color: #1A1A2E;
    font-weight: 800; font-size: 0.88rem; font-family: 'Inter', sans-serif;
    white-space: nowrap; transition: all 0.3s; flex-shrink: 0;
    box-shadow: 0 6px 20px rgba(212,163,115,0.4);
    text-transform: uppercase; letter-spacing: 0.3px;
}
.promo-cta:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 12px 32px rgba(212,163,115,0.5);
}

.promo-dots {
    position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%);
    display: flex; gap: 8px; z-index: 3;
}
.promo-dots .dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: rgba(255,255,255,0.25); cursor: pointer; transition: all 0.3s;
    border: 1px solid rgba(255,255,255,0.1);
}
.promo-dots .dot.active {
    background: #E9C46A; width: 28px; border-radius: 5px;
    box-shadow: 0 0 10px rgba(233,196,106,0.5);
}
.promo-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    background: rgba(255,255,255,0.1); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.15); color: #fff;
    width: 40px; height: 40px; border-radius: 50%; cursor: pointer;
    z-index: 3; display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; transition: all 0.25s;
}
.promo-nav:hover { background: rgba(255,255,255,0.25); transform: translateY(-50%) scale(1.1); }
.promo-nav.prev { left: 14px; }
.promo-nav.next { right: 14px; }
@media(max-width:768px) {
    .promo-slide { min-height: 200px; }
    .promo-slide-inner { padding: 24px 20px; gap: 16px; flex-wrap: wrap; }
    .promo-icon-wrap { width: 56px; height: 56px; font-size: 1.6rem; border-radius: 16px; }
    .promo-title { font-size: 1.15rem; }
    .promo-cta { padding: 12px 20px; font-size: 0.78rem; width: 100%; justify-content: center; }
}

/* Wishlist Icon in Search Bar */
.wl-icon-btn { position:relative; display:flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:14px; background:linear-gradient(135deg,var(--primary),var(--primary-light)); color:#fff; border:none; cursor:pointer; font-size:1.15rem; transition:all 0.25s; flex-shrink:0; }
.wl-icon-btn:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(45,106,79,0.3); }
.wl-badge { position:absolute; top:-4px; right:-4px; background:#EF4444; color:#fff; font-size:0.62rem; font-weight:800; min-width:18px; height:18px; border-radius:9px; display:flex; align-items:center; justify-content:center; border:2px solid var(--bg-page); padding:0 4px; animation:badgeBounce 0.4s ease; }
@keyframes badgeBounce { 0%{transform:scale(0)} 60%{transform:scale(1.2)} 100%{transform:scale(1)} }

/* â”€â”€â”€ Product Grid Header â”€â”€â”€ */
.grid-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}
.grid-header .result-count {
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-weight: 500;
}
.grid-header .result-count strong {
    color: var(--text-primary);
    font-weight: 700;
}
.sort-select {
    padding: 8px 16px;
    border-radius: var(--radius-input);
    border: 1.5px solid #E5E7EB;
    background: var(--bg-card);
    font-family: var(--font-body);
    font-size: 0.8rem;
    color: var(--text-primary);
    cursor: pointer;
    outline: none;
    transition: border-color 0.2s;
}
.sort-select:focus {
    border-color: var(--primary-light);
}

/* â”€â”€â”€ Product Grid â”€â”€â”€ */
.product-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

/* â”€â”€â”€ Product Card â”€â”€â”€ */
.product-card {
    background: var(--bg-card);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    overflow: hidden;
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    opacity: 0;
    transform: translateY(24px);
    animation: cardIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.1);
}

/* Image */
.pc-img-wrap {
    position: relative;
    height: 190px;
    overflow: hidden;
    background: linear-gradient(135deg, #E8F5E9 0%, #F0F9F4 100%);
}
.pc-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.product-card:hover .pc-img-wrap img {
    transform: scale(1.08);
}
.pc-cat-tag {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 12px;
    border-radius: var(--radius-pill);
    background: rgba(82,183,136,0.18);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: var(--primary-dark);
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}
.pc-stock-tag {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 10px;
    border-radius: var(--radius-pill);
    font-size: 0.65rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}
.pc-stock-tag.available {
    background: rgba(34,197,94,0.12);
    color: #166534;
}
.pc-stock-tag.unavailable {
    background: rgba(239,68,68,0.12);
    color: #991B1B;
}
.pc-stock-tag .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.pc-stock-tag.available .dot { background: #22C55E; }
.pc-stock-tag.unavailable .dot { background: #EF4444; }

/* Wishlist button on image */
.pc-wish-btn {
    position: absolute;
    bottom: 12px;
    right: 12px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(6px);
    color: var(--text-secondary);
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    opacity: 0;
    transform: translateY(6px);
}
.product-card:hover .pc-wish-btn {
    opacity: 1;
    transform: translateY(0);
}
.pc-wish-btn:hover {
    background: #fff;
    color: #EF4444;
    transform: scale(1.1);
}

/* Card Body */
.pc-body {
    padding: 16px 18px 18px;
}
.pc-name {
    font-family: var(--font-heading);
    font-size: 0.92rem;
    font-weight: 600;
    margin: 0 0 10px;
    color: var(--text-primary);
    line-height: 1.35;
}
.pc-price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.pc-price {
    font-family: var(--font-mono);
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--primary);
}
.pc-price small {
    font-size: 0.72rem;
    color: var(--text-secondary);
    font-weight: 500;
    font-family: var(--font-body);
}
.pc-stock-info {
    font-size: 0.72rem;
    color: var(--text-secondary);
    font-weight: 500;
}
.btn-sewa {
    width: 100%;
    padding: 10px 0;
    border-radius: var(--radius-pill);
    border: none;
    background: var(--primary);
    font-family: var(--font-body);
    font-size: 0.8rem;
    font-weight: 600;
    color: #fff;
    cursor: pointer;
    transition: all 0.25s ease;
    text-align: center;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.btn-sewa:hover {
    background: var(--primary-light);
    box-shadow: 0 4px 16px rgba(45,106,79,0.25);
    transform: translateY(-2px);
    color: #fff;
}
.btn-sewa.disabled {
    background: #D1D5DB;
    cursor: not-allowed;
    pointer-events: none;
    color: #9CA3AF;
}

/* â”€â”€â”€ Pagination â”€â”€â”€ */
.pagination-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 0 20px;
}
.page-btn {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-input);
    border: 1.5px solid #E5E7EB;
    background: var(--bg-card);
    color: var(--text-secondary);
    font-family: var(--font-body);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}
.page-btn:hover {
    border-color: var(--primary-light);
    color: var(--primary);
    background: rgba(82,183,136,0.06);
}
.page-btn.active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(45,106,79,0.25);
}
.page-btn.nav-btn {
    width: auto;
    padding: 0 16px;
    gap: 4px;
    font-size: 0.8rem;
}

/* â”€â”€â”€ Empty State â”€â”€â”€ */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    display: none;
}
.empty-state .empty-icon {
    font-size: 3.5rem;
    margin-bottom: 14px;
    display: block;
}
.empty-state h3 {
    font-family: var(--font-heading);
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 6px;
}
.empty-state p {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 0 0 18px;
}
.empty-state button {
    padding: 10px 24px;
    border-radius: var(--radius-pill);
    border: none;
    background: var(--primary);
    color: #fff;
    font-family: var(--font-body);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.empty-state button:hover {
    background: var(--primary-light);
}

/* â”€â”€â”€ Animations â”€â”€â”€ */
@keyframes cardIn {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes slideInRight {
    from { opacity: 0; transform: translateX(20px); }
    to   { opacity: 1; transform: translateX(0); }
}

/* â”€â”€â”€ Mobile Sidebar Overlay â”€â”€â”€ */
.catalog-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(4px);
    z-index: 500;
}
.catalog-sidebar-overlay.show { display: block; }

/* â”€â”€â”€ Responsive â”€â”€â”€ */
@media (max-width: 1200px) {
    .product-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 992px) {
    .topbar-hamburger { display: flex; }
    .catalog-layout {
        grid-template-columns: 1fr;
    }
    .catalog-sidebar {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 300px;
        height: 100vh;
        z-index: 600;
        background: var(--bg-page);
        padding: 24px 16px;
        overflow-y: auto;
        box-shadow: 4px 0 24px rgba(0,0,0,0.15);
        animation: slideInLeft 0.3s ease;
    }
    .catalog-sidebar.show { display: flex; }
    /* Topbar & content responsive handled by pelanggan-system.css */
    .promo-banner { padding: 22px 20px; flex-direction: column; gap: 16px; text-align: center; }
    .popular-card { width: 150px; }
}
@media (max-width: 576px) {
    .product-grid { grid-template-columns: 1fr; }
    .topbar-title { font-size: 1.1rem; }
    .pc-img-wrap { height: 180px; }
    .catalog-search-bar { padding: 14px 16px; }
}
@keyframes slideInLeft {
    from { transform: translateX(-100%); }
    to   { transform: translateX(0); }
}
</style>
</head>
<body>
<div class="pelanggan-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>
    <div class="pelanggan-main">

        <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>

        <!-- Content -->
        <div class="pelanggan-content">

            <!-- Full-Width Search Bar + Wishlist Icon -->
            <div class="catalog-search-bar" style="display:flex; align-items:center; gap:10px;">
                <div class="search-input-wrap" style="flex:1;">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari peralatan camping... tenda, carrier, sleeping bag" autocomplete="off">
                </div>
                <button class="search-btn" onclick="applyFilters()">
                    <i class="bi bi-search"></i> Cari
                </button>
                <a href="<?= BASE_URL ?>/pages/pelanggan/wishlist.php" class="wl-icon-btn" title="Wishlist">
                    <i class="bi bi-heart-fill"></i>
                    <?php if ($wishlistCount > 0): ?>
                    <span class="wl-badge" id="wlBadge"><?= $wishlistCount ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Layout: Sidebar + Main -->
            <div class="catalog-layout">

                <!-- Left Sidebar -->
                <div class="catalog-sidebar" id="catalogSidebar">
                    <!-- Category Filter -->
                    <div class="sidebar-section">
                        <div class="sidebar-section-title">
                            <i class="bi bi-grid-3x3-gap-fill"></i> Kategori
                        </div>
                        <div class="cat-filter-list">
                            <?php
                            $catCounts = [];
                            foreach($products as $p) {
                                $catCounts[$p['cat']] = ($catCounts[$p['cat']] ?? 0) + 1;
                            }
                            foreach($categories as $i => $cat):
                                $count = $cat['label'] === 'Semua' ? count($products) : ($catCounts[$cat['label']] ?? 0);
                            ?>
                            <button class="cat-filter-item <?= $i === 0 ? 'active' : '' ?>"
                                    data-category="<?= $cat['label'] === 'Semua' ? 'all' : htmlspecialchars($cat['label']) ?>"
                                    onclick="filterCategory(this)">
                                <i class="bi <?= $cat['icon'] ?>"></i>
                                <?= $cat['label'] ?>
                                <span class="cat-count"><?= $count ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="sidebar-section">
                        <div class="sidebar-section-title">
                            <i class="bi bi-cash-stack"></i> Rentang Harga
                        </div>
                        <div class="price-range-wrap">
                            <div class="price-inputs">
                                <input type="number" id="priceMin" placeholder="Min" value="0">
                                <span class="separator">&ndash;</span>
                                <input type="number" id="priceMax" placeholder="Max" value="50000">
                            </div>
                            <input type="range" class="price-range-slider" id="priceSlider" min="0" max="50000" step="5000" value="50000" oninput="document.getElementById('priceMax').value=this.value;applyFilters()">
                            <button class="price-apply-btn" onclick="applyFilters()">
                                <i class="bi bi-funnel me-1"></i> Terapkan Filter
                            </button>
                        </div>
                    </div>

                    <!-- Availability Toggle -->
                    <div class="sidebar-section">
                        <div class="sidebar-section-title">
                            <i class="bi bi-check-circle"></i> Ketersediaan
                        </div>
                        <div class="availability-toggle">
                            <label for="availToggle">Hanya tersedia</label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="availToggle" onchange="applyFilters()">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Overlay (mobile) -->
                <div class="catalog-sidebar-overlay" id="sidebarOverlay" onclick="closeCatalogSidebar()"></div>

                <!-- Main Content -->
                <div class="catalog-main">

                    <!-- Promo Carousel (from DB) -->
                    <?php if (!empty($promos)): ?>
                    <div class="promo-carousel">
                        <div class="promo-track" id="promoTrack">
                            <?php foreach ($promos as $promo): 
                                $isPercent = $promo['tipe'] === 'persentase';
                                $discLabel = $isPercent ? $promo['nilai'].'% OFF' : 'Rp '.number_format($promo['nilai'],0,',','.').' OFF';
                                $sisaHari = max(0, (int)((strtotime($promo['selesai']) - time()) / 86400));
                            ?>
                            <?php
                                $hasImage = !empty($promo['gambar']);
                                $bgStyle = '';
                                if ($hasImage) {
                                    $imgPath = ASSETS_URL . '/uploads/promo/' . htmlspecialchars($promo['gambar']);
                                    $bgStyle = 'style="background-image:url(' . $imgPath . ')"';
                                }
                            ?>
                            <div class="promo-slide <?= $hasImage ? 'has-image' : '' ?>" <?= $bgStyle ?>>
                                <?php if ($hasImage): ?><div class="promo-overlay"></div><?php endif; ?>
                                <div class="promo-deco"></div>
                                <div class="promo-slide-inner">
                                    <div class="promo-icon-wrap">
                                        <?= $isPercent ? '🏷️' : '💰' ?>
                                    </div>
                                    <div class="promo-content">
                                        <span class="promo-badge">
                                            <i class="bi bi-lightning-charge-fill"></i> <?= $discLabel ?>
                                        </span>
                                        <h2 class="promo-title"><?= htmlspecialchars($promo['nama']) ?></h2>
                                        <div class="promo-meta">
                                            <span class="promo-kode-tag"><i class="bi bi-ticket-perforated"></i> <?= htmlspecialchars($promo['kode']) ?></span>
                                            <span class="promo-date"><i class="bi bi-calendar3"></i> s/d <?= date('d M Y', strtotime($promo['selesai'])) ?></span>
                                            <?php if ($sisaHari <= 7 && $sisaHari > 0): ?>
                                            <span class="promo-urgent"><i class="bi bi-alarm"></i> <?= $sisaHari ?> hari lagi!</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button class="promo-cta" onclick="copyPromo('<?= htmlspecialchars($promo['kode']) ?>')">
                                        <i class="bi bi-clipboard"></i> Salin Kode
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="promo-nav prev" onclick="movePromo(-1)"><i class="bi bi-chevron-left"></i></button>
                        <button class="promo-nav next" onclick="movePromo(1)"><i class="bi bi-chevron-right"></i></button>
                        <div class="promo-dots" id="promoDots">
                            <?php foreach ($promos as $i => $p): ?>
                            <span class="dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToPromo(<?= $i ?>)"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Product Grid Header -->
                    <div class="grid-header">
                        <span class="result-count" id="resultCount">
                            Menampilkan <strong id="visibleCount"><?= count($products) ?></strong> dari <strong><?= count($products) ?></strong> peralatan
                        </span>
                        <select class="sort-select" id="sortSelect" onchange="applyFilters()">
                            <option value="default">Urutkan: Default</option>
                            <option value="name-asc">Nama: A-Z</option>
                            <option value="name-desc">Nama: Z-A</option>
                            <option value="price-asc">Harga: Rendah ke Tinggi</option>
                            <option value="price-desc">Harga: Tinggi ke Rendah</option>
                            <option value="stock-desc">Stok: Terbanyak</option>
                        </select>
                    </div>

                    <!-- Product Grid -->
                    <div class="product-grid" id="productGrid">
                        <?php foreach($products as $i => $p): ?>
                        <div class="product-card"
                             data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>"
                             data-category="<?= htmlspecialchars($p['cat']) ?>"
                             data-price="<?= $p['price'] ?>"
                             data-stock="<?= $p['stock'] ?>"
                             style="animation-delay: <?= ($i * 0.08) ?>s;">

                            <div class="pc-img-wrap">
                                <img src="<?= $p['img'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                                <span class="pc-cat-tag"><?= $p['cat'] ?></span>
                                <?php if ($p['stock'] > 0): ?>
                                <span class="pc-stock-tag available"><span class="dot"></span> Tersedia</span>
                                <?php else: ?>
                                <span class="pc-stock-tag unavailable"><span class="dot"></span> Habis</span>
                                <?php endif; ?>
                                <button class="pc-wish-btn" title="Tambah ke Wishlist" onclick="event.stopPropagation(); addToCart(<?= $p['id'] ?>, this)"><i class="bi bi-cart-plus"></i></button>
                            </div>

                            <div class="pc-body">
                                <h3 class="pc-name"><?= htmlspecialchars($p['name']) ?></h3>
                                <div class="pc-price-row">
                                    <div class="pc-price">
                                        Rp <?= number_format($p['price'],0,',','.') ?><small>/hari</small>
                                    </div>
                                    <span class="pc-stock-info"><?= $p['stock'] ?> unit</span>
                                </div>
                            <?php if ($p['stock'] > 0): ?>
                                <div style="display:flex; gap:6px;">
                                    <a href="<?= BASE_URL ?>/pages/pelanggan/detail_barang.php?id=<?= $p['id'] ?>" class="btn-sewa" style="flex:1;">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    <button class="btn-sewa btn-cart-katalog" onclick="addToCart(<?= $p['id'] ?>, this)" style="flex:1; background:var(--primary); color:#fff; border:none; cursor:pointer;">
                                        <i class="bi bi-cart-plus"></i> Keranjang
                                    </button>
                                </div>
                                <?php else: ?>
                                <span class="btn-sewa disabled">
                                    <i class="bi bi-x-circle"></i> Stok Habis
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Empty State -->
                        <div class="empty-state" id="emptyState">
                            <span class="empty-icon">ðŸ”</span>
                            <h3>Tidak Ada Hasil</h3>
                            <p>Coba ubah filter, kategori, atau kata kunci pencarian Anda.</p>
                            <button onclick="resetFilters()"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset Semua Filter</button>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination-wrap" id="paginationWrap"></div>

                </div><!-- /.catalog-main -->
            </div><!-- /.catalog-layout -->

        </div><!-- /.pelanggan-content -->
    </div><!-- /.pelanggan-main -->
</div><!-- /.pelanggan-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    'use strict';

    const searchInput = document.getElementById('searchInput');
    const productGrid = document.getElementById('productGrid');
    const productCards = Array.from(document.querySelectorAll('.product-card'));
    const emptyState = document.getElementById('emptyState');
    const visibleCountEl = document.getElementById('visibleCount');
    const sortSelect = document.getElementById('sortSelect');
    const priceMin = document.getElementById('priceMin');
    const priceMax = document.getElementById('priceMax');
    const priceSlider = document.getElementById('priceSlider');
    const availToggle = document.getElementById('availToggle');
    const paginationWrap = document.getElementById('paginationWrap');

    let activeCategory = 'all';
    let currentPage = 1;
    const ITEMS_PER_PAGE = 12;
    let lastVisibleCards = [];

    // ─── Category Filter ───
    window.filterCategory = function(btn) {
        document.querySelectorAll('.cat-filter-item').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        activeCategory = btn.dataset.category;
        currentPage = 1;
        applyFilters();
    };

    // ─── Filter by popular cat ───
    window.filterByCat = function(catLabel) {
        const items = document.querySelectorAll('.cat-filter-item');
        items.forEach(item => {
            item.classList.remove('active');
            if (item.dataset.category === catLabel) {
                item.classList.add('active');
                activeCategory = catLabel;
            }
        });
        currentPage = 1;
        applyFilters();
        document.getElementById('productGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    // ─── Search ───
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { currentPage = 1; applyFilters(); }
    });
    searchInput.addEventListener('input', function() {
        currentPage = 1;
        applyFilters();
    });

    // ─── Sort change ───
    sortSelect.addEventListener('change', function() {
        currentPage = 1;
        applyFilters();
    });

    // ─── Apply All Filters ───
    window.applyFilters = function() {
        const query = searchInput.value.toLowerCase().trim();
        const minPrice = parseInt(priceMin.value) || 0;
        const maxPrice = parseInt(priceMax.value) || 999999;
        const onlyAvailable = availToggle.checked;
        const sort = sortSelect.value;

        // Filter
        let filteredCards = productCards.map(card => {
            const name = card.dataset.name;
            const cat = card.dataset.category;
            const price = parseInt(card.dataset.price);
            const stock = parseInt(card.dataset.stock);
            let show = true;

            if (activeCategory !== 'all' && cat !== activeCategory) show = false;
            if (query && !name.includes(query)) show = false;
            if (price < minPrice || price > maxPrice) show = false;
            if (onlyAvailable && stock <= 0) show = false;

            return { card, show, name, price, stock };
        });

        // Sort visible cards
        let visibleCards = filteredCards.filter(c => c.show);
        switch(sort) {
            case 'name-asc':
                visibleCards.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'name-desc':
                visibleCards.sort((a, b) => b.name.localeCompare(a.name));
                break;
            case 'price-asc':
                visibleCards.sort((a, b) => a.price - b.price);
                break;
            case 'price-desc':
                visibleCards.sort((a, b) => b.price - a.price);
                break;
            case 'stock-desc':
                visibleCards.sort((a, b) => b.stock - a.stock);
                break;
        }

        lastVisibleCards = visibleCards;
        const totalVisible = visibleCards.length;
        const totalPages = Math.max(1, Math.ceil(totalVisible / ITEMS_PER_PAGE));

        // Clamp current page
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * ITEMS_PER_PAGE;
        const endIdx = startIdx + ITEMS_PER_PAGE;
        const pageCards = visibleCards.slice(startIdx, endIdx);
        const hiddenFromFilter = filteredCards.filter(c => !c.show);

        // Show only current page cards
        pageCards.forEach((c, idx) => {
            c.card.style.display = '';
            c.card.style.animationDelay = (idx * 0.06) + 's';
            c.card.style.animation = 'none';
            c.card.offsetHeight;
            c.card.style.animation = '';
            productGrid.appendChild(c.card);
        });

        // Hide cards not on current page
        const notOnPage = visibleCards.filter((c, i) => i < startIdx || i >= endIdx);
        notOnPage.forEach(c => {
            c.card.style.display = 'none';
            productGrid.appendChild(c.card);
        });

        hiddenFromFilter.forEach(c => {
            c.card.style.display = 'none';
            productGrid.appendChild(c.card);
        });

        // Keep empty state at end
        productGrid.appendChild(emptyState);

        visibleCountEl.textContent = totalVisible;
        emptyState.style.display = totalVisible === 0 ? 'block' : 'none';

        // Render pagination
        renderPagination(totalPages, totalVisible);
    };

    // ─── Render Pagination ───
    function renderPagination(totalPages, totalVisible) {
        paginationWrap.innerHTML = '';

        if (totalPages <= 1) return; // No pagination needed

        // Prev button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn nav-btn';
        prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i> Prev';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => { goToPage(currentPage - 1); });
        paginationWrap.appendChild(prevBtn);

        // Page number buttons
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'page-btn' + (i === currentPage ? ' active' : '');
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => { goToPage(i); });
            paginationWrap.appendChild(pageBtn);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn nav-btn';
        nextBtn.innerHTML = 'Next <i class="bi bi-chevron-right"></i>';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => { goToPage(currentPage + 1); });
        paginationWrap.appendChild(nextBtn);
    }

    function goToPage(page) {
        currentPage = page;
        applyFilters();
        document.getElementById('productGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ─── Reset ───
    window.resetFilters = function() {
        searchInput.value = '';
        priceMin.value = 0;
        priceMax.value = 50000;
        priceSlider.value = 50000;
        availToggle.checked = false;
        sortSelect.value = 'default';
        document.querySelectorAll('.cat-filter-item').forEach((p, i) => {
            p.classList.toggle('active', i === 0);
        });
        activeCategory = 'all';
        currentPage = 1;
        applyFilters();
    };

    // ─── Mobile catalog sidebar ───
    window.openCatalogSidebar = function() {
        document.getElementById('catalogSidebar').classList.add('show');
        document.getElementById('sidebarOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    };
    window.closeCatalogSidebar = function() {
        document.getElementById('catalogSidebar').classList.remove('show');
        document.getElementById('sidebarOverlay').classList.remove('show');
        document.body.style.overflow = '';
    };

    // ─── Initialize ───
    applyFilters();

})();

// ─── Add to Wishlist/Cart from Catalog ───
function addToCart(barangId, btn) {
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    btn.disabled = true;

    fetch('<?= BASE_URL ?>/api/wishlist.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ barang_id: barangId, jumlah: 1 })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            btn.style.background = 'var(--primary)';
            btn.style.color = '#fff';
            const count = data.data?.count || 0;
            showCartToast('Berhasil ditambahkan ke keranjang!', count);
            // Update wishlist badge
            let badge = document.getElementById('wlBadge');
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.id = 'wlBadge';
                    badge.className = 'wl-badge';
                    document.querySelector('.wl-icon-btn').appendChild(badge);
                }
                badge.textContent = count;
            }
            setTimeout(() => {
                btn.innerHTML = orig;
                btn.disabled = false;
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
        } else {
            btn.innerHTML = orig;
            btn.disabled = false;
            alert(data.message || 'Gagal menambahkan');
        }
    })
    .catch(() => {
        btn.innerHTML = orig;
        btn.disabled = false;
        alert('Terjadi kesalahan jaringan');
    });
}

function showCartToast(message, count) {
    let toast = document.getElementById('cartToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'cartToast';
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:linear-gradient(135deg,#2D6A4F,#52B788);color:#fff;padding:14px 22px;border-radius:14px;font-size:0.88rem;font-weight:600;z-index:9999;display:flex;align-items:center;gap:10px;box-shadow:0 8px 32px rgba(45,106,79,0.35);transform:translateY(100px);opacity:0;transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);font-family:Inter,sans-serif;';
        document.body.appendChild(toast);
    }
    toast.innerHTML = '<i class="bi bi-cart-check" style="font-size:1.2rem;"></i> ' + message +
        (count > 0 ? ' <a href="<?= BASE_URL ?>/pages/pelanggan/wishlist.php" style="color:#D4A373;text-decoration:underline;margin-left:6px;">Lihat (' + count + ')</a>' : '');
    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    });
    setTimeout(() => {
        toast.style.transform = 'translateY(100px)';
        toast.style.opacity = '0';
    }, 3500);
}

// ─── Promo Carousel (Card Stack) ───
let promoIndex = 0;
const promoTrack = document.getElementById('promoTrack');
const promoSlides = promoTrack ? promoTrack.querySelectorAll('.promo-slide') : [];
const promoDots = document.querySelectorAll('#promoDots .dot');
const promoCount = promoSlides.length;

// Init: first slide active
if (promoSlides.length > 0) promoSlides[0].classList.add('active');

function goToPromo(i) {
    if (!promoTrack || promoCount <= 1) return;
    const newIndex = ((i % promoCount) + promoCount) % promoCount;
    if (newIndex === promoIndex) return;

    const oldSlide = promoSlides[promoIndex];
    const newSlide = promoSlides[newIndex];

    // Old slide: leaving animation
    oldSlide.classList.remove('active');
    oldSlide.classList.add('leaving');

    // New slide: enter animation
    newSlide.classList.add('active');

    // Cleanup leaving class after transition
    setTimeout(() => { oldSlide.classList.remove('leaving'); }, 700);

    promoIndex = newIndex;
    promoDots.forEach((d, idx) => d.classList.toggle('active', idx === promoIndex));
}
function movePromo(dir) { goToPromo(promoIndex + dir); }
function copyPromo(code) {
    navigator.clipboard.writeText(code).then(() => {
        showCartToast('Kode promo "' + code + '" disalin!', 0);
    });
}

// Auto slide every 5 seconds
if (promoCount > 1) {
    setInterval(() => movePromo(1), 5000);
}

// ─── Update badge after addToCart ───
const origAddToCart = addToCart;
addToCart = function(barangId, btn) {
    const origCb = origAddToCart;
    origCb(barangId, btn);
};
</script>
</body>
</html><?php
