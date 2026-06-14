<?php
/**
 * Developer Center - Webhooks
 * READ-ONLY. em_webhooks: id, name, url, events, status, secret_key, last_triggered_at, created_by, created_at
 *            em_webhook_logs: id, webhook_id, status, response_code, created_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Webhooks — Event Manager';
$currentPage = 'webhooks';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?"; $params[] = $filterStatus; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_webhooks $whereClause", $params)['count'] ?? 0;
$webhooks   = em_fetch_all("SELECT w.*, (SELECT COUNT(*) FROM em_webhook_logs wl WHERE wl.webhook_id=w.id) as delivery_count,
              (SELECT COUNT(*) FROM em_webhook_logs wl WHERE wl.webhook_id=w.id AND wl.status='success') as success_count
              FROM em_webhooks w $whereClause ORDER BY w.created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$activeCount   = em_fetch("SELECT COUNT(*) as count FROM em_webhooks WHERE status='active'")['count'] ?? 0;
$totalLogs     = em_fetch("SELECT COUNT(*) as count FROM em_webhook_logs")['count'] ?? 0;
$failedLogs    = em_fetch("SELECT COUNT(*) as count FROM em_webhook_logs WHERE status='failed'")['count'] ?? 0;
$successRate   = $totalLogs > 0 ? round((($totalLogs - $failedLogs) / $totalLogs) * 100) : 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Webhooks</h2>
    <p class="text-muted">View configured webhooks and delivery logs</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-satellite-dish"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Webhooks</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-toggle-on"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Active</div><div class="em-stat-value"><?= em_format_number($activeCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-paper-plane"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Deliveries</div><div class="em-stat-value"><?= em_format_number($totalLogs) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon <?= $successRate >= 95 ? 'success' : ($successRate >= 80 ? 'warning' : 'danger') ?>"><i class="fas fa-percentage"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Success Rate</div><div class="em-stat-value"><?= $successRate ?>%</div></div>
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
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="webhooks.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Webhooks <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($webhooks)): ?>
            <div class="em-empty-state"><i class="fas fa-satellite-dish"></i><h4>No Webhooks Found</h4><p>No webhooks have been configured.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Name</th><th>URL</th><th>Status</th><th>Deliveries</th><th>Success</th><th>Last Triggered</th></tr></thead>
                    <tbody>
                        <?php foreach ($webhooks as $w): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                                <td>
                                    <code class="small text-muted">
                                        <?php $url = parse_url($w['url']); echo htmlspecialchars(($url['scheme']??'').'://'.($url['host']??'').substr($w['url'], strlen(($url['scheme']??'').'://'.($url['host']??'')))); ?>
                                    </code>
                                </td>
                                <td><?= em_status_badge($w['status']) ?></td>
                                <td><span class="badge bg-secondary"><?= em_format_number($w['delivery_count']) ?></span></td>
                                <td>
                                    <?php if ($w['delivery_count'] > 0):
                                        $pct = round(($w['success_count'] / $w['delivery_count']) * 100); ?>
                                        <span class="badge bg-<?= $pct >= 95 ? 'success' : ($pct >= 80 ? 'warning' : 'danger') ?>"><?= $pct ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= $w['last_triggered_at'] ? em_time_ago($w['last_triggered_at']) : 'Never' ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'webhooks.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
