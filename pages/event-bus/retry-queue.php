<?php
/**
 * Event Bus - Retry Queue
 * READ-ONLY. em_retry_queue: id, message_id, retry_count, next_retry_at, created_at
 * Joins em_queue_messages for context
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Retry Queue — Event Manager';
$currentPage = 'retry-queue';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$total = em_fetch("SELECT COUNT(*) as count FROM em_retry_queue")['count'] ?? 0;
$rows  = em_fetch_all("SELECT rq.*, qm.queue_name, qm.status as msg_status
    FROM em_retry_queue rq
    LEFT JOIN em_queue_messages qm ON rq.message_id = qm.id
    ORDER BY rq.next_retry_at ASC LIMIT {$offset}, {$perPage}");
$pagination = em_paginate($total, $perPage, $page);

$dueNow   = em_fetch("SELECT COUNT(*) as count FROM em_retry_queue WHERE next_retry_at <= NOW()")['count'] ?? 0;
$upcoming = em_fetch("SELECT COUNT(*) as count FROM em_retry_queue WHERE next_retry_at > NOW()")['count'] ?? 0;
$maxRetry = em_fetch("SELECT MAX(retry_count) as max FROM em_retry_queue")['max'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Retry Queue</h2>
    <p class="text-muted">Messages scheduled for automatic retry after failure</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-redo-alt"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Pending Retries</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-clock"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Due Now</div><div class="em-stat-value"><?= em_format_number($dueNow) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Scheduled</div><div class="em-stat-value"><?= em_format_number($upcoming) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-sort-numeric-up"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Max Retry Count</div><div class="em-stat-value"><?= em_format_number($maxRetry) ?></div></div>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Retry Queue <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="em-empty-state text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h4>Retry Queue is Empty</h4>
                <p class="text-muted">No messages are pending retry.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Message ID</th><th>Queue</th><th>Retry Count</th><th>Next Retry</th><th>Status</th><th>Queued</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r):
                            $isDue = $r['next_retry_at'] && strtotime($r['next_retry_at']) <= time();
                        ?>
                            <tr class="<?= $isDue ? 'table-warning' : '' ?>">
                                <td><small class="text-muted">#<?= $r['message_id'] ?></small></td>
                                <td><code><?= htmlspecialchars($r['queue_name'] ?? 'unknown') ?></code></td>
                                <td>
                                    <span class="badge bg-<?= $r['retry_count'] >= 5 ? 'danger' : ($r['retry_count'] >= 3 ? 'warning' : 'secondary') ?>">
                                        <?= (int)$r['retry_count'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($r['next_retry_at']): ?>
                                        <small class="text-<?= $isDue ? 'danger' : 'muted' ?>">
                                            <?= $isDue ? 'Overdue — ' : '' ?><?= em_time_ago($r['next_retry_at']) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= em_status_badge($r['msg_status'] ?? 'pending') ?></td>
                                <td><small class="text-muted"><?= em_time_ago($r['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'retry-queue.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
