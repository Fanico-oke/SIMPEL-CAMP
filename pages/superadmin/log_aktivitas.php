<?php
// pages/superadmin/log_aktivitas.php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/classes/LogAktivitas.php';
require_once dirname(__DIR__, 2) . '/classes/User.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'superadmin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$page_title = 'Log Aktivitas Sistem';
$current_page = 'log_aktivitas';

// --- Filters from GET params ---
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to'] ?? '';
$filterUserId   = $_GET['user_id'] ?? '';
$filterAksi     = $_GET['aksi'] ?? '';
$filterSearch   = $_GET['search'] ?? '';
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 10;
$offset         = ($page - 1) * $perPage;

// Build filter array
$filters = ['limit' => $perPage, 'offset' => $offset];
if (!empty($filterDateFrom)) $filters['date_from'] = $filterDateFrom;
if (!empty($filterDateTo))   $filters['date_to']   = $filterDateTo;
if (!empty($filterUserId))   $filters['user_id']   = $filterUserId;
if (!empty($filterAksi))     $filters['aksi']      = $filterAksi;
if (!empty($filterSearch))   $filters['search']    = $filterSearch;

// Get data
$logs = LogAktivitas::getAll($filters);

// Count for pagination (same filters minus limit/offset)
$countFilters = $filters;
unset($countFilters['limit'], $countFilters['offset']);
$totalLogs = LogAktivitas::count($countFilters);
$totalPages = max(1, ceil($totalLogs / $perPage));

// Count today's logs
$todayCount = LogAktivitas::count(['date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d')]);

// Get all users for filter dropdown
$allUsers = User::getAll(null, null, 200, 0);

// Helper function: determine action badge class and icon
function getActionBadge($aksi) {
    $aksiLower = strtolower($aksi);
    if (strpos($aksiLower, 'login') !== false || strpos($aksiLower, 'logout') !== false) {
        return ['action-auth', 'bi-box-arrow-in-right'];
    }
    if (strpos($aksiLower, 'create') !== false || strpos($aksiLower, 'tambah') !== false || strpos($aksiLower, 'buat') !== false) {
        return ['action-create', 'bi-plus-circle'];
    }
    if (strpos($aksiLower, 'update') !== false || strpos($aksiLower, 'ubah') !== false || strpos($aksiLower, 'edit') !== false || strpos($aksiLower, 'approve') !== false) {
        return ['action-update', 'bi-arrow-repeat'];
    }
    if (strpos($aksiLower, 'delete') !== false || strpos($aksiLower, 'hapus') !== false || strpos($aksiLower, 'ban') !== false) {
        return ['action-delete', 'bi-trash3'];
    }
    if (strpos($aksiLower, 'export') !== false || strpos($aksiLower, 'download') !== false) {
        return ['action-export', 'bi-file-earmark-arrow-down'];
    }
    if (strpos($aksiLower, 'setting') !== false || strpos($aksiLower, 'config') !== false) {
        return ['action-setting', 'bi-gear'];
    }
    return ['action-auth', 'bi-activity'];
}

// Helper: get role badge class from user role
function getRoleBadgeClass($email, $allUsers) {
    // We can infer role from the joined data — but log_aktivitas getAll joins users
    // The role isn't directly in the log. We'll check from the user list.
    return 'role-pelanggan'; // default
}

// Build query string for pagination links
function buildQueryString($params, $overrides = []) {
    $merged = array_merge($params, $overrides);
    $clean = array_filter($merged, function($v) { return $v !== '' && $v !== null; });
    return http_build_query($clean);
}
$baseParams = [
    'date_from' => $filterDateFrom,
    'date_to'   => $filterDateTo,
    'user_id'   => $filterUserId,
    'aksi'      => $filterAksi,
    'search'    => $filterSearch,
];

$userInitial = strtoupper(substr($_SESSION['nama'] ?? 'S', 0, 1));

