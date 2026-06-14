<?php
/**
 * Event Bus - Dead Letter Queue
 * READ-ONLY. em_dead_letter_queue: id, original_message_id, reason, created_at
 * Joins em_queue_messages for payload and queue_name
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Dead Letter Queue — Event Manager';
$currentPage = 'dead-letter-queue';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterQueue = $_GET['queue'] ?? '';
$where = []; $params = [];
if ($filterQueue) { $where[] = "qm.queue_name = ?"; $params[] = $filterQueue; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = em_fetch("SELECT COUNT(*) as count FROM em_dead_letter_queue dlq LEFT JOIN em_queue_messages qm ON dlq.original_message_id = qm.id $whereClause", $params)['count'] ?? 0;
$rows  = em_fetch_all("SELECT dlq.*, qm.queue_name, qm.payload, qm.attempts, qm.status as msg_status
    FROM em_dead_letter_queue dlq
    LEFT JOIN em_queue_messages qm ON dlq.original_message_id = qm.id
    $whereClause ORDER BY dlq.created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$queues   = em_fetch_all("SELECT DISTINCT qm.queue_name FROM em_dead_letter_queue dlq LEFT JOIN em_queue_messages qm ON dlq.original_message_id = qm.id WHERE qm.queue_name IS NOT NULL ORDER BY qm.queue_name");
$todayCount  = em_fetch("SELECT COUNT(*) as count FROM em_dead_letter_queue WHERE DATE(created_at)=CURDATE()")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Dead Letter Queue</h2>
    <p class="text-muted">Messages that could not be processed after all retry attempts</p>
</div>

<?php if ($total > 0): ?>
<div class="alert alert-danger mb-4">
    <i class="fas fa-skull-crossbones me-2"></i>
    <strong><?= em_format_number($total) ?> message(s)</strong> in the dead letter queue require investigation.
</div>
<?php endif; ?>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-skull-crossbones"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total DLQ Messages</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-calendar-day"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Added Today</div><div class="em-stat-value"><?= em_format_number($todayCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-layer-group"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Affected Queues</div><div class="em-stat-value"><?= count($queues) ?></div></div>
    </div>
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
                <a href="dead-letter-queue.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">DLQ Messages <span class="badge bg-danger"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="em-empty-state text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h4>Dead Letter Queue is Empty</h4>
                <p class="text-muted">All messages are being processed successfully.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>#</th><th>Queue</th><th>Reason</th><th>Attempts</th><th>Recorded</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><small class="text-muted">#<?= $r['original_message_id'] ?></small></td>
                                <td><code><?= htmlspecialchars($r['queue_name'] ?? 'unknown') ?></code></td>
                                <td><small class="text-danger"><?= htmlspecialchars(substr($r['reason'] ?? 'No reason recorded', 0, 100)) ?></small></td>
                                <td><span class="badge bg-danger"><?= (int)$r['attempts'] ?></span></td>
                                <td><small class="text-muted"><?= em_time_ago($r['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'dead-letter-queue.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
