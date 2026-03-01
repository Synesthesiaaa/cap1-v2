<?php
include('db.php');
require_once 'customer_summary_refresh.php';
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error'=>'unauth']);
    exit();
}

$ref = $_POST['ref'] ?? '';
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$priority = $_POST['priority'] ?? '';
$category = $_POST['category'] ?? '';

if (!$ref || !$title || !$description) {
    http_response_code(400);
    echo json_encode(['error'=>'missing required fields']);
    exit();
}

// Update ticket
$update = $conn->prepare("UPDATE tbl_ticket SET title = ?, description = ?, priority = ?, category = ?, updated_at = NOW() WHERE reference_id = ?");
$update->bind_param("sssss", $title, $description, $priority, $category, $ref);
if ($update->execute()) {
    refreshTicketSummaryByReference((string)$ref, $conn);
    echo json_encode(['ok' => true, 'message' => 'Ticket updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => $update->error]);
}
?>
