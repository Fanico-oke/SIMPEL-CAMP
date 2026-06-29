<?php
// pages/admin/nota.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Transaksi.php';
require_once dirname(__DIR__, 2) . '/classes/Reservasi.php';
require_once dirname(__DIR__, 2) . '/classes/Pembayaran.php';

requireRole(['admin', 'superadmin']);

$page_title = 'Nota / Invoice';
$current_page = 'reservasi';

// Get transaction ID from URL
$trx_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$transaksi = null;
$reservasi = null;
$details = [];
$pembayaran = [];
$subtotal = 0;
$diskon = 0;
$totalBayar = 0;
$durasi = 0;
$metodeBayar = '-';
$statusBayar = 'Belum Bayar';

if ($trx_id > 0) {
    $transaksi = Transaksi::getById($trx_id);
    if ($transaksi && !empty($transaksi['reservasi_id'])) {
        $reservasi = Reservasi::getById($transaksi['reservasi_id']);
        $details = $reservasi['details'] ?? [];
        $diskon = (float)($reservasi['diskon'] ?? 0);
        
        // Calculate duration
        if (!empty($transaksi['tanggal_mulai']) && !empty($transaksi['tanggal_selesai'])) {
            $start = new DateTime($transaksi['tanggal_mulai']);
            $end = new DateTime($transaksi['tanggal_selesai']);
            $durasi = $end->diff($start)->days;
            if ($durasi < 1) $durasi = 1;
        }
        
        // Calculate subtotal from details
        foreach ($details as $d) {
            $subtotal += (float)$d['subtotal'];
        }
        
        $totalBayar = (float)$transaksi['total_bayar'];
        
        // Get payment info
        $pembayaran = Pembayaran::getByTransaksi($trx_id);
        if (!empty($pembayaran)) {
            $lastPayment = $pembayaran[0];
            $metodeBayar = ucfirst($lastPayment['metode'] ?? '-');
            $statusBayar = $lastPayment['status'] === 'dikonfirmasi' ? 'LUNAS' : ucfirst($lastPayment['status'] ?? 'Pending');
        }
    }
}

