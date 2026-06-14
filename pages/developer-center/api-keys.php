<?php
/**
 * Developer Center - API Keys
 * READ-ONLY. em_api_keys: id, name, api_key, permissions, status, created_by, last_used_at, created_at, revoked_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'API Keys — Event Manager';
$currentPage = 'api-keys';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?"; $params[] = $filterStatus; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_api_keys $whereClause", $params)['count'] ?? 0;
$keys       = em_fetch_all("SELECT * FROM em_api_keys $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$activeCount  = em_fetch("SELECT COUNT(*) as count FROM em_api_keys WHERE status='active'")['count'] ?? 0;
$revokedCount = em_fetch("SELECT COUNT(*) as count FROM em_api_keys WHERE status='revoked'")['count'] ?? 0;
$recentCount  = em_fetch("SELECT COUNT(*) as count FROM em_api_keys WHERE last_used_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>API Keys</h2>
    <p class="text-muted">View all API keys and their access permissions</p>
</div>

<div class="em-card mb-3 border-warning">
    <div class="em-card-body py-2">
        <i class="fas fa-lock text-warning me-2"></i>
        <strong>Security Notice:</strong> Full API key values are never displayed. Only key prefixes are shown for identification.
    </div>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-key"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Keys</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Active</div><div class="em-stat-value"><?= em_format_number($activeCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-ban"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Revoked</div><div class="em-stat-value"><?= em_format_number($revokedCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-history"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Used (7 days)</div><div class="em-stat-value"><?= em_format_number($recentCount) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $filterStatus==='active'?'selected':'' ?>>Active</option>
                    <option value="revoked" <?= $filterStatus==='revoked'?'selected':'' ?>>Revoked</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="api-keys.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">API Keys <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($keys)): ?>
            <div class="em-empty-state"><i class="fas fa-key"></i><h4>No API Keys Found</h4><p>No API keys match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Name</th><th>API Key (masked)</th><th>Permissions</th><th>Status</th><th>Revoked At</th><th>Last Used</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($keys as $k): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($k['name']) ?></strong></td>
                                <td><code><?= htmlspecialchars(substr($k['api_key'], 0, 8)) ?>••••••••</code></td>
                                <td>
                                    <?php
                                    $perms = is_string($k['permissions']) ? json_decode($k['permissions'], true) : $k['permissions'];
                                    if (is_array($perms)) {
                                        foreach (array_slice($perms, 0, 3) as $p) {
                                            echo '<span class="badge bg-info me-1">' . htmlspecialchars($p) . '</span>';
                                        }
                                        if (count($perms) > 3) {
                                            echo '<span class="badge bg-secondary">+' . (count($perms) - 3) . '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">—</span>';
                                    }
                                    ?>
                                </td>
                                <td><?= em_status_badge($k['status']) ?></td>
                                <td><small class="text-muted"><?= $k['revoked_at'] ? em_time_ago($k['revoked_at']) : '—' ?></small></td>
                                <td><small class="text-muted"><?= $k['last_used_at'] ? em_time_ago($k['last_used_at']) : 'Never' ?></small></td>
                                <td><small class="text-muted"><?= em_time_ago($k['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'api-keys.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
