<?php
/**
 * Event Bus - Workers
 * READ-ONLY. em_workers: id, name, queue_name, status, last_heartbeat, created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Workers — Event Manager';
$currentPage = 'workers';

$filterStatus = $_GET['status'] ?? '';
$filterQueue  = $_GET['queue'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?";     $params[] = $filterStatus; }
if ($filterQueue)  { $where[] = "queue_name = ?"; $params[] = $filterQueue; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$workers    = em_fetch_all("SELECT * FROM em_workers $whereClause ORDER BY status, last_heartbeat DESC", $params);
$total      = count($workers);
$running    = em_fetch("SELECT COUNT(*) as count FROM em_workers WHERE status='running'")['count'] ?? 0;
$stopped    = em_fetch("SELECT COUNT(*) as count FROM em_workers WHERE status='stopped'")['count'] ?? 0;
$queues     = em_fetch_all("SELECT DISTINCT queue_name FROM em_workers ORDER BY queue_name");
$stale      = em_fetch("SELECT COUNT(*) as count FROM em_workers WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND status='running'")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Workers</h2>
    <p class="text-muted">Monitor queue worker processes and their health</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-cogs"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Workers</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-play-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Running</div><div class="em-stat-value"><?= em_format_number($running) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon secondary"><i class="fas fa-stop-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Stopped</div><div class="em-stat-value"><?= em_format_number($stopped) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon <?= $stale > 0 ? 'danger' : 'success' ?>"><i class="fas fa-heartbeat"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Stale (>5 min)</div><div class="em-stat-value"><?= em_format_number($stale) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="running" <?= $filterStatus==='running'?'selected':'' ?>>Running</option>
                    <option value="stopped" <?= $filterStatus==='stopped'?'selected':'' ?>>Stopped</option>
                </select>
            </div>
            <div class="col-md-5">
                <select name="queue" class="form-select">
                    <option value="">All Queues</option>
                    <?php foreach ($queues as $q): ?>
                        <option value="<?= htmlspecialchars($q['queue_name']) ?>" <?= $filterQueue===$q['queue_name']?'selected':'' ?>><?= htmlspecialchars($q['queue_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="workers.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Workers <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($workers)): ?>
            <div class="em-empty-state"><i class="fas fa-cogs"></i><h4>No Workers Found</h4><p>No worker processes match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Name</th><th>Queue</th><th>Status</th><th>Last Heartbeat</th><th>Health</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($workers as $w):
                            $isStale = $w['last_heartbeat'] && strtotime($w['last_heartbeat']) < (time() - 300) && $w['status'] === 'running';
                        ?>
                            <tr class="<?= $isStale ? 'table-warning' : '' ?>">
                                <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                                <td><code><?= htmlspecialchars($w['queue_name']) ?></code></td>
                                <td><?= em_status_badge($w['status']) ?></td>
                                <td><small class="text-muted"><?= $w['last_heartbeat'] ? em_time_ago($w['last_heartbeat']) : 'Never' ?></small></td>
                                <td>
                                    <?php if ($isStale): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Stale</span>
                                    <?php elseif ($w['status'] === 'running'): ?>
                                        <span class="badge bg-success"><i class="fas fa-heartbeat me-1"></i>Healthy</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Offline</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= em_time_ago($w['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
