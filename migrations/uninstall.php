<?php
/**
 * Event Manager - Uninstall Script
 * 
 * SAFETY: Only drops em_ prefixed tables
 * NO MODIFICATIONS to existing tables
 */

require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../includes/em_auth.php';
require_once __DIR__ . '/../includes/em_functions.php';

// Require admin authentication
em_require_auth();

$errors = [];
$success = [];
$tablesDropped = 0;

// Process uninstallation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'DELETE') {
    try {
        global $pdo;
        
        // Get all em_ tables
        $stmt = $pdo->query("SHOW TABLES LIKE 'em_%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            $errors[] = "No Event Manager tables found to uninstall";
        } else {
            // Start transaction
            $pdo->beginTransaction();
            
            // Drop all em_ tables
            foreach ($tables as $table) {
                // Safety check: only drop tables with em_ prefix
                if (str_starts_with($table, 'em_')) {
                    $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                    $tablesDropped++;
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            $success[] = "Successfully removed {$tablesDropped} Event Manager tables";
            $success[] = "All Event Manager data has been deleted";
            $success[] = "Existing platform data remains untouched";
        }
        
    } catch (PDOException $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $errors[] = "Uninstall error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Manager Uninstall</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: #f5f5f5;
            padding: 40px 0;
        }
        .uninstall-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .uninstall-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 40px;
        }
        .uninstall-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .uninstall-header i {
            font-size: 64px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="uninstall-container">
        <div class="uninstall-card">
            <div class="uninstall-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h1>Uninstall Event Manager</h1>
                <p class="text-muted">Remove all Event Manager tables and data</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Uninstall Failed</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Uninstall Successful!</h5>
                    <ul class="mb-0">
                        <?php foreach ($success as $msg): ?>
                            <li><?= htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-3">
                        <a href="<?= base_url('admin/index.php'); ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Admin
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
                <div class="warning-box">
                    <h5><i class="fas fa-exclamation-triangle"></i> Warning: This action cannot be undone!</h5>
                    <p class="mb-0">This will permanently delete all Event Manager tables and data.</p>
                </div>

                <div class="alert alert-danger">
                    <h6><i class="fas fa-trash"></i> What will be deleted:</h6>
                    <ul class="mb-0">
                        <li>All Event Manager database tables (em_ prefix)</li>
                        <li>All event logs and history</li>
                        <li>All configurations and settings</li>
                        <li>All CRM integration data</li>
                    </ul>
                </div>

                <div class="alert alert-success">
                    <h6><i class="fas fa-shield-alt"></i> What will be preserved:</h6>
                    <ul class="mb-0">
                        <li>All existing platform data (orders, customers, products)</li>
                        <li>All existing functionality</li>
                        <li>All existing integrations</li>
                        <li>Zero impact on existing system</li>
                    </ul>
                </div>

                <form method="POST" class="text-center" onsubmit="return confirm('Are you absolutely sure? This will permanently delete all Event Manager data!');">
                    <div class="mb-4">
                        <label class="form-label">Type <strong>DELETE</strong> to confirm:</label>
                        <input type="text" name="confirm" class="form-control text-center" required placeholder="DELETE" style="max-width: 200px; margin: 0 auto;">
                    </div>
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash"></i>
                        Uninstall Event Manager
                    </button>
                    <div class="mt-3">
                        <a href="<?= em_base_url('pages/dashboard.php'); ?>" class="btn btn-link">
                            <i class="fas fa-arrow-left"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
