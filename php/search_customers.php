<?php
/**
 * Optimized Customer Search API
 *
 * Enhanced performance with caching, modular filters, and optimized queries
 */

require_once 'db.php';

/**
 * Customer Search API Class
 */
class CustomerSearchAPI extends BaseAPI {
    private const CACHE_TTL = 300; // 5 minutes

    public function handleRequest() {
        header('Content-Type: application/json; charset=utf-8');

        // Get and sanitize input
        $search   = $this->sanitizeInput($_GET['q'] ?? '');
        $userType = $this->sanitizeInput($_GET['user_type'] ?? 'all');
        $slaStatus = $this->sanitizeInput($_GET['sla_status'] ?? 'all');
        $activityStatus = $this->sanitizeInput($_GET['activity_status'] ?? 'all');
        $page = max(1, intval($_GET['page'] ?? 1)); // Default to page 1
        $limit = max(10, min(50, intval($_GET['limit'] ?? 20))); // Default to 20, max 50, min 10

        // Debug logging
        error_log("Customer Search Request: search='$search', userType='$userType', slaStatus='$slaStatus', activityStatus='$activityStatus'");

        // Create cache key (note: not caching paginated results)
        $cacheKey = md5(serialize([
            'search' => $search,
            'userType' => $userType,
            'slaStatus' => $slaStatus,
            'activityStatus' => $activityStatus
        ]));

        // Use summary table for fast list/count when available (avoids joining 500k tickets)
        $useSummary = $this->useSummary();
        if ($useSummary) {
            $totalCount = $this->getTotalCountFromSummary($search, $userType, $slaStatus, $activityStatus);
            $query = $this->buildQueryFromSummary($search, $userType, $slaStatus, $activityStatus, $page, $limit);
        } else {
            $totalCount = $this->getTotalCount($search, $userType, $slaStatus, $activityStatus);
            $query = $this->buildQuery($search, $userType, $slaStatus, $activityStatus, $page, $limit);
        }

        $customers = $this->executeQuery($query);

        // Add pagination info to response
        $response = [
            'customers' => $customers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_count' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ];

        $this->sendResponse($response);
    }

    private function getTotalCount($search, $userType, $slaStatus, $activityStatus) {
        // Create count cache key
        $countCacheKey = md5(serialize([
            'search' => $search,
            'userType' => $userType,
            'slaStatus' => $slaStatus,
            'activityStatus' => $activityStatus,
            'count' => true
        ]));

        // Check cache first for total count
        $cachedTotal = QueryCache::get($countCacheKey);
        if ($cachedTotal !== null) {
            return $cachedTotal;
        }

        // Build count query with exact same conditions as main query
        // Build dynamic WHERE clause based on user_type filter
        $userTypeWhere = "";
        if ($userType === 'internal') {
            $userTypeWhere = "WHERE u.user_type = 'internal'";
        } elseif ($userType === 'external') {
            $userTypeWhere = "WHERE u.user_type = 'external'";
        } elseif ($userType === 'all') {
            $userTypeWhere = ""; // No filter, include all users
        }

        // Separate WHERE conditions (user-level) from HAVING conditions (aggregate-level)
        $whereConditions = [];
        $havingConditions = [];
        $params = [];
        $types = '';

        // Build WHERE conditions (user-level filtering)
        // Search conditions
        if (!empty($search)) {
            $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR CAST(u.user_id AS CHAR) LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= 'ssss';
        }

        // Filter conditions (aggregate conditions = HAVING)
        if ($slaStatus !== 'all') {
            $filters = $this->getSLAFilters($slaStatus);
            $havingConditions = array_merge($havingConditions, $filters['conditions']);
            $params = array_merge($params, $filters['params']);
            $types .= $filters['types'];
        }

        if ($activityStatus !== 'all') {
            $filters = $this->getActivityFilters($activityStatus);
            $havingConditions = array_merge($havingConditions, $filters['conditions']);
            $params = array_merge($params, $filters['params']);
            $types .= $filters['types'];
        }

        // Build WHERE clause
        $whereClause = "";
        if (!empty($whereConditions)) {
            if (empty($userTypeWhere)) {
                $whereClause = " WHERE " . implode(" AND ", $whereConditions);
            } else {
                $whereClause = " AND " . implode(" AND ", $whereConditions);
            }
        }

        // Build HAVING clause
        $havingClause = "";
        if (!empty($havingConditions)) {
            $havingClause = " HAVING " . implode(" AND ", $havingConditions);
        }

        $countQuery = "
            SELECT COUNT(*) as total FROM (
                SELECT u.user_id
                FROM tbl_user u
                LEFT JOIN tbl_department d ON u.department_id = d.department_id
                LEFT JOIN tbl_ticket t ON u.user_id = t.user_id
                {$userTypeWhere}
                {$whereClause}
                GROUP BY u.user_id
                {$havingClause}
            ) as temp_table
        ";

        try {
            $stmt = $this->conn->prepare($countQuery);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $total = $result->fetch_assoc()['total'];
            $stmt->close();

            // Cache the total count
            QueryCache::set($countCacheKey, $total);

            return $total;
        } catch (Exception $e) {
            error_log("Total count error: " . $e->getMessage());
            return 0;
        }
    }

    /** True if tbl_user_ticket_summary exists and has rows (fast path). */
    private function useSummary() {
        $r = @$this->conn->query("SELECT 1 FROM tbl_user_ticket_summary LIMIT 1");
        return $r && $r->num_rows > 0;
    }

    /** True if tbl_user has notes column (from migration). */
    private function hasNotesColumn() {
        static $has = null;
        if ($has === null) {
            $r = @$this->conn->query("SHOW COLUMNS FROM tbl_user LIKE 'notes'");
            $has = $r && $r->num_rows > 0;
        }
        return $has;
    }

    /** Fast count using summary table. */
    private function getTotalCountFromSummary($search, $userType, $slaStatus, $activityStatus) {
        $countCacheKey = md5(serialize([
            'search' => $search, 'userType' => $userType, 'slaStatus' => $slaStatus,
            'activityStatus' => $activityStatus, 'count' => true, 'summary' => true
        ]));
        $cachedTotal = QueryCache::get($countCacheKey);
        if ($cachedTotal !== null) {
            return $cachedTotal;
        }
        $userTypeWhere = ($userType === 'internal') ? "WHERE u.user_type = 'internal'" : (($userType === 'external') ? "WHERE u.user_type = 'external'" : "");
        $whereConditions = [];
        $params = [];
        $types = '';
        if (!empty($search)) {
            $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR CAST(u.user_id AS CHAR) LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= 'ssss';
        }
        $havingConditions = $this->getSummaryHavingConditions($slaStatus, $activityStatus, $params, $types);
        $whereClause = "";
        if (!empty($whereConditions)) {
            $whereClause = (empty($userTypeWhere) ? " WHERE " : " AND ") . implode(" AND ", $whereConditions);
        }
        $havingClause = empty($havingConditions) ? "" : " HAVING " . implode(" AND ", $havingConditions);
        $countQuery = "SELECT COUNT(*) AS total FROM (
            SELECT u.user_id FROM tbl_user u
            LEFT JOIN tbl_user_ticket_summary s ON u.user_id = s.user_id
            {$userTypeWhere}{$whereClause}
            GROUP BY u.user_id
            {$havingClause}
        ) AS t";
        try {
            $stmt = $this->conn->prepare($countQuery);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $total = (int)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            QueryCache::set($countCacheKey, $total);
            return $total;
        } catch (Exception $e) {
            return 0;
        }
    }

