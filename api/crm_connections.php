<?php
/**
 * Event Manager - CRM Connections API
 *
 * SAFETY: Admin-only CRUD for CRM connections.
 *   - CSRF protected (all state-changing methods)
 *   - API keys encrypted at rest (AES-256-GCM via em_crm_encrypt)
 *   - crm_type / status / endpoint validated
 *   - Secrets never returned to the client
 *   - Cascade-safe delete inside a transaction
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/em_auth.php';
require_once __DIR__ . '/../includes/em_db.php';
require_once __DIR__ . '/../includes/em_crm.php';

// Admin only
if (!em_is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Build the stored config array from request input.
 * Preserves the existing encrypted api_key when the client sends the mask
 * placeholder or leaves it blank (prevents double-encoding / key loss).
 */
function em_crm_build_config(array $input, ?array $existingConfig): array
{
    $endpoint = trim((string)($input['config']['api_endpoint'] ?? $input['api_endpoint'] ?? ''));
    $autoSync = (bool)($input['config']['auto_sync'] ?? $input['auto_sync'] ?? false);
    $rawKey   = (string)($input['config']['api_key'] ?? $input['api_key'] ?? '');

    // Validate endpoint for SSRF safety.
    [$ok, $err] = em_crm_check_endpoint($endpoint);
    if (!$ok) {
        throw new InvalidArgumentException($err);
    }

    $config = [
        'api_endpoint' => $endpoint,
        'auto_sync'    => $autoSync,
    ];

    $mask = em_crm_secret_mask();
    if ($rawKey === '' || $rawKey === $mask) {
        // Keep the existing encrypted key (update case). On create this means none.
        if ($existingConfig && !empty($existingConfig['api_key'])) {
            $config['api_key'] = $existingConfig['api_key'];
        } else {
            throw new InvalidArgumentException('API Key / Token is required.');
        }
    } else {
        $config['api_key'] = em_crm_encrypt($rawKey);
    }

    return $config;
}

try {
    switch ($method) {
        case 'GET':
            // Return a single connection (sanitised — no secret) for editing.
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException('Invalid connection ID');
            }
            $conn = em_fetch("SELECT * FROM em_crm_connections WHERE id = ?", [$id]);
            if (!$conn) {
                throw new RuntimeException('Connection not found');
            }
            $config = json_decode($conn['config'] ?? '{}', true) ?: [];
            // Never expose the stored secret. Indicate whether one is set.
            $hasKey = !empty($config['api_key']);
            unset($config['api_key']);
            echo json_encode([
                'success' => true,
                'connection' => [
                    'id'        => (int)$conn['id'],
                    'name'      => $conn['name'],
                    'crm_type'  => $conn['crm_type'],
                    'status'    => $conn['status'],
                    'config'    => $config,
                    'has_api_key' => $hasKey,
                ],
            ]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            if (!em_verify_csrf($input['em_csrf_token'] ?? null)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
                exit;
            }

            $name    = trim((string)($input['name'] ?? ''));
            $crmType = (string)($input['crm_type'] ?? '');
            $status  = (string)($input['status'] ?? 'active');

            if ($name === '' || $crmType === '') {
                throw new InvalidArgumentException('Name and CRM type are required');
            }
            if (!em_crm_valid_type($crmType)) {
                throw new InvalidArgumentException('Unsupported CRM type');
            }
            if (!em_crm_valid_status($status)) {
                $status = 'inactive';
            }

            $config = em_crm_build_config($input, null);

            em_query(
                "INSERT INTO em_crm_connections (name, crm_type, config, status, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [$name, $crmType, json_encode($config), $status]
            );

            $newId = em_last_insert_id();
            em_log_activity('create', 'crm_connection', $newId, ['name' => $name, 'crm_type' => $crmType]);
            echo json_encode(['success' => true, 'id' => $newId]);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            if (!em_verify_csrf($input['em_csrf_token'] ?? null)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
                exit;
            }

            $id      = (int)($input['id'] ?? 0);
            $name    = trim((string)($input['name'] ?? ''));
            $crmType = (string)($input['crm_type'] ?? '');
            $status  = (string)($input['status'] ?? 'active');

            if ($id <= 0 || $name === '' || $crmType === '') {
                throw new InvalidArgumentException('Invalid input');
            }
            if (!em_crm_valid_type($crmType)) {
                throw new InvalidArgumentException('Unsupported CRM type');
            }
            if (!em_crm_valid_status($status)) {
                $status = 'inactive';
            }

            $existing = em_fetch("SELECT config FROM em_crm_connections WHERE id = ?", [$id]);
            if (!$existing) {
                throw new RuntimeException('Connection not found');
            }
            $existingConfig = json_decode($existing['config'] ?? '{}', true) ?: [];

            $config = em_crm_build_config($input, $existingConfig);

            em_query(
                "UPDATE em_crm_connections
                 SET name = ?, crm_type = ?, config = ?, status = ?, updated_at = NOW()
                 WHERE id = ?",
                [$name, $crmType, json_encode($config), $status, $id]
            );

            em_log_activity('update', 'crm_connection', $id, ['name' => $name, 'crm_type' => $crmType]);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            if (!em_verify_csrf($input['em_csrf_token'] ?? null)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
                exit;
            }

            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException('Invalid connection ID');
            }

            // Cascade-safe delete: remove dependent rows in a transaction.
            em_begin_transaction();
            try {
                // Sync failures reference sync logs of this connection.
                em_query(
                    "DELETE f FROM em_crm_sync_failures f
                     INNER JOIN em_crm_sync_logs l ON f.sync_log_id = l.id
                     WHERE l.connection_id = ?",
                    [$id]
                );
                em_query("DELETE FROM em_crm_sync_logs WHERE connection_id = ?", [$id]);
                em_query("DELETE FROM em_crm_field_mappings WHERE connection_id = ?", [$id]);
                em_query("DELETE FROM em_crm_trigger_rules WHERE connection_id = ?", [$id]);
                em_query("DELETE FROM em_crm_customer_profiles WHERE crm_connection_id = ?", [$id]);
                em_query("DELETE FROM em_crm_connections WHERE id = ?", [$id]);
                em_commit();
            } catch (Throwable $tx) {
                em_rollback();
                throw $tx;
            }

            em_log_activity('delete', 'crm_connection', $id);
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
    error_log('EM_CRM connections API: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
