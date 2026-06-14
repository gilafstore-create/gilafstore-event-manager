<?php
/**
 * Event Bus - Message Explorer
 * READ-ONLY. em_queue_messages: id, queue_name, payload, status, attempts, created_at, processed_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Message Explorer — Event Manager';
$currentPage = 'message-explorer';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterQueue  = $_GET['queue'] ?? '';
$filterSearch = trim($_GET['search'] ?? '');

$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?";         $params[] = $filterStatus; }
if ($filterQueue)  { $where[] = "queue_name = ?";     $params[] = $filterQueue; }
if ($filterSearch) { $where[] = "JSON_SEARCH(payload, 'one', ?) IS NOT NULL"; $params[] = '%' . $filterSearch . '%'; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages $whereClause", $params)['count'] ?? 0;
$messages   = em_fetch_all("SELECT * FROM em_queue_messages $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}", $params);
$pagination = em_paginate($total, $perPage, $page);

$pendingCount    = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages WHERE status='pending'")['count'] ?? 0;
$processingCount = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages WHERE status='processing'")['count'] ?? 0;
$completedCount  = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages WHERE status='completed'")['count'] ?? 0;
$failedCount     = em_fetch("SELECT COUNT(*) as count FROM em_queue_messages WHERE status='failed'")['count'] ?? 0;
$queues          = em_fetch_all("SELECT DISTINCT queue_name FROM em_queue_messages ORDER BY queue_name");
$selectedMsg     = null;
if (isset($_GET['id'])) {
    $selectedMsg = em_fetch("SELECT * FROM em_queue_messages WHERE id = ?", [(int)$_GET['id']]);
}

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Message Explorer</h2>
    <p class="text-muted">Browse and inspect individual queue messages and their payloads</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Pending</div><div class="em-stat-value"><?= em_format_number($pendingCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-spinner"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Processing</div><div class="em-stat-value"><?= em_format_number($processingCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Completed</div><div class="em-stat-value"><?= em_format_number($completedCount) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-times-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Failed</div><div class="em-stat-value"><?= em_format_number($failedCount) ?></div></div>
    </div>
</div>

<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','processing','completed','failed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="queue" class="form-select">
                    <option value="">All Queues</option>
                    <?php foreach ($queues as $q): ?>
                        <option value="<?= htmlspecialchars($q['queue_name']) ?>" <?= $filterQueue===$q['queue_name']?'selected':'' ?>><?= htmlspecialchars($q['queue_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search payload content…" value="<?= htmlspecialchars($filterSearch) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="em-btn em-btn-primary flex-fill">Filter</button>
                <a href="message-explorer.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedMsg): ?>
<div class="em-card mb-4 border-primary">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-envelope-open me-2"></i>Message #<?= $selectedMsg['id'] ?></h6>
        <a href="message-explorer.php" class="btn btn-sm btn-outline-secondary">Close</a>
    </div>
    <div class="em-card-body">
        <div class="row g-3">
            <div class="col-md-3"><strong>Queue:</strong><br><code><?= htmlspecialchars($selectedMsg['queue_name']) ?></code></div>
            <div class="col-md-2"><strong>Status:</strong><br><?= em_status_badge($selectedMsg['status']) ?></div>
            <div class="col-md-2"><strong>Attempts:</strong><br><span class="badge bg-secondary"><?= $selectedMsg['attempts'] ?></span></div>
            <div class="col-md-2"><strong>Created:</strong><br><small><?= em_time_ago($selectedMsg['created_at']) ?></small></div>
            <div class="col-md-3"><strong>Processed:</strong><br><small><?= $selectedMsg['processed_at'] ? em_time_ago($selectedMsg['processed_at']) : '—' ?></small></div>
        </div>
        <hr>
        <strong>Payload:</strong>
        <pre class="bg-light p-3 rounded mt-2" style="max-height:300px;overflow-y:auto;font-size:12px"><?= htmlspecialchars(json_encode(json_decode($selectedMsg['payload']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    </div>
</div>
<?php endif; ?>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Messages <span class="badge bg-secondary"><?= em_format_number($total) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($messages)): ?>
            <div class="em-empty-state"><i class="fas fa-inbox"></i><h4>No Messages</h4><p>No queue messages match your criteria.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>ID</th><th>Queue</th><th>Status</th><th>Attempts</th><th>Payload Preview</th><th>Created</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($messages as $m):
                            $preview = '';
                            $decoded = json_decode($m['payload'], true);
                            if (is_array($decoded)) {
                                $preview = implode(', ', array_map(fn($k,$v) => $k.': '.(is_scalar($v)?$v:'…'), array_keys(array_slice($decoded,0,3)), array_slice($decoded,0,3)));
                            }
                        ?>
                            <tr class="<?= isset($_GET['id']) && $_GET['id'] == $m['id'] ? 'table-primary' : '' ?>">
                                <td><small class="text-muted">#<?= $m['id'] ?></small></td>
                                <td><code class="small"><?= htmlspecialchars($m['queue_name']) ?></code></td>
                                <td><?= em_status_badge($m['status']) ?></td>
                                <td><span class="badge bg-secondary"><?= $m['attempts'] ?></span></td>
                                <td><small class="text-muted"><?= htmlspecialchars(substr($preview, 0, 70)) ?></small></td>
                                <td><small class="text-muted"><?= em_time_ago($m['created_at']) ?></small></td>
                                <td><a href="?id=<?= $m['id'] ?>&<?= http_build_query(['status'=>$filterStatus,'queue'=>$filterQueue,'page'=>$page]) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer"><?= em_render_pagination($pagination, 'message-explorer.php') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
