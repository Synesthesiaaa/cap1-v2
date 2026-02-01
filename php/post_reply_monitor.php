<?php
/**
 * post_reply_monitor.php
 * Handles technician replies and logs the action.
 */

// Try to use new structure if available
$useNewStructure = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap.php';
    if (class_exists('Services\LogService')) {
        $useNewStructure = true;
    }
}

// Fallback to old structure
if (!$useNewStructure) {
    require_once("db.php");
    if (file_exists("insert_log_monitor.php")) {
        require_once("insert_log_monitor.php");
    }
}

session_start();
header("Content-Type: application/json");

// --- DEBUG SETTINGS ---
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// --- DEBUG LOGGING ---
error_log("=== [post_reply_monitor.php] Script started ===");
error_log("Session: " . print_r($_SESSION, true));
error_log("POST: " . print_r($_POST, true));
error_log("FILES: " . print_r($_FILES, true));

// --- GET SESSION INFO ---
$user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'system';

// --- SECURITY CHECK ---
if (!$user_id) {
    error_log("[post_reply_monitor] ❌ Unauthorized: Missing session user_id");
    echo json_encode(["ok" => false, "error" => "Unauthorized - missing user session"]);
    exit;
}

// --- INPUT VALIDATION ---
$ref = $_POST['ref'] ?? '';
$reply = trim($_POST['reply'] ?? '');

if (empty($ref)) {
    echo json_encode(["ok" => false, "error" => "Missing reference ID"]);
    exit;
}

if ($reply === "" && (!isset($_FILES['reply_attachment']) || empty($_FILES['reply_attachment']['name']))) {
    echo json_encode(["ok" => false, "error" => "Empty reply"]);
    exit;
}

// --- FETCH TICKET ---
$stmt = $conn->prepare("SELECT ticket_id FROM tbl_ticket WHERE reference_id = ?");
$stmt->bind_param("s", $ref);
$stmt->execute();
$res = $stmt->get_result();
$ticket = $res->fetch_assoc();
$stmt->close();

if (!$ticket) {
    echo json_encode(["ok" => false, "error" => "Ticket not found"]);
    exit;
}

$ticket_id = $ticket['ticket_id'];

// --- HANDLE FILE UPLOAD ---
$attachment_path = null;
if (isset($_FILES['reply_attachment']) && $_FILES['reply_attachment']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = "../uploads/replies/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename = time() . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", basename($_FILES["reply_attachment"]["name"]));
    $targetFile = $uploadDir . $filename;

    $allowedTypes = [
        'image/jpeg', 'image/png', 'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/rtf'
    ];

    $fileType = mime_content_type($_FILES['reply_attachment']['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(["ok" => false, "error" => "Invalid file type"]);
        exit;
    }

    $maxFileSize = 25 * 1024 * 1024; // 25MB
    if ($_FILES['reply_attachment']['size'] > $maxFileSize) {
        echo json_encode(["ok" => false, "error" => "File is too large"]);
        exit;
    }

    if (move_uploaded_file($_FILES["reply_attachment"]["tmp_name"], $targetFile)) {
        $attachment_path = $targetFile;
    } else {
        echo json_encode(["ok" => false, "error" => "Failed to upload attachment"]);
        exit;
    }
}

// --- DETERMINE WHO REPLIED ---
$replied_by = ($user_role === 'technician') ? 'technician' :
              (($user_role === 'admin') ? 'system' : 'user');

// --- INSERT REPLY ---
$insert = $conn->prepare("
    INSERT INTO tbl_ticket_reply (ticket_id, replied_by, replier_id, reply_text, attachment_path, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$insert->bind_param("isiss", $ticket_id, $replied_by, $user_id, $reply, $attachment_path);
$ok = $insert->execute();
$insert->close();

if (!$ok) {
    error_log("[post_reply_monitor] Failed to insert reply: " . $conn->error);
    echo json_encode(["ok" => false, "error" => $conn->error]);
    exit;
}

// --- CREATE LOG ENTRY ---
$short_reply = mb_substr($reply, 0, 50);
if (mb_strlen($reply) > 50) $short_reply .= "...";
$attachment_note = $attachment_path ? " (with attachment)" : "";

$roleLabel = ucfirst($replied_by);  // technician / user / system
$userID = intval($user_id);
$short_reply_safe = addslashes($short_reply);

// FINAL Log message
$action_type = "reply";
$action_details = "$roleLabel ID $userID replied: \"$short_reply_safe\"" . $attachment_note;

// --- INSERT LOG ---
$log_ok = false;
if ($useNewStructure) {
    try {
        $logService = new \Services\LogService();
        $logService->logTicketAction($ticket_id, $user_id, $user_role, $action_type, $action_details);
        $log_ok = true;
    } catch (\Exception $e) {
        error_log("[post_reply_monitor] LogService failed: " . $e->getMessage());
        // Fallback to old method
        if (function_exists('insertTicketLog')) {
            $log_ok = insertTicketLog($ticket_id, $user_id, $user_role, $action_type, $action_details, $conn);
        }
    }
} elseif (function_exists('insertTicketLog')) {
    $log_ok = insertTicketLog($ticket_id, $user_id, $user_role, $action_type, $action_details, $conn);
}

if ($log_ok) {
    error_log("[post_reply_monitor] Log inserted for Ticket ID $ticket_id by User $user_id");
} else {
    error_log("[post_reply_monitor] Log insert failed for Ticket ID $ticket_id");
}

// --- SUCCESS RESPONSE ---
echo json_encode(["ok" => true, "attachment_path" => $attachment_path]);

error_log("=== [post_reply_monitor.php] Completed successfully ===");
$conn->close();
?>
