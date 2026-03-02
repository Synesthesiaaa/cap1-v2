<?php
require_once __DIR__ . '/ticket_api_common.php';

$auth = ticketApiRequireAuth();

$comment = trim((string)($_POST['comment'] ?? ''));
if ($comment === '') {
    ticketApiJson(['ok' => false, 'error' => 'Empty comment'], 400);
}

$ticket = ticketApiResolveTicketByRef($conn, $_POST['ref'] ?? '');
ticketApiAuthorizeTicketAccess($ticket, 'comment_ticket', $auth);
$ticketId = (int)$ticket['ticket_id'];

$isTechnician = $auth['role'] === 'technician' ? 1 : 0;

$stmt = $conn->prepare("
    INSERT INTO tbl_ticket_comment (ticket_id, commenter_id, is_technician, role, comment_text)
    VALUES (?, ?, ?, ?, ?)
");
if (!$stmt) {
    ticketApiJson(['ok' => false, 'error' => 'Database prepare failed'], 500);
}

$stmt->bind_param('iiiss', $ticketId, $auth['user_id'], $isTechnician, $auth['role'], $comment);
$ok = $stmt->execute();
$commentId = $ok ? (int)$conn->insert_id : 0;
$stmt->close();

if (!$ok) {
    ticketApiJson(['ok' => false, 'error' => 'Failed to add comment'], 500);
}

$conn->close();

ticketApiJson([
    'ok' => true,
    'comment' => [
        'comment_id' => $commentId,
        'ticket_id' => $ticketId,
        'commenter_id' => $auth['user_id'],
        'is_technician' => $isTechnician,
        'role' => $auth['role'],
        'comment_text' => $comment,
    ],
]);
