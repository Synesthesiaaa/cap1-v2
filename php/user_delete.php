<?php
/**
 * Delete/Deactivate User API for User Management
 *
 * Sets user status to 'inactive' (soft delete) rather than removing from DB
 */

require_once 'db.php';
require_once 'check_um_access.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!checkUMAccess()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied. Admin only.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST ?: $_GET;
$userId = intval($input['user_id'] ?? $input['id'] ?? 0);

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Prevent admin from deactivating themselves
if ($userId == ($_SESSION['id'] ?? 0)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cannot deactivate your own account']);
    exit;
}

// Soft delete: set status to inactive
$stmt = $conn->prepare("UPDATE tbl_user SET status = 'inactive' WHERE user_id = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    $affected = $conn->affected_rows;
    $stmt->close();
    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'User deactivated']);
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
} else {
    $error = $conn->error;
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to deactivate user: ' . $error]);
}
