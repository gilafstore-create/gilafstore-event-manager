<?php
/**
 * Event Manager - Header Template
 * 
 * SAFETY: Uses existing admin styles
 * NO MODIFICATIONS to existing templates
 */

// Require authentication
em_require_auth();

$pageTitle = $pageTitle ?? 'Event Manager — Gilaf Store';
$currentPage = $currentPage ?? '';
$userName = em_get_user_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?></title>
    
    <!-- Use existing admin styles - NO NEW DEPENDENCIES -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css">
    <link rel="stylesheet" href="<?= asset_url('css/admin-premium.css'); ?>">
    
    <!-- Event Manager specific styles -->
    <link rel="stylesheet" href="<?= em_asset_url('css/event-manager.css'); ?>">
</head>
<body>
    <div class="em-layout d-flex">
        <!-- Sidebar -->
        <aside class="em-sidebar">
            <div class="em-brand">
                <i class="fas fa-project-diagram"></i>
                <span>Event Manager</span>
            </div>
            
            <nav class="em-nav">
                <a href="<?= em_base_url('pages/dashboard.php'); ?>" class="em-nav-link <?= $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>

                <!-- EVENT SETUP -->
                <div class="em-nav-section">Event Setup</div>
                <a href="<?= em_base_url('pages/event-setup/overview.php'); ?>" class="em-nav-link <?= $currentPage === 'overview' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i><span>Overview</span>
                </a>
                <a href="<?= em_base_url('pages/event-setup/definitions.php'); ?>" class="em-nav-link <?= $currentPage === 'definitions' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i><span>Event Definitions</span>
                </a>
                <a href="<?= em_base_url('pages/event-setup/sources.php'); ?>" class="em-nav-link <?= $currentPage === 'sources' ? 'active' : ''; ?>">
                    <i class="fas fa-plug"></i><span>Event Sources</span>
                </a>
                <a href="<?= em_base_url('pages/event-setup/destinations.php'); ?>" class="em-nav-link <?= $currentPage === 'destinations' ? 'active' : ''; ?>">
                    <i class="fas fa-paper-plane"></i><span>Destinations</span>
                </a>
                <a href="<?= em_base_url('pages/event-setup/schemas.php'); ?>" class="em-nav-link <?= $currentPage === 'schemas' ? 'active' : ''; ?>">
                    <i class="fas fa-file-code"></i><span>Schemas</span>
                </a>
                <a href="<?= em_base_url('pages/event-setup/settings.php'); ?>" class="em-nav-link <?= $currentPage === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-sliders-h"></i><span>Settings</span>
                </a>

                <!-- EVENT OPERATIONS -->
                <div class="em-nav-section">Event Operations</div>
                <a href="<?= em_base_url('pages/event-operations/logs.php'); ?>" class="em-nav-link <?= $currentPage === 'logs' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i><span>Event Logs</span>
                </a>
                <a href="<?= em_base_url('pages/event-operations/failed-events.php'); ?>" class="em-nav-link <?= $currentPage === 'failed-events' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i><span>Failed Events</span>
                </a>
                <a href="<?= em_base_url('pages/event-operations/replays.php'); ?>" class="em-nav-link <?= $currentPage === 'replays' ? 'active' : ''; ?>">
                    <i class="fas fa-redo"></i><span>Replays</span>
                </a>
                <a href="<?= em_base_url('pages/event-operations/simulations.php'); ?>" class="em-nav-link <?= $currentPage === 'simulations' ? 'active' : ''; ?>">
                    <i class="fas fa-flask"></i><span>Simulations</span>
                </a>
                <a href="<?= em_base_url('pages/event-operations/delivery-monitoring.php'); ?>" class="em-nav-link <?= $currentPage === 'delivery-monitoring' ? 'active' : ''; ?>">
                    <i class="fas fa-satellite-dish"></i><span>Delivery Monitoring</span>
                </a>
                <a href="<?= em_base_url('pages/event-operations/rate-limits.php'); ?>" class="em-nav-link <?= $currentPage === 'rate-limits' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i><span>Rate Limits</span>
                </a>
                <a href="<?= em_base_url('pages/event-operations/queue-status.php'); ?>" class="em-nav-link <?= $currentPage === 'queue-status' ? 'active' : ''; ?>">
                    <i class="fas fa-heartbeat"></i><span>Queue Status</span>
                </a>

                <!-- TRACE CENTER -->
                <div class="em-nav-section">Trace Center</div>
                <a href="<?= em_base_url('pages/trace-center/traces.php'); ?>" class="em-nav-link <?= $currentPage === 'traces' ? 'active' : ''; ?>">
                    <i class="fas fa-project-diagram"></i><span>Traces</span>
                </a>
                <a href="<?= em_base_url('pages/trace-center/event-journeys.php'); ?>" class="em-nav-link <?= $currentPage === 'event-journeys' ? 'active' : ''; ?>">
                    <i class="fas fa-route"></i><span>Event Journeys</span>
                </a>
                <a href="<?= em_base_url('pages/trace-center/customer-journeys.php'); ?>" class="em-nav-link <?= $currentPage === 'customer-journeys' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i><span>Customer Journeys</span>
                </a>
                <a href="<?= em_base_url('pages/trace-center/order-journey.php'); ?>" class="em-nav-link <?= $currentPage === 'order-journey' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i><span>Order Journey</span>
                </a>
                <a href="<?= em_base_url('pages/trace-center/service-map.php'); ?>" class="em-nav-link <?= $currentPage === 'service-map' ? 'active' : ''; ?>">
                    <i class="fas fa-network-wired"></i><span>Service Map</span>
                </a>
                <a href="<?= em_base_url('pages/trace-center/live-stream.php'); ?>" class="em-nav-link <?= $currentPage === 'live-stream' ? 'active' : ''; ?>">
                    <i class="fas fa-stream"></i><span>Live Stream</span>
                </a>
                <a href="<?= em_base_url('pages/trace-center/global-search.php'); ?>" class="em-nav-link <?= $currentPage === 'global-search' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i><span>Global Search</span>
                </a>

                <!-- GOVERNANCE -->
                <div class="em-nav-section">Governance</div>
                <a href="<?= em_base_url('pages/governance/audit-trail.php'); ?>" class="em-nav-link <?= $currentPage === 'audit-trail' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i><span>Audit Trail</span>
                </a>
                <a href="<?= em_base_url('pages/governance/compliance.php'); ?>" class="em-nav-link <?= $currentPage === 'compliance' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i><span>Compliance</span>
                </a>
                <a href="<?= em_base_url('pages/governance/alert-rules.php'); ?>" class="em-nav-link <?= $currentPage === 'alert-rules' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i><span>Alert Rules</span>
                </a>
                <a href="<?= em_base_url('pages/governance/permissions.php'); ?>" class="em-nav-link <?= $currentPage === 'permissions' ? 'active' : ''; ?>">
                    <i class="fas fa-lock"></i><span>Permissions</span>
                </a>
                <a href="<?= em_base_url('pages/governance/retention-policies.php'); ?>" class="em-nav-link <?= $currentPage === 'retention-policies' ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i><span>Retention Policies</span>
                </a>
                <a href="<?= em_base_url('pages/governance/approvals.php'); ?>" class="em-nav-link <?= $currentPage === 'approvals' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i><span>Approvals</span>
                </a>

                <!-- DEVELOPER CENTER -->
                <div class="em-nav-section">Developer Center</div>
                <a href="<?= em_base_url('pages/developer-center/documentation.php'); ?>" class="em-nav-link <?= $currentPage === 'documentation' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i><span>Documentation</span>
                </a>
                <a href="<?= em_base_url('pages/developer-center/api-keys.php'); ?>" class="em-nav-link <?= $currentPage === 'api-keys' ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i><span>API Keys</span>
                </a>
                <a href="<?= em_base_url('pages/developer-center/webhooks.php'); ?>" class="em-nav-link <?= $currentPage === 'webhooks' ? 'active' : ''; ?>">
                    <i class="fas fa-satellite-dish"></i><span>Webhooks</span>
                </a>
                <a href="<?= em_base_url('pages/developer-center/sdk-tokens.php'); ?>" class="em-nav-link <?= $currentPage === 'sdk-tokens' ? 'active' : ''; ?>">
                    <i class="fas fa-mobile-alt"></i><span>SDK Tokens</span>
                </a>
                <a href="<?= em_base_url('pages/developer-center/playground.php'); ?>" class="em-nav-link <?= $currentPage === 'playground' ? 'active' : ''; ?>">
                    <i class="fas fa-code"></i><span>Playground</span>
                </a>

                <!-- INTELLIGENCE -->
                <div class="em-nav-section">Intelligence</div>
                <a href="<?= em_base_url('pages/intelligence/event-coverage.php'); ?>" class="em-nav-link <?= $currentPage === 'event-coverage' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i><span>Event Coverage</span>
                </a>
                <a href="<?= em_base_url('pages/intelligence/suggestions.php'); ?>" class="em-nav-link <?= $currentPage === 'suggestions' ? 'active' : ''; ?>">
                    <i class="fas fa-lightbulb"></i><span>Suggestions</span>
                </a>
                <a href="<?= em_base_url('pages/intelligence/anomalies.php'); ?>" class="em-nav-link <?= $currentPage === 'anomalies' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-circle"></i><span>Anomalies</span>
                </a>
                <a href="<?= em_base_url('pages/intelligence/missing-event-detection.php'); ?>" class="em-nav-link <?= $currentPage === 'missing-events' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i><span>Missing Events</span>
                </a>
                <a href="<?= em_base_url('pages/intelligence/ai-event-builder.php'); ?>" class="em-nav-link <?= $currentPage === 'ai-event-builder' ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i><span>AI Event Builder</span>
                </a>

                <!-- EVENT BUS -->
                <div class="em-nav-section">Event Bus</div>
                <a href="<?= em_base_url('pages/event-bus/queue-monitor.php'); ?>" class="em-nav-link <?= $currentPage === 'queue-monitor' ? 'active' : ''; ?>">
                    <i class="fas fa-inbox"></i><span>Queue Monitor</span>
                </a>
                <a href="<?= em_base_url('pages/event-bus/message-explorer.php'); ?>" class="em-nav-link <?= $currentPage === 'message-explorer' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope-open-text"></i><span>Message Explorer</span>
                </a>
                <a href="<?= em_base_url('pages/event-bus/delivery-tracker.php'); ?>" class="em-nav-link <?= $currentPage === 'delivery-tracker' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i><span>Delivery Tracker</span>
                </a>
                <a href="<?= em_base_url('pages/event-bus/routing-rules.php'); ?>" class="em-nav-link <?= $currentPage === 'routing-rules' ? 'active' : ''; ?>">
                    <i class="fas fa-directions"></i><span>Routing Rules</span>
                </a>
                <a href="<?= em_base_url('pages/event-bus/workers.php'); ?>" class="em-nav-link <?= $currentPage === 'workers' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i><span>Workers</span>
                </a>
                <a href="<?= em_base_url('pages/event-bus/dead-letter-queue.php'); ?>" class="em-nav-link <?= $currentPage === 'dead-letter-queue' ? 'active' : ''; ?>">
                    <i class="fas fa-skull-crossbones"></i><span>Dead Letter Queue</span>
                </a>
                <a href="<?= em_base_url('pages/event-bus/retry-queue.php'); ?>" class="em-nav-link <?= $currentPage === 'retry-queue' ? 'active' : ''; ?>">
                    <i class="fas fa-redo-alt"></i><span>Retry Queue</span>
                </a>
                <a href="<?= em_base_url('pages/event-bus/broker-settings.php'); ?>" class="em-nav-link <?= $currentPage === 'broker-settings' ? 'active' : ''; ?>">
                    <i class="fas fa-sliders-h"></i><span>Broker Settings</span>
                </a>
                <a href="<?= em_base_url('pages/event-bus/event-replay.php'); ?>" class="em-nav-link <?= $currentPage === 'event-replay' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i><span>Event Replay</span>
                </a>
                <a href="<?= em_base_url('pages/event-bus/connections.php'); ?>" class="em-nav-link <?= $currentPage === 'connections' ? 'active' : ''; ?>">
                    <i class="fas fa-plug"></i><span>Connections</span>
                </a>

                <!-- CRM HUB -->
                <div class="em-nav-section">CRM Hub</div>
                <a href="<?= em_base_url('pages/crm-hub/manage-connections.php'); ?>" class="em-nav-link <?= $currentPage === 'crm-manage-connections' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i><span>Manage Connections</span>
                </a>
                <a href="<?= em_base_url('pages/crm-hub/connections.php'); ?>" class="em-nav-link <?= $currentPage === 'crm-connections' ? 'active' : ''; ?>">
                    <i class="fas fa-plug"></i><span>Connection Stats</span>
                </a>
                <a href="<?= em_base_url('pages/crm-hub/webhook-config.php'); ?>" class="em-nav-link <?= $currentPage === 'crm-webhook-config' ? 'active' : ''; ?>">
                    <i class="fas fa-satellite-dish"></i><span>Webhook Config</span>
                </a>
                <a href="<?= em_base_url('pages/crm-hub/sync-status.php'); ?>" class="em-nav-link <?= $currentPage === 'crm-sync-status' ? 'active' : ''; ?>">
                    <i class="fas fa-sync-alt"></i><span>Sync Status</span>
                </a>
                <a href="<?= em_base_url('pages/crm-hub/sync-logs.php'); ?>" class="em-nav-link <?= $currentPage === 'crm-sync-logs' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i><span>Sync Logs</span>
                </a>
                <a href="<?= em_base_url('pages/crm-hub/delivery-logs.php'); ?>" class="em-nav-link <?= $currentPage === 'crm-delivery-logs' ? 'active' : ''; ?>">
                    <i class="fas fa-list-alt"></i><span>Delivery Logs</span>
                </a>
                <a href="<?= em_base_url('pages/crm-hub/retry-history.php'); ?>" class="em-nav-link <?= $currentPage === 'crm-retry-history' ? 'active' : ''; ?>">
                    <i class="fas fa-redo-alt"></i><span>Retry History</span>
                </a>

                <div class="em-nav-divider"></div>
                <a href="<?= function_exists('getAdminUrl') ? getAdminUrl('index.php') : (function_exists('base_url') ? base_url('admin/index.php') : '/admin/index.php'); ?>" class="em-nav-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Admin</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="em-main">
            <!-- Top Bar -->
            <header class="em-topbar">
                <div class="em-topbar-left">
                    <h1 class="em-page-title"><?= htmlspecialchars($pageTitle); ?></h1>
                </div>
                <div class="em-topbar-right">
                    <span class="em-user-name">
                        <i class="fas fa-user-circle"></i>
                        <?= htmlspecialchars($userName); ?>
                    </span>
                </div>
            </header>
            
            <!-- Flash Messages -->
            <?php em_display_flash(); ?>
            
            <!-- Page Content -->
            <div class="em-content">
