<?php
/**
 * Stress-test seed: ~500k tickets + enough users for stress testing.
 *
 * Prerequisites: Run clean_and_seed_sample.php first (departments, technicians, SLA, base users).
 *
 * 1. Ensures enough external users (up to 50k); adds in batches if needed.
 * 2. Truncates ticket-related tables (logs, reply, escalation, checklist, product, comment, ticket).
 * 3. Inserts TICKET_TARGET tickets in batches (default 500,000).
 * 4. Optionally inserts 1 ticket_log per ticket and ~0.5 reply per ticket.
 *
 * Run from project root:
 *   php archive/migrations/seed_stress_500k.php [ticket_count]
 *   php archive/migrations/seed_stress_500k.php 500000
 *   php archive/migrations/seed_stress_500k.php 100000
 *
 * Use with caution: 500k inserts take several minutes and require sufficient memory/disk.
 *
 * Database: tbl_ticket.status enum should include: unassigned, assigning, pending, followup, complete.
 */

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/php/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$TICKET_TARGET = isset($argv[1]) ? max(1000, (int)$argv[1]) : 500000;
$USER_TARGET = 50000;   // external users to have (for ticket user_id distribution)
$BATCH_TICKETS = 2000;  // tickets per INSERT batch
$BATCH_USERS = 1000;    // users per INSERT batch
$BATCH_LOGS = 5000;     // logs per INSERT batch
$BATCH_REPLIES = 5000;  // replies per INSERT batch

$categories = [
    'Email configuration errors', 'Malfunctioning PCs or peripherals', 'Network or router troubleshooting',
    'Billing or reconciliation disputes', 'Password recovery', 'System access', 'Hardware or software installation',
    'ERP entry errors', 'Warranty validation errors', 'Other'
];
$types = ['IT', 'Finance', 'HR', 'internal'];
$priorities = ['low', 'regular', 'high', 'urgent'];
$urgencies = ['low', 'medium', 'high', 'urgent'];
$statuses = ['unassigned', 'assigning', 'pending', 'followup', 'complete'];
$titles = ['Login issue', 'PC not starting', 'Email setup', 'Network slow', 'Billing question', 'Password reset', 'Printer offline', 'VPN access', 'Report error', 'Data sync'];

function say($msg) {
    global $isCli;
    echo $msg . ($isCli ? "\n" : "<br>\n");
    if ($isCli) {
        @flush();
        @ob_flush();
    }
}

