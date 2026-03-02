<?php
require_once __DIR__ . '/ticket_api_common.php';
require_once __DIR__ . '/ticket_log_helper.php';

$auth = ticketApiRequireAuth();

$ref = (string)($_POST['ref'] ?? '');
$reply = trim((string)($_POST['reply'] ?? ''));
$attachment = $_FILES['replyAttachment'] ?? null;

if ($ref === '') {
    ticketApiJson(['ok' => false, 'error' => 'Missing ticket reference'], 400);
}
if ($reply === '' && (!$attachment || ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
    ticketApiJson(['ok' => false, 'error' => 'Reply cannot be empty'], 400);
}

$ticket = ticketApiResolveTicketByRef($conn, $ref);
ticketApiAuthorizeTicketAccess($ticket, 'reply_ticket', $auth);
$ticketId = (int)$ticket['ticket_id'];

$attachmentPath = '';
if ($attachment && ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (($attachment['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        ticketApiJson(['ok' => false, 'error' => 'Attachment upload failed'], 400);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array((string)$attachment['type'], $allowedTypes, true)) {
        ticketApiJson(['ok' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, PDF'], 400);
    }
    if ((int)$attachment['size'] > (50 * 1024 * 1024)) {
        ticketApiJson(['ok' => false, 'error' => 'File too large (max 50MB)'], 400);
    }

    $uploadDir = __DIR__ . '/../uploads/replies/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        ticketApiJson(['ok' => false, 'error' => 'Failed to create upload directory'], 500);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string)$attachment['name']);
    $fileName = time() . '_' . $safeName;
    $diskPath = $uploadDir . $fileName;

    if (!move_uploaded_file((string)$attachment['tmp_name'], $diskPath)) {
        ticketApiJson(['ok' => false, 'error' => 'Failed to upload file'], 500);
    }

    $attachmentPath = 'uploads/replies/' . $fileName;
}

$repliedBy = $auth['role'] === 'technician' ? 'technician' : 'user';
$stmt = $conn->prepare("
    INSERT INTO tbl_ticket_reply (ticket_id, replied_by, replier_id, reply_text, attachment_path, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
if (!$stmt) {
    ticketApiJson(['ok' => false, 'error' => 'Database prepare failed'], 500);
}
$stmt->bind_param('isiss', $ticketId, $repliedBy, $auth['user_id'], $reply, $attachmentPath);
$ok = $stmt->execute();
$replyId = $ok ? (int)$conn->insert_id : 0;
$stmt->close();

if (!$ok) {
    ticketApiJson(['ok' => false, 'error' => 'Failed to save reply'], 500);
}

if (function_exists('insertTicketLog')) {
    $excerpt = $reply !== '' ? substr($reply, 0, 100) : '[Attachment only]';
    insertTicketLog($ticketId, $auth['user_id'], $auth['role'], 'reply', 'Added reply: ' . $excerpt, $conn);
}

$fetch = $conn->prepare("
    SELECT r.reply_id, r.reply_text AS message, r.replied_by, r.attachment_path, r.created_at,
           CASE r.replied_by
               WHEN 'user' THEN u.name
               WHEN 'technician' THEN COALESCE(tech.name, 'Support Agent')
               ELSE 'System'
           END AS sender
    FROM tbl_ticket_reply r
    LEFT JOIN tbl_user u ON r.replied_by = 'user' AND r.replier_id = u.user_id
    LEFT JOIN tbl_technician tech ON r.replied_by = 'technician' AND r.replier_id = tech.technician_id
    WHERE r.reply_id = ?
    LIMIT 1
");
if (!$fetch) {
    ticketApiJson(['ok' => true, 'reply' => null]);
}
$fetch->bind_param('i', $replyId);
$fetch->execute();
$replyData = $fetch->get_result()->fetch_assoc();
$fetch->close();

$conn->close();
ticketApiJson([
    'ok' => true,
    'reply' => $replyData,
]);
