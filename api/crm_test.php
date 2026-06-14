<?php
/**
 * Event Manager - CRM Connection Test API
 * 
 * SAFETY: Admin-only, tests CRM connectivity
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

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// CSRF protection
if (!em_verify_csrf($input['em_csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
    exit;
}

try {
    $connectionId = (int)($input['connection_id'] ?? 0);

    if ($connectionId <= 0) {
        throw new InvalidArgumentException('Invalid connection ID');
    }

    // Get connection
    $conn = em_fetch("SELECT * FROM em_crm_connections WHERE id = ?", [$connectionId]);
    if (!$conn) {
        throw new RuntimeException('Connection not found');
    }

    $config = json_decode($conn['config'] ?? '{}', true) ?: [];
    $apiEndpoint = $config['api_endpoint'] ?? '';
    $apiKey = em_crm_decrypt($config['api_key'] ?? '');

    if (empty($apiEndpoint)) {
        throw new InvalidArgumentException('API endpoint not configured');
    }

    // Re-validate the endpoint for SSRF safety at test time.
    [$ok, $err] = em_crm_check_endpoint($apiEndpoint);
    if (!$ok) {
        throw new InvalidArgumentException($err);
    }

    // Test connection with a simple HEAD request (no redirects, short timeout).
    $ch = curl_init($apiEndpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY         => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'User-Agent: Gilaf-Event-Manager/1.0',
        ],
    ]);

    $result   = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    // Record the test attempt time regardless of outcome.
    em_query("UPDATE em_crm_connections SET last_tested_at = NOW() WHERE id = ?", [$connectionId]);

    if ($error) {
        em_query("UPDATE em_crm_connections SET status = 'error' WHERE id = ? AND status != 'inactive'", [$connectionId]);
        throw new RuntimeException('Connection failed: ' . $error);
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        // Reactivate the connection if it was previously in error state.
        em_query("UPDATE em_crm_connections SET status = 'active' WHERE id = ? AND status = 'error'", [$connectionId]);

        echo json_encode([
            'success' => true,
            'message' => "Connection successful (HTTP $httpCode)",
        ]);
    } else if ($httpCode === 401 || $httpCode === 403) {
        em_query("UPDATE em_crm_connections SET status = 'error' WHERE id = ? AND status != 'inactive'", [$connectionId]);
        throw new RuntimeException("Authentication failed (HTTP $httpCode). Please check your API credentials.");
    } else {
        em_query("UPDATE em_crm_connections SET status = 'error' WHERE id = ? AND status != 'inactive'", [$connectionId]);
        throw new RuntimeException("Connection test returned HTTP $httpCode");
    }

} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(400);
    error_log('EM_CRM test API: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
