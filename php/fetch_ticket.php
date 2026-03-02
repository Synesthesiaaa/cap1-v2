<?php
/**
 * Fetch Tickets - Migrated to use new architecture
 * 
 * This endpoint now uses TicketService while maintaining backward compatibility
 */

header('Content-Type: application/json');

// Try to use new structure if available
$useNewStructure = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap.php';
    if (class_exists('Services\TicketService') && class_exists('Repositories\TicketRepository')) {
        $useNewStructure = true;
    }
}

// Fallback to old structure
if (!$useNewStructure) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    require_once "db.php";
}

session_start();

// Validate session
$user_id = $_SESSION['id'] ?? 0;
$tech_id = $_SESSION['technician_id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'user';
$dept_id = intval($_SESSION['department_id'] ?? 0);

// Inputs
$table = intval($_GET['table'] ?? 1);
$q = trim($_GET['q'] ?? "");
$status = strtolower(trim($_GET['status'] ?? ""));
$priority = strtolower(trim($_GET['priority'] ?? ""));
$sort = $_GET['sort'] ?? "created_at_desc";
$page = max(1, intval($_GET['page'] ?? 1));
$limit = intval($_GET['page_size'] ?? 10);
$offset = ($page - 1) * $limit;
$needing_filter = isset($_GET['needing_filter']) && $_GET['needing_filter'] == '1' ? 1 : 0;

// Department info
$dept_name = "";
if ($useNewStructure) {
    $conn = \Database\Connection::getInstance()->getConnection();
}
$sqlDept = $conn->prepare("SELECT department_name FROM tbl_department WHERE department_id = ?");
if ($sqlDept) {
    $sqlDept->bind_param("i", $dept_id);
    $sqlDept->execute();
    $resDept = $sqlDept->get_result();
    if ($rowDept = $resDept->fetch_assoc()) {
        $dept_name = $rowDept['department_name'];
    }
    $sqlDept->close();
}


$where = [];
$values = [];
$types = "";

// Table logic
switch ($table) {

    case 1: // tickets created by user
        $where[] = "t.user_id = ?";
        $values[] = $user_id; $types .= "i";
        // Apply needing filter: exclude completed tickets (unless status filter is set)
        if ($needing_filter && $status === "") {
            $where[] = "t.status != 'complete'";
        } else if (!$needing_filter && $status === "") {
            // Default: exclude completed for table 1 unless status is explicitly set
            $where[] = "t.status != 'complete'";
        }
        break;

    case 2: // to-do queue: department head by department, admin sees all
        $role = $_SESSION['role'] ?? '';

        if ($role === 'department_head') {
            // Department heads see tickets that match their department type
            $where[] = "t.type = ?";
            $values[] = $dept_name;
            $types .= "s";
        } else if ($role === 'admin') {
            // Admin sees all tickets regardless of department
            // no extra where clause
        } else {
            $where[] = "1 = 0";
        }

        // Apply needing filter: exclude completed tickets (unless status filter is set)
        if ($needing_filter && $status === "") {
            $where[] = "t.status != 'complete'";
        } else if (!$needing_filter && $status === "") {
            // Default: exclude completed for table 2 unless status is explicitly set
            $where[] = "t.status != 'complete'";
        }

        break;


    case 3: // completed tickets owned by user
        $where[] = "t.user_id = ?";
        $values[] = $user_id; $types .= "i";
        $where[] = "t.status = 'complete'";
        break;

    default:
        echo json_encode(["success"=>false, "message"=>"Invalid table filter"]);
        exit;
}

// Search
if ($q !== "") {
    $where[] = "(t.title LIKE ? OR t.reference_id LIKE ?)";
    $values[] = "%$q%"; $types .= "s";
    $values[] = "%$q%"; $types .= "s";
}

// Status filter
if ($status !== "") {
    if ($status === 'resolved') {
        $status = 'complete';
    }
    $where[] = "LOWER(t.status) = ?";
    $values[] = $status; $types .= "s";
}

// Priority filter
if ($priority !== "") {
    if ($priority === 'medium') {
        $priority = 'regular';
    } elseif ($priority === 'urgent') {
        $priority = 'critical';
    }
    $where[] = "t.priority = ?";
    $values[] = $priority; $types .= "s";
}


$order_sql = ($sort === "created_at_asc") 
    ? "ORDER BY t.created_at ASC"
    : "ORDER BY t.created_at DESC";


$where_sql = count($where) ? "WHERE ".implode(" AND ", $where) : "";

$sql_count = "SELECT COUNT(*) AS total FROM tbl_ticket t $where_sql";
$stmt = $conn->prepare($sql_count);
if ($types !== "")
    $stmt->bind_param($types, ...$values);

$stmt->execute();
$count_res = $stmt->get_result()->fetch_assoc();
$total_rows = $count_res['total'] ?? 0;
$stmt->close();

$sql_data = "
    SELECT t.ticket_id, t.reference_id, t.title, t.status, t.priority, t.created_at
    FROM tbl_ticket t
    $where_sql
    $order_sql
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql_data);
$types_data = $types . "ii";
$values_data = array_merge($values, [$limit, $offset]);
$stmt->bind_param($types_data, ...$values_data);
$stmt->execute();
$result = $stmt->get_result();

$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
$stmt->close();

$dept_id_need = intval($_SESSION['department_id'] ?? 0);
$sql_need = "
    SELECT COUNT(*) AS need
    FROM tbl_ticket t
    WHERE t.user_id IN (SELECT user_id FROM tbl_user WHERE department_id = ?)
        AND t.status != 'complete'
";
$stmt = $conn->prepare($sql_need);
$stmt->bind_param("i", $dept_id_need);

$stmt->execute();
$need_res = $stmt->get_result()->fetch_assoc();
$needing_count = $need_res['need'] ?? 0;

// Use new structure if available for better performance
if ($useNewStructure && $table == 1) {
    try {
        $ticketService = new \Services\TicketService();
        $filters = [
            'user_id' => $user_id,
            'status' => $status ?: null,
            'priority' => $priority ?: null,
            'search' => $q ?: null
        ];
        
        // Exclude completed by default
        if (!$needing_filter && $status === "") {
            $filters['exclude_status'] = 'complete';
        }
        
        $result = $ticketService->getTickets($filters, $page, $limit);
        
        echo json_encode([
            "success" => true,
            "data" => $result['data'],
            "total_count" => $result['total'],
            "needing_count" => $needing_count,
            "page" => $result['page'],
            "total_pages" => $result['total_pages']
        ]);
        exit;
    } catch (\Exception $e) {
        // Fall through to old implementation
    }
}

// OLD IMPLEMENTATION (fallback)
echo json_encode([
    "success" => true,
    "data" => $tickets,
    "total_count" => $total_rows,
    "needing_count" => $needing_count,
    "page" => $page,
    "total_pages" => max(1, ceil($total_rows / $limit))
]);
exit;
