<?php
/**
 * Create or update SLA Weight
 */
header('Content-Type: application/json');
session_start();
require_once 'db.php';

if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$id = isset($_POST['sla_weight_id']) ? intval($_POST['sla_weight_id']) : 0;
$category = trim($_POST['category'] ?? '');
$department_name = trim($_POST['department_name'] ?? '');
$time_value = min(10, max(1, intval($_POST['time_value'] ?? 1)));
$importance = min(10, max(1, intval($_POST['importance'] ?? 1)));

if (empty($category) || empty($department_name)) {
    echo json_encode(['success' => false, 'error' => 'Category and department are required']);
    exit;
}

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE tbl_sla_weight SET category=?, department_name=?, time_value=?, importance=? WHERE sla_weight_id=?");
    $stmt->bind_param("ssiii", $category, $department_name, $time_value, $importance, $id);
} else {
    $stmt = $conn->prepare("INSERT INTO tbl_sla_weight (category, department_name, time_value, importance) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $category, $department_name, $time_value, $importance);
}

if ($stmt->execute()) {
    $newId = $id > 0 ? $id : $conn->insert_id;
    echo json_encode(['success' => true, 'sla_weight_id' => $newId]);
} else {
    if ($conn->errno === 1062) {
        echo json_encode(['success' => false, 'error' => 'Category and department combination already exists']);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
$stmt->close();
