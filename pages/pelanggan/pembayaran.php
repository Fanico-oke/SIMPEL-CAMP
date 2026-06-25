<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';
require_once dirname(__DIR__, 2) . '/classes/Pembayaran.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Pembayaran'; $current_page = 'pembayaran';
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// Load transaction for payment - get from URL param or latest pending
$trx_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$transaksi = null;
$reservasiDetail = [];

if ($trx_id > 0) {
    $transaksi = Transaksi::getById($trx_id);
    // Verify ownership
    if ($transaksi && $transaksi['user_id'] != $user_id) {
        $transaksi = null;
    }
}

// If no specific transaction, get the latest pending one
if (!$transaksi) {
    $pendingTrx = Transaksi::getByUser($user_id, 'menunggu_bayar');
    if (!empty($pendingTrx)) {
        $transaksi = Transaksi::getById($pendingTrx[0]['id']);
    }
}

if ($transaksi && !empty($transaksi['reservasi_id'])) {
    $reservasiDetail = Reservasi::getDetail($transaksi['reservasi_id']);
}
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
@keyframes fadeSlideUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes scaleIn{from{transform:scale(0.85);opacity:0}to{transform:scale(1);opacity:1}}
@keyframes checkDraw{to{stroke-dashoffset:0}}
@keyframes confettiFall{0%{opacity:1;transform:translateY(0) rotate(0deg)}100%{opacity:0;transform:translateY(100vh) rotate(720deg)}}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes timerPulse{0%,100%{opacity:1}50%{opacity:0.4}}
@keyframes toastSlideUp{from{opacity:0;transform:translateY(20px) scale(0.95)}to{opacity:1;transform:translateY(0) scale(1)}}

.saas-card{background:var(--bg-card);border-radius:var(--card-radius);box-shadow:var(--card-shadow);border:none;overflow:hidden;}
.animate-fade-up{opacity:0;transform:translateY(16px);animation:fadeSlideUp 0.5s cubic-bezier(0.4,0,0.2,1) forwards;}

