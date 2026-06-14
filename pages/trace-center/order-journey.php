<?php
/**
 * Trace Center - Order Journey
 * READ-ONLY. Traces order-related events from em_event_logs filtered by order event types.
 * em_event_logs: id, event_id, event_type, source_id, payload, status, error_message, created_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Order Journey — Event Manager';
$currentPage = 'order-journey';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';
$filterSearch = trim($_GET['search'] ?? '');

$where   = ["(event_type LIKE '%order%' OR event_type LIKE '%purchase%' OR event_type LIKE '%checkout%' OR event_type LIKE '%payment%' OR event_type LIKE '%shipment%' OR event_type LIKE '%delivery%' OR event_type LIKE '%refund%' OR event_type LIKE '%cancel%')"];
$params  = [];

if ($filterStatus) { $where[] = "status = ?";           $params[] = $filterStatus; }
if ($filterType)   { $where[] = "event_type LIKE ?";    $params[] = '%' . $filterType . '%'; }
if ($filterSearch) { $where[] = "event_id LIKE ?";      $params[] = '%' . $filterSearch . '%'; }

$whereClause = 'WHERE ' . implode(' AND ', $where);

$total      = em_fetch("SELECT COUNT(*) as count FROM em_event_logs $whereClause", $params)['count'] ?? 0;
$events     = em_fetch_all("SELECT * FROM em_event_logs $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$successCount = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE (event_type LIKE '%order%' OR event_type LIKE '%purchase%' OR event_type LIKE '%checkout%' OR event_type LIKE '%payment%' OR event_type LIKE '%shipment%' OR event_type LIKE '%delivery%' OR event_type LIKE '%refund%' OR event_type LIKE '%cancel%') AND status='success'")['count'] ?? 0;
$failedCount  = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE (event_type LIKE '%order%' OR event_type LIKE '%purchase%' OR event_type LIKE '%checkout%' OR event_type LIKE '%payment%' OR event_type LIKE '%shipment%' OR event_type LIKE '%delivery%' OR event_type LIKE '%refund%' OR event_type LIKE '%cancel%') AND status='failed'")['count'] ?? 0;
$todayCount   = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE (event_type LIKE '%order%' OR event_type LIKE '%purchase%' OR event_type LIKE '%checkout%' OR event_type LIKE '%payment%' OR event_type LIKE '%shipment%' OR event_type LIKE '%delivery%' OR event_type LIKE '%refund%' OR event_type LIKE '%cancel%') AND DATE(created_at)=CURDATE()")['count'] ?? 0;

$orderTypes = em_fetch_all("SELECT DISTINCT event_type FROM em_event_logs WHERE (event_type LIKE '%order%' OR event_type LIKE '%purchase%' OR event_type LIKE '%checkout%' OR event_type LIKE '%payment%' OR event_type LIKE '%shipment%' OR event_type LIKE '%delivery%' OR event_type LIKE '%refund%' OR event_type LIKE '%cancel%') ORDER BY event_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Order Journey</h2>
    <p class="text-muted">Trace all order-related events across the customer purchase lifecycle</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-shopping-cart"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Order Events</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Successful</div><div class="em-stat-value"><?= em_format_number($successCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-times-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Failed</div><div class="em-stat-value"><?= em_format_number($failedCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-calendar-day"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Today</div><div class="em-stat-value"><?= em_format_number($todayCount) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['success','failed','pending','processing'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Event Types</option>
                    <?php foreach ($orderTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['event_type']) ?>" <?= $filterType===$t['event_type']?'selected':'' ?>><?= htmlspecialchars($t['event_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by Event ID…" value="<?= htmlspecialchars($filterSearch) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="order-journey.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Journey Stage Legend -->
<div class="em-card mb-4">
    <div class="em-card-body py-2">
        <small class="text-muted me-3"><strong>Order lifecycle stages:</strong></small>
        <?php foreach ([
            'checkout' => 'primary', 'payment' => 'success', 'order' => 'info',
            'shipment' => 'warning', 'delivery' => 'success', 'refund' => 'danger', 'cancel' => 'secondary'
        ] as $stage => $color): ?>
            <span class="badge bg-<?= $color ?> me-1"><?= ucfirst($stage) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Order Events <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($events)): ?>
            <div class="em-empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h4>No Order Events Found</h4>
                <p class="text-muted">No order-related events match your criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Event ID</th><th>Event Type</th><th>Stage</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php foreach ($events as $e):
                            $type = strtolower($e['event_type']);
                            $stageColor = 'secondary';
                            if (str_contains($type,'checkout'))  $stageColor = 'primary';
                            elseif (str_contains($type,'payment'))   $stageColor = 'success';
                            elseif (str_contains($type,'shipment'))  $stageColor = 'warning';
                            elseif (str_contains($type,'delivery'))  $stageColor = 'success';
                            elseif (str_contains($type,'refund'))    $stageColor = 'danger';
                            elseif (str_contains($type,'cancel'))    $stageColor = 'dark';
                            elseif (str_contains($type,'order'))     $stageColor = 'info';
                        ?>
                            <tr>
                                <td><code class="small"><?= htmlspecialchars($e['event_id']) ?></code></td>
                                <td><span class="badge bg-<?= $stageColor ?>"><?= htmlspecialchars($e['event_type']) ?></span></td>
                                <td>
                                    <?php
                                    $stage = 'Other';
                                    foreach (['checkout','payment','order','shipment','delivery','refund','cancel'] as $s) {
                                        if (str_contains($type, $s)) { $stage = ucfirst($s); break; }
                                    }
                                    echo htmlspecialchars($stage);
                                    ?>
                                </td>
                                <td><?= em_status_badge($e['status']) ?></td>
                                <td><small class="text-muted"><?= em_time_ago($e['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'order-journey.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