try {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // ----- 1. Ensure we have departments and technicians -----
    $r = $conn->query("SELECT department_id FROM tbl_department WHERE department_name = 'Customer' LIMIT 1");
    if ($r->num_rows === 0) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        say("Run clean_and_seed_sample.php first to create departments and technicians.");
        exit(1);
    }
    $customerDeptId = (int)$r->fetch_assoc()['department_id'];
    $r->close();

    $techRes = $conn->query("SELECT technician_id FROM tbl_technician WHERE status = 'active'");
    $techIds = [];
    while ($row = $techRes->fetch_assoc()) {
        $techIds[] = (int)$row['technician_id'];
    }
    $techRes->close();
    if (empty($techIds)) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        say("No active technicians. Run clean_and_seed_sample.php first.");
        exit(1);
    }

    // ----- 2. Count external users; add up to USER_TARGET in batches -----
    $userCountRes = $conn->query("SELECT COUNT(*) AS c FROM tbl_user WHERE user_type = 'external'");
    $externalCount = (int)$userCountRes->fetch_assoc()['c'];
    $userCountRes->close();

    $defaultPassword = password_hash('Sample123', PASSWORD_DEFAULT);
    $toAdd = max(0, $USER_TARGET - $externalCount);
    if ($toAdd > 0) {
        say("Adding $toAdd external users in batches of $BATCH_USERS...");
        $stmtUser = $conn->prepare("INSERT INTO tbl_user (user_type, department_id, name, company, email, password, status, user_role, phone) VALUES ('external', ?, ?, ?, ?, ?, 'active', 'customer', NULL)");
        $inserted = 0;
        for ($b = 0; $b < $toAdd; $b += $BATCH_USERS) {
            $batchSize = min($BATCH_USERS, $toAdd - $b);
            for ($i = 0; $i < $batchSize; $i++) {
                $n = $inserted + $i + 1;
                $name = "Stress User " . $n;
                $company = "Company " . ($n % 1000);
                $email = "stress.user." . $n . "@stress.local";
                $stmtUser->bind_param("issss", $customerDeptId, $name, $company, $email, $defaultPassword);
                $stmtUser->execute();
            }
            $inserted += $batchSize;
            if ($inserted % 10000 === 0 || $inserted === $toAdd) {
                say("  Users: $inserted / $toAdd");
            }
        }
        $stmtUser->close();
        say("Total external users now: " . ($externalCount + $inserted));
    }

    // Get user_id range for external users (for random assignment)
    $userRange = $conn->query("SELECT MIN(user_id) AS mn, MAX(user_id) AS mx FROM tbl_user WHERE user_type = 'external'");
    $ur = $userRange->fetch_assoc();
    $userMin = (int)$ur['mn'];
    $userMax = (int)$ur['mx'];
    $userRange->close();

    // ----- 3. Truncate ticket-related tables (child first) -----
    $ticketTables = ['tbl_ticket_logs', 'tbl_ticket_reply', 'tbl_ticket_escalation', 'tbl_ticket_checklist', 'tbl_ticket_product', 'tbl_ticket_comment', 'tbl_ticket'];
    foreach ($ticketTables as $t) {
        $conn->query("TRUNCATE TABLE `$t`");
        say("Truncated: $t");
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    say("");

    // ----- 4. Insert tickets in batches (multi-row INSERT with escaped values) -----
    say("Inserting $TICKET_TARGET tickets in batches of $BATCH_TICKETS...");
    $startTime = microtime(true);
    $inserted = 0;
    $globalSeq = 0; // unique reference_id sequence

    while ($inserted < $TICKET_TARGET) {
        $batchSize = min($BATCH_TICKETS, $TICKET_TARGET - $inserted);
        $rows = [];
        for ($i = 0; $i < $batchSize; $i++) {
            $globalSeq++;
            $ref = 'TKT-' . date('Ymd') . '-' . str_pad((string)$globalSeq, 8, '0', STR_PAD_LEFT);
            $userId = $userMin + mt_rand(0, max(0, $userMax - $userMin));
            $title = $titles[array_rand($titles)] . ' #' . $globalSeq;
            $type = $types[array_rand($types)];
            $category = $categories[array_rand($categories)];
            $priority = $priorities[array_rand($priorities)];
            $urgency = $urgencies[array_rand($urgencies)];
            $desc = "Stress test ticket $globalSeq. " . substr(str_repeat('Description text. ', 5), 0, 100);
            $techId = (mt_rand(0, 3) === 0) ? 'NULL' : $techIds[array_rand($techIds)];
            $status = $statuses[array_rand($statuses)];
            $daysAgo = mt_rand(0, 730);
            $created = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));
            $slaDate = date('Y-m-d', strtotime($created . ' +' . mt_rand(1, 14) . ' days'));

            $ref = $conn->real_escape_string($ref);
            $title = $conn->real_escape_string($title);
            $type = $conn->real_escape_string($type);
            $category = $conn->real_escape_string($category);
            $priority = $conn->real_escape_string($priority);
            $urgency = $conn->real_escape_string($urgency);
            $desc = $conn->real_escape_string($desc);
            $status = $conn->real_escape_string($status);
            $rows[] = "('$ref',$userId,'$title','$type','$category','$priority','$urgency','$desc','',$techId,'$slaDate','$status','$created')";
        }
        $sql = "INSERT INTO tbl_ticket (reference_id, user_id, title, type, category, priority, urgency, description, attachments, assigned_technician_id, sla_date, status, created_at) VALUES " . implode(',', $rows);
        $conn->query($sql);
        $firstTicketId = $conn->insert_id;
        $inserted += $batchSize;

        // ----- 5. One log per ticket (same batch) -----
        $actions = ['create', 'assign', 'reply', 'update', 'escalate'];
        $roles = ['user', 'technician', 'system'];
        $logRows = [];
        for ($k = 0; $k < $batchSize; $k++) {
            $tid = $firstTicketId + $k;
            $action = $actions[array_rand($actions)];
            $role = $roles[array_rand($roles)];
            $details = $conn->real_escape_string("Stress log for ticket $tid");
            $logRows[] = "($tid,0,'$role','$action','$details','','',NOW())";
        }
        $conn->query("INSERT INTO tbl_ticket_logs (ticket_id, user_id, user_role, action_type, action_details, ip_address, user_agent, created_at) VALUES " . implode(',', $logRows));

        if ($inserted % 10000 === 0 || $inserted === $TICKET_TARGET) {
            $elapsed = round(microtime(true) - $startTime, 1);
            say("  Tickets: $inserted / $TICKET_TARGET (${elapsed}s)");
        }
    }

    $ticketElapsed = round(microtime(true) - $startTime, 1);
    say("Inserted $TICKET_TARGET tickets + $TICKET_TARGET logs in {$ticketElapsed}s.");
    say("");

    // ----- 6. Optional: ~0.5 reply per ticket (sample by ticket_id range) -----
    $replyCount = (int)($TICKET_TARGET * 0.5);
    if ($replyCount > 0) {
        say("Inserting ~$replyCount ticket replies in batches of $BATCH_REPLIES...");
        $replyStart = microtime(true);
        $insertedReplies = 0;
        $replyBy = ['user', 'technician', 'system'];
        // Get ticket_id range (min/max) to pick random tickets without loading all IDs
        $rangeRes = $conn->query("SELECT MIN(ticket_id) AS mn, MAX(ticket_id) AS mx FROM tbl_ticket");
        $range = $rangeRes->fetch_assoc();
        $rangeRes->close();
        $tidMin = (int)$range['mn'];
        $tidMax = (int)$range['mx'];
        for ($b = 0; $b < $replyCount; $b += $BATCH_REPLIES) {
            $batchSize = min($BATCH_REPLIES, $replyCount - $b);
            $rows = [];
            for ($i = 0; $i < $batchSize; $i++) {
                $tid = $tidMin + mt_rand(0, max(0, $tidMax - $tidMin));
                $by = $replyBy[array_rand($replyBy)];
                $replierId = ($by === 'system') ? 0 : (mt_rand(0, 1) ? $userMin + mt_rand(0, min(100, $userMax - $userMin)) : $techIds[array_rand($techIds)]);
                $text = $conn->real_escape_string("Stress reply " . ($insertedReplies + $i + 1));
                $rows[] = "($tid,'$by',$replierId,'$text',NULL,NOW())";
            }
            $conn->query("INSERT INTO tbl_ticket_reply (ticket_id, replied_by, replier_id, reply_text, attachment_path, created_at) VALUES " . implode(',', $rows));
            $insertedReplies += $batchSize;
            if ($insertedReplies % 50000 === 0 || $insertedReplies >= $replyCount) {
                say("  Replies: $insertedReplies");
            }
        }
        say("Inserted $insertedReplies replies in " . round(microtime(true) - $replyStart, 1) . "s.");
    }

    $totalTime = round(microtime(true) - $startTime, 1);
    say("");
    say("Done. Total time: ${totalTime}s.");
    say("Approximate row counts: tickets=$TICKET_TARGET, logs=$TICKET_TARGET, replies=" . (isset($insertedReplies) ? $insertedReplies : 0) . ".");
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    say("Error: " . $e->getMessage());
    exit(1);
}
