<?php
/**
 * Event Manager - Delivery Logs
 * Paginated view of all em_event_logs with filtering by type, status, and date.
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Delivery Logs — Event Manager';
$currentPage = 'crm-delivery-logs';

// Filters
$filterType   = trim($_GET['type']   ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterDate   = trim($_GET['date']   ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 25;
$offset       = ($page - 1) * $perPage;

// Build query
$where  = [];
$params = [];
if ($filterType !== '') {
    $where[]  = 'event_type = ?';
    $params[] = $filterType;
}
if ($filterStatus !== '') {
    $where[]  = 'status = ?';
    $params[] = $filterStatus;
}
if ($filterDate !== '') {
    $where[]  = 'DATE(created_at) = ?';
    $params[] = $filterDate;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalRows = 0;
$logs      = [];
try {
    $totalRows = (int)(em_fetch("SELECT COUNT(*) AS c FROM em_event_logs $whereSql", $params)['c'] ?? 0);
    $logs      = em_fetch_all(
        "SELECT l.*, s.name AS source_name
         FROM em_event_logs l
         LEFT JOIN em_event_sources s ON s.id = l.source_id
         $whereSql
         ORDER BY l.created_at DESC
         LIMIT $perPage OFFSET $offset",
        $params
    );
} catch (Exception $e) {
    error_log('EM delivery-logs: ' . $e->getMessage());
}

$totalPages = $totalRows > 0 ? (int)ceil($totalRows / $perPage) : 1;

// Filter options
$eventTypes = [];
try {
    $eventTypes = em_fetch_all("SELECT DISTINCT event_type FROM em_event_logs ORDER BY event_type");
} catch (Exception $e) {}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Delivery Logs</h2>
    <p class="text-muted">Complete audit trail of all event deliveries — <?= em_format_number($totalRows); ?> total records</p>
</div>

<!-- Filters -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Event Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($eventTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['event_type']); ?>"
                            <?= $filterType === $t['event_type'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($t['event_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="success" <?= $filterStatus === 'success' ? 'selected' : ''; ?>>Success</option>
                    <option value="failed"  <?= $filterStatus === 'failed'  ? 'selected' : ''; ?>>Failed</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control"
                       value="<?= htmlspecialchars($filterDate); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="em-btn em-btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="<?= em_base_url('pages/crm-hub/delivery-logs.php'); ?>" class="em-btn em-btn-secondary w-100">
                    <i class="fas fa-times me-1"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Log Table -->
<div class="em-card">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Event Delivery Log</h5>
        <small class="text-muted">Page <?= $page; ?> of <?= $totalPages; ?></small>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="em-empty-state">
                <i class="fas fa-inbox"></i>
                <h4>No Delivery Records</h4>
                <p>No events match the current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Event Type</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Payload Preview</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $payload = json_decode($log['payload'] ?? '{}', true);
                            $preview = is_array($payload)
                                ? implode(', ', array_slice(array_map(
                                    fn($k,$v) => "$k: " . (is_array($v) ? '[...]' : (string)$v),
                                    array_keys($payload), $payload), 0, 3))
                                : '';
                            ?>
                            <tr>
                                <td><small class="text-muted"><?= $log['id']; ?></small></td>
                                <td><code><?= htmlspecialchars($log['event_type']); ?></code></td>
                                <td>
                                    <small><?= htmlspecialchars($log['source_name'] ?? 'N/A'); ?></small>
                                </td>
                                <td><?= em_status_badge($log['status'] ?? 'unknown'); ?></td>
                                <td>
                                    <small class="text-muted font-monospace">
                                        <?= htmlspecialchars(substr($preview, 0, 70)); ?>
                                        <?= strlen($preview) > 70 ? '…' : ''; ?>
                                    </small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_time_ago($log['created_at']); ?>
                                        <br><?= em_format_date($log['created_at']); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="em-card-footer d-flex justify-content-between align-items-center">
                    <small class="text-muted">Showing <?= count($logs); ?> of <?= $totalRows; ?> records</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1; ?>&type=<?= urlencode($filterType); ?>&status=<?= urlencode($filterStatus); ?>&date=<?= urlencode($filterDate); ?>">
                                        &laquo;
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $p; ?>&type=<?= urlencode($filterType); ?>&status=<?= urlencode($filterStatus); ?>&date=<?= urlencode($filterDate); ?>">
                                        <?= $p; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1; ?>&type=<?= urlencode($filterType); ?>&status=<?= urlencode($filterStatus); ?>&date=<?= urlencode($filterDate); ?>">
                                        &raquo;
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
