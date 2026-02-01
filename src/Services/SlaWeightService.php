<?php

namespace Services;

use Database\Connection;

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

    /** Map form category strings to tbl_sla_weight category (for variations) */
    private static $categoryAliases = [
        'System Access or Login Issues' => 'System access',
        'Network or router troubleshooting' => 'Network or router troubleshooting',
        'Coordination with other departments' => 'Coordination with other departments',
        'Biling or reconciliation disputs' => 'Billing or reconciliation disputes',
        'Report Generation errors' => 'report generation errors',
        'Finandial data sync issues' => 'Financial data sync issues',
        'Apprval for replacement items' => 'Approval for replacement items',
        'Onboarding or Offboarding system access' => 'Onboarding or offboarding system access',
        'Delivery Confirmation requests' => 'Delivery confirmation requests',
        'Air Conditioning' => 'Airconditioning',
        'System-generated report errors' => 'System generated report errors',
    ];

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
        $result = [
            'priority_score' => 0.0,
            'priority' => 'low',
            'department_id' => null,
            'department_name' => null,
            'sla_weight' => null,
        ];

        $sla = $this->getByCategoryAndType($category, $type);
        if (!$sla) {
            return $result;
        }

        $result['sla_weight'] = $sla;
        $result['department_name'] = $sla['department_name'];

        $T = (int) $sla['time_value'];
        $I = (int) $sla['importance'];
        $C = (strtolower($userType) === 'external') ? 9 : 8;

        $P = (0.5 * $I) + (0.3 * $C) + (0.2 * (11 - $T));
        $P = $this->applyOverrideRules($P, $I, $C);

        $result['priority_score'] = round($P, 2);
        $result['priority'] = $this->mapScoreToPriority($P);
        $result['department_id'] = $this->getDepartmentId($sla['department_name']);

        return $result;
    }

    /**
     * Get SLA weight row by category and department/type
     */
    public function getByCategoryAndType(string $category, string $type): ?array
    {
        $category = trim($category);
        $type = trim($type);
        if (empty($category) || empty($type)) {
            return null;
        }

        $normalizedCategory = self::$categoryAliases[$category] ?? $category;

        $sql = "SELECT sla_weight_id, category, department_name, time_value, importance 
                FROM tbl_sla_weight 
                WHERE category = ? AND department_name = ? 
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("ss", $normalizedCategory, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            return $row;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $category, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    private function applyOverrideRules(float $p, int $i, int $c): float
    {
        if ($i >= 9 && $c >= 8) {
            return 9.0;
        }
        if ($c >= 9 && $i >= 7) {
            return 8.0;
        }
        return $p;
    }

    private function mapScoreToPriority(float $p): string
    {
        if ($p >= 9) {
            return 'urgent';
        }
        if ($p >= 8) {
            return 'high';
        }
        if ($p >= 7) {
            return 'regular';
        }
        return 'low';
    }

    /**
     * Map department name (IT, HR, etc.) to tbl_department.department_id
     */
    public function getDepartmentId(string $departmentName): ?int
    {
        $map = [
            'IT' => 'Engineering',
            'Engineering' => 'Engineering',
            'HR' => 'Human Resource',
            'Finance' => 'Finance',
            'Warehouse' => 'Warehouse',
            'Production' => 'Production',
            'Sales' => 'Sales',
            'Shipping' => 'Shipping',
            'Facilities' => 'Facilities',
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
}
