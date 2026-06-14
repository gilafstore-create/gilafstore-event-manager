<?php
/**
 * Event Manager - Export Audit Logs API
 * 
 * SAFETY: Admin-only, exports audit logs to CSV
 */

require_once __DIR__ . '/../includes/em_auth.php';
require_once __DIR__ . '/../includes/em_db.php';

// Admin only
if (!em_is_authenticated()) {
    http_response_code(401);
    exit('Unauthorized');
}

try {
    // Get filters from query params
    $filterAction = $_GET['action'] ?? '';
    $filterUser = $_GET['user'] ?? '';
    $filterDate = $_GET['date'] ?? '';
    
    // Build query
    $where = [];
    $params = [];
    
    if ($filterAction) {
        $where[] = "action = ?";
        $params[] = $filterAction;
    }
    
    if ($filterUser) {
        $where[] = "user_id = ?";
        $params[] = (int)$filterUser;
    }
    
    if ($filterDate) {
        $where[] = "DATE(created_at) = ?";
        $params[] = $filterDate;
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get audit logs
    $sql = "SELECT id, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at
            FROM em_audit_trail
            $whereClause
            ORDER BY created_at DESC
            LIMIT 10000";
    
    $logs = em_fetch_all($sql, $params);
    
    // Set headers for CSV download
    $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write CSV header
    fputcsv($output, [
        'ID',
        'User ID',
        'Action',
        'Entity Type',
        'Entity ID',
        'Old Values',
        'New Values',
        'IP Address',
        'User Agent',
        'Created At'
    ]);
    
    // Write data rows
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['user_id'],
            $log['action'],
            $log['entity_type'],
            $log['entity_id'],
            $log['old_values'],
            $log['new_values'],
            $log['ip_address'],
            substr($log['user_agent'], 0, 100), // Truncate long user agents
            $log['created_at']
        ]);
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Export failed: ' . $e->getMessage());
}
