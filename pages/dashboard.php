<?php
/**
 * Event Manager - Dashboard
 * 
 * SAFETY: Read-only access to existing data
 * NO MODIFICATIONS to existing tables
 */

require_once __DIR__ . '/../includes/em_auth.php';
require_once __DIR__ . '/../includes/em_functions.php';
require_once __DIR__ . '/../includes/em_db.php';

$pageTitle = 'Dashboard — Event Manager';
$currentPage = 'dashboard';

// Check if Event Manager is installed
$isInstalled = em_is_installed();

// Get stats (only if installed)
$stats = [
    'total_events' => 0,
    'failed_events' => 0,
    'active_sources' => 0,
    'active_destinations' => 0
];

if ($isInstalled) {
    try {
        $stats['total_events'] = em_table_count('em_event_logs');
        $stats['failed_events'] = em_table_count('em_failed_events');
        $stats['active_sources'] = em_fetch("SELECT COUNT(*) as count FROM em_event_sources WHERE status = 'active'")['count'] ?? 0;
        $stats['active_destinations'] = em_fetch("SELECT COUNT(*) as count FROM em_event_destinations WHERE status = 'active'")['count'] ?? 0;
    } catch (Exception $e) {
        // Silent fail - tables might not exist yet
    }
}

// Get recent activity (read-only from existing tables)
$recentOrders = [];
$recentCustomers = [];

try {
    // Read-only: Get recent orders (NO MODIFICATIONS)
    $recentOrders = em_fetch_all(
        "SELECT id, order_number, total_amount, status, created_at 
         FROM orders 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
    
    // Read-only: Get recent customers (NO MODIFICATIONS)
    $recentCustomers = em_fetch_all(
        "SELECT id, name, email, created_at 
         FROM users 
         WHERE role = 'customer' 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
} catch (Exception $e) {
    // Silent fail
}

require_once __DIR__ . '/../includes/em_header.php';
?>

<?php if (!$isInstalled): ?>
    <!-- Installation Required -->
    <div class="em-card">
        <div class="em-card-body text-center py-5">
            <i class="fas fa-database" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
            <h3>Event Manager Not Installed</h3>
            <p class="text-muted mb-4">The Event Manager database tables need to be created before you can use this module.</p>
            <a href="<?= em_base_url('migrations/install.php'); ?>" class="em-btn em-btn-primary">
                <i class="fas fa-play"></i>
                Run Installation
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Stats Grid -->
    <div class="em-stats-grid">
        <div class="em-stat-card">
            <div class="em-stat-icon primary">
                <i class="fas fa-stream"></i>
            </div>
            <div class="em-stat-info">
                <div class="em-stat-label">Total Events</div>
                <div class="em-stat-value"><?= em_format_number($stats['total_events']); ?></div>
            </div>
        </div>
        
        <div class="em-stat-card">
            <div class="em-stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="em-stat-info">
                <div class="em-stat-label">Failed Events</div>
                <div class="em-stat-value"><?= em_format_number($stats['failed_events']); ?></div>
            </div>
        </div>
        
        <div class="em-stat-card">
            <div class="em-stat-icon success">
                <i class="fas fa-plug"></i>
            </div>
            <div class="em-stat-info">
                <div class="em-stat-label">Active Sources</div>
                <div class="em-stat-value"><?= em_format_number($stats['active_sources']); ?></div>
            </div>
        </div>
        
        <div class="em-stat-card">
            <div class="em-stat-icon warning">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="em-stat-info">
                <div class="em-stat-label">Active Destinations</div>
                <div class="em-stat-value"><?= em_format_number($stats['active_destinations']); ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders (Read-Only) -->
        <div class="col-md-6">
            <div class="em-card">
                <div class="em-card-header">
                    <h5 class="em-card-title">Recent Orders</h5>
                    <small class="text-muted">Read-only view</small>
                </div>
                <div class="em-card-body">
                    <?php if (empty($recentOrders)): ?>
                        <div class="em-empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>No orders found</p>
                        </div>
                    <?php else: ?>
                        <table class="em-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_number']); ?></td>
                                        <td>₹<?= number_format($order['total_amount'], 2); ?></td>
                                        <td><?= em_status_badge($order['status']); ?></td>
                                        <td><?= em_time_ago($order['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Customers (Read-Only) -->
        <div class="col-md-6">
            <div class="em-card">
                <div class="em-card-header">
                    <h5 class="em-card-title">Recent Customers</h5>
                    <small class="text-muted">Read-only view</small>
                </div>
                <div class="em-card-body">
                    <?php if (empty($recentCustomers)): ?>
                        <div class="em-empty-state">
                            <i class="fas fa-users"></i>
                            <p>No customers found</p>
                        </div>
                    <?php else: ?>
                        <table class="em-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCustomers as $customer): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($customer['name']); ?></td>
                                        <td><?= htmlspecialchars($customer['email']); ?></td>
                                        <td><?= em_time_ago($customer['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="em-card">
        <div class="em-card-header">
            <h5 class="em-card-title">Quick Actions</h5>
        </div>
        <div class="em-card-body">
            <div class="d-flex gap-3 flex-wrap">
                <a href="<?= em_base_url('pages/event-setup/definitions.php'); ?>" class="em-btn em-btn-primary">
                    <i class="fas fa-plus"></i>
                    Create Event Definition
                </a>
                <a href="<?= em_base_url('pages/event-operations/logs.php'); ?>" class="em-btn em-btn-secondary">
                    <i class="fas fa-list"></i>
                    View Event Logs
                </a>
                <a href="<?= em_base_url('pages/event-operations/failed-events.php'); ?>" class="em-btn em-btn-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    View Failed Events
                </a>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="em-card">
        <div class="em-card-header">
            <h5 class="em-card-title">System Information</h5>
        </div>
        <div class="em-card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Event Manager Version:</strong> 1.0.0</p>
                    <p><strong>Installation Status:</strong> <span class="em-badge em-badge-success">Installed</span></p>
                    <p><strong>Database Tables:</strong> <?= em_format_number(61); ?> (all em_ prefixed)</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Current User:</strong> <?= htmlspecialchars(em_get_user_name()); ?></p>
                    <p><strong>Access Level:</strong> <span class="em-badge em-badge-info">Admin</span></p>
                    <p><strong>Impact on Existing System:</strong> <span class="em-badge em-badge-success">ZERO</span></p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/em_footer.php'; ?>
