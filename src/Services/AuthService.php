<?php

namespace Services;

use Database\Connection;
use Services\Logger;

/**
 * Authentication Service
 * 
 * Handles user authentication, password verification, and session management
 */
class AuthService
{
    private $conn;
    private $logger;

    public function __construct()
    {
        $this->conn = Connection::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
    }

    /**
     * Authenticate user by email and password
     * 
     * @param string $email User email
     * @param string $password Plain text password
     * @return array|false User data on success, false on failure
     */
    public function authenticate(string $email, string $password)
    {
        // Try technician table first
        $technician = $this->authenticateTechnician($email, $password);
        if ($technician !== false) {
            return $technician;
        }

        // Try user table
        $user = $this->authenticateUser($email, $password);
        if ($user !== false) {
            return $user;
        }

        return false;
    }

    /**
     * Authenticate technician
     */
    private function authenticateTechnician(string $email, string $password)
    {
        $sql = "SELECT * FROM tbl_technician WHERE email = ? AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->logger->error("Technician authentication prepare failed", [
                'error' => $this->conn->error
            ]);
            return false;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return false;
        }

        // Verify password (supports both hashed and plain text for migration)
        if (!$this->verifyPassword($password, $row['password'])) {
            return false;
        }

        // Hash password if it's still plain text (migration)
        if (strlen($row['password']) < 60) {
            $this->hashTechnicianPassword($row['technician_id'], $password);
        }

        return [
            'id' => $row['technician_id'],
            'role' => 'technician',
            'name' => $row['name'],
            'email' => $row['email'],
            'user_type' => 'internal'
        ];
    }

    /**
     * Authenticate regular user
     */
    private function authenticateUser(string $email, string $password)
    {
        $sql = "SELECT * FROM tbl_user WHERE email = ? AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            $this->logger->error("User authentication prepare failed", [
                'error' => $this->conn->error
            ]);
            return false;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return false;
        }

        // Verify password (supports both hashed and plain text for migration)
        if (!$this->verifyPassword($password, $row['password'])) {
            return false;
        }

        // Hash password if it's still plain text (migration)
        if (strlen($row['password']) < 60) {
            $this->hashUserPassword($row['user_id'], $password);
        }

        // Determine role
        $role = 'user';
        if ($row['user_role'] === 'department_head') {
            $role = 'department_head';
        } elseif ($row['user_role'] === 'admin') {
            $role = 'admin';
        }

        return [
            'id' => $row['user_id'],
            'role' => $role,
            'name' => $row['name'],
            'email' => $row['email'],
            'user_type' => $row['user_type'],
            'department_id' => $row['department_id']
        ];
    }

    /**
     * Verify password (supports both hashed and plain text for backward compatibility)
     */
    private function verifyPassword(string $password, string $hash): bool
    {
        // If hash is less than 60 characters, it's likely plain text
        if (strlen($hash) < 60) {
            // Plain text comparison (for migration period)
            return hash_equals($hash, $password);
        }

        // Use password_verify for hashed passwords
        return password_verify($password, $hash);
    }

    /**
     * Hash and update technician password
     */
    private function hashTechnicianPassword(int $technicianId, string $password): void
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE tbl_technician SET password = ? WHERE technician_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("si", $hashedPassword, $technicianId);
            $stmt->execute();
            $stmt->close();
            
            $this->logger->info("Hashed technician password", [
                'technician_id' => $technicianId
            ]);
        }
    }

    /**
     * Hash and update user password
     */
    private function hashUserPassword(int $userId, string $password): void
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE tbl_user SET password = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("si", $hashedPassword, $userId);
            $stmt->execute();
            $stmt->close();
            
            $this->logger->info("Hashed user password", [
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Create session for authenticated user
     */
    public function createSession(array $userData): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['id'] = $userData['id'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['name'] = $userData['name'];
        if ($userData['role'] === 'technician') {
            $_SESSION['technician_id'] = $userData['id'];
        }
        if (isset($userData['user_type'])) {
            $_SESSION['user_type'] = $userData['user_type'];
        }
        
        if (isset($userData['department_id'])) {
            $_SESSION['department_id'] = $userData['department_id'];
        }

        // Regenerate session ID for security
        session_regenerate_id(true);
    }

    /**
     * Destroy session
     */
    public function destroySession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['id']);
    }

    /**
     * Get current user ID
     */
    public function getUserId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['id'] ?? null;
    }

    /**
     * Get current user role
     */
    public function getUserRole(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['role'] ?? null;
    }
}
