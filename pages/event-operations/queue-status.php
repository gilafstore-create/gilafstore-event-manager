<?php
/**
 * Event Manager - Queue Status
 * 
 * SAFETY: READ-ONLY monitoring of queue processing
 * Shows queue stats, recent messages, and worker health
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';
require_once __DIR__ . '/../../includes/em_queue.php';

$pageTitle = 'Queue Status — Event Manager';
$currentPage = 'queue-status';

// Get queue statistics
$stats = em_get_queue_stats();

// Get recent queue messages
$recentMessages = [];
try {
    $recentMessages = em_fetch_all(
        "SELECT * FROM em_queue_messages 
         ORDER BY created_at DESC 
         LIMIT 50"
    );
} catch (Exception $e) {
    // Silent fail
}

// Calculate health metrics
$healthScore = 100;
if ($stats['total'] > 0) {
    $failureRate = ($stats['failed'] + $stats['dead_letter']) / $stats['total'];
    $healthScore = max(0, 100 - ($failureRate * 100));
}

$healthStatus = $healthScore >= 90 ? 'healthy' : ($healthScore >= 70 ? 'warning' : 'critical');
$healthColor = $healthScore >= 90 ? '#10B981' : ($healthScore >= 70 ? '#F59E0B' : '#EF4444');

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <div>
        <h1 class="em-page-title">Queue Status</h1>
        <p class="em-page-subtitle">Monitor event retry queue and worker health</p>
    </div>
</div>

<!-- Queue Health -->
<div class="em-card" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: <?= $healthColor ?>; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-heartbeat" style="font-size: 28px; color: #fff;"></i>
        </div>
        <div>
            <h3 style="margin: 0 0 4px; font-size: 1.1rem; color: #1f2937;">Queue Health</h3>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 2rem; font-weight: 700; color: <?= $healthColor ?>;"><?= number_format($healthScore, 1) ?>%</span>
                <span class="em-badge" style="background: <?= $healthColor ?>; color: #fff; text-transform: uppercase; font-size: 0.75rem; padding: 4px 10px;">
                    <?= $healthStatus ?>
                </span>
            </div>
        </div>
    </div>
    
    <div style="height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden;">
        <div style="height: 100%; width: <?= $healthScore ?>%; background: <?= $healthColor ?>; transition: width 0.3s ease;"></div>
    </div>
</div>

<!-- Queue Statistics -->
<div class="em-stats-grid" style="margin-bottom: 24px;">
    <div class="em-stat-card">
        <div class="em-stat-icon" style="background: #3B82F6;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="em-stat-content">
            <div class="em-stat-label">Pending</div>
            <div class="em-stat-value"><?= number_format($stats['pending']) ?></div>
        </div>
    </div>

    <div class="em-stat-card">
        <div class="em-stat-icon" style="background: #8B5CF6;">
            <i class="fas fa-spinner"></i>
        </div>
        <div class="em-stat-content">
            <div class="em-stat-label">Processing</div>
            <div class="em-stat-value"><?= number_format($stats['processing']) ?></div>
        </div>
    </div>

    <div class="em-stat-card">
        <div class="em-stat-icon" style="background: #10B981;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="em-stat-content">
            <div class="em-stat-label">Completed</div>
            <div class="em-stat-value"><?= number_format($stats['completed']) ?></div>
        </div>
    </div>

    <div class="em-stat-card">
        <div class="em-stat-icon" style="background: #EF4444;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="em-stat-content">
            <div class="em-stat-label">Failed</div>
            <div class="em-stat-value"><?= number_format($stats['failed']) ?></div>
        </div>
    </div>

    <div class="em-stat-card">
        <div class="em-stat-icon" style="background: #6B7280;">
            <i class="fas fa-skull-crossbones"></i>
        </div>
        <div class="em-stat-content">
            <div class="em-stat-label">Dead Letter</div>
            <div class="em-stat-value"><?= number_format($stats['dead_letter']) ?></div>
        </div>
    </div>

    <div class="em-stat-card">
        <div class="em-stat-icon" style="background: #1f2937;">
            <i class="fas fa-database"></i>
        </div>
        <div class="em-stat-content">
            <div class="em-stat-label">Total Messages</div>
            <div class="em-stat-value"><?= number_format($stats['total']) ?></div>
        </div>
    </div>
</div>

<!-- Worker Configuration -->
<div class="em-card" style="margin-bottom: 24px;">
    <h3 style="margin: 0 0 16px; font-size: 1rem; color: #1f2937; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-cog"></i> Worker Configuration
    </h3>
    
    <div style="background: #f9fafb; border-radius: 8px; padding: 16px; border-left: 4px solid #3B82F6;">
        <h4 style="margin: 0 0 12px; font-size: 0.9rem; color: #374151;">Cron Job Setup</h4>
        <p style="margin: 0 0 8px; font-size: 0.85rem; color: #6b7280;">Run the queue worker every 5 minutes:</p>
        <code style="display: block; background: #1f2937; color: #10B981; padding: 12px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 0.85rem; overflow-x: auto;">
            */5 * * * * php <?= realpath(__DIR__ . '/../../workers/queue_worker.php') ?> >> /var/log/em_queue.log 2>&1
        </code>
    </div>
    
    <div style="margin-top: 16px; padding: 12px; background: #FEF3C7; border-radius: 6px; border-left: 4px solid #F59E0B;">
        <p style="margin: 0; font-size: 0.85rem; color: #92400E; display: flex; align-items: start; gap: 8px;">
            <i class="fas fa-info-circle" style="margin-top: 2px;"></i>
            <span><strong>Note:</strong> The queue worker is a background process. Manual retry is available on the Failed Events page.</span>
        </p>
    </div>
