<?php
include("../php/db.php");
include("../utils/reference_generator.php");
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is a technician - they shouldn't use this form
if (isset($_SESSION['role']) && $_SESSION['role'] === 'technician') {
    die("Error: Technicians cannot create tickets through this form. Please use the technician dashboard.");
}

$user_id = $_SESSION['id'];

// Try to find user in tbl_user table
$sql = "SELECT u.email, u.user_type, u.department_id, COALESCE(d.department_name, 'Unassigned') as department_name
        FROM tbl_user u
        LEFT JOIN tbl_department d ON u.department_id = d.department_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error: Database query preparation failed. Please contact system administrator.");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Log the error for debugging
    $role = $_SESSION['role'] ?? 'not set';
    $name = $_SESSION['name'] ?? 'not set';
    error_log("User lookup failed - Session ID: $user_id, Role: $role, Name: $name");
    
    // Check if user exists but maybe with different ID or status
    $checkSql = "SELECT user_id, email, status FROM tbl_user WHERE user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bind_param("i", $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $checkRow = $checkResult->fetch_assoc();
            if ($checkRow['status'] !== 'active') {
                die("Error: Your account is not active. Please contact system administrator.");
            }
        }
        $checkStmt->close();
    }
    
    // If we get here, user truly doesn't exist
    session_destroy();
    header("Location: login.php?error=session_invalid");
    exit();
}

$row = $result->fetch_assoc();
$user_type = $row['user_type'];
$user_email = $row['email'];
$department_id = $row['department_id'] ?? null;
$department_name = $row['department_name'];

// For external users, always map their department to a dedicated "Customer" department (if available)
if ($user_type === 'external') {
    $custSql = "SELECT department_id, department_name FROM tbl_department WHERE department_name = 'Customer' LIMIT 1";
    $custResult = $conn->query($custSql);
    if ($custResult && $custResult->num_rows > 0) {
        $custRow = $custResult->fetch_assoc();
        $department_id = (int)$custRow['department_id'];
        $department_name = $custRow['department_name'];

        // Persist this mapping on the user record if it changed
        if ((int)($row['department_id'] ?? 0) !== $department_id) {
            $updateSql = "UPDATE tbl_user SET department_id = ? WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            if ($updateStmt) {
                $updateStmt->bind_param("ii", $department_id, $user_id);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
    } else {
        // If "Customers" department does not exist, fall back to existing logic below
        $department_id = $row['department_id'] ?? null;
        $department_name = $row['department_name'];
    }
}

// If user doesn't have a valid department (for non-external users or fallback), assign them to the first available department
if (empty($department_id) || $department_id == 0) {
    $deptSql = "SELECT department_id, department_name FROM tbl_department ORDER BY department_id LIMIT 1";
    $deptResult = $conn->query($deptSql);
    if ($deptResult && $deptResult->num_rows > 0) {
        $deptRow = $deptResult->fetch_assoc();
        $department_id = $deptRow['department_id'];
        $department_name = $deptRow['department_name'];
        
        // Update user's department
        $updateSql = "UPDATE tbl_user SET department_id = ? WHERE user_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ii", $department_id, $user_id);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        die("Error: No departments available. Please contact system administrator.");
    }
}

$stmt->close();
// save_ticket.js 
$reference_id = generateReferenceId($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Ticket</title>
    <link rel="stylesheet" href="../css/theme.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/basicTemp.css">
    <script src="../js/ticket_form.js"></script>
    <script src="../js/ui-enhancements.js" defer></script>
    <script src="../js/animations.js" defer></script>
    <script src="../js/form-enhancements.js" defer></script>
</head>
<body class="page-transition">
<?php include("../includes/navbar.php"); ?>
<div class="container page-transition">

    <div class="form-header">
        <a href="dashboard.php" class="back-btn">&lt;</a>
        <h2>Create Ticket</h2>
    </div>

    <form action="../php/save_ticket.php" method="POST" enctype="multipart/form-data" class="ticket-form">
        <input type="hidden" name="reference_id" value="<?php echo htmlspecialchars($reference_id); ?>">
        <input type="hidden" name="user_id" value="<?php echo (int)$user_id; ?>">
        <input type="hidden" name="department_id" value="<?php echo (int)$department_id; ?>">

        <div class="form-left">

            <div class="user-info-box">
                <p class="user-email">
                    <strong>Email:</strong>
                    <span class="user-value"><?php echo htmlspecialchars($user_email); ?></span>
                </p>
                <p class="user-department">
                    <strong>Department:</strong>
                    <span class="user-value"><?php echo htmlspecialchars($department_name); ?></span>
                </p>
            </div>

            <label>Title / Summary</label>
            <input type="text" name="title" placeholder="E.g Network issue, Furniture repair request" required>

            <label>Type of Ticket</label>       
            <select name="ticket_type" id="ticket_type" onchange="updateCategory()" required>
                <option value="">-- Select Ticket Type --</option>
                <?php if ($user_type === 'internal'): ?>
                    <option value="IT">IT</option>
                    <option value="Finance">Finance</option>
                    <option value="Engineering">Engineering</option>
                    <option value="HR">HR</option>
                    <option value="Warehouse">Warehouse</option>
                    <option value="Production">Production</option>
                    <option value="Facilities">Facilities</option>
                <?php elseif ($user_type === 'external'): ?>
                    <option value="Sales">Sales</option>
                    <option value="Shipping">Shipping</option>
                <?php endif; ?>
            </select>

            <label>
                <input type="checkbox" name="is_urgent" id="is_urgent" value="1">
                This is urgent - requires immediate attention
            </label>

            <div id="category-wrapper" style="display:none;">
                <label>Category / Tag</label>
                <select name="category" id="category" required></select>
            </div>

            <!-- Notes and Privacy -->
            <div class="ticket-notes">
                <p><strong>Note:</strong></p>
                <ul>
                    <li>Provide as much detail as possible to help address the issue effectively.</li>
                    <li>For IT related issue, indicate the property code of the equipment.</li>
                </ul>
                <p><strong>Privacy notice:</strong><br>
                Submitting a ticket is acknowledgement and agreement for collection and use of your information
                stated in the <a href="https://isc.co/company/" target="_blank">Interconnect Solution Company's privacy notice</a>.</p>
            </div>
        </div>

        <div class="form-right">
            <label>Description</label>
            <textarea name="description" rows="15" required placeholder="Describe your issue or request..."></textarea>

            <label>Upload Attachment</label>
            <div class="upload-box">
                <input type="file" name="attachment">
                <p>Drag & drop files or <span class="browse">Browse</span></p>
                <small>Supported: JPEG, PNG, GIF, MP4, PDF, DOCX, PPTX</small>
            </div>

            <button type="submit" class="submit-btn">Submit Ticket</button>
        </div>
    </form>
</div>

</body>
</html>
