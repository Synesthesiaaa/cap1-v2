<?php

namespace Services;

use Database\Connection;
require_once __DIR__ . '/../../config/sla_automation_rules.php';

/**
 * SLA Weight Service
 *
 * Computes priority scores from category, time, importance, and customer type.
 * Formula: P = (0.5*I) + (0.3*C) + (0.2*(11-T))
 * Override rules: I>=9 & C>=8 -> P=9; C>=9 & I>=7 -> P=8
 */
class SlaWeightService
{
    private $conn;

    public function __construct()
    {
        $this->conn = Connection::getInstance()->getConnection();
    }

    /**
     * Compute priority score and related data from category and user type
     *
     * @param string $category Ticket category
     * @param string $type Department/type (IT, Finance, HR, etc.)
     * @param string $userType internal|external
     * @return array ['priority_score' => float, 'priority' => string, 'department_id' => int|null, 'department_name' => string|null, 'sla_weight' => array|null]
     */
    public function computePriorityScore(string $category, string $type, string $userType = 'internal'): array
    {
        $normalizedCategory = $this->normalizeCategory($category);
        $customerWeight = \slaCustomerWeight($userType);
        $result = [
            'priority_score' => 0.0,
            'priority' => 'low',
            'department_id' => null,
            'department_name' => null,
            'sla_weight' => null,
            'matched_sla_weight_id' => null,
            'normalized_category' => $normalizedCategory,
            'customer_weight' => $customerWeight,
        ];

        $sla = $this->getByCategoryAndType($normalizedCategory, $type, $category);
        if (!$sla) {
            return $result;
        }

        $result['sla_weight'] = $sla;
        $result['matched_sla_weight_id'] = (int)$sla['sla_weight_id'];
        $result['department_name'] = $sla['department_name'];
        $result['normalized_category'] = (string)$sla['category'];
        $result['priority_score'] = \slaComputePriorityScore((int)$sla['time_value'], (int)$sla['importance'], $userType);
        $result['priority'] = \slaMapScoreToPriority((float)$result['priority_score']);
        $result['department_id'] = $this->getDepartmentId($sla['department_name']);

        return $result;
    }

    /**
     * Get SLA weight row by category and department/type
     */
    public function getByCategoryAndType(string $category, string $type, ?string $originalCategory = null): ?array
    {
        $category = trim($category);
        $type = trim($type);
        if (empty($category) || empty($type)) {
            return null;
        }

        $sql = "SELECT sla_weight_id, category, department_name, time_value, importance 
                FROM tbl_sla_weight 
                WHERE category = ? AND department_name = ? 
                LIMIT 1";
        $candidates = [$category];
        if ($originalCategory !== null) {
            $orig = trim($originalCategory);
            if ($orig !== '' && !in_array($orig, $candidates, true)) {
                $candidates[] = $orig;
            }
        }

        foreach ($candidates as $cat) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return null;
            }
            $stmt->bind_param("ss", $cat, $type);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Map department name (IT, HR, etc.) to tbl_department.department_id
     */
    public function getDepartmentId(string $departmentName): ?int
    {
        $map = [
            'IT' => 'IT',
            'Engineering' => 'Engineering',
            'HR' => 'HR',
            'Finance' => 'Finance',
            'Warehouse' => 'Warehouse',
            'Production' => 'Production',
            'Sales' => 'Sales',
            'Shipping' => 'Shipping',
            'Facilities' => 'Facilities',
            'Human Resource' => 'HR',
        ];

        $deptName = $map[$departmentName] ?? $departmentName;

        $stmt = $this->conn->prepare("SELECT department_id FROM tbl_department WHERE department_name = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("s", $deptName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int) $row['department_id'] : null;
    }

    public function normalizeCategory(string $category): string
    {
        return \slaNormalizeCategory($category);
    }
}
