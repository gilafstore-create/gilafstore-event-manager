<?php
/**
 * Event Manager - Event Logs
 * 
 * SAFETY: READ-ONLY display of existing event data
 * NO MODIFICATIONS to existing tables
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Logs — Event Manager';
$currentPage = 'logs';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterDate = $_GET['date'] ?? '';

// Build query
$where = [];
$params = [];

if ($filterType) {
    $where[] = "event_type = ?";
    $params[] = $filterType;
}

if ($filterStatus) {
    $where[] = "status = ?";
    $params[] = $filterStatus;
}

if ($filterDate) {
    $where[] = "DATE(created_at) = ?";
    $params[] = $filterDate;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalSql = "SELECT COUNT(*) as count FROM em_event_logs $whereClause";
$total = em_fetch($totalSql, $params)['count'] ?? 0;

// Get events
$sql = "SELECT * FROM em_event_logs $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}";
$events = em_fetch_all($sql, $params);

// Get pagination
$pagination = em_paginate($total, $perPage, $page);

// Get unique event types for filter
$eventTypes = em_fetch_all("SELECT DISTINCT event_type FROM em_event_logs ORDER BY event_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Event Logs</h2>
    <p class="text-muted">View all event activity and history</p>
</div>

<!-- Filters -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label class="em-form-label">Status</label>
                <select name="status" class="em-form-control">
                    <option value="">All Status</option>
                    <option value="success" <?= $filterStatus === 'success' ? 'selected' : ''; ?>>Success</option>
                    <option value="failed" <?= $filterStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="em-form-label">Date</label>
                <input type="date" name="date" class="em-form-control" value="<?= htmlspecialchars($filterDate); ?>">
            </div>
            <div class="col-md-3">
                <label class="em-form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="em-btn em-btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?= em_base_url('pages/event-operations/logs.php'); ?>" class="em-btn em-btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-stream"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Events</div>
            <div class="em-stat-value"><?= em_format_number($total); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Successful</div>
            <div class="em-stat-value">
                <?php
                $successCount = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE status = 'success'")['count'] ?? 0;
                echo em_format_number($successCount);
                ?>
            </div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon danger">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Failed</div>
            <div class="em-stat-value">
                <?php
                $failedCount = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE status = 'failed'")['count'] ?? 0;
                echo em_format_number($failedCount);
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Events Table -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">Event History</h5>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($events)): ?>
            <div class="em-empty-state">
                <i class="fas fa-inbox"></i>
                <h4>No Events Found</h4>
                <p>No events match your current filters. Try adjusting your search criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Timestamp</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($event['event_type']); ?></strong>
                                </td>
                                <td>
                                    <?= em_status_badge($event['status']); ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($event['source'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($event['destination'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_format_date($event['created_at']); ?>
                                        <br>
                                        <span class="text-muted"><?= em_time_ago($event['created_at']); ?></span>
                                    </small>
                                </td>
                                <td>
                                    <button class="em-btn em-btn-sm em-btn-secondary" 
                                            onclick='EventManager.showEventDetails(<?= json_encode($event, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer">
                    <?= em_render_pagination($pagination, em_base_url('pages/event-operations/logs.php') . '?' . http_build_query(array_filter(['type' => $filterType, 'status' => $filterStatus, 'date' => $filterDate]))); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="eventDetailsContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Register showEventDetails AFTER all footer scripts (event-manager.js) have loaded.
// DOMContentLoaded fires once the document + synchronous scripts in the body are parsed,
// so EventManager (defined in event-manager.js) is guaranteed to exist here.
document.addEventListener('DOMContentLoaded', function () {
    // Fallback: if event-manager.js failed to load, create the namespace so View still works.
    if (typeof window.EventManager === 'undefined' && typeof EventManager === 'undefined') {
        window.EventManager = {};
    }
    var EM = (typeof EventManager !== 'undefined') ? EventManager : window.EventManager;

    EM.showEventDetails = function(event) {
        try {
            const content = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Event Type:</strong><br>
                        ${event.event_type || 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-${event.status === 'success' ? 'success' : event.status === 'failed' ? 'danger' : 'warning'}">
                            ${event.status || 'unknown'}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Source:</strong><br>
                        ${event.source || 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Destination:</strong><br>
                        ${event.destination || 'N/A'}
                    </div>
                    <div class="col-12">
                        <strong>Payload:</strong><br>
                        <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">${event.payload || 'No payload'}</pre>
                    </div>
                    <div class="col-12">
                        <strong>Response:</strong><br>
                        <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">${event.response || 'No response'}</pre>
                    </div>
                    <div class="col-md-6">
                        <strong>Created:</strong><br>
                        ${event.created_at || 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Duration:</strong><br>
                        ${event.duration_ms ? event.duration_ms + ' ms' : 'N/A'}
                    </div>
                </div>
            `;
            
            const contentDiv = document.getElementById('eventDetailsContent');
            if (!contentDiv) {
                console.error('eventDetailsContent div not found');
                return;
            }
            
            contentDiv.innerHTML = content;
            
            const modalEl = document.getElementById('eventDetailsModal');
            if (!modalEl) {
                console.error('eventDetailsModal not found');
                return;
            }
            
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            console.log('Modal should be visible now');
        } catch (error) {
            console.error('Error in showEventDetails:', error);
            alert('Error showing event details: ' + error.message);
        }
    };
});
</script>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
