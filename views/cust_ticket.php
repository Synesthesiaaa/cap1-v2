<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include("../php/db.php");

// Get ticket reference from URL
$ticket_ref = $_GET['ref'] ?? '';

// Fetch ticket details
$ticket_query = "SELECT t.*, u.name AS customer_name, u.email AS customer_email, u.company AS customer_company,
                 tech.name AS technician_name, d.department_name
                 FROM tbl_ticket t
                 LEFT JOIN tbl_user u ON t.user_id = u.user_id
                 LEFT JOIN tbl_technician tech ON t.assigned_technician_id = tech.technician_id
                 LEFT JOIN tbl_department d ON u.department_id = d.department_id
                 WHERE t.reference_id = ?";
$stmt = $conn->prepare($ticket_query);
$stmt->bind_param("s", $ticket_ref);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

$ticket_not_found = !$ticket;

// Fetch ticket replies only if ticket exists
$replies_result = null;
if (!$ticket_not_found) {
$replies_query = "SELECT r.*,
                  CASE r.replied_by
                      WHEN 'user' THEN u.name
                      WHEN 'technician' THEN COALESCE(tech.name, 'Support Agent')
                      WHEN 'system' THEN 'System'
                  END AS replier_name,
                  CASE r.replied_by
                      WHEN 'user' THEN 'Customer'
                      WHEN 'technician' THEN 'Support Agent'
                      WHEN 'system' THEN 'System'
                  END AS reply_type
                  FROM tbl_ticket_reply r
                  LEFT JOIN tbl_user u ON r.replied_by = 'user' AND r.replier_id = u.user_id
                  LEFT JOIN tbl_technician tech ON r.replied_by = 'technician' AND r.replier_id = tech.technician_id
                  WHERE r.ticket_id = ?
                  ORDER BY r.created_at ASC";
    $replies_stmt = $conn->prepare($replies_query);
    $replies_stmt->bind_param("i", $ticket['ticket_id']);
    $replies_stmt->execute();
    $replies_result = $replies_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Ticket — <?php echo htmlspecialchars((!$ticket_not_found && $ticket) ? ($ticket['title'] ?: 'No Title') : 'Not Found'); ?></title>
  
  <!-- Theme CSS -->
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/basicTemp.css">
  
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- UI Enhancements -->
  <script src="../js/ui-enhancements.js" defer></script>
  <script src="../js/animations.js" defer></script>
  <script src="../js/popup.js"></script>
  <script>
    // Tailwind config: extend fonts
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

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
    }
    .text-muted {
      color: #64748b;
    }
    .card {
      background-color: white;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 1.25rem;
    }
    .btn-primary {
      background-color: #2563eb;
      color: white;
      border-radius: 6px;
      padding: 0.5rem 1.25rem;
      font-weight: 500;
    }
    .btn-primary:hover {
      background-color: #1e40af;
    }
    .badge {
      font-size: 0.75rem;
      font-weight: 500;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
    }
    .badge-gray {
      background-color: #f1f5f9;
      color: #475569;
    }
    .badge-green {
      background-color: #dcfce7;
      color: #166534;
    }
    .badge-red {
      background-color: #fee2e2;
      color: #991b1b;
    }
    .badge-yellow {
      background-color: #fef9c3;
      color: #854d0e;
    }
  </style>
