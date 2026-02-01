<?php
include("../php/db.php");
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../views/login.php");
    exit();
}

$user_role = $_SESSION['role'];
$user_id = $_SESSION['id'];

$reference_id = $_GET['ref'] ?? '';
if (!$reference_id) die("No reference ID provided.");

$sql = "SELECT 
            t.*, 
            u.name AS requester_name, 
            u.email AS requester_email,
            te.name AS technician_name
        FROM tbl_ticket t
        LEFT JOIN tbl_user u ON t.user_id = u.user_id
        LEFT JOIN tbl_technician te ON t.assigned_technician_id = te.technician_id
        WHERE t.reference_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $reference_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();
$stmt->close();

if (!$ticket) die("Ticket not found.");

$now = new DateTime();
$sla_date = new DateTime($ticket['sla_date']);
$diff = $now->diff($sla_date);
$sla_text = $sla_date->format("F j, Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ticket <?= htmlspecialchars($ticket['reference_id']) ?></title>
    <link rel="stylesheet" href="../css/theme.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/ticket_monitor.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="../js/ui-enhancements.js" defer></script>
    <script src="../js/animations.js" defer></script>
    <script src="../js/ticket-interactions.js" defer></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <script>
        window.API_BASE = "../php/";
        window.TICKET_REF = "<?php echo htmlspecialchars($_GET['ref'] ?? ''); ?>";
    </script>


    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/ticket_reply_monitor.js" defer></script>
    <!-- <script src="../js/ticket_escalation.js" defer></script> -->
    <script src="../js/ticket_logs_monitor.js" defer></script>
</head>
<body class="bg-gray-50 min-h-screen page-transition">
<?php include("../includes/navbar.php"); ?>
    <div class="max-w-7xl mx-auto py-10 px-6 grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- LEFT SECTION -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Ticket Header -->
            <div>
                <p class="text-sm text-gray-500">Ticket #<?= htmlspecialchars($ticket['reference_id']) ?></p>
                <h1 class="text-3xl font-semibold text-gray-800"><?= htmlspecialchars($ticket['title']) ?></h1>
                <p class="text-sm text-gray-400">Created <?= date('F j, Y', strtotime($ticket['created_at'])) ?></p>
            </div>

            <!-- Ticket Details -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Ticket Details</h2>
                <p class="text-gray-700 whitespace-pre-line mb-4 italic"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>

                <?php if (!empty($ticket['attachments'])): ?>
                    <div class="mb-4">
                        <p class="font-medium text-gray-700">Attachment:</p>
                        <a href="<?= htmlspecialchars($ticket['attachments']) ?>" target="_blank" class="text-blue-600 hover:underline">
                            View / Download
                        </a>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <?php $label = ($user_role === 'user') ? 'Urgency' : 'Priority'; ?>
                        <p class="text-xl text-gray-500"><?= $label ?></p>
                        <p class="px-2 py-1 inline-block rounded text-sm
                            <?php
                            if ($user_role === 'user') {
                                // For customers, show urgency as Normal/Urgent
                                $display = $ticket['urgency'] === 'urgent' ? 'Urgent' : 'Normal';
                                $class = $ticket['urgency'] === 'urgent' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
                            } else {
                                // For internal users, show actual priority level
                                $level = $ticket['priority'];
                                $display = match(strtolower($level)) {
                                    'critical' => 'Urgent',
                                    'regular' => 'Medium',
                                    default => ucfirst($level)
                                };
                                $class = match(strtolower($level)) {
                                    'critical' => 'bg-red-100 text-red-700',
                                    'high' => 'bg-orange-100 text-orange-700',
                                    'regular' => 'bg-blue-100 text-blue-700',
                                    default => 'bg-green-100 text-green-700'
                                };
                            }
                            echo $class;
                            ?>">
                            <?= htmlspecialchars($display) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xl text-gray-500">Category</p>
                        <p class="text-gray-700"><?= htmlspecialchars($ticket['category']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Replies Section -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Conversation</h2>

                <div id="repliesContainer" class="text-lg space-y-4 mb-6">
                    <!-- Replies dynamically loaded by ticket_reply.js -->
                </div>

                <div class="flex flex-col mt-4">
                  <textarea id="replyText" placeholder="Type your response..." rows="3" class="w-full border rounded px-3 py-2 resize-none" style="min-height: 60px;"></textarea>
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-2 gap-2">
                      <input type="file"id="replyAttachment" name="reply_attachment" class="text-sm text-gray-600"/>
                      <button id="send-reply-button" class="bg-blue-900 text-white px-4 py-2 rounded w-full sm:w-auto">Send Reply</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SECTION -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Ticket Info -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Ticket Information</h2>
                <div class="space-y-2 text-lg">
                    <p><strong>Reference ID:</strong> <?= htmlspecialchars($ticket['reference_id']) ?></p>
                    <p><strong>Requester:</strong> <?= htmlspecialchars($ticket['requester_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($ticket['requester_email']) ?></p>
                    <br>
                    <p><strong>Status:</strong> 
                        <span class="px-2 py-1 rounded text-sm <?= $ticket['status'] === 'resolved' ? 'bg-green-100 text-green-700' :
                            ($ticket['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700'); ?>">
                            <?= htmlspecialchars(ucfirst($ticket['status'])) ?>
                        </span>
                    </p>
                    <p><strong>SLA:</strong> <?= htmlspecialchars($sla_text) ?></p>
                    <p><strong>Assigned Technician:</strong> <?= htmlspecialchars($ticket['technician_name'] ?? 'Unassigned') ?></p>
                    <p><strong>Last Updated:</strong> <?= htmlspecialchars(date('F j, Y', strtotime($ticket['updated_at'] ?? $ticket['created_at']))) ?></p>

                </div>
                <!-- Actions based on user role -->
                <?php if ($user_role !== 'user' && !in_array(strtolower($ticket['status']), ['complete', 'resolved'])): ?>
                 <div class="mt-4 flex flex-col sm:flex-row gap-2">
                  <?php if ($user_role === 'evaluator' || $user_role === 'admin'): ?>
                  <button id="edit-ticket-btn"
                    class="bg-blue-900 hover:bg-blue-600 text-white px-4 py-2 rounded-lg w-full sm:w-auto">
                    Edit Ticket
                  </button>
                  <?php endif; ?>
                  <button id="resolve-ticket-btn"
                    class="bg-blue-900 hover:bg-blue-700 text-white px-4 py-2 rounded-lg w-full sm:w-auto" id="ticketInfo" data-status="<?= htmlspecialchars(strtolower($ticket['status'])) ?>">
                    Mark as Resolved
                  </button>
                  <button id="escalate-ticket-btn"
                     class="mt-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg w-full sm:w-auto">
                    Escalate Ticket
                    </button>
                 </div>
                <?php endif; ?>

                <!-- Edit Modal -->
                <div id="editTicketModal"
                  class="fixed inset-0 hidden bg-black bg-opacity-40 flex items-center justify-center z-50">
                  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                    <h2 class="text-xl font-semibold mb-3 text-gray-800">Edit Ticket</h2>

                    <label class="block text-sm text-gray-600 mb-1">Priority</label>
                    <select id="editPriority" class="w-full border rounded p-2 mb-3">
                      <option value="low">Low</option>
                      <option value="regular">Medium</option>
                      <option value="high">High</option>
                      <option value="critical">Urgent</option>
                    </select>

                    <label class="block text-sm text-gray-600 mb-1">SLA Deadline</label>
                    <input type="date" id="editSLA" class="w-full border rounded p-2 mb-3">

                    <div class="flex justify-end gap-2">
                      <button id="cancelEditBtn" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                      <button id="saveEditBtn" class="px-4 py-2 bg-blue-700 text-white rounded">Save Changes</button>
                    </div>
                  </div>
                </div>
            </div>

            <!-- Escalation Modal -->
            <div id="escalationModal" class="fixed inset-0 hidden bg-black bg-opacity-50 flex items-center justify-center z-50">
              <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">
      Escalate / Reassign Ticket
    </h2>

    <!-- REASON -->
    <div class="mb-4">
      <label class="block text-gray-700 text-sm font-bold mb-1">Reason</label>
      <textarea id="escalationReason" rows="3"
        class="w-full border border-gray-300 rounded-md p-3"
        placeholder="Provide reason for escalation">
      </textarea>
    </div>

    <!-- PRIORITY -->
    <div class="mb-4">
      <label class="block text-gray-700 text-sm font-bold mb-2">Priority</label>
      <select id="prioritySelect" name="priority"
        class="w-full px-3 py-2 border border-gray-300 rounded-md">
        <option value="">Select Priority</option>
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
    </div>

    <!-- DEPARTMENT -->
    <div class="mb-4">
      <label class="block text-gray-700 text-sm font-bold mb-2">Department</label>
      <select id="departmentSelect" 
        class="w-full px-3 py-2 border border-gray-300 rounded-md">
        <option value="">Select Department</option>
      </select>
    </div>

    <!-- TECHNICIAN -->
    <div class="mb-4">
      <label class="block text-gray-700 text-sm font-bold mb-2">Assign to Technician</label>
      <select id="technicianSelect"
        class="w-full px-3 py-2 border border-gray-300 rounded-md">
        <option value="">Select Technician</option>
      </select>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="flex justify-end gap-2 mt-6">
      <button id="cancelEscalationBtn" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
      <button id="confirmEscalationBtn" class="px-4 py-2 bg-blue-600 text-white rounded">
        Save Changes
      </button>
    </div>

              </div>
            </div>
            <!-- Logs / Escalation -->
            <?php if ($user_role !== 'customer'): ?>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <button onclick="toggleSection('logsSection')" 
                        class="w-full text-left font-medium text-gray-700 hover:text-gray-900 flex justify-between">
                    Logs
                    <span class="text-sm text-gray-500">▼</span>
                </button>
                <div id="logsSection" class="hidden mt-3 border-t pt-3">
                    <ul id="logsContainer" class="space-y-2 text-sm text-gray-600"></ul>
                </div>
            </div>

            <!-- Internal Comments & Checklist -->
            <div class="bg-white p-6 rounded-lg shadow-sm mt-6">
              <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Comments</h2>

              <div id="commentsContainer" class="space-y-3 mt-4">
                <!-- Filled by JS -->
              </div>
              <?php if (!in_array(strtolower($ticket['status']), ['complete', 'resolved'])): ?>
              <textarea id="newComment" class="w-full border rounded p-2 mt-3 text-sm" placeholder="Write a comment..."></textarea>
              <button id="addCommentBtn" class="mt-2 bg-blue-900 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">Post Comment</button>
              <?php endif; ?>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm mt-6">
              <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Checklist</h2>
              <!-- Progress Bar -->
              <div id="checklistProgressContainer" class="mt-4 mb-4">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700">Progress</span>
                  <span id="checklistProgressText" class="text-sm text-gray-600">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                  <div id="checklistProgressBar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
              </div>
              <div id="checklistContainer" class="space-y-3 mt-4"></div>

              <div class="flex gap-2 mt-3">
                <?php if (!in_array(strtolower($ticket['status']), ['complete', 'resolved'])): ?>
                <input id="newChecklist" class="border rounded p-2 flex-grow text-sm" placeholder="New checklist item">
                <button id="addChecklistBtn" class="ml-2 bg-blue-900 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">Add</button>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
        </div> 
    </div>

    <script>
      function loadDepartments() {
    fetch("../php/get_departments.php")
    .then(r => r.json())
    .then(list => {
        const sel = document.getElementById("departmentSelect");
        sel.innerHTML = '<option value="">Select Department</option>';

        list.forEach(d => {
            sel.innerHTML += `<option value="${d.department_id}">${d.department_name}</option>`;
        });
    });
}
document.addEventListener("DOMContentLoaded", () => {

    const openBtn = document.getElementById("escalate-ticket-btn");
    const deptSelect = document.getElementById("departmentSelect");
    const techSelect = document.getElementById("technicianSelect");

    if (!openBtn || !deptSelect || !techSelect) {
        console.error("Escalation modal elements missing.");
        return;
    }

    // OPEN MODAL + LOAD DEPARTMENTS
    openBtn.addEventListener("click", () => {
        loadDepartments();
        document.getElementById("escalationModal").classList.remove("hidden");
        document.getElementById("escalationModal").classList.add("flex");
    });

    // WHEN DEPARTMENT CHANGES → LOAD TECHNICIANS
    deptSelect.addEventListener("change", () => {
        const dept = deptSelect.value;
        if (!dept) return;

        fetch("../php/get_technicians.php?dept=" + dept)
        .then(r => r.json())
        .then(list => {
            techSelect.innerHTML = `<option value="">Select Technician</option>`;
            list.forEach(t => {
            techSelect.innerHTML += `
                <option value="${t.technician_id}">
                    ${t.name} (Active: ${t.active_tickets})
                </option>`;
            });
        });
    });

});



        function toggleSection(id) {
            const section = document.getElementById(id);
            section.classList.toggle('hidden');
        }
        
        async function loadComments() {
          const ref = window.TICKET_REF;

          const res = await fetch(`../php/get_comments.php?ref=${ref}`);
          const data = await res.json();

          const container = document.getElementById("commentsContainer");
          container.innerHTML = "";

          data.forEach(c => {
            const idLabel = c.is_technician == 1
              ? `Technician ID #${c.commenter_id}`
              : `User ID #${c.commenter_id}`;

            container.innerHTML += `
              <div class="border-b pb-2">
                  <p class="text-xs text-gray-500">${idLabel} — ${c.created_at}</p>
                  <p class="text-gray-700 mt-1">${c.comment_text}</p>
              </div>
            `;
          });
        }
        async function loadChecklist() {
          const ref = window.TICKET_REF;

          const res = await fetch(`../php/get_checklist.php?ref=${ref}`);
          const data = await res.json();

          const container = document.getElementById("checklistContainer");
          container.innerHTML = "";

          data.forEach(item => {
            const idLabel = item.is_technician == 1
              ? `Technician ID #${item.created_by}`
              : `User ID #${item.created_by}`;

              container.innerHTML += `
                <div class="flex items-start space-x-2 pb-2 border-b">
                  <input type="checkbox" data-id="${item.item_id}" ${item.is_completed == 1 ? "checked" : ""} class="chkItem mt-1">
                  <div>
                    <p class="${item.is_completed == 1 ? 'line-through text-gray-400' : 'text-gray-700'}">${item.description}</p>
                    <p class="text-xs text-gray-500">${idLabel} — ${item.created_at}</p>
                  </div>
              </div>
            `;
          });

          document.querySelectorAll(".chkItem").forEach(chk => {
            chk.addEventListener("change", async () => {
              await fetch("../php/toggle_checklist_item.php", {
                  method: "POST",
                  body: new URLSearchParams({
                      item_id: chk.dataset.id,
                      completed: chk.checked ? 1 : 0
                  })
              });
              loadChecklist();
            });
          });
        }

        // Edit & Resolve Ticket 
        document.addEventListener("DOMContentLoaded", () => {
            const ref = new URLSearchParams(window.location.search).get("ref");
            if (!ref) return;
  
            // Hide Edit/Resolve buttons if ticket is already complete/resolved
            const ticketInfo = document.getElementById("ticketInfo");
            const statusText = ticketInfo?.dataset.status || "";

            const editBtn = document.getElementById("edit-ticket-btn");
            const resolveBtn = document.getElementById("resolve-ticket-btn");

            console.log("Ticket status:", statusText); // Debugging line — can be removed later

            if (statusText === "complete" || statusText === "resolved") {
                if (editBtn) editBtn.classList.add("hidden");
                if (resolveBtn) resolveBtn.classList.add("hidden");
            }

            const modal = document.getElementById("editTicketModal");
            const cancelEditBtn = document.getElementById("cancelEditBtn");
            const saveEditBtn = document.getElementById("saveEditBtn");
            const priorityInput = document.getElementById("editPriority");
            const slaInput = document.getElementById("editSLA");

            // Open Edit Modal
            editBtn?.addEventListener("click", () => {
                modal.classList.remove("hidden");
                modal.classList.add("flex");
              });

            // Close Modal
            cancelEditBtn?.addEventListener("click", () => {
                modal.classList.add("hidden");
                modal.classList.remove("flex");
            });

            // Save Ticket Changes
            saveEditBtn?.addEventListener("click", async () => {
                const newPriority = priorityInput.value;
                const newSLA = slaInput.value;

                if (!newPriority || !newSLA) {
                    alert("Please fill in all fields before saving.");
                    return;
                }

                try {
    
                    const response = await fetch("../php/update_ticket_monitor.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams({
                        ref,
                        priority: newPriority,
                        sla_date: newSLA,
                        action_type: "edit",
                        action_details: `Ticket updated: Priority set to ${newPriority}, SLA changed to ${newSLA}`,
                        }),
                    });


                    const data = await response.json();
                    if (data.ok) {
                        modal.classList.add("hidden");

                        location.reload();
                        alert("Ticket updated successfully.");
                    } else {
                        alert("Failed to update ticket: " + (data.error || "Unknown error"));
                    }
                } catch (err) {
                    console.error("Error updating ticket:", err);
                    alert("An error occurred while saving changes.");
                }
            });

            // Resolve Ticket
            resolveBtn?.addEventListener("click", async () => {
            if (!confirm("Mark this ticket as resolved?")) return;

            try {
            const response = await fetch("../php/resolve_ticket.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ ref }),
            });

            const data = await response.json();
            if (data.ok) {
                alert("Ticket marked as resolved.");
                location.reload();
            } else {
                alert("Failed to resolve ticket: " + (data.error || "Unknown error"));
            }
            } catch (err) {
                console.error("Error resolving ticket:", err);
                alert("An error occurred while resolving the ticket.");
            }
            });
        });
    </script>
    <script>
document.addEventListener("DOMContentLoaded", () => {
  TicketLogs.init(window.TICKET_REF); // This is for logs that I forgot
  const ref = new URLSearchParams(window.location.search).get("ref");
  if (!ref) return;

  // Elements
  const escalateBtn = document.getElementById("escalate-ticket-btn");
  const modal = document.getElementById("escalationModal");
  const cancelBtn = document.getElementById("cancelEscalationBtn");
  const confirmBtn = document.getElementById("confirmEscalationBtn");
  const reasonInput = document.getElementById("escalationReason");

  // Verify all elements exist before adding listeners
  if (!escalateBtn || !modal || !cancelBtn || !confirmBtn) {
    console.error("Escalation elements not found in DOM.");
    return;
  }

  // Open Modal
  escalateBtn.addEventListener("click", (e) => {
    e.preventDefault();
    modal.classList.remove("hidden");
    modal.classList.add("flex");
  });

  // Cancel Button
  cancelBtn.addEventListener("click", (e) => {
    e.preventDefault();
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    reasonInput.value = ""; // Clear input
  });

  // Confirm Escalation
  // Confirm Escalation
confirmBtn.addEventListener("click", async (e) => {
    e.preventDefault();

    const priority = document.getElementById("prioritySelect").value;
    const reason = reasonInput.value.trim();
    const department_id = document.getElementById("departmentSelect").value;
    const technician_id = document.getElementById("technicianSelect").value;

    if (!reason) {
        alert("Please provide a reason for escalation.");
        return;
    }
    if (!department_id) {
        alert("Please select a department.");
        return;
    }
    if (!technician_id) {
        alert("Please select a technician.");
        return;
    }

    try {
        const response = await fetch("../php/escalate_ticket.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                ref,
                reason,
                department_id,
                technician_id,
                priority
            }),
        });

        const text = await response.text();
        console.log("Raw escalation response:", text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (err) {
            console.error("Invalid JSON:", text);
            alert("Unexpected server response. Check console for details.");
            return;
        }

        if (data.ok) {
            alert("Ticket successfully escalated.");
            modal.classList.add("hidden");
            location.reload();
        } else {
            alert("Escalation failed: " + (data.error || "Unknown error"));
        }
    } catch (err) {
        console.error("Error escalating ticket:", err);
        alert("An error occurred while sending the escalation request.");
    }
});

});

