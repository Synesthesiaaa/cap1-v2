<?php
include("db.php");
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'department_head') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Sorting parameters
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortDirection = isset($_GET['direction']) && strtoupper($_GET['direction']) === 'ASC' ? 'ASC' : 'DESC';

// Validate sort column to prevent SQL injection
$allowedColumns = ['reference_id', 'title', 'category', 'type', 'user_name', 'department_name', 'urgency', 'status', 'created_at'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'created_at';
}

$departmentHeadId = $_SESSION['id'];

try {
    // First resolve the department head's department via tbl_department_head (preferred)
    // and fall back to tbl_user if necessary.
    $departmentName = null;

    // Try tbl_department_head joined with tbl_department
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

    // Fallback: use tbl_user if no explicit mapping found
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
        echo json_encode(['tickets' => [], 'pagination' => ['currentPage' => $page, 'totalPages' => 0, 'totalCount' => 0]]);
        exit();
    }

    // Ticket routing rule:
    // - Ticket "type" represents the department (e.g., IT, Finance, Engineering)
    // - Department head sees tickets where t.type matches their department_name
    $departmentName = $deptRow['department_name'];

    // Get total count of tickets for this department type
    $countSql = "SELECT COUNT(*) as total FROM tbl_ticket t WHERE t.type = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("s", $departmentName);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'];
    $totalPages = $totalCount > 0 ? ceil($totalCount / $perPage) : 0;
    $countStmt->close();

    // Get tickets whose type matches this department
    $sql = "SELECT
        t.ticket_id,
        t.reference_id,
        t.title,
        t.category,
        t.type,
        t.urgency,
        t.status,
        t.created_at,
        u.name as user_name,
        d.department_name
        FROM tbl_ticket t
        LEFT JOIN tbl_user u ON t.user_id = u.user_id
        LEFT JOIN tbl_department d ON u.department_id = d.department_id
        WHERE t.type = ?
        ORDER BY {$sortColumn} {$sortDirection}
        LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $departmentName, $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'tickets' => $tickets,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
