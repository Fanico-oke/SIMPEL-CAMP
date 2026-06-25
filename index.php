<?php
// index.php
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'classes/Barang.php';
require_once 'classes/Kategori.php';
require_once 'classes/Konten.php';
require_once 'classes/Pengaturan.php';

$page_title = "Sewa Alat Camping & Pendakian";

// Ambil data dari database
$heroData = Konten::getBySection('hero');
$footerData = Konten::getBySection('footer');
$katalogBarang = Barang::getAll(['limit' => 4, 'status' => 'tersedia']);
$carouselBarang = Barang::getPopuler(5);
if (count($carouselBarang) < 5) {
    $carouselBarang = Barang::getAll(['limit' => 5, 'status' => 'tersedia']);
}

// Fallback images per kategori (Unsplash)
$categoryImages = [
    'Shelter & Tenda' => 'https://images.unsplash.com/photo-1504280390467-336c1e55b4bc?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Perlengkapan Tidur' => 'https://images.unsplash.com/photo-1523987355523-c7b5b0dd90a7?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Dapur Lapangan' => 'https://images.unsplash.com/photo-1556909114-44e3e70034e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Penerangan & Elektronik' => 'https://images.unsplash.com/photo-1530541930197-ff16ac917b0e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Pakaian & Alas Kaki' => 'https://images.unsplash.com/photo-1551632811-561732d1e306?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Keselamatan & Medis' => 'https://images.unsplash.com/photo-1603398938378-e54eab446dde?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Peralatan Pendukung' => 'https://images.unsplash.com/photo-1517824806704-9040b037703b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Navigasi & Orientasi' => 'https://images.unsplash.com/photo-1452421822248-d4c2b47f0c81?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Pemurnian Air' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Alat Bantu Jalan' => 'https://images.unsplash.com/photo-1622260614153-03223fb72052?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Pertukangan & Survival' => 'https://images.unsplash.com/photo-1510672981848-a1c4f1cb5ccf?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Kenyamanan Camp' => 'https://images.unsplash.com/photo-1487730116645-74489c55551f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'Komunikasi & Keamanan' => 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
];

