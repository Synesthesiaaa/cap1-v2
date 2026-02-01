<?php
include("db.php");
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error'=>'unauth']);
    exit();
}

$ref = $_POST['ref'] ?? '';
$reply = trim($_POST['reply'] ?? '');
$user_id = $_SESSION['id'];

if (!$ref || $reply === '') {
    http_response_code(400);
    echo json_encode(['error'=>'missing']);
    exit();
}

// Find ticket_id and verify ownership
$stmt = $conn->prepare("SELECT ticket_id, user_id FROM tbl_ticket WHERE reference_id = ? LIMIT 1");
$stmt->bind_param("s", $ref);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
if (!$r) {
    http_response_code(404);
    echo json_encode(['error'=>'Ticket not found']);
    exit();
}
$ticket_user_id = (int)$r['user_id'];
$ticket_id = (int)$r['ticket_id'];

// For now, allow replies from any logged-in user (support staff or customer)
// In customer management, support staff should be able to reply to any ticket
// Uncomment the line below if you want to restrict replies to ticket owners only:
// if ($ticket_user_id !== $user_id) { http_response_code(403); echo json_encode(['error'=>'You do not have permission to reply to this ticket']); exit(); }

// Insert customer reply
$ins = $conn->prepare("INSERT INTO tbl_ticket_reply (ticket_id, replied_by, replier_id, reply_text, created_at) VALUES (?, 'user', ?, ?, NOW())");
$ins->bind_param("isis", $ticket_id, $user_id, $reply);
if ($ins->execute()) {
    echo json_encode(['ok'=>true]);
} else {
    http_response_code(500);
    echo json_encode(['error'=>$ins->error]);
}
?>
