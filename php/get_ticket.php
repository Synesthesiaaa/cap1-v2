<?php
/**
 * Get Ticket - Migrated to use new architecture
 * 
 * This endpoint now uses TicketService while maintaining backward compatibility
 */

// Try to use new structure if available
$useNewStructure = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap.php';
    if (class_exists('Services\TicketService')) {
        $useNewStructure = true;
    }
}

// Fallback to old structure
if (!$useNewStructure) {
    include("db.php");
}

session_start();

$ref = $_GET['ref'] ?? '';
if (!$ref) {
    echo json_encode(['success' => false]);
    exit();
}

// Check if it's a technician or customer session
$user_id = $_SESSION['id'] ?? null;
$user_type = $_SESSION['user_type'] ?? 'technician';
$user_role = $_SESSION['role'] ?? 'user';

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit();
}

// Use new structure if available
if ($useNewStructure) {
    try {
        $ticketService = new \Services\TicketService();
        $ticket = $ticketService->getByReference($ref);
        
        if ($ticket) {
            // Check access permissions
            $hasAccess = false;
            
            if ($user_role === 'technician') {
                $filter = $_GET['filter'] ?? 'mine';
                if ($filter === 'mine') {
                    $hasAccess = ($ticket['assigned_technician_id'] == $user_id);
                } elseif ($filter === 'unassigned') {
                    $hasAccess = (empty($ticket['assigned_technician_id']) || $ticket['assigned_technician_id'] == 0);
                } else {
                    $hasAccess = true; // All tickets for technicians
                }
            } else {
                // Customer access - can only see their own tickets
                $hasAccess = ($ticket['user_id'] == $user_id);
            }
            
            if ($hasAccess) {
                // Get technician name if assigned
                if ($ticket['assigned_technician_id']) {
                    $conn = \Database\Connection::getInstance()->getConnection();
                    $techSql = "SELECT name FROM tbl_technician WHERE technician_id = ?";
                    $techStmt = $conn->prepare($techSql);
                    if ($techStmt) {
                        $techStmt->bind_param("i", $ticket['assigned_technician_id']);
                        $techStmt->execute();
                        $techResult = $techStmt->get_result();
                        if ($techRow = $techResult->fetch_assoc()) {
                            $ticket['technician_name'] = $techRow['name'];
                        }
                        $techStmt->close();
                    }
                }
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'ticket' => $ticket
                ]);
                exit();
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                exit();
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ticket not found']);
            exit();
        }
    } catch (\Exception $e) {
        // Fall through to old implementation
        $useNewStructure = false;
    }
}

if ($user_type !== 'technician') {
    // Customer access - can only see their own tickets
    $sql = "SELECT t.*, tech.name as technician_name
            FROM tbl_ticket t
            LEFT JOIN tbl_technician tech ON t.assigned_technician_id = tech.technician_id
            WHERE t.reference_id = ? AND t.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $ref, $user_id);
} else {
    // Technician access
    $filter = $_GET['filter'] ?? 'mine';
    $whereClause = "t.reference_id = ?";

    if ($filter === 'mine') {
        $whereClause .= " AND t.assigned_technician_id = ?";
        $stmt = $conn->prepare("SELECT t.*, tech.name as technician_name
                                FROM tbl_ticket t
                                LEFT JOIN tbl_technician tech ON t.assigned_technician_id = tech.technician_id
                                WHERE $whereClause");
        $stmt->bind_param("si", $ref, $user_id);
    } elseif ($filter === 'unassigned') {
        $stmt = $conn->prepare("SELECT t.*, tech.name as technician_name
                                FROM tbl_ticket t
                                LEFT JOIN tbl_technician tech ON t.assigned_technician_id = tech.technician_id
                                WHERE t.reference_id = ? AND (t.assigned_technician_id IS NULL OR t.assigned_technician_id = 0)");
        $stmt->bind_param("s", $ref);
    } else {
        $stmt = $conn->prepare("SELECT t.*, tech.name as technician_name
                                FROM tbl_ticket t
                                LEFT JOIN tbl_technician tech ON t.assigned_technician_id = tech.technician_id
                                WHERE t.reference_id = ?");
        $stmt->bind_param("s", $ref);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();

if ($ticket) {
    $response = [
        'success' => true,
        'ticket' => $ticket
    ];
} else {
    $response = ['success' => false];
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