function getBarangImage($barang, $categoryImages) {
    if (!empty($barang['gambar'])) return ASSETS_URL . '/img/barang/' . $barang['gambar'];
    return $categoryImages[$barang['kategori_nama'] ?? ''] ?? 'https://images.unsplash.com/photo-1504280390467-336c1e55b4bc?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <meta name="description" content="Platform #1 sewa perlengkapan camping dan pendakian. Reservasi online, harga terjangkau, kualitas terjamin.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1B4332;
            --primary-light: #2D6A4F;
            --primary-lighter: #52B788;
            --accent: #D4A373;
            --accent2: #E9C46A;
            --dark: #0F2B1E;
            --dark-soft: #163828;
            --bg-section: #122E21;
        }
        body { font-family: 'Inter', sans-serif; background: var(--dark); color: white; overflow-x: hidden; }

        /* ── NAVBAR ── */
        .navbar-sc {
            position: fixed; top: 0; left: 0; right: 0; z-index: 999;
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.2rem 3rem;
            transition: background 0.4s, backdrop-filter 0.4s;
        }
        .navbar-sc.scrolled {
            background: rgba(15, 43, 30, 0.92);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .nav-brand {
            font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.35rem;
            color: white; text-decoration: none; letter-spacing: 1px;
        }
        .nav-brand span { color: var(--accent2); }
        .nav-menu { display: flex; gap: 2.2rem; list-style: none; }
        .nav-menu a {
            color: rgba(255,255,255,0.7); font-size: 0.82rem; font-weight: 500;
            text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px;
            transition: color 0.2s;
        }
        .nav-menu a:hover { color: white; }
        .nav-actions { display: flex; align-items: center; gap: 1rem; }
        .btn-login {
            color: rgba(255,255,255,0.75); font-size: 0.82rem; font-weight: 500;
            text-decoration: none; transition: color 0.2s;
        }
        .btn-login:hover { color: white; }
        .btn-daftar {
            background: var(--accent); color: #1a1a1a; font-weight: 700;
            font-size: 0.78rem; text-transform: uppercase; letter-spacing: 1px;
            padding: 0.5rem 1.4rem; border-radius: 50px; text-decoration: none;
            transition: all 0.3s;
        }
        .btn-daftar:hover { background: var(--accent2); transform: translateY(-1px); box-shadow: 0 8px 25px rgba(212,163,115,0.4); }

        /* ── HERO ── */
        .hero {
            position: relative; height: 100vh; min-height: 700px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        /* Background foto alam nyata */
        .hero-bg-img {
            position: absolute; inset: 0; z-index: 0;
            background:
                url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=85')
                center/cover no-repeat;
            transform: scale(1.05);
            animation: slow-zoom 20s ease-in-out infinite alternate;
        }
        @keyframes slow-zoom {
            from { transform: scale(1.05); }
            to   { transform: scale(1.12); }
        }
        /* Overlay hijau terang di atas foto */
        .hero-overlay {
            position: absolute; inset: 0; z-index: 1;
            background: linear-gradient(
                to bottom,
                rgba(15,43,30,0.40) 0%,
                rgba(15,43,30,0.25) 40%,
                rgba(15,43,30,0.75) 85%,
                rgba(15,43,30,0.98) 100%
            );
        }
        /* Fog animasi */
        .hero-fog {
            position: absolute; bottom: 0; left: -10%; right: -10%;
            height: 280px; z-index: 2; pointer-events: none;
            background: radial-gradient(ellipse at center bottom, rgba(82,183,136,0.08) 0%, transparent 70%);
            animation: fog 10s ease-in-out infinite alternate;
        }
        @keyframes fog { from { transform: translateX(-3%); } to { transform: translateX(3%); } }

        /* Hero content */
        .hero-inner {
            position: relative; z-index: 5;
            display: flex; flex-direction: column;
            align-items: center; gap: 2.5rem;
            width: 100%; padding: 0 1.5rem;
        }
        .hero-text { text-align: center; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(212,163,115,0.15); border: 1px solid rgba(212,163,115,0.3);
            color: var(--accent2); font-size: 0.72rem; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            padding: 0.4rem 1.1rem; border-radius: 50px;
            margin-bottom: 1.2rem;
            opacity: 0; transform: translateY(15px);
            animation: fade-up 0.7s ease 0.4s forwards;
        }
        .hero-title {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(2.8rem, 6.5vw, 5.5rem);
            font-weight: 900; line-height: 1.02; letter-spacing: -2px;
            color: white; text-shadow: 0 4px 30px rgba(0,0,0,0.5);
            margin-bottom: 1rem;
            opacity: 0; transform: translateY(20px);
            animation: fade-up 0.8s ease 0.6s forwards;
        }
        .hero-title .gold {
            background: linear-gradient(135deg, var(--accent2) 0%, var(--accent) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-desc {
            font-size: 1rem; color: rgba(255,255,255,0.6); max-width: 440px;
            margin: 0 auto; line-height: 1.75;
            opacity: 0; transform: translateY(20px);
            animation: fade-up 0.8s ease 0.8s forwards;
        }

        /* ── 3D CAROUSEL ── */
        .carousel-section {
            opacity: 0; transform: translateY(20px);
            animation: fade-up 0.8s ease 1.1s forwards;
        }
        /* Track */
        .cards-track {
            position: relative; height: 310px;
            display: flex; align-items: center; justify-content: center;
            perspective: 900px;
        }
        .c-card {
            position: absolute;
            width: 190px; height: 280px;
            border-radius: 16px; overflow: hidden;
            cursor: pointer;
            transition: all 0.65s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 20px 60px rgba(0,0,0,0.7);
        }
        .c-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .c-card:hover img { transform: scale(1.06); }
        .c-card-info {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 2.5rem 0.9rem 0.9rem;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 60%, transparent 100%);
            text-align: center;
        }
        .c-card-name { font-family: 'Outfit', sans-serif; font-size: 0.85rem; font-weight: 700; color: white; line-height: 1.3; text-shadow: 0 1px 6px rgba(0,0,0,0.6); }

        /* Posisi kartu */
        .c-card[data-pos="-2"] { transform: translateX(-310px) rotateY(38deg) scale(0.62); filter: brightness(0.35) blur(1.5px); z-index: 1; }
        .c-card[data-pos="-1"] { transform: translateX(-185px) rotateY(22deg) scale(0.78); filter: brightness(0.5); z-index: 2; }
        .c-card[data-pos="0"]  { transform: translateX(0) rotateY(0) scale(1.05); filter: brightness(1); z-index: 5; width:210px; height:300px; }
        .c-card[data-pos="1"]  { transform: translateX(185px) rotateY(-22deg) scale(0.78); filter: brightness(0.5); z-index: 2; }
        .c-card[data-pos="2"]  { transform: translateX(310px) rotateY(-38deg) scale(0.62); filter: brightness(0.35) blur(1.5px); z-index: 1; }
        .c-card[data-pos="99"] { opacity: 0; pointer-events: none; }

        /* Navigasi carousel */
        .carousel-nav {
            display: flex; align-items: center; justify-content: center;
            gap: 1.2rem; margin-top: 1.5rem;
        }
        .c-btn {
            width: 44px; height: 44px; border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.05);
            color: white; font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        .c-btn:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.35); transform: scale(1.1); }
        .c-dots { display: flex; gap: 7px; align-items: center; }
        .c-dot {
            width: 6px; height: 6px; border-radius: 3px;
            background: rgba(255,255,255,0.25); cursor: pointer;
            transition: all 0.3s;
        }
        .c-dot.on { background: var(--accent); width: 22px; }

        /* Scroll hint */
        .scroll-hint {
            position: absolute; bottom: 2rem; left: 50%; transform: translateX(-50%);
            z-index: 10; text-align: center;
            opacity: 0; animation: fade-up 0.7s ease 2s forwards;
        }
        .scroll-hint span { display: block; font-size: 0.62rem; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,0.3); margin-bottom: 6px; }
        .scroll-line { width: 1px; height: 38px; background: linear-gradient(to bottom, rgba(255,255,255,0.3), transparent); margin: 0 auto; animation: bob 1.5s ease-in-out infinite; }
        @keyframes bob { 0%,100%{ opacity:1; transform: translateY(0); } 50%{ opacity:0.4; transform: translateY(6px); } }

        @keyframes fade-up { to { opacity:1; transform: translateY(0); } }

        /* ── HOW IT WORKS ── */
        .how-section {
            padding: 6rem 0;
            background: linear-gradient(180deg, var(--dark) 0%, var(--dark-soft) 100%);
        }
        .sec-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: var(--accent); margin-bottom: 0.8rem; }
        .sec-title { font-family: 'Outfit', sans-serif; font-size: clamp(1.8rem,4vw,2.8rem); font-weight: 800; color: white; line-height: 1.15; }
        .sec-title span { color: var(--accent2); }

        .step-item {
            display: flex; align-items: flex-start; gap: 1.2rem;
            padding: 1.5rem; border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            transition: all 0.35s;
        }
        .step-item:hover { background: rgba(255,255,255,0.1); border-color: rgba(82,183,136,0.4); transform: translateX(6px); }
        .step-num {
            min-width: 44px; height: 44px; border-radius: 12px;
            background: rgba(82,183,136,0.25); border: 1px solid rgba(82,183,136,0.45);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.1rem;
            color: var(--accent2);
        }
        .step-title { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1rem; color: white; margin-bottom: 0.3rem; }
        .step-desc { font-size: 0.85rem; color: rgba(255,255,255,0.55); line-height: 1.65; }

        /* Catalog cards */
        .prd-card {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 18px; overflow: hidden;
            transition: all 0.4s;
        }
        .prd-card:hover { transform: translateY(-7px); border-color: rgba(82,183,136,0.35); box-shadow: 0 25px 55px rgba(0,0,0,0.3); }
        .prd-card img { width:100%; height:200px; object-fit:cover; transition: transform 0.5s; }
        .prd-card:hover img { transform: scale(1.06); }
        .prd-body { padding: 1.1rem 1.2rem; }
        .prd-cat { font-size: 0.68rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--primary-lighter); margin-bottom: 0.4rem; }
        .prd-name { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 0.95rem; color: white; margin-bottom: 0.9rem; }
        .prd-price { font-size: 1.1rem; font-weight: 800; color: var(--accent2); }
        .prd-unit { font-size: 0.73rem; color: rgba(255,255,255,0.5); }
        .prd-btn {
            background: rgba(82,183,136,0.25); border: 1px solid rgba(82,183,136,0.5);
            color: #6ee7b7; font-size: 0.73rem; font-weight: 600;
            padding: 0.35rem 0.9rem; border-radius: 50px;
            text-decoration: none; transition: all 0.3s;
        }
        .prd-btn:hover { background: var(--primary-lighter); color: white; }

        /* ── CTA ── */
        .cta-section {
            padding: 6rem 0; text-align: center;
            background: var(--dark-soft);
            position: relative; overflow: hidden;
        }
        .cta-section::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse at center, rgba(82,183,136,0.12) 0%, transparent 70%);
        }
        .cta-title { font-family: 'Outfit', sans-serif; font-size: clamp(2rem,5vw,3.2rem); font-weight: 800; color: white; margin-bottom: 1rem; }
        .cta-sub { color: rgba(255,255,255,0.55); max-width: 400px; margin: 0 auto 2.2rem; font-size: 0.95rem; line-height: 1.7; }
        .btn-p {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #1a1a1a; font-weight: 700; font-size: 0.83rem;
            letter-spacing: 0.8px; text-transform: uppercase;
            padding: 0.85rem 2.2rem; border-radius: 50px;
            text-decoration: none; display: inline-block;
            transition: all 0.3s; box-shadow: 0 10px 30px rgba(212,163,115,0.3);
        }
        .btn-p:hover { transform: translateY(-3px); box-shadow: 0 18px 40px rgba(212,163,115,0.45); }
        .btn-o {
            background: transparent; color: rgba(255,255,255,0.75);
            border: 1px solid rgba(255,255,255,0.2); font-size: 0.83rem;
            font-weight: 500; padding: 0.85rem 2rem; border-radius: 50px;
            text-decoration: none; display: inline-block; transition: all 0.3s;
        }
        .btn-o:hover { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.4); color: white; }

        /* ── FOOTER ── */
        footer {
            padding: 3.5rem 0 2rem;
            background: linear-gradient(180deg, var(--dark-soft) 0%, #163D2E 100%);
            border-top: 1px solid rgba(82,183,136,0.15);
        }
        footer .brand { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.2rem; }
        footer .brand span { color: var(--accent2); }
        footer p, footer a { font-size: 0.82rem; color: rgba(255,255,255,0.45); text-decoration: none; }
        footer a:hover { color: rgba(255,255,255,0.8); }

        /* Reveal */
        .reveal { opacity: 0; transform: translateY(25px); transition: opacity 0.7s ease, transform 0.7s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        @media (max-width: 768px) {
            .navbar-sc { padding: 1rem 1.2rem; }
            .nav-menu { display: none; }
            .c-card[data-pos="-2"], .c-card[data-pos="2"] { display: none; }
            .c-card[data-pos="-1"] { transform: translateX(-130px) rotateY(22deg) scale(0.75); }
            .c-card[data-pos="1"]  { transform: translateX(130px) rotateY(-22deg) scale(0.75); }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-sc" id="mainNav">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="<?= BASE_URL ?>/" class="nav-brand">⛺ SIMPEL-<span>CAMP</span></a>
        
        <div class="d-none d-md-flex align-items-center gap-4">
            <ul class="nav-menu mb-0">
                <li><a href="#catalog">Katalog</a></li>
                <li><a href="#cara-sewa">Cara Sewa</a></li>
            </ul>
            <div class="nav-actions border-start border-secondary ps-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>/pages/<?= $_SESSION['role'] ?>/dashboard.php" class="btn-login">Dashboard</a>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn-daftar ms-3">Keluar</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn-login">Masuk</a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn-daftar ms-3">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- ══ HERO ══ -->
<section class="hero">
    <div class="hero-bg-img"></div>
    <div class="hero-overlay"></div>
    <div class="hero-fog"></div>

    <div class="hero-inner">
        <!-- Teks -->
        <div class="hero-text">
            <div class="hero-badge"><?= htmlspecialchars($heroData['badge'] ?? '⛰️ Platform Perlengkapan Outdoor #1') ?></div>
            <h1 class="hero-title">
                <?php
                $title = $heroData['title'] ?? 'Petualangan Dimulai Dari Persiapan Terbaik';
                $parts = explode(' ', $title, 4);
                echo htmlspecialchars($parts[0] . ' ' . ($parts[1] ?? '')) . '<br>Dari <span class="gold">' . htmlspecialchars(($parts[3] ?? 'Persiapan Terbaik')) . '</span>';
                ?>
            </h1>
            <p class="hero-desc"><?= htmlspecialchars($heroData['subtitle'] ?? 'Sewa alat camping & pendakian berkualitas, booking online, harga transparan — siap untuk petualangan berikutnya?') ?></p>
        </div>

        <!-- 3D Carousel -->
        <div class="carousel-section">
            <div class="cards-track" id="track"></div>
            <div class="carousel-nav">
                <button class="c-btn" id="prev"><i class="bi bi-chevron-left"></i></button>
                <div class="c-dots" id="dots"></div>
                <button class="c-btn" id="next"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>

        <!-- CTA buttons -->
        <div class="d-flex gap-3 flex-wrap justify-content-center" style="opacity:0;transform:translateY(20px);animation:fade-up 0.8s ease 1.4s forwards">
            <a href="<?= BASE_URL ?>/login.php" class="btn-p">Mulai Sewa Sekarang</a>
            <a href="#cara-sewa" class="btn-o">Cara Sewa <i class="bi bi-arrow-down ms-1"></i></a>
        </div>
    </div>

    <div class="scroll-hint">
        <span>Scroll</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- ══ CARA SEWA ══ -->
<section class="how-section" id="cara-sewa">
    <div class="container">
        <div class="row gy-5 align-items-center">
            <!-- Teks -->
            <div class="col-lg-5 reveal">
                <div class="sec-label">Panduan</div>
                <h2 class="sec-title mb-3">Sewa Alat Semudah<br><span>4 Langkah</span></h2>
                <p style="color:rgba(255,255,255,0.4);font-size:0.9rem;line-height:1.75;margin-bottom:2rem;">Dari pilih barang hingga berangkat mendaki, semua bisa dilakukan dari genggaman tangan Anda.</p>
                <a href="<?= BASE_URL ?>/login.php" class="btn-p">Lihat Katalog</a>
            </div>
            <!-- Steps -->
            <div class="col-lg-7">
                <div class="d-flex flex-column gap-3">
                    <?php
                    $steps = [
                        ['n'=>'01','t'=>'Pilih Peralatan','d'=>'Jelajahi E-Katalog. Filter berdasarkan kategori dan cek ketersediaan stok secara real-time.'],
                        ['n'=>'02','t'=>'Buat Reservasi Online','d'=>'Isi tanggal sewa & selesai. Total biaya terhitung otomatis — tidak ada biaya tersembunyi.'],
                        ['n'=>'03','t'=>'Ambil Alat di Toko','d'=>'Datang ke toko kami dengan bukti reservasi. Alat sudah disiapkan dan layak pakai.'],
                        ['n'=>'04','t'=>'Kembalikan Tepat Waktu','d'=>'Kembalikan sesuai tanggal. Jika terlambat, denda transparan terhitung otomatis.'],
                    ];
                    foreach($steps as $i=>$s): ?>
                    <div class="step-item reveal" style="transition-delay:<?=$i*0.1?>s">
                        <div class="step-num"><?=$s['n']?></div>
                        <div>
                            <div class="step-title"><?=$s['t']?></div>
                            <p class="step-desc mb-0"><?=$s['d']?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ KATALOG PREVIEW ══ -->
<section style="padding:5rem 0;background:var(--bg-section);" id="catalog">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div class="reveal">
                <div class="sec-label">Koleksi Kami</div>
                <h2 class="sec-title">Alat <span>Premium</span>, Siap Pakai</h2>
            </div>
            <a href="<?= BASE_URL ?>/login.php" class="btn-o d-none d-md-block reveal">Semua Alat <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row gy-4">
            <?php foreach($katalogBarang as $i=>$p): 
                $pImg = getBarangImage($p, $categoryImages);
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="prd-card reveal" style="transition-delay:<?=$i*0.1?>s">
                    <div style="overflow:hidden;"><img src="<?=$pImg?>" alt="<?=htmlspecialchars($p['nama'])?>" loading="lazy"></div>
                    <div class="prd-body">
                        <div class="prd-cat"><?=htmlspecialchars($p['kategori_nama'] ?? '')?></div>
                        <div class="prd-name"><?=htmlspecialchars($p['nama'])?></div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div><span class="prd-price">Rp <?=number_format($p['harga_per_hari'],0,',','.')?></span><span class="prd-unit">/hari</span></div>
                            <a href="<?=BASE_URL?>/login.php" class="prd-btn">Sewa</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══ CTA ══ -->
<section class="cta-section" style="background: url('https://images.unsplash.com/photo-1501555088652-021faa106b9b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=85') center/cover fixed; position: relative; overflow: hidden;">
    <!-- Overlay semi-transparan -->
    <div style="position: absolute; inset: 0; background: rgba(15, 43, 30, 0.78); z-index: 0;"></div>
    <!-- Gradasi atas: menyatu dengan section sebelumnya -->
    <div style="position: absolute; top: 0; left: 0; right: 0; height: 120px; background: linear-gradient(to bottom, var(--bg-section), transparent); z-index: 1;"></div>
    <!-- Gradasi bawah: menyatu dengan footer -->
    <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 120px; background: linear-gradient(to top, var(--dark-soft), transparent); z-index: 1;"></div>
    <!-- Dekorasi aksen -->
    <div style="position: absolute; top: -50%; left: -10%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(212,163,115,0.08) 0%, transparent 70%); border-radius: 50%; z-index: 0;"></div>
    <div style="position: absolute; bottom: -50%; right: -10%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(45,106,79,0.15) 0%, transparent 70%); border-radius: 50%; z-index: 0;"></div>
    
    <div class="container position-relative z-1">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center reveal">
                <div class="sec-label mb-3" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2);">Lebih dari sekedar sewa</div>
                <h2 class="cta-title" style="font-size: clamp(2rem, 4vw, 3rem); font-family: 'Outfit', sans-serif; font-weight: 800; margin-bottom: 1.5rem;">Siap Memulai Petualangan Anda? ⛰️</h2>
                <p class="cta-sub" style="font-size: 1.05rem; color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: 2.5rem; max-width: 600px; margin-inline: auto;">
                    Dapatkan akses ke ratusan peralatan outdoor premium yang selalu dirawat dengan standar tinggi. Proses booking mudah, harga transparan, dan tim kami siap membantu persiapan pendakian Anda.
                </p>
                
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="<?= BASE_URL ?>/register.php" class="btn-p"><i class="bi bi-person-plus me-2"></i>Daftar Gratis</a>
                    <a href="<?= BASE_URL ?>/login.php" class="btn-o"><i class="bi bi-box-arrow-in-right me-2"></i>Sudah Punya Akun</a>
                </div>
                
                <div class="mt-5 d-flex justify-content-center gap-4 flex-wrap" style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">
                    <span class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Alat Bersih & Terawat</span>
                    <span class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Harga Transparan</span>
                    <span class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Panduan Penggunaan</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ FOOTER ══ -->
<footer>
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                <div class="brand mb-3">⛺ SIMPEL-<span>CAMP</span></div>
                <p class="mb-4" style="color: rgba(255,255,255,0.6); line-height: 1.7; font-size: 0.9rem;">
                    <?= htmlspecialchars($footerData['description'] ?? 'Platform sewa alat camping dan pendakian terlengkap. Kami menyediakan peralatan berkualitas tinggi untuk memastikan petualangan alam Anda aman dan nyaman.') ?>
                </p>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 class="text-white mb-3" style="font-family: 'Outfit', sans-serif; font-size: 1.1rem;">Tautan Cepat</h5>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="<?= BASE_URL ?>/" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.9rem;">Beranda</a></li>
                    <li><a href="<?= BASE_URL ?>/katalog.php" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.9rem;">Katalog</a></li>
                    <li><a href="#cara-sewa" style="color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.9rem;">Cara Sewa</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-3" style="font-family: 'Outfit', sans-serif; font-size: 1.1rem;">Hubungi Kami</h5>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    <li class="d-flex align-items-center gap-2" style="color: rgba(255,255,255,0.6); font-size: 0.9rem;">
                        <i class="bi bi-whatsapp text-success fs-5"></i> <?= htmlspecialchars($footerData['whatsapp'] ?? '+62 812-3456-7890') ?>
                    </li>
                    <li class="d-flex align-items-center gap-2" style="color: rgba(255,255,255,0.6); font-size: 0.9rem;">
                        <i class="bi bi-envelope text-primary-light fs-5"></i> <?= htmlspecialchars($footerData['email'] ?? 'info@simpelcamp.com') ?>
                    </li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-3" style="font-family: 'Outfit', sans-serif; font-size: 1.1rem;">Lokasi</h5>
                <p style="color: rgba(255,255,255,0.6); line-height: 1.7; font-size: 0.9rem;">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                    <?= nl2br(htmlspecialchars($footerData['alamat'] ?? 'Jl. Pegunungan No. 123, Kota Petualang, Indonesia')) ?>
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-white" style="opacity: 0.5; font-size: 1.2rem; transition: 0.3s;"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white" style="opacity: 0.5; font-size: 1.2rem; transition: 0.3s;"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white" style="opacity: 0.5; font-size: 1.2rem; transition: 0.3s;"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>
        </div>
        <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
        <div class="text-center" style="color: rgba(255,255,255,0.4); font-size: 0.85rem;">
            © <?= date('Y') ?> SIMPEL-CAMP. All rights reserved.
        </div>
    </div>
</footer>

<script>
// ── NAVBAR SCROLL ──
const nav = document.getElementById('mainNav');
window.addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > 60));

