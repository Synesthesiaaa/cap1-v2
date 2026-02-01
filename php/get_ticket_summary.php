<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'technician') {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

// Use technician_id from session (same as tickets_list.php)
$techId = $_SESSION['technician_id'] ?? $_SESSION['id'] ?? 0;

if ($techId == 0) {
    echo json_encode(["error" => "Technician ID not found"]);
    exit();
}

// Helper function to get count using prepared statements
function getCount($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types !== "" && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return intval($row ? array_values($row)[0] : 0);
}

// today's date
$today = date("Y-m-d");

// summary container
$summary = [
    "open" => 0,
    "dueSoon" => 0,
    "dueToday" => 0,
    "overdue" => 0,
    "backlog" => 0,
    "escalations" => 0
];

// Assigned to technician (all non-completed tickets assigned to this technician)
$summary['open'] = getCount(
    $conn,
    "SELECT COUNT(*) FROM tbl_ticket 
     WHERE assigned_technician_id = ? AND status != 'complete'",
    "i",
    [$techId]
);

// Due within 42 hours (assigned to this technician, not completed)
$summary['dueSoon'] = getCount(
    $conn,
    "SELECT COUNT(*) FROM tbl_ticket 
     WHERE assigned_technician_id = ? 
     AND status != 'complete'
     AND TIMESTAMPDIFF(HOUR, NOW(), sla_date) BETWEEN 0 AND 42",
    "i",
    [$techId]
);

// Due today (assigned to this technician, not completed)
$summary['dueToday'] = getCount(
    $conn,
    "SELECT COUNT(*) FROM tbl_ticket 
     WHERE assigned_technician_id = ? 
     AND status != 'complete'
     AND DATE(sla_date) = CURDATE()",
    "i",
    [$techId]
);

// Overdue (assigned to this technician, not completed)
$summary['overdue'] = getCount(
    $conn,
    "SELECT COUNT(*) FROM tbl_ticket 
     WHERE assigned_technician_id = ?
     AND sla_date < NOW()
     AND status != 'complete'",
    "i",
    [$techId]
);

// Backlog (unassigned tickets - all unassigned tickets in the system)
$summary['backlog'] = getCount(
    $conn,
    "SELECT COUNT(*) FROM tbl_ticket 
     WHERE (assigned_technician_id IS NULL OR assigned_technician_id = 0)
     AND status != 'complete'"
);

// Escalations (tickets assigned to this technician that are escalated)
$summary['escalations'] = getCount(
    $conn,
    "SELECT COUNT(*) FROM tbl_ticket t
     JOIN tbl_ticket_escalation e ON e.ticket_id = t.ticket_id
     WHERE t.assigned_technician_id = ?
     AND e.sla_status = 'Escalated'",
    "i",
    [$techId]
);

echo json_encode($summary);
exit;
