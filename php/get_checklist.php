<?php
require_once "db.php";
require_once "checklist_common.php";
header("Content-Type: application/json");

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "error" => "Unauthorized"
    ]);
    exit;
}

// Validate reference ID
$ref = $_GET['ref'] ?? '';
if (!$ref) {
    echo json_encode([
        "ok" => false,
        "error" => "Missing ticket reference"
    ]);
    exit;
}

$ticket = checklistResolveTicketByRef($conn, $ref);
if (!$ticket) {
    http_response_code(404);
    echo json_encode([
        "ok" => false,
        "error" => "Ticket not found"
    ]);
    exit;
}

$canView = checklistCanViewTicket($ticket);
if (!$canView) {
    http_response_code(403);
    echo json_encode([
        "ok" => false,
        "error" => "Forbidden"
    ]);
    exit;
}

$ticketId = (int)$ticket['ticket_id'];
$items = checklistFetchItems($conn, $ticketId);
$progress = checklistComputeProgress($items);
$permissions = checklistPermissionsForCurrentUser($ticket);
$hasSourceType = checklistHasColumn($conn, 'source_type');

foreach ($items as &$item) {
    $item['can_delete'] = checklistCanDeleteItem($item, $ticket, $permissions, $hasSourceType);
}
unset($item);
$conn->close();

echo json_encode([
    "ok" => true,
    "ticket" => [
        "ticket_id" => $ticketId,
        "reference_id" => $ticket['reference_id'],
        "status" => $ticket['status']
    ],
    "items" => $items,
    "progress" => $progress,
    "permissions" => $permissions
]);
?>
