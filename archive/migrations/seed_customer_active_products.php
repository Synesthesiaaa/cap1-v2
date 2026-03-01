<?php
/**
 * Seed sample active products for ticketed external customers.
 *
 * Usage:
 *   php archive/migrations/seed_customer_active_products.php [--limit=100] [--min-products=1] [--max-products=3] [--dry-run]
 */

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/php/db.php';

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$options = getopt('', ['limit::', 'min-products::', 'max-products::', 'dry-run']);

$limit = isset($options['limit']) ? max(1, (int)$options['limit']) : 0; // 0 = no limit
$minProducts = isset($options['min-products']) ? max(1, (int)$options['min-products']) : 1;
$maxProducts = isset($options['max-products']) ? max(1, (int)$options['max-products']) : 3;
$dryRun = array_key_exists('dry-run', $options);

if ($minProducts > $maxProducts) {
    fwrite(STDERR, "--min-products cannot be greater than --max-products\n");
    exit(1);
}

$catalog = [
    ['name' => 'Laptop', 'model_prefix' => 'LT', 'category' => 'Computing', 'manufacturer' => 'Aster'],
    ['name' => 'Desktop Workstation', 'model_prefix' => 'WS', 'category' => 'Computing', 'manufacturer' => 'Aster'],
    ['name' => 'Network Router', 'model_prefix' => 'RT', 'category' => 'Networking', 'manufacturer' => 'NodeLine'],
    ['name' => 'Switch', 'model_prefix' => 'SW', 'category' => 'Networking', 'manufacturer' => 'NodeLine'],
    ['name' => 'Laser Printer', 'model_prefix' => 'PR', 'category' => 'Peripherals', 'manufacturer' => 'Printix'],
    ['name' => 'Monitor', 'model_prefix' => 'MN', 'category' => 'Peripherals', 'manufacturer' => 'ViewCore'],
];

$stats = [
    'users_scanned' => 0,
    'users_seeded' => 0,
    'users_skipped_existing_active' => 0,
    'products_inserted' => 0,
    'products_reused' => 0,
    'customer_products_inserted' => 0,
    'ticket_links_inserted' => 0,
    'errors' => 0,
];

echo "Seed started\n";
echo "  limit: " . ($limit > 0 ? $limit : 'all') . "\n";
echo "  min-products: {$minProducts}\n";
echo "  max-products: {$maxProducts}\n";
echo "  dry-run: " . ($dryRun ? 'yes' : 'no') . "\n\n";

// Ensure identity columns can auto-generate IDs on inserts.
$autoIncrementTargets = [
    ['table' => 'tbl_product', 'column' => 'product_id'],
    ['table' => 'tbl_customer_product', 'column' => 'customer_product_id'],
    ['table' => 'tbl_ticket_product', 'column' => 'ticket_product_id'],
];
foreach ($autoIncrementTargets as $target) {
    $tbl = $target['table'];
    $col = $target['column'];
    $colRes = $conn->query("SHOW COLUMNS FROM {$tbl} LIKE '{$col}'");
    $colRow = $colRes ? $colRes->fetch_assoc() : null;
    if (!$colRow) {
        continue;
    }
    $extra = strtolower((string)($colRow['Extra'] ?? ''));
    if (strpos($extra, 'auto_increment') === false) {
        $type = $colRow['Type'] ?? 'int(11)';
        $ddl = "ALTER TABLE {$tbl} MODIFY {$col} {$type} NOT NULL AUTO_INCREMENT";
        if ($conn->query($ddl)) {
            echo "Enabled AUTO_INCREMENT on {$tbl}.{$col}\n";
        } else {
            echo "WARN: Unable to enable AUTO_INCREMENT on {$tbl}.{$col}: {$conn->error}\n";
        }
    }
}

$sqlUsers = "
    SELECT u.user_id, u.name
    FROM tbl_user u
    WHERE u.user_type = 'external'
      AND EXISTS (SELECT 1 FROM tbl_ticket t WHERE t.user_id = u.user_id)
    ORDER BY u.user_id ASC
";
if ($limit > 0) {
    $sqlUsers .= " LIMIT " . $limit;
}

$usersRes = $conn->query($sqlUsers);
if (!$usersRes) {
    fwrite(STDERR, "Failed to fetch target users: {$conn->error}\n");
    exit(1);
}

