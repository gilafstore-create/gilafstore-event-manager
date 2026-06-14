<?php
/**
 * Event Manager - Sync Status
 * Shows CRM_SYNC_STARTED / CRM_SYNC_COMPLETED event activity and
 * a read-only summary of recent event log activity per source.
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Sync Status — Event Manager';
$currentPage = 'crm-sync-status';

// CRM sync events from em_event_logs
$syncEvents = [];
try {
    $syncEvents = em_fetch_all(
        "SELECT * FROM em_event_logs
         WHERE event_type IN ('CRM_SYNC_STARTED','CRM_SYNC_COMPLETED')
         ORDER BY created_at DESC LIMIT 20"
    );
} catch (Exception $e) {}

// Latest event per type summary
$eventSummary = [];
try {
    $eventSummary = em_fetch_all(
        "SELECT event_type,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successful,
                SUM(CASE WHEN status = 'failed'  THEN 1 ELSE 0 END) AS failed,
                MAX(created_at) AS last_seen
         FROM em_event_logs
         GROUP BY event_type
         ORDER BY last_seen DESC"
    );
} catch (Exception $e) {}

// Event source activity
$sourceActivity = [];
try {
    $sourceActivity = em_fetch_all(
        "SELECT s.name AS source_name, s.type AS source_type,
                COUNT(l.id) AS event_count,
                MAX(l.created_at) AS last_event
         FROM em_event_sources s
         LEFT JOIN em_event_logs l ON l.source_id = s.id
         GROUP BY s.id, s.name, s.type
         ORDER BY last_event DESC"
    );
} catch (Exception $e) {}

// Overall health
$totalEvents   = 0;
$successEvents = 0;
$failedEvents  = 0;
try {
    $row = em_fetch("SELECT COUNT(*) AS t,
                     SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) AS s,
                     SUM(CASE WHEN status='failed'  THEN 1 ELSE 0 END) AS f
                     FROM em_event_logs");
    $totalEvents   = (int)($row['t'] ?? 0);
    $successEvents = (int)($row['s'] ?? 0);
    $failedEvents  = (int)($row['f'] ?? 0);
} catch (Exception $e) {}

$healthPct = $totalEvents > 0 ? round(($successEvents / $totalEvents) * 100) : 100;

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Sync Status</h2>
    <p class="text-muted">Live overview of event pipeline health and CRM synchronisation activity</p>
</div>

<!-- Health Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-stream"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Events</div>
            <div class="em-stat-value"><?= em_format_number($totalEvents); ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Successful</div>
            <div class="em-stat-value"><?= em_format_number($successEvents); ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Failed</div>
            <div class="em-stat-value"><?= em_format_number($failedEvents); ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon <?= $healthPct >= 90 ? 'success' : ($healthPct >= 70 ? 'warning' : 'danger'); ?>">
            <i class="fas fa-heartbeat"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Health Score</div>
            <div class="em-stat-value"><?= $healthPct; ?>%</div>
        </div>
    </div>
</div>

<!-- Pipeline Health Bar -->
<div class="em-card mb-4">
    <div class="em-card-header">
        <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Pipeline Health</h5>
    </div>
    <div class="em-card-body">
        <div class="d-flex justify-content-between mb-1">
            <small>Success Rate</small>
            <small class="fw-bold"><?= $healthPct; ?>%</small>
        </div>
        <div class="progress" style="height: 12px;">
            <div class="progress-bar bg-<?= $healthPct >= 90 ? 'success' : ($healthPct >= 70 ? 'warning' : 'danger'); ?>"
                 style="width: <?= $healthPct; ?>%"></div>
        </div>
        <small class="text-muted mt-1 d-block">
            <?= $successEvents; ?> successful / <?= $totalEvents; ?> total events
        </small>
    </div>
</div>

<!-- Event Type Summary -->
<div class="em-card mb-4">
    <div class="em-card-header">
        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Event Activity by Type</h5>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($eventSummary)): ?>
            <div class="em-empty-state">
                <i class="fas fa-inbox"></i>
                <h4>No Event Data</h4>
                <p>No events have been logged yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Total</th>
                            <th>Successful</th>
                            <th>Failed</th>
                            <th>Success Rate</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventSummary as $row): ?>
                            <?php
                            $rate = $row['total'] > 0 ? round(($row['successful'] / $row['total']) * 100) : 100;
                            $rateClass = $rate >= 90 ? 'success' : ($rate >= 70 ? 'warning' : 'danger');
                            ?>
                            <tr>
                                <td><code><?= htmlspecialchars($row['event_type']); ?></code></td>
                                <td><?= em_format_number($row['total']); ?></td>
                                <td><span class="text-success"><?= em_format_number($row['successful']); ?></span></td>
                                <td><span class="text-danger"><?= em_format_number($row['failed']); ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px; min-width:60px;">
                                            <div class="progress-bar bg-<?= $rateClass; ?>" style="width:<?= $rate; ?>%"></div>
                                        </div>
                                        <small class="text-<?= $rateClass; ?>"><?= $rate; ?>%</small>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_time_ago($row['last_seen']); ?>
                                        <br><?= em_format_date($row['last_seen']); ?>
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

<!-- Source Activity -->
<div class="em-card mb-4">
    <div class="em-card-header">
        <h5 class="mb-0"><i class="fas fa-plug me-2"></i>Event Sources Activity</h5>
    </div>
    <div class="em-card-body p-0">
        <div class="table-responsive">
            <table class="em-table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Type</th>
                        <th>Events Fired</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sourceActivity as $src): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($src['source_name']); ?></strong></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($src['source_type']); ?></span></td>
                            <td><?= em_format_number($src['event_count'] ?? 0); ?></td>
                            <td>
                                <?php if ($src['last_event']): ?>
                                    <small class="text-muted">
                                        <?= em_time_ago($src['last_event']); ?>
                                        <br><?= em_format_date($src['last_event']); ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">No activity</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CRM Sync Events -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0"><i class="fas fa-sync-alt me-2"></i>CRM Sync Events (Recent 20)</h5>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($syncEvents)): ?>
            <div class="em-empty-state">
                <i class="fas fa-sync-alt"></i>
                <h4>No CRM Sync Events</h4>
                <p>CRM_SYNC_STARTED / CRM_SYNC_COMPLETED events will appear here when syncs run.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Status</th>
                            <th>Payload</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($syncEvents as $evt): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($evt['event_type']); ?></code></td>
                                <td><?= em_status_badge($evt['status']); ?></td>
                                <td>
                                    <small class="text-muted font-monospace">
                                        <?= htmlspecialchars(substr($evt['payload'] ?? '', 0, 80)); ?>
                                        <?= strlen($evt['payload'] ?? '') > 80 ? '…' : ''; ?>
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
