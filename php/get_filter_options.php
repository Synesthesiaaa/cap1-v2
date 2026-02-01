<?php
/**
 * API to get filter options for customer search
 * Fetches dynamic values based on search context for user types, SLA statuses, and activity statuses
 */

require_once 'db.php';

/**
 * Filter Options API Class
 */
class FilterOptionsAPI extends BaseAPI {

    public function handleRequest() {
        header('Content-Type: application/json; charset=utf-8');

        // Get search parameters to filter options contextually
        $search      = $this->sanitizeInput($_GET['q'] ?? '');
        $userType    = $this->sanitizeInput($_GET['user_type'] ?? 'all');
        $filterType  = $this->sanitizeInput($_GET['type'] ?? 'all');

        $response = [];

        if ($filterType === 'all' || $filterType === 'user_types') {
            $response['user_types'] = $this->getUserTypes($search, $userType);
        }

        if ($filterType === 'all' || $filterType === 'sla_statuses') {
            $response['sla_statuses'] = $this->getSLAStatuses($search, $userType);
        }

        if ($filterType === 'all' || $filterType === 'activity_statuses') {
            $response['activity_statuses'] = $this->getActivityStatuses($search, $userType);
        }

        $this->sendResponse($response);
    }

    /**
     * Get distinct user types from database - always show all available options
     * Count users based on search context (not current user type filter)
     */
    private function getUserTypes($search, $userTypeFilter) {
        // For user types, we want to show ALL available options regardless of current filter
        // So we base the search context but not the user type filter restriction

        $whereConditions = [];
        $params = [];
        $types = '';

        // Apply search conditions if provided (but not user type filter since we want all user types)
        if (!empty($search)) {
            $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR CAST(u.user_id AS CHAR) LIKE ? OR u.phone LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= 'sssss';
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // Get counts for each user type considering only the search context
        $userTypeList = ['external', 'internal'];
        $resultOptions = [];

        foreach ($userTypeList as $userType) {
            $typeWhere = $whereClause ? $whereClause . " AND u.user_type = ?" : "WHERE u.user_type = ?";
            $countParams = !empty($search) ? array_merge($params, [$userType]) : [$userType];
            $countTypes = $types . 's';

            $countQuery = "
                SELECT COUNT(DISTINCT u.user_id) as count
                FROM tbl_user u
                LEFT JOIN tbl_department d ON u.department_id = d.department_id
                LEFT JOIN tbl_ticket t ON u.user_id = t.user_id
                {$typeWhere}
            ";

            $stmt = $this->conn->prepare($countQuery);
            $stmt->bind_param($countTypes, ...$countParams);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $count = $row ? (int)$row['count'] : 0;
            $stmt->close();

            // Only include user types that have users
            if ($count > 0) {
                $resultOptions[] = [
                    'value' => $userType,
                    'label' => ucfirst($userType) . ' (' . $count . ')'
                ];
            }
        }

        // Always add 'all' option at the beginning
        array_unshift($resultOptions, [
            'value' => 'all',
            'label' => 'All Users'
        ]);

        return $resultOptions;
    }

    /**
     * Build base conditions for filtering operations
     */
    private function buildBaseConditions($search, $userTypeFilter) {
        $whereConditions = [];
        $params = [];
        $types = '';

        // Apply user type filter if specified
        if ($userTypeFilter === 'internal') {
            $whereConditions[] = "u.user_type = 'internal'";
        } elseif ($userTypeFilter === 'external') {
            $whereConditions[] = "u.user_type = 'external'";
        } // 'all' includes both

        // Apply search conditions if provided
        if (!empty($search)) {
            $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ? OR CAST(u.user_id AS CHAR) LIKE ? OR u.phone LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= 'sssss';
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        return [
            'whereClause' => $whereClause,
            'conditions' => $whereConditions,
            'params' => $params,
            'types' => $types
        ];
    }

    /**
     * Get SLA status filter options with counts based on search context
     * Allow all filters to be available regardless of current filter state
     */
    private function getSLAStatuses($search, $userTypeFilter) {
        // Build base conditions that match search and user type context
        $baseConditions = $this->buildBaseConditions($search, $userTypeFilter);

        $statuses = [
            [
                'value' => 'all',
                'label' => 'All SLA Status'
            ]
        ];

        // Define SLA filter criteria with descriptive labels
        $slaCriteria = [
            'priority' => [
                'having' => "COUNT(t.ticket_id) >= 3 AND SUM(CASE WHEN t.priority IN ('urgent', 'high') THEN 1 ELSE 0 END) > 0",
                'label' => 'Priority Clients (3+ tickets, urgent/high)'
            ],
            'recent' => [
                'having' => "MAX(t.created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                'label' => 'Recently Contacted (30 days)'
            ],
            'success' => [
                'having' => "(SUM(CASE WHEN t.status = 'complete' THEN 1 ELSE 0 END) / NULLIF(COUNT(t.ticket_id), 0)) >= 0.8 AND AVG(CASE WHEN t.status = 'complete' THEN DATEDIFF(NOW(), t.created_at) END) <= 5",
                'label' => 'High Success Rate (80%+, <5 days avg)'
            ]
        ];

        // Query database for each category count
        foreach ($slaCriteria as $value => $criteria) {
            $havingClause = $criteria['having'];

            $query = "
                SELECT COUNT(*) as count FROM (
                    SELECT u.user_id
                    FROM tbl_user u
                    LEFT JOIN tbl_department d ON u.department_id = d.department_id
                    LEFT JOIN tbl_ticket t ON u.user_id = t.user_id
                    {$baseConditions['whereClause']}
                    GROUP BY u.user_id
                    HAVING {$havingClause}
                ) as filtered_users
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $stmt->close();

            // Always include all SLA options, even if count is 0 (for filter selection)
            $statuses[] = [
                'value' => $value,
                'label' => $criteria['label'] . ' (' . $count . ')'
            ];
        }

        return $statuses;
    }

    /**
     * Get activity status filter options with counts based on search context
     * Queries database to count customers matching each activity category
     */
    private function getActivityStatuses($search, $userTypeFilter) {
        // Build base conditions that match the search context
        $baseConditions = $this->buildBaseConditions($search, $userTypeFilter);

        $statuses = [
            [
                'value' => 'all',
                'label' => 'All Activity'
            ]
        ];

        // Define activity filter criteria and labels
        $activityCriteria = [
            'active' => [
                'having' => "COUNT(CASE WHEN t.status IN ('assigning', 'pending', 'followup') THEN 1 END) > 0",
                'label' => 'Active (Assigning, Pending, Follow-up)'
            ],
            'overdue' => [
                'having' => "COUNT(CASE WHEN t.sla_date < CURDATE() AND t.status != 'complete' THEN 1 END) > 0",
                'label' => 'Overdue (Past SLA dates)'
            ],
            'churn_risk' => [
                'having' => "COUNT(t.ticket_id) <= 2",
                'label' => 'Churn Risk (≤2 tickets)'
            ]
        ];

        // Query database for each category count
        foreach ($activityCriteria as $value => $criteria) {
            $havingClause = $criteria['having'];

            $query = "
                SELECT COUNT(*) as count FROM (
                    SELECT u.user_id
                    FROM tbl_user u
                    LEFT JOIN tbl_department d ON u.department_id = d.department_id
                    LEFT JOIN tbl_ticket t ON u.user_id = t.user_id
                    {$baseConditions['whereClause']}
                    GROUP BY u.user_id
                    HAVING {$havingClause}
                ) as filtered_users
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $stmt->close();

            // Only add if there are matching customers (except for 'all' which is always shown)
            if ($value === 'all' || $count > 0) {
                $statuses[] = [
                    'value' => $value,
                    'label' => $criteria['label'] . ' (' . $count . ')'
                ];
            }
        }

        return $statuses;
    }
}

// Entry point
$api = new FilterOptionsAPI();
$api->handleRequest();
?>
