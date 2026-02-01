<?php
/**
 * Migration: Create tbl_department_head and populate it from tbl_user.
 *
 * 1. Creates table `tbl_department_head` if it does not exist.
 * 2. Inserts one row per department head found in `tbl_user` (user_role = 'department_head').
 *
 * Usage (from project root):
 *   php php/migrate_department_heads_to_new_table.php
 */

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die("This migration script must be run from the command line.\n");
}

require_once __DIR__ . '/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    echo "Starting migration for department heads...\n";

    // 1. Create table if it doesn't exist
    $createSql = "
        CREATE TABLE IF NOT EXISTS tbl_department_head (
            department_head_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            department_id INT(11) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (department_head_id),
            UNIQUE KEY uniq_department_head_user (user_id),
            KEY idx_department_id (department_id),
            CONSTRAINT fk_dept_head_user FOREIGN KEY (user_id) REFERENCES tbl_user(user_id),
            CONSTRAINT fk_dept_head_department FOREIGN KEY (department_id) REFERENCES tbl_department(department_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

    if (!$conn->query($createSql)) {
        throw new Exception("Failed to create tbl_department_head: " . $conn->error);
    }

    echo "Ensured table tbl_department_head exists.\n";

    // 2. Load department head users from tbl_user
    $selectSql = "
        SELECT user_id, department_id
        FROM tbl_user
        WHERE user_role = 'department_head'
    ";

    $result = $conn->query($selectSql);

    if (!$result) {
        throw new Exception("Failed to select department heads from tbl_user: " . $conn->error);
    }

    $departmentHeads = [];
    while ($row = $result->fetch_assoc()) {
        $departmentHeads[] = $row;
    }
    $result->close();

    if (empty($departmentHeads)) {
        echo "No department head users found in tbl_user. Nothing to migrate.\n";
        exit(0);
    }

    echo "Found " . count($departmentHeads) . " department head user(s) in tbl_user.\n";

    // 3. Insert or update into tbl_department_head
    $conn->begin_transaction();

    $insertSql = "
        INSERT INTO tbl_department_head (user_id, department_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE department_id = VALUES(department_id)
    ";
    $insertStmt = $conn->prepare($insertSql);

    $migratedCount = 0;
    foreach ($departmentHeads as $head) {
        $userId = (int)$head['user_id'];
        $deptId = isset($head['department_id']) ? (int)$head['department_id'] : null;

        // Bind department_id as null-safe
        if ($deptId > 0) {
            $insertStmt->bind_param("ii", $userId, $deptId);
        } else {
            // When department_id is NULL or 0, set it to NULL in the new table
            $null = null;
            $insertStmt->bind_param("ii", $userId, $null);
        }

        $insertStmt->execute();
        $migratedCount += $insertStmt->affected_rows >= 0 ? 1 : 0;
    }

    $conn->commit();
    $insertStmt->close();

    echo "Migration complete. Department head records in tbl_department_head: {$migratedCount}.\n";
    exit(0);
} catch (Throwable $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}


