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
if (!in_array($role, ['admin', 'technician', 'department_head'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$claimId = (int)($_POST['claim_id'] ?? 0);
$toStatus = trim($_POST['to_status'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');
$resolutionAction = trim($_POST['resolution_action'] ?? '');
$actorId = (int)$_SESSION['id'];

$allowedStatuses = ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'in_service', 'completed', 'cancelled'];
if ($claimId <= 0 || !in_array($toStatus, $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

$get = $conn->prepare("SELECT claim_status, ticket_id FROM tbl_warranty_claim WHERE claim_id = ? LIMIT 1");
if (!$get) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Prepare failed']);
    exit;
}
$get->bind_param("i", $claimId);
$get->execute();
$row = $get->get_result()->fetch_assoc();
$get->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Claim not found']);
    exit;
}

$fromStatus = $row['claim_status'];
$ticketId = (int)($row['ticket_id'] ?? 0);

$allowedTransitions = [
    'submitted' => ['under_review', 'cancelled'],
    'under_review' => ['approved', 'rejected', 'cancelled'],
    'approved' => ['in_service', 'completed', 'cancelled'],
    'rejected' => ['under_review', 'cancelled'],
    'in_service' => ['completed', 'cancelled'],
    'completed' => [],
    'cancelled' => [],
];
if ($toStatus !== $fromStatus) {
    $next = $allowedTransitions[$fromStatus] ?? [];
    if (!in_array($toStatus, $next, true)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => "Invalid transition from {$fromStatus} to {$toStatus}"]);
        exit;
    }
}

$updateSql = "
    UPDATE tbl_warranty_claim
    SET claim_status = ?,
        resolution_action = CASE WHEN ? <> '' THEN ? ELSE resolution_action END,
        approved_by = CASE WHEN ? = 'approved' THEN ? ELSE approved_by END,
        approved_at = CASE WHEN ? = 'approved' THEN NOW() ELSE approved_at END,
        notes = CASE WHEN ? <> '' THEN ? ELSE notes END
    WHERE claim_id = ?
";
$upd = $conn->prepare($updateSql);
if (!$upd) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Prepare failed']);
    exit;
}
$upd->bind_param(
    "sssissssi",
    $toStatus,
    $resolutionAction,
    $resolutionAction,
    $toStatus,
    $actorId,
    $toStatus,
    $remarks,
    $remarks,
    $claimId
);
$ok = $upd->execute();
$upd->close();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conn->error ?: 'Transition failed']);
    exit;
}

$hist = $conn->prepare("
    INSERT INTO tbl_warranty_claim_history
      (claim_id, from_status, to_status, actor_id, actor_role, remarks)
    VALUES (?, ?, ?, ?, ?, ?)
");
if ($hist) {
    $hist->bind_param("ississ", $claimId, $fromStatus, $toStatus, $actorId, $role, $remarks);
    $hist->execute();
    $hist->close();
}

echo json_encode([
    'ok' => true,
    'claim_id' => $claimId,
    'from_status' => $fromStatus,
    'to_status' => $toStatus
]);

if ($ticketId > 0 && function_exists('insertTicketLog')) {
    $detail = "Warranty claim #{$claimId} moved from {$fromStatus} to {$toStatus}";
    insertTicketLog($ticketId, $actorId, $role, 'warranty_transition', $detail, $conn);
}
