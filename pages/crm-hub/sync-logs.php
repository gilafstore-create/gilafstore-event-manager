<?php
/**
 * Event Manager - CRM Sync Logs
 *
 * Read-only view of em_crm_sync_logs and their associated failures.
 * SAFETY: Admin-only (via em_header), read-only.
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'CRM Sync Logs — Event Manager';
$currentPage = 'crm-sync-logs';

$logs = [];
$failures = [];
$stats = ['total' => 0, 'success' => 0, 'partial' => 0, 'failed' => 0, 'records' => 0];

try {
    $logs = em_fetch_all(
        "SELECT l.*, c.name AS connection_name, c.crm_type
         FROM em_crm_sync_logs l
         LEFT JOIN em_crm_connections c ON c.id = l.connection_id
         ORDER BY l.started_at DESC
         LIMIT 100"
    );

    $row = em_fetch(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) AS success,
                SUM(CASE WHEN status='partial' THEN 1 ELSE 0 END) AS partial,
                SUM(CASE WHEN status='failed'  THEN 1 ELSE 0 END) AS failed,
                COALESCE(SUM(records_synced),0) AS records
         FROM em_crm_sync_logs"
    );
    if ($row) {
        $stats = [
            'total'   => (int)$row['total'],
            'success' => (int)$row['success'],
            'partial' => (int)$row['partial'],
            'failed'  => (int)$row['failed'],
            'records' => (int)$row['records'],
        ];
    }

    $failures = em_fetch_all(
        "SELECT f.*, l.connection_id, l.sync_type
         FROM em_crm_sync_failures f
         INNER JOIN em_crm_sync_logs l ON l.id = f.sync_log_id
         ORDER BY f.created_at DESC
         LIMIT 50"
    );
} catch (Exception $e) {
    error_log('EM_CRM sync-logs page: ' . $e->getMessage());
}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <div>
        <h1 class="em-page-title">CRM Sync Logs</h1>
        <p class="em-page-subtitle">Outbound synchronisation history and failures</p>
    </div>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card"><div class="em-stat-info"><div class="em-stat-label">Total Syncs</div>
        <div class="em-stat-value"><?= em_format_number($stats['total']) ?></div></div></div>
    <div class="em-stat-card"><div class="em-stat-info"><div class="em-stat-label">Successful</div>
        <div class="em-stat-value text-success"><?= em_format_number($stats['success']) ?></div></div></div>
    <div class="em-stat-card"><div class="em-stat-info"><div class="em-stat-label">Partial / Failed</div>
        <div class="em-stat-value text-danger"><?= em_format_number($stats['partial'] + $stats['failed']) ?></div></div></div>
    <div class="em-stat-card"><div class="em-stat-info"><div class="em-stat-label">Records Synced</div>
        <div class="em-stat-value"><?= em_format_number($stats['records']) ?></div></div></div>
</div>

<div class="em-card mb-4">
    <div class="em-card-header"><h5 class="mb-0">Recent Sync Runs</h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="em-empty-state"><i class="fas fa-sync-alt"></i><p>No sync runs yet</p>
                <span>Sync logs appear after events trigger CRM synchronisation.</span></div>
        <?php else: ?>
        <div class="em-table-container">
            <table class="em-table">
                <thead><tr><th>Connection</th><th>Event</th><th>Status</th><th>Records</th><th>Error</th><th>Started</th><th>Completed</th></tr></thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($l['connection_name'] ?? ('#' . $l['connection_id'])) ?></strong></td>
                        <td><?= htmlspecialchars($l['sync_type']) ?></td>
                        <td><?= em_status_badge($l['status']) ?></td>
                        <td><?= em_format_number($l['records_synced']) ?></td>
                        <td><small class="text-danger"><?= htmlspecialchars($l['error_message'] ?? '') ?></small></td>
                        <td><small><?= em_format_date($l['started_at']) ?></small></td>
                        <td><small><?= $l['completed_at'] ? em_format_date($l['completed_at']) : '—' ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header"><h5 class="mb-0">Recent Failures</h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($failures)): ?>
            <div class="em-empty-state"><i class="fas fa-check-circle"></i><p>No failures recorded</p></div>
        <?php else: ?>
        <div class="em-table-container">
            <table class="em-table">
                <thead><tr><th>Sync Log</th><th>Event</th><th>Record</th><th>Error</th><th>When</th></tr></thead>
                <tbody>
                    <?php foreach ($failures as $f): ?>
                    <tr>
                        <td>#<?= (int)$f['sync_log_id'] ?></td>
                        <td><?= htmlspecialchars($f['sync_type'] ?? '') ?></td>
                        <td><code><?= htmlspecialchars($f['record_id'] ?? '—') ?></code></td>
                        <td><small class="text-danger"><?= htmlspecialchars($f['error_message'] ?? '') ?></small></td>
                        <td><small><?= em_format_date($f['created_at']) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
