<?php
/**
 * Governance - Compliance Logs
 * READ-ONLY. em_compliance_logs: id, compliance_type, status, details, checked_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Compliance Logs — Event Manager';
$currentPage = 'compliance';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?";           $params[] = $filterStatus; }
if ($filterType)   { $where[] = "compliance_type = ?";  $params[] = $filterType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_compliance_logs $whereClause", $params)['count'] ?? 0;
$logs       = em_fetch_all("SELECT * FROM em_compliance_logs $whereClause ORDER BY checked_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$compliantCount    = em_fetch("SELECT COUNT(*) as count FROM em_compliance_logs WHERE status='compliant'")['count'] ?? 0;
$nonCompliantCount = em_fetch("SELECT COUNT(*) as count FROM em_compliance_logs WHERE status='non_compliant'")['count'] ?? 0;
$warningCount      = em_fetch("SELECT COUNT(*) as count FROM em_compliance_logs WHERE status='warning'")['count'] ?? 0;
$complianceTypes   = em_fetch_all("SELECT DISTINCT compliance_type FROM em_compliance_logs ORDER BY compliance_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Compliance Logs</h2>
    <p class="text-muted">Review compliance check history and status</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-shield-alt"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Checks</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-shield"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Compliant</div><div class="em-stat-value"><?= em_format_number($compliantCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-times"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Non-Compliant</div><div class="em-stat-value"><?= em_format_number($nonCompliantCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Warnings</div><div class="em-stat-value"><?= em_format_number($warningCount) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="compliant" <?= $filterStatus==='compliant'?'selected':'' ?>>Compliant</option>
                    <option value="non_compliant" <?= $filterStatus==='non_compliant'?'selected':'' ?>>Non-Compliant</option>
                    <option value="warning" <?= $filterStatus==='warning'?'selected':'' ?>>Warning</option>
                </select>
            </div>
            <div class="col-md-5">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($complianceTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['compliance_type']) ?>" <?= $filterType===$t['compliance_type']?'selected':'' ?>><?= htmlspecialchars($t['compliance_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="compliance.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Compliance Records <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="em-empty-state"><i class="fas fa-shield-alt"></i><h4>No Compliance Logs</h4><p>No compliance check records match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Type</th><th>Status</th><th>Checked At</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($l['compliance_type']) ?></strong></td>
                                <td><?php
                                    $badge = $l['status']==='compliant' ? 'success' : ($l['status']==='warning' ? 'warning' : 'danger');
                                    echo "<span class='badge bg-{$badge}'>" . htmlspecialchars($l['status']) . "</span>";
                                ?></td>
                                <td><small class="text-muted"><?= em_time_ago($l['checked_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'compliance.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
