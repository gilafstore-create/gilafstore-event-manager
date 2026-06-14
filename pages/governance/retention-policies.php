<?php
/**
 * Governance - Retention Policies
 * READ-ONLY. em_retention_policies: id, table_name, retention_days, status, last_cleanup_at, created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Retention Policies — Event Manager';
$currentPage = 'retention-policies';

$filterStatus = $_GET['status'] ?? '';
$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?"; $params[] = $filterStatus; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$policies     = em_fetch_all("SELECT * FROM em_retention_policies $whereClause ORDER BY table_name", $params);
$total        = count($policies);
$activeCount  = em_fetch("SELECT COUNT(*) as count FROM em_retention_policies WHERE status='active'")['count'] ?? 0;
$avgDays      = em_fetch("SELECT ROUND(AVG(retention_days)) as avg FROM em_retention_policies")['avg'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Retention Policies</h2>
    <p class="text-muted">View data retention configuration for all Event Manager tables</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-database"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Policies</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-toggle-on"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Active</div><div class="em-stat-value"><?= em_format_number($activeCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-calendar-alt"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Avg Retention (days)</div><div class="em-stat-value"><?= $avgDays ?></div></div>
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
                <a href="retention-policies.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Retention Policies <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($policies)): ?>
            <div class="em-empty-state"><i class="fas fa-database"></i><h4>No Policies Configured</h4><p>No retention policies have been defined yet.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Table Name</th><th>Retention (days)</th><th>Status</th><th>Last Cleanup</th><th>Updated</th></tr></thead>
                    <tbody>
                        <?php foreach ($policies as $p): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($p['table_name']) ?></code></td>
                                <td>
                                    <span class="badge bg-<?= $p['retention_days'] <= 30 ? 'danger' : ($p['retention_days'] <= 90 ? 'warning' : 'success') ?>">
                                        <?= (int)$p['retention_days'] ?> days
                                    </span>
                                </td>
                                <td><?= em_status_badge($p['status']) ?></td>
                                <td><small class="text-muted"><?= $p['last_cleanup_at'] ? em_time_ago($p['last_cleanup_at']) : '<span class="text-muted">Never</span>' ?></small></td>
                                <td><small class="text-muted"><?= em_time_ago($p['updated_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
