<?php
/**
 * Event Manager - Event Destinations
 * 
 * SAFETY: READ-ONLY display
 * Management functionality in Phase 1B
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Destinations — Event Manager';
$currentPage = 'destinations';

// Get event destinations
$destinations = em_fetch_all("SELECT * FROM em_event_destinations ORDER BY created_at DESC");

// Get stats
$activeCount = em_fetch("SELECT COUNT(*) as count FROM em_event_destinations WHERE status = 'active'")['count'] ?? 0;
$inactiveCount = em_fetch("SELECT COUNT(*) as count FROM em_event_destinations WHERE status = 'inactive'")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Event Destinations</h2>
    <p class="text-muted">Manage event destinations and consumers</p>
</div>

<!-- Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Destinations</div>
            <div class="em-stat-value"><?= em_format_number(count($destinations)); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Active</div>
            <div class="em-stat-value"><?= em_format_number($activeCount); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon secondary">
            <i class="fas fa-pause-circle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Inactive</div>
            <div class="em-stat-value"><?= em_format_number($inactiveCount); ?></div>
        </div>
    </div>
</div>

<!-- Event Destinations -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">Event Destinations</h5>
    </div>
    <div class="em-card-body">
        <?php if (empty($destinations)): ?>
            <div class="em-empty-state">
                <i class="fas fa-paper-plane"></i>
                <h4>No Event Destinations</h4>
                <p>No event destinations have been configured yet.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($destinations as $dest): ?>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($dest['name']); ?></h5>
                                        <small class="text-muted"><?= htmlspecialchars($dest['type']); ?></small>
                                    </div>
                                    <?= em_status_badge($dest['status']); ?>
                                </div>
                                <p class="text-muted mb-2"><?= htmlspecialchars($dest['description'] ?? ''); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i>
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

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
