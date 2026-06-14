<?php
/**
 * Event Manager - Global Search
 * 
 * SAFETY: READ-ONLY search across all event data
 * NO MODIFICATIONS to any tables
 * ONLY SELECT queries
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Global Search — Event Manager';
$currentPage = 'global-search';

// Search parameters
$searchQuery = $_GET['q'] ?? '';
$searchType = $_GET['type'] ?? 'all';
$searchDate = $_GET['date'] ?? '';

$searchResults = [];
$totalResults = 0;

// Perform search if query provided (READ-ONLY)
if ($searchQuery) {
    $searchTerm = "%{$searchQuery}%";
    
    try {
        // Search in event logs
        if ($searchType === 'all' || $searchType === 'events') {
            $eventResults = em_fetch_all(
                "SELECT 'event_log' as result_type, id, event_type as title, 
                        payload as content, created_at, status
                 FROM em_event_logs 
                 WHERE event_type LIKE ? OR payload LIKE ?
                 ORDER BY created_at DESC
                 LIMIT 20",
                [$searchTerm, $searchTerm]
            );
            $searchResults = array_merge($searchResults, $eventResults);
        }
        
        // Search in failed events
        if ($searchType === 'all' || $searchType === 'failed') {
            $failedResults = em_fetch_all(
                "SELECT 'failed_event' as result_type, id, event_type as title,
                        failure_reason as content, failed_at as created_at, 'failed' as status
                 FROM em_failed_events 
                 WHERE event_type LIKE ? OR failure_reason LIKE ?
                 ORDER BY failed_at DESC
                 LIMIT 20",
                [$searchTerm, $searchTerm]
            );
            $searchResults = array_merge($searchResults, $failedResults);
        }
        
        // Search in audit trail
        if ($searchType === 'all' || $searchType === 'audit') {
            $auditResults = em_fetch_all(
                "SELECT 'audit_trail' as result_type, id, 
                        CONCAT(action, ' - ', entity_type) as title,
                        changes as content, created_at, action as status
                 FROM em_audit_trail 
                 WHERE entity_type LIKE ? OR changes LIKE ?
                 ORDER BY created_at DESC
                 LIMIT 20",
                [$searchTerm, $searchTerm]
            );
            $searchResults = array_merge($searchResults, $auditResults);
        }
        
        // Search in event definitions
        if ($searchType === 'all' || $searchType === 'definitions') {
            $defResults = em_fetch_all(
                "SELECT 'definition' as result_type, id, name as title,
                        description as content, created_at, status
                 FROM em_event_definitions 
                 WHERE name LIKE ? OR description LIKE ?
                 ORDER BY created_at DESC
                 LIMIT 20",
                [$searchTerm, $searchTerm]
            );
            $searchResults = array_merge($searchResults, $defResults);
        }
        
        $totalResults = count($searchResults);
        
        // Sort by date
        usort($searchResults, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Limit to 50 results
        $searchResults = array_slice($searchResults, 0, 50);
        
    } catch (Exception $e) {
        // Silent fail
    }
}

// Get search statistics (READ-ONLY)
$searchStats = [
    'total_events' => 0,
    'total_failed' => 0,
    'total_audit' => 0,
    'total_definitions' => 0
];

try {
    $searchStats['total_events'] = em_table_count('em_event_logs');
    $searchStats['total_failed'] = em_table_count('em_failed_events');
    $searchStats['total_audit'] = em_table_count('em_audit_trail');
    $searchStats['total_definitions'] = em_table_count('em_event_definitions');
} catch (Exception $e) {
    // Silent fail
}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Global Search</h2>
    <p class="text-muted">Search across all event data and logs</p>
</div>

<!-- Search Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-stream"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Event Logs</div>
            <div class="em-stat-value"><?= em_format_number($searchStats['total_events']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon danger">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Failed Events</div>
            <div class="em-stat-value"><?= em_format_number($searchStats['total_failed']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon info">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Audit Logs</div>
            <div class="em-stat-value"><?= em_format_number($searchStats['total_audit']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-list"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Definitions</div>
            <div class="em-stat-value"><?= em_format_number($searchStats['total_definitions']); ?></div>
        </div>
    </div>
</div>

<!-- Search Form -->
<div class="em-card mb-4">
    <div class="em-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="em-form-label">Search Query</label>
                <input type="text" 
                       name="q" 
                       class="em-form-control" 
                       placeholder="Search events, logs, audit trail..." 
                       value="<?= htmlspecialchars($searchQuery); ?>"
                       autofocus>
            </div>
            <div class="col-md-3">
                <label class="em-form-label">Search In</label>
                <select name="type" class="em-form-control">
                    <option value="all" <?= $searchType === 'all' ? 'selected' : ''; ?>>All Sources</option>
                    <option value="events" <?= $searchType === 'events' ? 'selected' : ''; ?>>Event Logs</option>
                    <option value="failed" <?= $searchType === 'failed' ? 'selected' : ''; ?>>Failed Events</option>
                    <option value="audit" <?= $searchType === 'audit' ? 'selected' : ''; ?>>Audit Trail</option>
                    <option value="definitions" <?= $searchType === 'definitions' ? 'selected' : ''; ?>>Event Definitions</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="em-form-label">Date Filter</label>
                <input type="date" name="date" class="em-form-control" value="<?= htmlspecialchars($searchDate); ?>">
            </div>
            <div class="col-md-2">
                <label class="em-form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="em-btn em-btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search Results -->
<?php if ($searchQuery): ?>
    <div class="em-card">
        <div class="em-card-header">
            <h5 class="mb-0">
                Search Results 
                <?php if ($totalResults > 0): ?>
                    <span class="badge bg-primary"><?= $totalResults; ?> found</span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="em-card-body">
            <?php if (empty($searchResults)): ?>
                <div class="em-empty-state">
                    <i class="fas fa-search"></i>
                    <h4>No Results Found</h4>
                    <p>No results match your search query "<strong><?= htmlspecialchars($searchQuery); ?></strong>"</p>
                    <p class="text-muted">Try different keywords or adjust your filters.</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($searchResults as $result): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">
                                        <span class="badge bg-<?= 
                                            $result['result_type'] === 'event_log' ? 'primary' : 
                                            ($result['result_type'] === 'failed_event' ? 'danger' : 
                                            ($result['result_type'] === 'audit_trail' ? 'info' : 'success')); 
                                        ?>">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $result['result_type']))); ?>
                                        </span>
                                        <?= htmlspecialchars($result['title']); ?>
                                    </h6>
                                    <p class="mb-1 text-muted">
                                        <?= htmlspecialchars(substr($result['content'] ?? '', 0, 150)); ?>
                                        <?= strlen($result['content'] ?? '') > 150 ? '...' : ''; ?>
                                    </p>
                                </div>
                                <?php if (isset($result['status'])): ?>
                                    <?= em_status_badge($result['status']); ?>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i>
                                <?= em_format_date($result['created_at']); ?>
                                (<?= em_time_ago($result['created_at']); ?>)
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($totalResults > 50): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle"></i>
                        Showing first 50 results. Refine your search for more specific results.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Search Tips -->
    <div class="em-card">
        <div class="em-card-header">
            <h5 class="mb-0">Search Tips</h5>
        </div>
        <div class="em-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <h6><i class="fas fa-lightbulb text-warning"></i> Search Examples</h6>
                    <ul>
                        <li>Search by event type: <code>order.created</code></li>
                        <li>Search by error message: <code>timeout</code></li>
                        <li>Search by entity: <code>customer</code></li>
                        <li>Search by action: <code>create</code></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-filter text-primary"></i> Filter Options</h6>
                    <ul>
                        <li><strong>All Sources:</strong> Search everywhere</li>
                        <li><strong>Event Logs:</strong> Search event activity</li>
                        <li><strong>Failed Events:</strong> Search failures only</li>
                        <li><strong>Audit Trail:</strong> Search system changes</li>
                        <li><strong>Definitions:</strong> Search event types</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
