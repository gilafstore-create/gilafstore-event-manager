#!/usr/bin/env php
<?php
/**
 * Event Manager - Queue Worker (CLI)
 * 
 * Background worker for processing event retry queue
 * Run via cron: */5 * * * * php /path/to/queue_worker.php
 * 
 * SAFETY: Non-blocking, logs all errors, safe to run concurrently
 */

// CLI only
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

// Set working directory to project root
chdir(__DIR__ . '/../../');

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/em_db.php';
require_once __DIR__ . '/../includes/em_queue.php';

echo "[" . date('Y-m-d H:i:s') . "] Event Manager Queue Worker Started\n";

try {
    // Process up to 50 messages per run
    $results = em_process_queue(50);
    
    echo "Processed: {$results['processed']}\n";
    echo "Succeeded: {$results['succeeded']}\n";
    echo "Failed: {$results['failed']}\n";
    
    if (!empty($results['errors'])) {
        echo "Errors:\n";
        foreach ($results['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
    // Purge old completed messages (older than 7 days)
    $purged = em_purge_queue(7);
    if ($purged > 0) {
        echo "Purged $purged old completed messages\n";
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Queue Worker Completed\n";
    exit(0);
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    error_log("EM Queue Worker: " . $e->getMessage());
    exit(1);
}
