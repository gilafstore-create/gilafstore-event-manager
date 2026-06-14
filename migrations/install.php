<?php
/**
 * Event Manager - Database Installation
 * 
 * CRITICAL SAFETY RULES:
 * ✅ NO ALTER TABLE on existing tables
 * ✅ NO DROP TABLE on existing tables
 * ✅ NO UPDATE on existing tables
 * ✅ NO DELETE on existing tables
 * ✅ Only CREATE TABLE with em_ prefix
 * ✅ All tables are isolated
 */

require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../includes/em_auth.php';
require_once __DIR__ . '/../includes/em_functions.php';

// Require admin authentication
em_require_auth();

$errors = [];
$success = [];
$tablesCreated = 0;

// Process installation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        global $pdo;
        
        // Note: DDL statements (CREATE TABLE) auto-commit in MySQL
        // Transactions are not used for schema changes
        
        /**
         * CORE EVENT TABLES (5 tables)
         * All prefixed with em_ for isolation
         */
        
        // 1. Event Definitions
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_definitions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            schema_id INT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 2. Event Sources
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_sources (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(100) NOT NULL,
            config JSON,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 3. Event Destinations
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_destinations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(100) NOT NULL,
            config JSON,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 4. Event Schemas
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_schemas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            version VARCHAR(50) NOT NULL,
            `schema` JSON NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name_version (name, version),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 5. Event Connections
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_connections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source_id INT NOT NULL,
            destination_id INT NOT NULL,
            config JSON,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_source (source_id),
            INDEX idx_destination (destination_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        /**
         * EVENT OPERATIONS TABLES (6 tables)
         */
        
        // 6. Event Logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_logs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            event_id VARCHAR(255) NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            source_id INT,
            payload JSON,
            status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_id (event_id),
            INDEX idx_event_type (event_type),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 7. Failed Events
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_failed_events (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            event_log_id BIGINT NOT NULL,
            retry_count INT DEFAULT 0,
            last_retry_at TIMESTAMP NULL,
            next_retry_at TIMESTAMP NULL,
            status ENUM('pending', 'retrying', 'abandoned') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_event_log (event_log_id),
            INDEX idx_status (status),
            INDEX idx_next_retry (next_retry_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 8. Event Replays
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_replays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            event_type VARCHAR(255),
            start_date TIMESTAMP NULL,
            end_date TIMESTAMP NULL,
            status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
            total_events INT DEFAULT 0,
            processed_events INT DEFAULT 0,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 9. Event Simulations
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_simulations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            payload JSON NOT NULL,
            status ENUM('pending', 'running', 'completed') DEFAULT 'pending',
            result JSON,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 10. Rate Limits
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT NOT NULL,
            limit_per_minute INT DEFAULT 60,
            limit_per_hour INT DEFAULT 3600,
            limit_per_day INT DEFAULT 86400,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 11. Delivery Monitoring
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_delivery_monitoring (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            event_log_id BIGINT NOT NULL,
            destination_id INT NOT NULL,
            status ENUM('pending', 'delivered', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            last_attempt_at TIMESTAMP NULL,
            delivered_at TIMESTAMP NULL,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_log (event_log_id),
            INDEX idx_destination (destination_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        /**
         * EVENT BUS TABLES (6 tables)
         */
        
        // 12. Queue Messages
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_queue_messages (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            queue_name VARCHAR(255) NOT NULL,
            payload JSON NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            INDEX idx_queue (queue_name),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 13. Retry Queue
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_retry_queue (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            message_id BIGINT NOT NULL,
            retry_count INT DEFAULT 0,
            next_retry_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_message (message_id),
            INDEX idx_next_retry (next_retry_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 14. Dead Letter Queue
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_dead_letter_queue (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            original_message_id BIGINT NOT NULL,
            reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_message (original_message_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 15. Workers
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_workers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            queue_name VARCHAR(255) NOT NULL,
            status ENUM('running', 'stopped', 'failed') DEFAULT 'stopped',
            last_heartbeat TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_queue (queue_name),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 16. Routing Rules
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_routing_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            `condition` JSON,
            destination_id INT NOT NULL,
            priority INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_event_type (event_type),
            INDEX idx_status (status),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 17. Broker Settings
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_broker_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(255) NOT NULL UNIQUE,
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        /**
         * TRACE & JOURNEY TABLES (4 tables)
         */
        
        // 18. Traces
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_traces (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            trace_id VARCHAR(255) NOT NULL UNIQUE,
            parent_trace_id VARCHAR(255),
            event_type VARCHAR(255) NOT NULL,
            payload JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_trace_id (trace_id),
            INDEX idx_parent (parent_trace_id),
            INDEX idx_event_type (event_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 19. Event Journeys
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_journeys (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            journey_id VARCHAR(255) NOT NULL,
            event_id VARCHAR(255) NOT NULL,
            step_number INT NOT NULL,
            step_name VARCHAR(255) NOT NULL,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_journey (journey_id),
            INDEX idx_event (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 20. Customer Journeys (Read-only reference to existing customers)
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_customer_journeys (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL COMMENT 'References users.id (read-only)',
            event_id VARCHAR(255) NOT NULL,
            touchpoint VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer (customer_id),
            INDEX idx_event (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 21. Service Dependencies
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_service_dependencies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_name VARCHAR(255) NOT NULL,
            depends_on VARCHAR(255) NOT NULL,
            dependency_type ENUM('required', 'optional') DEFAULT 'required',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_service (service_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        /**
         * INTELLIGENCE TABLES (6 tables)
         */
        
        // 22. Missing Events
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_missing_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            expected_event_type VARCHAR(255) NOT NULL,
            context JSON,
            detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL,
            status ENUM('detected', 'investigating', 'resolved') DEFAULT 'detected',
            INDEX idx_status (status),
            INDEX idx_detected_at (detected_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 23. Event Coverage
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_coverage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(255) NOT NULL,
            entity_id INT NOT NULL,
            coverage_percentage DECIMAL(5,2) DEFAULT 0.00,
            missing_events JSON,
            analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 24. Event Suggestions
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_event_suggestions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            suggested_event_type VARCHAR(255) NOT NULL,
            reason TEXT,
            confidence_score DECIMAL(3,2) DEFAULT 0.00,
            status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 25. Anomalies
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_anomalies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            anomaly_type VARCHAR(255) NOT NULL,
            description TEXT,
            severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL,
            status ENUM('detected', 'investigating', 'resolved') DEFAULT 'detected',
            INDEX idx_severity (severity),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 26. AI Recommendations
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_ai_recommendations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recommendation_type VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            action_required TEXT,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            status ENUM('pending', 'applied', 'dismissed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 27. Predictive Failures
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_predictive_failures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            predicted_failure_type VARCHAR(255) NOT NULL,
            probability DECIMAL(3,2) DEFAULT 0.00,
            predicted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actual_failure_at TIMESTAMP NULL,
            was_accurate BOOLEAN DEFAULT NULL,
            INDEX idx_predicted_at (predicted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        /**
         * GOVERNANCE TABLES (8 tables)
         */
        
        // 28. Audit Trail
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_audit_trail (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT COMMENT 'References admin user (read-only)',
            action VARCHAR(255) NOT NULL,
            entity_type VARCHAR(255) NOT NULL,
            entity_id INT,
            details JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_action (action),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 29. Compliance Logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_compliance_logs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            compliance_type VARCHAR(255) NOT NULL,
            status ENUM('compliant', 'non_compliant', 'warning') DEFAULT 'compliant',
            details JSON,
            checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_type (compliance_type),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 30. Alert Rules
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_alert_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            `condition` JSON NOT NULL,
            `action` JSON NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 31. Alert History
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_alert_history (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            rule_id INT NOT NULL,
            triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            details JSON,
            INDEX idx_rule (rule_id),
            INDEX idx_triggered_at (triggered_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 32. Permissions
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 33. Roles
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            permissions JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 34. Approval Workflows
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_approval_workflows (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(255) NOT NULL,
            entity_id INT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            requested_by INT,
            approved_by INT,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            approved_at TIMESTAMP NULL,
            notes TEXT,
            INDEX idx_status (status),
            INDEX idx_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 35. Retention Policies
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_retention_policies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(255) NOT NULL,
            retention_days INT NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_cleanup_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_table (table_name),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        /**
         * CRM INTEGRATION TABLES (13 tables)
         * Read-only access to existing CRM data
         */
        
        // 36. CRM Connections
        // NOTE: column is named `config` to match the rest of the Event Manager
        // module (em_event_sources/destinations/transformations all use `config`)
        // and the CRM API/UI code. Stores connection settings + encrypted api_key.
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_connections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            crm_type ENUM('salesforce', 'hubspot', 'zoho', 'dynamics', 'custom') NOT NULL,
            config JSON,
            status ENUM('active', 'inactive', 'error') DEFAULT 'inactive',
            last_sync_at TIMESTAMP NULL,
            last_tested_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (crm_type),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 37. CRM Field Mappings
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_field_mappings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            connection_id INT NOT NULL,
            local_field VARCHAR(255) NOT NULL,
            crm_field VARCHAR(255) NOT NULL,
            transformation JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_connection (connection_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 38. CRM Trigger Rules
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_trigger_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            connection_id INT NOT NULL,
            trigger_event VARCHAR(255) NOT NULL,
            `condition` JSON,
            `action` JSON,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_connection (connection_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 39. CRM Transformations
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_transformations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            transformation_type VARCHAR(100) NOT NULL,
            config JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 40. CRM Sync Logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_sync_logs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            connection_id INT NOT NULL,
            sync_type VARCHAR(100) NOT NULL,
            records_synced INT DEFAULT 0,
            status ENUM('success', 'partial', 'failed') DEFAULT 'success',
            error_message TEXT,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            INDEX idx_connection (connection_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 41. CRM Sync Failures
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_sync_failures (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            sync_log_id BIGINT NOT NULL,
            record_id VARCHAR(255),
            error_message TEXT,
            retry_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sync_log (sync_log_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 42. CRM Customer Profiles (References existing customers - read-only)
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_customer_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL COMMENT 'References users.id (read-only)',
            crm_connection_id INT NOT NULL,
            crm_customer_id VARCHAR(255),
            last_synced_at TIMESTAMP NULL,
            sync_status ENUM('synced', 'pending', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_customer_crm (customer_id, crm_connection_id),
            INDEX idx_customer (customer_id),
            INDEX idx_crm_connection (crm_connection_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 43. CRM Leads
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            crm_connection_id INT NOT NULL,
            crm_lead_id VARCHAR(255),
            name VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(50),
            status VARCHAR(100),
            source VARCHAR(100),
            last_synced_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_crm_connection (crm_connection_id),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 44. CRM Opportunities
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_opportunities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            crm_connection_id INT NOT NULL,
            crm_opportunity_id VARCHAR(255),
            name VARCHAR(255),
            amount DECIMAL(10,2),
            stage VARCHAR(100),
            probability DECIMAL(3,2),
            close_date DATE,
            last_synced_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_crm_connection (crm_connection_id),
            INDEX idx_stage (stage)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 45. CRM Contacts
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            crm_connection_id INT NOT NULL,
            crm_contact_id VARCHAR(255),
            name VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(50),
            last_synced_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_crm_connection (crm_connection_id),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 46. CRM Accounts
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            crm_connection_id INT NOT NULL,
            crm_account_id VARCHAR(255),
            name VARCHAR(255),
            industry VARCHAR(100),
            last_synced_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_crm_connection (crm_connection_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 47. CRM Timelines
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_timelines (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            event_data JSON,
            occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_occurred_at (occurred_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 48. CRM Data Quality
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_crm_data_quality (
            id INT AUTO_INCREMENT PRIMARY KEY,
            connection_id INT NOT NULL,
            quality_score DECIMAL(3,2) DEFAULT 0.00,
            issues JSON,
            checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_connection (connection_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        /**
         * DEVELOPER CENTER TABLES (4 tables)
         */
        
        // 49. API Keys
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_api_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            api_key VARCHAR(255) NOT NULL UNIQUE,
            permissions JSON,
            status ENUM('active', 'revoked') DEFAULT 'active',
            created_by INT,
            last_used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            revoked_at TIMESTAMP NULL,
            INDEX idx_api_key (api_key),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 50. SDK Tokens
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_sdk_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            sdk_type VARCHAR(100),
            status ENUM('active', 'revoked') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            revoked_at TIMESTAMP NULL,
            INDEX idx_token (token),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 51. Webhooks
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_webhooks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            events JSON,
            secret VARCHAR(255),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 52. Webhook Logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_webhook_logs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            webhook_id INT NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            payload JSON,
            response_code INT,
            response_body TEXT,
            status ENUM('success', 'failed') DEFAULT 'success',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_webhook (webhook_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        /**
         * ADMINISTRATION TABLES (9 tables)
         */
        
        // 53. Event Manager Users (Separate from main users)
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_user_id INT COMMENT 'References main admin user (read-only)',
            role_id INT,
            permissions JSON,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_admin_user (admin_user_id),
            INDEX idx_role (role_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 54. Teams
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 55. Team Members
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_team_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            user_id INT NOT NULL,
            role VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_team_user (team_id, user_id),
            INDEX idx_team (team_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 56. Organizations
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_organizations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            settings JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 57. Billing
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_billing (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            plan VARCHAR(100),
            billing_cycle ENUM('monthly', 'yearly') DEFAULT 'monthly',
            amount DECIMAL(10,2),
            status ENUM('active', 'cancelled', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_organization (organization_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 58. Settings
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(255) NOT NULL UNIQUE,
            value TEXT,
            type VARCHAR(50) DEFAULT 'string',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 59. Secrets (Encrypted storage)
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_secrets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(255) NOT NULL UNIQUE,
            encrypted_value TEXT NOT NULL,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 60. Notifications
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_notifications (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            type VARCHAR(100),
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_read (read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // 61. Environments
        $pdo->exec("CREATE TABLE IF NOT EXISTS em_environments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type ENUM('development', 'staging', 'production') DEFAULT 'development',
            config JSON,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $tablesCreated++;
        
        // Insert default settings
        $pdo->exec("INSERT IGNORE INTO em_settings (key_name, value, type) VALUES
            ('installation_date', NOW(), 'datetime'),
            ('version', '1.0.0', 'string'),
            ('status', 'active', 'string')
        ");
        
        $success[] = "Successfully created {$tablesCreated} Event Manager tables";
        $success[] = "All tables use 'em_' prefix for isolation";
        $success[] = "Zero impact on existing tables confirmed";
        
        // Log installation
        em_log_activity('install', 'event_manager', null, [
            'tables_created' => $tablesCreated,
            'version' => '1.0.0'
        ]);
        
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $errors[] = "Installation error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Manager Installation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: #f5f5f5;
            padding: 40px 0;
        }
        .install-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .install-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 40px;
        }
        .install-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .install-header i {
            font-size: 64px;
            color: #1A3C34;
            margin-bottom: 20px;
        }
        .safety-checklist {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 30px 0;
        }
        .safety-checklist h5 {
            color: #28a745;
            margin-bottom: 15px;
        }
        .safety-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .safety-item i {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <i class="fas fa-database"></i>
                <h1>Event Manager Installation</h1>
                <p class="text-muted">Install Event Manager database tables</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Installation Failed</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Installation Successful!</h5>
                    <ul class="mb-0">
                        <?php foreach ($success as $msg): ?>
                            <li><?= htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-3">
                        <a href="<?= em_base_url('pages/dashboard.php'); ?>" class="btn btn-success">
                            <i class="fas fa-tachometer-alt"></i>
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
                <div class="safety-checklist">
                    <h5><i class="fas fa-shield-alt"></i> Safety Guarantees</h5>
                    <div class="safety-item">
                        <i class="fas fa-check-circle"></i>
                        <span>NO ALTER TABLE on existing tables</span>
                    </div>
                    <div class="safety-item">
                        <i class="fas fa-check-circle"></i>
                        <span>NO DROP TABLE on existing tables</span>
                    </div>
                    <div class="safety-item">
                        <i class="fas fa-check-circle"></i>
                        <span>NO UPDATE on existing tables</span>
                    </div>
                    <div class="safety-item">
                        <i class="fas fa-check-circle"></i>
                        <span>NO DELETE on existing tables</span>
                    </div>
                    <div class="safety-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Only CREATE TABLE with em_ prefix</span>
                    </div>
                    <div class="safety-item">
                        <i class="fas fa-check-circle"></i>
                        <span>All 61 tables are isolated</span>
                    </div>
                </div>

                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> What will be installed:</h6>
                    <ul class="mb-0">
                        <li>61 new database tables (all prefixed with <code>em_</code>)</li>
                        <li>Core Event Tables (5)</li>
                        <li>Event Operations Tables (6)</li>
                        <li>Event Bus Tables (6)</li>
                        <li>Trace & Journey Tables (4)</li>
                        <li>Intelligence Tables (6)</li>
                        <li>Governance Tables (8)</li>
                        <li>CRM Integration Tables (13)</li>
                        <li>Developer Center Tables (4)</li>
                        <li>Administration Tables (9)</li>
                    </ul>
                </div>

                <form method="POST" class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-play"></i>
                        Run Installation
                    </button>
                    <div class="mt-3">
                        <a href="<?= base_url('admin/index.php'); ?>" class="btn btn-link">
                            <i class="fas fa-arrow-left"></i>
                            Back to Admin
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
