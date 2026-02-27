<?php
/**
 * Public Registration Endpoint (Customer-only)
 *
 * Creates a new tbl_user row with:
 * - user_role = customer
 * - user_type = external
 * - status    = active
 * - department_id = NULL
 *
 * Returns JSON.
 */
header('Content-Type: application/json; charset=utf-8');

// Load bootstrap/autoload if available (for CSRF middleware, Connection, etc.)
if (file_exists(__DIR__ . '/../bootstrap.php')) {
    require_once __DIR__ . '/../bootstrap.php';
}

session_start();

require_once __DIR__ . '/db.php';

// CSRF middleware if available
$csrfAvailable = class_exists('Middleware\\CsrfMiddleware');

function json_out(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed'], 405);
}

if ($csrfAvailable && !\Middleware\CsrfMiddleware::validateToken()) {
    json_out(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$passwordConfirm = (string)($_POST['password_confirm'] ?? '');
$company = trim($_POST['company'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// Validation
$errors = [];
if ($name === '') $errors[] = 'Name is required';
if ($email === '') $errors[] = 'Email is required';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is invalid';
if ($password === '') $errors[] = 'Password is required';
if ($password !== '' && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
if ($passwordConfirm === '') $errors[] = 'Confirm password is required';
if ($password !== '' && $passwordConfirm !== '' && !hash_equals($password, $passwordConfirm)) $errors[] = 'Passwords do not match';

if (!empty($errors)) {
    json_out(['success' => false, 'error' => implode('. ', $errors)], 400);
}

// Enforce role/type regardless of client input
$userType = 'external';
$userRole = 'customer';
$status = 'active';

// Uniqueness: check tbl_user
$check = $conn->prepare("SELECT user_id FROM tbl_user WHERE email = ? LIMIT 1");
if (!$check) {
    json_out(['success' => false, 'error' => 'Database error'], 500);
}
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    json_out(['success' => false, 'error' => 'Email already in use'], 409);
}
$check->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO tbl_user (user_type, department_id, name, company, email, password, status, user_role, phone) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    json_out(['success' => false, 'error' => 'Database error'], 500);
}
$stmt->bind_param("ssssssss", $userType, $name, $company, $email, $hashedPassword, $status, $userRole, $phone);

if (!$stmt->execute()) {
    $err = $conn->error ?: 'Insert failed';
    $stmt->close();
    json_out(['success' => false, 'error' => 'Failed to create user: ' . $err], 500);
}

$stmt->close();
json_out(['success' => true, 'message' => 'Account created. Please log in.']);

