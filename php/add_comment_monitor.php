<?php
require_once("db.php");
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['id'])) {
    echo json_encode(["ok" => false, "error" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role']; // 'technician', 'user', 'admin'
$is_technician = ($user_role === 'technician') ? 1 : 0;

$ref = $_POST['ref'] ?? '';
$comment = trim($_POST['comment'] ?? '');

if ($comment === "") {
    echo json_encode(["ok" => false, "error" => "Empty comment"]);
    exit;
}

// Fetch ticket ID
$stmt = $conn->prepare("SELECT ticket_id FROM tbl_ticket WHERE reference_id = ?");
$stmt->bind_param("s", $ref);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    echo json_encode(["ok" => false, "error" => "Ticket not found"]);
    exit;
}

$ticket_id = $ticket['ticket_id'];

// Insert comment
$stmt = $conn->prepare("
    INSERT INTO tbl_ticket_comment (ticket_id, commenter_id, is_technician, role, comment_text)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iiiss", $ticket_id, $user_id, $is_technician, $user_role, $comment);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(["ok" => $ok]);
$conn->close();
?>
