<?php
/**
 * Customer Management Access Control Middleware
 *
 * Centralized access checking for CM module
 * Returns access level: 'full'|'readonly'|'denied'
 */

require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check CM access level for current user
 * 
 * @return string Access level: 'full', 'readonly', or 'denied'
 */
function checkCMAccess() {
    if (!isset($_SESSION['id'])) {
        return 'denied';
    }
    
    $role = $_SESSION['role'] ?? '';
    $userType = $_SESSION['user_type'] ?? '';
    
    // Full access: Department heads, admins
    if (in_array($role, ['department_head', 'admin'])) {
        return 'full';
    }
    
    // Read-only access: Low-level internal employees
    if ($userType === 'internal' && in_array($role, ['user', 'technician'])) {
        return 'readonly';
    }
    
    // Denied: External users or other roles
    return 'denied';
}

/**
 * Require specific access level
 * 
 * @param string $requiredLevel Required access level
 * @return bool True if access granted, false otherwise
 */
function requireCMAccess($requiredLevel = 'readonly') {
    $access = checkCMAccess();
    
    $levels = ['denied' => 0, 'readonly' => 1, 'full' => 2];
    $userLevel = $levels[$access] ?? 0;
    $requiredLevelValue = $levels[$requiredLevel] ?? 0;
    
    return $userLevel >= $requiredLevelValue;
}

// API endpoint
if (basename($_SERVER['PHP_SELF']) === 'check_cm_access.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'access_level' => checkCMAccess(),
        'role' => $_SESSION['role'] ?? '',
        'user_type' => $_SESSION['user_type'] ?? ''
    ]);
    exit;
}
?>
