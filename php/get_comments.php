<?php
require_once __DIR__ . '/ticket_api_common.php';

$auth = ticketApiRequireAuth();
$ticket = ticketApiResolveTicketByRef($conn, $_GET['ref'] ?? '');
ticketApiAuthorizeTicketAccess($ticket, 'read_ticket', $auth);
$ticketId = (int)$ticket['ticket_id'];

$stmt = $conn->prepare("
    SELECT comment_id, ticket_id, commenter_id, is_technician, role, comment_text, created_at
    FROM tbl_ticket_comment
    WHERE ticket_id = ?
    ORDER BY created_at ASC, comment_id ASC
");
if (!$stmt) {
    ticketApiJson(['ok' => false, 'error' => 'Database prepare failed'], 500);
}

$stmt->bind_param('i', $ticketId);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

$stmt->close();
$conn->close();

ticketApiJson([
    'ok' => true,
    'ticket' => [
        'ticket_id' => $ticketId,
        'reference_id' => $ticket['reference_id'],
    ],
    'comments' => $comments,
]);
