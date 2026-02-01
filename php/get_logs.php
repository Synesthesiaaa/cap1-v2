<?php
// get_logs.php - Fetch ticket logs from database

@session_start();
@header("Content-Type: application/json");

while (@ob_get_level()) @ob_end_clean();
@ob_start();

function json_error($message, $code = 400) {
    @http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit(0);
}

function json_success($data) {
    echo json_encode(['ok' => true, 'data' => $data]);
    exit(0);
}

try {
    $ref = $_GET['ref'] ?? '';

    if (empty($ref)) {
        json_error("Missing ticket reference");
    }

    // Include database connection
    include("db.php");

    // Get ticket ID first
    $ticket_stmt = $conn->prepare("SELECT ticket_id FROM tbl_ticket WHERE reference_id = ?");
    $ticket_stmt->bind_param("s", $ref);
    $ticket_stmt->execute();
    $ticket_result = $ticket_stmt->get_result();

    if ($ticket_result->num_rows === 0) {
        json_error("Ticket not found");
    }

    $ticket = $ticket_result->fetch_assoc();
    $ticket_id = $ticket['ticket_id'];
    $ticket_stmt->close();

    // Get logs with user information
    $stmt = $conn->prepare("
        SELECT l.*, COALESCE(u.name, t.name, 'Unknown User') as user_name
        FROM tbl_ticket_logs l
        LEFT JOIN tbl_user u ON l.user_id = u.user_id AND l.user_role = 'user'
        LEFT JOIN tbl_technician t ON l.user_id = t.technician_id AND l.user_role = 'technician'
        WHERE l.ticket_id = ?
        ORDER BY l.created_at DESC
        LIMIT 50
    ");

    if (!$stmt) {
        json_error("Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $logs = [];

    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'log_id' => $row['log_id'],
            'user_name' => $row['user_name'] ?? 'Unknown User',
            'user_role' => $row['user_role'],
            'action_type' => $row['action_type'],
            'action_details' => $row['action_details'] ?? '',
            'created_at' => $row['created_at'],
            'ip_address' => $row['ip_address'],
            'user_agent' => $row['user_agent']
        ];
    }

    $stmt->close();
    $conn->close();

    json_success(['logs' => $logs]);

} catch (Exception $e) {
    json_error("Exception: " . $e->getMessage());
} catch (Throwable $t) {
    json_error("Error: " . $t->getMessage());
}
?>