// ── 3D CAROUSEL ── (data dari database)
const items = [
<?php foreach($carouselBarang as $cb): 
    $cbImg = getBarangImage($cb, $categoryImages);
    $cbHarga = 'Rp ' . number_format($cb['harga_per_hari'],0,',','.') . '/hari';
?>
    { name:'<?= strtoupper(addslashes($cb['nama'])) ?>', sub:'<?= $cbHarga ?>', tag:'#<?= addslashes($cb['kategori_nama'] ?? '') ?>',
      img:'<?= $cbImg ?>' },
<?php endforeach; ?>
];

let cur = 0;
const track = document.getElementById('track');
const dotsEl = document.getElementById('dots');

// Build cards
const cardEls = items.map((item, i) => {
    const el = document.createElement('div');
    el.className = 'c-card';
    el.innerHTML = `<img src="${item.img}" alt="${item.name}">
        <div class="c-card-info">
            <div class="c-card-name">${item.name}</div>
        </div>`;
    el.addEventListener('click', () => go(i));
    track.appendChild(el);
    return el;
});

// Build dots
items.forEach((_, i) => {
    const d = document.createElement('div');
    d.className = 'c-dot' + (i===0?' on':'');
    d.addEventListener('click', () => go(i));
    dotsEl.appendChild(d);
});