    /** HAVING conditions for summary table columns. Appends to $params and $types. */
    private function getSummaryHavingConditions($slaStatus, $activityStatus, &$params, &$types) {
        $conditions = [];
        if ($slaStatus === 'priority') {
            $conditions[] = "COALESCE(s.ticket_count,0) >= ? AND COALESCE(s.urgent_high_count,0) > ?";
            $params[] = 3;
            $params[] = 0;
            $types .= 'ii';
        } elseif ($slaStatus === 'recent') {
            $conditions[] = "s.last_contact >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        } elseif ($slaStatus === 'success') {
            $conditions[] = "s.success_rate >= ?";
            $params[] = 80.0;
            $types .= 'd';
        }
        if ($activityStatus === 'active') {
            $conditions[] = "s.current_ticket_status IN ('assigning','pending','followup')";
        } elseif ($activityStatus === 'overdue') {
            $conditions[] = "s.sla_status = 'At Risk'";
        } elseif ($activityStatus === 'churn_risk') {
            $conditions[] = "COALESCE(s.ticket_count,0) <= ?";
            $params[] = 2;
            $types .= 'i';
        }
        return $conditions;
    }

    /** Fast list query using summary table. */
    private function buildQueryFromSummary($search, $userType, $slaStatus, $activityStatus, $page, $limit) {
        $userTypeWhere = ($userType === 'internal') ? "WHERE u.user_type = 'internal'" : (($userType === 'external') ? "WHERE u.user_type = 'external'" : "");
        $whereConditions = [];
        $params = [];
        $types = '';
        if (!empty($search)) {
            $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR CAST(u.user_id AS CHAR) LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= 'ssss';
        }
        $havingConditions = $this->getSummaryHavingConditions($slaStatus, $activityStatus, $params, $types);
        $whereClause = "";
        if (!empty($whereConditions)) {
            $whereClause = (empty($userTypeWhere) ? " WHERE " : " AND ") . implode(" AND ", $whereConditions);
        }
        $havingClause = empty($havingConditions) ? "" : " HAVING " . implode(" AND ", $havingConditions);
        $offset = ($page - 1) * $limit;
        $params[] = $offset;
        $params[] = $limit;
        $types .= 'ii';
        $notesSel = $this->hasNotesColumn() ? ", u.notes" : ", NULL AS notes";
        $notesGrp = $this->hasNotesColumn() ? ", u.notes" : "";
        $baseQuery = "
            SELECT u.user_id, u.name, u.email, u.user_type, u.status, u.company, u.created_at, d.department_name,
                COALESCE(s.ticket_count,0) AS ticket_count, s.last_contact,
                COALESCE(s.success_rate,0) AS success_rate, COALESCE(s.sla_status,'On Track') AS sla_status,
                COALESCE(s.success_rate,0) AS csat_score, s.current_ticket_status
                {$notesSel}
            FROM tbl_user u
            LEFT JOIN tbl_user_ticket_summary s ON u.user_id = s.user_id
            LEFT JOIN tbl_department d ON u.department_id = d.department_id
            {$userTypeWhere}{$whereClause}
            GROUP BY u.user_id, s.ticket_count, s.last_contact, s.success_rate, s.sla_status, s.current_ticket_status{$notesGrp}
            {$havingClause}
            ORDER BY
                CASE WHEN s.current_ticket_status = 'followup' THEN 1 WHEN s.current_ticket_status = 'pending' THEN 2
                     WHEN s.current_ticket_status = 'assigning' THEN 3 WHEN s.current_ticket_status = 'complete' THEN 4
                     WHEN s.current_ticket_status IS NULL THEN 5 ELSE 6 END ASC,
                u.user_id DESC
            LIMIT ?, ?
        ";
        return ['query' => $baseQuery, 'params' => $params, 'types' => $types];
    }

