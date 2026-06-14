<?php
/**
 * Event Manager - User Management
 * 
 * SAFETY: READ-ONLY display of admin users
 * Uses existing Gilaf Store admin users table
 * NO MODIFICATIONS to any tables
 * ONLY SELECT queries
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'User Management — Event Manager';
$currentPage = 'user-management';

// Get admin users from existing Gilaf Store table (READ-ONLY)
$adminUsers = [];
$totalAdmins = 0;

try {
    // READ-ONLY: Get admin users from existing table
    $adminUsers = em_fetch_all(
        "SELECT id, username, email, created_at, last_login, is_active, role
         FROM admin_users 
         WHERE is_admin = 1
         ORDER BY created_at DESC"
    );
    $totalAdmins = count($adminUsers);
} catch (Exception $e) {
    // Silent fail - table might not exist or have different structure
    try {
        // Fallback: try simpler query
        $adminUsers = em_fetch_all(
            "SELECT id, username, email, created_at
             FROM admin_users 
             ORDER BY created_at DESC"
        );
        $totalAdmins = count($adminUsers);
    } catch (Exception $e2) {
        // Silent fail
    }
}

// Get user activity statistics (READ-ONLY)
$userStats = [
    'total_admins' => $totalAdmins,
    'active_admins' => 0,
    'recent_logins' => 0
];

try {
    $userStats['active_admins'] = em_fetch("SELECT COUNT(*) as count FROM admin_users WHERE is_active = 1")['count'] ?? 0;
    $userStats['recent_logins'] = em_fetch("SELECT COUNT(*) as count FROM admin_users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0;
} catch (Exception $e) {
    // Silent fail
}

// Get Event Manager activity by user (READ-ONLY)
$userActivity = [];
try {
    $userActivity = em_fetch_all(
        "SELECT user_id, 
                COUNT(*) as action_count,
                MAX(created_at) as last_activity
         FROM em_audit_trail
         WHERE user_id IS NOT NULL
         GROUP BY user_id
         ORDER BY action_count DESC
         LIMIT 10"
    );
} catch (Exception $e) {
    // Silent fail
}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <h2>User Management</h2>
    <p class="text-muted">View admin users and their Event Manager activity</p>
</div>

<!-- User Stats -->
<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary">
            <i class="fas fa-users"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Total Admins</div>
            <div class="em-stat-value"><?= em_format_number($userStats['total_admins']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon success">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Active Admins</div>
            <div class="em-stat-value"><?= em_format_number($userStats['active_admins']); ?></div>
        </div>
    </div>
    
    <div class="em-stat-card">
        <div class="em-stat-icon info">
            <i class="fas fa-sign-in-alt"></i>
        </div>
        <div class="em-stat-info">
            <div class="em-stat-label">Recent Logins (7d)</div>
            <div class="em-stat-value"><?= em_format_number($userStats['recent_logins']); ?></div>
        </div>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle"></i>
    <strong>Note:</strong> This is a read-only view. User management (create/edit/delete) is handled through the main Gilaf Store admin panel.
</div>

<!-- Admin Users Table -->
<div class="em-card mb-4">
    <div class="em-card-header">
        <h5 class="mb-0">Admin Users</h5>
    </div>
    <div class="em-card-body p-0">
        <?php if (empty($adminUsers)): ?>
            <div class="em-empty-state">
                <i class="fas fa-users"></i>
                <h4>No Admin Users Found</h4>
                <p>Unable to retrieve admin user data.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adminUsers as $user): ?>
                            <tr>
                                <td>
                                    <strong>#<?= $user['id']; ?></strong>
                                </td>
                                <td>
                                    <i class="fas fa-user"></i>
                                    <?= htmlspecialchars($user['username']); ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($user['email'] ?? 'N/A'); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (isset($user['role'])): ?>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars(ucfirst($user['role'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($user['is_active'])): ?>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_format_date($user['created_at']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (isset($user['last_login']) && $user['last_login']): ?>
                                        <small class="text-muted">
                                            <?= em_time_ago($user['last_login']); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Event Manager Activity by User -->
<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">Event Manager Activity by User</h5>
    </div>
    <div class="em-card-body">
        <?php if (empty($userActivity)): ?>
            <div class="em-empty-state">
                <i class="fas fa-chart-bar"></i>
                <h4>No Activity Data</h4>
                <p>No Event Manager activity has been recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Total Actions</th>
                            <th>Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userActivity as $activity): ?>
                            <?php
                            // Find user details
                            $activityUser = null;
                            foreach ($adminUsers as $u) {
                                if ($u['id'] == $activity['user_id']) {
                                    $activityUser = $u;
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong>#<?= $activity['user_id']; ?></strong>
                                </td>
                                <td>
                                    <?php if ($activityUser): ?>
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($activityUser['username']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Unknown User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?= em_format_number($activity['action_count']); ?> actions
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= em_time_ago($activity['last_activity']); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
