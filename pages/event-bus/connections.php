<?php
/**
 * Event Bus - Connections
 * READ-ONLY. em_event_connections: id, source_id, destination_id, config, status, created_by, created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Connections — Event Manager';
$currentPage = 'connections';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "ec.status = ?"; $params[] = $filterStatus; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total       = em_fetch("SELECT COUNT(*) as count FROM em_event_connections ec $whereClause", $params)['count'] ?? 0;
$connections = em_fetch_all("SELECT ec.*, s.name as source_name, d.name as dest_name
    FROM em_event_connections ec
    LEFT JOIN em_event_sources s ON ec.source_id = s.id
    LEFT JOIN em_event_destinations d ON ec.destination_id = d.id
    $whereClause ORDER BY ec.created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination  = em_paginate($total, $perPage, $page);

$activeCount   = em_fetch("SELECT COUNT(*) as count FROM em_event_connections WHERE status='active'")['count'] ?? 0;
$inactiveCount = em_fetch("SELECT COUNT(*) as count FROM em_event_connections WHERE status='inactive'")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Connections</h2>
    <p class="text-muted">View event source and destination connection configurations</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-plug"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Connections</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-link"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Active</div><div class="em-stat-value"><?= em_format_number($activeCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon secondary"><i class="fas fa-unlink"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Inactive</div><div class="em-stat-value"><?= em_format_number($inactiveCount) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $filterStatus==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $filterStatus==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="connections.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Connections <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($connections)): ?>
            <div class="em-empty-state"><i class="fas fa-plug"></i><h4>No Connections Found</h4><p>No event connections match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Source</th><th>Destination</th><th>Status</th><th>Created</th><th>Updated</th></tr></thead>
                    <tbody>
                        <?php foreach ($connections as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['source_name'] ?? 'Source #'.(int)$c['source_id']) ?></td>
                                <td><?= htmlspecialchars($c['dest_name'] ?? 'Dest #'.(int)$c['destination_id']) ?></td>
                                <td><?= em_status_badge($c['status']) ?></td>
                                <td><small class="text-muted"><?= em_time_ago($c['created_at']) ?></small></td>
                                <td><small class="text-muted"><?= em_time_ago($c['updated_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'connections.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
