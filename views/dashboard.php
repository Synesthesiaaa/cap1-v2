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
        <!-- Alerts Section -->
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

        <!-- Quick Actions -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <span class="mr-2">⚡</span>Quick Actions
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-6">
                <a href="../views/create_ticket.php" class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow border border-gray-200">
                    <div class="text-center">
                        <div class="text-blue-500 text-4xl mb-3">📝</div>
                        <h3 class="font-semibold text-gray-900">Create New Ticket</h3>
                        <p class="text-sm text-gray-600 mt-2">Submit a new support request</p>
                    </div>
                </a>
                <a href="../views/user_ticket_monitor.php" class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow border border-gray-200">
                    <div class="text-center">
                        <div class="text-green-500 text-4xl mb-3">📋</div>
                        <h3 class="font-semibold text-gray-900">My Tickets</h3>
                        <p class="text-sm text-gray-600 mt-2">View and track your tickets</p>
                    </div>
                </a>
               <!-- <a href="../views/view_ticket.php" class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow border border-gray-200">
                    <div class="text-center">
                        <div class="text-purple-500 text-4xl mb-3">📈</div>
                        <h3 class="font-semibold text-gray-900">Ticket History</h3>
                        <p class="text-sm text-gray-600 mt-2">View completed and archived tickets</p>
                    </div>
                </a> -->
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
        const hasRoleDashboard = ['technician','department_head'].includes(userRole);

        // Load alerts and pending tasks on page load (for technician, department_head)
        document.addEventListener('DOMContentLoaded', function() {
            if (hasRoleDashboard) {
                loadAlerts();
                loadPendingTasks();
                setInterval(loadAlerts, 300000); // Refresh alerts every 5 minutes
            }
        });

        // Load alerts
        function loadAlerts() {
            const container = document.getElementById('alertsContainer');
            if (!container) return;
            
            fetch('../php/role_dashboard_api.php?action=get_alerts')
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'HTTP ' + response.status);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Error loading alerts:', data.error);
                        container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg"><p class="text-sm font-semibold">Error loading alerts:</p><p class="text-sm">' + (data.error || 'Unknown error') + '</p></div>';
                        return;
                    }
                    alertsData = Array.isArray(data) ? data : [];
                    renderAlerts();
                })
                .catch(error => {
                    console.error('Error loading alerts:', error);
                    if (container) {
                        container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg"><p class="text-sm font-semibold">Error loading alerts:</p><p class="text-sm">' + error.message + '</p><p class="text-xs mt-2">Check browser console for details.</p></div>';
                    }
                });
        }

        // Load pending tasks
        function loadPendingTasks() {
            const container = document.getElementById('pendingTasksContainer');
            if (!container) return;
            
            fetch('../php/role_dashboard_api.php?action=get_dashboard_stats')
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'HTTP ' + response.status);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Error loading dashboard stats:', data.error);
                        container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg"><p class="text-sm font-semibold">Error loading dashboard stats:</p><p class="text-sm">' + (data.error || 'Unknown error') + '</p></div>';
                        return;
                    }
                    pendingTasksData = data || {};
                    renderPendingTasks();
                })
                .catch(error => {
                    console.error('Error loading dashboard stats:', error);
                    if (container) {
                        container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg"><p class="text-sm font-semibold">Error loading dashboard stats:</p><p class="text-sm">' + error.message + '</p><p class="text-xs mt-2">Check browser console for details.</p></div>';
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
                            <h3 class="text-sm font-medium">${alert.title}</h3>
                            <p class="text-sm mt-1">${alert.message}</p>
                            <p class="text-xs mt-2">${alert.count ?? 0} items • Click for details</p>
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

            // Base tasks shared by all roles that use this dashboard section
            const tasks = [
                {
                    title: 'Tickets Awaiting Evaluation',
                    count: pendingTasksData.awaiting_evaluation || 0,
                    description: 'New tickets requiring your review',
                    icon: '📝',
                    color: 'blue',
                    link: monitorBase + '?filter=awaiting'
                },
                {
                    title: 'Recently Assigned',
                    count: pendingTasksData.assigned_today || 0,
                    description: 'Tickets assigned today',
                    icon: '✅',
                    color: 'green',
                    link: monitorBase + '?filter=assigned_today'
                }
            ];

            // Technicians see the escalation queue card
            if (userRole === 'technician') {
                tasks.push({
                    title: 'Escalation Queue',
                    count: pendingTasksData.escalated_tickets || 0,
                    description: 'Tickets approaching or past SLA',
                    icon: '⏰',
                    color: 'red',
                    link: monitorBase + '?filter=escalated'
                });
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
            document.getElementById('alertModalTitle').textContent = alert.title;
            document.getElementById('alertModalContent').innerHTML = `
                <div class="space-y-4">
                    <p>${alert.message}</p>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium mb-2">Details:</h4>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            ${alert.details.map(detail => `<li>${detail}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="flex justify-end">
                        <a href="${alert.action_url}" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 mr-2">
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

        // Generate report function (placeholder)
        function generateReport() {
            alert('Report generation feature will be available soon!');
        }
    </script>
</body>
</html>
