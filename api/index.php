<?php
/**
 * API Entry Point
 * 
 * This file handles all API requests
 */

require_once __DIR__ . '/../bootstrap.php';

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load routes
$router = require __DIR__ . '/../routes/api.php';

// Dispatch request
$router->dispatch();
