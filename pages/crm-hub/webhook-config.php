<?php
/**
 * Event Manager - Webhook Configuration
 * READ-ONLY view of event destinations configured as webhooks.
 * No writes to existing CRM tables.
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Webhook Configuration — Event Manager';
$currentPage = 'crm-webhook-config';

// Webhook destinations from em_event_destinations
$webhookDestinations = em_fetch_all(
    "SELECT * FROM em_event_destinations WHERE type = 'webhook' ORDER BY created_at DESC"
);

// All active destinations for reference
$allDestinations = em_fetch_all(
    "SELECT * FROM em_event_destinations ORDER BY status ASC, name ASC"
);

// Event source map for display
$eventSources = em_fetch_all("SELECT * FROM em_event_sources ORDER BY name ASC");

// Event definitions mapped to their typical webhook-triggering events
$webhookEventTypes = em_fetch_all(
    "SELECT * FROM em_event_definitions WHERE status = 'active' ORDER BY name ASC"
);

// Stats from em_event_logs for webhook-related events
$webhookEventStats = [
    'sent'   => 0,
    'failed' => 0,
    'total'  => 0,
];
try {
    $webhookEventStats['sent']   = (int)em_fetch("SELECT COUNT(*) as c FROM em_event_logs WHERE event_type = 'WEBHOOK_SENT'")['c'];
    $webhookEventStats['failed'] = (int)em_fetch("SELECT COUNT(*) as c FROM em_event_logs WHERE event_type = 'WEBHOOK_FAILED'")['c'];
    $webhookEventStats['total']  = $webhookEventStats['sent'] + $webhookEventStats['failed'];
} catch (Exception $e) {}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Webhook Configuration</h2>
    <p class="text-muted">View and manage outbound webhook destinations for event delivery</p>
</div>

<!-- Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-satellite-dish"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Webhook Destinations</div>
            <div class="em-stat-value"><?= count($webhookDestinations); ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Webhooks Sent</div>
            <div class="em-stat-value"><?= em_format_number($webhookEventStats['sent']); ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Webhooks Failed</div>
            <div class="em-stat-value"><?= em_format_number($webhookEventStats['failed']); ?></div>
        </div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info">
            <i class="fas fa-list"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Trackable Event Types</div>
            <div class="em-stat-value"><?= count($webhookEventTypes); ?></div>
        </div>
    </div>
</div>

<!-- Webhook Destinations -->
<div class="em-card mb-4">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-satellite-dish me-2"></i>Webhook Destinations</h5>
        <a href="<?= em_base_url('pages/event-setup/destinations.php'); ?>" class="em-btn em-btn-primary em-btn-sm">
            <i class="fas fa-cog"></i> Manage Destinations
        </a>
    </div>
    <div class="em-card-body">
        <?php if (empty($webhookDestinations)): ?>
            <div class="em-empty-state">
                <i class="fas fa-satellite-dish"></i>
                <h4>No Webhook Destinations</h4>
                <p>No webhook-type destinations are configured yet.</p>
                <p class="text-muted">Add webhook destinations via Event Setup &rarr; Destinations.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($webhookDestinations as $dest): ?>
                    <?php $config = json_decode($dest['config'] ?? '{}', true); ?>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?= htmlspecialchars($dest['name']); ?></h6>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($dest['type']); ?></span>
                                    </div>
                                    <?= em_status_badge($dest['status']); ?>
                                </div>
                                <?php if (!empty($config['description'])): ?>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars($config['description']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($config['endpoint_url'])): ?>
                                    <div class="mb-2">
                                        <small class="text-muted"><i class="fas fa-link me-1"></i>
                                            <code><?= htmlspecialchars(substr($config['endpoint_url'], 0, 50)); ?>...</code>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Created: <?= em_format_date($dest['created_at']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- All Event Destinations Reference -->
<div class="em-card mb-4">
    <div class="em-card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Event Destinations</h5>
    </div>
    <div class="em-card-body p-0">
        <div class="table-responsive">
            <table class="em-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allDestinations as $dest): ?>
                        <?php $config = json_decode($dest['config'] ?? '{}', true); ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($dest['name']); ?></strong></td>
                            <td>
                                <span class="badge bg-<?= $dest['type'] === 'webhook' ? 'primary' : ($dest['type'] === 'internal' ? 'success' : 'secondary'); ?>">
                                    <?= htmlspecialchars($dest['type']); ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($config['description'] ?? '—'); ?></small></td>
                            <td><?= em_status_badge($dest['status']); ?></td>
                            <td><small class="text-muted"><?= em_format_date($dest['created_at']); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Trackable Event Types -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Trackable Event Types (<?= count($webhookEventTypes); ?>)</h5>
    </div>
    <div class="em-card-body">
        <div class="row g-2">
            <?php foreach ($webhookEventTypes as $evDef): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="d-flex align-items-center p-2 border rounded">
                        <i class="fas fa-circle text-success me-2" style="font-size:8px;"></i>
                        <code class="small"><?= htmlspecialchars($evDef['name']); ?></code>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
