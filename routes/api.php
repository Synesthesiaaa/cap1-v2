<?php
/**
 * API Routes
 * 
 * Define all API endpoints here
 */

use App\Router;
use App\Controllers\TicketController;
use App\Controllers\AuthController;
use Middleware\CsrfMiddleware;

$router = new Router();

// Authentication routes
$router->post('/api/auth/login', [AuthController::class, 'login'], []);
$router->post('/api/auth/logout', [AuthController::class, 'logout'], []);
$router->get('/api/auth/check', [AuthController::class, 'checkAuth'], []);

// Ticket routes
$router->get('/api/tickets', [TicketController::class, 'list'], []);
$router->post('/api/tickets', [TicketController::class, 'create'], [CsrfMiddleware::class]);
$router->get('/api/tickets/{reference}', [TicketController::class, 'getByReference'], []);
$router->post('/api/tickets/{reference}/resolve', [TicketController::class, 'resolve'], [CsrfMiddleware::class]);

return $router;