function go(idx) {
    cur = (idx + items.length) % items.length;
    render();
}
function render() {
    const n = items.length;
    cardEls.forEach((el, i) => {
        let diff = i - cur;
        if (diff < -2) diff += n;
        if (diff >  2) diff -= n;
        el.setAttribute('data-pos', Math.abs(diff) > 2 ? '99' : diff);
    });
    document.querySelectorAll('.c-dot').forEach((d,i) => d.classList.toggle('on', i===cur));
}
document.getElementById('prev').onclick = () => go(cur-1);
document.getElementById('next').onclick = () => go(cur+1);
document.addEventListener('keydown', e => {
    if (e.key==='ArrowLeft') go(cur-1);
    if (e.key==='ArrowRight') go(cur+1);
});
// Touch/swipe
let tx = 0;
track.addEventListener('touchstart', e => tx = e.changedTouches[0].clientX);
track.addEventListener('touchend', e => { const d=tx-e.changedTouches[0].clientX; if(Math.abs(d)>40)go(cur+(d>0?1:-1)); });
// Auto
let auto = setInterval(() => go(cur+1), 4500);
track.addEventListener('mouseenter', () => clearInterval(auto));
track.addEventListener('mouseleave', () => { auto = setInterval(() => go(cur+1), 4500); });
render();

// ── SCROLL REVEAL ──
const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
</script>
</body>
</html>
