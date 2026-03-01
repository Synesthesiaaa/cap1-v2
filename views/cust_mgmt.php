<?php
// Try to use new structure if available
$useNewStructure = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap.php';
    $useNewStructure = true;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use centralized access control
require_once("../php/check_cm_access.php");

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$accessLevel = checkCMAccess();

// Deny access to external users
if ($accessLevel === 'denied') {
    header("Location: dashboard.php");
    exit();
}

$role = $_SESSION['role'] ?? '';
$userType = $_SESSION['user_type'] ?? '';
$isReadOnly = ($accessLevel === 'readonly');

// Include database connection
if (!$useNewStructure) {
    include("../php/db.php");
} else {
    // Use new Database\Connection if available
    try {
        $conn = \Database\Connection::getInstance()->getConnection();
    } catch (\Exception $e) {
        // Fallback to old db.php
        include("../php/db.php");
    }
}

// Get total customers count for customer list header only (customers loaded via AJAX)
$total_customers_query = "SELECT COUNT(*) AS total FROM tbl_user";
$result = $conn->query($total_customers_query);
$total_customers = $result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Customer Management — Interconnect Solutions Company</title>

  <!-- Theme CSS -->
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/basicTemp.css">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  
  <!-- UI Enhancements -->
  <script src="../js/ui-enhancements.js" defer></script>
  <script src="../js/animations.js" defer></script>
  <script src="../js/popup.js" defer></script>

  <style>
    body { 
      font-family: var(--font-family, 'Inter'), system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; 
      background-color: var(--bg-primary, #f8fafc);
      color: var(--text-primary, #1f2937);
    }

    /* Enhanced scrollbar styling - using theme variables */
    .sidebar-scroll::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    .sidebar-scroll::-webkit-scrollbar-track {
      background: transparent;
    }
    .sidebar-scroll::-webkit-scrollbar-thumb {
      background: linear-gradient(180deg, var(--primary-color, #6366f1)33 0%, var(--primary-color, #6366f1)80 100%);
      border-radius: 999px;
      transition: background var(--transition-base, 0.3s) ease;
    }
    .sidebar-scroll::-webkit-scrollbar-thumb:hover {
      background: var(--primary-color-hover, rgba(99,102,241,0.7));
    }

    /* Loading animations - using theme variables */
    .loading-pulse { 
      animation: pulse var(--animation-duration, 1.8s) infinite ease-in-out; 
    }

    .loading-shimmer {
      background: linear-gradient(90deg, var(--bg-secondary, #f0f0f0) 25%, var(--border-color, #e0e0e0) 50%, var(--bg-secondary, #f0f0f0) 75%);
      background-size: 200px 100%;
      animation: shimmer var(--animation-duration, 1.5s) infinite;
    }

    /* Modal loading spinner - used in Ticket, History, Products modals */
    .modal-loading-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      padding: 1rem 0;
      min-height: 4rem;
    }
    .modal-spinner {
      width: 32px;
      height: 32px;
      border: 3px solid var(--border-color, #e5e7eb);
      border-top-color: var(--primary-color, #6366f1);
      border-radius: 50%;
      animation: modal-spin 0.7s linear infinite;
    }
    @keyframes modal-spin {
      to { transform: rotate(360deg); }
    }

    /* Advanced shadows and depth - using theme variables */
    .shadow-elegant {
      box-shadow: var(--shadow-sm, 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06));
    }
    .shadow-elegant-lg {
      box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06));
    }
    .shadow-glow {
      box-shadow: 
        0 0 0 3px var(--primary-color, rgba(99,102,241,0.1)),
        0 4px 15px 0 var(--primary-color, rgba(99,102,241,0.15)),
        0 10px 20px -5px var(--primary-color, rgba(99,102,241,0.1));
    }

    /* Button effects - using theme transitions */
    .btn-reactive {
      position: relative;
      transform: translateY(0);
      transition: all var(--transition-base, 0.2s) cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: var(--shadow-sm, 0 1px 3px 0 rgba(0, 0, 0, 0.1));
    }
    .btn-reactive:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md, 0 4px 8px 0 rgba(0, 0, 0, 0.12));
    }
    .btn-reactive:active {
      transform: translateY(0);
      box-shadow: var(--shadow-sm, 0 2px 4px 0 rgba(0, 0, 0, 0.1));
    }

    /* Ripple effect */
    .btn-ripple {
      position: relative;
      overflow: hidden;
    }
    .btn-ripple::before {
      content: "";
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.4);
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
      pointer-events: none;
    }
    .btn-ripple:active::before {
      width: 300px;
      height: 300px;
    }

    /* Card hover effects - using theme transitions */
    .card-interactive {
      transition: all var(--transition-base, 0.3s) cubic-bezier(0.4, 0, 0.2, 1);
      transform: translateY(0);
    }
    .card-interactive:hover {
      transform: translateY(-3px);
      cursor: pointer;
      box-shadow: var(--shadow-lg, 0 10px 25px -3px rgba(0, 0, 0, 0.1));
    }

    /* Customer item selection effects */
    .customer-item {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }
    .customer-item:hover {
      transform: translateY(-2px);
    }

    .customer-item.active {
      transform: translateY(-5px);
      box-shadow: 
        0 10px 30px -5px var(--primary-color, rgba(99, 102, 241, 0.25)),
        0 20px 25px -5px rgba(0, 0, 0, 0.1);
      border-color: var(--primary-color, rgba(99, 102, 241, 0.3));
      margin-bottom: 12px;
      background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, var(--primary-color, rgba(99,102,241,0.02)) 100%);
    }

    .customer-item.active::before {
      content: '';
      position: absolute;
      top: -2px;
      left: -2px;
      right: -2px;
      bottom: -2px;
      background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(99,102,241,0.05));
      border-radius: 10px;
      z-index: -1;
      pointer-events: none;
    }

    /* Input focus enhancements - using theme variables */
    .input-reactive {
      transition: all var(--transition-base, 0.3s) cubic-bezier(0.4, 0, 0.2, 1);
      transform: translateY(0);
    }
    .input-reactive:focus {
      transform: translateY(-1px);
      box-shadow: 
        0 0 0 3px var(--primary-color, rgba(99,102,241,0.1)),
        0 4px 8px 0 rgba(0, 0, 0, 0.12);
      border-color: var(--primary-color, #6366f1);
    }

    /* Skeletons for loading states - using theme variables */
    .skeleton {
      background: linear-gradient(90deg, var(--bg-secondary, #f0f0f0) 25%, var(--border-color, #e0e0e0) 50%, var(--bg-secondary, #f0f0f0) 75%);
      background-size: 200px 100%;
      animation: shimmer var(--animation-duration, 1.5s) infinite;
    }

    /* Modal enhancements - using theme animations */
    .modal-backdrop {
      backdrop-filter: blur(2px);
      animation: fadeIn var(--transition-fast, 0.2s) ease-out;
    }
    .modal-content {
      animation: slideUp var(--transition-base, 0.3s) cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    /* Status indicators - using theme colors */
    .status-live::before {
      content: '';
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--success-color, #10b981), var(--success-color-light, #34d399));
      margin-right: 6px;
      animation: pulse var(--animation-duration, 2s) infinite;
    }

    /* Progressive loading - using theme animations */
    .loading-progressive {
      animation: fadeIn var(--transition-slow, 0.6s) ease-out;
      animation-fill-mode: both;
    }
    .loading-progressive:nth-child(1) { animation-delay: 0.1s; }
    .loading-progressive:nth-child(2) { animation-delay: 0.2s; }
    .loading-progressive:nth-child(3) { animation-delay: 0.3s; }

    /* Gradient backgrounds for depth - using theme variables */
    .bg-gradient-elegant {
      background: linear-gradient(135deg, var(--bg-primary, rgba(255,255,255,0.1)) 0%, var(--bg-secondary, rgba(255,255,255,0.05)) 100%);
    }

    /* Avatar hover effects - using theme transitions */
    .avatar-interactive {
      transition: all var(--transition-base, 0.3s) cubic-bezier(0.4, 0, 0.2, 1);
    }
    .avatar-interactive:hover {
      transform: scale(1.05) rotate(5deg);
    }

    /* Smooth transitions for all interactive elements - using theme variables */
    *, *::before, *::after {
      box-sizing: border-box;
    }

    button, input, select, textarea {
      transition: all var(--transition-base, 0.2s) ease;
    }

    /* Accessible focus indicators - using theme colors */
    .focus-visible:focus {
      outline: 2px solid var(--primary-color, #6366f1);
      outline-offset: 2px;
    }

    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
      *,
      *::before,
      *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }

    /* Loading gallery for customer list */
    .customer-skeleton {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      margin-bottom: 12px;
    }
    .customer-skeleton .avatar-skeleton {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(90deg, var(--bg-secondary, #f3f4f6) 25%, var(--border-color, #e5e7eb) 50%, var(--bg-secondary, #f3f4f6) 75%);
      background-size: 200px 100%;
      animation: shimmer var(--animation-duration, 1.5s) infinite;
    }
    .customer-skeleton .text-skeleton {
      flex: 1;
      height: 20px;
      margin-bottom: 8px;
      background: linear-gradient(90deg, var(--bg-secondary, #f3f4f6) 25%, var(--border-color, #e5e7eb) 50%, var(--bg-secondary, #f3f4f6) 75%);
      background-size: 200px 100%;
      animation: shimmer var(--animation-duration, 1.5s) infinite;
    }
    .customer-skeleton .text-skeleton:last-child {
      width: 60%;
      margin-bottom: 0;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased page-transition" style="background-color: var(--bg-primary, #f8fafc); color: var(--text-primary, #1f2937);">
<?php include("../includes/navbar.php"); ?>
  <div class="max-w-[1400px] mx-auto px-4 py-6">
    <!-- Main area -->
    <main class="w-full">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
        <h1 class="text-3xl font-extrabold">Customer Management</h1>
        <div class="flex flex-col sm:flex-row gap-3">
          <input id="searchInput" type="search" placeholder="Search customers..." class="px-3 py-2 rounded border border-slate-200 w-full sm:w-64 input-reactive shadow-elegant" />
          <div class="flex gap-3">
            <select id="filterUser" class="px-3 py-2 rounded border border-slate-200 text-sm btn-reactive shadow-elegant">
              <option value="all" selected>All Users</option>
              <option value="internal">Internal</option>
              <option value="external">External</option>
            </select>
            <select id="filterSLA" class="px-3 py-2 rounded border border-slate-200 text-sm btn-reactive shadow-elegant">
              <option value="all">All SLA Status</option>
              <option value="priority">Priority Clients</option>
              <option value="recent">Last Contacted Date</option>
              <option value="success">Success Rate</option>
            </select>
            <select id="filterActivity" class="px-3 py-2 rounded border border-slate-200 text-sm btn-reactive shadow-elegant">
              <option value="all">All activity</option>
              <option value="active">Active</option>
              <option value="overdue">Overdue</option>
              <option value="churn_risk">Churn Risk</option>
            </select>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <div class="text-sm text-gray-600 px-2">
          <span id="filterDescription">Showing all customers</span>
        </div>
      </div>

      <!-- Top charts 
      <div class="grid md:grid-cols-3 gap-4 mb-6 loading-progressive" style="animation-delay: 0.1s;">
        <div class="bg-white bg-gradient-elegant rounded-lg p-4 shadow-elegant card-interactive" style="min-height: 250px;">
          <div class="text-sm font-semibold mb-3 text-gray-700">Internal vs External users</div>
          <canvas id="pieChart" style="max-height: 200px;"></canvas>
        </div>
        <div class="bg-white bg-gradient-elegant rounded-lg p-4 shadow-elegant card-interactive" style="min-height: 250px;">
          <div class="text-sm font-semibold mb-3 text-gray-700">Ticket Volume</div>
          <canvas id="lineChart" style="max-height: 200px;"></canvas>
        </div>
        <div class="bg-white bg-gradient-elegant rounded-lg p-4 shadow-elegant card-interactive" style="min-height: 250px;">
          <div class="text-sm font-semibold mb-3 text-gray-700">High Volume Customers</div>
          <canvas id="barChart" style="max-height: 200px;"></canvas>
        </div>
      </div> -->

      <!-- Two column content: customer list + profile -->
      <div class="grid lg:grid-cols-2 gap-6 mb-6 loading-progressive" style="animation-delay: 0.3s;">
        <!-- Customer List -->
        <section class="bg-white bg-gradient-elegant p-4 rounded-lg shadow-elegant card-interactive">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">Customer List</h2>
            <div class="text-sm text-gray-500"><div class="status-live loading-pulse inline-block" data-status="loading"></div>Loading <span id="visibleCount">0</span> of <span id="totalCount"><?php echo $total_customers; ?></span></div>
          </div>

          <div id="customerList" class="space-y-3">
            <!-- Loading skeletons -->
            <div class="customer-skeleton">
              <div class="avatar-skeleton"></div>
              <div>
                <div class="text-skeleton"></div>
                <div class="text-skeleton"></div>
              </div>
            </div>
            <div class="customer-skeleton">
              <div class="avatar-skeleton"></div>
              <div>
                <div class="text-skeleton"></div>
                <div class="text-skeleton"></div>
              </div>
            </div>
            <div class="customer-skeleton">
              <div class="avatar-skeleton"></div>
              <div>
                <div class="text-skeleton"></div>
                <div class="text-skeleton"></div>
              </div>
            </div>
            <!-- List items injected by JS -->
          </div>
        </section>

        <!-- Customer Profile -->
        <aside id="customerProfilePanel" class="bg-white rounded-xl shadow-md p-6 space-y-6" data-user-id="">

          <!-- Header -->
          

          <!-- Profile Section -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <img id="profileAvatar" src="https://api.dicebear.com/7.x/avataaars/svg?seed=Default" alt="Customer Avatar" class="w-14 h-14 rounded-full border">
              <div>
                <h3 id="profileName" class="text-lg font-semibold text-gray-800 editable" contenteditable="<?php echo $isReadOnly ? 'false' : 'false'; ?>">Select a customer</h3>
                <p id="profileType" class="text-sm text-gray-500 editable" contenteditable="<?php echo $isReadOnly ? 'false' : 'false'; ?>">N/A</p>
                <p id="profileEmail" class="text-sm text-gray-600 editable" contenteditable="<?php echo $isReadOnly ? 'false' : 'false'; ?>">N/A</p>
                <p id="profilePhone" class="text-sm text-gray-600 editable" contenteditable="<?php echo $isReadOnly ? 'false' : 'false'; ?>">N/A</p>
              </div>
            </div>
            <div class="flex flex-col space-y-2">
              <button type="button" id="viewTicketBtn" class="bg-slate-800 text-white text-sm px-4 py-1.5 rounded hover:bg-slate-700 transition" disabled>View Ticket</button>
              <button type="button" id="viewHistoryBtn" class="bg-slate-800 text-white text-sm px-4 py-1.5 rounded hover:bg-slate-700 transition">View History</button>
              <button type="button" id="viewProductsBtn" class="bg-slate-800 text-white text-sm px-4 py-1.5 rounded hover:bg-slate-700 transition">Product History</button>
              <?php if ($isReadOnly): ?>
                <span class="text-xs text-gray-500 italic">Read-only mode</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- SLA and CSAT Section -->
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded-lg border">
              <p class="text-sm text-gray-600">SLA Status</p>
              <p id="profileSLA" class="font-medium text-gray-800 editable" contenteditable="<?php echo $isReadOnly ? 'false' : 'false'; ?>">N/A</p>
              <p class="text-xs text-gray-500 mt-1">Priority Response: 24h</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg border flex flex-col justify-center">
              <p class="text-sm text-gray-600">CSAT Score</p>
              <p id="profileCSAT" class="font-medium text-gray-800 text-lg editable" contenteditable="<?php echo $isReadOnly ? 'false' : 'false'; ?>">N/A</p>
            </div>
          </div>

          <!-- Assigned Staff -->
          <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Assigned Department</h4>
            <div class="flex items-center space-x-3 bg-gray-50 p-3 rounded-lg border">
              <img id="staffAvatar" src="https://api.dicebear.com/7.x/avataaars/svg?seed=Staff" alt="Staff Avatar" class="w-10 h-10 rounded-full border">
              <p id="profileStaff" class="text-sm text-gray-800 editable" contenteditable="<?php echo $isReadOnly ? 'false' : 'false'; ?>">N/A</p>
            </div>
          </div>

          <!-- Active Products -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-sm font-semibold text-gray-700">Active Products</h4>
              <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Count: <span id="activeProductsCount">0</span></span>
            </div>
            <div id="profileProducts" class="space-y-2">
              <div class="bg-gray-50 p-3 rounded-lg border text-sm text-gray-500">Select a customer to view active products.</div>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Notes</h4>
            <div id="profileNotes" class="bg-gray-50 p-3 rounded-lg border text-sm text-gray-600 editable" contenteditable="<?php echo $isReadOnly ? 'false' : 'true'; ?>">
              N/A
            </div>
          </div>
        </aside>
      </div>

      <!-- Activity Timeline -->
      <section class="bg-white p-4 rounded-lg shadow-sm">
        <h3 class="font-semibold mb-3">Activity Timeline</h3>
        <ul id="timeline" class="space-y-4 text-sm text-gray-700">
          <?php
          // Get recent ticket activities for all users (internal/external) - comprehensive view
          $activity_query = "SELECT t.reference_id, t.title, t.status, t.created_at, t.type, t.priority,
                            u.name as customer_name, u.user_type, tech.name as tech_name
                            FROM tbl_ticket t
                            LEFT JOIN tbl_user u ON t.user_id = u.user_id
                            LEFT JOIN tbl_technician tech ON t.assigned_technician_id = tech.technician_id
                            ORDER BY t.created_at DESC LIMIT 15";
          $activity_result = $conn->query($activity_query);

          if ($activity_result->num_rows > 0) {
              while ($activity = $activity_result->fetch_assoc()) {
                  $status_text = $activity['status'] == 'complete' ? 'Ticket resolved' :
                                ($activity['status'] == 'pending' ? 'Ticket escalated' : 'Ticket created');
          ?>
          <li class="flex justify-between">
            <div>
              <div class="font-medium"><?php echo htmlspecialchars($status_text); ?> - #<?php echo htmlspecialchars($activity['reference_id']); ?></div>
              <div class="text-gray-500"><?php echo htmlspecialchars($activity['title']); ?> by <?php echo htmlspecialchars($activity['tech_name'] ?: 'system'); ?></div>
            </div>
            <div class="text-xs text-gray-400"><?php echo date('M j, Y', strtotime($activity['created_at'])); ?></div>
          </li>
          <?php
              }
          } else {
          ?>
          <li class="text-gray-500">No recent activities found.</li>
          <?php } ?>
        </ul>
      </section>

    </main>
  </div>

  <!-- Modals (at body level so they are always visible and on top) -->
  <div id="ticketModal" class="modal fixed inset-0 bg-black/40 flex items-center justify-center hidden z-[9999]" style="display:none;visibility:hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-80 max-w-[90vw] relative z-10" style="pointer-events:auto">
      <h3 class="text-lg font-semibold mb-3">Active Ticket</h3>
      <div id="ticketModalContent" class="text-sm text-gray-600">
        <!-- Content loaded dynamically -->
      </div>
      <div class="mt-4 flex justify-end space-x-2">
        <button type="button" class="px-4 py-1.5 bg-gray-200 rounded hover:bg-gray-300" onclick="closeCustomerModal('ticketModal')">Close</button>
      </div>
    </div>
  </div>
  <div id="historyModal" class="modal fixed inset-0 bg-black/40 flex items-center justify-center hidden z-[9999]" style="display:none;visibility:hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96 max-w-[90vw] relative z-10" style="pointer-events:auto">
      <h3 class="text-lg font-semibold mb-3">Interaction History</h3>
      <ul id="historyModalContent" class="text-sm text-gray-600 space-y-1 max-h-64 overflow-y-auto">
        <!-- Content loaded dynamically -->
      </ul>
      <div class="mt-4 flex justify-end space-x-2">
        <button type="button" class="px-4 py-1.5 bg-gray-200 rounded hover:bg-gray-300" onclick="closeCustomerModal('historyModal')">Close</button>
      </div>
    </div>
  </div>
  <div id="productsModal" class="modal fixed inset-0 bg-black/40 flex items-center justify-center hidden z-[9999]" style="display:none;visibility:hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96 max-w-[90vw] relative z-10" style="pointer-events:auto">
      <h3 class="text-lg font-semibold mb-3">Product History</h3>
      <div id="productsModalContent" class="space-y-2 max-h-64 overflow-y-auto">
        <!-- Content loaded dynamically -->
      </div>
      <div class="mt-4 flex justify-end space-x-2">
        <button type="button" class="px-4 py-1.5 bg-gray-200 rounded hover:bg-gray-300" onclick="closeCustomerModal('productsModal')">Close</button>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    // Modal functions - force display so modal is visible and on top
    function openCustomerModal(id) {
      var el = document.getElementById(id);
      if (!el) return;
      
      // Remove hidden class and use setProperty with 'important' to override any CSS
      el.classList.remove('hidden');
      el.style.setProperty('display', 'flex', 'important');
      el.style.setProperty('visibility', 'visible', 'important');
      el.style.setProperty('z-index', '9999', 'important');
      el.style.setProperty('opacity', '1', 'important');
      document.body.style.overflow = 'hidden';
    }

    function closeCustomerModal(id) {
      var el = document.getElementById(id);
      if (!el) return;
      el.classList.add('hidden');
      // Clear all inline styles set by openCustomerModal
      el.style.removeProperty('display');
      el.style.removeProperty('visibility');
      el.style.removeProperty('z-index');
      el.style.removeProperty('opacity');
      document.body.style.overflow = 'auto';
    }

    // Expose with unique names to avoid conflict with ui-enhancements.js
    window.openCustomerModal = openCustomerModal;
    window.closeCustomerModal = closeCustomerModal;

    // Close modal when clicking overlay
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal')) {
        const modal = e.target;
        closeCustomerModal(modal.id);
      }
    });
  </script>

  <!-- Load customer search JS -->
  <script src="../js/customer_search.js?v=3"></script>
  
  <!-- Additional UI enhancements -->
  <script>
    // Initialize smooth page transitions
    document.addEventListener('DOMContentLoaded', function() {
      // Enhance button interactions
      document.querySelectorAll('.btn-reactive, .view-button').forEach(btn => {
        btn.addEventListener('click', function(e) {
          // Add ripple effect
          const ripple = document.createElement('span');
          ripple.style.position = 'absolute';
          ripple.style.borderRadius = '50%';
          ripple.style.background = 'rgba(255, 255, 255, 0.6)';
          ripple.style.transform = 'scale(0)';
          ripple.style.animation = 'ripple 0.6s linear';
          ripple.style.left = (e.offsetX - 10) + 'px';
          ripple.style.top = (e.offsetY - 10) + 'px';
          ripple.style.width = '20px';
          ripple.style.height = '20px';
          this.style.position = 'relative';
          this.style.overflow = 'hidden';
          this.appendChild(ripple);
          setTimeout(() => ripple.remove(), 600);
        });
      });
    });
  </script>

  <!-- <script>
    // Test priority filtering functionality
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Page loaded - testing priority filtering');

      // Override switchView to test functionality
      window.testSwitchView = function(viewType) {
        console.log('Testing switchView with:', viewType);

        // Manually set SLA filter
        document.getElementById('filterSLA').value = viewType === 'priority' ? 'priority' : 'all';
        console.log('SLA filter set to:', document.getElementById('filterSLA').value);

        // Test AJAX calls directly
        const query = '';
        const userType = 'all';
        const slaStatus = viewType === 'priority' ? 'priority' : 'all';
        const activityStatus = 'all';

        console.log('Testing customer API call with:', {query, userType, slaStatus, activityStatus});

        // Test customers API
        fetch('../php/search_customers.php?q=' + query + '&user_type=' + userType + '&sla_status=' + slaStatus + '&activity_status=' + activityStatus + '&page=1&limit=5')
          .then(response => response.json())
          .then(data => console.log('Customer API response:', data))
          .catch(error => console.error('Customer API error:', error));

        // Test analytics API
        fetch('../php/get_analytics.php?q=' + query + '&user_type=' + userType + '&sla_status=' + slaStatus + '&activity_status=' + activityStatus)
          .then(response => response.json())
          .then(data => console.log('Analytics API response:', data))
          .catch(error => console.error('Analytics API error:', error));

        // Test chart data API
        fetch('../php/get_chart_data.php?q=' + query + '&user_type=' + userType + '&sla_status=' + slaStatus + '&activity_status=' + activityStatus)
          .then(response => response.json())
          .then(data => console.log('Chart API response:', data))
          .catch(error => console.error('Chart API error:', error));
      };

      // Make test buttons
      setTimeout(function() {
        const sidebar = document.querySelector('aside');
        if (sidebar) {
          const testDiv = document.createElement('div');
          testDiv.innerHTML = `
            <hr class="my-4">
            <div class="text-xs font-semibold text-gray-700">TEST BUTTONS</div>
            <button id="testAllBtn" class="view-button w-full text-left px-4 py-2.5 mb-2 rounded-lg bg-red-100 text-red-800">TEST All Customers</button>
            <button id="testPriorityBtn" class="view-button w-full text-left px-4 py-2.5 mb-2 rounded-lg bg-red-100 text-red-800">TEST Priority Clients</button>
          `;
          sidebar.appendChild(testDiv);

          document.getElementById('testAllBtn').onclick = function() {
            window.testSwitchView('all');
          };

          document.getElementById('testPriorityBtn').onclick = function() {
            window.testSwitchView('priority');
          };
        }
      }, 2000);
    });
  </script> -->
</body>
</Html>
