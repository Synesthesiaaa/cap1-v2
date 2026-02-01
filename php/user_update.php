<?php
/**
 * Update User API for User Management
 */

require_once 'db.php';
require_once 'check_um_access.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

$userId = intval($input['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$userType = $input['user_type'] ?? null;
$userRole = $input['user_role'] ?? null;
$departmentId = isset($input['department_id']) ? (empty($input['department_id']) ? null : intval($input['department_id'])) : null;
$company = trim($input['company'] ?? '');
$phone = trim($input['phone'] ?? '');
$status = $input['status'] ?? null;
$password = $input['password'] ?? null; // Optional password change

$allowedUserTypes = ['internal', 'external'];
$allowedRoles = ['customer', 'department_head', 'admin'];
$allowedStatuses = ['active', 'inactive'];

$errors = [];
if (empty($name)) $errors[] = 'Name is required';
if (empty($email)) $errors[] = 'Email is required';
if ($userType !== null && !in_array($userType, $allowedUserTypes)) $errors[] = 'Invalid user type';
if ($userRole !== null && !in_array($userRole, $allowedRoles)) $errors[] = 'Invalid role';
if ($status !== null && !in_array($status, $allowedStatuses)) $errors[] = 'Invalid status';
if ($password !== null && $password !== '' && strlen($password) < 4) $errors[] = 'Password must be at least 4 characters';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => implode('. ', $errors)]);
    exit;
}

// Check email uniqueness (excluding current user)
$check = $conn->prepare("SELECT user_id FROM tbl_user WHERE email = ? AND user_id != ?");
$check->bind_param("si", $email, $userId);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email already in use']);
    exit;
}
$check->close();

// Build update query dynamically
$updates = ['name = ?', 'email = ?', 'company = ?', 'phone = ?', 'department_id = ?'];
$params = [$name, $email, $company, $phone, $departmentId];
$types = 'ssssi';

if ($userType !== null) {
    $updates[] = 'user_type = ?';
    $params[] = $userType;
    $types .= 's';
}
if ($userRole !== null) {
    $updates[] = 'user_role = ?';
    $params[] = $userRole;
    $types .= 's';
}
if ($status !== null) {
    $updates[] = 'status = ?';
    $params[] = $status;
    $types .= 's';
}
if ($password !== null && $password !== '') {
    $hashedPassword = (strlen($password) >= 60) ? $password : password_hash($password, PASSWORD_DEFAULT);
    $updates[] = 'password = ?';
    $params[] = $hashedPassword;
    $types .= 's';
}

$params[] = $userId;
$types .= 'i';

$sql = "UPDATE tbl_user SET " . implode(', ', $updates) . " WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true]);
} else {
    $error = $conn->error;
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update user: ' . $error]);
}
