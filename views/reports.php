<?php
session_start();
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'department_head', 'technician'])) {
    header("Location: ../views/dashboard.php");
    exit();
}
$user_name = $_SESSION['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Reports — Interconnect Solutions Company</title>
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/basicTemp.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <script src="../js/ui-enhancements.js" defer></script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased page-transition">
<?php include("../includes/navbar.php"); ?>
  <div class="max-w-[1400px] mx-auto px-4 py-6">
    <main class="w-full">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Reports</h1>
      <p class="text-sm text-gray-500 mb-6">Customer complaints, ticket volume, resolution time, and SLA compliance.</p>

      <!-- Tabs -->
      <div class="flex flex-wrap gap-2 border-b border-gray-200 mb-4">
        <button type="button" data-tab="complaint" class="report-tab px-4 py-2 rounded-t-lg font-medium text-sm border-b-2 border-transparent hover:bg-gray-100" data-report="customer_complaint">Customer Complaint</button>
        <button type="button" data-tab="volume" class="report-tab px-4 py-2 rounded-t-lg font-medium text-sm border-b-2 border-transparent hover:bg-gray-100" data-report="ticket_volume">Ticket Volume</button>
        <button type="button" data-tab="resolution" class="report-tab px-4 py-2 rounded-t-lg font-medium text-sm border-b-2 border-transparent hover:bg-gray-100" data-report="resolution_time">Resolution Time</button>
        <button type="button" data-tab="sla" class="report-tab px-4 py-2 rounded-t-lg font-medium text-sm border-b-2 border-transparent hover:bg-gray-100" data-report="sla_compliance">SLA Compliance</button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-md p-4 mb-6 flex flex-wrap items-end gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
          <input type="date" id="filterDateFrom" class="h-10 px-3 rounded-lg border border-slate-200 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
          <input type="date" id="filterDateTo" class="h-10 px-3 rounded-lg border border-slate-200 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
          <select id="filterCategory" class="h-10 px-3 rounded-lg border border-slate-200 text-sm min-w-[140px]">
            <option value="">All</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Priority</label>
          <select id="filterPriority" class="h-10 px-3 rounded-lg border border-slate-200 text-sm min-w-[100px]">
            <option value="">All</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
          <select id="filterDepartment" class="h-10 px-3 rounded-lg border border-slate-200 text-sm min-w-[120px]">
            <option value="">All</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
          <select id="filterStatus" class="h-10 px-3 rounded-lg border border-slate-200 text-sm min-w-[100px]">
            <option value="">All</option>
          </select>
        </div>
        <button type="button" id="btnApply" class="h-10 px-4 bg-slate-600 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">Apply</button>
        <button type="button" id="btnReset" class="h-10 px-4 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium">Reset</button>
        <div class="ml-auto flex gap-2">
          <a id="btnExportCsv" href="#" class="h-10 px-4 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium inline-flex items-center">Export CSV</a>
          <button type="button" id="btnExportPdf" class="h-10 px-4 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">Export PDF</button>
        </div>
      </div>

      <!-- Report content (captured for PDF) -->
      <div id="reportContent">
        <!-- Complaint Report -->
        <div id="panel-complaint" class="report-panel hidden">
          <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Customer</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Ticket ID</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Description</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Category</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Priority</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Date Reported</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Status</th>
                  </tr>
                </thead>
                <tbody id="complaintTableBody">
                  <tr><td colspan="7" class="py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
              </table>
            </div>
            <div id="complaintPagination" class="p-3 border-t flex justify-between items-center"></div>
          </div>
        </div>

        <!-- Volume Report -->
        <div id="panel-volume" class="report-panel hidden">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-md p-4">
              <h3 class="font-semibold text-gray-800 mb-2">Tickets over time</h3>
              <canvas id="chartVolumeLine" height="200"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4">
              <h3 class="font-semibold text-gray-800 mb-2">By category</h3>
              <canvas id="chartVolumeCategory" height="200"></canvas>
            </div>
          </div>
          <div class="bg-white rounded-xl shadow-md p-4">
            <h3 class="font-semibold text-gray-800 mb-2">By priority</h3>
            <canvas id="chartVolumePriority" height="180"></canvas>
          </div>
          <div class="mt-4 text-sm text-gray-600"><span id="volumeTotal">Total: 0</span> tickets in selected period.</div>
        </div>

        <!-- Resolution Time Report -->
        <div id="panel-resolution" class="report-panel hidden">
          <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <h3 class="font-semibold text-gray-800 mb-2">Summary (hours)</h3>
            <div class="flex flex-wrap gap-6">
              <div><span class="text-gray-500">Average:</span> <span id="resAvg" class="font-semibold">—</span></div>
              <div><span class="text-gray-500">Min:</span> <span id="resMin" class="font-semibold">—</span></div>
              <div><span class="text-gray-500">Max:</span> <span id="resMax" class="font-semibold">—</span></div>
            </div>
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-md p-4">
              <h3 class="font-semibold text-gray-800 mb-2">By category</h3>
              <canvas id="chartResCategory" height="220"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4">
              <h3 class="font-semibold text-gray-800 mb-2">By department</h3>
              <canvas id="chartResDepartment" height="220"></canvas>
            </div>
          </div>
        </div>

        <!-- SLA Compliance Report -->
        <div id="panel-sla" class="report-panel hidden">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-md p-4">
              <h3 class="font-semibold text-gray-800 mb-2">SLA met vs breached</h3>
              <canvas id="chartSlaMet" height="200"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4">
              <h3 class="font-semibold text-gray-800 mb-2">Breach reasons</h3>
              <canvas id="chartSlaReasons" height="200"></canvas>
            </div>
          </div>
          <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <h3 class="font-semibold text-gray-800 p-4 border-b">Compliance list</h3>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Reference</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">SLA target</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Actual resolution</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">SLA met</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Actual hours</th>
                  </tr>
                </thead>
                <tbody id="slaTableBody">
                  <tr><td colspan="5" class="py-8 text-center text-gray-500">Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="mt-2 text-sm text-gray-600"><span id="slaSummary">—</span></div>
        </div>
      </div>
    </main>
  </div>

  <script>
  (function() {
    const API = '../php/reports_api.php';
    let currentReport = 'customer_complaint';
    let currentPage = 1;
    let charts = {};

    function getFilters() {
      return {
        date_from: $('#filterDateFrom').val() || undefined,
        date_to: $('#filterDateTo').val() || undefined,
        category: $('#filterCategory').val() || undefined,
        priority: $('#filterPriority').val() || undefined,
        department: $('#filterDepartment').val() || undefined,
        status: $('#filterStatus').val() || undefined,
        page: currentPage,
        pageSize: 20
      };
    }

    function paramsToQuery(obj) {
      const p = new URLSearchParams();
      Object.keys(obj).forEach(k => { if (obj[k] !== undefined && obj[k] !== '') p.set(k, obj[k]); });
      return p.toString();
    }

    function setDefaultDates() {
      const to = new Date();
      const from = new Date();
      from.setDate(from.getDate() - 30);
      $('#filterDateFrom').val(from.toISOString().slice(0, 10));
      $('#filterDateTo').val(to.toISOString().slice(0, 10));
    }

    function loadFilterOptions() {
      $.get(API + '?report=filter_options').done(function(data) {
        const cat = $('#filterCategory');
        cat.find('option:not(:first)').remove();
        (data.categories || []).forEach(c => cat.append($('<option></option>').val(c).text(c)));
        const pri = $('#filterPriority');
        pri.find('option:not(:first)').remove();
        (data.priorities || []).forEach(p => pri.append($('<option></option>').val(p).text(p || '—')));
        const dept = $('#filterDepartment');
        dept.find('option:not(:first)').remove();
        (data.departments || []).forEach(d => dept.append($('<option></option>').val(d).text(d)));
        const st = $('#filterStatus');
        st.find('option:not(:first)').remove();
        (data.statuses || []).forEach(s => st.append($('<option></option>').val(s).text(s)));
      }).fail(function() {
        $('#filterStatus').append('<option value="unassigned">unassigned</option><option value="pending">pending</option><option value="complete">complete</option>');
      });
    }

    function switchTab(tab) {
      $('.report-tab').removeClass('border-indigo-600 text-indigo-600').addClass('border-transparent');
      $('.report-tab[data-tab="' + tab + '"]').addClass('border-indigo-600 text-indigo-600').removeClass('border-transparent');
      $('.report-panel').addClass('hidden');
      $('#panel-' + tab).removeClass('hidden');
      currentReport = $('.report-tab[data-tab="' + tab + '"]').data('report');
      currentPage = 1;
      loadReport();
    }

    function loadReport() {
      const q = paramsToQuery({ ...getFilters(), report: currentReport });
      if (currentReport === 'customer_complaint') {
        $('#complaintTableBody').html('<tr><td colspan="7" class="py-8 text-center text-gray-500">Loading...</td></tr>');
      } else if (currentReport === 'sla_compliance') {
        $('#slaTableBody').html('<tr><td colspan="5" class="py-8 text-center text-gray-500">Loading...</td></tr>');
      }

      $.get(API + '?' + q).done(function(data) {
        if (data.error) {
          alert(data.error);
          return;
        }
        if (currentReport === 'customer_complaint') {
          renderComplaint(data);
        } else if (currentReport === 'ticket_volume') {
          renderVolume(data);
        } else if (currentReport === 'resolution_time') {
          renderResolution(data);
        } else if (currentReport === 'sla_compliance') {
          renderSla(data);
        }
      }).fail(function() {
        if (currentReport === 'customer_complaint') {
          $('#complaintTableBody').html('<tr><td colspan="7" class="py-8 text-center text-red-500">Failed to load</td></tr>');
        } else if (currentReport === 'sla_compliance') {
          $('#slaTableBody').html('<tr><td colspan="5" class="py-8 text-center text-red-500">Failed to load</td></tr>');
        }
      });
    }

    function renderComplaint(data) {
      const rows = data.data || [];
      const meta = data.meta || {};
      const html = rows.length === 0
        ? '<tr><td colspan="7" class="py-8 text-center text-gray-500">No records</td></tr>'
        : rows.map(r => `
          <tr class="border-b hover:bg-gray-50">
            <td class="py-3 px-4 text-sm">${escapeHtml(r.customer_name)} (${r.user_id})</td>
            <td class="py-3 px-4 text-sm">${escapeHtml(r.reference_id)}</td>
            <td class="py-3 px-4 text-sm max-w-xs truncate">${escapeHtml(r.title || '')} ${escapeHtml((r.description || '').slice(0, 80))}</td>
            <td class="py-3 px-4 text-sm">${escapeHtml(r.category)}</td>
            <td class="py-3 px-4 text-sm">${escapeHtml(r.priority || '—')}</td>
            <td class="py-3 px-4 text-sm">${escapeHtml(r.date_reported)}</td>
            <td class="py-3 px-4 text-sm">${escapeHtml(r.status)}</td>
          </tr>
        `).join('');
      $('#complaintTableBody').html(html);

      const total = meta.total || 0;
      const pages = Math.max(1, Math.ceil(total / (meta.pageSize || 20)));
      let paginationHtml = '<span class="text-sm text-gray-600">Page ' + meta.page + ' of ' + pages + ' (' + total + ' total)</span>';
      if (pages > 1) {
        paginationHtml += '<div class="flex gap-2">';
        if (meta.page > 1) {
          paginationHtml += '<button type="button" class="complaint-page h-8 px-3 rounded border border-gray-300 text-sm" data-page="' + (meta.page - 1) + '">Previous</button>';
        }
        if (meta.page < pages) {
          paginationHtml += '<button type="button" class="complaint-page h-8 px-3 rounded border border-gray-300 text-sm" data-page="' + (meta.page + 1) + '">Next</button>';
        }
        paginationHtml += '</div>';
      }
      $('#complaintPagination').html(paginationHtml);
    }

    function renderVolume(data) {
      $('#volumeTotal').text('Total: ' + (data.total_tickets || 0));
      const byDay = data.by_day || [];
      const byCategory = data.by_category || [];
      const byPriority = data.by_priority || [];

      if (charts.volumeLine) charts.volumeLine.destroy();
      charts.volumeLine = new Chart(document.getElementById('chartVolumeLine'), {
        type: 'line',
        data: {
          labels: byDay.map(d => d.date),
          datasets: [{ label: 'Tickets', data: byDay.map(d => d.count), borderColor: '#4f46e5', fill: false }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });

      if (charts.volumeCategory) charts.volumeCategory.destroy();
      charts.volumeCategory = new Chart(document.getElementById('chartVolumeCategory'), {
        type: 'doughnut',
        data: {
          labels: byCategory.map(c => c.category || 'Other'),
          datasets: [{ data: byCategory.map(c => c.count), backgroundColor: ['#4f46e5', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0ea5e9'] }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });

      if (charts.volumePriority) charts.volumePriority.destroy();
      charts.volumePriority = new Chart(document.getElementById('chartVolumePriority'), {
        type: 'bar',
        data: {
          labels: byPriority.map(p => p.priority || '—'),
          datasets: [{ label: 'Tickets', data: byPriority.map(p => p.count), backgroundColor: '#4f46e5' }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
      });
    }

    function renderResolution(data) {
      $('#resAvg').text(data.average_hours != null ? data.average_hours + ' h' : '—');
      $('#resMin').text(data.min_hours != null ? data.min_hours + ' h' : '—');
      $('#resMax').text(data.max_hours != null ? data.max_hours + ' h' : '—');

      const byCat = data.by_category || [];
      const byDept = data.by_department || [];

      if (charts.resCategory) charts.resCategory.destroy();
      charts.resCategory = new Chart(document.getElementById('chartResCategory'), {
        type: 'bar',
        data: {
          labels: byCat.map(c => c.category || 'Other'),
          datasets: [{ label: 'Avg hours', data: byCat.map(c => Math.round(c.avg_hours * 10) / 10), backgroundColor: '#059669' }]
        },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
      });

      if (charts.resDepartment) charts.resDepartment.destroy();
      charts.resDepartment = new Chart(document.getElementById('chartResDepartment'), {
        type: 'bar',
        data: {
          labels: byDept.map(d => d.department || 'Other'),
          datasets: [{ label: 'Avg hours', data: byDept.map(d => Math.round(d.avg_hours * 10) / 10), backgroundColor: '#0ea5e9' }]
        },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
      });
    }

    function renderSla(data) {
      const rows = data.data || [];
      const met = data.sla_met_count || 0;
      const breached = data.sla_breached_count || 0;
      const total = data.total || 0;
      $('#slaSummary').text('Total: ' + total + ' | SLA met: ' + met + ' | Breached: ' + breached);

      const html = rows.length === 0
        ? '<tr><td colspan="5" class="py-8 text-center text-gray-500">No records</td></tr>'
        : rows.map(r => `
          <tr class="border-b hover:bg-gray-50">
            <td class="py-3 px-4 text-sm">${escapeHtml(r.reference_id)}</td>
            <td class="py-3 px-4 text-sm">${escapeHtml(r.sla_date)}</td>
            <td class="py-3 px-4 text-sm">${escapeHtml(r.resolved_at)}</td>
            <td class="py-3 px-4 text-sm">${escapeHtml(r.sla_met)}</td>
            <td class="py-3 px-4 text-sm">${r.actual_hours != null ? r.actual_hours : '—'}</td>
          </tr>
        `).join('');
      $('#slaTableBody').html(html);

      if (charts.slaMet) charts.slaMet.destroy();
      charts.slaMet = new Chart(document.getElementById('chartSlaMet'), {
        type: 'doughnut',
        data: {
          labels: ['SLA met', 'Breached'],
          datasets: [{ data: [met, breached], backgroundColor: ['#059669', '#dc2626'] }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });

      const reasons = data.breach_reasons || [];
      if (charts.slaReasons) charts.slaReasons.destroy();
      if (reasons.length === 0) {
        charts.slaReasons = new Chart(document.getElementById('chartSlaReasons'), {
          type: 'bar',
          data: { labels: ['No breach reasons recorded'], datasets: [{ data: [0], backgroundColor: '#9ca3af' }] },
          options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
      } else {
        charts.slaReasons = new Chart(document.getElementById('chartSlaReasons'), {
          type: 'bar',
          data: {
            labels: reasons.map(r => (r.reason || '').slice(0, 30)),
            datasets: [{ label: 'Count', data: reasons.map(r => r.cnt), backgroundColor: '#dc2626' }]
          },
          options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
        });
      }
    }

    function escapeHtml(s) {
      if (s == null) return '';
      const d = document.createElement('div');
      d.textContent = s;
      return d.innerHTML;
    }

    function updateExportCsv() {
      const q = paramsToQuery({ ...getFilters(), report: currentReport, export: 'csv' });
      $('#btnExportCsv').attr('href', API + '?' + q);
    }

    $('#btnExportPdf').on('click', function() {
      const el = document.getElementById('reportContent');
      html2canvas(el, { scale: 2, useCORS: true }).then(function(canvas) {
        const img = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        const w = pdf.internal.pageSize.getWidth();
        const h = (canvas.height * w) / canvas.width;
        pdf.addImage(img, 'PNG', 0, 0, w, h);
        pdf.save('report_' + currentReport + '.pdf');
      });
    });

    $('.report-tab').on('click', function() {
      switchTab($(this).data('tab'));
    });

    $('#btnApply').on('click', function() {
      currentPage = 1;
      loadReport();
      updateExportCsv();
    });

    $('#btnReset').on('click', function() {
      setDefaultDates();
      $('#filterCategory, #filterPriority, #filterDepartment, #filterStatus').val('');
      currentPage = 1;
      loadReport();
      updateExportCsv();
    });

    $(document).on('click', '.complaint-page', function() {
      currentPage = $(this).data('page');
      loadReport();
      updateExportCsv();
    });

    setDefaultDates();
    loadFilterOptions();
    switchTab('complaint');
    updateExportCsv();
  })();
  </script>
</body>
</html>
