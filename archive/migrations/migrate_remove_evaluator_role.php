<?php
/**
 * Migration Script: Remove Evaluator Role from tbl_user
 * 
 * Removes 'evaluator' from user_role enum and converts existing evaluator users to 'customer'
 */

require_once 'db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starting migration: Remove Evaluator Role\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Step 1: Convert existing evaluator users to 'customer'
    echo "Step 1: Converting evaluator users to 'customer' role...\n";
    $update_stmt = $conn->prepare("UPDATE tbl_user SET user_role = 'customer' WHERE user_role = 'evaluator'");
    $update_stmt->execute();
    $affected = $conn->affected_rows;
    $update_stmt->close();
    echo "  ✓ Converted $affected evaluator users to 'customer' role\n\n";
    
    // Step 2: Modify enum to remove 'evaluator'
    // Note: MySQL/MariaDB doesn't support removing enum values directly
    // We need to alter the column to a new enum without 'evaluator'
    echo "Step 2: Removing 'evaluator' from user_role enum...\n";
    
    $alter_query = "ALTER TABLE tbl_user MODIFY user_role ENUM('customer','department_head','admin') DEFAULT 'customer'";
    
    if ($conn->query($alter_query)) {
        echo "  ✓ Successfully removed 'evaluator' from user_role enum\n\n";
    } else {
        echo "  ✗ Error: " . $conn->error . "\n";
        echo "  You may need to manually alter the table:\n";
        echo "  ALTER TABLE tbl_user MODIFY user_role ENUM('customer','department_head','admin') DEFAULT 'customer';\n\n";
    }
    
    // Step 3: Update ticket_logs enum if it contains evaluator
    echo "Step 3: Removing 'evaluator' from tbl_ticket_logs user_role enum...\n";
    $alter_logs_query = "ALTER TABLE tbl_ticket_logs MODIFY user_role ENUM('user','technician','system','department_head','admin') NOT NULL DEFAULT 'user'";
    
    if ($conn->query($alter_logs_query)) {
        echo "  ✓ Successfully removed 'evaluator' from tbl_ticket_logs user_role enum\n\n";
    } else {
        echo "  ⚠ Warning: " . $conn->error . "\n";
        echo "  You may need to manually alter the table:\n";
        echo "  ALTER TABLE tbl_ticket_logs MODIFY user_role ENUM('user','technician','system','department_head','admin') NOT NULL DEFAULT 'user';\n\n";
    }
    
    echo str_repeat("=", 60) . "\n";
    echo "Migration completed!\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