    private function buildQuery($search, $userType, $slaStatus, $activityStatus, $page, $limit) {
        // Build dynamic WHERE clause based on user_type filter
        $userTypeWhere = "";
        if ($userType === 'internal') {
            $userTypeWhere = "WHERE u.user_type = 'internal'";
        } elseif ($userType === 'external') {
            $userTypeWhere = "WHERE u.user_type = 'external'";
        } elseif ($userType === 'all') {
            $userTypeWhere = ""; // No filter, include all users
        }

        // Separate WHERE conditions (user-level) from HAVING conditions (aggregate-level)
        $whereConditions = [];
        $havingConditions = [];
        $params = [];
        $types = '';

        // Build WHERE conditions (for user-level filtering)
        // Search conditions
        if (!empty($search)) {
            $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR CAST(u.user_id AS CHAR) LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= 'ssss';
        }

        // Filter conditions (these are aggregate conditions, so they belong in HAVING)
        if ($slaStatus !== 'all') {
            $filters = $this->getSLAFilters($slaStatus);
            $havingConditions = array_merge($havingConditions, $filters['conditions']);
            $params = array_merge($params, $filters['params']);
            $types .= $filters['types'];
        }

        if ($activityStatus !== 'all') {
            $filters = $this->getActivityFilters($activityStatus);
            $havingConditions = array_merge($havingConditions, $filters['conditions']);
            $params = array_merge($params, $filters['params']);
            $types .= $filters['types'];
        }

        // Build WHERE clause
        $whereClause = "";
        if (!empty($whereConditions)) {
            if (empty($userTypeWhere)) {
                $whereClause = " WHERE " . implode(" AND ", $whereConditions);
            } else {
                $whereClause = " AND " . implode(" AND ", $whereConditions);
            }
        }

        // Build HAVING clause
        $havingClause = "";
        if (!empty($havingConditions)) {
            $havingClause = " HAVING " . implode(" AND ", $havingConditions);
        }

        // Calculate offset for pagination
        $offset = ($page - 1) * $limit;

        // Use derived table for latest ticket status per user (one pass) instead of 50k correlated subqueries
        $notesSel = $this->hasNotesColumn() ? ", u.notes" : ", NULL AS notes";
        $notesGrp = $this->hasNotesColumn() ? ", u.notes" : "";
        $baseQuery = "
            SELECT
                u.user_id, u.name, u.email, u.user_type, u.status, u.company,
                u.created_at, d.department_name,
                COUNT(t.ticket_id) as ticket_count,
                MAX(t.created_at) as last_contact,
                ROUND(
                    (SUM(CASE WHEN t.status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(t.ticket_id), 0)) * 100,
                    1
                ) as success_rate,
                CASE
                    WHEN SUM(CASE WHEN t.sla_date < CURDATE() AND t.status != 'complete' THEN 1 ELSE 0 END) > 0
                    THEN 'At Risk'
                    WHEN SUM(CASE WHEN t.sla_date >= CURDATE() AND t.sla_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND t.status != 'complete' THEN 1 ELSE 0 END) > 0
                    THEN 'Approaching'
                    ELSE 'On Track'
                END as sla_status,
                ROUND(
                    (SUM(CASE WHEN t.status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(t.ticket_id), 0)) * 100,
                    1
                ) as csat_score,
                latest.current_ticket_status
                {$notesSel}
            FROM tbl_user u
            LEFT JOIN (
                SELECT user_id, status AS current_ticket_status
                FROM (
                    SELECT user_id, status, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC) AS rn
                    FROM tbl_ticket
                ) x
                WHERE rn = 1
            ) latest ON u.user_id = latest.user_id
            LEFT JOIN tbl_department d ON u.department_id = d.department_id
            LEFT JOIN tbl_ticket t ON u.user_id = t.user_id
            {$userTypeWhere}
            {$whereClause}
            GROUP BY u.user_id, latest.current_ticket_status{$notesGrp}
            {$havingClause}
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

