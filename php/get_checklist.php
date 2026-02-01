<?php
require_once "db.php";
header("Content-Type: application/json");

// Validate reference ID
$ref = $_GET['ref'] ?? '';
if (!$ref) {
    echo json_encode([]);
    exit;
}

// Get ticket_id
$stmt = $conn->prepare("SELECT ticket_id FROM tbl_ticket WHERE reference_id = ?");
$stmt->bind_param("s", $ref);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    echo json_encode([]);
    exit;
}

$ticket_id = $res['ticket_id'];

// Fetch checklist items
$sql = "SELECT item_id, ticket_id, created_by, is_technician, description, 
               is_completed, created_at, completed_at
        FROM tbl_ticket_checklist
        WHERE ticket_id = ?
        ORDER BY created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();

$result = $stmt->get_result();
$items = [];

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($items);
?>
