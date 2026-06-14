<?php
/**
 * Event Manager - Authentication & Authorization
 * 
 * SECURITY: Uses existing admin authentication
 * NO MODIFICATIONS to existing auth system
 * Read-only access to admin session
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include existing database connection (read-only)
// Try multiple paths for localhost vs production compatibility
$dbPaths = [
    __DIR__ . '/../../includes/db_connect.php',           // Localhost: public_html/event-manager/includes -> public_html/includes
    __DIR__ . '/../../../includes/db_connect.php',        // Production: /event-manager/includes -> /includes
    $_SERVER['DOCUMENT_ROOT'] . '/includes/db_connect.php' // Absolute fallback
];

$dbConnected = false;
foreach ($dbPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $dbConnected = true;
        break;
    }
}

if (!$dbConnected) {
    die('Event Manager Error: Could not locate database connection file. Please ensure includes/db_connect.php exists.');
}

/**
 * Check if user is authenticated as admin
 * Uses existing admin session - NO MODIFICATIONS
 */
function em_is_authenticated(): bool
{
    return isset($_SESSION['admin']) && !empty($_SESSION['admin']['is_admin']);
}

/**
 * Require authentication - redirect to admin login if not authenticated
 * Uses existing admin login - NO MODIFICATIONS
 */
function em_require_auth(): void
{
    if (!em_is_authenticated()) {
        // Use existing base_url function to build admin login URL
        // This works on both localhost and production automatically
        if (function_exists('base_url')) {
            header('Location: ' . base_url('gs-secure-portal-92XK'));
        } else {
            // Fallback if base_url not available
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            header('Location: ' . $protocol . '://' . $host . '/gs-secure-portal-92XK');
        }
        exit;
    }
}

/**
 * Get current admin user info
 * Read-only access to session - NO MODIFICATIONS
 */
function em_get_user(): ?array
{
    return $_SESSION['admin'] ?? null;
}

/**
 * Get current admin user ID
 */
function em_get_user_id(): ?int
{
    $user = em_get_user();
    return $user['id'] ?? null;
}

/**
 * Get current admin user name
 */
function em_get_user_name(): string
{
    $user = em_get_user();
    return $user['name'] ?? 'Admin';
}

/**
 * Check if Event Manager tables exist
 * Used to determine if migration needs to run
 */
function em_is_installed(): bool
{
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'em_settings'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Log Event Manager activity
 * Separate audit trail - NO MODIFICATIONS to existing logs
 */
function em_log_activity(string $action, string $entity_type, ?int $entity_id = null, ?array $details = null): void
{
    global $pdo;
    
    if (!em_is_installed()) {
        return; // Skip logging if not installed
    }
    
    try {
        $user_id = em_get_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO em_audit_trail 
            (user_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $entity_type,
            $entity_id,
            $details ? json_encode($details) : null,
            $ip_address,
            $user_agent
        ]);
    } catch (PDOException $e) {
        // Silent fail - don't break functionality if logging fails
        error_log("Event Manager audit log failed: " . $e->getMessage());
    }
}

/**
 * Check if user has permission for Event Manager feature
 * Currently all admins have access - can be extended later
 */
function em_has_permission(string $feature): bool
{
    // For now, all authenticated admins have full access
    // This can be extended with role-based permissions later
    return em_is_authenticated();
}

/**
 * Validate CSRF token for Event Manager forms
 * Separate token system - NO MODIFICATIONS to existing CSRF
 */
function em_validate_csrf(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true; // Only validate POST requests
    }
    
    $token = $_POST['em_csrf_token'] ?? '';
    $session_token = $_SESSION['em_csrf_token'] ?? '';
    
    return hash_equals($session_token, $token);
}

/**
 * Generate CSRF token for Event Manager forms
 */
function em_get_csrf_token(): string
{
    if (!isset($_SESSION['em_csrf_token'])) {
        $_SESSION['em_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['em_csrf_token'];
}

/**
 * Verify a CSRF token for JSON/AJAX API endpoints.
 *
 * Accepts the token from (in order):
 *   1. X-EM-CSRF / X-CSRF-Token request header
 *   2. em_csrf_token in the decoded JSON body (passed in by caller)
 *   3. em_csrf_token POST field
 *
 * @param string|null $bodyToken Optional token already parsed from a JSON body.
 * @return bool True if the token matches the session token.
 */
function em_verify_csrf(?string $bodyToken = null): bool
{
    $session_token = $_SESSION['em_csrf_token'] ?? '';
    if ($session_token === '') {
        return false;
    }

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $headerToken = '';
    foreach ($headers as $name => $value) {
        $lname = strtolower($name);
        if ($lname === 'x-em-csrf' || $lname === 'x-csrf-token') {
            $headerToken = $value;
            break;
        }
    }
    if ($headerToken === '' && isset($_SERVER['HTTP_X_EM_CSRF'])) {
        $headerToken = $_SERVER['HTTP_X_EM_CSRF'];
    }

    $token = $headerToken !== '' ? $headerToken
        : ($bodyToken ?? ($_POST['em_csrf_token'] ?? ''));

    return is_string($token) && $token !== '' && hash_equals($session_token, $token);
}

/**
 * Output CSRF token field for forms
 */
function em_csrf_field(): void
{
    echo '<input type="hidden" name="em_csrf_token" value="' . htmlspecialchars(em_get_csrf_token()) . '">';
}
