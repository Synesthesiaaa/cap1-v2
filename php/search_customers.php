<?php
/**
 * Customer search API (optimized for customer management).
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ob_start();

require_once 'db.php';

class CustomerSearchAPI extends BaseAPI
{
    public function handleRequest(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();

        header('Content-Type: application/json; charset=utf-8');
        header('Connection: keep-alive');

        $search = $this->sanitizeInput($_GET['q'] ?? '');
        $userType = $this->sanitizeInput($_GET['user_type'] ?? 'all');
        $slaStatus = $this->sanitizeInput($_GET['sla_status'] ?? 'all');
        $activityStatus = $this->sanitizeInput($_GET['activity_status'] ?? 'all');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(10, min(50, (int)($_GET['limit'] ?? 20)));

        $useSummary = $this->hasSummaryTable();

        if ($useSummary) {
            [$countSql, $countParams, $countTypes] = $this->buildSummaryCountQuery($search, $userType, $slaStatus, $activityStatus);
            $total = $this->fetchScalarInt($countSql, $countParams, $countTypes);

            [$listSql, $listParams, $listTypes] = $this->buildSummaryListQuery($search, $userType, $slaStatus, $activityStatus, $page, $limit);
            $customers = $this->fetchRows($listSql, $listParams, $listTypes);
        } else {
            [$countSql, $countParams, $countTypes] = $this->buildFallbackCountQuery($search, $userType, $slaStatus, $activityStatus);
            $total = $this->fetchScalarInt($countSql, $countParams, $countTypes);

            [$listSql, $listParams, $listTypes] = $this->buildFallbackListQuery($search, $userType, $slaStatus, $activityStatus, $page, $limit);
            $customers = $this->fetchRows($listSql, $listParams, $listTypes);
        }

        $this->formatRows($customers);

        if (ob_get_level()) {
            ob_end_clean();
        }
        $this->sendResponse([
            'customers' => $customers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_count' => $total,
                'total_pages' => $limit > 0 ? (int)ceil($total / $limit) : 0,
            ],
        ]);
    }

    private function hasSummaryTable(): bool
    {
        $r = @$this->conn->query("SHOW TABLES LIKE 'tbl_user_ticket_summary'");
        return $r && $r->num_rows > 0;
    }

    private function hasNotesColumn(): bool
    {
        static $has = null;
        if ($has === null) {
            $r = @$this->conn->query("SHOW COLUMNS FROM tbl_user LIKE 'notes'");
            $has = $r && $r->num_rows > 0;
        }
        return $has;
    }

    private function buildSummaryFilters(string $search, string $userType, string $slaStatus, string $activityStatus, array &$params, string &$types): string
    {
        $conds = [];

        if ($userType === 'internal' || $userType === 'external') {
            $conds[] = 'u.user_type = ?';
            $params[] = $userType;
            $types .= 's';
        }

        if ($search !== '') {
            $conds[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR CAST(u.user_id AS CHAR) LIKE ?)";
            $needle = '%' . $search . '%';
            $params = array_merge($params, [$needle, $needle, $needle, $needle]);
            $types .= 'ssss';
        }

        if ($slaStatus === 'priority') {
            $conds[] = 'COALESCE(s.ticket_count, 0) >= ?';
            $conds[] = 'COALESCE(s.urgent_high_count, 0) > ?';
            $params[] = 3;
            $params[] = 0;
            $types .= 'ii';
        } elseif ($slaStatus === 'recent') {
            $conds[] = 's.last_contact >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        } elseif ($slaStatus === 'success') {
            $conds[] = 'COALESCE(s.success_rate, 0) >= ?';
            $params[] = 80.0;
            $types .= 'd';
        }

        if ($activityStatus === 'active') {
            $conds[] = "s.current_ticket_status IN ('assigning','pending','followup')";
        } elseif ($activityStatus === 'overdue') {
            $conds[] = "s.sla_status = 'At Risk'";
        } elseif ($activityStatus === 'churn_risk') {
            $conds[] = 'COALESCE(s.ticket_count, 0) <= ?';
            $params[] = 2;
            $types .= 'i';
        }

        return empty($conds) ? '' : (' WHERE ' . implode(' AND ', $conds));
    }

    private function buildSummaryCountQuery(string $search, string $userType, string $slaStatus, string $activityStatus): array
    {
        $params = [];
        $types = '';
        $where = $this->buildSummaryFilters($search, $userType, $slaStatus, $activityStatus, $params, $types);

        $sql = "
            SELECT COUNT(*) AS total
            FROM tbl_user u
            LEFT JOIN tbl_user_ticket_summary s ON s.user_id = u.user_id
            {$where}
        ";
        return [$sql, $params, $types];
    }

    private function buildSummaryListQuery(string $search, string $userType, string $slaStatus, string $activityStatus, int $page, int $limit): array
    {
        $params = [];
        $types = '';
        $where = $this->buildSummaryFilters($search, $userType, $slaStatus, $activityStatus, $params, $types);

        $offset = ($page - 1) * $limit;
        $params[] = $offset;
        $params[] = $limit;
        $types .= 'ii';

        $notesSel = $this->hasNotesColumn() ? ', u.notes' : ', NULL AS notes';
        $sql = "
            SELECT
                u.user_id,
                u.name,
                u.email,
                u.user_type,
                u.status,
                u.company,
                u.created_at,
                d.department_name,
                COALESCE(s.ticket_count, 0) AS ticket_count,
                s.last_contact,
                COALESCE(s.success_rate, 0) AS success_rate,
                COALESCE(s.sla_status, 'On Track') AS sla_status,
                COALESCE(s.success_rate, 0) AS csat_score,
                s.current_ticket_status
                {$notesSel}
            FROM tbl_user u
            LEFT JOIN tbl_user_ticket_summary s ON s.user_id = u.user_id
            LEFT JOIN tbl_department d ON d.department_id = u.department_id
            {$where}
            ORDER BY
                FIELD(COALESCE(s.current_ticket_status, ''), 'followup', 'pending', 'assigning', 'complete', '') ASC,
                u.user_id DESC
            LIMIT ?, ?
        ";

        return [$sql, $params, $types];
    }

    private function buildFallbackCountQuery(string $search, string $userType, string $slaStatus, string $activityStatus): array
    {
        $params = [];
        $types = '';
        [$whereSql, $havingSql, $params, $types] = $this->buildFallbackClauses($search, $userType, $slaStatus, $activityStatus);

        $sql = "
            SELECT COUNT(*) AS total
            FROM (
                SELECT u.user_id
                FROM tbl_user u
                LEFT JOIN tbl_ticket t ON t.user_id = u.user_id
                {$whereSql}
                GROUP BY u.user_id
                {$havingSql}
            ) temp
        ";
        return [$sql, $params, $types];
    }

    private function buildFallbackListQuery(string $search, string $userType, string $slaStatus, string $activityStatus, int $page, int $limit): array
    {
        [$whereSql, $havingSql, $params, $types] = $this->buildFallbackClauses($search, $userType, $slaStatus, $activityStatus);

        $offset = ($page - 1) * $limit;
        $params[] = $offset;
        $params[] = $limit;
        $types .= 'ii';

        $notesSel = $this->hasNotesColumn() ? ', u.notes' : ', NULL AS notes';
        $notesGrp = $this->hasNotesColumn() ? ', u.notes' : '';

        $sql = "
            SELECT
                u.user_id,
                u.name,
                u.email,
                u.user_type,
                u.status,
                u.company,
                u.created_at,
                d.department_name,
                COUNT(t.ticket_id) AS ticket_count,
                MAX(t.created_at) AS last_contact,
                ROUND((SUM(CASE WHEN t.status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(t.ticket_id), 0)) * 100, 1) AS success_rate,
                CASE
                    WHEN SUM(CASE WHEN t.sla_date < CURDATE() AND t.status != 'complete' THEN 1 ELSE 0 END) > 0 THEN 'At Risk'
                    WHEN SUM(CASE WHEN t.sla_date >= CURDATE() AND t.sla_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND t.status != 'complete' THEN 1 ELSE 0 END) > 0 THEN 'Approaching'
                    ELSE 'On Track'
                END AS sla_status,
                ROUND((SUM(CASE WHEN t.status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(t.ticket_id), 0)) * 100, 1) AS csat_score,
                latest.current_ticket_status
                {$notesSel}
            FROM tbl_user u
            LEFT JOIN (
                SELECT x.user_id, x.status AS current_ticket_status
                FROM (
                    SELECT tt.user_id, tt.status, ROW_NUMBER() OVER (PARTITION BY tt.user_id ORDER BY tt.created_at DESC, tt.ticket_id DESC) AS rn
                    FROM tbl_ticket tt
                ) x
                WHERE x.rn = 1
            ) latest ON latest.user_id = u.user_id
            LEFT JOIN tbl_department d ON d.department_id = u.department_id
            LEFT JOIN tbl_ticket t ON t.user_id = u.user_id
            {$whereSql}
            GROUP BY u.user_id, latest.current_ticket_status{$notesGrp}
            {$havingSql}
            ORDER BY
                CASE
                    WHEN latest.current_ticket_status = 'followup' THEN 1
                    WHEN latest.current_ticket_status = 'pending' THEN 2
                    WHEN latest.current_ticket_status = 'assigning' THEN 3
                    WHEN latest.current_ticket_status = 'complete' THEN 4
                    WHEN latest.current_ticket_status IS NULL THEN 5
                    ELSE 6
                END ASC,
                u.user_id DESC
            LIMIT ?, ?
        ";
        return [$sql, $params, $types];
    }

    private function buildFallbackClauses(string $search, string $userType, string $slaStatus, string $activityStatus): array
    {
        $where = [];
        $having = [];
        $params = [];
        $types = '';

        if ($userType === 'internal' || $userType === 'external') {
            $where[] = 'u.user_type = ?';
            $params[] = $userType;
            $types .= 's';
        }

        if ($search !== '') {
            $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR CAST(u.user_id AS CHAR) LIKE ?)";
            $needle = '%' . $search . '%';
            $params = array_merge($params, [$needle, $needle, $needle, $needle]);
            $types .= 'ssss';
        }

        if ($slaStatus === 'priority') {
            $having[] = 'COUNT(t.ticket_id) >= ?';
            $having[] = "SUM(CASE WHEN t.priority IN ('urgent', 'high') THEN 1 ELSE 0 END) > ?";
            $params[] = 3;
            $params[] = 0;
            $types .= 'ii';
        } elseif ($slaStatus === 'recent') {
            $having[] = 'MAX(t.created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        } elseif ($slaStatus === 'success') {
            $having[] = '(SUM(CASE WHEN t.status = \'complete\' THEN 1 ELSE 0 END) / NULLIF(COUNT(t.ticket_id), 0)) >= ?';
            $params[] = 0.8;
            $types .= 'd';
        }

        if ($activityStatus === 'active') {
            $having[] = "SUM(CASE WHEN t.status IN ('assigning', 'pending', 'followup') THEN 1 ELSE 0 END) > ?";
            $params[] = 0;
            $types .= 'i';
        } elseif ($activityStatus === 'overdue') {
            $having[] = "SUM(CASE WHEN t.sla_date < CURDATE() AND t.status != 'complete' THEN 1 ELSE 0 END) > ?";
            $params[] = 0;
            $types .= 'i';
        } elseif ($activityStatus === 'churn_risk') {
            $having[] = 'COUNT(t.ticket_id) <= ?';
            $params[] = 2;
            $types .= 'i';
        }

        $whereSql = empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));
        $havingSql = empty($having) ? '' : (' HAVING ' . implode(' AND ', $having));

        return [$whereSql, $havingSql, $params, $types];
    }

    private function fetchScalarInt(string $sql, array $params, string $types): int
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    private function fetchRows(string $sql, array $params, string $types): array
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    private function formatRows(array &$rows): void
    {
        foreach ($rows as &$row) {
            $row['ticket_count'] = (int)($row['ticket_count'] ?? 0);
            $row['success_rate'] = isset($row['success_rate']) ? round((float)$row['success_rate'], 1) : 0.0;

            if (!empty($row['last_contact'])) {
                $row['last_contact_formatted'] = date('M j, Y', strtotime($row['last_contact']));
            }
            if (!empty($row['created_at'])) {
                $row['created_at_formatted'] = date('M j, Y', strtotime($row['created_at']));
            }
        }
        unset($row);
    }
}

$api = new CustomerSearchAPI();
$api->handleRequest();

