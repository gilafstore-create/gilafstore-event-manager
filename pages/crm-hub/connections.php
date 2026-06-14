<?php
/**
 * Event Manager - CRM Connections
 * 
 * SAFETY: READ-ONLY display of CRM connections and webhook deliveries
 * NO MODIFICATIONS to existing CRM tables
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'CRM Connections — Event Manager';
$currentPage = 'crm-connections';

// Get CRM connections from Event Manager
$connections = [];
try {
    $connections = em_fetch_all("SELECT * FROM em_crm_connections ORDER BY created_at DESC");
} catch (Exception $e) {
    // Table may not exist yet (pre-migration) — degrade to empty state.
    error_log('EM_CRM connections page: ' . $e->getMessage());
}

// Get webhook delivery stats from existing Gilaf Store CRM tables (READ-ONLY)
$webhookStats = [
    'total_deliveries' => 0,
    'successful' => 0,
    'failed' => 0,
    'pending' => 0
];

try {
    // READ-ONLY: Get webhook delivery stats from existing table
    $webhookStats['total_deliveries'] = em_fetch("SELECT COUNT(*) as count FROM crm_webhook_deliveries")['count'] ?? 0;
    $webhookStats['successful'] = em_fetch("SELECT COUNT(*) as count FROM crm_webhook_deliveries WHERE status = 'success'")['count'] ?? 0;
    $webhookStats['failed'] = em_fetch("SELECT COUNT(*) as count FROM crm_webhook_deliveries WHERE status = 'failed'")['count'] ?? 0;
    $webhookStats['pending'] = em_fetch("SELECT COUNT(*) as count FROM crm_webhook_deliveries WHERE status = 'pending'")['count'] ?? 0;
} catch (Exception $e) {
    // Silent fail if table doesn't exist
}

// Get recent webhook deliveries (READ-ONLY)
$recentDeliveries = [];
try {
    $recentDeliveries = em_fetch_all(
        "SELECT * FROM crm_webhook_deliveries 
         ORDER BY created_at DESC 
         LIMIT 10"
    );
} catch (Exception $e) {
    // Silent fail
}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>CRM Connections</h2>
    <p class="text-muted">Manage CRM integrations and webhook connections</p>
</div>

<!-- Webhook Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-plug"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Deliveries</div>
            <div class="em-stat-value"><?= em_format_number($webhookStats['total_deliveries']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Successful</div>
            <div class="em-stat-value"><?= em_format_number($webhookStats['successful']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon danger">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Failed</div>
            <div class="em-stat-value"><?= em_format_number($webhookStats['failed']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Pending</div>
            <div class="em-stat-value"><?= em_format_number($webhookStats['pending']); ?></div>
        </div>
    </div>
</div>

<!-- CRM Connections -->
<div class="em-card mb-4">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">CRM Connections</h5>
        <a href="<?= em_base_url('pages/crm-hub/manage-connections.php'); ?>" class="em-btn em-btn-primary em-btn-sm">
            <i class="fas fa-cog"></i> Manage Connections
        </a>
    </div>
    <div class="em-card-body">
        <?php if (empty($connections)): ?>
            <div class="em-empty-state">
                <i class="fas fa-plug"></i>
                <h4>No CRM Connections</h4>
                <p>No CRM connections have been configured yet.</p>
                <a href="<?= em_base_url('pages/crm-hub/manage-connections.php'); ?>" class="em-btn em-btn-primary" style="margin-top: 16px;">
                    <i class="fas fa-plus"></i> Create First Connection
                </a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($connections as $conn): ?>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($conn['name']); ?></h5>
                                        <small class="text-muted"><?= htmlspecialchars($conn['crm_type']); ?></small>
                                    </div>
                                    <?= em_status_badge($conn['status']); ?>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        Created: <?= em_format_date($conn['created_at']); ?>
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        Last Sync: <?= em_time_ago($conn['last_sync_at'] ?? null); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Webhook Deliveries -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">Recent Webhook Deliveries</h5>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($recentDeliveries)): ?>
            <div class="em-empty-state">
                <i class="fas fa-inbox"></i>
                <h4>No Webhook Deliveries</h4>
                <p>No webhook deliveries have been recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Webhook</th>
                            <th>Event</th>
                            <th>Status</th>
                            <th>Response Code</th>
                            <th>Delivered At</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDeliveries as $delivery): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($delivery['webhook_name'] ?? 'N/A'); ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars(substr($delivery['url'] ?? '', 0, 40)); ?>...</small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($delivery['event_type'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <?= em_status_badge($delivery['status'] ?? 'unknown'); ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        ($delivery['response_code'] ?? 0) >= 200 && ($delivery['response_code'] ?? 0) < 300 ? 'success' : 'danger'; 
                                    ?>">
                                        <?= htmlspecialchars($delivery['response_code'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_format_date($delivery['created_at']); ?>
                                        <br>
                                        <?= em_time_ago($delivery['created_at']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (isset($delivery['duration_ms'])): ?>
                                        <small class="text-muted"><?= $delivery['duration_ms']; ?> ms</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="em-card-footer text-center">
                <a href="<?= em_base_url('pages/crm-hub/webhook-logs.php'); ?>" class="em-btn em-btn-secondary">
                    <i class="fas fa-list"></i> View All Webhook Logs
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
