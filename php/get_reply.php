<?php
include("db.php");
session_start();
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Check if user is authenticated
if (!isset($_SESSION['id'])) {
    echo json_encode(["ok" => false, "error" => "Unauthorized"]);
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$ref = $_GET['ref'] ?? '';
error_log("Reference ID from URL: '" . $ref . "'");

// Ensure that the reference is provided
if (empty($ref)) {
    echo json_encode(["ok" => false, "error" => "No reference ID provided"]);
    exit();
}

// Fetch ticket_id using reference
$stmt = $conn->prepare("SELECT ticket_id FROM tbl_ticket WHERE reference_id = ?");
if ($stmt === false) {
    error_log("MySQL prepare error: " . $conn->error); 
    echo json_encode(["ok" => false, "error" => "Database preparation failed at ticket ID fetch"]);
    exit();
}
$stmt->bind_param("s", $ref);
if (!$stmt->execute()) {
    echo json_encode(["ok" => false, "error" => "Database execution failed"]);
    exit();
}
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["ok" => false, "error" => "Ticket not found"]);
    exit();
}

$ticket = $res->fetch_assoc();
$ticket_id = $ticket['ticket_id'];
$stmt->close();

// Fetch replies (both user and technician)
$sql = "
    SELECT 
        r.reply_id,
        r.reply_text AS message,
        r.replied_by,
        r.attachment_path,
        r.created_at,
        u.name AS user_name,
        tech.name AS technician_name
    FROM tbl_ticket_reply r
    LEFT JOIN tbl_user u ON r.replied_by = 'user'
    LEFT JOIN tbl_technician tech ON r.replied_by = 'technician'
    WHERE r.ticket_id = ?
    ORDER BY r.created_at ASC
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(["ok" => false, "error" => "Database preparation failed: SQL Connection error"]);
    exit();
}
$stmt->bind_param("i", $ticket_id);
if (!$stmt->execute()) {
    echo json_encode(["ok" => false, "error" => "Database execution failed: Ticket ID not found"]);
    exit();
}
$result = $stmt->get_result();

$replies = [];
while ($row = $result->fetch_assoc()) {
    $sender = "System"; 
    if ($row['replied_by'] === "user") {
        $sender = $row['user_name'] ?: "User";
    } elseif ($row['replied_by'] === "technician") {
        $sender = $row['technician_name'] ?: "Technician";
    }

    // Explicit date/time handling for time zone
    $datetime = new DateTime($row["created_at"]);
    $datetime->setTimezone(new DateTimeZone('UTC')); 
    $formatted_date = $datetime->format("Y-m-d H:i");

    $replies[] = [
        "reply_id" => $row["reply_id"],
        "message" => $row["message"],
        "replied_by" => $row["replied_by"],
        "sender" => $sender,
        "attachment_path" => $row["attachment_path"], // This was added in the last edit
        "created_at" => $formatted_date
    ];
}

$stmt->close();
$conn->close();

echo json_encode(["ok" => true, "replies" => $replies]);
?>
