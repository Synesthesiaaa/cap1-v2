<?php
/**
 * Reports API – Customer Complaint, Ticket Volume, Resolution Time, SLA Compliance
 * Access: admin, department_head, technician. Department heads see only their department.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'department_head', 'technician'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$role = $_SESSION['role'];
$userId = (int)$_SESSION['id'];

/** Department head: restrict to their department (ticket type = department name) */
function getReportDepartmentFilter($conn, $role, $userId) {
    if ($role !== 'department_head') {
        return null;
    }
    $sql = "SELECT d.department_name FROM tbl_department_head dh
            INNER JOIN tbl_department d ON dh.department_id = d.department_id
            WHERE dh.user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && !empty($row['department_name'])) {
        return $row['department_name'];
    }
    $sql = "SELECT d.department_name FROM tbl_user u
            LEFT JOIN tbl_department d ON u.department_id = d.department_id
            WHERE u.user_id = ? AND u.user_role = 'department_head' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['department_name'] ?? null;
}

$report = trim($_GET['report'] ?? '');
$export = strtolower(trim($_GET['export'] ?? ''));
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$category = trim($_GET['category'] ?? '');
$priority = trim($_GET['priority'] ?? '');
$department = trim($_GET['department'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = max(1, min(100, (int)($_GET['pageSize'] ?? 20)));

$deptFilter = getReportDepartmentFilter($conn, $role, $userId);

// Default date range: last 30 days
if ($dateFrom === '') {
    $dateFrom = date('Y-m-d', strtotime('-30 days'));
}
if ($dateTo === '') {
    $dateTo = date('Y-m-d');
}
$dateToEnd = $dateTo . ' 23:59:59';

$allowedReports = ['customer_complaint', 'ticket_volume', 'resolution_time', 'sla_compliance', 'filter_options'];
if (!in_array($report, $allowedReports)) {
    echo json_encode(['error' => 'Invalid report']);
    exit;
}

// ---------- Filter options for dropdowns ----------
if ($report === 'filter_options') {
    $categories = [];
    $r = $conn->query("SELECT DISTINCT category FROM tbl_ticket WHERE category IS NOT NULL AND category != '' ORDER BY category");
    if ($r) while ($row = $r->fetch_assoc()) $categories[] = $row['category'];
    $priorities = [];
    $r = $conn->query("SELECT DISTINCT priority FROM tbl_ticket WHERE priority IS NOT NULL AND priority != '' ORDER BY priority");
    if ($r) while ($row = $r->fetch_assoc()) $priorities[] = $row['priority'];
    $departments = [];
    $r = $conn->query("SELECT DISTINCT department_name FROM tbl_department ORDER BY department_name");
    if ($r) while ($row = $r->fetch_assoc()) $departments[] = $row['department_name'];
    $statuses = ['unassigned', 'pending', 'followup', 'complete'];
    echo json_encode(['categories' => $categories, 'priorities' => $priorities, 'departments' => $departments, 'statuses' => $statuses]);
    exit;
}

$params = [];
$types = '';
$whereClause = " t.created_at BETWEEN ? AND ? ";
$params[] = $dateFrom;
$params[] = $dateToEnd;
$types .= 'ss';

if ($deptFilter !== null) {
    $whereClause .= " AND t.type = ? ";
    $params[] = $deptFilter;
    $types .= 's';
}
if ($category !== '') {
    $whereClause .= " AND t.category = ? ";
    $params[] = $category;
    $types .= 's';
}
if ($priority !== '') {
    $whereClause .= " AND t.priority = ? ";
    $params[] = $priority;
    $types .= 's';
}
if ($department !== '' && $deptFilter === null) {
    $whereClause .= " AND t.type = ? ";
    $params[] = $department;
    $types .= 's';
}
if ($status !== '') {
    $whereClause .= " AND t.status = ? ";
    $params[] = $status;
    $types .= 's';
}

// ---------- Report 1: Customer Complaint ----------
if ($report === 'customer_complaint') {
    $whereComplaint = $whereClause;
    $paramsComplaint = $params;
    $typesComplaint = $types;

    $sql = "SELECT t.ticket_id, t.reference_id, u.name AS customer_name, u.user_id,
            t.title, t.description, t.category, t.priority, t.status,
            t.created_at AS date_reported
            FROM tbl_ticket t
            JOIN tbl_user u ON u.user_id = t.user_id
            WHERE $whereComplaint
            ORDER BY t.created_at DESC";

    if ($export === 'csv') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typesComplaint, ...$paramsComplaint);
        $stmt->execute();
        $result = $stmt->get_result();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="customer_complaint_report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Ticket ID', 'Reference', 'Customer Name', 'Customer ID', 'Title', 'Description', 'Category', 'Priority', 'Status', 'Date Reported']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($out, [
                $row['ticket_id'],
                $row['reference_id'],
                $row['customer_name'],
                $row['user_id'],
                $row['title'],
                $row['description'],
                $row['category'],
                $row['priority'],
                $row['status'],
                $row['date_reported']
            ]);
        }
        fclose($out);
        $stmt->close();
        exit;
    }

    $countSql = "SELECT COUNT(*) AS total FROM tbl_ticket t JOIN tbl_user u ON u.user_id = t.user_id WHERE $whereComplaint";
    $stmt = $conn->prepare($countSql);
    $stmt->bind_param($typesComplaint, ...$paramsComplaint);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $offset = ($page - 1) * $pageSize;
    $sql .= " LIMIT ? OFFSET ?";
    $paramsComplaint[] = $pageSize;
    $paramsComplaint[] = $offset;
    $typesComplaint .= 'ii';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($typesComplaint, ...$paramsComplaint);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'data' => $data,
        'meta' => ['total' => $total, 'page' => $page, 'pageSize' => $pageSize]
    ]);
    exit;
}

