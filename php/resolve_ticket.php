<?php
/**
 * Resolve Ticket - Migrated to use new architecture
 * 
 * This endpoint now uses TicketService while maintaining backward compatibility
 */

@session_start();
@header("Content-Type: application/json");

while (@ob_get_level()) @ob_end_clean();
@ob_start();

function json_response($data, $status_code = 200) {
    @http_response_code($status_code);
    echo json_encode($data);
    @ob_end_flush();
    exit();
}

// Try to use new structure if available
$useNewStructure = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap.php';
    if (class_exists('Services\TicketService')) {
        $useNewStructure = true;
    }
}

try {
    if (!isset($_SESSION['id'])) {
        json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
    }

    $ref = $_POST['ref'] ?? '';
    if (!$ref) {
        json_response(['ok' => false, 'error' => 'Missing ticket reference'], 400);
    }

    // Use new structure if available
    if ($useNewStructure) {
        try {
            $ticketService = new \Services\TicketService();
            $ticket = $ticketService->getByReference($ref);
            
            if (!$ticket) {
                json_response(['ok' => false, 'error' => 'Ticket not found'], 404);
            }
            
            $userId = $_SESSION['id'];
            $userRole = $_SESSION['role'] ?? 'user';
            
            // Toggle status: complete if not complete, reopen if complete
            $newStatus = ($ticket['status'] === 'complete') ? 'pending' : 'complete';
            $message = ($newStatus === 'complete') ? 'Ticket has been completed.' : 'Ticket has been reopened.';
            
            if ($newStatus === 'complete') {
                $result = $ticketService->resolve($ticket['ticket_id'], $userId, $userRole, $message);
            } else {
                // Reopen ticket: clear resolved_at
                $result = $ticketService->update($ticket['ticket_id'], ['status' => 'pending', 'resolved_at' => null], $userId, $userRole);
            }
            
            if ($result) {
                json_response(['ok' => true, 'status' => $newStatus, 'message' => $message]);
            } else {
                json_response(['ok' => false, 'error' => 'Failed to update ticket'], 500);
            }
        } catch (\Exception $e) {
            // Fall through to old implementation
            $useNewStructure = false;
        }
    }
    
    // OLD IMPLEMENTATION (fallback)
    if (!$useNewStructure) {
        include('db.php');

    // Update ticket status to resolved/reopened based on current status
    $stmt = $conn->prepare("SELECT status FROM tbl_ticket WHERE reference_id = ?");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
        json_response(['ok' => false, 'error' => 'Ticket not found'], 404);
    }

    // Set to complete if not already complete, or reopen if complete
    $new_status = ($current['status'] === 'complete') ? 'pending' : 'complete';
    $message = ($new_status === 'complete') ? 'Ticket has been completed.' : 'Ticket has been reopened.';

    if ($new_status === 'complete') {
        $update = $conn->prepare("UPDATE tbl_ticket SET status = ?, resolved_at = NOW() WHERE reference_id = ?");
        $update->bind_param("ss", $new_status, $ref);
    } else {
        $update = $conn->prepare("UPDATE tbl_ticket SET status = ?, resolved_at = NULL WHERE reference_id = ?");
        $update->bind_param("ss", $new_status, $ref);
    }
    $success = $update->execute();
    $update->close();

    if ($success) {
        // Add system reply
        $stmt = $conn->prepare("SELECT ticket_id FROM tbl_ticket WHERE reference_id = ?");
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($r) {
            $ins_reply = $conn->prepare("INSERT INTO tbl_ticket_reply (ticket_id, replied_by, replier_id, reply_text, created_at) VALUES (?, 'system', 0, ?, NOW())");
            $ins_reply->bind_param("is", $r['ticket_id'], $message);
            $ins_reply->execute();
            $ins_reply->close();

            // Log the complete/reopen action
            $user_role = isset($_SESSION['role']) && $_SESSION['role'] === 'technician' ? 'technician' : 'user';
            $action_type = $new_status === 'complete' ? 'complete' : 'reopen';
            $action_details = $new_status === 'complete' ? "Ticket completed by user" : "Ticket reopened by user";
            
            // Try to use LogService if available
            if (class_exists('Services\LogService')) {
                try {
                    $logService = new \Services\LogService();
                    $logService->logTicketAction($r['ticket_id'], $_SESSION['id'], $user_role, $action_type, $action_details);
                } catch (\Exception $e) {
                    // Fallback to old method
                    if (file_exists("insert_log.php")) {
                        include("insert_log.php");
                        if (function_exists('insertTicketLog')) {
                            insertTicketLog($r['ticket_id'], $_SESSION['id'], $user_role, $action_type, $action_details, $conn);
                        }
                    }
                }
            } elseif (file_exists("insert_log.php")) {
                include("insert_log.php");
                if (function_exists('insertTicketLog')) {
                    insertTicketLog($r['ticket_id'], $_SESSION['id'], $user_role, $action_type, $action_details, $conn);
                }
            }
        }

        json_response(['ok' => true, 'status' => $new_status, 'message' => $message]);
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
        exit();
