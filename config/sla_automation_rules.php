<?php
/**
 * Central SLA automation rules and helpers.
 */

if (!function_exists('slaAutomationRules')) {
    function slaAutomationRules(): array
    {
        static $rules = null;
        if ($rules !== null) {
            return $rules;
        }

        $rules = [
            'weights' => [
                'importance' => 0.5,
                'customer' => 0.3,
                'time' => 0.2,
            ],
            'customer_weight' => [
                'internal' => 8,
                'external' => 9,
            ],
            'override_rules' => [
                ['min_importance' => 9, 'min_customer' => 8, 'force_score' => 9.0],
                ['min_importance' => 7, 'min_customer' => 9, 'force_score' => 8.0],
            ],
            'score_priority_map' => [
                ['min' => 9.0, 'priority' => 'urgent'],
                ['min' => 8.0, 'priority' => 'high'],
                ['min' => 7.0, 'priority' => 'regular'],
                ['min' => 0.0, 'priority' => 'low'],
            ],
            'priority_rank' => [
                'low' => 1,
                'regular' => 2,
                'high' => 3,
                'urgent' => 4,
            ],
            'approaching_hours' => [
                'urgent' => 4,
                'high' => 12,
                'regular' => 24,
                'low' => 48,
            ],
            'sla_days' => [
                'urgent' => 1,
                'high' => 1,
                'regular' => 3,
                'low' => 7,
            ],
            'escalation_dedupe_hours' => 4,
            'category_aliases' => [
                'System Access or Login Issues' => 'System access',
                'system access or login issues' => 'System access',
                'Biling or reconciliation disputs' => 'Billing or reconciliation disputes',
                'biling or reconciliation disputs' => 'Billing or reconciliation disputes',
                'Billing or reconciliation disputes' => 'Billing or reconciliation disputes',
                'Payment verification issues' => 'payment verification issues',
                'Report Generation errors' => 'report generation errors',
                'report generation errors' => 'report generation errors',
                'Finandial data sync issues' => 'Financial data sync issues',
                'finandial data sync issues' => 'Financial data sync issues',
                'Apprval for replacement items' => 'Approval for replacement items',
                'apprval for replacement items' => 'Approval for replacement items',
                'Onboarding or Offboarding system access' => 'Onboarding or offboarding system access',
                'onboarding or offboarding system access' => 'Onboarding or offboarding system access',
                'Delivery Confirmation requests' => 'Delivery confirmation requests',
                'delivery confirmation requests' => 'Delivery confirmation requests',
                'Air Conditioning' => 'Airconditioning',
                'air conditioning' => 'Airconditioning',
                'System-generated report errors' => 'System generated report errors',
                'system-generated report errors' => 'System generated report errors',
                'System geerated report errors' => 'System generated report errors',
                'system geerated report errors' => 'System generated report errors',
                'Warraty record assistance' => 'Warranty record assistance',
                'warraty record assistance' => 'Warranty record assistance',
            ],
        ];

        return $rules;
    }
}

if (!function_exists('slaNormalizeCategory')) {
    function slaNormalizeCategory(string $category): string
    {
        $raw = trim($category);
        if ($raw === '') {
            return '';
        }

        $aliases = slaAutomationRules()['category_aliases'];
        if (isset($aliases[$raw])) {
            return $aliases[$raw];
        }

        $lower = strtolower($raw);
        if (isset($aliases[$lower])) {
            return $aliases[$lower];
        }

        return $raw;
    }
}

if (!function_exists('slaNormalizePriority')) {
    function slaNormalizePriority(string $priority): string
    {
        $p = strtolower(trim($priority));
        if ($p === 'medium') {
            return 'regular';
        }
        if ($p === 'critical') {
            return 'urgent';
        }
        if (!in_array($p, ['low', 'regular', 'high', 'urgent'], true)) {
            return 'low';
        }
        return $p;
    }
}

if (!function_exists('slaPriorityRank')) {
    function slaPriorityRank(string $priority): int
    {
        $priority = slaNormalizePriority($priority);
        $ranks = slaAutomationRules()['priority_rank'];
        return (int)($ranks[$priority] ?? 1);
    }
}

if (!function_exists('slaPriorityToUrgency')) {
    function slaPriorityToUrgency(string $priority): string
    {
        $priority = slaNormalizePriority($priority);
        if ($priority === 'urgent') {
            return 'urgent';
        }
        if ($priority === 'high') {
            return 'high';
        }
        if ($priority === 'regular') {
            return 'medium';
        }
        return 'low';
    }
}

if (!function_exists('slaApproachingHoursForPriority')) {
    function slaApproachingHoursForPriority(string $priority): int
    {
        $priority = slaNormalizePriority($priority);
        $windows = slaAutomationRules()['approaching_hours'];
        return (int)($windows[$priority] ?? 24);
    }
}

if (!function_exists('slaPrioritySlaDays')) {
    function slaPrioritySlaDays(string $priority): int
    {
        $priority = slaNormalizePriority($priority);
        $days = slaAutomationRules()['sla_days'];
        return (int)($days[$priority] ?? 7);
    }
}

if (!function_exists('slaCustomerWeight')) {
    function slaCustomerWeight(string $userType): int
    {
        $u = strtolower(trim($userType));
        $weights = slaAutomationRules()['customer_weight'];
        return (int)($weights[$u] ?? $weights['internal']);
    }
}

if (!function_exists('slaComputePriorityScore')) {
    function slaComputePriorityScore(int $timeValue, int $importance, string $userType = 'internal'): float
    {
        $rules = slaAutomationRules();
        $timeValue = max(1, min(10, (int)$timeValue));
        $importance = max(1, min(10, (int)$importance));
        $customerWeight = slaCustomerWeight($userType);

        $score = ($rules['weights']['importance'] * $importance)
            + ($rules['weights']['customer'] * $customerWeight)
            + ($rules['weights']['time'] * (11 - $timeValue));

        foreach ($rules['override_rules'] as $override) {
            if ($importance >= $override['min_importance'] && $customerWeight >= $override['min_customer']) {
                $score = (float)$override['force_score'];
                break;
            }
        }

        return round($score, 2);
    }
}

if (!function_exists('slaMapScoreToPriority')) {
    function slaMapScoreToPriority(float $score): string
    {
        $map = slaAutomationRules()['score_priority_map'];
        foreach ($map as $row) {
            if ($score >= (float)$row['min']) {
                return (string)$row['priority'];
            }
        }
        return 'low';
    }
}

if (!function_exists('slaEscalationDedupeHours')) {
    function slaEscalationDedupeHours(): int
    {
        return (int)(slaAutomationRules()['escalation_dedupe_hours'] ?? 4);
    }
}

