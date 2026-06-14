<?php
/**
 * Trace Center - Event Journeys
 * READ-ONLY. em_event_journeys: id, journey_id, event_id, step_number, step_name, status, created_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Journeys — Event Manager';
$currentPage = 'event-journeys';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterStatus  = $_GET['status'] ?? '';
$filterJourney = $_GET['journey'] ?? '';

$where = []; $params = [];
if ($filterStatus)  { $where[] = "status = ?";          $params[] = $filterStatus; }
if ($filterJourney) { $where[] = "journey_id LIKE ?";   $params[] = "%{$filterJourney}%"; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total   = em_fetch("SELECT COUNT(*) as count FROM em_event_journeys $whereClause", $params)['count'] ?? 0;
$rows    = em_fetch_all("SELECT * FROM em_event_journeys $whereClause ORDER BY journey_id, step_number LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$completedCount = em_fetch("SELECT COUNT(*) as count FROM em_event_journeys WHERE status='completed'")['count'] ?? 0;
$failedCount    = em_fetch("SELECT COUNT(*) as count FROM em_event_journeys WHERE status='failed'")['count'] ?? 0;
$uniqueJourneys = em_fetch("SELECT COUNT(DISTINCT journey_id) as count FROM em_event_journeys")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Journeys</h2>
    <p class="text-muted">Track multi-step event journey flows step by step</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-route"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Steps</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-map"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Unique Journeys</div><div class="em-stat-value"><?= em_format_number($uniqueJourneys) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-flag-checkered"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Completed Steps</div><div class="em-stat-value"><?= em_format_number($completedCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Failed Steps</div><div class="em-stat-value"><?= em_format_number($failedCount) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="journey" class="form-control" placeholder="Search journey ID..." value="<?= htmlspecialchars($filterJourney) ?>">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','completed','failed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="event-journeys.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Journey Steps <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="em-empty-state"><i class="fas fa-route"></i><h4>No Journey Records</h4><p>No event journey steps match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr><th>Journey ID</th><th>Event ID</th><th>Step</th><th>Step Name</th><th>Status</th><th>Recorded</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><code class="text-primary small"><?= htmlspecialchars(substr($r['journey_id'],0,20)) ?>...</code></td>
                                <td><small class="text-muted"><?= htmlspecialchars($r['event_id']) ?></small></td>
                                <td><span class="badge bg-secondary"><?= (int)$r['step_number'] ?></span></td>
                                <td><strong><?= htmlspecialchars($r['step_name']) ?></strong></td>
                                <td><?= em_status_badge($r['status']) ?></td>
                                <td><small class="text-muted"><?= em_time_ago($r['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'event-journeys.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
