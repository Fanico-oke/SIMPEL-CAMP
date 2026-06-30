<?php
// pages/pelanggan/transaksi.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Transaksi Saya';
$current_page = 'transaksi';
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';

// Fetch real transactions from database
$allTransaksi = Transaksi::getByUser($user_id);
$riwayat = [];
foreach ($allTransaksi as $trx) {
    // Get item names from reservasi detail
    $itemNames = 'Peralatan camping';
    if (!empty($trx['reservasi_id'])) {
        $details = Reservasi::getDetail($trx['reservasi_id']);
        $names = array_map(function($d) { return $d['barang_nama']; }, $details);
        $barangIds = array_map(function($d) { return ['id' => $d['barang_id'], 'nama' => $d['barang_nama']]; }, $details);
        if (!empty($names)) $itemNames = implode(', ', $names);
    }
    // Map status to display
    $statusMap = [
        'menunggu_bayar' => 'Menunggu Bayar',
        'dibayar' => 'Dibayar',
        'aktif' => 'Aktif',
        'menunggu_cek' => 'Menunggu Dicek',
        'menunggu_denda' => 'Menunggu Denda',
        'selesai' => 'Selesai',
        'batal' => 'Dibatalkan',
    ];
    $displayStatus = $statusMap[$trx['status']] ?? ucfirst($trx['status']);
    $riwayat[] = [
        'id'         => $trx['kode_reservasi'] ?? $trx['kode_transaksi'],
        'trx_id'     => (int)$trx['id'],
        'reservasi_id'=> (int)($trx['reservasi_id'] ?? 0),
        'sewa'       => !empty($trx['tanggal_mulai']) ? date('d M Y', strtotime($trx['tanggal_mulai'])) : date('d M Y', strtotime($trx['created_at'])),
        'kembali'    => !empty($trx['tanggal_selesai']) ? date('d M Y', strtotime($trx['tanggal_selesai'])) : '-',
        'barang'     => $itemNames,
        'barang_list'=> json_encode($barangIds ?? []),
        'total'      => (int)$trx['total_bayar'],
        'denda'      => (int)($trx['denda'] ?? 0),
        'status'     => $displayStatus,
        'raw_status' => $trx['status'],
        'metode'     => (strtolower($trx['tipe']) === 'walk_in' || strtolower($trx['tipe']) === 'offline') ? 'Offline (Toko)' : ucfirst($trx['tipe'] ?? 'online'),
        'raw_tipe'   => strtolower($trx['tipe'] ?? 'online'),
    ];
}
$aktif_rentals = array_filter($riwayat, fn($r) => in_array($r['raw_status'], ['dibayar', 'aktif']));
$total_pengeluaran = array_sum(array_map(fn($r) => $r['total'], array_filter($riwayat, fn($r) => in_array($r['raw_status'], ['dibayar', 'aktif', 'selesai']))));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <meta name="description" content="Kelola transaksi penyewaan peralatan camping - Perpanjangan dan Riwayat Transaksi di SIMPEL-CAMP">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">
    <style>
    /* ------------------------------------------------------------
       TRANSAKSI PAGE - Ultra Premium Redesign v2
       Design: #F2F7F4 bg | White cards 20px | NO borders | pill 50px
       ------------------------------------------------------------ */
    :root {
        --trx-primary: #2D6A4F;
        --trx-primary-light: #52B788;
        --trx-primary-dark: #1B4332;
        --trx-sage: #40916C;
        --trx-gold: #D4A373;
        --trx-gold-light: #E9C46A;
        --trx-text: #1A1A2E;
        --trx-text-muted: #6B7280;
        --trx-page-bg: #F2F7F4;
        --trx-card-bg: #FFFFFF;
        --trx-card-radius: 20px;
        --trx-card-shadow: 0 2px 20px rgba(0,0,0,0.04);
        --trx-pill-radius: 50px;
        --trx-input-radius: 12px;
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Page Header Icon Ã¢â€â‚¬Ã¢â€â‚¬ */
    .page-header-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px; height: 52px;
        background: linear-gradient(135deg, var(--trx-primary-dark), var(--trx-primary));
        border-radius: 16px;
        color: #fff;
        font-size: 1.35rem;
        animation: iconFloat 3s ease-in-out infinite;
        box-shadow: 0 6px 20px rgba(45,106,79,0.3);
    }
    @keyframes iconFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-5px) rotate(3deg); }
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Stat Pills Row Ã¢â€â‚¬Ã¢â€â‚¬ */
    .stat-pills-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-pill {
        flex: 1;
        background: var(--trx-card-bg);
        border-radius: var(--trx-card-radius);
        box-shadow: var(--trx-card-shadow);
        border: none;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        position: relative;
        overflow: hidden;
    }
    .stat-pill::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(82,183,136,0.06), transparent);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .stat-pill:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.08);
    }
    .stat-pill:hover::after { opacity: 1; }
    .stat-pill-icon {
        width: 48px; height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }
    .stat-pill-icon.green {
        background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
        color: var(--trx-primary);
    }
    .stat-pill-icon.blue {
        background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
        color: #2563EB;
    }
    .stat-pill-icon.gold {
        background: linear-gradient(135deg, #FEF3C7, #FDE68A);
        color: #B45309;
    }
    .stat-pill-info { position: relative; z-index: 1; }
    .stat-pill-label {
        font-family: 'Inter', sans-serif;
        font-size: 0.78rem;
        color: var(--trx-text-muted);
        font-weight: 500;
        margin-bottom: 2px;
        letter-spacing: 0.01em;
    }
    .stat-pill-value {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 700;
        font-size: 1.3rem;
        color: var(--trx-text);
    }

    /* Pulse dot for active stat */
    .pulse-dot {
        display: inline-block;
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #3B82F6;
        margin-right: 6px;
        animation: pulseDot 2s ease-in-out infinite;
        vertical-align: middle;
    }
    @keyframes pulseDot {
        0%, 100% { box-shadow: 0 0 0 0 rgba(59,130,246,0.4); }
        50% { box-shadow: 0 0 0 8px rgba(59,130,246,0); }
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Premium Pill Tabs Ã¢â€â‚¬Ã¢â€â‚¬ */
    .premium-tabs {
        display: inline-flex;
        background: var(--trx-card-bg);
        border-radius: var(--trx-pill-radius);
        padding: 5px;
        position: relative;
        margin-bottom: 2rem;
        box-shadow: var(--trx-card-shadow);
        border: none;
    }
    .premium-tab-indicator {
        position: absolute;
        top: 5px; bottom: 5px;
        background: linear-gradient(135deg, var(--trx-primary-dark), var(--trx-primary));
        border-radius: calc(var(--trx-pill-radius) - 5px);
        transition: all 0.45s cubic-bezier(0.4,0,0.2,1);
        box-shadow: 0 4px 15px rgba(45,106,79,0.3);
        z-index: 1;
    }
    .premium-tab-btn {
        position: relative;
        z-index: 2;
        border: none;
        background: transparent;
        padding: 0.7rem 1.8rem;
        font-weight: 600;
        font-size: 0.9rem;
        font-family: 'Inter', sans-serif;
        color: var(--trx-text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-radius: calc(var(--trx-pill-radius) - 5px);
        transition: color 0.3s;
        white-space: nowrap;
    }
    .premium-tab-btn.active { color: #fff; }
    .premium-tab-btn:hover:not(.active) { color: var(--trx-primary); }
    .premium-tab-btn .tab-badge {
        background: rgba(255,255,255,0.25);
        color: #fff;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 700;
    }
    .premium-tab-btn:not(.active) .tab-badge {
        background: rgba(59,130,246,0.1);
        color: #3B82F6;
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Tab Panels Ã¢â€â‚¬Ã¢â€â‚¬ */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: panelIn 0.5s ease; }
    @keyframes panelIn {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Section Header Ã¢â€â‚¬Ã¢â€â‚¬ */
    .section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .section-header-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Rental Cards (Perpanjangan Tab) Ã¢â€â‚¬Ã¢â€â‚¬ */
    .rental-card {
        background: var(--trx-card-bg);
        border-radius: var(--trx-card-radius);
        overflow: hidden;
        margin-bottom: 1.25rem;
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        box-shadow: var(--trx-card-shadow);
        border: none;
    }
    .rental-card:hover {
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        transform: translateY(-3px);
    }
    .rental-card-body {
        display: flex;
        gap: 1.5rem;
        padding: 1.5rem;
        align-items: flex-start;
    }
    .rental-img {
        width: 140px; height: 110px;
        border-radius: 14px;
        object-fit: cover;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .rental-info { flex: 1; min-width: 0; }
    .rental-id-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
        color: var(--trx-primary-dark);
        padding: 0.3rem 0.8rem;
        border-radius: var(--trx-pill-radius);
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.78rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .rental-items-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        color: var(--trx-text);
        font-size: 1.05rem;
        margin-bottom: 0.35rem;
    }
    .rental-dates {
        font-size: 0.82rem;
        color: var(--trx-text-muted);
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 0.85rem;
    }
    .rental-dates .arrow { color: var(--trx-primary-light); font-weight: 700; }

    /* Progress Bar */
    .rental-progress-wrap {
        background: #E5E7EB;
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }
    .rental-progress-fill {
        height: 100%;
        border-radius: 10px;
        background: linear-gradient(90deg, var(--trx-primary), var(--trx-primary-light));
        transition: width 1.2s cubic-bezier(0.4,0,0.2,1);
        position: relative;
    }
    .rental-progress-fill::after {
        content: '';
        position: absolute;
        top: 0; right: 0; bottom: 0;
        width: 30px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3));
        border-radius: 10px;
    }
    .rental-progress-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.75rem;
        color: #9CA3AF;
        margin-bottom: 0.85rem;
    }
    .days-remaining-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: linear-gradient(135deg, #FEF3C7, #FDE68A);
        color: #92400E;
        padding: 0.25rem 0.75rem;
        border-radius: var(--trx-pill-radius);
        font-size: 0.72rem;
        font-weight: 700;
    }
    .days-remaining-pill.urgent {
        background: linear-gradient(135deg, #FEE2E2, #FECACA);
        color: #991B1B;
        animation: urgentPulse 2s infinite;
    }
    @keyframes urgentPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.3); }
        50% { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
    }

    /* Rental Action Buttons */
    .rental-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }
    .btn-perpanjang {
        background: linear-gradient(135deg, var(--trx-primary-dark), var(--trx-primary));
        color: #fff;
        border: none;
        padding: 0.55rem 1.4rem;
        border-radius: var(--trx-pill-radius);
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        display: flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 4px 15px rgba(45,106,79,0.25);
    }
    .btn-perpanjang:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(45,106,79,0.35);
    }
    .btn-detail-expand {
        background: rgba(45,106,79,0.08);
        color: var(--trx-primary);
        border: none;
        padding: 0.55rem 1.2rem;
        border-radius: var(--trx-pill-radius);
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .btn-detail-expand:hover {
        background: rgba(45,106,79,0.14);
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Perpanjangan Slide-Down Panel Ã¢â€â‚¬Ã¢â€â‚¬ */
    .perpanjang-panel {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.55s cubic-bezier(0.4,0,0.2,1), padding 0.35s;
        background: linear-gradient(135deg, rgba(45,106,79,0.02), rgba(82,183,136,0.03));
    }
    .perpanjang-panel.open {
        max-height: 2000px;
        padding: 1.5rem;
    }
    .day-stepper {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .stepper-btn {
        width: 44px; height: 44px;
        border-radius: 50%;
        border: none;
        background: var(--trx-card-bg);
        color: var(--trx-primary);
        font-size: 1.3rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.25s;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }
    .stepper-btn:hover {
        background: var(--trx-primary);
        color: #fff;
        box-shadow: 0 4px 15px rgba(45,106,79,0.3);
        transform: scale(1.05);
    }
    .stepper-value {
        font-family: 'JetBrains Mono', monospace;
        font-size: 2rem;
        font-weight: 700;
        color: var(--trx-primary-dark);
        min-width: 50px;
        text-align: center;
    }
    .cost-display {
        background: var(--trx-card-bg);
        border-radius: var(--trx-input-radius);
        padding: 1rem 1.25rem;
        margin: 1rem 0;
        box-shadow: 0 1px 8px rgba(0,0,0,0.03);
        border: none;
    }
    .cost-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.35rem 0;
    }
    .cost-label { font-size: 0.85rem; color: var(--trx-text-muted); }
    .cost-value {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        color: var(--trx-text);
        transition: all 0.3s;
    }
    .cost-total {
        font-size: 1.15rem;
        color: var(--trx-primary) !important;
    }

    /* Payment Radio Cards */
    .pay-card-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        margin: 1rem 0;
    }
    .pay-card {
        border: 2px solid transparent;
        border-radius: var(--trx-input-radius);
        padding: 1rem 0.8rem;
        cursor: pointer;
        text-align: center;
        transition: all 0.3s;
        background: var(--trx-card-bg);
        box-shadow: 0 1px 8px rgba(0,0,0,0.04);
        position: relative;
    }
    .pay-card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .pay-card.selected {
        background: linear-gradient(135deg, rgba(45,106,79,0.04), rgba(82,183,136,0.06));
        border-color: var(--trx-primary);
        box-shadow: 0 4px 15px rgba(45,106,79,0.15);
    }
    .pay-card.selected::after {
        content: '\F26B';
        font-family: 'bootstrap-icons';
        position: absolute;
        top: 6px; right: 6px;
        font-size: 0.7rem;
        background: var(--trx-primary);
        color: #fff;
        width: 18px; height: 18px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
    .pay-card-icon { font-size: 1.6rem; margin-bottom: 0.35rem; }
    .pay-card-label { font-size: 0.82rem; font-weight: 700; color: #1F2937; margin-bottom: 0.2rem; }
    .pay-card-detail { font-size: 0.68rem; color: #6B7280; line-height: 1.3; }

    /* Upload Bukti Section */
    .upload-bukti-section {
        margin-top: 1rem;
    }
    .upload-bukti-area {
        border: 2px dashed #D1D5DB;
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #FAFAFA;
    }
    .upload-bukti-area:hover {
        border-color: var(--trx-primary);
        background: rgba(45,106,79,0.02);
    }
    .upload-bukti-area.has-file {
        border-color: var(--trx-primary);
        border-style: solid;
        background: #F0FDF4;
    }
    .upload-bukti-area .upload-icon { font-size: 1.8rem; color: #9CA3AF; }
    .upload-bukti-area.has-file .upload-icon { color: var(--trx-primary); }
    .upload-bukti-preview {
        max-height: 80px;
        border-radius: 8px;
        margin-top: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .perpanjang-panel-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1.25rem;
    }
    .btn-cancel-ext {
        background: transparent;
        border: none;
        color: var(--trx-text-muted);
        padding: 0.6rem 1.3rem;
        border-radius: var(--trx-pill-radius);
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-cancel-ext:hover { background: rgba(0,0,0,0.04); }
    .btn-confirm-ext {
        background: linear-gradient(135deg, var(--trx-primary-dark), var(--trx-primary));
        color: #fff;
        border: none;
        padding: 0.6rem 1.6rem;
        border-radius: var(--trx-pill-radius);
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 4px 15px rgba(45,106,79,0.25);
    }
    .btn-confirm-ext:hover {
        box-shadow: 0 8px 25px rgba(45,106,79,0.35);
        transform: translateY(-2px);
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Expandable Detail (rental card) Ã¢â€â‚¬Ã¢â€â‚¬ */
    .rental-expand-detail {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease, padding 0.3s;
    }
    .rental-expand-detail.open {
        max-height: 300px;
        padding: 1.25rem 1.5rem;
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Filter Pills (Riwayat Tab) Ã¢â€â‚¬Ã¢â€â‚¬ */
    .filter-pills {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1.15rem;
        border-radius: var(--trx-pill-radius);
        border: none;
        background: var(--trx-card-bg);
        color: var(--trx-text-muted);
        font-weight: 600;
        font-size: 0.82rem;
        cursor: pointer;
        transition: all 0.3s;
        font-family: 'Inter', sans-serif;
        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
    }
    .filter-pill:hover {
        color: var(--trx-primary);
        box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        transform: translateY(-1px);
    }
    .filter-pill.active {
        background: linear-gradient(135deg, var(--trx-primary-dark), var(--trx-primary));
        color: #fff;
        box-shadow: 0 4px 15px rgba(45,106,79,0.25);
    }
    .filter-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .filter-dot.blue { background: #3B82F6; animation: dotPulse 1.5s ease-in-out infinite; }
    .filter-dot.green { background: #10B981; }
    .filter-dot.red { background: #EF4444; }
    @keyframes dotPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.5); }
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Transaction Cards (Riwayat) Ã¢â€â‚¬Ã¢â€â‚¬ */
    .trx-card {
        background: var(--trx-card-bg);
        border: none;
        border-radius: var(--trx-card-radius);
        overflow: hidden;
        margin-bottom: 1rem;
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        display: flex;
        opacity: 0;
        transform: translateY(18px);
        box-shadow: var(--trx-card-shadow);
    }
    .trx-card.visible {
        opacity: 1;
        transform: translateY(0);
    }
    .trx-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 40px rgba(0,0,0,0.08);
    }
    .trx-status-bar {
        width: 5px;
        flex-shrink: 0;
        border-radius: 5px 0 0 5px;
    }
    .trx-status-bar.aktif { background: linear-gradient(180deg, #3B82F6, #60A5FA); }
    .trx-status-bar.selesai { background: linear-gradient(180deg, #10B981, #34D399); }
    .trx-status-bar.dibatalkan { background: linear-gradient(180deg, #EF4444, #F87171); }
    .trx-card-body {
        flex: 1;
        padding: 1.25rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
    }
    .trx-main-info { flex: 1; min-width: 200px; }
    .trx-id {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        font-size: 0.8rem;
        background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
        color: var(--trx-primary-dark);
        padding: 0.22rem 0.65rem;
        border-radius: var(--trx-pill-radius);
        display: inline-block;
        margin-bottom: 0.4rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .trx-id:hover { box-shadow: 0 2px 8px rgba(45,106,79,0.15); }
    .trx-items {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        color: var(--trx-text);
        font-size: 0.95rem;
        margin-bottom: 0.25rem;
    }
    .trx-dates {
        font-size: 0.78rem;
        color: #9CA3AF;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    .trx-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .trx-total {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 700;
        font-size: 1.05rem;
        color: var(--trx-text);
    }

    /* Status & Metode Tags - Pastel Pills */
    .status-badge {
        padding: 0.3em 0.75em;
        border-radius: var(--trx-pill-radius);
        font-size: 0.72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        letter-spacing: 0.02em;
    }
    .status-badge::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
    }
    .status-aktif { background: #DBEAFE; color: #1D4ED8; }
    .status-aktif::before { background: #2563EB; }
    .status-selesai { background: #D1FAE5; color: #047857; }
    .status-selesai::before { background: #059669; }
    .status-batal, .status-dibatalkan { background: #FEE2E2; color: #B91C1C; }
    .status-batal::before, .status-dibatalkan::before { background: #DC2626; }
    .metode-badge {
        font-size: 0.72rem;
        padding: 0.25em 0.7em;
        border-radius: var(--trx-pill-radius);
        font-weight: 600;
        background: #FEF3C7;
        color: #B45309;
    }

    /* Transaction Action Buttons */
    .trx-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    .btn-trx-detail {
        background: #EEF2FF;
        color: #4338CA;
        border: none;
        padding: 0.45rem 1rem;
        border-radius: var(--trx-pill-radius);
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.25s;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    .btn-trx-detail:hover {
        background: #4338CA;
        color: #fff;
        box-shadow: 0 4px 12px rgba(67,56,202,0.25);
    }
    .btn-trx-nota {
        background: rgba(45,106,79,0.08);
        color: var(--trx-primary);
        border: none;
        padding: 0.45rem 1rem;
        border-radius: var(--trx-pill-radius);
        font-weight: 600;
        font-size: 0.8rem;
        text-decoration: none;
        transition: all 0.25s;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    .btn-trx-nota:hover {
        background: var(--trx-primary);
        color: #fff;
        box-shadow: 0 4px 12px rgba(45,106,79,0.25);
    }
    .btn-trx-bayar {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        border: none;
        padding: 0.45rem 1rem;
        border-radius: var(--trx-pill-radius);
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.25s;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        text-decoration: none;
    }
    .btn-trx-bayar:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
        color: #fff;
        box-shadow: 0 4px 12px rgba(217,119,6,0.35);
        transform: translateY(-1px);
    }
    .btn-trx-bayar i { font-size: 0.9rem; }
    /* Payment Modal */
    .pay-method-cards { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .pay-method-card { flex: 1; min-width: 100px; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.2s; background: #fff; }
    .pay-method-card:hover { border-color: var(--trx-primary); }
    .pay-method-card.selected { border-color: var(--trx-primary); background: rgba(45,106,79,0.05); }
    .pay-method-card i { font-size: 1.5rem; display: block; margin-bottom: 0.25rem; color: var(--trx-primary); }
    .pay-method-card span { font-size: 0.75rem; font-weight: 600; }
    .pay-upload-area { border: 2px dashed #d1d5db; border-radius: 12px; padding: 1.5rem; text-align: center; cursor: pointer; transition: all 0.2s; background: #fafafa; }
    .pay-upload-area:hover { border-color: var(--trx-primary); background: rgba(45,106,79,0.03); }
    .pay-upload-area.has-file { border-color: var(--trx-primary); background: rgba(45,106,79,0.05); }
    .pay-upload-area i { font-size: 2rem; color: #9ca3af; display: block; margin-bottom: 0.5rem; }
    .pay-upload-area.has-file i { color: var(--trx-primary); }
    .pay-preview { max-width: 200px; max-height: 150px; border-radius: 8px; margin-top: 0.5rem; }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Empty State Ã¢â€â‚¬Ã¢â€â‚¬ */
    .empty-state {
        text-align: center;
        padding: 3.5rem 1.5rem;
        background: var(--trx-card-bg);
        border-radius: var(--trx-card-radius);
        box-shadow: var(--trx-card-shadow);
        border: none;
    }
    .empty-state i { font-size: 3.5rem; color: #D1D5DB; margin-bottom: 1rem; }
    .empty-state p { font-family: 'Inter', sans-serif; }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Modals - Premium Ã¢â€â‚¬Ã¢â€â‚¬ */
    .modal-premium .modal-content {
        border: none;
        border-radius: var(--trx-card-radius);
        box-shadow: 0 25px 60px rgba(0,0,0,0.15);
        overflow: hidden;
    }
    .modal-premium .modal-header {
        background: linear-gradient(135deg, var(--trx-primary-dark), var(--trx-primary));
        color: #fff;
        border: none;
        padding: 1.25rem 1.5rem;
    }
    .modal-premium .modal-header .btn-close { filter: invert(1); }
    .modal-premium .modal-body { padding: 1.5rem; }

    /* Confirm Modal Summary */
    .confirm-summary {
        background: #F8FAF9;
        border-radius: 16px;
        padding: 1.25rem;
        border: none;
    }
    .confirm-row {
        display: flex;
        justify-content: space-between;
        padding: 0.45rem 0;
        font-size: 0.88rem;
    }
    .confirm-row .label { color: var(--trx-text-muted); }
    .confirm-row .value { font-weight: 600; color: var(--trx-text); }
    .confirm-total {
        border-top: 2px solid #E5E7EB;
        margin-top: 0.5rem;
        padding-top: 0.75rem;
    }
    .confirm-total .value {
        font-family: 'JetBrains Mono', monospace;
        font-size: 1.15rem;
        color: var(--trx-primary);
    }

    /* Success Modal */
    .success-modal-body {
        text-align: center;
        padding: 2.5rem 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .checkmark-circle {
        width: 96px; height: 96px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--trx-primary-light), var(--trx-sage));
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        animation: scaleIn 0.5s cubic-bezier(0.4,0,0.2,1);
        box-shadow: 0 8px 30px rgba(82,183,136,0.3);
    }
    .checkmark-circle svg { width: 42px; height: 42px; }
    .checkmark-path {
        stroke: #fff;
        stroke-width: 3;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 50;
        stroke-dashoffset: 50;
        animation: drawCheck 0.6s 0.3s ease forwards;
    }
    @keyframes scaleIn {
        from { transform: scale(0) rotate(-10deg); }
        to { transform: scale(1) rotate(0); }
    }
    @keyframes drawCheck { to { stroke-dashoffset: 0; } }
    .success-id-display {
        font-family: 'JetBrains Mono', monospace;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--trx-primary-dark);
        letter-spacing: 0.05em;
    }

    /* Confetti */
    .confetti-piece {
        position: absolute;
        width: 10px; height: 10px;
        top: -10px;
        animation: confettiFall 3s ease-in-out forwards;
        pointer-events: none;
    }
    @keyframes confettiFall {
        0% { transform: translateY(0) rotate(0) scale(1); opacity: 1; }
        100% { transform: translateY(400px) rotate(720deg) scale(0.3); opacity: 0; }
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Detail Modal Timeline Ã¢â€â‚¬Ã¢â€â‚¬ */
    .timeline {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin: 1.5rem 0;
        position: relative;
    }
    .timeline::before {
        content: '';
        position: absolute;
        top: 18px;
        left: 25px;
        right: 25px;
        height: 3px;
        background: #E5E7EB;
        z-index: 0;
    }
    .timeline-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 1;
        flex: 1;
    }
    .timeline-dot {
        width: 38px; height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        background: var(--trx-card-bg);
        color: #9CA3AF;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border: none;
    }
    .timeline-dot.done {
        background: linear-gradient(135deg, var(--trx-primary-light), var(--trx-sage));
        color: #fff;
        box-shadow: 0 3px 12px rgba(82,183,136,0.3);
    }
    .timeline-dot.current {
        background: linear-gradient(135deg, var(--trx-gold-light), var(--trx-gold));
        color: #fff;
        animation: urgentPulse 2s infinite;
    }
    .timeline-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: #9CA3AF;
        text-align: center;
    }
    .timeline-label.done { color: var(--trx-primary); }
    .timeline-label.current { color: var(--trx-gold); }
    .timeline-date {
        font-size: 0.65rem;
        color: #D1D5DB;
        text-align: center;
    }
    .detail-info-card {
        background: #F8FAF9;
        border-radius: var(--trx-input-radius);
        padding: 1rem;
        border: none;
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Toast System Ã¢â€â‚¬Ã¢â€â‚¬ */
    .toast-container-custom {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .toast-custom {
        background: rgba(26,26,46,0.92);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        color: #fff;
        padding: 0.85rem 1.3rem;
        border-radius: 14px;
        font-size: 0.85rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        animation: toastIn 0.4s cubic-bezier(0.4,0,0.2,1);
        border: none;
    }
    .toast-custom .toast-icon {
        width: 28px; height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        background: rgba(82,183,136,0.2);
        color: var(--trx-primary-light);
        flex-shrink: 0;
    }
    .toast-custom.hiding { animation: toastOut 0.3s ease forwards; }
    @keyframes toastIn {
        from { opacity: 0; transform: translateX(40px) scale(0.95); }
        to { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes toastOut {
        to { opacity: 0; transform: translateX(40px) scale(0.95); }
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Stagger Animation Ã¢â€â‚¬Ã¢â€â‚¬ */
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-stagger {
        opacity: 0;
        animation: fadeSlideUp 0.6s cubic-bezier(0.4,0,0.2,1) forwards;
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬ Responsive Ã¢â€â‚¬Ã¢â€â‚¬ */
    @media (max-width: 768px) {
        .stat-pills-row { flex-direction: column; }
        .rental-card-body { flex-direction: column; }
        .rental-img { width: 100%; height: 160px; }
        .pay-card-grid { grid-template-columns: 1fr 1fr 1fr; }
        .trx-card-body { flex-direction: column; align-items: flex-start; }
        .trx-meta { width: 100%; }
        .trx-actions { width: 100%; }
        .timeline { flex-wrap: wrap; gap: 0.5rem; justify-content: center; }
        .premium-tab-btn { padding: 0.6rem 1.1rem; font-size: 0.82rem; }
        .premium-tab-btn .tab-text-full { display: none; }
    }
    @media (min-width: 769px) {
        .premium-tab-btn .tab-text-short { display: none; }
    }
    </style>
</head>
<body>
<div class="pelanggan-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>
    <div class="pelanggan-main">
        <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>

        <div class="pelanggan-content">
            <div class="container-fluid pb-5">

                <!-- ----------- Page Header ----------- -->

                <!-- ----------- 3 Stat Pills ----------- -->
                <div class="stat-pills-row animate-stagger" style="animation-delay:0.1s">
                    <!-- Total Transaksi -->
                    <div class="stat-pill">
                        <div class="stat-pill-icon green"><i class="bi bi-receipt"></i></div>
                        <div class="stat-pill-info">
                            <div class="stat-pill-label">Total Transaksi</div>
                            <div class="stat-pill-value"><?= count($riwayat) ?></div>
                        </div>
                    </div>
                    <!-- Sewa Aktif -->
                    <div class="stat-pill">
                        <div class="stat-pill-icon blue"><i class="bi bi-bag-check"></i></div>
                        <div class="stat-pill-info">
                            <div class="stat-pill-label">Sewa Aktif</div>
                            <div class="stat-pill-value" style="color:#2563EB;"><span class="pulse-dot"></span><?= count($aktif_rentals) ?></div>
                        </div>
                    </div>
                    <!-- Pengeluaran -->
                    <div class="stat-pill">
                        <div class="stat-pill-icon gold"><i class="bi bi-wallet2"></i></div>
                        <div class="stat-pill-info">
                            <div class="stat-pill-label">Pengeluaran</div>
                            <div class="stat-pill-value" style="font-size:1.1rem; color:#B45309;">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <!-- ----------- Premium Pill Tabs ----------- -->
                <div class="premium-tabs animate-stagger" style="animation-delay:0.15s" id="premiumTabs">
                    <div class="premium-tab-indicator" id="tabIndicator"></div>
                    <button class="premium-tab-btn active" data-tab="perpanjangan" onclick="switchTab('perpanjangan')">
                        <i class="bi bi-box-seam"></i>
                        <span class="tab-text-full">Penyewaan Aktif</span>
                        <span class="tab-text-short">Aktif</span>
                        <span class="tab-badge"><?= count($aktif_rentals) ?></span>
                    </button>
                    <button class="premium-tab-btn" data-tab="riwayat" onclick="switchTab('riwayat')">
                        <i class="bi bi-clock-history"></i>
                        <span class="tab-text-full">Riwayat Transaksi</span>
                        <span class="tab-text-short">Riwayat</span>
                    </button>
                </div>

                <!-- -----------------------------------
                     TAB 1: PERPANJANGAN
                     ----------------------------------- -->
                <div class="tab-panel active" id="panel-perpanjangan">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:#DBEAFE; color:#2563EB;">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="font-family:'Outfit',sans-serif; font-size:1.05rem; color:var(--trx-text);">Penyewaan Aktif</h5>
                            <p class="small mb-0" style="color:var(--trx-text-muted);">Perpanjang masa sewa penyewaan yang masih aktif</p>
                        </div>
                    </div>

                    <?php if (empty($aktif_rentals)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox d-block"></i>
                        <p class="fw-semibold mt-2 mb-1" style="color:var(--trx-text);">Tidak ada penyewaan aktif</p>
                        <p class="small mb-0" style="color:var(--trx-text-muted);">Tidak ada penyewaan aktif saat ini.</p>
                    </div>
                    <?php else: ?>
                    <?php
                    $rental_images = [
                        'RSV-015' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=300&h=220&fit=crop',
                        'RSV-014' => 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=300&h=220&fit=crop',
                    ];
                    $idx = 0;
                    foreach ($aktif_rentals as $rental):
                        $img = $rental_images[$rental['id']] ?? 'https://images.unsplash.com/photo-1478827536114-da961b7f86d2?w=300&h=220&fit=crop';
                    $sewaDate = strtotime(str_replace(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],$rental['sewa']));
                    $kembaliDate = strtotime(str_replace(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],$rental['kembali']));
                    $totalDays = max(1, round(($kembaliDate - $sewaDate) / 86400));
                    $daysLeft = max(0, round(($kembaliDate - time()) / 86400));
                    $currentDay = $totalDays - $daysLeft;
                        $progress = round(($currentDay / $totalDays) * 100);
                    ?>
                    <div class="rental-card animate-stagger" style="animation-delay:<?= 0.2 + ($idx * 0.1) ?>s">
                        <div class="rental-card-body">
                            <img src="<?= $img ?>" alt="Camping gear" class="rental-img" loading="lazy">
                            <div class="rental-info">
                                <div class="rental-id-badge"><i class="bi bi-hash"></i><?= $rental['id'] ?></div>
                                <div class="rental-items-text"><?= htmlspecialchars($rental['barang']) ?></div>
                                <div class="rental-dates">
                                    <i class="bi bi-calendar3"></i>
                                    <?= $rental['sewa'] ?>
                                    <span class="arrow">-></span>
                                    <?= $rental['kembali'] ?>
                                </div>
                                <div class="rental-progress-wrap">
                                    <div class="rental-progress-fill" style="width:<?= $progress ?>%"></div>
                                </div>
                                <div class="rental-progress-label">
                                    <span>Hari <?= $currentDay ?> dari <?= $totalDays ?></span>
                                    <span class="days-remaining-pill <?= $daysLeft <= 1 ? 'urgent' : '' ?>">
                                        <i class="bi bi-clock"></i><?= $daysLeft ?> hari tersisa
                                    </span>
                                </div>
                                <div class="rental-actions">
                                    <button class="btn-perpanjang" onclick="togglePanel('<?= $rental['id'] ?>')">
                                        <i class="bi bi-arrow-repeat"></i>Perpanjang
                                    </button>
                                    <button class="btn-detail-expand" onclick="openPengembalianModal('<?= $rental['reservasi_id'] ?>', '<?= $rental['trx_id'] ?>', '<?= $rental['id'] ?>', '<?= htmlspecialchars($rental['barang']) ?>', '<?= $rental['kembali'] ?>')" style="background:var(--trx-primary); color:white; border-color:var(--trx-primary);">
                                        <i class="bi bi-box-seam"></i>Kembalikan
                                    </button>
                                    <button class="btn-detail-expand" onclick="openDetailReservasiModal('<?= $rental['reservasi_id'] ?>')">
                                        <i class="bi bi-info-circle"></i>Detail
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Perpanjangan Slide-Down Panel -->
                        <div class="perpanjang-panel" id="panel-<?= $rental['id'] ?>">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <label class="fw-semibold mb-2 d-block" style="font-size:0.88rem; color:var(--trx-text);">Tanggal Kembali Baru</label>
                                    <?php 
                                        $kembaliTs = strtotime(str_replace(['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], $rental['kembali']));
                                        $minExtDate = date('Y-m-d', strtotime('+1 day', $kembaliTs));
                                        $maxExtDate = date('Y-m-d', strtotime('+7 days', $kembaliTs));
                                    ?>
                                    <input type="date" class="form-control" 
                                           id="extDate-<?= $rental['id'] ?>" 
                                           min="<?= $minExtDate ?>" 
                                           max="<?= $maxExtDate ?>" 
                                           value="<?= $minExtDate ?>"
                                           onchange="updateCostFromDate('<?= $rental['id'] ?>', '<?= date('Y-m-d', $kembaliTs) ?>')"
                                           style="border-radius:var(--trx-input-radius); padding:0.6rem 0.75rem; border:1px solid #D1D5DB; box-shadow:0 1px 2px rgba(0,0,0,0.05); cursor:pointer;">
                                    <input type="hidden" id="days-<?= $rental['id'] ?>" data-days="1" value="1">
                                    <span style="font-size:0.75rem; color:var(--trx-text-muted); display:block; margin-top:0.4rem;">
                                        Tambahan hari: <span id="daysLabel-<?= $rental['id'] ?>" class="fw-bold text-primary">1</span> hari
                                    </span>
                                </div>
                                <div class="col-md-7">
                                    <div class="cost-display">
                                        <div class="cost-row">
                                            <span class="cost-label">Biaya harian</span>
                                            <span class="cost-value" id="daily-<?= $rental['id'] ?>">Rp <?= number_format($totalDays > 0 ? intval($rental['total'] / $totalDays) : 0, 0, ',', '.') ?></span>
                                        </div>
                                        <div class="cost-row" style="border-top:1px dashed #E5E7EB; margin-top:0.4rem; padding-top:0.5rem;">
                                            <span class="cost-label fw-semibold" style="color:var(--trx-text);">Biaya Tambahan</span>
                                            <span class="cost-value cost-total" id="extCost-<?= $rental['id'] ?>"
                                                data-daily="<?= $totalDays > 0 ? intval($rental['total'] / $totalDays) : 0 ?>"
                                                data-rental-id="<?= $rental['id'] ?>">
                                                Rp <?= number_format($totalDays > 0 ? intval($rental['total'] / $totalDays) : 0, 0, ',', '.') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <label class="fw-semibold mb-2 d-block mt-3" style="font-size:0.88rem; color:var(--trx-text);">Metode Pembayaran</label>
                            <div class="pay-card-grid">
                                <div class="pay-card selected" data-method="transfer" onclick="selectPayCard(this, '<?= $rental['id'] ?>')">
                                    <div class="pay-card-icon" style="color:#3B82F6;"><i class="bi bi-bank"></i></div>
                                    <div class="pay-card-label">Transfer</div>
                                    <div class="pay-card-detail">Pilihan Multi-Bank</div>
                                </div>
                                <div class="pay-card" data-method="qris" onclick="selectPayCard(this, '<?= $rental['id'] ?>')">
                                    <div class="pay-card-icon" style="color:var(--trx-gold);"><i class="bi bi-qr-code"></i></div>
                                    <div class="pay-card-label">QRIS</div>
                                    <div class="pay-card-detail">Scan QR Code</div>
                                </div>
                            </div>

                            <!-- Payment Detail Information -->
                            <div class="payment-detail-box" id="payDetail-<?= $rental['id'] ?>" style="background:#F9FAFB; border-radius:12px; padding:1rem; margin-bottom:1rem; border:1px solid #E5E7EB;">
                                <!-- Transfer Info -->
                                <div id="infoTransfer-<?= $rental['id'] ?>">
                                    <p class="fw-semibold mb-2" style="font-size:0.85rem; color:#374151;">Transfer ke salah satu rekening berikut:</p>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="bank-option selected" onclick="selectBank(this)" style="cursor:pointer; background:white; border:2px solid var(--trx-primary); padding:0.5rem 0.75rem; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bank-radio" style="width:16px; height:16px; border-radius:50%; border:2px solid var(--trx-primary); display:flex; align-items:center; justify-content:center;">
                                                    <div class="bank-radio-inner" style="width:8px; height:8px; border-radius:50%; background:var(--trx-primary);"></div>
                                                </div>
                                                <div><span class="fw-bold text-primary">BCA</span><br><small class="text-muted" style="font-size:0.7rem;">a/n SimpelCamp</small></div>
                                            </div>
                                            <span class="fw-bold" style="font-family:monospace; letter-spacing:1px;">1234567890</span>
                                        </div>
                                        <div class="bank-option" onclick="selectBank(this)" style="cursor:pointer; background:white; border:1px solid #E5E7EB; padding:0.5rem 0.75rem; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bank-radio" style="width:16px; height:16px; border-radius:50%; border:1px solid #CBD5E1; display:flex; align-items:center; justify-content:center;">
                                                    <div class="bank-radio-inner" style="width:8px; height:8px; border-radius:50%; background:transparent;"></div>
                                                </div>
                                                <div><span class="fw-bold text-primary">Mandiri</span><br><small class="text-muted" style="font-size:0.7rem;">a/n SimpelCamp</small></div>
                                            </div>
                                            <span class="fw-bold" style="font-family:monospace; letter-spacing:1px;">0987654321</span>
                                        </div>
                                        <div class="bank-option" onclick="selectBank(this)" style="cursor:pointer; background:white; border:1px solid #E5E7EB; padding:0.5rem 0.75rem; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bank-radio" style="width:16px; height:16px; border-radius:50%; border:1px solid #CBD5E1; display:flex; align-items:center; justify-content:center;">
                                                    <div class="bank-radio-inner" style="width:8px; height:8px; border-radius:50%; background:transparent;"></div>
                                                </div>
                                                <div><span class="fw-bold text-primary">BRI</span><br><small class="text-muted" style="font-size:0.7rem;">a/n SimpelCamp</small></div>
                                            </div>
                                            <span class="fw-bold" style="font-family:monospace; letter-spacing:1px;">1122334455</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- QRIS Info -->
                                <div id="infoQris-<?= $rental['id'] ?>" style="display:none; text-align:center;">
                                    <p class="fw-semibold mb-2" style="font-size:0.85rem; color:#374151;">Scan QR Code di bawah ini:</p>
                                    <div style="background:white; padding:1rem; border-radius:10px; display:inline-block; border:1px solid #E5E7EB;">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=QRIS_SIMPELCAMP_<?= $rental['id'] ?>" alt="QRIS" style="width:150px; height:150px;">
                                    </div>
                                    <p class="small text-muted mt-2 mb-0">Mendukung semua e-wallet & mobile banking</p>
                                </div>
                            </div>

                            <!-- Upload Bukti (muncul saat Transfer/QRIS) -->
                            <div class="upload-bukti-section show" style="margin-top:0;" id="uploadSection-<?= $rental['id'] ?>">
                                <label class="fw-semibold mb-2 d-block" style="font-size:0.88rem; color:var(--trx-text);"><i class="bi bi-cloud-arrow-up me-1"></i>Upload Bukti Pembayaran <span class="text-danger">*</span></label>
                                <div class="upload-bukti-area" id="uploadArea-<?= $rental['id'] ?>" onclick="document.getElementById('buktiFile-<?= $rental['id'] ?>').click()">
                                    <i class="bi bi-image upload-icon d-block"></i>
                                    <span class="d-block" style="font-size:0.8rem; color:#6B7280;" id="uploadText-<?= $rental['id'] ?>">Klik untuk pilih foto bukti transfer</span>
                                    <small class="text-muted" style="font-size:0.7rem;">JPG, PNG - Maks 2MB</small>
                                    <img class="upload-bukti-preview" id="uploadPreview-<?= $rental['id'] ?>" style="display:none;">
                                </div>
                                <input type="file" id="buktiFile-<?= $rental['id'] ?>" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewBuktiPerpanjangan(this, '<?= $rental['id'] ?>')">
                            </div>

                            <div class="perpanjang-panel-actions">
                                <button class="btn-cancel-ext" onclick="togglePanel('<?= $rental['id'] ?>')">Batal</button>
                                <button class="btn-confirm-ext" onclick="openConfirmModal('<?= $rental['id'] ?>', '<?= htmlspecialchars($rental['barang']) ?>', '<?= $rental['kembali'] ?>', '<?= $rental['reservasi_id'] ?>')">
                                    <i class="bi bi-check-circle"></i>Konfirmasi Perpanjangan
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php $idx++; endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- ───────────────────────────────────────────
                     TAB 2: RIWAYAT TRANSAKSI
                     ─────────────────────────────────────────── -->
                <div class="tab-panel" id="panel-riwayat">
                    <div class="section-header">
                        <div class="section-header-icon" style="background:rgba(45,106,79,0.08); color:var(--trx-primary);">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="font-family:'Outfit',sans-serif; font-size:1.05rem; color:var(--trx-text);">Riwayat Transaksi</h5>
                            <p class="small mb-0" style="color:var(--trx-text-muted);">Semua riwayat penyewaan peralatan camping Anda</p>
                        </div>
                    </div>

                    <!-- Filter Pills -->
                    <div class="filter-pills">
                        <button class="filter-pill active" onclick="filterCards('all', this)">Semua</button>
                        <button class="filter-pill" onclick="filterCards('Aktif', this)">
                            <span class="filter-dot blue"></span>Aktif
                        </button>
                        <button class="filter-pill" onclick="filterCards('Selesai', this)">
                            <span class="filter-dot green"></span>Selesai
                        </button>
                        <button class="filter-pill" onclick="filterCards('Dibatalkan', this)">
                            <span class="filter-dot red"></span>Dibatalkan
                        </button>
                    </div>

                    <!-- Transaction Cards -->
                    <div id="trxCardList">
                    <?php foreach ($riwayat as $i => $r): ?>
                        <div class="trx-card" data-status="<?= $r['status'] ?>" style="transition-delay:<?= $i * 0.08 ?>s">
                            <div class="trx-status-bar <?= strtolower($r['status']) ?>"></div>
                            <div class="trx-card-body">
                                <div class="trx-main-info">
                                    <span class="trx-id" onclick="copyId('<?= $r['id'] ?>')" title="Klik untuk salin"><?= $r['id'] ?></span>
                                    <div class="trx-items"><?= htmlspecialchars($r['barang']) ?></div>
                                    <div class="trx-dates">
                                        <i class="bi bi-calendar3"></i>
                                        <?= $r['sewa'] ?> <span style="color:var(--trx-primary-light); font-weight:700;">-></span> <?= $r['kembali'] ?>
                                    </div>
                                </div>
                                <div class="trx-meta">
                                    <span class="trx-total">Rp <?= number_format($r['total'], 0, ',', '.') ?></span>
                                    <span class="status-badge status-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span>
                                    <span class="metode-badge"><i class="bi bi-<?= $r['metode'] === 'QRIS' ? 'qr-code' : ($r['metode'] === 'Transfer' ? 'bank' : 'cash-stack') ?> me-1"></i><?= $r['metode'] ?></span>
                                </div>
                                <div class="trx-actions">
                                    <?php if ($r['raw_status'] === 'menunggu_bayar'): ?>
                                    <?php if (in_array($r['raw_tipe'], ['walk_in', 'cash'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:10px;background:#FEF3C7;color:#92400E;font-size:0.78rem;font-weight:600;"><i class="bi bi-cash-coin"></i> Bayar di Toko</span>
                                    <?php else: ?>
                                    <button class="btn-trx-bayar" onclick="openPayModal(<?= $r['trx_id'] ?>, <?= $r['total'] ?>, '<?= htmlspecialchars($r['barang']) ?>')">
                                        <i class="bi bi-wallet2"></i>Bayar
                                    </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($r['raw_status'] === 'menunggu_denda'): ?>
                                    <button class="btn-trx-bayar" style="background:var(--trx-primary);color:#fff;" onclick="openDendaModal(<?= $r['trx_id'] ?>, <?= $r['denda'] ?>, '<?= htmlspecialchars(addslashes($r['barang'])) ?>')">
                                        <i class="bi bi-exclamation-triangle"></i>Bayar Denda
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn-trx-nota" onclick="openNotaModal(<?= $r['trx_id'] ?>)">
                                        <i class="bi bi-file-text"></i>Nota
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <!-- Empty state -->
                    <div class="empty-state" id="emptyState" style="display:none;">
                        <i class="bi bi-funnel d-block"></i>
                        <p class="fw-semibold mt-2 mb-1" style="color:var(--trx-text);">Tidak ada transaksi</p>
                        <p class="small mb-0" style="color:var(--trx-text-muted);">Tidak ada transaksi dengan status yang dipilih.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- -------------------------------------------
     MODALS
     ------------------------------------------- -->

<!-- Confirm Perpanjangan Modal -->
<div class="modal fade modal-premium" id="confirmExtModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="font-family:'Outfit',sans-serif;"><i class="bi bi-arrow-repeat me-2"></i>Konfirmasi Perpanjangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="confirm-summary">
                    <div class="confirm-row">
                        <span class="label">ID Sewa</span>
                        <span class="value" id="cRentalId" style="font-family:'JetBrains Mono',monospace;"></span>
                    </div>
                    <div class="confirm-row">
                        <span class="label">Barang</span>
                        <span class="value" id="cItems"></span>
                    </div>
                    <div class="confirm-row">
                        <span class="label">Kembali Semula</span>
                        <span class="value" id="cOrigDate"></span>
                    </div>
                    <div class="confirm-row">
                        <span class="label">Tambahan Hari</span>
                        <span class="value" id="cDays" style="color:var(--trx-primary);"></span>
                    </div>
                    <div class="confirm-row">
                        <span class="label">Metode Bayar</span>
                        <span class="value" id="cPayMethod"></span>
                    </div>
                    <div class="confirm-row confirm-total">
                        <span class="label fw-bold">Biaya Tambahan</span>
                        <span class="value" id="cTotalCost"></span>
                    </div>
                </div>
                <div id="cUploadSection" style="display:none; margin-top:1.25rem;">
                    <label class="fw-semibold mb-2" style="font-size:0.88rem; color:var(--trx-text);">Upload Bukti Pembayaran <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="cBuktiBayar" accept="image/jpeg, image/png, image/jpg" style="border-radius:var(--trx-input-radius);">
                    <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Harap unggah bukti transfer / QRIS (Max 2MB)</small>
                </div>
            </div>
            <div class="modal-footer border-0" style="padding:0.75rem 1.5rem 1.25rem;">
                <button class="btn-cancel-ext" data-bs-dismiss="modal">Batal</button>
                <button class="btn-confirm-ext" onclick="submitExtension()">
                    <i class="bi bi-check-circle"></i>Konfirmasi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border:none; border-radius:var(--trx-card-radius); overflow:hidden;">
            <div class="success-modal-body" id="successBody">
                <div class="checkmark-circle">
                    <svg viewBox="0 0 40 40">
                        <path class="checkmark-path" d="M10 20 L17 27 L30 13"/>
                    </svg>
                </div>
                <h4 class="fw-bold mb-2" style="font-family:'Outfit',sans-serif; color:var(--trx-text);">Perpanjangan Berhasil!</h4>
                <p style="color:var(--trx-text-muted);" class="mb-1">Masa sewa telah diperpanjang untuk:</p>
                <div class="success-id-display mb-1" id="sRentalId"></div>
                <p class="small" style="color:var(--trx-text-muted);" id="sInfo"></p>
                <button class="btn-confirm-ext mt-3" data-bs-dismiss="modal" style="margin:0 auto;" onclick="window.location.reload()">
                    <i class="bi bi-check-lg"></i>Selesai
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Bayar Denda -->
<div class="modal fade modal-premium" id="dendaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="font-family:'Outfit',sans-serif; color:var(--trx-primary);"><i class="bi bi-exclamation-triangle me-2"></i>Pembayaran Denda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex gap-3 align-items-center mb-4">
                    <i class="bi bi-info-circle-fill fs-4"></i>
                    <div>
                        <strong>Pesanan <span id="dendaTrxId"></span></strong><br>
                        Barang: <span id="dendaItems"></span>
                    </div>
                </div>
                <div class="confirm-summary mb-3">
                    <div class="confirm-row confirm-total">
                        <span class="label fw-bold">Total Denda</span>
                        <span class="value" id="dendaTotal"></span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-semibold mb-2" style="font-size:0.88rem; color:var(--trx-text);">Upload Bukti Pembayaran <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="dendaBukti" accept="image/jpeg, image/png, image/jpg" style="border-radius:var(--trx-input-radius);">
                    <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Harap unggah bukti transfer denda (Max 2MB)</small>
                </div>
            </div>
            <div class="modal-footer border-0" style="padding:0.75rem 1.5rem 1.25rem;">
                <button class="btn-cancel-ext" data-bs-dismiss="modal">Batal</button>
                <button class="btn-confirm-ext" id="btnSubmitDenda" onclick="submitDenda()">
                    <i class="bi bi-upload"></i>Kirim Bukti
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let _dendaTrxId = 0;
function openDendaModal(id, denda, items) {
    _dendaTrxId = id;
    document.getElementById('dendaTrxId').textContent = 'TRX-' + id;
    document.getElementById('dendaItems').textContent = items;
    document.getElementById('dendaTotal').textContent = formatRp(denda);
    document.getElementById('dendaBukti').value = '';
    new bootstrap.Modal(document.getElementById('dendaModal')).show();
}

function submitDenda() {
    const fileInput = document.getElementById('dendaBukti');
    if (!fileInput.files || fileInput.files.length === 0) {
        showToast('Harap unggah foto bukti transfer!', 'bi-exclamation-triangle');
        return;
    }

    const btn = document.getElementById('btnSubmitDenda');
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('transaksi_id', _dendaTrxId);
    formData.append('bukti_denda', fileInput.files[0]);

    fetch('<?= BASE_URL ?>/api/pengembalian.php?action=bayar_denda_transfer', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('dendaModal')).hide();
            showToast('Bukti berhasil dikirim!', 'bi-check-circle');
            setTimeout(() => location.reload(), 1500);
        } else {
            alert(data.message || 'Gagal mengirim bukti denda.');
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan sistem.');
        console.error(error);
    })
    .finally(() => {
        btn.innerHTML = origHtml;
        btn.disabled = false;
    });
}

function selectBank(el) {
    // Reset all bank options in this group
    const group = el.closest('.d-flex.flex-column');
    group.querySelectorAll('.bank-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.style.border = '1px solid #E5E7EB';
        const radio = opt.querySelector('.bank-radio');
        radio.style.border = '1px solid #CBD5E1';
        radio.querySelector('.bank-radio-inner').style.background = 'transparent';
    });
    
    // Select the clicked one
    el.classList.add('selected');
    el.style.border = '2px solid var(--trx-primary)';
    const radio = el.querySelector('.bank-radio');
    radio.style.border = '2px solid var(--trx-primary)';
    radio.querySelector('.bank-radio-inner').style.background = 'var(--trx-primary)';
}

</script>

<!-- Modal Ajukan Pengembalian -->
<div class="modal fade modal-premium" id="pengembalianModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="font-family:'Outfit',sans-serif;"><i class="bi bi-box-seam me-2"></i>Ajukan Pengembalian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="confirm-summary mb-3">
                    <div class="confirm-row">
                        <span class="label">ID Sewa</span>
                        <span class="value" id="pRentalId" style="font-family:'JetBrains Mono',monospace;"></span>
                    </div>
                    <div class="confirm-row">
                        <span class="label">Barang</span>
                        <span class="value" id="pItems"></span>
                    </div>

                </div>
                <div id="lateWarningContainer" style="display:none; background:#FEF2F2; border-left:4px solid #EF4444; padding:1rem; border-radius:8px; margin-bottom:1rem;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-circle-fill text-danger me-2" style="font-size:1.2rem;"></i>
                            <div>
                                <h6 class="mb-1 text-danger fw-bold">Anda Terlambat <span id="lateDays">0</span> Hari</h6>
                                <p class="mb-0 text-danger" style="font-size:0.85rem;">Denda keterlambatan: <span id="lateFine" class="fw-bold">Rp 0</span>. Denda ini akan ditambahkan ke tagihan setelah admin memeriksa kondisi barang.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                    <label class="fw-semibold mb-2" style="font-size:0.88rem; color:var(--trx-text);">Upload Foto Barang Saat Ini <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="pFotoBarang" accept="image/jpeg, image/png, image/jpg" style="border-radius:var(--trx-input-radius);">
                    <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Unggah foto barang yang ingin dikembalikan untuk pengecekan admin (Max 2MB).</small>
                </div>
            </div>
            <div class="modal-footer border-0" style="padding:0.75rem 1.5rem 1.25rem;">
                <button class="btn-cancel-ext" data-bs-dismiss="modal">Batal</button>
                <button class="btn-confirm-ext" id="btnSubmitPengembalian" onclick="submitPengembalian()" style="background:var(--trx-primary);">
                    <i class="bi bi-upload"></i>Ajukan Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container-custom" id="toastBox"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
   TRANSAKSI PAGE -- JavaScript v2
   - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

// --------------------------
// Utility
// --------------------------
function formatRp(n) {
    return 'Rp ' + n.toLocaleString('id-ID');
}

// --------------------------
// Toast System
// --------------------------
function showToast(msg, icon) {
    const box = document.getElementById('toastBox');
    if (!box) return;
    const toast = document.createElement('div');
    toast.className = 'toast-custom';
    toast.innerHTML = `<span class="toast-icon"><i class="bi ${icon || 'bi-info-circle'}"></i></span>${msg}`;
    box.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// --------------------------
// Copy ID
// --------------------------
function copyId(id) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(id).then(() => {
            showToast('ID ' + id + ' berhasil disalin!', 'bi-clipboard-check');
        }).catch(() => { fallbackCopy(id); });
    } else {
        fallbackCopy(id);
    }
}

function fallbackCopy(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        if (document.execCommand('copy'))
            showToast('ID ' + text + ' berhasil disalin!', 'bi-clipboard-check');
        else alert('Gagal menyalin kode');
    } catch (err) { alert('Gagal menyalin kode'); }
    document.body.removeChild(textArea);
}

// --------------------------
// Tab Switching with Sliding Indicator
// --------------------------
function switchTab(tab) {
    document.querySelectorAll('.premium-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    const btn = document.querySelector(`.premium-tab-btn[data-tab="${tab}"]`);
    if(btn) btn.classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
    updateIndicator();
    if (tab === 'riwayat') animateTrxCards();
}

function updateIndicator() {
    const tabs = document.getElementById('premiumTabs');
    const activeBtn = tabs.querySelector('.premium-tab-btn.active');
    const indicator = document.getElementById('tabIndicator');
    if (activeBtn) {
        indicator.style.left = activeBtn.offsetLeft + 'px';
        indicator.style.width = activeBtn.offsetWidth + 'px';
    }
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Perpanjangan Panel (slide-down)
// - - - - - - - - - - - - - - - - - - - - - - - - - - 
function togglePanel(id) {
    const panel = document.getElementById('panel-' + id);
    panel.classList.toggle('open');
}

function toggleDetail(id) {
    const detail = document.getElementById('detail-' + id);
    detail.classList.toggle('open');
}

function stepDay(id, delta) {
    // legacy function kept for safety
    const el = document.getElementById('days-' + id);
    if(el) {
        let val = parseInt(el.dataset.days) + delta;
        val = Math.max(1, Math.min(7, val));
        el.dataset.days = val;
        updateCost(id, val);
    }
}

function updateCostFromDate(id, originalDateStr) {
    const input = document.getElementById('extDate-' + id);
    const origDate = new Date(originalDateStr);
    const newDate = new Date(input.value);
    
    // Calculate difference in days
    const diffTime = newDate.getTime() - origDate.getTime();
    let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    // Fallback if invalid
    if (isNaN(diffDays) || diffDays < 1) {
        input.value = input.min;
        diffDays = 1;
    }
    
    // Update the hidden days element
    const daysEl = document.getElementById('days-' + id);
    if(daysEl) daysEl.dataset.days = diffDays;
    
    const daysLabel = document.getElementById('daysLabel-' + id);
    if(daysLabel) daysLabel.textContent = diffDays;
    
    // Call the original updateCost function
    updateCost(id, diffDays);
}

function updateCost(id, days) {
    const costEl = document.getElementById('extCost-' + id);
    const daily = parseInt(costEl.dataset.daily);
    const total = daily * days;
    
    // Set cost attribute for calculation, and innerHTML for display
    costEl.dataset.total = total;
    costEl.innerHTML = `<span style="font-size:0.8rem; color:var(--trx-text-muted); font-weight:normal; margin-right:8px;">(${days}x ${formatRp(daily)})</span>${formatRp(total)}`;
    
    // Flash animation
    costEl.style.color = '#2D6A4F';
    costEl.style.transform = 'scale(1.05)';
    setTimeout(() => {
        costEl.style.color = 'var(--trx-primary)';
        costEl.style.transform = 'scale(1)';
    }, 200);
}

function selectPayCard(el, rentalId) {
    el.closest('.pay-card-grid').querySelectorAll('.pay-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');

    const method = el.dataset.method;
    
    // Toggle info boxes
    const infoTransfer = document.getElementById('infoTransfer-' + rentalId);
    const infoQris = document.getElementById('infoQris-' + rentalId);
    
    if (infoTransfer && infoQris) {
        if (method === 'transfer') {
            infoTransfer.style.display = 'block';
            infoQris.style.display = 'none';
        } else if (method === 'qris') {
            infoTransfer.style.display = 'none';
            infoQris.style.display = 'block';
        }
    }
}

function previewBuktiPerpanjangan(input, rentalId) {
    const area = document.getElementById('uploadArea-' + rentalId);
    const text = document.getElementById('uploadText-' + rentalId);
    const preview = document.getElementById('uploadPreview-' + rentalId);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            showToast('File terlalu besar! Maksimal 2MB.', 'bi-exclamation-triangle');
            input.value = '';
            return;
        }
        text.textContent = file.name;
        area.classList.add('has-file');
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Confirm Modal
// - - - - - - - - - - - - - - - - - - - - - - - - - - 
let _confirmRentalId = '';
let _confirmReservasiId = '';
let _confirmTanggalBaru = '';

function openConfirmModal(id, items, origDate, reservasiId) {
    _confirmRentalId = id;
    _confirmReservasiId = reservasiId;

    const days = parseInt(document.getElementById('days-' + id).dataset.days);
    const costEl = document.getElementById('extCost-' + id);
    const costTotal = costEl.dataset.total ? parseInt(costEl.dataset.total) : parseInt(costEl.dataset.daily) * days;
    const cost = formatRp(costTotal);
    
    const panel = document.getElementById('panel-' + id);
    const selectedPay = panel.querySelector('.pay-card.selected .pay-card-label');
    const payMethod = selectedPay ? selectedPay.textContent : 'Cash';

    // Parse origDate (d M Y) and add days
    // Assume origDate format is 'd M Y' e.g. '01 Jul 2026'
    // To be safe, let's fetch raw date or parse correctly.
    const monthMap = { 'Jan':0, 'Feb':1, 'Mar':2, 'Apr':3, 'Mei':4, 'Jun':5, 'Jul':6, 'Agu':7, 'Sep':8, 'Okt':9, 'Nov':10, 'Des':11 };
    const parts = origDate.split(' ');
    let dateObj = new Date();
    if(parts.length === 3) {
        dateObj = new Date(parts[2], monthMap[parts[1]] || 0, parts[0]);
    }
    dateObj.setDate(dateObj.getDate() + days);
    let newDateStr = dateObj.getFullYear() + '-' + String(dateObj.getMonth() + 1).padStart(2, '0') + '-' + String(dateObj.getDate()).padStart(2, '0');
    
    // Check if we have the explicit calendar input
    const dateInput = document.getElementById('extDate-' + id);
    if(dateInput && dateInput.value) {
        newDateStr = dateInput.value;
    }
    
    _confirmTanggalBaru = newDateStr;

    document.getElementById('cRentalId').textContent = id;
    document.getElementById('cItems').textContent = items;
    document.getElementById('cOrigDate').textContent = origDate;
    document.getElementById('cDays').textContent = days + ' hari';
    document.getElementById('cPayMethod').textContent = payMethod;
    document.getElementById('cTotalCost').textContent = cost;

    // Check if bukti was already uploaded in the panel
    const panelFileInput = document.getElementById('buktiFile-' + id);
    const confirmUploadSec = document.getElementById('cUploadSection');
    if (payMethod.toLowerCase() === 'transfer' || payMethod.toLowerCase() === 'qris') {
        if (panelFileInput && panelFileInput.files && panelFileInput.files.length > 0) {
            // Already uploaded in panel, show filename in confirm modal
            confirmUploadSec.style.display = 'block';
            confirmUploadSec.innerHTML = '<div class="d-flex align-items-center gap-2 p-2" style="background:#F0FDF4; border-radius:10px; border:1px solid #BBF7D0;">' +
                '<i class="bi bi-check-circle-fill" style="color:var(--trx-primary); font-size:1.1rem;"></i>' +
                '<div><small class="fw-semibold d-block" style="color:var(--trx-primary);">Bukti sudah diunggah</small>' +
                '<small class="text-muted">' + panelFileInput.files[0].name + '</small></div></div>';
        } else {
            // Not yet uploaded, show upload input in modal
            confirmUploadSec.style.display = 'block';
            confirmUploadSec.innerHTML = '<label class="fw-semibold mb-2" style="font-size:0.88rem; color:var(--trx-text);">Upload Bukti Pembayaran <span class="text-danger">*</span></label>' +
                '<input type="file" class="form-control" id="cBuktiBayar" accept="image/jpeg, image/png, image/jpg" style="border-radius:var(--trx-input-radius);">' +
                '<small class="text-muted d-block mt-1" style="font-size:0.75rem;">Harap unggah bukti transfer / QRIS (Max 2MB)</small>';
        }
    } else {
        confirmUploadSec.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('confirmExtModal')).show();
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Submit Extension -> Success Modal + Confetti
// - - - - - - - - - - - - - - - - - - - - - - - - - - 
function submitExtension() {
    const id = _confirmRentalId;
    const days = document.getElementById('cDays').textContent;
    const cost = document.getElementById('cTotalCost').textContent;
    const payMethod = document.getElementById('cPayMethod').textContent.toLowerCase();
    
    // Check bukti from panel first, then fallback to confirm modal
    const panelFileInput = document.getElementById('buktiFile-' + id);
    const modalFileInput = document.getElementById('cBuktiBayar');
    let buktiFile = null;
    
    if (panelFileInput && panelFileInput.files && panelFileInput.files.length > 0) {
        buktiFile = panelFileInput.files[0];
    } else if (modalFileInput && modalFileInput.files && modalFileInput.files.length > 0) {
        buktiFile = modalFileInput.files[0];
    }

    if ((payMethod === 'transfer' || payMethod === 'qris') && !buktiFile) {
        showToast('Harap unggah bukti pembayaran!', 'bi-exclamation-triangle');
        return;
    }

    const btn = document.querySelector('#confirmExtModal .btn-confirm-ext');
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('reservasi_id', _confirmReservasiId);
    formData.append('tanggal_baru', _confirmTanggalBaru);
    formData.append('metode_bayar', payMethod);
    if (buktiFile) {
        formData.append('bukti_bayar', buktiFile);
    }

    fetch('<?= BASE_URL ?>/api/perpanjangan.php?action=create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('confirmExtModal')).hide();
            setTimeout(() => {
                document.getElementById('sRentalId').textContent = id;
                document.getElementById('sInfo').textContent = 'Diperpanjang ' + days + ' - ' + cost;
                const modal = new bootstrap.Modal(document.getElementById('successModal'));
                modal.show();
                spawnConfetti();
                showToast('Perpanjangan berhasil diajukan!', 'bi-check-circle');
            }, 350);
        } else {
            bootstrap.Modal.getInstance(document.getElementById('confirmExtModal')).hide();
            setTimeout(() => {
                showToast(data.message || 'Gagal mengajukan perpanjangan.', 'bi-exclamation-circle');
            }, 350);
        }
    })
    .catch(error => {
        bootstrap.Modal.getInstance(document.getElementById('confirmExtModal')).hide();
        setTimeout(() => {
            showToast('Terjadi kesalahan sistem.', 'bi-exclamation-circle');
        }, 350);
        console.error(error);
    })
    .finally(() => {
        btn.innerHTML = origHtml;
        btn.disabled = false;
    });
}

// ---------------------------------------------------------
// PENGEMBALIAN LOGIC
// ---------------------------------------------------------
let _returnReservasiId = '';

async function openPengembalianModal(reservasiId, trxId, kode, items, kembali) {
    _returnReservasiId = reservasiId;
    document.getElementById('pRentalId').textContent = kode;
    document.getElementById('pItems').textContent = items;
    document.getElementById('pFotoBarang').value = '';
    
    // Hide warning by default
    document.getElementById('lateWarningContainer').style.display = 'none';
    
    // Show modal first so it doesn't wait
    new bootstrap.Modal(document.getElementById('pengembalianModal')).show();
    
    try {
        // Cek denda via hitung_denda
        const response = await fetch('<?= BASE_URL ?>/api/pengembalian.php?action=hitung_denda', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ transaksi_id: trxId, tanggal_kembali: new Date().toISOString().split('T')[0] })
        });
        const res = await response.json();
        if(res.status === 'success' && res.data.hari_terlambat > 0) {
            document.getElementById('lateDays').textContent = res.data.hari_terlambat;
            document.getElementById('lateFine').textContent = formatRp(res.data.denda_keterlambatan);
            document.getElementById('lateWarningContainer').style.display = 'block';
        }
    } catch(err) {
        console.error("Gagal mengecek denda:", err);
    }
}

function submitPengembalian() {
    const fileInput = document.getElementById('pFotoBarang');
    if (!fileInput.files || fileInput.files.length === 0) {
        showToast('Harap unggah foto kondisi barang!', 'bi-exclamation-triangle');
        return;
    }

    const btn = document.getElementById('btnSubmitPengembalian');
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('reservasi_id', _returnReservasiId);
    formData.append('bukti_foto', fileInput.files[0]);

    fetch('<?= BASE_URL ?>/api/pengembalian.php?action=ajukan', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('pengembalianModal')).hide();
            showToast('Pengembalian berhasil diajukan!', 'bi-check-circle');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            alert(data.message || 'Gagal mengajukan pengembalian.');
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan sistem.');
        console.error(error);
    })
    .finally(() => {
        btn.innerHTML = origHtml;
        btn.disabled = false;
    });
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - 
// Confetti Animation
// - - - - - - - - - - - - - - - - - - - - - - - - - - 
function spawnConfetti() {
    const body = document.getElementById('successBody');
    const colors = ['#52B788', '#2D6A4F', '#E9C46A', '#D4A373', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6'];
    for (let i = 0; i < 35; i++) {
        const piece = document.createElement('div');
        piece.className = 'confetti-piece';
        piece.style.left = Math.random() * 100 + '%';
        piece.style.animationDelay = Math.random() * 0.6 + 's';
        piece.style.animationDuration = (2 + Math.random() * 2) + 's';
        piece.style.background = colors[Math.floor(Math.random() * colors.length)];
        piece.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        piece.style.width = (6 + Math.random() * 8) + 'px';
        piece.style.height = (6 + Math.random() * 8) + 'px';
        body.appendChild(piece);
        setTimeout(() => piece.remove(), 4000);
    }
}

// --------------------------
// Riwayat Filter
// --------------------------
function filterCards(status, btn) {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.trx-card');
    let visibleCount = 0;
    let delay = 0;

    cards.forEach(card => {
        const match = status === 'all' || card.dataset.status === status;
        if (match) {
            card.style.display = 'flex';
            card.classList.remove('visible');
            setTimeout(() => card.classList.add('visible'), 60 * delay);
            delay++;
            visibleCount++;
        } else {
            card.style.display = 'none';
            card.classList.remove('visible');
        }
    });

    document.getElementById('emptyState').style.display = visibleCount === 0 ? 'block' : 'none';
    showToast('Filter: ' + (status === 'all' ? 'Semua' : status), 'bi-funnel');
}

// Stagger animate cards on tab switch
function animateTrxCards() {
    const cards = document.querySelectorAll('.trx-card');
    cards.forEach((card, i) => {
        card.classList.remove('visible');
        setTimeout(() => card.classList.add('visible'), 80 * i);
    });
}

// --------------------------
// Detail Modal (Riwayat) with Timeline
// --------------------------
function showDetailModal(id, trxId, sewa, kembali, barang, total, status, metode) {
    document.getElementById('dId').textContent = id;
    document.getElementById('dBarang').textContent = barang;
    document.getElementById('dMetode').textContent = metode;
    document.getElementById('dTotal').textContent = formatRp(total);
    document.getElementById('dSewa').textContent = sewa;
    document.getElementById('dKembali').textContent = kembali;
    document.getElementById('dNotaLink').dataset.trxId = trxId;

    const badge = document.getElementById('dStatus');
    badge.className = 'status-badge status-' + status.toLowerCase();
    badge.textContent = status;

    // Timeline logic
    const isAktif = status === 'Aktif';
    const isSelesai = status === 'Selesai';

    document.getElementById('dDate1').textContent = sewa;
    document.getElementById('dDate2').textContent = sewa;

    const dot3 = document.getElementById('dDot3');
    const dot4 = document.getElementById('dDot4');
    const label3 = document.getElementById('dLabel3');
    const label4 = document.getElementById('dLabel4');

    dot3.className = 'timeline-dot';
    dot4.className = 'timeline-dot';
    label3.className = 'timeline-label';
    label4.className = 'timeline-label';

    if (isAktif) {
        dot3.classList.add('current');
        label3.classList.add('current');
        document.getElementById('dDate3').textContent = sewa;
        document.getElementById('dDate4').textContent = '-';
    } else if (isSelesai) {
        dot3.classList.add('done');
        dot4.classList.add('done');
        label3.classList.add('done');
        label4.classList.add('done');
        document.getElementById('dDate3').textContent = sewa;
        document.getElementById('dDate4').textContent = kembali;
    } else {
        document.getElementById('dDate3').textContent = '-';
        document.getElementById('dDate4').textContent = '-';
    }

    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

// --------------------------
// Init
// --------------------------
document.addEventListener('DOMContentLoaded', () => {
    updateIndicator();
    window.addEventListener('resize', updateIndicator);

    // Hash-based tab switching
    const hash = window.location.hash;
    if (hash === '#riwayat') {
        switchTab('riwayat');
    }

    // Initial card stagger animation
    setTimeout(() => {
        document.querySelectorAll('.trx-card').forEach((card, i) => {
            setTimeout(() => card.classList.add('visible'), 100 * i);
        });
    }, 300);
});

</script>

<!-- Payment Modal -->
<div class="modal fade modal-premium" id="payModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none; border-radius:20px; overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706); border:none; padding:1.2rem 1.5rem;">
                <h5 class="modal-title fw-bold text-white" style="font-family:'Outfit',sans-serif;"><i class="bi bi-wallet2 me-2"></i>Upload Bukti Pembayaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem;">
                <div class="mb-3 p-3" style="background:#f0fdf4; border-radius:12px; border:1px solid #bbf7d0;">
                    <small class="text-muted d-block">Total yang harus dibayar</small>
                    <span class="fw-bold fs-4" style="color:var(--trx-primary);" id="payAmount">Rp 0</span>
                    <small class="d-block mt-1" style="color:#6b7280;" id="payItems"></small>
                </div>
                <input type="hidden" id="payTrxId">
                <label class="fw-semibold mb-2 d-block" style="font-size:0.88rem;">Metode Pembayaran</label>
                <div class="pay-method-cards">
                    <div class="pay-method-card" onclick="selectPayMethod(this, 'qris')">
                        <i class="bi bi-qr-code"></i><span>QRIS</span>
                    </div>
                    <div class="pay-method-card" onclick="selectPayMethod(this, 'transfer')">
                        <i class="bi bi-bank"></i><span>Transfer</span>
                    </div>
                    <div class="pay-method-card" onclick="selectPayMethod(this, 'ewallet')">
                        <i class="bi bi-phone"></i><span>E-Wallet</span>
                    </div>
                </div>
                <input type="hidden" id="payMethod" value="">
                <label class="fw-semibold mb-2 d-block" style="font-size:0.88rem;">Upload Bukti Bayar</label>
                <div class="pay-upload-area" onclick="document.getElementById('payFileInput').click()">
                    <i class="bi bi-cloud-arrow-up" id="payUploadIcon"></i>
                    <span id="payFileName">Klik untuk upload gambar bukti bayar</span>
                    <small class="d-block text-muted mt-1">JPG, PNG, WebP (Max 2MB)</small>
                    <img id="payPreview" class="pay-preview" style="display:none;">
                </div>
                <input type="file" id="payFileInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewPayFile(this)">
                <div class="mt-3">
                    <label class="fw-semibold mb-1 d-block" style="font-size:0.88rem;">Catatan (opsional)</label>
                    <textarea id="payCatatan" class="form-control" rows="2" placeholder="Catatan tambahan..." style="border-radius:10px; font-size:0.88rem;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border:none; padding:0 1.5rem 1.5rem;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;">Batal</button>
                <button type="button" class="btn" id="paySubmitBtn" onclick="submitPayment()" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; border:none; border-radius:10px; font-weight:600; padding:0.5rem 1.5rem;">
                    <i class="bi bi-send me-1"></i>Kirim Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedPayMethod = '';

function openPayModal(trxId, total, items) {
    document.getElementById('payTrxId').value = trxId;
    document.getElementById('payAmount').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('payItems').textContent = items;
    document.getElementById('payMethod').value = '';
    document.getElementById('payFileInput').value = '';
    document.getElementById('payFileName').textContent = 'Klik untuk upload gambar bukti bayar';
    document.getElementById('payPreview').style.display = 'none';
    document.getElementById('payUploadIcon').className = 'bi bi-cloud-arrow-up';
    document.querySelector('.pay-upload-area').classList.remove('has-file');
    document.getElementById('payCatatan').value = '';
    document.querySelectorAll('.pay-method-card').forEach(c => c.classList.remove('selected'));
    selectedPayMethod = '';
    new bootstrap.Modal(document.getElementById('payModal')).show();
}

function selectPayMethod(el, method) {
    document.querySelectorAll('.pay-method-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedPayMethod = method;
    document.getElementById('payMethod').value = method;
}

function previewPayFile(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            alert('File terlalu besar! Maksimal 2MB.');
            input.value = '';
            return;
        }
        document.getElementById('payFileName').textContent = file.name;
        document.getElementById('payUploadIcon').className = 'bi bi-check-circle-fill';
        document.querySelector('.pay-upload-area').classList.add('has-file');
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('payPreview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function submitPayment() {
    const trxId = document.getElementById('payTrxId').value;
    const method = selectedPayMethod;
    const file = document.getElementById('payFileInput').files[0];
    const catatan = document.getElementById('payCatatan').value;
    const totalText = document.getElementById('payAmount').textContent;
    const jumlah = parseInt(totalText.replace(/[^0-9]/g, ''));

    if (!method) { alert('Pilih metode pembayaran!'); return; }
    if (!file) { alert('Upload bukti pembayaran!'); return; }

    const btn = document.getElementById('paySubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...';

    const formData = new FormData();
    formData.append('transaksi_id', trxId);
    formData.append('metode', method);
    formData.append('jumlah', jumlah);
    formData.append('bukti_bayar', file);
    formData.append('catatan', catatan);

    fetch('<?= BASE_URL ?>/api/pembayaran.php?action=create', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('payModal')).hide();
            alert('Bukti pembayaran berhasil dikirim! Menunggu konfirmasi admin.');
            location.reload();
        } else {
            alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(err => {
        alert('Error: ' + err.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send me-1"></i>Kirim Pembayaran';
    });
}


let currentReviewTrxId = null;

function openReviewModal(trxId, barangListStr) {
    currentReviewTrxId = trxId;
    let barangList = [];
    try {
        barangList = JSON.parse(barangListStr);
    } catch(e) { console.error(e); }
    
    const container = document.getElementById('reviewItemsContainer');
    container.innerHTML = '';
    
    if (barangList.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">Tidak ada barang yang bisa diulas.</div>';
    } else {
        barangList.forEach((b, index) => {
            container.innerHTML += `
                <div class="review-item-form mb-4 pb-3 border-bottom">
                    <h6 class="fw-bold mb-2">${b.nama}</h6>
                    <input type="hidden" name="reviews[${index}][barang_id]" value="${b.id}">
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-1">Rating</label>
                        <select class="form-select form-select-sm" name="reviews[${index}][rating]" required>
                            <option value="5">5 Bintang - Sangat Bagus</option>
                            <option value="4">4 Bintang - Bagus</option>
                            <option value="3">3 Bintang - Cukup</option>
                            <option value="2">2 Bintang - Kurang</option>
                            <option value="1">1 Bintang - Buruk</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted mb-1">Komentar (Opsional)</label>
                        <textarea class="form-control form-control-sm" name="reviews[${index}][komentar]" rows="2" placeholder="Tuliskan pengalaman Anda..."></textarea>
                    </div>
                </div>
            `;
        });
    }
    
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

async function submitReview(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitReview');
    const form = e.target;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

    const formData = new FormData(form);
    formData.append('transaksi_id', currentReviewTrxId);
    
    try {
        const response = await fetch('<?= BASE_URL ?>/api/ulasan.php?action=submit', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();
        
        if (res.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
            alert('Terima kasih! Ulasan Anda berhasil disimpan.');
        } else {
            alert(res.message || 'Gagal menyimpan ulasan');
        }
    } catch (err) {
        alert('Terjadi kesalahan jaringan.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send me-2"></i>Kirim Ulasan';
    }
}

async function openDetailReservasiModal(rsvId) {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/reservasi.php?action=detail&id=' + rsvId);
        const data = await res.json();
        if (data.status === 'success') {
            const r = data.data;
            document.getElementById('mDetailKode').textContent = r.kode_reservasi;
            document.getElementById('mDetailTglSewa').textContent = r.tanggal_mulai;
            document.getElementById('mDetailTglKembali').textContent = r.tanggal_selesai;
            document.getElementById('mDetailBiaya').textContent = 'Rp ' + parseInt(r.total_biaya).toLocaleString('id-ID');
            document.getElementById('mDetailCatatan').textContent = r.catatan || '-';
            
            let html = '';
            r.items.forEach(item => {
                html += `<div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                    <span>${item.nama_barang} <span class="badge bg-secondary ms-1">x${item.jumlah}</span></span>
                    <span class="fw-medium">Rp ${parseInt(item.subtotal).toLocaleString('id-ID')}</span>
                </div>`;
            });
            document.getElementById('mDetailItems').innerHTML = html;
            
            new bootstrap.Modal(document.getElementById('detailReservasiModal')).show();
        } else {
            alert('Gagal mengambil detail reservasi: ' + data.message);
        }
    } catch (err) {
        alert('Terjadi kesalahan jaringan.');
    }
}

</script>


<!-- Detail Reservasi Modal (Aktif) -->
<div class="modal fade modal-premium" id="detailReservasiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-card-list me-2"></i>Detail Penyewaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4 text-center">
                    <small class="text-muted d-block mb-1">Kode Reservasi</small>
                    <span class="fw-bold" id="mDetailKode" style="font-size:1.3rem; color:var(--trx-primary); padding: 5px 15px; background: #e0f2fe; border-radius: 8px;"></span>
                </div>
                <div class="row mb-4">
                    <div class="col-6 text-center">
                        <small class="text-muted d-block mb-1">Tanggal Sewa</small>
                        <span class="fw-medium" id="mDetailTglSewa"></span>
                    </div>
                    <div class="col-6 text-center">
                        <small class="text-muted d-block mb-1">Tanggal Kembali</small>
                        <span class="fw-medium text-danger" id="mDetailTglKembali"></span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-2 fw-bold">Daftar Barang yang Disewa:</small>
                    <div id="mDetailItems" class="p-3 bg-light rounded border">
                    </div>
                </div>
                
                <div class="mt-3 p-3" style="background:#f8f9fa; border-radius:8px;">
                    <small class="text-muted d-block mb-1">Catatan Pesanan:</small>
                    <div id="mDetailCatatan" class="fst-italic text-secondary" style="font-size:0.9rem;"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <span class="text-muted fw-bold">Total Biaya</span>
                    <span class="fw-bold fs-4 text-primary" id="mDetailBiaya"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:20px;overflow:hidden;box-shadow:0 25px 80px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:#F8FAF9;border-bottom:1px solid rgba(0,0,0,0.04);padding:1.5rem;">
                <div style="display:flex;align-items:center;gap:1rem;">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(245,158,11,0.1);display:flex;align-items:center;justify-content:center;color:#F59E0B;font-size:1.1rem;">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" style="font-family:var(--font-heading);font-weight:700;color:var(--text-primary);">Berikan Ulasan</h5>
                        <div style="font-size:0.8rem;color:var(--text-secondary);">Bagaimana pengalaman Anda menggunakan alat kami?</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="background-size:0.7em;opacity:0.5;"></button>
            </div>
            <div class="modal-body p-4">
                <form id="reviewForm" onsubmit="submitReview(event)">
                    <div id="reviewItemsContainer">
                        <!-- Dynamic items here -->
                    </div>
                    <div class="text-end mt-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSubmitReview" style="background:#F59E0B;border:none;box-shadow:0 8px 20px rgba(245,158,11,0.25);">
                            <i class="bi bi-send me-2"></i>Kirim Ulasan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nota Invoice -->
<div class="modal fade" id="notaModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:900px;">
        <div class="modal-content" style="border:none; border-radius:20px; overflow:hidden; box-shadow:0 25px 80px rgba(0,0,0,0.18);">
            <div class="modal-header" style="background:linear-gradient(135deg,#2D6A4F,#40916C); padding:1.2rem 1.5rem; border:none;">
                <h5 class="modal-title fw-bold" style="font-family:'Outfit',sans-serif; color:#fff;">
                    <i class="bi bi-receipt me-2"></i>Nota Transaksi
                </h5>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button type="button" class="btn btn-sm" style="background:rgba(255,255,255,0.2);color:#fff;border:none;border-radius:8px;padding:6px 14px;font-size:0.8rem;" onclick="printNota()">
                        <i class="bi bi-printer me-1"></i>Cetak
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="height:70vh; overflow:hidden;">
                <div id="notaLoading" style="display:flex;align-items:center;justify-content:center;height:100%;">
                    <div style="text-align:center;">
                        <div class="spinner-border text-success" role="status" style="width:3rem;height:3rem;"></div>
                        <p class="mt-3 text-muted">Memuat nota...</p>
                    </div>
                </div>
                <iframe id="notaIframe" style="width:100%;height:100%;border:none;display:none;" onload="document.getElementById('notaLoading').style.display='none';this.style.display='block';"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function openNotaModal(trxId) {
    const iframe = document.getElementById('notaIframe');
    const loading = document.getElementById('notaLoading');
    iframe.style.display = 'none';
    loading.style.display = 'flex';
    iframe.src = 'nota.php?id=' + trxId + '&embed=1';
    new bootstrap.Modal(document.getElementById('notaModal')).show();
}
function printNota() {
    const iframe = document.getElementById('notaIframe');
    if (iframe && iframe.contentWindow) {
        iframe.contentWindow.print();
    }
}
function openNotaFromDetail() {
    const trxId = document.getElementById('dNotaLink').dataset.trxId;
    bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
    setTimeout(function() { openNotaModal(trxId); }, 400);
}
</script>
</body>
</html>



