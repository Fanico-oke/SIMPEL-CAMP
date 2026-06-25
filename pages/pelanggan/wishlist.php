<?php
// pages/pelanggan/wishlist.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Wishlist.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/MemberLevel.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Keranjang Sewa';
$current_page = 'wishlist';
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';
$wishlistItems = Wishlist::getByUser($user_id);
$itemCount = count($wishlistItems);

$diskon_persen = MemberLevel::getDiskon($user_id);
$member = MemberLevel::getByUser($user_id);
$member_level = $member ? ucfirst($member['level']) : 'Regular';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">
    <style>
    :root {
        --wl-primary: #2D6A4F; --wl-light: #52B788; --wl-gold: #D4A373;
        --wl-bg: #F2F7F4; --wl-card: #FFFFFF; --wl-text: #1B4332;
        --wl-muted: #6B7280; --wl-radius: 16px; --wl-danger: #EF4444;
    }

    /* ─── Page Header ─── */
    .wl-header {
        display:flex !important; align-items:center !important; justify-content:space-between !important;
        flex-wrap:wrap; gap:16px; margin-bottom:28px !important; padding:0 !important;
    }
    .wl-header h1 {
        font-family:'Outfit',sans-serif !important; font-size:1.6rem !important; font-weight:800 !important;
        color:var(--wl-text); margin:0 !important; display:flex !important; align-items:center; gap:10px;
    }
    .wl-header .badge-count {
        background:linear-gradient(135deg,var(--wl-primary),var(--wl-light)); color:#fff;
        font-size:0.75rem; padding:4px 14px; border-radius:20px; font-weight:700;
    }
    .wl-header-actions { display:flex; align-items:center; gap:10px; }
    .wl-add-btn {
        display:inline-flex !important; align-items:center; gap:6px; padding:10px 20px;
        background:linear-gradient(135deg,var(--wl-primary),var(--wl-light)); color:#fff !important;
        border-radius:12px; text-decoration:none !important; font-weight:600; font-size:0.85rem;
        transition:all 0.25s; border:none; cursor:pointer;
    }
    .wl-add-btn:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(45,106,79,0.3); color:#fff !important; }

    /* ─── Select Bar ─── */
    .wl-select-bar {
        display:flex !important; align-items:center !important; gap:14px;
        padding:14px 20px !important; background:var(--wl-card); border-radius:14px;
        margin-bottom:20px; border:1px solid rgba(45,106,79,0.08);
        box-shadow:0 2px 8px rgba(0,0,0,0.02);
    }
    .wl-select-bar label {
        font-size:0.85rem; color:var(--wl-text); font-weight:600; cursor:pointer;
        display:flex !important; align-items:center; gap:8px; margin:0;
    }
    .wl-select-bar label input[type="checkbox"] { width:18px; height:18px; accent-color:var(--wl-primary); cursor:pointer; }
    .wl-clear-btn {
        margin-left:auto !important; font-size:0.82rem; color:var(--wl-danger) !important;
        cursor:pointer; border:1.5px solid rgba(239,68,68,0.2) !important;
        background:rgba(239,68,68,0.04) !important; font-weight:600;
        display:inline-flex !important; align-items:center; gap:5px; padding:8px 16px !important;
        border-radius:10px; transition:all 0.2s;
    }
    .wl-clear-btn:hover { background:rgba(239,68,68,0.1) !important; border-color:var(--wl-danger) !important; }

    /* ─── Layout ─── */
    .wl-layout { display:grid !important; grid-template-columns:1fr 340px; gap:24px; align-items:start; }
    @media(max-width:992px){ .wl-layout { grid-template-columns:1fr !important; } }

    /* ─── Item Card ─── */
    .wl-item {
        background:var(--wl-card) !important; border-radius:var(--wl-radius) !important;
        padding:22px !important; margin-bottom:14px; border:1px solid rgba(0,0,0,0.05) !important;
        box-shadow:0 2px 12px rgba(0,0,0,0.03); transition:all 0.3s ease;
        animation:wlFadeIn 0.4s ease forwards; opacity:0;
    }
    .wl-item:hover { box-shadow:0 6px 24px rgba(45,106,79,0.08); transform:translateY(-2px); }
    .wl-item.unchecked { opacity:0.5; }
    @keyframes wlFadeIn { to { opacity:1; } }

    .wl-item-top { display:flex !important; gap:14px; align-items:center !important; }
    .wl-checkbox {
        width:22px !important; height:22px !important; min-width:22px; border-radius:6px;
        border:2px solid #D1D5DB; appearance:none; -webkit-appearance:none; cursor:pointer;
        transition:all 0.2s; flex-shrink:0;
    }
    .wl-checkbox:checked {
        background:linear-gradient(135deg,var(--wl-primary),var(--wl-light)); border-color:var(--wl-primary);
        background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2.5-2.5a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3E%3C/svg%3E");
        background-repeat:no-repeat; background-position:center; background-size:14px;
    }
    .wl-img { width:80px !important; height:80px !important; border-radius:12px; object-fit:cover; flex-shrink:0; }
    .wl-info { flex:1; min-width:0; }
    .wl-cat { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--wl-light); font-weight:700; margin-bottom:3px; }
    .wl-name { font-family:'Outfit',sans-serif; font-size:1rem; font-weight:700; color:var(--wl-text); margin-bottom:4px; }
    .wl-price { font-family:'JetBrains Mono',monospace; font-size:0.9rem; font-weight:700; color:var(--wl-gold); }
    .wl-price small { font-weight:400; color:var(--wl-muted); font-size:0.72rem; }
    .wl-remove {
        background:rgba(239,68,68,0.06) !important; border:1.5px solid rgba(239,68,68,0.2) !important;
        color:var(--wl-danger) !important; font-size:0.8rem !important; cursor:pointer;
        padding:8px 14px !important; border-radius:10px !important; transition:all 0.2s;
        display:inline-flex !important; align-items:center !important; gap:5px; flex-shrink:0;
        font-weight:600; font-family:'Inter',sans-serif; white-space:nowrap;
    }
    .wl-remove:hover { background:rgba(239,68,68,0.12) !important; border-color:var(--wl-danger) !important; }

    /* ─── Qty + Date Row ─── */
    .wl-controls {
        display:flex !important; flex-wrap:wrap; gap:14px; margin-top:16px;
        padding-top:16px; border-top:1px solid rgba(0,0,0,0.05); align-items:flex-end;
    }
    .wl-ctrl-group { display:flex; flex-direction:column; gap:5px; }
    .wl-ctrl-label { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--wl-muted); font-weight:600; }
    .wl-qty-wrap {
        display:flex !important; align-items:center !important; gap:0;
        border:1.5px solid #E5E7EB; border-radius:10px; overflow:hidden; height:38px;
    }
    .wl-qty-btn {
        width:38px !important; height:38px !important; border:none !important;
        background:#F3F4F6 !important; cursor:pointer; font-size:1.15rem; font-weight:700;
        color:var(--wl-text) !important; transition:all 0.15s;
        display:flex !important; align-items:center !important; justify-content:center !important;
        padding:0 !important; line-height:1; margin:0 !important;
    }
    .wl-qty-btn:hover { background:var(--wl-primary) !important; color:#fff !important; }
    .wl-qty-val {
        width:46px !important; text-align:center; font-weight:700; font-size:0.9rem;
        border:none !important; border-left:1px solid #E5E7EB !important; border-right:1px solid #E5E7EB !important;
        outline:none; background:#fff !important; font-family:'JetBrains Mono',monospace;
        color:var(--wl-text); height:38px !important; line-height:38px;
    }
    .wl-date-input {
        font-size:0.82rem; padding:8px 12px; border:1.5px solid #E5E7EB;
        border-radius:10px; background:#fff; color:var(--wl-text);
        font-weight:600; font-family:'Inter',sans-serif;
        transition:border-color 0.2s; outline:none; min-width:145px; height:38px;
    }
    .wl-date-input:focus { border-color:var(--wl-light); box-shadow:0 0 0 3px rgba(82,183,136,0.12); }

    /* ─── Duration + Subtotal ─── */
    .wl-item-summary {
        display:flex !important; justify-content:space-between !important; align-items:center !important;
        margin-top:14px; padding:12px 16px; background:rgba(45,106,79,0.03); border-radius:10px;
    }
    .wl-dur { font-size:0.82rem; color:var(--wl-muted); }
    .wl-dur strong { color:var(--wl-primary); }
    .wl-subtotal { font-family:'JetBrains Mono',monospace; font-weight:700; font-size:0.95rem; color:var(--wl-primary); }

    /* ─── Summary Panel ─── */
    .wl-summary {
        background:var(--wl-card) !important; border-radius:var(--wl-radius); border:1px solid rgba(0,0,0,0.04);
        box-shadow:0 4px 20px rgba(0,0,0,0.04); position:sticky; top:90px; overflow:hidden;
    }
    .wl-sum-head {
        background:linear-gradient(135deg,#0F2B1E,#1a3a2a); color:#fff;
        padding:18px 20px; font-family:'Outfit',sans-serif; font-weight:700;
        font-size:0.95rem; display:flex; align-items:center; gap:8px;
    }
    .wl-sum-body { padding:20px; }
    .wl-sum-row { display:flex; justify-content:space-between; padding:9px 0; font-size:0.84rem; color:var(--wl-muted); border-bottom:1px solid rgba(0,0,0,0.03); }
    .wl-sum-row .val { font-weight:600; color:var(--wl-text); }
    .wl-sum-disc { display:flex; justify-content:space-between; padding:8px 12px; background:rgba(212,163,115,0.08); border-radius:8px; margin-top:8px; font-size:0.82rem; color:var(--wl-gold); font-weight:600; }
    .wl-sum-total { display:flex; justify-content:space-between; padding:16px 0 0; margin-top:10px; border-top:2px solid rgba(45,106,79,0.1); font-family:'Outfit',sans-serif; align-items:center; }
    .wl-sum-total .label { font-weight:700; font-size:0.95rem; color:var(--wl-text); }
    .wl-sum-total .amount { font-weight:800; font-size:1.25rem; color:var(--wl-primary); }
    .wl-checkout-btn {
        display:flex !important; align-items:center; justify-content:center; gap:8px;
        width:100%; padding:15px; margin-top:18px; border:none !important; border-radius:12px;
        background:linear-gradient(135deg,var(--wl-primary),var(--wl-light)) !important; color:#fff !important;
        font-weight:700; font-size:0.95rem; cursor:pointer; font-family:'Inter',sans-serif;
        transition:all 0.3s;
    }
    .wl-checkout-btn:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(45,106,79,0.3); }
    .wl-checkout-btn:disabled { opacity:0.5; cursor:not-allowed; transform:none !important; box-shadow:none !important; }
    .wl-sum-note { font-size:0.74rem; color:var(--wl-muted); text-align:center; margin-top:12px; }

    /* ─── Empty State ─── */
    .wl-empty { text-align:center; padding:80px 20px; }
    .wl-empty-icon { font-size:4rem; margin-bottom:16px; display:block; }
    .wl-empty h3 { font-family:'Outfit',sans-serif; font-weight:700; color:var(--wl-text); margin-bottom:8px; }
    .wl-empty p { color:var(--wl-muted); font-size:0.9rem; margin-bottom:24px; }
    .wl-empty a { display:inline-flex; align-items:center; gap:8px; padding:12px 28px; background:linear-gradient(135deg,var(--wl-primary),var(--wl-light)); color:#fff; border-radius:12px; text-decoration:none; font-weight:600; font-size:0.9rem; transition:all 0.25s; }
    .wl-empty a:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(45,106,79,0.3); }

    /* ─── Toast ─── */
    .wl-toast { position:fixed; bottom:24px; right:24px; background:linear-gradient(135deg,var(--wl-primary),var(--wl-light)); color:#fff; padding:14px 22px; border-radius:12px; font-size:0.85rem; font-weight:600; z-index:9999; transform:translateY(80px); opacity:0; transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1); box-shadow:0 8px 24px rgba(45,106,79,0.3); display:flex; align-items:center; gap:8px; }
    .wl-toast.show { transform:translateY(0); opacity:1; }
    </style>
</head>
<body>
<div class="pelanggan-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>
    <div class="pelanggan-main">
        <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>
        <div class="pelanggan-content">

            <?php if ($itemCount === 0): ?>
            <!-- Empty State -->
            <div class="wl-empty">
                <span class="wl-empty-icon">🛒</span>
                <h3>Keranjang Kosong</h3>
                <p>Belum ada barang di keranjang. Yuk jelajahi katalog dan pilih peralatan camping!</p>
                <a href="<?= BASE_URL ?>/pages/pelanggan/katalog.php"><i class="bi bi-shop"></i> Jelajahi Katalog</a>
            </div>

            <?php else: ?>
            <!-- Select Bar -->
            <div class="wl-select-bar">
                <label><input type="checkbox" id="selectAll" checked onchange="toggleSelectAll()" style="accent-color:var(--wl-primary);"> Pilih Semua</label>
                <span class="badge-count" id="totalBadge"><?= $itemCount ?> item</span>
                <div style="flex:1;"></div>
                <a href="<?= BASE_URL ?>/pages/pelanggan/katalog.php" class="wl-add-btn">
                    <i class="bi bi-plus-lg"></i> Tambah Barang
                </a>
                <button class="wl-clear-btn" onclick="clearAll()"><i class="bi bi-trash3 me-1"></i>Hapus Semua</button>
            </div>

            <div class="wl-layout">
                <!-- LEFT: Items -->
                <div class="wl-items-list">
                    <?php foreach ($wishlistItems as $idx => $item):
                        $imgUrl = !empty($item['gambar']) ? ASSETS_URL . '/img/barang/' . $item['gambar'] : 'https://images.unsplash.com/photo-1537225228614-56cc3556d7ed?auto=format&fit=crop&w=200&q=80';
                        $harga = (int)$item['harga_per_hari'];
                        $stok = (int)$item['stok_tersedia'];
                        $mulai = $item['tanggal_mulai'] ?? '';
                        $selesai = $item['tanggal_selesai'] ?? '';
                    ?>
                    <div class="wl-item" id="item-<?= $item['barang_id'] ?>" data-id="<?= $item['barang_id'] ?>" data-harga="<?= $harga ?>" data-qty="<?= $item['jumlah'] ?>" data-mulai="<?= $mulai ?>" data-selesai="<?= $selesai ?>" style="animation-delay:<?= $idx * 0.06 ?>s;">
                        <div class="wl-item-top">
                            <input type="checkbox" class="wl-checkbox" checked onchange="onCheckChange()">
                            <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($item['barang_nama']) ?>" class="wl-img">
                            <div class="wl-info">
                                <div class="wl-cat"><?= htmlspecialchars($item['kategori_nama'] ?? 'Umum') ?></div>
                                <div class="wl-name"><?= htmlspecialchars($item['barang_nama']) ?></div>
                                <div class="wl-price">Rp <?= number_format($harga, 0, ',', '.') ?> <small>/hari</small></div>
                            </div>
                            <button class="wl-remove" onclick="removeItem(<?= $item['barang_id'] ?>)" title="Hapus">
                                <i class="bi bi-trash3"></i> Hapus
                            </button>
                        </div>

                        <div class="wl-controls">
                            <div class="wl-ctrl-group">
                                <span class="wl-ctrl-label">Jumlah</span>
                                <div class="wl-qty-wrap">
                                    <button class="wl-qty-btn" onclick="updateQty(<?= $item['barang_id'] ?>, -1)">−</button>
                                    <input type="text" class="wl-qty-val" id="qty-<?= $item['barang_id'] ?>" value="<?= $item['jumlah'] ?>" readonly>
                                    <button class="wl-qty-btn" onclick="updateQty(<?= $item['barang_id'] ?>, 1)">+</button>
                                </div>
                            </div>
                            <div class="wl-ctrl-group">
                                <span class="wl-ctrl-label">Tanggal Mulai</span>
                                <input type="date" class="wl-date-input" id="mulai-<?= $item['barang_id'] ?>" value="<?= $mulai ?>" onchange="updateDates(<?= $item['barang_id'] ?>)">
                            </div>
                            <div class="wl-ctrl-group">
                                <span class="wl-ctrl-label">Tanggal Selesai</span>
                                <input type="date" class="wl-date-input" id="selesai-<?= $item['barang_id'] ?>" value="<?= $selesai ?>" onchange="updateDates(<?= $item['barang_id'] ?>)">
                            </div>
                        </div>

                        <div class="wl-item-summary">
                            <span class="wl-dur" id="dur-<?= $item['barang_id'] ?>">
                                <?php
                                    if ($mulai && $selesai) {
                                        $days = (strtotime($selesai) - strtotime($mulai)) / 86400;
                                        echo "<strong>{$days} hari</strong> sewa";
                                    } else {
                                        echo "Pilih tanggal sewa";
                                    }
                                ?>
                            </span>
                            <span class="wl-subtotal" id="sub-<?= $item['barang_id'] ?>">
                                <?php
                                    if ($mulai && $selesai) {
                                        $days = (strtotime($selesai) - strtotime($mulai)) / 86400;
                                        echo 'Rp ' . number_format($harga * $item['jumlah'] * $days, 0, ',', '.');
                                    } else {
                                        echo 'Rp 0';
                                    }
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- RIGHT: Summary -->
                <div>
                    <div class="wl-summary">
                        <div class="wl-sum-head"><i class="bi bi-receipt"></i> Ringkasan Pesanan</div>
                        <div class="wl-sum-body">
                            <div class="wl-sum-row"><span>Item Dipilih</span><span class="val" id="sumItems">0 barang</span></div>
                            <div class="wl-sum-row"><span>Total Unit</span><span class="val" id="sumUnits">0</span></div>
                            <div class="wl-sum-row"><span>Subtotal</span><span class="val" id="sumSubtotal">Rp 0</span></div>
                            <?php if ($diskon_persen > 0): ?>
                            <div class="wl-sum-disc">
                                <span><i class="bi bi-star-fill me-1"></i>Diskon <?= $member_level ?> -<?= $diskon_persen ?>%</span>
                                <span id="sumDiskon">- Rp 0</span>
                            </div>
                            <?php endif; ?>
                            <div class="wl-sum-total">
                                <span class="label">Grand Total</span>
                                <span class="amount" id="sumGrand">Rp 0</span>
                            </div>
                            <button class="wl-checkout-btn" id="btnCheckout" onclick="goToCheckout()" disabled>
                                <i class="bi bi-shield-check me-1"></i> Sewa Sekarang
                            </button>
                            <div class="wl-sum-note"><i class="bi bi-lock-fill me-1"></i>Tanggal sewa per item bisa berbeda</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div class="wl-toast" id="wlToast"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = '<?= BASE_URL ?>';
const DISKON = <?= $diskon_persen / 100 ?>;

function formatRp(n) { return 'Rp ' + n.toLocaleString('id-ID'); }

function showToast(msg) {
    const t = document.getElementById('wlToast');
    t.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

function getItemData(id) {
    const el = document.getElementById('item-' + id);
    if (!el) return null;
    return {
        el, id,
        harga: parseInt(el.dataset.harga),
        qty: parseInt(document.getElementById('qty-' + id).value),
        mulai: document.getElementById('mulai-' + id).value,
        selesai: document.getElementById('selesai-' + id).value,
        checked: el.querySelector('.wl-checkbox').checked
    };
}

function calcDays(mulai, selesai) {
    if (!mulai || !selesai) return 0;
    const d = (new Date(selesai) - new Date(mulai)) / (1000*60*60*24);
    return d > 0 ? Math.ceil(d) : 0;
}

// ─── Update Qty ───
function updateQty(id, delta) {
    const qtyEl = document.getElementById('qty-' + id);
    let val = parseInt(qtyEl.value) + delta;
    if (val < 1) val = 1;
    if (val > 20) val = 20;
    qtyEl.value = val;
    document.getElementById('item-' + id).dataset.qty = val;

    recalcItem(id);
    recalcTotal();

    fetch(BASE + '/api/wishlist.php?action=update', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ barang_id: id, jumlah: val })
    });
}

