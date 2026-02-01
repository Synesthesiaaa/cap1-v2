<?php
/**
 * Product Data Extraction from Tickets
 * 
 * Parses ticket descriptions for product information (serial numbers, model names)
 * Auto-creates product records when detected and links tickets to products
 */

require_once 'db.php';

/**
 * Extract product information from ticket description
 * 
 * @param string $description Ticket description text
 * @param int $user_id Customer user ID
 * @return array Extracted product information
 */
function extractProductInfo($description, $user_id) {
    $extracted = [
        'serial_numbers' => [],
        'model_names' => [],
        'product_names' => []
    ];
    
    if (empty($description)) {
        return $extracted;
    }
    
    // Pattern for serial numbers (common formats: SN: ABC123, Serial: XYZ-456, S/N: 789ABC)
    $serial_patterns = [
        '/\b(?:serial|s\/n|sn|serial number)[\s:]*([A-Z0-9\-]{4,20})\b/i',
        '/\b(?:serial|s\/n|sn)[\s:]*#?([A-Z0-9\-]{4,20})\b/i',
        '/\b([A-Z]{2,4}[\-]?[0-9]{4,12})\b/', // Format like ABC-123456
    ];
    
    foreach ($serial_patterns as $pattern) {
        if (preg_match_all($pattern, $description, $matches)) {
            $extracted['serial_numbers'] = array_merge($extracted['serial_numbers'], $matches[1]);
        }
    }
    
    // Pattern for model numbers (common formats: Model: XYZ-123, Model# ABC456)
    $model_patterns = [
        '/\b(?:model|model number|model#)[\s:]*([A-Z0-9\-]{3,20})\b/i',
        '/\b(?:model|model#)[\s:]*#?([A-Z0-9\-]{3,20})\b/i',
    ];
    
    foreach ($model_patterns as $pattern) {
        if (preg_match_all($pattern, $description, $matches)) {
            $extracted['model_names'] = array_merge($extracted['model_names'], $matches[1]);
        }
    }
    
    // Pattern for product names (common product keywords)
    $product_keywords = [
        'printer', 'laptop', 'desktop', 'monitor', 'keyboard', 'mouse', 'router', 'switch',
        'server', 'workstation', 'tablet', 'phone', 'cable', 'wire', 'adapter', 'charger'
    ];
    
    foreach ($product_keywords as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $description)) {
            $extracted['product_names'][] = ucfirst($keyword);
        }
    }
    
    // Remove duplicates
    $extracted['serial_numbers'] = array_unique($extracted['serial_numbers']);
    $extracted['model_names'] = array_unique($extracted['model_names']);
    $extracted['product_names'] = array_unique($extracted['product_names']);
    
    return $extracted;
}

/**
 * Find or create product by serial number or model
 * 
 * @param mysqli $conn Database connection
 * @param string $serial_number Serial number
 * @param string $model Model name
 * @param string $product_name Product name
 * @return int|null Product ID
 */
function findOrCreateProduct($conn, $serial_number = null, $model = null, $product_name = null) {
    $product_id = null;
    
    // Try to find existing product by serial number
    if (!empty($serial_number)) {
        $stmt = $conn->prepare("SELECT product_id FROM tbl_product WHERE serial_number = ? LIMIT 1");
        $stmt->bind_param("s", $serial_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $product_id = $row['product_id'];
            $stmt->close();
            return $product_id;
        }
        $stmt->close();
    }
    
    // Try to find existing product by model
    if (!$product_id && !empty($model)) {
        $stmt = $conn->prepare("SELECT product_id FROM tbl_product WHERE model = ? LIMIT 1");
        $stmt->bind_param("s", $model);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $product_id = $row['product_id'];
            $stmt->close();
            return $product_id;
        }
        $stmt->close();
    }
    
    // Create new product if not found
    $name = !empty($product_name) ? $product_name : (!empty($model) ? $model : 'Unknown Product');
    $stmt = $conn->prepare("INSERT INTO tbl_product (name, model, serial_number, category) VALUES (?, ?, ?, ?)");
    $category = 'General';
    $stmt->bind_param("ssss", $name, $model, $serial_number, $category);
    
    if ($stmt->execute()) {
        $product_id = $conn->insert_id;
    }
    $stmt->close();
    
    return $product_id;
}

