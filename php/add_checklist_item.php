<?php
require_once("db.php");
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['id'])) {
    echo json_encode(["ok" => false, "error" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];
$is_technician = ($user_role === 'technician') ? 1 : 0;

$ref = $_POST['ref'] ?? '';
$description = trim($_POST['description'] ?? '');

if ($description === "") {
    echo json_encode(["ok" => false, "error" => "Empty checklist item"]);
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

// Insert checklist item
$stmt = $conn->prepare("
    INSERT INTO tbl_ticket_checklist (ticket_id, created_by, is_technician, description)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiis", $ticket_id, $user_id, $is_technician, $description);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(["ok" => $ok]);
$conn->close();
?>
