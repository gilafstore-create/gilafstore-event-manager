<?php
/**
 * Event Operations - Rate Limits
 * READ-ONLY. em_rate_limits: id, entity_type, entity_id, limit_per_minute,
 *            limit_per_hour, limit_per_day, created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Rate Limits — Event Manager';
$currentPage = 'rate-limits';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterType = $_GET['type'] ?? '';
$where = []; $params = [];
if ($filterType) { $where[] = "entity_type = ?"; $params[] = $filterType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_rate_limits $whereClause", $params)['count'] ?? 0;
$rateLimits = em_fetch_all("SELECT * FROM em_rate_limits $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$entityTypes = em_fetch_all("SELECT DISTINCT entity_type FROM em_rate_limits ORDER BY entity_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Rate Limits</h2>
    <p class="text-muted">View configured rate limits for event sources and destinations</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-tachometer-alt"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Rules</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-layer-group"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Entity Types</div><div class="em-stat-value"><?= count($entityTypes) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <select name="type" class="form-select">
                    <option value="">All Entity Types</option>
                    <?php foreach ($entityTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['entity_type']) ?>" <?= $filterType===$t['entity_type']?'selected':'' ?>><?= htmlspecialchars($t['entity_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="rate-limits.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Rate Limit Rules <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($rateLimits)): ?>
            <div class="em-empty-state"><i class="fas fa-tachometer-alt"></i><h4>No Rate Limits Configured</h4><p>No rate limit rules have been defined yet.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr><th>Entity Type</th><th>Entity ID</th><th>Per Minute</th><th>Per Hour</th><th>Per Day</th><th>Created</th><th>Updated</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rateLimits as $r): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($r['entity_type']) ?></span></td>
                                <td><strong>#<?= (int)$r['entity_id'] ?></strong></td>
                                <td><span class="badge bg-info"><?= em_format_number($r['limit_per_minute']) ?></span></td>
                                <td><span class="badge bg-warning text-dark"><?= em_format_number($r['limit_per_hour']) ?></span></td>
                                <td><span class="badge bg-secondary"><?= em_format_number($r['limit_per_day']) ?></span></td>
                                <td><small class="text-muted"><?= em_time_ago($r['created_at']) ?></small></td>
                                <td><small class="text-muted"><?= em_time_ago($r['updated_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'rate-limits.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
