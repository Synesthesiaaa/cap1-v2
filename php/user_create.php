<?php
/**
 * Create User API for User Management
 */

require_once 'db.php';
require_once 'check_um_access.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!checkUMAccess()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied. Admin only.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$userType = $input['user_type'] ?? 'external';
$userRole = $input['user_role'] ?? 'customer';
$departmentId = !empty($input['department_id']) ? intval($input['department_id']) : 0;
$company = trim($input['company'] ?? '');
$phone = trim($input['phone'] ?? '');
$status = $input['status'] ?? 'active';

// Validation
$errors = [];
if (empty($name)) $errors[] = 'Name is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($password)) $errors[] = 'Password is required';
if (strlen($password) < 4) $errors[] = 'Password must be at least 4 characters';

$allowedUserTypes = ['internal', 'external'];
$allowedRoles = ['customer', 'department_head', 'admin'];
$allowedStatuses = ['active', 'inactive'];

if (!in_array($userType, $allowedUserTypes)) $errors[] = 'Invalid user type';
if (!in_array($userRole, $allowedRoles)) $errors[] = 'Invalid role';
if (!in_array($status, $allowedStatuses)) $errors[] = 'Invalid status';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => implode('. ', $errors)]);
    exit;
}

// Check email uniqueness
$check = $conn->prepare("SELECT user_id FROM tbl_user WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email already in use']);
    exit;
}
$check->close();

// Hash password
$hashedPassword = (strlen($password) >= 60) ? $password : password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO tbl_user (user_type, department_id, name, company, email, password, status, user_role, phone) VALUES (?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sisssssss", $userType, $departmentId, $name, $company, $email, $hashedPassword, $status, $userRole, $phone);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    $stmt->close();
    echo json_encode(['success' => true, 'user_id' => $userId]);
} else {
    $error = $conn->error;
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create user: ' . $error]);
}
