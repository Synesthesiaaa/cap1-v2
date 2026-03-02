<?php
require_once __DIR__ . '/ticket_api_common.php';
require_once __DIR__ . '/customer_summary_refresh.php';

if (file_exists(__DIR__ . '/insert_log_monitor.php')) {
    require_once __DIR__ . '/insert_log_monitor.php';
}

$auth = ticketApiRequireAuth();
ticketApiRequireRole(['technician', 'department_head', 'admin']);

$ref = (string)($_POST['ref'] ?? '');
$reason = trim((string)($_POST['reason'] ?? ''));
$departmentId = (int)($_POST['department_id'] ?? $_POST['new_department_id'] ?? 0);
$technicianId = (int)($_POST['technician_id'] ?? $_POST['new_technician_id'] ?? 0);
$priorityInput = (string)($_POST['priority'] ?? '');

if ($reason === '') {
    ticketApiJson(['ok' => false, 'error' => 'Escalation reason required'], 400);
}
if ($departmentId <= 0) {
    ticketApiJson(['ok' => false, 'error' => 'Department is required'], 400);
}
if ($technicianId <= 0) {
    ticketApiJson(['ok' => false, 'error' => 'Technician is required'], 400);
}
if ($priorityInput === '') {
    ticketApiJson(['ok' => false, 'error' => 'Priority is required'], 400);
}

$ticket = ticketApiResolveTicketByRef($conn, $ref);
$normalized = ticketApiNormalizePriority($priorityInput);

if (!ticketApiDepartmentExists($conn, $departmentId)) {
    ticketApiJson(['ok' => false, 'error' => 'Department not found'], 400);
}
if (!ticketApiTechnicianExistsInDepartment($conn, $technicianId, $departmentId)) {
    ticketApiJson(['ok' => false, 'error' => 'Selected technician does not belong to the selected department'], 400);
}

$ticketId = (int)$ticket['ticket_id'];
$prevTechnicianId = isset($ticket['assigned_technician_id']) ? (int)$ticket['assigned_technician_id'] : null;
$prevDepartmentId = ticketApiCurrentDepartmentIdFromType($conn, (string)($ticket['type'] ?? ''));

$hasEscState = false;
$colRes = $conn->query("SHOW COLUMNS FROM tbl_ticket LIKE 'escalation_state'");
if ($colRes && $colRes->num_rows > 0) {
    $hasEscState = true;
}
if ($colRes) {
    $colRes->free();
}

try {
    $conn->begin_transaction();

    $esc = $conn->prepare("
        INSERT INTO tbl_ticket_escalation
          (ticket_id, prev_technician_id, new_technician_id, prev_department_id, new_department_id, reason, escalator_id, escalation_type, sla_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'manual', 'escalated')
    ");
    if (!$esc) {
        throw new RuntimeException('Failed to prepare escalation insert');
    }
    $esc->bind_param(
        'iiiiisi',
        $ticketId,
        $prevTechnicianId,
        $technicianId,
        $prevDepartmentId,
        $departmentId,
        $reason,
        $auth['user_id']
    );
    if (!$esc->execute()) {
        throw new RuntimeException('Failed to insert escalation record');
    }
    $escalationId = (int)$conn->insert_id;
    $esc->close();

    if ($prevTechnicianId !== null && $prevTechnicianId > 0 && $prevTechnicianId !== $technicianId) {
        $dec = $conn->prepare("
            UPDATE tbl_technician
            SET active_tickets = IF(active_tickets > 0, active_tickets - 1, 0)
            WHERE technician_id = ?
        ");
        if (!$dec) {
            throw new RuntimeException('Failed to prepare previous technician update');
        }
        $dec->bind_param('i', $prevTechnicianId);
        if (!$dec->execute()) {
            throw new RuntimeException('Failed to update previous technician load');
        }
        $dec->close();
    }

    if ($prevTechnicianId !== $technicianId) {
        $inc = $conn->prepare("
            UPDATE tbl_technician
            SET active_tickets = active_tickets + 1
            WHERE technician_id = ?
        ");
        if (!$inc) {
            throw new RuntimeException('Failed to prepare new technician update');
        }
        $inc->bind_param('i', $technicianId);
        if (!$inc->execute()) {
            throw new RuntimeException('Failed to update new technician load');
        }
        $inc->close();
    }

    if ($hasEscState) {
        $update = $conn->prepare("
            UPDATE tbl_ticket
            SET assigned_technician_id = ?, priority = ?, urgency = ?, escalation_state = 'escalated'
            WHERE ticket_id = ?
        ");
    } else {
        $update = $conn->prepare("
            UPDATE tbl_ticket
            SET assigned_technician_id = ?, priority = ?, urgency = ?
            WHERE ticket_id = ?
        ");
    }
    if (!$update) {
        throw new RuntimeException('Failed to prepare ticket update');
    }
    $update->bind_param(
        'issi',
        $technicianId,
        $normalized['priority'],
        $normalized['urgency'],
        $ticketId
    );
    if (!$update->execute()) {
        throw new RuntimeException('Failed to update ticket assignment');
    }
    $update->close();

    $logDetails = sprintf(
        'Ticket escalated to technician #%d (department #%d) with priority %s. Reason: %s',
        $technicianId,
        $departmentId,
        $normalized['priority'],
        $reason
    );
    if (function_exists('insertTicketLog')) {
        insertTicketLog($ticketId, $auth['user_id'], $auth['role'], 'escalate', $logDetails, $conn);
    }

    refreshTicketSummaryByTicketId($ticketId, $conn);
    $conn->commit();

    ticketApiJson([
        'ok' => true,
        'message' => 'Ticket successfully escalated',
        'ticket' => [
            'ticket_id' => $ticketId,
            'reference_id' => $ticket['reference_id'],
            'assigned_technician_id' => $technicianId,
            'priority' => $normalized['priority'],
            'urgency' => $normalized['urgency'],
            'escalation_state' => $hasEscState ? 'escalated' : null,
        ],
        'escalation' => [
            'escalation_id' => $escalationId,
            'new_department_id' => $departmentId,
            'new_technician_id' => $technicianId,
            'sla_status' => 'escalated',
        ],
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('[escalate_ticket] ' . $e->getMessage());
    ticketApiJson(['ok' => false, 'error' => 'Escalation failed'], 500);
}
