<?php
header('Content-Type: application/json');
session_start();
require_once 'db.php';
require_once 'check_cm_access.php';

// For technicians: id is technician_id; for admins use technician_id if set
$tech_id = $_SESSION['technician_id'] ?? $_SESSION['id'] ?? 0;
// Allow monitor roles and customer-management readonly/full access users.
$role = $_SESSION['role'] ?? '';
$isMonitorRole = in_array($role, ['technician', 'admin', 'department_head'], true);
$hasCmAccess = requireCMAccess('readonly');
if (!isset($_SESSION['id']) || (!$isMonitorRole && !$hasCmAccess)) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

/* -------------------
   INPUT FILTERS
------------------- */
$search     = $_GET['search']   ?? '';
$priority   = $_GET['priority'] ?? '';
$status     = $_GET['status']   ?? '';
$userId     = $_GET['user_id']   ?? null; // For customer management page
$page       = max(1, intval($_GET['page'] ?? 1));
$pageSize   = max(5, intval($_GET['pageSize'] ?? 10));
$includeCompleted = (isset($_GET['include_completed']) && (int)$_GET['include_completed'] === 1) ? 1 : 0;

/* Summary card overrides */
$assigned_only    = isset($_GET['assigned_only'])    ? 1 : 0;
$due_within_hours = isset($_GET['due_within_hours']) ? intval($_GET['due_within_hours']) : null;
$due_today        = isset($_GET['due_today'])        ? 1 : 0;
$overdue          = isset($_GET['overdue'])          ? 1 : 0;
$backlog          = isset($_GET['backlog'])          ? 1 : 0;
$escalated_filter = isset($_GET['escalated'])        ? 1 : 0;

/* Sorting (default newest) */
$sort = $_GET['sort'] ?? 'date_desc';

switch ($sort) {
    case 'date_asc':
        $sortSQL = "t.created_at ASC";
        break;

    case 'date_desc':
    default:
        $sortSQL = "t.created_at DESC";
        break;
}


/* -------------------
   BASE QUERY
------------------- */
$where = [];
$params = [];
$types = "";
$normalizedStatusExpr = "COALESCE(NULLIF(t.status, ''), 'unassigned')";

/* Search */
if ($search !== "") {
    $where[] = "(t.title LIKE ? OR t.reference_id LIKE ?)";
    $params[] = "%$search%"; $types .= "s";
    $params[] = "%$search%"; $types .= "s";
}

/* Priority */
if ($priority !== "") {
    $where[] = "t.priority = ?";
    $params[] = $priority; $types .= "s";
}

/* Status */
if ($status !== "") {
    $where[] = "{$normalizedStatusExpr} = ?";
    $params[] = $status; $types .= "s";
}

/* User ID filter (for customer management page) */
if ($userId !== null) {
    $where[] = "t.user_id = ?";
    $params[] = $userId; $types .= "i";
}

/* Type */
if (!empty($_GET['type'])) {
    $where[] = "t.type = ?";
    $params[] = $_GET['type'];
    $types .= "s";
}


/* Summary card filters */
if ($assigned_only) {
    $where[] = "t.assigned_technician_id = ?";
    $params[] = $tech_id; $types .= "i";
}

if ($due_within_hours !== null) {
    $where[] = "TIMESTAMPDIFF(HOUR, NOW(), t.sla_date) BETWEEN 0 AND ?";
    $params[] = $due_within_hours; $types .= "i";
}

if ($due_today) {
    $where[] = "DATE(t.sla_date) = CURDATE()";
}

if ($overdue) {
    $where[] = "t.sla_date < NOW() AND {$normalizedStatusExpr} != 'complete'";
}

if ($backlog) {
    $where[] = "{$normalizedStatusExpr} != 'complete'";
}

if ($escalated_filter) {
    $where[] = "EXISTS (
        SELECT 1
        FROM tbl_ticket_escalation e2
        WHERE e2.ticket_id = t.ticket_id
          AND e2.sla_status IN ('escalated', 'overdue')
    )";
}

