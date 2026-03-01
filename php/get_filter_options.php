<?php
/**
 * Customer-management filter options API.
 * Optimized to use tbl_user_ticket_summary for dynamic SLA/activity counts.
 */

@ini_set('display_errors', '0');
ob_start();

require_once 'db.php';

class FilterOptionsAPI extends BaseAPI
{
    public function handleRequest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $search = $this->sanitizeInput($_GET['q'] ?? '');
            $userType = $this->sanitizeInput($_GET['user_type'] ?? 'all');
            $filterType = $this->sanitizeInput($_GET['type'] ?? 'all');

            $response = [];
            if ($filterType === 'all' || $filterType === 'user_types') {
                $response['user_types'] = $this->getUserTypes($search);
            }

            $source = 'summary';
            $counts = $this->getSummaryCounts($search, $userType);
            if ($counts === null) {
                $source = 'fallback';
                $counts = $this->getFallbackCounts($search, $userType);
            }

            if ($filterType === 'all' || $filterType === 'sla_statuses') {
                $response['sla_statuses'] = $this->buildSlaOptions($counts);
            }
            if ($filterType === 'all' || $filterType === 'activity_statuses') {
                $response['activity_statuses'] = $this->buildActivityOptions($counts);
            }

            $response['source'] = $source;

            if (ob_get_level()) {
                ob_end_clean();
            }
            $this->sendResponse($response);
        } catch (Throwable $e) {
            error_log('get_filter_options error: ' . $e->getMessage());
            if (ob_get_level()) {
                ob_end_clean();
            }
            $this->sendResponse([
                'user_types' => [['value' => 'all', 'label' => 'All Users']],
                'sla_statuses' => [['value' => 'all', 'label' => 'All SLA Status']],
                'activity_statuses' => [['value' => 'all', 'label' => 'All Activity']],
                'source' => 'error',
            ]);
        }
    }

    private function getUserTypes(string $search): array
    {
        $params = [];
        $types = '';
        $where = $this->buildUserWhere($search, 'all', $params, $types, false);

        $sql = "SELECT u.user_type, COUNT(*) AS cnt FROM tbl_user u {$where} GROUP BY u.user_type";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [['value' => 'all', 'label' => 'All Users']];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $map = ['external' => 0, 'internal' => 0];
        while ($row = $res->fetch_assoc()) {
            $ut = $row['user_type'] ?? '';
            if (isset($map[$ut])) {
                $map[$ut] = (int)$row['cnt'];
            }
        }
        $stmt->close();

        $out = [['value' => 'all', 'label' => 'All Users']];
        foreach (['internal', 'external'] as $ut) {
            if ($map[$ut] > 0) {
                $out[] = [
                    'value' => $ut,
                    'label' => ucfirst($ut) . ' (' . $map[$ut] . ')',
                ];
            }
        }

        return $out;
    }

    /**
     * Returns summary-backed counts, or null if summary table is unavailable.
     */
    private function getSummaryCounts(string $search, string $userType): ?array
    {
        if (!$this->hasSummaryTable()) {
            return null;
        }

        $params = [];
        $types = '';
        $where = $this->buildUserWhere($search, $userType, $params, $types, true);

        $sql = "
            SELECT
                COUNT(*) AS users_total,
                SUM(CASE WHEN COALESCE(s.ticket_count, 0) >= 3 AND COALESCE(s.urgent_high_count, 0) > 0 THEN 1 ELSE 0 END) AS sla_priority,
                SUM(CASE WHEN s.last_contact >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS sla_recent,
                SUM(CASE WHEN COALESCE(s.success_rate, 0) >= 80 THEN 1 ELSE 0 END) AS sla_success,
                SUM(CASE WHEN s.current_ticket_status IN ('assigning', 'pending', 'followup') THEN 1 ELSE 0 END) AS activity_active,
                SUM(CASE WHEN s.sla_status = 'At Risk' THEN 1 ELSE 0 END) AS activity_overdue,
                SUM(CASE WHEN COALESCE(s.ticket_count, 0) <= 2 THEN 1 ELSE 0 END) AS activity_churn
            FROM tbl_user u
            LEFT JOIN tbl_user_ticket_summary s ON s.user_id = u.user_id
            {$where}
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        return [
            'sla_priority' => (int)($row['sla_priority'] ?? 0),
            'sla_recent' => (int)($row['sla_recent'] ?? 0),
            'sla_success' => (int)($row['sla_success'] ?? 0),
            'activity_active' => (int)($row['activity_active'] ?? 0),
            'activity_overdue' => (int)($row['activity_overdue'] ?? 0),
            'activity_churn' => (int)($row['activity_churn'] ?? 0),
        ];
    }

    /**
     * Fallback counts when summary table is missing.
     * Keeps behavior functional, but this path is intentionally conservative.
     */
    private function getFallbackCounts(string $search, string $userType): array
    {
        $params = [];
        $types = '';
        $where = $this->buildUserWhere($search, $userType, $params, $types, true);

        $sql = "
            SELECT
                SUM(CASE WHEN agg.ticket_count >= 3 AND agg.urgent_high_count > 0 THEN 1 ELSE 0 END) AS sla_priority,
                SUM(CASE WHEN agg.last_contact >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS sla_recent,
                SUM(CASE WHEN COALESCE(agg.success_rate, 0) >= 80 THEN 1 ELSE 0 END) AS sla_success,
                SUM(CASE WHEN latest.current_ticket_status IN ('assigning', 'pending', 'followup') THEN 1 ELSE 0 END) AS activity_active,
                SUM(CASE WHEN agg.sla_status = 'At Risk' THEN 1 ELSE 0 END) AS activity_overdue,
                SUM(CASE WHEN COALESCE(agg.ticket_count, 0) <= 2 THEN 1 ELSE 0 END) AS activity_churn
            FROM tbl_user u
            LEFT JOIN (
                SELECT
                    t.user_id,
                    COUNT(*) AS ticket_count,
                    MAX(t.created_at) AS last_contact,
                    ROUND((SUM(CASE WHEN t.status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 1) AS success_rate,
                    CASE
                        WHEN SUM(CASE WHEN t.sla_date < CURDATE() AND t.status != 'complete' THEN 1 ELSE 0 END) > 0 THEN 'At Risk'
                        WHEN SUM(CASE WHEN t.sla_date >= CURDATE() AND t.sla_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND t.status != 'complete' THEN 1 ELSE 0 END) > 0 THEN 'Approaching'
                        ELSE 'On Track'
                    END AS sla_status,
                    SUM(CASE WHEN t.priority IN ('urgent', 'high') THEN 1 ELSE 0 END) AS urgent_high_count
                FROM tbl_ticket t
                GROUP BY t.user_id
            ) agg ON agg.user_id = u.user_id
            LEFT JOIN (
                SELECT x.user_id, x.status AS current_ticket_status
                FROM (
                    SELECT t.user_id, t.status, ROW_NUMBER() OVER (PARTITION BY t.user_id ORDER BY t.created_at DESC, t.ticket_id DESC) AS rn
                    FROM tbl_ticket t
                ) x
                WHERE x.rn = 1
            ) latest ON latest.user_id = u.user_id
            {$where}
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [
                'sla_priority' => 0,
                'sla_recent' => 0,
                'sla_success' => 0,
                'activity_active' => 0,
                'activity_overdue' => 0,
                'activity_churn' => 0,
            ];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'sla_priority' => (int)($row['sla_priority'] ?? 0),
            'sla_recent' => (int)($row['sla_recent'] ?? 0),
            'sla_success' => (int)($row['sla_success'] ?? 0),
            'activity_active' => (int)($row['activity_active'] ?? 0),
            'activity_overdue' => (int)($row['activity_overdue'] ?? 0),
            'activity_churn' => (int)($row['activity_churn'] ?? 0),
        ];
    }

    private function buildSlaOptions(array $counts): array
    {
        return [
            ['value' => 'all', 'label' => 'All SLA Status'],
            ['value' => 'priority', 'label' => 'Priority Clients (3+ tickets, urgent/high) (' . (int)$counts['sla_priority'] . ')'],
            ['value' => 'recent', 'label' => 'Recently Contacted (30 days) (' . (int)$counts['sla_recent'] . ')'],
            ['value' => 'success', 'label' => 'High Success Rate (80%+, fast resolution) (' . (int)$counts['sla_success'] . ')'],
        ];
    }

    private function buildActivityOptions(array $counts): array
    {
        $options = [
            ['value' => 'all', 'label' => 'All Activity'],
        ];

        $map = [
            'active' => ['count' => (int)$counts['activity_active'], 'label' => 'Active (Assigning, Pending, Follow-up)'],
            'overdue' => ['count' => (int)$counts['activity_overdue'], 'label' => 'Overdue (Past SLA dates)'],
            'churn_risk' => ['count' => (int)$counts['activity_churn'], 'label' => 'Churn Risk (<=2 tickets)'],
        ];

        foreach ($map as $value => $cfg) {
            if ($cfg['count'] > 0) {
                $options[] = [
                    'value' => $value,
                    'label' => $cfg['label'] . ' (' . $cfg['count'] . ')',
                ];
            }
        }

        return $options;
    }

    private function buildUserWhere(string $search, string $userType, array &$params, string &$types, bool $includeUserType): string
    {
        $conditions = [];

        if ($includeUserType && in_array($userType, ['internal', 'external'], true)) {
            $conditions[] = 'u.user_type = ?';
            $params[] = $userType;
            $types .= 's';
        }

        if ($search !== '') {
            $conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR CAST(u.user_id AS CHAR) LIKE ? OR u.phone LIKE ?)";
            $needle = '%' . $search . '%';
            $params = array_merge($params, [$needle, $needle, $needle, $needle, $needle]);
            $types .= 'sssss';
        }

        if (empty($conditions)) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $conditions);
    }

    private function hasSummaryTable(): bool
    {
        $res = @$this->conn->query("SHOW TABLES LIKE 'tbl_user_ticket_summary'");
        return $res && $res->num_rows > 0;
    }
}

$api = new FilterOptionsAPI();
$api->handleRequest();

