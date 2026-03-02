<?php
/**
 * Shared checklist helpers for ticket detail/list APIs.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Read checklist table columns once per request.
 */
function checklistTableColumns(mysqli $conn): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = [];
    $res = $conn->query("SHOW COLUMNS FROM tbl_ticket_checklist");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cached[$row['Field']] = true;
        }
        $res->free();
    }
    return $cached;
}

function checklistHasColumn(mysqli $conn, string $column): bool
{
    $columns = checklistTableColumns($conn);
    return isset($columns[$column]);
}

/**
 * Current actor metadata from session.
 */
function checklistCurrentActor(): array
{
    return [
        'user_id' => (int)($_SESSION['id'] ?? 0),
        'role' => (string)($_SESSION['role'] ?? ''),
    ];
}

/**
 * Whether ticket is considered active for customer-side mutations.
 */
function checklistIsTicketActive(array $ticket): bool
{
    $status = strtolower((string)($ticket['status'] ?? ''));
    return !in_array($status, ['complete', 'resolved'], true);
}

/**
 * Access check for checklist visibility.
 */
function checklistCanViewTicket(array $ticket): bool
{
    $actor = checklistCurrentActor();
    if ($actor['user_id'] <= 0) {
        return false;
    }

    $role = $actor['role'];
    if (in_array($role, ['technician', 'admin', 'department_head', 'evaluator'], true)) {
        return true;
    }

    return (int)($ticket['user_id'] ?? 0) === $actor['user_id'];
}

/**
 * Resolve ticket by reference id.
 */
function checklistResolveTicketByRef(mysqli $conn, string $ref): ?array
{
    $stmt = $conn->prepare("
        SELECT ticket_id, reference_id, status, user_id, assigned_technician_id
        FROM tbl_ticket
        WHERE reference_id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $ticket ?: null;
}

/**
 * Role-based permissions for checklist mutation.
 */
function checklistPermissionsForCurrentUser(?array $ticket = null): array
{
    $actor = checklistCurrentActor();
    $role = $actor['role'];
    $isStaff = in_array($role, ['technician', 'admin', 'department_head'], true);

    $canEdit = $isStaff;
    $canToggle = $isStaff;
    $canDelete = $isStaff;
    $canSetRequired = $isStaff;

    if ($role === 'user' && is_array($ticket)) {
        $isOwner = (int)($ticket['user_id'] ?? 0) === $actor['user_id'];
        $isActive = checklistIsTicketActive($ticket);
        $canEdit = $isOwner && $isActive;
        $canToggle = $canEdit;
        $canDelete = $canEdit;
    }

    return [
        'user_id' => $actor['user_id'],
        'role' => $role,
        'can_edit' => $canEdit,
        'can_toggle' => $canToggle,
        'can_delete' => $canDelete,
        'can_set_required' => $canSetRequired,
    ];
}

/**
 * Resolve checklist item + parent ticket metadata by item id.
 */
function checklistResolveItemById(mysqli $conn, int $itemId): ?array
{
    $hasSourceType = checklistHasColumn($conn, 'source_type');
    $sourceTypeExpr = $hasSourceType ? "COALESCE(c.source_type, 'manual')" : "'manual'";

    $stmt = $conn->prepare("
        SELECT
            c.item_id,
            c.ticket_id,
            c.created_by,
            {$sourceTypeExpr} AS source_type,
            t.reference_id,
            t.status,
            t.user_id,
            t.assigned_technician_id
        FROM tbl_ticket_checklist c
        INNER JOIN tbl_ticket t ON t.ticket_id = c.ticket_id
        WHERE c.item_id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Manual source detection with backward-compatible fallback.
 */
function checklistItemIsManual(array $item, bool $hasSourceTypeColumn): bool
{
    if ($hasSourceTypeColumn) {
        return strtolower((string)($item['source_type'] ?? 'manual')) === 'manual';
    }
    return (int)($item['created_by'] ?? 0) > 0;
}

/**
 * Item-level delete eligibility.
 */
function checklistCanDeleteItem(array $item, array $ticket, array $permissions, bool $hasSourceTypeColumn): bool
{
    if (empty($permissions['can_delete'])) {
        return false;
    }

    if (!checklistItemIsManual($item, $hasSourceTypeColumn)) {
        return false;
    }

    $role = (string)($permissions['role'] ?? '');
    if ($role === 'user') {
        $actorUserId = (int)($permissions['user_id'] ?? 0);
        return $actorUserId > 0 && (int)($item['created_by'] ?? 0) === $actorUserId;
    }

    return true;
}

/**
 * Fetch checklist rows for ticket id.
 */
function checklistFetchItems(mysqli $conn, int $ticketId): array
{
    $hasSourceType = checklistHasColumn($conn, 'source_type');
    $hasStepOrder = checklistHasColumn($conn, 'step_order');
    $hasIsRequired = checklistHasColumn($conn, 'is_required');
    $hasCompletedBy = checklistHasColumn($conn, 'completed_by');
    $hasCompletedByRole = checklistHasColumn($conn, 'completed_by_role');
    $hasUpdatedAt = checklistHasColumn($conn, 'updated_at');

    $sourceTypeExpr = $hasSourceType ? "COALESCE(source_type, 'manual')" : "'manual'";
    $stepOrderExpr = $hasStepOrder ? "COALESCE(step_order, 9999)" : "9999";
    $isRequiredExpr = $hasIsRequired ? "COALESCE(is_required, 1)" : "1";
    $completedByExpr = $hasCompletedBy ? "completed_by" : "NULL";
    $completedByRoleExpr = $hasCompletedByRole ? "completed_by_role" : "NULL";
    $updatedAtExpr = $hasUpdatedAt ? "updated_at" : "NULL";

    $sql = "
        SELECT
            item_id,
            ticket_id,
            created_by,
            is_technician,
            description,
            is_completed,
            created_at,
            completed_at,
            {$sourceTypeExpr} AS source_type,
            {$stepOrderExpr} AS step_order,
            {$isRequiredExpr} AS is_required,
            {$completedByExpr} AS completed_by,
            {$completedByRoleExpr} AS completed_by_role,
            {$updatedAtExpr} AS updated_at
        FROM tbl_ticket_checklist
        WHERE ticket_id = ?
        ORDER BY
            {$stepOrderExpr} ASC,
            created_at ASC,
            item_id ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();
    return $items;
}

/**
 * Compute checklist progress metrics.
 */
function checklistComputeProgress(array $items): array
{
    $total = count($items);
    $completed = 0;
    $requiredTotal = 0;
    $requiredCompleted = 0;

    foreach ($items as $item) {
        $isCompleted = ((int)($item['is_completed'] ?? 0) === 1);
        $isRequired = ((int)($item['is_required'] ?? 1) === 1);

        if ($isCompleted) {
            $completed++;
        }
        if ($isRequired) {
            $requiredTotal++;
            if ($isCompleted) {
                $requiredCompleted++;
            }
        }
    }

    $percent = $total > 0 ? (int)round(($completed / $total) * 100) : 0;
    $requiredPercent = $requiredTotal > 0 ? (int)round(($requiredCompleted / $requiredTotal) * 100) : 0;

    return [
        'total' => $total,
        'completed' => $completed,
        'percent' => $percent,
        'required_total' => $requiredTotal,
        'required_completed' => $requiredCompleted,
        'required_percent' => $requiredPercent,
    ];
}
