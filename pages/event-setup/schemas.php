<?php
/**
 * Event Setup - Event Schemas
 * READ-ONLY. em_event_schemas: id, name, version, schema, status, created_by, created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Schemas — Event Manager';
$currentPage = 'schemas';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterName   = $_GET['name'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?"; $params[] = $filterStatus; }
if ($filterName)   { $where[] = "name LIKE ?"; $params[] = "%{$filterName}%"; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total   = em_fetch("SELECT COUNT(*) as count FROM em_event_schemas $whereClause", $params)['count'] ?? 0;
$sql     = "SELECT * FROM em_event_schemas $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}";
$schemas = em_fetch_all($sql, $params);
$pagination = em_paginate($total, $perPage, $page);

$activeCount   = em_fetch("SELECT COUNT(*) as count FROM em_event_schemas WHERE status='active'")['count'] ?? 0;
$inactiveCount = em_fetch("SELECT COUNT(*) as count FROM em_event_schemas WHERE status='inactive'")['count'] ?? 0;
$versions      = em_fetch_all("SELECT DISTINCT version FROM em_event_schemas ORDER BY version DESC");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Schemas</h2>
    <p class="text-muted">Schema registry for all event type definitions</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-file-code"></i></div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Schemas</div>
            <div class="em-stat-value"><?= em_format_number($total) ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="em-stat-info">
            <div class="em-stat-label">Active</div>
            <div class="em-stat-value"><?= em_format_number($activeCount) ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon secondary"><i class="fas fa-pause-circle"></i></div>
        <div class="em-stat-info">
            <div class="em-stat-label">Inactive</div>
            <div class="em-stat-value"><?= em_format_number($inactiveCount) ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-code-branch"></i></div>
        <div class="em-stat-info">
            <div class="em-stat-label">Versions</div>
            <div class="em-stat-value"><?= count($versions) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="name" class="form-control" placeholder="Search schema name..." value="<?= htmlspecialchars($filterName) ?>">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $filterStatus==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $filterStatus==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="schemas.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="em-card">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Schemas <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($schemas)): ?>
            <div class="em-empty-state">
                <i class="fas fa-file-code"></i>
                <h4>No Schemas Found</h4>
                <p>No event schemas match your criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schemas as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($s['version']) ?></span></td>
                                <td><?= em_status_badge($s['status']) ?></td>
                                <td><?= $s['created_by'] ? '#'.(int)$s['created_by'] : '<span class="text-muted">—</span>' ?></td>
                                <td><small class="text-muted"><?= em_time_ago($s['created_at']) ?></small></td>
                                <td><small class="text-muted"><?= em_time_ago($s['updated_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'schemas.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
