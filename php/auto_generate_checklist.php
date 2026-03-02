<?php
/**
 * Auto-Generate Checklist from Templates
 * 
 * Automatically generates checklist items from templates when tickets are created
 */

require_once 'db.php';
require_once __DIR__ . '/../config/sla_automation_rules.php';

/**
 * Generate checklist items from template for a ticket
 * 
 * @param mysqli $conn Database connection
 * @param int $ticket_id Ticket ID
 * @param string $category Ticket category
 * @param int|null $department_id Department ID
 * @param array $options Optional context: priority, priority_score, user_type, sla_weight_id, normalized_category
 * @return array Results
 */
function autoGenerateChecklist($conn, $ticket_id, $category, $department_id = null, array $options = []) {
    $results = [
        'items_created' => 0,
        'template_used' => null,
        'fallback_used' => false,
        'score_tier' => null,
    ];

    $normalizedCategory = \slaNormalizeCategory((string)($options['normalized_category'] ?? $category));
    $template = checklistFindTemplate($conn, $category, $normalizedCategory, $department_id);

    $columns = checklistColumnFlags($conn);
    $created_by = 0;
    $is_technician = 0;

    if ($template) {
        $results['template_used'] = (int)$template['template_id'];
        $step_stmt = $conn->prepare("
            SELECT step_order, description, is_required
            FROM tbl_checklist_template_step
            WHERE template_id = ?
            ORDER BY step_order ASC
        ");
        $templateId = (int)$template['template_id'];
        $step_stmt->bind_param("i", $templateId);
        $step_stmt->execute();
        $step_result = $step_stmt->get_result();

        while ($step = $step_result->fetch_assoc()) {
            $ok = checklistInsertRow(
                $conn,
                $columns,
                (int)$ticket_id,
                $created_by,
                $is_technician,
                (string)$step['description'],
                'template',
                (int)$step['step_order'],
                (int)$step['is_required']
            );
            if ($ok) {
                $results['items_created']++;
            }
        }
        $step_stmt->close();
        return $results;
    }

    $existingCount = checklistTicketItemCount($conn, (int)$ticket_id);
    if ($existingCount > 0) {
        return $results;
    }

    $ticketContext = checklistResolveTicketContext($conn, (int)$ticket_id);
    $priorityScore = isset($options['priority_score']) && $options['priority_score'] !== ''
        ? (float)$options['priority_score']
        : (float)($ticketContext['sla_priority_score'] ?? 0);
    $priority = (string)($options['priority'] ?? ($ticketContext['priority'] ?? 'low'));
    $priority = \slaNormalizePriority($priority);
    if ($priorityScore <= 0 && $priority !== '') {
        if ($priority === 'urgent') {
            $priorityScore = 9.0;
        } elseif ($priority === 'high') {
            $priorityScore = 8.0;
        } elseif ($priority === 'regular') {
            $priorityScore = 7.0;
        } else {
            $priorityScore = 6.0;
        }
    }

    $userType = strtolower((string)($options['user_type'] ?? ($ticketContext['user_type'] ?? 'internal')));
    if ($userType !== 'external') {
        $userType = 'internal';
    }

    $results['fallback_used'] = true;
    $results['score_tier'] = $priority;

    $fallbackSteps = [
        ['Validate issue scope and affected asset/account', 1],
        ['Capture diagnostic evidence', 1],
        ['Confirm assignee and ETA', 1],
        ['Apply fix or workaround', 1],
        ['Confirm requester acceptance and document resolution', 1],
    ];

    if ($priorityScore >= 8.0) {
        $fallbackSteps[] = ['Notify department head / escalation readiness', 1];
    }
    if ($userType === 'external') {
        $fallbackSteps[] = ['Send customer-facing status update', 1];
    }

    $stepOrder = 1;
    foreach ($fallbackSteps as $row) {
        $ok = checklistInsertRow(
            $conn,
            $columns,
            (int)$ticket_id,
            $created_by,
            $is_technician,
            (string)$row[0],
            'system',
            $stepOrder,
            (int)$row[1]
        );
        if ($ok) {
            $results['items_created']++;
        }
        $stepOrder++;
    }

    return $results;
}

function checklistFindTemplate(mysqli $conn, string $category, string $normalizedCategory, ?int $departmentId): ?array
{
    $candidates = [$category];
    if ($normalizedCategory !== '' && !in_array($normalizedCategory, $candidates, true)) {
        $candidates[] = $normalizedCategory;
    }

    foreach ($candidates as $candidate) {
        $where = ["category = ?", "is_active = 1"];
        $params = [$candidate];
        $types = 's';

        if ($departmentId !== null) {
            $where[] = "(department_id = ? OR department_id IS NULL)";
            $params[] = $departmentId;
            $types .= 'i';
        }

        $whereClause = "WHERE " . implode(" AND ", $where) . " ORDER BY department_id DESC LIMIT 1";
        $query = "SELECT template_id FROM tbl_checklist_template $whereClause";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $template = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($template) {
            return $template;
        }
    }

    return null;
}

function checklistColumnFlags(mysqli $conn): array
{
    $columns = [];
    $colRes = $conn->query("SHOW COLUMNS FROM tbl_ticket_checklist");
    if ($colRes) {
        while ($col = $colRes->fetch_assoc()) {
            $columns[$col['Field']] = true;
        }
        $colRes->free();
    }
    return [
        'source_type' => isset($columns['source_type']),
        'step_order' => isset($columns['step_order']),
        'is_required' => isset($columns['is_required']),
    ];
}

function checklistInsertRow(
    mysqli $conn,
    array $flags,
    int $ticketId,
    int $createdBy,
    int $isTechnician,
    string $description,
    string $sourceType,
    int $stepOrder,
    int $isRequired
): bool {
    if ($flags['source_type'] && $flags['step_order'] && $flags['is_required']) {
        $stmt = $conn->prepare("
            INSERT INTO tbl_ticket_checklist
                (ticket_id, created_by, is_technician, description, is_completed, source_type, step_order, is_required)
            VALUES (?, ?, ?, ?, 0, ?, ?, ?)
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iiissii", $ticketId, $createdBy, $isTechnician, $description, $sourceType, $stepOrder, $isRequired);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO tbl_ticket_checklist (ticket_id, created_by, is_technician, description, is_completed)
            VALUES (?, ?, ?, ?, 0)
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iiis", $ticketId, $createdBy, $isTechnician, $description);
    }

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function checklistResolveTicketContext(mysqli $conn, int $ticketId): array
{
    $stmt = $conn->prepare("
        SELECT t.priority, t.sla_priority_score, COALESCE(u.user_type, 'internal') AS user_type
        FROM tbl_ticket t
        LEFT JOIN tbl_user u ON u.user_id = t.user_id
        WHERE t.ticket_id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: [];
}

function checklistTicketItemCount(mysqli $conn, int $ticketId): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM tbl_ticket_checklist WHERE ticket_id = ?");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['c'] ?? 0);
}
?>
