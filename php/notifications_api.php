<?php
/**
 * Notifications API
 * 
 * Can be included in other PHP files to access createNotification().
 * Routing (HTTP handler) only runs when this file is the primary entry point.
 *
 * Actions (when called directly via HTTP):
 *   GET  ?action=get_unread         - returns unread notification count and latest items
 *   GET  ?action=get_all            - returns paginated notification list
 *   POST ?action=mark_read          - mark a single notification as read (body: notification_id)
 *   POST ?action=mark_all_read      - mark all notifications for this user as read
 */

// Ensure db connection and session are available
if (!isset($conn)) {
    include(__DIR__ . "/db.php");
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only run the HTTP routing when this file is the primary script (not when included)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $sessionUserId = (int)$_SESSION['id'];
    $sessionRole   = (string)($_SESSION['role'] ?? 'user');

    // Determine recipient type and ID
    // Technicians are stored in tbl_technician; all other roles live in tbl_user
    if ($sessionRole === 'technician') {
        $recipientType = 'technician';
        $recipientId   = (int)($_SESSION['technician_id'] ?? $sessionUserId);
    } else {
        $recipientType = 'user';
        $recipientId   = $sessionUserId;
    }

    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $action = trim($_GET['action'] ?? '');

    try {
        switch ($action) {
            case 'get_unread':
                getUnread($conn, $recipientId, $recipientType);
                break;

            case 'get_all':
                $page  = max(1, (int)($_GET['page'] ?? 1));
                $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
                getAll($conn, $recipientId, $recipientType, $page, $limit);
                break;

            case 'mark_read':
                if ($method !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method Not Allowed']);
                    exit();
                }
                $notifId = (int)(json_decode(file_get_contents('php://input'), true)['notification_id'] ?? 0);
                markRead($conn, $recipientId, $recipientType, $notifId);
                break;

            case 'mark_all_read':
                if ($method !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method Not Allowed']);
                    exit();
                }
                markAllRead($conn, $recipientId, $recipientType);
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                exit();
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log("Notifications API Error: " . $e->getMessage());
        echo json_encode(['error' => 'Server error occurred']);
    } catch (Throwable $e) {
        http_response_code(500);
        error_log("Notifications API Fatal: " . $e->getMessage());
        echo json_encode(['error' => 'Server error occurred']);
    }
}


// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Return the unread count and the most recent unread notifications (up to 10).
 */
function getUnread(mysqli $conn, int $recipientId, string $recipientType): void
{
    // Count
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt
         FROM tbl_notification
         WHERE recipient_id = ? AND recipient_type = ? AND is_read = 0"
    );
    $stmt->bind_param('is', $recipientId, $recipientType);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // Latest items
    $stmt = $conn->prepare(
        "SELECT notification_id, type, title, message, link, created_at
         FROM tbl_notification
         WHERE recipient_id = ? AND recipient_type = ? AND is_read = 0
         ORDER BY created_at DESC
         LIMIT 10"
    );
    $stmt->bind_param('is', $recipientId, $recipientType);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['count' => $count, 'notifications' => $rows]);
}

/**
 * Return a paginated list of all notifications (read + unread).
 */
function getAll(mysqli $conn, int $recipientId, string $recipientType, int $page, int $limit): void
{
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare(
        "SELECT notification_id, type, title, message, is_read, link, created_at
         FROM tbl_notification
         WHERE recipient_id = ? AND recipient_type = ?
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('isii', $recipientId, $recipientType, $limit, $offset);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Total count for pagination
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt
         FROM tbl_notification
         WHERE recipient_id = ? AND recipient_type = ?"
    );
    $stmt->bind_param('is', $recipientId, $recipientType);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    echo json_encode([
        'notifications' => $rows,
        'total'         => $total,
        'page'          => $page,
        'limit'         => $limit,
        'has_more'      => ($offset + count($rows)) < $total
    ]);
}

/**
 * Mark a single notification as read (must belong to this recipient).
 */
function markRead(mysqli $conn, int $recipientId, string $recipientType, int $notifId): void
{
    if ($notifId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid notification_id']);
        exit();
    }

    $stmt = $conn->prepare(
        "UPDATE tbl_notification
         SET is_read = 1
         WHERE notification_id = ? AND recipient_id = ? AND recipient_type = ?"
    );
    $stmt->bind_param('iis', $notifId, $recipientId, $recipientType);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode(['ok' => $affected > 0]);
}

/**
 * Mark all notifications for this recipient as read.
 */
function markAllRead(mysqli $conn, int $recipientId, string $recipientType): void
{
    $stmt = $conn->prepare(
        "UPDATE tbl_notification
         SET is_read = 1
         WHERE recipient_id = ? AND recipient_type = ? AND is_read = 0"
    );
    $stmt->bind_param('is', $recipientId, $recipientType);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode(['ok' => true, 'marked' => $affected]);
}

/**
 * Helper: create a notification record. Called by other PHP files (e.g. post_reply.php).
 * Returns the new notification_id or 0 on failure.
 */
function createNotification(
    mysqli $conn,
    int    $recipientId,
    string $recipientType,
    string $type,
    string $title,
    string $message,
    string $link = ''
): int {
    $stmt = $conn->prepare(
        "INSERT INTO tbl_notification
             (recipient_id, recipient_type, type, title, message, link)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        error_log("createNotification prepare failed: " . $conn->error);
        return 0;
    }
    $stmt->bind_param('isssss', $recipientId, $recipientType, $type, $title, $message, $link);
    $ok = $stmt->execute();
    $id = $ok ? (int)$conn->insert_id : 0;
    $stmt->close();
    return $id;
}
