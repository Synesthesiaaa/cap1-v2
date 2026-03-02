<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'technician') {
    header("Location: login.php");
    exit();
}
$techName = $_SESSION['name'] ?? "Technician";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Technician Ticket Monitor</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="../css/ticket_monitor2.css">
</head>
<body class="bg-gray-100 min-h-screen page-transition">
<?php include "../includes/navbar.php"; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-2">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Ticket Dashboard — <span class="text-indigo-600"><?= htmlspecialchars($techName) ?></span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Monitor your assigned tickets, track SLA, and manage priorities in one place.
            </p>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">

        <div id="cardOpen" class="summary-card bg-white p-4 rounded-xl shadow-sm border border-slate-200 cursor-pointer hover:shadow-md transition-shadow">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Assigned</p>
            <p id="cardOpenValue" class="mt-2 text-3xl font-bold text-slate-900">--</p>
        </div>

        <div id="cardDue" class="summary-card bg-white p-4 rounded-xl shadow-sm border border-slate-200 cursor-pointer hover:shadow-md transition-shadow">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Due Soon (42h)</p>
            <p id="cardDueValue" class="mt-2 text-3xl font-bold text-amber-500">--</p>
        </div>

        <div id="cardAtRisk" class="summary-card bg-white p-4 rounded-xl shadow-sm border border-slate-200 cursor-pointer hover:shadow-md transition-shadow">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Due Today</p>
            <p id="cardAtRiskValue" class="mt-2 text-3xl font-bold text-orange-500">--</p>
        </div>

        <div id="cardOverdue" class="summary-card bg-white p-4 rounded-xl shadow-sm border border-slate-200 cursor-pointer hover:shadow-md transition-shadow">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Overdue</p>
            <p id="cardOverdueValue" class="mt-2 text-3xl font-bold text-red-600">--</p>
        </div>

        <div id="cardBacklog" class="summary-card bg-white p-4 rounded-xl shadow-sm border border-slate-200 cursor-pointer hover:shadow-md transition-shadow">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Backlog</p>
            <p id="cardBacklogValue" class="mt-2 text-3xl font-bold text-slate-700">--</p>
        </div>

        <div id="cardEscalations" class="summary-card bg-white p-4 rounded-xl shadow-sm border border-slate-200 cursor-pointer hidden hover:shadow-md transition-shadow">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Escalations</p>
            <p id="cardEscalationsValue" class="mt-2 text-3xl font-bold text-purple-600">--</p>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white p-4 shadow-sm rounded-xl mb-6 flex flex-wrap gap-4 items-center border border-slate-200">

        <input id="searchInput" type="text" class="border border-slate-200 px-3 py-2 rounded w-full md:w-64 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
               placeholder="Search by title, reference, or requester…">

        <!-- Status dropdown -->
        <div id="statusDropdown" class="dropdown-container">
            <label class="text-xs font-semibold text-slate-600">Status</label>
            <div class="dropdown">
                <span class="selected">All</span>
                <span class="chevron">▼</span>
                <ul class="dropdown-list">
                    <li data-value="">All</li>
                    <li data-value="Pending">Pending</li>
                    <li data-value="Assigning">Assigning</li>
                    <li data-value="Complete">Complete</li>
                </ul>
            </div>
            <input type="hidden" id="filterStatus">
        </div>

        <!-- Priority dropdown -->
        <div id="priorityDropdown" class="dropdown-container">
            <label class="text-xs font-semibold text-slate-600">Priority</label>
            <div class="dropdown">
                <span class="selected">All</span>
                <span class="chevron">▼</span>
                <ul class="dropdown-list">
                    <li data-value="">All</li>
                    <li data-value="Low">Low</li>
                    <li data-value="Medium">Medium</li>
                    <li data-value="High">High</li>
                    <li data-value="Urgent">Urgent</li>
                </ul>
            </div>
            <input type="hidden" id="filterPriority">
        </div>

        <!-- TYPE FILTER -->
       <div id="typeDropdown" class="dropdown-container">
           <label class="text-xs font-semibold text-slate-600">Type</label>
           <div class="dropdown">
               <span class="selected">All</span>
               <span class="chevron">▼</span>
               <ul class="dropdown-list">
                   <li data-value="">All</li>
                   <li data-value="IT">IT</li>
                   <li data-value="Facilities">Facilities</li>
                   <li data-value="Finance">Finance</li>
                   <li data-value="Shipping">Shipping</li>
                   <li data-value="Warehouse">Warehouse</li>
                   <li data-value="Engineering">Engineering</li>
                   <li data-value="HR">HR</li>
                   <li data-value="Production">Production</li>
                   <li data-value="Sales">Sales</li>
                   <!-- You may add more here -->
               </ul>
           </div>
           <input type="hidden" id="filterType">
       </div>
        <!-- SORT BY DATE -->
        <div id="sortDropdown" class="dropdown-container">
            <label class="text-xs font-semibold text-slate-600">Sort</label>
            <div class="dropdown">
                <span class="selected">Newest First</span>
                <span class="chevron">▼</span>
                <ul class="dropdown-list">
                    <li data-value="date_desc">Newest First</li>
                    <li data-value="date_asc">Oldest First</li>
                </ul>
            </div>
            <input type="hidden" id="filterSort" value="date_desc">
        </div>

        <button id="clearFiltersBtn" class="ml-auto bg-slate-600 hover:bg-slate-700 text-white px-3 py-2 rounded text-sm">
            Clear Filters
        </button>
    </div>

    <!-- TABLE -->
    <div class="bg-white shadow-sm rounded-xl overflow-x-auto border border-slate-200">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-100 text-xs uppercase tracking-wide text-slate-600">
            <tr>
                <th class="px-3 py-2">Type</th>
                <th class="px-3 py-2">Reference</th>
                <th class="px-3 py-2">Title</th>
                <th class="px-3 py-2">Requester</th>
                <th class="px-3 py-2">Technician</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Priority</th>
                <th class="px-3 py-2">SLA</th>
                <th class="px-3 py-2">Progress</th>
                <th class="px-3 py-2 text-right">Action</th>
            </tr>
            </thead>
            <tbody id="ticketsBody" class="divide-y divide-slate-100"></tbody>
        </table>
    </div>

    <div id="pagination" class="flex gap-2 mt-4 justify-center"></div>
