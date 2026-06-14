<?php
/**
 * Event Manager - Audit Trail
 * 
 * SAFETY: READ-ONLY display of audit logs
 * Displays both Event Manager and existing Gilaf Store audit logs
 * NO MODIFICATIONS to existing tables
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Audit Trail — Event Manager';
$currentPage = 'audit-trail';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$filterAction = $_GET['action'] ?? '';
$filterUser = $_GET['user'] ?? '';
$filterDate = $_GET['date'] ?? '';

// Build query for Event Manager audit trail
$where = [];
$params = [];

if ($filterAction) {
    $where[] = "action = ?";
    $params[] = $filterAction;
}

if ($filterUser) {
    $where[] = "user_id = ?";
    $params[] = $filterUser;
}

if ($filterDate) {
    $where[] = "DATE(created_at) = ?";
    $params[] = $filterDate;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalSql = "SELECT COUNT(*) as count FROM em_audit_trail $whereClause";
$total = em_fetch($totalSql, $params)['count'] ?? 0;

// Get audit logs
$sql = "SELECT * FROM em_audit_trail $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}";
$auditLogs = em_fetch_all($sql, $params);

// Get pagination
$pagination = em_paginate($total, $perPage, $page);

// Get unique actions
$actions = em_fetch_all("SELECT DISTINCT action FROM em_audit_trail ORDER BY action");

// Get stats
$todayCount = em_fetch("SELECT COUNT(*) as count FROM em_audit_trail WHERE DATE(created_at) = CURDATE()")['count'] ?? 0;
$weekCount = em_fetch("SELECT COUNT(*) as count FROM em_audit_trail WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Audit Trail</h2>
    <p class="text-muted">Track all system changes and user activities</p>
</div>

<!-- Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Audit Logs</div>
            <div class="em-stat-value"><?= em_format_number($total); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Today's Activity</div>
            <div class="em-stat-value"><?= em_format_number($todayCount); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon info">
            <i class="fas fa-calendar-week"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">This Week</div>
            <div class="em-stat-value"><?= em_format_number($weekCount); ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="em-form-label">Action</label>
                <select name="action" class="em-form-control">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?= htmlspecialchars($action['action']); ?>" <?= $filterAction === $action['action'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars(ucfirst($action['action'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="em-form-label">User ID</label>
                <input type="number" name="user" class="em-form-control" placeholder="User ID" value="<?= htmlspecialchars($filterUser); ?>">
            </div>
            <div class="col-md-3">
                <label class="em-form-label">Date</label>
                <input type="date" name="date" class="em-form-control" value="<?= htmlspecialchars($filterDate); ?>">
            </div>
            <div class="col-md-3">
                <label class="em-form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="em-btn em-btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?= em_base_url('pages/governance/audit-trail.php'); ?>" class="em-btn em-btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Audit Logs Table -->
<div class="em-card">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Audit Log History</h5>
        <button class="em-btn em-btn-sm em-btn-secondary" onclick="EventManager.exportAuditLogs()">
            <i class="fas fa-download"></i> Export CSV
        </button>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($auditLogs)): ?>
            <div class="em-empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h4>No Audit Logs Found</h4>
                <p>No audit logs match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?= 
                                        $log['action'] === 'create' ? 'success' : 
                                        ($log['action'] === 'delete' ? 'danger' : 
                                        ($log['action'] === 'update' ? 'info' : 'secondary')); 
                                    ?>">
                                        <?= htmlspecialchars(ucfirst($log['action'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($log['entity_type']); ?></strong>
                                    <br>
                                    <small class="text-muted">ID: <?= htmlspecialchars($log['entity_id'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <?php if ($log['user_id']): ?>
                                        <i class="fas fa-user"></i> User #<?= htmlspecialchars($log['user_id']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_format_date($log['created_at']); ?>
                                        <br>
                                        <?= em_time_ago($log['created_at']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($log['changes'])): ?>
                                        <button class="em-btn em-btn-sm em-btn-secondary" 
                                                onclick='EventManager.showAuditDetails(<?= json_encode($log, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer">
                    <?= em_render_pagination($pagination, em_base_url('pages/governance/audit-trail.php') . '?' . http_build_query(array_filter(['action' => $filterAction, 'user' => $filterUser, 'date' => $filterDate]))); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Audit Details Modal -->
<div class="modal fade" id="auditDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="auditDetailsContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Register AFTER footer scripts (event-manager.js) load.
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.EventManager === 'undefined' && typeof EventManager === 'undefined') {
        window.EventManager = {};
    }
    var EM = (typeof EventManager !== 'undefined') ? EventManager : window.EventManager;

    EM.showAuditDetails = function(log) {
        try {
    const content = `
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Action:</strong><br>
                <span class="badge bg-${log.action === 'create' ? 'success' : (log.action === 'delete' ? 'danger' : (log.action === 'update' ? 'info' : 'secondary'))}">
                    ${log.action.toUpperCase()}
                </span>
            </div>
            <div class="col-md-6">
                <strong>Entity:</strong><br>
                ${log.entity_type} (ID: ${log.entity_id || 'N/A'})
            </div>
            <div class="col-md-6">
                <strong>User:</strong><br>
                ${log.user_id ? 'User #' + log.user_id : 'System'}
            </div>
            <div class="col-md-6">
                <strong>IP Address:</strong><br>
                ${log.ip_address || 'N/A'}
            </div>
            <div class="col-12">
                <strong>Timestamp:</strong><br>
                ${log.created_at}
            </div>
            <div class="col-12">
                <strong>Changes:</strong><br>
                <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">${log.changes || 'No changes recorded'}</pre>
            </div>
        </div>
    `;
    
            const contentDiv = document.getElementById('auditDetailsContent');
            if (!contentDiv) {
                console.error('auditDetailsContent div not found');
                return;
            }
            contentDiv.innerHTML = content;
            
            const modalEl = document.getElementById('auditDetailsModal');
            if (!modalEl) {
                console.error('auditDetailsModal not found');
                return;
            }
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            console.log('Modal should be visible now');
        } catch (error) {
            console.error('Error in showAuditDetails:', error);
            alert('Error showing audit details: ' + error.message);
        }
    };

    EM.exportAuditLogs = function() {
    // Get current filters
    const params = new URLSearchParams(window.location.search);
    const action = params.get('action') || '';
    const user = params.get('user') || '';
    const date = params.get('date') || '';
    
    // Build export URL with filters
    let exportUrl = '<?= base_url('event-manager/api/export_audit_logs.php') ?>';
    const queryParams = [];
    if (action) queryParams.push('action=' + encodeURIComponent(action));
    if (user) queryParams.push('user=' + encodeURIComponent(user));
    if (date) queryParams.push('date=' + encodeURIComponent(date));
    
    if (queryParams.length > 0) {
        exportUrl += '?' + queryParams.join('&');
    }
    
    // Trigger download
    window.location.href = exportUrl;
    };
});
</script>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
