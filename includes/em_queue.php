<?php
/**
 * Event Manager - Queue Processing Engine
 * 
 * SAFETY: Non-blocking queue processing with retry logic
 * Handles failed event delivery with exponential backoff
 */

/**
 * Enqueue a failed event for retry
 */
function em_enqueue_retry(int $eventLogId, string $reason = ''): bool
{
    try {
        $event = em_fetch("SELECT * FROM em_event_logs WHERE id = ?", [$eventLogId]);
        if (!$event) return false;

        $payload = [
            'event_log_id' => $eventLogId,
            'event_type' => $event['event_type'],
            'payload' => $event['payload'],
            'source_id' => $event['source_id'],
            'destination_id' => $event['destination_id'],
            'retry_reason' => $reason,
            'original_created_at' => $event['created_at']
        ];

        em_query(
            "INSERT INTO em_queue_messages (queue_name, payload, status, priority, scheduled_at, created_at)
             VALUES ('event_retry', ?, 'pending', 5, NOW(), NOW())",
            [json_encode($payload)]
        );

        return true;
    } catch (Exception $e) {
        error_log("EM Queue: Failed to enqueue retry for event {$eventLogId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Process pending queue messages
 */
function em_process_queue(int $limit = 10): array
{
    $results = [
        'processed' => 0,
        'succeeded' => 0,
        'failed' => 0,
        'errors' => []
    ];

    try {
        // Get pending messages that are ready to process
        $messages = em_fetch_all(
            "SELECT * FROM em_queue_messages 
             WHERE status = 'pending' 
             AND scheduled_at <= NOW()
             ORDER BY priority DESC, created_at ASC
             LIMIT $limit"
        );

        foreach ($messages as $msg) {
            $results['processed']++;
            
            // Mark as processing
            em_query("UPDATE em_queue_messages SET status = 'processing', processed_at = NOW() WHERE id = ?", [$msg['id']]);

            try {
                $payload = json_decode($msg['payload'], true);
                
                // Route the job to the correct handler by queue name.
                if ($msg['queue_name'] === 'event_retry') {
                    $success = em_retry_event($payload);
                } elseif ($msg['queue_name'] === 'crm_sync') {
                    require_once __DIR__ . '/em_crm_sync.php';
                    $success = em_crm_process_sync_message(is_array($payload) ? $payload : []);
                } else {
                    $success = false;
                }

                if ($success) {
                    // Mark as completed
                    em_query(
                        "UPDATE em_queue_messages SET status = 'completed', completed_at = NOW() WHERE id = ?",
                        [$msg['id']]
                    );
                    $results['succeeded']++;
                } else {
                    // Increment retry count
                    $retryCount = ($msg['retry_count'] ?? 0) + 1;
                    $maxRetries = 5;

                    if ($retryCount >= $maxRetries) {
                        // Move to dead letter
                        em_query(
                            "UPDATE em_queue_messages SET status = 'dead_letter', retry_count = ?, completed_at = NOW() WHERE id = ?",
                            [$retryCount, $msg['id']]
                        );
                    } else {
                        // Schedule next retry with exponential backoff
                        $backoffMinutes = pow(2, $retryCount) * 5; // 5, 10, 20, 40, 80 minutes
                        em_query(
                            "UPDATE em_queue_messages 
                             SET status = 'pending', retry_count = ?, scheduled_at = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                             WHERE id = ?",
                            [$retryCount, $backoffMinutes, $msg['id']]
                        );
                    }
                    $results['failed']++;
                }
            } catch (Exception $e) {
                $results['errors'][] = "Message {$msg['id']}: " . $e->getMessage();
                em_query("UPDATE em_queue_messages SET status = 'failed', error_message = ? WHERE id = ?", [$e->getMessage(), $msg['id']]);
                $results['failed']++;
            }
        }
    } catch (Exception $e) {
        $results['errors'][] = "Queue processing error: " . $e->getMessage();
    }

    return $results;
}

/**
 * Retry a failed event
 */
function em_retry_event(array $payload): bool
{
    try {
        require_once __DIR__ . '/em_dispatcher.php';
        
        $eventType = $payload['event_type'] ?? '';
        $eventPayload = is_string($payload['payload']) ? json_decode($payload['payload'], true) : $payload['payload'];
        
        if (!$eventType || !$eventPayload) {
            return false;
        }

        // Re-dispatch using the dispatcher
        em_dispatch($eventType, $eventPayload);
        
        // Update original event log
        if (!empty($payload['event_log_id'])) {
            em_query(
                "UPDATE em_event_logs SET status = 'success', updated_at = NOW() WHERE id = ?",
                [$payload['event_log_id']]
            );
        }

        return true;
    } catch (Exception $e) {
        error_log("EM Queue: Retry failed for event {$payload['event_type']}: " . $e->getMessage());
        return false;
    }
}

/**
 * Get queue statistics
 */
function em_get_queue_stats(): array
{
    try {
        $stats = [
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'dead_letter' => 0,
            'total' => 0
        ];

        $rows = em_fetch_all("SELECT status, COUNT(*) as count FROM em_queue_messages GROUP BY status");
        
        foreach ($rows as $row) {
            $status = $row['status'];
            $count = (int)$row['count'];
            if (isset($stats[$status])) {
                $stats[$status] = $count;
            }
            $stats['total'] += $count;
        }

        return $stats;
    } catch (Exception $e) {
        return ['pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0, 'dead_letter' => 0, 'total' => 0];
    }
}

/**
 * Purge completed queue messages older than X days
 */
function em_purge_queue(int $daysOld = 7): int
{
    try {
        $stmt = em_query(
            "DELETE FROM em_queue_messages 
             WHERE status = 'completed' 
             AND completed_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$daysOld]
        );
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("EM Queue: Purge failed: " . $e->getMessage());
        return 0;
    }
}

/**
 * Manually retry a specific queue message
 */
function em_manual_retry(int $messageId): bool
{
    try {
        em_query(
            "UPDATE em_queue_messages 
             SET status = 'pending', scheduled_at = NOW(), retry_count = 0
             WHERE id = ?",
            [$messageId]
        );
        return true;
    } catch (Exception $e) {
        error_log("EM Queue: Manual retry failed for message {$messageId}: " . $e->getMessage());
        return false;
    }
}
