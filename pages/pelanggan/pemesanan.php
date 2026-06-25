<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Barang.php';
require_once dirname(__DIR__, 2) . '/classes/Kategori.php';
require_once dirname(__DIR__, 2) . '/classes/MemberLevel.php';
require_once dirname(__DIR__, 2) . '/classes/Wishlist.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Buat Pemesanan'; $current_page = 'pemesanan';
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// Get member discount
$diskon_persen = MemberLevel::getDiskon($_SESSION['user_id']);
$member = MemberLevel::getByUser($_SESSION['user_id']);
$member_level = $member ? ucfirst($member['level']) : 'Regular';

// Read dates from URL (legacy fallback for single item)
$prefill_mulai = isset($_GET['mulai']) ? htmlspecialchars($_GET['mulai']) : '';
$prefill_selesai = isset($_GET['selesai']) ? htmlspecialchars($_GET['selesai']) : '';
$has_prefill = !empty($prefill_mulai) && !empty($prefill_selesai);

// Load items from wishlist
$from_wishlist = isset($_GET['from']) && $_GET['from'] === 'wishlist';
$checkout_items = [];

if ($from_wishlist && isset($_GET['items'])) {
    $item_ids = array_filter(array_map('intval', explode(',', $_GET['items'])));
    $all_wishlist = Wishlist::getByUser($_SESSION['user_id']);
    foreach ($all_wishlist as $wi) {
        if (in_array((int)$wi['barang_id'], $item_ids)) {
            $checkout_items[] = [
                'barang_id' => (int)$wi['barang_id'],
                'nama' => htmlspecialchars($wi['barang_nama']),
                'gambar' => !empty($wi['gambar']) ? ASSETS_URL . '/img/barang/' . $wi['gambar'] : 'https://images.unsplash.com/photo-1537225228614-56cc3556d7ed?auto=format&fit=crop&w=600&q=80',
                'kategori' => htmlspecialchars($wi['kategori_nama'] ?? 'Umum'),
                'harga' => (int)$wi['harga_per_hari'],
                'jumlah' => (int)$wi['jumlah'],
                'stok' => (int)$wi['stok_tersedia'],
                'tanggal_mulai' => $wi['tanggal_mulai'] ?? '',
                'tanggal_selesai' => $wi['tanggal_selesai'] ?? '',
            ];
        }
    }
}

// Fallback: single item from ?id=
if (empty($checkout_items)) {
    $barang_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $barang = $barang_id > 0 ? Barang::getById($barang_id) : null;
    if (!$barang) {
        header('Location: ' . BASE_URL . '/pages/pelanggan/wishlist.php');
        exit;
    }
    $checkout_items[] = [
        'barang_id' => (int)$barang['id'],
        'nama' => htmlspecialchars($barang['nama']),
        'gambar' => !empty($barang['gambar']) ? ASSETS_URL . '/img/barang/' . $barang['gambar'] : 'https://images.unsplash.com/photo-1537225228614-56cc3556d7ed?auto=format&fit=crop&w=600&q=80',
        'kategori' => htmlspecialchars($barang['kategori_nama'] ?? 'Umum'),
        'harga' => (int)$barang['harga_per_hari'],
        'jumlah' => isset($_GET['qty']) ? max(1, (int)$_GET['qty']) : 1,
        'stok' => (int)$barang['stok_tersedia'],
        'tanggal_mulai' => $prefill_mulai,
        'tanggal_selesai' => $prefill_selesai,
    ];
}

// For JS
$items_json = json_encode($checkout_items);
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
    --bg-page:#F2F7F4; --bg-card:#FFFFFF; --card-radius:20px;
    --card-shadow:0 2px 20px rgba(0,0,0,0.04);
    --primary:#2D6A4F; --primary-light:#52B788; --primary-dark:#1B4332;
    --accent-gold:#D4A373; --text-primary:#1A1A2E; --text-secondary:#6B7280;
    --btn-radius:50px; --input-radius:12px;
    --font-body:'Inter',sans-serif; --font-heading:'Outfit',sans-serif;
    --font-mono:'JetBrains Mono',monospace;
}
@keyframes fadeInUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideDown{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
@keyframes toastIn{from{opacity:0;transform:translateX(100px)}to{opacity:1;transform:translateX(0)}}
@keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(100px)}}
@keyframes progressFill{from{width:0}to{width:100%}}
@keyframes checkDraw{0%{stroke-dashoffset:100}100%{stroke-dashoffset:0}}
@keyframes circleDraw{0%{stroke-dashoffset:314}100%{stroke-dashoffset:0}}
@keyframes confettiFall{0%{transform:translateY(-100%) rotate(0deg);opacity:1}100%{transform:translateY(100vh) rotate(720deg);opacity:0}}
@keyframes scaleIn{0%{transform:scale(0);opacity:0}100%{transform:scale(1);opacity:1}}

.checkout-page{max-width:1200px;margin:0 auto;}
.saas-card{background:var(--bg-card);border-radius:var(--card-radius);box-shadow:var(--card-shadow);border:none;padding:1.75rem;margin-bottom:1.5rem;animation:fadeInUp 0.5s ease;}

