<?php
/**
 * Event Bus - Event Replay (Bus-level)
 * READ-ONLY. Displays em_queue_messages with status='processed' available for replay reference.
 * Shows recently processed messages that could be replayed via em_event_replays.
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Replay (Bus) — Event Manager';
$currentPage = 'event-replay';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterQueue = $_GET['queue'] ?? '';
$where = []; $params = [];
if ($filterQueue) { $where[] = "queue_name = ?"; $params[] = $filterQueue; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages $whereClause", $params)['count'] ?? 0;
$messages   = em_fetch_all("SELECT * FROM em_queue_messages $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$processedCount = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages WHERE status='processed'")['count'] ?? 0;
$pendingCount   = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages WHERE status='pending'")['count'] ?? 0;
$queues         = em_fetch_all("SELECT DISTINCT queue_name FROM em_queue_messages ORDER BY queue_name");
$replayCount    = em_fetch("SELECT COUNT(*) as count FROM em_event_replays WHERE status IN ('pending','running')")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Bus Replay</h2>
    <p class="text-muted">Browse queue messages and view active replay sessions</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-history"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Messages</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Processed</div><div class="em-stat-value"><?= em_format_number($processedCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-clock"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Pending</div><div class="em-stat-value"><?= em_format_number($pendingCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-redo"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Active Replays</div><div class="em-stat-value"><?= em_format_number($replayCount) ?></div></div>
    </div>
</div>

<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    To initiate a replay session, see <a href="../event-operations/replays.php">Event Operations → Replays</a>.
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <select name="queue" class="form-select">
                    <option value="">All Queues</option>
                    <?php foreach ($queues as $q): ?>
                        <option value="<?= htmlspecialchars($q['queue_name']) ?>" <?= $filterQueue===$q['queue_name']?'selected':'' ?>><?= htmlspecialchars($q['queue_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="event-replay.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Queue Messages <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($messages)): ?>
            <div class="em-empty-state"><i class="fas fa-inbox"></i><h4>No Messages</h4><p>No queue messages match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>ID</th><th>Queue</th><th>Status</th><th>Attempts</th><th>Created</th><th>Processed</th></tr></thead>
                    <tbody>
                        <?php foreach ($messages as $m): ?>
                            <tr>
                                <td><small class="text-muted">#<?= $m['id'] ?></small></td>
                                <td><code><?= htmlspecialchars($m['queue_name']) ?></code></td>
                                <td><?= em_status_badge($m['status']) ?></td>
                                <td><span class="badge bg-secondary"><?= (int)$m['attempts'] ?></span></td>
                                <td><small class="text-muted"><?= em_time_ago($m['created_at']) ?></small></td>
                                <td><small class="text-muted"><?= $m['processed_at'] ? em_time_ago($m['processed_at']) : '—' ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'event-replay.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
