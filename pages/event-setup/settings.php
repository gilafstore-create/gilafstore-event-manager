<?php
/**
 * Event Setup - Settings
 * READ-ONLY. Displays broker/system settings from em_broker_settings relevant to event setup.
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Setup Settings — Event Manager';
$currentPage = 'settings';

$settings    = em_fetch_all("SELECT * FROM em_broker_settings ORDER BY key_name");
$total       = count($settings);
$lastUpdated = em_fetch("SELECT MAX(updated_at) as latest FROM em_broker_settings")['latest'] ?? null;

$totalDefs    = em_fetch("SELECT COUNT(*) as c FROM em_event_definitions")['c'] ?? 0;
$totalSchemas = em_fetch("SELECT COUNT(*) as c FROM em_event_schemas")['c'] ?? 0;
$totalSources = em_fetch("SELECT COUNT(*) as c FROM em_event_sources")['c'] ?? 0;
$totalDests   = em_fetch("SELECT COUNT(*) as c FROM em_event_destinations")['c'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Setup Settings</h2>
    <p class="text-muted">System configuration for Event Manager behaviour and defaults</p>
</div>

<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Read-Only:</strong> Settings are managed by the system configuration. Contact your administrator to modify values.
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="em-card text-center p-3">
            <i class="fas fa-cogs fa-2x text-primary mb-2"></i>
            <div class="fw-bold fs-5"><?= em_format_number($totalDefs) ?></div>
            <div class="text-muted small">Event Definitions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="em-card text-center p-3">
            <i class="fas fa-file-code fa-2x text-success mb-2"></i>
            <div class="fw-bold fs-5"><?= em_format_number($totalSchemas) ?></div>
            <div class="text-muted small">Schemas Registered</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="em-card text-center p-3">
            <i class="fas fa-plug fa-2x text-info mb-2"></i>
            <div class="fw-bold fs-5"><?= em_format_number($totalSources) ?></div>
            <div class="text-muted small">Sources</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="em-card text-center p-3">
            <i class="fas fa-paper-plane fa-2x text-warning mb-2"></i>
            <div class="fw-bold fs-5"><?= em_format_number($totalDests) ?></div>
            <div class="text-muted small">Destinations</div>
        </div>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Configuration <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5>
        <?php if ($lastUpdated): ?>
            <small class="text-muted">Last updated <?= em_time_ago($lastUpdated) ?></small>
        <?php endif; ?>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($settings)): ?>
            <div class="em-empty-state">
                <i class="fas fa-sliders-h"></i>
                <h4>No Settings Configured</h4>
                <p class="text-muted">No broker settings have been initialised yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Key</th><th>Value</th><th>Last Updated</th></tr></thead>
                    <tbody>
                        <?php foreach ($settings as $s): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($s['key_name']) ?></code></td>
                                <td>
                                    <?php if (preg_match('/secret|password|key|token/i', $s['key_name'])): ?>
                                        <code class="text-muted">••••••••</code>
                                    <?php else: ?>
                                        <code><?= htmlspecialchars($s['value'] ?? '') ?></code>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= em_time_ago($s['updated_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