// ─── Update Dates (per item) ───
function updateDates(id) {
    const mulai = document.getElementById('mulai-' + id).value;
    const selesai = document.getElementById('selesai-' + id).value;

    // Auto set min selesai
    if (mulai) {
        document.getElementById('selesai-' + id).min = mulai;
    }

    recalcItem(id);
    recalcTotal();

    if (mulai && selesai) {
        fetch(BASE + '/api/wishlist.php?action=update_dates', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ barang_id: id, tanggal_mulai: mulai, tanggal_selesai: selesai })
        });
    }
}

// ─── Recalc single item ───
function recalcItem(id) {
    const data = getItemData(id);
    if (!data) return;
    const days = calcDays(data.mulai, data.selesai);
    const sub = data.harga * data.qty * days;

    document.getElementById('dur-' + id).innerHTML = days > 0
        ? '<strong>' + days + ' hari</strong> sewa'
        : 'Pilih tanggal sewa';
    document.getElementById('sub-' + id).textContent = formatRp(sub);
}

// ─── Recalc total summary ───
function recalcTotal() {
    const items = document.querySelectorAll('.wl-item');
    let totalSub = 0, totalItems = 0, totalUnits = 0;
    let allHaveDates = true;
    let checkedCount = 0;

    items.forEach(el => {
        const cb = el.querySelector('.wl-checkbox');
        if (!cb.checked) {
            el.classList.add('unchecked');
            return;
        }
        el.classList.remove('unchecked');
        checkedCount++;

        const id = el.dataset.id;
        const data = getItemData(parseInt(id));
        if (!data) return;

        const days = calcDays(data.mulai, data.selesai);
        if (days <= 0) allHaveDates = false;
        const sub = data.harga * data.qty * days;
        totalSub += sub;
        totalItems++;
        totalUnits += data.qty;
    });

    const disc = Math.round(totalSub * DISKON);
    const grand = totalSub - disc;

    document.getElementById('sumItems').textContent = totalItems + ' barang';
    document.getElementById('sumUnits').textContent = totalUnits;
    document.getElementById('sumSubtotal').textContent = formatRp(totalSub);
    if (document.getElementById('sumDiskon')) document.getElementById('sumDiskon').textContent = '- ' + formatRp(disc);
    document.getElementById('sumGrand').textContent = formatRp(grand);

    const btn = document.getElementById('btnCheckout');
    btn.disabled = !(checkedCount > 0 && allHaveDates && totalSub > 0);

    // Update select all
    const allCbs = document.querySelectorAll('.wl-checkbox');
    const allChecked = Array.from(allCbs).every(c => c.checked);
    document.getElementById('selectAll').checked = allChecked;
}

