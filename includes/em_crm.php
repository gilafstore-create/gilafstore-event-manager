<?php
/**
 * Event Manager - CRM Core Helpers
 *
 * Provides: credential encryption (AES-256-GCM, backward compatible with legacy
 * base64), CRM type validation, and SSRF-safe endpoint validation.
 *
 * SAFETY:
 *   - Writes ONLY to em_ prefixed tables / dedicated key file
 *   - No modifications to existing store tables
 *   - All public helpers are defensive and never leak secrets
 */

if (defined('EM_CRM_LOADED')) {
    return;
}
define('EM_CRM_LOADED', true);

require_once __DIR__ . '/em_db.php';

/**
 * Canonical list of supported CRM types.
 * Mirrors the ENUM in em_crm_connections.
 */
function em_crm_types(): array
{
    return ['salesforce', 'hubspot', 'zoho', 'dynamics', 'custom'];
}

/**
 * Validate a CRM type against the supported whitelist.
 */
function em_crm_valid_type(string $type): bool
{
    return in_array($type, em_crm_types(), true);
}

/**
 * Validate a connection status value.
 */
function em_crm_valid_status(string $status): bool
{
    return in_array($status, ['active', 'inactive', 'error'], true);
}

/* ───────────────────────────────────────────────────────────────────────────
 * ENCRYPTION
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Resolve the 32-byte encryption key.
 *
 * Resolution order:
 *   1. EM_CRM_ENC_KEY constant (if defined)
 *   2. EM_CRM_ENC_KEY environment variable / .env
 *   3. Key file at event-manager/config/crm_encryption.key (auto-generated once)
 *
 * Any source material is normalised to exactly 32 bytes via SHA-256 so the
 * cipher always receives a valid key length regardless of source format.
 */
function em_crm_get_key(): string
{
    static $key = null;
    if ($key !== null) {
        return $key;
    }

    $material = '';

    if (defined('EM_CRM_ENC_KEY') && EM_CRM_ENC_KEY !== '') {
        $material = (string) EM_CRM_ENC_KEY;
    } elseif (($env = getenv('EM_CRM_ENC_KEY')) !== false && $env !== '') {
        $material = $env;
    } else {
        $material = em_crm_key_from_file();
    }

    // Deterministically derive a 32-byte key from whatever material we have.
    $key = hash('sha256', 'em_crm::' . $material, true);
    return $key;
}

/**
 * Read (or lazily create) the key file. Protected from web access via .htaccess.
 * Returns the raw key material (base64 string).
 */
function em_crm_key_from_file(): string
{
    $dir  = __DIR__ . '/../config';
    $file = $dir . '/crm_encryption.key';

    if (is_file($file)) {
        $contents = trim((string) @file_get_contents($file));
        if ($contents !== '') {
            return $contents;
        }
    }

    // Generate a new key once.
    try {
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        em_crm_protect_config_dir($dir);

        $newMaterial = base64_encode(random_bytes(32));
        $written = @file_put_contents($file, $newMaterial, LOCK_EX);
        if ($written !== false && stripos(PHP_OS, 'WIN') !== 0) {
            @chmod($file, 0600);
        }
        return $newMaterial;
    } catch (Throwable $e) {
        // Last-resort deterministic fallback (still better than plaintext, but
        // logged so operators can provision a real key).
        error_log('EM_CRM: could not provision key file: ' . $e->getMessage());
        return hash('sha256', __DIR__ . php_uname());
    }
}

/**
 * Drop protective files into the config directory so secrets are never served.
 */
function em_crm_protect_config_dir(string $dir): void
{
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents(
            $htaccess,
            "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
        );
    }
    $index = $dir . '/index.php';
    if (!is_file($index)) {
        @file_put_contents($index, "<?php http_response_code(403); exit('Forbidden');\n");
    }
}

/**
 * Encrypt a secret using AES-256-GCM.
 * Returns a self-describing token: enc:v1:<iv>:<tag>:<ciphertext> (all base64).
 * Empty input returns empty string.
 */
