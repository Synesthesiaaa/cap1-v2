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

$item_id = (int)($_POST['item_id'] ?? 0);
$completed = (int)($_POST['completed'] ?? 0);
$completed = $completed === 1 ? 1 : 0;
$completed_by = (int)($_SESSION['id'] ?? 0);
$completed_by_role = $_SESSION['role'] ?? '';
$hasCompletedBy = checklistHasColumn($conn, 'completed_by');
$hasCompletedByRole = checklistHasColumn($conn, 'completed_by_role');
$hasUpdatedAt = checklistHasColumn($conn, 'updated_at');

$item = checklistResolveItemById($conn, $item_id);
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
if (!$permissions['can_toggle']) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Forbidden"]);
    $conn->close();
    exit;
}

$ticketId = (int)$item['ticket_id'];

if ($hasCompletedBy && $hasCompletedByRole && $hasUpdatedAt) {
    $stmt = $conn->prepare("
        UPDATE tbl_ticket_checklist
        SET
          is_completed = ?,
          completed_at = IF(? = 1, NOW(), NULL),
          completed_by = IF(? = 1, ?, NULL),
          completed_by_role = IF(? = 1, ?, NULL),
          updated_at = NOW()
        WHERE item_id = ?
    ");
    $stmt->bind_param(
        "iiiiisi",
        $completed,
        $completed,
        $completed,
        $completed_by,
        $completed,
        $completed_by_role,
        $item_id
    );
} else {
    $stmt = $conn->prepare("
        UPDATE tbl_ticket_checklist
        SET is_completed = ?, completed_at = IF(? = 1, NOW(), NULL)
        WHERE item_id = ?
    ");
    $stmt->bind_param("iii", $completed, $completed, $item_id);
}
$ok = $stmt->execute();
$stmt->close();

echo json_encode(["ok" => $ok, "item_id" => (int)$item_id]);

if ($ok && function_exists('insertTicketLog')) {
    $action = (int)$completed === 1 ? 'checklist_complete' : 'checklist_uncheck';
    $details = (int)$completed === 1
        ? "Checklist item #{$item_id} marked complete"
        : "Checklist item #{$item_id} marked incomplete";
    insertTicketLog($ticketId, $completed_by, $completed_by_role ?: 'user', $action, $details, $conn);
}
$conn->close();
?>
