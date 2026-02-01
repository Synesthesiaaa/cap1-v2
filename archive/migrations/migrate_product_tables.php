<?php
/**
 * Migration Script: Create Product and Checklist Template Tables
 * 
 * This script creates the new tables for product management and checklist templates.
 * Run this script once to set up the database schema.
 */

require_once 'db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starting migration: Product and Checklist Template Tables\n";
echo str_repeat("=", 60) . "\n\n";

$queries = [];

// Create tbl_product table
$queries[] = "CREATE TABLE IF NOT EXISTS `tbl_product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `idx_serial_number` (`serial_number`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create tbl_customer_product table
$queries[] = "CREATE TABLE IF NOT EXISTS `tbl_customer_product` (
  `customer_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_start` date DEFAULT NULL,
  `warranty_end` date DEFAULT NULL,
  `status` enum('active','inactive','warranty_expired','replaced') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`customer_product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_warranty_end` (`warranty_end`),
  KEY `idx_status` (`status`),
  CONSTRAINT `tbl_customer_product_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_customer_product_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create tbl_ticket_product table
$queries[] = "CREATE TABLE IF NOT EXISTS `tbl_ticket_product` (
  `ticket_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `customer_product_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `action_type` enum('repair','warranty_claim','purchase','inquiry','other') DEFAULT 'repair',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ticket_product_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_customer_product_id` (`customer_product_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_action_type` (`action_type`),
  CONSTRAINT `tbl_ticket_product_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tbl_ticket` (`ticket_id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_ticket_product_ibfk_2` FOREIGN KEY (`customer_product_id`) REFERENCES `tbl_customer_product` (`customer_product_id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_ticket_product_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create tbl_checklist_template table
$queries[] = "CREATE TABLE IF NOT EXISTS `tbl_checklist_template` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`template_id`),
  KEY `idx_category` (`category`),
  KEY `idx_department_id` (`department_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `tbl_checklist_template_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `tbl_department` (`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create tbl_checklist_template_step table
$queries[] = "CREATE TABLE IF NOT EXISTS `tbl_checklist_template_step` (
  `step_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL DEFAULT 1,
  `description` varchar(255) NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`step_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_step_order` (`step_order`),
  CONSTRAINT `tbl_checklist_template_step_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `tbl_checklist_template` (`template_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

$success_count = 0;
$error_count = 0;

foreach ($queries as $index => $query) {
    $table_name = '';
    if (preg_match('/CREATE TABLE.*?`([^`]+)`/i', $query, $matches)) {
        $table_name = $matches[1];
    }
    
    echo "Creating table: $table_name...\n";
    
    if ($conn->query($query)) {
        echo "  ✓ Successfully created $table_name\n";
        $success_count++;
    } else {
        echo "  ✗ Error creating $table_name: " . $conn->error . "\n";
        $error_count++;
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "Migration completed!\n";
echo "Success: $success_count tables\n";
echo "Errors: $error_count tables\n";

if ($error_count > 0) {
    echo "\nPlease review the errors above and fix them before proceeding.\n";
    exit(1);
} else {
    echo "\nAll tables created successfully!\n";
}

$conn->close();
?>
