<?php
/**
 * @deprecated This file is deprecated and will be removed in a future version.
 * Please use Services\LogService instead.
 * 
 * This file is kept temporarily for backward compatibility only.
 * All new code should use: $logService = new \Services\LogService();
 */

// insert_log.php - Helper function to insert logs into database
if (!function_exists('insertTicketLog')) {
function insertTicketLog($ticket_id, $user_id, $user_role, $action_type, $action_details, $conn = null) {
    // Try to use LogService if available
    if (class_exists('Services\LogService')) {
        try {
            $logService = new \Services\LogService();
            $logService->logTicketAction($ticket_id, $user_id, $user_role, $action_type, $action_details);
            return true;
        } catch (\Exception $e) {
            // Fall through to old implementation
        }
    }
    
    // OLD IMPLEMENTATION (fallback only)
    // Get user IP and agent
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Use provided connection or create new one
    if (!$conn) {
        // Create a new database connection (require_once prevents duplicate inclusions)
        require_once("db.php");
        $conn = $GLOBALS['conn'];
        $close_connection = true;
    } else {
        $close_connection = false;
    }

    try {
        // Check if user_id exists, if not set to NULL
        $check_user = $conn->prepare("SELECT user_id FROM tbl_user WHERE user_id = ?");
        $check_user->bind_param("i", $user_id);
        $check_user->execute();
        $user_exists = $check_user->get_result()->num_rows > 0;
        $check_user->close();

        $actual_user_id = $user_exists ? $user_id : null;

        // Insert log
        $stmt = $conn->prepare("
            INSERT INTO tbl_ticket_logs (ticket_id, user_id, user_role, action_type, action_details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iisssss", $ticket_id, $actual_user_id, $user_role, $action_type, $action_details, $ip_address, $user_agent);
        $result = $stmt->execute();
        $stmt->close();

        if ($close_connection && isset($conn)) {
            $conn->close();
        }

        return $result;
    } catch (Exception $e) {
        error_log("insertTicketLog error: " . $e->getMessage());
        if ($close_connection && isset($conn)) {
            $conn->close();
        }
        return false;
    }
}
}
?>
