<?php
/**
 * Event Manager - Missing Event Detection
 * 
 * SAFETY: READ-ONLY analysis of event patterns and anomalies
 * NO MODIFICATIONS to any tables
 * ONLY SELECT queries
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Missing Event Detection — Event Manager';
$currentPage = 'missing-event-detection';

// Analyze event patterns (READ-ONLY)
$eventAnalysis = [
    'total_event_types' => 0,
    'active_event_types' => 0,
    'events_last_24h' => 0,
    'events_last_7d' => 0,
    'silent_event_types' => []
];

try {
    // Get total event types
    $eventAnalysis['total_event_types'] = em_table_count('em_event_definitions');
    $eventAnalysis['active_event_types'] = em_fetch("SELECT COUNT(*) as count FROM em_event_definitions WHERE status = 'active'")['count'] ?? 0;
    
    // Get recent event activity
    $eventAnalysis['events_last_24h'] = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;
    $eventAnalysis['events_last_7d'] = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0;
    
    // Detect silent event types (defined but no recent activity)
    $eventAnalysis['silent_event_types'] = em_fetch_all(
        "SELECT ed.id, ed.name, ed.description, ed.created_at,
                (SELECT COUNT(*) FROM em_event_logs WHERE event_type = ed.name) as total_events,
                (SELECT MAX(created_at) FROM em_event_logs WHERE event_type = ed.name) as last_event_at
         FROM em_event_definitions ed
         WHERE ed.status = 'active'
         AND NOT EXISTS (
             SELECT 1 FROM em_event_logs 
             WHERE event_type = ed.name 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         )
         ORDER BY ed.created_at DESC
         LIMIT 20"
    );
} catch (Exception $e) {
    // Silent fail
}

// Get event frequency analysis (READ-ONLY)
$eventFrequency = [];
try {
    $eventFrequency = em_fetch_all(
        "SELECT event_type, 
                COUNT(*) as event_count,
                MAX(created_at) as last_event,
                MIN(created_at) as first_event,
                COUNT(DISTINCT DATE(created_at)) as active_days
         FROM em_event_logs
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY event_type
         ORDER BY event_count DESC
         LIMIT 10"
    );
} catch (Exception $e) {
    // Silent fail
}

// Get anomaly detection (READ-ONLY)
$anomalies = [];
try {
    // Detect sudden drops in event frequency
    $anomalies = em_fetch_all(
        "SELECT event_type,
                COUNT(*) as recent_count,
                (SELECT COUNT(*) FROM em_event_logs el2 
                 WHERE el2.event_type = el.event_type 
                 AND el2.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                 AND el2.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)) as previous_count
         FROM em_event_logs el
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY event_type
         HAVING recent_count > 0 AND previous_count > 0
         ORDER BY event_type
         LIMIT 20"
    );
} catch (Exception $e) {
    // Silent fail
}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Missing Event Detection</h2>
    <p class="text-muted">Analyze event patterns and detect anomalies</p>
</div>

<!-- Analysis Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-list"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Event Types Defined</div>
            <div class="em-stat-value"><?= em_format_number($eventAnalysis['total_event_types']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Active Event Types</div>
            <div class="em-stat-value"><?= em_format_number($eventAnalysis['active_event_types']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon info">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Events (24h)</div>
            <div class="em-stat-value"><?= em_format_number($eventAnalysis['events_last_24h']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon warning">
            <i class="fas fa-calendar-week"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Events (7 days)</div>
            <div class="em-stat-value"><?= em_format_number($eventAnalysis['events_last_7d']); ?></div>
        </div>
    </div>
</div>

<!-- Silent Event Types -->
<div class="em-card mb-4">
    <div class="em-card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-volume-mute"></i> Silent Event Types
            <span class="badge bg-dark"><?= count($eventAnalysis['silent_event_types']); ?></span>
        </h5>
    </div>
    <div class="em-card-body">
        <?php if (empty($eventAnalysis['silent_event_types'])): ?>
            <div class="alert alert-success mb-0">
                <i class="fas fa-check-circle"></i>
                <strong>All Good!</strong> All active event types have recent activity.
            </div>
        <?php else: ?>
            <p class="text-muted mb-3">
                These event types are defined and active but have not fired in the last 7 days.
            </p>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Description</th>
                            <th>Total Events</th>
                            <th>Last Event</th>
                            <th>Days Silent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventAnalysis['silent_event_types'] as $silent): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($silent['name']); ?></strong>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(substr($silent['description'] ?? '', 0, 50)); ?>
                                        <?= strlen($silent['description'] ?? '') > 50 ? '...' : ''; ?>
                                    </small>
                                </td>
                                <td>
                                    <?= em_format_number($silent['total_events']); ?>
                                </td>
                                <td>
                                    <?php if ($silent['last_event_at']): ?>
                                        <small class="text-muted">
                                            <?= em_format_date($silent['last_event_at']); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($silent['last_event_at']): ?>
                                        <span class="badge bg-warning">
                                            <?= floor((time() - strtotime($silent['last_event_at'])) / 86400); ?> days
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Event Frequency Analysis -->
<div class="em-card mb-4">
    <div class="em-card-header">
        <h5 class="mb-0">Event Frequency (Last 30 Days)</h5>
    </div>
    <div class="em-card-body">
        <?php if (empty($eventFrequency)): ?>
            <div class="em-empty-state">
                <i class="fas fa-chart-line"></i>
                <h4>No Event Data</h4>
                <p>No events have been recorded in the last 30 days.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Total Events</th>
                            <th>Active Days</th>
                            <th>Avg/Day</th>
                            <th>Last Event</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventFrequency as $freq): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($freq['event_type']); ?></strong>
                                </td>
                                <td>
                                    <?= em_format_number($freq['event_count']); ?>
                                </td>
                                <td>
                                    <?= $freq['active_days']; ?> days
                                </td>
                                <td>
                                    <?= number_format($freq['event_count'] / max(1, $freq['active_days']), 1); ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_time_ago($freq['last_event']); ?>
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

<!-- Anomaly Detection -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">
            <i class="fas fa-exclamation-triangle"></i> Anomaly Detection
        </h5>
    </div>
    <div class="em-card-body">
        <p class="text-muted mb-3">
            Comparing event frequency: Last 7 days vs. Previous 7 days
        </p>
        <?php if (empty($anomalies)): ?>
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle"></i>
                Insufficient data for anomaly detection. Need at least 14 days of event history.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Previous Week</th>
                            <th>Recent Week</th>
                            <th>Change</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anomalies as $anomaly): ?>
                            <?php
                            $change = $anomaly['previous_count'] > 0 
                                ? (($anomaly['recent_count'] - $anomaly['previous_count']) / $anomaly['previous_count']) * 100 
                                : 0;
                            $isAnomaly = abs($change) > 50; // More than 50% change
                            ?>
                            <tr class="<?= $isAnomaly ? 'table-warning' : ''; ?>">
                                <td>
                                    <strong><?= htmlspecialchars($anomaly['event_type']); ?></strong>
                                </td>
                                <td>
                                    <?= em_format_number($anomaly['previous_count']); ?>
                                </td>
                                <td>
                                    <?= em_format_number($anomaly['recent_count']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $change > 0 ? 'success' : ($change < 0 ? 'danger' : 'secondary'); ?>">
                                        <?= $change > 0 ? '+' : ''; ?><?= number_format($change, 1); ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isAnomaly): ?>
                                        <i class="fas fa-exclamation-triangle text-warning"></i> Anomaly
                                    <?php else: ?>
                                        <i class="fas fa-check-circle text-success"></i> Normal
                                    <?php endif; ?>
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
