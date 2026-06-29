<?php
// pages/pelanggan/nota.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';
require_once dirname(__DIR__, 2) . '/classes/Pembayaran.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'pelanggan') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Nota Transaksi';
$current_page = 'transaksi';
$user_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pelanggan';
$is_embed = isset($_GET['embed']) && $_GET['embed'] == '1';

// Load transaction from URL param
$transaksi_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$transaksi = $transaksi_id > 0 ? Transaksi::getById($transaksi_id) : null;

// Verify ownership
if (!$transaksi || (int)$transaksi['user_id'] !== (int)$_SESSION['user_id']) {
    header('Location: ' . BASE_URL . '/pages/pelanggan/transaksi.php');
    exit;
}

// Load reservation details (items)
$reservasi_id = $transaksi['reservasi_id'];
$detail_items = Reservasi::getDetail($reservasi_id);

// Load payment info
$pembayaran_list = Pembayaran::getByTransaksi($transaksi_id);
$pembayaran = !empty($pembayaran_list) ? $pembayaran_list[0] : null;

// Calculate duration
$tgl_mulai = $transaksi['tanggal_mulai'] ?? null;
$tgl_selesai = $transaksi['tanggal_selesai'] ?? null;
$durasi = 1;
if ($tgl_mulai && $tgl_selesai) {
    $start = new DateTime($tgl_mulai);
    $end = new DateTime($tgl_selesai);
    $durasi = max(1, $end->diff($start)->days);
}

// Build invoice-compatible data structure
$id = htmlspecialchars($transaksi['kode_transaksi'] ?? 'TRX-' . $transaksi_id);

// Map status
$status_map = [
    'menunggu_bayar' => 'Belum Bayar',
    'aktif' => 'Aktif',
    'selesai' => 'Lunas',
    'dibatalkan' => 'Dibatalkan',
];
$inv = [
    'nama' => $transaksi['user_nama'] ?? $_SESSION['nama'],
    'telp' => $transaksi['user_telp'] ?? '-',
    'alamat' => '-', // Not in transaksi join, keep placeholder
    'metode' => $pembayaran ? ucfirst($pembayaran['metode'] ?? 'Cash') : ucfirst($transaksi['tipe'] ?? 'Online'),
    'status' => $status_map[$transaksi['status']] ?? ucfirst($transaksi['status']),
    'tgl_sewa' => $tgl_mulai ? date('d M Y', strtotime($tgl_mulai)) : '-',
    'tgl_kembali' => $tgl_selesai ? date('d M Y', strtotime($tgl_selesai)) : '-',
    'tgl_bayar' => $pembayaran && !empty($pembayaran['created_at']) ? date('d M Y', strtotime($pembayaran['created_at'])) : date('d M Y', strtotime($transaksi['created_at'] ?? $tgl_mulai ?? 'now')),
    'diskon' => 0,
    'deposit' => (int)($transaksi['deposit'] ?? 0),
    'items' => [],
];

// Get user address
require_once dirname(__DIR__, 2) . '/classes/User.php';
$user_data = User::getById($_SESSION['user_id']);
if ($user_data && !empty($user_data['alamat'])) {
    $inv['alamat'] = $user_data['alamat'];
}

// Build items from detail_reservasi
foreach ($detail_items as $detail) {
    $inv['items'][] = [
        'nama' => $detail['barang_nama'],
        'harga' => (int)$detail['harga_satuan'],
        'qty' => (int)$detail['jumlah'],
        'durasi' => $durasi,
    ];
}

// If no items found, create a fallback from transaction total
if (empty($inv['items'])) {
    $inv['items'][] = [
        'nama' => 'Paket Sewa',
        'harga' => (int)$transaksi['total_bayar'],
        'qty' => 1,
        'durasi' => 1,
    ];
}

// Calculate diskon from reservasi if available
$reservasi_data = Reservasi::getById($reservasi_id);
if ($reservasi_data && (float)$reservasi_data['diskon'] > 0) {
    $subtotal_raw = 0;
    foreach ($inv['items'] as $item) {
        $subtotal_raw += $item['harga'] * $item['qty'] * $item['durasi'];
    }
    if ($subtotal_raw > 0) {
        $inv['diskon'] = round(((float)$reservasi_data['diskon'] / $subtotal_raw) * 100, 1);
    }
}

