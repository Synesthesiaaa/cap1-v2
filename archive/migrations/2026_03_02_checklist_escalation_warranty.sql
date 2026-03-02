-- Checklist, escalation, and warranty workflow additive migration

-- Ticket SLA scoring + normalized escalation state
ALTER TABLE tbl_ticket
  ADD COLUMN IF NOT EXISTS sla_weight_id INT(11) NULL AFTER sla_date,
  ADD COLUMN IF NOT EXISTS sla_priority_score DECIMAL(5,2) NULL AFTER sla_weight_id,
  ADD COLUMN IF NOT EXISTS escalation_state ENUM('on_track','approaching','overdue','escalated') NOT NULL DEFAULT 'on_track' AFTER sla_priority_score;

ALTER TABLE tbl_ticket
  ADD INDEX IF NOT EXISTS idx_ticket_escalation_state (escalation_state),
  ADD INDEX IF NOT EXISTS idx_ticket_sla_date_status (sla_date, status),
  ADD INDEX IF NOT EXISTS idx_ticket_assignee_status (assigned_technician_id, status);

-- Checklist metadata + audit fields
ALTER TABLE tbl_ticket_checklist
  ADD COLUMN IF NOT EXISTS source_type ENUM('template','manual','warranty','system') NOT NULL DEFAULT 'manual' AFTER description,
  ADD COLUMN IF NOT EXISTS step_order INT(11) NOT NULL DEFAULT 9999 AFTER source_type,
  ADD COLUMN IF NOT EXISTS is_required TINYINT(1) NOT NULL DEFAULT 1 AFTER step_order,
  ADD COLUMN IF NOT EXISTS completed_by INT(11) NULL AFTER completed_at,
  ADD COLUMN IF NOT EXISTS completed_by_role VARCHAR(50) NULL AFTER completed_by,
  ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL AFTER completed_by_role;

ALTER TABLE tbl_ticket_checklist
  ADD INDEX IF NOT EXISTS idx_checklist_ticket_completed (ticket_id, is_completed),
  ADD INDEX IF NOT EXISTS idx_checklist_ticket_required (ticket_id, is_required),
  ADD INDEX IF NOT EXISTS idx_checklist_ticket_source (ticket_id, source_type);

-- Escalation index hardening
ALTER TABLE tbl_ticket_escalation
  ADD INDEX IF NOT EXISTS idx_ticket_escalation_lookup (ticket_id, escalation_type, escalation_timestamp),
  ADD INDEX IF NOT EXISTS idx_ticket_escalation_status (sla_status);

-- Warranty claims
CREATE TABLE IF NOT EXISTS tbl_warranty_claim (
  claim_id INT(11) NOT NULL AUTO_INCREMENT,
  ticket_id INT(11) NOT NULL,
  customer_product_id INT(11) DEFAULT NULL,
  product_id INT(11) DEFAULT NULL,
  claim_type ENUM('repair','replacement','refund','inspection') NOT NULL DEFAULT 'inspection',
  claim_status ENUM('draft','submitted','under_review','approved','rejected','in_service','completed','cancelled') NOT NULL DEFAULT 'submitted',
  resolution_action VARCHAR(255) DEFAULT NULL,
  approved_by INT(11) DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_by INT(11) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (claim_id),
  KEY idx_warranty_claim_ticket (ticket_id),
  KEY idx_warranty_claim_status (claim_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS tbl_warranty_claim_history (
  history_id INT(11) NOT NULL AUTO_INCREMENT,
  claim_id INT(11) NOT NULL,
  from_status VARCHAR(50) DEFAULT NULL,
  to_status VARCHAR(50) NOT NULL,
  actor_id INT(11) DEFAULT NULL,
  actor_role VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (history_id),
  KEY idx_warranty_history_claim (claim_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Normalize historical escalation statuses
UPDATE tbl_ticket_escalation SET sla_status = 'escalated' WHERE LOWER(sla_status) = 'escalated' OR sla_status = 'Escalated';
UPDATE tbl_ticket_escalation SET sla_status = 'overdue' WHERE LOWER(sla_status) = 'overdue' OR sla_status = 'Overdue';
UPDATE tbl_ticket_escalation SET sla_status = 'on-time' WHERE LOWER(sla_status) IN ('on-time', 'ontime', 'on_time', 'on time');