</div>

<script src="../js/tech_monitor.js"></script>

<!-- SUMMARY CARD JS -->
<script>
  document.addEventListener("DOMContentLoaded", () => {

    // Assigned to the technician
    document.getElementById("cardOpen").addEventListener("click", () => {
        loadTickets({ assigned_only: 1 });
    });

    // Due within 42 hours
    document.getElementById("cardDue").addEventListener("click", () => {
        loadTickets({ due_within_hours: 42 });
    });

    // Due today
    document.getElementById("cardAtRisk").addEventListener("click", () => {
        loadTickets({ due_today: 1 });
    });

    // Overdue
    document.getElementById("cardOverdue").addEventListener("click", () => {
        loadTickets({ overdue: 1 });
    });

    // Backlog
    document.getElementById("cardBacklog").addEventListener("click", () => {
        loadTickets({ backlog: 1 });
    });

    // Escalations
    document.getElementById("cardEscalations").addEventListener("click", () => {
        loadTickets({ escalated: 1 });
    });

});
const SUMMARY_REFRESH_INTERVAL = 30000; // 30 seconds

document.addEventListener("DOMContentLoaded", () => {
    loadSummaryCards();
    // Auto-refresh summary cards every 30 seconds
    setInterval(loadSummaryCards, SUMMARY_REFRESH_INTERVAL);
});

// Close dropdowns when clicking outside
document.addEventListener("click", function (e) {
    document.querySelectorAll(".dropdown-list.visible").forEach(list => {
        if (!list.parentElement.contains(e.target)) {
            list.classList.remove("visible");
        }
    });
});

function loadSummaryCards() {
    fetch("../php/get_ticket_summary.php?tech_id=<?= $_SESSION['id'] ?>")
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                console.error("Error loading summary:", data.error);
                // Show error on cards
                document.querySelectorAll('[id$="Value"]').forEach(el => {
                    if (el) el.textContent = "?";
                });
                return;
            }
            updateSummaryCards(data);
            updateEscCard(data.escalations);
        })
        .catch(err => {
            console.error("Error fetching summary cards:", err);
            // Show error indicator on cards
            document.querySelectorAll('[id$="Value"]').forEach(el => {
                if (el) el.textContent = "?";
            });
        });
}

function updateSummaryCards(s) {
    if (!s) return;
    
    const cardOpenValue = document.getElementById("cardOpenValue");
    const cardDueValue = document.getElementById("cardDueValue");
    const cardAtRiskValue = document.getElementById("cardAtRiskValue");
    const cardOverdueValue = document.getElementById("cardOverdueValue");
    const cardBacklogValue = document.getElementById("cardBacklogValue");
    const cardEscalationsValue = document.getElementById("cardEscalationsValue");
    
    if (cardOpenValue) cardOpenValue.textContent = s.open ?? 0;
    if (cardDueValue) cardDueValue.textContent = s.dueSoon ?? 0;
    if (cardAtRiskValue) cardAtRiskValue.textContent = s.dueToday ?? 0;
    if (cardOverdueValue) cardOverdueValue.textContent = s.overdue ?? 0;
    if (cardBacklogValue) cardBacklogValue.textContent = s.backlog ?? 0;
    if (cardEscalationsValue) cardEscalationsValue.textContent = s.escalations ?? 0;
}

function updateEscCard(x) {
    const el = document.getElementById("cardEscalations");
    if (!el) return;
    
    if (x > 0) el.classList.remove("hidden");
    else el.classList.add("hidden");
}
</script>

</body>
</html>
