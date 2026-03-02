<?php
require_once("../php/check_um_access.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>SLA Weight - Interconnect Solutions Company</title>
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/basicTemp.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <script src="../js/ui-enhancements.js" defer></script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased page-transition" style="background-color: var(--bg-primary, #f8fafc);">
<?php include("../includes/navbar.php"); ?>
  <div class="max-w-[1400px] mx-auto px-4 py-6">
    <main class="w-full">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900">SLA Weight Table</h1>
          <p class="text-sm text-gray-500 mt-1">P = (0.5*I) + (0.3*C) + (0.2*(11-T)) - drives priority and auto-assign</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <input id="searchInput" type="search" placeholder="Search category..." class="h-10 px-3 rounded-lg border border-slate-200 text-sm w-48" />
          <select id="filterDepartment" class="h-10 px-3 rounded-lg border border-slate-200 text-sm min-w-[120px]">
            <option value="">All Departments</option>
            <option value="IT">IT</option>
            <option value="Finance">Finance</option>
            <option value="Engineering">Engineering</option>
            <option value="HR">HR</option>
            <option value="Warehouse">Warehouse</option>
            <option value="Production">Production</option>
            <option value="Sales">Sales</option>
            <option value="Shipping">Shipping</option>
            <option value="Facilities">Facilities</option>
          </select>
          <button id="btnRefresh" class="h-10 px-4 bg-slate-600 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">Refresh</button>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Category</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Department</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Time (T)</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Importance (I)</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 align-middle">P (Internal C=8)</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 align-middle">P (External C=9)</th>
                <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 align-middle">Actions</th>
              </tr>
            </thead>
            <tbody id="slaTableBody">
              <tr><td colspan="7" class="py-8 text-center text-gray-500">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit SLA Weight</h3>
      <input type="hidden" id="editId" value="" />
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <input type="text" id="editCategory" class="w-full h-10 px-3 rounded-lg border border-slate-200" readonly />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
          <input type="text" id="editDepartment" class="w-full h-10 px-3 rounded-lg border border-slate-200" readonly />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Time (1-10)</label>
          <input type="number" id="editTime" min="1" max="10" class="w-full h-10 px-3 rounded-lg border border-slate-200" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Importance (1-10)</label>
          <input type="number" id="editImportance" min="1" max="10" class="w-full h-10 px-3 rounded-lg border border-slate-200" />
        </div>
      </div>
      <div class="flex gap-2 mt-6">
        <button id="btnSaveEdit" class="flex-1 h-10 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium">Save</button>
        <button id="btnCancelEdit" class="flex-1 h-10 px-4 bg-slate-200 hover:bg-slate-300 text-gray-800 rounded-lg font-medium">Cancel</button>
      </div>
    </div>
  </div>

  <script>
  (function() {
    const API_LIST = '../php/sla_weight_list.php';
    const API_SAVE = '../php/sla_weight_save.php';

    function computeP(T, I, C) {
      let p = (0.5 * I) + (0.3 * C) + (0.2 * (11 - T));
      if (I >= 9 && C >= 8) p = 9;
      else if (C >= 9 && I >= 7) p = 8;
      return p.toFixed(2);
    }

    function loadSlaWeights() {
      const search = $('#searchInput').val().trim();
      const dept = $('#filterDepartment').val();
      let url = API_LIST;
      const params = new URLSearchParams();
      if (search) params.set('search', search);
      if (dept) params.set('department', dept);
      if (params.toString()) url += '?' + params.toString();

      $.get(url).done(function(res) {
        if (res.success && res.data) {
          renderTable(res.data);
        } else {
          $('#slaTableBody').html('<tr><td colspan="7" class="py-8 text-center text-red-500">Error loading data</td></tr>');
        }
      }).fail(function() {
        $('#slaTableBody').html('<tr><td colspan="7" class="py-8 text-center text-red-500">Failed to load</td></tr>');
      });
    }

    function renderTable(rows) {
      if (!rows.length) {
        $('#slaTableBody').html('<tr><td colspan="7" class="py-8 text-center text-gray-500">No records</td></tr>');
        return;
      }
      const html = rows.map(r => {
        const T = parseInt(r.time_value, 10);
        const I = parseInt(r.importance, 10);
        const pInternal = computeP(T, I, 8);
        const pExternal = computeP(T, I, 9);
        return `<tr class="border-b hover:bg-gray-50">
          <td class="py-3 px-4 text-sm text-gray-800">${escapeHtml(r.category)}</td>
          <td class="py-3 px-4 text-sm text-gray-700">${escapeHtml(r.department_name)}</td>
          <td class="py-3 px-4 text-sm text-center">${T}</td>
          <td class="py-3 px-4 text-sm text-center">${I}</td>
          <td class="py-3 px-4 text-sm text-center font-medium">${pInternal}</td>
          <td class="py-3 px-4 text-sm text-center font-medium">${pExternal}</td>
          <td class="py-3 px-4 text-right">
            <button class="edit-btn text-indigo-600 hover:text-indigo-800 text-sm font-medium" data-id="${r.sla_weight_id}" data-cat="${escapeHtml(r.category)}" data-dept="${escapeHtml(r.department_name)}" data-time="${T}" data-imp="${I}">Edit</button>
          </td>
        </tr>`;
      }).join('');
      $('#slaTableBody').html(html);
    }

    function escapeHtml(s) {
      const d = document.createElement('div');
      d.textContent = s || '';
      return d.innerHTML;
    }

    function openEditModal(id, category, department, time, importance) {
      $('#editId').val(id);
      $('#editCategory').val(category);
      $('#editDepartment').val(department);
      $('#editTime').val(time);
      $('#editImportance').val(importance);
      $('#editModal').removeClass('hidden').addClass('flex');
    }

    function closeEditModal() {
      $('#editModal').addClass('hidden').removeClass('flex');
    }

    $('#searchInput, #filterDepartment').on('change keyup', loadSlaWeights);
    $('#btnRefresh').on('click', loadSlaWeights);

    $(document).on('click', '.edit-btn', function() {
      const id = $(this).data('id');
      const cat = $(this).data('cat');
      const dept = $(this).data('dept');
      const time = $(this).data('time');
      const imp = $(this).data('imp');
      openEditModal(id, cat, dept, time, imp);
    });

    $('#btnCancelEdit').on('click', closeEditModal);
    $('#editModal').on('click', function(e) {
      if (e.target === this) closeEditModal();
    });

    $('#btnSaveEdit').on('click', function() {
      const id = $('#editId').val();
      const category = $('#editCategory').val();
      const department = $('#editDepartment').val();
      const time = parseInt($('#editTime').val(), 10) || 1;
      const importance = parseInt($('#editImportance').val(), 10) || 1;
      const fd = new FormData();
      fd.append('sla_weight_id', id);
      fd.append('category', category);
      fd.append('department_name', department);
      fd.append('time_value', Math.min(10, Math.max(1, time)));
      fd.append('importance', Math.min(10, Math.max(1, importance)));
      $.ajax({
        url: API_SAVE,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false
      }).done(function(res) {
        if (res.success) {
          closeEditModal();
          loadSlaWeights();
        } else {
          alert(res.error || 'Save failed');
        }
      }).fail(function() {
        alert('Request failed');
      });
    });

    loadSlaWeights();
  })();
  </script>
</body>
</html>
