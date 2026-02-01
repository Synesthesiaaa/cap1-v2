<?php

namespace App\Controllers;

use Services\AuthService;
use Middleware\CsrfMiddleware;

/**
 * Authentication Controller
 * 
 * Handles login, logout, and authentication-related operations
 */
class AuthController
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Handle login request
     */
    public function login(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        // Validate CSRF token
        if (!CsrfMiddleware::validateToken()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email and password are required']);
            return;
        }

        $userData = $this->authService->authenticate($email, $password);

        if ($userData !== false) {
            $this->authService->createSession($userData);

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $userData['id'],
                    'role' => $userData['role'],
                    'name' => $userData['name']
                ],
                'redirect' => $userData['user_type'] === 'external' ? 'create_ticket.php' : 'dashboard.php'
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        }
    }

    /**
     * Handle logout request
     */
    public function logout(): void
    {
        $this->authService->destroySession();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        } else {
            header('Location: login.php');
        }
        exit;
    }

    /**
     * Check authentication status
     */
    public function checkAuth(): void
    {
        header('Content-Type: application/json');
        
        $isAuthenticated = $this->authService->isAuthenticated();
        
        echo json_encode([
            'authenticated' => $isAuthenticated,
            'user_id' => $this->authService->getUserId(),
            'role' => $this->authService->getUserRole()
        ]);
    }
}
