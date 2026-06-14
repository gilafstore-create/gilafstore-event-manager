<?php
/**
 * Event Bus - Delivery Tracker
 * READ-ONLY. em_delivery_monitoring: id, event_log_id, destination_id, status, attempts,
 *            last_attempt_at, delivered_at, error_message, created_at
 * Joins em_event_logs (event_type) and em_event_destinations (name)
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Delivery Tracker — Event Manager';
$currentPage = 'delivery-tracker';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterDest   = $_GET['dest'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "dm.status = ?";         $params[] = $filterStatus; }
if ($filterDest)   { $where[] = "dm.destination_id = ?"; $params[] = (int)$filterDest; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total     = em_fetch("SELECT COUNT(*) as count FROM em_delivery_monitoring dm $whereClause", $params)['count'] ?? 0;
$deliveries = em_fetch_all("SELECT dm.*, el.event_type, d.name as dest_name
    FROM em_delivery_monitoring dm
    LEFT JOIN em_event_logs el ON dm.event_log_id = el.id
    LEFT JOIN em_event_destinations d ON dm.destination_id = d.id
    $whereClause ORDER BY dm.created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$deliveredCount = em_fetch("SELECT COUNT(*) as count FROM em_delivery_monitoring WHERE status='delivered'")['count'] ?? 0;
$failedCount    = em_fetch("SELECT COUNT(*) as count FROM em_delivery_monitoring WHERE status='failed'")['count'] ?? 0;
$pendingCount   = em_fetch("SELECT COUNT(*) as count FROM em_delivery_monitoring WHERE status='pending'")['count'] ?? 0;
$avgAttempts    = em_fetch("SELECT ROUND(AVG(attempts),1) as avg FROM em_delivery_monitoring")['avg'] ?? 0;
$successRate    = $total > 0 ? round(($deliveredCount / $total) * 100) : 0;
$destinations   = em_fetch_all("SELECT DISTINCT dm.destination_id, d.name FROM em_delivery_monitoring dm LEFT JOIN em_event_destinations d ON dm.destination_id=d.id ORDER BY d.name");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Delivery Tracker</h2>
    <p class="text-muted">Track real-time event delivery status to all destinations</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-paper-plane"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Deliveries</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-double"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Delivered</div><div class="em-stat-value"><?= em_format_number($deliveredCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-times-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Failed</div><div class="em-stat-value"><?= em_format_number($failedCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon <?= $successRate >= 95 ? 'success' : ($successRate >= 80 ? 'warning' : 'danger') ?>"><i class="fas fa-percentage"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Success Rate</div><div class="em-stat-value"><?= $successRate ?>%</div></div>
    </div>
</div>

<?php if ($failedCount > 0): ?>
<div class="alert alert-danger mb-4 d-flex align-items-center gap-2">
    <i class="fas fa-exclamation-triangle fa-lg"></i>
    <div><strong><?= em_format_number($failedCount) ?></strong> delivery failures require attention. <a href="?status=failed" class="alert-link">View failures →</a></div>
</div>
<?php endif; ?>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','delivered','failed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <select name="dest" class="form-select">
                    <option value="">All Destinations</option>
                    <?php foreach ($destinations as $d): ?>
                        <option value="<?= (int)$d['destination_id'] ?>" <?= $filterDest==$d['destination_id']?'selected':'' ?>><?= htmlspecialchars($d['name'] ?? 'Dest #'.$d['destination_id']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="delivery-tracker.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Deliveries <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5>
        <small class="text-muted">Avg attempts: <strong><?= $avgAttempts ?></strong></small>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($deliveries)): ?>
            <div class="em-empty-state"><i class="fas fa-paper-plane"></i><h4>No Deliveries Found</h4><p>No delivery records match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Event Type</th><th>Destination</th><th>Status</th><th>Attempts</th><th>Last Attempt</th><th>Delivered At</th><th>Error</th></tr></thead>
                    <tbody>
                        <?php foreach ($deliveries as $d): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($d['event_type'] ?? 'unknown') ?></span></td>
                                <td><?= htmlspecialchars($d['dest_name'] ?? 'Dest #'.(int)$d['destination_id']) ?></td>
                                <td><?= em_status_badge($d['status']) ?></td>
                                <td><span class="badge bg-<?= $d['attempts'] >= 3 ? 'danger' : 'secondary' ?>"><?= (int)$d['attempts'] ?></span></td>
                                <td><small class="text-muted"><?= $d['last_attempt_at'] ? em_time_ago($d['last_attempt_at']) : '—' ?></small></td>
                                <td><small class="text-<?= $d['delivered_at'] ? 'success' : 'muted' ?>"><?= $d['delivered_at'] ? em_time_ago($d['delivered_at']) : '—' ?></small></td>
                                <td><small class="text-danger"><?= htmlspecialchars(substr($d['error_message'] ?? '', 0, 60)) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'delivery-tracker.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
