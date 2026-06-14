<?php
/**
 * Event Operations - Delivery Monitoring
 * READ-ONLY. em_delivery_monitoring: id, event_log_id, destination_id, status,
 *            attempts, last_attempt_at, delivered_at, error_message, created_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Delivery Monitoring — Event Manager';
$currentPage = 'delivery-monitoring';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterDest   = $_GET['dest'] ?? '';

$where = ['1=1']; $params = [];
if ($filterStatus) { $where[] = "dm.status = ?";        $params[] = $filterStatus; }
if ($filterDest)   { $where[] = "dm.destination_id = ?"; $params[] = (int)$filterDest; }
$whereClause = 'WHERE ' . implode(' AND ', $where);

$total = em_fetch("SELECT COUNT(*) as count FROM em_delivery_monitoring dm $whereClause", $params)['count'] ?? 0;
$sql   = "SELECT dm.*, el.event_type, el.event_id, d.name as destination_name
          FROM em_delivery_monitoring dm
          LEFT JOIN em_event_logs el ON dm.event_log_id = el.id
          LEFT JOIN em_event_destinations d ON dm.destination_id = d.id
          $whereClause ORDER BY dm.created_at DESC LIMIT {$offset}, {$perPage}";
$records    = em_fetch_all($sql, $params);
$pagination = em_paginate($total, $perPage, $page);

$pendingCount   = em_fetch("SELECT COUNT(*) as count FROM em_delivery_monitoring WHERE status='pending'")['count'] ?? 0;
$deliveredCount = em_fetch("SELECT COUNT(*) as count FROM em_delivery_monitoring WHERE status='delivered'")['count'] ?? 0;
$failedCount    = em_fetch("SELECT COUNT(*) as count FROM em_delivery_monitoring WHERE status='failed'")['count'] ?? 0;
$avgAttempts    = em_fetch("SELECT ROUND(AVG(attempts),1) as avg FROM em_delivery_monitoring")['avg'] ?? 0;
$destinations   = em_fetch_all("SELECT id, name FROM em_event_destinations ORDER BY name");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Delivery Monitoring</h2>
    <p class="text-muted">Track event delivery status to all destinations</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-paper-plane"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Deliveries</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Delivered</div><div class="em-stat-value"><?= em_format_number($deliveredCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-times-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Failed</div><div class="em-stat-value"><?= em_format_number($failedCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-chart-bar"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Avg Attempts</div><div class="em-stat-value"><?= $avgAttempts ?></div></div>
    </div>
</div>

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
                        <option value="<?= $d['id'] ?>" <?= $filterDest==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="delivery-monitoring.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Delivery Records <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($records)): ?>
            <div class="em-empty-state"><i class="fas fa-paper-plane"></i><h4>No Delivery Records</h4><p>No delivery records match your filters.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr><th>Event</th><th>Destination</th><th>Status</th><th>Attempts</th><th>Last Attempt</th><th>Delivered At</th><th>Error</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($r['event_type'] ?? 'Unknown') ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($r['event_id'] ?? '—') ?></small>
                                </td>
                                <td><?= htmlspecialchars($r['destination_name'] ?? '#'.(int)$r['destination_id']) ?></td>
                                <td><?= em_status_badge($r['status']) ?></td>
                                <td><span class="badge bg-secondary"><?= (int)$r['attempts'] ?></span></td>
                                <td><small class="text-muted"><?= $r['last_attempt_at'] ? em_time_ago($r['last_attempt_at']) : '—' ?></small></td>
                                <td><small class="text-muted"><?= $r['delivered_at'] ? em_time_ago($r['delivered_at']) : '—' ?></small></td>
                                <td>
                                    <?php if ($r['error_message']): ?>
                                        <small class="text-danger"><?= htmlspecialchars(substr($r['error_message'], 0, 50)) ?>...</small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'delivery-monitoring.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
