<?php
session_start();
require_once("../php/check_cm_access.php");
require_once("../php/db.php");

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$accessLevel = checkCMAccess();
if ($accessLevel === 'denied') {
    header("Location: dashboard.php");
    exit();
}

$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
$ticket = null;

if ($ticket_id > 0) {
    $stmt = $conn->prepare("SELECT t.*, u.name as user_name, u.user_id FROM tbl_ticket t LEFT JOIN tbl_user u ON t.user_id = u.user_id WHERE t.ticket_id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$ticket) {
    header("Location: dashboard.php");
    exit();
}

// Get linked products
$stmt = $conn->prepare("
    SELECT tp.*, p.name as product_name, p.model, p.serial_number, cp.customer_product_id, cp.warranty_start, cp.warranty_end
    FROM tbl_ticket_product tp
    LEFT JOIN tbl_product p ON tp.product_id = p.product_id
    LEFT JOIN tbl_customer_product cp ON tp.customer_product_id = cp.customer_product_id
    WHERE tp.ticket_id = ?
");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$linked_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get customer products
$customer_products = [];
if ($ticket['user_id']) {
    $stmt = $conn->prepare("
        SELECT cp.*, p.name as product_name, p.model, p.serial_number
        FROM tbl_customer_product cp
        LEFT JOIN tbl_product p ON cp.product_id = p.product_id
        WHERE cp.user_id = ? AND cp.status = 'active'
        ORDER BY cp.created_at DESC
    ");
    $stmt->bind_param("i", $ticket['user_id']);
    $stmt->execute();
    $customer_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Integration - Ticket #<?php echo htmlspecialchars($ticket['reference_id']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 page-transition">
<?php include("../includes/navbar.php"); ?>
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-4">Product Integration</h1>
            <p class="text-gray-600 mb-6">Link products to ticket: <strong>#<?php echo htmlspecialchars($ticket['reference_id']); ?></strong> - <?php echo htmlspecialchars($ticket['title']); ?></p>

            <!-- Linked Products -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Linked Products</h2>
                <div id="linkedProductsList" class="space-y-3">
                    <?php if (empty($linked_products)): ?>
                        <p class="text-gray-500">No products linked to this ticket.</p>
                    <?php else: ?>
                        <?php foreach ($linked_products as $linked): ?>
                            <div class="border rounded-lg p-4 bg-gray-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold"><?php echo htmlspecialchars($linked['product_name'] ?? 'Unknown Product'); ?></h3>
                                        <?php if ($linked['model']): ?>
                                            <p class="text-sm text-gray-600">Model: <?php echo htmlspecialchars($linked['model']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($linked['serial_number']): ?>
                                            <p class="text-sm text-gray-600">Serial: <?php echo htmlspecialchars($linked['serial_number']); ?></p>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-500 mt-2">Action: <?php echo htmlspecialchars($linked['action_type']); ?></p>
                                        <?php if ($linked['warranty_start'] && $linked['warranty_end']): ?>
                                            <p class="text-sm text-gray-500">Warranty: <?php echo date('M d, Y', strtotime($linked['warranty_start'])); ?> - <?php echo date('M d, Y', strtotime($linked['warranty_end'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <button onclick="unlinkProduct(<?php echo $linked['ticket_product_id']; ?>)" class="text-red-600 hover:text-red-800 text-sm">Unlink</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Link Product Form -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Link Product</h2>
                <form id="linkProductForm" class="space-y-4">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Action Type</label>
                        <select name="action_type" class="w-full border rounded p-2" required>
                            <option value="repair">Repair</option>
                            <option value="warranty_claim">Warranty Claim</option>
                            <option value="purchase">Purchase</option>
                            <option value="inquiry">Inquiry</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Customer Product</label>
                        <select name="customer_product_id" id="customerProductSelect" class="w-full border rounded p-2">
                            <option value="">-- Select existing product --</option>
                            <?php foreach ($customer_products as $cp): ?>
                                <option value="<?php echo $cp['customer_product_id']; ?>" 
                                        data-product-id="<?php echo $cp['product_id']; ?>">
                                    <?php echo htmlspecialchars($cp['product_name'] ?? 'Unknown'); ?>
                                    <?php if ($cp['model']): ?> - <?php echo htmlspecialchars($cp['model']); ?><?php endif; ?>
                                    <?php if ($cp['warranty_end']): ?> (Warranty: <?php echo date('M d, Y', strtotime($cp['warranty_end'])); ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="text-center text-gray-500">OR</div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Create New Product</label>
                        <input type="text" name="product_name" placeholder="Product Name" class="w-full border rounded p-2 mb-2">
                        <input type="text" name="product_model" placeholder="Model (optional)" class="w-full border rounded p-2 mb-2">
                        <input type="text" name="product_serial" placeholder="Serial Number (optional)" class="w-full border rounded p-2 mb-2">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="warranty_start" placeholder="Warranty Start" class="border rounded p-2">
                            <input type="date" name="warranty_end" placeholder="Warranty End" class="border rounded p-2">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optional)</label>
                        <textarea name="notes" rows="3" class="w-full border rounded p-2" placeholder="Additional notes..."></textarea>
                    </div>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">Link Product</button>
                </form>
            </div>

            <div class="mt-6">
                <a href="view_ticket.php?ref=<?php echo urlencode($ticket['reference_id']); ?>" class="text-blue-600 hover:text-blue-800">← Back to Ticket</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('linkProductForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('../php/link_ticket_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Product linked successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (result.error || 'Failed to link product'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to link product. Please try again.');
            }
        });

        async function unlinkProduct(ticketProductId) {
            if (!confirm('Are you sure you want to unlink this product?')) {
                return;
            }
            
            try {
                const response = await fetch('../php/unlink_ticket_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ticket_product_id: ticketProductId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Product unlinked successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (result.error || 'Failed to unlink product'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to unlink product. Please try again.');
            }
        }
    </script>
</body>
</html>