function onCheckChange() { recalcTotal(); }

function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.wl-checkbox').forEach(cb => cb.checked = checked);
    recalcTotal();
}

// ─── Remove Item ───
function removeItem(id) {
    const el = document.getElementById('item-' + id);
    if (!el) return;
    el.style.transform = 'translateX(-100%)';
    el.style.opacity = '0';
    setTimeout(() => {
        el.remove();
        recalcTotal();
        updateBadge();
        if (!document.querySelector('.wl-item')) location.reload();
    }, 300);

    fetch(BASE + '/api/wishlist.php?action=remove', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ barang_id: id })
    }).then(() => showToast('Item dihapus'));
}

function clearAll() {
    if (!confirm('Hapus semua item dari keranjang?')) return;
    fetch(BASE + '/api/wishlist.php?action=clear', { method:'POST', headers:{'Content-Type':'application/json'} })
    .then(() => location.reload());
}

function updateBadge() {
    const count = document.querySelectorAll('.wl-item').length;
    const badge = document.getElementById('totalBadge');
    if (badge) badge.textContent = count + ' item';
}

// ─── Checkout ───
function goToCheckout() {
    const items = document.querySelectorAll('.wl-item');
    const selectedIds = [];
    let hasIssue = false;

    items.forEach(el => {
        const cb = el.querySelector('.wl-checkbox');
        if (!cb.checked) return;
        const id = parseInt(el.dataset.id);
        const mulai = document.getElementById('mulai-' + id).value;
        const selesai = document.getElementById('selesai-' + id).value;

        if (!mulai || !selesai) {
            hasIssue = true;
            document.getElementById('mulai-' + id).style.borderColor = '#EF4444';
            document.getElementById('selesai-' + id).style.borderColor = '#EF4444';
        } else {
            document.getElementById('mulai-' + id).style.borderColor = '';
            document.getElementById('selesai-' + id).style.borderColor = '';
            selectedIds.push(id);
        }
    });

    if (hasIssue) {
        showToast('⚠️ Lengkapi tanggal sewa untuk semua item!');
        return;
    }
    if (selectedIds.length === 0) {
        showToast('⚠️ Pilih minimal 1 item!');
        return;
    }

    const url = BASE + '/pages/pelanggan/pemesanan.php?from=wishlist&items=' + selectedIds.join(',');
    window.location.href = url;
}

// ─── Set min dates ───
document.querySelectorAll('.wl-date-input[id^="mulai-"]').forEach(el => {
    const today = new Date().toISOString().split('T')[0];
    el.min = today;
    const id = el.id.replace('mulai-','');
    const selesaiEl = document.getElementById('selesai-' + id);
    if (selesaiEl) selesaiEl.min = el.value || today;
});

// Initial calc
recalcTotal();
</script>
</body>
</html>
