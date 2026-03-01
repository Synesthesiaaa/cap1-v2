<?php
/**
 * Customer management performance indexes (v2).
 * Run once: php archive/migrations/add_indexes_customer_management_v2.php
 */

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/php/db.php';

$dbName = defined('DB_NAME') ? DB_NAME : ($_ENV['DB_NAME'] ?? 'ts_isc');
$dbEsc = $conn->real_escape_string($dbName);

function indexExists(mysqli $conn, string $dbEsc, string $table, string $index): bool
{
    $tableEsc = $conn->real_escape_string($table);
    $indexEsc = $conn->real_escape_string($index);
    $sql = "SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = '{$dbEsc}'
              AND TABLE_NAME = '{$tableEsc}'
              AND INDEX_NAME = '{$indexEsc}'
            LIMIT 1";
    $r = $conn->query($sql);
    return $r && $r->num_rows > 0;
}

function ensureIndex(mysqli $conn, string $dbEsc, string $table, string $index, string $ddl): void
{
    if (indexExists($conn, $dbEsc, $table, $index)) {
        echo "[skip] {$table}.{$index} already exists\n";
        return;
    }
    if ($conn->query($ddl)) {
        echo "[ok] Added {$table}.{$index}\n";
        return;
    }
    echo "[err] Failed {$table}.{$index}: {$conn->error}\n";
}

echo "Applying customer management indexes (v2)...\n";

// tbl_customer_product
ensureIndex(
    $conn,
    $dbEsc,
    'tbl_customer_product',
    'idx_user_status_created',
    "ALTER TABLE tbl_customer_product ADD INDEX idx_user_status_created (user_id, status, created_at)"
);

// tbl_user
ensureIndex(
    $conn,
    $dbEsc,
    'tbl_user',
    'idx_user_type_user_id',
    "ALTER TABLE tbl_user ADD INDEX idx_user_type_user_id (user_type, user_id)"
);

// tbl_user_ticket_summary
ensureIndex(
    $conn,
    $dbEsc,
    'tbl_user_ticket_summary',
    'idx_current_ticket_status',
    "ALTER TABLE tbl_user_ticket_summary ADD INDEX idx_current_ticket_status (current_ticket_status)"
);
ensureIndex(
    $conn,
    $dbEsc,
    'tbl_user_ticket_summary',
    'idx_sla_status',
    "ALTER TABLE tbl_user_ticket_summary ADD INDEX idx_sla_status (sla_status)"
);
ensureIndex(
    $conn,
    $dbEsc,
    'tbl_user_ticket_summary',
    'idx_last_contact',
    "ALTER TABLE tbl_user_ticket_summary ADD INDEX idx_last_contact (last_contact)"
);
ensureIndex(
    $conn,
    $dbEsc,
    'tbl_user_ticket_summary',
    'idx_ticket_urgent',
    "ALTER TABLE tbl_user_ticket_summary ADD INDEX idx_ticket_urgent (ticket_count, urgent_high_count)"
);
ensureIndex(
    $conn,
    $dbEsc,
    'tbl_user_ticket_summary',
    'idx_success_rate',
    "ALTER TABLE tbl_user_ticket_summary ADD INDEX idx_success_rate (success_rate)"
);

echo "Done.\n";

