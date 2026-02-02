<?php
/**
 * Migration: Add notes column to tbl_user for Customer Management
 * Run once: php php/migrate_add_user_notes.php
 */

require_once __DIR__ . '/db.php';

$result = $conn->query("SHOW COLUMNS FROM tbl_user LIKE 'notes'");
if ($result->num_rows > 0) {
    echo "Column 'notes' already exists in tbl_user. Skipping.\n";
    exit(0);
}

$sql = "ALTER TABLE tbl_user ADD COLUMN notes TEXT DEFAULT NULL AFTER phone";
if ($conn->query($sql)) {
    echo "Successfully added 'notes' column to tbl_user.\n";
} else {
    echo "Error: " . $conn->error . "\n";
    exit(1);
}
