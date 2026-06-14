<?php
/**
 * Event Bus - Broker Settings
 * READ-ONLY. em_broker_settings: id, key_name, value, created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Broker Settings — Event Manager';
$currentPage = 'broker-settings';

$settings = em_fetch_all("SELECT * FROM em_broker_settings ORDER BY key_name");
$total    = count($settings);
$lastUpdated = em_fetch("SELECT MAX(updated_at) as latest FROM em_broker_settings")['latest'] ?? null;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Broker Settings</h2>
    <p class="text-muted">View Event Bus broker configuration parameters</p>
</div>

<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Read-Only:</strong> These settings are managed by the Event Manager configuration system. Direct modifications require a system administrator.
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-sliders-h"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Settings</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-clock"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Last Updated</div><div class="em-stat-value" style="font-size:0.9rem"><?= $lastUpdated ? em_time_ago($lastUpdated) : 'Never' ?></div></div>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Broker Configuration <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($settings)): ?>
            <div class="em-empty-state"><i class="fas fa-sliders-h"></i><h4>No Settings Found</h4><p>No broker settings have been configured.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Key Name</th><th>Value</th><th>Last Updated</th></tr></thead>
                    <tbody>
                        <?php foreach ($settings as $s): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($s['key_name']) ?></code></td>
                                <td>
                                    <?php
                                    if (preg_match('/secret|password|key|token/i', $s['key_name'])) {
                                        echo '<code class="text-muted">••••••••</code>';
                                    } else {
                                        echo '<code>' . htmlspecialchars($s['value'] ?? '') . '</code>';
                                    }
                                    ?>
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
