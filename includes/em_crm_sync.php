<?php
/**
 * Event Manager - CRM Synchronization Engine
 *
 * Turns CRM connections + trigger rules + field mappings + transformations into
 * real outbound synchronisation, with full sync-log + failure tracking.
 *
 * Pipeline:
 *   em_dispatch(event)                     (fire-and-forget, in dispatcher)
 *     -> em_crm_dispatch_event()           enqueues crm_sync jobs (cheap)
 *        -> em_queue worker                processes crm_sync jobs
 *           -> em_crm_process_sync_message()
 *              -> em_crm_sync_event()       evaluate rule, map, transform, POST, log
 *
 * SAFETY:
 *   - Writes ONLY to em_ prefixed tables
 *   - All outbound HTTP is SSRF-guarded and time-bounded
 *   - Never throws to the dispatcher (checkout path stays safe)
 */

if (defined('EM_CRM_SYNC_LOADED')) {
    return;
}
define('EM_CRM_SYNC_LOADED', true);

require_once __DIR__ . '/em_db.php';
require_once __DIR__ . '/em_crm.php';

/* ───────────────────────────────────────────────────────────────────────────
 * DISPATCH / ENQUEUE  (called from em_dispatcher)
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Find active connections with an active trigger rule for the given event and
 * enqueue a lightweight crm_sync job for each. Designed to be extremely cheap
 * and to NEVER throw (it runs inside the silent dispatcher).
 *
 * @return int Number of sync jobs enqueued.
 */
function em_crm_dispatch_event(string $eventType, array $payload): int
{
    $enqueued = 0;
    try {
        if (!em_crm_tables_ready()) {
            return 0;
        }

        $rules = em_fetch_all(
            "SELECT r.id AS rule_id, r.connection_id
             FROM em_crm_trigger_rules r
             INNER JOIN em_crm_connections c ON c.id = r.connection_id
             WHERE r.status = 'active'
               AND c.status = 'active'
               AND r.trigger_event = ?",
            [$eventType]
        );

        foreach ($rules as $rule) {
            if (em_crm_enqueue((int)$rule['connection_id'], (int)$rule['rule_id'], $eventType, $payload)) {
                $enqueued++;
            }
        }
    } catch (Throwable $e) {
        error_log('EM_CRM dispatch_event: ' . $e->getMessage());
    }
    return $enqueued;
}

/**
 * Insert a crm_sync job into the shared queue.
 */
