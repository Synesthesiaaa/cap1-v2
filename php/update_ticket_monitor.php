<?php
require_once __DIR__ . '/ticket_api_common.php';
require_once __DIR__ . '/customer_summary_refresh.php';

if (file_exists(__DIR__ . '/insert_log_monitor.php')) {
    require_once __DIR__ . '/insert_log_monitor.php';
}

$auth = ticketApiRequireAuth();
ticketApiRequireRole(['admin', 'department_head', 'technician']);

$ticket = ticketApiResolveTicketByRef($conn, $_POST['ref'] ?? '');
$ticketId = (int)$ticket['ticket_id'];

$priorityInput = (string)($_POST['priority'] ?? '');
$slaDate = trim((string)($_POST['sla_date'] ?? ''));
if ($priorityInput === '' || $slaDate === '') {
    ticketApiJson(['ok' => false, 'error' => 'Missing required fields'], 400);
}

$normalized = ticketApiNormalizePriority($priorityInput);
$stmt = $conn->prepare("UPDATE tbl_ticket SET priority = ?, urgency = ?, sla_date = ? WHERE ticket_id = ?");
if (!$stmt) {
    ticketApiJson(['ok' => false, 'error' => 'Database prepare failed'], 500);
}
$stmt->bind_param('sssi', $normalized['priority'], $normalized['urgency'], $slaDate, $ticketId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    ticketApiJson(['ok' => false, 'error' => 'Failed to update ticket'], 500);
}

if (function_exists('insertTicketLog')) {
    $details = sprintf(
        'Ticket updated: priority=%s, urgency=%s, sla_date=%s',
        $normalized['priority'],
        $normalized['urgency'],
        $slaDate
    );
    insertTicketLog($ticketId, $auth['user_id'], $auth['role'], 'edit', $details, $conn);
}

refreshTicketSummaryByTicketId($ticketId, $conn);
$conn->close();

ticketApiJson([
    'ok' => true,
    'ticket' => [
        'ticket_id' => $ticketId,
        'reference_id' => $ticket['reference_id'],
        'priority' => $normalized['priority'],
        'urgency' => $normalized['urgency'],
        'sla_date' => $slaDate,
    ]
]);
