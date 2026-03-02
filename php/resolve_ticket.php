<?php
require_once __DIR__ . '/ticket_api_common.php';
require_once __DIR__ . '/customer_summary_refresh.php';
require_once __DIR__ . '/ticket_log_helper.php';

$auth = ticketApiRequireAuth();
$ticket = ticketApiResolveTicketByRef($conn, $_POST['ref'] ?? '');
ticketApiAuthorizeTicketAccess($ticket, 'resolve_ticket', $auth);

$ticketId = (int)$ticket['ticket_id'];
$prevStatus = (string)($ticket['status'] ?? 'pending');
$newStatus = $prevStatus === 'complete' ? 'pending' : 'complete';
$message = $newStatus === 'complete'
    ? 'Ticket has been completed.'
    : 'Ticket has been reopened.';

try {
    $conn->begin_transaction();

    if ($newStatus === 'complete') {
        $update = $conn->prepare("UPDATE tbl_ticket SET status = ?, resolved_at = NOW() WHERE ticket_id = ?");
        $update->bind_param('si', $newStatus, $ticketId);
    } else {
        $update = $conn->prepare("UPDATE tbl_ticket SET status = ?, resolved_at = NULL WHERE ticket_id = ?");
        $update->bind_param('si', $newStatus, $ticketId);
    }
    if (!$update || !$update->execute()) {
        throw new RuntimeException('Failed to update ticket status');
    }
    $update->close();

    $reply = $conn->prepare("
        INSERT INTO tbl_ticket_reply (ticket_id, replied_by, replier_id, reply_text, created_at)
        VALUES (?, 'system', 0, ?, NOW())
    ");
    if ($reply) {
        $reply->bind_param('is', $ticketId, $message);
        $reply->execute();
        $reply->close();
    }

    $actionType = $newStatus === 'complete' ? 'complete' : 'reopen';
    if (function_exists('insertTicketLog')) {
        insertTicketLog($ticketId, $auth['user_id'], $auth['role'], $actionType, $message, $conn);
    }

    refreshTicketSummaryByTicketId($ticketId, $conn);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    ticketApiLog('resolve_ticket_failed', [
        'ticket_id' => $ticketId,
        'actor_id' => $auth['user_id'] ?? 0,
        'role' => $auth['role'] ?? '',
        'error' => $e->getMessage(),
    ]);
    ticketApiJson(['ok' => false, 'error' => 'Failed to update ticket'], 500);
}

$conn->close();
ticketApiJson([
    'ok' => true,
    'ticket' => [
        'ticket_id' => $ticketId,
        'reference_id' => $ticket['reference_id'],
        'previous_status' => $prevStatus,
        'status' => $newStatus,
    ],
    'message' => $message,
]);
