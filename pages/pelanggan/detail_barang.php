<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/Kategori.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$barang_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$barang = $barang_id > 0 ? Barang::getById($barang_id) : null;

if (!$barang) {
    header('Location: ' . BASE_URL . '/pages/pelanggan/katalog.php');
    exit;
}

$page_title = 'SIMPEL-CAMP'; 
$current_page = 'katalog';
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// Set product variables for use in HTML
$product_name = $barang['nama'];
$product_price = (int)$barang['harga_per_hari'];
$product_stock = (int)$barang['stok_tersedia'];
$product_category = $barang['kategori_nama'] ?? 'Lainnya';
$product_desc = $barang['deskripsi'] ?? 'Peralatan camping berkualitas tinggi untuk petualangan outdoor Anda.';
$product_img = !empty($barang['gambar']) ? ASSETS_URL . '/img/barang/' . $barang['gambar'] : 'https://images.unsplash.com/photo-1504280390467-336c1e55b4bc?auto=format&fit=crop&w=600&q=80';
$product_status = $barang['status'];

// Get related products (same category)
$relatedProducts = Barang::getAll(['kategori_id' => $barang['kategori_id'], 'limit' => 4]);
$relatedProducts = array_filter($relatedProducts, function($p) use ($barang_id) { return $p['id'] != $barang_id; });
$relatedProducts = array_slice($relatedProducts, 0, 3);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $page_title ?> - <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1781550666">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">
<style>
:root {
    --bg-page: #F2F7F4;
    --bg-card: #FFFFFF;
    --card-radius: 20px;
    --card-shadow: 0 2px 20px rgba(0,0,0,0.04);
    --primary: #2D6A4F;
    --primary-light: #52B788;
    --primary-dark: #1B4332;
    --accent-gold: #D4A373;
    --text-primary: #1A1A2E;
    --text-secondary: #6B7280;
    --btn-radius: 50px;
    --input-radius: 12px;
    --font-body: 'Inter', sans-serif;
    --font-heading: 'Outfit', sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
}

/* Animations */
@keyframes fadeInUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes slideInRight { from { opacity:0; transform:translateX(30px); } to { opacity:1; transform:translateX(0); } }
@keyframes heartBeat { 0%{transform:scale(1)} 15%{transform:scale(1.3)} 30%{transform:scale(1)} 45%{transform:scale(1.2)} 60%{transform:scale(1)} }
@keyframes toastIn { from{opacity:0;transform:translateX(100px)} to{opacity:1;transform:translateX(0)} }
@keyframes toastOut { from{opacity:1;transform:translateX(0)} to{opacity:0;transform:translateX(100px)} }
@keyframes shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }

.detail-page { max-width:1200px; margin:0 auto; }
.anim-in { animation:fadeInUp 0.6s ease forwards; opacity:0; }
.anim-delay-1 { animation-delay:0.1s; }
.anim-delay-2 { animation-delay:0.2s; }
.anim-delay-3 { animation-delay:0.3s; }
.anim-delay-4 { animation-delay:0.4s; }

/* SaaS Card */
.saas-card {
    background: var(--bg-card);
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    border: none;
    padding: 1.5rem;
}

/* Breadcrumb Pills */
.breadcrumb-pills {
    display: flex; align-items: center; gap: 0.5rem;
    margin-bottom: 1.5rem; flex-wrap: wrap;
}
.breadcrumb-pills a, .breadcrumb-pills span {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.35rem 0.85rem; border-radius: var(--btn-radius);
    font-size: 0.82rem; font-weight: 500; text-decoration: none;
    transition: all 0.25s;
}
.breadcrumb-pills a {
    background: rgba(82,183,136,0.1); color: var(--primary);
}
.breadcrumb-pills a:hover {
    background: rgba(82,183,136,0.2); color: var(--primary-dark);
}
.breadcrumb-pills .sep { color: var(--text-secondary); font-size: 0.75rem; }
.breadcrumb-pills .current {
    background: rgba(45,106,79,0.12); color: var(--primary-dark); font-weight: 600;
}

