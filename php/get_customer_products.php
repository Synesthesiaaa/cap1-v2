<?php
/**
 * Get Customer Products API
 * Returns product history for a customer
 */

require_once 'db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT cp.*, p.name as product_name, p.model, p.serial_number, p.category
        FROM tbl_customer_product cp
        LEFT JOIN tbl_product p ON cp.product_id = p.product_id
        WHERE cp.user_id = ?
        ORDER BY cp.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'products' => $products]);
    
} catch (Exception $e) {
    error_log("Get customer products error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
