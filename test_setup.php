<?php
/**
 * Event Manager - Setup Test Script
 * 
 * Run this script to verify your Event Manager setup
 * Usage: php event-manager/test_setup.php
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         Event Manager - Setup Verification Test           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Change to project root
chdir(__DIR__ . '/../');

// Test 1: Database Connection
echo "Test 1: Database Connection...\n";
try {
    require_once __DIR__ . '/includes/db_connect.php';
    echo "  ✓ Database connection successful\n\n";
} catch (Exception $e) {
    echo "  ✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Event Manager Database Functions
echo "Test 2: Event Manager Database Functions...\n";
try {
    require_once __DIR__ . '/includes/em_db.php';
    echo "  ✓ EM database functions loaded\n\n";
} catch (Exception $e) {
    echo "  ✗ EM database functions failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Event Dispatcher
echo "Test 3: Event Dispatcher...\n";
try {
    require_once __DIR__ . '/includes/em_dispatcher.php';
    echo "  ✓ Event dispatcher loaded\n\n";
} catch (Exception $e) {
    echo "  ✗ Event dispatcher failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Queue Processing Engine
echo "Test 4: Queue Processing Engine...\n";
try {
    require_once __DIR__ . '/includes/em_queue.php';
    echo "  ✓ Queue processing engine loaded\n\n";
} catch (Exception $e) {
    echo "  ✗ Queue processing engine failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 5: Database Tables
echo "Test 5: Checking Database Tables...\n";
$requiredTables = [
    'em_event_definitions',
    'em_event_logs',
    'em_event_sources',
    'em_event_destinations',
    'em_queue_messages',
    'em_crm_connections',
    'em_audit_trail'
];

$missingTables = [];
foreach ($requiredTables as $table) {
    try {
        $result = em_fetch("SHOW TABLES LIKE '$table'");
        if ($result) {
            echo "  ✓ Table '$table' exists\n";
        } else {
            echo "  ✗ Table '$table' missing\n";
            $missingTables[] = $table;
        }
    } catch (Exception $e) {
        echo "  ✗ Error checking table '$table': " . $e->getMessage() . "\n";
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "\n  ⚠ Missing tables: " . implode(', ', $missingTables) . "\n";
    echo "  Run the installation script to create missing tables.\n\n";
} else {
    echo "\n  ✓ All required tables exist\n\n";
}

// Test 6: Event Definitions
echo "Test 6: Checking Event Definitions...\n";
try {
    $eventDefs = em_fetch_all("SELECT name FROM em_event_definitions WHERE status = 'active'");
    $count = count($eventDefs);
    echo "  ✓ Found $count active event definitions\n";
    if ($count > 0) {
        echo "    Event types: ";
        $names = array_column($eventDefs, 'name');
        echo implode(', ', array_slice($names, 0, 5));
        if ($count > 5) echo ", ... (+" . ($count - 5) . " more)";
        echo "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "  ✗ Error checking event definitions: " . $e->getMessage() . "\n\n";
}

// Test 7: Queue Statistics
echo "Test 7: Queue Statistics...\n";
try {
    $stats = em_get_queue_stats();
    echo "  ✓ Queue stats retrieved\n";
    echo "    Pending: {$stats['pending']}\n";
    echo "    Processing: {$stats['processing']}\n";
    echo "    Completed: {$stats['completed']}\n";
    echo "    Failed: {$stats['failed']}\n";
    echo "    Dead Letter: {$stats['dead_letter']}\n";
    echo "    Total: {$stats['total']}\n\n";
} catch (Exception $e) {
    echo "  ✗ Error getting queue stats: " . $e->getMessage() . "\n\n";
}

// Test 8: CRM Connections
echo "Test 8: CRM Connections...\n";
try {
    $connections = em_fetch_all("SELECT id, name, crm_type, status FROM em_crm_connections");
    $count = count($connections);
    echo "  ✓ Found $count CRM connection(s)\n";
    if ($count > 0) {
        foreach ($connections as $conn) {
            echo "    - {$conn['name']} ({$conn['crm_type']}) - {$conn['status']}\n";
        }
    } else {
        echo "    ⚠ No CRM connections configured yet\n";
        echo "    Create your first connection at: CRM Hub → Manage Connections\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "  ✗ Error checking CRM connections: " . $e->getMessage() . "\n\n";
}

// Test 9: Test Event Dispatch
echo "Test 9: Testing Event Dispatch...\n";
try {
    $testPayload = [
        'test_id' => rand(1000, 9999),
        'test_message' => 'Setup verification test',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    em_dispatch('SETUP_TEST', $testPayload);
    echo "  ✓ Test event dispatched successfully\n";
    echo "    Check Event Manager → Event Logs to see the event\n\n";
} catch (Exception $e) {
    echo "  ✗ Event dispatch failed: " . $e->getMessage() . "\n\n";
}

// Test 10: Worker Script
echo "Test 10: Queue Worker Script...\n";
$workerPath = __DIR__ . '/workers/queue_worker.php';
if (file_exists($workerPath)) {
    echo "  ✓ Queue worker script exists\n";
    if (is_readable($workerPath)) {
        echo "  ✓ Queue worker script is readable\n";
        echo "    Location: $workerPath\n";
        echo "    Set up cron job to run this every 5 minutes\n";
    } else {
        echo "  ✗ Queue worker script is not readable\n";
        echo "    Run: chmod +x $workerPath\n";
    }
} else {
    echo "  ✗ Queue worker script not found\n";
    echo "    Expected location: $workerPath\n";
}
echo "\n";

// Summary
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    Test Summary                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if (empty($missingTables)) {
    echo "✓ All tests passed! Your Event Manager is ready to use.\n\n";
    echo "Next Steps:\n";
    echo "1. Set up cron job for queue worker (see QUICK_START_GUIDE.md)\n";
    echo "2. Create your first CRM connection\n";
    echo "3. Monitor Queue Status page\n";
    echo "4. Check Event Logs for the test event\n\n";
} else {
    echo "⚠ Some issues found. Please review the test results above.\n\n";
    echo "Common fixes:\n";
    echo "1. Run installation script to create missing tables\n";
    echo "2. Check database credentials in db_connect.php\n";
    echo "3. Verify file permissions\n\n";
}

echo "For detailed setup instructions, see:\n";
echo "  - QUICK_START_GUIDE.md\n";
echo "  - PHASE_3C_4_README.md\n";
echo "  - FINAL_COMPLETION_REPORT.md\n\n";

echo "Happy Event Managing! 🚀\n";
