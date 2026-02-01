<?php
include("db.php");
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'department_head') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$departmentHeadId = $_SESSION['id'];

try {
    // First resolve the department head's department via tbl_department_head (preferred),
    // then fall back to tbl_user if necessary.
    $departmentName = null;

    $deptSql = "
        SELECT d.department_id, d.department_name
        FROM tbl_department_head dh
        INNER JOIN tbl_department d ON dh.department_id = d.department_id
        WHERE dh.user_id = ?
        LIMIT 1
    ";
    $deptStmt = $conn->prepare($deptSql);
    $deptStmt->bind_param("i", $departmentHeadId);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    $deptRow = $deptResult->fetch_assoc();
    $deptStmt->close();

    if (!$deptRow) {
        $fallbackSql = "
            SELECT d.department_id, d.department_name
            FROM tbl_user u
            LEFT JOIN tbl_department d ON u.department_id = d.department_id
            WHERE u.user_id = ? AND u.user_role = 'department_head'
            LIMIT 1
        ";
        $fbStmt = $conn->prepare($fallbackSql);
        $fbStmt->bind_param("i", $departmentHeadId);
        $fbStmt->execute();
        $fbResult = $fbStmt->get_result();
        $deptRow = $fbResult->fetch_assoc();
        $fbStmt->close();
    }

    if (!$deptRow || empty($deptRow['department_name'])) {
        echo json_encode([
            'open' => 0,
            'pending' => 0,
            'assigned' => 0,
            'complete' => 0
        ]);
        exit();
    }

    $departmentName = $deptRow['department_name'];

    // Get counts for tickets whose type matches the department head's department
    $summarySql = "
        SELECT
            SUM(CASE WHEN status = 'unassigned' THEN 1 ELSE 0 END) as open,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'followup' THEN 1 ELSE 0 END) as assigned,
            SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as complete
        FROM tbl_ticket t
        WHERE t.type = ?
    ";

    $stmt = $conn->prepare($summarySql);
    $stmt->bind_param("s", $departmentName);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'open' => $summary['open'] ?? 0,
        'pending' => $summary['pending'] ?? 0,
        'assigned' => $summary['assigned'] ?? 0,
        'complete' => $summary['complete'] ?? 0
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
