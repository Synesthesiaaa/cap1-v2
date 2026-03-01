<?php
/**
 * Save Ticket - Migrated to use new architecture
 * 
 * This file now uses TicketService while maintaining backward compatibility
 */

// Try to use new structure if available
$useNewStructure = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap.php';
    if (class_exists('Services\TicketService')) {
        $useNewStructure = true;
    }
}

// Fallback to old structure
if (!$useNewStructure) {
    include("db.php");
    include("../utils/reference_generator.php");
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    include("db.php");
}

session_start();
require_once 'customer_summary_refresh.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../views/login.php");
    exit();
}

$user_id        = $_SESSION['id'];
$user_role      = $_SESSION['role'] ?? 'user';
$title          = $_POST['title'] ?? '';
$type           = $_POST['ticket_type'] ?? '';
$category       = $_POST['category'] ?? '';
$description    = $_POST['description'] ?? '';
$department_id  = $_SESSION['department_id'] ?? 0;
$is_urgent      = isset($_POST['is_urgent']) && $_POST['is_urgent'] == '1';

// Use new structure if available
if ($useNewStructure) {
    try {
        $ticketService = new \Services\TicketService();
        
        // Handle file upload
        $attachment = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "../uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = time() . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", basename($_FILES['attachment']['name']));
            $targetFile = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
                $attachment = $targetFile;
            }
        }
        
        $user_type = $_SESSION['user_type'] ?? 'internal';

        // Prepare ticket data - priority/assignment handled by TicketService (SLA Weight)
        $ticketData = [
            'title' => $title,
            'type' => $type,
            'ticket_type' => $type,
            'category' => $category,
            'description' => $description,
            'user_type' => $user_type,
            'urgency' => $is_urgent ? 'urgent' : 'normal',
            'attachments' => $attachment ?? '',
            'is_urgent' => $is_urgent ? '1' : '0'
        ];
        
        // Create ticket using service
        $result = $ticketService->create($ticketData, $user_id, $user_role);
        
        if ($result['success']) {
            $reference_id = $result['reference_id'];
            
            // Handle product linking (legacy functionality)
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
            $customer_product_id = isset($_POST['customer_product_id']) ? intval($_POST['customer_product_id']) : null;
            
            if ($product_id || $customer_product_id) {
                require_once 'extract_product_from_ticket.php';
                if (function_exists('linkTicketToProduct')) {
                    $action_type = 'repair';
                    $desc_lower = strtolower($description);
                    if (strpos($desc_lower, 'warranty') !== false) {
                        $action_type = 'warranty_claim';
                    } elseif (strpos($desc_lower, 'purchase') !== false || strpos($desc_lower, 'buy') !== false) {
                        $action_type = 'purchase';
                    }
                    linkTicketToProduct($conn, $result['ticket_id'], $product_id, $customer_product_id, $action_type);
                }
            }
            
            // Auto-extract products from description
            if (!empty($description) && function_exists('processTicketForProducts')) {
                processTicketForProducts($conn, $result['ticket_id'], $description, $user_id);
            }

            refreshUserTicketSummary(
                (int)$user_id,
                (isset($conn) && $conn instanceof mysqli) ? $conn : null
            );
            
            // Redirect
            if ($user_role === 'user') {
                header("Location: ../views/user_ticket_monitor.php?success=ticket_created&ref=" . urlencode($reference_id));
            } else {
                header("Location: ../views/dashboard.php?success=ticket_created&ref=" . urlencode($reference_id));
            }
            exit();
        } else {
            die("Error: " . ($result['error'] ?? 'Failed to create ticket'));
        }
    } catch (\Exception $e) {
        // Fall through to old implementation
        $useNewStructure = false;
    }
}

// OLD IMPLEMENTATION (fallback)
$reference_id   = function_exists('generateReferenceID') ? generateReferenceID($conn) : 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
$assigned_user_id = null;
$urgency        = $is_urgent ? 'urgent' : 'normal';


// ---------------------------
// DEPARTMENT ROUTING
// ---------------------------

// STEP 1 — Find routing based on category
$routeSql = "SELECT target_department_id, auto_assign_technician, priority_boost
             FROM tbl_department_routing
             WHERE category = ?
             LIMIT 1";

$routeStmt = $conn->prepare($routeSql);
$routeStmt->bind_param("s", $category);
$routeStmt->execute();
$routeResult = $routeStmt->get_result();

$target_department_id = null;
$priority_boost = "none";
$auto_assign_tech = 0;

if ($routeResult->num_rows > 0) {
    $row = $routeResult->fetch_assoc();
    $target_department_id = $row['target_department_id'];
    $priority_boost = $row['priority_boost'];
    $auto_assign_tech = $row['auto_assign_technician'];
}

$routeStmt->close();

// Default priority
$priority = "low";

// If user marked urgent → override everything
if ($is_urgent) {
    $priority = "urgent";
} else {
    switch ($priority_boost) {
        case 'high':
            $priority = 'high';
            break;

        case 'medium':
            $priority = 'regular';
            break;

        case 'low':    
        case 'none':   
        default:
            $priority = 'low';
            break;
    }
}

$assigned_technician = NULL;

