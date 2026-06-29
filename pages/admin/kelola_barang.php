<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/Kategori.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . '/login.php'); exit; }
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$page_title = 'Kelola Barang';
$current_page = 'kelola_barang';

$barangList = Barang::getAll(['limit' => 100]);
$kategoriList = Kategori::getAll();
$totalBarang = count($barangList);
$tersedia = 0; $disewa = 0;
foreach ($barangList as $b) {
    $stok = (int)($b['stok_tersedia'] ?? 0);
    if ($stok > 0) $tersedia++; else $disewa++;
}
$adminName = $_SESSION['nama'] ?? 'Admin';

// Fetch pengaturan denda
$db = Database::getInstance();
$stmtSet = $db->query("SELECT `key`, `value` FROM pengaturan WHERE `key` IN ('denda_rusak_ringan_persen', 'denda_rusak_berat_persen', 'denda_hilang_persen', 'denda_per_hari_persen')");
$settings = [];
while ($row = $stmtSet->fetch()) {
    $settings[$row['key']] = floatval($row['value']);
}
$denda_ringan = $settings['denda_rusak_ringan_persen'] ?? 25;
$denda_berat  = $settings['denda_rusak_berat_persen'] ?? 50;
$denda_hilang = $settings['denda_hilang_persen'] ?? 100;
$denda_telat  = $settings['denda_per_hari_persen'] ?? 10;

