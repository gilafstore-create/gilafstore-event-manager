<?php
/**
 * Event Manager - Retry History
 * Shows failed events and their retry context.
 * No queue workers — read-only view of failed em_event_logs.
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Retry History — Event Manager';
$currentPage = 'crm-retry-history';

// Failed events from em_event_logs
$failedEvents = [];
$failedCount  = 0;
try {
    $failedCount = (int)(em_fetch("SELECT COUNT(*) AS c FROM em_event_logs WHERE status = 'failed'")['c'] ?? 0);
    $failedEvents = em_fetch_all(
        "SELECT l.*, s.name AS source_name
         FROM em_event_logs l
         LEFT JOIN em_event_sources s ON s.id = l.source_id
         WHERE l.status = 'failed'
         ORDER BY l.created_at DESC
         LIMIT 50"
    );
} catch (Exception $e) {
    error_log('EM retry-history: ' . $e->getMessage());
}

// Retry queue counts (em_queue_messages if table exists)
$queueStats = ['pending' => 0, 'retrying' => 0, 'dead' => 0];
try {
    $queueStats['pending']  = (int)(em_fetch("SELECT COUNT(*) AS c FROM em_queue_messages WHERE status = 'pending'")['c'] ?? 0);
    $queueStats['retrying'] = (int)(em_fetch("SELECT COUNT(*) AS c FROM em_queue_messages WHERE status = 'retrying'")['c'] ?? 0);
    $queueStats['dead']     = (int)(em_fetch("SELECT COUNT(*) AS c FROM em_queue_messages WHERE status = 'dead'")['c'] ?? 0);
} catch (Exception $e) {
    // Queue table may not be active yet
}

// Failed events by type
$failedByType = [];
try {
    $failedByType = em_fetch_all(
        "SELECT event_type, COUNT(*) AS cnt, MAX(created_at) AS last_fail
         FROM em_event_logs WHERE status = 'failed'
         GROUP BY event_type ORDER BY cnt DESC"
    );
} catch (Exception $e) {}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Retry History</h2>
    <p class="text-muted">Failed event deliveries and retry queue status</p>
</div>

<!-- Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon danger">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Failed</div>
            <div class="em-stat-value"><?= em_format_number($failedCount); ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Queue: Pending</div>
            <div class="em-stat-value"><?= em_format_number($queueStats['pending']); ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info">
            <i class="fas fa-redo-alt"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Queue: Retrying</div>
            <div class="em-stat-value"><?= em_format_number($queueStats['retrying']); ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon secondary">
            <i class="fas fa-skull-crossbones"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Dead Letters</div>
            <div class="em-stat-value"><?= em_format_number($queueStats['dead']); ?></div>
        </div>
    </div>
</div>

<!-- Queue Worker Status -->
<div class="alert alert-success mb-4">
    <i class="fas fa-check-circle me-2"></i>
    <strong>Queue Processing Active.</strong>
    Automated retry processing is enabled. Failed events are automatically retried with exponential backoff.
    <a href="<?= em_base_url('pages/event-operations/queue-status.php'); ?>" class="alert-link">View Queue Status</a>
</div>

<?php if (!empty($failedByType)): ?>
<!-- Failed by Type -->
<div class="em-card mb-4">
    <div class="em-card-header">
        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Failures by Event Type</h5>
    </div>
    <div class="em-card-body p-0">
        <div class="table-responsive">
            <table class="em-table">
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Failures</th>
                        <th>Last Failure</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($failedByType as $row): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($row['event_type']); ?></code></td>
                            <td>
                                <span class="badge bg-danger"><?= em_format_number($row['cnt']); ?></span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= em_time_ago($row['last_fail']); ?>
                                    <br><?= em_format_date($row['last_fail']); ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Failed Events List -->
<div class="em-card">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Failed Events (last 50)</h5>
        <?php if ($failedCount === 0): ?>
            <span class="badge bg-success"><i class="fas fa-check me-1"></i>All Clear</span>
        <?php endif; ?>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($failedEvents)): ?>
            <div class="em-empty-state">
                <i class="fas fa-check-circle text-success"></i>
                <h4 class="text-success">No Failed Events</h4>
                <p>All event deliveries are successful.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Event Type</th>
                            <th>Source</th>
                            <th>Error / Payload</th>
                            <th>Failed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($failedEvents as $evt): ?>
                            <?php
                            $payload = json_decode($evt['payload'] ?? '{}', true);
                            $errMsg  = $evt['error_message'] ?? '';
                            if (!$errMsg && is_array($payload)) {
                                $errMsg = $payload['error'] ?? $payload['message'] ?? '';
                            }
                            ?>
                            <tr>
                                <td><small class="text-muted"><?= $evt['id']; ?></small></td>
                                <td><code><?= htmlspecialchars($evt['event_type']); ?></code></td>
                                <td><small><?= htmlspecialchars($evt['source_name'] ?? 'N/A'); ?></small></td>
                                <td>
                                    <small class="text-danger font-monospace">
                                        <?= htmlspecialchars(substr($errMsg ?: json_encode($payload), 0, 80)); ?>
                                    </small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_time_ago($evt['created_at']); ?>
                                        <br><?= em_format_date($evt['created_at']); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
