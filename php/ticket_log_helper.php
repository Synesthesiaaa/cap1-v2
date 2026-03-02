<?php
/**
 * Canonical ticket log include for legacy endpoints.
 *
 * This loader guarantees insertTicketLog() is defined at most once,
 * while keeping backward compatibility with existing helper files.
 */

if (!function_exists('insertTicketLog') && file_exists(__DIR__ . '/insert_log.php')) {
    require_once __DIR__ . '/insert_log.php';
}

if (!function_exists('insertTicketLog') && file_exists(__DIR__ . '/insert_log_monitor.php')) {
    require_once __DIR__ . '/insert_log_monitor.php';
}

