<?php
require_once __DIR__ . '/ticket_api_common.php';

$auth = ticketApiRequireAuth();
$ticket = ticketApiResolveTicketByRef($conn, $_GET['ref'] ?? '');
ticketApiAuthorizeTicketAccess($ticket, 'view_logs', $auth);
$ticketId = (int)$ticket['ticket_id'];

$stmt = $conn->prepare("
    SELECT l.*, COALESCE(u.name, t.name, 'System') AS user_name
    FROM tbl_ticket_logs l
    LEFT JOIN tbl_user u ON l.user_id = u.user_id AND l.user_role IN ('user', 'department_head', 'admin')
    LEFT JOIN tbl_technician t ON l.user_id = t.technician_id AND l.user_role = 'technician'
    WHERE l.ticket_id = ?
    ORDER BY l.created_at DESC, l.log_id DESC
    LIMIT 100
");

if (!$stmt) {
    ticketApiJson(['ok' => false, 'error' => 'Database prepare failed'], 500);
}

$stmt->bind_param('i', $ticketId);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = [
        'log_id' => (int)$row['log_id'],
        'user_name' => (string)($row['user_name'] ?? 'System'),
        'user_role' => (string)($row['user_role'] ?? 'system'),
        'action_type' => (string)($row['action_type'] ?? ''),
        'action_details' => (string)($row['action_details'] ?? ''),
        'created_at' => (string)($row['created_at'] ?? ''),
        'ip_address' => (string)($row['ip_address'] ?? ''),
        'user_agent' => (string)($row['user_agent'] ?? '')
    ];
}

$stmt->close();
$conn->close();

ticketApiJson([
    'ok' => true,
    'ticket' => [
        'ticket_id' => $ticketId,
        'reference_id' => $ticket['reference_id']
    ],
    'data' => [
        'logs' => $logs
    ]
]);
