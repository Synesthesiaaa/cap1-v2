<?php
/**
 * Checklist Template Management
 * 
 * Create and manage checklist templates for automated step generation
 */

require_once 'db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only admins and department heads can create templates
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'department_head'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createTemplate($conn);
            break;
        case 'list':
            listTemplates($conn);
            break;
        case 'get':
            getTemplate($conn);
            break;
        case 'update':
            updateTemplate($conn);
            break;
        case 'delete':
            deleteTemplate($conn);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }
} catch (Exception $e) {
    error_log("Checklist template error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function createTemplate($conn) {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
    $description = trim($_POST['description'] ?? '');
    $steps = json_decode($_POST['steps'] ?? '[]', true);
    $created_by = $_SESSION['id'];
    
    if (empty($name) || empty($category)) {
        echo json_encode(['success' => false, 'error' => 'Name and category are required']);
        exit;
    }
    
    if (!is_array($steps) || empty($steps)) {
        echo json_encode(['success' => false, 'error' => 'At least one step is required']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        // Insert template
        $stmt = $conn->prepare("
            INSERT INTO tbl_checklist_template (name, category, department_id, description, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisi", $name, $category, $department_id, $description, $created_by);
        $stmt->execute();
        $template_id = $conn->insert_id;
        $stmt->close();
        
        // Insert steps
        $step_stmt = $conn->prepare("
            INSERT INTO tbl_checklist_template_step (template_id, step_order, description, is_required)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($steps as $index => $step) {
            $step_order = $index + 1;
            $step_description = trim($step['description'] ?? '');
            $is_required = isset($step['is_required']) ? (int)$step['is_required'] : 1;
            
            if (!empty($step_description)) {
                $step_stmt->bind_param("iisi", $template_id, $step_order, $step_description, $is_required);
                $step_stmt->execute();
            }
        }
        $step_stmt->close();
        
        $conn->commit();
        echo json_encode(['success' => true, 'template_id' => $template_id]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function listTemplates($conn) {
    $category = $_GET['category'] ?? '';
    $department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
    
    $where = ["is_active = 1"];
    $params = [];
    $types = '';
    
    if (!empty($category)) {
        $where[] = "category = ?";
        $params[] = $category;
        $types .= 's';
    }
    
    if ($department_id !== null) {
        $where[] = "(department_id = ? OR department_id IS NULL)";
        $params[] = $department_id;
        $types .= 'i';
    }
    
    $whereClause = "WHERE " . implode(" AND ", $where);
    
    $query = "SELECT template_id, name, category, department_id, description, created_at 
              FROM tbl_checklist_template 
              $whereClause 
              ORDER BY name ASC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    
    echo json_encode(['success' => true, 'templates' => $templates]);
}

function getTemplate($conn) {
    $template_id = intval($_GET['template_id'] ?? 0);
    
    if ($template_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid template ID']);
        exit;
    }
    
    // Get template
    $stmt = $conn->prepare("
        SELECT template_id, name, category, department_id, description, created_at
        FROM tbl_checklist_template
        WHERE template_id = ?
    ");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$template) {
        echo json_encode(['success' => false, 'error' => 'Template not found']);
        exit;
    }
    
    // Get steps
    $stmt = $conn->prepare("
        SELECT step_id, step_order, description, is_required
        FROM tbl_checklist_template_step
        WHERE template_id = ?
        ORDER BY step_order ASC
    ");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $steps = [];
    while ($row = $result->fetch_assoc()) {
        $steps[] = $row;
    }
    $stmt->close();
    
    $template['steps'] = $steps;
    
    echo json_encode(['success' => true, 'template' => $template]);
}

function updateTemplate($conn) {
    $template_id = intval($_POST['template_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
    $description = trim($_POST['description'] ?? '');
    $steps = json_decode($_POST['steps'] ?? '[]', true);
    
    if ($template_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid template ID']);
        exit;
    }
    
    if (empty($name) || empty($category)) {
        echo json_encode(['success' => false, 'error' => 'Name and category are required']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        // Update template
        $stmt = $conn->prepare("
            UPDATE tbl_checklist_template
            SET name = ?, category = ?, department_id = ?, description = ?
            WHERE template_id = ?
        ");
        $stmt->bind_param("ssisi", $name, $category, $department_id, $description, $template_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete existing steps
        $del_stmt = $conn->prepare("DELETE FROM tbl_checklist_template_step WHERE template_id = ?");
        $del_stmt->bind_param("i", $template_id);
        $del_stmt->execute();
        $del_stmt->close();
        
        // Insert new steps
        if (is_array($steps) && !empty($steps)) {
            $step_stmt = $conn->prepare("
                INSERT INTO tbl_checklist_template_step (template_id, step_order, description, is_required)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($steps as $index => $step) {
                $step_order = $index + 1;
                $step_description = trim($step['description'] ?? '');
                $is_required = isset($step['is_required']) ? (int)$step['is_required'] : 1;
                
                if (!empty($step_description)) {
                    $step_stmt->bind_param("iisi", $template_id, $step_order, $step_description, $is_required);
                    $step_stmt->execute();
                }
            }
            $step_stmt->close();
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function deleteTemplate($conn) {
    $template_id = intval($_POST['template_id'] ?? 0);
    
    if ($template_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid template ID']);
        exit;
    }
    
    // Soft delete - set is_active to 0
    $stmt = $conn->prepare("UPDATE tbl_checklist_template SET is_active = 0 WHERE template_id = ?");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
}
?>
