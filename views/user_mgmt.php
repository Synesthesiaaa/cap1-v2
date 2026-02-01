<?php
// User Management - Admin only
$useNewStructure = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap.php';
    $useNewStructure = true;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../php/check_um_access.php");

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if (!checkUMAccess()) {
    header("Location: dashboard.php");
    exit();
}

if (!$useNewStructure) {
    include("../php/db.php");
} else {
    try {
        $conn = \Database\Connection::getInstance()->getConnection();
    } catch (\Exception $e) {
        include("../php/db.php");
    }
}

// Role capabilities reference (used for display)
$roleCapabilities = [
    'customer' => ['Create tickets', 'View own tickets', 'Reply to own tickets'],
    'department_head' => ['All customer capabilities', 'Manage department tickets', 'View team tickets', 'Escalate tickets', 'Access customer management'],
    'admin' => ['All capabilities', 'User management', 'Manage all users', 'Manage roles', 'Full system access'],
];

$technicianCapabilities = ['Manage assigned tickets', 'View assigned tickets', 'Reply to tickets', 'Update ticket status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>User Management — Interconnect Solutions Company</title>
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/basicTemp.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <script src="../js/ui-enhancements.js" defer></script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased page-transition" style="background-color: var(--bg-primary, #f8fafc); color: var(--text-primary, #1f2937);">
<?php include("../includes/navbar.php"); ?>
  <div class="max-w-[1400px] mx-auto px-4 py-6">
    <main class="w-full">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <h1 class="text-3xl font-extrabold text-gray-900 shrink-0">User Management</h1>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full lg:max-w-2xl">
          <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
            <button id="btnAddUser" class="w-full sm:w-auto h-10 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition shadow shrink-0 flex items-center justify-center">
              + Add User
            </button>
            <input id="searchInput" type="search" placeholder="Search users..." class="w-full sm:w-52 h-10 px-3 rounded-lg border border-slate-200 text-sm" />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <select id="filterUserType" class="h-10 px-3 rounded-lg border border-slate-200 text-sm min-w-[110px]">
              <option value="all">All Types</option>
              <option value="internal">Internal</option>
              <option value="external">External</option>
            </select>
            <select id="filterRole" class="h-10 px-3 rounded-lg border border-slate-200 text-sm min-w-[110px]">
              <option value="all">All Roles</option>
              <option value="customer">Customer</option>
              <option value="department_head">Department Head</option>
              <option value="admin">Admin</option>
            </select>
            <select id="filterStatus" class="h-10 px-3 rounded-lg border border-slate-200 text-sm min-w-[110px]">
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Users Table -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-4 border-b flex flex-wrap items-center justify-between gap-2">
              <h2 class="font-semibold text-gray-800">Users</h2>
              <p class="text-sm text-gray-500">Showing <span id="visibleCount">0</span> of <span id="totalCount">0</span></p>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Name</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Email</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Type</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Role</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Status</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Actions</th>
                  </tr>
                </thead>
                <tbody id="usersTableBody">
                  <tr><td colspan="6" class="py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
              </table>
            </div>
            <div id="pagination" class="p-4 border-t flex justify-between items-center"></div>
          </div>
        </div>

        <!-- Role & Capabilities Reference -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-md p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Roles & Capabilities</h2>
            <div class="space-y-4 text-sm">
              <?php foreach ($roleCapabilities as $role => $caps): ?>
              <div class="border rounded-lg p-3">
                <div class="font-medium text-gray-800 capitalize mb-2"><?php echo htmlspecialchars($role); ?></div>
                <ul class="space-y-1 text-gray-600">
                  <?php foreach ($caps as $cap): ?>
                  <li>• <?php echo htmlspecialchars($cap); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <?php endforeach; ?>
              <div class="border rounded-lg p-3 bg-amber-50">
                <div class="font-medium text-gray-800 mb-2">Technician</div>
                <p class="text-xs text-gray-600 mb-2">Managed via tbl_technician (separate login)</p>
                <ul class="space-y-1 text-gray-600">
                  <?php foreach ($technicianCapabilities as $cap): ?>
                  <li>• <?php echo htmlspecialchars($cap); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Add/Edit User Modal -->
  <div id="userModal" class="fixed inset-0 bg-black/40 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <h3 id="modalTitle" class="text-lg font-semibold">Add User</h3>
      </div>
      <form id="userForm" class="p-6 space-y-4">
        <input type="hidden" id="userId" name="user_id" value="" />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Name *</label>
          <input type="text" id="userName" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-200 h-10" />
        </div>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Email *</label>
          <input type="email" id="userEmail" name="email" required class="w-full px-3 py-2 rounded-lg border border-slate-200 h-10" />
        </div>
        <div id="passwordField" class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Password * <span class="text-gray-400 text-xs font-normal">(min 4 chars)</span></label>
          <input type="password" id="userPassword" name="password" class="w-full px-3 py-2 rounded-lg border border-slate-200 h-10" placeholder="Leave blank to keep current" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">User Type</label>
            <select id="userType" name="user_type" class="w-full px-3 py-2 rounded-lg border border-slate-200 h-10">
              <option value="internal">Internal</option>
              <option value="external">External</option>
            </select>
          </div>
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">Role</label>
            <select id="userRole" name="user_role" class="w-full px-3 py-2 rounded-lg border border-slate-200 h-10">
              <option value="customer">Customer</option>
              <option value="department_head">Department Head</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Department</label>
          <select id="userDepartment" name="department_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 h-10">
            <option value="">— None —</option>
          </select>
        </div>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Company</label>
          <input type="text" id="userCompany" name="company" class="w-full px-3 py-2 rounded-lg border border-slate-200 h-10" />
        </div>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Phone</label>
          <input type="text" id="userPhone" name="phone" class="w-full px-3 py-2 rounded-lg border border-slate-200 h-10" />
        </div>
        <div id="statusField" class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select id="userStatus" name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200 h-10">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="flex justify-end gap-3 pt-4 border-t mt-6">
          <button type="button" id="btnCancel" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 h-10 min-w-[80px]">Cancel</button>
          <button type="submit" id="btnSave" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 h-10 min-w-[80px]">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete confirmation -->
  <div id="deleteModal" class="fixed inset-0 bg-black/40 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Deactivate User</h3>
      <p class="text-gray-600 text-sm mb-6">Are you sure you want to deactivate this user? They will no longer be able to log in.</p>
      <div class="flex justify-end gap-3">
        <button id="btnDeleteCancel" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 h-10 min-w-[80px]">Cancel</button>
        <button id="btnDeleteConfirm" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 h-10 min-w-[90px]">Deactivate</button>
      </div>
    </div>
  </div>

  <script src="../js/user_mgmt.js"></script>
</body>
</html>
