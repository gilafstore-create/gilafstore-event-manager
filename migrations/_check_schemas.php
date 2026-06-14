<?php
require_once __DIR__ . '/../../includes/db_connect.php';

echo "Checking em_event_schemas table:\n";
echo "=================================\n\n";

// Check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'em_event_schemas'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "❌ Table 'em_event_schemas' does NOT exist!\n";
        echo "\nAvailable EM tables:\n";
        $tables = $pdo->query("SHOW TABLES LIKE 'em_%'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    } else {
        echo "✅ Table 'em_event_schemas' exists\n\n";
        
        // Check schema structure
        echo "Table structure:\n";
        $cols = $pdo->query("DESCRIBE em_event_schemas")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        
        // Check data
        echo "\nData count:\n";
        $count = $pdo->query("SELECT COUNT(*) as c FROM em_event_schemas")->fetch()['c'];
        echo "  Total schemas: $count\n";
        
        if ($count > 0) {
            echo "\nAll schemas:\n";
            $schemas = $pdo->query("SELECT id, name, status, created_at FROM em_event_schemas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($schemas as $s) {
                echo "  - ID: {$s['id']}, Name: {$s['name']}, Status: {$s['status']}, Created: {$s['created_at']}\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
