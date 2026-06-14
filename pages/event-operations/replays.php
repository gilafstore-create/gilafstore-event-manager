<?php
/**
 * Event Operations - Event Replays
 * READ-ONLY. em_event_replays: id, name, event_type, start_date, end_date, status,
 *            total_events, processed_events, created_by, created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Replays — Event Manager';
$currentPage = 'replays';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?";     $params[] = $filterStatus; }
if ($filterType)   { $where[] = "event_type = ?"; $params[] = $filterType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total   = em_fetch("SELECT COUNT(*) as count FROM em_event_replays $whereClause", $params)['count'] ?? 0;
$replays = em_fetch_all("SELECT * FROM em_event_replays $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$pendingCount   = em_fetch("SELECT COUNT(*) as count FROM em_event_replays WHERE status='pending'")['count'] ?? 0;
$runningCount   = em_fetch("SELECT COUNT(*) as count FROM em_event_replays WHERE status='running'")['count'] ?? 0;
$completedCount = em_fetch("SELECT COUNT(*) as count FROM em_event_replays WHERE status='completed'")['count'] ?? 0;
$eventTypes     = em_fetch_all("SELECT DISTINCT event_type FROM em_event_replays WHERE event_type IS NOT NULL ORDER BY event_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Replays</h2>
    <p class="text-muted">Monitor and review historical event replay sessions</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-redo"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Replays</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-clock"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Pending</div><div class="em-stat-value"><?= em_format_number($pendingCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-spinner"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Running</div><div class="em-stat-value"><?= em_format_number($runningCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-circle"></i></div>
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
                    <?php foreach (['pending','running','completed','failed'] as $s): ?>
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
                <a href="replays.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Replay Sessions <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($replays)): ?>
            <div class="em-empty-state"><i class="fas fa-redo"></i><h4>No Replays Found</h4><p>No replay sessions match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr><th>Name</th><th>Event Type</th><th>Status</th><th>Progress</th><th>Date Range</th><th>Created</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($replays as $r):
                            $pct = $r['total_events'] > 0 ? round(($r['processed_events'] / $r['total_events']) * 100) : 0;
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['event_type'] ?? '—') ?></span></td>
                                <td><?= em_status_badge($r['status']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-fill" style="height:6px">
                                            <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <small><?= $pct ?>%</small>
                                    </div>
                                    <small class="text-muted"><?= em_format_number($r['processed_events']) ?> / <?= em_format_number($r['total_events']) ?></small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= $r['start_date'] ? em_format_date($r['start_date']) : '—' ?>
                                        <span class="mx-1">→</span>
                                        <?= $r['end_date'] ? em_format_date($r['end_date']) : '—' ?>
                                    </small>
                                </td>
                                <td><small class="text-muted"><?= em_time_ago($r['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'replays.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
