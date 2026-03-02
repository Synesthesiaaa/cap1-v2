<?php
require_once("db.php");
require_once("checklist_common.php");
if (file_exists(__DIR__ . '/insert_log_monitor.php')) {
    require_once __DIR__ . '/insert_log_monitor.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? '';
$is_technician = ($user_role === 'technician') ? 1 : 0;

$ref = $_POST['ref'] ?? '';
$description = trim($_POST['description'] ?? '');

if ($description === "") {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Empty checklist item"]);
    exit;
}

$ticket = checklistResolveTicketByRef($conn, $ref);

if (!$ticket) {
    http_response_code(404);
    echo json_encode(["ok" => false, "error" => "Ticket not found"]);
    exit;
}

if (!checklistCanViewTicket($ticket)) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Forbidden"]);
    exit;
}

$permissions = checklistPermissionsForCurrentUser($ticket);
if (!$permissions['can_edit']) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Forbidden"]);
    exit;
}

$is_required = isset($_POST['is_required']) ? (int)$_POST['is_required'] : 0;
if (!$permissions['can_set_required']) {
    $is_required = 0;
}
$is_required = $is_required === 1 ? 1 : 0;
$step_order = isset($_POST['step_order']) ? (int)$_POST['step_order'] : 9999;

$ticket_id = $ticket['ticket_id'];

// Insert checklist item
$hasSourceType = checklistHasColumn($conn, 'source_type');
$hasIsRequired = checklistHasColumn($conn, 'is_required');
$hasStepOrder = checklistHasColumn($conn, 'step_order');

if ($hasSourceType && $hasIsRequired && $hasStepOrder) {
    $stmt = $conn->prepare("
        INSERT INTO tbl_ticket_checklist
            (ticket_id, created_by, is_technician, description, source_type, is_required, step_order)
        VALUES (?, ?, ?, ?, 'manual', ?, ?)
    ");
    $stmt->bind_param("iiisii", $ticket_id, $user_id, $is_technician, $description, $is_required, $step_order);
} else {
    $stmt = $conn->prepare("
        INSERT INTO tbl_ticket_checklist (ticket_id, created_by, is_technician, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiis", $ticket_id, $user_id, $is_technician, $description);
}

$ok = $stmt->execute();
$newId = $ok ? (int)$conn->insert_id : 0;
$stmt->close();

$items = checklistFetchItems($conn, (int)$ticket_id);
$progress = checklistComputeProgress($items);

echo json_encode([
    "ok" => $ok,
    "item_id" => $newId,
    "progress" => $progress
]);

if ($ok && function_exists('insertTicketLog')) {
    $details = "Checklist item added: " . substr($description, 0, 120);
    insertTicketLog((int)$ticket_id, (int)$user_id, (string)$user_role, 'checklist_add', $details, $conn);
}
$conn->close();
?>
