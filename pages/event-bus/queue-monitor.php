<?php
/**
 * Event Bus - Queue Monitor
 * 
 * SAFETY: READ-ONLY. Schema verified against install.php migration.
 * em_queue_messages: id, queue_name, payload, status, attempts, created_at, processed_at
 * em_dead_letter_queue: id, original_message_id, reason, created_at
 * em_retry_queue: id, message_id, retry_count, next_retry_at, created_at
 * em_workers: id, name, queue_name, status, last_heartbeat, created_at, updated_at
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Queue Monitor — Event Manager';
$currentPage = 'queue-monitor';

// Get queue statistics (READ-ONLY)
$queueStats = [
    'total_messages' => 0,
    'pending_messages' => 0,
    'processing_messages' => 0,
    'dead_letter_messages' => 0,
    'retry_queue_messages' => 0
];

try {
    $queueStats['total_messages'] = em_table_count('em_queue_messages');
    $queueStats['pending_messages'] = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages WHERE status = 'pending'")['count'] ?? 0;
    $queueStats['processing_messages'] = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages WHERE status = 'processing'")['count'] ?? 0;
    $queueStats['dead_letter_messages'] = em_table_count('em_dead_letter_queue');
    $queueStats['retry_queue_messages'] = em_table_count('em_retry_queue');
} catch (Exception $e) {
    // Silent fail
}

// Get recent queue messages (READ-ONLY)
$recentMessages = [];
try {
    $recentMessages = em_fetch_all(
        "SELECT * FROM em_queue_messages 
         ORDER BY created_at DESC 
         LIMIT 10"
    );
} catch (Exception $e) {
    // Silent fail
}

// Get dead letter queue messages (READ-ONLY)
$deadLetterMessages = [];
try {
    $deadLetterMessages = em_fetch_all(
        "SELECT * FROM em_dead_letter_queue 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
} catch (Exception $e) {
    // Silent fail
}

// Get worker statistics (READ-ONLY)
$workerStats = [
    'total_workers' => 0,
    'active_workers' => 0,
    'idle_workers' => 0
];

try {
    $workerStats['total_workers'] = em_table_count('em_workers');
    $workerStats['active_workers'] = em_fetch("SELECT COUNT(*) as count FROM em_workers WHERE status = 'running'")['count'] ?? 0;
    $workerStats['idle_workers'] = em_fetch("SELECT COUNT(*) as count FROM em_workers WHERE status = 'stopped'")['count'] ?? 0;
} catch (Exception $e) {
    // Silent fail
}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Queue Monitor</h2>
    <p class="text-muted">Monitor message queues and worker status</p>
</div>

<!-- Queue Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Messages</div>
            <div class="em-stat-value"><?= em_format_number($queueStats['total_messages']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Pending</div>
            <div class="em-stat-value"><?= em_format_number($queueStats['pending_messages']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon info">
            <i class="fas fa-spinner"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Processing</div>
            <div class="em-stat-value"><?= em_format_number($queueStats['processing_messages']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon danger">
            <i class="fas fa-skull-crossbones"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Dead Letter Queue</div>
            <div class="em-stat-value"><?= em_format_number($queueStats['dead_letter_messages']); ?></div>
        </div>
    </div>
</div>

<!-- Worker Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="em-card">
            <div class="em-card-body text-center">
                <i class="fas fa-server fa-3x text-primary mb-3"></i>
                <h4><?= em_format_number($workerStats['total_workers']); ?></h4>
                <p class="text-muted mb-0">Total Workers</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="em-card">
            <div class="em-card-body text-center">
                <i class="fas fa-play-circle fa-3x text-success mb-3"></i>
                <h4><?= em_format_number($workerStats['active_workers']); ?></h4>
                <p class="text-muted mb-0">Active Workers</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="em-card">
            <div class="em-card-body text-center">
                <i class="fas fa-pause-circle fa-3x text-secondary mb-3"></i>
                <h4><?= em_format_number($workerStats['idle_workers']); ?></h4>
                <p class="text-muted mb-0">Idle Workers</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Queue Messages -->
<div class="em-card mb-4">
    <div class="em-card-header">
        <h5 class="mb-0">Recent Queue Messages</h5>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($recentMessages)): ?>
            <div class="em-empty-state">
                <i class="fas fa-inbox"></i>
                <h4>No Messages in Queue</h4>
                <p>The message queue is currently empty.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Queue Name</th>
                            <th>Status</th>
                            <th>Attempts</th>
                            <th>Created</th>
                            <th>Processed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMessages as $msg): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($msg['queue_name']); ?></strong></td>
                                <td><?= em_status_badge($msg['status']); ?></td>
                                <td><span class="badge bg-secondary"><?= (int)$msg['attempts']; ?></span></td>
                                <td><small class="text-muted"><?= em_time_ago($msg['created_at']); ?></small></td>
                                <td><small class="text-muted"><?= $msg['processed_at'] ? em_time_ago($msg['processed_at']) : '—'; ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Dead Letter Queue -->
<?php if (!empty($deadLetterMessages)): ?>
<div class="em-card">
    <div class="em-card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="fas fa-skull-crossbones"></i> Dead Letter Queue
        </h5>
    </div>
    <div class="em-card-body p-0">
        <div class="table-responsive">
            <table class="em-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Original Message ID</th>
                        <th>Reason</th>
                        <th>Recorded At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deadLetterMessages as $dlq): ?>
                        <tr>
                            <td><strong>#<?= $dlq['id']; ?></strong></td>
                            <td>#<?= $dlq['original_message_id']; ?></td>
                            <td><small class="text-danger"><?= htmlspecialchars(substr($dlq['reason'] ?? 'No reason recorded', 0, 80)); ?></small></td>
                            <td><small class="text-muted"><?= em_time_ago($dlq['created_at']); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Info Alert -->
<div class="alert alert-info mt-4">
    <i class="fas fa-info-circle"></i>
    <strong>Note:</strong> This is a read-only monitor. Queue management and message processing require background workers which will be configured separately.
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
