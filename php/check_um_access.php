<?php
/**
 * User Management Access Control Middleware
 *
 * Admin-only access for user management (CRUD, roles, capabilities)
 */

require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if current user can access User Management
 *
 * @return bool True if access granted (admin only), false otherwise
 */
function checkUMAccess() {
    if (!isset($_SESSION['id'])) {
        return false;
    }

    $role = $_SESSION['role'] ?? '';

    // Admin only
    return ($role === 'admin');
}
