<?php
/**
 * Post Reply - Migrated to use new architecture
 * 
 * This endpoint now uses ReplyService while maintaining backward compatibility
 */

@session_start();
@header("Content-Type: application/json");

function json_error($message) {
    echo '{"ok":false,"error":"' . addslashes($message) . '"}';
    exit(0);
}

// Try to use new structure if available
$useNewStructure = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap.php';
    if (class_exists('Services\ReplyService') && class_exists('Repositories\TicketRepository')) {
        $useNewStructure = true;
    }
}

try {
    if (!isset($_SESSION['id'])) {
        json_error("Unauthorized - please log in");
    }

    $ref = $_POST['ref'] ?? '';
    $reply = trim($_POST['reply'] ?? '');
    $attachment = $_FILES['replyAttachment'] ?? null;

    if (empty($ref)) {
        json_error("Missing ticket reference");
    }

    if (empty($reply) && !$attachment) {
        json_error("Reply cannot be empty and no attachment provided");
    }

    // Use new structure if available
    if ($useNewStructure) {
        try {
            $ticketRepository = new \Repositories\TicketRepository();
            $ticket = $ticketRepository->findByReference($ref);
            
            if (!$ticket) {
                json_error("Ticket not found");
            }
            
            $replyService = new \Services\ReplyService();
            $replied_by = (($_SESSION['role'] ?? '') === 'technician') ? 'technician' : 'user';
            $replier_id = $_SESSION['id'];
            
            // Handle attachment
            $attachmentPath = null;
            if ($attachment) {
                try {
                    $attachmentPath = $replyService->handleAttachmentUpload($attachment);
                } catch (\Exception $e) {
                    json_error($e->getMessage());
                }
            }
            
            // Create reply
            $replyData = $replyService->createReply(
                $ticket['ticket_id'],
                $replier_id,
                $replied_by,
                $reply,
                $attachmentPath
            );
            
            if ($replyData) {
                echo json_encode([
                    "ok" => true,
                    "reply" => $replyData
                ]);
                exit(0);
            } else {
                json_error("Failed to save reply");
            }
        } catch (\Exception $e) {
            // Fall through to old implementation
            $useNewStructure = false;
        }
    }
    
    // OLD IMPLEMENTATION (fallback)
    if (!$useNewStructure) {
        // Handle file upload
        $attachment_path = null;
        if ($attachment && $attachment['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            if (!in_array($attachment['type'], $allowed_types)) {
                json_error("Invalid file type. Only JPG, PNG, and PDF files are allowed.");
            }

            $max_size = 50 * 1024 * 1024;
            if ($attachment['size'] > $max_size) {
                json_error("File too large. Maximum file size is 50MB.");
            }

            $upload_dir = '../uploads/replies/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $timestamp = time();
            $filename = $timestamp . '_' . basename($attachment['name']);
            $file_path = $upload_dir . $filename;

            if (move_uploaded_file($attachment['tmp_name'], $file_path)) {
                $attachment_path = $file_path;
            } else {
                json_error("Failed to upload file.");
            }
        }
        
        include("db.php");

        // Get ticket ID
        $stmt = $conn->prepare("SELECT ticket_id FROM tbl_ticket WHERE reference_id = ?");
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        $res = $stmt->get_result();
        $ticket = $res->fetch_assoc();
        $stmt->close();

        if (!$ticket) {
            json_error("Ticket not found");
        }

        $ticket_id = $ticket['ticket_id'];
        $replied_by = (($_SESSION['role'] ?? '') === 'technician') ? 'technician' : 'user';
        $replier_id = $_SESSION['id'];

        // Prepare attachment path
        $db_attachment_path = null;
        if ($attachment_path) {
            $db_attachment_path = str_replace('../', '', $attachment_path);
        }

        // Insert reply
        $insert = $conn->prepare("
            INSERT INTO tbl_ticket_reply (ticket_id, replied_by, replier_id, reply_text, attachment_path, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $insert->bind_param("isiss", $ticket_id, $replied_by, $replier_id, $reply, $db_attachment_path);
        $ok = $insert->execute();
        $insert->close();

        if ($ok) {
            // Get the inserted reply ID
            $reply_id = $conn->insert_id;

            // Log the reply action
            $action_details = strlen($reply) > 100 ? substr($reply, 0, 97) . "..." : $reply;
            $log_message = "Added reply: {$action_details}";
            
            // Try to use LogService if available
            if (class_exists('Services\LogService')) {
                try {
                    $logService = new \Services\LogService();
                    $logService->logTicketAction($ticket_id, $replier_id, $replied_by, 'reply', $log_message);
                } catch (\Exception $e) {
                    // Fallback to old method
                    if (file_exists("insert_log.php")) {
                        include("insert_log.php");
                        if (function_exists('insertTicketLog')) {
                            insertTicketLog($ticket_id, $replier_id, $replied_by, 'reply', $log_message, $conn);
                        }
                    }
                }
            } elseif (file_exists("insert_log.php")) {
                include("insert_log.php");
                if (function_exists('insertTicketLog')) {
                    insertTicketLog($ticket_id, $replier_id, $replied_by, 'reply', $log_message, $conn);
                }
            }

            // Fetch the complete reply data
            $fetch_stmt = $conn->prepare("
                SELECT r.reply_text AS message, r.replied_by, r.attachment_path, r.created_at,
                       CASE r.replied_by
                           WHEN 'user' THEN u.name
                           WHEN 'technician' THEN COALESCE(tech.name, 'Support Agent')
                           ELSE 'System'
                       END AS sender
                FROM tbl_ticket_reply r
                LEFT JOIN tbl_user u ON r.replied_by = 'user' AND r.replier_id = u.user_id
                LEFT JOIN tbl_technician tech ON r.replied_by = 'technician' AND r.replier_id = tech.technician_id
                WHERE r.reply_id = ?
            ");
            $fetch_stmt->bind_param("i", $reply_id);
            $fetch_stmt->execute();
            $reply_data = $fetch_stmt->get_result()->fetch_assoc();
            $fetch_stmt->close();

            echo json_encode([
                "ok" => true,
                "reply" => $reply_data
            ]);
            exit(0);
        } else {
            json_error("Failed to save reply: " . $conn->error);
        }

        $conn->close();
    }

} catch (Exception $e) {
    json_error("Exception: " . $e->getMessage());
} catch (Throwable $t) {
    json_error("Error: " . $t->getMessage());
}
?>
