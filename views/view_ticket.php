<?php
include("../php/db.php");
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../views/login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'user';
$reference_id = $_GET['ref'] ?? '';
if (!$reference_id) {
    die("No reference ID provided.");
}

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
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    die("Ticket not found.");
}

$viewer_id = (int)($_SESSION['id'] ?? 0);
$is_staff_role = in_array($user_role, ['technician', 'department_head', 'admin'], true);
if (!$is_staff_role && (int)$ticket['user_id'] !== $viewer_id) {
    http_response_code(403);
    die("Forbidden.");
}

$sla_text = !empty($ticket['sla_date']) ? date('F j, Y', strtotime($ticket['sla_date'])) : 'N/A';
$current_status = strtolower((string)($ticket['status'] ?? 'pending'));
$is_closed = in_array($current_status, ['complete', 'resolved'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Ticket <?= htmlspecialchars($ticket['reference_id']) ?></title>
    <link rel="stylesheet" href="../css/theme.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/ticket_monitor.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      window.TICKET_REF = <?= json_encode($reference_id) ?>;
      window.USER_ROLE = <?= json_encode($user_role) ?>;
      window.TICKET_ID = <?= (int)$ticket['ticket_id'] ?>;
      window.TICKET_STATUS = <?= json_encode($current_status) ?>;
    </script>
</head>
<body class="bg-gray-50 min-h-screen page-transition">
<?php include("../includes/navbar.php"); ?>

<div class="max-w-7xl mx-auto py-10 px-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
  <div class="lg:col-span-7 space-y-6">
    <div>
      <p class="text-sm text-gray-500">Ticket #<?= htmlspecialchars($ticket['reference_id']) ?></p>
      <h1 class="text-3xl font-semibold text-gray-800"><?= htmlspecialchars($ticket['title']) ?></h1>
      <p class="text-sm text-gray-400">Created <?= date('F j, Y', strtotime($ticket['created_at'])) ?></p>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm">
      <h2 class="text-2xl font-semibold text-gray-800 mb-4">Ticket Details</h2>
      <p class="text-gray-700 whitespace-pre-line mb-4"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <p class="text-sm text-gray-500">Priority</p>
          <p class="text-gray-800 font-medium"><?= htmlspecialchars(ucfirst((string)($ticket['priority'] ?? 'low'))) ?></p>
        </div>
        <div>
          <p class="text-sm text-gray-500">Category</p>
          <p class="text-gray-800 font-medium"><?= htmlspecialchars((string)($ticket['category'] ?? 'General')) ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm">
      <h2 class="text-2xl font-semibold text-gray-800 mb-4">Conversation</h2>
      <div id="repliesContainer" class="space-y-3 mb-6"></div>
      <div class="flex flex-col gap-2">
        <textarea id="replyText" placeholder="Type your response..." rows="3" class="w-full border rounded px-3 py-2 resize-none"></textarea>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <input type="file" id="replyAttachment" class="text-sm text-gray-600" accept=".jpg,.jpeg,.png,.pdf">
          <button id="sendReplyBtn" class="bg-blue-900 text-white px-4 py-2 rounded w-full sm:w-auto">Send Reply</button>
        </div>
      </div>
    </div>
  </div>

  <div class="lg:col-span-5 space-y-6">
    <div class="bg-white p-6 rounded-lg shadow-sm">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Ticket Information</h2>
      <div class="space-y-2 text-sm">
        <p><strong>Requester:</strong> <?= htmlspecialchars($ticket['requester_name'] ?? 'N/A') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($ticket['requester_email'] ?? 'N/A') ?></p>
        <p><strong>Status:</strong> <span id="statusLabel"><?= htmlspecialchars(ucfirst($current_status)) ?></span></p>
        <p><strong>SLA:</strong> <?= htmlspecialchars($sla_text) ?></p>
        <p><strong>Assigned Technician:</strong> <?= htmlspecialchars($ticket['technician_name'] ?? 'Unassigned') ?></p>
      </div>

      <?php if ($is_staff_role): ?>
      <div class="mt-4 flex flex-col sm:flex-row gap-2">
        <button id="resolveTicketBtn" class="bg-blue-900 hover:bg-blue-700 text-white px-4 py-2 rounded-lg w-full sm:w-auto">
          <?= $current_status === 'complete' ? 'Reopen Ticket' : 'Mark as Resolved' ?>
        </button>
        <?php if (!$is_closed): ?>
        <button id="escalateTicketBtn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg w-full sm:w-auto">Escalate Ticket</button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($is_staff_role): ?>
    <div class="bg-white p-6 rounded-lg shadow-sm">
      <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Logs</h2>
      <ul id="logsContainer" class="space-y-2 text-sm text-gray-600 mt-4"></ul>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm">
      <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Comments</h2>
      <div id="commentsContainer" class="space-y-3 mt-4"></div>
      <?php if (!$is_closed): ?>
      <textarea id="newComment" class="w-full border rounded p-2 mt-3 text-sm" placeholder="Write a comment..."></textarea>
      <button id="addCommentBtn" class="mt-2 bg-blue-900 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">Post Comment</button>
      <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm">
      <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Checklist</h2>
      <div class="mt-4 mb-4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700">Progress</span>
          <span id="checklistProgressText" class="text-sm text-gray-600">0/0 (0%)</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5">
          <div id="checklistProgressBar" class="bg-blue-600 h-2.5 rounded-full" style="width:0%"></div>
        </div>
      </div>
      <div id="checklistContainer" class="space-y-3 mt-4"></div>
      <?php if (!$is_closed): ?>
      <div class="flex gap-2 mt-3">
        <input id="newChecklist" class="border rounded p-2 flex-grow text-sm" placeholder="New checklist item">
        <label id="newChecklistRequiredWrap" class="hidden items-center gap-1 text-xs text-gray-600 whitespace-nowrap">
          <input type="checkbox" id="newChecklistRequired" class="rounded border-gray-300">
          Required
        </label>
        <button id="addChecklistBtn" class="bg-blue-900 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">Add</button>
      </div>
      <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm">
      <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Warranty</h2>
      <div id="warrantySummary" class="mt-4 text-sm text-gray-600">Loading warranty details...</div>
      <div class="mt-3 flex gap-2">
        <?php if (!$is_closed): ?>
        <button id="createWarrantyClaimBtn" class="bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-2 rounded text-sm">Create Claim</button>
        <?php endif; ?>
        <?php if (in_array($user_role, ['technician', 'department_head', 'admin'], true)): ?>
        <button id="advanceWarrantyBtn" class="bg-amber-700 hover:bg-amber-800 text-white px-4 py-2 rounded text-sm">Advance Claim</button>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<div id="escalationModal" class="fixed inset-0 hidden bg-black bg-opacity-50 items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
    <h2 class="text-xl font-semibold mb-4 text-gray-800">Escalate / Reassign Ticket</h2>
    <div class="mb-3">
      <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
      <textarea id="escalationReason" class="w-full border border-gray-300 rounded-md p-3" rows="3" placeholder="Provide reason"></textarea>
    </div>
    <div class="mb-3">
      <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
      <select id="prioritySelect" class="w-full px-3 py-2 border border-gray-300 rounded-md">
        <option value="">Select Priority</option>
        <option value="low">Low</option>
        <option value="regular">Medium</option>
        <option value="high">High</option>
        <option value="critical">Urgent</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
      <select id="departmentSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md"></select>
    </div>
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Technician</label>
      <select id="technicianSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md"></select>
    </div>
    <div class="flex justify-end gap-2">
      <button id="cancelEscalationBtn" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
      <button id="confirmEscalationBtn" class="px-4 py-2 bg-blue-600 text-white rounded">Save Changes</button>
    </div>
  </div>
</div>

<script>
(() => {
  const ref = window.TICKET_REF;
  const ticketId = Number(window.TICKET_ID || 0);

  function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function toast(message, type = 'info') {
    const existing = document.querySelectorAll('.toast');
    existing.forEach((el) => el.remove());

    const toastEl = document.createElement('div');
    toastEl.className = 'toast fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full';

    if (type === 'success') {
      toastEl.classList.add('bg-green-500', 'text-white');
    } else if (type === 'error') {
      toastEl.classList.add('bg-red-500', 'text-white');
    } else if (type === 'warning') {
      toastEl.classList.add('bg-yellow-500', 'text-white');
    } else {
      toastEl.classList.add('bg-blue-500', 'text-white');
    }

    toastEl.textContent = message;
    document.body.appendChild(toastEl);

    setTimeout(() => {
      toastEl.classList.remove('translate-x-full');
    }, 10);

    setTimeout(() => {
      toastEl.classList.add('translate-x-full');
      setTimeout(() => {
        if (toastEl.parentNode) toastEl.remove();
      }, 300);
    }, 3000);
  }

  async function jsonFetch(url, opts = {}) {
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      throw new Error(data.error || `Request failed: ${res.status}`);
    }
    return data;
  }

  async function loadReplies() {
    const box = document.getElementById('repliesContainer');
    if (!box) return;
    try {
      const data = await jsonFetch(`../php/get_reply.php?ref=${encodeURIComponent(ref)}&t=${Date.now()}`);
      const replies = Array.isArray(data.replies) ? data.replies : [];
      if (!replies.length) {
        box.innerHTML = '<p class="text-gray-500 text-sm italic">No replies yet.</p>';
        return;
      }
      box.innerHTML = replies.map((r) => {
        const sender = r.replied_by === 'technician' ? 'Technician' : (r.replied_by === 'system' ? 'System' : 'User');
        const att = r.attachment_path ? `<a href="../${escapeHtml(r.attachment_path)}" class="text-blue-700 text-sm" target="_blank">View Attachment</a>` : '';
        return `<div class="p-3 border rounded-lg bg-gray-50">
          <div class="flex justify-between items-center mb-1">
            <span class="font-semibold">${escapeHtml(sender)}</span>
            <span class="text-xs text-gray-500">${escapeHtml(r.created_at || '')}</span>
          </div>
          <div class="text-sm text-gray-700">${escapeHtml(r.message || '')}</div>
          ${att}
        </div>`;
      }).join('');
    } catch (e) {
      box.innerHTML = '<p class="text-red-500 text-sm">Failed to load replies.</p>';
    }
  }

  async function sendReply() {
    const btn = document.getElementById('sendReplyBtn');
    const textEl = document.getElementById('replyText');
    const fileEl = document.getElementById('replyAttachment');
    if (!btn || !textEl || !fileEl) return;

    const text = textEl.value.trim();
    const file = fileEl.files[0];
    if (!text && !file) {
      toast('Please enter a reply or attach a file.', 'warning');
      return;
    }

    btn.disabled = true;
    const prev = btn.textContent;
    btn.textContent = 'Sending...';
    try {
      const form = new FormData();
      form.append('ref', ref);
      form.append('reply', text);
      if (file) form.append('replyAttachment', file);
      await jsonFetch('../php/post_reply.php', { method: 'POST', body: form });
      textEl.value = '';
      fileEl.value = '';
      await loadReplies();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = prev;
    }
  }

  async function loadLogs() {
    const box = document.getElementById('logsContainer');
    if (!box) return;
    try {
      const data = await jsonFetch(`../php/get_logs.php?ref=${encodeURIComponent(ref)}`);
      const logs = data?.data?.logs || [];
      if (!logs.length) {
        box.innerHTML = '<li class="text-gray-500">No logs yet.</li>';
        return;
      }
      box.innerHTML = logs.map((log) => `<li class="p-2 border rounded">
        <div class="flex justify-between"><span class="font-medium">${escapeHtml(log.user_name)}</span><span class="text-xs text-gray-500">${escapeHtml(log.created_at)}</span></div>
        <div class="text-sm text-gray-700">${escapeHtml(log.action_details || '')}</div>
      </li>`).join('');
    } catch (e) {
      box.innerHTML = '<li class="text-red-500">Failed to load logs.</li>';
    }
  }

  async function loadComments() {
    const box = document.getElementById('commentsContainer');
    if (!box) return;
    try {
      const data = await jsonFetch(`../php/get_comments.php?ref=${encodeURIComponent(ref)}`);
      const comments = data.comments || [];
      if (!comments.length) {
        box.innerHTML = '<div class="text-sm text-gray-500">No comments yet.</div>';
        return;
      }
      box.innerHTML = comments.map((c) => `<div class="border-b pb-2">
        <p class="text-xs text-gray-500">${escapeHtml(c.role)} #${escapeHtml(c.commenter_id)} - ${escapeHtml(c.created_at)}</p>
        <p class="text-gray-700 mt-1">${escapeHtml(c.comment_text)}</p>
      </div>`).join('');
    } catch (e) {
      box.innerHTML = '<div class="text-sm text-red-500">Failed to load comments.</div>';
    }
  }

  async function postComment() {
    const el = document.getElementById('newComment');
    if (!el) return;
    const text = el.value.trim();
    if (!text) return;
    const body = new URLSearchParams({ ref, comment: text });
    await jsonFetch('../php/add_comment_monitor.php', { method: 'POST', body });
    el.value = '';
    await loadComments();
  }

  function renderChecklist(items, progress, perms) {
    const box = document.getElementById('checklistContainer');
    const pText = document.getElementById('checklistProgressText');
    const pBar = document.getElementById('checklistProgressBar');
    if (!box || !pText || !pBar) return;

    const p = progress || { completed: 0, total: 0, percent: 0 };
    pText.textContent = `${p.completed || 0}/${p.total || 0} (${p.percent || 0}%)`;
    pBar.style.width = `${p.percent || 0}%`;

    if (!items.length) {
      box.innerHTML = '<div class="text-sm text-gray-500">No checklist items.</div>';
      return;
    }

    const canToggle = !!(perms && perms.can_toggle);
    box.innerHTML = items.map((i) => {
      const checked = i.is_completed == 1 ? 'checked' : '';
      const disabled = canToggle ? '' : 'disabled';
      const canDelete = i.can_delete ? '' : 'hidden';
      return `<div class="flex items-start justify-between gap-2 pb-2 border-b">
        <label class="flex items-start space-x-2 min-w-0 flex-1">
          <input type="checkbox" class="checkItem mt-1" data-id="${i.item_id}" ${checked} ${disabled}>
          <div class="min-w-0">
            <p class="${i.is_completed == 1 ? 'line-through text-gray-400' : 'text-gray-700'} break-words">${escapeHtml(i.description || '')}</p>
            <p class="text-xs text-gray-500">${escapeHtml(i.created_at || '')}</p>
          </div>
        </label>
        <button type="button" class="deleteCheckItem text-xs text-red-600 hover:text-red-700 ${canDelete}" data-id="${i.item_id}">Delete</button>
      </div>`;
    }).join('');

    box.querySelectorAll('.checkItem').forEach((el) => {
      el.addEventListener('change', async () => {
        try {
          const body = new URLSearchParams({ item_id: el.dataset.id, completed: el.checked ? '1' : '0' });
          await jsonFetch('../php/toggle_checklist_item.php', { method: 'POST', body });
          await loadChecklist();
        } catch (e) {
          toast(e.message, 'error');
          await loadChecklist();
        }
      });
    });

    box.querySelectorAll('.deleteCheckItem').forEach((el) => {
      el.addEventListener('click', async () => {
        if (!confirm('Delete this checklist item?')) return;
        try {
          const body = new URLSearchParams({ item_id: el.dataset.id });
          await jsonFetch('../php/delete_checklist_item.php', { method: 'POST', body });
          await loadChecklist();
        } catch (e) {
          toast(e.message, 'error');
          await loadChecklist();
        }
      });
    });
  }

  async function loadChecklist() {
    try {
      const data = await jsonFetch(`../php/get_checklist.php?ref=${encodeURIComponent(ref)}`);
      const addBtn = document.getElementById('addChecklistBtn');
      const input = document.getElementById('newChecklist');
      const requiredWrap = document.getElementById('newChecklistRequiredWrap');
      const requiredInput = document.getElementById('newChecklistRequired');
      const canEdit = !!(data.permissions && data.permissions.can_edit);
      const canSetRequired = !!(data.permissions && data.permissions.can_set_required);
      if (addBtn) addBtn.disabled = !canEdit;
      if (input) input.disabled = !canEdit;
      if (requiredInput) requiredInput.disabled = !canEdit || !canSetRequired;
      if (requiredWrap) {
        requiredWrap.classList.toggle('hidden', !canSetRequired);
        requiredWrap.classList.toggle('flex', canSetRequired);
      }
      renderChecklist(data.items || [], data.progress || {}, data.permissions || {});
    } catch (e) {
      const box = document.getElementById('checklistContainer');
      if (box) box.innerHTML = '<div class="text-red-500 text-sm">Failed to load checklist.</div>';
    }
  }

  async function addChecklistItem() {
    const input = document.getElementById('newChecklist');
    const requiredInput = document.getElementById('newChecklistRequired');
    if (!input) return;
    const description = input.value.trim();
    if (!description) return;
    const body = new URLSearchParams({ ref, description });
    if (requiredInput && !requiredInput.disabled) {
      body.set('is_required', requiredInput.checked ? '1' : '0');
    }
    await jsonFetch('../php/add_checklist_item.php', { method: 'POST', body });
    input.value = '';
    if (requiredInput) requiredInput.checked = false;
    await loadChecklist();
  }

  async function toggleResolve() {
    if (!confirm('Update ticket status?')) return;
    const body = new URLSearchParams({ ref });
    const data = await jsonFetch('../php/resolve_ticket.php', { method: 'POST', body });
    toast(data.message || 'Status updated.');
    location.reload();
  }

  async function loadDepartments() {
    const sel = document.getElementById('departmentSelect');
    if (!sel) return;
    const data = await jsonFetch('../php/get_departments.php');
    const depts = Array.isArray(data) ? data : (data.departments || []);
    sel.innerHTML = '<option value="">Select Department</option>' + depts.map((d) => `<option value="${d.department_id}">${escapeHtml(d.department_name)}</option>`).join('');
  }

  async function loadTechnicians(deptId) {
    const sel = document.getElementById('technicianSelect');
    if (!sel) return;
    const data = await jsonFetch(`../php/get_technicians.php?dept=${encodeURIComponent(deptId)}`);
    const list = Array.isArray(data) ? data : (data.technicians || []);
    sel.innerHTML = '<option value="">Select Technician</option>' + list.map((t) => `<option value="${t.technician_id}">${escapeHtml(t.name)} (Active: ${t.active_tickets})</option>`).join('');
  }

  async function submitEscalation() {
    const reason = (document.getElementById('escalationReason')?.value || '').trim();
    const priority = document.getElementById('prioritySelect')?.value || '';
    const department_id = document.getElementById('departmentSelect')?.value || '';
    const technician_id = document.getElementById('technicianSelect')?.value || '';

    if (!reason || !priority || !department_id || !technician_id) {
      toast('Please fill all escalation fields.', 'warning');
      return;
    }

    const body = new URLSearchParams({ ref, reason, priority, department_id, technician_id });
    await jsonFetch('../php/escalate_ticket.php', { method: 'POST', body });
    location.reload();
  }

  async function loadWarranty() {
    const box = document.getElementById('warrantySummary');
    if (!box || !ticketId) return;
    try {
      const data = await jsonFetch(`../php/warranty_claim_get.php?ticket_id=${ticketId}`);
      const claims = data.claims || [];
      if (!claims.length) {
        box.textContent = 'No warranty claim linked to this ticket.';
        box.dataset.claimId = '';
        return;
      }
      const claim = claims[0];
      box.dataset.claimId = claim.claim_id;
      box.innerHTML = `Latest claim #${escapeHtml(claim.claim_id)} - <strong>${escapeHtml(claim.claim_status)}</strong> (${escapeHtml(claim.claim_type)})`;
    } catch (e) {
      box.textContent = 'Warranty details unavailable.';
    }
  }

  async function createWarrantyClaim() {
    if (!ticketId) return;
    const notes = prompt('Warranty claim notes:', 'Claim created from ticket detail page') || '';
    const body = new URLSearchParams({ ticket_id: String(ticketId), claim_type: 'inspection', notes });
    await jsonFetch('../php/warranty_claim_create.php', { method: 'POST', body });
    await loadWarranty();
  }

  async function advanceWarrantyClaim() {
    const box = document.getElementById('warrantySummary');
    const claimId = box?.dataset.claimId;
    if (!claimId) {
      toast('No claim available to advance.', 'warning');
      return;
    }
    const toStatus = prompt('Next status (under_review, approved, rejected, in_service, completed, cancelled):', 'under_review') || '';
    if (!toStatus) return;
    const remarks = prompt('Transition remarks:', 'Updated from ticket detail page') || '';
    const body = new URLSearchParams({ claim_id: String(claimId), to_status: toStatus, remarks });
    await jsonFetch('../php/warranty_claim_transition.php', { method: 'POST', body });
    await loadWarranty();
  }

  document.addEventListener('DOMContentLoaded', async () => {
    document.getElementById('sendReplyBtn')?.addEventListener('click', () => {
      sendReply().catch((e) => toast(e.message, 'error'));
    });
    document.getElementById('addCommentBtn')?.addEventListener('click', () => {
      postComment().catch((e) => toast(e.message, 'error'));
    });
    document.getElementById('addChecklistBtn')?.addEventListener('click', () => {
      addChecklistItem().catch((e) => toast(e.message, 'error'));
    });
    document.getElementById('resolveTicketBtn')?.addEventListener('click', () => {
      toggleResolve().catch((e) => toast(e.message, 'error'));
    });

    const modal = document.getElementById('escalationModal');
    document.getElementById('escalateTicketBtn')?.addEventListener('click', async () => {
      if (!modal) return;
      await loadDepartments();
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    });
    document.getElementById('cancelEscalationBtn')?.addEventListener('click', () => {
      if (!modal) return;
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    });
    document.getElementById('departmentSelect')?.addEventListener('change', (e) => {
      const dept = e.target.value;
      if (dept) loadTechnicians(dept);
    });
    document.getElementById('confirmEscalationBtn')?.addEventListener('click', () => {
      submitEscalation().catch((e) => toast(e.message, 'error'));
    });

    document.getElementById('createWarrantyClaimBtn')?.addEventListener('click', () => {
      createWarrantyClaim().catch((e) => toast(e.message, 'error'));
    });
    document.getElementById('advanceWarrantyBtn')?.addEventListener('click', () => {
      advanceWarrantyClaim().catch((e) => toast(e.message, 'error'));
    });

    await Promise.allSettled([loadReplies(), loadLogs(), loadComments(), loadChecklist(), loadWarranty()]);
  });
})();
</script>

</body>
</html>
