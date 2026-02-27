<?php
/**
 * Migration Script: Add resolved_at to tbl_ticket and backfill from logs
 *
 * Run: php archive/migrations/migrate_add_resolved_at.php
 * Or via browser: /archive/migrations/migrate_add_resolved_at.php
 */

require_once dirname(__DIR__, 2) . '/php/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starting migration: Add resolved_at to tbl_ticket\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Check if column already exists
$check = $conn->query("SHOW COLUMNS FROM tbl_ticket LIKE 'resolved_at'");
if ($check && $check->num_rows > 0) {
    echo "Column resolved_at already exists. Skipping ALTER.\n";
} else {
    $alter = "ALTER TABLE tbl_ticket ADD COLUMN resolved_at DATETIME DEFAULT NULL AFTER status";
    if ($conn->query($alter)) {
        echo "Added resolved_at column to tbl_ticket.\n";
    } else {
        die("Failed to add column: " . $conn->error);
    }
}

// 2. Backfill from tbl_ticket_logs where action_type IN ('complete', 'resolved')
$backfill = "
UPDATE tbl_ticket t
JOIN (
    SELECT ticket_id, MIN(created_at) AS resolved
    FROM tbl_ticket_logs
    WHERE action_type IN ('complete', 'resolved')
    GROUP BY ticket_id
) l ON t.ticket_id = l.ticket_id
SET t.resolved_at = l.resolved
WHERE t.status = 'complete' AND (t.resolved_at IS NULL OR t.resolved_at = '0000-00-00 00:00:00')
";
if ($conn->query($backfill)) {
    $affected = $conn->affected_rows;
    echo "Backfilled resolved_at from logs for $affected ticket(s).\n";
} else {
    echo "Backfill warning (non-fatal): " . $conn->error . "\n";
}

echo "\nMigration completed successfully.\n";
