<?php
/**
 * Event Manager - Helper Functions
 * 
 * SAFETY: All functions are isolated to Event Manager
 * NO MODIFICATIONS to existing functions
 */

/**
 * Get Event Manager base URL
 * Works on both localhost and production
 */
function em_base_url(string $path = ''): string
{
    // Use existing base_url function if available
    if (function_exists('base_url')) {
        return base_url('event-manager/' . ltrim($path, '/'));
    }
    
    // Fallback: build URL manually
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove /event-manager from path if present
    $baseUri = str_replace('/event-manager', '', $scriptDir);
    $baseUri = rtrim($baseUri, '/');
    
    return $protocol . '://' . $host . $baseUri . '/event-manager/' . ltrim($path, '/');
}

/**
 * Get Event Manager asset URL
 */
function em_asset_url(string $path): string
{
    return em_base_url('assets/' . ltrim($path, '/'));
}

/**
 * Redirect within Event Manager
 */
function em_redirect(string $path, ?string $message = null, string $type = 'success'): void
{
    if ($message) {
        $_SESSION['em_flash'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    header('Location: ' . em_base_url($path));
    exit;
}

/**
 * Display Event Manager flash message
 */
function em_display_flash(): void
{
    if (!empty($_SESSION['em_flash'])) {
        $flash = $_SESSION['em_flash'];
        $bgColor = match($flash['type']) {
            'danger' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#17a2b8',
            default => '#28a745'
        };
        
        $uid = 'em_flash_' . mt_rand(1000, 9999);
        echo '<div id="' . $uid . '" style="position:fixed;top:80px;right:16px;z-index:9999;min-width:300px;max-width:400px;background:' . $bgColor . ';color:#fff;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);padding:16px 20px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:12px;animation:emFlashIn .3s ease;">';
        echo '<i class="fas fa-' . ($flash['type'] === 'danger' ? 'exclamation-circle' : ($flash['type'] === 'warning' ? 'exclamation-triangle' : ($flash['type'] === 'info' ? 'info-circle' : 'check-circle'))) . '"></i>';
        echo '<span style="flex:1;">' . htmlspecialchars($flash['message']) . '</span>';
        echo '<span onclick="this.parentElement.remove()" style="cursor:pointer;opacity:0.8;font-size:20px;line-height:1;">&times;</span>';
        echo '</div>';
        echo '<style>@keyframes emFlashIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}</style>';
        echo '<script>setTimeout(function(){var e=document.getElementById("' . $uid . '");if(e){e.style.transition="opacity .3s";e.style.opacity="0";setTimeout(function(){e.remove()},300);}},4000);</script>';
        unset($_SESSION['em_flash']);
    }
}

/**
 * Format date for Event Manager
 */
function em_format_date(?string $date, string $format = 'M d, Y g:i A'): string
{
    if (!$date) {
        return 'N/A';
    }
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Format number with commas
 */
function em_format_number($number, int $decimals = 0): string
{
    return number_format((float)$number, $decimals);
}

/**
 * Sanitize input for Event Manager
 */
function em_sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate JSON schema
 */
function em_validate_json(string $json): bool
{
    json_decode($json);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Generate unique ID for Event Manager entities
 */
function em_generate_id(string $prefix = 'em'): string
{
    return $prefix . '_' . bin2hex(random_bytes(16));
}

/**
 * Check if string is valid UUID
 */
function em_is_uuid(string $uuid): bool
{
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
}

/**
 * Get status badge HTML
 */
function em_status_badge(string $status): string
{
    $colors = [
        'active' => 'success',
        'inactive' => 'secondary',
        'pending' => 'warning',
        'failed' => 'danger',
        'success' => 'success',
        'error' => 'danger',
        'processing' => 'info'
    ];
    
    $color = $colors[strtolower($status)] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst($status) . '</span>';
}

/**
 * Paginate results
 */
function em_paginate(int $total, int $perPage = 20, int $currentPage = 1): array
{
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Render pagination HTML
 */
function em_render_pagination(array $pagination, string $baseUrl): string
{
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($pagination['has_prev']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['current_page'] - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($pagination['has_next']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['current_page'] + 1) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Convert bytes to human readable format
 */
function em_format_bytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Get time ago string
 */
function em_time_ago(?string $datetime): string
{
    if (!$datetime) {
        return 'Never';
    }
    
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}
