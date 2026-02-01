<?php
/**
 * Clean database and seed sample data for sampling/demo.
 *
 * 1. Truncates all tbl_* tables (cleans previous entries).
 * 2. Seeds: departments, users (internal + external), technicians, department heads,
 *    SLA weights, sample tickets, replies, and logs.
 *
 * Run from project root:
 *   php archive/migrations/clean_and_seed_sample.php
 * Or via browser: /archive/migrations/clean_and_seed_sample.php (use with caution)
 */

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/php/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function say($msg) {
    global $isCli;
    echo $msg . ($isCli ? "\n" : "<br>\n");
}

try {
    $conn->begin_transaction();

    // ----- 1. Disable FK checks and truncate all tbl_* tables -----
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    $dbName = defined('DB_NAME') ? DB_NAME : ($_ENV['DB_NAME'] ?? 'ts_isc');
    $res = $conn->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($dbName) . "' AND TABLE_NAME LIKE 'tbl_%' ORDER BY TABLE_NAME");
    $tables = [];
    while ($row = $res->fetch_assoc()) {
        $tables[] = $row['TABLE_NAME'];
    }

    foreach ($tables as $t) {
        $conn->query("TRUNCATE TABLE `" . $conn->real_escape_string($t) . "`");
        say("Truncated: $t");
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    say("");

    // ----- 2. Seed departments -----
    $departments = ['IT', 'Finance', 'HR', 'Customer', 'Facilities', 'Engineering', 'Warehouse', 'Production', 'Sales', 'Shipping'];
    $stmtDept = $conn->prepare("INSERT INTO tbl_department (department_name) VALUES (?)");
    $deptIds = [];
    foreach ($departments as $i => $name) {
        $stmtDept->bind_param("s", $name);
        $stmtDept->execute();
        $deptIds[$name] = $conn->insert_id;
    }
    $stmtDept->close();
    say("Seeded " . count($departments) . " departments.");

    // ----- 3. Seed users (internal: admin, evaluator, department_head; external) -----
    $defaultPassword = password_hash('Sample123', PASSWORD_DEFAULT);
    $users = [
        ['internal', $deptIds['IT'], 'Admin User', 'ACME Corp', 'admin@sample.com', $defaultPassword, 'active', 'admin', '+1-555-001'],
        ['internal', $deptIds['IT'], 'Evaluator One', null, 'evaluator@sample.com', $defaultPassword, 'active', 'evaluator', null],
        ['internal', $deptIds['IT'], 'Dept Head IT', null, 'depthead.it@sample.com', $defaultPassword, 'active', 'department_head', null],
        ['internal', $deptIds['Finance'], 'Dept Head Finance', null, 'depthead.fin@sample.com', $defaultPassword, 'active', 'department_head', null],
        ['internal', $deptIds['HR'], 'HR Staff', null, 'hr@sample.com', $defaultPassword, 'active', 'evaluator', null],
        ['external', $deptIds['Customer'], 'Alice Customer', 'Alice Co', 'alice@customer.com', $defaultPassword, 'active', 'user', '+1-555-101'],
        ['external', $deptIds['Customer'], 'Bob Customer', 'Bob Inc', 'bob@customer.com', $defaultPassword, 'active', 'user', '+1-555-102'],
        ['external', $deptIds['Customer'], 'Carol External', 'Carol Ltd', 'carol@customer.com', $defaultPassword, 'active', 'user', null],
        ['external', $deptIds['Customer'], 'Dave User', 'Dave LLC', 'dave@customer.com', $defaultPassword, 'active', 'user', '+1-555-104'],
        ['external', $deptIds['Customer'], 'Eve Requester', 'Eve Corp', 'eve@customer.com', $defaultPassword, 'active', 'user', null],
    ];
    $stmtUser = $conn->prepare("INSERT INTO tbl_user (user_type, department_id, name, company, email, password, status, user_role, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $userIds = [];
    foreach ($users as $u) {
        $stmtUser->bind_param("sisssssss", $u[0], $u[1], $u[2], $u[3], $u[4], $u[5], $u[6], $u[7], $u[8]);
        $stmtUser->execute();
        $userIds[] = $conn->insert_id;
    }
    $stmtUser->close();
    say("Seeded " . count($users) . " users.");

    // ----- 4. Seed technicians -----
    $techs = [
        ['Tech Anna', 'tech.anna@sample.com', $defaultPassword, 'active', 'software', 0],
        ['Tech Ben', 'tech.ben@sample.com', $defaultPassword, 'active', 'hardware', 0],
        ['Tech Chris', 'tech.chris@sample.com', $defaultPassword, 'active', 'operation', 0],
    ];
    $stmtTech = $conn->prepare("INSERT INTO tbl_technician (name, email, password, status, specialization, active_tickets) VALUES (?, ?, ?, ?, ?, ?)");
    $techIds = [];
    foreach ($techs as $t) {
        $stmtTech->bind_param("sssssi", $t[0], $t[1], $t[2], $t[3], $t[4], $t[5]);
        $stmtTech->execute();
        $techIds[] = $conn->insert_id;
    }
    $stmtTech->close();
    say("Seeded " . count($techs) . " technicians.");

    // ----- 5. Department heads (link users to departments) -----
    $deptHeadUserIds = [$userIds[2], $userIds[3]]; // Dept Head IT, Dept Head Finance
    $deptHeadDeptIds = [$deptIds['IT'], $deptIds['Finance']];
    if ($conn->query("SHOW TABLES LIKE 'tbl_department_head'")->num_rows > 0) {
        $stmtDH = $conn->prepare("INSERT INTO tbl_department_head (user_id, department_id) VALUES (?, ?)");
        for ($i = 0; $i < count($deptHeadUserIds); $i++) {
            $stmtDH->bind_param("ii", $deptHeadUserIds[$i], $deptHeadDeptIds[$i]);
            $stmtDH->execute();
        }
        $stmtDH->close();
        say("Seeded department heads.");
    }

    // ----- 6. SLA weights (same as migrate_sla_weight) -----
    $slaSeed = [
        ['System access', 'IT', 10, 8], ['Network or router troubleshooting', 'IT', 7, 8], ['Hardware or software installation', 'IT', 2, 2],
        ['Malfunctioning PCs or peripherals', 'IT', 3, 6], ['Email configuration errors', 'IT', 3, 8], ['Coordination with other departments', 'IT', 2, 4],
        ['System Audit', 'IT', 2, 2], ['Maintenance', 'IT', 2, 2], ['ERP entry errors', 'Finance', 6, 9], ['Billing or reconciliation disputes', 'Finance', 8, 8],
        ['payment verification issues', 'Finance', 3, 8], ['report generation errors', 'Finance', 2, 5], ['Financial data sync issues', 'Finance', 1, 5],
        ['Warranty validation errors', 'Engineering', 3, 5], ['Delayed ticket for servicing items', 'Engineering', 5, 2], ['Product serial verification', 'Engineering', 2, 3],
        ['Approval for replacement items', 'Engineering', 5, 5], ['Onboarding or offboarding system access', 'HR', 2, 3], ['Employee account creation', 'HR', 2, 3],
        ['Password recovery', 'HR', 2, 7], ['Attendance record discrepancies', 'HR', 2, 3], ['Inventory record inconsistencies', 'Warehouse', 3, 5],
        ['Missing stock entries', 'Warehouse', 2, 3], ['Damaged item reports', 'Warehouse', 8, 5], ['Delayed shipment arrivals', 'Warehouse', 3, 5],
        ['Batch tagging errors', 'Production', 8, 8], ['System synchronization lag', 'Production', 2, 1], ['Staff scheduling module malfunction', 'Production', 2, 2],
        ['Equipment maintenance', 'Production', 2, 2], ['Customer inquiry updates', 'Sales', 1, 1], ['Warranty record assistance', 'Sales', 4, 4],
        ['System generated report errors', 'Sales', 3, 5], ['Customer profile updates', 'Sales', 2, 2], ['Wrong delivery or Update issues', 'Shipping', 9, 10],
        ['Missing items', 'Shipping', 8, 8], ['Delivery confirmation requests', 'Shipping', 2, 2], ['Logistics coordination', 'Shipping', 2, 2],
        ['Furniture', 'Facilities', 1, 1], ['Lighting', 'Facilities', 3, 5], ['Plumbing', 'Facilities', 5, 8], ['Airconditioning', 'Facilities', 5, 5],
        ['Renovation', 'Facilities', 3, 6], ['Electrical', 'Facilities', 7, 8],
        ['Other', 'IT', 1, 1], ['Other', 'Finance', 1, 1], ['Other', 'Engineering', 1, 1], ['Other', 'HR', 1, 1], ['Other', 'Warehouse', 1, 1],
        ['Other', 'Production', 1, 1], ['Other', 'Sales', 1, 1], ['Other', 'Shipping', 1, 1], ['Other', 'Facilities', 1, 1],
    ];
    $stmtSLA = $conn->prepare("INSERT INTO tbl_sla_weight (category, department_name, time_value, importance) VALUES (?, ?, ?, ?)");
    foreach ($slaSeed as $row) {
        $stmtSLA->bind_param("ssii", $row[0], $row[1], $row[2], $row[3]);
        $stmtSLA->execute();
    }
    $stmtSLA->close();
    say("Seeded " . count($slaSeed) . " SLA weight entries.");

    // ----- 7. Sample tickets -----
    $externalUserIds = array_slice($userIds, 5, 5); // Alice, Bob, Carol, Dave, Eve
    $refBase = 'TKT-' . date('Ymd') . '-';
    $ticketRows = [
        [$externalUserIds[0], 'Cannot login to email', 'internal', 'Email configuration errors', 'high', 'urgent', 'Email keeps asking for password.', $techIds[0], date('Y-m-d', strtotime('+3 days')), 'pending'],
        [$externalUserIds[1], 'PC not turning on', 'internal', 'Malfunctioning PCs or peripherals', 'regular', 'normal', 'Desktop does not power on.', $techIds[1], date('Y-m-d', strtotime('+5 days')), 'assigning'],
        [$externalUserIds[2], 'Billing dispute for invoice #1001', 'internal', 'Billing or reconciliation disputes', 'high', 'normal', 'Duplicate charge on last invoice.', $techIds[2], date('Y-m-d', strtotime('+7 days')), 'followup'],
        [$externalUserIds[0], 'Password reset request', 'internal', 'Password recovery', 'regular', 'normal', 'Need to reset my HR portal password.', null, date('Y-m-d', strtotime('+2 days')), 'pending'],
        [$externalUserIds[3], 'Network slow in building B', 'internal', 'Network or router troubleshooting', 'regular', 'normal', 'WiFi very slow since yesterday.', $techIds[0], date('Y-m-d', strtotime('+4 days')), 'complete'],
    ];
    $stmtTicket = $conn->prepare("INSERT INTO tbl_ticket (reference_id, user_id, title, type, category, priority, urgency, description, attachments, assigned_technician_id, sla_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?, ?)");
    $ticketIds = [];
    foreach ($ticketRows as $idx => $row) {
        $ref = $refBase . strtoupper(substr(uniqid(), -6));
        $assignee = $row[7];
        $stmtTicket->bind_param("sissssssiss", $ref, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $assignee, $row[8], $row[9]);
        $stmtTicket->execute();
        $ticketIds[] = $conn->insert_id;
    }
    $stmtTicket->close();
    say("Seeded " . count($ticketRows) . " tickets.");

    // ----- 8. Sample ticket replies -----
    $stmtReply = $conn->prepare("INSERT INTO tbl_ticket_reply (ticket_id, replied_by, replier_id, reply_text, created_at) VALUES (?, ?, ?, ?, ?)");
    $replies = [
        [$ticketIds[0], 'technician', $techIds[0], 'We have reset your email password. Please try logging in again.', date('Y-m-d H:i:s', strtotime('-1 hour'))],
        [$ticketIds[1], 'technician', $techIds[1], 'Checking power supply and will update you shortly.', date('Y-m-d H:i:s', strtotime('-2 hours'))],
        [$ticketIds[2], 'user', $externalUserIds[2], 'I have attached the duplicate invoice copy.', date('Y-m-d H:i:s', strtotime('-3 hours'))],
    ];
    foreach ($replies as $r) {
        $stmtReply->bind_param("isiss", $r[0], $r[1], $r[2], $r[3], $r[4]);
        $stmtReply->execute();
    }
    $stmtReply->close();
    say("Seeded " . count($replies) . " ticket replies.");

    // ----- 9. Sample ticket logs -----
    if ($conn->query("SHOW TABLES LIKE 'tbl_ticket_logs'")->num_rows > 0) {
        $stmtLog = $conn->prepare("INSERT INTO tbl_ticket_logs (ticket_id, user_id, user_role, action_type, action_details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, '', '', NOW())");
        $logs = [
            [$ticketIds[0], $techIds[0], 'technician', 'reply', 'Replied to ticket'],
            [$ticketIds[1], $techIds[1], 'technician', 'assign', 'Ticket assigned to technician'],
            [$ticketIds[4], $techIds[0], 'technician', 'resolve', 'Ticket marked complete'],
        ];
        foreach ($logs as $l) {
            $stmtLog->bind_param("iisss", $l[0], $l[1], $l[2], $l[3], $l[4]);
            $stmtLog->execute();
        }
        $stmtLog->close();
        say("Seeded " . count($logs) . " ticket logs.");
    }

    $conn->commit();
    say("");
    say("Done. Database cleaned and sample data seeded.");
    say("Sample login: admin@sample.com / Sample123 (admin), tech.anna@sample.com / Sample123 (technician), alice@customer.com / Sample123 (external).");
} catch (Exception $e) {
    $conn->rollback();
    say("Error: " . $e->getMessage());
    exit(1);
}
