<?php
/**
 * Auto-Generate Checklist from Templates
 * 
 * Automatically generates checklist items from templates when tickets are created
 */

require_once 'db.php';

/**
 * Generate checklist items from template for a ticket
 * 
 * @param mysqli $conn Database connection
 * @param int $ticket_id Ticket ID
 * @param string $category Ticket category
 * @param int|null $department_id Department ID
 * @return array Results
 */
function autoGenerateChecklist($conn, $ticket_id, $category, $department_id = null) {
    $results = [
        'items_created' => 0,
        'template_used' => null
    ];
    
    // Find matching template by category
    $where = ["category = ?", "is_active = 1"];
    $params = [$category];
    $types = 's';
    
    if ($department_id !== null) {
        $where[] = "(department_id = ? OR department_id IS NULL)";
        $params[] = $department_id;
        $types .= 'i';
    }
    
    $whereClause = "WHERE " . implode(" AND ", $where) . " ORDER BY department_id DESC LIMIT 1";
    
    $query = "SELECT template_id FROM tbl_checklist_template $whereClause";
    
    $stmt = $conn->prepare($query);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();
    $stmt->close();
    
    if (!$template) {
        return $results; // No template found
    }
    
    $template_id = $template['template_id'];
    $results['template_used'] = $template_id;
    
    // Get template steps
    $step_stmt = $conn->prepare("
        SELECT step_order, description, is_required
        FROM tbl_checklist_template_step
        WHERE template_id = ?
        ORDER BY step_order ASC
    ");
    $step_stmt->bind_param("i", $template_id);
    $step_stmt->execute();
    $step_result = $step_stmt->get_result();
    
    // Get system user ID (0) for auto-generated items
    $created_by = 0; // System-generated
    $is_technician = 0;
    
    // Insert checklist items
    $insert_stmt = $conn->prepare("
        INSERT INTO tbl_ticket_checklist (ticket_id, created_by, is_technician, description, is_completed)
        VALUES (?, ?, ?, ?, 0)
    ");
    
    while ($step = $step_result->fetch_assoc()) {
        $description = $step['description'];
        $insert_stmt->bind_param("iiis", $ticket_id, $created_by, $is_technician, $description);
        if ($insert_stmt->execute()) {
            $results['items_created']++;
        }
    }
    
    $insert_stmt->close();
    $step_stmt->close();
    
    return $results;
}
?>