</div>

<!-- Recent Queue Messages -->
<div class="em-card">
    <h3 style="margin: 0 0 16px; font-size: 1rem; color: #1f2937; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-list"></i> Recent Queue Messages (Last 50)
    </h3>
    
    <?php if (empty($recentMessages)): ?>
        <div class="em-empty-state">
            <i class="fas fa-inbox"></i>
            <p>No Queue Messages</p>
            <span>The retry queue is empty. All events are processing normally.</span>
        </div>
    <?php else: ?>
        <div class="em-table-container">
            <table class="em-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Queue</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Retry Count</th>
                        <th>Scheduled</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentMessages as $msg): 
                        $statusClass = [
                            'pending' => 'em-badge-warning',
                            'processing' => 'em-badge-info',
                            'completed' => 'em-badge-success',
                            'failed' => 'em-badge-danger',
                            'dead_letter' => 'em-badge-dark'
                        ][$msg['status']] ?? 'em-badge-secondary';
                    ?>
                        <tr>
                            <td><code>#<?= $msg['id'] ?></code></td>
                            <td><?= htmlspecialchars($msg['queue_name']) ?></td>
                            <td><span class="em-badge <?= $statusClass ?>"><?= strtoupper($msg['status']) ?></span></td>
                            <td><?= $msg['priority'] ?></td>
                            <td><?= $msg['retry_count'] ?? 0 ?></td>
                            <td><?= em_format_datetime($msg['scheduled_at']) ?></td>
                            <td><?= em_format_datetime($msg['created_at']) ?></td>
                            <td>
                                <?php if ($msg['status'] === 'failed' || $msg['status'] === 'dead_letter'): ?>
                                    <button class="em-btn-sm em-btn-primary" onclick="retryMessage(<?= $msg['id'] ?>)">
                                        <i class="fas fa-redo"></i> Retry
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function retryMessage(messageId) {
    if (!confirm('Retry this queue message?')) return;
    
    fetch('<?= base_url('event-manager/api/queue_retry.php') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message_id: messageId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Message queued for retry');
            location.reload();
        } else {
            alert('Retry failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Request failed: ' + err.message);
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
