<?php
/**
 * Event Manager - Event Sources
 * 
 * SAFETY: READ-ONLY display
 * Management functionality in Phase 1B
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Sources — Event Manager';
$currentPage = 'sources';

// Get event sources
$sources = em_fetch_all("SELECT * FROM em_event_sources ORDER BY created_at DESC");

// Get stats
$activeCount = em_fetch("SELECT COUNT(*) as count FROM em_event_sources WHERE status = 'active'")['count'] ?? 0;
$inactiveCount = em_fetch("SELECT COUNT(*) as count FROM em_event_sources WHERE status = 'inactive'")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Event Sources</h2>
    <p class="text-muted">Manage event sources and producers</p>
</div>

<!-- Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-plug"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Sources</div>
            <div class="em-stat-value"><?= em_format_number(count($sources)); ?></div>
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

<!-- Event Sources -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">Event Sources</h5>
    </div>
    <div class="em-card-body">
        <?php if (empty($sources)): ?>
            <div class="em-empty-state">
                <i class="fas fa-plug"></i>
                <h4>No Event Sources</h4>
                <p>No event sources have been configured yet.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($sources as $source): ?>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($source['name']); ?></h5>
                                        <small class="text-muted"><?= htmlspecialchars($source['type']); ?></small>
                                    </div>
                                    <?= em_status_badge($source['status']); ?>
                                </div>
                                <p class="text-muted mb-2"><?= htmlspecialchars($source['description'] ?? ''); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i>
                                    Created: <?= em_format_date($source['created_at']); ?>
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
