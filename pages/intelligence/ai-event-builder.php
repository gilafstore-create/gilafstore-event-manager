<?php
/**
 * Intelligence - AI Event Builder
 * READ-ONLY. Consolidates em_ai_recommendations, em_event_suggestions, em_predictive_failures.
 * em_ai_recommendations: id, recommendation_type, title, description, action_required, priority, status, created_at
 * em_event_suggestions: id, event_type, suggested_name, description, confidence_score, status, created_at, updated_at
 * em_predictive_failures: id, event_type, predicted_at, probability, reason, status, created_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'AI Event Builder — Event Manager';
$currentPage = 'ai-event-builder';

$tab = $_GET['tab'] ?? 'recommendations';

// Pagination
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Recommendations
$filterRStatus   = $_GET['rstatus'] ?? '';
$filterPriority  = $_GET['priority'] ?? '';
$rWhere = []; $rParams = [];
if ($filterRStatus)  { $rWhere[] = "status = ?";   $rParams[] = $filterRStatus; }
if ($filterPriority) { $rWhere[] = "priority = ?"; $rParams[] = $filterPriority; }
$rClause = $rWhere ? 'WHERE ' . implode(' AND ', $rWhere) : '';

$totalRec  = em_fetch("SELECT COUNT(*) as c FROM em_ai_recommendations $rClause", $rParams)['c'] ?? 0;
$recs      = em_fetch_all("SELECT * FROM em_ai_recommendations $rClause ORDER BY priority='high' DESC, created_at DESC LIMIT {$offset}, {$perPage}", $rParams);
$recPages  = em_paginate($totalRec, $perPage, $page);

// Suggestions
$filterSStatus = $_GET['sstatus'] ?? '';
$sWhere = []; $sParams = [];
if ($filterSStatus) { $sWhere[] = "status = ?"; $sParams[] = $filterSStatus; }
$sClause = $sWhere ? 'WHERE ' . implode(' AND ', $sWhere) : '';
$totalSug  = em_fetch("SELECT COUNT(*) as c FROM em_event_suggestions $sClause", $sParams)['c'] ?? 0;
$sugs      = em_fetch_all("SELECT * FROM em_event_suggestions $sClause ORDER BY confidence_score DESC LIMIT {$offset}, {$perPage}", $sParams);
$sugPages  = em_paginate($totalSug, $perPage, $page);

// Predictive Failures
$totalPred = em_fetch("SELECT COUNT(*) as c FROM em_predictive_failures")['c'] ?? 0;
$preds     = em_fetch_all("SELECT * FROM em_predictive_failures ORDER BY probability DESC LIMIT {$offset}, {$perPage}");
$predPages = em_paginate($totalPred, $perPage, $page);

// Stats
$highPrioRecs  = em_fetch("SELECT COUNT(*) as c FROM em_ai_recommendations WHERE priority='high' AND status='pending'")['c'] ?? 0;
$pendingSugs   = em_fetch("SELECT COUNT(*) as c FROM em_event_suggestions WHERE status='pending'")['c'] ?? 0;
$highRiskPreds = em_fetch("SELECT COUNT(*) as c FROM em_predictive_failures WHERE probability >= 0.75")['c'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>AI Event Builder</h2>
    <p class="text-muted">AI-powered recommendations, event suggestions, and predictive failure insights</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-robot"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">AI Recommendations</div><div class="em-stat-value"><?= em_format_number($totalRec) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">High Priority</div><div class="em-stat-value"><?= em_format_number($highPrioRecs) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-lightbulb"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Pending Suggestions</div><div class="em-stat-value"><?= em_format_number($pendingSugs) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-skull"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">High-Risk Predictions</div><div class="em-stat-value"><?= em_format_number($highRiskPreds) ?></div></div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?= $tab==='recommendations'?'active':'' ?>" href="?tab=recommendations"><i class="fas fa-robot me-1"></i>Recommendations <span class="badge bg-secondary"><?= $totalRec ?></span></a></li>
    <li class="nav-item"><a class="nav-link <?= $tab==='suggestions'?'active':'' ?>" href="?tab=suggestions"><i class="fas fa-lightbulb me-1"></i>Suggestions <span class="badge bg-secondary"><?= $totalSug ?></span></a></li>
    <li class="nav-item"><a class="nav-link <?= $tab==='predictions'?'active':'' ?>" href="?tab=predictions"><i class="fas fa-chart-line me-1"></i>Predictive Failures <span class="badge bg-secondary"><?= $totalPred ?></span></a></li>
</ul>

<?php if ($tab === 'recommendations'): ?>
<!-- Recommendations Filter -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="tab" value="recommendations">
            <div class="col-md-4">
                <select name="rstatus" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','applied','dismissed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterRStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <?php foreach (['high','medium','low'] as $p): ?>
                        <option value="<?= $p ?>" <?= $filterPriority===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="?tab=recommendations" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>
<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">AI Recommendations</h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($recs)): ?>
            <div class="em-empty-state"><i class="fas fa-robot"></i><h4>No Recommendations</h4><p>No AI recommendations match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Title</th><th>Type</th><th>Priority</th><th>Action Required</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($recs as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['title']) ?></strong><br><small class="text-muted"><?= htmlspecialchars(substr($r['description'] ?? '', 0, 60)) ?></small></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['recommendation_type']) ?></span></td>
                                <td><?php
                                    $pc = $r['priority']==='high'?'danger':($r['priority']==='medium'?'warning':'info');
                                    echo "<span class='badge bg-{$pc}'>" . ucfirst($r['priority']) . "</span>";
                                ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars(substr($r['action_required'] ?? '—', 0, 80)) ?></small></td>
                                <td><?= em_status_badge($r['status']) ?></td>
                                <td><small class="text-muted"><?= em_time_ago($r['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($recPages['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($recPages, 'ai-event-builder.php', ['tab' => 'recommendations', 'rstatus' => $filterRStatus, 'priority' => $filterPriority]) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'suggestions'): ?>
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="tab" value="suggestions">
            <div class="col-md-5">
                <select name="sstatus" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','accepted','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterSStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="?tab=suggestions" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>
<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Event Suggestions</h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($sugs)): ?>
            <div class="em-empty-state"><i class="fas fa-lightbulb"></i><h4>No Suggestions</h4><p>No event suggestions match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Suggested Name</th><th>Event Type</th><th>Confidence</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($sugs as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['suggested_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars(substr($s['description'] ?? '', 0, 60)) ?></small></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($s['event_type']) ?></span></td>
                                <td>
                                    <?php $score = (float)$s['confidence_score']; $pct = round($score * 100); ?>
                                    <div class="progress" style="width:80px;height:8px;" title="<?= $pct ?>%">
                                        <div class="progress-bar bg-<?= $pct>=80?'success':($pct>=50?'warning':'danger') ?>" style="width:<?= $pct ?>%"></div>
                                    </div>
                                    <small><?= $pct ?>%</small>
                                </td>
                                <td><?= em_status_badge($s['status']) ?></td>
                                <td><small class="text-muted"><?= em_time_ago($s['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($sugPages['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($sugPages, 'ai-event-builder.php', ['tab' => 'suggestions', 'sstatus' => $filterSStatus]) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Predictive Failures</h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($preds)): ?>
            <div class="em-empty-state"><i class="fas fa-chart-line"></i><h4>No Predictions</h4><p>No predictive failure data available.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Event Type</th><th>Probability</th><th>Risk</th><th>Reason</th><th>Status</th><th>Predicted At</th></tr></thead>
                    <tbody>
                        <?php foreach ($preds as $p):
                            $prob = (float)$p['probability'];
                            $risk = $prob >= 0.75 ? 'danger' : ($prob >= 0.5 ? 'warning' : 'info');
                            $riskLabel = $prob >= 0.75 ? 'High' : ($prob >= 0.5 ? 'Medium' : 'Low');
                        ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($p['event_type']) ?></span></td>
                                <td>
                                    <div class="progress" style="width:80px;height:8px;" title="<?= round($prob*100) ?>%">
                                        <div class="progress-bar bg-<?= $risk ?>" style="width:<?= round($prob*100) ?>%"></div>
                                    </div>
                                    <small><?= round($prob*100) ?>%</small>
                                </td>
                                <td><span class="badge bg-<?= $risk ?>"><?= $riskLabel ?></span></td>
                                <td><small class="text-muted"><?= htmlspecialchars(substr($p['reason'] ?? '—', 0, 80)) ?></small></td>
                                <td><?= em_status_badge($p['status']) ?></td>
                                <td><small class="text-muted"><?= em_time_ago($p['predicted_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($predPages['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($predPages, 'ai-event-builder.php', ['tab' => 'predictions']) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
