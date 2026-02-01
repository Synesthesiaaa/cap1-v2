<?php

namespace Services;

use Database\Connection;
use Database\QueryBuilder;
use Services\LogService;
use Services\Logger;

/**
 * Reply Service
 * 
 * Handles ticket replies and comments
 */
class ReplyService
{
    private $conn;
    private $logService;
    private $logger;
    private $table = 'tbl_ticket_reply';

    public function __construct()
    {
        $this->conn = Connection::getInstance()->getConnection();
        $this->logService = new LogService();
        $this->logger = Logger::getInstance();
    }

    /**
     * Create a reply to a ticket
     */
    public function createReply(int $ticketId, int $replierId, string $repliedBy, string $replyText, ?string $attachmentPath = null): ?array
    {
        try {
            $builder = new QueryBuilder($this->conn, $this->table);
            $replyId = $builder->insert([
                'ticket_id' => $ticketId,
                'replied_by' => $repliedBy,
                'replier_id' => $replierId,
                'reply_text' => $replyText,
                'attachment_path' => $attachmentPath,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($replyId) {
                // Log the reply
                $actionDetails = strlen($replyText) > 100 ? substr($replyText, 0, 97) . "..." : $replyText;
                $this->logService->logTicketAction(
                    $ticketId,
                    $replierId,
                    $repliedBy,
                    'reply',
                    "Added reply: {$actionDetails}"
                );

                // Get the complete reply data
                $reply = $this->getReplyById($replyId);

                $this->logger->info("Reply created", [
                    'reply_id' => $replyId,
                    'ticket_id' => $ticketId,
                    'replier_id' => $replierId
                ]);

                return $reply;
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error("Failed to create reply", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get reply by ID
     */
    public function getReplyById(int $replyId): ?array
    {
        try {
            $sql = "SELECT r.reply_text AS message, r.replied_by, r.attachment_path, r.created_at,
                           CASE r.replied_by
                               WHEN 'user' THEN u.name
                               WHEN 'technician' THEN COALESCE(tech.name, 'Support Agent')
                               ELSE 'System'
                           END AS sender
                    FROM tbl_ticket_reply r
                    LEFT JOIN tbl_user u ON r.replied_by = 'user' AND r.replier_id = u.user_id
                    LEFT JOIN tbl_technician tech ON r.replied_by = 'technician' AND r.replier_id = tech.technician_id
                    WHERE r.reply_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $replyId);
            $stmt->execute();
            $result = $stmt->get_result();
            $reply = $result->fetch_assoc();
            $stmt->close();

            return $reply ?: null;

        } catch (\Exception $e) {
            $this->logger->error("Failed to get reply", [
                'reply_id' => $replyId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get all replies for a ticket
     */
    public function getTicketReplies(int $ticketId): array
    {
        try {
            $sql = "SELECT r.reply_id, r.reply_text AS message, r.replied_by, r.attachment_path, r.created_at,
                           CASE r.replied_by
                               WHEN 'user' THEN u.name
                               WHEN 'technician' THEN COALESCE(tech.name, 'Support Agent')
                               ELSE 'System'
                           END AS sender
                    FROM tbl_ticket_reply r
                    LEFT JOIN tbl_user u ON r.replied_by = 'user' AND r.replier_id = u.user_id
                    LEFT JOIN tbl_technician tech ON r.replied_by = 'technician' AND r.replier_id = tech.technician_id
                    WHERE r.ticket_id = ?
                    ORDER BY r.created_at ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $replies = [];
            while ($row = $result->fetch_assoc()) {
                $replies[] = $row;
            }
            $stmt->close();

            return $replies;

        } catch (\Exception $e) {
            $this->logger->error("Failed to get ticket replies", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Handle file upload for reply attachment
     */
    public function handleAttachmentUpload(array $file): ?string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new \Exception("Invalid file type. Only JPG, PNG, and PDF files are allowed.");
        }

        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($file['size'] > $maxSize) {
            throw new \Exception("File too large. Maximum file size is 50MB.");
        }

        $uploadDir = __DIR__ . '/../../uploads/replies/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $timestamp = time();
        $filename = $timestamp . '_' . preg_replace("/[^A-Za-z0-9._-]/", "_", basename($file['name']));
        $filePath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Return relative path for database storage
            return str_replace(__DIR__ . '/../../', '', $filePath);
        }

        throw new \Exception("Failed to upload file.");
    }
}
