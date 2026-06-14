<?php
/**
 * Trace Center - Service Map
 * READ-ONLY. em_service_dependencies: id, service_name, depends_on, dependency_type, created_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Service Map — Event Manager';
$currentPage = 'service-map';

$filterService = $_GET['service'] ?? '';
$filterType    = $_GET['dep_type'] ?? '';

$where = []; $params = [];
if ($filterService) { $where[] = "service_name LIKE ?"; $params[] = "%{$filterService}%"; }
if ($filterType)    { $where[] = "dependency_type = ?"; $params[] = $filterType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$deps     = em_fetch_all("SELECT * FROM em_service_dependencies $whereClause ORDER BY service_name, dependency_type", $params);
$total    = em_fetch("SELECT COUNT(*) as count FROM em_service_dependencies $whereClause", $params)['count'] ?? 0;
$services = em_fetch_all("SELECT DISTINCT service_name FROM em_service_dependencies ORDER BY service_name");
$required = em_fetch("SELECT COUNT(*) as count FROM em_service_dependencies WHERE dependency_type='required'")['count'] ?? 0;
$optional = em_fetch("SELECT COUNT(*) as count FROM em_service_dependencies WHERE dependency_type='optional'")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Service Map</h2>
    <p class="text-muted">View service dependencies and integration topology</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-network-wired"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Dependencies</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-server"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Services</div><div class="em-stat-value"><?= count($services) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-link"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Required</div><div class="em-stat-value"><?= em_format_number($required) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon secondary"><i class="fas fa-unlink"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Optional</div><div class="em-stat-value"><?= em_format_number($optional) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="service" class="form-control" placeholder="Search service name..." value="<?= htmlspecialchars($filterService) ?>">
            </div>
            <div class="col-md-4">
                <select name="dep_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="required" <?= $filterType==='required'?'selected':'' ?>>Required</option>
                    <option value="optional" <?= $filterType==='optional'?'selected':'' ?>>Optional</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="service-map.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Visual dependency grouping by service -->
<?php
$grouped = [];
foreach ($deps as $d) { $grouped[$d['service_name']][] = $d; }
?>

<?php if (empty($grouped)): ?>
    <div class="em-card"><div class="em-empty-state"><i class="fas fa-network-wired"></i><h4>No Dependencies Found</h4><p>No service dependencies match your criteria.</p></div></div>
<?php else: ?>
    <?php foreach ($grouped as $svc => $svcDeps): ?>
        <div class="em-card mb-3">
            <div class="em-card-header d-flex align-items-center gap-2">
                <i class="fas fa-server text-primary"></i>
                <h6 class="mb-0"><?= htmlspecialchars($svc) ?></h6>
                <span class="badge bg-secondary ms-auto"><?= count($svcDeps) ?> deps</span>
            </div>
            <div class="em-card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($svcDeps as $d): ?>
                        <div class="d-flex align-items-center gap-1">
                            <i class="fas fa-arrow-right text-muted small"></i>
                            <span class="badge bg-<?= $d['dependency_type']==='required'?'danger':'secondary' ?>">
                                <?= htmlspecialchars($d['depends_on']) ?>
                                <span class="ms-1 opacity-75">(<?= $d['dependency_type'] ?>)</span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