</head>
<body class="page-transition" style="background-color: var(--bg-primary, #f8fafc);">
<?php include("../includes/navbar.php"); ?>
  <!-- Page content -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if ($ticket_not_found): ?>
    <div class="text-center py-12">
      <h1 class="text-2xl font-semibold text-gray-900 mb-2">Ticket Not Found</h1>
      <p class="text-gray-600 mb-6">The ticket you're looking for doesn't exist or may have been deleted.</p>
      <a href="cust_mgmt.php" class="btn-primary">Back to Customer Management</a>
    </div>
    <?php else: ?>
    <div class="mb-6 text-sm text-gray-500">View Ticket • Ticket <span class="font-medium text-gray-700">#<?php echo htmlspecialchars($ticket['reference_id']); ?></span></div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Main column (left) -->
      <section class="lg:col-span-8 space-y-6">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900"><?php echo htmlspecialchars($ticket['title'] ?: 'No Title'); ?></h1>
            <div class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars(ucfirst($ticket['status'])); ?> • Created <?php echo htmlspecialchars($ticket['created_at'] ? date('d F, Y', strtotime($ticket['created_at'])) : 'Unknown'); ?></div>
          </div>
          <div class="hidden md:flex items-center space-x-3">
            <button id="quickResolve" class="bg-brand-700 hover:bg-brand-900 text-white px-4 py-2 rounded shadow text-sm">✓ Complete</button>
          </div>
        </div>

        <!-- Ticket Details card -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
          <h3 class="font-medium text-gray-800 mb-3">Ticket Details</h3>
          <p class="text-gray-700 text-sm leading-relaxed mb-4">
            <?php echo htmlspecialchars($ticket['description'] ?: 'No description available.'); ?>
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <div class="text-xs text-gray-500 mb-1">Priority</div>
              <div class="inline-block bg-<?php echo $ticket['priority'] === 'high' ? 'red' : ($ticket['priority'] === 'urgent' || strtolower($ticket['priority']) === 'critical' ? 'red' : 'gray'); ?>-100 text-<?php echo $ticket['priority'] === 'high' ? 'red' : ($ticket['priority'] === 'urgent' || strtolower($ticket['priority']) === 'critical' ? 'red' : 'gray'); ?>-800 text-xs px-3 py-1 rounded-full font-medium"><?php 
                $priorityDisplay = match(strtolower($ticket['priority'] ?? 'low')) {
                    'critical' => 'Urgent',
                    'regular' => 'Medium',
                    default => ucfirst($ticket['priority'] ?: 'Low')
                };
                echo htmlspecialchars($priorityDisplay);
              ?></div>
            </div>
            <div>
              <div class="text-xs text-gray-500 mb-1">Category</div>
              <div class="text-sm font-medium"><?php echo htmlspecialchars($ticket['category'] ?: 'General'); ?></div>
            </div>
          </div>
        </div>

        <!-- Conversation card -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
          <h3 class="font-medium text-gray-800 mb-4">Conversation</h3>

          <div id="conversation" class="space-y-4 min-h-[100px]">
            <?php if ($replies_result && $replies_result->num_rows > 0) { ?>
              <?php
              $replies_result->data_seek(0); // Reset pointer
              while ($reply = $replies_result->fetch_assoc()) {
                $name = htmlspecialchars($reply['replier_name'] ?: 'Unknown User');
                $role = htmlspecialchars($reply['reply_type']);

                if ($reply['replied_by'] == 'user') {
                  $initials = strtoupper(substr($name, 0, 2)) ?: 'CU';
                  $colorClass = 'bg-gray-200 text-gray-700';
                } elseif ($reply['replied_by'] == 'technician') {
                  $initials = strtoupper(substr($name, 0, 2)) ?: 'SA';
                  $colorClass = 'bg-brand-900 text-white';
                } else {
                  $name = 'System';
                  $initials = '⚙';
                  $colorClass = 'bg-gray-100 text-gray-600';
                }
              ?>
              <div class="flex items-start space-x-3">
                <div class="w-10 h-10 <?php echo $colorClass; ?> flex items-center justify-center text-sm font-semibold rounded-full flex-shrink-0">
                  <?php echo $initials; ?>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-baseline justify-between mb-1">
                    <div class="text-sm font-semibold text-gray-900 truncate">
                      <?php echo $name; ?>
                      <span class="text-xs text-gray-500 font-normal ml-1">• <?php echo $role; ?></span>
                    </div>
                    <div class="text-xs text-gray-500 flex-shrink-0 ml-2">
                      <?php echo htmlspecialchars($reply['created_at'] ? date('d M, H:i', strtotime($reply['created_at'])) : 'Unknown'); ?>
                    </div>
                  </div>
                  <div class="text-sm text-gray-700 leading-relaxed break-words">
                    <?php echo nl2br(htmlspecialchars($reply['reply_text'] ?: '')); ?>
                  </div>
                  <?php if (isset($reply['attachment_path']) && $reply['attachment_path']): ?>
                  <div class="mt-2">
                    <a href="<?php echo htmlspecialchars('../' . $reply['attachment_path']); ?>" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                      </svg>
                      View Attachment
                    </a>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php } ?>
            <?php } else { ?>
              <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Start the conversation</h3>
                <p class="text-gray-500 mb-4">Be the first to send a message. Share details, ask questions, or provide updates about this ticket.</p>
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-brand-50 text-brand-700 text-sm">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                  </svg>
                  Ready to send your first reply
                </div>
              </div>
            <?php } ?>
          </div>

          <!-- reply form -->
          <form id="replyForm" class="mt-5" onsubmit="return false;">
            <label class="sr-only" for="replyInput">Type your response</label>
            <textarea id="replyInput" name="reply" rows="3" placeholder="Type your response..." class="w-full resize-none border border-gray-200 rounded-md p-3 text-sm focus:ring-1 focus:ring-brand-700 focus:border-brand-700"></textarea>

            <div class="mt-3 flex items-center justify-between">
              <div class="flex items-center space-x-2 text-gray-400 text-sm">
                <input type="file" id="replyAttachment" name="replyAttachment" accept=".jpg,.jpeg,.png,.pdf" class="hidden">
                <button type="button" id="attachFileBtn" class="p-2 rounded hover:bg-gray-100" title="Attach file">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 9.4l-4.9 4.9a3 3 0 01-4.2 0 3 3 0 010-4.2l6-6a5 5 0 117.1 7.1l-6 6"/></svg>
                </button>
                <span id="fileName" class="text-xs text-gray-500 hidden"></span>
                <button type="button" class="p-2 rounded hover:bg-gray-100" title="Add emoji">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828A4 4 0 019.172 9.172m0 0A4 4 0 014.343 4.343M9.172 9.172L3 15.343"/></svg>
                </button>
              </div>

              <div class="flex items-center space-x-2">
                <button type="button" id="cancelReply" class="text-sm text-gray-500 px-3 py-1 rounded hover:bg-gray-100">Cancel</button>
                <button type="submit" class="bg-brand-700 hover:bg-brand-900 text-white px-4 py-1.5 rounded text-sm">Send Reply</button>
              </div>
            </div>
          </form>
        </div>

        <!-- Escalate Ticket -->
      <!--  <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
          <h3 class="font-medium text-gray-800 mb-4">Escalate Ticket</h3>

          <form id="escalateForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1">
              <label class="block text-xs text-gray-600 mb-1">Reason <span class="text-red-500">*</span></label>
              <input required id="escalateReason" class="w-full border border-gray-200 rounded-md p-2 text-sm" placeholder="Brief reason" />
            </div>

            <div class="md:col-span-1">
              <label class="block text-xs text-gray-600 mb-1">Escalate to</label>
              <select id="escalateTo" class="w-full border border-gray-200 rounded-md p-2 text-sm">
                <option>Tier 2 Support</option>
                <option>Engineering</option>
                <option>Manager</option>
                <option selected>Security Team</option>
              </select>
            </div>

            <div class="md:col-span-1">
              <label class="block text-xs text-gray-600 mb-1">Your action note</label>
              <input id="escalateNote" class="w-full border border-gray-200 rounded-md p-2 text-sm" placeholder="Write your action note here..." />
            </div>

            <div class="md:col-span-3 text-right mt-2">
              <button type="submit" class="bg-white border border-gray-200 hover:bg-gray-50 text-sm px-4 py-2 rounded">Submit</button>
            </div>
          </form>
        </div> -->

      </section>

      <!-- Sidebar (right) -->
      <aside class="lg:col-span-4 space-y-6">
        <!-- Customer Info -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
          <h4 class="text-base font-medium text-gray-800 mb-3">Customer Information</h4>
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center font-semibold"><?php echo $ticket['customer_name'] ? strtoupper(substr($ticket['customer_name'], 0, 2)) : 'UN'; ?></div>
            <div>
              <div class="font-semibold"><?php echo htmlspecialchars($ticket['customer_name'] ?: 'Unknown Customer'); ?></div>
              <div class="text-xs text-gray-500"><?php echo htmlspecialchars($ticket['customer_company'] ?: 'N/A'); ?></div>
            </div>
          </div>

          <div class="mt-4 text-sm text-gray-700 space-y-2">
            <div><span class="text-xs text-gray-500">Email</span><div class="font-medium"><?php echo htmlspecialchars($ticket['customer_email'] ?: 'N/A'); ?></div></div>
            <div><span class="text-xs text-gray-500">Phone</span><div class="font-medium">09123456789</div></div>
          </div>
        </div>

        <!-- Ticket Information -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
          <h4 class="text-base font-medium text-gray-800 mb-3">Ticket Information</h4>
          <div class="text-sm text-gray-700 space-y-3">
            <div><span class="text-xs text-gray-500">Assigned To</span><div class="font-medium"><?php echo htmlspecialchars($ticket['technician_name'] ?: 'Unassigned'); ?></div></div>
            <div><span class="text-xs text-gray-500">Department</span><div class="font-medium"><?php echo htmlspecialchars($ticket['department_name'] ?: 'N/A'); ?></div></div>
            <div><span class="text-xs text-gray-500">Source</span><div class="font-medium"><?php echo htmlspecialchars($ticket['type'] ?: 'IT'); ?></div></div>
            <div>
              <span class="text-xs text-gray-500">Last Updated</span>
              <div class="font-medium">
                <?php
                  $lastUpdatedRaw = $ticket['updated_at'] ?? $ticket['created_at'] ?? null;
                  echo htmlspecialchars($lastUpdatedRaw ? date('d F, Y \a\t g:i A', strtotime($lastUpdatedRaw)) : 'Unknown');
                ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Logs -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
          <h4 class="text-base font-medium text-gray-800 mb-3">Logs</h4>

          <div id="logs" class="space-y-4 text-sm text-gray-700">
            <!-- Logs will be loaded dynamically -->
          </div>
        </div>

        <!-- Quick actions -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm flex items-center justify-between">
          <div class="text-sm text-gray-700">Status: <span id="statusLabel" class="font-medium text-brand-700 ml-1"><?php echo htmlspecialchars(ucfirst($ticket['status'])); ?></span></div>
          <div>
            <button id="toggleResolveSmall" class="text-sm bg-gray-50 px-3 py-1 rounded border border-gray-200"><?php echo htmlspecialchars($ticket['status'] === 'complete' ? 'Reopen' : 'Mark Complete'); ?></button>
          </div>
        </div>

      </aside>
    </div>
  </main>

  <!-- Scripts -->
  <script>
    // Basic interactivity: sending replies, toggling resolve, escalate logging
    const replyForm = document.getElementById('replyForm');
    const replyInput = document.getElementById('replyInput');
    const conversation = document.getElementById('conversation');
    const logs = document.getElementById('logs');
    const escalateForm = document.getElementById('escalateForm');
    const quickResolve = document.getElementById('quickResolve');
    const toggleResolveSmall = document.getElementById('toggleResolveSmall');
    const statusLabel = document.getElementById('statusLabel');
    const ticketRef = '<?php echo htmlspecialchars($ticket_ref, ENT_QUOTES); ?>';

    let isCompleted = '<?php echo $ticket['status']; ?>' === 'complete';

    function appendMessage(authorInitials, authorName, role, text, attachmentPath = null) {
      // Remove empty state if it exists
      const emptyState = conversation.querySelector('.text-center.py-8');
      if (emptyState) {
        emptyState.remove();
      }

      const wrapper = document.createElement('div');
      wrapper.className = 'flex items-start space-x-3';

      // Format timestamp
      const now = new Date();
      const timeString = now.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
      }) + ', ' + now.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      });

      // Determine color class based on role
      let colorClass = 'bg-gray-200 text-gray-700';
      if (role === 'Support Agent' || role === 'Technician') {
        colorClass = 'bg-blue-900 text-white';
      }

      let attachmentHtml = '';
      if (attachmentPath) {
        attachmentHtml = `
          <div class="mt-2">
            <a href="../${escapeHtml(attachmentPath)}" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
              </svg>
              View Attachment
            </a>
          </div>
        `;
      }

      wrapper.innerHTML = `
        <div class="w-10 h-10 ${colorClass} rounded-full flex items-center justify-center text-sm font-semibold flex-shrink-0">
          ${escapeHtml(authorInitials)}
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-baseline justify-between mb-1">
            <div class="text-sm font-semibold text-gray-900 truncate">
              ${escapeHtml(authorName)}
              <span class="text-xs text-gray-500 font-normal ml-1">• ${escapeHtml(role)}</span>
            </div>
            <div class="text-xs text-gray-500 flex-shrink-0 ml-2">
              ${escapeHtml(timeString)}
            </div>
          </div>
          <div class="text-sm text-gray-700 leading-relaxed break-words">
            ${escapeHtml(text).replace(/\n/g, '<br>')}
          </div>
          ${attachmentHtml}
        </div>
      `;

      conversation.appendChild(wrapper);
      // Smooth scroll to the new message
      wrapper.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }

    function appendLog(name, message, tag, tagColorClass = 'bg-blue-100 text-blue-800') {
      const entry = document.createElement('div');
      entry.className = 'flex items-start space-x-3';
      entry.innerHTML = `
        <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-sm">${escapeHtml(name.split(' ').map(n => n[0]).slice(0, 2).join(''))}</div>
        <div class="flex-1">
          <div class="flex items-center justify-between">
            <div class="font-medium">${escapeHtml(name)}</div>
            <div class="text-xs text-gray-400">${new Date().toLocaleString()}</div>
          </div>
          <div class="text-gray-600 mt-1">${escapeHtml(message)}</div>
          <div class="mt-2"><span class="inline-block text-xs ${tagColorClass} px-2 py-0.5 rounded">${escapeHtml(tag)}</span></div>
        </div>
      `;
      logs.insertBefore(entry, logs.children[logs.children.length - 1]); // insert before the System item
    }

    // Function to load logs from database
    function loadLogs() {
      fetch(`../php/get_logs.php?ref=${ticketRef}`)
        .then(response => response.json())
        .then(data => {
          if (data.ok) {
            logs.innerHTML = ''; // Clear existing logs
            if (data.data.logs.length === 0) {
              logs.innerHTML = '<div class="text-gray-500 text-center py-4">No activity logs yet</div>';
              return;
            }

            // Create logs in reverse order (newest first in DB, display newest at top)
            data.data.logs.reverse().forEach(log => {
              const entry = document.createElement('div');
              entry.className = 'flex items-start space-x-3';

              // Get initials
              const initials = log.user_name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();

              // Choose avatar color based on role
              const avatarColor = log.user_role === 'technician' ? 'bg-brand-900 text-white' : 'bg-gray-200';

              // Format timestamp
              const date = new Date(log.created_at);
              const timeString = date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
              }) + ' at ' + date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
              });

              // Choose tag color based on action type
              let tagColor = 'bg-blue-100 text-blue-800';
              switch (log.action_type) {
                case 'reply':
                  tagColor = 'bg-green-100 text-green-800';
                  break;
                case 'escalate':
                  tagColor = 'bg-red-100 text-red-800';
                  break;
                case 'complete':
                case 'reopen':
                  tagColor = 'bg-purple-100 text-purple-800';
                  break;
                case 'view':
                  tagColor = 'bg-gray-100 text-gray-800';
                  break;
              }

              entry.innerHTML = `
                <div class="w-9 h-9 ${avatarColor} flex items-center justify-center text-sm font-semibold rounded-full flex-shrink-0">
                  ${escapeHtml(initials)}
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-1">
                    <div class="text-sm font-semibold text-gray-900">
                      ${escapeHtml(log.user_name)}
                      <span class="text-xs text-gray-500 font-normal ml-1">• ${escapeHtml(log.user_role)}</span>
                    </div>
                    <div class="text-xs text-gray-500 flex-shrink-0">
                      ${escapeHtml(timeString)}
                    </div>
                  </div>
                  <div class="text-sm text-gray-700 leading-relaxed">
                    ${escapeHtml(log.action_details)}
                  </div>
                  <div class="mt-2">
                    <span class="inline-block text-xs ${tagColor} px-2 py-0.5 rounded capitalize">
                      ${escapeHtml(log.action_type)}
                    </span>
                  </div>
                </div>
              `;

              logs.appendChild(entry);
            });
          } else {
            logs.innerHTML = '<div class="text-gray-500 text-center py-4">Error loading logs</div>';
          }
        })
        .catch(error => {
          console.error('Error loading logs:', error);
          logs.innerHTML = '<div class="text-gray-500 text-center py-4">Error loading logs</div>';
        });
    }

    function escapeHtml(text) {
      if (typeof text !== 'string') return text;
      return text
        .replace(/&/g, '&')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/"/g, '"')
        .replace(/'/g, '&#039;');
    }

    function showToast(message, type = 'info') {
      // Remove existing toasts
      const existingToasts = document.querySelectorAll('.toast');
      existingToasts.forEach(toast => toast.remove());

      // Create toast element
      const toast = document.createElement('div');
      toast.className = `toast fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full`;

      // Set color based on type
      switch (type) {
        case 'success':
          toast.classList.add('bg-green-500', 'text-white');
          break;
        case 'error':
          toast.classList.add('bg-red-500', 'text-white');
          break;
        case 'warning':
          toast.classList.add('bg-yellow-500', 'text-white');
          break;
        default:
          toast.classList.add('bg-blue-500', 'text-white');
      }

      toast.textContent = message;

      // Add to page
      document.body.appendChild(toast);

      // Animate in
      setTimeout(() => {
        toast.classList.remove('translate-x-full');
      }, 10);

      // Auto remove after 3 seconds
      setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
          if (toast.parentNode) {
            toast.remove();
          }
        }, 300);
      }, 3000);
    }

    if (replyForm) {
      replyForm.addEventListener('submit', (e) => {
      // #region agent log
      fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'cust_ticket.php:632',message:'Reply form submit event',data:{ticketRef,url:window.location.href,hasRef:!!ticketRef},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
      // #endregion
      e.preventDefault();
      const text = replyInput.value.trim();
      const attachmentFile = document.getElementById('replyAttachment').files[0];

      if (!text && !attachmentFile) {
        showToast('Please enter a reply or attach a file.', 'warning');
        return;
      }
      if (!ticketRef) {
        showToast('Error: Missing ticket reference', 'error');
        return;
      }

      // Disable form while submitting
      const submitBtn = replyForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Sending...';
      submitBtn.disabled = true;

      // Send reply via AJAX
      const formData = new FormData();
      formData.append('ref', ticketRef);
      formData.append('reply', text);
      if (attachmentFile) {
        formData.append('replyAttachment', attachmentFile);
      }

      // #region agent log
      fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'cust_ticket.php:660',message:'Before fetch post_reply',data:{ticketRef,textLength:text.length,hasAttachment:!!attachmentFile},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
      // #endregion
      fetch('../php/post_reply.php', {
        method: 'POST',
        body: formData
      })
      .then(async response => {
        // #region agent log
        fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'cust_ticket.php:664',message:'Response received',data:{status:response.status,statusText:response.statusText,ok:response.ok},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
        // #endregion
        let data;
        try {
          data = await response.json();
          // #region agent log
          fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'cust_ticket.php:667',message:'Response parsed',data:{ok:data?.ok,hasError:!!data?.error,error:data?.error?.substring(0,100)},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
          // #endregion
        } catch (e) {
          // #region agent log
          fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'cust_ticket.php:669',message:'JSON parse error',data:{error:e.message,status:response.status},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
          // #endregion
          // If response.json() fails, create a fallback error object
          data = {
            ok: false,
            error: 'Server error: ' + response.status + ' ' + response.statusText
          };
        }

        if (!response.ok) {
          throw new Error(data.error || 'HTTP error! status: ' + response.status);
        }

        return data;
      })
      .then(data => {
        console.log('Reply response:', data);
        // #region agent log
        fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'cust_ticket.php:682',message:'Success handler',data:{ok:data?.ok,hasReply:!!data?.reply,currentUrl:window.location.href},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
        // #endregion
        if (data.ok && data.reply) {
          // Clear the input
          replyInput.value = '';
          document.getElementById('replyAttachment').value = '';
          document.getElementById('fileName').classList.add('hidden');
          document.getElementById('fileName').textContent = '';

          // Add the new reply to the conversation immediately
          const replyData = data.reply;
          const userInitials = replyData.sender.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
          appendMessage(userInitials, replyData.sender, 'Customer', replyData.message, replyData.attachment_path);

          // Show success feedback
          showToast('Reply sent successfully!', 'success');
        } else {
          showToast('Error sending reply: ' + (data.error || 'Unknown error'), 'error');
        }
      })
      .catch(error => {
        // #region agent log
        fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'cust_ticket.php:702',message:'Reply error caught',data:{error:error.message,currentUrl:window.location.href},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
        // #endregion
        console.error('Reply error:', error);
        showToast('Failed to send reply: ' + error.message, 'error');
      })
      .finally(() => {
        // Re-enable form
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      });
    });
    }

    const cancelReplyBtn = document.getElementById('cancelReply');
    if (cancelReplyBtn) {
      cancelReplyBtn.addEventListener('click', () => {
        replyInput.value = '';
        document.getElementById('replyAttachment').value = '';
        document.getElementById('fileName').classList.add('hidden');
        document.getElementById('fileName').textContent = '';
      });
    }

    // Handle file attachment
    const attachFileBtn = document.getElementById('attachFileBtn');
    if (attachFileBtn) {
      attachFileBtn.addEventListener('click', () => {
        document.getElementById('replyAttachment').click();
      });
    }

    const replyAttachment = document.getElementById('replyAttachment');
    if (replyAttachment) {
      replyAttachment.addEventListener('change', (e) => {
      const file = e.target.files[0];
      const fileNameSpan = document.getElementById('fileName');
      if (file) {
        fileNameSpan.textContent = file.name;
        fileNameSpan.classList.remove('hidden');
      } else {
        fileNameSpan.classList.add('hidden');
        fileNameSpan.textContent = '';
      }
    });
    }

    if (escalateForm) {
      escalateForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const reason = document.getElementById('escalateReason').value.trim();
      const to = document.getElementById('escalateTo').value;
      const note = document.getElementById('escalateNote').value.trim();

      if (!reason) {
        showToast('Please provide an escalation reason.', 'warning');
        return;
      }

      // Disable form while submitting
      const submitBtn = escalateForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Escalating...';
      submitBtn.disabled = true;

      // Map escalation targets to department IDs (you may need to adjust these IDs based on your department table)
      const departmentMapping = {
        'Tier 2 Support': 4,    // IT Department
        'Engineering': 5,       // Engineering
        'Manager': 6,           // Management
        'Security Team': 7      // Security
      };

      const newDepartmentId = departmentMapping[to] || 4; // Default to IT

      // Send escalation request
      fetch('../php/escalate_ticket.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          ref: ticketRef,
          reason: reason,
          new_department_id: newDepartmentId,
          note: note
        })
      })
      .then(async response => {
        let data;
        try {
          data = await response.json();
        } catch (e) {
          data = {
            ok: false,
            error: 'Server error: ' + response.status + ' ' + response.statusText
          };
        }

        if (!response.ok) {
          throw new Error(data.error || 'HTTP error! status: ' + response.status);
        }

        return data;
      })
      .then(data => {
        console.log('Escalation response:', data);
        if (data.ok) {
          // Add log entry for successful escalation
          const message = `Escalated to ${to}. Reason: ${reason}${note ? ' — Note: ' + note : ''}`;
          appendLog('You', message, 'Escalated', 'bg-red-100 text-red-800');

          // Clear fields
          document.getElementById('escalateReason').value = '';
          document.getElementById('escalateNote').value = '';

          // Show success feedback
          showToast('Ticket escalated successfully!', 'success');
        } else {
          showToast('Error escalating ticket: ' + (data.error || 'Unknown error'), 'error');
        }
      })
      .catch(error => {
        console.error('Escalation error:', error);
        showToast('Failed to escalate ticket: ' + error.message, 'error');
      })
      .finally(() => {
        // Re-enable form
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      });
    });
    }

    function toggleResolve() {
      // Toggle complete/reopen via AJAX
      const action = isCompleted ? 'reopen' : 'complete';
      if (confirm(`Are you sure you want to ${action} this ticket?`)) {
        fetch('../php/resolve_ticket.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            ref: ticketRef
          })
        })
        .then(response => {
          if (!response.ok) {
            return response.json().then(err => {
              throw new Error(err.error || 'HTTP error! status: ' + response.status);
            });
          }
          return response.json();
        })
        .then(data => {
          if (data.ok) {
            location.reload(); // Reload to update status
          } else {
            showToast('Error ' + (isCompleted ? 'reopening' : 'completing') + ' ticket: ' + (data.error || 'Unknown error'), 'error');
          }
        })
        .catch(error => {
          console.error('Resolve error:', error);
          showToast('An error occurred while updating the ticket.', 'error');
        });
      }
    }

    if (quickResolve) {
      quickResolve.addEventListener('click', toggleResolve);
    }
    if (toggleResolveSmall) {
      toggleResolveSmall.addEventListener('click', toggleResolve);
    }

    // Keyboard shortcut Ctrl+Enter to send reply
    if (replyInput && replyForm) {
      replyInput.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
          e.preventDefault();
          replyForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
      });
    }

    // Load logs when page loads
    if (ticketRef) {
      loadLogs();
    }

  </script>
  <?php endif; ?>
      </body>
</Html>
