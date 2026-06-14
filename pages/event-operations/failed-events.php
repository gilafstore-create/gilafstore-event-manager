<?php
/**
 * Event Manager - Failed Events
 * 
 * SAFETY: READ-ONLY display of failed events
 * NO MODIFICATIONS to existing tables
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Failed Events — Event Manager';
$currentPage = 'failed-events';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$filterType = $_GET['type'] ?? '';
$filterReason = $_GET['reason'] ?? '';

// Build query
$where = [];
$params = [];

if ($filterType) {
    $where[] = "el.event_type = ?";
    $params[] = $filterType;
}

if ($filterReason) {
    $where[] = "el.error_message LIKE ?";
    $params[] = "%{$filterReason}%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalSql = "SELECT COUNT(*) as count FROM em_failed_events fe LEFT JOIN em_event_logs el ON fe.event_log_id = el.id $whereClause";
$total = em_fetch($totalSql, $params)['count'] ?? 0;

// Get failed events
$sql = "SELECT fe.*, el.event_id, el.event_type, el.payload, el.error_message, el.created_at AS event_created_at
        FROM em_failed_events fe
        LEFT JOIN em_event_logs el ON fe.event_log_id = el.id
        $whereClause
        ORDER BY fe.created_at DESC LIMIT {$offset}, {$perPage}";
$failedEvents = em_fetch_all($sql, $params);

// Get pagination
$pagination = em_paginate($total, $perPage, $page);

// Get unique event types
$eventTypes = em_fetch_all("SELECT DISTINCT el.event_type FROM em_failed_events fe LEFT JOIN em_event_logs el ON fe.event_log_id = el.id WHERE el.event_type IS NOT NULL ORDER BY el.event_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Failed Events</h2>
    <p class="text-muted">Monitor and troubleshoot failed event deliveries</p>
</div>

<!-- Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon danger">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Failed</div>
            <div class="em-stat-value"><?= em_format_number($total); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon warning">
            <i class="fas fa-redo"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Retry Pending</div>
            <div class="em-stat-value">
                <?php
                $retryCount = em_fetch("SELECT COUNT(*) as count FROM em_failed_events WHERE status IN ('pending', 'retrying')")['count'] ?? 0;
                echo em_format_number($retryCount);
                ?>
            </div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon secondary">
            <i class="fas fa-ban"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Max Retries Reached</div>
            <div class="em-stat-value">
                <?php
                $maxRetriesCount = em_fetch("SELECT COUNT(*) as count FROM em_failed_events WHERE status = 'abandoned'")['count'] ?? 0;
                echo em_format_number($maxRetriesCount);
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="em-form-label">Event Type</label>
                <select name="type" class="em-form-control">
                    <option value="">All Types</option>
                    <?php foreach ($eventTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type['event_type']); ?>" <?= $filterType === $type['event_type'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($type['event_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="em-form-label">Failure Reason</label>
                <input type="text" name="reason" class="em-form-control" placeholder="Search failure reason..." value="<?= htmlspecialchars($filterReason); ?>">
            </div>
            <div class="col-md-4">
                <label class="em-form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="em-btn em-btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?= em_base_url('pages/event-operations/failed-events.php'); ?>" class="em-btn em-btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Failed Events Table -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">Failed Event History</h5>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($failedEvents)): ?>
            <div class="em-empty-state">
                <i class="fas fa-check-circle text-success"></i>
                <h4>No Failed Events</h4>
                <p>All events are being delivered successfully. Great job!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Failure Reason</th>
                            <th>Retry Count</th>
                            <th>Failed At</th>
                            <th>Next Retry</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($failedEvents as $event): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($event['event_type']); ?></strong>
                                    <br>
                                    <small class="text-muted">ID: <?= htmlspecialchars($event['id']); ?></small>
                                </td>
                                <td>
                                    <span class="text-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?= htmlspecialchars(substr($event['error_message'] ?? 'No error message recorded', 0, 50)); ?>
                                        <?= strlen($event['error_message'] ?? '') > 50 ? '...' : ''; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $event['status'] === 'abandoned' ? 'danger' : 'warning'; ?>">
                                        <?= $event['retry_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_format_date($event['created_at']); ?>
                                        <br>
                                        <?= em_time_ago($event['created_at']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($event['next_retry_at'] && $event['status'] !== 'abandoned'): ?>
                                        <small class="text-muted">
                                            <?= em_format_date($event['next_retry_at']); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="em-btn em-btn-sm em-btn-secondary" 
                                            onclick='EventManager.showFailedEventDetails(<?= json_encode($event, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <?php if ($event['status'] !== 'abandoned'): ?>
                                        <button class="em-btn em-btn-sm em-btn-primary" 
                                                onclick="retryFailedEvent(<?= $event['event_log_id'] ?>)"
                                                style="margin-left: 4px;">
                                            <i class="fas fa-redo"></i> Retry
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer">
                    <?= em_render_pagination($pagination, em_base_url('pages/event-operations/failed-events.php') . '?' . http_build_query(array_filter(['type' => $filterType, 'reason' => $filterReason]))); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Failed Event Details Modal -->
<div class="modal fade" id="failedEventDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Failed Event Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="failedEventDetailsContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function retryFailedEvent(eventLogId) {
    if (!confirm('Queue this event for retry?')) return;
    
    fetch('<?= base_url('event-manager/api/event_retry.php') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ event_log_id: eventLogId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Event queued for retry');
            location.reload();
        } else {
            alert('Retry failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Request failed: ' + err.message);
    });
}

// Register AFTER footer scripts (event-manager.js) load.
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.EventManager === 'undefined' && typeof EventManager === 'undefined') {
        window.EventManager = {};
    }
    var EM = (typeof EventManager !== 'undefined') ? EventManager : window.EventManager;

    EM.showFailedEventDetails = function(event) {
        try {
    const content = `
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Event ID:</strong><br>
                ${event.id}
            </div>
            <div class="col-md-6">
                <strong>Event Type:</strong><br>
                ${event.event_type}
            </div>
            <div class="col-md-6">
                <strong>Retry Count:</strong><br>
                <span class="badge bg-${event.status === 'abandoned' ? 'danger' : 'warning'}">
                    ${event.retry_count || 0}
                </span>
            </div>
            <div class="col-md-6">
                <strong>Recorded At:</strong><br>
                ${event.created_at || 'N/A'}
            </div>
            <div class="col-12">
                <strong>Error Message:</strong><br>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    ${event.error_message || 'No error message recorded'}
                </div>
            </div>
            <div class="col-12">
                <strong>Event Payload:</strong><br>
                <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">${event.payload || 'No payload'}</pre>
            </div>
            <div class="col-12">
                <strong>Status:</strong><br>
                <span class="badge bg-${event.status === 'abandoned' ? 'danger' : (event.status === 'retrying' ? 'warning' : 'secondary')}">${event.status || 'unknown'}</span>
            </div>
            ${event.next_retry_at && event.status !== 'abandoned' ? `
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Next Retry:</strong> ${event.next_retry_at}
                </div>
            </div>
            ` : ''}
            ${event.status === 'abandoned' ? `
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="fas fa-ban"></i>
                    <strong>Abandoned:</strong> This event is no longer pending retry.
                </div>
            </div>
            ` : ''}
        </div>
    `;
    
            const contentDiv = document.getElementById('failedEventDetailsContent');
            if (!contentDiv) {
                console.error('failedEventDetailsContent div not found');
                return;
            }
            contentDiv.innerHTML = content;
            
            const modalEl = document.getElementById('failedEventDetailsModal');
            if (!modalEl) {
                console.error('failedEventDetailsModal not found');
                return;
            }
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            console.log('Modal should be visible now');
        } catch (error) {
            console.error('Error in showFailedEventDetails:', error);
            alert('Error showing failed event details: ' + error.message);
        }
    };
});
</script>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
