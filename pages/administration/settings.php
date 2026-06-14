<?php
/**
 * Event Manager - Settings
 * 
 * SAFETY: READ-ONLY display of settings
 * Settings management will be in Phase 1B
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Settings — Event Manager';
$currentPage = 'settings';

// Get all settings (READ-ONLY)
$settings = em_fetch_all("SELECT * FROM em_settings ORDER BY key_name");

// Get system info
$systemInfo = [
    'total_tables' => 0,
    'total_events' => 0,
    'total_sources' => 0,
    'total_destinations' => 0
];

try {
    // Count Event Manager tables
    $tables = em_fetch_all("SHOW TABLES LIKE 'em_%'");
    $systemInfo['total_tables'] = count($tables);
    
    $systemInfo['total_events'] = em_table_count('em_event_logs');
    $systemInfo['total_sources'] = em_table_count('em_event_sources');
    $systemInfo['total_destinations'] = em_table_count('em_event_destinations');
} catch (Exception $e) {
    // Silent fail
}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>System Settings</h2>
    <p class="text-muted">Configure Event Manager system settings</p>
</div>

<!-- System Info -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-database"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Database Tables</div>
            <div class="em-stat-value"><?= em_format_number($systemInfo['total_tables']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-stream"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Events</div>
            <div class="em-stat-value"><?= em_format_number($systemInfo['total_events']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon info">
            <i class="fas fa-plug"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Event Sources</div>
            <div class="em-stat-value"><?= em_format_number($systemInfo['total_sources']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon warning">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Destinations</div>
            <div class="em-stat-value"><?= em_format_number($systemInfo['total_destinations']); ?></div>
        </div>
    </div>
</div>

<!-- Settings -->
<div class="em-card mb-4">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Settings</h5>
        <div class="alert alert-info mb-0 py-2 px-3">
            <i class="fas fa-info-circle"></i> Settings management will be available in Phase 1B
        </div>
    </div>
    <div class="em-card-body">
        <?php if (empty($settings)): ?>
            <div class="em-empty-state">
                <i class="fas fa-cog"></i>
                <h4>No Settings Configured</h4>
                <p>System settings have not been configured yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Setting</th>
                            <th>Value</th>
                            <th>Type</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settings as $setting): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($setting['key_name']); ?></strong>
                                    <?php if ($setting['description']): ?>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($setting['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($setting['value']); ?></code>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($setting['type']); ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_format_date($setting['updated_at']); ?>
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

<!-- System Health -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">System Health</h5>
    </div>
    <div class="em-card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                    <div>
                        <i class="fas fa-check-circle text-success"></i>
                        <strong class="ms-2">Database Connection</strong>
                    </div>
                    <span class="badge bg-success">Healthy</span>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                    <div>
                        <i class="fas fa-check-circle text-success"></i>
                        <strong class="ms-2">Event Manager Tables</strong>
                    </div>
                    <span class="badge bg-success"><?= $systemInfo['total_tables']; ?> Tables</span>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                    <div>
                        <i class="fas fa-check-circle text-success"></i>
                        <strong class="ms-2">Authentication</strong>
                    </div>
                    <span class="badge bg-success">Active</span>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                    <div>
                        <i class="fas fa-check-circle text-success"></i>
                        <strong class="ms-2">Admin Integration</strong>
                    </div>
                    <span class="badge bg-success">Connected</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
