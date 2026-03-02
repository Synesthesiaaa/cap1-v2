<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$claimId = (int)($_GET['claim_id'] ?? 0);
$ticketId = (int)($_GET['ticket_id'] ?? 0);

if ($claimId <= 0 && $ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Provide claim_id or ticket_id']);
    exit;
}

if ($claimId > 0) {
    $stmt = $conn->prepare("
        SELECT *
        FROM tbl_warranty_claim
        WHERE claim_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $claimId);
} else {
    $stmt = $conn->prepare("
        SELECT *
        FROM tbl_warranty_claim
        WHERE ticket_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $ticketId);
}

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Prepare failed']);
    exit;
}

$stmt->execute();
$res = $stmt->get_result();
$claims = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($claimId > 0 && empty($claims)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Claim not found']);
    exit;
}

if ($claimId > 0) {
    $claim = $claims[0];
    $histStmt = $conn->prepare("
        SELECT *
        FROM tbl_warranty_claim_history
        WHERE claim_id = ?
        ORDER BY created_at ASC, history_id ASC
    ");
    $history = [];
    if ($histStmt) {
        $histStmt->bind_param("i", $claimId);
        $histStmt->execute();
        $history = $histStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $histStmt->close();
    }

    echo json_encode([
        'ok' => true,
        'claim' => $claim,
        'history' => $history
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'claims' => $claims
]);

