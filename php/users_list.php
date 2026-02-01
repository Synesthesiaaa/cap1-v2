<?php
/**
 * Users List API for User Management
 *
 * Returns paginated list of users (tbl_user) with filters
 */

require_once 'db.php';
require_once 'check_um_access.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Access control: admin only
if (!checkUMAccess()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin only.']);
    exit;
}

// Get and sanitize input
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$userType = isset($_GET['user_type']) ? $_GET['user_type'] : 'all';
$userRole = isset($_GET['user_role']) ? $_GET['user_role'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(10, min(50, intval($_GET['limit'] ?? 20)));

// Build WHERE conditions
$whereConditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR u.phone LIKE ? OR CAST(u.user_id AS CHAR) LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= 'sssss';
}

if ($userType !== 'all') {
    $whereConditions[] = "u.user_type = ?";
    $params[] = $userType;
    $types .= 's';
}

if ($userRole !== 'all') {
    $whereConditions[] = "u.user_role = ?";
    $params[] = $userRole;
    $types .= 's';
}

if ($status !== 'all') {
    $whereConditions[] = "u.status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM tbl_user u {$whereClause}";
if (!empty($params)) {
    $stmt = $conn->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalCount = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query($countSql);
    $totalCount = $result->fetch_assoc()['total'];
}

// Build main query with department join
$offset = ($page - 1) * $limit;
$sql = "SELECT u.user_id, u.user_type, u.department_id, u.name, u.company, u.email, u.phone, u.status, u.user_role, u.created_at,
        d.department_name
        FROM tbl_user u
        LEFT JOIN tbl_department d ON u.department_id = d.department_id
        {$whereClause}
        ORDER BY u.user_id DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $row['department_name'] = $row['department_name'] ?? null;
    $users[] = $row;
}
$stmt->close();

echo json_encode([
    'users' => $users,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total_count' => (int)$totalCount,
        'total_pages' => (int)ceil($totalCount / $limit)
    ]
]);
