<?php
/**
 * Trace Center - Customer Journeys
 * READ-ONLY. em_customer_journeys: id, customer_id, event_id, touchpoint, created_at
 * Joins Gilaf Store users table (read-only reference)
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Customer Journeys — Event Manager';
$currentPage = 'customer-journeys';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterTouchpoint = $_GET['touchpoint'] ?? '';
$filterCustomer   = $_GET['customer'] ?? '';

$where = []; $params = [];
if ($filterTouchpoint) { $where[] = "cj.touchpoint = ?"; $params[] = $filterTouchpoint; }
if ($filterCustomer)   { $where[] = "cj.customer_id = ?"; $params[] = (int)$filterCustomer; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = em_fetch("SELECT COUNT(*) as count FROM em_customer_journeys cj $whereClause", $params)['count'] ?? 0;
$rows  = em_fetch_all("SELECT cj.* FROM em_customer_journeys cj $whereClause ORDER BY cj.created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$uniqueCustomers  = em_fetch("SELECT COUNT(DISTINCT customer_id) as count FROM em_customer_journeys")['count'] ?? 0;
$touchpoints      = em_fetch_all("SELECT DISTINCT touchpoint FROM em_customer_journeys ORDER BY touchpoint");
$todayCount       = em_fetch("SELECT COUNT(*) as count FROM em_customer_journeys WHERE DATE(created_at)=CURDATE()")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Customer Journeys</h2>
    <p class="text-muted">Track customer interaction touchpoints across events</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-users"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Touchpoints</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-user-friends"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Unique Customers</div><div class="em-stat-value"><?= em_format_number($uniqueCustomers) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-map-marker-alt"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Touchpoint Types</div><div class="em-stat-value"><?= count($touchpoints) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-calendar-day"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Today</div><div class="em-stat-value"><?= em_format_number($todayCount) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="number" name="customer" class="form-control" placeholder="Customer ID..." value="<?= htmlspecialchars($filterCustomer) ?>">
            </div>
            <div class="col-md-5">
                <select name="touchpoint" class="form-select">
                    <option value="">All Touchpoints</option>
                    <?php foreach ($touchpoints as $t): ?>
                        <option value="<?= htmlspecialchars($t['touchpoint']) ?>" <?= $filterTouchpoint===$t['touchpoint']?'selected':'' ?>><?= htmlspecialchars($t['touchpoint']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="customer-journeys.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Customer Touchpoints <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="em-empty-state"><i class="fas fa-users"></i><h4>No Journey Data</h4><p>No customer journey records match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr><th>Customer ID</th><th>Event ID</th><th>Touchpoint</th><th>Recorded</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><strong>#<?= (int)$r['customer_id'] ?></strong></td>
                                <td><small class="text-muted"><?= htmlspecialchars($r['event_id']) ?></small></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($r['touchpoint']) ?></span></td>
                                <td><small class="text-muted"><?= em_time_ago($r['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'customer-journeys.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
