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
    $products = [];
    $seenKeys = [];

    // 1. Products from tbl_customer_product (customer's registered products)
    $stmt = $conn->prepare("
        SELECT cp.customer_product_id, cp.user_id, cp.product_id, cp.purchase_date, cp.warranty_start,
               cp.warranty_end, cp.status, cp.notes as product_notes, cp.created_at,
               p.name as product_name, p.model, p.serial_number, p.category, 'registered' as source
        FROM tbl_customer_product cp
        LEFT JOIN tbl_product p ON cp.product_id = p.product_id
        WHERE cp.user_id = ?
        ORDER BY cp.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $key = ($row['product_id'] ?? 0) . '-' . ($row['purchase_date'] ?? '');
        if (!isset($seenKeys[$key])) {
            $seenKeys[$key] = true;
            $products[] = $row;
        }
    }
    $stmt->close();

    // 2. Products from tbl_ticket_product (products linked to customer's tickets)
    $stmt2 = $conn->prepare("
        SELECT tp.ticket_product_id as customer_product_id, t.user_id, tp.product_id,
               NULL as purchase_date, cp.warranty_start, cp.warranty_end,
               COALESCE(cp.status, 'active') as status, tp.notes as product_notes,
               tp.created_at, p.name as product_name, p.model, p.serial_number, p.category,
               'ticket' as source
        FROM tbl_ticket_product tp
        INNER JOIN tbl_ticket t ON tp.ticket_id = t.ticket_id
        LEFT JOIN tbl_product p ON tp.product_id = p.product_id
        LEFT JOIN tbl_customer_product cp ON tp.customer_product_id = cp.customer_product_id
        WHERE t.user_id = ?
        ORDER BY tp.created_at DESC
    ");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $key = ($row['product_id'] ?? 0) . '-ticket-' . ($row['customer_product_id'] ?? 0);
        if (!isset($seenKeys[$key])) {
            $seenKeys[$key] = true;
            $products[] = $row;
        }
    }
    $stmt2->close();

    // Sort by created_at descending
    usort($products, function ($a, $b) {
        $da = strtotime($a['created_at'] ?? 0);
        $db = strtotime($b['created_at'] ?? 0);
        return $db - $da;
    });

    echo json_encode(['success' => true, 'products' => $products]);
    
} catch (Exception $e) {
    error_log("Get customer products error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