/* Always exclude completed unless specifically filtering for backlog */
if ($status === "" && !$includeCompleted && !$backlog && !$escalated_filter && !$overdue && !$due_today && !$due_within_hours) {
    $where[] = "{$normalizedStatusExpr} != 'complete'";
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

/* -------------------
   SUMMARY COUNTS
------------------- */
function getSingleValue($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types !== "" && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) return null;

    $row = $result->fetch_assoc();
    return $row ? array_values($row)[0] : null;
}


$summary = [];

// Skip summary counts when filtering by user_id (customer management modals) - not needed and slows down response
if ($userId === null) {
    /* Assigned to technician */
    $summary['open'] = getSingleValue(
        $conn,
        "SELECT COUNT(*) FROM tbl_ticket 
         WHERE assigned_technician_id = ? AND COALESCE(NULLIF(status, ''), 'unassigned') != 'complete'",
        "i",
        [$tech_id]
    );

    /* Due within 42 hours */
    $summary['dueToday'] = getSingleValue(
        $conn,
        "SELECT COUNT(*) FROM tbl_ticket 
         WHERE assigned_technician_id = ? 
         AND COALESCE(NULLIF(status, ''), 'unassigned') != 'complete'
         AND TIMESTAMPDIFF(HOUR, NOW(), sla_date) BETWEEN 0 AND 42",
        "i",
        [$tech_id]
    );

    /* Due within the day */
    $summary['atRisk'] = getSingleValue(
        $conn,
        "SELECT COUNT(*) FROM tbl_ticket 
         WHERE assigned_technician_id = ?
         AND COALESCE(NULLIF(status, ''), 'unassigned') != 'complete'
         AND DATE(sla_date) = CURDATE()",
        "i",
        [$tech_id]
    );

    /* Overdue */
    $summary['overdue'] = getSingleValue(
        $conn,
        "SELECT COUNT(*) FROM tbl_ticket 
         WHERE assigned_technician_id = ?
         AND sla_date < NOW()
         AND COALESCE(NULLIF(status, ''), 'unassigned') != 'complete'",
        "i",
        [$tech_id]
    );

    /* Backlog (all not completed) */
    $summary['backlog'] = getSingleValue(
        $conn,
        "SELECT COUNT(*) FROM tbl_ticket
         WHERE COALESCE(NULLIF(status, ''), 'unassigned') != 'complete'"
    );

    /* Escalations (Assigned + Exists in escalation table) */
    $summary['escalations'] = getSingleValue(
        $conn,
        "SELECT COUNT(DISTINCT t.ticket_id) FROM tbl_ticket t
         JOIN tbl_ticket_escalation e ON e.ticket_id = t.ticket_id
         WHERE t.assigned_technician_id = ?
         AND e.sla_status IN ('escalated', 'overdue')",
        "i",
        [$tech_id]
    );

    /* Should the escalation card be shown? */
    $summary['showEscCard'] = $summary['escalations'] > 0 ? 1 : 0;
}


/* -------------------
   MAIN DATA QUERY
------------------- */
$offset = ($page - 1) * $pageSize;

$sql = "
SELECT 
    t.ticket_id,
    t.reference_id,
    t.title,
    COALESCE(NULLIF(t.status, ''), 'unassigned') AS status,
    t.priority,
    t.type,
    t.sla_date,
    t.created_at,
    u.name AS requester_name,
    tech.name AS technician_name
FROM tbl_ticket t
LEFT JOIN tbl_user u ON u.user_id = t.user_id
LEFT JOIN tbl_technician tech ON tech.technician_id = t.assigned_technician_id
$whereSQL
ORDER BY $sortSQL
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$finalTypes = $types . "ii";
$params[] = $pageSize;
$params[] = $offset;
if (!$stmt){
    die("SQL Error: " . $conn->error . "<br>Query: " . $sql);
}
$stmt->bind_param($finalTypes, ...$params);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Get total count (for pagination) - skip when filtering by user_id for faster customer management modals */
$total = 0;
if ($userId === null) {
    $total = getSingleValue($conn, "SELECT COUNT(*) FROM tbl_ticket t $whereSQL", $types, array_slice($params, 0, -2));
} else {
    // For customer management, just use the result count
    $total = count($data);
}

echo json_encode([
    "data" => $data,
    "meta" => [
        "total" => $total,
        "page" => $page,
        "pageSize" => $pageSize,
        "summary" => $summary
    ]
]);
exit;
