<?php
/**
 * Customer summary refresh helpers.
 *
 * Keeps tbl_user_ticket_summary in sync for a specific user after ticket mutations.
 */

if (!function_exists('refreshUserTicketSummary')) {
    /**
     * Recompute and upsert summary metrics for one user.
     */
    function refreshUserTicketSummary(int $userId, ?mysqli $conn = null): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $localConn = $conn;
        if (!$localConn) {
            require_once __DIR__ . '/db.php';
            if (!isset($conn) || !($conn instanceof mysqli)) {
                return false;
            }
            $localConn = $conn;
        }

        $sql = "
            INSERT INTO tbl_user_ticket_summary (
                user_id,
                ticket_count,
                last_contact,
                success_rate,
                sla_status,
                current_ticket_status,
                urgent_high_count
            )
            SELECT
                u.user_id,
                COALESCE(agg.ticket_count, 0) AS ticket_count,
                agg.last_contact,
                agg.success_rate,
                agg.sla_status,
                (
                    SELECT t2.status
                    FROM tbl_ticket t2
                    WHERE t2.user_id = u.user_id
                    ORDER BY t2.created_at DESC, t2.ticket_id DESC
                    LIMIT 1
                ) AS current_ticket_status,
                COALESCE(agg.urgent_high_count, 0) AS urgent_high_count
            FROM tbl_user u
            LEFT JOIN (
                SELECT
                    t.user_id,
                    COUNT(*) AS ticket_count,
                    MAX(t.created_at) AS last_contact,
                    ROUND(
                        (SUM(CASE WHEN t.status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100,
                        1
                    ) AS success_rate,
                    CASE
                        WHEN SUM(CASE WHEN t.sla_date < CURDATE() AND t.status != 'complete' THEN 1 ELSE 0 END) > 0 THEN 'At Risk'
                        WHEN SUM(CASE WHEN t.sla_date >= CURDATE() AND t.sla_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND t.status != 'complete' THEN 1 ELSE 0 END) > 0 THEN 'Approaching'
                        ELSE 'On Track'
                    END AS sla_status,
                    SUM(CASE WHEN t.priority IN ('urgent', 'high') THEN 1 ELSE 0 END) AS urgent_high_count
                FROM tbl_ticket t
                WHERE t.user_id = ?
                GROUP BY t.user_id
            ) agg ON agg.user_id = u.user_id
            WHERE u.user_id = ?
            ON DUPLICATE KEY UPDATE
                ticket_count = VALUES(ticket_count),
                last_contact = VALUES(last_contact),
                success_rate = VALUES(success_rate),
                sla_status = VALUES(sla_status),
                current_ticket_status = VALUES(current_ticket_status),
                urgent_high_count = VALUES(urgent_high_count),
                updated_at = CURRENT_TIMESTAMP
        ";

        $stmt = $localConn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $userId, $userId);
        $ok = $stmt->execute();
        $stmt->close();

        return (bool)$ok;
    }
}

if (!function_exists('refreshTicketSummaryByTicketId')) {
    /**
     * Refresh summary by ticket_id.
     */
    function refreshTicketSummaryByTicketId(int $ticketId, ?mysqli $conn = null): bool
    {
        if ($ticketId <= 0) {
            return false;
        }

        $localConn = $conn;
        if (!$localConn) {
            require_once __DIR__ . '/db.php';
            if (!isset($conn) || !($conn instanceof mysqli)) {
                return false;
            }
            $localConn = $conn;
        }

        $stmt = $localConn->prepare("SELECT user_id FROM tbl_ticket WHERE ticket_id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !isset($row['user_id'])) {
            return false;
        }

        return refreshUserTicketSummary((int)$row['user_id'], $localConn);
    }
}

if (!function_exists('refreshTicketSummaryByReference')) {
    /**
     * Refresh summary by ticket reference_id.
     */
    function refreshTicketSummaryByReference(string $referenceId, ?mysqli $conn = null): bool
    {
        $referenceId = trim($referenceId);
        if ($referenceId === '') {
            return false;
        }

        $localConn = $conn;
        if (!$localConn) {
            require_once __DIR__ . '/db.php';
            if (!isset($conn) || !($conn instanceof mysqli)) {
                return false;
            }
            $localConn = $conn;
        }

        $stmt = $localConn->prepare("SELECT ticket_id FROM tbl_ticket WHERE reference_id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $referenceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !isset($row['ticket_id'])) {
            return false;
        }

        return refreshTicketSummaryByTicketId((int)$row['ticket_id'], $localConn);
    }
}

