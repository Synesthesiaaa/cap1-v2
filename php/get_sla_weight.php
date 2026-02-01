<?php
/**
 * Get SLA weight by ID or by category+type
 */
header('Content-Type: application/json');
session_start();
require_once 'db.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$category = trim($_GET['category'] ?? '');
$department = trim($_GET['department'] ?? '');

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tbl_sla_weight WHERE sla_weight_id = ?");
    $stmt->bind_param("i", $id);
} elseif ($category && $department) {
    $stmt = $conn->prepare("SELECT * FROM tbl_sla_weight WHERE category = ? AND department_name = ?");
    $stmt->bind_param("ss", $category, $department);
} else {
    echo json_encode(['error' => 'Provide id or category+department']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'error' => 'Not found']);
}
