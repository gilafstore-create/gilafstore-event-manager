<?php
/**
 * Event Manager - CRM Schema Repair Migration (idempotent)
 *
 * Fixes the historical `credentials` vs `config` column mismatch on
 * em_crm_connections and adds the `last_tested_at` column. Safe to run
 * multiple times; only applies changes that are actually needed.
 *
 * Run from browser (admin only) or CLI:
 *   php event-manager/migrations/fix_crm_schema.php
 */

$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/../../includes/db_connect.php';

if (!$isCli) {
    require_once __DIR__ . '/../includes/em_auth.php';
    if (!em_is_authenticated()) {
        http_response_code(403);
        exit('Forbidden: admin authentication required.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

/** @var PDO $pdo */
$applied = [];
$skipped = [];

function emfix_columns(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $stmt->execute([$table]);
    return array_map('strtolower', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME'));
}

function emfix_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1"
    );
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

try {
    if (!emfix_table_exists($pdo, 'em_crm_connections')) {
        // Table not yet installed — nothing to repair. install.php will create it correctly.
        $skipped[] = 'em_crm_connections does not exist yet (run install.php first).';
    } else {
        $cols = emfix_columns($pdo, 'em_crm_connections');

        $hasConfig      = in_array('config', $cols, true);
        $hasCredentials = in_array('credentials', $cols, true);

        if (!$hasConfig && $hasCredentials) {
            // Rename credentials -> config, preserving any existing data.
            $pdo->exec("ALTER TABLE em_crm_connections CHANGE `credentials` `config` JSON NULL");
            $applied[] = "Renamed column `credentials` -> `config`.";
        } elseif (!$hasConfig && !$hasCredentials) {
            $pdo->exec("ALTER TABLE em_crm_connections ADD COLUMN `config` JSON NULL AFTER `crm_type`");
            $applied[] = "Added missing `config` column.";
        } elseif ($hasConfig && $hasCredentials) {
            // Both exist (rare drift). Copy non-null credentials into config where config is null, then drop credentials.
            $pdo->exec("UPDATE em_crm_connections SET config = credentials WHERE config IS NULL AND credentials IS NOT NULL");
            $pdo->exec("ALTER TABLE em_crm_connections DROP COLUMN `credentials`");
            $applied[] = "Merged `credentials` into `config` and dropped `credentials`.";
        } else {
            $skipped[] = "`config` column already present.";
        }

        // Refresh column list and ensure last_tested_at exists.
        $cols = emfix_columns($pdo, 'em_crm_connections');
        if (!in_array('last_tested_at', $cols, true)) {
            $pdo->exec("ALTER TABLE em_crm_connections ADD COLUMN `last_tested_at` TIMESTAMP NULL AFTER `last_sync_at`");
            $applied[] = "Added `last_tested_at` column.";
        } else {
            $skipped[] = "`last_tested_at` column already present.";
        }
    }

    // Ensure the SSRF private-endpoint setting exists (default: blocked in prod).
    if (emfix_table_exists($pdo, 'em_settings')) {
        // Default: block private/loopback endpoints (production-safe). Operators
        // can flip this to '1' in em_settings for local/dev CRM testing.
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO em_settings (key_name, value, type) VALUES ('crm_allow_private_endpoints', '0', 'boolean')"
        );
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $applied[] = "Seeded setting `crm_allow_private_endpoints` = 0.";
        } else {
            $skipped[] = "Setting `crm_allow_private_endpoints` already present.";
        }
    }

    $out  = "CRM Schema Repair — completed\n\n";
    $out .= "Applied (" . count($applied) . "):\n";
    foreach ($applied as $a) { $out .= "  + $a\n"; }
    $out .= "\nSkipped (" . count($skipped) . "):\n";
    foreach ($skipped as $s) { $out .= "  - $s\n"; }

    echo $out;
} catch (Throwable $e) {
    http_response_code(500);
    echo "CRM Schema Repair FAILED: " . $e->getMessage() . "\n";
    error_log('EM_CRM fix_crm_schema: ' . $e->getMessage());
}
