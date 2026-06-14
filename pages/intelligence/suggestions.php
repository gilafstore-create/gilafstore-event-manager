<?php
/**
 * Intelligence - Event Suggestions
 * READ-ONLY. em_event_suggestions: id, suggested_event_type, reason, confidence_score, status, created_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Suggestions — Event Manager';
$currentPage = 'suggestions';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?"; $params[] = $filterStatus; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total       = em_fetch("SELECT COUNT(*) as count FROM em_event_suggestions $whereClause", $params)['count'] ?? 0;
$suggestions = em_fetch_all("SELECT * FROM em_event_suggestions $whereClause ORDER BY confidence_score DESC LIMIT {$offset}, {$perPage}", $params);
$pagination  = em_paginate($total, $perPage, $page);

$pendingCount  = em_fetch("SELECT COUNT(*) as count FROM em_event_suggestions WHERE status='pending'")['count'] ?? 0;
$acceptedCount = em_fetch("SELECT COUNT(*) as count FROM em_event_suggestions WHERE status='accepted'")['count'] ?? 0;
$rejectedCount = em_fetch("SELECT COUNT(*) as count FROM em_event_suggestions WHERE status='rejected'")['count'] ?? 0;
$avgConfidence = em_fetch("SELECT ROUND(AVG(confidence_score)*100) as avg FROM em_event_suggestions")['avg'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Suggestions</h2>
    <p class="text-muted">AI-generated suggestions for new event types to implement</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-lightbulb"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Suggestions</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-clock"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Pending Review</div><div class="em-stat-value"><?= em_format_number($pendingCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Accepted</div><div class="em-stat-value"><?= em_format_number($acceptedCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-brain"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Avg Confidence</div><div class="em-stat-value"><?= $avgConfidence ?>%</div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','accepted','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="suggestions.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Suggestions <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($suggestions)): ?>
            <div class="em-empty-state"><i class="fas fa-lightbulb"></i><h4>No Suggestions</h4><p>No event suggestions have been generated yet.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Suggested Event Type</th><th>Reason</th><th>Confidence</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($suggestions as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['suggested_event_type']) ?></strong></td>
                                <td><small class="text-muted"><?= htmlspecialchars(substr($s['reason'] ?? '', 0, 80)) ?><?= strlen($s['reason'] ?? '') > 80 ? '...' : '' ?></small></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress flex-fill" style="height:6px;width:80px">
                                            <div class="progress-bar bg-<?= $s['confidence_score']>=0.8?'success':($s['confidence_score']>=0.5?'warning':'danger') ?>" style="width:<?= $s['confidence_score']*100 ?>%"></div>
                                        </div>
                                        <small><?= round($s['confidence_score']*100) ?>%</small>
                                    </div>
                                </td>
                                <td><?php
                                    $b = $s['status']==='accepted'?'success':($s['status']==='rejected'?'danger':'warning');
                                    echo "<span class='badge bg-{$b}'>" . ucfirst($s['status']) . "</span>";
                                ?></td>
                                <td><small class="text-muted"><?= em_time_ago($s['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'suggestions.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
