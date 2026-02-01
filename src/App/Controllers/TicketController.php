<?php

namespace App\Controllers;

use Services\TicketService;
use Services\AuthService;
use Middleware\CsrfMiddleware;

/**
 * Ticket Controller
 * 
 * Handles ticket-related API requests
 */
class TicketController
{
    private $ticketService;
    private $authService;

    public function __construct()
    {
        $this->ticketService = new TicketService();
        $this->authService = new AuthService();
    }

    /**
     * Create a new ticket
     */
    public function create(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        // Validate authentication
        if (!$this->authService->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        // Validate CSRF token
        if (!CsrfMiddleware::validateToken()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            return;
        }

        $userId = $this->authService->getUserId();
        $userRole = $this->authService->getUserRole() ?? 'user';

        $userType = $_SESSION['user_type'] ?? 'internal';
        $ticketType = $_POST['ticket_type'] ?? '';

        // Prepare ticket data - priority/assignment from SLA Weight in TicketService
        $ticketData = [
            'title' => trim($_POST['title'] ?? ''),
            'type' => $ticketType,
            'ticket_type' => $ticketType,
            'category' => $_POST['category'] ?? '',
            'description' => trim($_POST['description'] ?? ''),
            'user_type' => $userType,
            'urgency' => isset($_POST['is_urgent']) && $_POST['is_urgent'] == '1' ? 'urgent' : 'normal',
            'attachments' => $this->handleFileUpload(),
            'is_urgent' => isset($_POST['is_urgent']) && $_POST['is_urgent'] == '1' ? '1' : '0'
        ];

        // Validate required fields
        if (empty($ticketData['title']) || empty($ticketData['description'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Title and description are required']);
            return;
        }

        $result = $this->ticketService->create($ticketData, $userId, $userRole);

        if ($result['success']) {
            http_response_code(201);
            echo json_encode($result);
        } else {
            http_response_code(500);
            echo json_encode($result);
        }
    }

    /**
     * Get ticket by reference ID
     */
    public function getByReference(string $referenceId): void
    {
        header('Content-Type: application/json');

        if (!$this->authService->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $ticket = $this->ticketService->getByReference($referenceId);

        if ($ticket) {
            echo json_encode([
                'success' => true,
                'ticket' => $ticket
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        }
    }

    /**
     * Get tickets with filters
     */
    public function list(): void
    {
        header('Content-Type: application/json');

        if (!$this->authService->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $filters = [
            'user_id' => $_GET['user_id'] ?? $this->authService->getUserId(),
            'status' => $_GET['status'] ?? null,
            'priority' => $_GET['priority'] ?? null,
            'search' => $_GET['q'] ?? null
        ];

        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = max(10, min(50, intval($_GET['page_size'] ?? 10)));

        $result = $this->ticketService->getTickets($filters, $page, $perPage);

        echo json_encode([
            'success' => true,
            'data' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages']
            ]
        ]);
    }

    /**
     * Resolve ticket
     */
    public function resolve(string $referenceId): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        if (!$this->authService->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $ticket = $this->ticketService->getByReference($referenceId);
        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ticket not found']);
            return;
        }

        $userId = $this->authService->getUserId();
        $userRole = $this->authService->getUserRole() ?? 'user';
        $resolutionNotes = trim($_POST['resolution_notes'] ?? '');

        $result = $this->ticketService->resolve($ticket['ticket_id'], $userId, $userRole, $resolutionNotes);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Ticket resolved successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to resolve ticket']);
        }
    }

    /**
     * Calculate priority from POST data
     */
    private function calculatePriority(array $postData): string
    {
        if (isset($postData['is_urgent']) && $postData['is_urgent'] == '1') {
            return 'urgent';
        }

        // Check category routing for priority boost
        // This would query tbl_department_routing
        // For now, default to low
        return $postData['priority'] ?? 'low';
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload(): ?string
    {
        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = time() . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", basename($_FILES['attachment']['name']));
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
            return $targetFile;
        }

        return null;
    }
}
