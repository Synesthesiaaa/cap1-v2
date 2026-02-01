<?php

namespace Services;

use Database\Connection;
use Database\QueryBuilder;
use Services\Logger;

/**
 * Log Service
 * 
 * Centralized service for logging ticket actions and system events
 * Consolidates insert_log.php and insert_log_monitor.php
 */
class LogService
{
    private $conn;
    private $logger;
    private $table = 'tbl_ticket_logs';

    public function __construct()
    {
        $this->conn = Connection::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
    }

    /**
     * Insert a ticket log entry
     * 
     * @param int $ticketId Ticket ID
     * @param int|null $userId User ID (can be null for system actions)
     * @param string $userRole User role (technician, user, admin, etc.)
     * @param string $actionType Action type (created, updated, replied, resolved, etc.)
     * @param string $actionDetails Description of the action
     * @return bool Success status
     */
    public function logTicketAction(
        int $ticketId,
        ?int $userId,
        string $userRole,
        string $actionType,
        string $actionDetails
    ): bool {
        try {
            // Verify user exists if userId is provided
            if ($userId !== null) {
                $userExists = $this->verifyUserExists($userId);
                if (!$userExists) {
                    $userId = null; // Set to null if user doesn't exist
                    $this->logger->warning("Logging action for non-existent user", [
                        'ticket_id' => $ticketId,
                        'user_id' => $userId,
                        'action_type' => $actionType
                    ]);
                }
            }

            // Get client information
            $ipAddress = $this->getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            // Insert log entry
            $builder = new QueryBuilder($this->conn, $this->table);
            $logId = $builder->insert([
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'user_role' => $userRole,
                'action_type' => $actionType,
                'action_details' => $actionDetails,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($logId) {
                $this->logger->info("Ticket log entry created", [
                    'log_id' => $logId,
                    'ticket_id' => $ticketId,
                    'action_type' => $actionType
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error("Failed to create ticket log entry", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get logs for a specific ticket
     * 
     * @param int $ticketId Ticket ID
     * @param int $limit Maximum number of logs to return
     * @return array Log entries
     */
    public function getTicketLogs(int $ticketId, int $limit = 100): array
    {
        try {
            $builder = new QueryBuilder($this->conn, $this->table);
            $logs = $builder->where('ticket_id', $ticketId)
                           ->orderBy('created_at', 'DESC')
                           ->limit($limit)
                           ->get();

            return $logs;
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve ticket logs", [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get logs for a specific user
     * 
     * @param int $userId User ID
     * @param int $limit Maximum number of logs to return
     * @return array Log entries
     */
    public function getUserLogs(int $userId, int $limit = 100): array
    {
        try {
            $builder = new QueryBuilder($this->conn, $this->table);
            $logs = $builder->where('user_id', $userId)
                           ->orderBy('created_at', 'DESC')
                           ->limit($limit)
                           ->get();

            return $logs;
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve user logs", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Verify if user exists in database
     */
    private function verifyUserExists(int $userId): bool
    {
        try {
            $sql = "SELECT user_id FROM tbl_user WHERE user_id = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();

            return $exists;
        } catch (\Exception $e) {
            $this->logger->error("Error verifying user existence", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
            }
        }

        return 'unknown';
    }
}
