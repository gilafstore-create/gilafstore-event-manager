<?php
/**
 * Event Manager - Multi-Domain Database Connection
 * Auto-detects: localhost, gilafstore.com, gilafkashmirifoods.in
 * ZERO manual configuration required
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load multi-domain configuration system
$multiDomainConfigPath = __DIR__ . '/../../includes/multi_domain_config.php';
if (file_exists($multiDomainConfigPath)) {
    require_once $multiDomainConfigPath;
    $dbConfig = getDatabaseConfig();
} else {
    // Fallback: Auto-detect environment (standalone mode)
    $isLocal = (
        php_sapi_name() === 'cli' ||
        (isset($_SERVER['HTTP_HOST']) && (
            $_SERVER['HTTP_HOST'] === 'localhost' ||
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
            strpos($_SERVER['HTTP_HOST'], '::1') !== false ||
            strpos($_SERVER['HTTP_HOST'], '192.168.') !== false
        )) ||
        (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'localhost') !== false)
    );
    
    if ($isLocal) {
        $dbConfig = [
            'host' => 'localhost',
            'name' => 'ecommerce_db',
            'user' => 'root',
            'pass' => '',
        ];
    } else {
        // Check domain for multi-site support
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'gilafkashmirifoods.in') !== false) {
            $dbConfig = [
                'host' => 'localhost',
                'name' => 'u237768108_gilafkashmiri',
                'user' => 'u237768108_gilafkashmiri',
                'pass' => '',
            ];
        } else {
            // Default: gilafstore.com
            $dbConfig = [
                'host' => 'localhost',
                'name' => 'u237768108_gilafstore',
                'user' => 'u237768108_gilafstore',
                'pass' => '1Gfs@#$222',
            ];
        }
    }
}

// Define database constants
define('EM_DB_HOST', $dbConfig['host']);
define('EM_DB_NAME', $dbConfig['name']);
define('EM_DB_USER', $dbConfig['user']);
define('EM_DB_PASS', $dbConfig['pass']);

// Create PDO connection
try {
    $dsn = 'mysql:host=' . EM_DB_HOST . ';dbname=' . EM_DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, EM_DB_USER, EM_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Set MySQL timezone
    $pdo->exec("SET time_zone = '+05:30'");
} catch (PDOException $exception) {
    die('Event Manager Database Error: ' . $exception->getMessage());
}

// MySQLi connection for compatibility
$conn = new mysqli(EM_DB_HOST, EM_DB_USER, EM_DB_PASS, EM_DB_NAME);

if ($conn->connect_error) {
    die('Event Manager MySQLi Error: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+05:30'");

// Helper functions
function get_db_connection(): PDO
{
    global $pdo;
    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_fetch_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_fetch(string $sql, array $params = []): ?array
{
    $stmt = db_query($sql, $params);
    $result = $stmt->fetch();
    return $result ?: null;
}

function db_last_insert_id(): int
{
    global $pdo;
    return (int)$pdo->lastInsertId();
}
