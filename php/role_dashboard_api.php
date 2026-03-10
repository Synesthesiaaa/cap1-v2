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

$allowedRoles = ['technician', 'department_head', 'customer', 'admin'];
if (!in_array($role, $allowedRoles)) {
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
        case 'get_customer_notifications':
            getCustomerNotifications($userId);
            break;
        case 'get_admin_overview':
            getAdminOverview($userId);
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
                         AND status != 'complete'
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
                             AND status != 'complete'
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
                             AND status != 'complete'
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
                             AND status != 'complete'
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
                      AND status != 'complete'
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
                             AND status != 'complete'
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
                         AND status != 'complete'
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
                             AND status != 'complete'
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
                             AND status != 'complete'
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
                             AND status != 'complete'
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
                      AND status != 'complete'
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
                             AND status != 'complete'
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

        // All open (non-complete) tickets assigned to this technician
        $openSql = "SELECT COUNT(*) as count 
                    FROM tbl_ticket 
                    WHERE assigned_technician_id = ? 
                      AND status != 'complete'";
        $stmt = $conn->prepare($openSql);
        $stmt->bind_param("i", $techId);
        $stmt->execute();
        $result = $stmt->get_result();
        $openTickets = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        // Tickets assigned to this technician that were created today (recently assigned)
        $newTodaySql = "SELECT COUNT(*) as count 
                        FROM tbl_ticket 
                        WHERE assigned_technician_id = ? 
                          AND DATE(created_at) = CURDATE()";
        $stmt = $conn->prepare($newTodaySql);
        $stmt->bind_param("i", $techId);
        $stmt->execute();
        $result = $stmt->get_result();
        $newToday = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        // Escalated tickets (overdue or approaching SLA) for this technician
        $escalatedSql = "SELECT COUNT(*) as count 
                         FROM tbl_ticket 
                         WHERE sla_date IS NOT NULL
                           AND (sla_date < NOW() OR sla_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR))
                           AND status != 'complete'
                           AND assigned_technician_id = ?";
        $stmt = $conn->prepare($escalatedSql);
        $stmt->bind_param("i", $techId);
        $stmt->execute();
        $result = $stmt->get_result();
        $escalatedTickets = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        echo json_encode([
            'open_tickets'       => $openTickets,
            'new_today'          => $newToday,
            'escalated_tickets'  => $escalatedTickets
        ]);
        return;
    }

    if ($role === 'department_head') {
        $departmentName = getDepartmentHeadDepartmentName($userId);
        if (!$departmentName) {
            echo json_encode([
                'open_tickets'      => 0,
                'new_today'         => 0,
                'escalated_tickets' => 0
            ]);
            return;
        }

        // Active (non-complete) tickets in this department
        $activeSql = "SELECT COUNT(*) as count 
                      FROM tbl_ticket 
                      WHERE type = ?
                        AND status IN ('unassigned', 'pending', 'followup')";
        $stmt = $conn->prepare($activeSql);
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $activeTickets = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        // Tickets created today in this department
        $newTodaySql = "SELECT COUNT(*) as count 
                        FROM tbl_ticket 
                        WHERE type = ?
                          AND DATE(created_at) = CURDATE()";
        $stmt = $conn->prepare($newTodaySql);
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $newToday = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        // Escalated tickets (overdue or approaching SLA) in this department
        $escalatedSql = "SELECT COUNT(*) as count 
                         FROM tbl_ticket 
                         WHERE sla_date IS NOT NULL
                           AND (sla_date < NOW() OR sla_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR))
                           AND status != 'complete'
                           AND type = ?";
        $stmt = $conn->prepare($escalatedSql);
        $stmt->bind_param("s", $departmentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $escalatedTickets = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        $stmt->close();

        echo json_encode([
            'open_tickets'      => $activeTickets,
            'new_today'         => $newToday,
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

/**
 * Customer dashboard: return recent in-app notifications and ticket summary.
 */
function getCustomerNotifications(int $userId): void
{
    global $conn;

    // Recent in-app notifications from tbl_notification
    $notifSql = "SELECT notification_id, type, title, message, is_read, link, created_at
                 FROM tbl_notification
                 WHERE recipient_id = ? AND recipient_type = 'user'
                 ORDER BY created_at DESC
                 LIMIT 10";
    $stmt = $conn->prepare($notifSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Count unread
    $unreadSql = "SELECT COUNT(*) AS cnt
                  FROM tbl_notification
                  WHERE recipient_id = ? AND recipient_type = 'user' AND is_read = 0";
    $stmt = $conn->prepare($unreadSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $unreadCount = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // Ticket summary for this user
    $summarySql = "SELECT
                       COUNT(*) AS total,
                       SUM(status != 'complete') AS open_count,
                       SUM(status = 'complete')  AS closed_count
                   FROM tbl_ticket
                   WHERE user_id = ?";
    $stmt = $conn->prepare($summarySql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'notifications' => $notifications,
        'unread_count'  => $unreadCount,
        'ticket_summary' => [
            'total'  => (int)($summary['total']  ?? 0),
            'open'   => (int)($summary['open_count']   ?? 0),
            'closed' => (int)($summary['closed_count'] ?? 0),
        ]
    ]);
}

/**
 * Admin dashboard: return system-wide ticket overview and urgent alerts.
 */
function getAdminOverview(int $userId): void
{
    global $conn;

    // Overall ticket stats
    $statsSql = "SELECT
                     COUNT(*) AS total,
                     SUM(status = 'unassigned') AS unassigned,
                     SUM(status = 'pending')    AS pending,
                     SUM(status = 'followup')   AS followup,
                     SUM(status = 'complete')   AS complete
                 FROM tbl_ticket";
    $result = $conn->query($statsSql);
    $stats  = $result ? $result->fetch_assoc() : [];

    // Overdue tickets (past SLA, not complete)
    $overdueSql = "SELECT COUNT(*) AS cnt
                   FROM tbl_ticket
                   WHERE sla_date IS NOT NULL
                     AND sla_date < NOW()
                     AND status != 'complete'";
    $result   = $conn->query($overdueSql);
    $overdue  = $result ? (int)$result->fetch_assoc()['cnt'] : 0;

    // Unassigned tickets (need attention)
    $unassignedSql = "SELECT COUNT(*) AS cnt
                      FROM tbl_ticket
                      WHERE status = 'unassigned'";
    $result     = $conn->query($unassignedSql);
    $unassigned = $result ? (int)$result->fetch_assoc()['cnt'] : 0;

    // New tickets today
    $newTodaySql = "SELECT COUNT(*) AS cnt
                    FROM tbl_ticket
                    WHERE DATE(created_at) = CURDATE()";
    $result   = $conn->query($newTodaySql);
    $newToday = $result ? (int)$result->fetch_assoc()['cnt'] : 0;

    // Build alerts for admin
    $alerts = [];
    if ($overdue > 0) {
        $alerts[] = [
            'type'       => 'critical',
            'title'      => 'Overdue Tickets',
            'message'    => "{$overdue} ticket(s) have passed their SLA deadline system-wide.",
            'count'      => $overdue,
            'action_url' => 'user_ticket_monitor.php',
            'details'    => []
        ];
    }
    if ($unassigned > 0) {
        $alerts[] = [
            'type'       => 'warning',
            'title'      => 'Unassigned Tickets',
            'message'    => "{$unassigned} ticket(s) are still unassigned and need a technician.",
            'count'      => $unassigned,
            'action_url' => 'user_ticket_monitor.php',
            'details'    => []
        ];
    }

    echo json_encode([
        'stats' => [
            'total'      => (int)($stats['total']      ?? 0),
            'unassigned' => (int)($stats['unassigned']  ?? 0),
            'pending'    => (int)($stats['pending']     ?? 0),
            'followup'   => (int)($stats['followup']    ?? 0),
            'complete'   => (int)($stats['complete']    ?? 0),
            'overdue'    => $overdue,
            'new_today'  => $newToday,
        ],
        'alerts' => $alerts
    ]);
}
