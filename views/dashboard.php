<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Role-based redirection and setup
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['id'];
$user_name = $_SESSION['name'] ?? 'User';

// Base URL for views (ensures ticket links resolve correctly and session is preserved)
$views_base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/';

// Pending tasks layout:
// - Technician: 3 cards → 3 columns on large screens
// - Department Head (and others with this section): 2 cards → 2 columns on large screens
if ($user_role === 'technician') {
    $pendingGridClasses = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3';
} else {
    $pendingGridClasses = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="../css/theme.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/basicTemp.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/ui-enhancements.js" defer></script>
    <script src="../js/animations.js" defer></script>
    <script src="../js/ticket-interactions.js" defer></script>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 min-h-screen page-transition">
<?php include("../includes/navbar.php"); ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p class="text-gray-600">Welcome to the Interconnect Solutions Company ticketing system.</p>
        </div>

        <?php if (in_array($user_role, ['technician','department_head'])): ?>
        <!-- Alerts Section (technician / department_head) -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">🚨</span>Alerts
            </h2>
            <div id="alertsContainer" class="space-y-4">
                <div class="animate-pulse text-center py-8">
                    <div class="text-gray-500">Loading alerts...</div>
                </div>
            </div>
        </div>

        <!-- Pending Tasks Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2">📋</span>Pending Tasks
                </h2>
                <button id="refreshTasksBtn" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm">
                    Refresh
                </button>
            </div>
            <div id="pendingTasksContainer" class="<?php echo $pendingGridClasses; ?> gap-6">
                <div class="animate-pulse bg-gray-200 rounded-lg h-32"></div>
                <div class="animate-pulse bg-gray-200 rounded-lg h-32"></div>
                <div class="animate-pulse bg-gray-200 rounded-lg h-32"></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
        <!-- Admin System Overview -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2">📊</span>System Overview
                </h2>
                <button id="refreshAdminBtn" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm">
                    Refresh
                </button>
            </div>
            <div id="adminAlertsContainer" class="space-y-3">
                <div class="animate-pulse text-center py-4"><div class="text-gray-500">Loading alerts...</div></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user_role === 'customer'): ?>
        <!-- Customer Notifications -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <span class="mr-2">🔔</span>Notifications
                    <span id="customerUnreadBadge" class="hidden ml-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"></span>
                </h2>
                <button id="markAllReadBtn" class="hidden text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Mark all as read
                </button>
            </div>
            <div id="customerNotifsContainer" class="space-y-3">
                <div class="animate-pulse text-center py-4"><div class="text-gray-500">Loading notifications...</div></div>
            </div>
            <!-- Ticket summary cards -->
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-blue-700" id="custTotalTickets">—</div>
                    <div class="text-sm text-gray-600 mt-1">Total Tickets</div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-yellow-700" id="custOpenTickets">—</div>
                    <div class="text-sm text-gray-600 mt-1">Open</div>
                </div>
                <div class="bg-green-50 border border-green-200 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-green-700" id="custClosedTickets">—</div>
                    <div class="text-sm text-gray-600 mt-1">Resolved</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">⚡</span>Quick Actions
            </h2>
            <?php
                // Role-aware monitor page link (root-relative so session is preserved)
                if ($user_role === 'technician') {
                    $myTicketsHref = $views_base . 'tech_ticket_monitor.php';
                    $myTicketsLabel = 'My Assigned Tickets';
                    $myTicketsDesc  = 'View and manage your assigned tickets';
                } elseif ($user_role === 'department_head') {
                    $myTicketsHref = $views_base . 'department_head_monitor.php';
                    $myTicketsLabel = 'Ticket Monitor';
                    $myTicketsDesc  = 'Monitor all tickets in your department';
                } else {
                    $myTicketsHref = $views_base . 'user_ticket_monitor.php';
                    $myTicketsLabel = 'My Tickets';
                    $myTicketsDesc  = 'View and track your tickets';
                }
                // Admins don't need "Create Ticket", show customer management instead
                $showCreateTicket = !in_array($user_role, ['admin']);
            ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-6">
                <?php if ($showCreateTicket): ?>
                <a href="<?php echo htmlspecialchars($views_base); ?>create_ticket.php" class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow border border-gray-200">
                    <div class="text-center">
                        <div class="text-blue-500 text-4xl mb-3">📝</div>
                        <h3 class="font-semibold text-gray-900">Create New Ticket</h3>
                        <p class="text-sm text-gray-600 mt-2">Submit a new support request</p>
                    </div>
                </a>
                <?php else: ?>
                <a href="<?php echo htmlspecialchars($views_base); ?>cust_mgmt.php" class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow border border-gray-200">
                    <div class="text-center">
                        <div class="text-blue-500 text-4xl mb-3">👥</div>
                        <h3 class="font-semibold text-gray-900">Customer Management</h3>
                        <p class="text-sm text-gray-600 mt-2">Manage customer accounts and tickets</p>
                    </div>
                </a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($myTicketsHref); ?>" class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow border border-gray-200">
                    <div class="text-center">
                        <div class="text-green-500 text-4xl mb-3">📋</div>
                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($myTicketsLabel); ?></h3>
                        <p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($myTicketsDesc); ?></p>
                    </div>
                </a>
            </div>

            <!-- Dashboard Stats -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-green-50 border border-green-200 p-6 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mr-4">
                            <span class="text-white font-semibold">✓</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Quick Support</h3>
                            <p class="text-sm text-gray-600">Get help within hours</p>
                        </div>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 p-6 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mr-4">
                            <span class="text-white font-semibold">24</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">24/7 Support</h3>
                            <p class="text-sm text-gray-600">Round-the-clock assistance</p>
                        </div>
                    </div>
                </div>
                <div class="bg-purple-50 border border-purple-200 p-6 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center mr-4">
                            <span class="text-white font-semibold">★</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Expert Team</h3>
                            <p class="text-sm text-gray-600">Certified technical professionals</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Modal -->
    <div id="alertModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900" id="alertModalTitle">Alert Details</h3>
                    <button id="closeAlertModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="alertModalContent"></div>
            </div>
        </div>
    </div>

    <script>
        let alertsData = [];
        let pendingTasksData = [];
        const userRole = "<?php echo htmlspecialchars($user_role, ENT_QUOTES); ?>";
        const viewsBase = "<?php echo htmlspecialchars($views_base, ENT_QUOTES); ?>";
        const hasRoleDashboard = ['technician','department_head'].includes(userRole);

        // Sanitize strings before inserting into innerHTML
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Safe JSON fetch with resilient error handling
        function apiFetch(url) {
            return fetch(url).then(response => {
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Server returned non-JSON response (HTTP ' + response.status + ')');
                    });
                }
                return response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.error || 'HTTP ' + response.status);
                    }
                    return data;
                });
            });
        }

        // Load alerts and pending tasks on page load (for technician, department_head)
        document.addEventListener('DOMContentLoaded', function() {
            if (hasRoleDashboard) {
                loadAlerts();
                loadPendingTasks();
                setInterval(loadAlerts, 120000); // Refresh alerts every 2 minutes
            }
        });

        // Load alerts
        function loadAlerts() {
            const container = document.getElementById('alertsContainer');
            if (!container) return;

            apiFetch('../php/role_dashboard_api.php?action=get_alerts')
                .then(data => {
                    alertsData = Array.isArray(data) ? data : [];
                    renderAlerts();
                })
                .catch(error => {
                    console.error('Error loading alerts:', error);
                    if (container) {
                        container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg"><p class="text-sm font-semibold">Could not load alerts</p><p class="text-sm">' + escapeHtml(error.message) + '</p></div>';
                    }
                });
        }

        // Load pending tasks
        function loadPendingTasks() {
            const container = document.getElementById('pendingTasksContainer');
            if (!container) return;

            apiFetch('../php/role_dashboard_api.php?action=get_dashboard_stats')
                .then(data => {
                    pendingTasksData = data || {};
                    renderPendingTasks();
                })
                .catch(error => {
                    console.error('Error loading dashboard stats:', error);
                    if (container) {
                        container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg"><p class="text-sm font-semibold">Could not load task stats</p><p class="text-sm">' + escapeHtml(error.message) + '</p></div>';
                    }
                });
        }

        // Render alerts
        function renderAlerts() {
            const container = document.getElementById('alertsContainer');

            if (alertsData.length === 0) {
                container.innerHTML = '<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg"><div class="flex"><div>✅</div><div class="ml-3"><p class="text-sm">All clear! No immediate alerts.</p></div></div></div>';
                return;
            }

            container.innerHTML = '';

            alertsData.forEach(alert => {
                let alertClass = '';
                let icon = '⚠️';

                switch (alert.type) {
                    case 'critical':
                        alertClass = 'bg-red-50 border-red-200 text-red-800';
                        icon = '🚨';
                        break;
                    case 'warning':
                        alertClass = 'bg-yellow-50 border-yellow-200 text-yellow-800';
                        icon = '⚠️';
                        break;
                    case 'info':
                        alertClass = 'bg-blue-50 border-blue-200 text-blue-800';
                        icon = 'ℹ️';
                        break;
                    default:
                        alertClass = 'bg-gray-50 border-gray-200 text-gray-800';
                }

                const alertDiv = document.createElement('div');
                alertDiv.className = `border px-4 py-3 rounded-lg ${alertClass} cursor-pointer hover:opacity-80 transition-opacity`;
                alertDiv.onclick = () => showAlertDetails(alert);

                alertDiv.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">${icon}</div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium">${escapeHtml(alert.title)}</h3>
                            <p class="text-sm mt-1">${escapeHtml(alert.message)}</p>
                            <p class="text-xs mt-2">${escapeHtml(alert.count ?? 0)} items • Click for details</p>
                        </div>
                    </div>
                `;

                container.appendChild(alertDiv);
            });
        }

        // Render pending tasks
        function renderPendingTasks() {
            const container = document.getElementById('pendingTasksContainer');

            // Determine correct monitor page based on role
            let monitorBase = 'user_ticket_monitor.php';
            if (userRole === 'technician') {
                monitorBase = 'tech_ticket_monitor.php';
            } else if (userRole === 'department_head') {
                monitorBase = 'department_head_monitor.php';
            }

            // Cards are role-specific so labels accurately describe the data
            let tasks = [];
            if (userRole === 'technician') {
                tasks = [
                    {
                        title: 'My Open Tickets',
                        count: pendingTasksData.open_tickets || 0,
                        description: 'Active tickets currently assigned to you',
                        icon: '📝',
                        color: 'blue',
                        link: viewsBase + monitorBase + '?filter=open'
                    },
                    {
                        title: 'New Today',
                        count: pendingTasksData.new_today || 0,
                        description: 'Tickets assigned to you that arrived today',
                        icon: '✅',
                        color: 'green',
                        link: viewsBase + monitorBase + '?filter=new_today'
                    },
                    {
                        title: 'Escalation Queue',
                        count: pendingTasksData.escalated_tickets || 0,
                        description: 'Tickets approaching or past SLA deadline',
                        icon: '⏰',
                        color: 'red',
                        link: viewsBase + monitorBase + '?filter=escalated'
                    }
                ];
            } else {
                // department_head
                tasks = [
                    {
                        title: 'Active Department Tickets',
                        count: pendingTasksData.open_tickets || 0,
                        description: 'Unassigned and in-progress tickets in your department',
                        icon: '📝',
                        color: 'blue',
                        link: viewsBase + monitorBase + '?filter=active'
                    },
                    {
                        title: 'New Today',
                        count: pendingTasksData.new_today || 0,
                        description: 'Tickets submitted to your department today',
                        icon: '✅',
                        color: 'green',
                        link: viewsBase + monitorBase + '?filter=new_today'
                    }
                ];
            }

            container.innerHTML = '';

            tasks.forEach(task => {
                const taskDiv = document.createElement('div');
                taskDiv.className = `bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow border border-gray-200`;
                taskDiv.innerHTML = `
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <span class="text-${task.color}-500 text-2xl mr-3">${task.icon}</span>
                            <h3 class="font-semibold text-gray-900">${task.title}</h3>
                        </div>
                        <span class="text-2xl font-bold text-${task.color}-600">${task.count}</span>
                    </div>
                    <p class="text-gray-600 text-sm mb-3">${task.description}</p>
                    <a href="${task.link}" class="inline-flex items-center text-sm text-${task.color}-600 hover:text-${task.color}-500 font-medium">
                        View Details →
                    </a>
                `;

                container.appendChild(taskDiv);
            });
        }

        // Show alert details in modal
        function showAlertDetails(alert) {
            document.getElementById('alertModalTitle').textContent = alert.title || 'Alert Details';
            const details = Array.isArray(alert.details) ? alert.details : [];
            const detailItems = details.length > 0
                ? details.map(d => `<li>${escapeHtml(d)}</li>`).join('')
                : '<li class="text-gray-500">No additional details available.</li>';
            document.getElementById('alertModalContent').innerHTML = `
                <div class="space-y-4">
                    <p>${escapeHtml(alert.message)}</p>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium mb-2">Details:</h4>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            ${detailItems}
                        </ul>
                    </div>
                    <div class="flex justify-end">
                        <a href="${alert.action_url ? escapeHtml(viewsBase + alert.action_url) : '#'}" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 mr-2">
                            Take Action
                        </a>
                        <button onclick="document.getElementById('alertModal').classList.add('hidden')" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400">
                            Close
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('alertModal').classList.remove('hidden');
        }

        // Close modal when clicking close button
        const closeAlertModal = document.getElementById('closeAlertModal');
        if (closeAlertModal) {
            closeAlertModal.addEventListener('click', function() {
                const alertModal = document.getElementById('alertModal');
                if (alertModal) {
                    alertModal.classList.add('hidden');
                }
            });
        }

        // Close modal when clicking outside
        const alertModal = document.getElementById('alertModal');
        if (alertModal) {
            alertModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        }

        // Refresh tasks button (for technician, department_head)
        const refreshBtn = document.getElementById('refreshTasksBtn');
        if (refreshBtn && hasRoleDashboard) {
            refreshBtn.addEventListener('click', function() {
                loadPendingTasks();
                loadAlerts();
            });
        }

        // ── Customer notifications ────────────────────────────────────────────
        if (userRole === 'customer') {
            document.addEventListener('DOMContentLoaded', function() {
                loadCustomerNotifications();
                setInterval(loadCustomerNotifications, 60000); // Refresh every minute
            });
        }

        function loadCustomerNotifications() {
            const container = document.getElementById('customerNotifsContainer');
            if (!container) return;

            apiFetch('../php/role_dashboard_api.php?action=get_customer_notifications')
                .then(data => {
                    // Update ticket summary counts
                    const s = data.ticket_summary || {};
                    const totalEl  = document.getElementById('custTotalTickets');
                    const openEl   = document.getElementById('custOpenTickets');
                    const closedEl = document.getElementById('custClosedTickets');
                    if (totalEl)  totalEl.textContent  = s.total  ?? 0;
                    if (openEl)   openEl.textContent   = s.open   ?? 0;
                    if (closedEl) closedEl.textContent = s.closed ?? 0;

                    // Update unread badge
                    const badge = document.getElementById('customerUnreadBadge');
                    const markAllBtn = document.getElementById('markAllReadBtn');
                    const unread = data.unread_count || 0;
                    if (badge) {
                        if (unread > 0) {
                            badge.textContent = unread > 99 ? '99+' : unread;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                    if (markAllBtn) {
                        if (unread > 0) {
                            markAllBtn.classList.remove('hidden');
                        } else {
                            markAllBtn.classList.add('hidden');
                        }
                    }

                    renderCustomerNotifications(data.notifications || []);
                })
                .catch(error => {
                    console.error('Error loading customer notifications:', error);
                    if (container) {
                        container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">Could not load notifications. Please refresh the page.</div>';
                    }
                });
        }

        function renderCustomerNotifications(notifications) {
            const container = document.getElementById('customerNotifsContainer');
            if (!container) return;

            if (!notifications.length) {
                container.innerHTML = '<div class="bg-gray-50 border border-gray-200 text-gray-600 px-4 py-4 rounded-lg text-sm text-center">No notifications yet. You\'ll be notified when your tickets are updated.</div>';
                return;
            }

            const typeIcon = { reply: '💬', status_change: '🔄', assignment: '👤', alert: '⚠️', info: 'ℹ️' };
            container.innerHTML = notifications.map(n => {
                const icon     = typeIcon[n.type] || 'ℹ️';
                const unreadCls = n.is_read == 0 ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-white';
                const dotHtml  = n.is_read == 0 ? '<span class="inline-block w-2 h-2 bg-blue-500 rounded-full mr-2 flex-shrink-0"></span>' : '<span class="inline-block w-2 h-2 mr-2 flex-shrink-0"></span>';
                const linkAttr = n.link ? `href="${escapeHtml(n.link)}"` : '';
                const tag      = n.link ? 'a' : 'div';
                return `<${tag} ${linkAttr} data-notif-id="${escapeHtml(n.notification_id)}"
                    class="flex items-start border ${unreadCls} px-4 py-3 rounded-lg cursor-pointer hover:opacity-80 transition-opacity text-sm notif-item"
                    onclick="markNotifRead(${escapeHtml(n.notification_id)}, this)">
                    <span class="flex-shrink-0 mr-3 text-base">${icon}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center">${dotHtml}<span class="font-medium truncate">${escapeHtml(n.title)}</span></div>
                        <p class="text-gray-600 mt-0.5">${escapeHtml(n.message)}</p>
                        <p class="text-gray-400 text-xs mt-1">${escapeHtml(n.created_at)}</p>
                    </div>
                </${tag}>`;
            }).join('');
        }

        function markNotifRead(notifId, el) {
            if (!notifId) return;
            fetch('../php/notifications_api.php?action=mark_read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notifId })
            }).then(() => {
                // Visually mark as read
                if (el) {
                    el.classList.remove('border-blue-300', 'bg-blue-50');
                    el.classList.add('border-gray-200', 'bg-white');
                    const dot = el.querySelector('span.bg-blue-500');
                    if (dot) dot.classList.remove('bg-blue-500');
                }
                // Refresh badge count
                loadCustomerNotifications();
            }).catch(err => console.error('Failed to mark notification read:', err));
        }

        // Mark all read button
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function() {
                fetch('../php/notifications_api.php?action=mark_all_read', { method: 'POST' })
                    .then(() => loadCustomerNotifications())
                    .catch(err => console.error('Failed to mark all read:', err));
            });
        }

        // ── Admin system overview ─────────────────────────────────────────────
        if (userRole === 'admin') {
            document.addEventListener('DOMContentLoaded', function() {
                loadAdminOverview();
                setInterval(loadAdminOverview, 120000); // Refresh every 2 minutes
            });
        }

        function loadAdminOverview() {
            const alertsEl = document.getElementById('adminAlertsContainer');
            if (!alertsEl) return;

            apiFetch('../php/role_dashboard_api.php?action=get_admin_overview')
                .then(data => {
                    if (alertsEl) renderAdminAlerts(data.alerts || []);
                })
                .catch(error => {
                    console.error('Error loading admin overview:', error);
                    if (alertsEl) alertsEl.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">Could not load admin overview.</div>';
                });
        }

        function renderAdminAlerts(alerts) {
            const container = document.getElementById('adminAlertsContainer');
            if (!container) return;
            if (!alerts.length) {
                container.innerHTML = '<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">✅ No critical system alerts.</div>';
                return;
            }
            container.innerHTML = alerts.map(a => {
                const cls  = a.type === 'critical' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-yellow-50 border-yellow-200 text-yellow-800';
                const icon = a.type === 'critical' ? '🚨' : '⚠️';
                return `<div class="border ${cls} px-4 py-3 rounded-lg flex items-start">
                    <span class="mr-3 text-base">${icon}</span>
                    <div>
                        <p class="text-sm font-semibold">${escapeHtml(a.title)}</p>
                        <p class="text-sm">${escapeHtml(a.message)}</p>
                        <a href="${a.action_url ? escapeHtml(viewsBase + a.action_url) : '#'}" class="text-xs underline mt-1 inline-block">View tickets →</a>
                    </div>
                </div>`;
            }).join('');
        }

        // Refresh admin button
        const refreshAdminBtn = document.getElementById('refreshAdminBtn');
        if (refreshAdminBtn) {
            refreshAdminBtn.addEventListener('click', loadAdminOverview);
        }

        // Generate report function (placeholder)
        function generateReport() {
            alert('Report generation feature will be available soon!');
        }
    </script>
</body>
</html>
