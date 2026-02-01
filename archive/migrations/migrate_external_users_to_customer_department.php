<?php
/**
 * One-time migration:
 * 1. Ensure a 'Customer' department exists in tbl_department.
 * 2. Move all external users (tbl_user.user_type = 'external') into that department.
 *
 * Usage (from project root):
 *   php php/migrate_external_users_to_customer_department.php
 */

// Allow CLI execution without session/auth
if (php_sapi_name() !== 'cli') {
    die("This migration script must be run from the command line.\n");
}

require_once __DIR__ . '/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn->begin_transaction();

    // 1. Ensure 'Customer' department exists
    $customerDeptId = null;

    // Prefer exact 'Customer'
    $stmt = $conn->prepare("SELECT department_id, department_name FROM tbl_department WHERE department_name = 'Customer' LIMIT 1");
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $customerDeptId = (int)$row['department_id'];
        echo "Found existing department '{$row['department_name']}' with ID {$customerDeptId}.\n";
    } else {
        // If there is a legacy 'Customers' department, rename it to 'Customer'
        $stmt = $conn->prepare("SELECT department_id, department_name FROM tbl_department WHERE department_name = 'Customers' LIMIT 1");
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $customerDeptId = (int)$row['department_id'];
            echo "Renaming existing 'Customers' department (ID {$customerDeptId}) to 'Customer'.\n";

            $upd = $conn->prepare("UPDATE tbl_department SET department_name = 'Customer' WHERE department_id = ?");
            $upd->bind_param("i", $customerDeptId);
            $upd->execute();
            $upd->close();
        } else {
            // Create a new 'Customer' department
            echo "Creating new 'Customer' department...\n";
            $ins = $conn->prepare("INSERT INTO tbl_department (department_name) VALUES ('Customer')");
            $ins->execute();
            $customerDeptId = (int)$conn->insert_id;
            $ins->close();

            echo "Created 'Customer' department with ID {$customerDeptId}.\n";
        }
    }
    $stmt->close();

    if (!$customerDeptId) {
        throw new Exception("Unable to determine 'Customer' department ID.");
    }

    // 2. Migrate all external users into the Customer department
    echo "Migrating external users to department ID {$customerDeptId}...\n";

    $updateUsers = $conn->prepare(
        "UPDATE tbl_user 
         SET department_id = ? 
         WHERE user_type = 'external' 
           AND (department_id IS NULL OR department_id <> ?)"
    );
    $updateUsers->bind_param("ii", $customerDeptId, $customerDeptId);
    $updateUsers->execute();

    $affected = $updateUsers->affected_rows;
    $updateUsers->close();

    $conn->commit();

    echo "Migration complete. External users updated: {$affected}.\n";
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

exit(0);


