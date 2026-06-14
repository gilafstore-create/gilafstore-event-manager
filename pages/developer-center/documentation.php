<?php
/**
 * Event Manager - Documentation
 * 
 * SAFETY: Static documentation page
 * NO DATABASE OPERATIONS
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';

$pageTitle = 'Documentation — Event Manager';
$currentPage = 'documentation';

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>Developer Documentation</h2>
    <p class="text-muted">API reference, guides, and code examples</p>
</div>

<div class="row g-4">
    <!-- Quick Start -->
    <div class="col-md-6">
        <div class="em-card">
            <div class="em-card-header">
                <h5 class="mb-0"><i class="fas fa-rocket text-primary"></i> Quick Start</h5>
            </div>
            <div class="em-card-body">
                <h6>Getting Started with Event Manager</h6>
                <p>Event Manager provides a comprehensive event-driven architecture for Gilaf Store.</p>
                
                <h6 class="mt-3">Key Concepts</h6>
                <ul>
                    <li><strong>Events:</strong> Actions that occur in your system</li>
                    <li><strong>Sources:</strong> Where events originate</li>
                    <li><strong>Destinations:</strong> Where events are delivered</li>
                    <li><strong>Event Bus:</strong> Message routing infrastructure</li>
                </ul>
                
                <h6 class="mt-3">Architecture</h6>
                <p>Event Manager uses a publish-subscribe pattern with:</p>
                <ul>
                    <li>Event definitions and schemas</li>
                    <li>Source and destination connectors</li>
                    <li>Message queuing and routing</li>
                    <li>Retry and error handling</li>
                    <li>Audit trail and compliance</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- API Reference -->
    <div class="col-md-6">
        <div class="em-card">
            <div class="em-card-header">
                <h5 class="mb-0"><i class="fas fa-code text-success"></i> API Reference</h5>
            </div>
            <div class="em-card-body">
                <h6>Event Manager PHP Functions</h6>
                
                <div class="mb-3">
                    <code class="bg-light p-2 d-block rounded">em_query($sql, $params)</code>
                    <small class="text-muted">Execute Event Manager query</small>
                </div>
                
                <div class="mb-3">
                    <code class="bg-light p-2 d-block rounded">em_fetch_all($sql, $params)</code>
                    <small class="text-muted">Fetch all rows</small>
                </div>
                
                <div class="mb-3">
                    <code class="bg-light p-2 d-block rounded">em_fetch($sql, $params)</code>
                    <small class="text-muted">Fetch single row</small>
                </div>
                
                <div class="mb-3">
                    <code class="bg-light p-2 d-block rounded">em_base_url($path)</code>
                    <small class="text-muted">Generate Event Manager URL</small>
                </div>
                
                <div class="mb-3">
                    <code class="bg-light p-2 d-block rounded">em_redirect($path, $message, $type)</code>
                    <small class="text-muted">Redirect with flash message</small>
                </div>
                
                <a href="#" class="em-btn em-btn-sm em-btn-primary mt-2">
                    <i class="fas fa-book"></i> View Full API Reference
                </a>
            </div>
        </div>
    </div>
    
    <!-- Database Schema -->
    <div class="col-md-12">
        <div class="em-card">
            <div class="em-card-header">
                <h5 class="mb-0"><i class="fas fa-database text-info"></i> Database Schema</h5>
            </div>
            <div class="em-card-body">
                <h6>Core Event Tables</h6>
                <div class="table-responsive">
                    <table class="em-table">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Description</th>
                                <th>Key Fields</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>em_event_definitions</code></td>
                                <td>Event type definitions</td>
                                <td>name, description, schema_id, status</td>
                            </tr>
                            <tr>
                                <td><code>em_event_sources</code></td>
                                <td>Event sources/producers</td>
                                <td>name, type, config, status</td>
                            </tr>
                            <tr>
                                <td><code>em_event_destinations</code></td>
                                <td>Event destinations/consumers</td>
                                <td>name, type, config, status</td>
                            </tr>
                            <tr>
                                <td><code>em_event_logs</code></td>
                                <td>Event activity logs</td>
                                <td>event_type, status, payload, response</td>
                            </tr>
                            <tr>
                                <td><code>em_failed_events</code></td>
                                <td>Failed event deliveries</td>
                                <td>event_type, failure_reason, retry_count</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Code Examples -->
    <div class="col-md-12">
        <div class="em-card">
            <div class="em-card-header">
                <h5 class="mb-0"><i class="fas fa-file-code text-warning"></i> Code Examples</h5>
            </div>
            <div class="em-card-body">
                <h6>Example 1: Query Event Logs</h6>
                <pre class="bg-light p-3 rounded"><code class="language-php">// Get recent events
$events = em_fetch_all(
    "SELECT * FROM em_event_logs 
     WHERE status = ? 
     ORDER BY created_at DESC 
     LIMIT 10",
    ['success']
);

foreach ($events as $event) {
    echo $event['event_type'] . "\n";
}</code></pre>
                
                <h6 class="mt-4">Example 2: Create Event Definition</h6>
                <pre class="bg-light p-3 rounded"><code class="language-php">// Insert new event definition
em_query(
    "INSERT INTO em_event_definitions 
     (name, description, status, created_by) 
     VALUES (?, ?, ?, ?)",
    ['order.created', 'Order creation event', 'active', $userId]
);

$eventId = em_last_insert_id();</code></pre>
                
                <h6 class="mt-4">Example 3: Log Event Activity</h6>
                <pre class="bg-light p-3 rounded"><code class="language-php">// Log event activity
em_log_activity(
    'create',
    'event_definition',
    $eventId,
    ['name' => 'order.created']
);</code></pre>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
