<?php
include("db.php");

try {
    // Include current active ticket load so department heads can see how busy each technician is
    $sql = "SELECT technician_id, name, specialization, active_tickets 
            FROM tbl_technician 
            WHERE status = 'active' 
            ORDER BY name";
    $result = $conn->query($sql);

    $technicians = [];
    while ($row = $result->fetch_assoc()) {
        $technicians[] = $row;
    }

    echo json_encode($technicians);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