// Build a lookup of user IDs to roles
$userRoleLookup = [];
foreach ($allUsers as $u) {
    $userRoleLookup[(int)$u['id']] = $u['role'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Log Aktivitas Sistem - Audit trail untuk Super Admin SIMPEL-CAMP">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css?v=<?= time() ?>">
    <style>
        /* ============================================================
           LOG AKTIVITAS — Audit Log Premium Styles
           ============================================================ */

        /* --- Page Header --- */
        .log-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .log-page-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .log-page-header h1 i {
            color: #D4A373;
        }

        .log-page-header .log-stats {
            display: flex;
            gap: 1rem;
        }

        .log-stat-pill {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .log-stat-pill i {
            font-size: 0.85rem;
        }

        /* --- Filter Bar --- */
        .log-filter-bar {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .log-filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            min-width: 160px;
        }

        .log-filter-group label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
        }

        .log-filter-group input,
        .log-filter-group select {
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg-body);
            color: var(--text-primary);
            transition: border-color 0.2s ease;
        }

        .log-filter-group input:focus,
        .log-filter-group select:focus {
            outline: none;
            border-color: #D4A373;
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.1);
        }

        .log-filter-actions {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .btn-filter {
            background: linear-gradient(135deg, #1B4332, #2D6A4F);
            color: #fff;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-filter:hover {
            background: linear-gradient(135deg, #2D6A4F, #40916C);
            transform: translateY(-1px);
        }

        .btn-filter-reset {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-filter-reset:hover {
            background: var(--bg-body);
            color: var(--text-primary);
        }

        /* --- Log Table Card --- */
        .log-table-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .log-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .log-table-header h5 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            margin: 0;
        }

        .btn-export {
            background: linear-gradient(135deg, #D4A373, #E9C46A);
            color: #081C15;
            border: none;
            padding: 0.45rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-export:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(212, 163, 115, 0.3);
        }

        /* --- Log Table --- */
        .log-table {
            width: 100%;
            font-size: 0.82rem;
        }

        .log-table thead th {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-secondary);
            padding: 0.85rem 1rem;
            border-bottom: 2px solid var(--border);
            background: rgba(248, 250, 247, 0.5);
            white-space: nowrap;
        }

        .log-table tbody td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .log-table tbody tr:last-child td {
            border-bottom: none;
        }

        .log-table tbody tr {
            transition: background 0.15s ease;
        }

        .log-table tbody tr:hover {
            background: rgba(27, 67, 50, 0.03);
        }

        /* --- Action Badges --- */
        .log-action-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.02em;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .log-action-badge.action-create {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .log-action-badge.action-update {
            background: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .log-action-badge.action-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .log-action-badge.action-auth {
            background: rgba(107, 114, 128, 0.1);
            color: #6B7280;
            border: 1px solid rgba(107, 114, 128, 0.2);
        }

        .log-action-badge.action-export {
            background: rgba(212, 163, 115, 0.1);
            color: #D4A373;
            border: 1px solid rgba(212, 163, 115, 0.2);
        }

        .log-action-badge.action-setting {
            background: rgba(139, 92, 246, 0.1);
            color: #8B5CF6;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        /* --- Role Badge --- */
        .log-role-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: 0.03em;
        }

        .role-superadmin {
            background: linear-gradient(135deg, rgba(212, 163, 115, 0.15), rgba(233, 196, 106, 0.15));
            color: #D4A373;
            border: 1px solid rgba(212, 163, 115, 0.3);
        }

        .role-admin {
            background: rgba(45, 106, 79, 0.1);
            color: #2D6A4F;
            border: 1px solid rgba(45, 106, 79, 0.2);
        }

        .role-pelanggan {
            background: rgba(59, 130, 246, 0.08);
            color: #3B82F6;
            border: 1px solid rgba(59, 130, 246, 0.15);
        }

        /* --- Timestamp --- */
        .log-timestamp {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-secondary);
            white-space: nowrap;
        }

        .log-detail {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.8rem;
            color: var(--text-primary);
        }

        .log-ip {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            color: var(--text-secondary);
            background: rgba(107, 114, 128, 0.06);
            padding: 2px 8px;
            border-radius: 4px;
        }

        /* --- Pagination --- */
        .log-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
        }

        .log-pagination .page-info {
            font-size: 0.78rem;
            color: var(--text-secondary);
        }

        .log-pagination .page-nav {
            display: flex;
            gap: 4px;
        }

        .log-pagination .page-btn {
            width: 34px;
            height: 34px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--bg-card);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .log-pagination .page-btn:hover {
            border-color: #D4A373;
            color: #D4A373;
        }

        .log-pagination .page-btn.active {
            background: linear-gradient(135deg, #1B4332, #2D6A4F);
            color: #fff;
            border-color: transparent;
        }

        .log-pagination .page-btn.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        /* --- Responsive --- */
        @media (max-width: 991.98px) {
            .log-filter-bar {
                flex-direction: column;
            }
            .log-filter-group {
                min-width: 100%;
            }
        }

        @media (max-width: 767.98px) {
            .log-page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .log-page-header h1 {
                font-size: 1.3rem;
            }
        }
    
/* Stagger Animation */
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.stagger-item{opacity:0;animation:fadeInUp .5s ease forwards}
    </style>
</head>
<body>
<div class="superadmin-wrapper">
    <?php include dirname(__DIR__, 2) . '/includes/sidebar_superadmin.php'; ?>
    <div class="superadmin-main">
        <?php $_header_role = 'superadmin'; include dirname(__DIR__, 2) . '/includes/header_glass.php'; ?>


        <!-- Content -->
        <div class="admin-content" style="padding:1.5rem;">

            <!-- Page Header -->
            <div class="log-page-header stagger-item">
                <div>
                    <h1><i class="bi bi-journal-text"></i> Log Aktivitas Sistem</h1>
                    <p class="text-secondary mb-0" style="font-size:0.85rem;">Audit trail semua aktivitas dalam sistem</p>
                </div>
                <div class="log-stats">
                    <div class="log-stat-pill">
                        <i class="bi bi-activity" style="color:#10B981;"></i>
                        <span><?= number_format($totalLogs) ?> total</span>
                    </div>
                    <div class="log-stat-pill">
                        <i class="bi bi-clock" style="color:#D4A373;"></i>
                        <span><?= number_format($todayCount) ?> hari ini</span>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" action="">
            <div class="log-filter-bar stagger-item">
                <div class="log-filter-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($filterDateFrom) ?>">
                </div>
                <div class="log-filter-group">
                    <label>Tanggal Akhir</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($filterDateTo) ?>">
                </div>
                <div class="log-filter-group">
                    <label>User</label>
                    <select name="user_id">
                        <option value="">Semua User</option>
                        <?php foreach ($allUsers as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= ($filterUserId == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="log-filter-group">
                    <label>Tipe Aksi</label>
                    <select name="aksi">
                        <option value="">Semua Aksi</option>
                        <option value="login" <?= $filterAksi === 'login' ? 'selected' : '' ?>>Login</option>
                        <option value="logout" <?= $filterAksi === 'logout' ? 'selected' : '' ?>>Logout</option>
                        <option value="create" <?= $filterAksi === 'create' ? 'selected' : '' ?>>Create</option>
                        <option value="update" <?= $filterAksi === 'update' ? 'selected' : '' ?>>Update</option>
                        <option value="delete" <?= $filterAksi === 'delete' ? 'selected' : '' ?>>Delete</option>
                        <option value="export" <?= $filterAksi === 'export' ? 'selected' : '' ?>>Export</option>
                        <option value="setting" <?= $filterAksi === 'setting' ? 'selected' : '' ?>>Settings</option>
                    </select>
                </div>
                <div class="log-filter-actions">
                    <button type="submit" class="btn-filter"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="<?= BASE_URL ?>/pages/superadmin/log_aktivitas.php" class="btn-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
            </form>

            <!-- Log Table -->
            <div class="log-table-card stagger-item">
                <div class="log-table-header">
                    <h5>Hasil Log Aktivitas</h5>
                    <button class="btn-export"><i class="bi bi-download"></i> Export CSV</button>
                </div>
                <div class="table-responsive">
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Aksi</th>
                                <th>Detail</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">Tidak ada log ditemukan.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($logs as $log):
                                $badge = getActionBadge($log['aksi'] ?? '');
                                $userId = $log['user_id'] ? (int)$log['user_id'] : null;
                                $userRole = $userId && isset($userRoleLookup[$userId]) ? $userRoleLookup[$userId] : 'pelanggan';
                                $roleClass = 'role-' . $userRole;
                                $roleName = ucfirst($userRole);
                                if ($userRole === 'superadmin') $roleName = 'Super Admin';
                            ?>
                            <tr>
                                <td class="log-timestamp"><?= date('d M Y', strtotime($log['created_at'])) ?><br><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
                                <td class="fw-medium"><?= htmlspecialchars($log['nama_user'] ?? 'System') ?></td>
                                <td><span class="log-role-badge <?= $roleClass ?>"><?= $roleName ?></span></td>
                                <td><span class="log-action-badge <?= $badge[0] ?>"><i class="bi <?= $badge[1] ?>"></i> <?= htmlspecialchars($log['aksi'] ?? '') ?></span></td>
                                <td class="log-detail" title="<?= htmlspecialchars($log['detail'] ?? '') ?>"><?= htmlspecialchars($log['detail'] ?? '—') ?></td>
                                <td><span class="log-ip"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="log-pagination">
                    <div class="page-info">
                        Menampilkan <strong><?= $totalLogs > 0 ? $offset + 1 : 0 ?>-<?= min($offset + $perPage, $totalLogs) ?></strong> dari <strong><?= number_format($totalLogs) ?></strong> log
                    </div>
                    <div class="page-nav">
                        <?php if ($page <= 1): ?>
                        <span class="page-btn disabled"><i class="bi bi-chevron-left"></i></span>
                        <?php else: ?>
                        <a href="?<?= buildQueryString($baseParams, ['page' => $page - 1]) ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a>
                        <?php endif; ?>

                        <?php
                        // Show page numbers
                        $startP = max(1, $page - 2);
                        $endP = min($totalPages, $page + 2);
                        if ($startP > 1): ?>
                        <a href="?<?= buildQueryString($baseParams, ['page' => 1]) ?>" class="page-btn">1</a>
                        <?php if ($startP > 2): ?><span class="page-btn disabled">...</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($p = $startP; $p <= $endP; $p++): ?>
                        <a href="?<?= buildQueryString($baseParams, ['page' => $p]) ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                        <?php endfor; ?>

                        <?php if ($endP < $totalPages): ?>
                        <?php if ($endP < $totalPages - 1): ?><span class="page-btn disabled">...</span><?php endif; ?>
                        <a href="?<?= buildQueryString($baseParams, ['page' => $totalPages]) ?>" class="page-btn"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($page >= $totalPages): ?>
                        <span class="page-btn disabled"><i class="bi bi-chevron-right"></i></span>
                        <?php else: ?>
                        <a href="?<?= buildQueryString($baseParams, ['page' => $page + 1]) ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.stagger-item').forEach(function(item, i){
        item.style.animationDelay = (i * 0.08) + 's';
    });
});
</script>
</body>
</html>
