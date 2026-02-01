<?php
/**
 * Migration Script: Remove Evaluator Fields from tbl_ticket
 * 
 * Removes evaluator_id, evaluated_at, and evaluation_notes columns
 */

require_once 'db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starting migration: Remove Evaluator Fields\n";
echo str_repeat("=", 60) . "\n\n";

$queries = [];

// Remove foreign key constraints first
$queries[] = "ALTER TABLE tbl_ticket DROP FOREIGN KEY IF EXISTS fk_evaluator_id";
$queries[] = "ALTER TABLE tbl_ticket DROP FOREIGN KEY IF EXISTS fk_ticket_evaluator";

// Remove index
$queries[] = "ALTER TABLE tbl_ticket DROP INDEX IF EXISTS idx_evaluator_id";

// Remove columns
$queries[] = "ALTER TABLE tbl_ticket DROP COLUMN IF EXISTS evaluator_id";
$queries[] = "ALTER TABLE tbl_ticket DROP COLUMN IF EXISTS evaluated_at";
$queries[] = "ALTER TABLE tbl_ticket DROP COLUMN IF EXISTS evaluation_notes";

$success_count = 0;
$error_count = 0;

foreach ($queries as $index => $query) {
    echo "Executing: " . substr($query, 0, 60) . "...\n";
    
    if ($conn->query($query)) {
        echo "  ✓ Success\n";
        $success_count++;
    } else {
        // Ignore errors for IF EXISTS clauses (MySQL/MariaDB may not support)
        if (strpos($query, 'IF EXISTS') !== false) {
            echo "  ⚠ Warning (may not be supported): " . $conn->error . "\n";
        } else {
            echo "  ✗ Error: " . $conn->error . "\n";
            $error_count++;
        }
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "Migration completed!\n";
echo "Success: $success_count operations\n";
echo "Errors: $error_count operations\n";

if ($error_count > 0) {
    echo "\nPlease review the errors above.\n";
    exit(1);
} else {
    echo "\nAll evaluator fields removed successfully!\n";
}

$conn->close();
?>
