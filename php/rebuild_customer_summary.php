<?php
/**
 * Rebuild customer summary cache in bulk.
 *
 * Usage:
 *   php php/rebuild_customer_summary.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "This script can only run from CLI.\n";
    exit(1);
}

require_once __DIR__ . '/db.php';

echo "[1/4] Normalizing legacy blank ticket statuses...\n";
$cleanupSql = "UPDATE tbl_ticket SET status = 'unassigned' WHERE status = '' OR status IS NULL";
if (!$conn->query($cleanupSql)) {
    fwrite(STDERR, "Status cleanup failed: {$conn->error}\n");
    exit(1);
}
$normalized = $conn->affected_rows;
echo "  - Updated {$normalized} ticket rows\n";

echo "[2/4] Ensuring summary table exists...\n";
$createSql = "
CREATE TABLE IF NOT EXISTS tbl_user_ticket_summary (
  user_id INT(11) NOT NULL PRIMARY KEY,
  ticket_count INT(11) NOT NULL DEFAULT 0,
  last_contact DATETIME NULL,
  success_rate DECIMAL(5,2) NULL,
  sla_status VARCHAR(20) NULL,
  current_ticket_status VARCHAR(20) NULL,
  urgent_high_count INT(11) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_current_ticket_status (current_ticket_status),
  KEY idx_sla_status (sla_status),
  KEY idx_last_contact (last_contact),
  KEY idx_ticket_urgent (ticket_count, urgent_high_count),
  KEY idx_success_rate (success_rate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
if (!$conn->query($createSql)) {
    fwrite(STDERR, "Create summary table failed: {$conn->error}\n");
    exit(1);
}

echo "[3/4] Rebuilding summary cache...\n";
$conn->begin_transaction();
try {
    if (!$conn->query("TRUNCATE TABLE tbl_user_ticket_summary")) {
        throw new RuntimeException('Truncate failed: ' . $conn->error);
    }

    $insertSql = "
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
            COALESCE(agg.sla_status, 'No Open Tickets') AS sla_status,
            latest.current_ticket_status,
            COALESCE(agg.urgent_high_count, 0) AS urgent_high_count
        FROM tbl_user u
        LEFT JOIN (
            SELECT
                t.user_id,
                COUNT(*) AS ticket_count,
                MAX(t.created_at) AS last_contact,
                ROUND((SUM(CASE WHEN t.status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 1) AS success_rate,
                CASE
                    WHEN SUM(CASE WHEN COALESCE(NULLIF(t.status, ''), 'unassigned') IN ('unassigned','assigning','pending','followup') AND t.sla_date < CURDATE() THEN 1 ELSE 0 END) > 0 THEN 'At Risk'
                    WHEN SUM(CASE WHEN COALESCE(NULLIF(t.status, ''), 'unassigned') IN ('unassigned','assigning','pending','followup') AND t.sla_date >= CURDATE() AND t.sla_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) > 0 THEN 'Approaching'
                    WHEN SUM(CASE WHEN COALESCE(NULLIF(t.status, ''), 'unassigned') IN ('unassigned','assigning','pending','followup') THEN 1 ELSE 0 END) > 0 THEN 'On Track'
                    ELSE 'No Open Tickets'
                END AS sla_status,
                SUM(CASE WHEN t.priority IN ('urgent', 'high') THEN 1 ELSE 0 END) AS urgent_high_count
            FROM tbl_ticket t
            GROUP BY t.user_id
        ) agg ON agg.user_id = u.user_id
        LEFT JOIN (
            SELECT x.user_id, x.current_ticket_status
            FROM (
                SELECT
                    t.user_id,
                    COALESCE(NULLIF(t.status, ''), 'unassigned') AS current_ticket_status,
                    ROW_NUMBER() OVER (PARTITION BY t.user_id ORDER BY t.created_at DESC, t.ticket_id DESC) AS rn
                FROM tbl_ticket t
            ) x
            WHERE x.rn = 1
        ) latest ON latest.user_id = u.user_id
    ";

    if (!$conn->query($insertSql)) {
        throw new RuntimeException('Insert failed: ' . $conn->error);
    }
    $inserted = $conn->affected_rows;

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "Summary rebuild failed: {$e->getMessage()}\n");
    exit(1);
}

echo "  - Rebuilt {$inserted} summary rows\n";

echo "[4/4] Done.\n";
exit(0);

