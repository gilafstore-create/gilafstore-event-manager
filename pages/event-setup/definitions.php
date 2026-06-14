<?php
/**
 * Event Manager - Event Definitions
 * 
 * SAFETY: READ-ONLY display for now
 * CRUD operations will be in Phase 1B
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Event Definitions — Event Manager';
$currentPage = 'definitions';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$filterStatus = $_GET['status'] ?? '';

// Build query
$where = [];
$params = [];

if ($filterStatus) {
    $where[] = "status = ?";
    $params[] = $filterStatus;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalSql = "SELECT COUNT(*) as count FROM em_event_definitions $whereClause";
$total = em_fetch($totalSql, $params)['count'] ?? 0;

// Get event definitions
$sql = "SELECT * FROM em_event_definitions $whereClause ORDER BY created_at DESC LIMIT {$offset}, {$perPage}";
$definitions = em_fetch_all($sql, $params);

// Get pagination
$pagination = em_paginate($total, $perPage, $page);

// Get stats
$activeCount = em_fetch("SELECT COUNT(*) as count FROM em_event_definitions WHERE status = 'active'")['count'] ?? 0;
$inactiveCount = em_fetch("SELECT COUNT(*) as count FROM em_event_definitions WHERE status = 'inactive'")['count'] ?? 0;

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Event Definitions</h2>
    <p class="text-muted">Define and manage event types</p>
</div>

<!-- Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-list"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Definitions</div>
            <div class="em-stat-value"><?= em_format_number($total); ?></div>
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

<!-- Filters -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="em-form-control">
                        <option value="">All Status</option>
                        <option value="active" <?= $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <button type="submit" class="em-btn em-btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?= em_base_url('pages/event-setup/definitions.php'); ?>" class="em-btn em-btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Event Definitions Table -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">Event Definitions</h5>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($definitions)): ?>
            <div class="em-empty-state">
                <i class="fas fa-list"></i>
                <h4>No Event Definitions</h4>
                <p>No event definitions have been created yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Schema</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($definitions as $def): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($def['name']); ?></strong>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(substr($def['description'] ?? '', 0, 60)); ?>
                                        <?= strlen($def['description'] ?? '') > 60 ? '...' : ''; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($def['schema_id']): ?>
                                        <span class="badge bg-info">Schema #<?= $def['schema_id']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= em_status_badge($def['status']); ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_format_date($def['created_at']); ?>
                                    </small>
                                </td>
                                <td>
                                    <button class="em-btn em-btn-sm em-btn-secondary" 
                                            onclick='EventManager.showDefinitionDetails(<?= json_encode($def, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="em-card-footer">
                    <?= em_render_pagination($pagination, em_base_url('pages/event-setup/definitions.php') . '?' . http_build_query(array_filter(['status' => $filterStatus]))); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Definition Details Modal -->
<div class="modal fade" id="definitionDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Event Definition Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="definitionDetailsContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Register the handler AFTER footer scripts (event-manager.js) load.
// DOMContentLoaded fires once the body's synchronous scripts have run,
// so EventManager is guaranteed to exist here.
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.EventManager === 'undefined' && typeof EventManager === 'undefined') {
        window.EventManager = {};
    }
    var EM = (typeof EventManager !== 'undefined') ? EventManager : window.EventManager;

    EM.showDefinitionDetails = function(def) {
        try {
            const content = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Name:</strong><br>
                        ${def.name || 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-${def.status === 'active' ? 'success' : 'secondary'}">
                            ${def.status || 'unknown'}
                        </span>
                    </div>
                    <div class="col-12">
                        <strong>Description:</strong><br>
                        ${def.description || 'No description'}
                    </div>
                    <div class="col-md-6">
                        <strong>Schema ID:</strong><br>
                        ${def.schema_id || 'None'}
                    </div>
                    <div class="col-md-6">
                        <strong>Created By:</strong><br>
                        ${def.created_by ? 'User #' + def.created_by : 'System'}
                    </div>
                    <div class="col-md-6">
                        <strong>Created At:</strong><br>
                        ${def.created_at || 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Updated At:</strong><br>
                        ${def.updated_at || 'N/A'}
                    </div>
                </div>
            `;
            
            const contentDiv = document.getElementById('definitionDetailsContent');
            if (!contentDiv) {
                console.error('definitionDetailsContent div not found');
                return;
            }
            
            contentDiv.innerHTML = content;
            
            const modalEl = document.getElementById('definitionDetailsModal');
            if (!modalEl) {
                console.error('definitionDetailsModal not found');
                return;
            }
            
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            console.log('Modal should be visible now');
        } catch (error) {
            console.error('Error in showDefinitionDetails:', error);
            alert('Error showing definition details: ' + error.message);
        }
    };
});
</script>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
