<?php
/**
 * Developer Center - Event Playground
 * READ-ONLY. Displays sample event schemas from em_event_definitions + em_event_schemas for reference.
 * No event dispatching — READ ONLY.
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Playground — Event Manager';
$currentPage = 'playground';

$definitions = em_fetch_all("SELECT * FROM em_event_definitions WHERE status='active' ORDER BY name LIMIT 100");
$schemas     = em_fetch_all("SELECT * FROM em_event_schemas WHERE status='active' ORDER BY name LIMIT 100");

$selectedDef = isset($_GET['def']) ? (int)$_GET['def'] : null;
$defDetail   = null;
if ($selectedDef) {
    $defDetail = em_fetch("SELECT * FROM em_event_definitions WHERE id = ?", [$selectedDef]);
}

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Event Playground</h2>
    <p class="text-muted">Browse event definitions and schemas in read-only mode</p>
</div>

<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Read-Only Mode:</strong> This playground displays event definitions and schemas for reference. Event dispatching will be available in a future release.
</div>

<div class="row g-4">
    <!-- Event Definitions Panel -->
    <div class="col-md-5">
        <div class="em-card h-100">
            <div class="em-card-header"><h6 class="mb-0"><i class="fas fa-list me-2"></i>Event Definitions (<?= count($definitions) ?>)</h6></div>
            <div class="em-card-body p-0" style="max-height:600px;overflow-y:auto">
                <?php if (empty($definitions)): ?>
                    <div class="p-3 text-muted text-center">No active event definitions found.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($definitions as $d): ?>
                            <a href="?def=<?= $d['id'] ?>" class="list-group-item list-group-item-action <?= $selectedDef == $d['id'] ? 'active' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= htmlspecialchars($d['name']) ?></strong><br>
                                        <small class="<?= $selectedDef == $d['id'] ? 'text-white-50' : 'text-muted' ?>"><?= htmlspecialchars(substr($d['description'] ?? '', 0, 60)) ?></small>
                                    </div>
                                    <span class="badge bg-<?= $selectedDef == $d['id'] ? 'light text-dark' : 'primary' ?>"><?= htmlspecialchars($d['version'] ?? 'v1') ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detail Panel -->
    <div class="col-md-7">
        <?php if ($defDetail): ?>
            <div class="em-card mb-4">
                <div class="em-card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><?= htmlspecialchars($defDetail['name']) ?></h6>
                    <?= em_status_badge($defDetail['status']) ?>
                </div>
                <div class="em-card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Version</dt><dd class="col-sm-9"><code><?= htmlspecialchars($defDetail['version'] ?? '—') ?></code></dd>
                        <dt class="col-sm-3">Category</dt><dd class="col-sm-9"><?= htmlspecialchars($defDetail['category'] ?? '—') ?></dd>
                        <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= htmlspecialchars($defDetail['description'] ?? '—') ?></dd>
                        <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?= em_time_ago($defDetail['created_at']) ?></dd>
                    </dl>
                    <?php if ($defDetail['payload_schema']): ?>
                        <hr>
                        <h6>Payload Schema</h6>
                        <pre class="bg-light p-3 rounded" style="max-height:300px;overflow-y:auto;font-size:12px"><?= htmlspecialchars(json_encode(json_decode($defDetail['payload_schema']), JSON_PRETTY_PRINT)) ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="em-card h-100">
                <div class="em-card-body d-flex flex-column align-items-center justify-content-center text-center" style="min-height:300px">
                    <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Select an event definition</h5>
                    <p class="text-muted">Click any item on the left to view its schema and details</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Schema Library -->
        <?php if (!empty($schemas)): ?>
            <div class="em-card">
                <div class="em-card-header"><h6 class="mb-0"><i class="fas fa-file-code me-2"></i>Active Schemas (<?= count($schemas) ?>)</h6></div>
                <div class="em-card-body p-0" style="max-height:250px;overflow-y:auto">
                    <table class="em-table">
                        <thead><tr><th>Name</th><th>Version</th><th>Updated</th></tr></thead>
                        <tbody>
                            <?php foreach ($schemas as $s): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($s['version']) ?></span></td>
                                    <td><small class="text-muted"><?= em_time_ago($s['updated_at']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
