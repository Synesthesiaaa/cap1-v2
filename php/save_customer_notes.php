<?php
/**
 * Save customer notes - requires notes column in tbl_user (run migrate_add_user_notes.php)
 */

require_once 'db.php';
require_once 'check_cm_access.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$accessLevel = checkCMAccess();
if ($accessLevel === 'denied' || $accessLevel === 'readonly') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Check if notes column exists
$r = @$conn->query("SHOW COLUMNS FROM tbl_user LIKE 'notes'");
if (!$r || $r->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Notes column not available. Run php/migrate_add_user_notes.php']);
    exit;
}

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE tbl_user SET notes = ? WHERE user_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

$stmt->bind_param("si", $notes, $user_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save notes']);
}
$stmt->close();
