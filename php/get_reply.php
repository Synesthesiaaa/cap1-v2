<?php
require_once __DIR__ . '/ticket_api_common.php';

$auth = ticketApiRequireAuth();
$ticket = ticketApiResolveTicketByRef($conn, (string)($_GET['ref'] ?? ''));
ticketApiAuthorizeTicketAccess($ticket, 'read_ticket', $auth);

$ticketId = (int)$ticket['ticket_id'];

$stmt = $conn->prepare("
    SELECT
        r.reply_id,
        r.reply_text AS message,
        r.replied_by,
        r.attachment_path,
        r.created_at,
        CASE r.replied_by
            WHEN 'user' THEN COALESCE(u.name, 'User')
            WHEN 'technician' THEN COALESCE(tech.name, 'Technician')
            ELSE 'System'
        END AS sender
    FROM tbl_ticket_reply r
    LEFT JOIN tbl_user u ON r.replied_by = 'user' AND r.replier_id = u.user_id
    LEFT JOIN tbl_technician tech ON r.replied_by = 'technician' AND r.replier_id = tech.technician_id
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC, r.reply_id ASC
");

if (!$stmt) {
    ticketApiJson(['ok' => false, 'error' => 'Database prepare failed'], 500);
}

$stmt->bind_param('i', $ticketId);
$stmt->execute();
$result = $stmt->get_result();

$replies = [];
while ($row = $result->fetch_assoc()) {
    $replies[] = [
        'reply_id' => (int)$row['reply_id'],
        'message' => (string)($row['message'] ?? ''),
        'replied_by' => (string)($row['replied_by'] ?? 'system'),
        'sender' => (string)($row['sender'] ?? 'System'),
        'attachment_path' => (string)($row['attachment_path'] ?? ''),
        'created_at' => (string)($row['created_at'] ?? ''),
    ];
}

$stmt->close();
$conn->close();

ticketApiJson([
    'ok' => true,
    'ticket' => [
        'ticket_id' => $ticketId,
        'reference_id' => (string)$ticket['reference_id'],
    ],
    'replies' => $replies,
]);

