<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';
if (file_exists(__DIR__ . '/insert_log_monitor.php')) {
    require_once __DIR__ . '/insert_log_monitor.php';
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['user', 'admin', 'technician', 'department_head'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$ticketId = (int)($_POST['ticket_id'] ?? 0);
$customerProductId = isset($_POST['customer_product_id']) ? (int)$_POST['customer_product_id'] : null;
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
$claimType = trim($_POST['claim_type'] ?? 'inspection');
$notes = trim($_POST['notes'] ?? '');
$createdBy = (int)$_SESSION['id'];

$allowedTypes = ['repair', 'replacement', 'refund', 'inspection'];
if ($ticketId <= 0 || !in_array($claimType, $allowedTypes, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO tbl_warranty_claim
      (ticket_id, customer_product_id, product_id, claim_type, claim_status, notes, created_by)
    VALUES (?, ?, ?, ?, 'submitted', ?, ?)
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Prepare failed']);
    exit;
}

$stmt->bind_param("iiissi", $ticketId, $customerProductId, $productId, $claimType, $notes, $createdBy);
$ok = $stmt->execute();
$claimId = $ok ? (int)$conn->insert_id : 0;
$stmt->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conn->error ?: 'Failed to create claim']);
    exit;
}

$hist = $conn->prepare("
    INSERT INTO tbl_warranty_claim_history
      (claim_id, from_status, to_status, actor_id, actor_role, remarks)
    VALUES (?, NULL, 'submitted', ?, ?, ?)
");
if ($hist) {
    $hist->bind_param("iiss", $claimId, $createdBy, $role, $notes);
    $hist->execute();
    $hist->close();
}

echo json_encode([
    'ok' => true,
    'claim_id' => $claimId,
    'claim_status' => 'submitted'
]);

if (function_exists('insertTicketLog')) {
    $detail = "Warranty claim #{$claimId} created (type: {$claimType}, status: submitted)";
    insertTicketLog($ticketId, $createdBy, $role, 'warranty_create', $detail, $conn);
}