/* Image Gallery */
.gallery-card { padding: 0; overflow: hidden; }
.main-image-wrap {
    position: relative; overflow: hidden;
    border-radius: var(--card-radius) var(--card-radius) 0 0;
    cursor: zoom-in; height: 400px;
}
.main-image-wrap img {
    width:100%; height:100%; object-fit:cover;
    transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
}
.main-image-wrap:hover img { transform: scale(1.08); }
.popular-badge {
    position:absolute; top:16px; left:16px;
    background: linear-gradient(135deg,#E9C46A,#D4A373);
    color:#fff; font-weight:700; font-size:0.78rem;
    padding:0.4rem 0.9rem; border-radius: var(--btn-radius);
    display:flex; align-items:center; gap:0.35rem;
    box-shadow:0 4px 12px rgba(212,163,115,0.4); z-index:2;
}
.zoom-hint {
    position:absolute; bottom:16px; right:16px;
    background:rgba(0,0,0,0.5); backdrop-filter:blur(8px);
    color:#fff; font-size:0.75rem; padding:0.35rem 0.75rem;
    border-radius:8px; display:flex; align-items:center; gap:0.35rem;
    opacity:0; transition:opacity 0.3s;
}
.main-image-wrap:hover .zoom-hint { opacity:1; }
.thumbnails { display:flex; gap:0.65rem; padding:1rem; }
.thumb-item {
    width:80px; height:65px; border-radius:12px;
    overflow:hidden; cursor:pointer;
    border:2.5px solid transparent;
    transition:all 0.25s ease; flex-shrink:0;
}
.thumb-item img { width:100%; height:100%; object-fit:cover; }
.thumb-item:hover { border-color:var(--primary-light); transform:translateY(-2px); }
.thumb-item.active { border-color:var(--primary); box-shadow:0 3px 10px rgba(45,106,79,0.2); }

/* Product Info */
.product-info { animation:slideInRight 0.6s ease forwards; }
.cat-pill {
    display:inline-flex; align-items:center; gap:0.3rem;
    background:rgba(82,183,136,0.1); color:var(--primary);
    font-size:0.78rem; font-weight:600;
    padding:0.3rem 0.85rem; border-radius: var(--btn-radius);
    margin-bottom:0.75rem;
}
.product-name {
    font-family:var(--font-heading); font-weight:800;
    font-size:1.5rem; color:var(--text-primary);
    line-height:1.2; margin-bottom:0.65rem;
}
.rating-row { display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem; }
.stars { display:flex; gap:2px; }
.stars i { color:#E9C46A; font-size:0.95rem; }
.rating-text { color:var(--text-primary); font-weight:600; font-size:0.9rem; font-family:var(--font-mono); }
.review-count { color:var(--text-secondary); font-size:0.85rem; }
.price-tag {
    font-family:var(--font-mono); font-size:1.75rem;
    font-weight:600; color:var(--primary-light); margin-bottom:0.25rem;
}
.price-tag span {
    font-family:var(--font-body); color:var(--text-secondary);
    font-size:0.9rem; font-weight:400;
}
.avail-pill {
    display:inline-flex; align-items:center; gap:0.4rem;
    background:rgba(82,183,136,0.1); color:var(--primary);
    font-weight:600; font-size:0.82rem;
    padding:0.4rem 0.9rem; border-radius: var(--btn-radius);
    margin-bottom:1.25rem;
}
.avail-pill i { color:var(--primary-light); }

/* Specs 2x2 Grid */
.specs-grid {
    display:grid; grid-template-columns:1fr 1fr;
    gap:0.65rem; margin-bottom:1.25rem;
}
.spec-item {
    background:#F8FAF9; border-radius:12px;
    padding:0.75rem 0.85rem;
}
.spec-item .spec-label {
    font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.15rem;
}
.spec-item .spec-value {
    font-size:0.88rem; color:var(--text-primary);
    font-weight:600; font-family:var(--font-mono);
}

/* Date Inputs */
.date-group { margin-bottom:0; }
.date-group label {
    font-weight:600; color:var(--text-primary);
    font-size:0.85rem; margin-bottom:0.35rem; display:block;
}
.date-input {
    width:100%; border:1.5px solid rgba(107,114,128,0.2);
    border-radius: var(--input-radius); padding:0.6rem 0.85rem;
    font-size:0.88rem; transition:all 0.25s;
    background:#FAFCFB; font-family:var(--font-body);
}
.date-input:focus {
    border-color:var(--primary-light);
    box-shadow:0 0 0 3px rgba(82,183,136,0.12);
    outline:none; background:#fff;
}

/* Quantity Selector */
.qty-group { display:flex; align-items:center; gap:0; margin-bottom:1rem; }
.qty-group label {
    font-weight:600; color:var(--text-primary);
    font-size:0.88rem; margin-right:1rem; min-width:60px;
}
.qty-btn {
    width:38px; height:38px;
    border:1.5px solid rgba(107,114,128,0.2);
    background:#fff; color:var(--text-primary);
    font-size:1.1rem; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:all 0.2s;
}
.qty-btn:first-of-type { border-radius:var(--input-radius) 0 0 var(--input-radius); }
.qty-btn:last-of-type { border-radius:0 var(--input-radius) var(--input-radius) 0; }
.qty-btn:hover { background:var(--primary-light); color:#fff; border-color:var(--primary-light); }
.qty-input {
    width:50px; height:38px;
    border:1.5px solid rgba(107,114,128,0.2);
    border-left:none; border-right:none;
    text-align:center; font-weight:700;
    font-family:var(--font-mono); font-size:0.95rem;
    color:var(--text-primary); background:#FAFCFB;
}
.qty-input:focus { outline:none; background:#fff; }

/* Calc Box */
.calc-box {
    background:linear-gradient(135deg,rgba(82,183,136,0.06),rgba(45,106,79,0.03));
    border-radius:var(--input-radius); padding:0.85rem 1rem;
    margin-bottom:1.25rem;
    display:flex; justify-content:space-between; align-items:center;
}
.calc-box .calc-label { color:var(--text-secondary); font-size:0.88rem; }
.calc-box .calc-label strong { color:var(--text-primary); }
.calc-box .calc-value {
    font-family:var(--font-mono); font-weight:600;
    color:var(--primary); font-size:1.1rem;
}

/* CTA Buttons */
.btn-sewa {
    display:flex; align-items:center; justify-content:center;
    gap:0.6rem; width:100%;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    color:#fff; border:none; border-radius: var(--btn-radius);
    padding:0.9rem 2rem; font-weight:700; font-size:1rem;
    transition:all 0.3s ease; cursor:pointer;
    text-decoration:none; margin-bottom:0.75rem;
}
.btn-sewa:hover {
    background:linear-gradient(135deg,var(--primary-dark),#0F2B1E);
    transform:translateY(-2px);
    box-shadow:0 8px 24px rgba(27,67,50,0.3); color:#fff;
}
.btn-sewa i { transition:transform 0.3s; }
.btn-sewa:hover i { transform:translateX(4px); }
.btn-wishlist {
    display:flex; align-items:center; justify-content:center;
    gap:0.5rem; width:100%;
    background:transparent; color:var(--primary);
    border:1.5px solid rgba(45,106,79,0.2);
    border-radius: var(--btn-radius); padding:0.75rem;
    font-weight:600; font-size:0.9rem;
    cursor:pointer; transition:all 0.3s; margin-bottom:0.75rem;
}
.btn-wishlist:hover { border-color:var(--primary-light); background:rgba(82,183,136,0.04); transform:translateY(-1px); }
.btn-wishlist.active { background:rgba(220,53,69,0.06); border-color:#dc3545; color:#dc3545; }
.btn-wishlist.active i { animation:heartBeat 0.6s; }
.share-row { display:flex; gap:0.5rem; }
.share-btn {
    flex:1; display:flex; align-items:center; justify-content:center;
    gap:0.4rem; padding:0.55rem;
    border:1.5px solid rgba(107,114,128,0.12);
    border-radius: var(--btn-radius); font-size:0.82rem;
    font-weight:600; cursor:pointer; transition:all 0.25s;
    background:#fff; text-decoration:none;
}
.share-btn.wa { color:#25D366; }
.share-btn.wa:hover { background:#25D366; color:#fff; border-color:#25D366; }
.share-btn.copy { color:var(--text-secondary); }
.share-btn.copy:hover { background:#f5f5f5; }

/* Tabs Section */
.tabs-card { padding:0; margin-top:2rem; overflow:hidden; }
.tabs-card .nav-pills {
    padding:1rem 1.5rem; gap:0.5rem;
    background:rgba(248,250,249,0.6);
    border-bottom:1px solid rgba(107,114,128,0.06);
}
.tabs-card .nav-pills .nav-link {
    border:none; color:var(--text-secondary);
    font-weight:600; font-size:0.88rem;
    padding:0.6rem 1.15rem; border-radius: var(--btn-radius);
    transition:all 0.3s; background:transparent;
}
.tabs-card .nav-pills .nav-link:hover { color:var(--primary); background:rgba(82,183,136,0.06); }
.tabs-card .nav-pills .nav-link.active {
    color:#fff; background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    box-shadow:0 4px 12px rgba(45,106,79,0.25);
}
.tabs-card .tab-content { padding:1.5rem; }

/* Reviews */
.review-item {
    display:flex; gap:1rem; padding:1.15rem 0;
    border-bottom:1px solid rgba(107,114,128,0.06);
}
.review-item:last-child { border-bottom:none; }
.review-avatar {
    width:44px; height:44px; border-radius:50%;
    background:linear-gradient(135deg,var(--primary-light),var(--primary));
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:1rem; flex-shrink:0;
}
.review-stars { display:flex; gap:2px; margin-bottom:0.3rem; }
.review-stars i { color:#E9C46A; font-size:0.8rem; }
.review-name { font-weight:600; color:var(--text-primary); font-size:0.9rem; }
.review-date { color:var(--text-secondary); font-size:0.78rem; }
.review-text { color:var(--text-secondary); font-size:0.88rem; line-height:1.6; margin-top:0.3rem; }

/* Spec Table */
.spec-table { width:100%; }
.spec-table tr:nth-child(even) td { background:rgba(82,183,136,0.03); }
.spec-table td {
    padding:0.65rem 1rem; font-size:0.88rem;
    border-bottom:1px solid rgba(107,114,128,0.06);
}
.spec-table td:first-child { color:var(--text-secondary); width:35%; font-weight:500; }
.spec-table td:last-child { color:var(--text-primary); font-weight:600; }

/* Policy List */
.policy-item {
    display:flex; gap:0.85rem; align-items:flex-start;
    padding:0.85rem 0;
    border-bottom:1px solid rgba(107,114,128,0.06);
}
.policy-item:last-child { border-bottom:none; }
.policy-icon {
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:1rem; flex-shrink:0;
}
.policy-item h6 { font-weight:600; color:var(--text-primary); font-size:0.88rem; margin:0; }
.policy-item p { color:var(--text-secondary); font-size:0.82rem; margin:0.15rem 0 0; }

/* Related Products */
.related-section { margin-top:2rem; }
.related-section h4 {
    font-family:var(--font-heading); font-weight:700;
    color:var(--text-primary); margin-bottom:1.25rem; font-size:1.3rem;
}
.related-scroll {
    display:flex; gap:1rem; overflow-x:auto; padding-bottom:1rem;
    scrollbar-width:thin; scrollbar-color:var(--primary-light) transparent;
}
.related-scroll::-webkit-scrollbar { height:6px; }
.related-scroll::-webkit-scrollbar-thumb { background:var(--primary-light); border-radius:3px; }
.related-card {
    min-width:230px; max-width:230px;
    background:var(--bg-card); border-radius:16px;
    box-shadow:var(--card-shadow); overflow:hidden; flex-shrink:0;
    transition:all 0.3s ease; border:none;
}
.related-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(27,67,50,0.1); }
.related-card img { width:100%; height:140px; object-fit:cover; transition:transform 0.4s; }
.related-card:hover img { transform:scale(1.05); }
.related-card .rc-body { padding:0.85rem; }
.related-card .rc-name { font-weight:600; color:var(--text-primary); font-size:0.88rem; margin-bottom:0.3rem; }
.related-card .rc-price { font-family:var(--font-mono); color:var(--primary-light); font-weight:600; font-size:0.85rem; margin-bottom:0.35rem; }
.related-card .rc-rating { display:flex; align-items:center; gap:0.3rem; font-size:0.75rem; color:#E9C46A; margin-bottom:0.6rem; }
.related-card .rc-rating span { color:var(--text-secondary); }
.related-card .rc-btn {
    display:block; text-align:center;
    background:rgba(82,183,136,0.08); color:var(--primary);
    font-weight:600; font-size:0.82rem;
    padding:0.45rem; border-radius: var(--btn-radius);
    text-decoration:none; transition:all 0.25s;
}
.related-card .rc-btn:hover { background:var(--primary); color:#fff; }

/* Mobile Bar */
.mobile-bar {
    display:none; position:fixed; bottom:0; left:0; right:0; z-index:1000;
    background:rgba(255,255,255,0.9); backdrop-filter:blur(16px);
    -webkit-backdrop-filter:blur(16px);
    border-top:1px solid rgba(107,114,128,0.08);
    padding:0.75rem 1.25rem; box-shadow:0 -4px 20px rgba(0,0,0,0.06);
}
.mobile-bar .mb-price { font-family:var(--font-mono); font-weight:600; color:var(--primary-light); font-size:1.15rem; }
.mobile-bar .mb-price span { font-family:var(--font-body); color:var(--text-secondary); font-size:0.78rem; font-weight:400; }
.mobile-bar .mb-btn {
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    color:#fff; border:none; border-radius: var(--btn-radius);
    padding:0.6rem 1.5rem; font-weight:700; font-size:0.88rem;
    cursor:pointer; text-decoration:none;
}
@media (max-width:991px) {
    .mobile-bar { display:flex; align-items:center; justify-content:space-between; }
    .detail-page { padding-bottom:80px; }
}

/* Toast */
.toast-container {
    position:fixed; top:20px; right:20px; z-index:10000;
    display:flex; flex-direction:column; gap:0.5rem;
}
.custom-toast {
    background:rgba(255,255,255,0.95); backdrop-filter:blur(12px);
    border-radius:var(--input-radius); padding:0.85rem 1.25rem;
    box-shadow:0 8px 30px rgba(0,0,0,0.12);
    display:flex; align-items:center; gap:0.65rem;
    min-width:280px; animation:toastIn 0.4s ease;
    font-size:0.88rem; font-weight:500;
}
.custom-toast.hiding { animation:toastOut 0.3s ease forwards; }
.custom-toast.success .toast-icon { background:rgba(82,183,136,0.15); color:var(--primary-light); }
.custom-toast.info .toast-icon { background:rgba(45,106,79,0.12); color:var(--primary); }
.custom-toast.warning .toast-icon { background:rgba(212,163,115,0.15); color:var(--accent-gold); }
.toast-icon {
    width:32px; height:32px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    font-size:0.95rem; flex-shrink:0;
}

/* Lightbox */
.lightbox-overlay {
    display:none; position:fixed; top:0; left:0; right:0; bottom:0;
    background:rgba(0,0,0,0.88); backdrop-filter:blur(8px);
    z-index:10001; align-items:center; justify-content:center;
    animation:fadeIn 0.3s ease;
}
.lightbox-overlay.active { display:flex; }
.lightbox-close {
    position:absolute; top:20px; right:24px;
    color:#fff; font-size:1.5rem; cursor:pointer;
    background:rgba(255,255,255,0.1); border:none; border-radius:50%;
    width:44px; height:44px; display:flex; align-items:center; justify-content:center;
    transition:background 0.2s;
}
.lightbox-close:hover { background:rgba(255,255,255,0.2); }
.lightbox-img { max-width:85vw; max-height:80vh; border-radius:12px; object-fit:contain; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
.lightbox-nav {
    position:absolute; top:50%; transform:translateY(-50%);
    background:rgba(255,255,255,0.12); border:none; color:#fff;
    font-size:1.5rem; width:48px; height:48px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:background 0.2s;
}
.lightbox-nav:hover { background:rgba(255,255,255,0.25); }
.lightbox-prev { left:20px; }
.lightbox-next { right:20px; }
.lightbox-counter { position:absolute; bottom:24px; color:rgba(255,255,255,0.7); font-size:0.9rem; font-weight:500; }

/* Scroll Animation */
.scroll-anim { opacity:0; transform:translateY(20px); transition:all 0.5s ease; }
.scroll-anim.visible { opacity:1; transform:translateY(0); }
</style>
</head>
<body>
<div class="pelanggan-wrapper">
<?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>
<div class="pelanggan-main">
    <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>
    <div class="pelanggan-content">

<div class="detail-page">

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Breadcrumb Pills -->
    <div class="breadcrumb-pills anim-in">
        <a href="<?= BASE_URL ?>/pages/pelanggan/katalog.php"><i class="bi bi-grid-3x3-gap-fill"></i> Katalog</a>
        <span class="sep"><i class="bi bi-chevron-right"></i></span>
        <a href="#"><?= htmlspecialchars($product_category) ?></a>
        <span class="sep"><i class="bi bi-chevron-right"></i></span>
        <span class="current"><?= htmlspecialchars($product_name) ?></span>
    </div>

    <!-- Main Product Row -->
    <div class="row g-4">
        <!-- LEFT: Image Gallery -->
        <div class="col-lg-6 anim-in anim-delay-1">
            <div class="saas-card gallery-card">
                <div class="main-image-wrap" id="mainImageWrap" onclick="openLightbox()">
                    <span class="popular-badge"><i class="bi bi-fire"></i> Populer</span>
                    <img src="<?= htmlspecialchars($product_img) ?>" alt="<?= htmlspecialchars($product_name) ?>" id="mainImage">
                    <span class="zoom-hint"><i class="bi bi-zoom-in"></i> Klik untuk zoom</span>
                </div>
                <div class="thumbnails">
                    <div class="thumb-item active" onclick="setMainImage(0, this)">
                        <img src="<?= htmlspecialchars($product_img) ?>" alt="<?= htmlspecialchars($product_name) ?>">
                    </div>
                    <div class="thumb-item" onclick="setMainImage(1, this)">
                        <img src="https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=200&q=80" alt="Camping 2">
                    </div>
                    <div class="thumb-item" onclick="setMainImage(2, this)">
                        <img src="https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=200&q=80" alt="Camping 3">
                    </div>
                    <div class="thumb-item" onclick="setMainImage(3, this)">
                        <img src="https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=200&q=80" alt="Camping 4">
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Product Info -->
        <div class="col-lg-6 anim-in anim-delay-2">
            <div class="saas-card product-info">
                <span class="cat-pill"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($product_category) ?></span>
                <h1 class="product-name"><?= htmlspecialchars($product_name) ?></h1>
                <div class="rating-row">
                    <div class="stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                    </div>
                    <span class="rating-text">4.8</span>
                    <span class="review-count">(ulasan)</span>
                </div>
                <div class="price-tag">Rp <?= number_format($product_price, 0, ',', '.') ?> <span>/hari</span></div>
                <div class="avail-pill"><i class="bi bi-check-circle-fill"></i> <?= $product_stock > 0 ? 'Tersedia - ' . $product_stock . ' unit' : 'Stok Habis' ?></div>

                <!-- Specs 2x2 Grid -->
                <div class="specs-grid">
                    <div class="spec-item">
                        <div class="spec-label">Kategori</div>
                        <div class="spec-value"><?= htmlspecialchars($product_category) ?></div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">Harga/Hari</div>
                        <div class="spec-value">Rp <?= number_format($product_price, 0, ',', '.') ?></div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">Stok</div>
                        <div class="spec-value"><?= $product_stock ?> Unit</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">Status</div>
                        <div class="spec-value"><?= ucfirst(htmlspecialchars($product_status)) ?></div>
                    </div>
                </div></div>

                <!-- ══════ Deskripsi Produk ══════ -->
                <div style="margin-bottom:1.25rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.65rem;">
                        <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,rgba(82,183,136,0.15),rgba(45,106,79,0.08));display:flex;align-items:center;justify-content:center;color:var(--primary-light);font-size:0.9rem;">
                            <i class="bi bi-file-text-fill"></i>
                        </div>
                        <h6 style="font-family:var(--font-heading);font-weight:700;color:var(--text-primary);font-size:0.95rem;margin:0;">Deskripsi Produk</h6>
                    </div>
                    <div style="background:linear-gradient(135deg,#F8FAF9,#F2F7F4);border-radius:12px;padding:1rem 1.15rem;border-left:3px solid var(--primary-light);">
                        <p style="color:var(--text-secondary);font-size:0.86rem;line-height:1.75;margin:0;"><?= nl2br(htmlspecialchars($product_desc)) ?></p>
                    </div>
                </div>

                <!-- ══════ Informasi Penyewaan ══════ -->
                <div style="margin-bottom:1.25rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.65rem;">
                        <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,rgba(212,163,115,0.18),rgba(212,163,115,0.06));display:flex;align-items:center;justify-content:center;color:var(--accent-gold);font-size:0.9rem;">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <h6 style="font-family:var(--font-heading);font-weight:700;color:var(--text-primary);font-size:0.95rem;margin:0;">Informasi Penyewaan</h6>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.55rem;">
                        <!-- Deposit -->
                        <div style="background:#F8FAF9;border-radius:12px;padding:0.75rem 0.7rem;text-align:center;border:1px solid rgba(82,183,136,0.1);transition:all 0.25s;" onmouseover="this.style.borderColor='var(--primary-light)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='rgba(82,183,136,0.1)';this.style.transform='translateY(0)'">
                            <div style="width:32px;height:32px;border-radius:50%;background:rgba(82,183,136,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 0.4rem;color:var(--primary-light);font-size:0.85rem;">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div style="font-size:0.7rem;color:var(--text-secondary);margin-bottom:0.15rem;">Deposit</div>
                            <div style="font-family:var(--font-mono);font-weight:600;color:var(--text-primary);font-size:0.8rem;">Rp 100.000</div>
                        </div>
                        <!-- Denda -->
                        <div style="background:#F8FAF9;border-radius:12px;padding:0.75rem 0.7rem;text-align:center;border:1px solid rgba(220,53,69,0.08);transition:all 0.25s;" onmouseover="this.style.borderColor='#dc3545';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='rgba(220,53,69,0.08)';this.style.transform='translateY(0)'">
                            <div style="width:32px;height:32px;border-radius:50%;background:rgba(220,53,69,0.08);display:flex;align-items:center;justify-content:center;margin:0 auto 0.4rem;color:#dc3545;font-size:0.85rem;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div style="font-size:0.7rem;color:var(--text-secondary);margin-bottom:0.15rem;">Denda Telat</div>
                            <div style="font-family:var(--font-mono);font-weight:600;color:var(--text-primary);font-size:0.8rem;">Rp 25.000<small style="font-family:var(--font-body);color:var(--text-secondary);font-weight:400;">/hari</small></div>
                        </div>
                        <!-- Maks. Sewa -->
                        <div style="background:#F8FAF9;border-radius:12px;padding:0.75rem 0.7rem;text-align:center;border:1px solid rgba(212,163,115,0.1);transition:all 0.25s;" onmouseover="this.style.borderColor='var(--accent-gold)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='rgba(212,163,115,0.1)';this.style.transform='translateY(0)'">
                            <div style="width:32px;height:32px;border-radius:50%;background:rgba(212,163,115,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 0.4rem;color:var(--accent-gold);font-size:0.85rem;">
                                <i class="bi bi-calendar-range"></i>
                            </div>
                            <div style="font-size:0.7rem;color:var(--text-secondary);margin-bottom:0.15rem;">Maks. Sewa</div>
                            <div style="font-family:var(--font-mono);font-weight:600;color:var(--text-primary);font-size:0.8rem;">14 Hari</div>
                        </div>
                    </div>
                </div>

                <!-- ══════ Ketersediaan (Stock Progress) ══════ -->
                <?php
                    $stok_total = max((int)($barang['stok_total'] ?? 1), 1);
                    $stok_tersedia = (int)($barang['stok_tersedia'] ?? 0);
                    $stok_persen = round(($stok_tersedia / $stok_total) * 100);
                    // Color logic: green (>50%), gold (20-50%), red (<20%)
                    if ($stok_persen > 50) {
                        $bar_color = 'var(--primary-light)';
                        $bar_bg = 'rgba(82,183,136,0.12)';
                        $stok_label = 'Stok Tersedia';
                        $stok_icon = 'bi-check-circle-fill';
                        $label_color = 'var(--primary)';
                    } elseif ($stok_persen > 20) {
                        $bar_color = 'var(--accent-gold)';
                        $bar_bg = 'rgba(212,163,115,0.12)';
                        $stok_label = 'Stok Terbatas';
                        $stok_icon = 'bi-exclamation-circle-fill';
                        $label_color = 'var(--accent-gold)';
                    } else {
                        $bar_color = '#dc3545';
                        $bar_bg = 'rgba(220,53,69,0.08)';
                        $stok_label = $stok_tersedia > 0 ? 'Hampir Habis' : 'Stok Habis';
                        $stok_icon = 'bi-x-circle-fill';
                        $label_color = '#dc3545';
                    }
                ?>
                <div style="margin-bottom:1.25rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.65rem;">
                        <div style="width:30px;height:30px;border-radius:8px;background:<?= $bar_bg ?>;display:flex;align-items:center;justify-content:center;color:<?= $label_color ?>;font-size:0.9rem;">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <h6 style="font-family:var(--font-heading);font-weight:700;color:var(--text-primary);font-size:0.95rem;margin:0;">Ketersediaan</h6>
                    </div>
                    <div style="background:#F8FAF9;border-radius:12px;padding:1rem 1.15rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                            <span style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.82rem;font-weight:600;color:<?= $label_color ?>;">
                                <i class="bi <?= $stok_icon ?>"></i> <?= $stok_label ?>
                            </span>
                            <span style="font-family:var(--font-mono);font-size:0.82rem;font-weight:600;color:var(--text-primary);"><?= $stok_tersedia ?> / <?= $stok_total ?> unit</span>
                        </div>
                        <!-- Progress Bar -->
                        <div style="width:100%;height:10px;background:rgba(107,114,128,0.08);border-radius:50px;overflow:hidden;position:relative;">
                            <div style="height:100%;width:<?= $stok_persen ?>%;background:linear-gradient(90deg,<?= $bar_color ?>,<?= $bar_color ?>cc);border-radius:50px;transition:width 1s cubic-bezier(0.25,0.46,0.45,0.94);position:relative;overflow:hidden;">
                                <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);animation:shimmer 2s infinite;"></div>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:0.4rem;">
                            <span style="font-size:0.72rem;color:var(--text-secondary);"><?= $stok_persen ?>% tersedia</span>
                            <span style="font-size:0.72rem;color:var(--text-secondary);"><?= $stok_total - $stok_tersedia ?> unit sedang disewa</span>
                        </div>
                    </div>
                </div>

                <!-- CTA Buttons -->
                <a href="javascript:void(0)" class="btn-sewa" id="btnSewa" onclick="addToWishlist()">
                    <i class="bi bi-cart-plus"></i> Sewa Sekarang <i class="bi bi-arrow-right"></i>
                </a>
                <div class="share-row">
                    <a href="https://wa.me/?text=Cek%20<?= urlencode($product_name) ?>%20di%20SIMPEL-CAMP!" target="_blank" class="share-btn wa">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                    <button class="share-btn copy" onclick="copyLink()">
                        <i class="bi bi-link-45deg"></i> Salin Link
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="saas-card tabs-card scroll-anim">
        <ul class="nav nav-pills" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#tabDeskripsi">Deskripsi</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tabSpesifikasi">Spesifikasi</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tabUlasan">Ulasan</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tabKebijakan">Kebijakan</a></li>
        </ul>
        <div class="tab-content">
            <!-- Tab 1: Deskripsi -->
            <div class="tab-pane fade show active" id="tabDeskripsi">
                <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--text-primary);margin-bottom:0.75rem;"><?= htmlspecialchars($product_name) ?></h5>
                <p style="color:var(--text-secondary);line-height:1.8;font-size:0.92rem;"><?= nl2br(htmlspecialchars($product_desc)) ?></p>
            </div>
            <!-- Tab 2: Spesifikasi -->
            <div class="tab-pane fade" id="tabSpesifikasi">
                <table class="spec-table">
                    <tr><td>Nama Produk</td><td><?= htmlspecialchars($product_name) ?></td></tr>
                    <tr><td>Kategori</td><td><?= htmlspecialchars($product_category) ?></td></tr>
                    <tr><td>Harga Sewa</td><td>Rp <?= number_format($product_price, 0, ',', '.') ?> /hari</td></tr>
                    <tr><td>Stok Tersedia</td><td><?= $product_stock ?> unit</td></tr>
                    <tr><td>Status</td><td><?= ucfirst(htmlspecialchars($product_status)) ?></td></tr>
                </table>
                <p class="text-muted small mt-3"><em>* Spesifikasi mendalam dikelola per produk dalam sistem manajemen stok.</em></p>
            </div>
            <!-- Tab 3: Ulasan -->
            <div class="tab-pane fade" id="tabUlasan">
                <div class="text-center py-4">
                    <i class="bi bi-chat-square-text" style="font-size:2.5rem;color:var(--text-secondary);opacity:0.3;"></i>
                    <p style="color:var(--text-secondary);font-size:0.92rem;margin-top:12px;">Belum ada ulasan untuk produk ini. Jadilah yang pertama memberikan ulasan!</p>
                </div>
            </div>
            <!-- Tab 4: Kebijakan -->
            <div class="tab-pane fade" id="tabKebijakan">
                <div class="policy-item">
                    <div class="policy-icon" style="background:rgba(82,183,136,0.1);color:var(--primary-light);"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <h6>Deposit: Rp 100.000</h6>
                        <p>Deposit akan dikembalikan setelah barang dikembalikan dalam kondisi baik.</p>
                    </div>
                </div>
                <div class="policy-item">
                    <div class="policy-icon" style="background:rgba(220,53,69,0.08);color:#dc3545;"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <h6>Denda Keterlambatan: Rp 25.000/hari</h6>
                        <p>Denda berlaku untuk setiap hari keterlambatan pengembalian.</p>
                    </div>
                </div>
                <div class="policy-item">
                    <div class="policy-icon" style="background:rgba(212,163,115,0.12);color:var(--accent-gold);"><i class="bi bi-calendar-range"></i></div>
                    <div>
                        <h6>Maksimal Sewa: 14 Hari</h6>
                        <p>Durasi sewa maksimal 14 hari per transaksi. Perpanjangan bisa diajukan.</p>
                    </div>
                </div>
                <div class="policy-item">
                    <div class="policy-icon" style="background:rgba(45,106,79,0.1);color:var(--primary);"><i class="bi bi-droplet"></i></div>
                    <div>
                        <h6>Pengembalian dalam Kondisi Bersih</h6>
                        <p>Barang harus dikembalikan dalam kondisi bersih dan kering.</p>
                    </div>
                </div>
                <div class="policy-item">
                    <div class="policy-icon" style="background:rgba(255,193,7,0.1);color:#ffc107;"><i class="bi bi-exclamation-triangle"></i></div>
                    <div>
                        <h6>Kerusakan Ditanggung Penyewa</h6>
                        <p>Segala kerusakan di luar pemakaian normal akan menjadi tanggung jawab penyewa.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <div class="related-section scroll-anim">
        <h4><i class="bi bi-grid me-2"></i>Produk Terkait</h4>
        <div class="related-scroll">
            <?php if (!empty($relatedProducts)): ?>
            <?php foreach ($relatedProducts as $rel): 
                $relImg = !empty($rel['gambar']) ? ASSETS_URL . '/img/barang/' . $rel['gambar'] : 'https://images.unsplash.com/photo-1504280390467-336c1e55b4bc?auto=format&fit=crop&w=400&q=80';
            ?>
            <div class="related-card">
                <div style="overflow:hidden;"><img src="<?= htmlspecialchars($relImg) ?>" alt="<?= htmlspecialchars($rel['nama']) ?>"></div>
                <div class="rc-body">
                    <div class="rc-name"><?= htmlspecialchars($rel['nama']) ?></div>
                    <div class="rc-price">Rp <?= number_format((int)$rel['harga_per_hari'], 0, ',', '.') ?> <small style="color:var(--text-secondary);font-family:var(--font-body);">/hari</small></div>
                    <div class="rc-rating"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i> <span>4.5</span></div>
                    <a href="<?= BASE_URL ?>/pages/pelanggan/detail_barang.php?id=<?= $rel['id'] ?>" class="rc-btn">Lihat Detail</a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="text-center py-3" style="width:100%;">
                <p style="color:var(--text-secondary);font-size:0.88rem;">Tidak ada produk terkait.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /detail-page -->

<!-- Floating Mobile Bar -->
<div class="mobile-bar">
    <div>
        <div class="mb-price">Rp <?= number_format($product_price, 0, ',', '.') ?> <span>/hari</span></div>
    </div>
    <a href="javascript:void(0)" class="mb-btn" onclick="addToWishlist()"><i class="bi bi-cart-plus me-1"></i> Sewa Sekarang</a>
</div>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightbox">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="bi bi-x-lg"></i></button>
    <button class="lightbox-nav lightbox-prev" onclick="navLightbox(-1)"><i class="bi bi-chevron-left"></i></button>
    <img src="" alt="Lightbox" class="lightbox-img" id="lightboxImg">
    <button class="lightbox-nav lightbox-next" onclick="navLightbox(1)"><i class="bi bi-chevron-right"></i></button>
    <div class="lightbox-counter" id="lightboxCounter">1 / 4</div>
</div>

    </div><!-- /pelanggan-content -->
</div><!-- /pelanggan-main -->
</div><!-- /pelanggan-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  IMAGE GALLERY & LIGHTBOX
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
const images = [
    '<?= htmlspecialchars($product_img) ?>',
    'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800&q=80',
    'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=800&q=80',
    'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=800&q=80'
];
let currentImg = 0;

function setMainImage(idx, el) {
    currentImg = idx;
    document.getElementById('mainImage').src = images[idx];
    document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
}

function openLightbox() {
    document.getElementById('lightboxImg').src = images[currentImg];
    document.getElementById('lightboxCounter').textContent = (currentImg + 1) + ' / ' + images.length;
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}

function navLightbox(dir) {
    currentImg = (currentImg + dir + images.length) % images.length;
    document.getElementById('lightboxImg').src = images[currentImg];
    document.getElementById('lightboxCounter').textContent = (currentImg + 1) + ' / ' + images.length;
    document.querySelectorAll('.thumb-item').forEach((t, i) => {
        t.classList.toggle('active', i === currentImg);
    });
}

document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
});
document.addEventListener('keydown', function(e) {
    if (!document.getElementById('lightbox').classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') navLightbox(-1);
    if (e.key === 'ArrowRight') navLightbox(1);
});

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  QUANTITY SELECTOR
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function changeQty(delta) {
    const input = document.getElementById('qty');
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(5, val));
    input.value = val;
    calcTotal();
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  DATE CALCULATION
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
const HARGA = <?= $product_price ?>;
const tglMulai = document.getElementById('tglMulai');
const tglSelesai = document.getElementById('tglSelesai');
const today = new Date().toISOString().split('T')[0];
tglMulai.min = today;
tglSelesai.min = today;

function calcTotal() {
    const m = new Date(tglMulai.value);
    const s = new Date(tglSelesai.value);
    const qty = parseInt(document.getElementById('qty').value) || 1;
    let days = 0;
    if (tglMulai.value && tglSelesai.value && s > m) {
        days = Math.ceil((s - m) / (1000 * 60 * 60 * 24));
    }
    const total = days * qty * HARGA;
    document.getElementById('durasi').textContent = days + ' hari';
    document.getElementById('totalHarga').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

tglMulai.addEventListener('change', function() {
    if (this.value) {
        tglSelesai.min = this.value;
        if (tglSelesai.value && tglSelesai.value <= this.value) tglSelesai.value = '';
    }
    calcTotal();
});
tglSelesai.addEventListener('change', calcTotal);

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  WISHLIST TOGGLE
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
let wishlisted = false;
function toggleWishlist() {
    wishlisted = !wishlisted;
    const btn = document.getElementById('btnWishlist');
    if (wishlisted) {
        btn.classList.add('active');
        btn.innerHTML = '<i class="bi bi-heart-fill" id="wishlistIcon"></i> Hapus dari Wishlist';
        showToast('Ditambahkan ke wishlist!', 'success');
    } else {
        btn.classList.remove('active');
        btn.innerHTML = '<i class="bi bi-heart" id="wishlistIcon"></i> Tambah ke Wishlist';
        showToast('Dihapus dari wishlist', 'info');
    }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  COPY LINK
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        showToast('Link berhasil disalin!', 'success');
    }).catch(() => {
        showToast('Gagal menyalin link', 'warning');
    });
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  TOAST NOTIFICATIONS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'custom-toast ' + type;
    const icons = {
        success: 'bi-check-circle-fill',
        info: 'bi-info-circle-fill',
        warning: 'bi-exclamation-triangle-fill'
    };
    toast.innerHTML = '<div class="toast-icon"><i class="bi ' + (icons[type] || icons.info) + '"></i></div><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  SCROLL ANIMATIONS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
    });
}, { threshold: 0.1 });

document.querySelectorAll('.scroll-anim').forEach(el => observer.observe(el));

// ╔══════════════════════════════════════════╗
//  ADD TO WISHLIST — save to DB, redirect
// ╚══════════════════════════════════════════╝
function addToWishlist() {
    const btn = document.getElementById('btnSewa');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menambahkan...';
    btn.style.pointerEvents = 'none';

    fetch('<?= BASE_URL ?>/api/wishlist.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ barang_id: <?= $barang_id ?>, jumlah: 1 })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '<?= BASE_URL ?>/pages/pelanggan/wishlist.php';
        } else {
            alert(data.message || 'Gagal menambahkan ke wishlist');
            btn.innerHTML = orig;
            btn.style.pointerEvents = '';
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan jaringan');
        btn.innerHTML = orig;
        btn.style.pointerEvents = '';
    });
}
</script>
</body>
</html>
