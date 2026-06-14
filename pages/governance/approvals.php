<?php
/**
 * Governance - Approval Workflows
 * READ-ONLY. em_approval_workflows: id, entity_type, entity_id, status,
 *            requested_by, approved_by, requested_at, approved_at, notes
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Approval Workflows — Event Manager';
$currentPage = 'approvals';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?";      $params[] = $filterStatus; }
if ($filterType)   { $where[] = "entity_type = ?"; $params[] = $filterType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_approval_workflows $whereClause", $params)['count'] ?? 0;
$rows       = em_fetch_all("SELECT * FROM em_approval_workflows $whereClause ORDER BY requested_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$pendingCount  = em_fetch("SELECT COUNT(*) as count FROM em_approval_workflows WHERE status='pending'")['count'] ?? 0;
$approvedCount = em_fetch("SELECT COUNT(*) as count FROM em_approval_workflows WHERE status='approved'")['count'] ?? 0;
$rejectedCount = em_fetch("SELECT COUNT(*) as count FROM em_approval_workflows WHERE status='rejected'")['count'] ?? 0;
$entityTypes   = em_fetch_all("SELECT DISTINCT entity_type FROM em_approval_workflows ORDER BY entity_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Approval Workflows</h2>
    <p class="text-muted">Review change approval requests and decisions</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-tasks"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Requests</div><div class="em-stat-value"><?= em_format_number($total) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Pending</div><div class="em-stat-value"><?= em_format_number($pendingCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-thumbs-up"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Approved</div><div class="em-stat-value"><?= em_format_number($approvedCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-thumbs-down"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Rejected</div><div class="em-stat-value"><?= em_format_number($rejectedCount) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','approved','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <select name="type" class="form-select">
                    <option value="">All Entity Types</option>
                    <?php foreach ($entityTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['entity_type']) ?>" <?= $filterType===$t['entity_type']?'selected':'' ?>><?= htmlspecialchars($t['entity_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="approvals.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Approval Requests <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="em-empty-state"><i class="fas fa-tasks"></i><h4>No Approval Requests</h4><p>No approval workflows match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Entity</th><th>Status</th><th>Requested By</th><th>Approved By</th><th>Requested</th><th>Decided</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($r['entity_type']) ?></span>
                                    <small class="text-muted ms-1">#<?= (int)$r['entity_id'] ?></small>
                                </td>
                                <td><?php
                                    $b = $r['status']==='approved'?'success':($r['status']==='rejected'?'danger':'warning');
                                    echo "<span class='badge bg-{$b}'>" . ucfirst($r['status']) . "</span>";
                                ?></td>
                                <td><?= $r['requested_by'] ? '#'.(int)$r['requested_by'] : '—' ?></td>
                                <td><?= $r['approved_by'] ? '#'.(int)$r['approved_by'] : '<span class="text-muted">—</span>' ?></td>
                                <td><small class="text-muted"><?= em_time_ago($r['requested_at']) ?></small></td>
                                <td><small class="text-muted"><?= $r['approved_at'] ? em_time_ago($r['approved_at']) : '—' ?></small></td>
                                <td><small class="text-muted"><?= htmlspecialchars(substr($r['notes'] ?? '', 0, 40)) ?><?= strlen($r['notes'] ?? '') > 40 ? '...' : '' ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'approvals.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
