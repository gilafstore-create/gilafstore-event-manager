<?php
/**
 * Intelligence - Anomalies & Recommendations
 * READ-ONLY. em_anomalies, em_ai_recommendations, em_predictive_failures
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Anomalies — Event Manager';
$currentPage = 'anomalies';

$tab = $_GET['tab'] ?? 'anomalies';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus   = $_GET['status'] ?? '';
$filterSeverity = $_GET['severity'] ?? '';

$where = []; $params = [];
if ($filterStatus)   { $where[] = "status = ?";   $params[] = $filterStatus; }
if ($filterSeverity) { $where[] = "severity = ?"; $params[] = $filterSeverity; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

if ($tab === 'anomalies') {
    $total = em_fetch("SELECT COUNT(*) as count FROM em_anomalies $whereClause", $params)['count'] ?? 0;
    $rows  = em_fetch_all("SELECT * FROM em_anomalies $whereClause ORDER BY detected_at DESC LIMIT {$offset}, {$perPage}", $params);
} elseif ($tab === 'recommendations') {
    $where2 = []; $params2 = [];
    if ($filterStatus) { $where2[] = "status = ?"; $params2[] = $filterStatus; }
    $w2 = $where2 ? 'WHERE ' . implode(' AND ', $where2) : '';
    $total = em_fetch("SELECT COUNT(*) as count FROM em_ai_recommendations $w2", $params2)['count'] ?? 0;
    $rows  = em_fetch_all("SELECT * FROM em_ai_recommendations $w2 ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params2);
} else {
    $total = em_fetch("SELECT COUNT(*) as count FROM em_predictive_failures")['count'] ?? 0;
    $rows  = em_fetch_all("SELECT * FROM em_predictive_failures ORDER BY predicted_at DESC LIMIT {$offset}, {$perPage}");
}
$pagination = em_paginate($total, $perPage, $page);

$totalAnomalies     = em_fetch("SELECT COUNT(*) as count FROM em_anomalies WHERE status != 'resolved'")['count'] ?? 0;
$criticalAnomalies  = em_fetch("SELECT COUNT(*) as count FROM em_anomalies WHERE severity='critical' AND status != 'resolved'")['count'] ?? 0;
$pendingRecs        = em_fetch("SELECT COUNT(*) as count FROM em_ai_recommendations WHERE status='pending'")['count'] ?? 0;
$predictions        = em_fetch("SELECT COUNT(*) as count FROM em_predictive_failures")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Anomalies & Intelligence</h2>
    <p class="text-muted">Detected anomalies, AI recommendations, and predictive failure analysis</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Active Anomalies</div><div class="em-stat-value"><?= em_format_number($totalAnomalies) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-radiation"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Critical</div><div class="em-stat-value"><?= em_format_number($criticalAnomalies) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-robot"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">AI Recommendations</div><div class="em-stat-value"><?= em_format_number($pendingRecs) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon secondary"><i class="fas fa-crystal-ball"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Predictions</div><div class="em-stat-value"><?= em_format_number($predictions) ?></div></div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?= $tab==='anomalies'?'active':'' ?>" href="?tab=anomalies"><i class="fas fa-exclamation-triangle me-1"></i>Anomalies</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab==='recommendations'?'active':'' ?>" href="?tab=recommendations"><i class="fas fa-robot me-1"></i>AI Recommendations</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab==='predictions'?'active':'' ?>" href="?tab=predictions"><i class="fas fa-chart-line me-1"></i>Predictive Failures</a></li>
</ul>

<?php if ($tab === 'anomalies'): ?>
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="tab" value="anomalies">
            <div class="col-md-4">
                <select name="severity" class="form-select">
                    <option value="">All Severities</option>
                    <?php foreach (['low','medium','high','critical'] as $sv): ?>
                        <option value="<?= $sv ?>" <?= $filterSeverity===$sv?'selected':'' ?>><?= ucfirst($sv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['detected','investigating','resolved'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="?tab=anomalies" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>
<div class="em-card">
    <div class="em-card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="em-empty-state"><i class="fas fa-check-circle text-success"></i><h4>No Anomalies Detected</h4><p>System is operating normally.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Type</th><th>Description</th><th>Severity</th><th>Status</th><th>Detected</th><th>Resolved</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['anomaly_type']) ?></strong></td>
                                <td><small><?= htmlspecialchars(substr($r['description'] ?? '', 0, 80)) ?></small></td>
                                <td>
                                    <?php $sev=['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'secondary'];
                                    echo "<span class='badge bg-".($sev[$r['severity']]??'secondary')."'>".ucfirst($r['severity'])."</span>"; ?>
                                </td>
                                <td><?= em_status_badge($r['status']) ?></td>
                                <td><small class="text-muted"><?= em_time_ago($r['detected_at']) ?></small></td>
                                <td><small class="text-muted"><?= $r['resolved_at'] ? em_time_ago($r['resolved_at']) : '—' ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, '?tab=anomalies') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'recommendations'): ?>
<div class="em-card">
    <div class="em-card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="em-empty-state"><i class="fas fa-robot"></i><h4>No Recommendations</h4><p>No AI recommendations available.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Title</th><th>Type</th><th>Priority</th><th>Status</th><th>Action Required</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['title']) ?></strong><br><small class="text-muted"><?= htmlspecialchars(substr($r['description'] ?? '', 0, 60)) ?></small></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['recommendation_type']) ?></span></td>
                                <td><?php $pc=['high'=>'danger','medium'=>'warning','low'=>'secondary']; echo "<span class='badge bg-".($pc[$r['priority']]??'secondary')."'>".ucfirst($r['priority'])."</span>"; ?></td>
                                <td><?php $sc=['applied'=>'success','dismissed'=>'secondary','pending'=>'warning']; echo "<span class='badge bg-".($sc[$r['status']]??'secondary')."'>".ucfirst($r['status'])."</span>"; ?></td>
                                <td><small><?= htmlspecialchars(substr($r['action_required'] ?? '', 0, 60)) ?></small></td>
                                <td><small class="text-muted"><?= em_time_ago($r['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="em-card">
    <div class="em-card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="em-empty-state"><i class="fas fa-chart-line"></i><h4>No Predictions</h4><p>No predictive failure data available.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Failure Type</th><th>Probability</th><th>Predicted At</th><th>Actual Failure</th><th>Accurate</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['predicted_failure_type']) ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress" style="width:80px;height:6px">
                                            <div class="progress-bar bg-<?= $r['probability']>=0.7?'danger':($r['probability']>=0.4?'warning':'info') ?>" style="width:<?= $r['probability']*100 ?>%"></div>
                                        </div>
                                        <small><?= round($r['probability']*100) ?>%</small>
                                    </div>
                                </td>
                                <td><small class="text-muted"><?= em_time_ago($r['predicted_at']) ?></small></td>
                                <td><small class="text-muted"><?= $r['actual_failure_at'] ? em_time_ago($r['actual_failure_at']) : '—' ?></small></td>
                                <td><?php
                                    if (is_null($r['was_accurate'])) echo '<span class="badge bg-secondary">Pending</span>';
                                    elseif ($r['was_accurate']) echo '<span class="badge bg-success">Yes</span>';
                                    else echo '<span class="badge bg-danger">No</span>';
                                ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