$fallbackImages = [
    'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=400',
    'https://images.unsplash.com/photo-1622260614153-03f8c8e3e5d4?w=400',
    'https://images.unsplash.com/photo-1510672981848-a1c4f1cb5ccf?w=400',
    'https://images.unsplash.com/photo-1556909114-44e3e70034e2?w=400',
    'https://images.unsplash.com/photo-1520101244246-293f77ffc39e?w=400',
    'https://images.unsplash.com/photo-1567306226416-28f0efdc88ce?w=400',
];
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?> - <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781677663">
<style>
:root{--kb-dark:#1B4332;--kb-mid:#2D6A4F;--kb-light:#52B788;--kb-gold:#D4A373;--kb-bg:#f0f4f1}
body{font-family:'Inter',sans-serif;background:var(--kb-bg)}
h1,h2,h3,h4,h5,h6,.heading{font-family:'Outfit',sans-serif}
.mono{font-family:'JetBrains Mono',monospace}

/* Tabs */
.kb-tabs{display:flex;gap:6px;background:#fff;border-radius:14px;padding:6px;box-shadow:0 2px 12px rgba(27,67,50,0.06);margin-bottom:28px;flex-wrap:wrap}
.kb-tab{padding:10px 28px;border-radius:10px;border:none;background:transparent;font-weight:600;color:#6c757d;cursor:pointer;transition:all .3s;font-family:'Inter',sans-serif;font-size:.93rem}
.kb-tab.active{background:linear-gradient(135deg,var(--kb-mid),var(--kb-light));color:#fff;box-shadow:0 4px 16px rgba(45,106,79,0.3)}
.kb-tab:hover:not(.active){background:rgba(82,183,136,0.08);color:var(--kb-mid)}

/* Stat Cards */
.stat-mini{background:#fff;border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:16px;box-shadow:0 2px 12px rgba(0,0,0,0.04);transition:transform .3s,box-shadow .3s;border-left:4px solid var(--kb-light)}
.stat-mini:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.08)}
.stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.stat-icon.green{background:rgba(82,183,136,0.12);color:var(--kb-light)}
.stat-icon.blue{background:rgba(59,130,246,0.1);color:#3b82f6}
.stat-icon.orange{background:rgba(249,115,22,0.1);color:#f97316}
.stat-val{font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:700;color:var(--kb-dark)}
.stat-label{font-size:.82rem;color:#6c757d}

/* Action Bar */
.action-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:24px}
.search-box{position:relative;flex:1;min-width:220px}
.search-box input{border-radius:50px;padding:10px 18px 10px 42px;border:1.5px solid #e0e0e0;width:100%;font-size:.9rem;transition:border-color .3s,box-shadow .3s}
.search-box input:focus{border-color:var(--kb-light);box-shadow:0 0 0 3px rgba(82,183,136,0.15);outline:none}
.search-box i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#adb5bd}
.filter-select{border-radius:10px;padding:10px 16px;border:1.5px solid #e0e0e0;font-size:.9rem;min-width:160px;transition:border-color .3s}
.filter-select:focus{border-color:var(--kb-light);box-shadow:0 0 0 3px rgba(82,183,136,0.15);outline:none}
.btn-gradient{background:linear-gradient(135deg,var(--kb-mid),var(--kb-light));color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:600;font-size:.9rem;transition:all .3s;box-shadow:0 4px 14px rgba(45,106,79,0.25)}
.btn-gradient:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(45,106,79,0.35);color:#fff}

/* Product Cards */
.product-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.05);transition:all .35s cubic-bezier(.4,0,.2,1);cursor:pointer;height:100%;display:flex;flex-direction:column}
.product-card:hover{transform:translateY(-8px);box-shadow:0 16px 40px rgba(27,67,50,0.12)}
.product-card .card-img{width:100%;height:200px;object-fit:cover}
.product-card .card-body{padding:18px;flex:1;display:flex;flex-direction:column}
.product-card .card-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:1.05rem;color:var(--kb-dark);margin-bottom:8px}
.badge-category{background:rgba(82,183,136,0.1);color:var(--kb-mid);font-weight:500;font-size:.75rem;padding:4px 12px;border-radius:50px}
.product-price{font-family:'JetBrains Mono',monospace;font-weight:500;color:var(--kb-mid);font-size:.95rem;margin:10px 0}
.badge-stock{font-size:.75rem;padding:4px 12px;border-radius:50px;font-weight:600}
.badge-stock.available{background:rgba(82,183,136,0.1);color:#16a34a}
.badge-stock.empty{background:rgba(239,68,68,0.1);color:#dc2626}
.card-actions{margin-top:auto;padding-top:14px;border-top:1px solid #f0f0f0;display:flex;gap:8px}
.card-actions .btn{flex:1;border-radius:8px;font-size:.82rem;font-weight:600;padding:7px 0}
.btn-outline-green{border:1.5px solid var(--kb-light);color:var(--kb-mid)}
.btn-outline-green:hover{background:var(--kb-light);color:#fff}
.btn-outline-red{border:1.5px solid #ef4444;color:#ef4444}
.btn-outline-red:hover{background:#ef4444;color:#fff}

/* Kategori Cards */
.kategori-card{background:#fff;border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:16px;box-shadow:0 2px 10px rgba(0,0,0,0.04);transition:all .3s;margin-bottom:12px}
.kategori-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.08);transform:translateY(-3px)}
.kat-icon{width:44px;height:44px;border-radius:12px;background:rgba(82,183,136,0.1);display:flex;align-items:center;justify-content:center;color:var(--kb-mid);font-size:1.3rem;flex-shrink:0}
.kat-name{font-weight:600;color:var(--kb-dark);font-size:1rem;font-family:'Outfit',sans-serif}
.kat-count{background:rgba(82,183,136,0.1);color:var(--kb-mid);font-size:.78rem;padding:3px 12px;border-radius:50px;font-weight:600}
.kat-actions{margin-left:auto;display:flex;gap:6px;opacity:0;transition:opacity .3s}
.kategori-card:hover .kat-actions{opacity:1}
.kat-actions .btn{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0;font-size:.9rem}

/* Modals */
.modal-content{border-radius:16px;border:none;overflow:hidden}
.modal-header.green-header{background:linear-gradient(135deg,var(--kb-mid),var(--kb-light));color:#fff;border:none;padding:18px 24px}
.modal-header.green-header .btn-close{filter:brightness(0) invert(1)}
.modal-header.red-header{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;padding:18px 24px}
.modal-header.red-header .btn-close{filter:brightness(0) invert(1)}
.modal-body{padding:24px}
.modal-footer{border-top:1px solid #f0f0f0;padding:16px 24px}
.form-control,.form-select{border-radius:10px;padding:10px 14px;border:1.5px solid #e0e0e0;transition:border-color .3s,box-shadow .3s}
.form-control:focus,.form-select:focus{border-color:var(--kb-light);box-shadow:0 0 0 3px rgba(82,183,136,0.15)}
.form-label{font-weight:600;font-size:.85rem;color:#495057}

/* Toast */
.toast-container{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px}
.custom-toast{background:#fff;border-radius:12px;padding:14px 20px;box-shadow:0 8px 30px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;transform:translateX(120%);animation:slideInToast .4s forwards;min-width:300px;border-left:4px solid var(--kb-light)}
.custom-toast.error{border-left-color:#ef4444}
.custom-toast .toast-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.custom-toast .toast-icon.success{background:rgba(82,183,136,0.12);color:var(--kb-light)}
.custom-toast .toast-icon.error{background:rgba(239,68,68,0.1);color:#ef4444}
.custom-toast .toast-msg{font-weight:500;font-size:.88rem;color:#333}
@keyframes slideInToast{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes slideOutToast{from{transform:translateX(0);opacity:1}to{transform:translateX(120%);opacity:0}}

/* Animations */
@keyframes fadeInUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.stagger-item{opacity:0;animation:fadeInUp .5s ease forwards}

/* Icon Preview */
.icon-preview-box{width:56px;height:56px;border-radius:14px;background:rgba(82,183,136,0.1);display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:var(--kb-mid)}

/* Drag & Drop File Upload */
.dropzone-area { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 24px; text-align: center; transition: all 0.3s; background: #f8fafc; cursor: pointer; position: relative; overflow: hidden; min-height: 160px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.dropzone-area:hover, .dropzone-area.dragover { border-color: var(--kb-light); background: rgba(82,183,136,0.05); }
.dropzone-area i { font-size: 2rem; color: #94a3b8; margin-bottom: 8px; transition: color 0.3s; }
.dropzone-area:hover i { color: var(--kb-light); }
.dropzone-area .dropzone-text { font-size: 0.9rem; color: #64748b; font-weight: 500; }
.dropzone-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; z-index: 10; }
.dropzone-area img { max-width: 100%; max-height: 200px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 5; }
.dropzone-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; opacity: 0; transition: opacity 0.3s; z-index: 6; border-radius: 12px; font-weight: 600; }
.dropzone-area:hover .dropzone-overlay { opacity: 1; }
.dropzone-remove { margin-top: 8px; font-size: 0.8rem; color: #ef4444; cursor: pointer; font-weight: 600; display: inline-block; z-index: 11; position: relative; }

/* Detail Modal */
.detail-img{width:100%;height:260px;object-fit:cover;border-radius:12px;margin-bottom:16px}
.detail-info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0}
.detail-info-row:last-child{border:none}
.detail-label{color:#6c757d;font-size:.85rem}
.detail-value{font-weight:600;color:var(--kb-dark)}

/* Responsive */
@media(max-width:576px){
    .kb-tabs{padding:4px}.kb-tab{padding:8px 16px;font-size:.82rem}
    .action-bar{flex-direction:column}
    .stat-mini{padding:14px 16px}
}
</style>
</head><body>
<div class="admin-wrapper">
<?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


            <div class="admin-content">
            <div class="container-fluid stagger-in">
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="stat-mini stagger-item" style="border-left-color:#10b981"><div class="stat-icon green"><i class="bi bi-box-seam"></i></div><div><div class="stat-val"><?= $totalBarang ?></div><div class="stat-label">Total Barang</div></div></div></div>
                <div class="col-md-4"><div class="stat-mini stagger-item" style="border-left-color:#3b82f6"><div class="stat-icon blue"><i class="bi bi-check-circle"></i></div><div><div class="stat-val"><?= $tersedia ?></div><div class="stat-label">Tersedia</div></div></div></div>
                <div class="col-md-4"><div class="stat-mini stagger-item" style="border-left-color:#f97316"><div class="stat-icon orange"><i class="bi bi-arrow-repeat"></i></div><div><div class="stat-val"><?= $disewa ?></div><div class="stat-label">Stok Habis</div></div></div></div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchBarang" placeholder="Cari barang..." onkeyup="filterBarang()">
                </div>
                <select class="filter-select" id="filterKategori" onchange="filterBarang()">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategoriList as $kat): ?>
                    <option value="<?= htmlspecialchars($kat['nama'] ?? '') ?>"><?= htmlspecialchars($kat['nama'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-gradient" onclick="openAddBarang()"><i class="bi bi-plus-lg me-2"></i>Tambah Barang</button>
            </div>

            <!-- Product Grid -->
            <div class="row g-4" id="productGrid">
                <?php foreach ($barangList as $idx => $item):
                    $imgUrl = !empty($item['gambar']) ? ASSETS_URL . '/img/barang/' . $item['gambar'] : $fallbackImages[$idx % count($fallbackImages)];
                    $nama = $item['nama'] ?? '-';
                    $kategori = $item['kategori_nama'] ?? '-';
                    $harga = (int)($item['harga_per_hari'] ?? 0);
                    $stok = (int)($item['stok_tersedia'] ?? 0);
                    $id = $item['id'] ?? $idx + 1;
                ?>
                <div class="col-lg-4 col-md-6 product-col stagger-item" data-name="<?= htmlspecialchars($nama) ?>" data-category="<?= htmlspecialchars($kategori) ?>">
                    <div class="product-card" onclick="openDetailBarang(<?= $id ?>)">
                        <img src="<?= htmlspecialchars($imgUrl) ?>" class="card-img" alt="<?= htmlspecialchars($nama) ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between mb-1">
                                <h6 class="card-title mb-0"><?= htmlspecialchars($nama) ?></h6>
                                <span class="badge-category"><?= htmlspecialchars($kategori) ?></span>
                            </div>
                            <div class="product-price">Rp <?= number_format($harga,0,',','.') ?><span style="font-family:Inter;font-size:.78rem;color:#999">/hari</span></div>
                            <span class="badge-stock <?= $stok > 0 ? 'available' : 'empty' ?>"><i class="bi bi-circle-fill me-1" style="font-size:.5rem"></i><?= $stok > 0 ? 'Stok: '.$stok : 'Habis' ?></span>
                            <div class="card-actions" onclick="event.stopPropagation()">
                                <button class="btn btn-outline-green" onclick="openEditBarang(<?= $id ?>)"><i class="bi bi-pencil me-1"></i>Edit</button>
                                <button class="btn btn-outline-red" onclick="confirmDelete(<?= $id ?>,'<?= htmlspecialchars(addslashes($nama)) ?>')"><i class="bi bi-trash me-1"></i>Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($barangList)): ?>
                <div class="col-12"><p class="text-muted text-center py-4">Belum ada data barang</p></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══════ TAB 2: KATEGORI ══════ -->
        <div id="tab-kategori" class="tab-pane-kb" style="display:none">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="heading fw-bold mb-0">Daftar Kategori</h5>
                <button class="btn btn-gradient" onclick="openAddKategori()"><i class="bi bi-plus-lg me-2"></i>Tambah Kategori</button>
            </div>

            <div id="kategoriList">
                <?php foreach ($kategoriList as $kat): ?>
                <div class="kategori-card stagger-item" data-kid="<?= $kat['id'] ?>">
                    <div class="kat-icon"><i class="bi <?= htmlspecialchars($kat['icon'] ?? 'bi-box') ?>"></i></div>
                    <div><div class="kat-name"><?= htmlspecialchars($kat['nama']) ?></div><div class="text-muted small"><?= htmlspecialchars($kat['deskripsi'] ?? '') ?></div></div>
                    <span class="kat-count ms-auto me-3"><?= $kat['jumlah_barang'] ?? 0 ?> item</span>
                    <div class="kat-actions">
                        <button class="btn btn-outline-green" onclick="openEditKategori(<?= $kat['id'] ?>,'<?= htmlspecialchars($kat['nama'], ENT_QUOTES) ?>','<?= htmlspecialchars($kat['icon'] ?? 'bi-box') ?>')"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-outline-red" onclick="deleteKategori(<?= $kat['id'] ?>,'<?= htmlspecialchars($kat['nama'], ENT_QUOTES) ?>')"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /admin-content -->
</div><!-- /admin-main -->
</div><!-- /admin-wrapper -->

<!-- ══════ MODALS ══════ -->

<!-- Add/Edit Barang Modal -->
<div class="modal fade" id="modalBarang" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
    <div class="modal-header green-header">
        <h5 class="modal-title heading" id="modalBarangTitle"><i class="bi bi-plus-circle me-2"></i>Tambah Barang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <input type="hidden" id="barangId">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Foto Barang</label>
                <div class="dropzone-area" id="dropzoneArea" onclick="document.getElementById('barangGambar').click()">
                    <div id="dropzoneContent">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <div class="dropzone-text">Tarik & Lepas foto ke sini atau klik untuk memilih</div>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">Maksimal 2MB (JPG, PNG, WebP)</div>
                    </div>
                    <img src="" id="imgPreview" alt="Preview" style="display:none;">
                    <div class="dropzone-overlay" id="dropzoneOverlay" style="display:none;">
                        <i class="bi bi-pencil-square me-2" style="color:white;"></i> Ganti Foto
                    </div>
                    <input type="file" id="barangGambar" accept="image/jpeg, image/png, image/webp" onchange="previewImage(this)">
                </div>
                <div class="text-center" id="removePhotoWrapper" style="display:none;">
                    <span class="dropzone-remove" onclick="removeImage(event)"><i class="bi bi-trash me-1"></i>Hapus Foto</span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nama Barang</label>
                <input type="text" class="form-control" id="barangNama" placeholder="Masukkan nama barang">
            </div>
            <div class="col-md-6">
                <label class="form-label">Kategori</label>
                <select class="form-select" id="barangKategori">
                    <option value="">Pilih Kategori</option>
                    <?php foreach ($kategoriList as $kat): ?>
                    <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Harga / Hari (Rp)</label>
                <input type="number" class="form-control mono" id="barangHarga" placeholder="0" oninput="calculateLiveDenda()">
            </div>
            <div class="col-md-6">
                <label class="form-label">Harga Dasar / Nilai Ganti (Rp)</label>
                <input type="number" class="form-control mono" id="barangHargaDenda" placeholder="0" oninput="calculateLiveDenda()">
            </div>
            <div class="col-12" id="liveDendaPreview" style="display:none;">
                <div class="p-3" style="background:#fff4f4; border-radius:12px; border:1px solid #ffcdd2;">
                    <h6 class="fw-bold mb-2" style="color:#d32f2f; font-size:0.85rem;"><i class="bi bi-info-circle me-1"></i>Rincian Denda Barang</h6>
                    <div class="row g-2" style="font-size:0.8rem;">
                        <div class="col-6"><span class="text-muted">Terlambat:</span> <strong class="mono" id="liveDendaTelat">Rp 0</strong><span class="text-muted">/hr</span></div>
                        <div class="col-6"><span class="text-muted">Rusak Ringan:</span> <strong class="mono" id="liveDendaRingan">Rp 0</strong></div>
                        <div class="col-6"><span class="text-muted">Rusak Berat:</span> <strong class="mono" id="liveDendaBerat">Rp 0</strong></div>
                        <div class="col-6"><span class="text-muted">Hilang:</span> <strong class="mono text-danger" id="liveDendaHilang">Rp 0</strong></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Stok</label>
                <input type="number" class="form-control" id="barangStok" placeholder="0">
            </div>
            <div class="col-12">
                <label class="form-label">Deskripsi</label>
                <textarea class="form-control" id="barangDeskripsi" rows="3" placeholder="Deskripsi singkat barang..."></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Status</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="barangStatus" checked style="width:3em;height:1.5em">
                    <label class="form-check-label ms-2" for="barangStatus" id="statusLabel">Aktif</label>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-gradient" onclick="saveBarang()"><i class="bi bi-check-lg me-2"></i>Simpan</button>
    </div>
</div></div></div>

<!-- Detail Barang Modal -->
<div class="modal fade" id="modalDetail" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
    <div class="modal-header green-header">
        <h5 class="modal-title heading"><i class="bi bi-eye me-2"></i>Detail Barang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" id="detailContent">
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
    </div>
</div></div></div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="modalHapus" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-sm">
<div class="modal-content">
    <div class="modal-header red-header">
        <h5 class="modal-title heading"><i class="bi bi-exclamation-triangle me-2"></i>Hapus Barang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body text-center py-4">
        <i class="bi bi-trash text-danger" style="font-size:3rem"></i>
        <p class="mt-3 mb-0">Yakin ingin menghapus <strong id="deleteItemName"></strong>?</p>
        <p class="text-muted small">Tindakan ini tidak dapat dibatalkan.</p>
        <input type="hidden" id="deleteItemId">
    </div>
    <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" onclick="deleteBarang()"><i class="bi bi-trash me-1"></i>Hapus</button>
    </div>
</div></div></div>

<!-- Add/Edit Kategori Modal -->
<div class="modal fade" id="modalKategori" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
    <div class="modal-header green-header">
        <h5 class="modal-title heading" id="modalKategoriTitle"><i class="bi bi-plus-circle me-2"></i>Tambah Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <input type="hidden" id="kategoriId">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Nama Kategori</label>
                <input type="text" class="form-control" id="kategoriNama" placeholder="Masukkan nama kategori">
            </div>
            <div class="col-12">
                <label class="form-label">Icon Bootstrap</label>
                <div class="d-flex gap-3 align-items-end">
                    <div class="flex-grow-1">
                        <select class="form-select" id="kategoriIcon" onchange="updateIconPreview()">
                            <option value="bi-house">bi-house (Rumah)</option>
                            <option value="bi-backpack">bi-backpack (Tas)</option>
                            <option value="bi-moon-stars">bi-moon-stars (Tidur)</option>
                            <option value="bi-fire">bi-fire (Api)</option>
                            <option value="bi-lightbulb">bi-lightbulb (Lampu)</option>
                            <option value="bi-tree">bi-tree (Pohon)</option>
                            <option value="bi-compass">bi-compass (Kompas)</option>
                            <option value="bi-water">bi-water (Air)</option>
                            <option value="bi-tools">bi-tools (Alat)</option>
                            <option value="bi-umbrella">bi-umbrella (Payung)</option>
                        </select>
                    </div>
                    <div class="icon-preview-box" id="iconPreview"><i class="bi bi-house"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-gradient" onclick="saveKategori()"><i class="bi bi-check-lg me-2"></i>Simpan</button>
    </div>
</div></div></div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
const DENDA_SETTINGS = {
    telat: <?= $denda_telat ?>,
    ringan: <?= $denda_ringan ?>,
    berat: <?= $denda_berat ?>,
    hilang: <?= $denda_hilang ?>
};

// ── Data from Database ──
const barangData = <?= json_encode(array_reduce($barangList, function($carry, $item) use ($fallbackImages) {
    static $idx = 0;
    $id = $item['id'] ?? $idx + 1;
    $carry[$id] = [
        'id' => $id,
        'name' => $item['nama'] ?? '-',
        'category' => $item['kategori_nama'] ?? '-',
        'category_id' => $item['kategori_id'] ?? 0,
        'price' => (int)($item['harga_per_hari'] ?? 0),
        'denda' => (int)($item['harga_denda'] ?? 0),
        'stock' => (int)($item['stok_tersedia'] ?? 0),
        'image' => !empty($item['gambar']) ? ASSETS_URL . '/img/barang/' . $item['gambar'] : $fallbackImages[$idx % count($fallbackImages)],
        'desc' => $item['deskripsi'] ?? '-',
    ];
    $idx++;
    return $carry;
}, [])) ?>;

// ── Tab Switching ──
function switchTab(btn, tabId) {
    document.querySelectorAll('.kb-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.tab-pane-kb').forEach(p => p.style.display = 'none');
    document.getElementById(tabId).style.display = 'block';
    // Re-trigger stagger animation
    triggerStagger(document.getElementById(tabId));
}

// ── Stagger Animation ──
function triggerStagger(container) {
    const items = container.querySelectorAll('.stagger-item');
    items.forEach((item, i) => {
        item.style.animation = 'none';
        item.offsetHeight; // trigger reflow
        item.style.animation = '';
        item.style.animationDelay = (i * 0.1) + 's';
    });
}

// ── Toast ──
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'custom-toast' + (type === 'error' ? ' error' : '');
    toast.innerHTML = `
        <div class="toast-icon ${type}"><i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'}"></i></div>
        <div class="toast-msg">${message}</div>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOutToast .4s forwards';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// ── Barang CRUD ──
let modalBarangBS;
let modalDetailBS;
let modalHapusBS;
let modalKategoriBS;

document.addEventListener('DOMContentLoaded', function() {
    modalBarangBS = new bootstrap.Modal(document.getElementById('modalBarang'));
    modalDetailBS = new bootstrap.Modal(document.getElementById('modalDetail'));
    modalHapusBS = new bootstrap.Modal(document.getElementById('modalHapus'));
    modalKategoriBS = new bootstrap.Modal(document.getElementById('modalKategori'));
    triggerStagger(document.getElementById('tab-barang'));

    // Status toggle label
    document.getElementById('barangStatus').addEventListener('change', function() {
        document.getElementById('statusLabel').textContent = this.checked ? 'Aktif' : 'Nonaktif';
    });

    // Drag & drop logic
    const dropzone = document.getElementById('dropzoneArea');
    const fileInput = document.getElementById('barangGambar');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.add('dragover'), false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.remove('dragover'), false);
    });
    dropzone.addEventListener('drop', (e) => {
        let dt = e.dataTransfer;
        let files = dt.files;
        if(files.length > 0) {
            fileInput.files = files;
            previewImage(fileInput);
        }
    }, false);
});

function openAddBarang() {
    document.getElementById('modalBarangTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Tambah Barang';
    document.getElementById('barangId').value = '';
    removeImage(new Event('click')); // Reset image
    document.getElementById('barangNama').value = '';
    document.getElementById('barangKategori').value = '';
    document.getElementById('barangHarga').value = '';
    document.getElementById('barangHargaDenda').value = '';
    document.getElementById('barangStok').value = '';
    document.getElementById('barangDeskripsi').value = '';
    document.getElementById('barangStatus').checked = true;
    calculateLiveDenda();
    modalBarangBS.show();
}

function openEditBarang(id) {
    const item = barangData[id];
    if (!item) return;
    document.getElementById('modalBarangTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Barang';
    document.getElementById('barangId').value = id;
    
    // Set existing image
    removeImage(new Event('click'));
    if (item.has_image) {
        document.getElementById('dropzoneContent').style.display = 'none';
        document.getElementById('imgPreview').style.display = 'block';
        document.getElementById('imgPreview').src = item.image;
        document.getElementById('dropzoneOverlay').style.display = 'flex';
        document.getElementById('removePhotoWrapper').style.display = 'block';
    }

    document.getElementById('barangNama').value = item.name;
    document.getElementById('barangKategori').value = item.category_id || '';
    document.getElementById('barangHarga').value = item.price;
    document.getElementById('barangHargaDenda').value = item.denda;
    document.getElementById('barangStok').value = item.stock;
    document.getElementById('barangDeskripsi').value = item.desc;
    document.getElementById('barangStatus').checked = true;
    calculateLiveDenda();
    modalBarangBS.show();
}

// ── Save Barang → API ──
function saveBarang() {
    const id = document.getElementById('barangId').value;
    const nama = document.getElementById('barangNama').value.trim();
    if (!nama) { showToast('Nama barang harus diisi!', 'error'); return; }

    const kategoriId = document.getElementById('barangKategori').value;
    if (!kategoriId) { showToast('Kategori harus dipilih!', 'error'); return; }

    const harga = document.getElementById('barangHarga').value;
    if (!harga || parseFloat(harga) <= 0) { showToast('Harga harus lebih dari 0!', 'error'); return; }

    const action = id ? 'update' : 'create';
    const fd = new FormData();
    if (id) fd.append('id', id);
    fd.append('nama', nama);
    fd.append('kategori_id', kategoriId);
    fd.append('harga_per_hari', harga);
    fd.append('harga_denda', document.getElementById('barangHargaDenda').value || '0');
    fd.append('stok_total', document.getElementById('barangStok').value || '0');
    fd.append('deskripsi', document.getElementById('barangDeskripsi').value);
    fd.append('status', document.getElementById('barangStatus').checked ? 'tersedia' : 'maintenance');

    const fileInput = document.getElementById('barangGambar');
    if (fileInput.files.length > 0) {
        fd.append('gambar', fileInput.files[0]);
    }

    fetch(BASE_URL + '/api/barang.php?action=' + action, { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        if (res.success) {
            modalBarangBS.hide();
            showToast(id ? `Barang "${nama}" berhasil diperbarui!` : `Barang "${nama}" berhasil ditambahkan!`);
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(res.message || 'Gagal menyimpan barang', 'error');
        }
    }).catch(() => showToast('Terjadi kesalahan jaringan', 'error'));
}

function confirmDelete(id, name) {
    document.getElementById('deleteItemId').value = id;
    document.getElementById('deleteItemName').textContent = name;
    modalHapusBS.show();
}

// ── Delete Barang → API ──
function deleteBarang() {
    const id = document.getElementById('deleteItemId').value;
    const name = document.getElementById('deleteItemName').textContent;

    const fd = new FormData();
    fd.append('id', id);

    fetch(BASE_URL + '/api/barang.php?action=delete', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        modalHapusBS.hide();
        if (res.success) {
            // Remove card from grid with animation
            const cards = document.querySelectorAll('.product-col');
            cards.forEach(c => {
                if (c.querySelector('.card-title') && c.querySelector('.card-title').textContent === name) {
                    c.style.animation = 'fadeInUp .4s ease reverse forwards';
                    setTimeout(() => c.remove(), 400);
                }
            });
            showToast(`Barang "${name}" berhasil dihapus!`);
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(res.message || 'Gagal menghapus barang', 'error');
        }
    }).catch(() => { modalHapusBS.hide(); showToast('Terjadi kesalahan jaringan', 'error'); });
}

function openDetailBarang(id) {
    const item = barangData[id];
    if (!item) return;

    const dendaRingan = item.denda * (DENDA_SETTINGS.ringan / 100);
    const dendaBerat = item.denda * (DENDA_SETTINGS.berat / 100);
    const dendaHilang = item.denda * (DENDA_SETTINGS.hilang / 100);
    const dendaTelat = item.price * (DENDA_SETTINGS.telat / 100);

    document.getElementById('detailContent').innerHTML = `
        <img src="${item.image}" class="detail-img" alt="${item.name}">
        <h5 class="heading fw-bold">${item.name}</h5>
        <div class="detail-info-row"><span class="detail-label">Kategori</span><span class="badge-category">${item.category}</span></div>
        <div class="detail-info-row"><span class="detail-label">Harga Sewa / Hari</span><span class="detail-value mono" style="color:var(--kb-mid)">Rp ${item.price.toLocaleString('id-ID')}</span></div>
        <div class="detail-info-row"><span class="detail-label">Stok</span><span class="badge-stock ${item.stock > 0 ? 'available' : 'empty'}">${item.stock > 0 ? 'Stok: ' + item.stock : 'Habis'}</span></div>
        <div class="detail-info-row"><span class="detail-label">Deskripsi</span><span class="detail-value" style="max-width:60%;text-align:right">${item.desc}</span></div>
        
        <div class="mt-4 p-3" style="background:#fff4f4; border-radius:12px; border:1px solid #ffcdd2;">
            <h6 class="fw-bold mb-3" style="color:#d32f2f; font-family:'Outfit',sans-serif;">
                <i class="bi bi-exclamation-triangle me-2"></i>Informasi Denda Barang
            </h6>
            <div class="detail-info-row border-0 py-1"><span class="detail-label text-dark">Harga Dasar (Nilai Ganti)</span><span class="detail-value mono text-danger">Rp ${item.denda.toLocaleString('id-ID')}</span></div>
            <hr class="my-2" style="border-color:#ffcdd2">
            <div class="detail-info-row border-0 py-1"><span class="detail-label">Terlambat (${DENDA_SETTINGS.telat}% harga sewa/hari)</span><span class="detail-value mono" style="font-size:0.9rem">Rp ${dendaTelat.toLocaleString('id-ID')} / hari</span></div>
            <div class="detail-info-row border-0 py-1"><span class="detail-label">Rusak Ringan (${DENDA_SETTINGS.ringan}% harga ganti)</span><span class="detail-value mono" style="font-size:0.9rem">Rp ${dendaRingan.toLocaleString('id-ID')}</span></div>
            <div class="detail-info-row border-0 py-1"><span class="detail-label">Rusak Berat (${DENDA_SETTINGS.berat}% harga ganti)</span><span class="detail-value mono" style="font-size:0.9rem">Rp ${dendaBerat.toLocaleString('id-ID')}</span></div>
            <div class="detail-info-row border-0 py-1"><span class="detail-label fw-bold">Hilang (${DENDA_SETTINGS.hilang}% harga ganti)</span><span class="detail-value mono fw-bold text-danger">Rp ${dendaHilang.toLocaleString('id-ID')}</span></div>
            <div class="mt-2 text-muted" style="font-size:0.75rem;">*Denda kerusakan dihitung dari harga dasar (nilai ganti) barang.</div>
        </div>
    `;
    modalDetailBS.show();
}

// ── Search & Filter ──
function decodeHtml(html) {
    const t = document.createElement('textarea');
    t.innerHTML = html;
    return t.value;
}
function filterBarang() {
    const search = document.getElementById('searchBarang').value.toLowerCase();
    const kategori = document.getElementById('filterKategori').value;
    document.querySelectorAll('.product-col').forEach(col => {
        const name = (col.dataset.name || '').toLowerCase();
        const cat = decodeHtml(col.dataset.category || '');
        const matchSearch = name.includes(search);
        const matchCat = !kategori || cat === kategori;
        col.style.display = (matchSearch && matchCat) ? '' : 'none';
    });
}

// ── Kategori CRUD ──
function openAddKategori() {
    document.getElementById('modalKategoriTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Tambah Kategori';
    document.getElementById('kategoriId').value = '';
    document.getElementById('kategoriNama').value = '';
    document.getElementById('kategoriIcon').value = 'bi-house';
    updateIconPreview();
    modalKategoriBS.show();
}

function openEditKategori(id, name, icon) {
    document.getElementById('modalKategoriTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Kategori';
    document.getElementById('kategoriId').value = id;
    document.getElementById('kategoriNama').value = name;
    document.getElementById('kategoriIcon').value = icon;
    updateIconPreview();
    modalKategoriBS.show();
}

// ── Save Kategori → API ──
function saveKategori() {
    const nama = document.getElementById('kategoriNama').value.trim();
    if (!nama) { showToast('Nama kategori harus diisi!', 'error'); return; }

    const id = document.getElementById('kategoriId').value;
    const icon = document.getElementById('kategoriIcon').value;
    const action = id ? 'update' : 'create';

    const fd = new FormData();
    if (id) fd.append('id', id);
    fd.append('nama', nama);
    fd.append('icon', icon);

    fetch(BASE_URL + '/api/kategori.php?action=' + action, { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        if (res.success) {
            modalKategoriBS.hide();
            showToast(id ? `Kategori "${nama}" berhasil diperbarui!` : `Kategori "${nama}" berhasil ditambahkan!`);
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(res.message || 'Gagal menyimpan kategori', 'error');
        }
    }).catch(() => showToast('Terjadi kesalahan jaringan', 'error'));
}

// ── Delete Kategori → API ──
function deleteKategori(id, name) {
    if (!confirm(`Hapus kategori "${name}"?`)) return;

    const fd = new FormData();
    fd.append('id', id);

    fetch(BASE_URL + '/api/kategori.php?action=delete', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        if (res.success) {
            const card = document.querySelector(`.kategori-card[data-kid="${id}"]`);
            if (card) {
                card.style.animation = 'fadeInUp .4s ease reverse forwards';
                setTimeout(() => card.remove(), 400);
            }
            showToast(`Kategori "${name}" berhasil dihapus!`);
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(res.message || 'Gagal menghapus kategori', 'error');
        }
    }).catch(() => showToast('Terjadi kesalahan jaringan', 'error'));
}

function updateIconPreview() {
    const icon = document.getElementById('kategoriIcon').value;
    document.getElementById('iconPreview').innerHTML = `<i class="bi ${icon}"></i>`;
}

// Image Preview logic
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('dropzoneContent').style.display = 'none';
            document.getElementById('imgPreview').style.display = 'block';
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('dropzoneOverlay').style.display = 'flex';
            document.getElementById('removePhotoWrapper').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
function removeImage(e) {
    if (e) e.stopPropagation();
    document.getElementById('barangGambar').value = '';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('imgPreview').src = '';
    document.getElementById('dropzoneOverlay').style.display = 'none';
    document.getElementById('dropzoneContent').style.display = 'block';
    document.getElementById('removePhotoWrapper').style.display = 'none';
}

function calculateLiveDenda() {
    const harga = parseFloat(document.getElementById('barangHarga').value) || 0;
    const ganti = parseFloat(document.getElementById('barangHargaDenda').value) || 0;
    
    if (harga > 0 || ganti > 0) {
        document.getElementById('liveDendaPreview').style.display = 'block';
        document.getElementById('liveDendaTelat').textContent = 'Rp ' + (harga * (DENDA_SETTINGS.telat / 100)).toLocaleString('id-ID');
        document.getElementById('liveDendaRingan').textContent = 'Rp ' + (ganti * (DENDA_SETTINGS.ringan / 100)).toLocaleString('id-ID');
        document.getElementById('liveDendaBerat').textContent = 'Rp ' + (ganti * (DENDA_SETTINGS.berat / 100)).toLocaleString('id-ID');
        document.getElementById('liveDendaHilang').textContent = 'Rp ' + (ganti * (DENDA_SETTINGS.hilang / 100)).toLocaleString('id-ID');
    } else {
        document.getElementById('liveDendaPreview').style.display = 'none';
    }
}
</script>
</body></html>