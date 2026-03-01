<?php
/**
 * Get customer products for customer management profile.
 *
 * Query params:
 * - user_id (required)
 * - scope: active|all (default: active)
 */

require_once 'db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$scope = strtolower(trim($_GET['scope'] ?? 'active'));
if ($scope !== 'all') {
    $scope = 'active';
}

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

try {
    $products = [];
    $seen = [];

    if ($scope === 'active') {
        // Active scope: only registered active products.
        $stmt = $conn->prepare("
            SELECT
                cp.customer_product_id,
                cp.user_id,
                cp.product_id,
                cp.purchase_date,
                cp.warranty_start,
                cp.warranty_end,
                cp.status,
                cp.notes AS product_notes,
                cp.created_at,
                p.name AS product_name,
                p.model,
                p.serial_number,
                p.category,
                'registered' AS source
            FROM tbl_customer_product cp
            LEFT JOIN tbl_product p ON p.product_id = cp.product_id
            WHERE cp.user_id = ?
              AND cp.status = 'active'
            ORDER BY cp.created_at DESC, cp.customer_product_id DESC
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
    } else {
        // All scope: registered history + ticket-linked history, deduping linked customer products.
        $stmt = $conn->prepare("
            SELECT
                cp.customer_product_id,
                cp.user_id,
                cp.product_id,
                cp.purchase_date,
                cp.warranty_start,
                cp.warranty_end,
                cp.status,
                cp.notes AS product_notes,
                cp.created_at,
                p.name AS product_name,
                p.model,
                p.serial_number,
                p.category,
                'registered' AS source
            FROM tbl_customer_product cp
            LEFT JOIN tbl_product p ON p.product_id = cp.product_id
            WHERE cp.user_id = ?
            ORDER BY cp.created_at DESC, cp.customer_product_id DESC
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $key = 'cp:' . (int)$row['customer_product_id'];
            $seen[$key] = true;
            $products[] = $row;
        }
        $stmt->close();

        $stmt2 = $conn->prepare("
            SELECT
                tp.ticket_product_id AS customer_product_id,
                t.user_id,
                tp.product_id,
                NULL AS purchase_date,
                cp.warranty_start,
                cp.warranty_end,
                COALESCE(cp.status, 'active') AS status,
                tp.notes AS product_notes,
                tp.created_at,
                p.name AS product_name,
                p.model,
                p.serial_number,
                p.category,
                'ticket' AS source,
                tp.customer_product_id AS linked_customer_product_id
            FROM tbl_ticket_product tp
            INNER JOIN tbl_ticket t ON t.ticket_id = tp.ticket_id
            LEFT JOIN tbl_product p ON p.product_id = tp.product_id
            LEFT JOIN tbl_customer_product cp ON cp.customer_product_id = tp.customer_product_id
            WHERE t.user_id = ?
            ORDER BY tp.created_at DESC, tp.ticket_product_id DESC
        ");
        $stmt2->bind_param('i', $userId);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        while ($row = $result2->fetch_assoc()) {
            $linkedCpId = (int)($row['linked_customer_product_id'] ?? 0);
            if ($linkedCpId > 0) {
                $key = 'cp:' . $linkedCpId;
                if (isset($seen[$key])) {
                    continue;
                }
            } else {
                $ticketKey = 'tp:' . (int)$row['customer_product_id'];
                if (isset($seen[$ticketKey])) {
                    continue;
                }
                $seen[$ticketKey] = true;
            }

            unset($row['linked_customer_product_id']);
            $products[] = $row;
        }
        $stmt2->close();

        usort($products, static function (array $a, array $b): int {
            $da = strtotime($a['created_at'] ?? '1970-01-01 00:00:00');
            $db = strtotime($b['created_at'] ?? '1970-01-01 00:00:00');
            if ($da === $db) {
                return 0;
            }
            return ($da < $db) ? 1 : -1;
        });
    }

    $activeCount = 0;
    foreach ($products as $row) {
        if (($row['status'] ?? '') === 'active') {
            $activeCount++;
        }
    }

    echo json_encode([
        'success' => true,
        'products' => $products,
        'meta' => [
            'scope' => $scope,
            'active_count' => $activeCount,
            'total_count' => count($products),
        ],
    ]);
} catch (Throwable $e) {
    error_log('Get customer products error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Unable to load customer products']);
}

