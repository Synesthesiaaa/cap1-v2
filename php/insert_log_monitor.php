<?php
/**
 * @deprecated This file is deprecated and will be removed in a future version.
 * Please use Services\LogService instead.
 * 
 * This file is kept temporarily for backward compatibility only.
 * All new code should use: $logService = new \Services\LogService();
 */

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
    if (!$conn) {
        include("db.php");
    }

    error_log("[insert_log_monitor] Started | Ticket ID: $ticket_id | Action: $action_type");
    error_log("[insert_log_monitor] Inserting log → user_id=$user_id | user_role=$user_role");

    $userExists = false;
    $checkUser = $conn->prepare("SELECT user_id FROM tbl_user WHERE user_id = ?");
    if ($checkUser) {
        $checkUser->bind_param("i", $user_id);
        $checkUser->execute();
        $checkUser->store_result();
        $userExists = $checkUser->num_rows > 0;
        $checkUser->close();
    }

    if (!$userExists) {
        $user_id = null;
    }

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $stmt = $conn->prepare("
        INSERT INTO tbl_ticket_logs (ticket_id, user_id, user_role, action_type, action_details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        error_log("[insert_log_monitor] Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("iisssss", $ticket_id, $user_id, $user_role, $action_type, $action_details, $ip_address, $user_agent);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        error_log("[insert_log_monitor] Log inserted successfully");
    } else {
        error_log("[insert_log_monitor] Failed to insert log: " . $conn->error);
    }

    return $ok;
}
}
?>
