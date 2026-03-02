<?php
/**
 * SLA Automation Runner
 *
 * Runs scheduled SLA-related automation routines.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (php_sapi_name() === 'cli') {
    // Scheduled runs must be able to process backlog safely.
    set_time_limit(0);
}

require_once 'db.php';
if (!defined('AUTO_ESCALATE_NO_AUTORUN')) {
    define('AUTO_ESCALATE_NO_AUTORUN', true);
}
require_once 'auto_escalate_tickets.php';
require_once 'auto_generate_checklist.php';

function checklistMaintenanceResolveDepartmentId(mysqli $conn, string $type, string $category): ?int
{
    $category = trim($category);
    if ($category !== '') {
        $routeStmt = $conn->prepare("
            SELECT target_department_id
            FROM tbl_department_routing
            WHERE category = ?
            LIMIT 1
        ");
        if ($routeStmt) {
            $routeStmt->bind_param("s", $category);
            $routeStmt->execute();
            $routeRow = $routeStmt->get_result()->fetch_assoc();
            $routeStmt->close();
            $targetDepartmentId = (int)($routeRow['target_department_id'] ?? 0);
            if ($targetDepartmentId > 0) {
                return $targetDepartmentId;
            }
        }
    }

    $type = trim($type);
    if ($type === '') {
        return null;
    }

    $deptMap = [
        'Human Resource' => 'HR',
    ];
    $departmentName = $deptMap[$type] ?? $type;
    $deptStmt = $conn->prepare("SELECT department_id FROM tbl_department WHERE department_name = ? LIMIT 1");
    if (!$deptStmt) {
        return null;
    }
    $deptStmt->bind_param("s", $departmentName);
    $deptStmt->execute();
    $row = $deptStmt->get_result()->fetch_assoc();
    $deptStmt->close();
    return $row ? (int)$row['department_id'] : null;
}

function runChecklistMaintenance(mysqli $conn): array
{
    $result = [
        'ok' => true,
        'status' => 'completed',
        'checked' => 0,
        'eligible' => 0,
        'generated' => 0,
        'generated_items' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    $openCountRes = $conn->query("
        SELECT COUNT(*) AS c
        FROM tbl_ticket t
        WHERE LOWER(COALESCE(t.status, '')) NOT IN ('complete', 'resolved')
    ");
    if ($openCountRes) {
        $row = $openCountRes->fetch_assoc();
        $result['checked'] = (int)($row['c'] ?? 0);
        $openCountRes->free();
    } else {
        $result['errors'][] = 'Open ticket count query failed: ' . $conn->error;
    }

    $eligibleSql = "
        SELECT
            t.ticket_id,
            t.category,
            t.type,
            t.priority,
            t.sla_priority_score,
            COALESCE(u.user_type, 'internal') AS user_type
        FROM tbl_ticket t
        LEFT JOIN tbl_user u ON u.user_id = t.user_id
        LEFT JOIN tbl_ticket_checklist c ON c.ticket_id = t.ticket_id
        WHERE LOWER(COALESCE(t.status, '')) NOT IN ('complete', 'resolved')
        GROUP BY
            t.ticket_id,
            t.category,
            t.type,
            t.priority,
            t.sla_priority_score,
            u.user_type
        HAVING COUNT(c.item_id) = 0
        ORDER BY t.ticket_id ASC
    ";

    $eligibleRes = $conn->query($eligibleSql);
    if (!$eligibleRes) {
        $result['ok'] = false;
        $result['status'] = 'failed';
        $result['errors'][] = 'Checklist maintenance query failed: ' . $conn->error;
        return $result;
    }

    while ($ticket = $eligibleRes->fetch_assoc()) {
        $result['eligible']++;
        $ticketId = (int)($ticket['ticket_id'] ?? 0);
        $category = trim((string)($ticket['category'] ?? ''));
        if ($ticketId <= 0 || $category === '') {
            $result['skipped']++;
            continue;
        }

        $departmentId = checklistMaintenanceResolveDepartmentId(
            $conn,
            (string)($ticket['type'] ?? ''),
            $category
        );

        try {
            $generation = autoGenerateChecklist(
                $conn,
                $ticketId,
                $category,
                $departmentId,
                [
                    'priority' => (string)($ticket['priority'] ?? 'low'),
                    'priority_score' => $ticket['sla_priority_score'],
                    'user_type' => (string)($ticket['user_type'] ?? 'internal'),
                ]
            );

            $itemsCreated = (int)($generation['items_created'] ?? 0);
            if ($itemsCreated > 0) {
                $result['generated']++;
                $result['generated_items'] += $itemsCreated;
            } else {
                $result['skipped']++;
            }
        } catch (Throwable $e) {
            $result['errors'][] = "Ticket {$ticketId}: " . $e->getMessage();
            $result['skipped']++;
        }
    }
    $eligibleRes->free();

    if (!empty($result['errors'])) {
        $result['ok'] = false;
        $result['status'] = 'partial';
    }

    return $result;
}

function runSlaAutomation(mysqli $conn): array
{
    $started = microtime(true);
    $startedAt = date('c');

    $escalation = autoEscalateTickets($conn);
    $checklistMaintenance = runChecklistMaintenance($conn);

    $finishedAt = date('c');
    $durationMs = (int)round((microtime(true) - $started) * 1000);

    return [
        'ok' => empty($escalation['errors']) && empty($checklistMaintenance['errors']),
        'started_at' => $startedAt,
        'finished_at' => $finishedAt,
        'duration_ms' => $durationMs,
        'escalation' => $escalation,
        'checklist_maintenance' => $checklistMaintenance,
    ];
}

if (!defined('RUN_SLA_AUTOMATION_NO_AUTORUN')) {
    if (php_sapi_name() === 'cli') {
        $result = runSlaAutomation($conn);
        echo "SLA automation completed\n";
        echo "  Started: {$result['started_at']}\n";
        echo "  Finished: {$result['finished_at']}\n";
        echo "  Duration: {$result['duration_ms']} ms\n";
        echo "  Escalation checked: " . ($result['escalation']['checked'] ?? 0) . "\n";
        echo "  Escalated: " . ($result['escalation']['escalated'] ?? 0) . "\n";
        echo "  Warnings: " . ($result['escalation']['warnings'] ?? 0) . "\n";
        echo "  State resets: " . ($result['escalation']['state_resets'] ?? 0) . "\n";
        if (!empty($result['escalation']['errors'])) {
            echo "  Escalation errors: " . implode(" | ", $result['escalation']['errors']) . "\n";
        }
        echo "  Checklist checked: " . ($result['checklist_maintenance']['checked'] ?? 0) . "\n";
        echo "  Checklist eligible: " . ($result['checklist_maintenance']['eligible'] ?? 0) . "\n";
        echo "  Checklist generated: " . ($result['checklist_maintenance']['generated'] ?? 0) . "\n";
        echo "  Checklist skipped: " . ($result['checklist_maintenance']['skipped'] ?? 0) . "\n";
        echo "  Checklist generated items: " . ($result['checklist_maintenance']['generated_items'] ?? 0) . "\n";
        if (!empty($result['checklist_maintenance']['errors'])) {
            echo "  Checklist errors: " . implode(" | ", $result['checklist_maintenance']['errors']) . "\n";
        }
    } else {
        header('Content-Type: application/json');
        if (!isset($_SESSION['id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'department_head'], true)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Forbidden']);
            exit;
        }

        try {
            $result = runSlaAutomation($conn);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
