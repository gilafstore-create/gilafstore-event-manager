<?php
/**
 * Event Manager - CRM Manual Sync API
 *
 * Admin-only, CSRF protected. Runs the sync engine synchronously for one
 * connection with a supplied event type + payload. Useful for testing the
 * mapping/trigger/transformation pipeline without waiting for a live event.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/em_auth.php';
require_once __DIR__ . '/../includes/em_db.php';
require_once __DIR__ . '/../includes/em_crm.php';
require_once __DIR__ . '/../includes/em_crm_sync.php';

if (!em_is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (!em_verify_csrf($input['em_csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
    exit;
}

try {
    $connectionId = (int)($input['connection_id'] ?? 0);
    $eventType    = trim((string)($input['event_type'] ?? ''));
    $payload      = $input['payload'] ?? [];

    if ($connectionId <= 0 || $eventType === '') {
        throw new InvalidArgumentException('connection_id and event_type are required');
    }
    if (!is_array($payload)) {
        throw new InvalidArgumentException('payload must be a JSON object');
    }

    $result = em_crm_sync_event($connectionId, $eventType, $payload);

    em_log_activity('manual_sync', 'crm_connection', $connectionId, [
        'event_type' => $eventType,
        'status'     => $result['status'],
    ]);

    echo json_encode([
        'success'     => $result['success'],
        'status'      => $result['status'],
        'message'     => $result['message'],
        'sync_log_id' => $result['sync_log_id'],
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(400);
    error_log('EM_CRM sync_run API: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
