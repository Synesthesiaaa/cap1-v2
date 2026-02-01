<?php
/**
 * Add indexes to improve customer management / search performance with large ticket data.
 * Run once: php archive/migrations/add_indexes_customer_search_perf.php
 */

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/php/db.php';

$dbName = defined('DB_NAME') ? DB_NAME : ($_ENV['DB_NAME'] ?? 'ts_isc');

// tbl_ticket: composite index so correlated subquery (user_id, ORDER BY created_at DESC LIMIT 1) uses index
$r = $conn->query("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($dbName) . "' AND TABLE_NAME = 'tbl_ticket' AND INDEX_NAME = 'idx_user_created' LIMIT 1");
if ($r && $r->num_rows === 0) {
    $conn->query("ALTER TABLE tbl_ticket ADD INDEX idx_user_created (user_id, created_at)");
    echo "Added idx_user_created on tbl_ticket\n";
} else {
    echo "idx_user_created already exists on tbl_ticket\n";
}
if ($r) $r->close();

// tbl_user: index for WHERE user_type = ... in count and main query
$r = $conn->query("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($dbName) . "' AND TABLE_NAME = 'tbl_user' AND INDEX_NAME = 'idx_user_type' LIMIT 1");
if ($r && $r->num_rows === 0) {
    $conn->query("ALTER TABLE tbl_user ADD INDEX idx_user_type (user_type)");
    echo "Added idx_user_type on tbl_user\n";
} else {
    echo "idx_user_type already exists on tbl_user\n";
}
if ($r) $r->close();

echo "Done.\n";
