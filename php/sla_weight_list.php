<?php
/**
 * List SLA Weights - for admin UI
 */
header('Content-Type: application/json');
session_start();
require_once 'db.php';

if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');

$sql = "SELECT sla_weight_id, category, department_name, time_value, importance, created_at 
        FROM tbl_sla_weight 
        WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (category LIKE ? OR department_name LIKE ?)";
    $p = "%{$search}%";
    $params[] = $p;
    $params[] = $p;
    $types .= 'ss';
}
if ($department !== '') {
    $sql .= " AND department_name = ?";
    $params[] = $department;
    $types .= 's';
}

$sql .= " ORDER BY department_name, category";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'data' => $rows]);