if (!$assigned_user_id) {

    if (in_array($type, ['IT'])) {
        $techSql = "SELECT technician_id FROM tbl_technician
                    WHERE status = 'active'
                    AND specialization IN ('software', 'hardware')
                    ORDER BY active_tickets ASC LIMIT 1";
    } elseif (in_array($type, ['Facilities', 'Warehouse', 'Production', 'Engineering', 'Finance', 'HR', 'Sales', 'Shipping'])) {
        $techSql = "SELECT technician_id FROM tbl_technician
                    WHERE status = 'active'
                    AND specialization = 'operation'
                    ORDER BY active_tickets ASC LIMIT 1";
    } else {
        $techSql = "SELECT technician_id FROM tbl_technician
                    WHERE status = 'active'
                    ORDER BY active_tickets ASC LIMIT 1";
    }

    $techStmt = $conn->prepare($techSql);
    $techStmt->execute();
    $techResult = $techStmt->get_result();

    if ($techResult->num_rows > 0) {
        $assigned_technician = $techResult->fetch_assoc()['technician_id'];
        $status = 'pending'; 
    }

    $techStmt->close();

} else {
    $status = 'assigning';
}

// File upload handling
$attachment = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = "../uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = time() . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", basename($_FILES['attachment']['name']));
    $targetFile = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
        $attachment = $targetFile;
    }
}

// SLA will be calculated based on priority
$sla_date = NULL;
switch ($priority) {
    case 'urgent':
        $sla_date = date('Y-m-d', strtotime('+1 day'));
        break;
    case 'high':
        $sla_date = date('Y-m-d', strtotime('+1 day'));
        break;
    case 'regular':
        $sla_date = date('Y-m-d', strtotime('+3 days'));
        break;
    case 'low':
    default:
        $sla_date = date('Y-m-d', strtotime('+7 days'));
        break;
}

// Get product information from POST if provided
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
$customer_product_id = isset($_POST['customer_product_id']) ? intval($_POST['customer_product_id']) : null;

// Insert Ticket
$sql = "INSERT INTO tbl_ticket
(reference_id, user_id, title, type, category, priority, urgency, description, attachments,
 assigned_technician_id, sla_date, status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Prepare Failed: " . $conn->error);
}

if ($attachment === null) $attachment = "";
$assignee_for_insert = $assigned_user_id ?: $assigned_technician;

// Handle NULL values for bind_param
$stmt->bind_param(
    "sisssssssiss", 
    $reference_id,
    $user_id,
    $title,
    $type,
    $category,
    $priority,
    $urgency,
    $description,
    $attachment,
    $assigned_technician,
    $sla_date,
    $status
);


// Execute and Update Technician Load
if ($stmt->execute()) {
    $ticket_id = $conn->insert_id;
    
    if ($assigned_user_id === null && $assigned_technician) {
        $updateTech = $conn->prepare("UPDATE tbl_technician 
            SET active_tickets = active_tickets + 1 
            WHERE technician_id = ?");
        $updateTech->bind_param("i", $assigned_technician);
        $updateTech->execute();
        $updateTech->close();
    }
    
    // Product extraction and linking
    require_once 'extract_product_from_ticket.php';
    
    // Auto-generate checklist from template
    require_once 'auto_generate_checklist.php';
    $target_dept_name = null;
    if ($target_department_id) {
        $dept_stmt = $conn->prepare("SELECT department_name FROM tbl_department WHERE department_id = ?");
        $dept_stmt->bind_param("i", $target_department_id);
        $dept_stmt->execute();
        $dept_result = $dept_stmt->get_result();
        if ($dept_row = $dept_result->fetch_assoc()) {
            $target_dept_name = $dept_row['department_name'];
        }
        $dept_stmt->close();
    }
    autoGenerateChecklist($conn, $ticket_id, $category, $target_department_id);
    
    // Link product if provided
    if ($product_id || $customer_product_id) {
        $action_type = 'repair';
        // Determine action type from description
        $desc_lower = strtolower($description);
        if (strpos($desc_lower, 'warranty') !== false || strpos($desc_lower, 'warranty claim') !== false) {
            $action_type = 'warranty_claim';
        } elseif (strpos($desc_lower, 'purchase') !== false || strpos($desc_lower, 'buy') !== false) {
            $action_type = 'purchase';
        }
        
        if ($customer_product_id) {
            // Get product_id from customer_product
            $cp_stmt = $conn->prepare("SELECT product_id FROM tbl_customer_product WHERE customer_product_id = ?");
            $cp_stmt->bind_param("i", $customer_product_id);
            $cp_stmt->execute();
            $cp_result = $cp_stmt->get_result();
            if ($cp_row = $cp_result->fetch_assoc()) {
                $product_id = $cp_row['product_id'];
            }
            $cp_stmt->close();
        }
        
        if ($product_id) {
            linkTicketToProduct($conn, $ticket_id, $product_id, $customer_product_id, $action_type);
        }
    }
    
    // Auto-extract product information from description
    if (!empty($description)) {
        processTicketForProducts($conn, $ticket_id, $description, $user_id);
    }

    refreshUserTicketSummary((int)$user_id, $conn);

    if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
        header("Location: ../views/user_ticket_monitor.php?success=ticket_created&ref=" . urlencode($reference_id));
    } else {
        header("Location: ../views/dashboard.php?success=ticket_created&ref=" . urlencode($reference_id));
    }
    exit();
} else {
    echo "Error executing query: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
