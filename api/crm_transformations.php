<?php
/**
 * Event Manager - CRM Transformations API
 *
 * Admin-only CRUD for em_crm_transformations (reusable, named transformations
 * that field mappings can reference via { "transformation_id": N }). CSRF protected.
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

const EM_CRM_TRANSFORM_TYPES = [
    'uppercase', 'lowercase', 'trim', 'capitalize', 'date', 'concat',
    'static', 'default', 'prefix', 'suffix', 'number_format', 'map', 'boolean', 'none',
];

function emt_input(): array
{
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

function emt_guard_csrf(array $input): void
{
    if (!em_verify_csrf($input['em_csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
        exit;
    }
}

function emt_config($raw): ?string
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
    throw new InvalidArgumentException('config must be valid JSON');
}

try {
    switch ($method) {
        case 'GET':
            $rows = em_fetch_all("SELECT * FROM em_crm_transformations ORDER BY id ASC");
            echo json_encode(['success' => true, 'transformations' => $rows]);
            break;

        case 'POST':
            $input = emt_input();
            emt_guard_csrf($input);

            $name = trim((string)($input['name'] ?? ''));
            $type = strtolower(trim((string)($input['transformation_type'] ?? '')));
            $config = emt_config($input['config'] ?? null);

            if ($name === '' || $type === '') {
                throw new InvalidArgumentException('name and transformation_type are required');
            }
            if (!in_array($type, EM_CRM_TRANSFORM_TYPES, true)) {
                throw new InvalidArgumentException('Unsupported transformation_type');
            }

            em_query(
                "INSERT INTO em_crm_transformations (name, transformation_type, config, created_at)
                 VALUES (?, ?, ?, NOW())",
                [$name, $type, $config]
            );
            $id = em_last_insert_id();
            em_log_activity('create', 'crm_transformation', $id, ['name' => $name]);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'PUT':
            $input = emt_input();
            emt_guard_csrf($input);

            $id   = (int)($input['id'] ?? 0);
            $name = trim((string)($input['name'] ?? ''));
            $type = strtolower(trim((string)($input['transformation_type'] ?? '')));
            $config = emt_config($input['config'] ?? null);

            if ($id <= 0 || $name === '' || $type === '') {
                throw new InvalidArgumentException('id, name and transformation_type are required');
            }
            if (!in_array($type, EM_CRM_TRANSFORM_TYPES, true)) {
                throw new InvalidArgumentException('Unsupported transformation_type');
            }
            if (!em_fetch("SELECT id FROM em_crm_transformations WHERE id = ?", [$id])) {
                throw new RuntimeException('Transformation not found');
            }

            em_query(
                "UPDATE em_crm_transformations SET name = ?, transformation_type = ?, config = ?, updated_at = NOW() WHERE id = ?",
                [$name, $type, $config, $id]
            );
            em_log_activity('update', 'crm_transformation', $id);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $input = emt_input();
            emt_guard_csrf($input);

            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException('Invalid transformation ID');
            }
            em_query("DELETE FROM em_crm_transformations WHERE id = ?", [$id]);
            em_log_activity('delete', 'crm_transformation', $id);
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
    error_log('EM_CRM transformations API: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
