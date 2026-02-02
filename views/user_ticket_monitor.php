<?php
// user_ticket_monitor.php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}
$user_type = $_SESSION['role'];

// Success notification when a ticket was just created
$ticket_created = isset($_GET['success']) && $_GET['success'] === 'ticket_created';
$created_ref = $_GET['ref'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Tickets</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Base styles -->
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/ticket_monitor2.css">
  <!-- Google Font to match new UI -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Bootstrap 4 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="../js/ui-enhancements.js" defer></script>
  <script src="../js/animations.js" defer></script>
  <script src="../js/ticket-interactions.js" defer></script>

  <style>
    body {
      background:#f8fafc;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .panel {
      background:#ffffff;
      border-radius:12px;
      padding:24px 24px 20px;
      margin-top:24px;
      box-shadow:0 8px 20px rgba(15, 23, 42, 0.06);
    }

    .page-header-title {
      font-weight:600;
      color:#0f172a;
      margin-bottom:4px;
    }

    .page-header-subtitle {
      color:#64748b;
      font-size:0.9rem;
      margin-bottom:0;
    }

    .table-buttons {
      display:flex;
      justify-content:center;
      gap:12px;
      margin:18px 0 16px;
      flex-wrap:wrap;
    }

    .table-btn {
      background:#0b4c6a;
      color:#ffffff;
      border-radius:999px;
      padding:8px 22px;
      font-size:0.95rem;
      font-weight:500;
      border:0;
      transition:background 0.15s ease, transform 0.1s ease, box-shadow 0.1s ease;
      box-shadow:0 1px 3px rgba(15, 23, 42, 0.15);
    }

    .table-btn:hover {
      background:#083b54;
      transform:translateY(-1px);
      box-shadow:0 4px 10px rgba(15, 23, 42, 0.18);
    }

    .table-btn.inactive {
      background:#e5e7eb;
      color:#4b5563;
      box-shadow:none;
    }

    .stats {
      display:flex;
      gap:16px;
      margin-bottom:18px;
      flex-wrap:wrap;
    }

    .stat-card {
      flex:1;
      min-width:220px;
      background:#f9fafb;
      border-radius:10px;
      padding:14px 16px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      cursor:pointer;
      transition:background 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
      background:#f3f4f6;
      transform:translateY(-2px);
      box-shadow:0 4px 12px rgba(15, 23, 42, 0.1);
    }

    .stat-card.active {
      background:#e0f2fe;
      border:2px solid #0ea5e9;
    }

    .stat-card h2 {
      margin:0;
      font-size:1.6rem;
      font-weight:600;
      color:#111827;
    }

    .stat-card-label {
      margin:4px 0 0;
      font-size:0.85rem;
      color:#6b7280;
    }

    .controls {
      display:flex;
      gap:10px;
      align-items:center;
      margin-bottom:14px;
      flex-wrap:wrap;
    }

    .controls .form-control {
      font-size:0.9rem;
    }

    .search-input {
      min-width:260px;
      max-width:340px;
    }

    .table thead {
      background:#0f172a;
      color:#f9fafb;
      font-size:0.82rem;
    }

    .table thead th {
      border-top:none;
      border-bottom:none;
      text-transform:uppercase;
      letter-spacing:0.03em;
    }

    .table tbody td {
      vertical-align:middle;
      font-size:0.9rem;
    }

    .details-link {
      color:#2563eb;
      font-weight:500;
      text-decoration:none;
    }

    .details-link:hover {
      text-decoration:underline;
      color:#1d4ed8;
    }

    .center { text-align:center; }

    @media (max-width:768px) {
      .controls {
        flex-direction:column;
        align-items:stretch;
      }

      .search-input {
        width:100%!important;
      }
    }
  </style>
</head>
<body class="page-transition">
<?php include "../includes/navbar.php"; ?>

  <?php if ($ticket_created): ?>
  <div id="ticketCreatedToast" class="position-fixed" style="top: 80px; right: 16px; z-index: 1050;">
    <div class="d-flex align-items-center bg-success text-white px-3 py-2 rounded shadow-sm small">
      <span class="badge badge-light text-success mr-2">New</span>
      <span>
        Ticket
        <?php if (!empty($created_ref)): ?>
          <strong>#<?php echo htmlspecialchars($created_ref); ?></strong>
        <?php else: ?>
          <strong>created</strong>
        <?php endif; ?>
        successfully.
      </span>
      <button type="button" class="close ml-2 text-white p-0" aria-label="Close"
              onclick="document.getElementById('ticketCreatedToast')?.remove();"
              style="opacity: 0.8;">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  </div>
  <?php endif; ?>

  <div class="container my-4">
    <div class="d-flex justify-content-between align-items-baseline flex-wrap">
      <div class="mb-2">
        <h3 class="page-header-title">My Tickets</h3>
        <p class="page-header-subtitle">Track the status of your requests and view details in one place.</p>
      </div>
      <div class="text-right mb-2">
        <span class="text-muted small">Signed in as</span><br>
        <span class="font-weight-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
      </div>
    </div>

    <div class="panel">
      <!-- buttons -->
      <div class="table-buttons">
        <button id="btn-table-1" class="table-btn" data-table="1">My Tickets</button>
        <?php if ($user_type === 'department_head' || $user_type === 'admin'): ?>
        <button id="btn-table-2" class="table-btn inactive" data-table="2">To do</button>
        <?php endif; ?>
        <button id="btn-table-3" class="table-btn inactive" data-table="3">Completed</button>
      </div>

      <div class="stats">
        <div class="stat-card" id="stat-card-total" data-filter="all">
          <h2 id="stat-total">0</h2>
          <div class="stat-card-label">Total Tickets</div>
        </div>
        <?php if ($user_type !== 'external'): ?>
        <div class="stat-card" id="stat-card-needing" data-filter="needing">
          <h2 id="stat-needing">0</h2>
          <div class="stat-card-label">Needing My Input</div>
        </div>
        <?php endif; ?>
      </div>

      <!-- controls -->
      <div class="controls">
        <input id="search" class="form-control search-input" placeholder="Search by title or reference ID">
        <select id="priority" class="form-control" style="max-width:180px;">
          <option value="">Priority (All)</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>

        <select id="status" class="form-control" style="max-width:220px;">
          <option value="">Status (All)</option>
          <option value="Assigning">Assigning</option>
          <option value="Pending">Pending</option>
          <option value="Followup">Followup</option>
          <option value="Complete">Complete</option>
        </select>

        <select id="sort" class="form-control" style="max-width:220px;">
          <option value="created_at_desc">Date: Newest first</option>
          <option value="created_at_asc">Date: Oldest first</option>
        </select>

        <button id="btn-refresh" class="btn btn-outline-primary">Refresh</button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover" id="tickets-table">
          <thead>
            <tr>
              <th>Ticket ID</th>
              <th>Title</th>
              <th>Status</th>
              <th>Priority</th>
              <th>Date</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody id="tickets-body">
            <!-- loaded via AJAX -->
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <small id="page-info">Page 1</small>
        <nav>
          <ul class="pagination" id="pagination"></ul>
        </nav>
      </div>
    </div>
  </div>

  <!-- jQuery + Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // client-side logic
    let currentTable = 1;
    let currentPage = 1;
    const pageSize = 10;
    let activeStatFilter = 'all'; // 'all' or 'needing'

    function setActiveButton(table) {
      currentTable = table;
      $('.table-btn').addClass('inactive');
      $(`#btn-table-${table}`).removeClass('inactive');
    }

    function renderStatusBadge(status) {
      const raw = (status || '').trim();
      if (!raw) return '';
      const capital = raw.charAt(0).toUpperCase() + raw.slice(1).toLowerCase();
      const lower = raw.toLowerCase();
      const label = escapeHtml(raw);
      // Use shared .status + type-specific class so colors come from ticket_monitor2.css
      return `<span class="status ${capital} ${lower}">${label}</span>`;
    }

    function renderPriorityBadge(priority) {
      const raw = (priority || '').trim();
      if (!raw) return '';
      const lower = raw.toLowerCase();
      let className;
      switch (lower) {
        case 'low':
          className = 'Low';
          break;
        case 'medium':
        case 'regular':
          className = 'Medium';
          break;
        case 'high':
          className = 'High';
          break;
        case 'urgent':
          className = 'Urgent';
          break;
        case 'critical':
          className = 'Urgent';
          break;
        default:
          className = raw.charAt(0).toUpperCase() + raw.slice(1);
      }
      const label = escapeHtml(className);
      return `<span class="priority ${className}">${label}</span>`;
    }

    function fetchTickets() {
      const q = $('#search').val().trim();
      let status = $('#status').val();
      const priority = $('#priority').val();
      const sort = $('#sort').val();
      const page = currentPage;

      // Apply stat filter: if "needing" is active, filter out completed tickets
      if (activeStatFilter === 'needing' && !status) {
        // Don't override if user has explicitly selected a status
        // The backend will handle the needing filter logic
      }

      $.ajax({
        url: '../php/fetch_ticket.php',
        method: 'GET',
        dataType: 'json',
        data: {
          table: currentTable,
          q: q,
          status: status,
          priority: priority,
          sort: sort,
          page: page,
          page_size: pageSize,
          needing_filter: activeStatFilter === 'needing' ? 1 : 0
        },
        success: function(resp) {
          if (resp.success) {
            // update stats
            $('#stat-total').text(resp.total_count);
            $('#stat-needing').text(resp.needing_count);

            // render rows
            const tbody = $('#tickets-body');
            const detailsPage = (currentTable === 2)
              ? "view_ticket.php"
              : "cust_ticket.php";

            tbody.empty();
            if (resp.data.length === 0) {
              tbody.append('<tr><td colspan="6" class="center">No tickets found</td></tr>');
            } else {
              resp.data.forEach(r => {
                const badge = renderStatusBadge(r.status);
                const priorityBadge = renderPriorityBadge(r.priority);
                const date = r.created_at;
                const row = `<tr>
                  <td>${r.reference_id}</td>
                  <td>${escapeHtml(r.title)}</td>
                  <td>${badge}</td>
                  <td>${priorityBadge}</td>
                  <td>${date}</td>
                  <td><a class="details-link" href="${detailsPage}?ref=${encodeURIComponent(r.reference_id)}">View Details</a></td>
                </tr>`;
                tbody.append(row);
              });
            }

            // pagination
            renderPagination(resp.page, resp.total_pages);
            $('#page-info').text(`Page ${resp.page} of ${resp.total_pages}`);
          } else {
            alert('Failed to load tickets: ' + resp.message);
          }
        },
        error: function(xhr, st, err) {
          console.error(xhr, st, err);
          alert('An error occurred while loading tickets.');
        }
      });
    }

    function renderPagination(current, total) {
      const ul = $('#pagination');
      ul.empty();
      if (total <= 1) return;
      // previous
      const prevDisabled = current <= 1 ? 'disabled' : '';
      ul.append(`<li class="page-item ${prevDisabled}"><a class="page-link" href="#" data-page="${current-1}">Prev</a></li>`);
      // pages (show some neighbors)
      const start = Math.max(1, current - 2);
      const end = Math.min(total, current + 2);
      for (let i = start; i <= end; i++) {
        const active = i === current ? 'active' : '';
        ul.append(`<li class="page-item ${active}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
      }
      // next
      const nextDisabled = current >= total ? 'disabled' : '';
      ul.append(`<li class="page-item ${nextDisabled}"><a class="page-link" href="#" data-page="${current+1}">Next</a></li>`);
    }

    function setActiveStatCard(filter) {
      activeStatFilter = filter;
      $('.stat-card').removeClass('active');
      if (filter === 'all') {
        $('#stat-card-total').addClass('active');
      } else if (filter === 'needing') {
        $('#stat-card-needing').addClass('active');
      }
    }

    // click handlers
    $(function(){
      setActiveButton(1);
      setActiveStatCard('all');
      fetchTickets();

      // Stat card click handlers
      $('#stat-card-total').on('click', function(){
        setActiveStatCard('all');
        currentPage = 1;
        $('#status').val(''); // Clear status filter
        fetchTickets();
      });

      $('#stat-card-needing').on('click', function(){
        setActiveStatCard('needing');
        currentPage = 1;
        // Set status to show non-completed tickets
        if (!$('#status').val()) {
          // Only auto-set if no status is selected
        }
        fetchTickets();
      });

      $('.table-btn').on('click', function(){
        const table = parseInt($(this).data('table'));
        setActiveButton(table);
        currentPage = 1;
        // Clear status filter if table 3 shows completed, otherwise hide completed by default
        if (table !== 3) {
          $('#status').val('');
        }
        fetchTickets();
      });

      $('#btn-refresh').on('click', function(){
        currentPage = 1;
        fetchTickets();
      });

      $('#search').on('keyup', function(e){
        if (e.key === 'Enter') {
          currentPage = 1;
          fetchTickets();
        }
      });

      $('#status, #priority, #sort').on('change', function(){
        currentPage = 1;
        fetchTickets();
      });

      $('#pagination').on('click', 'a.page-link', function(e){
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (!isNaN(page) && page >= 1) {
          currentPage = page;
          fetchTickets();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });

      // Auto-hide ticket-created toast after a few seconds
      <?php if ($ticket_created): ?>
      setTimeout(function() {
        $('#ticketCreatedToast').fadeOut(200, function() { $(this).remove(); });
      }, 5000);
      <?php endif; ?>
    });

    // small helper to prevent XSS in titles
    function escapeHtml(text) {
      if (!text) return '';
      return text.replace(/[&<>"'`=\/]/g, function (s) {
        return ({
          '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;", "/":"&#x2F;", "`":"&#x60;","=":"&#x3D;"
        })[s];
      });
    }
  </script>
</body>
</html>