$stmtHasActive = $conn->prepare("
    SELECT 1
    FROM tbl_customer_product
    WHERE user_id = ? AND status = 'active'
    LIMIT 1
");

$stmtFindProductBySerial = $conn->prepare("
    SELECT product_id
    FROM tbl_product
    WHERE serial_number = ?
    LIMIT 1
");

$stmtFindProductByModel = $conn->prepare("
    SELECT product_id
    FROM tbl_product
    WHERE name = ? AND model = ?
    LIMIT 1
");

$stmtInsertProduct = $conn->prepare("
    INSERT INTO tbl_product (name, model, serial_number, category, manufacturer, description)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmtInsertCustomerProduct = $conn->prepare("
    INSERT INTO tbl_customer_product (user_id, product_id, purchase_date, warranty_start, warranty_end, status, notes)
    VALUES (?, ?, ?, ?, ?, 'active', ?)
");

$stmtLatestTicket = $conn->prepare("
    SELECT ticket_id
    FROM tbl_ticket
    WHERE user_id = ?
    ORDER BY created_at DESC, ticket_id DESC
    LIMIT 1
");

$stmtHasTicketLink = $conn->prepare("
    SELECT 1
    FROM tbl_ticket_product
    WHERE ticket_id = ?
      AND (customer_product_id = ? OR product_id = ?)
    LIMIT 1
");

$stmtInsertTicketLink = $conn->prepare("
    INSERT INTO tbl_ticket_product (ticket_id, customer_product_id, product_id, action_type, notes)
    VALUES (?, ?, ?, 'inquiry', ?)
");

while ($user = $usersRes->fetch_assoc()) {
    $userId = (int)$user['user_id'];
    $stats['users_scanned']++;

    $stmtHasActive->bind_param('i', $userId);
    $stmtHasActive->execute();
    $hasActive = $stmtHasActive->get_result()->num_rows > 0;

    if ($hasActive) {
        $stats['users_skipped_existing_active']++;
        continue;
    }

    $seedCount = ($minProducts === $maxProducts) ? $minProducts : mt_rand($minProducts, $maxProducts);
    $stats['users_seeded']++;

    if (!$dryRun) {
        $conn->begin_transaction();
    }

    $firstInsertedCpId = 0;
    $firstInsertedProductId = 0;

    try {
        for ($i = 0; $i < $seedCount; $i++) {
            $tpl = $catalog[($userId + $i) % count($catalog)];
            $model = $tpl['model_prefix'] . '-' . str_pad((string)(($userId % 9000) + 1000 + $i), 4, '0', STR_PAD_LEFT);
            $serial = 'SN-' . str_pad((string)$userId, 6, '0', STR_PAD_LEFT) . '-' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);

            // Reuse by serial first, then by (name, model).
            $productId = 0;

            $stmtFindProductBySerial->bind_param('s', $serial);
            $stmtFindProductBySerial->execute();
            $row = $stmtFindProductBySerial->get_result()->fetch_assoc();
            if ($row) {
                $productId = (int)$row['product_id'];
                $stats['products_reused']++;
            } else {
                $stmtFindProductByModel->bind_param('ss', $tpl['name'], $model);
                $stmtFindProductByModel->execute();
                $row = $stmtFindProductByModel->get_result()->fetch_assoc();
                if ($row) {
                    $productId = (int)$row['product_id'];
                    $stats['products_reused']++;
                }
            }

            if ($productId <= 0) {
                $description = 'Seeded sample product for customer management testing';
                if (!$dryRun) {
                    $stmtInsertProduct->bind_param(
                        'ssssss',
                        $tpl['name'],
                        $model,
                        $serial,
                        $tpl['category'],
                        $tpl['manufacturer'],
                        $description
                    );
                    if (!$stmtInsertProduct->execute()) {
                        throw new RuntimeException('Insert product failed: ' . $stmtInsertProduct->error);
                    }
                    $productId = (int)$conn->insert_id;
                } else {
                    $productId = -1; // placeholder id for dry-run math
                }
                $stats['products_inserted']++;
            }

            $purchaseDate = date('Y-m-d', strtotime('-' . (30 + (($userId + $i) % 540)) . ' days'));
            $warrantyStart = $purchaseDate;
            $warrantyEnd = date('Y-m-d', strtotime($purchaseDate . ' +365 days'));
            $notes = 'Seeded sample active product';

            $customerProductId = 0;
            if (!$dryRun) {
                $stmtInsertCustomerProduct->bind_param(
                    'iissss',
                    $userId,
                    $productId,
                    $purchaseDate,
                    $warrantyStart,
                    $warrantyEnd,
                    $notes
                );
                if (!$stmtInsertCustomerProduct->execute()) {
                    throw new RuntimeException('Insert customer_product failed: ' . $stmtInsertCustomerProduct->error);
                }
                $customerProductId = (int)$conn->insert_id;
            }
            $stats['customer_products_inserted']++;

            if ($i === 0) {
                $firstInsertedCpId = $customerProductId;
                $firstInsertedProductId = $productId;
            }
        }

        // Optionally create one ticket-product link to latest ticket.
        $stmtLatestTicket->bind_param('i', $userId);
        $stmtLatestTicket->execute();
        $latest = $stmtLatestTicket->get_result()->fetch_assoc();
        if ($latest) {
            $latestTicketId = (int)$latest['ticket_id'];
            $probeCpId = $dryRun ? 0 : $firstInsertedCpId;
            $probeProductId = $dryRun ? 0 : $firstInsertedProductId;

            $hasLink = false;
            if (!$dryRun && $probeProductId > 0) {
                $stmtHasTicketLink->bind_param('iii', $latestTicketId, $probeCpId, $probeProductId);
                $stmtHasTicketLink->execute();
                $hasLink = $stmtHasTicketLink->get_result()->num_rows > 0;
            }

            if (!$hasLink && $firstInsertedProductId !== 0) {
                if (!$dryRun) {
                    $note = 'Seeded sample product linkage';
                    $stmtInsertTicketLink->bind_param('iiis', $latestTicketId, $firstInsertedCpId, $firstInsertedProductId, $note);
                    if (!$stmtInsertTicketLink->execute()) {
                        throw new RuntimeException('Insert ticket_product failed: ' . $stmtInsertTicketLink->error);
                    }
                }
                $stats['ticket_links_inserted']++;
            }
        }

        if (!$dryRun) {
            $conn->commit();
        }
    } catch (Throwable $e) {
        if (!$dryRun) {
            $conn->rollback();
        }
        $stats['errors']++;
        if ($stats['errors'] <= 25) {
            fwrite(STDERR, "User {$userId} failed: {$e->getMessage()}\n");
        }
    }
}

echo "Seed complete\n";
foreach ($stats as $k => $v) {
    echo "  {$k}: {$v}\n";
}
if ($stats['errors'] > 25) {
    echo "  note: only first 25 user errors were printed above.\n";
}
