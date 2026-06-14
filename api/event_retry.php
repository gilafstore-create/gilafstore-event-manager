<?php
/**
 * Event Manager - Event Retry API
 * 
 * SAFETY: Admin-only, validates event log ID
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/em_auth.php';
require_once __DIR__ . '/../includes/em_db.php';
require_once __DIR__ . '/../includes/em_queue.php';

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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventLogId = (int)($input['event_log_id'] ?? 0);
    
    if ($eventLogId <= 0) {
        throw new Exception('Invalid event log ID');
    }
    
    $success = em_enqueue_retry($eventLogId, 'Manual retry requested');
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Event queued for retry']);
    } else {
        throw new Exception('Failed to queue event for retry');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