/* Stepper */
.stepper-card{padding:1.5rem 2rem;margin-bottom:2rem;}
.stepper{display:flex;align-items:center;justify-content:center;gap:0;}
.step-item{display:flex;align-items:center;gap:0.6rem;position:relative;}
.step-circle{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.95rem;border:2.5px solid #d1d5db;color:#9ca3af;background:#fff;transition:all 0.4s ease;flex-shrink:0;}
.step-label{font-weight:600;font-size:0.85rem;color:#9ca3af;transition:all 0.3s;white-space:nowrap;}
.step-item.active .step-circle{background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-color:var(--primary);color:#fff;box-shadow:0 4px 12px rgba(45,106,79,0.3);}
.step-item.active .step-label{color:var(--text-primary);}
.step-item.completed .step-circle{background:var(--primary-light);border-color:var(--primary-light);color:#fff;}
.step-item.completed .step-label{color:var(--primary-light);}
.step-line{width:80px;height:3px;background:#e5e7eb;margin:0 0.75rem;border-radius:2px;position:relative;overflow:hidden;}
.step-line .fill{position:absolute;top:0;left:0;height:100%;width:0;background:linear-gradient(90deg,var(--primary-light),var(--primary));border-radius:2px;transition:width 0.5s ease;}
.step-line.filled .fill{width:100%;}
@media(max-width:576px){.step-label{display:none;}.step-line{width:40px;}.stepper-card{padding:1rem;}}

/* Card Title */
.card-title-c{font-family:var(--font-heading);font-weight:700;font-size:1.15rem;color:var(--text-primary);margin-bottom:1.25rem;display:flex;align-items:center;gap:0.6rem;}
.card-title-c i{color:var(--primary-light);}

/* Item Preview */
.item-preview{display:flex;gap:1.15rem;background:#F8FAF9;border-radius:var(--input-radius);padding:1.15rem;margin-bottom:1.25rem;}
.item-preview img{width:100px;height:100px;object-fit:cover;border-radius:var(--input-radius);flex-shrink:0;}
.item-preview .ip-name{font-family:var(--font-heading);font-weight:700;color:var(--text-primary);font-size:1.05rem;margin-bottom:0.25rem;}
.item-preview .ip-cat{display:inline-flex;align-items:center;gap:0.25rem;font-size:0.72rem;background:rgba(82,183,136,0.1);color:var(--primary);padding:0.15rem 0.55rem;border-radius:var(--btn-radius);margin-bottom:0.35rem;font-weight:600;}
.item-preview .ip-price{font-family:var(--font-mono);font-weight:600;color:var(--accent-gold);font-size:1.05rem;}
.item-preview .ip-price small{font-family:var(--font-body);color:var(--text-secondary);font-weight:400;font-size:0.78rem;}
.item-preview .ip-stock{display:inline-flex;align-items:center;gap:0.25rem;font-size:0.72rem;color:var(--primary);font-weight:600;margin-top:0.25rem;}
@media(max-width:576px){.item-preview{flex-direction:column;align-items:center;text-align:center;}.item-preview img{width:100%;height:160px;}}

/* Quantity */
.qty-row{display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;}
.qty-row label{font-weight:600;color:var(--text-primary);font-size:0.88rem;min-width:80px;}
.qty-ctrl{display:flex;align-items:center;}
.qty-ctrl button{width:36px;height:36px;border:1.5px solid rgba(107,114,128,0.2);background:#fff;color:var(--text-primary);font-size:1.1rem;font-weight:700;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s;}
.qty-ctrl button:first-child{border-radius:var(--input-radius) 0 0 var(--input-radius);}
.qty-ctrl button:last-child{border-radius:0 var(--input-radius) var(--input-radius) 0;}
.qty-ctrl button:hover{background:var(--primary-light);color:#fff;border-color:var(--primary-light);}
.qty-ctrl input{width:48px;height:36px;border:1.5px solid rgba(107,114,128,0.2);border-left:none;border-right:none;text-align:center;font-weight:700;font-family:var(--font-mono);font-size:0.9rem;color:var(--text-primary);background:#FAFCFB;}
.qty-ctrl input:focus{outline:none;}

/* Form Inputs */
.form-label-c{font-weight:600;color:var(--text-primary);font-size:0.85rem;margin-bottom:0.4rem;}
.form-input-c{width:100%;border:1.5px solid rgba(107,114,128,0.2);border-radius:var(--input-radius);padding:0.65rem 0.9rem;font-size:0.9rem;transition:all 0.25s;background:#FAFCFB;font-family:var(--font-body);}
.form-input-c:focus{border-color:var(--primary-light);box-shadow:0 0 0 3px rgba(82,183,136,0.12);outline:none;background:#fff;}

/* Calc Highlight */
.calc-highlight{background:linear-gradient(135deg,rgba(82,183,136,0.06),rgba(45,106,79,0.03));border-radius:var(--input-radius);padding:0.85rem 1rem;margin-top:1rem;display:flex;justify-content:space-between;align-items:center;}
.calc-highlight .ch-label{color:var(--text-secondary);font-size:0.88rem;}
.calc-highlight .ch-label strong{color:var(--text-primary);}
.calc-highlight .ch-value{font-family:var(--font-mono);font-weight:600;color:var(--primary);font-size:1.1rem;}

/* Step Buttons */
.btn-next{display:inline-flex;align-items:center;gap:0.6rem;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border:none;border-radius:var(--btn-radius);padding:0.8rem 2rem;font-weight:700;font-size:0.95rem;cursor:pointer;transition:all 0.3s;width:100%;justify-content:center;margin-top:1.25rem;}
.btn-next:hover{background:linear-gradient(135deg,var(--primary-dark),#0F2B1E);transform:translateY(-2px);box-shadow:0 6px 20px rgba(27,67,50,0.25);}
.btn-next i{transition:transform 0.3s;}
.btn-next:hover i{transform:translateX(4px);}
.btn-next:disabled{background:#d1d5db;cursor:not-allowed;transform:none;box-shadow:none;}
.btn-back{display:inline-flex;align-items:center;gap:0.4rem;background:none;border:none;color:var(--primary);font-weight:600;font-size:0.88rem;cursor:pointer;transition:all 0.25s;padding:0.5rem 0;margin-top:0.75rem;}
.btn-back:hover{color:var(--primary-dark);gap:0.6rem;}

/* Step Content */
.step-content{display:none;animation:slideDown 0.4s ease;}
.step-content.active{display:block;}

/* Payment Cards */
.pay-methods{display:grid;grid-template-columns:repeat(3,1fr);gap:0.85rem;margin-bottom:1.25rem;}
@media(max-width:576px){.pay-methods{grid-template-columns:1fr;}}
.pay-card{position:relative;border:2px solid rgba(107,114,128,0.1);border-radius:16px;padding:1.15rem 0.85rem;cursor:pointer;transition:all 0.3s;background:var(--bg-card);text-align:center;}
.pay-card:hover{border-color:var(--primary-light);background:rgba(82,183,136,0.02);}
.pay-card.active{border-color:var(--primary);background:rgba(82,183,136,0.04);box-shadow:0 0 0 3px rgba(82,183,136,0.1);}
.pay-card.active::after{content:'\F26A';font-family:'bootstrap-icons';position:absolute;top:8px;right:10px;color:var(--primary);font-size:0.85rem;}
.pay-card input[type="radio"]{position:absolute;opacity:0;}
.pay-card .pc-icon{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 0.6rem;font-size:1.3rem;}
.pay-card .pc-title{font-weight:700;font-size:0.88rem;color:var(--text-primary);margin-bottom:0.2rem;}
.pay-card .pc-desc{font-size:0.75rem;color:var(--text-secondary);line-height:1.4;}

/* Payment detail panels */
.pay-detail{display:none;animation:slideDown 0.35s ease;}
.pay-detail.show{display:block;}

.transfer-box{background:#F8FAF9;border-radius:14px;padding:1.25rem;}
.bank-number{font-family:var(--font-mono);font-size:1.2rem;font-weight:600;color:var(--primary);letter-spacing:1px;}
.copy-btn{background:var(--primary);color:#fff;border:none;border-radius:var(--btn-radius);padding:0.4rem 0.85rem;font-size:0.8rem;font-weight:600;cursor:pointer;transition:all 0.25s;display:inline-flex;align-items:center;gap:0.35rem;}
.copy-btn:hover{background:var(--primary-dark);transform:translateY(-1px);}
.copy-btn.copied{background:var(--primary-light);}

.upload-box{border:2px dashed rgba(82,183,136,0.25);border-radius:14px;padding:1.5rem;text-align:center;cursor:pointer;transition:all 0.3s;background:rgba(82,183,136,0.02);margin-top:1rem;}
.upload-box:hover{border-color:var(--primary-light);background:rgba(82,183,136,0.04);}
.upload-box.dragover{border-color:var(--primary);background:rgba(82,183,136,0.06);}
.upload-box i{font-size:1.8rem;color:var(--primary-light);}
.upload-box p{margin:0.5rem 0 0;color:var(--text-secondary);font-size:0.82rem;}
.upload-preview{margin-top:0.75rem;display:flex;align-items:center;gap:0.75rem;justify-content:center;}
.upload-preview img{max-height:80px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.progress-fake{width:200px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;margin-top:0.5rem;display:inline-block;}
.progress-fake .bar{height:100%;background:linear-gradient(90deg,var(--primary-light),var(--primary));border-radius:3px;animation:progressFill 1.5s ease forwards;}

.qris-box{text-align:center;background:#F8FAF9;border-radius:14px;padding:1.5rem;}
.qris-timer{font-family:var(--font-mono);font-size:1.4rem;font-weight:700;color:var(--accent-gold);margin-top:0.75rem;}
.ewallet-pills{display:flex;justify-content:center;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;}
.ewallet-pill{padding:0.3rem 0.75rem;border-radius:var(--btn-radius);font-size:0.75rem;font-weight:600;background:rgba(139,92,246,0.08);color:#7c3aed;}

.cash-box{background:#F8FAF9;border-radius:14px;padding:1.5rem;text-align:center;}
.cash-box i{font-size:2.2rem;color:var(--accent-gold);}
.cash-box p{margin:0.75rem 0 0;color:var(--text-primary);font-weight:500;}

/* Review Row */
.review-row{display:flex;justify-content:space-between;padding:0.5rem 0;font-size:0.88rem;border-bottom:1px solid rgba(107,114,128,0.06);}
.review-row:last-child{border-bottom:none;}
.review-row .rr-label{color:var(--text-secondary);}
.review-row .rr-value{font-weight:600;color:var(--text-primary);}

/* Summary Sidebar */
.summary-card{position:sticky;top:90px;background:var(--bg-card);border-radius:var(--card-radius);box-shadow:var(--card-shadow);border:none;overflow:hidden;}
.sum-header{background:linear-gradient(135deg,var(--primary-dark),var(--primary));color:#fff;padding:1rem 1.25rem;font-family:var(--font-heading);font-weight:700;font-size:1rem;display:flex;align-items:center;gap:0.5rem;}
.sum-body{padding:1.25rem;}
.sum-thumb{display:flex;gap:0.85rem;align-items:center;padding-bottom:1rem;border-bottom:1px solid rgba(107,114,128,0.08);margin-bottom:1rem;}
.sum-thumb img{width:52px;height:52px;border-radius:10px;object-fit:cover;}
.sum-thumb .st-name{font-weight:600;color:var(--text-primary);font-size:0.88rem;}
.sum-thumb .st-price{font-family:var(--font-mono);color:var(--accent-gold);font-weight:600;font-size:0.82rem;}
.sum-row{display:flex;justify-content:space-between;padding:0.35rem 0;font-size:0.85rem;}
.sum-row .sr-label{color:var(--text-secondary);}
.sum-row .sr-value{font-weight:600;color:var(--text-primary);font-family:var(--font-mono);font-size:0.82rem;}
.sum-divider{border:none;border-top:1.5px dashed rgba(107,114,128,0.1);margin:0.6rem 0;}
.sum-discount{background:linear-gradient(90deg,rgba(212,163,115,0.1),transparent);border-left:3px solid var(--accent-gold);padding:0.4rem 0.75rem;border-radius:0 8px 8px 0;display:flex;justify-content:space-between;margin:0.5rem 0;font-size:0.82rem;}
.sum-discount .sd-label{color:var(--accent-gold);font-weight:600;display:flex;align-items:center;gap:0.3rem;}
.sum-discount .sd-value{color:var(--accent-gold);font-weight:700;font-family:var(--font-mono);}
.sum-total{background:linear-gradient(135deg,rgba(27,67,50,0.04),rgba(82,183,136,0.06));border-radius:var(--input-radius);padding:0.85rem;display:flex;justify-content:space-between;align-items:center;margin-top:0.6rem;}
.sum-total .st-label{font-weight:700;color:var(--text-primary);font-size:0.95rem;}
.sum-total .st-value{font-family:var(--font-heading);font-weight:800;color:var(--text-primary);font-size:1.25rem;}

/* Terms */
.terms-check{display:flex;align-items:flex-start;gap:0.6rem;margin:1.25rem 0;}
.terms-check input[type="checkbox"]{width:18px;height:18px;margin-top:2px;accent-color:var(--primary);cursor:pointer;}
.terms-check label{font-size:0.88rem;color:var(--text-secondary);}
.terms-check a{color:var(--primary);font-weight:600;text-decoration:underline;cursor:pointer;}

/* Toast */
.toast-container{position:fixed;top:20px;right:20px;z-index:10000;display:flex;flex-direction:column;gap:0.5rem;}
.custom-toast{background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-radius:var(--input-radius);padding:0.85rem 1.15rem;box-shadow:0 8px 28px rgba(0,0,0,0.12);display:flex;align-items:center;gap:0.6rem;min-width:260px;animation:toastIn 0.4s ease;font-size:0.85rem;font-weight:500;}
.custom-toast.hiding{animation:toastOut 0.3s ease forwards;}
.custom-toast.success .ti{background:rgba(82,183,136,0.15);color:var(--primary-light);}
.custom-toast.info .ti{background:rgba(45,106,79,0.12);color:var(--primary);}
.custom-toast.warning .ti{background:rgba(212,163,115,0.15);color:var(--accent-gold);}
.ti{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0;}

/* Success Modal */
.success-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:10001;align-items:center;justify-content:center;animation:fadeIn 0.3s ease;}
.success-overlay.active{display:flex;}
.success-card{background:#fff;border-radius:24px;padding:2.5rem 2rem;text-align:center;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.15);animation:scaleIn 0.5s cubic-bezier(0.34,1.56,0.64,1);position:relative;overflow:hidden;}
.checkmark-circle{width:80px;height:80px;margin:0 auto 1.5rem;position:relative;}
.checkmark-circle svg{width:80px;height:80px;}
.checkmark-circle .circle{fill:none;stroke:var(--primary-light);stroke-width:3;stroke-dasharray:314;stroke-dashoffset:314;animation:circleDraw 0.6s ease forwards;}
.checkmark-circle .check{fill:none;stroke:var(--primary-light);stroke-width:4;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:100;stroke-dashoffset:100;animation:checkDraw 0.4s 0.5s ease forwards;}
.success-card h4{font-family:var(--font-heading);font-weight:800;color:var(--text-primary);margin-bottom:0.5rem;}
.success-card p{color:var(--text-secondary);margin-bottom:1.25rem;}
.rsv-code{font-family:var(--font-mono);background:rgba(82,183,136,0.08);border-radius:10px;padding:0.6rem 1.5rem;font-weight:600;color:var(--primary);display:inline-block;margin-bottom:1.5rem;font-size:1.1rem;letter-spacing:1px;}
.btn-s-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border:none;border-radius:var(--btn-radius);padding:0.65rem 1.75rem;font-weight:700;transition:all 0.3s;text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;}
.btn-s-primary:hover{background:linear-gradient(135deg,var(--primary-dark),#0F2B1E);color:#fff;transform:translateY(-1px);}
.btn-s-outline{background:transparent;color:var(--primary);border:1.5px solid var(--primary);border-radius:var(--btn-radius);padding:0.65rem 1.75rem;font-weight:600;transition:all 0.3s;text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;}
.btn-s-outline:hover{background:rgba(45,106,79,0.06);color:var(--primary-dark);}
.confetti-piece{position:absolute;width:8px;height:8px;border-radius:2px;animation:confettiFall linear forwards;opacity:0;}

/* Terms Modal */
.terms-modal .modal-content{border:none;border-radius:var(--card-radius);}
.terms-modal .modal-header{background:linear-gradient(135deg,var(--primary-dark),var(--primary));color:#fff;border-radius:var(--card-radius) var(--card-radius) 0 0;border-bottom:none;}
.terms-modal .modal-header .btn-close{filter:invert(1);}
.terms-modal .modal-body{padding:1.5rem;color:var(--text-secondary);line-height:1.8;font-size:0.9rem;}
.terms-modal .modal-body h6{color:var(--text-primary);font-weight:700;margin-top:1rem;}
</style>
</head>
<body>
<div class="pelanggan-wrapper">
<?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>
<div class="pelanggan-main">
        <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>
    <div class="pelanggan-content">

<div class="checkout-page">
    <div class="toast-container" id="toastContainer"></div>

    <!-- Step Indicator -->
    <div class="saas-card stepper-card">
        <div class="stepper">
            <div class="step-item active" id="step1item">
                <div class="step-circle" id="step1circle">1</div>
                <span class="step-label">Detail Sewa</span>
            </div>
            <div class="step-line" id="line1"><div class="fill"></div></div>
            <div class="step-item" id="step2item">
                <div class="step-circle" id="step2circle">2</div>
                <span class="step-label">Pembayaran</span>
            </div>
            <div class="step-line" id="line2"><div class="fill"></div></div>
            <div class="step-item" id="step3item">
                <div class="step-circle" id="step3circle">3</div>
                <span class="step-label">Konfirmasi</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT: Steps -->
        <div class="col-lg-8">
            <!-- STEP 1 -->
            <div class="step-content active" id="stepContent1">
                <div class="saas-card">
                    <div class="card-title-c"><i class="bi bi-box-seam"></i> Detail Penyewaan</div>

                    <!-- Multi-item list with per-item dates -->
                    <?php foreach ($checkout_items as $idx => $ci):
                        $days = 0;
                        if (!empty($ci['tanggal_mulai']) && !empty($ci['tanggal_selesai'])) {
                            $days = (strtotime($ci['tanggal_selesai']) - strtotime($ci['tanggal_mulai'])) / 86400;
                        }
                        $subtotal = $ci['harga'] * $ci['jumlah'] * $days;
                    ?>
                    <div class="item-preview" style="<?= $idx > 0 ? 'margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid rgba(0,0,0,0.05);' : '' ?>; flex-direction:column; gap:10px;">
                        <div style="display:flex; gap:14px; align-items:flex-start; width:100%;">
                            <img src="<?= $ci['gambar'] ?>" alt="<?= $ci['nama'] ?>">
                            <div style="flex:1;">
                                <div class="ip-cat"><i class="bi bi-tag-fill"></i> <?= $ci['kategori'] ?></div>
                                <div class="ip-name"><?= $ci['nama'] ?></div>
                                <div class="ip-price">Rp <?= number_format($ci['harga'], 0, ',', '.') ?> <small>/hari</small></div>
                            </div>
                            <div style="text-align:right; min-width:80px;">
                                <div style="font-size:0.78rem; color:var(--text-secondary);">Jumlah</div>
                                <div style="font-weight:700; color:var(--primary); font-size:1.1rem;"><?= $ci['jumlah'] ?></div>
                            </div>
                        </div>
                        <div style="display:flex; gap:12px; flex-wrap:wrap; padding:8px 12px; background:rgba(45,106,79,0.03); border-radius:8px; font-size:0.78rem; width:100%;">
                            <span style="color:var(--text-secondary);"><i class="bi bi-calendar3 me-1"></i><?= !empty($ci['tanggal_mulai']) ? date('d M Y', strtotime($ci['tanggal_mulai'])) : '-' ?> → <?= !empty($ci['tanggal_selesai']) ? date('d M Y', strtotime($ci['tanggal_selesai'])) : '-' ?></span>
                            <span style="color:var(--primary); font-weight:700;"><i class="bi bi-clock me-1"></i><?= $days ?> hari</span>
                            <span style="margin-left:auto; font-family:var(--font-mono); font-weight:700; color:var(--accent-gold);">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div style="margin-top:0.75rem; padding:0.5rem 0.75rem; background:rgba(45,106,79,0.06); border-radius:8px; font-size:0.8rem; color:var(--primary);">
                        <i class="bi bi-lock-fill me-1"></i> Data diisi dari Keranjang. <a href="<?= BASE_URL ?>/pages/pelanggan/wishlist.php" style="color:var(--accent-gold); font-weight:600;">Ubah data</a>
                    </div>
                    <div class="calc-highlight">
                        <span class="ch-label"><?= count($checkout_items) ?> item</span>
                        <span class="ch-value" id="totalText">Rp 0</span>
                    </div>
                    <button class="btn-next" onclick="goToStep(2)">Lanjutkan ke Pembayaran <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <!-- STEP 2 -->
            <div class="step-content" id="stepContent2">
                <div class="saas-card">
                    <div class="card-title-c"><i class="bi bi-credit-card"></i> Metode Pembayaran</div>
                    <div class="pay-methods">
                        <label class="pay-card active" id="pcCash">
                            <input type="radio" name="payment" value="cash" checked>
                            <div class="pc-icon" style="background:rgba(212,163,115,0.12);color:var(--accent-gold);"><i class="bi bi-cash-stack"></i></div>
                            <div class="pc-title">Bayar di Tempat</div>
                            <div class="pc-desc">Bayar langsung di outlet</div>
                        </label>
                        <label class="pay-card" id="pcTransfer">
                            <input type="radio" name="payment" value="transfer">
                            <div class="pc-icon" style="background:rgba(45,106,79,0.1);color:var(--primary);"><i class="bi bi-bank"></i></div>
                            <div class="pc-title">Transfer Bank</div>
                            <div class="pc-desc">Virtual Account</div>
                        </label>
                        <label class="pay-card" id="pcQris">
                            <input type="radio" name="payment" value="qris">
                            <div class="pc-icon" style="background:rgba(82,183,136,0.1);color:var(--primary-light);"><i class="bi bi-qr-code"></i></div>
                            <div class="pc-title">QRIS</div>
                            <div class="pc-desc">Scan QR untuk bayar</div>
                        </label>
                    </div>

                    <!-- CASH Detail -->
                    <div class="pay-detail show" id="detCash">
                        <div class="cash-box">
                            <i class="bi bi-shop"></i>
                            <p>Bayar langsung di outlet kami saat pengambilan barang.</p>
                            <small style="color:var(--text-secondary);"><i class="bi bi-geo-alt-fill me-1" style="color:var(--accent-gold);"></i>Jl. Petualang No. 12, Bandung</small>
                        </div>
                    </div>

                    <!-- TRANSFER Detail (Multi-Bank) -->
                    <div class="pay-detail" id="detTransfer">
                        <div class="transfer-box">
                            <label class="fw-semibold d-block mb-2" style="font-size:0.88rem; color:var(--text-primary);">Pilih Bank</label>
                            <select id="bankSelect" class="form-select mb-3" onchange="showBankDetail()" style="border-radius:10px; font-size:0.88rem; border:2px solid #e5e7eb; padding:0.6rem 0.75rem;">
                                <option value="" disabled selected>-- Pilih Bank --</option>
                                <option value="bca">BCA (Bank Central Asia)</option>
                                <option value="bni">BNI (Bank Negara Indonesia)</option>
                                <option value="bri">BRI (Bank Rakyat Indonesia)</option>
                                <option value="mandiri">Mandiri</option>
                                <option value="bsi">BSI (Bank Syariah Indonesia)</option>
                                <option value="cimb">CIMB Niaga</option>
                                <option value="permata">Permata Bank</option>
                            </select>
                            <div id="bankDetailBox" style="display:none;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-bank2" style="color:var(--primary);font-size:1.2rem;"></i>
                                    <strong style="color:var(--text-primary);" id="bankNameDisplay">-</strong>
                                </div>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <span class="bank-number" id="bankNum">-</span>
                                    <button class="copy-btn" id="copyBtn" onclick="copyBank()"><i class="bi bi-clipboard"></i> Salin</button>
                                </div>
                                <small style="color:var(--text-secondary);">a/n <strong>SIMPEL-CAMP</strong></small>
                            </div>
                        </div>
                        <div class="upload-box" id="uploadBox" onclick="document.getElementById('uploadInput').click()">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <p>Seret & lepas atau klik untuk upload <strong>bukti transfer</strong></p>
                            <small style="color:var(--text-secondary);">Format: JPG, PNG, WebP (max 2MB)</small>
                            <input type="file" id="uploadInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleUpload(this)">
                            <div id="uploadPreview"></div>
                        </div>
                    </div>

                    <!-- QRIS Detail -->
                    <div class="pay-detail" id="detQris">
                        <div class="qris-box">
                            <p class="fw-bold" style="color:var(--text-primary);">Scan QR Code di bawah</p>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=SIMPELCAMP-PAY-<?= $barang_id ?>" alt="QR Code" width="200" height="200" style="border-radius:10px;border:3px solid #f5f5f5;">
                            <div class="qris-timer" id="qrisTimer">15:00</div>
                            <div style="font-size:0.78rem; color:#dc3545; margin-top:0.25rem;"><i class="bi bi-exclamation-circle me-1"></i>Selesaikan pembayaran sebelum waktu habis</div>
                            <div class="ewallet-pills">
                                <span class="ewallet-pill">GoPay</span><span class="ewallet-pill">OVO</span><span class="ewallet-pill">DANA</span><span class="ewallet-pill">ShopeePay</span>
                            </div>
                            <p style="color:var(--text-secondary);font-size:0.82rem;margin-top:0.5rem;">Scan menggunakan e-wallet atau mobile banking</p>
                        </div>
                        <div class="upload-box" id="uploadBoxQris" onclick="document.getElementById('uploadInputQris').click()">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <p>Upload <strong>screenshot bukti bayar QRIS</strong></p>
                            <small style="color:var(--text-secondary);">Format: JPG, PNG, WebP (max 2MB)</small>
                            <input type="file" id="uploadInputQris" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleUploadQris(this)">
                            <div id="uploadPreviewQris"></div>
                        </div>
                    </div>

                    <button class="btn-next" onclick="goToStep(3)">Lanjutkan ke Konfirmasi <i class="bi bi-arrow-right"></i></button>
                    <button class="btn-back" onclick="goToStep(1)"><i class="bi bi-arrow-left"></i> Kembali</button>
                </div>
            </div>

            <!-- STEP 3 -->
            <div class="step-content" id="stepContent3">
                <div class="saas-card">
                    <div class="card-title-c"><i class="bi bi-clipboard-check"></i> Konfirmasi Pemesanan</div>
                    <div style="background:#F8FAF9;border-radius:14px;padding:1.25rem;margin-bottom:1.25rem;">
                        <!-- Items list -->
                        <?php foreach ($checkout_items as $idx => $ci): ?>
                        <div class="d-flex gap-3 align-items-center <?= $idx < count($checkout_items) - 1 ? 'mb-3 pb-3' : 'mb-3 pb-3' ?>" style="<?= $idx < count($checkout_items) - 1 ? 'border-bottom:1px solid rgba(107,114,128,0.06);' : 'border-bottom:1px solid rgba(107,114,128,0.06);' ?>">
                            <img src="<?= $ci['gambar'] ?>" alt="<?= $ci['nama'] ?>" style="width:48px;height:48px;border-radius:10px;object-fit:cover;">
                            <div style="flex:1;">
                                <div style="font-weight:600;color:var(--text-primary);font-size:0.88rem;"><?= $ci['nama'] ?></div>
                                <div style="font-size:0.78rem;color:var(--text-secondary);">Rp <?= number_format($ci['harga'], 0, ',', '.') ?>/hari × <?= $ci['jumlah'] ?> unit</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="review-row"><span class="rr-label">Total Item</span><span class="rr-value"><?= count($checkout_items) ?> barang</span></div>
                        <div class="review-row"><span class="rr-label">Tanggal Mulai</span><span class="rr-value" id="rvMulai">-</span></div>
                        <div class="review-row"><span class="rr-label">Tanggal Selesai</span><span class="rr-value" id="rvSelesai">-</span></div>
                        <div class="review-row"><span class="rr-label">Durasi Sewa</span><span class="rr-value" id="rvDurasi">0 hari</span></div>
                        <div class="review-row"><span class="rr-label">Subtotal</span><span class="rr-value" id="rvSubtotal">Rp 0</span></div>
                        <?php if ($diskon_persen > 0): ?>
                        <div class="review-row"><span class="rr-label" style="color:var(--accent-gold);"><i class="bi bi-star-fill me-1"></i>Diskon <?= $member_level ?> -<?= $diskon_persen ?>%</span><span class="rr-value" style="color:var(--accent-gold);" id="rvDiskon">- Rp 0</span></div>
                        <?php endif; ?>
                        <div class="review-row" style="border-bottom:none;padding-top:0.75rem;">
                            <span class="rr-label" style="font-weight:700;color:var(--text-primary);font-size:0.95rem;">Grand Total</span>
                            <span class="rr-value" style="font-size:1.1rem;color:var(--text-primary);font-family:var(--font-heading);font-weight:800;" id="rvGrandTotal">Rp 0</span>
                        </div>
                        <div class="review-row" style="border-top:1px solid rgba(107,114,128,0.06);margin-top:0.5rem;padding-top:0.75rem;">
                            <span class="rr-label">Metode Pembayaran</span>
                            <span class="rr-value" id="rvPayment">Cash</span>
                        </div>
                    </div>
                    <div class="terms-check">
                        <input type="checkbox" id="agreeTerms" onchange="toggleConfirmBtn()">
                        <label for="agreeTerms">Saya menyetujui <a onclick="openTermsModal()" style="cursor:pointer;">syarat dan ketentuan</a> penyewaan SIMPEL-CAMP</label>
                    </div>
                    <button class="btn-next" id="btnConfirm" onclick="submitOrder()" disabled><i class="bi bi-shield-check"></i> Konfirmasi Pemesanan</button>
                    <button class="btn-back" onclick="goToStep(2)"><i class="bi bi-arrow-left"></i> Kembali</button>
                </div>
            </div>
        </div>

        <!-- RIGHT: Summary -->
        <div class="col-lg-4">
            <div class="summary-card">
                <div class="sum-header"><i class="bi bi-receipt-cutoff"></i> Ringkasan Pesanan</div>
                <div class="sum-body">
                    <?php foreach ($checkout_items as $ci): ?>
                    <div class="sum-thumb" style="margin-bottom:0.5rem;">
                        <img src="<?= $ci['gambar'] ?>" alt="<?= $ci['nama'] ?>">
                        <div>
                            <div class="st-name"><?= $ci['nama'] ?></div>
                            <div class="st-price">Rp <?= number_format($ci['harga'], 0, ',', '.') ?>/hari × <?= $ci['jumlah'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="sum-row"><span class="sr-label">Durasi Sewa</span><span class="sr-value" id="sumDurasi">0 hari</span></div>
                    <div class="sum-row"><span class="sr-label">Total Item</span><span class="sr-value"><?= count($checkout_items) ?> barang</span></div>
                    <hr class="sum-divider">
                    <div class="sum-row"><span class="sr-label">Subtotal</span><span class="sr-value" id="sumSubtotal">Rp 0</span></div>
                    <?php if ($diskon_persen > 0): ?>
                    <div class="sum-discount">
                        <span class="sd-label"><i class="bi bi-star-fill"></i> Diskon <?= $member_level ?> -<?= $diskon_persen ?>%</span>
                        <span class="sd-value" id="sumDiskon">- Rp 0</span>
                    </div>
                    <?php endif; ?>
                    <!-- Promo Code Input -->
                    <div class="promo-input-section" style="margin-top:12px; padding:14px; background:rgba(45,106,79,0.03); border-radius:12px; border:1px dashed rgba(45,106,79,0.15);">
                        <div style="font-size:0.78rem; font-weight:700; color:#2D6A4F; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                            <i class="bi bi-ticket-perforated"></i> Punya kode promo?
                        </div>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="promoCodeInput" placeholder="Masukkan kode" style="flex:1; padding:8px 12px; border:1.5px solid #E5E7EB; border-radius:10px; font-size:0.82rem; font-weight:600; font-family:'JetBrains Mono',monospace; text-transform:uppercase; outline:none; transition:border-color 0.2s;" onfocus="this.style.borderColor='#52B788'" onblur="this.style.borderColor='#E5E7EB'">
                            <button type="button" id="promoApplyBtn" onclick="applyPromo()" style="padding:8px 16px; border:none; border-radius:10px; background:linear-gradient(135deg,#2D6A4F,#52B788); color:#fff; font-weight:700; font-size:0.78rem; cursor:pointer; white-space:nowrap; transition:all 0.2s;">
                                Terapkan
                            </button>
                        </div>
                        <div id="promoResult" style="display:none; margin-top:8px;"></div>
                    </div>
                    <!-- Promo Discount Row (hidden by default) -->
                    <div class="sum-discount" id="promoDiscountRow" style="display:none; margin-top:8px;">
                        <span class="sd-label" id="promoDiscLabel"><i class="bi bi-ticket-perforated"></i> Promo</span>
                        <span class="sd-value" id="sumPromoDiskon">- Rp 0</span>
                    </div>
                    <div class="sum-total">
                        <span class="st-label">Grand Total</span>
                        <span class="st-value" id="sumGrandTotal">Rp 0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade terms-modal" id="termsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Syarat & Ketentuan Penyewaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>1. Deposit</h6><p>Setiap penyewaan dikenakan deposit sebesar Rp 100.000 yang akan dikembalikan setelah barang dikembalikan dalam kondisi baik.</p>
                <h6>2. Denda Keterlambatan</h6><p>Keterlambatan pengembalian barang dikenakan denda sebesar Rp 25.000 per hari.</p>
                <h6>3. Kerusakan Barang</h6><p>Kerusakan di luar pemakaian normal menjadi tanggung jawab penyewa.</p>
                <h6>4. Kebijakan Pembatalan</h6><p>Pembatalan lebih dari 24 jam sebelum tanggal sewa mendapat pengembalian penuh. Kurang dari 24 jam dikenakan 50%.</p>
                <h6>5. Maksimal Durasi Sewa</h6><p>Durasi sewa maksimal 14 hari per transaksi.</p>
                <h6>6. Kondisi Pengembalian</h6><p>Barang wajib dikembalikan dalam kondisi bersih dan kering.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-s-primary" data-bs-dismiss="modal"><i class="bi bi-check-lg"></i> Saya Mengerti</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Overlay -->
<div class="success-overlay" id="successOverlay">
    <div class="success-card" id="successCard">
        <div class="checkmark-circle">
            <svg viewBox="0 0 100 100"><circle class="circle" cx="50" cy="50" r="45"/><path class="check" d="M30 52 L44 66 L70 38"/></svg>
        </div>
        <h4>Pemesanan Berhasil! 🎉</h4>
        <p>Pesanan Anda telah berhasil dibuat. Silakan lakukan pembayaran sesuai metode yang dipilih.</p>
        <div class="rsv-code" id="rsvCodeDisplay">-</div>
        <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
            <a href="<?= BASE_URL ?>/pages/pelanggan/transaksi.php" class="btn-s-primary"><i class="bi bi-list-check"></i> Lihat Transaksi</a>
            <a href="<?= BASE_URL ?>/pages/pelanggan/katalog.php" class="btn-s-outline"><i class="bi bi-arrow-left"></i> Kembali ke Katalog</a>
        </div>
    </div>
</div>

    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
const CHECKOUT_ITEMS = <?= $items_json ?>;
const DISKON = <?= $diskon_persen / 100 ?>;
let currentStep=1, qrisInterval=null, qrisSeconds=900;

// Bank data
const bankData = {
    bca:     { name: 'BCA', number: '123-456-7890' },
    bni:     { name: 'BNI', number: '987-654-3210' },
    bri:     { name: 'BRI', number: '456-789-0123' },
    mandiri: { name: 'Mandiri', number: '789-012-3456' },
    bsi:     { name: 'BSI', number: '321-654-0987' },
    cimb:    { name: 'CIMB Niaga', number: '654-321-0987' },
    permata: { name: 'Permata', number: '012-345-6789' }
};

function showBankDetail() {
    const select = document.getElementById('bankSelect');
    const box = document.getElementById('bankDetailBox');
    const bank = bankData[select.value];
    if (bank) {
        document.getElementById('bankNameDisplay').textContent = bank.name;
        document.getElementById('bankNum').textContent = bank.number;
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}

function formatRp(n){return 'Rp '+n.toLocaleString('id-ID');}

function calcDays(mulai, selesai) {
    if (!mulai || !selesai) return 0;
    const d = (new Date(selesai) - new Date(mulai)) / (1000*60*60*24);
    return d > 0 ? Math.ceil(d) : 0;
}

let currentPromo = null; // { promo_id, kode, diskon, label }

function calcTotal(){
    let sub = 0;
    CHECKOUT_ITEMS.forEach(item => {
        const days = calcDays(item.tanggal_mulai, item.tanggal_selesai);
        sub += item.harga * item.jumlah * days;
    });
    const disc = Math.round(sub * DISKON);
    const afterMember = sub - disc;
    const promoDisc = currentPromo ? currentPromo.diskon : 0;
    const grand = afterMember - promoDisc;

    document.getElementById('totalText').textContent = formatRp(sub);
    document.getElementById('sumDurasi').textContent = CHECKOUT_ITEMS.length + ' item';
    document.getElementById('sumSubtotal').textContent = formatRp(sub);
    if(document.getElementById('sumDiskon')) document.getElementById('sumDiskon').textContent = '- ' + formatRp(disc);

    // Promo discount
    const promoRow = document.getElementById('promoDiscountRow');
    if (currentPromo && promoDisc > 0) {
        promoRow.style.display = 'flex';
        document.getElementById('promoDiscLabel').innerHTML = '<i class="bi bi-ticket-perforated"></i> ' + currentPromo.label;
        document.getElementById('sumPromoDiskon').textContent = '- ' + formatRp(promoDisc);
    } else {
        promoRow.style.display = 'none';
    }

    document.getElementById('sumGrandTotal').textContent = formatRp(Math.max(0, grand));
    if(document.getElementById('rvMulai')) document.getElementById('rvMulai').textContent = 'Per item';
    if(document.getElementById('rvSelesai')) document.getElementById('rvSelesai').textContent = 'Per item';
    document.getElementById('rvDurasi').textContent = 'Per item';
    document.getElementById('rvSubtotal').textContent = formatRp(sub);
    if(document.getElementById('rvDiskon')) document.getElementById('rvDiskon').textContent = '- ' + formatRp(disc);
    document.getElementById('rvGrandTotal').textContent = formatRp(Math.max(0, grand));
}

function applyPromo() {
    const input = document.getElementById('promoCodeInput');
    const btn = document.getElementById('promoApplyBtn');
    const result = document.getElementById('promoResult');
    const kode = input.value.trim().toUpperCase();

    if (!kode) { showToast('Masukkan kode promo', 'warning'); return; }

    // Calc subtotal for validation
    let sub = 0;
    CHECKOUT_ITEMS.forEach(item => {
        const days = calcDays(item.tanggal_mulai, item.tanggal_selesai);
        sub += item.harga * item.jumlah * days;
    });

    btn.textContent = '...';
    btn.disabled = true;

    fetch(BASE_URL + '/api/promo.php?action=validate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ kode, subtotal: sub })
    })
    .then(r => r.json())
    .then(data => {
        btn.textContent = 'Terapkan';
        btn.disabled = false;
        result.style.display = 'block';

        if (data.success) {
            currentPromo = data.data;
            result.innerHTML = '<div style="display:flex;align-items:center;gap:6px;color:#2D6A4F;font-size:0.78rem;font-weight:600;"><i class="bi bi-check-circle-fill"></i> ' + data.data.label + ' — Hemat ' + formatRp(data.data.diskon) + ' <button onclick="removePromo()" style="margin-left:auto;background:none;border:none;color:#EF4444;cursor:pointer;font-size:0.75rem;font-weight:600;"><i class="bi bi-x-lg"></i> Hapus</button></div>';
            input.style.borderColor = '#52B788';
            showToast('Promo diterapkan! Hemat ' + formatRp(data.data.diskon), 'success');
            calcTotal();
        } else {
            currentPromo = null;
            result.innerHTML = '<div style="color:#EF4444;font-size:0.78rem;font-weight:600;"><i class="bi bi-x-circle-fill"></i> ' + data.message + '</div>';
            input.style.borderColor = '#EF4444';
            calcTotal();
        }
    })
    .catch(() => {
        btn.textContent = 'Terapkan';
        btn.disabled = false;
        showToast('Gagal validasi promo', 'warning');
    });
}

function removePromo() {
    currentPromo = null;
    document.getElementById('promoCodeInput').value = '';
    document.getElementById('promoCodeInput').style.borderColor = '#E5E7EB';
    document.getElementById('promoResult').style.display = 'none';
    calcTotal();
    showToast('Promo dihapus', 'info');
}

calcTotal();

function goToStep(n){
    if(n===2&&currentStep===1){ /* dates already validated per-item from wishlist */ }
    if(n===3&&currentStep===2){
        const pm=document.querySelector('input[name="payment"]:checked').value;
        if(pm==='transfer'){
            const bankSel=document.getElementById('bankSelect').value;
            if(!bankSel){showToast('Pilih bank terlebih dahulu','warning');return;}
            if(!document.getElementById('uploadInput').files[0]){showToast('Upload bukti transfer terlebih dahulu','warning');return;}
        }
        if(pm==='qris'){
            if(!document.getElementById('uploadInputQris').files[0]){showToast('Upload screenshot bukti bayar QRIS','warning');return;}
        }
    }
    currentStep=n;
    document.querySelectorAll('.step-content').forEach(s=>s.classList.remove('active'));
    document.getElementById('stepContent'+n).classList.add('active');
    for(let i=1;i<=3;i++){const item=document.getElementById('step'+i+'item'),circle=document.getElementById('step'+i+'circle');item.classList.remove('active','completed');if(i<n){item.classList.add('completed');circle.innerHTML='<i class="bi bi-check-lg"></i>';}else if(i===n){item.classList.add('active');circle.textContent=i;}else{circle.textContent=i;}}
    document.getElementById('line1').classList.toggle('filled',n>=2);
    document.getElementById('line2').classList.toggle('filled',n>=3);
    if(n===3){
        const payMethod=document.querySelector('input[name="payment"]:checked').value;
        const labels={cash:'Bayar di Tempat',transfer:'Transfer Bank',qris:'QRIS'};
        let label = labels[payMethod]||payMethod;
        if(payMethod==='transfer'){
            const bankSel=document.getElementById('bankSelect');
            if(bankSel.value) label += ' ('+bankData[bankSel.value].name+')';
        }
        document.getElementById('rvPayment').textContent=label;
        calcTotal();
    }
    if(n===2){const pm=document.querySelector('input[name="payment"]:checked').value;if(pm==='qris')startQrisTimer();}
    document.querySelector('.pelanggan-content').scrollTo({top:0,behavior:'smooth'});
}

const payCards=document.querySelectorAll('.pay-card');
const payDetails={cash:document.getElementById('detCash'),transfer:document.getElementById('detTransfer'),qris:document.getElementById('detQris')};
payCards.forEach(card=>{card.addEventListener('click',function(){payCards.forEach(c=>c.classList.remove('active'));this.classList.add('active');const method=this.querySelector('input[type="radio"]').value;Object.values(payDetails).forEach(d=>d.classList.remove('show'));payDetails[method].classList.add('show');if(method==='qris')startQrisTimer();else stopQrisTimer();});});

function copyBank(){const num=document.getElementById('bankNum').textContent.replace(/-/g,'');navigator.clipboard.writeText(num).then(()=>{const btn=document.getElementById('copyBtn');btn.innerHTML='<i class="bi bi-check-lg"></i> Tersalin!';btn.classList.add('copied');showToast('Nomor rekening berhasil disalin!','success');setTimeout(()=>{btn.innerHTML='<i class="bi bi-clipboard"></i> Salin';btn.classList.remove('copied');},2500);});}

const uploadBox=document.getElementById('uploadBox');
uploadBox.addEventListener('dragover',e=>{e.preventDefault();uploadBox.classList.add('dragover');});
uploadBox.addEventListener('dragleave',()=>uploadBox.classList.remove('dragover'));
uploadBox.addEventListener('drop',e=>{e.preventDefault();uploadBox.classList.remove('dragover');const input=document.getElementById('uploadInput');input.files=e.dataTransfer.files;handleUpload(input);});
function handleUpload(input){const preview=document.getElementById('uploadPreview');preview.innerHTML='';if(input.files&&input.files[0]){const file=input.files[0];if(file.size>2*1024*1024){showToast('File terlalu besar! Max 2MB','warning');input.value='';return;}let html='<div class="upload-preview">';if(file.type.startsWith('image/')){const reader=new FileReader();reader.onload=function(e){html+='<img src="'+e.target.result+'" alt="Preview">';html+='<div><div style="font-size:0.82rem;font-weight:600;color:var(--text-primary);">'+file.name+'</div>';html+='<div class="progress-fake"><div class="bar"></div></div></div></div>';preview.innerHTML=html;};reader.readAsDataURL(file);}else{html+='<i class="bi bi-file-earmark" style="font-size:2rem;color:var(--primary);"></i>';html+='<div><div style="font-size:0.82rem;font-weight:600;color:var(--text-primary);">'+file.name+'</div>';html+='<div class="progress-fake"><div class="bar"></div></div></div></div>';preview.innerHTML=html;}showToast('File berhasil diupload','success');}}

function handleUploadQris(input){const preview=document.getElementById('uploadPreviewQris');preview.innerHTML='';if(input.files&&input.files[0]){const file=input.files[0];if(file.size>2*1024*1024){showToast('File terlalu besar! Max 2MB','warning');input.value='';return;}const reader=new FileReader();reader.onload=function(e){preview.innerHTML='<div class="upload-preview"><img src="'+e.target.result+'" alt="Preview"><div><div style="font-size:0.82rem;font-weight:600;color:var(--text-primary);">'+file.name+'</div><div class="progress-fake"><div class="bar"></div></div></div></div>';};reader.readAsDataURL(file);showToast('Bukti QRIS berhasil diupload','success');}}

function startQrisTimer(){stopQrisTimer();qrisSeconds=900;updateTimerDisplay();qrisInterval=setInterval(()=>{qrisSeconds--;if(qrisSeconds<=0){stopQrisTimer();document.getElementById('qrisTimer').textContent='Expired';document.getElementById('qrisTimer').style.color='#dc3545';showToast('QR Code telah expired. Silakan refresh halaman.','warning');}else updateTimerDisplay();},1000);}
function stopQrisTimer(){if(qrisInterval){clearInterval(qrisInterval);qrisInterval=null;}}
function updateTimerDisplay(){const m=Math.floor(qrisSeconds/60),s=qrisSeconds%60;const timer=document.getElementById('qrisTimer');timer.textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');timer.style.color=qrisSeconds<=60?'#dc3545':'var(--primary)';}

function openTermsModal(){const modal=new bootstrap.Modal(document.getElementById('termsModal'));modal.show();}
function toggleConfirmBtn(){document.getElementById('btnConfirm').disabled=!document.getElementById('agreeTerms').checked;}

function submitOrder(){
    const btn=document.getElementById('btnConfirm');
    const orig=btn.innerHTML;
    btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';
    btn.disabled=true;

    const payMethod = document.querySelector('input[name="payment"]:checked').value;

    // Step 1: Create reservation via API
    const payload = {
        items: CHECKOUT_ITEMS.map(i => ({
            barang_id: i.barang_id,
            jumlah: i.jumlah,
            tanggal_mulai: i.tanggal_mulai,
            tanggal_selesai: i.tanggal_selesai
        })),
        catatan: '',
        metode_bayar: payMethod,
        promo_kode: currentPromo ? currentPromo.kode : null
    };

    fetch(BASE_URL + '/api/reservasi.php?action=create_and_pay', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.text())
    .then(text => { console.log('API response:', text); try { return JSON.parse(text); } catch(e) { throw new Error('Invalid JSON: ' + text.substring(0,300)); } })
    .then(data => {
        if (data.success) {
            const trxId = data.data.transaksi_id;
            const kode = data.data.kode_reservasi || '-';

            // Step 2: If transfer/qris — upload bukti bayar
            if ((payMethod === 'transfer' || payMethod === 'qris') && trxId) {
                const fileInput = payMethod === 'transfer'
                    ? document.getElementById('uploadInput')
                    : document.getElementById('uploadInputQris');
                const file = fileInput ? fileInput.files[0] : null;

                if (file) {
                    const formData = new FormData();
                    formData.append('transaksi_id', trxId);
                    formData.append('metode', payMethod);
                    formData.append('jumlah', data.data.total_biaya || 0);
                    formData.append('bukti_bayar', file);
                    formData.append('catatan', 'Pembayaran via ' + payMethod.toUpperCase());

                    fetch(BASE_URL + '/api/pembayaran.php?action=create', {
                        method: 'POST',
                        body: formData
                    }).then(r => r.json()).then(payData => {
                        document.getElementById('rsvCodeDisplay').textContent = kode;
                        showSuccessModal();
                    }).catch(() => {
                        document.getElementById('rsvCodeDisplay').textContent = kode;
                        showSuccessModal();
                    });
                } else {
                    document.getElementById('rsvCodeDisplay').textContent = kode;
                    showSuccessModal();
                }
            } else {
                // Cash — no upload needed
                document.getElementById('rsvCodeDisplay').textContent = kode;
                showSuccessModal();
            }
        } else {
            btn.innerHTML = orig;
            btn.disabled = false;
            showToast(data.message || 'Gagal membuat reservasi', 'warning');
        }
    })
    .catch(err => {
        btn.innerHTML = orig;
        btn.disabled = false;
        showToast('Terjadi kesalahan jaringan. Silakan coba lagi.', 'warning');
        console.error('submitOrder error:', err);
    });
}
function showSuccessModal(){document.getElementById('successOverlay').classList.add('active');document.body.style.overflow='hidden';createConfetti();}
function createConfetti(){const card=document.getElementById('successCard');const colors=['#52B788','#2D6A4F','#D4A373','#E9C46A','#1B4332','#40916C','#ff6b6b','#ffd93d'];for(let i=0;i<50;i++){const piece=document.createElement('div');piece.className='confetti-piece';piece.style.left=Math.random()*100+'%';piece.style.top='-10px';piece.style.background=colors[Math.floor(Math.random()*colors.length)];piece.style.width=(Math.random()*8+4)+'px';piece.style.height=(Math.random()*8+4)+'px';piece.style.animationDuration=(Math.random()*2+1.5)+'s';piece.style.animationDelay=(Math.random()*0.8)+'s';piece.style.opacity='1';card.appendChild(piece);}}

function showToast(message,type='info'){const container=document.getElementById('toastContainer');const toast=document.createElement('div');toast.className='custom-toast '+type;const icons={success:'bi-check-circle-fill',info:'bi-info-circle-fill',warning:'bi-exclamation-triangle-fill'};toast.innerHTML='<div class="ti"><i class="bi '+(icons[type]||icons.info)+'"></i></div><span>'+message+'</span>';container.appendChild(toast);setTimeout(()=>{toast.classList.add('hiding');setTimeout(()=>toast.remove(),300);},3000);}
</script>
</body>
</html>
