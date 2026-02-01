<?php
require_once("db.php");
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['id'])) {
    echo json_encode(["ok" => false, "error" => "Unauthorized"]);
    exit;
}

$item_id = $_POST['item_id'] ?? 0;
$completed = $_POST['completed'] ?? 0;

$stmt = $conn->prepare("
    UPDATE tbl_ticket_checklist
    SET is_completed = ?, completed_at = IF(? = 1, NOW(), NULL)
    WHERE item_id = ?
");
$stmt->bind_param("iii", $completed, $completed, $item_id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(["ok" => $ok]);
$conn->close();
?>