function em_crm_encrypt(string $plaintext): string
{
    if ($plaintext === '') {
        return '';
    }

    if (!function_exists('openssl_encrypt')) {
        // Should never happen on supported stacks; fail loudly rather than
        // silently storing plaintext.
        throw new RuntimeException('OpenSSL extension unavailable; cannot encrypt CRM credentials.');
    }

    $key = em_crm_get_key();
    $iv  = random_bytes(12);
    $tag = '';

    $cipher = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',
        16
    );

    if ($cipher === false) {
        throw new RuntimeException('CRM credential encryption failed.');
    }

    return 'enc:v1:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($cipher);
}

/**
 * Decrypt a stored secret.
 * Supports both the new enc:v1 format and legacy base64-encoded values.
 * Returns empty string on failure (never throws to callers).
 */
function em_crm_decrypt(?string $stored): string
{
    if ($stored === null || $stored === '') {
        return '';
    }

    if (str_starts_with($stored, 'enc:v1:')) {
        $parts = explode(':', $stored, 5);
        if (count($parts) !== 5) {
            return '';
        }
        $iv     = base64_decode($parts[2], true);
        $tag    = base64_decode($parts[3], true);
        $cipher = base64_decode($parts[4], true);
        if ($iv === false || $tag === false || $cipher === false) {
            return '';
        }
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', em_crm_get_key(), OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? '' : $plain;
    }

    // Legacy fallback: previously secrets were stored as base64 ("demo" encoding).
    $decoded = base64_decode($stored, true);
    return $decoded === false ? $stored : $decoded;
}

/**
 * True if a stored value is already in the new encrypted format.
 */
function em_crm_is_encrypted(?string $stored): bool
{
    return is_string($stored) && str_starts_with($stored, 'enc:v1:');
}

/**
 * Masked placeholder returned to clients instead of real credentials.
 */
function em_crm_secret_mask(): string
{
    return '__EM_KEEP__';
}

/* ───────────────────────────────────────────────────────────────────────────
 * SSRF-SAFE ENDPOINT VALIDATION
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Detect a local/development environment (mirrors db_connect.php logic).
 */
function em_crm_is_local_env(): bool
{
    if (php_sapi_name() === 'cli') {
        return true;
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === 'localhost'
        || strpos($host, '127.0.0.1') !== false
        || strpos($host, '::1') !== false
        || strpos($host, '192.168.') !== false) {
        return true;
    }
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    return strpos($serverName, 'localhost') !== false;
}

/**
 * Whether private/loopback endpoints are permitted.
 * Default: allowed in local/dev, blocked in production. Can be overridden via
 * the EM_CRM_ALLOW_PRIVATE constant or the crm_allow_private_endpoints setting.
 */
function em_crm_private_allowed(): bool
{
    if (defined('EM_CRM_ALLOW_PRIVATE')) {
        return (bool) EM_CRM_ALLOW_PRIVATE;
    }
    try {
        $row = em_fetch("SELECT value FROM em_settings WHERE key_name = 'crm_allow_private_endpoints' LIMIT 1");
        if ($row && isset($row['value'])) {
            return $row['value'] === '1' || strtolower((string) $row['value']) === 'true';
        }
    } catch (Throwable $e) {
        // setting table may not exist yet — fall through to env default
    }
    return em_crm_is_local_env();
}

/**
 * Validate an outbound endpoint URL for SSRF safety.
 * Returns [bool $ok, string $error]. Never throws.
 */
function em_crm_check_endpoint(string $url): array
{
    $url = trim($url);
    if ($url === '') {
        return [false, 'API endpoint is required.'];
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
        return [false, 'API endpoint must be a valid absolute URL.'];
    }

    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return [false, 'API endpoint must use http or https.'];
    }

    $allowPrivate = em_crm_private_allowed();

    // Production must use https.
    if (!$allowPrivate && $scheme !== 'https') {
        return [false, 'API endpoint must use HTTPS.'];
    }

    $host = $parts['host'];

    // Resolve host to an IP for range checks.
    $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : @gethostbyname($host);

    // gethostbyname returns the host unchanged on failure.
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        // Could not resolve. Block in production, allow in dev.
        if (!$allowPrivate) {
            return [false, 'API endpoint host could not be resolved.'];
        }
        return [true, ''];
    }

    if (!$allowPrivate) {
        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
        if ($isPublic === false) {
            return [false, 'API endpoint resolves to a private or reserved address, which is not allowed.'];
        }
    }

    return [true, ''];
}