        // Add pagination parameters
        $params = array_merge($params, [$offset, $limit]);
        $types .= 'ii';

        return [
            'query' => $baseQuery,
            'params' => $params,
            'types' => $types
        ];
    }

    private function getSLAFilters($slaStatus) {
        $conditions = [];
        $params = [];
        $types = '';

        switch ($slaStatus) {
            case 'priority':
                // Urgent and High priority tickets + 3+ tickets total
                $conditions[] = "COUNT(t.ticket_id) >= ?";
                $conditions[] = "SUM(CASE WHEN t.priority IN ('urgent', 'high') THEN 1 ELSE 0 END) > ?";
                $params = [3, 0]; // 3+ total tickets, at least 1 urgent/high priority
                $types = 'ii';
                break;

            case 'recent':
                // Recently contacted - last ticket activity update time (within 30 days)
                $conditions[] = "MAX(t.created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;

            case 'success':
                // High success rate (>80%) and fastest resolution - average completion under 5 days
                $conditions[] = "(SUM(CASE WHEN t.status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(t.ticket_id), 0)) >= ?";
                $conditions[] = "AVG(CASE WHEN t.status = 'complete' THEN DATEDIFF(t.created_at, NOW()) END) >= ?";
                $params = [0.8, -5]; // 80% success rate, average resolution within 5 days
                $types = 'di';
                break;
        }

        return [
            'conditions' => $conditions,
            'params' => $params,
            'types' => $types
        ];
    }

    private function getActivityFilters($activityStatus) {
        $conditions = [];
        $params = [];
        $types = '';

        switch ($activityStatus) {
            case 'all':
                // All Activity - no filtering, show all users
                break;

            case 'active':
                // Assigning, Pending, Follow-up
                $conditions[] = "SUM(CASE WHEN t.status IN ('assigning', 'pending', 'followup') THEN 1 ELSE 0 END) > ?";
                $params = [0];
                $types = 'i';
                break;

            case 'overdue':
                // Past SLA date from ticket activity
                $conditions[] = "SUM(CASE WHEN t.sla_date < CURDATE() AND t.status != 'complete' THEN 1 ELSE 0 END) > ?";
                $params = [0];
                $types = 'i';
                break;

            case 'churn_risk':
                // Little to no activity users - low to zero tickets
                $conditions[] = "COUNT(t.ticket_id) <= ?";
                $params = [2]; // 2 or fewer tickets = low activity
                $types = 'i';
                break;
        }

        return [
            'conditions' => $conditions,
            'params' => $params,
            'types' => $types
        ];
    }

    private function executeQuery($queryData) {
        try {
            $stmt = $this->conn->prepare($queryData['query']);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            if (!empty($queryData['params'])) {
                $stmt->bind_param($queryData['types'], ...$queryData['params']);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $customers = [];
            while ($row = $result->fetch_assoc()) {
                // Format data
                $row['ticket_count'] = (int)$row['ticket_count'];
                $row['success_rate'] = $row['success_rate'] ? round(floatval($row['success_rate']), 1) : 0.0;

                // Format dates
                if ($row['last_contact']) {
                    $row['last_contact_formatted'] = date('M j, Y', strtotime($row['last_contact']));
                }
                if ($row['created_at']) {
                    $row['created_at_formatted'] = date('M j, Y', strtotime($row['created_at']));
                }

                $customers[] = $row;
            }

            $stmt->close();
            return $customers;

        } catch (Exception $e) {
            error_log("Customer search error: " . $e->getMessage());
            return [];
        }
    }
}

// Entry point
$api = new CustomerSearchAPI();
$api->handleRequest();
?>