// ---------- Report 2: Ticket Volume ----------
if ($report === 'ticket_volume') {
    $whereVol = $whereClause;
    $paramsVol = $params;
    $typesVol = $types;

    if ($export === 'csv') {
        $sqlTotal = "SELECT COUNT(*) AS total FROM tbl_ticket t WHERE $whereVol";
        $stmt = $conn->prepare($sqlTotal);
        $stmt->bind_param($typesVol, ...$paramsVol);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $sqlByDay = "SELECT DATE(t.created_at) AS date, COUNT(*) AS count FROM tbl_ticket t WHERE $whereVol GROUP BY DATE(t.created_at) ORDER BY date";
        $stmt = $conn->prepare($sqlByDay);
        $stmt->bind_param($typesVol, ...$paramsVol);
        $stmt->execute();
        $byDay = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ticket_volume_report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Report', 'Period/Group', 'Count']);
        fputcsv($out, ['Total', $dateFrom . ' to ' . $dateTo, $total]);
        foreach ($byDay as $r) {
            fputcsv($out, ['By Day', $r['date'], $r['count']]);
        }
        fclose($out);
        exit;
    }

    $sqlTotal = "SELECT COUNT(*) AS total FROM tbl_ticket t WHERE $whereVol";
    $stmt = $conn->prepare($sqlTotal);
    $stmt->bind_param($typesVol, ...$paramsVol);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $sqlByDay = "SELECT DATE(t.created_at) AS date, COUNT(*) AS count FROM tbl_ticket t WHERE $whereVol GROUP BY DATE(t.created_at) ORDER BY date";
    $stmt = $conn->prepare($sqlByDay);
    $stmt->bind_param($typesVol, ...$paramsVol);
    $stmt->execute();
    $byDay = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $sqlByCategory = "SELECT t.category, COUNT(*) AS count FROM tbl_ticket t WHERE $whereVol GROUP BY t.category ORDER BY count DESC";
    $stmt = $conn->prepare($sqlByCategory);
    $stmt->bind_param($typesVol, ...$paramsVol);
    $stmt->execute();
    $byCategory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $sqlByPriority = "SELECT t.priority, COUNT(*) AS count FROM tbl_ticket t WHERE $whereVol GROUP BY t.priority ORDER BY count DESC";
    $stmt = $conn->prepare($sqlByPriority);
    $stmt->bind_param($typesVol, ...$paramsVol);
    $stmt->execute();
    $byPriority = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'total_tickets' => $total,
        'by_day' => $byDay,
        'by_category' => $byCategory,
        'by_priority' => $byPriority
    ]);
    exit;
}

