<?php
/**
 * Governance - Permissions & Roles
 * READ-ONLY. em_permissions: id, name, description, created_at
 *            em_roles: id, name, description, permissions (JSON), created_at, updated_at
 */
require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Permissions & Roles — Event Manager';
$currentPage = 'permissions';

$permissions   = em_fetch_all("SELECT * FROM em_permissions ORDER BY name");
$roles         = em_fetch_all("SELECT * FROM em_roles ORDER BY name");
$totalPerms    = count($permissions);
$totalRoles    = count($roles);

require_once __DIR__ . '/../../includes/em_header.php';
?>
<div class="em-page-header">
    <h2>Permissions &amp; Roles</h2>
    <p class="text-muted">View all defined permissions and role configurations</p>
</div>

<div class="em-stats-grid mb-4">
    <div class="em-stat-card">
        <div class="em-stat-icon primary"><i class="fas fa-lock"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Permissions</div><div class="em-stat-value"><?= em_format_number($totalPerms) ?></div></div>
    </div>
    <div class="em-stat-card">
        <div class="em-stat-icon info"><i class="fas fa-user-tag"></i></div>
        <div class="em-stat-info"><div class="em-stat-label">Total Roles</div><div class="em-stat-value"><?= em_format_number($totalRoles) ?></div></div>
    </div>
</div>

<div class="row g-4">
    <!-- Permissions -->
    <div class="col-lg-5">
        <div class="em-card">
            <div class="em-card-header"><h5 class="mb-0"><i class="fas fa-lock me-2"></i>Permissions <span class="badge bg-secondary"><?= $totalPerms ?></span></h5></div>
            <div class="em-card-body p-0">
                <?php if (empty($permissions)): ?>
                    <div class="em-empty-state py-4"><i class="fas fa-lock"></i><h5>No Permissions Defined</h5></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="em-table">
                            <thead><tr><th>Permission</th><th>Description</th><th>Created</th></tr></thead>
                            <tbody>
                                <?php foreach ($permissions as $p): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($p['name']) ?></code></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($p['description'] ?? '—') ?></small></td>
                                        <td><small class="text-muted"><?= em_time_ago($p['created_at']) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Roles -->
    <div class="col-lg-7">
        <div class="em-card">
            <div class="em-card-header"><h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>Roles <span class="badge bg-secondary"><?= $totalRoles ?></span></h5></div>
            <div class="em-card-body p-0">
                <?php if (empty($roles)): ?>
                    <div class="em-empty-state py-4"><i class="fas fa-user-tag"></i><h5>No Roles Defined</h5></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="em-table">
                            <thead><tr><th>Role</th><th>Description</th><th>Permissions</th><th>Updated</th></tr></thead>
                            <tbody>
                                <?php foreach ($roles as $r):
                                    $perms = is_string($r['permissions']) ? json_decode($r['permissions'], true) : $r['permissions'];
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($r['description'] ?? '—') ?></small></td>
                                        <td>
                                            <?php if (is_array($perms) && count($perms) > 0): ?>
                                                <?php foreach (array_slice($perms, 0, 3) as $perm): ?>
                                                    <span class="badge bg-info me-1"><?= htmlspecialchars($perm) ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($perms) > 3): ?>
                                                    <span class="badge bg-secondary">+<?= count($perms) - 3 ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?= em_time_ago($r['updated_at']) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
