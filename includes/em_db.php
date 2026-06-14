<?php
/**
 * Event Manager - Database Helper Functions
 * 
 * SAFETY: Read-only access to existing tables
 * All Event Manager tables use em_ prefix
 * NO MODIFICATIONS to existing tables
 */

/**
 * Execute Event Manager query
 * Uses existing PDO connection - NO NEW CONNECTION
 */
function em_query(string $sql, array $params = []): PDOStatement
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch all rows from Event Manager query
 */
function em_fetch_all(string $sql, array $params = []): array
{
    return em_query($sql, $params)->fetchAll();
}

/**
 * Fetch single row from Event Manager query
 */
function em_fetch(string $sql, array $params = []): ?array
{
    $stmt = em_query($sql, $params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Get last insert ID for Event Manager
 */
function em_last_insert_id(): int
{
    global $pdo;
    return (int)$pdo->lastInsertId();
}

/**
 * Begin transaction for Event Manager operations
 */
function em_begin_transaction(): void
{
    global $pdo;
    $pdo->beginTransaction();
}

/**
 * Commit Event Manager transaction
 */
function em_commit(): void
{
    global $pdo;
    $pdo->commit();
}

/**
 * Rollback Event Manager transaction
 */
function em_rollback(): void
{
    global $pdo;
    $pdo->rollBack();
}

/**
 * Read-only: Get orders for event tracking
 * NO MODIFICATIONS to orders table
 */
function em_get_orders(int $limit = 100, int $offset = 0): array
{
    return em_fetch_all(
        "SELECT id, order_number, user_id, total_amount, status, payment_status, created_at 
         FROM orders 
         ORDER BY created_at DESC 
         LIMIT ? OFFSET ?",
        [$limit, $offset]
    );
}

/**
 * Read-only: Get customers for event tracking
 * NO MODIFICATIONS to users table
 */
function em_get_customers(int $limit = 100, int $offset = 0): array
{
    return em_fetch_all(
        "SELECT id, name, email, phone, created_at 
         FROM users 
         WHERE role = 'customer' 
         ORDER BY created_at DESC 
         LIMIT ? OFFSET ?",
        [$limit, $offset]
    );
}

/**
 * Read-only: Get products for event tracking
 * NO MODIFICATIONS to products table
 */
function em_get_products(int $limit = 100, int $offset = 0): array
{
    return em_fetch_all(
        "SELECT id, name, sku, price, stock, status, created_at 
         FROM products 
         ORDER BY created_at DESC 
         LIMIT ? OFFSET ?",
        [$limit, $offset]
    );
}

/**
 * Read-only: Get order by ID
 * NO MODIFICATIONS to orders table
 */
function em_get_order(int $orderId): ?array
{
    return em_fetch(
        "SELECT * FROM orders WHERE id = ?",
        [$orderId]
    );
}

/**
 * Read-only: Get customer by ID
 * NO MODIFICATIONS to users table
 */
function em_get_customer(int $customerId): ?array
{
    return em_fetch(
        "SELECT * FROM users WHERE id = ? AND role = 'customer'",
        [$customerId]
    );
}

/**
 * Read-only: Get product by ID
 * NO MODIFICATIONS to products table
 */
function em_get_product(int $productId): ?array
{
    return em_fetch(
        "SELECT * FROM products WHERE id = ?",
        [$productId]
    );
}

/**
 * Check if Event Manager table exists
 */
function em_table_exists(string $tableName): bool
{
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get Event Manager table row count
 */
function em_table_count(string $tableName): int
{
    try {
        $result = em_fetch("SELECT COUNT(*) as count FROM {$tableName}");
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Truncate Event Manager table (for testing/cleanup)
 * ONLY works on em_ prefixed tables for safety
 */
function em_truncate_table(string $tableName): bool
{
    if (!str_starts_with($tableName, 'em_')) {
        throw new Exception('Can only truncate Event Manager tables (em_ prefix)');
    }
    
    try {
        global $pdo;
        $pdo->exec("TRUNCATE TABLE {$tableName}");
        return true;
    } catch (PDOException $e) {
        error_log("Failed to truncate {$tableName}: " . $e->getMessage());
        return false;
    }
}
