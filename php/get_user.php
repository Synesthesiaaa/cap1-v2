<?php
/**
 * Get single user by ID for User Management
 */

require_once 'db.php';
require_once 'check_um_access.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!checkUMAccess()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin only.']);
    exit;
}

$userId = intval($_GET['user_id'] ?? $_GET['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$stmt = $conn->prepare("SELECT u.user_id, u.user_type, u.department_id, u.name, u.company, u.email, u.phone, u.status, u.user_role, u.created_at, d.department_name FROM tbl_user u LEFT JOIN tbl_department d ON u.department_id = d.department_id WHERE u.user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

echo json_encode($user);
