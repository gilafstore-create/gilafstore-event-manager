<?php
/**
 * Event Operations - Event Simulations
 * READ-ONLY. em_event_simulations: id, name, event_type, payload, status, result,
 *            created_by, created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Simulations — Event Manager';
$currentPage = 'simulations';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?";     $params[] = $filterStatus; }
if ($filterType)   { $where[] = "event_type = ?"; $params[] = $filterType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total       = em_fetch("SELECT COUNT(*) as count FROM em_event_simulations $whereClause", $params)['count'] ?? 0;
$sims        = em_fetch_all("SELECT * FROM em_event_simulations $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination  = em_paginate($total, $perPage, $page);

$pendingCount   = em_fetch("SELECT COUNT(*) as count FROM em_event_simulations WHERE status='pending'")['count'] ?? 0;
$runningCount   = em_fetch("SELECT COUNT(*) as count FROM em_event_simulations WHERE status='running'")['count'] ?? 0;
$completedCount = em_fetch("SELECT COUNT(*) as count FROM em_event_simulations WHERE status='completed'")['count'] ?? 0;
$eventTypes     = em_fetch_all("SELECT DISTINCT event_type FROM em_event_simulations ORDER BY event_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Simulations</h2>
    <p class="text-muted">Review event simulation runs and their results</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-flask"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Simulations</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Pending</div><div class="em-stat-value"><?= em_format_number($pendingCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-running"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Running</div><div class="em-stat-value"><?= em_format_number($runningCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-double"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Completed</div><div class="em-stat-value"><?= em_format_number($completedCount) ?></div></div>
    </div>
</div>

<!-- Filters -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','running','completed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <select name="type" class="form-select">
                    <option value="">All Event Types</option>
                    <?php foreach ($eventTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['event_type']) ?>" <?= $filterType===$t['event_type']?'selected':'' ?>><?= htmlspecialchars($t['event_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="simulations.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Simulations <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($sims)): ?>
            <div class="em-empty-state"><i class="fas fa-flask"></i><h4>No Simulations Found</h4><p>No simulation runs match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr><th>Name</th><th>Event Type</th><th>Status</th><th>Result</th><th>Created By</th><th>Created</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sims as $sim): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($sim['name']) ?></strong></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($sim['event_type']) ?></span></td>
                                <td><?= em_status_badge($sim['status']) ?></td>
                                <td>
                                    <?php if ($sim['result']): ?>
                                        <button class="em-btn em-btn-sm em-btn-secondary"
                                                onclick='document.getElementById("simResult").textContent=JSON.stringify(<?= $sim["result"] ?>,null,2);new bootstrap.Modal(document.getElementById("resultModal")).show()'>
                                            View Result
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $sim['created_by'] ? '#'.(int)$sim['created_by'] : '<span class="text-muted">—</span>' ?></td>
                                <td><small class="text-muted"><?= em_time_ago($sim['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'simulations.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Simulation Result</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><pre id="simResult" class="bg-light p-3 rounded" style="max-height:400px;overflow-y:auto"></pre></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
