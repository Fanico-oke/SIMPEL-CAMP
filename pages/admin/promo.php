<?php
// pages/admin/promo.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/Promo.php';

requireRole(['admin', 'superadmin']);

$page_title = 'Kelola Promo & Diskon';
$current_page = 'promo';

// Upload directory for promo images
$uploadDir = dirname(__DIR__, 2) . '/frontend/uploads/promo/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/**
 * Handle promo image upload
 * @return string|null filename on success, null if no file
 */
function handlePromoUpload($uploadDir) {
    if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return null;
    }
    // Max 2MB
    if ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
        return null;
    }
    $filename = 'promo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }
    return null;
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $gambarFile = handlePromoUpload($uploadDir);
        $createData = [
            'kode' => $_POST['kode'] ?? '',
            'nama' => $_POST['nama'] ?? '',
            'tipe' => $_POST['tipe'] ?? 'persentase',
            'nilai' => $_POST['nilai'] ?? 0,
            'min_transaksi' => $_POST['min_transaksi'] ?? 0,
            'mulai' => $_POST['mulai'] ?? date('Y-m-d'),
            'selesai' => $_POST['selesai'] ?? date('Y-m-d'),
            'kuota' => $_POST['kuota'] ?? 0,
        ];
        if ($gambarFile) {
            $createData['gambar'] = $gambarFile;
        }
        $result = Promo::create($createData);
        if ($result['success']) {
            // Set status if needed
            if (empty($_POST['status_aktif'])) {
                $newId = $result['data']['id'];
                Promo::update($newId, ['status' => 'nonaktif']);
            }
            $_SESSION['flash_success'] = 'Promo berhasil ditambahkan!';
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        header('Location: ' . BASE_URL . '/pages/admin/promo.php');
        exit;
    }
    
    if ($action === 'update' && !empty($_POST['id'])) {
        $gambarFile = handlePromoUpload($uploadDir);
        $data = [
            'nama' => $_POST['nama'] ?? '',
            'tipe' => $_POST['tipe'] ?? 'persentase',
            'nilai' => $_POST['nilai'] ?? 0,
            'min_transaksi' => $_POST['min_transaksi'] ?? 0,
            'mulai' => $_POST['mulai'] ?? '',
            'selesai' => $_POST['selesai'] ?? '',
            'kuota' => $_POST['kuota'] ?? 0,
            'status' => !empty($_POST['status_aktif']) ? 'aktif' : 'nonaktif',
        ];
        if (!empty($_POST['kode'])) {
            $data['kode'] = $_POST['kode'];
        }
        if ($gambarFile) {
            // Delete old image if exists
            $oldPromo = Promo::getById((int)$_POST['id']);
            if ($oldPromo && !empty($oldPromo['gambar']) && file_exists($uploadDir . $oldPromo['gambar'])) {
                unlink($uploadDir . $oldPromo['gambar']);
            }
            $data['gambar'] = $gambarFile;
        }
        $result = Promo::update((int)$_POST['id'], $data);
        $_SESSION['flash_success'] = $result['success'] ? 'Promo berhasil diperbarui!' : $result['message'];
        header('Location: ' . BASE_URL . '/pages/admin/promo.php');
        exit;
    }
    
    if ($action === 'delete' && !empty($_POST['id'])) {
        $result = Promo::delete((int)$_POST['id']);
        $_SESSION['flash_success'] = $result['success'] ? 'Promo berhasil dihapus!' : $result['message'];
        header('Location: ' . BASE_URL . '/pages/admin/promo.php');
        exit;
    }
}

// Check expired promos
Promo::checkExpired();

