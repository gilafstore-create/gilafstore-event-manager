<?php
/**
 * Trace Center - Live Stream
 * READ-ONLY. Shows recent em_event_logs refreshed every 10s
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Live Stream — Event Manager';
$currentPage = 'live-stream';

$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';

$where = []; $params = [];
if ($filterStatus) { $where[] = "status = ?";     $params[] = $filterStatus; }
if ($filterType)   { $where[] = "event_type = ?"; $params[] = $filterType; }
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$recentEvents = em_fetch_all("SELECT * FROM em_event_logs $whereClause ORDER BY created_at DESC LIMIT 50", $params);
$totalToday   = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE DATE(created_at)=CURDATE()")['count'] ?? 0;
$successToday = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE status='success' AND DATE(created_at)=CURDATE()")['count'] ?? 0;
$failedToday  = em_fetch("SELECT COUNT(*) as count FROM em_event_logs WHERE status='failed' AND DATE(created_at)=CURDATE()")['count'] ?? 0;
$eventTypes   = em_fetch_all("SELECT DISTINCT event_type FROM em_event_logs ORDER BY event_type");

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header d-flex justify-content-between align-items-start">
    <div>
        <h2>Live Stream <span class="badge bg-danger ms-2 blink">LIVE</span></h2>
        <p class="text-muted">Real-time feed of the last 50 events — auto-refreshes every 10s</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span id="lastRefresh" class="text-muted small"></span>
        <button class="em-btn em-btn-sm em-btn-secondary" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-bolt"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Events Today</div><div class="em-stat-value"><?= em_format_number($totalToday) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Success Today</div><div class="em-stat-value"><?= em_format_number($successToday) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon danger"><i class="fas fa-times-circle"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Failed Today</div><div class="em-stat-value"><?= em_format_number($failedToday) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-stream"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">In Stream</div><div class="em-stat-value"><?= count($recentEvents) ?></div></div>
    </div>
</div>

<!-- Filter bar -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['success','failed','pending'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
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
                <a href="live-stream.php" class="em-btn em-btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Stream -->
<div class="em-card" id="streamCard">
    <div class="em-card-header"><h5 class="mb-0">Event Stream <span class="badge bg-secondary"><?= count($recentEvents) ?></span></h5></div>
    <div class="em-card-body p-0">
        <?php if (empty($recentEvents)): ?>
            <div class="em-empty-state"><i class="fas fa-stream"></i><h4>No Events Yet</h4><p>Waiting for events to flow...</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead><tr><th>Event ID</th><th>Type</th><th>Status</th><th>Source</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentEvents as $e): ?>
                            <tr class="<?= $e['status']==='failed'?'table-danger':($e['status']==='pending'?'table-warning':'') ?>">
                                <td><code class="small"><?= htmlspecialchars($e['event_id']) ?></code></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($e['event_type']) ?></span></td>
                                <td><?= em_status_badge($e['status']) ?></td>
                                <td><?= $e['source_id'] ? '#'.(int)$e['source_id'] : '<span class="text-muted">—</span>' ?></td>
                                <td><small class="text-muted"><?= em_time_ago($e['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>.blink{animation:blink 1s step-start infinite}@keyframes blink{50%{opacity:0}}</style>
<script>
document.getElementById('lastRefresh').textContent = 'Last refresh: ' + new Date().toLocaleTimeString();
setTimeout(function(){ location.reload(); }, 10000);
</script>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
