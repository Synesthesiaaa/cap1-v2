<?php
include("db.php");
header("Content-Type: application/json");

$ref = $_GET['ref'] ?? '';

if (empty($ref)) {
    echo json_encode(["ok" => false, "error" => "Missing reference ID"]);
    exit();
}

// Get logs and join with both user + technician for names
$stmt = $conn->prepare("
    SELECT 
        l.log_id,
        l.action_type,
        l.action_details,
        l.created_at,
        COALESCE(u.name, t.name, 'System') AS user_name,  
        CASE 
            WHEN u.user_id IS NOT NULL THEN 'user'
            WHEN t.technician_id IS NOT NULL THEN 'technician'
            ELSE l.user_role
        END AS user_role
    FROM tbl_ticket_logs l
    LEFT JOIN tbl_user u ON l.user_id = u.user_id
    LEFT JOIN tbl_technician t ON l.user_id = t.technician_id  
    WHERE l.ticket_id = (SELECT ticket_id FROM tbl_ticket WHERE reference_id = ?)
    ORDER BY l.created_at DESC
");

$stmt->bind_param("s", $ref);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode([
    "ok" => true,
    "data" => [
        "logs" => $logs
    ]
]);

$stmt->close();
$conn->close();
?>