// Load all promos
$promoList = Promo::getAll();
$flash_success = getFlash('success');
$flash_error = getFlash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=1781677663">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Outfit:wght@400;500;600;700&display=swap');

        :root {
            --promo-dark: #1B4332;
            --promo-mid: #2D6A4F;
            --promo-light: #40916C;
            --promo-accent: #D4A373;
            --promo-accent-light: #E9C89B;
        }

        .promo-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .promo-page-header h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: var(--promo-dark);
            margin: 0;
        }

        .btn-add-promo {
            background: linear-gradient(135deg, var(--promo-mid), var(--promo-light));
            color: #fff;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(45, 106, 79, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-add-promo:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(45, 106, 79, 0.45);
            color: #fff;
            background: linear-gradient(135deg, var(--promo-dark), var(--promo-mid));
        }

        /* Promo Card */
        .promo-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(27, 67, 50, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .promo-card-image {
            width: 100%;
            height: 160px;
            overflow: hidden;
            background: #f0f0f0;
        }

        .promo-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .promo-card:hover .promo-card-image img {
            transform: scale(1.05);
        }

        .promo-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(27, 67, 50, 0.15);
        }

        .promo-card-header {
            background: linear-gradient(135deg, var(--promo-dark), var(--promo-mid));
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .promo-card-header::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }

        .promo-card-header::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -10%;
            width: 120px;
            height: 120px;
            background: rgba(212, 163, 115, 0.1);
            border-radius: 50%;
        }

        .promo-discount-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: linear-gradient(135deg, var(--promo-accent), var(--promo-accent-light));
            color: var(--promo-dark);
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 1.6rem;
            padding: 0.3rem 1rem;
            border-radius: 10px;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 12px rgba(212, 163, 115, 0.3);
        }

        .promo-discount-badge small {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .promo-card-header .promo-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #fff;
            font-size: 1.15rem;
            margin: 0.75rem 0 0;
            position: relative;
            z-index: 1;
        }

        .promo-card-body {
            padding: 1.25rem 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .promo-description {
            font-family: 'Inter', sans-serif;
            color: #6c757d;
            font-size: 0.88rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .promo-meta {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            margin-bottom: 1.25rem;
        }

        .promo-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            color: #555;
        }

        .promo-meta-item i {
            color: var(--promo-mid);
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
        }

        .promo-status-badge {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.3rem 0.9rem;
            border-radius: 20px;
            letter-spacing: 0.3px;
        }

        .promo-status-aktif {
            background: rgba(27, 67, 50, 0.1);
            color: var(--promo-dark);
        }

        .promo-status-nonaktif {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .promo-status-expired {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .promo-card-footer {
            padding: 0.75rem 1.5rem 1.25rem;
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .btn-promo-edit {
            flex: 1;
            padding: 0.5rem;
            border-radius: 10px;
            border: 1.5px solid var(--promo-mid);
            background: transparent;
            color: var(--promo-mid);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.82rem;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .btn-promo-edit:hover {
            background: var(--promo-mid);
            color: #fff;
        }

        .btn-promo-delete {
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            border: 1.5px solid #dc3545;
            background: transparent;
            color: #dc3545;
            font-size: 0.9rem;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-promo-delete:hover {
            background: #dc3545;
            color: #fff;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 16px;
            overflow: hidden;
        }

        .promo-modal-header {
            background: linear-gradient(135deg, var(--promo-dark), var(--promo-mid));
            color: #fff;
            padding: 1.25rem 1.5rem;
            border: none;
        }

        .promo-modal-header .modal-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        .promo-modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .promo-modal-header .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .promo-form-label {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            color: #333;
            margin-bottom: 0.4rem;
        }

        .promo-form-control {
            border-radius: 10px;
            border: 1.5px solid #dee2e6;
            padding: 0.6rem 0.9rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            transition: all 0.25s ease;
        }

        .promo-form-control:focus {
            border-color: var(--promo-mid);
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.12);
        }

        .form-check-input:checked {
            background-color: var(--promo-mid);
            border-color: var(--promo-mid);
        }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            cursor: pointer;
        }

        .modal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 1rem 1.5rem;
        }

        .btn-promo-save {
            background: linear-gradient(135deg, var(--promo-mid), var(--promo-light));
            color: #fff;
            border: none;
            padding: 0.55rem 1.8rem;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-promo-save:hover {
            background: linear-gradient(135deg, var(--promo-dark), var(--promo-mid));
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(45, 106, 79, 0.3);
        }

        /* Delete Modal */
        .delete-icon-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(220, 53, 69, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .delete-icon-circle i {
            font-size: 2rem;
            color: #dc3545;
        }

        .btn-confirm-delete {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #fff;
            border: none;
            padding: 0.55rem 1.8rem;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-confirm-delete:hover {
            background: linear-gradient(135deg, #c82333, #a71d2a);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            .promo-page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .promo-discount-badge {
                font-size: 1.3rem;
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
            <?php if ($flash_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash_success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if ($flash_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="promo-page-header">
                <h2><i class="bi bi-tag-fill me-2" style="color: var(--promo-accent);"></i><?= $page_title ?></h2>
                <button class="btn btn-add-promo" data-bs-toggle="modal" data-bs-target="#promoModal" onclick="resetPromoForm()">
                    <i class="bi bi-plus-circle"></i> Tambah Promo
                </button>
            </div>

            <!-- Promo Cards Grid -->
            <div class="row g-4">
                <?php if (empty($promoList)): ?>
                <div class="col-12">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-tag display-4"></i>
                        <p class="mt-3">Belum ada promo yang ditambahkan.</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($promoList as $promo): 
                    $isAktif = $promo['status'] === 'aktif';
                    $isExpired = $promo['status'] === 'expired';
                    $nilaiDisplay = $promo['tipe'] === 'persentase' 
                        ? htmlspecialchars($promo['nilai']) . '%' 
                        : 'Rp ' . number_format($promo['nilai'], 0, ',', '.');
                    $tanggalDisplay = !empty($promo['mulai']) && !empty($promo['selesai'])
                        ? date('d M Y', strtotime($promo['mulai'])) . ' — ' . date('d M Y', strtotime($promo['selesai']))
                        : 'Ongoing (Tanpa batas waktu)';
                    $statusClass = $isAktif ? 'aktif' : ($isExpired ? 'expired' : 'nonaktif');
                    $statusLabel = $isAktif ? 'Aktif' : ($isExpired ? 'Expired' : 'Nonaktif');
                    $headerStyle = !$isAktif ? ' style="background: linear-gradient(135deg, #495057, #6c757d);"' : '';
                    $badgeStyle = !$isAktif ? ' style="background: linear-gradient(135deg, #adb5bd, #dee2e6); color: #495057;"' : '';
                    $dotColor = $isAktif ? 'var(--promo-mid)' : '#adb5bd';
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="promo-card">
                        <?php if (!empty($promo['gambar'])): ?>
                        <div class="promo-card-image">
                            <img src="<?= ASSETS_URL ?>/../uploads/promo/<?= htmlspecialchars($promo['gambar']) ?>" alt="<?= htmlspecialchars($promo['nama']) ?>" loading="lazy">
                        </div>
                        <?php endif; ?>
                        <div class="promo-card-header"<?= $headerStyle ?>>
                            <div class="promo-discount-badge"<?= $badgeStyle ?>>
                                <?= $nilaiDisplay ?> <small>OFF</small>
                            </div>
                            <h5 class="promo-title"><?= htmlspecialchars($promo['nama']) ?></h5>
                        </div>
                        <div class="promo-card-body">
                            <p class="promo-description">Kode: <strong><?= htmlspecialchars($promo['kode']) ?></strong><?= !empty($promo['min_transaksi']) && $promo['min_transaksi'] > 0 ? ' | Min. Rp ' . number_format($promo['min_transaksi'], 0, ',', '.') : '' ?><?= !empty($promo['kuota']) && $promo['kuota'] > 0 ? ' | Kuota: ' . (int)$promo['terpakai'] . '/' . (int)$promo['kuota'] : '' ?></p>
                            <div class="promo-meta">
                                <div class="promo-meta-item">
                                    <i class="bi bi-calendar-event"></i>
                                    <span><?= htmlspecialchars($tanggalDisplay) ?></span>
                                </div>
                                <div class="promo-meta-item">
                                    <i class="bi bi-circle-fill" style="font-size:0.5rem;color:<?= $dotColor ?>!important;"></i>
                                    <span class="promo-status-badge promo-status-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="promo-card-footer">
                            <button class="btn-promo-edit" onclick="editPromo(<?= (int)$promo['id'] ?>, '<?= htmlspecialchars(addslashes($promo['nama']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($promo['kode']), ENT_QUOTES) ?>', '<?= htmlspecialchars($promo['tipe']) ?>', '<?= htmlspecialchars($promo['nilai']) ?>', '<?= htmlspecialchars($promo['mulai'] ?? '') ?>', '<?= htmlspecialchars($promo['selesai'] ?? '') ?>', <?= $isAktif ? 'true' : 'false' ?>, '<?= htmlspecialchars($promo['min_transaksi'] ?? '0') ?>', '<?= htmlspecialchars($promo['kuota'] ?? '0') ?>', '<?= htmlspecialchars($promo['gambar'] ?? '') ?>')" data-bs-toggle="modal" data-bs-target="#promoModal">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            <button class="btn-promo-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" onclick="setDeleteTarget(<?= (int)$promo['id'] ?>, '<?= htmlspecialchars(addslashes($promo['nama']), ENT_QUOTES) ?>')">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Promo Modal -->
<div class="modal fade" id="promoModal" tabindex="-1" aria-labelledby="promoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="promo-modal-header modal-header">
                <h5 class="modal-title" id="promoModalLabel"><i class="bi bi-tag me-2"></i>Tambah Promo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="promoForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="promoAction" value="create">
                    <input type="hidden" name="id" id="promoId" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="promo-form-label" for="promoNama">Nama Promo</label>
                            <input type="text" class="form-control promo-form-control" id="promoNama" name="nama" placeholder="Contoh: Paket Hemat Pendakian" required>
                        </div>
                        <div class="col-md-6">
                            <label class="promo-form-label" for="promoKode">Kode Promo</label>
                            <input type="text" class="form-control promo-form-control" id="promoKode" name="kode" placeholder="Contoh: HEMAT30" required style="text-transform:uppercase;">
                        </div>
                        <div class="col-md-6">
                            <label class="promo-form-label" for="promoTipe">Tipe Diskon</label>
                            <select class="form-select promo-form-control" id="promoTipe" name="tipe">
                                <option value="persentase">Persentase (%)</option>
                                <option value="nominal">Nominal (Rp)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="promo-form-label" for="promoNilai">Nilai Diskon</label>
                            <div class="input-group">
                                <input type="number" class="form-control promo-form-control" id="promoNilai" name="nilai" placeholder="30" min="0" required>
                                <span class="input-group-text" id="promoNilaiSuffix" style="border-radius:0 10px 10px 0;border:1.5px solid #dee2e6;border-left:0;font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--promo-mid);">%</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="promo-form-label" for="promoMinTransaksi">Min. Transaksi (Rp)</label>
                            <input type="number" class="form-control promo-form-control" id="promoMinTransaksi" name="min_transaksi" placeholder="0" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="promo-form-label" for="promoKuota">Kuota Pemakaian</label>
                            <input type="number" class="form-control promo-form-control" id="promoKuota" name="kuota" placeholder="0 = Unlimited" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <!-- spacer -->
                        </div>
                        <div class="col-md-4">
                            <label class="promo-form-label" for="promoMulai">Tanggal Mulai</label>
                            <input type="date" class="form-control promo-form-control" id="promoMulai" name="mulai" required>
                        </div>
                        <div class="col-md-4">
                            <label class="promo-form-label" for="promoSelesai">Tanggal Selesai</label>
                            <input type="date" class="form-control promo-form-control" id="promoSelesai" name="selesai" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch d-flex align-items-center gap-2 mt-1">
                                <input class="form-check-input" type="checkbox" id="promoStatus" name="status_aktif" value="1" checked>
                                <label class="promo-form-label mb-0" for="promoStatus">Status Aktif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="promo-form-label" for="promoGambar"><i class="bi bi-image me-1"></i>Gambar Promo</label>
                            <input type="file" class="form-control promo-form-control" id="promoGambar" name="gambar" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="text-muted" style="font-size:0.78rem;">Format: JPG, PNG, GIF, WEBP. Maks 2MB. Biarkan kosong jika tidak ingin mengubah gambar.</small>
                            <div id="promoGambarPreview" class="mt-2" style="display:none;">
                                <img id="promoGambarPreviewImg" src="" alt="Preview" style="max-height:120px;border-radius:10px;border:2px solid #dee2e6;">
                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearGambarPreview()" style="border-radius:8px;"><i class="bi bi-x-circle"></i> Hapus</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;font-family:'Inter',sans-serif;font-weight:600;">Batal</button>
                <button type="button" class="btn btn-promo-save" onclick="document.getElementById('promoForm').submit()">
                    <i class="bi bi-check-circle me-1"></i> Simpan Promo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="delete-icon-circle">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-2" style="font-family:'Outfit',sans-serif;">Hapus Promo?</h5>
                <p class="text-secondary mb-0" style="font-family:'Inter',sans-serif;font-size:0.9rem;">
                    Apakah Anda yakin ingin menghapus promo <strong id="deleteTargetName"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;font-family:'Inter',sans-serif;font-weight:600;padding:0.55rem 1.5rem;">Batal</button>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteTargetId" value="">
                    <button type="submit" class="btn btn-confirm-delete">
                        <i class="bi bi-trash3 me-1"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
<script>
    // Sidebar toggle for mobile
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.querySelector('.admin-sidebar').classList.toggle('show');
    });

    // Tipe diskon suffix toggle
    document.getElementById('promoTipe').addEventListener('change', function() {
        const suffix = document.getElementById('promoNilaiSuffix');
        suffix.textContent = this.value === 'persentase' ? '%' : 'Rp';
    });

    // Reset form for new promo
    function resetPromoForm() {
        document.getElementById('promoModalLabel').innerHTML = '<i class="bi bi-tag me-2"></i>Tambah Promo';
        document.getElementById('promoForm').reset();
        document.getElementById('promoAction').value = 'create';
        document.getElementById('promoId').value = '';
        document.getElementById('promoStatus').checked = true;
        document.getElementById('promoNilaiSuffix').textContent = '%';
        clearGambarPreview();
    }

    // Populate form for editing
    function editPromo(id, nama, kode, tipe, nilai, mulai, selesai, status, minTransaksi, kuota, gambar) {
        document.getElementById('promoModalLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Promo';
        document.getElementById('promoAction').value = 'update';
        document.getElementById('promoId').value = id;
        document.getElementById('promoNama').value = nama;
        document.getElementById('promoKode').value = kode;
        document.getElementById('promoTipe').value = tipe;
        document.getElementById('promoNilai').value = nilai;
        document.getElementById('promoMulai').value = mulai;
        document.getElementById('promoSelesai').value = selesai;
        document.getElementById('promoStatus').checked = status;
        document.getElementById('promoMinTransaksi').value = minTransaksi;
        document.getElementById('promoKuota').value = kuota;
        document.getElementById('promoNilaiSuffix').textContent = tipe === 'persentase' ? '%' : 'Rp';
        // Show existing image preview
        if (gambar && gambar.length > 0) {
            var imgUrl = '<?= ASSETS_URL ?>/../uploads/promo/' + gambar;
            document.getElementById('promoGambarPreviewImg').src = imgUrl;
            document.getElementById('promoGambarPreview').style.display = 'block';
        } else {
            clearGambarPreview();
        }
    }

    // Clear gambar preview
    function clearGambarPreview() {
        document.getElementById('promoGambarPreview').style.display = 'none';
        document.getElementById('promoGambarPreviewImg').src = '';
        document.getElementById('promoGambar').value = '';
    }

    // Live preview on file select
    document.getElementById('promoGambar').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('promoGambarPreviewImg').src = ev.target.result;
                document.getElementById('promoGambarPreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            clearGambarPreview();
        }
    });

    // Set delete target
    function setDeleteTarget(id, name) {
        document.getElementById('deleteTargetName').textContent = name;
        document.getElementById('deleteTargetId').value = id;
    }
</script>
</body>
</html>