function em_crm_enqueue(int $connectionId, int $ruleId, string $eventType, array $payload): bool
{
    try {
        $job = [
            'connection_id' => $connectionId,
            'rule_id'       => $ruleId,
            'event_type'    => $eventType,
            'payload'       => $payload,
        ];
        em_query(
            "INSERT INTO em_queue_messages (queue_name, payload, status, priority, scheduled_at, created_at)
             VALUES ('crm_sync', ?, 'pending', 5, NOW(), NOW())",
            [json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
        return true;
    } catch (Throwable $e) {
        error_log('EM_CRM enqueue: ' . $e->getMessage());
        return false;
    }
}

/**
 * Lightweight check that the CRM tables exist (cached per-request).
 */
function em_crm_tables_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $ready = em_table_exists('em_crm_trigger_rules')
            && em_table_exists('em_crm_connections')
            && em_table_exists('em_queue_messages');
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/* ───────────────────────────────────────────────────────────────────────────
 * QUEUE PROCESSING  (called from em_queue worker)
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Process a single crm_sync queue job.
 * @return bool True on success (job completes), false to trigger retry.
 */
function em_crm_process_sync_message(array $job): bool
{
    $connectionId = (int)($job['connection_id'] ?? 0);
    $ruleId       = (int)($job['rule_id'] ?? 0);
    $eventType    = (string)($job['event_type'] ?? '');
    $payload      = $job['payload'] ?? [];
    if (is_string($payload)) {
        $payload = json_decode($payload, true) ?: [];
    }

    if ($connectionId <= 0 || $eventType === '') {
        return false;
    }

    $result = em_crm_sync_event($connectionId, $eventType, $payload, $ruleId);
    return $result['success'] === true;
}

/* ───────────────────────────────────────────────────────────────────────────
 * CORE SYNC
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Execute synchronisation of one event to one CRM connection.
 *
 * @param int    $connectionId
 * @param string $eventType
 * @param array  $payload     Event payload (local data).
 * @param int    $ruleId      Optional specific trigger rule id (0 = match all).
 * @return array { success: bool, status: string, message: string, sync_log_id: int }
 */
function em_crm_sync_event(int $connectionId, string $eventType, array $payload, int $ruleId = 0): array
{
    $logId = 0;
    try {
        $conn = em_fetch("SELECT * FROM em_crm_connections WHERE id = ?", [$connectionId]);
        if (!$conn) {
            return em_crm_result(false, 'failed', 'Connection not found', 0);
        }
        if ($conn['status'] !== 'active') {
            return em_crm_result(false, 'failed', 'Connection is not active', 0);
        }

        $config   = json_decode($conn['config'] ?? '{}', true) ?: [];
        $endpoint = trim((string)($config['api_endpoint'] ?? ''));
        $apiKey   = em_crm_decrypt($config['api_key'] ?? '');

        if ($endpoint === '') {
            return em_crm_result(false, 'failed', 'Connection has no API endpoint', 0);
        }

        // Load the relevant trigger rule(s).
        if ($ruleId > 0) {
            $rules = em_fetch_all(
                "SELECT * FROM em_crm_trigger_rules WHERE id = ? AND connection_id = ? AND status = 'active'",
                [$ruleId, $connectionId]
            );
        } else {
            $rules = em_fetch_all(
                "SELECT * FROM em_crm_trigger_rules
                 WHERE connection_id = ? AND trigger_event = ? AND status = 'active'",
                [$connectionId, $eventType]
            );
        }

        if (empty($rules)) {
            return em_crm_result(true, 'success', 'No active trigger rules matched (nothing to sync)', 0);
        }

        // Load field mappings for this connection once.
        $mappings = em_fetch_all(
            "SELECT * FROM em_crm_field_mappings WHERE connection_id = ?",
            [$connectionId]
        );

        // Start a sync log.
        $logId = em_crm_sync_log_start($connectionId, $eventType);

        // Signal sync start (best-effort).
        em_crm_emit('CRM_SYNC_STARTED', [
            'connection_id' => $connectionId,
            'event_type'    => $eventType,
            'sync_log_id'   => $logId,
        ]);

        $synced   = 0;
        $failures = 0;
        $lastError = '';

        foreach ($rules as $rule) {
            // Evaluate condition.
            $condition = em_crm_json($rule['condition'] ?? null);
            if (!em_crm_evaluate_condition($payload, $condition)) {
                continue; // rule did not match this payload
            }

            // Build the CRM body via field mappings + transformations.
            $body = em_crm_apply_mappings($payload, $mappings);

            // Resolve the action (method + path + static fields).
            $action = em_crm_json($rule['action'] ?? null);
            $method = strtoupper((string)($action['method'] ?? 'POST'));
            if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                $method = 'POST';
            }
            $url = em_crm_build_url($endpoint, (string)($action['path'] ?? ''));

            // Merge any static fields defined on the action.
            if (!empty($action['static']) && is_array($action['static'])) {
                $body = array_merge($body, $action['static']);
            }
            // Always include provenance metadata.
            $body['_event'] = [
                'type'       => $eventType,
                'dispatched' => date('c'),
                'source'     => 'gilaf-event-manager',
            ];

            // SSRF re-check on the final URL.
            [$ok, $err] = em_crm_check_endpoint($url);
            if (!$ok) {
                $failures++;
                $lastError = $err;
                $recordId = em_crm_record_id($payload);
                em_crm_sync_record_failure($logId, $recordId, "Blocked URL: $err");
                continue;
            }

            // Perform the request.
            [$httpCode, $respBody, $curlErr] = em_crm_http_request($method, $url, $apiKey, $body);

            if ($curlErr !== '') {
                $failures++;
                $lastError = $curlErr;
                em_crm_sync_record_failure($logId, em_crm_record_id($payload), "HTTP error: $curlErr");
            } elseif ($httpCode >= 200 && $httpCode < 300) {
                $synced++;
            } else {
                $failures++;
                $lastError = "HTTP $httpCode";
                $snippet = is_string($respBody) ? substr($respBody, 0, 500) : '';
                em_crm_sync_record_failure($logId, em_crm_record_id($payload), "HTTP $httpCode: $snippet");
            }
        }

        // Determine final status.
        if ($synced > 0 && $failures === 0) {
            $status = 'success';
        } elseif ($synced > 0 && $failures > 0) {
            $status = 'partial';
        } elseif ($synced === 0 && $failures === 0) {
            // No rule matched the payload condition — treat as success/no-op.
            $status = 'success';
        } else {
            $status = 'failed';
        }

        em_crm_sync_log_finish($logId, $status, $synced, $failures > 0 ? $lastError : null);

        if ($synced > 0) {
            em_query("UPDATE em_crm_connections SET last_sync_at = NOW() WHERE id = ?", [$connectionId]);
        }
        // Flip connection into error state on a total failure so operators notice.
        if ($status === 'failed') {
            em_query("UPDATE em_crm_connections SET status = 'error' WHERE id = ? AND status = 'active'", [$connectionId]);
        }

        em_crm_emit('CRM_SYNC_COMPLETED', [
            'connection_id' => $connectionId,
            'event_type'    => $eventType,
            'sync_log_id'   => $logId,
            'status'        => $status,
            'records'       => $synced,
            'failures'      => $failures,
        ]);

        // Success for the queue = no outstanding failures (so it can retry on partial/failed).
        $jobSuccess = ($status === 'success');
        return em_crm_result($jobSuccess, $status, "Synced=$synced Failures=$failures", $logId);

    } catch (Throwable $e) {
        error_log('EM_CRM sync_event: ' . $e->getMessage());
        if ($logId > 0) {
            em_crm_sync_log_finish($logId, 'failed', 0, $e->getMessage());
        }
        return em_crm_result(false, 'failed', $e->getMessage(), $logId);
    }
}

function em_crm_result(bool $success, string $status, string $message, int $logId): array
{
    return ['success' => $success, 'status' => $status, 'message' => $message, 'sync_log_id' => $logId];
}

/* ───────────────────────────────────────────────────────────────────────────
 * FIELD MAPPINGS + TRANSFORMATIONS
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Apply field mappings to a payload, producing the CRM-bound body.
 * Each mapping: local_field -> crm_field with an optional transformation spec.
 * If no mappings are configured, the raw payload is passed through.
 */
function em_crm_apply_mappings(array $payload, array $mappings): array
{
    if (empty($mappings)) {
        return $payload; // pass-through when no explicit mapping defined
    }

    $out = [];
    foreach ($mappings as $map) {
        $localField = (string)($map['local_field'] ?? '');
        $crmField   = (string)($map['crm_field'] ?? '');
        if ($crmField === '') {
            continue;
        }

        $value = em_crm_get_path($payload, $localField);

        $transform = em_crm_json($map['transformation'] ?? null);
        if (!empty($transform)) {
            $value = em_crm_apply_transformation($value, $transform, $payload);
        }

        em_crm_set_path($out, $crmField, $value);
    }
    return $out;
}

/**
 * Apply a single transformation spec to a value.
 *
 * Spec formats:
 *   { "type": "uppercase" }
 *   { "type": "date", "format": "Y-m-d" }
 *   { "type": "concat", "fields": ["first_name","last_name"], "separator": " " }
 *   { "type": "static", "value": "lead" }
 *   { "type": "default", "value": "N/A" }
 *   { "type": "map", "values": { "1": "Active", "0": "Inactive" } }
 *   { "type": "number_format", "decimals": 2 }
 *   { "transformation_id": 5 }   // reference a saved em_crm_transformations row
 */
function em_crm_apply_transformation($value, array $spec, array $payload = [])
{
    // Resolve a referenced/global transformation.
    if (!empty($spec['transformation_id'])) {
        $row = em_fetch("SELECT * FROM em_crm_transformations WHERE id = ?", [(int)$spec['transformation_id']]);
        if ($row) {
            $cfg = em_crm_json($row['config'] ?? null);
            $cfg['type'] = $cfg['type'] ?? $row['transformation_type'];
            $spec = array_merge($cfg, array_diff_key($spec, ['transformation_id' => true]));
        }
    }

    $type = strtolower((string)($spec['type'] ?? 'none'));

    switch ($type) {
        case 'uppercase':
            return is_scalar($value) ? strtoupper((string)$value) : $value;
        case 'lowercase':
            return is_scalar($value) ? strtolower((string)$value) : $value;
        case 'trim':
            return is_scalar($value) ? trim((string)$value) : $value;
        case 'capitalize':
            return is_scalar($value) ? ucwords(strtolower((string)$value)) : $value;
        case 'date':
            $fmt = (string)($spec['format'] ?? 'Y-m-d H:i:s');
            $ts  = is_numeric($value) ? (int)$value : strtotime((string)$value);
            return $ts ? date($fmt, $ts) : $value;
        case 'concat':
            $fields = $spec['fields'] ?? [];
            $sep    = (string)($spec['separator'] ?? ' ');
            $parts  = [];
            foreach ($fields as $f) {
                $parts[] = (string)em_crm_get_path($payload, (string)$f);
            }
            return trim(implode($sep, array_filter($parts, fn($p) => $p !== '')));
        case 'static':
            return $spec['value'] ?? '';
        case 'default':
            return ($value === null || $value === '') ? ($spec['value'] ?? '') : $value;
        case 'prefix':
            return ($value === null || $value === '') ? $value : ((string)($spec['value'] ?? '') . $value);
        case 'suffix':
            return ($value === null || $value === '') ? $value : ($value . (string)($spec['value'] ?? ''));
        case 'number_format':
            $decimals = (int)($spec['decimals'] ?? 2);
            return is_numeric($value) ? number_format((float)$value, $decimals, '.', '') : $value;
        case 'map':
            $values = $spec['values'] ?? [];
            $key = (string)$value;
            return array_key_exists($key, $values) ? $values[$key] : ($spec['fallback'] ?? $value);
        case 'boolean':
            return (bool)$value;
        case 'none':
        default:
            return $value;
    }
}

/* ───────────────────────────────────────────────────────────────────────────
 * CONDITION EVALUATION
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Evaluate a trigger-rule condition against a payload.
 *
 * Supported shapes:
 *   []                                   -> always true
 *   { "field":"status","operator":"eq","value":"paid" }
 *   { "all": [ {...}, {...} ] }          -> AND
 *   { "any": [ {...}, {...} ] }          -> OR
 */
function em_crm_evaluate_condition(array $payload, $condition): bool
{
    if (empty($condition)) {
        return true;
    }

    if (isset($condition['all']) && is_array($condition['all'])) {
        foreach ($condition['all'] as $c) {
            if (!em_crm_evaluate_condition($payload, $c)) {
                return false;
            }
        }
        return true;
    }

    if (isset($condition['any']) && is_array($condition['any'])) {
        foreach ($condition['any'] as $c) {
            if (em_crm_evaluate_condition($payload, $c)) {
                return true;
            }
        }
        return false;
    }

    if (!isset($condition['field'])) {
        return true; // malformed/empty -> non-blocking
    }

    $actual   = em_crm_get_path($payload, (string)$condition['field']);
    $operator = strtolower((string)($condition['operator'] ?? 'eq'));
    $expected = $condition['value'] ?? null;

    switch ($operator) {
        case 'eq':       return em_crm_loose_eq($actual, $expected);
        case 'neq':      return !em_crm_loose_eq($actual, $expected);
        case 'gt':       return is_numeric($actual) && is_numeric($expected) && $actual > $expected;
        case 'gte':      return is_numeric($actual) && is_numeric($expected) && $actual >= $expected;
        case 'lt':       return is_numeric($actual) && is_numeric($expected) && $actual < $expected;
        case 'lte':      return is_numeric($actual) && is_numeric($expected) && $actual <= $expected;
        case 'contains': return $actual !== null && stripos((string)$actual, (string)$expected) !== false;
        case 'in':       return is_array($expected) && in_array($actual, $expected);
        case 'exists':   return $actual !== null;
        case 'not_empty':return $actual !== null && $actual !== '';
        default:         return false;
    }
}

function em_crm_loose_eq($a, $b): bool
{
    if (is_bool($b)) {
        return (bool)$a === $b;
    }
    return (string)$a === (string)$b;
}

/* ───────────────────────────────────────────────────────────────────────────
 * HTTP
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Perform an SSRF-guarded JSON HTTP request to the CRM.
 * @return array [int $httpCode, string $body, string $error]
 */
function em_crm_http_request(string $method, string $url, string $apiKey, array $body): array
{
    $ch = curl_init($url);
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Gilaf-Event-Manager/1.0',
    ];
    if ($apiKey !== '') {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $respBody = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    return [$httpCode, is_string($respBody) ? $respBody : '', $error];
}

/* ───────────────────────────────────────────────────────────────────────────
 * SYNC LOGS + FAILURES
 * ────────────────────────────────────────────────────────────────────────── */

function em_crm_sync_log_start(int $connectionId, string $syncType): int
{
    try {
        em_query(
            "INSERT INTO em_crm_sync_logs (connection_id, sync_type, records_synced, status, started_at)
             VALUES (?, ?, 0, 'success', NOW())",
            [$connectionId, $syncType]
        );
        return em_last_insert_id();
    } catch (Throwable $e) {
        error_log('EM_CRM sync_log_start: ' . $e->getMessage());
        return 0;
    }
}

function em_crm_sync_log_finish(int $logId, string $status, int $recordsSynced, ?string $error): void
{
    if ($logId <= 0) {
        return;
    }
    try {
        em_query(
            "UPDATE em_crm_sync_logs
             SET status = ?, records_synced = ?, error_message = ?, completed_at = NOW()
             WHERE id = ?",
            [$status, $recordsSynced, $error, $logId]
        );
    } catch (Throwable $e) {
        error_log('EM_CRM sync_log_finish: ' . $e->getMessage());
    }
}

function em_crm_sync_record_failure(int $logId, ?string $recordId, string $error): void
{
    if ($logId <= 0) {
        return;
    }
    try {
        em_query(
            "INSERT INTO em_crm_sync_failures (sync_log_id, record_id, error_message, retry_count, created_at)
             VALUES (?, ?, ?, 0, NOW())",
            [$logId, $recordId, substr($error, 0, 2000)]
        );
    } catch (Throwable $e) {
        error_log('EM_CRM sync_record_failure: ' . $e->getMessage());
    }
}

/* ───────────────────────────────────────────────────────────────────────────
 * HELPERS
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Decode a JSON column that may already be an array or null.
 */
function em_crm_json($value): array
{
    if (is_array($value)) {
        return $value;
    }
    if (is_string($value) && $value !== '') {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    return [];
}

/**
 * Get a value from a payload using dot notation (e.g. "customer.email").
 */
function em_crm_get_path(array $data, string $path)
{
    if ($path === '') {
        return null;
    }
    if (array_key_exists($path, $data)) {
        return $data[$path];
    }
    $segments = explode('.', $path);
    $cur = $data;
    foreach ($segments as $seg) {
        if (is_array($cur) && array_key_exists($seg, $cur)) {
            $cur = $cur[$seg];
        } else {
            return null;
        }
    }
    return $cur;
}

/**
 * Set a value into an output array using dot notation.
 */
function em_crm_set_path(array &$data, string $path, $value): void
{
    if ($path === '') {
        return;
    }
    if (strpos($path, '.') === false) {
        $data[$path] = $value;
        return;
    }
    $segments = explode('.', $path);
    $cur = &$data;
    foreach ($segments as $i => $seg) {
        if ($i === count($segments) - 1) {
            $cur[$seg] = $value;
        } else {
            if (!isset($cur[$seg]) || !is_array($cur[$seg])) {
                $cur[$seg] = [];
            }
            $cur = &$cur[$seg];
        }
    }
    unset($cur);
}

/**
 * Build a full request URL from a base endpoint + optional action path.
 */
function em_crm_build_url(string $endpoint, string $path): string
{
    $endpoint = rtrim($endpoint, '/');
    $path = trim($path);
    if ($path === '') {
        return $endpoint;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path; // absolute override
    }
    return $endpoint . '/' . ltrim($path, '/');
}

/**
 * Best-effort extraction of a record identifier for failure tracking.
 */
function em_crm_record_id(array $payload): ?string
{
    foreach (['id', 'order_id', 'customer_id', 'user_id', 'product_id', 'email'] as $k) {
        if (!empty($payload[$k])) {
            return (string)$payload[$k];
        }
    }
    return null;
}

/**
 * Emit an Event Manager event if the dispatcher is available (best-effort).
 */
function em_crm_emit(string $eventType, array $payload): void
{
    try {
        if (!function_exists('em_dispatch')) {
            $dispatcher = __DIR__ . '/em_dispatcher.php';
            if (is_file($dispatcher)) {
                require_once $dispatcher;
            }
        }
        if (function_exists('em_dispatch')) {
            em_dispatch($eventType, $payload);
        }
    } catch (Throwable $e) {
        // never block on telemetry
    }
}
