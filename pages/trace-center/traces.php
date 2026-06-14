<?php
/**
 * Trace Center - Traces
 * READ-ONLY. em_traces: id, trace_id, parent_trace_id, event_type, payload, created_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Traces — Event Manager';
$currentPage = 'traces';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterType = $_GET['type'] ?? '';
$filterTrace = $_GET['trace_id'] ?? '';

$where = []; $params = [];
if ($filterType)  { $where[] = "event_type = ?"; $params[] = $filterType; }
if ($filterTrace) { $where[] = "trace_id LIKE ?"; $params[] = "%{$filterTrace}%"; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_traces $whereClause", $params)['count'] ?? 0;
$traces     = em_fetch_all("SELECT * FROM em_traces $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$rootCount  = em_fetch("SELECT COUNT(*) as count FROM em_traces WHERE parent_trace_id IS NULL OR parent_trace_id = ''")['count'] ?? 0;
$childCount = $total - $rootCount;
$eventTypes = em_fetch_all("SELECT DISTINCT event_type FROM em_traces ORDER BY event_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Traces</h2>
    <p class="text-muted">Distributed trace records for event flows</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-project-diagram"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Traces</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-seedling"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Root Traces</div><div class="em-stat-value"><?= em_format_number($rootCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-sitemap"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Child Spans</div><div class="em-stat-value"><?= em_format_number($childCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon secondary"><i class="fas fa-tags"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Event Types</div><div class="em-stat-value"><?= count($eventTypes) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="trace_id" class="form-control" placeholder="Search trace ID..." value="<?= htmlspecialchars($filterTrace) ?>">
            </div>
            <div class="col-md-5">
                <select name="type" class="form-select">
                    <option value="">All Event Types</option>
                    <?php foreach ($eventTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['event_type']) ?>" <?= $filterType===$t['event_type']?'selected':'' ?>><?= htmlspecialchars($t['event_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="traces.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Trace Records <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($traces)): ?>
            <div class="em-empty-state"><i class="fas fa-project-diagram"></i><h4>No Traces Found</h4><p>No trace records match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr><th>Trace ID</th><th>Event Type</th><th>Parent Trace</th><th>Created</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($traces as $t): ?>
                            <tr>
                                <td><code class="text-primary"><?= htmlspecialchars($t['trace_id']) ?></code></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($t['event_type']) ?></span></td>
                                <td>
                                    <?php if ($t['parent_trace_id']): ?>
                                        <code class="text-muted small"><?= htmlspecialchars(substr($t['parent_trace_id'], 0, 20)) ?>...</code>
                                    <?php else: ?>
                                        <span class="badge bg-success">Root</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= em_time_ago($t['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'traces.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
