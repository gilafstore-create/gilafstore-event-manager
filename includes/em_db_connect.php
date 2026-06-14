<?php
/**
 * Event Manager - Standalone Database Connection
 * Works on both localhost and production without external dependencies
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-detect environment
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

// Database credentials
if ($isLocal) {
    // Localhost (XAMPP)
    define('EM_DB_HOST', 'localhost');
    define('EM_DB_NAME', 'ecommerce_db');
    define('EM_DB_USER', 'root');
    define('EM_DB_PASS', '');
} else {
    // Production (Hostinger)
    define('EM_DB_HOST', 'localhost');
    define('EM_DB_NAME', 'u237768108_gilafstore');
    define('EM_DB_USER', 'u237768108_gilafstore');
    define('EM_DB_PASS', '1Gfs@#$222');
}

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
