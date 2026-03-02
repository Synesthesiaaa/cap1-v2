<?php
/**
 * Backfill SLA metadata for open tickets.
 *
 * Usage:
 *   php php/backfill_sla_open_tickets.php --dry-run --batch-size=1000 --max-batches=5 --start-ticket-id=0
 */

require_once 'db.php';
require_once __DIR__ . '/../config/sla_automation_rules.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'CLI only']);
    exit;
}

$opts = getopt('', ['dry-run', 'batch-size::', 'max-batches::', 'start-ticket-id::']);
$dryRun = isset($opts['dry-run']);
$batchSize = isset($opts['batch-size']) ? max(1, (int)$opts['batch-size']) : 1000;
$maxBatches = isset($opts['max-batches']) ? max(1, (int)$opts['max-batches']) : null;
$startTicketId = isset($opts['start-ticket-id']) ? max(0, (int)$opts['start-ticket-id']) : 0;

$summary = [
    'ok' => true,
    'dry_run' => $dryRun,
    'batch_size' => $batchSize,
    'max_batches' => $maxBatches,
    'start_ticket_id' => $startTicketId,
    'batches' => 0,
    'scanned' => 0,
    'matched' => 0,
    'updated' => 0,
    'skipped' => 0,
    'unmatched' => 0,
    'errors' => 0,
    'last_ticket_id' => $startTicketId,
    'unmatched_examples' => [],
];

$slaRows = $conn->query("SELECT sla_weight_id, category, department_name, time_value, importance FROM tbl_sla_weight");
if (!$slaRows) {
    fwrite(STDERR, "Failed to load SLA rows: {$conn->error}\n");
    exit(1);
}

$slaMap = [];
while ($row = $slaRows->fetch_assoc()) {
    $key = strtolower(trim((string)$row['department_name'])) . '|' . strtolower(trim((string)$row['category']));
    $slaMap[$key] = $row;
}
$slaRows->free();

if (empty($slaMap)) {
    fwrite(STDERR, "No SLA rows found in tbl_sla_weight.\n");
    exit(1);
}

$select = $conn->prepare("
    SELECT
        t.ticket_id,
        t.type,
        t.category,
        t.priority,
        t.urgency,
        t.sla_date,
        t.sla_weight_id,
        t.sla_priority_score,
        COALESCE(u.user_type, 'internal') AS user_type
    FROM tbl_ticket t
    LEFT JOIN tbl_user u ON u.user_id = t.user_id
    WHERE t.status <> 'complete'
      AND t.ticket_id > ?
    ORDER BY t.ticket_id ASC
    LIMIT ?
");

if (!$select) {
    fwrite(STDERR, "Failed to prepare select statement: {$conn->error}\n");
    exit(1);
}

$update = $conn->prepare("
    UPDATE tbl_ticket
    SET
        sla_weight_id = ?,
        sla_priority_score = ?,
        priority = ?,
        urgency = ?,
        sla_date = IF(sla_date IS NULL OR sla_date = '0000-00-00', ?, sla_date)
    WHERE ticket_id = ?
");

if (!$update) {
    fwrite(STDERR, "Failed to prepare update statement: {$conn->error}\n");
    exit(1);
}

$unmatchedPairs = [];
$lastId = $startTicketId;

while (true) {
    if ($maxBatches !== null && $summary['batches'] >= $maxBatches) {
        break;
    }

    $select->bind_param("ii", $lastId, $batchSize);
    $select->execute();
    $rows = $select->get_result()->fetch_all(MYSQLI_ASSOC);
    if (empty($rows)) {
        break;
    }

    $summary['batches']++;

    foreach ($rows as $ticket) {
        $ticketId = (int)$ticket['ticket_id'];
        $lastId = $ticketId;
        $summary['last_ticket_id'] = $ticketId;
        $summary['scanned']++;

        $type = trim((string)$ticket['type']);
        $normalizedCategory = \slaNormalizeCategory((string)$ticket['category']);
        $lookupKey = strtolower($type) . '|' . strtolower($normalizedCategory);

        if (!isset($slaMap[$lookupKey])) {
            $summary['unmatched']++;
            $pair = $type . ' | ' . $normalizedCategory;
            $unmatchedPairs[$pair] = ($unmatchedPairs[$pair] ?? 0) + 1;
            continue;
        }

        $summary['matched']++;
        $sla = $slaMap[$lookupKey];
        $score = \slaComputePriorityScore((int)$sla['time_value'], (int)$sla['importance'], (string)$ticket['user_type']);
        $priority = \slaMapScoreToPriority($score);
        $urgency = \slaPriorityToUrgency($priority);
        $slaDateForNull = date('Y-m-d', strtotime('+' . \slaPrioritySlaDays($priority) . ' days'));

        $existingWeightId = isset($ticket['sla_weight_id']) ? (int)$ticket['sla_weight_id'] : 0;
        $existingScore = $ticket['sla_priority_score'] !== null ? round((float)$ticket['sla_priority_score'], 2) : null;
        $existingPriority = \slaNormalizePriority((string)($ticket['priority'] ?? 'low'));
        $existingUrgency = strtolower(trim((string)($ticket['urgency'] ?? 'low')));
        if (!in_array($existingUrgency, ['low', 'medium', 'high', 'urgent'], true)) {
            $existingUrgency = 'low';
        }
        $missingSlaDate = ($ticket['sla_date'] === null || $ticket['sla_date'] === '0000-00-00');

        $needsUpdate = false;
        if ($existingWeightId !== (int)$sla['sla_weight_id']) {
            $needsUpdate = true;
        } elseif ($existingScore === null || abs($existingScore - $score) > 0.0001) {
            $needsUpdate = true;
        } elseif ($existingPriority !== $priority) {
            $needsUpdate = true;
        } elseif ($existingUrgency !== $urgency) {
            $needsUpdate = true;
        } elseif ($missingSlaDate) {
            $needsUpdate = true;
        }

        if (!$needsUpdate) {
            $summary['skipped']++;
            continue;
        }

        if ($dryRun) {
            $summary['updated']++;
            continue;
        }

        $slaWeightId = (int)$sla['sla_weight_id'];
        $update->bind_param("idsssi", $slaWeightId, $score, $priority, $urgency, $slaDateForNull, $ticketId);
        if ($update->execute()) {
            $summary['updated']++;
        } else {
            $summary['errors']++;
        }
    }
}

$select->close();
$update->close();

if (!empty($unmatchedPairs)) {
    arsort($unmatchedPairs);
    $summary['unmatched_examples'] = array_slice($unmatchedPairs, 0, 20, true);
    foreach ($summary['unmatched_examples'] as $pair => $count) {
        error_log("[backfill_sla_open_tickets] unmatched SLA mapping: {$pair} x{$count}");
    }
}

if ($summary['errors'] > 0) {
    $summary['ok'] = false;
}

echo "Backfill SLA open tickets completed\n";
echo "  Dry run: " . ($dryRun ? 'yes' : 'no') . "\n";
echo "  Batches: {$summary['batches']}\n";
echo "  Scanned: {$summary['scanned']}\n";
echo "  Matched: {$summary['matched']}\n";
echo "  Updated: {$summary['updated']}\n";
echo "  Skipped: {$summary['skipped']}\n";
echo "  Unmatched: {$summary['unmatched']}\n";
echo "  Errors: {$summary['errors']}\n";
echo "  Last ticket id: {$summary['last_ticket_id']}\n";
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

$conn->close();
exit($summary['ok'] ? 0 : 1);

