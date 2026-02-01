<?php
/**
 * Create tbl_user_ticket_summary and populate for fast customer list/count.
 * Run once: php archive/migrations/add_user_ticket_summary.php
 * Refresh anytime: re-run this script to repopulate.
 */

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/php/db.php';

$conn->query("CREATE TABLE IF NOT EXISTS tbl_user_ticket_summary (
    user_id INT(11) NOT NULL PRIMARY KEY,
    ticket_count INT(11) NOT NULL DEFAULT 0,
    last_contact DATETIME NULL,
    success_rate DECIMAL(5,2) NULL,
    sla_status VARCHAR(20) NULL,
    current_ticket_status VARCHAR(20) NULL,
    urgent_high_count INT(11) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Created tbl_user_ticket_summary\n";

$conn->query("TRUNCATE TABLE tbl_user_ticket_summary");
echo "Truncated (refresh)\n";

$sql = "
INSERT INTO tbl_user_ticket_summary (user_id, ticket_count, last_contact, success_rate, sla_status, current_ticket_status, urgent_high_count)
SELECT
    u.user_id,
    COALESCE(agg.ticket_count, 0),
    agg.last_contact,
    agg.success_rate,
    agg.sla_status,
    latest.current_ticket_status,
    COALESCE(agg.urgent_high_count, 0)
FROM tbl_user u
LEFT JOIN (
    SELECT user_id, COUNT(*) AS ticket_count, MAX(created_at) AS last_contact,
        ROUND((SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 1) AS success_rate,
        CASE WHEN SUM(CASE WHEN sla_date < CURDATE() AND status != 'complete' THEN 1 ELSE 0 END) > 0 THEN 'At Risk' ELSE 'On Track' END AS sla_status,
        SUM(CASE WHEN priority IN ('urgent','high') THEN 1 ELSE 0 END) AS urgent_high_count
    FROM tbl_ticket
    GROUP BY user_id
) agg ON u.user_id = agg.user_id
LEFT JOIN (
    SELECT user_id, status AS current_ticket_status FROM (
        SELECT user_id, status, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC) AS rn FROM tbl_ticket
    ) x WHERE rn = 1
) latest ON u.user_id = latest.user_id
";
$conn->query($sql);
$affected = $conn->affected_rows;
echo "Populated $affected rows (this may take 1-2 min with 500k tickets).\n";
echo "Done.\n";
