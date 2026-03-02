<?php
require_once __DIR__ . '/ticket_api_common.php';

ticketApiRequireAuth();

$dept = (int)($_GET['dept'] ?? 0);

if ($dept > 0) {
    $stmt = $conn->prepare("
        SELECT technician_id, department_id, name, specialization, active_tickets
        FROM tbl_technician
        WHERE status = 'active' AND department_id = ?
        ORDER BY name
    ");
    if (!$stmt) {
        ticketApiJson(['ok' => false, 'error' => 'Database prepare failed'], 500);
    }
    $stmt->bind_param('i', $dept);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("
        SELECT technician_id, department_id, name, specialization, active_tickets
        FROM tbl_technician
        WHERE status = 'active'
        ORDER BY name
    ");
    if (!$result) {
        ticketApiJson(['ok' => false, 'error' => 'Database query failed'], 500);
    }
}

$technicians = [];
while ($row = $result->fetch_assoc()) {
    $technicians[] = [
        'technician_id' => (int)$row['technician_id'],
        'department_id' => (int)($row['department_id'] ?? 0),
        'name' => (string)$row['name'],
        'specialization' => (string)($row['specialization'] ?? ''),
        'active_tickets' => (int)($row['active_tickets'] ?? 0),
    ];
}

if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}

$conn->close();
ticketApiJson($technicians);
