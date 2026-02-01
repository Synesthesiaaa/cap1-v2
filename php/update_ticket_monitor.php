<?php
// Try to use new structure if available
$useNewStructure = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap.php';
    if (class_exists('Services\LogService')) {
        $useNewStructure = true;
    }
}

// Fallback to old structure
if (!$useNewStructure) {
    include("db.php");
    if (file_exists("insert_log_monitor.php")) {
        include("insert_log_monitor.php");
    }
}

session_start();

@header("Content-Type: application/json");
@ini_set('display_errors', 0);
error_reporting(E_ALL);

// ✅ Get user info from session
$user_id = $_SESSION['id'] ?? null;
$user_role = $_SESSION['role'] ?? 'system';

try {
    // ✅ Get POST data
    $ref = $_POST['ref'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $sla_date = $_POST['sla_date'] ?? '';

    if (empty($ref) || empty($priority) || empty($sla_date)) {
        echo json_encode(["ok" => false, "error" => "Missing required fields"]);
        exit();
    }

    // ✅ Update the ticket
    $stmt = $conn->prepare("UPDATE tbl_ticket SET priority = ?, sla_date = ? WHERE reference_id = ?");
    if (!$stmt) {
        echo json_encode(["ok" => false, "error" => "Prepare failed: " . $conn->error]);
        exit();
    }

    $stmt->bind_param("sss", $priority, $sla_date, $ref);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        throw new Exception("Database error: " . $conn->error);
    }

    // ✅ Retrieve ticket_id for logging
    $get = $conn->prepare("SELECT ticket_id FROM tbl_ticket WHERE reference_id = ?");
    $get->bind_param("s", $ref);
    $get->execute();
    $res = $get->get_result()->fetch_assoc();
    $get->close();

    $ticket_id = $res['ticket_id'] ?? null;

    if (!$ticket_id) {
        echo json_encode(["ok" => false, "error" => "Ticket not found"]);
        exit();
    }

    // ✅ Insert log entry
    $roleLabel = ucfirst($user_role); 
    $userID = intval($user_id);    
    $action_type = "edit";
    $action_details = "$roleLabel ID $userID updated ticket: Priority set to {$priority}, SLA changed to {$sla_date}";
    
    // Use LogService if available
    if ($useNewStructure) {
        try {
            $logService = new \Services\LogService();
            $logService->logTicketAction($ticket_id, $user_id, $user_role, $action_type, $action_details);
        } catch (\Exception $e) {
            // Fallback to old method
            if (function_exists('insertTicketLog')) {
                insertTicketLog($ticket_id, $user_id, $user_role, $action_type, $action_details, $conn);
            }
        }
    } elseif (function_exists('insertTicketLog')) {
        insertTicketLog($ticket_id, $user_id, $user_role, $action_type, $action_details, $conn);
    }

    // ✅ Respond back
    echo json_encode(["ok" => true, "ticket_id" => $ticket_id]);

} catch (Throwable $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
