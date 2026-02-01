<?php
// Generic dashboard API for technician and department_head roles
include("db.php");
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$role = $_SESSION['role'] ?? '';
$userId = (int)$_SESSION['id'];

// Only technician and department_head use this API for now
if (!in_array($role, ['technician', 'department_head'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_alerts':
            getAlerts($role, $userId);
            break;
        case 'get_dashboard_stats':
            getDashboardStats($role, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Role Dashboard API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("Role Dashboard API Fatal Error: " . $e->getMessage());
    echo json_encode(['error' => 'Server error occurred']);
}

function getAlerts(string $role, int $userId) {
    global $conn;

    $alerts = [];

    if ($role === 'technician') {
        // Technician: alerts based on tickets assigned to this technician
        $techId = $userId;

        // Overdue tickets (past SLA) assigned to this technician
        $overdueSql = "SELECT COUNT(*) as count 
                       FROM tbl_ticket 
                       WHERE sla_date IS NOT NULL
                         AND sla_date < NOW() 
                         AND status NOT IN ('complete', 'closed')
                         AND assigned_technician_id = ?";
        $stmt = $conn->prepare($overdueSql);
        $stmt->bind_param("i", $techId);
        $stmt->execute();
        $result = $stmt->get_result();
        $overdueCount = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        if ($overdueCount > 0) {
            $detailsSql = "SELECT reference_id, title, sla_date 
                           FROM tbl_ticket 
                           WHERE sla_date IS NOT NULL
                             AND sla_date < NOW() 
                             AND status NOT IN ('complete', 'closed')
                             AND assigned_technician_id = ?
                           ORDER BY sla_date ASC 
                           LIMIT 5";
            $stmt = $conn->prepare($detailsSql);
            $stmt->bind_param("i", $techId);
            $stmt->execute();
            $detailsResult = $stmt->get_result();
            $details = [];
            while ($row = $detailsResult->fetch_assoc()) {
                $details[] = "Ticket #{$row['reference_id']}: {$row['title']}";
            }
            $stmt->close();

            $alerts[] = [
                'type' => 'critical',
                'title' => 'Overdue Tickets',
                'message' => "{$overdueCount} of your ticket(s) have passed their SLA deadline",
                'count' => $overdueCount,
                'details' => $details,
                'action_url' => 'tech_ticket_monitor.php?filter=overdue'
            ];
        }

        // Tickets approaching SLA (within 24 hours) assigned to this technician
        $approachingSql = "SELECT COUNT(*) as count 
                           FROM tbl_ticket 
                           WHERE sla_date IS NOT NULL
                             AND sla_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
                             AND status NOT IN ('complete', 'closed')
                             AND assigned_technician_id = ?";
        $stmt = $conn->prepare($approachingSql);
        $stmt->bind_param("i", $techId);
        $stmt->execute();
        $result = $stmt->get_result();
        $approachingCount = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        if ($approachingCount > 0) {
            $detailsSql = "SELECT reference_id, title, sla_date 
                           FROM tbl_ticket 
                           WHERE sla_date IS NOT NULL
                             AND sla_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
                             AND status NOT IN ('complete', 'closed')
                             AND assigned_technician_id = ?
                           ORDER BY sla_date ASC 
                           LIMIT 5";
            $stmt = $conn->prepare($detailsSql);
            $stmt->bind_param("i", $techId);
            $stmt->execute();
            $detailsResult = $stmt->get_result();
            $details = [];
            while ($row = $detailsResult->fetch_assoc()) {
                $hoursLeft = round((strtotime($row['sla_date']) - time()) / 3600, 1);
                $details[] = "Ticket #{$row['reference_id']}: {$row['title']} ({$hoursLeft}h remaining)";
            }
            $stmt->close();

            $alerts[] = [
                'type' => 'warning',
                'title' => 'Tickets Approaching SLA',
                'message' => "{$approachingCount} of your ticket(s) are approaching their SLA deadline within 24 hours",
                'count' => $approachingCount,
                'details' => $details,
                'action_url' => 'tech_ticket_monitor.php?filter=approaching'
            ];
        }

        // High priority open tickets assigned to this technician
        $highSql = "SELECT COUNT(*) as count 
                    FROM tbl_ticket 
                    WHERE priority IN ('high', 'critical') 
                      AND status NOT IN ('complete', 'closed')
                      AND assigned_technician_id = ?";
        $stmt = $conn->prepare($highSql);
        $stmt->bind_param("i", $techId);
        $stmt->execute();
        $result = $stmt->get_result();
        $highCount = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        if ($highCount > 0) {
            $detailsSql = "SELECT reference_id, title, priority 
                           FROM tbl_ticket 
                           WHERE priority IN ('high', 'critical') 
                             AND status NOT IN ('complete', 'closed')
                             AND assigned_technician_id = ?
                           ORDER BY 
                               CASE priority 
                                   WHEN 'critical' THEN 1 
                                   WHEN 'high' THEN 2 
                               END,
                               created_at ASC 
                           LIMIT 5";
            $stmt = $conn->prepare($detailsSql);
            $stmt->bind_param("i", $techId);
            $stmt->execute();
            $detailsResult = $stmt->get_result();
            $details = [];
            while ($row = $detailsResult->fetch_assoc()) {
                $details[] = "Ticket #{$row['reference_id']}: {$row['title']} ({$row['priority']})";
            }
            $stmt->close();

            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Priority Tickets',
                'message' => "{$highCount} high/critical ticket(s) require your attention",
                'count' => $highCount,
                'details' => $details,
                'action_url' => 'tech_ticket_monitor.php?priority=high'
            ];
        }
    } elseif ($role === 'department_head') {
        // Department head: alerts based on ticket type (department)
        $departmentName = getDepartmentHeadDepartmentName($userId);
        if (!$departmentName) {
            echo json_encode([]);
            return;
        }

        // Overdue tickets for this department (by type)
        $overdueSql = "SELECT COUNT(*) as count 
                       FROM tbl_ticket 
                       WHERE sla_date IS NOT NULL
                         AND sla_date < NOW() 
                         AND status NOT IN ('complete', 'closed')
                         AND type = ?";
        $stmt = $conn->prepare($overdueSql);
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $overdueCount = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        if ($overdueCount > 0) {
            $detailsSql = "SELECT reference_id, title, sla_date 
                           FROM tbl_ticket 
                           WHERE sla_date IS NOT NULL
                             AND sla_date < NOW() 
                             AND status NOT IN ('complete', 'closed')
                             AND type = ?
                           ORDER BY sla_date ASC 
                           LIMIT 5";
            $stmt = $conn->prepare($detailsSql);
            $stmt->bind_param("s", $departmentName);
            $stmt->execute();
            $detailsResult = $stmt->get_result();
            $details = [];
            while ($row = $detailsResult->fetch_assoc()) {
                $details[] = "Ticket #{$row['reference_id']}: {$row['title']}";
            }
            $stmt->close();

            $alerts[] = [
                'type' => 'critical',
                'title' => 'Overdue Department Tickets',
                'message' => "{$overdueCount} ticket(s) in {$departmentName} have passed their SLA deadline",
                'count' => $overdueCount,
                'details' => $details,
                'action_url' => 'department_head_monitor.php?filter=overdue'
            ];
        }

        // Tickets approaching SLA for this department
        $approachingSql = "SELECT COUNT(*) as count 
                           FROM tbl_ticket 
                           WHERE sla_date IS NOT NULL
                             AND sla_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
                             AND status NOT IN ('complete', 'closed')
                             AND type = ?";
        $stmt = $conn->prepare($approachingSql);
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $approachingCount = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        if ($approachingCount > 0) {
            $detailsSql = "SELECT reference_id, title, sla_date 
                           FROM tbl_ticket 
                           WHERE sla_date IS NOT NULL
                             AND sla_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
                             AND status NOT IN ('complete', 'closed')
                             AND type = ?
                           ORDER BY sla_date ASC 
                           LIMIT 5";
            $stmt = $conn->prepare($detailsSql);
            $stmt->bind_param("s", $departmentName);
            $stmt->execute();
            $detailsResult = $stmt->get_result();
            $details = [];
            while ($row = $detailsResult->fetch_assoc()) {
                $hoursLeft = round((strtotime($row['sla_date']) - time()) / 3600, 1);
                $details[] = "Ticket #{$row['reference_id']}: {$row['title']} ({$hoursLeft}h remaining)";
            }
            $stmt->close();

            $alerts[] = [
                'type' => 'warning',
                'title' => 'Tickets Approaching SLA',
                'message' => "{$approachingCount} ticket(s) in {$departmentName} are approaching their SLA deadline",
                'count' => $approachingCount,
                'details' => $details,
                'action_url' => 'department_head_monitor.php?filter=approaching'
            ];
        }

        // High priority open tickets for this department
        $highSql = "SELECT COUNT(*) as count 
                    FROM tbl_ticket 
                    WHERE priority IN ('high', 'critical') 
                      AND status NOT IN ('complete', 'closed')
                      AND type = ?";
        $stmt = $conn->prepare($highSql);
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $highCount = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        if ($highCount > 0) {
            $detailsSql = "SELECT reference_id, title, priority 
                           FROM tbl_ticket 
                           WHERE priority IN ('high', 'critical') 
                             AND status NOT IN ('complete', 'closed')
                             AND type = ?
                           ORDER BY 
                               CASE priority 
                                   WHEN 'critical' THEN 1 
                                   WHEN 'high' THEN 2 
                               END,
                               created_at ASC 
                           LIMIT 5";
            $stmt = $conn->prepare($detailsSql);
            $stmt->bind_param("s", $departmentName);
            $stmt->execute();
            $detailsResult = $stmt->get_result();
            $details = [];
            while ($row = $detailsResult->fetch_assoc()) {
                $details[] = "Ticket #{$row['reference_id']}: {$row['title']} ({$row['priority']})";
            }
            $stmt->close();

            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Priority Tickets',
                'message' => "{$highCount} high/critical ticket(s) in {$departmentName} require attention",
                'count' => $highCount,
                'details' => $details,
                'action_url' => 'department_head_monitor.php?priority=high'
            ];
        }
    }

    echo json_encode($alerts);
}

function getDashboardStats(string $role, int $userId) {
    global $conn;

    if ($role === 'technician') {
        $techId = $userId;

        // Open tickets assigned to this technician
        $awaitingSql = "SELECT COUNT(*) as count 
                        FROM tbl_ticket 
                        WHERE assigned_technician_id = ? 
                          AND status NOT IN ('complete', 'closed')";
        $stmt = $conn->prepare($awaitingSql);
        $stmt->bind_param("i", $techId);
        $stmt->execute();
        $result = $stmt->get_result();
        $openTickets = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        // Tickets completed today by this technician
        // Note: since tbl_ticket has no updated_at column, we approximate "completed today"
        // as tickets created today that are now in a completed/closed state.
        $completedTodaySql = "SELECT COUNT(*) as count 
                              FROM tbl_ticket 
                              WHERE assigned_technician_id = ? 
                                AND status IN ('complete', 'closed')
                                AND DATE(created_at) = CURDATE()";
        $stmt = $conn->prepare($completedTodaySql);
        $stmt->bind_param("i", $techId);
        $stmt->execute();
        $result = $stmt->get_result();
        $completedToday = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        // Escalated tickets (overdue or approaching SLA) for this technician
        $escalatedSql = "SELECT COUNT(*) as count 
                         FROM tbl_ticket 
                         WHERE sla_date IS NOT NULL
                           AND (sla_date < NOW() OR sla_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR))
                           AND status NOT IN ('complete', 'closed')
                           AND assigned_technician_id = ?";
        $stmt = $conn->prepare($escalatedSql);
        $stmt->bind_param("i", $techId);
        $stmt->execute();
        $result = $stmt->get_result();
        $escalatedTickets = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        echo json_encode([
            'awaiting_evaluation' => $openTickets,
            'assigned_today' => $completedToday,
            'escalated_tickets' => $escalatedTickets
        ]);
        return;
    }

    if ($role === 'department_head') {
        $departmentName = getDepartmentHeadDepartmentName($userId);
        if (!$departmentName) {
            echo json_encode([
                'awaiting_evaluation' => 0,
                'assigned_today' => 0,
                'escalated_tickets' => 0
            ]);
            return;
        }

        // Tickets awaiting action in this department
        $awaitingSql = "SELECT COUNT(*) as count 
                        FROM tbl_ticket 
                        WHERE type = ?
                          AND status IN ('unassigned', 'pending', 'followup')";
        $stmt = $conn->prepare($awaitingSql);
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $awaiting = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        // Tickets in this department created today (activity indicator)
        $assignedTodaySql = "SELECT COUNT(*) as count 
                             FROM tbl_ticket 
                             WHERE type = ?
                               AND DATE(created_at) = CURDATE()";
        $stmt = $conn->prepare($assignedTodaySql);
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $today = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        // Escalated tickets (overdue or approaching SLA) in this department
        $escalatedSql = "SELECT COUNT(*) as count 
                         FROM tbl_ticket 
                         WHERE sla_date IS NOT NULL
                           AND (sla_date < NOW() OR sla_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR))
                           AND status NOT IN ('complete', 'closed')
                           AND type = ?";
        $stmt = $conn->prepare($escalatedSql);
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $escalatedTickets = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        echo json_encode([
            'awaiting_evaluation' => $awaiting,
            'assigned_today' => $today,
            'escalated_tickets' => $escalatedTickets
        ]);
        return;
    }
}

function getDepartmentHeadDepartmentName(int $userId): ?string {
    global $conn;

    // Prefer tbl_department_head mapping
    $sql = "SELECT d.department_name
            FROM tbl_department_head dh
            INNER JOIN tbl_department d ON dh.department_id = d.department_id
            WHERE dh.user_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row && !empty($row['department_name'])) {
        return $row['department_name'];
    }

    // Fallback to tbl_user
    $fallbackSql = "SELECT d.department_name
                    FROM tbl_user u
                    LEFT JOIN tbl_department d ON u.department_id = d.department_id
                    WHERE u.user_id = ? AND u.user_role = 'department_head'
                    LIMIT 1";
    $stmt = $conn->prepare($fallbackSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['department_name'] ?? null;
}


