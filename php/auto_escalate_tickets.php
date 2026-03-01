<?php
/**
 * Automatic Ticket Escalation System
 * 
 * Checks tickets approaching or exceeding SLA and auto-escalates them
 * This should be run as a cron job (e.g., every 15 minutes)
 */

require_once 'db.php';
require_once 'customer_summary_refresh.php';

// Set execution time limit for long-running script
set_time_limit(300);

/**
 * Auto-escalate tickets based on SLA status
 */
function autoEscalateTickets($conn) {
    $results = [
        'checked' => 0,
        'escalated' => 0,
        'warnings' => 0,
        'errors' => []
    ];
    
    // Get tickets that are approaching or overdue SLA
    // Approach threshold: within 24 hours of SLA date
    // Overdue: past SLA date
    $query = "
        SELECT t.ticket_id, t.reference_id, t.sla_date, t.priority, t.status, 
               t.assigned_technician_id, t.type, t.user_id
        FROM tbl_ticket t
        WHERE t.status NOT IN ('complete', 'closed')
        AND t.sla_date IS NOT NULL
        AND (
            -- Overdue tickets
            (t.sla_date < CURDATE())
            OR
            -- Approaching SLA (within 24 hours)
            (t.sla_date = CURDATE() AND TIME(NOW()) >= '00:00:00')
            OR
            (t.sla_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY))
        )
        ORDER BY t.sla_date ASC, t.priority DESC
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        $results['errors'][] = "Query failed: " . $conn->error;
        return $results;
    }
    
    while ($ticket = $result->fetch_assoc()) {
        $results['checked']++;
        
        $ticket_id = $ticket['ticket_id'];
        $sla_date = $ticket['sla_date'];
        $is_overdue = strtotime($sla_date) < strtotime('today');
        $hours_until_sla = $is_overdue ? 0 : (strtotime($sla_date . ' 23:59:59') - time()) / 3600;
        
        // Check if already escalated recently (within last 4 hours)
        $recent_esc = $conn->prepare("
            SELECT escalation_id 
            FROM tbl_ticket_escalation 
            WHERE ticket_id = ? 
            AND escalation_type = 'system'
            AND escalation_timestamp >= DATE_SUB(NOW(), INTERVAL 4 HOUR)
            LIMIT 1
        ");
        $recent_esc->bind_param("i", $ticket_id);
        $recent_esc->execute();
        $recent_result = $recent_esc->get_result();
        
        if ($recent_result->num_rows > 0) {
            $recent_esc->close();
            continue; // Already escalated recently
        }
        $recent_esc->close();
        
        // Determine escalation action
        $escalation_reason = '';
        $new_priority = $ticket['priority'];
        $new_technician_id = $ticket['assigned_technician_id'];
        
        if ($is_overdue) {
            $escalation_reason = 'Ticket is overdue (SLA date: ' . $sla_date . ')';
            // Escalate priority if not already urgent
            if ($ticket['priority'] !== 'urgent') {
                $priority_map = ['low' => 'regular', 'regular' => 'high', 'high' => 'urgent'];
                $new_priority = $priority_map[$ticket['priority']] ?? 'urgent';
            }
        } elseif ($hours_until_sla <= 24 && $hours_until_sla > 0) {
            $escalation_reason = 'Ticket approaching SLA deadline (within 24 hours)';
            // Boost priority if low
            if ($ticket['priority'] === 'low') {
                $new_priority = 'regular';
            }
        } else {
            continue; // Not yet time to escalate
        }
        
        // Get department head for escalation
        $dept_name = $ticket['type'];
        $dept_head_id = null;
        
        if ($dept_name) {
            $dept_stmt = $conn->prepare("
                SELECT d.department_id 
                FROM tbl_department d 
                WHERE d.department_name = ? 
                LIMIT 1
            ");
            $dept_stmt->bind_param("s", $dept_name);
            $dept_stmt->execute();
            $dept_result = $dept_stmt->get_result();
            if ($dept_row = $dept_result->fetch_assoc()) {
                $dept_id = $dept_row['department_id'];
                
                // Find department head
                $head_stmt = $conn->prepare("
                    SELECT u.user_id 
                    FROM tbl_user u
                    INNER JOIN tbl_department_head dh ON u.user_id = dh.user_id
                    WHERE dh.department_id = ? AND u.status = 'active'
                    LIMIT 1
                ");
                $head_stmt->bind_param("i", $dept_id);
                $head_stmt->execute();
                $head_result = $head_stmt->get_result();
                if ($head_row = $head_result->fetch_assoc()) {
                    $dept_head_id = $head_row['user_id'];
                }
                $head_stmt->close();
            }
            $dept_stmt->close();
        }
        
        // Record escalation
        $prev_technician_id = $ticket['assigned_technician_id'];
        $prev_dept_id = null;
        $new_dept_id = null;
        
        if ($dept_name) {
            $dept_stmt = $conn->prepare("SELECT department_id FROM tbl_department WHERE department_name = ? LIMIT 1");
            $dept_stmt->bind_param("s", $dept_name);
            $dept_stmt->execute();
            $dept_result = $dept_stmt->get_result();
            if ($dept_row = $dept_result->fetch_assoc()) {
                $new_dept_id = $dept_row['department_id'];
            }
            $dept_stmt->close();
        }
        
        $esc_stmt = $conn->prepare("
            INSERT INTO tbl_ticket_escalation (
                ticket_id, prev_technician_id, new_technician_id, 
                prev_department_id, new_department_id, reason, 
                escalator_id, escalation_type, sla_status
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 'system', ?)
        ");
        
        $sla_status = $is_overdue ? 'overdue' : 'escalated';
        $esc_stmt->bind_param(
            "iiiiiss", 
            $ticket_id, 
            $prev_technician_id, 
            $new_technician_id,
            $prev_dept_id,
            $new_dept_id,
            $escalation_reason,
            $sla_status
        );
        $esc_stmt->execute();
        $esc_stmt->close();
        
        // Update ticket priority if changed
        if ($new_priority !== $ticket['priority']) {
            $update_stmt = $conn->prepare("UPDATE tbl_ticket SET priority = ? WHERE ticket_id = ?");
            $update_stmt->bind_param("si", $new_priority, $ticket_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        // Log escalation
        $log_stmt = $conn->prepare("
            INSERT INTO tbl_ticket_logs (ticket_id, user_id, user_role, action_type, action_details)
            VALUES (?, 0, 'system', 'escalate', ?)
        ");
        $log_details = "Auto-escalated: " . $escalation_reason . ". Priority: " . $new_priority;
        $log_stmt->bind_param("is", $ticket_id, $log_details);
        $log_stmt->execute();
        $log_stmt->close();

        refreshTicketSummaryByTicketId((int)$ticket_id, $conn);
        
        $results['escalated']++;
        
        if ($hours_until_sla <= 24 && $hours_until_sla > 0) {
            $results['warnings']++;
        }
    }
    
    return $results;
}

// Run escalation if called directly
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === '1')) {
    $results = autoEscalateTickets($conn);
    
    if (php_sapi_name() === 'cli') {
        echo "Auto-escalation completed:\n";
        echo "  Checked: " . $results['checked'] . " tickets\n";
        echo "  Escalated: " . $results['escalated'] . " tickets\n";
        echo "  Warnings: " . $results['warnings'] . " tickets\n";
        if (!empty($results['errors'])) {
            echo "  Errors: " . implode(", ", $results['errors']) . "\n";
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode($results);
    }
}

$conn->close();
?>
