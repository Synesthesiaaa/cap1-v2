<?php
/**
 * Shared helpers for ticket-related API endpoints.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config/sla_automation_rules.php';

function ticketApiJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function ticketApiRequireAuth(): array
{
    $userId = (int)($_SESSION['id'] ?? 0);
    if ($userId <= 0) {
        ticketApiJson(['ok' => false, 'error' => 'Unauthorized'], 401);
    }
    return [
        'user_id' => $userId,
        'role' => (string)($_SESSION['role'] ?? 'user'),
        'department_id' => (int)($_SESSION['department_id'] ?? 0),
    ];
}

function ticketApiRequireRole(array $allowedRoles): string
{
    $role = (string)($_SESSION['role'] ?? '');
    if (!in_array($role, $allowedRoles, true)) {
        ticketApiJson(['ok' => false, 'error' => 'Forbidden'], 403);
    }
    return $role;
}

function ticketApiResolveTicketByRef(mysqli $conn, string $ref): array
{
    $ref = trim($ref);
    if ($ref === '') {
        ticketApiJson(['ok' => false, 'error' => 'Missing ticket reference'], 400);
    }

    $stmt = $conn->prepare("
        SELECT ticket_id, reference_id, user_id, assigned_technician_id, type, priority, urgency, status, sla_date
        FROM tbl_ticket
        WHERE reference_id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        ticketApiJson(['ok' => false, 'error' => 'Database prepare failed'], 500);
    }

    $stmt->bind_param('s', $ref);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ticket) {
        ticketApiJson(['ok' => false, 'error' => 'Ticket not found'], 404);
    }

    return $ticket;
}

function ticketApiNormalizePriority(string $priority): array
{
    $p = \slaNormalizePriority($priority);
    $map = [
        'low' => ['priority' => 'low', 'urgency' => 'low'],
        'regular' => ['priority' => 'regular', 'urgency' => 'medium'],
        'medium' => ['priority' => 'regular', 'urgency' => 'medium'],
        'high' => ['priority' => 'high', 'urgency' => 'high'],
        'critical' => ['priority' => 'urgent', 'urgency' => 'urgent'],
        'urgent' => ['priority' => 'urgent', 'urgency' => 'urgent'],
    ];

    if (!isset($map[$p])) {
        ticketApiJson(['ok' => false, 'error' => 'Invalid priority'], 400);
    }

    return $map[$p];
}

function ticketApiTechnicianExistsInDepartment(mysqli $conn, int $technicianId, int $departmentId): bool
{
    $stmt = $conn->prepare("
        SELECT technician_id
        FROM tbl_technician
        WHERE technician_id = ?
          AND department_id = ?
          AND status = 'active'
        LIMIT 1
    ");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ii', $technicianId, $departmentId);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $ok;
}

function ticketApiDepartmentExists(mysqli $conn, int $departmentId): bool
{
    $stmt = $conn->prepare("SELECT department_id FROM tbl_department WHERE department_id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $departmentId);
    $stmt->execute();
    $ok = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $ok;
}

function ticketApiCurrentDepartmentIdFromType(mysqli $conn, string $type): ?int
{
    $stmt = $conn->prepare("SELECT department_id FROM tbl_department WHERE department_name = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $type);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['department_id'] : null;
}

function ticketApiLog(string $event, array $context = []): void
{
    $payload = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    error_log('[ticket_api] ' . $event . $payload);
}

function ticketApiActorTechnicianId(array $auth): int
{
    if (($auth['role'] ?? '') !== 'technician') {
        return 0;
    }

    $sessionTechnicianId = (int)($_SESSION['technician_id'] ?? 0);
    if ($sessionTechnicianId > 0) {
        return $sessionTechnicianId;
    }

    return (int)($auth['user_id'] ?? 0);
}

function ticketApiAuthorizeTicketAccess(array $ticket, string $capability, array $auth): void
{
    $role = (string)($auth['role'] ?? '');
    $actorUserId = (int)($auth['user_id'] ?? 0);
    $ownerUserId = (int)($ticket['user_id'] ?? 0);
    $assignedTechnicianId = (int)($ticket['assigned_technician_id'] ?? 0);

    $isAdminOrHead = in_array($role, ['admin', 'department_head'], true);
    $isOwner = $actorUserId > 0 && $ownerUserId > 0 && $actorUserId === $ownerUserId;
    $actorTechnicianId = ticketApiActorTechnicianId($auth);
    $isAssignedTechnician = $role === 'technician'
        && $actorTechnicianId > 0
        && $assignedTechnicianId > 0
        && $actorTechnicianId === $assignedTechnicianId;

    $allowed = false;

    switch ($capability) {
        case 'read_ticket':
            $allowed = $isAdminOrHead
                || $isAssignedTechnician
                || $isOwner
                || $role === 'evaluator';
            break;

        case 'reply_ticket':
            $allowed = $isAdminOrHead || $isAssignedTechnician || $isOwner;
            break;

        case 'comment_ticket':
            $allowed = $isAdminOrHead || $isAssignedTechnician;
            break;

        case 'resolve_ticket':
            $allowed = $isAdminOrHead || $isAssignedTechnician;
            break;

        case 'view_logs':
            $allowed = $isAdminOrHead || $isAssignedTechnician;
            break;

        default:
            ticketApiJson(['ok' => false, 'error' => 'Invalid access capability'], 400);
    }

    if (!$allowed) {
        ticketApiJson(['ok' => false, 'error' => 'Forbidden'], 403);
    }
}
