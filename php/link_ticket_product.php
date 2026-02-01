<?php
/**
 * Link Product to Ticket
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

$ticket_id = intval($data['ticket_id'] ?? 0);
$customer_product_id = isset($data['customer_product_id']) && !empty($data['customer_product_id']) ? intval($data['customer_product_id']) : null;
$action_type = $data['action_type'] ?? 'repair';
$notes = trim($data['notes'] ?? '');

if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ticket ID']);
    exit;
}

try {
    $conn->begin_transaction();
    
    $product_id = null;
    
    // If customer_product_id provided, get product_id from it
    if ($customer_product_id) {
        $stmt = $conn->prepare("SELECT product_id FROM tbl_customer_product WHERE customer_product_id = ?");
        $stmt->bind_param("i", $customer_product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $product_id = $row['product_id'];
        }
        $stmt->close();
    }
    
    // If new product info provided, create product and customer_product
    if (!$product_id && !empty($data['product_name'])) {
        $product_name = trim($data['product_name']);
        $product_model = trim($data['product_model'] ?? '');
        $product_serial = trim($data['product_serial'] ?? '');
        
        // Create product
        $stmt = $conn->prepare("INSERT INTO tbl_product (name, model, serial_number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $product_name, $product_model, $product_serial);
        $stmt->execute();
        $product_id = $conn->insert_id;
        $stmt->close();
        
        // Get ticket user_id
        $stmt = $conn->prepare("SELECT user_id FROM tbl_ticket WHERE ticket_id = ?");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $ticket_result = $stmt->get_result();
        $ticket_row = $ticket_result->fetch_assoc();
        $user_id = $ticket_row['user_id'];
        $stmt->close();
        
        // Create customer_product
        $warranty_start = !empty($data['warranty_start']) ? $data['warranty_start'] : null;
        $warranty_end = !empty($data['warranty_end']) ? $data['warranty_end'] : null;
        
        $stmt = $conn->prepare("INSERT INTO tbl_customer_product (user_id, product_id, warranty_start, warranty_end) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $product_id, $warranty_start, $warranty_end);
        $stmt->execute();
        $customer_product_id = $conn->insert_id;
        $stmt->close();
    }
    
    if (!$product_id) {
        throw new Exception('Product ID is required');
    }
    
    // Link ticket to product
    $stmt = $conn->prepare("INSERT INTO tbl_ticket_product (ticket_id, customer_product_id, product_id, action_type, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $ticket_id, $customer_product_id, $product_id, $action_type, $notes);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Link product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
