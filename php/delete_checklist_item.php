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

$itemId = (int)($_POST['item_id'] ?? 0);
if ($itemId <= 0) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Invalid checklist item"]);
    exit;
}

$item = checklistResolveItemById($conn, $itemId);
if (!$item) {
    http_response_code(404);
    echo json_encode(["ok" => false, "error" => "Checklist item not found"]);
    $conn->close();
    exit;
}

$ticket = [
    'ticket_id' => (int)$item['ticket_id'],
    'reference_id' => (string)$item['reference_id'],
    'status' => (string)$item['status'],
    'user_id' => (int)$item['user_id'],
    'assigned_technician_id' => (int)$item['assigned_technician_id'],
];
if (!checklistCanViewTicket($ticket)) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Forbidden"]);
    $conn->close();
    exit;
}

$permissions = checklistPermissionsForCurrentUser($ticket);
$hasSourceType = checklistHasColumn($conn, 'source_type');
if (!checklistCanDeleteItem($item, $ticket, $permissions, $hasSourceType)) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Forbidden"]);
    $conn->close();
    exit;
}

$stmt = $conn->prepare("DELETE FROM tbl_ticket_checklist WHERE item_id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Delete prepare failed"]);
    $conn->close();
    exit;
}
$stmt->bind_param("i", $itemId);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if (!$ok || $affected <= 0) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Delete failed"]);
    $conn->close();
    exit;
}

$ticketId = (int)$item['ticket_id'];
$items = checklistFetchItems($conn, $ticketId);
$progress = checklistComputeProgress($items);

echo json_encode([
    "ok" => true,
    "item_id" => $itemId,
    "progress" => $progress
]);

if (function_exists('insertTicketLog')) {
    $actorId = (int)($_SESSION['id'] ?? 0);
    $actorRole = (string)($_SESSION['role'] ?? 'user');
    $details = "Checklist item #{$itemId} deleted";
    insertTicketLog($ticketId, $actorId, $actorRole ?: 'user', 'checklist_delete', $details, $conn);
}

$conn->close();
?>
