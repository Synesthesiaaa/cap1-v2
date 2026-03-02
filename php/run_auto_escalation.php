<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'department_head'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

require_once 'db.php';
if (!defined('RUN_SLA_AUTOMATION_NO_AUTORUN')) {
    define('RUN_SLA_AUTOMATION_NO_AUTORUN', true);
}
require_once 'run_sla_automation.php';

try {
    $automation = runSlaAutomation($conn);
    echo json_encode([
        'ok' => true,
        'results' => $automation['escalation'] ?? [],
        'automation' => $automation
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
