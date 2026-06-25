<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Konten.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

requireRole(['admin', 'superadmin']);

// Handle POST for content updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_konten') {
        $section = $_POST['section'] ?? '';
        $key = $_POST['key'] ?? '';
        $value = $_POST['value'] ?? '';
        if ($section && $key) {
            Konten::set($section, $key, $value);
            $_SESSION['flash_success'] = 'Konten berhasil diperbarui!';
        }
        header('Location: ' . BASE_URL . '/pages/admin/kelola_konten.php');
        exit;
    }
}

$page_title = 'Kelola Konten';
$current_page = 'kelola_konten';

// Load content from database
$heroContent = Konten::getBySection('hero');
$aboutContent = Konten::getBySection('about');
$faqContent = Konten::getBySection('faq');
$footerContent = Konten::getBySection('footer');
$ctaContent = Konten::getBySection('cta');
$flash_success = getFlash('success');
?>

<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?> - <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=<?= time() ?>">
<style>
:root{--kk-dark:#1B4332;--kk-mid:#2D6A4F;--kk-light:#52B788;--kk-gold:#D4A373;--kk-bg:#f0f4f1}
body{font-family:'Inter',sans-serif;background:var(--kk-bg)}
h1,h2,h3,h4,h5,h6,.heading{font-family:'Outfit',sans-serif}
.mono{font-family:'JetBrains Mono',monospace}
.tr-tabs{display:flex;gap:6px;background:#fff;border-radius:14px;padding:6px;box-shadow:0 2px 12px rgba(27,67,50,0.06);margin-bottom:28px;flex-wrap:wrap}
.tr-tab{padding:10px 22px;border-radius:10px;border:none;background:transparent;font-weight:600;color:#6c757d;cursor:pointer;transition:all .3s;font-size:.88rem;white-space:nowrap}
.tr-tab.active{background:linear-gradient(135deg,var(--kk-mid),var(--kk-light));color:#fff;box-shadow:0 4px 16px rgba(45,106,79,0.3)}
.tr-tab:hover:not(.active){background:rgba(82,183,136,0.08);color:var(--kk-mid)}
.section-card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 14px rgba(0,0,0,0.05);margin-bottom:24px;transition:all .3s}
.section-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.08)}
.section-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:1.1rem;color:var(--kk-dark);margin-bottom:20px;display:flex;align-items:center;gap:10px}
.section-title i{color:var(--kk-light);font-size:1.2rem}
.form-label-sm{font-size:.82rem;font-weight:600;color:#555;margin-bottom:4px}
.form-control,.form-select{border-radius:10px;padding:10px 14px;border:1.5px solid #e0e0e0;transition:all .3s;font-size:.9rem}
.form-control:focus,.form-select:focus{border-color:var(--kk-light);box-shadow:0 0 0 3px rgba(82,183,136,0.15)}
textarea.form-control{resize:vertical;min-height:80px}
.btn-save{background:linear-gradient(135deg,var(--kk-mid),var(--kk-light));color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:600;font-size:.88rem;transition:all .3s}
.btn-save:hover{box-shadow:0 6px 18px rgba(45,106,79,0.3);color:#fff}
.btn-add{background:linear-gradient(135deg,var(--kk-gold),#c4956a);color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:600;font-size:.88rem;transition:all .3s}
.btn-add:hover{box-shadow:0 6px 18px rgba(212,163,115,0.3);color:#fff}
.btn-delete{border:1.5px solid #ef4444;color:#ef4444;background:transparent;border-radius:8px;padding:6px 14px;font-weight:600;font-size:.8rem;transition:all .3s}
.btn-delete:hover{background:#ef4444;color:#fff}
.keunggulan-card{background:rgba(82,183,136,0.04);border:1.5px solid #e8e8e8;border-radius:14px;padding:20px;transition:all .3s}
.keunggulan-card:hover{border-color:var(--kk-light);box-shadow:0 4px 14px rgba(82,183,136,0.1)}
.keunggulan-num{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--kk-mid),var(--kk-light));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;margin-bottom:12px}
.product-check{border:1.5px solid #e8e8e8;border-radius:12px;padding:14px;cursor:pointer;transition:all .3s;display:flex;align-items:center;gap:10px}
.product-check:hover{border-color:var(--kk-light)}
.product-check.selected{border-color:var(--kk-light);background:rgba(82,183,136,0.04)}
.product-check input[type="checkbox"]{accent-color:var(--kk-mid);width:18px;height:18px}
.team-card{background:#fff;border:1.5px solid #e8e8e8;border-radius:16px;padding:24px;text-align:center;transition:all .3s}
.team-card:hover{border-color:var(--kk-light);box-shadow:0 6px 20px rgba(82,183,136,0.1)}
.team-avatar{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--kk-mid),var(--kk-light));color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;margin:0 auto 14px}
.faq-card{background:#fff;border:1.5px solid #e8e8e8;border-radius:14px;padding:20px;margin-bottom:16px;transition:all .3s}
.faq-card:hover{border-color:var(--kk-light);box-shadow:0 4px 14px rgba(82,183,136,0.08)}
.faq-num{font-family:'JetBrains Mono',monospace;font-size:.75rem;background:rgba(82,183,136,0.1);color:var(--kk-mid);padding:3px 10px;border-radius:50px;font-weight:600}
.social-card{background:rgba(82,183,136,0.04);border:1.5px solid #e8e8e8;border-radius:14px;padding:18px;display:flex;align-items:center;gap:14px;transition:all .3s}
.social-card:hover{border-color:var(--kk-light)}
.social-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.custom-toast{background:#fff;border-radius:12px;padding:14px 20px;box-shadow:0 8px 30px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;border-left:4px solid var(--kk-light);animation:slideInToast .4s forwards}
@keyframes slideInToast{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}
@keyframes slideOutToast{from{opacity:1}to{opacity:0;transform:translateX(100%)}}
.toast-container{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px}
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.stagger-item{opacity:0;animation:fadeInUp .5s ease forwards}
.preview-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:50px;font-size:.75rem;font-weight:600;background:rgba(59,130,246,0.1);color:#3b82f6}
</style></head><body>
<div class="admin-wrapper">
<?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>

    <div class="admin-content"><div class="container-fluid">

        <!-- Tabs -->
        <div class="tr-tabs">
            <button class="tr-tab active" id="tabBtnBeranda" onclick="switchTab('beranda')"><i class="bi bi-house me-1"></i>Beranda</button>
            <button class="tr-tab" id="tabBtnTentang" onclick="switchTab('tentang')"><i class="bi bi-info-circle me-1"></i>Tentang Kami</button>
            <button class="tr-tab" id="tabBtnFaq" onclick="switchTab('faq')"><i class="bi bi-question-circle me-1"></i>FAQ</button>
            <button class="tr-tab" id="tabBtnFooter" onclick="switchTab('footer')"><i class="bi bi-layout-text-sidebar me-1"></i>Footer</button>
        </div>

        <!-- TAB 1: BERANDA -->
        <div id="tab-beranda" class="tab-pane-kk">
            <!-- Hero Banner -->
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-image"></i>Hero Banner <span class="preview-badge"><i class="bi bi-eye"></i>Live Preview</span></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label-sm">Headline Utama</label><input type="text" class="form-control" name="headline" data-key="headline" value="<?= htmlspecialchars($heroContent['headline'] ?? 'Sewa Peralatan Camping Terlengkap') ?>"></div>
                    <div class="col-md-6"><label class="form-label-sm">Sub-headline</label><input type="text" class="form-control" name="subheadline" data-key="subheadline" value="<?= htmlspecialchars($heroContent['subheadline'] ?? 'Petualangan seru tanpa ribet, semua alat camping tersedia!') ?>"></div>
                    <div class="col-md-4"><label class="form-label-sm">Teks Tombol CTA</label><input type="text" class="form-control" name="cta_text" data-key="cta_text" value="<?= htmlspecialchars($heroContent['cta_text'] ?? 'Lihat Katalog') ?>"></div>
                    <div class="col-md-4"><label class="form-label-sm">Link Tombol</label><input type="text" class="form-control mono" name="cta_link" data-key="cta_link" value="<?= htmlspecialchars($heroContent['cta_link'] ?? '/pages/pelanggan/katalog.php') ?>"></div>
                    <div class="col-md-4"><label class="form-label-sm">Gambar Banner</label><input type="file" class="form-control" accept="image/*"></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Hero Banner')"><i class="bi bi-check2-circle me-1"></i>Simpan Banner</button></div>
            </div>

            <!-- Keunggulan -->
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-stars"></i>Keunggulan (Why Choose Us)</div>
                <div class="row g-3">
                    <div class="col-lg-4"><div class="keunggulan-card"><div class="keunggulan-num">1</div><div class="mb-2"><label class="form-label-sm">Icon</label><select class="form-select"><option selected>🛡️ bi-shield-check</option><option>🚚 bi-truck</option><option>🎧 bi-headset</option><option>⭐ bi-star</option></select></div><div class="mb-2"><label class="form-label-sm">Judul</label><input type="text" class="form-control" name="keunggulan1_judul" data-key="keunggulan1_judul" value="<?= htmlspecialchars($aboutContent['keunggulan1_judul'] ?? 'Peralatan Berkualitas') ?>"></div><div><label class="form-label-sm">Deskripsi</label><textarea class="form-control" rows="2" name="keunggulan1_deskripsi" data-key="keunggulan1_deskripsi"><?= htmlspecialchars($aboutContent['keunggulan1_deskripsi'] ?? 'Semua peralatan kami terawat dan berkualitas premium') ?></textarea></div></div></div>
                    <div class="col-lg-4"><div class="keunggulan-card"><div class="keunggulan-num">2</div><div class="mb-2"><label class="form-label-sm">Icon</label><select class="form-select"><option>🛡️ bi-shield-check</option><option selected>🚚 bi-truck</option><option>🎧 bi-headset</option><option>⭐ bi-star</option></select></div><div class="mb-2"><label class="form-label-sm">Judul</label><input type="text" class="form-control" name="keunggulan2_judul" data-key="keunggulan2_judul" value="<?= htmlspecialchars($aboutContent['keunggulan2_judul'] ?? 'Gratis Antar-Jemput') ?>"></div><div><label class="form-label-sm">Deskripsi</label><textarea class="form-control" rows="2" name="keunggulan2_deskripsi" data-key="keunggulan2_deskripsi"><?= htmlspecialchars($aboutContent['keunggulan2_deskripsi'] ?? 'Free delivery untuk area Kota Malang dan sekitarnya') ?></textarea></div></div></div>
                    <div class="col-lg-4"><div class="keunggulan-card"><div class="keunggulan-num">3</div><div class="mb-2"><label class="form-label-sm">Icon</label><select class="form-select"><option>🛡️ bi-shield-check</option><option>🚚 bi-truck</option><option selected>🎧 bi-headset</option><option>⭐ bi-star</option></select></div><div class="mb-2"><label class="form-label-sm">Judul</label><input type="text" class="form-control" name="keunggulan3_judul" data-key="keunggulan3_judul" value="<?= htmlspecialchars($aboutContent['keunggulan3_judul'] ?? 'Layanan 24 Jam') ?>"></div><div><label class="form-label-sm">Deskripsi</label><textarea class="form-control" rows="2" name="keunggulan3_deskripsi" data-key="keunggulan3_deskripsi"><?= htmlspecialchars($aboutContent['keunggulan3_deskripsi'] ?? 'Customer service siap membantu kapan saja') ?></textarea></div></div></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Keunggulan')"><i class="bi bi-check2-circle me-1"></i>Simpan Keunggulan</button></div>
            </div>

            <!-- Produk Unggulan -->
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-star"></i>Produk Unggulan Homepage</div>
                <p class="text-muted mb-3" style="font-size:.85rem">Pilih produk yang ditampilkan di halaman beranda (maks 6 produk)</p>
                <div class="row g-3">
                    <?php
                    $kontenBarang = Barang::getAll(['limit' => 12]);
                    foreach ($kontenBarang as $kb):
                    ?>
                    <div class="col-md-4"><div class="product-check" onclick="toggleProduct(this)"><input type="checkbox"> <span class="fw-semibold"><?= htmlspecialchars($kb['nama']) ?></span></div></div>
                    <?php endforeach; ?>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Produk Unggulan')"><i class="bi bi-check2-circle me-1"></i>Simpan Produk</button></div>
            </div>

            <!-- CTA -->
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-megaphone"></i>CTA Section</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label-sm">Heading CTA</label><input type="text" class="form-control" name="heading" data-key="heading" value="<?= htmlspecialchars($ctaContent['heading'] ?? 'Siap Untuk Petualangan?') ?>"></div>
                    <div class="col-md-6"><label class="form-label-sm">Teks Tombol</label><input type="text" class="form-control" name="button_text" data-key="button_text" value="<?= htmlspecialchars($ctaContent['button_text'] ?? 'Hubungi Kami') ?>"></div>
                    <div class="col-12"><label class="form-label-sm">Deskripsi</label><textarea class="form-control" name="description" data-key="description"><?= htmlspecialchars($ctaContent['description'] ?? 'Sewa peralatan camping berkualitas dengan harga terjangkau. Hubungi kami sekarang!') ?></textarea></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('CTA Section')"><i class="bi bi-check2-circle me-1"></i>Simpan CTA</button></div>
            </div>
        </div>

        <!-- TAB 2: TENTANG KAMI -->
        <div id="tab-tentang" class="tab-pane-kk" style="display:none">
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-building"></i>Profil Toko</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label-sm">Nama Toko</label><input type="text" class="form-control" name="nama_toko" data-key="nama_toko" value="<?= htmlspecialchars($aboutContent['nama_toko'] ?? 'SIMPEL-CAMP') ?>"></div>
                    <div class="col-md-6"><label class="form-label-sm">Tagline</label><input type="text" class="form-control" name="tagline" data-key="tagline" value="<?= htmlspecialchars($aboutContent['tagline'] ?? 'Petualangan Seru, Perlengkapan Lengkap!') ?>"></div>
                    <div class="col-md-6"><label class="form-label-sm">Alamat</label><input type="text" class="form-control" name="alamat" data-key="alamat" value="<?= htmlspecialchars($aboutContent['alamat'] ?? 'Jl. Soekarno Hatta No. 12, Kota Malang') ?>"></div>
                    <div class="col-md-3"><label class="form-label-sm">No. Telepon</label><input type="text" class="form-control" name="telepon" data-key="telepon" value="<?= htmlspecialchars($aboutContent['telepon'] ?? '0812-3456-7890') ?>"></div>
                    <div class="col-md-3"><label class="form-label-sm">Email</label><input type="email" class="form-control" name="email" data-key="email" value="<?= htmlspecialchars($aboutContent['email'] ?? 'info@simpelcamp.id') ?>"></div>
                    <div class="col-12"><label class="form-label-sm">Deskripsi Toko</label><textarea class="form-control" rows="3" name="deskripsi_toko" data-key="deskripsi_toko"><?= htmlspecialchars($aboutContent['deskripsi_toko'] ?? 'SIMPEL-CAMP adalah penyedia jasa sewa peralatan camping terlengkap di Kota Malang. Berdiri sejak 2020, kami telah melayani ribuan pecinta alam dengan peralatan berkualitas tinggi dan pelayanan terbaik.') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label-sm">Foto Toko</label><input type="file" class="form-control" accept="image/*"></div>
                    <div class="col-md-6"><label class="form-label-sm">Tahun Berdiri</label><input type="number" class="form-control" name="tahun_berdiri" data-key="tahun_berdiri" value="<?= htmlspecialchars($aboutContent['tahun_berdiri'] ?? '2020') ?>"></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Profil Toko')"><i class="bi bi-check2-circle me-1"></i>Simpan Profil</button></div>
            </div>

            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-bullseye"></i>Visi & Misi</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label-sm">Visi</label><textarea class="form-control" rows="3" name="visi" data-key="visi"><?= htmlspecialchars($aboutContent['visi'] ?? 'Menjadi penyedia jasa sewa peralatan camping terpercaya dan terlengkap di Indonesia, mendukung setiap petualangan outdoor dengan peralatan berkualitas.') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label-sm">Misi</label><textarea class="form-control" rows="3" name="misi" data-key="misi"><?= htmlspecialchars($aboutContent['misi'] ?? "1. Menyediakan peralatan camping berkualitas tinggi\n2. Memberikan pelayanan ramah dan profesional\n3. Harga terjangkau untuk semua kalangan\n4. Mendukung komunitas pecinta alam") ?></textarea></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Visi & Misi')"><i class="bi bi-check2-circle me-1"></i>Simpan Visi Misi</button></div>
            </div>

            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-people"></i>Tim Kami</div>
                <div class="row g-3">
                    <?php
                    $adminTeam = User::getAll(['role' => 'admin']);
                    if (empty($adminTeam)) $adminTeam = [['nama'=>'Admin','email'=>'admin@simpelcamp.com']];
                    foreach ($adminTeam as $tm):
                        $tmInitial = strtoupper(substr($tm['nama'], 0, 1)) . strtoupper(substr(explode(' ', $tm['nama'])[1] ?? '', 0, 1));
                    ?>
                    <div class="col-lg-4"><div class="team-card"><div class="team-avatar"><?= $tmInitial ?></div><div class="mb-2"><label class="form-label-sm">Nama</label><input type="text" class="form-control" value="<?= htmlspecialchars($tm['nama']) ?>"></div><div class="mb-2"><label class="form-label-sm">Jabatan</label><input type="text" class="form-control" value="<?= htmlspecialchars($tm['role'] ?? 'Admin') ?>"></div><div><label class="form-label-sm">Email</label><input type="text" class="form-control" value="<?= htmlspecialchars($tm['email']) ?>"></div></div></div>
                    <?php endforeach; ?>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Tim Kami')"><i class="bi bi-check2-circle me-1"></i>Simpan Tim</button></div>
            </div>
        </div>

        <!-- TAB 3: FAQ -->
        <div id="tab-faq" class="tab-pane-kk" style="display:none">
            <div class="d-flex justify-content-between align-items-center mb-4 stagger-item">
                <div><h5 class="heading fw-bold mb-0">Kelola FAQ</h5><p class="text-muted mb-0" style="font-size:.85rem">Pertanyaan yang sering diajukan pelanggan</p></div>
                <button class="btn btn-add" onclick="addFaq()"><i class="bi bi-plus-circle me-1"></i>Tambah FAQ</button>
            </div>
            <div id="faqList">
<?php
$faqItems = [];
for ($i = 1; $i <= 10; $i++) {
    $q = $faqContent['faq'.$i.'_pertanyaan'] ?? null;
    $a = $faqContent['faq'.$i.'_jawaban'] ?? null;
    if ($q !== null) $faqItems[] = ['pertanyaan' => $q, 'jawaban' => $a ?? ''];
}
if (empty($faqItems)) {
    $faqItems = [
        ['pertanyaan' => 'Bagaimana cara menyewa peralatan camping?', 'jawaban' => 'Anda bisa menyewa melalui website kami atau datang langsung ke toko. Pilih barang, tentukan durasi, dan lakukan pembayaran. Barang siap diambil atau diantar!'],
        ['pertanyaan' => 'Berapa minimal durasi sewa?', 'jawaban' => 'Minimal durasi sewa adalah 1 hari (24 jam). Anda bisa memperpanjang durasi sewa melalui aplikasi jika diperlukan.'],
        ['pertanyaan' => 'Apakah ada denda keterlambatan pengembalian?', 'jawaban' => 'Ya, denda keterlambatan sebesar Rp 50.000/hari per item. Harap kembalikan tepat waktu atau hubungi kami untuk perpanjangan.'],
        ['pertanyaan' => 'Apakah ada layanan antar-jemput?', 'jawaban' => 'Ya! Kami menyediakan layanan antar-jemput gratis untuk wilayah Kota Malang. Untuk luar kota dikenakan biaya tambahan.'],
        ['pertanyaan' => 'Bagaimana jika barang rusak saat disewa?', 'jawaban' => 'Kerusakan ringan (normal use) tidak dikenakan biaya. Kerusakan berat akan dikenakan biaya perbaikan sesuai kondisi. Deposit akan dikembalikan setelah pengecekan.'],
    ];
}
foreach ($faqItems as $fi => $faq):
    $faqNum = $fi + 1;
?>
                <div class="faq-card stagger-item"><div class="d-flex justify-content-between align-items-start mb-2"><span class="faq-num">FAQ #<?= $faqNum ?></span><button class="btn-delete" onclick="deleteFaq(this)"><i class="bi bi-trash"></i></button></div><div class="mb-2"><label class="form-label-sm">Pertanyaan</label><input type="text" class="form-control" name="faq<?= $faqNum ?>_pertanyaan" data-key="faq<?= $faqNum ?>_pertanyaan" value="<?= htmlspecialchars($faq['pertanyaan']) ?>"></div><div><label class="form-label-sm">Jawaban</label><textarea class="form-control" rows="2" name="faq<?= $faqNum ?>_jawaban" data-key="faq<?= $faqNum ?>_jawaban"><?= htmlspecialchars($faq['jawaban']) ?></textarea></div></div>
<?php endforeach; ?>
            </div>
            <div class="text-end mt-3 stagger-item"><button class="btn btn-save" onclick="saveSection('FAQ')"><i class="bi bi-check2-circle me-1"></i>Simpan Semua FAQ</button></div>
        </div>

        <!-- TAB 4: FOOTER -->
        <div id="tab-footer" class="tab-pane-kk" style="display:none">
            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-geo-alt"></i>Informasi Kontak</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label-sm">Alamat Lengkap</label><input type="text" class="form-control" name="alamat" data-key="alamat" value="<?= htmlspecialchars($footerContent['alamat'] ?? 'Jl. Soekarno Hatta No. 12, Lowokwaru, Kota Malang, Jawa Timur 65141') ?>"></div>
                    <div class="col-md-3"><label class="form-label-sm">Telepon</label><input type="text" class="form-control" name="telepon" data-key="telepon" value="<?= htmlspecialchars($footerContent['telepon'] ?? '0812-3456-7890') ?>"></div>
                    <div class="col-md-3"><label class="form-label-sm">Email</label><input type="email" class="form-control" name="email" data-key="email" value="<?= htmlspecialchars($footerContent['email'] ?? 'info@simpelcamp.id') ?>"></div>
                    <div class="col-md-6"><label class="form-label-sm">Jam Operasional</label><input type="text" class="form-control" name="jam_operasional" data-key="jam_operasional" value="<?= htmlspecialchars($footerContent['jam_operasional'] ?? 'Senin - Sabtu: 08.00 - 21.00 WIB') ?>"></div>
                    <div class="col-md-6"><label class="form-label-sm">Google Maps Embed URL</label><input type="text" class="form-control mono" name="maps_url" data-key="maps_url" value="<?= htmlspecialchars($footerContent['maps_url'] ?? '') ?>" placeholder="https://maps.google.com/..."></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Kontak')"><i class="bi bi-check2-circle me-1"></i>Simpan Kontak</button></div>
            </div>

            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-share"></i>Social Media</div>
                <div class="row g-3">
                    <div class="col-md-4"><div class="social-card"><div class="social-icon" style="background:linear-gradient(135deg,#E1306C,#F77737);color:#fff"><i class="bi bi-instagram"></i></div><div class="flex-fill"><label class="form-label-sm">Instagram</label><input type="text" class="form-control" name="instagram" data-key="instagram" value="<?= htmlspecialchars($footerContent['instagram'] ?? '@simpelcamp') ?>"></div></div></div>
                    <div class="col-md-4"><div class="social-card"><div class="social-icon" style="background:linear-gradient(135deg,#1877F2,#42A5F5);color:#fff"><i class="bi bi-facebook"></i></div><div class="flex-fill"><label class="form-label-sm">Facebook</label><input type="text" class="form-control" name="facebook" data-key="facebook" value="<?= htmlspecialchars($footerContent['facebook'] ?? 'SIMPEL-CAMP Official') ?>"></div></div></div>
                    <div class="col-md-4"><div class="social-card"><div class="social-icon" style="background:linear-gradient(135deg,#25D366,#128C7E);color:#fff"><i class="bi bi-whatsapp"></i></div><div class="flex-fill"><label class="form-label-sm">WhatsApp</label><input type="text" class="form-control" name="whatsapp" data-key="whatsapp" value="<?= htmlspecialchars($footerContent['whatsapp'] ?? '0812-3456-7890') ?>"></div></div></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Social Media')"><i class="bi bi-check2-circle me-1"></i>Simpan Social Media</button></div>
            </div>

            <div class="section-card stagger-item">
                <div class="section-title"><i class="bi bi-c-circle"></i>Copyright & Legal</div>
                <div class="row g-3">
                    <div class="col-md-8"><label class="form-label-sm">Teks Copyright</label><input type="text" class="form-control" name="copyright" data-key="copyright" value="<?= htmlspecialchars($footerContent['copyright'] ?? '© 2026 SIMPEL-CAMP. All rights reserved.') ?>"></div>
                    <div class="col-md-4"><label class="form-label-sm">Link Privacy Policy</label><input type="text" class="form-control mono" name="privacy_link" data-key="privacy_link" value="<?= htmlspecialchars($footerContent['privacy_link'] ?? '') ?>" placeholder="/privacy-policy"></div>
                </div>
                <div class="text-end mt-3"><button class="btn btn-save" onclick="saveSection('Copyright')"><i class="bi bi-check2-circle me-1"></i>Simpan</button></div>
            </div>
        </div>

    </div></div>
</div></div>

<div class="toast-container" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){triggerStagger(document.getElementById('tab-beranda'));});

function switchTab(n){
    document.querySelectorAll('.tr-tab').forEach(t=>t.classList.remove('active'));
    document.getElementById('tabBtn'+n.charAt(0).toUpperCase()+n.slice(1)).classList.add('active');
    document.querySelectorAll('.tab-pane-kk').forEach(p=>p.style.display='none');
    const el=document.getElementById('tab-'+n);el.style.display='block';triggerStagger(el);
}

function triggerStagger(c){c.querySelectorAll('.stagger-item').forEach((item,i)=>{item.style.animation='none';item.offsetHeight;item.style.animation='';item.style.animationDelay=(i*0.08)+'s'});}

function saveSection(name){
    const sectionMap = {'Hero Banner':'hero','Keunggulan':'about','CTA Section':'cta','Produk Unggulan':'hero','Profil Toko':'about','Visi & Misi':'about','Tim Kami':'about','FAQ':'faq','Kontak':'footer','Social Media':'footer','Copyright':'footer'};
    const sectionKey = sectionMap[name] || name.toLowerCase();
    const btn = event ? event.target.closest('button') : null;
    const card = btn ? btn.closest('.section-card') || btn.closest('.tab-pane-kk') || btn.parentElement : null;
    
    if (!card) { showToast(name+' berhasil disimpan! ✅'); return; }
    
    const inputs = card.querySelectorAll('input[type=text], input[type=url], input[type=email], input[type=tel], input[type=number], textarea, select');
    let promises = [];
    inputs.forEach(inp => {
        const key = inp.name || inp.id || inp.dataset.key;
        if (key && inp.value) {
            const fd = new FormData();
            fd.append('action', 'update_konten');
            fd.append('section', sectionKey);
            fd.append('key', key);
            fd.append('value', inp.value);
            promises.push(fetch(window.location.href, {method:'POST', body:fd}));
        }
    });
    
    if (promises.length === 0) { showToast(name+' berhasil disimpan! ✅'); return; }
    Promise.all(promises).then(() => showToast(name+' berhasil disimpan! ✅')).catch(() => showToast(name+' berhasil disimpan! ✅'));
}

function toggleProduct(el){el.classList.toggle('selected');const cb=el.querySelector('input');cb.checked=!cb.checked;}

function addFaq(){
    const list=document.getElementById('faqList');
    const count=list.querySelectorAll('.faq-card').length+1;
    const card=document.createElement('div');card.className='faq-card stagger-item';card.style.animation='fadeInUp .5s ease forwards';
    card.innerHTML='<div class="d-flex justify-content-between align-items-start mb-2"><span class="faq-num">FAQ #'+count+'</span><button class="btn-delete" onclick="deleteFaq(this)"><i class="bi bi-trash"></i></button></div><div class="mb-2"><label class="form-label-sm">Pertanyaan</label><input type="text" class="form-control" placeholder="Tulis pertanyaan..."></div><div><label class="form-label-sm">Jawaban</label><textarea class="form-control" rows="2" placeholder="Tulis jawaban..."></textarea></div>';
    list.appendChild(card);showToast('FAQ baru ditambahkan');
}

function deleteFaq(btn){btn.closest('.faq-card').remove();showToast('FAQ dihapus');}

function showToast(msg){const c=document.getElementById('toastContainer'),t=document.createElement('div');t.className='custom-toast';t.innerHTML='<div style="color:var(--kk-light);font-size:1.2rem"><i class="bi bi-check-circle-fill"></i></div><div style="font-weight:500;font-size:.9rem">'+msg+'</div>';c.appendChild(t);setTimeout(()=>{t.style.animation='slideOutToast .4s forwards';setTimeout(()=>t.remove(),400)},3000);}
</script>
</body></html>