// Use database total as the source of truth
$db_total = (int)$transaksi['total_bayar'];
$subtotal_calc = 0;
foreach ($inv['items'] as $item) {
    $subtotal_calc += $item['harga'] * $item['qty'] * $item['durasi'];
}

// If calculated subtotal doesn't match DB, the harga_satuan already includes duration
// In that case, keep items as-is but set durasi display to actual duration
// and recalculate subtotal to match DB total
if ($subtotal_calc != $db_total && $db_total > 0) {
    // harga_satuan in DB already includes duration multiplication
    // So we display: harga per hari = harga_satuan / durasi, durasi = actual, subtotal = harga_satuan * qty
    $inv['items'] = [];
    foreach ($detail_items as $detail) {
        $harga_satuan = (int)$detail['harga_satuan'];
        $qty = (int)$detail['jumlah'];
        // Check if harga_satuan already includes duration
        if ($durasi > 1) {
            $harga_per_hari = round($harga_satuan / $durasi);
            // Verify: if harga_per_hari * durasi ≈ harga_satuan, then harga includes duration
            if (abs($harga_per_hari * $durasi - $harga_satuan) <= 1) {
                $inv['items'][] = [
                    'nama' => $detail['barang_nama'],
                    'harga' => $harga_per_hari,
                    'qty' => $qty,
                    'durasi' => $durasi,
                ];
            } else {
                // harga_satuan does NOT include duration, use as-is
                $inv['items'][] = [
                    'nama' => $detail['barang_nama'],
                    'harga' => $harga_satuan,
                    'qty' => $qty,
                    'durasi' => 1,
                ];
            }
        } else {
            $inv['items'][] = [
                'nama' => $detail['barang_nama'],
                'harga' => $harga_satuan,
                'qty' => $qty,
                'durasi' => 1,
            ];
        }
    }
}

$subtotal = 0;
foreach ($inv['items'] as $item) {
    $subtotal += $item['harga'] * $item['qty'] * $item['durasi'];
}

// Final safety: if subtotal still doesn't match DB, force DB total
if (abs($subtotal - $db_total) > 1 && $db_total > 0) {
    $subtotal = $db_total;
}

