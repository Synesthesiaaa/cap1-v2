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

header("Content-Type: application/json");
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    if (!isset($_SESSION['id'])) {
        throw new Exception("Unauthorized access.");
    }

    $user_id = $_SESSION['id'];
    $user_role = $_SESSION['role'];

    $ref = $_POST['ref'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $department_id = $_POST['department_id'] ?? '';
    $technician_id = $_POST['technician_id'] ?? '';
    $priority = $_POST['priority'] ?? '';
    
    if (!$ref) throw new Exception("Missing ticket reference.");
    if (!$reason) throw new Exception("Escalation reason required.");
    if (!$department_id) throw new Exception("Department is required.");
    if (!$technician_id) throw new Exception("Technician is required.");
    if (!$priority) throw new Exception("Priority is required.");
    if ($priority == 'medium') {
        $priority = 'regular'; // Normalize priority input
        $urgency = 'medium';
    }
    $stmt = $conn->prepare("
        SELECT ticket_id, type 
        FROM tbl_ticket 
        WHERE reference_id = ?
    ");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ticket) throw new Exception("Ticket not found.");

    $ticket_id = $ticket['ticket_id'];
    $ticket_type = $ticket['type']; 


    // Allowable roles
    $allowed_roles = ["technician", "department_head", "admin"];

    if (!in_array($user_role, $allowed_roles)) {
        throw new Exception("Unauthorized. Your role cannot escalate tickets.");
    }


    $stmt = $conn->prepare("
        SELECT technician_id 
        FROM tbl_technician 
        WHERE technician_id = ?
    ");
    $stmt->bind_param("i", $technician_id);
    $stmt->execute();
    $validTech = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$validTech) {
        throw new Exception("Selected technician does not belong to the specified department.");
    }

    $stmt = $conn->prepare("
        SELECT assigned_technician_id, type
        FROM tbl_ticket
        WHERE ticket_id = ?
    ");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prev_technician_id = $old["assigned_technician_id"];
    $prev_department_name = $old["type"];

    $stmt = $conn->prepare("SELECT department_id FROM tbl_department WHERE department_name = ?");
    $stmt->bind_param("s", $prev_department_name);
    $stmt->execute();
    $deptRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prev_department_id = $deptRow["department_id"] ?? null;

    $stmt = $conn->prepare("
    INSERT INTO tbl_ticket_escalation
    (ticket_id, prev_technician_id, new_technician_id, prev_department_id, new_department_id, reason, escalator_id, escalation_type, sla_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'manual', 'escalated')
    ");
    $stmt->bind_param(
        "iiiiisi",
        $ticket_id,
        $prev_technician_id,
        $technician_id,
        $prev_department_id,
        $department_id,
        $reason,
        $user_id
    );
    $stmt->execute();
    $stmt->close();

    // --- ACTIVE TICKETS UPDATE ---
    if ($prev_technician_id != $technician_id) {

        // Decrease old technician counter (if exists)
        if (!empty($prev_technician_id)) {
            $stmt = $conn->prepare("
                UPDATE tbl_technician
                SET active_tickets = active_tickets - 1
                WHERE technician_id = ? AND active_tickets > 0
            ");
            $stmt->bind_param("i", $prev_technician_id);
            $stmt->execute();
            $stmt->close();
        }

        // Increase new technician counter
        $stmt = $conn->prepare("
            UPDATE tbl_technician
            SET active_tickets = active_tickets + 1
            WHERE technician_id = ?
        ");
        $stmt->bind_param("i", $technician_id);
        $stmt->execute();
        $stmt->close();
    }


    $stmt = $conn->prepare("
        UPDATE tbl_ticket 
        SET assigned_technician_id = ?, priority = ?, urgency = ? 
        WHERE ticket_id = ?
    ");
    $stmt->bind_param("issi", $technician_id, $priority, $urgency, $ticket_id);
    $stmt->execute();
    $stmt->close();

    // Insert log
    $action_type = "escalate";
    $action_details = "Ticket escalated to technician ID $technician_id with new priority $priority. Reason: $reason";
    
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

    echo json_encode(["ok" => true, "message" => "Ticket successfully escalated."]);
    exit();

} catch (Throwable $e) {
    error_log("[escalate_ticket] " . $e->getMessage());
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
    exit();
}
?>
