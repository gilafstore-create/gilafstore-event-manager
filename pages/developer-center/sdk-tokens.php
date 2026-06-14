<?php
/**
 * Developer Center - SDK Tokens
 * READ-ONLY. em_sdk_tokens: id, name, token, sdk_type, status, created_by, created_at, revoked_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'SDK Tokens — Event Manager';
$currentPage = 'sdk-tokens';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus  = $_GET['status'] ?? '';
$filterSdkType = $_GET['sdk_type'] ?? '';

$where = []; $params = [];
if ($filterStatus)  { $where[] = "status = ?";   $params[] = $filterStatus; }
if ($filterSdkType) { $where[] = "sdk_type = ?"; $params[] = $filterSdkType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_sdk_tokens $whereClause", $params)['count'] ?? 0;
$tokens     = em_fetch_all("SELECT * FROM em_sdk_tokens $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$activeCount  = em_fetch("SELECT COUNT(*) as count FROM em_sdk_tokens WHERE status='active'")['count'] ?? 0;
$revokedCount = em_fetch("SELECT COUNT(*) as count FROM em_sdk_tokens WHERE status='revoked'")['count'] ?? 0;
$sdkTypes     = em_fetch_all("SELECT DISTINCT sdk_type FROM em_sdk_tokens WHERE sdk_type IS NOT NULL ORDER BY sdk_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>SDK Tokens</h2>
    <p class="text-muted">Manage SDK authentication tokens for platform integrations</p>
</div>

<div class="em-card mb-3 border-warning">
    <div class="em-card-body py-2">
        <i class="fas fa-lock text-warning me-2"></i>
        <strong>Security Notice:</strong> Full token values are never displayed. Only token prefixes are shown for identification.
    </div>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-mobile-alt"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Tokens</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
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
        <div class="em-stat-icon info"><i class="fas fa-layer-group"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">SDK Types</div><div class="em-stat-value"><?= count($sdkTypes) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $filterStatus==='active'?'selected':'' ?>>Active</option>
                    <option value="revoked" <?= $filterStatus==='revoked'?'selected':'' ?>>Revoked</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="sdk_type" class="form-select">
                    <option value="">All SDK Types</option>
                    <?php foreach ($sdkTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['sdk_type']) ?>" <?= $filterSdkType===$t['sdk_type']?'selected':'' ?>><?= htmlspecialchars($t['sdk_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="sdk-tokens.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">SDK Tokens <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($tokens)): ?>
            <div class="em-empty-state"><i class="fas fa-mobile-alt"></i><h4>No Tokens Found</h4><p>No SDK tokens match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Name</th><th>Token (masked)</th><th>SDK Type</th><th>Status</th><th>Revoked At</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($tokens as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                                <td><code><?= htmlspecialchars(substr($t['token'], 0, 8)) ?>••••••••</code></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($t['sdk_type'] ?? '—') ?></span></td>
                                <td><?= em_status_badge($t['status']) ?></td>
                                <td><small class="text-muted"><?= $t['revoked_at'] ? em_time_ago($t['revoked_at']) : '—' ?></small></td>
                                <td><small class="text-muted"><?= em_time_ago($t['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'sdk-tokens.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
