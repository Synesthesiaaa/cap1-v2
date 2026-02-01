<?php
/**
 * Unlink Product from Ticket
 */

require_once 'db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ticket_product_id = intval($data['ticket_product_id'] ?? 0);

if ($ticket_product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ticket product ID']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM tbl_ticket_product WHERE ticket_product_id = ?");
    $stmt->bind_param("i", $ticket_product_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Unlink product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
