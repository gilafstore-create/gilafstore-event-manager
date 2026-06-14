<?php
/**
 * Governance - Alert Rules
 * READ-ONLY. em_alert_rules: id, name, condition, action, status, created_by, created_at, updated_at
 *            em_alert_history: id, rule_id, triggered_at, details
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Alert Rules — Event Manager';
$currentPage = 'alert-rules';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?"; $params[] = $filterStatus; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_alert_rules $whereClause", $params)['count'] ?? 0;
$rules      = em_fetch_all("SELECT ar.*, (SELECT COUNT(*) FROM em_alert_history ah WHERE ah.rule_id=ar.id) as trigger_count FROM em_alert_rules ar $whereClause ORDER BY ar.created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$activeCount   = em_fetch("SELECT COUNT(*) as count FROM em_alert_rules WHERE status='active'")['count'] ?? 0;
$inactiveCount = em_fetch("SELECT COUNT(*) as count FROM em_alert_rules WHERE status='inactive'")['count'] ?? 0;
$totalTriggers = em_fetch("SELECT COUNT(*) as count FROM em_alert_history")['count'] ?? 0;
$todayTriggers = em_fetch("SELECT COUNT(*) as count FROM em_alert_history WHERE DATE(triggered_at)=CURDATE()")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Alert Rules</h2>
    <p class="text-muted">View configured alert rules and trigger history</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-bell"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Rules</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-bell-slash"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Active</div><div class="em-stat-value"><?= em_format_number($activeCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-history"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Triggers</div><div class="em-stat-value"><?= em_format_number($totalTriggers) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-calendar-day"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Triggered Today</div><div class="em-stat-value"><?= em_format_number($todayTriggers) ?></div></div>
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
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="alert-rules.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Alert Rules <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($rules)): ?>
            <div class="em-empty-state"><i class="fas fa-bell"></i><h4>No Alert Rules</h4><p>No alert rules have been configured yet.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Name</th><th>Status</th><th>Triggers</th><th>Created By</th><th>Created</th><th>Updated</th></tr></thead>
                    <tbody>
                        <?php foreach ($rules as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                                <td><?= em_status_badge($r['status']) ?></td>
                                <td><span class="badge bg-<?= $r['trigger_count']>0?'warning':'secondary' ?>"><?= em_format_number($r['trigger_count']) ?></span></td>
                                <td><?= $r['created_by'] ? '#'.(int)$r['created_by'] : '<span class="text-muted">—</span>' ?></td>
                                <td><small class="text-muted"><?= em_time_ago($r['created_at']) ?></small></td>
                                <td><small class="text-muted"><?= em_time_ago($r['updated_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'alert-rules.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
