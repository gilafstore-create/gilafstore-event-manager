<?php
/**
 * Event Manager - CRM Trigger Rules API
 *
 * Admin-only CRUD for em_crm_trigger_rules. CSRF protected.
 * A trigger rule binds an event type to a CRM action, with an optional condition.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/em_auth.php';
require_once __DIR__ . '/../includes/em_db.php';
require_once __DIR__ . '/../includes/em_crm.php';

if (!em_is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function emtr_input(): array
{
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

function emtr_guard_csrf(array $input): void
{
    if (!em_verify_csrf($input['em_csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
        exit;
    }
}

/** Normalise a JSON-ish field (array or JSON string) to a JSON string or null. */
function emtr_json($raw, string $label): ?string
{
    if ($raw === null || $raw === '' || $raw === []) {
        return null;
    }
    if (is_array($raw)) {
        return json_encode($raw);
    }
    if (is_string($raw)) {
        json_decode($raw);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $raw;
        }
    }
    throw new InvalidArgumentException("$label must be valid JSON");
}

try {
    switch ($method) {
        case 'GET':
            $connectionId = (int)($_GET['connection_id'] ?? 0);
            if ($connectionId > 0) {
                $rows = em_fetch_all(
                    "SELECT * FROM em_crm_trigger_rules WHERE connection_id = ? ORDER BY id ASC",
                    [$connectionId]
                );
            } else {
                $rows = em_fetch_all("SELECT * FROM em_crm_trigger_rules ORDER BY connection_id, id ASC");
            }
            echo json_encode(['success' => true, 'rules' => $rows]);
            break;

        case 'POST':
            $input = emtr_input();
            emtr_guard_csrf($input);

            $connectionId = (int)($input['connection_id'] ?? 0);
            $triggerEvent = trim((string)($input['trigger_event'] ?? ''));
            $status       = (string)($input['status'] ?? 'active');
            $condition    = emtr_json($input['condition'] ?? null, 'Condition');
            $action       = emtr_json($input['action'] ?? null, 'Action');

            if ($connectionId <= 0 || $triggerEvent === '') {
                throw new InvalidArgumentException('connection_id and trigger_event are required');
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }
            if (!em_fetch("SELECT id FROM em_crm_connections WHERE id = ?", [$connectionId])) {
                throw new InvalidArgumentException('Connection not found');
            }

            em_query(
                "INSERT INTO em_crm_trigger_rules (connection_id, trigger_event, `condition`, `action`, status, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$connectionId, $triggerEvent, $condition, $action, $status]
            );
            $id = em_last_insert_id();
            em_log_activity('create', 'crm_trigger_rule', $id, ['event' => $triggerEvent]);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'PUT':
            $input = emtr_input();
            emtr_guard_csrf($input);

            $id           = (int)($input['id'] ?? 0);
            $triggerEvent = trim((string)($input['trigger_event'] ?? ''));
            $status       = (string)($input['status'] ?? 'active');
            $condition    = emtr_json($input['condition'] ?? null, 'Condition');
            $action       = emtr_json($input['action'] ?? null, 'Action');

            if ($id <= 0 || $triggerEvent === '') {
                throw new InvalidArgumentException('id and trigger_event are required');
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }
            if (!em_fetch("SELECT id FROM em_crm_trigger_rules WHERE id = ?", [$id])) {
                throw new RuntimeException('Trigger rule not found');
            }

            em_query(
                "UPDATE em_crm_trigger_rules
                 SET trigger_event = ?, `condition` = ?, `action` = ?, status = ?, updated_at = NOW()
                 WHERE id = ?",
                [$triggerEvent, $condition, $action, $status, $id]
            );
            em_log_activity('update', 'crm_trigger_rule', $id);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $input = emtr_input();
            emtr_guard_csrf($input);

            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException('Invalid rule ID');
            }
            em_query("DELETE FROM em_crm_trigger_rules WHERE id = ?", [$id]);
            em_log_activity('delete', 'crm_trigger_rule', $id);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(400);
    error_log('EM_CRM trigger_rules API: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
