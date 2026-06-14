<?php
/**
 * Event Manager - CRM Field Mappings API
 *
 * Admin-only CRUD for em_crm_field_mappings. CSRF protected.
 * Maps local payload fields -> CRM fields with an optional transformation spec.
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

function emfm_input(): array
{
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

function emfm_guard_csrf(array $input): void
{
    if (!em_verify_csrf($input['em_csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
        exit;
    }
}

function emfm_decode_transformation($raw): ?string
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
        throw new InvalidArgumentException('Transformation must be valid JSON');
    }
    return null;
}

try {
    switch ($method) {
        case 'GET':
            $connectionId = (int)($_GET['connection_id'] ?? 0);
            if ($connectionId > 0) {
                $rows = em_fetch_all(
                    "SELECT * FROM em_crm_field_mappings WHERE connection_id = ? ORDER BY id ASC",
                    [$connectionId]
                );
            } else {
                $rows = em_fetch_all("SELECT * FROM em_crm_field_mappings ORDER BY connection_id, id ASC");
            }
            echo json_encode(['success' => true, 'mappings' => $rows]);
            break;

        case 'POST':
            $input = emfm_input();
            emfm_guard_csrf($input);

            $connectionId = (int)($input['connection_id'] ?? 0);
            $localField   = trim((string)($input['local_field'] ?? ''));
            $crmField     = trim((string)($input['crm_field'] ?? ''));
            $transform    = emfm_decode_transformation($input['transformation'] ?? null);

            if ($connectionId <= 0 || $localField === '' || $crmField === '') {
                throw new InvalidArgumentException('connection_id, local_field and crm_field are required');
            }
            if (!em_fetch("SELECT id FROM em_crm_connections WHERE id = ?", [$connectionId])) {
                throw new InvalidArgumentException('Connection not found');
            }

            em_query(
                "INSERT INTO em_crm_field_mappings (connection_id, local_field, crm_field, transformation, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [$connectionId, $localField, $crmField, $transform]
            );
            $id = em_last_insert_id();
            em_log_activity('create', 'crm_field_mapping', $id, ['connection_id' => $connectionId]);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'PUT':
            $input = emfm_input();
            emfm_guard_csrf($input);

            $id         = (int)($input['id'] ?? 0);
            $localField = trim((string)($input['local_field'] ?? ''));
            $crmField   = trim((string)($input['crm_field'] ?? ''));
            $transform  = emfm_decode_transformation($input['transformation'] ?? null);

            if ($id <= 0 || $localField === '' || $crmField === '') {
                throw new InvalidArgumentException('id, local_field and crm_field are required');
            }
            if (!em_fetch("SELECT id FROM em_crm_field_mappings WHERE id = ?", [$id])) {
                throw new RuntimeException('Mapping not found');
            }

            em_query(
                "UPDATE em_crm_field_mappings SET local_field = ?, crm_field = ?, transformation = ? WHERE id = ?",
                [$localField, $crmField, $transform, $id]
            );
            em_log_activity('update', 'crm_field_mapping', $id);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $input = emfm_input();
            emfm_guard_csrf($input);

            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException('Invalid mapping ID');
            }
            em_query("DELETE FROM em_crm_field_mappings WHERE id = ?", [$id]);
            em_log_activity('delete', 'crm_field_mapping', $id);
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
    error_log('EM_CRM field_mappings API: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