</script>
<script>
(function () {
  // Ensure DOM is ready even if script is included in head
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(() => {
    // sanity checks
    const ref = window.TICKET_REF || new URLSearchParams(window.location.search).get('ref');
    if (!ref) {
      console.error("TICKET_REF not found. Buttons disabled.");
      return;
    }

    // DOM elements (must match IDs in your HTML)
    const addCommentBtn = document.getElementById('addCommentBtn');
    const newCommentEl = document.getElementById('newComment');
    const commentsContainer = document.getElementById('commentsContainer');

    const addChecklistBtn = document.getElementById('addChecklistBtn');
    const newChecklistEl = document.getElementById('newChecklist');
    const checklistContainer = document.getElementById('checklistContainer');

    // Utility: show temporary message (optional)
    function toast(msg) {
      // minimal: console + alert fallback
      console.log("NOTICE:", msg);
      // optionally use a nicer UI; for now keep unobtrusive
    }

    // ------ Load Comments ------
    async function loadComments() {
      try {
        const res = await fetch(`../php/get_comments.php?ref=${encodeURIComponent(ref)}`, { cache: 'no-store' });
        if (!res.ok) throw new Error('Network error while loading comments: ' + res.status);
        const data = await res.json();
        commentsContainer.innerHTML = '';

        if (!Array.isArray(data) || data.length === 0) {
          commentsContainer.innerHTML = '<div class="text-sm text-gray-500">No comments yet.</div>';
          return;
        }

        data.forEach(c => {
          const idLabel = (c.is_technician == 1 || c.is_technician === true) 
            ? `Technician ID #${c.commenter_id}` 
            : `User ID #${c.commenter_id}`;

          const createdAt = c.created_at ?? c.createdAt ?? '';
          const html = `
            <div class="border-b pb-2">
              <p class="text-xs text-gray-500">${escapeHtml(idLabel)} — ${escapeHtml(createdAt)}</p>
              <p class="text-gray-700 mt-1">${escapeHtml(c.comment_text)}</p>
            </div>
          `;
          commentsContainer.insertAdjacentHTML('beforeend', html);
        });
      } catch (err) {
        console.error(err);
        commentsContainer.innerHTML = '<div class="text-sm text-red-500">Failed to load comments.</div>';
      }
    }

    // ------ Post Comment ------
    async function postComment() {
      const text = (newCommentEl?.value || '').trim();
      if (!text) {
        alert('Please enter a comment.');
        return;
      }

      try {
        const body = new URLSearchParams();
        body.append('ref', ref);
        body.append('comment', text);

        const res = await fetch('../php/add_comment_monitor.php', {
          method: 'POST',
          body
        });

        const json = await res.json();
        if (!json || !json.ok) {
          throw new Error(json?.error || 'Failed to save comment');
        }

        newCommentEl.value = '';
        await loadComments();
        toast('Comment added.');
      } catch (err) {
        console.error('postComment error', err);
        alert('Failed to add comment. See console for details.');
      }
    }

    // ------ Update Progress Bar ------
    function updateChecklistProgress(items) {
      if (!Array.isArray(items) || items.length === 0) {
        const progressBar = document.getElementById('checklistProgressBar');
        const progressText = document.getElementById('checklistProgressText');
        if (progressBar) progressBar.style.width = '0%';
        if (progressText) progressText.textContent = '0%';
        return;
      }
      
      const total = items.length;
      const completed = items.filter(item => item.is_completed == 1 || item.is_completed === true).length;
      const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
      
      const progressBar = document.getElementById('checklistProgressBar');
      const progressText = document.getElementById('checklistProgressText');
      
      if (progressBar) {
        progressBar.style.width = percentage + '%';
      }
      if (progressText) {
        progressText.textContent = `${completed}/${total} (${percentage}%)`;
      }
    }

    // ------ Load Checklist ------
    async function loadChecklist() {
      try {
        const res = await fetch(`../php/get_checklist.php?ref=${encodeURIComponent(ref)}`, { cache: 'no-store' });
        if (!res.ok) throw new Error('Network error while loading checklist: ' + res.status);
        const items = await res.json();
        checklistContainer.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
          checklistContainer.innerHTML = '<div class="text-sm text-gray-500">No checklist items.</div>';
          updateChecklistProgress([]);
          return;
        }

        // Update progress bar
        updateChecklistProgress(items);

        items.forEach(item => {
          const idLabel = (item.is_technician == 1 || item.is_technician === true) 
            ? `Technician ID #${item.created_by}` 
            : `User ID #${item.created_by}`;

          const checked = item.is_completed == 1 || item.is_completed === true ? 'checked' : '';
          const lineThrough = checked ? 'line-through text-gray-400' : 'text-gray-700';
          const createdAt = item.created_at ?? item.createdAt ?? '';

          const html = `
            <div class="flex items-start space-x-2 pb-2 border-b">
              <input type="checkbox" data-id="${item.item_id}" ${checked} class="chkItem mt-1">
              <div>
                <p class="${lineThrough}">${escapeHtml(item.description)}</p>
                <p class="text-xs text-gray-500">${escapeHtml(idLabel)} — ${escapeHtml(createdAt)}</p>
              </div>
            </div>
          `;
          checklistContainer.insertAdjacentHTML('beforeend', html);
        });

        // attach toggle handlers
        document.querySelectorAll('.chkItem').forEach(chk => {
          // remove previous listener if any (safer)
          chk.onchange = async (e) => {
            const itemId = chk.dataset.id;
            const completed = chk.checked ? 1 : 0;
            try {
              const body = new URLSearchParams();
              body.append('item_id', itemId);
              body.append('completed', completed);

              const res = await fetch('../php/toggle_checklist_item.php', {
                method: 'POST',
                body
              });
              const j = await res.json();
              if (!j || !j.ok) throw new Error(j?.error || 'Toggle failed');
              // reload to reflect completed_at, author etc. and update progress
              await loadChecklist();
            } catch (err) {
              console.error('toggleChecklist error', err);
              alert('Failed to toggle checklist item.');
              // revert checkbox visually
              chk.checked = !chk.checked;
            }
          };
        });

      } catch (err) {
        console.error(err);
        checklistContainer.innerHTML = '<div class="text-sm text-red-500">Failed to load checklist.</div>';
        updateChecklistProgress([]);
      }
    }

    // ------ Add Checklist Item ------
    async function addChecklistItem() {
      const text = (newChecklistEl?.value || '').trim();
      if (!text) {
        alert('Please enter a checklist item.');
        return;
      }

      try {
        const body = new URLSearchParams();
        body.append('ref', ref);
        body.append('description', text);

        const res = await fetch('../php/add_checklist_item.php', {
          method: 'POST',
          body
        });
        const json = await res.json();
        if (!json || !json.ok) throw new Error(json?.error || 'Failed to add item');

        newChecklistEl.value = '';
        await loadChecklist();
        toast('Checklist item added.');
      } catch (err) {
        console.error('addChecklistItem error', err);
        alert('Failed to add checklist item. See console for details.');
      }
    }

    // ----- Bind UI handlers -----
    if (addCommentBtn && newCommentEl) {
      addCommentBtn.addEventListener('click', (e) => {
        e.preventDefault();
        postComment();
      });
    } else {
      console.warn('Comment elements not found: addCommentBtn/newComment');
    }

    if (addChecklistBtn && newChecklistEl) {
      addChecklistBtn.addEventListener('click', (e) => {
        e.preventDefault();
        addChecklistItem();
      });
    } else {
      console.warn('Checklist elements not found: addChecklistBtn/newChecklist');
    }

    // Load initial data
    loadComments();
    loadChecklist();

    // small helper
    function escapeHtml(s) {
      if (!s && s !== 0) return '';
      return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }
  });
})();
</script>

</body>
</html>