$invoiceNo = 'INV/' . date('Y/m', strtotime($transaksi['created_at'] ?? 'now')) . '/' . str_pad($trx_id, 3, '0', STR_PAD_LEFT);
$invoiceDate = date('d F Y', strtotime($transaksi['created_at'] ?? 'now'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin <?= APP_NAME ?></title>
    <meta name="description" content="Nota invoice penyewaan peralatan camping SIMPEL-CAMP">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781677663">
    <style>
        /* ═══════════════════════════════════════════
           NOTA / INVOICE PAGE STYLES
           ═══════════════════════════════════════════ */

        /* Print Controls */
        .print-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .btn-print-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            border: 1.5px solid var(--border, #e5e7eb);
            background: var(--bg-card, #fff);
            color: #374151;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-print-back:hover {
            border-color: #2D6A4F;
            color: #2D6A4F;
        }
        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-print-primary {
            background: linear-gradient(135deg, #2D6A4F, #52B788);
            color: #fff;
        }
        .btn-print-primary:hover {
            background: linear-gradient(135deg, #1B4332, #2D6A4F);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45,106,79,0.3);
        }
        .btn-print-secondary {
            background: rgba(220,38,38,0.08);
            color: #dc2626;
            border: 1.5px solid rgba(220,38,38,0.2);
        }
        .btn-print-secondary:hover {
            background: rgba(220,38,38,0.12);
        }

        /* Invoice Container */
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .invoice-inner {
            padding: 2.5rem;
        }

        /* Company Header */
        .invoice-company {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .company-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .company-logo {
            font-size: 2.5rem;
            line-height: 1;
        }
        .company-name {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: #1B4332;
        }
        .company-name span {
            color: #D4A373;
        }
        .company-address {
            text-align: right;
            font-size: 0.8rem;
            color: #6b7280;
            line-height: 1.6;
        }
        .invoice-divider {
            height: 3px;
            background: linear-gradient(90deg, #1B4332, #2D6A4F, #52B788);
            border: none;
            margin: 1.5rem 0;
            border-radius: 3px;
        }

        /* Invoice Meta */
        .invoice-meta {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .invoice-meta-block h6 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2D6A4F;
            margin-bottom: 8px;
        }
        .meta-row {
            display: flex;
            gap: 12px;
            font-size: 0.85rem;
            padding: 3px 0;
        }
        .meta-row .m-label {
            color: #6b7280;
            min-width: 100px;
        }
        .meta-row .m-value {
            font-weight: 600;
            color: #1f2937;
        }
        .bill-to {
            font-size: 0.85rem;
            line-height: 1.6;
        }
        .bill-to .bt-name {
            font-weight: 700;
            font-size: 1rem;
            color: #1f2937;
        }

        /* Invoice Items Table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        .invoice-table thead th {
            background: #1B4332;
            color: #fff;
            padding: 12px 14px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-table thead th:first-child {
            border-radius: 8px 0 0 0;
        }
        .invoice-table thead th:last-child {
            border-radius: 0 8px 0 0;
        }
        .invoice-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f3f4f6;
        }
        .invoice-table tbody tr:hover {
            background: rgba(82,183,136,0.03);
        }

        /* Payment Summary */
        .payment-summary {
            width: 300px;
            margin-left: auto;
        }
        .summary-line {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.9rem;
        }
        .summary-line .s-label { color: #6b7280; }
        .summary-line .s-value { font-weight: 600; }
        .summary-line.discount .s-value { color: #059669; }
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 12px 14px;
            background: linear-gradient(135deg, #1B4332, #2D6A4F);
            color: #fff;
            border-radius: 10px;
            margin-top: 8px;
            font-weight: 700;
            font-size: 1.05rem;
        }
        .payment-method-line {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.85rem;
            margin-top: 8px;
        }
        .payment-status {
            color: #059669;
            font-weight: 700;
            font-size: 0.9rem;
        }

        /* Invoice Footer */
        .invoice-notes {
            background: #f8faf9;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 2rem;
            border-left: 3px solid #52B788;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 2.5rem;
            padding-top: 1rem;
        }
        .signature-block {
            text-align: center;
            width: 180px;
        }
        .signature-line {
            border-bottom: 1px solid #d1d5db;
            height: 60px;
            margin-bottom: 8px;
        }
        .signature-label {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 600;
        }
        .invoice-legal {
            text-align: center;
            font-size: 0.7rem;
            color: #9ca3af;
            margin-top: 1.5rem;
            font-style: italic;
        }

        /* ═══════════════════════════════════════════
           PRINT STYLES
           ═══════════════════════════════════════════ */
        @media print {
            /* Hide everything except invoice */
            .admin-sidebar,
            .sidebar-backdrop,
            .admin-topbar,
            .print-controls,
            .sidebar-toggle-btn,
            .pelanggan-topbar {
                display: none !important;
            }

            .admin-wrapper {
                display: block !important;
            }
            .admin-main {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .admin-content {
                padding: 0 !important;
            }

            body {
                background: #fff !important;
                margin: 0;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: 100% !important;
            }
            .invoice-inner {
                padding: 1.5rem !important;
            }

            /* Print-friendly table */
            .invoice-table thead th {
                background: #1B4332 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .summary-total {
                background: #1B4332 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .invoice-notes {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                margin: 10mm;
                size: A4;
            }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php $_header_role = 'admin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


        <!-- Content -->
        <div class="admin-content">

            <?php if (!$transaksi): ?>
            <div class="text-center py-5">
                <i class="bi bi-receipt display-3 text-muted"></i>
                <h4 class="mt-3">Transaksi tidak ditemukan</h4>
                <p class="text-muted">Silakan pilih transaksi dari daftar reservasi.</p>
                <a href="<?= BASE_URL ?>/pages/admin/transaksi.php" class="btn btn-sc-primary mt-2">Kembali ke Transaksi</a>
            </div>
            <?php else: ?>

            <!-- ════════════════════════════════════════
                 PRINT CONTROLS
                 ════════════════════════════════════════ -->
            <div class="print-controls">
                <a href="<?= BASE_URL ?>/pages/admin/detail_reservasi.php<?= !empty($transaksi['reservasi_id']) ? '?id=' . (int)$transaksi['reservasi_id'] : '' ?>" class="btn-print-back">
                    <i class="bi bi-arrow-left"></i> Kembali ke Detail
                </a>
                <div class="ms-auto d-flex gap-2">
                    <button class="btn-print btn-print-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Cetak Nota
                    </button>
                    <button class="btn-print btn-print-secondary" onclick="alert('Fitur download PDF akan segera hadir!')">
                        <i class="bi bi-file-pdf"></i> Download PDF
                    </button>
                </div>
            </div>

            <!-- ════════════════════════════════════════
                 INVOICE
                 ════════════════════════════════════════ -->
            <div class="invoice-container">
                <div class="invoice-inner">

                    <!-- Company Header -->
                    <div class="invoice-company">
                        <div class="company-brand">
                            <div class="company-logo">⛺</div>
                            <div>
                                <div class="company-name">SIMPEL<span>-CAMP</span></div>
                                <div style="font-size:0.75rem; color:#6b7280;">Camping Equipment Rental</div>
                            </div>
                        </div>
                        <div class="company-address">
                            <strong style="color:#1f2937;">PT. SIMPEL CAMP Indonesia</strong><br>
                            Jl. Pendaki Gunung No. 88<br>
                            Bandung, Jawa Barat 40123<br>
                            Telp: (022) 1234-5678<br>
                            Email: info@simpelcamp.com
                        </div>
                    </div>

                    <hr class="invoice-divider">

                    <!-- Invoice Meta -->
                    <div class="invoice-meta">
                        <div class="invoice-meta-block">
                            <h6>Detail Invoice</h6>
                            <div class="meta-row">
                                <span class="m-label">No. Invoice</span>
                                <span class="m-value mono-font"><?= htmlspecialchars($invoiceNo) ?></span>
                            </div>
                            <div class="meta-row">
                                <span class="m-label">Tanggal</span>
                                <span class="m-value"><?= htmlspecialchars($invoiceDate) ?></span>
                            </div>
                            <div class="meta-row">
                                <span class="m-label">Kode Transaksi</span>
                                <span class="m-value mono-font"><?= htmlspecialchars($transaksi['kode_transaksi'] ?? '-') ?></span>
                            </div>
                        </div>
                        <div class="invoice-meta-block" style="text-align:right;">
                            <h6>Kepada</h6>
                            <div class="bill-to">
                                <div class="bt-name"><?= htmlspecialchars($transaksi['user_nama'] ?? '-') ?></div>
                                <?= htmlspecialchars($transaksi['user_email'] ?? '-') ?><br>
                                <?= htmlspecialchars($transaksi['user_telp'] ?? '-') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>Deskripsi</th>
                                <th class="text-center" style="width:60px;">Qty</th>
                                <th class="text-center" style="width:80px;">Durasi</th>
                                <th class="text-end" style="width:120px;">Harga Satuan</th>
                                <th class="text-end" style="width:120px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($details)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Tidak ada item</td></tr>
                            <?php else: ?>
                            <?php foreach ($details as $idx => $item): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td class="fw-medium"><?= htmlspecialchars($item['barang_nama'] ?? 'Barang') ?></td>
                                <td class="text-center"><?= (int)$item['jumlah'] ?></td>
                                <td class="text-center"><?= $durasi ?> hari</td>
                                <td class="text-end mono-font">Rp <?= number_format($item['harga_satuan'] ?? 0, 0, ',', '.') ?></td>
                                <td class="text-end mono-font fw-medium">Rp <?= number_format($item['subtotal'] ?? 0, 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Payment Summary -->
                    <div class="payment-summary">
                        <div class="summary-line">
                            <span class="s-label">Subtotal</span>
                            <span class="s-value mono-font">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                        </div>
                        <?php if ($diskon > 0): ?>
                        <div class="summary-line discount">
                            <span class="s-label">Diskon</span>
                            <span class="s-value mono-font">-Rp <?= number_format($diskon, 0, ',', '.') ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-total">
                            <span>Total</span>
                            <span class="mono-font">Rp <?= number_format($totalBayar, 0, ',', '.') ?></span>
                        </div>
                        <div class="payment-method-line">
                            <span style="color:#6b7280;">Metode Bayar</span>
                            <span class="fw-medium"><?= htmlspecialchars($metodeBayar) ?></span>
                        </div>
                        <div class="payment-method-line">
                            <span style="color:#6b7280;">Status</span>
                            <span class="payment-status">
                                <?php if ($statusBayar === 'LUNAS'): ?>
                                <i class="bi bi-check-circle-fill me-1"></i>LUNAS
                                <?php else: ?>
                                <?= htmlspecialchars($statusBayar) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="invoice-notes">
                        <strong><i class="bi bi-info-circle me-1"></i>Catatan:</strong><br>
                        Terima kasih telah menyewa di SIMPEL-CAMP. Barang yang disewa wajib dikembalikan dalam kondisi baik. Kerusakan atau kehilangan akan dikenakan biaya penggantian sesuai ketentuan yang berlaku.
                    </div>

                    <!-- Points Info -->
                    <?php 
                    $poinDapat = floor($totalBayar / 10000); 
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

                    <!-- Legal -->
                    <div class="invoice-legal">
                        Nota ini sah tanpa tanda tangan basah &bull; Dicetak oleh sistem SIMPEL-CAMP &bull; <?= date('d/m/Y H:i') ?>
                    </div>

                </div><!-- /.invoice-inner -->
            </div><!-- /.invoice-container -->

            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
