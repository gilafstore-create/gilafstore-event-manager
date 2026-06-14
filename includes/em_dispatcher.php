<?php
/**
 * Event Manager — Central Event Dispatcher (Phase 3A)
 *
 * SAFETY CONTRACT:
 *   ✅ NEVER throws exceptions to the caller
 *   ✅ NEVER interrupts checkout, payment verification, or admin operations
 *   ✅ Writes ONLY to em_ prefixed tables
 *   ✅ All DB operations wrapped in try/catch
 *   ✅ Failures logged silently via error_log()
 *   ✅ No queue processing in Phase 3A
 *   ✅ No CRM writes in Phase 3A
 *   ✅ No schema changes — reads from existing tables only
 */

if (!defined('EM_DISPATCHER_LOADED')) {
    define('EM_DISPATCHER_LOADED', true);

    /**
     * em_dispatch() — fire-and-forget event logger.
     *
     * Usage:
     *   em_dispatch('ORDER_CREATED', ['order_id' => 42, 'user_id' => 7, ...]);
     *
     * @param string $eventType  One of the 13 canonical event type names.
     * @param array  $payload    Associative array of event data (will be JSON-encoded).
     */
    function em_dispatch(string $eventType, array $payload = []): void
    {
        try {
            // ── 1. Obtain the shared PDO connection ──────────────────────────────
            global $pdo;
            if (!($pdo instanceof PDO)) {
                // Fall back: try to get connection via helper if available
                if (function_exists('get_db_connection')) {
                    $pdo = get_db_connection();
                }
                if (!($pdo instanceof PDO)) {
                    error_log('EM_DISPATCH: no PDO connection available — skipping ' . $eventType);
                    return;
                }
            }

            // ── 2. Guard: abort silently if em_event_logs table is absent ────────
            //    (tables not yet installed — installer hasn't run yet)
            try {
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'em_event_logs'");
                if (!$tableCheck || $tableCheck->rowCount() === 0) {
                    return;
                }
            } catch (\Throwable $tcErr) {
                return;
            }

            // ── 3. Resolve source_id from em_event_sources ───────────────────────
            $sourceId = null;
            try {
                // Map each event type to its logical source name (seeded in Phase 3A)
                static $sourceMap = [
                    'CUSTOMER_CREATED'   => 'Gilaf Store - Customers',
                    'CUSTOMER_UPDATED'   => 'Gilaf Store - Customers',
                    'ORDER_CREATED'      => 'Gilaf Store - Orders',
                    'ORDER_UPDATED'      => 'Gilaf Store - Orders',
                    'ORDER_CANCELLED'    => 'Gilaf Store - Orders',
                    'PAYMENT_SUCCESS'    => 'Razorpay Gateway',
                    'PAYMENT_FAILED'     => 'Razorpay Gateway',
                    'PRODUCT_CREATED'    => 'Gilaf Store - Products',
                    'PRODUCT_UPDATED'    => 'Gilaf Store - Products',
                    'WEBHOOK_SENT'       => 'CRM Webhook System',
                    'WEBHOOK_FAILED'     => 'CRM Webhook System',
                    'CRM_SYNC_STARTED'   => 'CRM Webhook System',
                    'CRM_SYNC_COMPLETED' => 'CRM Webhook System',
                ];
                $sourceName = $sourceMap[$eventType] ?? 'Gilaf Store - Orders';

                // Cache source IDs in a static array to avoid repeated lookups
                static $sourceIdCache = [];
                if (!array_key_exists($sourceName, $sourceIdCache)) {
                    $srcStmt = $pdo->prepare(
                        "SELECT id FROM em_event_sources WHERE name = ? LIMIT 1"
                    );
                    $srcStmt->execute([$sourceName]);
                    $srcRow = $srcStmt->fetch(PDO::FETCH_ASSOC);
                    $sourceIdCache[$sourceName] = $srcRow ? (int)$srcRow['id'] : null;
                }
                $sourceId = $sourceIdCache[$sourceName];
            } catch (\Throwable $srcErr) {
                error_log('EM_DISPATCH: source lookup failed for ' . $eventType . ': ' . $srcErr->getMessage());
                // Continue without source_id
            }

            // ── 4. Generate a unique event ID ────────────────────────────────────
            try {
                $randomSuffix = substr(bin2hex(random_bytes(4)), 0, 8);
            } catch (\Throwable $rndErr) {
                $randomSuffix = str_pad((string)mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            }
            $eventId = 'evt_' . date('Ymd_His') . '_' . $randomSuffix;

            // ── 5. Insert into em_event_logs ─────────────────────────────────────
            $stmt = $pdo->prepare(
                "INSERT INTO em_event_logs
                    (event_id, event_type, source_id, payload, status, created_at)
                 VALUES
                    (?, ?, ?, ?, 'success', NOW())"
            );
            $stmt->execute([
                $eventId,
                $eventType,
                $sourceId,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            // ── 6. CRM auto-sync hook (Phase 4) ──────────────────────────────────
            //    Cheaply enqueues outbound CRM sync jobs for matching active
            //    trigger rules. Never blocks: only inserts queue rows here; the
            //    queue worker performs the actual HTTP delivery.
            //    Loop guard: never re-trigger on the engine's own telemetry events.
            if ($eventType !== 'CRM_SYNC_STARTED' && $eventType !== 'CRM_SYNC_COMPLETED') {
                try {
                    $syncLib = __DIR__ . '/em_crm_sync.php';
                    if (is_file($syncLib)) {
                        require_once $syncLib;
                        if (function_exists('em_crm_dispatch_event')) {
                            em_crm_dispatch_event($eventType, $payload);
                        }
                    }
                } catch (\Throwable $crmErr) {
                    error_log('EM_DISPATCH: CRM hook failed silently for ' . $eventType . ': ' . $crmErr->getMessage());
                }
            }

        } catch (\Throwable $e) {
            // ── SILENT FAILURE — never propagate to caller ───────────────────────
            error_log('EM_DISPATCH: ' . $eventType . ' failed silently: ' . $e->getMessage());
        }
    }

    /**
     * em_dispatcher_available() — lightweight check for callers that want to
     * conditionally use em_dispatch without re-checking file_exists.
     */
    function em_dispatcher_available(): bool
    {
        return defined('EM_DISPATCHER_LOADED');
    }
}
