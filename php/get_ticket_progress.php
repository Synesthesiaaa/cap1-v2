<?php
require_once "db.php";
require_once "checklist_common.php";
header("Content-Type: application/json");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Unauthorized"]);
    exit;
}
$actorId = (int)($_SESSION['id'] ?? 0);
$role = (string)($_SESSION['role'] ?? '');

$refsRaw = trim($_GET['refs'] ?? '');
if ($refsRaw === '') {
    echo json_encode(["ok" => false, "error" => "Missing refs"]);
    exit;
}

$refs = array_values(array_filter(array_map('trim', explode(',', $refsRaw))));
$refs = array_slice($refs, 0, 200);
if (empty($refs)) {
    echo json_encode(["ok" => true, "data" => []]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($refs), '?'));
$types = str_repeat('s', count($refs));
$params = $refs;
$extraWhere = '';

if ($role === 'user') {
    $extraWhere = " AND t.user_id = ?";
    $types .= 'i';
    $params[] = $actorId;
}

$sql = "
    SELECT t.reference_id, t.ticket_id, t.status
    FROM tbl_ticket t
    WHERE t.reference_id IN ($placeholders)
    $extraWhere
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["ok" => false, "error" => "Prepare failed"]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$ticketResult = $stmt->get_result();
$stmt->close();

$ticketMap = [];
while ($row = $ticketResult->fetch_assoc()) {
    $ticketMap[$row['reference_id']] = [
        'ticket_id' => (int)$row['ticket_id'],
        'status' => (string)($row['status'] ?? ''),
    ];
}

$data = [];
foreach ($refs as $ref) {
    $ticketMeta = $ticketMap[$ref] ?? null;
    if (!is_array($ticketMeta)) {
        $data[$ref] = [
            'completed' => 0,
            'total' => 0,
            'checklist_percent' => 0,
            'percent' => 0,
            'status_label' => 'Unavailable',
            'stage_key' => 'unknown',
            'summary' => 'Ticket progress is unavailable.',
            'remaining_items' => 0,
        ];
        continue;
    }

    $ticketId = (int)$ticketMeta['ticket_id'];
    $items = checklistFetchItems($conn, $ticketId);
    $checklistProgress = checklistComputeProgress($items);
    $ticketProgress = checklistComputeTicketProgress((string)$ticketMeta['status'], $checklistProgress);

    $data[$ref] = [
        'completed' => (int)($checklistProgress['completed'] ?? 0),
        'total' => (int)($checklistProgress['total'] ?? 0),
        'checklist_percent' => (int)($checklistProgress['percent'] ?? 0),
        'percent' => (int)($ticketProgress['percent'] ?? 0),
        'status_label' => (string)($ticketProgress['status_label'] ?? 'Open'),
        'stage_key' => (string)($ticketProgress['stage_key'] ?? 'open'),
        'summary' => (string)($ticketProgress['summary'] ?? ''),
        'remaining_items' => (int)($ticketProgress['remaining_items'] ?? 0),
    ];
}

echo json_encode([
    "ok" => true,
    "data" => $data
]);

$conn->close();
