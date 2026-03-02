<?php
require_once __DIR__ . '/ticket_api_common.php';

ticketApiRequireAuth();

$sql = "SELECT department_id, department_name FROM tbl_department ORDER BY department_name";
$result = $conn->query($sql);
if (!$result) {
    ticketApiJson(['ok' => false, 'error' => 'Database query failed'], 500);
}

$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = [
        'department_id' => (int)$row['department_id'],
        'department_name' => (string)$row['department_name'],
    ];
}

$conn->close();
ticketApiJson($departments);
