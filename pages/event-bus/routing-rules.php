<?php
/**
 * Event Bus - Routing Rules
 * READ-ONLY. em_routing_rules: id, name, event_type, destination_id, conditions, priority, status, created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Routing Rules — Event Manager';
$currentPage = 'routing-rules';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "rr.status = ?";     $params[] = $filterStatus; }
if ($filterType)   { $where[] = "rr.event_type = ?"; $params[] = $filterType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = em_fetch("SELECT COUNT(*) as count FROM em_routing_rules rr $whereClause", $params)['count'] ?? 0;
$rules = em_fetch_all("SELECT rr.*, d.name as dest_name FROM em_routing_rules rr
    LEFT JOIN em_event_destinations d ON rr.destination_id = d.id
    $whereClause ORDER BY rr.priority ASC, rr.created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$activeCount   = em_fetch("SELECT COUNT(*) as count FROM em_routing_rules WHERE status='active'")['count'] ?? 0;
$inactiveCount = em_fetch("SELECT COUNT(*) as count FROM em_routing_rules WHERE status='inactive'")['count'] ?? 0;
$eventTypes    = em_fetch_all("SELECT DISTINCT event_type FROM em_routing_rules ORDER BY event_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Routing Rules</h2>
    <p class="text-muted">View event routing rules and destination mappings</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-directions"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Rules</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-toggle-on"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Active</div><div class="em-stat-value"><?= em_format_number($activeCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon secondary"><i class="fas fa-toggle-off"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Inactive</div><div class="em-stat-value"><?= em_format_number($inactiveCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-tags"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Event Types</div><div class="em-stat-value"><?= count($eventTypes) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $filterStatus==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $filterStatus==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-5">
                <select name="type" class="form-select">
                    <option value="">All Event Types</option>
                    <?php foreach ($eventTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['event_type']) ?>" <?= $filterType===$t['event_type']?'selected':'' ?>><?= htmlspecialchars($t['event_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="routing-rules.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Routing Rules <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($rules)): ?>
            <div class="em-empty-state"><i class="fas fa-directions"></i><h4>No Routing Rules</h4><p>No routing rules have been configured.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Priority</th><th>Name</th><th>Event Type</th><th>Destination</th><th>Status</th><th>Updated</th></tr></thead>
                    <tbody>
                        <?php foreach ($rules as $r): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= (int)$r['priority'] ?></span></td>
                                <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($r['event_type']) ?></span></td>
                                <td><?= htmlspecialchars($r['dest_name'] ?? '#'.(int)$r['destination_id']) ?></td>
                                <td><?= em_status_badge($r['status']) ?></td>
                                <td><small class="text-muted"><?= em_time_ago($r['updated_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'routing-rules.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
