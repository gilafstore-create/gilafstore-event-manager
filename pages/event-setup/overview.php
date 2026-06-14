<?php
/**
 * Event Setup - Overview
 * READ-ONLY. Summary of all event setup entities.
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Setup Overview — Event Manager';
$currentPage = 'overview';

$totalDefs    = em_fetch("SELECT COUNT(*) as c FROM em_event_definitions")['c'] ?? 0;
$activeDefs   = em_fetch("SELECT COUNT(*) as c FROM em_event_definitions WHERE status='active'")['c'] ?? 0;
$totalSources = em_fetch("SELECT COUNT(*) as c FROM em_event_sources")['c'] ?? 0;
$activeSources= em_fetch("SELECT COUNT(*) as c FROM em_event_sources WHERE status='active'")['c'] ?? 0;
$totalDests   = em_fetch("SELECT COUNT(*) as c FROM em_event_destinations")['c'] ?? 0;
$activeDests  = em_fetch("SELECT COUNT(*) as c FROM em_event_destinations WHERE status='active'")['c'] ?? 0;
$totalSchemas = em_fetch("SELECT COUNT(*) as c FROM em_event_schemas")['c'] ?? 0;
$activeSchemas= em_fetch("SELECT COUNT(*) as c FROM em_event_schemas WHERE status='active'")['c'] ?? 0;
$totalConns   = em_fetch("SELECT COUNT(*) as c FROM em_event_connections")['c'] ?? 0;
$activeConns  = em_fetch("SELECT COUNT(*) as c FROM em_event_connections WHERE status='active'")['c'] ?? 0;

$recentDefs   = em_fetch_all("SELECT * FROM em_event_definitions ORDER BY created_at DESC LIMIT 5");
$recentSources= em_fetch_all("SELECT * FROM em_event_sources ORDER BY created_at DESC LIMIT 5");
$recentDests  = em_fetch_all("SELECT * FROM em_event_destinations ORDER BY created_at DESC LIMIT 5");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Setup Overview</h2>
    <p class="text-muted">Summary of all configured event definitions, sources, destinations and schemas</p>
</div>

<div class="row g-4 mb-4">
    <!-- Event Definitions -->
    <div class="col-md-6 col-xl-3">
        <div class="em-card h-100">
            <div class="em-card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="em-stat-icon primary me-3"><i class="fas fa-cogs"></i></div>
                    <div>
                        <div class="text-muted small">Event Definitions</div>
                        <div class="fs-4 fw-bold"><?= em_format_number($totalDefs) ?></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="badge bg-success">Active: <?= $activeDefs ?></span>
                    <span class="badge bg-secondary">Inactive: <?= $totalDefs - $activeDefs ?></span>
                </div>
                <a href="definitions.php" class="btn btn-sm btn-outline-primary mt-3 w-100">View All</a>
            </div>
        </div>
    </div>
    <!-- Event Sources -->
    <div class="col-md-6 col-xl-3">
        <div class="em-card h-100">
            <div class="em-card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="em-stat-icon info me-3"><i class="fas fa-plug"></i></div>
                    <div>
                        <div class="text-muted small">Event Sources</div>
                        <div class="fs-4 fw-bold"><?= em_format_number($totalSources) ?></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="badge bg-success">Active: <?= $activeSources ?></span>
                    <span class="badge bg-secondary">Inactive: <?= $totalSources - $activeSources ?></span>
                </div>
                <a href="sources.php" class="btn btn-sm btn-outline-info mt-3 w-100">View All</a>
            </div>
        </div>
    </div>
    <!-- Destinations -->
    <div class="col-md-6 col-xl-3">
        <div class="em-card h-100">
            <div class="em-card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="em-stat-icon warning me-3"><i class="fas fa-paper-plane"></i></div>
                    <div>
                        <div class="text-muted small">Destinations</div>
                        <div class="fs-4 fw-bold"><?= em_format_number($totalDests) ?></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="badge bg-success">Active: <?= $activeDests ?></span>
                    <span class="badge bg-secondary">Inactive: <?= $totalDests - $activeDests ?></span>
                </div>
                <a href="destinations.php" class="btn btn-sm btn-outline-warning mt-3 w-100">View All</a>
            </div>
        </div>
    </div>
    <!-- Schemas -->
    <div class="col-md-6 col-xl-3">
        <div class="em-card h-100">
            <div class="em-card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="em-stat-icon success me-3"><i class="fas fa-file-code"></i></div>
                    <div>
                        <div class="text-muted small">Schemas</div>
                        <div class="fs-4 fw-bold"><?= em_format_number($totalSchemas) ?></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="badge bg-success">Active: <?= $activeSchemas ?></span>
                    <span class="badge bg-secondary">Inactive: <?= $totalSchemas - $activeSchemas ?></span>
                </div>
                <a href="schemas.php" class="btn btn-sm btn-outline-success mt-3 w-100">View All</a>
            </div>
        </div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body py-2 d-flex align-items-center gap-3">
        <div class="em-stat-icon secondary"><i class="fas fa-link"></i></div>
        <div>
            <strong>Active Connections:</strong>
            <span class="badge bg-success ms-2"><?= $activeConns ?></span>
            <span class="text-muted ms-2">of <?= $totalConns ?> total</span>
        </div>
        <a href="../event-bus/connections.php" class="btn btn-sm btn-outline-secondary ms-auto">View Connections</a>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Definitions -->
    <div class="col-lg-4">
        <div class="em-card">
            <div class="em-card-header"><h6 class="mb-0"><i class="fas fa-cogs me-2 text-primary"></i>Recent Definitions</h6></div>
            <div class="em-card-body p-0">
                <?php if (empty($recentDefs)): ?>
                    <div class="p-3 text-muted text-center small">No definitions yet.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentDefs as $d): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <strong class="small"><?= htmlspecialchars($d['name']) ?></strong><br>
                                    <small class="text-muted"><?= em_time_ago($d['created_at']) ?></small>
                                </div>
                                <?= em_status_badge($d['status']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Recent Sources -->
    <div class="col-lg-4">
        <div class="em-card">
            <div class="em-card-header"><h6 class="mb-0"><i class="fas fa-plug me-2 text-info"></i>Recent Sources</h6></div>
            <div class="em-card-body p-0">
                <?php if (empty($recentSources)): ?>
                    <div class="p-3 text-muted text-center small">No sources yet.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentSources as $s): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <strong class="small"><?= htmlspecialchars($s['name']) ?></strong><br>
                                    <small class="text-muted"><?= em_time_ago($s['created_at']) ?></small>
                                </div>
                                <?= em_status_badge($s['status']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Recent Destinations -->
    <div class="col-lg-4">
        <div class="em-card">
            <div class="em-card-header"><h6 class="mb-0"><i class="fas fa-paper-plane me-2 text-warning"></i>Recent Destinations</h6></div>
            <div class="em-card-body p-0">
                <?php if (empty($recentDests)): ?>
                    <div class="p-3 text-muted text-center small">No destinations yet.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentDests as $d): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <strong class="small"><?= htmlspecialchars($d['name']) ?></strong><br>
                                    <small class="text-muted"><?= em_time_ago($d['created_at']) ?></small>
                                </div>
                                <?= em_status_badge($d['status']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