/**
 * Link ticket to product
 * 
 * @param mysqli $conn Database connection
 * @param int $ticket_id Ticket ID
 * @param int $product_id Product ID
 * @param int|null $customer_product_id Customer product ID (if exists)
 * @param string $action_type Action type (repair, warranty_claim, purchase, etc.)
 * @return bool Success
 */
function linkTicketToProduct($conn, $ticket_id, $product_id, $customer_product_id = null, $action_type = 'repair') {
    // Check if link already exists
    $check_stmt = $conn->prepare("SELECT ticket_product_id FROM tbl_ticket_product WHERE ticket_id = ? AND product_id = ? LIMIT 1");
    $check_stmt->bind_param("ii", $ticket_id, $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $check_stmt->close();
        return true; // Link already exists
    }
    $check_stmt->close();
    
    // Create new link
    $stmt = $conn->prepare("INSERT INTO tbl_ticket_product (ticket_id, customer_product_id, product_id, action_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $ticket_id, $customer_product_id, $product_id, $action_type);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Process ticket for product extraction
 * 
 * @param mysqli $conn Database connection
 * @param int $ticket_id Ticket ID
 * @param string $description Ticket description
 * @param int $user_id Customer user ID
 * @return array Results of extraction
 */
function processTicketForProducts($conn, $ticket_id, $description, $user_id) {
    $results = [
        'products_found' => 0,
        'products_linked' => 0,
        'errors' => []
    ];
    
    // Extract product information
    $extracted = extractProductInfo($description, $user_id);
    
    if (empty($extracted['serial_numbers']) && empty($extracted['model_names']) && empty($extracted['product_names'])) {
        return $results; // No product info found
    }
    
    // Process serial numbers first (most specific)
    foreach ($extracted['serial_numbers'] as $serial) {
        $product_id = findOrCreateProduct($conn, $serial, null, null);
        if ($product_id) {
            $results['products_found']++;
            if (linkTicketToProduct($conn, $ticket_id, $product_id, null, 'repair')) {
                $results['products_linked']++;
            }
        }
    }
    
    // Process model numbers
    foreach ($extracted['model_names'] as $model) {
        // Skip if we already processed this as a serial number
        if (in_array($model, $extracted['serial_numbers'])) {
            continue;
        }
        
        $product_id = findOrCreateProduct($conn, null, $model, null);
        if ($product_id) {
            $results['products_found']++;
            if (linkTicketToProduct($conn, $ticket_id, $product_id, null, 'repair')) {
                $results['products_linked']++;
            }
        }
    }
    
    // Process product names (if no serial/model found)
    if ($results['products_linked'] == 0 && !empty($extracted['product_names'])) {
        $product_name = $extracted['product_names'][0]; // Use first product name found
        $product_id = findOrCreateProduct($conn, null, null, $product_name);
        if ($product_id) {
            $results['products_found']++;
            if (linkTicketToProduct($conn, $ticket_id, $product_id, null, 'repair')) {
                $results['products_linked']++;
            }
        }
    }
    
    return $results;
}

// API endpoint for manual extraction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    header('Content-Type: application/json');
    
    $ticket_id = intval($_POST['ticket_id']);
    
    // Get ticket information
    $stmt = $conn->prepare("SELECT description, user_id FROM tbl_ticket WHERE ticket_id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $description = $row['description'];
        $user_id = $row['user_id'];
        
        $results = processTicketForProducts($conn, $ticket_id, $description, $user_id);
        
        echo json_encode([
            'success' => true,
            'results' => $results
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Ticket not found'
        ]);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}
?>
