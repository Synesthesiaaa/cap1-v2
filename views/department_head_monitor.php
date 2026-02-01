<?php
include("../php/db.php");
session_start();

//Check role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'department_head') {
    header("Location: login.php");
    exit();
}

$departmentHeadId = $_SESSION['id'];
$departmentHeadName = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Department Head Ticket Monitor</title>
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/basicTemp.css">
  <link rel="stylesheet" href="../css/ticket_monitor.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <script src="../js/ui-enhancements.js" defer></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Inter"', 'ui-sans-serif', 'system-ui', 'sans-serif']
          },
          colors: {
            brand: {
              900: '#083b54',
              700: '#0b4c6a'
            }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen flex flex-col page-transition" style="background-color: var(--bg-primary, #f8fafc);">
<?php include("../includes/navbar.php"); ?>
  <div class="max-w-[1400px] mx-auto px-4 py-6 flex-1 w-full">
  <main class="space-y-8">

    <!-- Header -->
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-800">Department Ticket Monitor</h1>
      <div class="text-sm text-gray-500">
        Logged in as <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($departmentHeadName); ?></span>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="summaryCards">
      <div id="cardOpen" class="summary-card bg-white p-4 rounded-xl shadow hover:bg-blue-50 cursor-pointer text-center">
        <p class="text-gray-600 text-sm">Open Tickets</p>
        <h2 class="text-2xl font-bold text-gray-800" id="openCount">--</h2>
      </div>
      <div id="cardPending" class="summary-card bg-white p-4 rounded-xl shadow hover:bg-blue-50 cursor-pointer text-center">
        <p class="text-gray-600 text-sm">Pending</p>
        <h2 class="text-2xl font-bold text-gray-800" id="pendingCount">--</h2>
      </div>
      <div id="cardAssigned" class="summary-card bg-white p-4 rounded-xl shadow hover:bg-blue-50 cursor-pointer text-center">
        <p class="text-gray-600 text-sm">Assigned</p>
        <h2 class="text-2xl font-bold text-gray-800" id="assignedCount">--</h2>
      </div>
      <div id="cardComplete" class="summary-card bg-white p-4 rounded-xl shadow hover:bg-blue-50 cursor-pointer text-center">
        <p class="text-gray-600 text-sm">Completed</p>
        <h2 class="text-2xl font-bold text-gray-800" id="completeCount">--</h2>
      </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow flex flex-wrap items-center gap-3">
      <button id="clearFiltersBtn" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400">Clear</button>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white p-4 rounded-xl shadow overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead class="text-gray-600 border-b">
          <tr>
            <th class="sortable cursor-pointer hover:bg-gray-50 px-2 py-2" data-column="reference_id">Ticket ID<span class="sort-arrow ml-1">↕</span></th>
            <th class="sortable cursor-pointer hover:bg-gray-50 px-2 py-2" data-column="title">Title<span class="sort-arrow ml-1">↕</span></th>
            <th class="sortable cursor-pointer hover:bg-gray-50 px-2 py-2" data-column="category">Category<span class="sort-arrow ml-1">↕</span></th>
            <th class="sortable cursor-pointer hover:bg-gray-50 px-2 py-2" data-column="type">Type<span class="sort-arrow ml-1">↕</span></th>
            <th class="sortable cursor-pointer hover:bg-gray-50 px-2 py-2" data-column="user_name">Requester<span class="sort-arrow ml-1">↕</span></th>
            <th class="sortable cursor-pointer hover:bg-gray-50 px-2 py-2" data-column="urgency">Urgency<span class="sort-arrow ml-1">↕</span></th>
            <th class="sortable cursor-pointer hover:bg-gray-50 px-2 py-2" data-column="status">Status<span class="sort-arrow ml-1">↕</span></th>
            <th class="sortable cursor-pointer hover:bg-gray-50 px-2 py-2" data-column="created_at">Created<span class="sort-arrow ml-1">↕</span></th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="ticketsBody" class="text-gray-700">
          <tr><td colspan="9" class="text-center py-6 text-gray-500">Loading tickets...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div id="pagination" class="flex justify-center items-center gap-2 mt-4"></div>
  </main>
  </div>

  <script src="../js/department_head_monitor.js"></script>
</body>
</html>