// ---------- Report 3: Resolution Time ----------
if ($report === 'resolution_time') {
    $whereRes = $whereClause . " AND t.resolved_at IS NOT NULL ";
    $paramsRes = $params;
    $typesRes = $types;

    if ($export === 'csv') {
        $sql = "SELECT t.ticket_id, t.reference_id, t.category, t.type AS department,
                TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) AS resolution_hours,
                t.created_at, t.resolved_at
                FROM tbl_ticket t
                WHERE $whereRes
                ORDER BY resolution_hours DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typesRes, ...$paramsRes);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="resolution_time_report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Ticket ID', 'Reference', 'Category', 'Department', 'Resolution Hours', 'Created', 'Resolved']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['ticket_id'], $r['reference_id'], $r['category'], $r['department'], $r['resolution_hours'], $r['created_at'], $r['resolved_at']]);
        }
        fclose($out);
        exit;
    }

    $sqlAgg = "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)) AS avg_hours,
               MIN(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)) AS min_hours,
               MAX(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)) AS max_hours
               FROM tbl_ticket t WHERE $whereRes";
    $stmt = $conn->prepare($sqlAgg);
    $stmt->bind_param($typesRes, ...$paramsRes);
    $stmt->execute();
    $agg = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $sqlByCat = "SELECT t.category, AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)) AS avg_hours
                 FROM tbl_ticket t WHERE $whereRes GROUP BY t.category ORDER BY avg_hours DESC";
    $stmt = $conn->prepare($sqlByCat);
    $stmt->bind_param($typesRes, ...$paramsRes);
    $stmt->execute();
    $byCategory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $sqlByDept = "SELECT t.type AS department, AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at)) AS avg_hours
                  FROM tbl_ticket t WHERE $whereRes GROUP BY t.type ORDER BY avg_hours DESC";
    $stmt = $conn->prepare($sqlByDept);
    $stmt->bind_param($typesRes, ...$paramsRes);
    $stmt->execute();
    $byDepartment = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'average_hours' => $agg ? round((float)$agg['avg_hours'], 2) : null,
        'min_hours' => $agg ? (int)$agg['min_hours'] : null,
        'max_hours' => $agg ? (int)$agg['max_hours'] : null,
        'by_category' => $byCategory,
        'by_department' => $byDepartment
    ]);
    exit;
}

// ---------- Report 4: SLA Compliance ----------
if ($report === 'sla_compliance') {
    $whereSla = $whereClause . " AND t.resolved_at IS NOT NULL AND t.sla_date IS NOT NULL ";
    $paramsSla = $params;
    $typesSla = $types;

    if ($export === 'csv') {
        $sql = "SELECT t.ticket_id, t.reference_id, t.sla_date, t.resolved_at,
                (CASE WHEN DATE(t.resolved_at) <= t.sla_date THEN 'Yes' ELSE 'No' END) AS sla_met,
                TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) AS actual_hours
                FROM tbl_ticket t
                WHERE $whereSla
                ORDER BY t.resolved_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typesSla, ...$paramsSla);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sla_compliance_report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Ticket ID', 'Reference', 'SLA Target Date', 'Actual Resolution', 'SLA Met', 'Actual Hours']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['ticket_id'], $r['reference_id'], $r['sla_date'], $r['resolved_at'], $r['sla_met'], $r['actual_hours']]);
        }
        fclose($out);
        exit;
    }

    $sqlList = "SELECT t.ticket_id, t.reference_id, t.sla_date, t.resolved_at,
                (CASE WHEN DATE(t.resolved_at) <= t.sla_date THEN 'Yes' ELSE 'No' END) AS sla_met,
                TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) AS actual_hours
                FROM tbl_ticket t
                WHERE $whereSla
                ORDER BY t.resolved_at DESC
                LIMIT 500";
    $stmt = $conn->prepare($sqlList);
    $stmt->bind_param($typesSla, ...$paramsSla);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $sqlAgg = "SELECT COUNT(*) AS total,
               SUM(CASE WHEN DATE(t.resolved_at) <= t.sla_date THEN 1 ELSE 0 END) AS met,
               SUM(CASE WHEN DATE(t.resolved_at) > t.sla_date THEN 1 ELSE 0 END) AS breached
               FROM tbl_ticket t WHERE $whereSla";
    $stmt = $conn->prepare($sqlAgg);
    $stmt->bind_param($typesSla, ...$paramsSla);
    $stmt->execute();
    $agg = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $breachReasons = [];
    if ($agg && (int)$agg['breached'] > 0) {
        $sqlBreachedIds = "SELECT t.ticket_id FROM tbl_ticket t WHERE $whereSla AND DATE(t.resolved_at) > t.sla_date";
        $stmt = $conn->prepare($sqlBreachedIds);
        $stmt->bind_param($typesSla, ...$paramsSla);
        $stmt->execute();
        $breachedIds = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'ticket_id');
        $stmt->close();
        if (!empty($breachedIds)) {
            $placeholders = implode(',', array_fill(0, count($breachedIds), '?'));
            $typesB = str_repeat('i', count($breachedIds));
            $sqlReasons = "SELECT e.reason, COUNT(*) AS cnt FROM tbl_ticket_escalation e
                          WHERE e.ticket_id IN ($placeholders) AND e.reason IS NOT NULL AND e.reason != ''
                          GROUP BY e.reason ORDER BY cnt DESC";
            $stmt = $conn->prepare($sqlReasons);
            $stmt->bind_param($typesB, ...$breachedIds);
            $stmt->execute();
            $breachReasons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }

    echo json_encode([
        'data' => $rows,
        'total' => (int)($agg['total'] ?? 0),
        'sla_met_count' => (int)($agg['met'] ?? 0),
        'sla_breached_count' => (int)($agg['breached'] ?? 0),
        'breach_reasons' => $breachReasons
    ]);
    exit;
}