/* Timer Banner */
.timer-banner{padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.timer-left{display:flex;align-items:center;gap:12px;}
.timer-icon-wrap{width:46px;height:46px;border-radius:13px;background:rgba(212,163,115,0.15);color:var(--accent-gold);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.timer-info .timer-label{font-size:0.8rem;color:var(--text-secondary);font-weight:500;margin-bottom:2px;}
.order-id-badge{font-family:var(--font-mono);font-weight:700;font-size:0.82rem;color:var(--primary);background:rgba(82,183,136,0.1);padding:4px 12px;border-radius:var(--btn-radius);display:inline-flex;align-items:center;gap:5px;}
.timer-countdown{font-family:var(--font-mono);font-weight:700;font-size:1.8rem;color:var(--accent-gold);letter-spacing:3px;line-height:1;}
.timer-countdown.timer-urgent{color:#dc2626;animation:timerPulse 1s ease infinite;}

/* Pay Card Section */
.pay-card-header{padding:1.25rem 1.5rem;border-bottom:1px solid rgba(107,114,128,0.06);display:flex;align-items:center;gap:10px;}
.pay-card-header h4{font-family:var(--font-heading);font-weight:700;font-size:1.1rem;margin:0;color:var(--text-primary);}
.pay-card-header i{color:var(--primary);font-size:1.2rem;}
.pay-card-body{padding:1.5rem;}

/* Sticky Summary */
.summary-sticky{position:sticky;top:100px;}
@media(max-width:991px){.summary-sticky{position:static;}}

/* Method Option */
.method-option{border:2px solid rgba(107,114,128,0.08);border-radius:16px;padding:0;cursor:pointer;transition:all 0.35s cubic-bezier(0.4,0,0.2,1);position:relative;overflow:hidden;background:var(--bg-card);}
.method-option:hover{border-color:rgba(82,183,136,0.25);box-shadow:0 4px 20px rgba(82,183,136,0.06);}
.method-option.active{border-color:var(--primary);background:rgba(82,183,136,0.02);box-shadow:0 0 0 3px rgba(82,183,136,0.1);}
.method-option input[type="radio"]{display:none;}
.method-option-header{display:flex;align-items:center;gap:12px;padding:1.25rem;}
.method-radio{width:22px;height:22px;border:2px solid #d1d5db;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.3s ease;}
.method-option.active .method-radio{border-color:var(--primary);}
.method-radio::after{content:'';width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));opacity:0;transition:all 0.3s ease;transform:scale(0);}
.method-option.active .method-radio::after{opacity:1;transform:scale(1);}
.method-icon{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
.method-icon.m-cash{background:rgba(16,185,129,0.12);color:#059669;}
.method-icon.m-bank{background:rgba(59,130,246,0.12);color:#3b82f6;}
.method-icon.m-qris{background:rgba(139,92,246,0.12);color:#8b5cf6;}
.method-title{font-weight:700;font-size:0.95rem;color:var(--text-primary);}
.method-desc{font-size:0.8rem;color:var(--text-secondary);margin:0;}

/* Expandable */
.method-expand{max-height:0;overflow:hidden;transition:max-height 0.5s cubic-bezier(0.4,0,0.2,1),padding 0.4s ease;padding:0 1.25rem;}
.method-expand.show{max-height:1200px;padding:0 1.25rem 1.25rem;}

/* Cash Details */
.cash-details{background:#F8FAF9;border-radius:var(--input-radius);padding:1.1rem;}
.cash-info-row{display:flex;align-items:flex-start;gap:10px;padding:6px 0;font-size:0.85rem;}
.cash-info-row i{color:#059669;margin-top:2px;font-size:1rem;}
.cash-info-row .c-label{color:var(--text-secondary);font-size:0.78rem;}
.cash-info-row .c-value{font-weight:600;color:var(--text-primary);}

/* Bank List */
.bank-list{display:flex;flex-direction:column;gap:10px;}
.bank-item{display:flex;align-items:center;justify-content:space-between;background:#F8FAF9;border-radius:var(--input-radius);padding:14px 16px;cursor:pointer;transition:all 0.3s ease;}
.bank-item:hover{box-shadow:0 4px 15px rgba(59,130,246,0.06);}
.bank-item.selected{background:rgba(59,130,246,0.06);box-shadow:0 0 0 2px rgba(59,130,246,0.2);}
.bank-item-left{display:flex;align-items:center;gap:12px;}
.bank-logo{width:40px;height:40px;border-radius:10px;background:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.7rem;color:#3b82f6;font-family:var(--font-heading);box-shadow:0 1px 4px rgba(0,0,0,0.06);}
.bank-name{font-weight:700;font-size:0.88rem;color:var(--text-primary);}
.bank-va{font-family:var(--font-mono);font-size:0.78rem;color:var(--text-secondary);letter-spacing:1px;}
.btn-copy-va{background:rgba(59,130,246,0.08);border:none;color:#3b82f6;padding:6px 14px;border-radius:var(--btn-radius);font-size:0.78rem;font-weight:600;cursor:pointer;transition:all 0.25s ease;display:inline-flex;align-items:center;gap:5px;flex-shrink:0;}
.btn-copy-va:hover{background:rgba(59,130,246,0.15);transform:translateY(-1px);}
.btn-copy-va.copied{background:#059669;color:#fff;}

/* Upload */
.upload-section{margin-top:16px;}
.upload-label{font-weight:700;font-size:0.88rem;color:var(--text-primary);margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.upload-area{border:2px dashed rgba(82,183,136,0.25);border-radius:14px;padding:2rem;text-align:center;cursor:pointer;transition:all 0.3s ease;position:relative;background:rgba(82,183,136,0.02);}
.upload-area:hover,.upload-area.dragover{border-color:var(--primary-light);background:rgba(82,183,136,0.05);}
.upload-area input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;}
.upload-icon{font-size:2.2rem;color:var(--primary-light);margin-bottom:8px;}
.upload-preview{margin-top:12px;display:none;text-align:center;}
.upload-preview.show{display:block;animation:fadeSlideUp 0.3s ease;}
.upload-preview img{max-width:200px;border-radius:var(--input-radius);box-shadow:0 4px 15px rgba(0,0,0,0.08);}
.btn-remove-preview{display:inline-flex;align-items:center;gap:5px;margin-top:10px;padding:6px 14px;border-radius:var(--btn-radius);border:1.5px solid rgba(220,38,38,0.3);background:rgba(220,38,38,0.06);color:#dc2626;font-size:0.8rem;font-weight:600;cursor:pointer;transition:all 0.25s ease;}
.btn-remove-preview:hover{background:rgba(220,38,38,0.12);}

/* QRIS */
.qris-content{text-align:center;}
.qris-container{background:#fff;border-radius:18px;padding:1.5rem;display:inline-block;box-shadow:0 4px 20px rgba(139,92,246,0.06);}
.qris-container img{width:220px;height:220px;border-radius:8px;}
.ewallet-badges{display:flex;justify-content:center;gap:8px;margin-top:16px;flex-wrap:wrap;}
.ewallet-badge{padding:5px 14px;border-radius:var(--btn-radius);font-size:0.75rem;font-weight:700;background:rgba(139,92,246,0.08);color:#7c3aed;}
.qris-timer-pill{display:inline-flex;align-items:center;gap:6px;margin-top:14px;padding:6px 14px;border-radius:var(--btn-radius);background:rgba(139,92,246,0.06);color:#7c3aed;font-size:0.82rem;font-weight:600;}
.qris-timer-pill .time{font-family:var(--font-mono);font-weight:700;}

/* Confirm Button */
.btn-confirm-pay{background:linear-gradient(135deg,var(--primary),var(--primary-light));border:none;color:#fff;padding:16px;border-radius:var(--btn-radius);font-weight:700;font-size:1.05rem;font-family:var(--font-body);width:100%;transition:all 0.35s;display:inline-flex;align-items:center;justify-content:center;gap:10px;cursor:pointer;position:relative;overflow:hidden;}
.btn-confirm-pay:hover{background:linear-gradient(135deg,var(--primary-dark),var(--primary));color:#fff;transform:translateY(-2px);box-shadow:0 10px 30px rgba(45,106,79,0.3);}
.btn-confirm-pay:active{transform:translateY(0);}
.btn-confirm-pay::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent);transform:translateX(-100%);transition:transform 0.6s ease;}
.btn-confirm-pay:hover::after{transform:translateX(100%);}

/* Cancel Link */
.cancel-link{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;margin-top:10px;color:#dc2626;font-size:0.85rem;font-weight:600;cursor:pointer;border-radius:var(--btn-radius);transition:all 0.25s ease;background:none;border:none;width:100%;}
.cancel-link:hover{background:rgba(220,38,38,0.06);}

/* Order Items */
.order-item{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid rgba(107,114,128,0.06);}
.order-item:last-child{border-bottom:none;}
.order-item-icon{width:48px;height:48px;border-radius:var(--input-radius);background:rgba(82,183,136,0.08);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;}
.order-item-info{flex:1;}
.order-item-name{font-weight:600;font-size:0.9rem;color:var(--text-primary);}
.order-item-qty{font-size:0.78rem;color:var(--text-secondary);}
.order-item-price{font-family:var(--font-mono);font-weight:700;font-size:0.9rem;color:var(--primary);}

/* Summary Rows */
.summary-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;font-size:0.88rem;}
.summary-row .s-label{color:var(--text-secondary);}
.summary-row .s-value{font-family:var(--font-mono);font-weight:600;color:var(--text-primary);}
.summary-row.discount .s-value{color:#059669;}
.summary-row.total-row{border-top:2px solid rgba(45,106,79,0.12);margin-top:8px;padding-top:14px;}
.summary-row.total-row .s-value{font-family:var(--font-heading);font-weight:800;font-size:1.35rem;color:var(--primary);}

/* Payment Status */
.payment-status-section{background:#F8FAF9;border-radius:var(--input-radius);padding:14px;margin-top:16px;}
.status-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;font-size:0.82rem;}
.status-row .s-label{color:var(--text-secondary);}
.status-row .s-value{font-weight:600;color:var(--text-primary);}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:var(--btn-radius);font-size:0.75rem;font-weight:600;}
.status-badge.pending{background:rgba(245,158,11,0.12);color:#d97706;}

/* Security Badges */
.security-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;}
.security-badge{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:var(--btn-radius);font-size:0.75rem;font-weight:600;background:rgba(82,183,136,0.06);color:var(--primary);}

/* Help Link */
.help-link{display:flex;align-items:center;justify-content:center;gap:6px;padding:12px;margin-top:16px;color:var(--primary);font-size:0.85rem;font-weight:600;cursor:pointer;border-radius:var(--btn-radius);transition:all 0.25s ease;text-decoration:none;}
.help-link:hover{background:rgba(82,183,136,0.06);color:var(--primary-dark);}

/* Modal Overlay */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:22px;padding:2.5rem;text-align:center;max-width:440px;width:100%;animation:scaleIn 0.4s cubic-bezier(0.4,0,0.2,1);box-shadow:0 25px 60px rgba(0,0,0,0.15);position:relative;}

/* Loading Overlay */
.loading-overlay{position:fixed;inset:0;background:rgba(27,67,50,0.85);backdrop-filter:blur(12px);display:none;align-items:center;justify-content:center;z-index:10000;flex-direction:column;gap:20px;}
.loading-overlay.show{display:flex;}
.loading-spinner{width:56px;height:56px;border:4px solid rgba(255,255,255,0.2);border-top-color:var(--primary-light);border-radius:50%;animation:spin 0.8s linear infinite;}
.loading-text{color:#fff;font-family:var(--font-body);font-weight:600;font-size:1rem;}

/* Success Modal */
.success-icon-wrap{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;position:relative;}
.success-icon-wrap svg{width:42px;height:42px;}
.success-icon-wrap svg path{stroke:#fff;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;fill:none;stroke-dasharray:60;stroke-dashoffset:60;}
.modal-overlay.show .success-icon-wrap svg path{animation:checkDraw 0.6s 0.3s cubic-bezier(0.4,0,0.2,1) forwards;}
.success-title{font-family:var(--font-heading);font-weight:800;font-size:1.5rem;color:var(--text-primary);margin-bottom:8px;}
.success-desc{color:var(--text-secondary);font-size:0.9rem;line-height:1.6;margin-bottom:20px;}
.btn-success-action{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;padding:12px 28px;border-radius:var(--btn-radius);border:none;font-weight:700;font-size:0.95rem;cursor:pointer;transition:all 0.3s ease;text-decoration:none;}
.btn-success-action:hover{background:linear-gradient(135deg,var(--primary-dark),var(--primary));color:#fff;transform:translateY(-2px);box-shadow:0 8px 25px rgba(45,106,79,0.3);}

/* Confetti */
.confetti-container{position:fixed;inset:0;pointer-events:none;z-index:10001;overflow:hidden;}
.confetti-piece{position:absolute;width:10px;height:10px;top:-20px;opacity:0;}
.confetti-piece.active{animation:confettiFall 3s ease-out forwards;}

/* FAQ Modal */
.faq-modal{text-align:left;max-width:520px;}
.faq-modal-title{font-family:var(--font-heading);font-weight:700;font-size:1.2rem;color:var(--text-primary);margin-bottom:20px;text-align:center;}
.faq-item{border:1px solid rgba(107,114,128,0.08);border-radius:var(--input-radius);margin-bottom:10px;overflow:hidden;}
.faq-question{padding:14px 16px;font-weight:600;font-size:0.88rem;color:var(--text-primary);cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:background 0.2s ease;}
.faq-question:hover{background:rgba(82,183,136,0.04);}
.faq-answer{max-height:0;overflow:hidden;transition:max-height 0.35s ease,padding 0.35s ease;padding:0 16px;font-size:0.84rem;color:var(--text-secondary);line-height:1.6;}
.faq-item.open .faq-answer{max-height:200px;padding:0 16px 14px;}
.faq-item.open .faq-chevron{transform:rotate(180deg);}
.faq-chevron{transition:transform 0.3s ease;}

/* Cancel Modal */
.cancel-icon-wrap{width:70px;height:70px;border-radius:50%;background:rgba(220,38,38,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;color:#dc2626;font-size:1.8rem;}
.cancel-title{font-family:var(--font-heading);font-weight:700;font-size:1.2rem;color:var(--text-primary);margin-bottom:8px;}
.cancel-desc{color:var(--text-secondary);font-size:0.88rem;margin-bottom:20px;}
.btn-cancel-confirm{padding:10px 24px;border-radius:var(--btn-radius);font-weight:600;font-size:0.9rem;cursor:pointer;transition:all 0.25s ease;border:none;}
.btn-cancel-danger{background:#dc2626;color:#fff;}
.btn-cancel-danger:hover{background:#b91c1c;transform:translateY(-1px);}
.btn-cancel-secondary{background:rgba(107,114,128,0.06);color:var(--text-primary);border:1.5px solid rgba(107,114,128,0.12);}
.btn-cancel-secondary:hover{background:rgba(107,114,128,0.1);}

/* Toast */
.toast-container{position:fixed;bottom:30px;right:30px;z-index:10002;display:flex;flex-direction:column;gap:10px;}
.toast-item{background:linear-gradient(135deg,var(--primary-dark),var(--primary));color:#fff;padding:14px 22px;border-radius:14px;font-family:var(--font-body);font-size:0.88rem;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 35px rgba(27,67,50,0.35);animation:toastSlideUp 0.4s cubic-bezier(0.4,0,0.2,1);min-width:280px;}
.toast-item.error{background:linear-gradient(135deg,#991b1b,#dc2626);}
.toast-item i{font-size:1.2rem;color:var(--primary-light);}
.toast-item.error i{color:#fca5a5;}

@media(max-width:576px){
    .timer-banner{flex-direction:column;text-align:center;}
    .timer-left{flex-direction:column;}
    .timer-countdown{font-size:1.4rem;}
    .bank-item{flex-direction:column;gap:10px;text-align:center;}
    .modal-box{padding:1.5rem;}
}
</style>
</head>
<body>
<div class="pelanggan-wrapper">
<?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>
<div class="pelanggan-main">
        <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>
    <div class="pelanggan-content">

        <!-- Timer Banner -->
        <div class="saas-card timer-banner animate-fade-up">
            <div class="timer-left">
                <div class="timer-icon-wrap"><i class="bi bi-clock-history"></i></div>
                <div class="timer-info">
                    <div class="timer-label">Selesaikan pembayaran dalam</div>
                    <div class="order-id-badge"><i class="bi bi-receipt"></i> ORD-2026-0016</div>
                </div>
            </div>
            <div class="timer-countdown" id="paymentTimer">14:59</div>
        </div>

        <!-- Two-Column Layout -->
        <div class="row mt-4 g-4">
            <!-- LEFT: Payment Methods -->
            <div class="col-lg-7">
                <div class="saas-card animate-fade-up" style="animation-delay:0.1s">
                    <div class="pay-card-header">
                        <i class="bi bi-credit-card-2-front"></i>
                        <h4>Metode Pembayaran</h4>
                    </div>
                    <div class="pay-card-body">
                        <div class="d-flex flex-column gap-3">
                            <!-- Cash -->
                            <div class="method-option" id="optionCash" onclick="selectMethod('cash')">
                                <input type="radio" name="paymentMethod" value="cash" id="methodCash">
                                <div class="method-option-header">
                                    <div class="method-radio"></div>
                                    <div class="method-icon m-cash"><i class="bi bi-cash-stack"></i></div>
                                    <div>
                                        <div class="method-title">Bayar di Outlet</div>
                                        <p class="method-desc">Bayar langsung di outlet kami</p>
                                    </div>
                                </div>
                                <div class="method-expand" id="cashExpand">
                                    <div class="cash-details">
                                        <div class="cash-info-row">
                                            <i class="bi bi-geo-alt-fill"></i>
                                            <div><div class="c-label">Alamat Outlet</div><div class="c-value">Jl. Raya Camping No. 45, Bandung</div></div>
                                        </div>
                                        <div class="cash-info-row">
                                            <i class="bi bi-clock-fill"></i>
                                            <div><div class="c-label">Jam Operasional</div><div class="c-value">Senin - Minggu, 08:00 - 21:00</div></div>
                                        </div>
                                        <div class="cash-info-row">
                                            <i class="bi bi-info-circle-fill"></i>
                                            <div><div class="c-value" style="font-size:0.82rem;">Tunjukkan Order ID saat pembayaran</div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Transfer Bank -->
                            <div class="method-option" id="optionTransfer" onclick="selectMethod('transfer')">
                                <input type="radio" name="paymentMethod" value="transfer" id="methodTransfer">
                                <div class="method-option-header">
                                    <div class="method-radio"></div>
                                    <div class="method-icon m-bank"><i class="bi bi-bank"></i></div>
                                    <div>
                                        <div class="method-title">Transfer Bank</div>
                                        <p class="method-desc">Transfer via Virtual Account</p>
                                    </div>
                                </div>
                                <div class="method-expand" id="transferExpand">
                                    <div class="bank-list">
                                        <div class="bank-item" onclick="selectBank('bca',event)">
                                            <div class="bank-item-left">
                                                <div class="bank-logo">BCA</div>
                                                <div><div class="bank-name">Bank BCA</div><div class="bank-va">8800 1234 5678 9012</div></div>
                                            </div>
                                            <button class="btn-copy-va" onclick="copyToClipboard('8800123456789012',this,event)"><i class="bi bi-clipboard"></i> Salin</button>
                                        </div>
                                        <div class="bank-item" onclick="selectBank('bri',event)">
                                            <div class="bank-item-left">
                                                <div class="bank-logo" style="color:#0066b3;">BRI</div>
                                                <div><div class="bank-name">Bank BRI</div><div class="bank-va">0026 0811 2233 4455</div></div>
                                            </div>
                                            <button class="btn-copy-va" onclick="copyToClipboard('0026081122334455',this,event)"><i class="bi bi-clipboard"></i> Salin</button>
                                        </div>
                                        <div class="bank-item" onclick="selectBank('mandiri',event)">
                                            <div class="bank-item-left">
                                                <div class="bank-logo" style="color:#003d79;">MDR</div>
                                                <div><div class="bank-name">Bank Mandiri</div><div class="bank-va">8900 0012 3456 7890</div></div>
                                            </div>
                                            <button class="btn-copy-va" onclick="copyToClipboard('8900001234567890',this,event)"><i class="bi bi-clipboard"></i> Salin</button>
                                        </div>
                                    </div>
                                    <div class="upload-section">
                                        <div class="upload-label"><i class="bi bi-cloud-arrow-up"></i> Upload Bukti Transfer</div>
                                        <div class="upload-area" id="uploadArea">
                                            <input type="file" accept="image/*" id="fileInput" onchange="handleFileSelect(this)">
                                            <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                            <p class="mb-1 text-secondary" style="font-size:0.85rem;">Klik atau seret file ke sini</p>
                                            <small class="text-muted">Maks. 2MB (JPG, PNG)</small>
                                        </div>
                                        <div class="upload-preview" id="uploadPreview">
                                            <img id="previewImg" src="" alt="Preview Bukti"><br>
                                            <button class="btn-remove-preview" onclick="removePreview(event)"><i class="bi bi-trash3"></i> Hapus</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QRIS -->
                            <div class="method-option" id="optionQRIS" onclick="selectMethod('qris')">
                                <input type="radio" name="paymentMethod" value="qris" id="methodQRIS">
                                <div class="method-option-header">
                                    <div class="method-radio"></div>
                                    <div class="method-icon m-qris"><i class="bi bi-qr-code"></i></div>
                                    <div>
                                        <div class="method-title">QRIS</div>
                                        <p class="method-desc">Scan QR dengan e-wallet atau mobile banking</p>
                                    </div>
                                </div>
                                <div class="method-expand" id="qrisExpand">
                                    <div class="qris-content">
                                        <div class="qris-container">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=SIMPELCAMP-ORD-2026-0016" alt="QRIS Payment Code">
                                        </div>
                                        <p class="mt-3 text-secondary" style="font-size:0.85rem;"><i class="bi bi-phone me-1"></i>Scan dengan e-wallet favorit Anda</p>
                                        <div class="ewallet-badges">
                                            <span class="ewallet-badge">GoPay</span><span class="ewallet-badge">OVO</span><span class="ewallet-badge">DANA</span><span class="ewallet-badge">ShopeePay</span>
                                        </div>
                                        <div class="qris-timer-pill"><i class="bi bi-arrow-repeat"></i> QR berlaku <span class="time" id="qrisTimer">4:59</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button class="btn-confirm-pay mt-4" id="btnConfirmPay" onclick="confirmPayment()"><i class="bi bi-shield-check fs-5"></i> Konfirmasi Pembayaran</button>
                        <button class="cancel-link" onclick="openCancelModal()"><i class="bi bi-x-circle"></i> Batalkan Pembayaran</button>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Order Summary -->
            <div class="col-lg-5">
                <div class="summary-sticky">
                    <div class="saas-card animate-fade-up" style="animation-delay:0.2s">
                        <div class="pay-card-header"><i class="bi bi-bag-check"></i><h4>Ringkasan Pesanan</h4></div>
                        <div class="pay-card-body">
                            <div class="order-item">
                                <div class="order-item-icon">â›º</div>
                                <div class="order-item-info">
                                    <div class="order-item-name">Tenda Dome Eiger 4P</div>
                                    <div class="order-item-qty">1 unit Ã— 3 hari Ã— Rp 75.000</div>
                                </div>
                                <div class="order-item-price">Rp 225.000</div>
                            </div>
                            <div class="order-item">
                                <div class="order-item-icon">ðŸ”¦</div>
                                <div class="order-item-info">
                                    <div class="order-item-name">Headlamp LED Pro</div>
                                    <div class="order-item-qty">2 unit Ã— 3 hari Ã— Rp 10.000</div>
                                </div>
                                <div class="order-item-price">Rp 60.000</div>
                            </div>
                            <div class="mt-3 pt-3" style="border-top:1px solid rgba(107,114,128,0.08);">
                                <div class="summary-row"><span class="s-label">Subtotal</span><span class="s-value">Rp 285.000</span></div>
                                <div class="summary-row discount"><span class="s-label"><i class="bi bi-tag me-1"></i>Diskon Member Gold (-5%)</span><span class="s-value">-Rp 14.250</span></div>
                                <div class="summary-row total-row"><span class="s-label fw-bold">Total Pembayaran</span><span class="s-value">Rp 270.750</span></div>
                            </div>
                            <div class="payment-status-section">
                                <div class="status-row"><span class="s-label">Metode</span><span class="s-value" id="selectedMethodText">Belum dipilih</span></div>
                                <div class="status-row"><span class="s-label">Status</span><span class="status-badge pending"><i class="bi bi-hourglass-split"></i> Menunggu Pembayaran</span></div>
                            </div>
                            <div class="security-badges">
                                <div class="security-badge">ðŸ”’ Pembayaran Aman</div>
                                <div class="security-badge">ðŸ›¡ï¸ Data Terenkripsi</div>
                                <div class="security-badge">âœ… Garansi Pengembalian</div>
                            </div>
                            <a class="help-link" onclick="openFaqModal()"><i class="bi bi-question-circle"></i> Butuh bantuan?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay"><div class="loading-spinner"></div><div class="loading-text">Memproses pembayaran...</div></div>

<!-- Success Modal -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box">
        <div class="success-icon-wrap"><svg viewBox="0 0 40 40"><path d="M10 20 L18 28 L30 12"/></svg></div>
        <div class="success-title">Pembayaran Berhasil!</div>
        <div class="success-desc">Pembayaran untuk pesanan <strong>ORD-2026-0016</strong> telah dikonfirmasi.<br>Silakan tunggu verifikasi dari admin kami.</div>
        <a href="<?= BASE_URL ?>/pages/pelanggan/riwayat.php" class="btn-success-action"><i class="bi bi-receipt"></i> Lihat Riwayat</a>
    </div>
</div>

<!-- FAQ Modal -->
<div class="modal-overlay" id="faqModal">
    <div class="modal-box faq-modal">
        <div class="faq-modal-title"><i class="bi bi-question-circle me-2" style="color:var(--primary-light);"></i>Pertanyaan Umum</div>
        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">Bagaimana cara melakukan pembayaran?<i class="bi bi-chevron-down faq-chevron"></i></div>
            <div class="faq-answer">Pilih metode pembayaran yang Anda inginkan (Cash, Transfer Bank, atau QRIS), lalu ikuti instruksi yang ditampilkan. Untuk transfer bank, salin nomor VA dan transfer sesuai nominal. Untuk QRIS, scan QR code dengan e-wallet Anda.</div>
        </div>
        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">Berapa lama verifikasi pembayaran?<i class="bi bi-chevron-down faq-chevron"></i></div>
            <div class="faq-answer">Transfer Bank: 1Ã—24 jam setelah bukti transfer diunggah. QRIS: otomatis 5-15 menit. Cash: diverifikasi saat Anda datang ke outlet.</div>
        </div>
        <div class="faq-item" onclick="toggleFaq(this)">
            <div class="faq-question">Apa yang terjadi jika waktu habis?<i class="bi bi-chevron-down faq-chevron"></i></div>
            <div class="faq-answer">Jika waktu pembayaran habis, pesanan Anda akan otomatis dibatalkan dan stok barang akan dikembalikan. Anda bisa membuat pesanan baru kapan saja.</div>
        </div>
        <button class="btn-cancel-secondary mt-3 w-100" onclick="closeFaqModal()" style="padding:12px;border-radius:var(--btn-radius);"><i class="bi bi-x-lg me-1"></i> Tutup</button>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal-overlay" id="cancelModal">
    <div class="modal-box">
        <div class="cancel-icon-wrap"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="cancel-title">Yakin Batalkan Pembayaran?</div>
        <div class="cancel-desc">Pesanan <strong>ORD-2026-0016</strong> akan dibatalkan dan tidak dapat dikembalikan. Stok barang akan dikembalikan ke katalog.</div>
        <div class="d-flex gap-3 justify-content-center">
            <button class="btn-cancel-secondary btn-cancel-confirm" onclick="closeCancelModal()"><i class="bi bi-arrow-left me-1"></i> Kembali</button>
            <button class="btn-cancel-danger btn-cancel-confirm" onclick="cancelPayment()"><i class="bi bi-trash3 me-1"></i> Ya, Batalkan</button>
        </div>
    </div>
</div>

<!-- Confetti -->
<div class="confetti-container" id="confettiContainer"></div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
const TRX_ID = <?= $transaksi ? (int)$transaksi['id'] : 0 ?>;
const TRX_TOTAL = <?= $transaksi ? (float)$transaksi['total_bayar'] : 0 ?>;
const RESERVASI_ID = <?= $transaksi && !empty($transaksi['reservasi_id']) ? (int)$transaksi['reservasi_id'] : 0 ?>;

// Countdown Timer (14:59)
(function(){
    let timeLeft=14*60+59;
    const timerEl=document.getElementById('paymentTimer');
    const interval=setInterval(function(){
        if(timeLeft<=0){clearInterval(interval);timerEl.textContent='00:00';timerEl.classList.add('timer-urgent');return;}
        timeLeft--;
        const min=Math.floor(timeLeft/60).toString().padStart(2,'0');
        const sec=(timeLeft%60).toString().padStart(2,'0');
        timerEl.textContent=min+':'+sec;
        if(timeLeft<=120)timerEl.classList.add('timer-urgent');
    },1000);
})();

// QRIS Timer (4:59)
(function(){
    let qrisTime=4*60+59;
    const qrisEl=document.getElementById('qrisTimer');
    setInterval(function(){
        if(qrisTime<=0){qrisEl.textContent='0:00';return;}
        qrisTime--;
        const min=Math.floor(qrisTime/60);
        const sec=(qrisTime%60).toString().padStart(2,'0');
        qrisEl.textContent=min+':'+sec;
    },1000);
})();

// Payment Method Selection
let currentMethod=null;
const methodLabels={cash:'Bayar di Outlet',transfer:'Transfer Bank',qris:'QRIS'};
function selectMethod(method){
    currentMethod=method;
    document.querySelectorAll('.method-option').forEach(el=>el.classList.remove('active'));
    document.querySelectorAll('.method-expand').forEach(el=>el.classList.remove('show'));
    if(method==='transfer'){document.getElementById('optionTransfer').classList.add('active');document.getElementById('methodTransfer').checked=true;document.getElementById('transferExpand').classList.add('show');}
    else if(method==='cash'){document.getElementById('optionCash').classList.add('active');document.getElementById('methodCash').checked=true;document.getElementById('cashExpand').classList.add('show');}
    else if(method==='qris'){document.getElementById('optionQRIS').classList.add('active');document.getElementById('methodQRIS').checked=true;document.getElementById('qrisExpand').classList.add('show');}
    document.getElementById('selectedMethodText').textContent=methodLabels[method]||'Belum dipilih';
}

// Bank Selection
function selectBank(bank,event){if(event)event.stopPropagation();document.querySelectorAll('.bank-item').forEach(el=>el.classList.remove('selected'));event.currentTarget.classList.add('selected');}

// Copy to Clipboard
function copyToClipboard(text,btn,event){
    if(event){event.preventDefault();event.stopPropagation();}
    navigator.clipboard.writeText(text).then(function(){
        btn.classList.add('copied');btn.innerHTML='<i class="bi bi-check-lg"></i> Tersalin';
        showToast('Nomor berhasil disalin!','success');
        setTimeout(function(){btn.classList.remove('copied');btn.innerHTML='<i class="bi bi-clipboard"></i> Salin';},2500);
    }).catch(function(){showToast('Gagal menyalin nomor','error');});
}

// File Upload
const uploadArea=document.getElementById('uploadArea');
if(uploadArea){
    ['dragenter','dragover'].forEach(evt=>{uploadArea.addEventListener(evt,function(e){e.preventDefault();e.stopPropagation();uploadArea.classList.add('dragover');});});
    ['dragleave','drop'].forEach(evt=>{uploadArea.addEventListener(evt,function(e){e.preventDefault();e.stopPropagation();uploadArea.classList.remove('dragover');});});
    uploadArea.addEventListener('drop',function(e){const files=e.dataTransfer.files;if(files.length>0){document.getElementById('fileInput').files=files;handleFileSelect(document.getElementById('fileInput'));}});
}
function handleFileSelect(input){
    if(input.files&&input.files[0]){
        const file=input.files[0];
        if(file.size>2*1024*1024){showToast('Ukuran file melebihi 2MB','error');input.value='';return;}
        const reader=new FileReader();
        reader.onload=function(e){document.getElementById('previewImg').src=e.target.result;document.getElementById('uploadPreview').classList.add('show');document.getElementById('uploadArea').style.display='none';showToast('Bukti transfer berhasil diunggah','success');};
        reader.readAsDataURL(file);
    }
}
function removePreview(event){if(event){event.preventDefault();event.stopPropagation();}document.getElementById('uploadPreview').classList.remove('show');document.getElementById('uploadArea').style.display='';document.getElementById('fileInput').value='';}

// Confirm Payment — calls POST /api/pembayaran.php?action=create
function confirmPayment(){
    const selected=document.querySelector('input[name="paymentMethod"]:checked');
    if(!selected){showToast('Silakan pilih metode pembayaran terlebih dahulu','error');return;}
    if(TRX_ID <= 0){showToast('Tidak ada transaksi yang perlu dibayar','error');return;}

    const metode = selected.value;
    const fileInput = document.getElementById('fileInput');

    // For transfer, require bukti bayar
    if(metode === 'transfer' && (!fileInput || !fileInput.files || !fileInput.files[0])){
        showToast('Silakan upload bukti transfer terlebih dahulu','error');
        return;
    }

    document.getElementById('loadingOverlay').classList.add('show');

    const formData = new FormData();
    formData.append('transaksi_id', TRX_ID);
    formData.append('metode', metode);
    formData.append('jumlah', TRX_TOTAL);
    if(fileInput && fileInput.files && fileInput.files[0]){
        formData.append('bukti_bayar', fileInput.files[0]);
    }

    fetch(BASE_URL + '/api/pembayaran.php?action=create', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loadingOverlay').classList.remove('show');
        if(data.success){
            document.getElementById('successModal').classList.add('show');
            launchConfetti();
        } else {
            showToast(data.message || 'Gagal mengirim pembayaran', 'error');
        }
    })
    .catch(err => {
        document.getElementById('loadingOverlay').classList.remove('show');
        showToast('Terjadi kesalahan jaringan. Silakan coba lagi.', 'error');
        console.error('confirmPayment error:', err);
    });
}

// Confetti
function launchConfetti(){
    const container=document.getElementById('confettiContainer');
    const colors=['#2D6A4F','#52B788','#D4A373','#E9C46A','#40916C','#ff6b6b','#4ecdc4'];
    for(let i=0;i<60;i++){const piece=document.createElement('div');piece.className='confetti-piece';piece.style.left=Math.random()*100+'%';piece.style.width=(Math.random()*8+6)+'px';piece.style.height=(Math.random()*8+6)+'px';piece.style.backgroundColor=colors[Math.floor(Math.random()*colors.length)];piece.style.borderRadius=Math.random()>0.5?'50%':'2px';piece.style.animationDelay=(Math.random()*1.5)+'s';piece.style.animationDuration=(Math.random()*2+2)+'s';container.appendChild(piece);setTimeout(()=>piece.classList.add('active'),50);}
    setTimeout(()=>{container.innerHTML='';},5000);
}

// Toast
function showToast(message,type){
    const container=document.getElementById('toastContainer');
    const toast=document.createElement('div');
    toast.className='toast-item '+(type||'success');
    const iconClass=type==='error'?'bi-x-circle-fill':'bi-check-circle-fill';
    toast.innerHTML='<i class="bi '+iconClass+'"></i><span>'+message+'</span>';
    container.appendChild(toast);
    setTimeout(()=>{toast.style.opacity='0';toast.style.transform='translateY(20px) scale(0.95)';toast.style.transition='all 0.3s ease';setTimeout(()=>toast.remove(),300);},3000);
}

// FAQ Modal
function openFaqModal(){document.getElementById('faqModal').classList.add('show');}
function closeFaqModal(){document.getElementById('faqModal').classList.remove('show');}
function toggleFaq(item){const wasOpen=item.classList.contains('open');document.querySelectorAll('.faq-item').forEach(i=>i.classList.remove('open'));if(!wasOpen)item.classList.add('open');}

// Cancel Modal
function openCancelModal(){document.getElementById('cancelModal').classList.add('show');}
function closeCancelModal(){document.getElementById('cancelModal').classList.remove('show');}

// Cancel Payment — calls POST /api/reservasi.php?action=cancel
function cancelPayment(){
    if(RESERVASI_ID <= 0){
        closeCancelModal();
        showToast('Tidak ada reservasi yang bisa dibatalkan','error');
        return;
    }

    closeCancelModal();
    document.getElementById('loadingOverlay').classList.add('show');

    fetch(BASE_URL + '/api/reservasi.php?action=cancel', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: RESERVASI_ID })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loadingOverlay').classList.remove('show');
        if(data.success){
            showToast('Pembayaran berhasil dibatalkan', 'success');
            setTimeout(() => {
                window.location.href = BASE_URL + '/pages/pelanggan/transaksi.php';
            }, 1500);
        } else {
            showToast(data.message || 'Gagal membatalkan pembayaran', 'error');
        }
    })
    .catch(err => {
        document.getElementById('loadingOverlay').classList.remove('show');
        showToast('Terjadi kesalahan jaringan. Silakan coba lagi.', 'error');
        console.error('cancelPayment error:', err);
    });
}

// Close modals on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay=>{overlay.addEventListener('click',function(e){if(e.target===this)this.classList.remove('show');});});
</script>
</body>
</html>
