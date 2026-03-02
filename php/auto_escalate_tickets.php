<?php
/**
 * Automatic Ticket Escalation System
 *
 * Hybrid model:
 * - Overdue by SLA date
 * - Approaching windows by SLA priority tier
 */

require_once 'db.php';
require_once 'customer_summary_refresh.php';
require_once __DIR__ . '/../config/sla_automation_rules.php';
if (file_exists(__DIR__ . '/insert_log_monitor.php')) {
    require_once __DIR__ . '/insert_log_monitor.php';
}

if (php_sapi_name() === 'cli') {
    // Scheduled CLI automation can process large backlogs.
    set_time_limit(0);
} else {
    set_time_limit(300);
}

function autoEscalateTickets(mysqli $conn): array
{
    $results = [
        'checked' => 0,
        'escalated' => 0,
        'warnings' => 0,
        'state_resets' => 0,
        'summary_refreshed_users' => 0,
        'errors' => []
    ];

    $hasEscState = autoEscHasEscalationStateColumn($conn);
    $dedupeHours = \slaEscalationDedupeHours();

    $query = "
        SELECT
            t.ticket_id,
            t.reference_id,
            t.sla_date,
            t.priority,
            t.status,
            t.assigned_technician_id,
            t.type,
            t.user_id,
            t.sla_priority_score,
            " . ($hasEscState ? "t.escalation_state" : "'on_track' AS escalation_state") . "
        FROM tbl_ticket t
        WHERE t.status <> 'complete'
          AND t.sla_date IS NOT NULL
          AND (
            t.sla_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
            " . ($hasEscState ? "OR t.escalation_state <> 'on_track'" : "") . "
          )
        ORDER BY
            t.sla_date ASC,
            FIELD(t.priority, 'urgent', 'high', 'regular', 'low') DESC,
            t.ticket_id ASC
    ";

    $result = $conn->query($query);
    if (!$result) {
        $results['errors'][] = 'Query failed: ' . $conn->error;
        return $results;
    }

    $summaryUserIds = [];

    while ($ticket = $result->fetch_assoc()) {
        $results['checked']++;

        $ticketId = (int)$ticket['ticket_id'];
        $slaDate = (string)$ticket['sla_date'];
        $currentPriority = \slaNormalizePriority((string)($ticket['priority'] ?? 'low'));
        $scorePriority = $currentPriority;
        if ($ticket['sla_priority_score'] !== null && $ticket['sla_priority_score'] !== '') {
            $scorePriority = \slaMapScoreToPriority((float)$ticket['sla_priority_score']);
        }

        $deadlineTs = strtotime($slaDate . ' 23:59:59');
        $isOverdue = strtotime($slaDate) < strtotime(date('Y-m-d'));
        $hoursUntilSla = ($deadlineTs - time()) / 3600;
        $approachingWindow = \slaApproachingHoursForPriority($scorePriority);
        $isApproaching = !$isOverdue && $hoursUntilSla >= 0 && $hoursUntilSla <= $approachingWindow;

        $targetState = 'on_track';
        $targetPriority = $currentPriority;
        $escalationReason = '';

        if ($isOverdue) {
            $targetState = 'overdue';
            $targetPriority = 'urgent';
            $escalationReason = 'Ticket is overdue (SLA date: ' . $slaDate . ')';
        } elseif ($isApproaching) {
            $targetState = 'approaching';
            if (\slaPriorityRank($scorePriority) > \slaPriorityRank($currentPriority)) {
                $targetPriority = $scorePriority;
            }
            $escalationReason = sprintf(
                'Ticket approaching SLA deadline (%.1f hours left, threshold %d hours)',
                max(0, $hoursUntilSla),
                $approachingWindow
            );
        }

        if ($targetState === 'on_track') {
            if ($hasEscState && (string)$ticket['escalation_state'] !== 'on_track') {
                $stmt = $conn->prepare("UPDATE tbl_ticket SET escalation_state = 'on_track' WHERE ticket_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $ticketId);
                    $stmt->execute();
                    $stmt->close();
                    $results['state_resets']++;
                }
            }
            continue;
        }

        if ($targetState === 'approaching') {
            $results['warnings']++;
        }

        $needsPriorityUpdate = $targetPriority !== $currentPriority;
        $needsStateUpdate = $hasEscState && ((string)$ticket['escalation_state'] !== $targetState);
        if ($needsPriorityUpdate || $needsStateUpdate) {
            $urgency = \slaPriorityToUrgency($targetPriority);
            if ($hasEscState) {
                $update = $conn->prepare("UPDATE tbl_ticket SET priority = ?, urgency = ?, escalation_state = ? WHERE ticket_id = ?");
                if ($update) {
                    $update->bind_param("sssi", $targetPriority, $urgency, $targetState, $ticketId);
                    $update->execute();
                    $update->close();
                }
            } else {
                $update = $conn->prepare("UPDATE tbl_ticket SET priority = ?, urgency = ? WHERE ticket_id = ?");
                if ($update) {
                    $update->bind_param("ssi", $targetPriority, $urgency, $ticketId);
                    $update->execute();
                    $update->close();
                }
            }
        }

        if (autoEscHasRecentSystemEscalation($conn, $ticketId, $dedupeHours)) {
            continue;
        }

        $departmentId = autoEscDepartmentId($conn, (string)$ticket['type']);
        $prevTechnicianId = isset($ticket['assigned_technician_id']) ? (int)$ticket['assigned_technician_id'] : null;
        $newTechnicianId = $prevTechnicianId;
        $prevDepartmentId = null;
        $newDepartmentId = $departmentId;
        $slaStatus = $isOverdue ? 'overdue' : 'escalated';

        $esc = $conn->prepare("
            INSERT INTO tbl_ticket_escalation (
                ticket_id, prev_technician_id, new_technician_id,
                prev_department_id, new_department_id, reason,
                escalator_id, escalation_type, sla_status
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 'system', ?)
        ");
        if (!$esc) {
            $results['errors'][] = 'Escalation insert prepare failed for ticket ' . $ticketId . ': ' . $conn->error;
            continue;
        }
        $esc->bind_param(
            "iiiiiss",
            $ticketId,
            $prevTechnicianId,
            $newTechnicianId,
            $prevDepartmentId,
            $newDepartmentId,
            $escalationReason,
            $slaStatus
        );
        if (!$esc->execute()) {
            $results['errors'][] = 'Escalation insert failed for ticket ' . $ticketId . ': ' . $esc->error;
            $esc->close();
            continue;
        }
        $esc->close();

        $logDetails = "Auto-escalated: {$escalationReason}. Priority: {$targetPriority}";
        if (function_exists('insertTicketLog')) {
            insertTicketLog($ticketId, 0, 'system', 'escalate', $logDetails, $conn);
        } else {
            $logStmt = $conn->prepare("
                INSERT INTO tbl_ticket_logs (ticket_id, user_id, user_role, action_type, action_details)
                VALUES (?, 0, 'system', 'escalate', ?)
            ");
            if ($logStmt) {
                $logStmt->bind_param("is", $ticketId, $logDetails);
                $logStmt->execute();
                $logStmt->close();
            }
        }

        $affectedUserId = (int)($ticket['user_id'] ?? 0);
        if ($affectedUserId > 0) {
            $summaryUserIds[$affectedUserId] = true;
        }
        $results['escalated']++;
    }

    if (!empty($summaryUserIds) && function_exists('refreshUserTicketSummary')) {
        foreach (array_keys($summaryUserIds) as $userId) {
            $ok = refreshUserTicketSummary((int)$userId, $conn);
            if ($ok) {
                $results['summary_refreshed_users']++;
            } else {
                $results['errors'][] = "Summary refresh failed for user {$userId}";
            }
        }
    }

    return $results;
}

function autoEscHasRecentSystemEscalation(mysqli $conn, int $ticketId, int $hours): bool
{
    $stmt = $conn->prepare("
        SELECT escalation_id
        FROM tbl_ticket_escalation
        WHERE ticket_id = ?
          AND escalation_type = 'system'
          AND escalation_timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        LIMIT 1
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ii", $ticketId, $hours);
    $stmt->execute();
    $hasRecent = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $hasRecent;
}

function autoEscDepartmentId(mysqli $conn, string $departmentName): ?int
{
    static $cache = [];
    $departmentName = trim($departmentName);
    if ($departmentName === '') {
        return null;
    }
    if (array_key_exists($departmentName, $cache)) {
        return $cache[$departmentName];
    }

    $stmt = $conn->prepare("SELECT department_id FROM tbl_department WHERE department_name = ? LIMIT 1");
    if (!$stmt) {
        $cache[$departmentName] = null;
        return null;
    }
    $stmt->bind_param("s", $departmentName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $cache[$departmentName] = $row ? (int)$row['department_id'] : null;
    return $cache[$departmentName];
}

function autoEscHasEscalationStateColumn(mysqli $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $res = $conn->query("SHOW COLUMNS FROM tbl_ticket LIKE 'escalation_state'");
    $cached = $res && $res->num_rows > 0;
    if ($res) {
        $res->free();
    }
    return $cached;
}

if (!defined('AUTO_ESCALATE_NO_AUTORUN') && (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === '1'))) {
    $results = autoEscalateTickets($conn);

    if (php_sapi_name() === 'cli') {
        echo "Auto-escalation completed:\n";
        echo "  Checked: " . $results['checked'] . " tickets\n";
        echo "  Escalated: " . $results['escalated'] . " tickets\n";
        echo "  Warnings: " . $results['warnings'] . " tickets\n";
        echo "  State resets: " . $results['state_resets'] . " tickets\n";
        if (!empty($results['errors'])) {
            echo "  Errors: " . implode(" | ", $results['errors']) . "\n";
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode($results);
    }

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
