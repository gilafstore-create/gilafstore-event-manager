<?php
/**
 * Intelligence - Event Coverage
 * READ-ONLY. em_event_coverage: id, entity_type, entity_id, coverage_percentage, missing_events, analyzed_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Coverage — Event Manager';
$currentPage = 'event-coverage';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterType = $_GET['type'] ?? '';
$where = []; $params = [];
if ($filterType) { $where[] = "entity_type = ?"; $params[] = $filterType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_event_coverage $whereClause", $params)['count'] ?? 0;
$rows       = em_fetch_all("SELECT * FROM em_event_coverage $whereClause ORDER BY coverage_percentage ASC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$avgCoverage    = em_fetch("SELECT ROUND(AVG(coverage_percentage),1) as avg FROM em_event_coverage")['avg'] ?? 0;
$fullyCovered   = em_fetch("SELECT COUNT(*) as count FROM em_event_coverage WHERE coverage_percentage = 100")['count'] ?? 0;
$lowCoverage    = em_fetch("SELECT COUNT(*) as count FROM em_event_coverage WHERE coverage_percentage < 50")['count'] ?? 0;
$entityTypes    = em_fetch_all("SELECT DISTINCT entity_type FROM em_event_coverage ORDER BY entity_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Coverage</h2>
    <p class="text-muted">Analyze event coverage across entities and identify gaps</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-chart-pie"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Entities Analyzed</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-percentage"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Avg Coverage</div><div class="em-stat-value"><?= $avgCoverage ?>%</div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Fully Covered</div><div class="em-stat-value"><?= em_format_number($fullyCovered) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Low Coverage (&lt;50%)</div><div class="em-stat-value"><?= em_format_number($lowCoverage) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <select name="type" class="form-select">
                    <option value="">All Entity Types</option>
                    <?php foreach ($entityTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['entity_type']) ?>" <?= $filterType===$t['entity_type']?'selected':'' ?>><?= htmlspecialchars($t['entity_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="event-coverage.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Coverage Analysis <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="em-empty-state"><i class="fas fa-chart-pie"></i><h4>No Coverage Data</h4><p>No coverage analysis records found.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Entity Type</th><th>Entity ID</th><th>Coverage</th><th>Analyzed</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($r['entity_type']) ?></span></td>
                                <td><strong>#<?= (int)$r['entity_id'] ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-fill" style="height:8px">
                                            <div class="progress-bar bg-<?= $r['coverage_percentage']>=80?'success':($r['coverage_percentage']>=50?'warning':'danger') ?>" style="width:<?= $r['coverage_percentage'] ?>%"></div>
                                        </div>
                                        <strong><?= number_format($r['coverage_percentage'],1) ?>%</strong>
                                    </div>
                                </td>
                                <td><small class="text-muted"><?= em_time_ago($r['analyzed_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'event-coverage.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
