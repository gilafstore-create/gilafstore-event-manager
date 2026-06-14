<?php
/**
 * Event Manager — Phase 3A Seed Data
 *
 * Populates:
 *   • em_event_definitions  (13 canonical event types)
 *   • em_event_sources       (6 system sources)
 *   • em_event_destinations  (4 destinations)
 *
 * SAFETY:
 *   ✅ INSERT only — no ALTER, no UPDATE, no DELETE
 *   ✅ Idempotent — checks existence before inserting
 *   ✅ Admin-authenticated
 *   ✅ Writes only to em_ prefixed tables
 *   ✅ Wrapped in try/catch
 */

require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../includes/em_auth.php';
require_once __DIR__ . '/../includes/em_functions.php';

em_require_auth();

$pageTitle  = 'Phase 3A Seed Data — Event Manager';
$results    = [];
$errors     = [];

// ── Helper: insert one record if no row with matching name exists ─────────────
function seed_if_missing(PDO $pdo, string $table, string $nameCol, array $row): string
{
    try {
        $check = $pdo->prepare("SELECT id FROM {$table} WHERE {$nameCol} = ? LIMIT 1");
        $check->execute([$row[$nameCol]]);
        if ($check->fetch()) {
            return 'skipped';
        }
        $cols   = implode(', ', array_keys($row));
        $pholds = implode(', ', array_fill(0, count($row), '?'));
        $stmt   = $pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$pholds})");
        $stmt->execute(array_values($row));
        return 'inserted';
    } catch (\Throwable $e) {
        return 'error: ' . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// RUN SEED
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'seed') {

    try {
        global $pdo;

        // ── Verify all three target tables exist ─────────────────────────────
        $required = ['em_event_definitions', 'em_event_sources', 'em_event_destinations'];
        foreach ($required as $tbl) {
            $chk = $pdo->query("SHOW TABLES LIKE '{$tbl}'");
            if (!$chk || $chk->rowCount() === 0) {
                $errors[] = "Table '{$tbl}' does not exist. Run the Event Manager installer first.";
            }
        }

        if (empty($errors)) {

            // ══════════════════════════════════════════════════════════════════
            // 1. EVENT DEFINITIONS (13 canonical types)
            // ══════════════════════════════════════════════════════════════════
            $definitions = [
                ['name' => 'ORDER_CREATED',      'description' => 'Fired when a new order is placed (COD, Razorpay, UPI).',              'status' => 'active'],
                ['name' => 'ORDER_UPDATED',       'description' => 'Fired when an order status is updated by admin.',                     'status' => 'active'],
                ['name' => 'ORDER_CANCELLED',     'description' => 'Fired when an order is cancelled or refunded.',                       'status' => 'active'],
                ['name' => 'PAYMENT_SUCCESS',     'description' => 'Fired when a payment is verified and captured successfully.',         'status' => 'active'],
                ['name' => 'PAYMENT_FAILED',      'description' => 'Fired when a payment fails signature or gateway verification.',       'status' => 'active'],
                ['name' => 'CUSTOMER_CREATED',    'description' => 'Fired when a new customer registers (direct or guest checkout).',     'status' => 'active'],
                ['name' => 'CUSTOMER_UPDATED',    'description' => 'Fired when a customer profile is updated.',                          'status' => 'active'],
                ['name' => 'PRODUCT_CREATED',     'description' => 'Fired when a new product is created by an admin.',                   'status' => 'active'],
                ['name' => 'PRODUCT_UPDATED',     'description' => 'Fired when a product is updated by an admin.',                       'status' => 'active'],
                ['name' => 'CRM_SYNC_STARTED',    'description' => 'Fired when a CRM synchronisation task begins.',                      'status' => 'active'],
                ['name' => 'CRM_SYNC_COMPLETED',  'description' => 'Fired when a CRM synchronisation task finishes.',                    'status' => 'active'],
                ['name' => 'WEBHOOK_SENT',        'description' => 'Fired when an outbound webhook is successfully delivered.',           'status' => 'active'],
                ['name' => 'WEBHOOK_FAILED',      'description' => 'Fired when an outbound webhook delivery fails.',                      'status' => 'active'],
            ];

            $defInserted = 0;
            $defSkipped  = 0;
            foreach ($definitions as $def) {
                $outcome = seed_if_missing($pdo, 'em_event_definitions', 'name', $def);
                if ($outcome === 'inserted') {
                    $defInserted++;
                } elseif ($outcome === 'skipped') {
                    $defSkipped++;
                } else {
                    $errors[] = 'em_event_definitions [' . $def['name'] . ']: ' . $outcome;
                }
            }
            $results[] = "em_event_definitions → inserted {$defInserted}, skipped {$defSkipped} (already existed)";

            // ══════════════════════════════════════════════════════════════════
            // 2. EVENT SOURCES (6 system sources)
            // ══════════════════════════════════════════════════════════════════
            $sources = [
                ['name' => 'Gilaf Store — Orders',     'type' => 'internal', 'config' => json_encode(['description' => 'COD, Razorpay, UPI order placement']),          'status' => 'active'],
                ['name' => 'Gilaf Store — Customers',  'type' => 'internal', 'config' => json_encode(['description' => 'Customer registration and profile updates']),    'status' => 'active'],
                ['name' => 'Gilaf Store — Products',   'type' => 'internal', 'config' => json_encode(['description' => 'Admin product CRUD operations']),                'status' => 'active'],
                ['name' => 'Razorpay Gateway',         'type' => 'external', 'config' => json_encode(['description' => 'Razorpay payment capture and webhook receiver']),'status' => 'active'],
                ['name' => 'Gilaf Store — Emails',     'type' => 'internal', 'config' => json_encode(['description' => 'Transactional order email notifications']),      'status' => 'active'],
                ['name' => 'CRM Webhook System',       'type' => 'external', 'config' => json_encode(['description' => 'Outbound CRM webhook delivery pipeline']),       'status' => 'active'],
            ];

            $srcInserted = 0;
            $srcSkipped  = 0;
            foreach ($sources as $src) {
                $outcome = seed_if_missing($pdo, 'em_event_sources', 'name', $src);
                if ($outcome === 'inserted') {
                    $srcInserted++;
                } elseif ($outcome === 'skipped') {
                    $srcSkipped++;
                } else {
                    $errors[] = 'em_event_sources [' . $src['name'] . ']: ' . $outcome;
                }
            }
            $results[] = "em_event_sources → inserted {$srcInserted}, skipped {$srcSkipped} (already existed)";

            // ══════════════════════════════════════════════════════════════════
            // 3. EVENT DESTINATIONS (4 destinations)
            // ══════════════════════════════════════════════════════════════════
            $destinations = [
                ['name' => 'Internal Event Log',   'type' => 'internal', 'config' => json_encode(['table' => 'em_event_logs',           'description' => 'All events written to the EM event log']),         'status' => 'active'],
                ['name' => 'Email Notification',   'type' => 'email',    'config' => json_encode(['handler' => 'send_task_email',        'description' => 'Transactional emails via PHPMailer']),             'status' => 'active'],
                ['name' => 'CRM Webhook',          'type' => 'webhook',  'config' => json_encode(['table' => 'crm_webhook_deliveries',   'description' => 'Outbound CRM webhook delivery']),                  'status' => 'active'],
                ['name' => 'Internal Queue',       'type' => 'queue',    'config' => json_encode(['table' => 'em_queue_messages',        'description' => 'Async queue for event retries and CRM sync jobs']), 'status' => 'active'],
            ];

            $dstInserted = 0;
            $dstSkipped  = 0;
            foreach ($destinations as $dst) {
                $outcome = seed_if_missing($pdo, 'em_event_destinations', 'name', $dst);
                if ($outcome === 'inserted') {
                    $dstInserted++;
                } elseif ($outcome === 'skipped') {
                    $dstSkipped++;
                } else {
                    $errors[] = 'em_event_destinations [' . $dst['name'] . ']: ' . $outcome;
                }
            }
            $results[] = "em_event_destinations → inserted {$dstInserted}, skipped {$dstSkipped} (already existed)";
        }

    } catch (\Throwable $e) {
        $errors[] = 'Unexpected error: ' . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// READ CURRENT COUNTS FOR DISPLAY
// ─────────────────────────────────────────────────────────────────────────────
$counts = ['em_event_definitions' => 0, 'em_event_sources' => 0, 'em_event_destinations' => 0];
try {
    global $pdo;
    foreach (array_keys($counts) as $tbl) {
        $r = $pdo->query("SELECT COUNT(*) FROM {$tbl}");
        $counts[$tbl] = $r ? (int)$r->fetchColumn() : 0;
    }
} catch (\Throwable $e) {}

require_once __DIR__ . '/../includes/em_header.php';
?>
<div class="em-content-header">
    <div>
        <h1 class="em-page-title"><i class="fas fa-database me-2 text-success"></i>Phase 3A — Seed Data</h1>
        <p class="em-page-subtitle">Inserts canonical event definitions, sources, and destinations into the Event Manager.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Errors occurred:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($results)): ?>
    <div class="alert alert-success">
        <strong><i class="fas fa-check-circle me-1"></i>Seed completed:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($results as $res): ?>
                <li><?= htmlspecialchars($res) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="em-card text-center p-3">
            <div class="display-4 text-primary fw-bold"><?= $counts['em_event_definitions'] ?></div>
            <div class="text-muted small">Event Definitions</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="em-card text-center p-3">
            <div class="display-4 text-success fw-bold"><?= $counts['em_event_sources'] ?></div>
            <div class="text-muted small">Event Sources</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="em-card text-center p-3">
            <div class="display-4 text-info fw-bold"><?= $counts['em_event_destinations'] ?></div>
            <div class="text-muted small">Event Destinations</div>
        </div>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header">
        <h5 class="mb-0">Run Seed</h5>
    </div>
    <div class="em-card-body">
        <p>This operation is <strong>idempotent</strong> — running it multiple times will not create duplicate records. Existing rows are skipped automatically.</p>
        <p><strong>Will insert:</strong></p>
        <ul>
            <li>13 canonical Event Definitions (ORDER_CREATED, PAYMENT_SUCCESS, CUSTOMER_CREATED, etc.)</li>
            <li>6 Event Sources (Orders, Customers, Products, Razorpay, Emails, CRM)</li>
            <li>4 Event Destinations (Internal Log, Email, CRM Webhook, Internal Queue)</li>
        </ul>
        <form method="POST">
            <input type="hidden" name="action" value="seed">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-play me-1"></i>Run Phase 3A Seed
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/em_footer.php'; ?>