$diskon_amount = $subtotal * $inv['diskon'] / 100;
$grand_total = $subtotal - $diskon_amount;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> â€” SIMPEL-CAMP</title>
    <meta name="description" content="Nota transaksi penyewaan alat camping SIMPEL-CAMP">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781550666">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pelanggan-system.css">
    <style>
        /* embed mode: hide everything except the invoice */
        <?php if ($is_embed): ?>
        .pelanggan-wrapper { padding:0 !important; display:block !important; }
        .pelanggan-sidebar, .sidebar-backdrop, .pelanggan-topbar, .pelanggan-topbar.glass-theme, .nota-toolbar { display:none !important; }
        .pelanggan-main { margin-left:0 !important; padding:0 !important; width:100% !important; }
        .pelanggan-content { padding:10px !important; max-width:100% !important; }
        body { background:#fff !important; overflow-y:auto !important; margin:0 !important; padding:0 !important; }
        .nota-invoice-card { margin:0 auto !important; box-shadow:none !important; }
        <?php endif; ?>
        /* â”€â”€ NOTA PAGE STYLES â”€â”€ */
        :root {
            --nota-primary: #2D6A4F;
            --nota-primary-light: #52B788;
            --nota-accent-gold: #D4A373;
            --nota-bg: #F2F7F4;
            --nota-card: #FFFFFF;
            --nota-text: #1A1A2E;
            --nota-muted: #6B7280;
            --nota-radius: 20px;
            --nota-shadow: 0 2px 20px rgba(0,0,0,0.04);
        }

        .nota-toolbar {
            max-width: 820px;
            margin: 0 auto 28px auto;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .nota-toolbar .nota-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 26px;
            border-radius: 50px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(.4,0,.2,1);
            text-decoration: none;
        }
        .nota-btn-back {
            background: var(--nota-card);
            color: var(--nota-text);
            box-shadow: var(--nota-shadow);
        }
        .nota-btn-back:hover {
            background: #f0f0f0;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .nota-btn-share {
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            color: #0369a1;
        }
        .nota-btn-share:hover {
            background: linear-gradient(135deg, #bae6fd, #7dd3fc);
            transform: translateY(-1px);
        }
        .nota-btn-download {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
        .nota-btn-download:hover {
            background: linear-gradient(135deg, #fde68a, #fcd34d);
            transform: translateY(-1px);
        }
        .nota-btn-print {
            background: linear-gradient(135deg, var(--nota-primary), var(--nota-primary-light));
            color: #fff;
        }
        .nota-btn-print:hover {
            background: linear-gradient(135deg, #245a42, #40a070);
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(45,106,79,0.3);
        }

        /* â”€â”€ INVOICE CARD â”€â”€ */
        .nota-invoice-card {
            max-width: 820px;
            margin: 0 auto;
            background: var(--nota-card);
            border-radius: var(--nota-radius);
            box-shadow: var(--nota-shadow);
            overflow: hidden;
            border: none;
        }

        /* â”€â”€ HEADER â”€â”€ */
        .nota-header {
            background: linear-gradient(135deg, var(--nota-primary) 0%, var(--nota-primary-light) 100%);
            padding: 36px 40px 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            overflow: hidden;
        }
        .nota-header::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 160px;
            height: 160px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .nota-header::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: 30%;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .nota-brand {
            position: relative;
            z-index: 1;
        }
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.18);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            backdrop-filter: blur(4px);
        }
            font-size: 24px;
            color: #fff;
        }
        .nota-brand h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 22px;
            color: #fff;
            margin: 0 0 2px 0;
            letter-spacing: 0.5px;
        }
        .nota-brand-sub {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: rgba(255,255,255,0.7);
            font-weight: 400;
        }
        .nota-invoice-label {
            position: relative;
            z-index: 1;
            text-align: right;
        }
        .nota-invoice-label h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 32px;
            color: var(--nota-accent-gold);
            margin: 0 0 4px 0;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .nota-invoice-id {
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            color: rgba(255,255,255,0.85);
            font-weight: 500;
        }

        /* â”€â”€ BODY â”€â”€ */
        .nota-body {
            padding: 36px 40px 40px;
        }

        /* â”€â”€ CUSTOMER INFO â”€â”€ */
        .nota-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            margin-bottom: 32px;
        }
        .nota-info-block {
            background: #f8faf9;
            border-radius: 14px;
            padding: 20px 22px;
        }
        .nota-info-label {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--nota-primary);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nota-info-label i {
            font-size: 14px;
            opacity: 0.7;
        }
        .nota-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
        }
        .nota-info-row:not(:last-child) {
            border-bottom: 1px dashed #e5e7eb;
        }
        .nota-info-key {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--nota-muted);
            font-weight: 500;
        }
        .nota-info-val {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--nota-text);
            font-weight: 600;
            text-align: right;
        }

        /* â”€â”€ ITEMS TABLE â”€â”€ */
        .nota-table-wrap {
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 28px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.03);
        }
        .nota-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Inter', sans-serif;
        }
        .nota-table thead {
            background: linear-gradient(135deg, var(--nota-primary), var(--nota-primary-light));
        }
        .nota-table thead th {
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 14px 18px;
            border: none;
        }
        .nota-table thead th:first-child {
            text-align: left;
        }
        .nota-table thead th:not(:first-child) {
            text-align: center;
        }
        .nota-table thead th:last-child {
            text-align: right;
        }
        .nota-table tbody td {
            padding: 14px 18px;
            font-size: 13px;
            color: var(--nota-text);
            border-bottom: 1px solid #f3f4f6;
        }
        .nota-table tbody td:first-child {
            font-weight: 600;
        }
        .nota-table tbody td:not(:first-child) {
            text-align: center;
        }
        .nota-table tbody td:last-child {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            font-size: 13px;
        }
        .nota-table tbody tr:last-child td {
            border-bottom: none;
        }
        .nota-table tbody tr:hover {
            background: #f8fdf9;
        }
        .nota-item-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
        }

        /* â”€â”€ TOTALS BOX â”€â”€ */
        .nota-totals {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 28px;
        }
        .nota-totals-box {
            width: 320px;
            background: #f8faf9;
            border-radius: 14px;
            padding: 20px 24px;
        }
        .nota-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }
        .nota-total-row:not(:last-child) {
            border-bottom: 1px dashed #e5e7eb;
        }
        .nota-total-label {
            color: var(--nota-muted);
            font-weight: 500;
        }
        .nota-total-val {
            font-family: 'JetBrains Mono', monospace;
            color: var(--nota-text);
            font-weight: 600;
            font-size: 13px;
        }
        .nota-total-row.nota-discount .nota-total-val {
            color: #dc2626;
        }
        .nota-total-row.nota-grand {
            border-bottom: none;
            padding-top: 14px;
            margin-top: 4px;
            border-top: 2px solid var(--nota-primary-light);
        }
        .nota-total-row.nota-grand .nota-total-label {
            font-weight: 800;
            font-size: 15px;
            color: var(--nota-text);
        }
        .nota-total-row.nota-grand .nota-total-val {
            font-size: 18px;
            font-weight: 800;
            color: var(--nota-primary);
        }

        /* â”€â”€ LUNAS BADGE BAR â”€â”€ */
        .nota-status-bar {
            background: linear-gradient(135deg, var(--nota-primary) 0%, var(--nota-primary-light) 100%);
            border-radius: 14px;
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
        }
        .nota-status-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nota-status-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nota-status-icon i {
            font-size: 20px;
            color: #fff;
        }
        .nota-status-text {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 16px;
            color: #fff;
        }
        .nota-status-sub {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: rgba(255,255,255,0.75);
            font-weight: 400;
        }
        .nota-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.22);
            backdrop-filter: blur(6px);
            padding: 8px 22px;
            border-radius: 50px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* â”€â”€ TERMS â”€â”€ */
        .nota-terms {
            margin-bottom: 36px;
        }
        .nota-terms-title {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--nota-primary);
            margin-bottom: 12px;
        }
        .nota-terms-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .nota-terms-list li {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: var(--nota-muted);
            padding: 5px 0;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.6;
        }
        .nota-terms-list li::before {
            content: '';
            width: 5px;
            height: 5px;
            min-width: 5px;
            background: var(--nota-primary-light);
            border-radius: 50%;
            margin-top: 6px;
        }

        /* â”€â”€ SIGNATURES â”€â”€ */
        .nota-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 36px;
        }
        .nota-sig-block {
            text-align: center;
        }
        .nota-sig-label {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: var(--nota-muted);
            font-weight: 600;
            margin-bottom: 60px;
        }
        .nota-sig-line {
            width: 160px;
            margin: 0 auto 8px;
            border-bottom: 2px dashed #d1d5db;
        }
        .nota-sig-name {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: var(--nota-text);
        }

        /* â”€â”€ QR PLACEHOLDER â”€â”€ */
        .nota-qr-section {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #f8faf9;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 32px;
        }
        .nota-qr-box {
            width: 80px;
            height: 80px;
            min-width: 80px;
            background: #fff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }
        .nota-qr-box i {
            font-size: 36px;
            color: var(--nota-primary);
        }
        .nota-qr-info {
            font-family: 'Inter', sans-serif;
        }
        .nota-qr-info-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--nota-text);
            margin-bottom: 4px;
        }
        .nota-qr-info-desc {
            font-size: 12px;
            color: var(--nota-muted);
            line-height: 1.5;
        }

        /* â”€â”€ CLOSING â”€â”€ */
        .nota-closing {
            text-align: center;
            padding-top: 8px;
        }
        .nota-closing-divider {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--nota-primary-light), var(--nota-accent-gold));
            border-radius: 3px;
            margin: 0 auto 16px;
        }
        .nota-closing-text {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--nota-muted);
            margin-bottom: 4px;
        }
        .nota-closing-brand {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: var(--nota-primary);
            letter-spacing: 0.5px;
        }

        /* â”€â”€ SHARE TOAST â”€â”€ */
        .nota-toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: linear-gradient(135deg, var(--nota-primary), var(--nota-primary-light));
            color: #fff;
            padding: 14px 32px;
            border-radius: 50px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 32px rgba(45,106,79,0.3);
            opacity: 0;
            transition: all 0.4s cubic-bezier(.4,0,.2,1);
            z-index: 9999;
            pointer-events: none;
        }
        .nota-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        .nota-toast i {
            font-size: 18px;
        }

        /* â”€â”€ RESPONSIVE â”€â”€ */
        @media (max-width: 768px) {
            .nota-header {
                padding: 28px 24px 24px;
                flex-direction: column;
                gap: 16px;
            }
            .nota-invoice-label {
                text-align: left;
            }
            .nota-invoice-label h2 {
                font-size: 24px;
            }
            .nota-body {
                padding: 24px 20px 28px;
            }
            .nota-info-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .nota-table-wrap {
                overflow-x: auto;
            }
            .nota-table {
                min-width: 500px;
            }
            .nota-totals-box {
                width: 100%;
            }
            .nota-status-bar {
                flex-direction: column;
                gap: 14px;
                text-align: center;
                padding: 20px;
            }
            .nota-status-left {
                flex-direction: column;
            }
            .nota-signatures {
                grid-template-columns: 1fr;
                gap: 28px;
            }
            .nota-qr-section {
                flex-direction: column;
                text-align: center;
            }
            .nota-toolbar {
                justify-content: center;
            }
            .nota-btn {
                padding: 10px 18px;
                font-size: 13px;
            }
        }

        /* â”€â”€ PRINT STYLES â”€â”€ */
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
            body {
                background: #fff !important;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print,
            .pelanggan-sidebar,
            .pelanggan-topbar,
            .nota-toolbar,
            .nota-toast,
            .sidebar-overlay {
                display: none !important;
            }
            .pelanggan-wrapper {
                display: block !important;
            }
            .pelanggan-main {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .pelanggan-content {
                padding: 0 !important;
            }
            .nota-invoice-card {
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: 100% !important;
            }
            .nota-header {
                border-radius: 0 !important;
            }
            .nota-status-bar,
            .nota-table thead,
            .nota-info-block,
            .nota-qr-section,
            .nota-totals-box {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="pelanggan-wrapper">
    <?php if (!$is_embed): ?>
        <?php include dirname(__DIR__, 2) . '/includes/sidebar_pelanggan.php'; ?>
    <?php endif; ?>
    <div class="pelanggan-main">
        <?php if (!$is_embed): ?>
            <?php $_header_role = 'pelanggan'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>
        <?php endif; ?>
        <div class="pelanggan-content" <?= $is_embed ? 'style="padding:10px !important; margin:0;"' : '' ?>>

            <?php if (!$is_embed): ?>
            <!-- â• â• â• â• â• â• â• â• â• â•  PRINT TOOLBAR â• â• â• â• â• â• â• â• â• â•  -->
            <div class="nota-toolbar no-print">
                <a href="<?= BASE_URL ?>/pages/pelanggan/transaksi.php" class="nota-btn nota-btn-back">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <div style="flex:1"></div>
                <button class="nota-btn nota-btn-share" id="btnShare" onclick="shareNota()">
                    <i class="bi bi-share"></i> Share
                </button>
                <button class="nota-btn nota-btn-download" id="btnDownload" onclick="downloadNota()">
                    <i class="bi bi-download"></i> Download
                </button>
                <button class="nota-btn nota-btn-print" id="btnPrint" onclick="window.print()">
                    <i class="bi bi-printer"></i> Cetak
                </button>
            </div>
            <?php endif; ?>

            <!-- â• â• â• â• â• â• â• â• â• â•  INVOICE CARD â• â• â• â• â• â• â• â• â• â•  -->
            <div class="nota-invoice-card" id="invoiceCard">

                <!-- â”€â”€ HEADER â”€â”€ -->
                <div class="nota-header">
                    <div class="nota-brand">
                            <i class="bi bi-fire"></i>
                        </div>
                        <h1>SIMPEL-CAMP</h1>
                        <div class="nota-brand-sub">Sewa Alat Camping Terpercaya</div>
                    </div>
                    <div class="nota-invoice-label">
                        <h2>Invoice</h2>
                        <div class="nota-invoice-id">#<?= htmlspecialchars($id) ?></div>
                    </div>
                </div>

                <!-- â”€â”€ BODY â”€â”€ -->
                <div class="nota-body">

                    <!-- Customer Info Grid -->
                    <div class="nota-info-grid">
                        <div class="nota-info-block">
                            <div class="nota-info-label"><i class="bi bi-person"></i> Informasi Pelanggan</div>
                            <div class="nota-info-row">
                                <span class="nota-info-key">Nama</span>
                                <span class="nota-info-val"><?= htmlspecialchars($inv['nama']) ?></span>
                            </div>
                            <div class="nota-info-row">
                                <span class="nota-info-key">Telepon</span>
                                <span class="nota-info-val"><?= htmlspecialchars($inv['telp']) ?></span>
                            </div>
                            <div class="nota-info-row">
                                <span class="nota-info-key">Alamat</span>
                                <span class="nota-info-val"><?= htmlspecialchars($inv['alamat']) ?></span>
                            </div>
                        </div>
                        <div class="nota-info-block">
                            <div class="nota-info-label"><i class="bi bi-calendar-event"></i> Detail Penyewaan</div>
                            <div class="nota-info-row">
                                <span class="nota-info-key">Tanggal Sewa</span>
                                <span class="nota-info-val"><?= htmlspecialchars($inv['tgl_sewa']) ?></span>
                            </div>
                            <div class="nota-info-row">
                                <span class="nota-info-key">Tanggal Kembali</span>
                                <span class="nota-info-val"><?= htmlspecialchars($inv['tgl_kembali']) ?></span>
                            </div>
                            <div class="nota-info-row">
                                <span class="nota-info-key">Durasi</span>
                                <span class="nota-info-val"><?= $durasi ?> Hari</span>
                            </div>
                            <div class="nota-info-row">
                                <span class="nota-info-key">Metode Bayar</span>
                                <span class="nota-info-val">
                                    <span style="background:linear-gradient(135deg,#e0f2fe,#bae6fd);color:#0369a1;padding:3px 12px;border-radius:50px;font-size:11px;font-weight:700;"><?= htmlspecialchars($inv['metode']) ?></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="nota-table-wrap">
                        <table class="nota-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Harga/Hari</th>
                                    <th>Qty</th>
                                    <th>Durasi</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inv['items'] as $i => $item): 
                                    $item_total = $item['harga'] * $item['qty'] * $item['durasi'];
                                ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <span style="width:30px;height:30px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#065f46;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;"><?= $i + 1 ?></span>
                                            <?= htmlspecialchars($item['nama']) ?>
                                        </div>
                                    </td>
                                    <td><span class="nota-item-price">Rp <?= number_format($item['harga'], 0, ',', '.') ?></span></td>
                                    <td><?= $item['qty'] ?></td>
                                    <td><?= $durasi ?> hari</td>
                                    <td>Rp <?= number_format($item_total, 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals Box -->
                    <div class="nota-totals">
                        <div class="nota-totals-box">
                            <div class="nota-total-row">
                                <span class="nota-total-label">Subtotal</span>
                                <span class="nota-total-val">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                            </div>
                            <?php if ($inv['diskon'] > 0): ?>
                            <div class="nota-total-row nota-discount">
                                <span class="nota-total-label">Diskon (<?= $inv['diskon'] ?>%)</span>
                                <span class="nota-total-val">- Rp <?= number_format($diskon_amount, 0, ',', '.') ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="nota-total-row">
                                <span class="nota-total-label">Deposit</span>
                                <span class="nota-total-val">Rp <?= number_format($inv['deposit'], 0, ',', '.') ?></span>
                            </div>
                            <div class="nota-total-row nota-grand">
                                <span class="nota-total-label">Grand Total</span>
                                <span class="nota-total-val">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- LUNAS Badge Bar -->
                    <div class="nota-status-bar">
                        <div class="nota-status-left">
                            <div class="nota-status-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div>
                                <div class="nota-status-text">Pembayaran Berhasil</div>
                                <div class="nota-status-sub">Dibayar pada <?= htmlspecialchars($inv['tgl_bayar']) ?> via <?= htmlspecialchars($inv['metode']) ?></div>
                            </div>
                        </div>
                        <div class="nota-status-badge">
                            <i class="bi bi-patch-check-fill"></i> <?= strtoupper($inv['status']) ?>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="nota-terms">
                        <div class="nota-terms-title">Syarat & Ketentuan</div>
                        <ul class="nota-terms-list">
                            <li>Alat yang disewa wajib dikembalikan dalam kondisi bersih dan tidak rusak.</li>
                            <li>Keterlambatan pengembalian dikenakan denda Rp 15.000/hari per item.</li>
                            <li>Deposit akan dikembalikan setelah alat diterima dalam kondisi baik.</li>
                            <li>Kerusakan atau kehilangan alat menjadi tanggung jawab penyewa.</li>
                            <li>Pembatalan setelah pembayaran dikenakan biaya administrasi 10%.</li>
                        </ul>
                    </div>

                    <!-- Points Info -->
                    <?php 
                    $poinDapat = floor($db_total / 10000); 
                    if ($poinDapat > 0): 
                    ?>
                    <div style="background: rgba(139, 92, 246, 0.08); border-radius: 8px; padding: 12px; margin-top: 20px; display: flex; align-items: center; gap: 10px; color: #6D28D9; border: 1px solid rgba(139, 92, 246, 0.2);">
                        <i class="bi bi-star-fill" style="font-size: 1.2rem;"></i>
                        <div>
                            <strong style="display: block; font-size: 0.9rem;">Selamat! Anda mendapatkan <?= $poinDapat ?> Poin</strong>
                            <span style="font-size: 0.8rem;">Poin ini akan ditambahkan ke akun Anda setelah transaksi selesai. (Rp 10.000 = 1 Poin)</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- QR Placeholder -->
                    <div class="nota-qr-section">
                        <div class="nota-qr-box">
                            <i class="bi bi-qr-code"></i>
                        </div>
                        <div class="nota-qr-info">
                            <div class="nota-qr-info-title">Verifikasi Digital</div>
                            <div class="nota-qr-info-desc">Scan kode QR untuk memverifikasi keaslian nota ini secara online.<br>ID Transaksi: <strong><?= htmlspecialchars($id) ?></strong></div>
                        </div>
                    </div>

                    <!-- Closing -->
                    <div class="nota-closing">
                        <div class="nota-closing-divider"></div>
                        <div class="nota-closing-text">Terima kasih telah mempercayakan kebutuhan camping Anda kepada kami.</div>
                        <div class="nota-closing-brand">SIMPEL-CAMP</div>
                    </div>

                </div><!-- end .nota-body -->
            </div><!-- end .nota-invoice-card -->

        </div><!-- end .pelanggan-content -->
    </div><!-- end .pelanggan-main -->
</div><!-- end .pelanggan-wrapper -->

<!-- â•â•â•â•â•â•â•â•â•â• SHARE TOAST â•â•â•â•â•â•â•â•â•â• -->
<div class="nota-toast" id="shareToast">
    <i class="bi bi-check-circle-fill"></i>
    <span>Link nota berhasil disalin!</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // â”€â”€ Share function â”€â”€
    function shareNota() {
        const url = window.location.href;
        if (navigator.share) {
            navigator.share({
                title: 'Nota Transaksi #<?= htmlspecialchars($id) ?>',
                text: 'Nota transaksi penyewaan alat camping SIMPEL-CAMP',
                url: url
            }).catch(() => copyToClipboard(url));
        } else {
            copyToClipboard(url);
        }
    }

    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(showToast).catch(() => fallbackCopy(text));
        } else {
            fallbackCopy(text);
        }
    }

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast();
    }

    function showToast() {
        const toast = document.getElementById('shareToast');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // â”€â”€ Download function (print to PDF) â”€â”€
    function downloadNota() {
        window.print();
    }
</script>

</body>
</html>
