<?php
/**
 * Migration Script: Create tbl_sla_weight and seed from CSV
 *
 * Run: php archive/migrations/migrate_sla_weight.php
 * Or via browser: /archive/migrations/migrate_sla_weight.php
 */

require_once dirname(__DIR__, 2) . '/php/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starting migration: SLA Weight Table\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Add Facilities to tbl_department if not exists
$checkFacilities = $conn->query("SELECT department_id FROM tbl_department WHERE department_name = 'Facilities' LIMIT 1");
if ($checkFacilities->num_rows === 0) {
    $conn->query("INSERT INTO tbl_department (department_name) VALUES ('Facilities')");
    echo "Added Facilities to tbl_department\n";
} else {
    echo "Facilities already exists in tbl_department\n";
}

// 2. Create tbl_sla_weight (category + department_name for "Other" entries)
$createTable = "CREATE TABLE IF NOT EXISTS tbl_sla_weight (
  sla_weight_id INT(11) NOT NULL AUTO_INCREMENT,
  category VARCHAR(100) NOT NULL,
  department_name VARCHAR(100) NOT NULL,
  time_value TINYINT NOT NULL,
  importance TINYINT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (sla_weight_id),
  UNIQUE KEY uk_category_department (category, department_name),
  KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($createTable)) {
    echo "Created tbl_sla_weight\n";
} else {
    die("Failed to create table: " . $conn->error);
}

// 3. Seed from CSV data (Category, Department, Time, Importance)
$seedData = [
    ['System access', 'IT', 10, 8],
    ['Network or router troubleshooting', 'IT', 7, 8],
    ['Hardware or software installation', 'IT', 2, 2],
    ['Malfunctioning PCs or peripherals', 'IT', 3, 6],
    ['Email configuration errors', 'IT', 3, 8],
    ['Coordination with other departments', 'IT', 2, 4],
    ['System Audit', 'IT', 2, 2],
    ['Maintenance', 'IT', 2, 2],
    ['ERP entry errors', 'Finance', 6, 9],
    ['Billing or reconciliation disputes', 'Finance', 8, 8],
    ['payment verification issues', 'Finance', 3, 8],
    ['report generation errors', 'Finance', 2, 5],
    ['Financial data sync issues', 'Finance', 1, 5],
    ['Warranty validation errors', 'Engineering', 3, 5],
    ['Delayed ticket for servicing items', 'Engineering', 5, 2],
    ['Product serial verification', 'Engineering', 2, 3],
    ['Approval for replacement items', 'Engineering', 5, 5],
    ['Onboarding or offboarding system access', 'HR', 2, 3],
    ['Employee account creation', 'HR', 2, 3],
    ['Password recovery', 'HR', 2, 7],
    ['Attendance record discrepancies', 'HR', 2, 3],
    ['Inventory record inconsistencies', 'Warehouse', 3, 5],
    ['Missing stock entries', 'Warehouse', 2, 3],
    ['Damaged item reports', 'Warehouse', 8, 5],
    ['Delayed shipment arrivals', 'Warehouse', 3, 5],
    ['Batch tagging errors', 'Production', 8, 8],
    ['System synchronization lag', 'Production', 2, 1],
    ['Staff scheduling module malfunction', 'Production', 2, 2],
    ['Equipment maintenance', 'Production', 2, 2],
    ['Customer inquiry updates', 'Sales', 1, 1],
    ['Warranty record assistance', 'Sales', 4, 4],
    ['System generated report errors', 'Sales', 3, 5],
    ['Customer profile updates', 'Sales', 2, 2],
    ['Wrong delivery or Update issues', 'Shipping', 9, 10],
    ['Missing items', 'Shipping', 8, 8],
    ['Delivery confirmation requests', 'Shipping', 2, 2],
    ['Logistics coordination', 'Shipping', 2, 2],
    ['Furniture', 'Facilities', 1, 1],
    ['Lighting', 'Facilities', 3, 5],
    ['Plumbing', 'Facilities', 5, 8],
    ['Airconditioning', 'Facilities', 5, 5],
    ['Renovation', 'Facilities', 3, 6],
    ['Electrical', 'Facilities', 7, 8],
    ['Other', 'IT', 1, 1],
    ['Other', 'Finance', 1, 1],
    ['Other', 'Engineering', 1, 1],
    ['Other', 'HR', 1, 1],
    ['Other', 'Warehouse', 1, 1],
    ['Other', 'Production', 1, 1],
    ['Other', 'Sales', 1, 1],
    ['Other', 'Shipping', 1, 1],
    ['Other', 'Facilities', 1, 1],
];

$stmt = $conn->prepare("INSERT INTO tbl_sla_weight (category, department_name, time_value, importance) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE time_value = VALUES(time_value), importance = VALUES(importance)");

$inserted = 0;
foreach ($seedData as $row) {
    $stmt->bind_param("ssii", $row[0], $row[1], $row[2], $row[3]);
    if ($stmt->execute()) {
        $inserted++;
    }
}
$stmt->close();

echo "Seeded $inserted SLA weight entries\n";
echo "\nMigration completed successfully.\n";
