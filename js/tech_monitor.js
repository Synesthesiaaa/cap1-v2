/* ============================================================
   Technician Ticket Monitor JS
   - Handles ticket loading
   - Filters & search
   - Pagination
   - SLA countdown
   - Status + Priority color classes
============================================================ */

const API_BASE = "../php/tickets_list.php";

let currentPage = 1;
let pageSize = 10;
let autoRefreshInterval = 30000;
let lastChecksum = null;
let slaTimerInterval = null;

document.addEventListener("DOMContentLoaded", () => {
    setupDropdown("statusDropdown", "filterStatus");
    setupDropdown("priorityDropdown", "filterPriority");
    setupDropdown("typeDropdown", "filterType");
    setupDropdown("sortDropdown", "filterSort");


    document.getElementById("clearFiltersBtn").addEventListener("click", clearFilters);
    document.getElementById("searchInput").addEventListener("input", () => {
        currentPage = 1;
        loadTickets();
    });

    // Default to showing assigned tickets for the technician
    loadTickets({ assigned_only: 1 });
    setInterval(loadTickets, autoRefreshInterval);
});

/* ============================================================
   DROPDOWNS
============================================================ */
function setupDropdown(dropdownId, hiddenId) {
    const container = document.getElementById(dropdownId);
    if (!container) return;

    const dropdown = container.querySelector(".dropdown");
    const list = container.querySelector(".dropdown-list");
    const selected = container.querySelector(".selected");
    const hidden = document.getElementById(hiddenId);

    dropdown.addEventListener("click", () => {
        list.classList.toggle("visible");
    });

    list.querySelectorAll("li").forEach(li => {
        li.addEventListener("click", () => {
            hidden.value = li.dataset.value;
            selected.textContent = li.innerText;

            list.classList.remove("visible");
            currentPage = 1;
            loadTickets();
        });
    });
}

/* ============================================================
   FILTER RESET
============================================================ */
function clearFilters() {
    document.getElementById("searchInput").value = "";
    document.getElementById("filterStatus").value = "";
    document.getElementById("filterPriority").value = "";

    currentPage = 1;
    loadTickets();
}

/* ============================================================
   LOAD TICKETS (MAIN FUNCTION)
============================================================ */
function loadTickets(extraFilters = {}) {

    // Base filters from dropdowns
    const baseParams = {
        search: document.getElementById("searchInput").value.trim(),
        status: document.getElementById("filterStatus").value,
        priority: document.getElementById("filterPriority").value,
        type: document.getElementById("filterType").value,
        sort: document.getElementById("filterSort").value,
        page: currentPage,
        pageSize: pageSize
    };

    // Merge summary card filters (overrides base)
    const merged = { 
        ...baseParams,
        ...extraFilters 
    };

    const params = new URLSearchParams(merged);

    fetch(`${API_BASE}?${params}`)
        .then(r => r.json())
        .then(data => {
            if (!data || !data.data) return;

            const newChecksum = JSON.stringify(data.meta);
            if (newChecksum === lastChecksum) {
                startSLATimer();
                return;
            }
            lastChecksum = newChecksum;

            renderTable(data.data);
            renderPagination(data.meta.total, data.meta.page, data.meta.pageSize);
            startSLATimer();
        })
        .catch(err => console.error("Ticket load error:", err));
}


/* ============================================================
   TABLE RENDER
============================================================ */
function renderTable(rows) {
    const tbody = document.getElementById("ticketsBody");
    tbody.innerHTML = "";

    rows.forEach(r => {
        const tr = document.createElement("tr");

        tr.innerHTML = `
            <td>${safe(r.type)}</td>
            <td>${safe(r.reference_id)}</td>
            <td>${safe(r.title)}</td>
            <td>${safe(r.requester_name)}</td>
            <td>${safe(r.technician_name ?? "Unassigned")}</td>

            <td>
                <span class="status ${formatStatus(r.status)}">${safe(r.status)}</span>
            </td>

            <td>
                <span class="priority ${formatPriority(r.priority)}">${safe(r.priority && r.priority.toLowerCase() === 'critical' ? 'Urgent' : (r.priority && r.priority.toLowerCase() === 'regular' ? 'Medium' : r.priority))}</span>
            </td>

            <td>
                <span class="sla-countdown" data-sla="${safe(r.sla_date)}">--</span>
            </td>

            <td>
                <button class="btn-details" data-ref="${safe(r.reference_id)}">View</button>
            </td>
        `;

        tbody.appendChild(tr);
    });

    // Attach detail handlers
    document.querySelectorAll(".btn-details").forEach(btn => {
        btn.addEventListener("click", e => {
            const ref = e.currentTarget.dataset.ref;
            window.location.href = `view_ticket.php?ref=${encodeURIComponent(ref)}`;
        });
    });
}

/* ============================================================
   STATUS & PRIORITY FORMATTERS
============================================================ */
function formatStatus(s) {
    if (!s) return "";
    return s.replace(/\s+/g, "").toLowerCase(); // normalize
}

function formatPriority(p) {
    if (!p) return "";
    return p.replace(/\s+/g, "").toLowerCase();
}

/* ============================================================
   PAGINATION
============================================================ */
function renderPagination(total, page, pageSize) {
    const pages = Math.max(1, Math.ceil(total / pageSize));
    const container = document.getElementById("pagination");

    container.innerHTML = "";

    const prev = document.createElement("button");
    prev.textContent = "Previous";
    prev.disabled = page <= 1;
    prev.onclick = () => {
        currentPage = Math.max(1, page - 1);
        loadTickets();
    };
    container.appendChild(prev);

    const start = Math.max(1, page - 3);
    const end = Math.min(pages, page + 3);

    for (let i = start; i <= end; i++) {
        const btn = document.createElement("button");
        btn.textContent = i;
        btn.disabled = i === page;
        btn.onclick = () => {
            currentPage = i;
            loadTickets();
        };
        container.appendChild(btn);
    }

    const next = document.createElement("button");
    next.textContent = "Next";
    next.disabled = page >= pages;
    next.onclick = () => {
        currentPage = Math.min(pages, page + 1);
        loadTickets();
    };
    container.appendChild(next);
}

/* ============================================================
   SLA COUNTDOWN TIMER
============================================================ */
function startSLATimer() {
    if (slaTimerInterval) clearInterval(slaTimerInterval);
    updateSLAs();
    slaTimerInterval = setInterval(updateSLAs, 1000);
}

function updateSLAs() {
    document.querySelectorAll(".sla-countdown").forEach(el => {
        const iso = el.dataset.sla;
        if (!iso) {
            el.textContent = "--";
            return;
        }

        // Parse the SLA date - if it's just a date (YYYY-MM-DD), treat it as end of day in local timezone
        let target;
        if (iso.match(/^\d{4}-\d{2}-\d{2}$/)) {
            // Date only format - set to end of day (23:59:59) in local timezone
            const dateParts = iso.split('-');
            target = new Date(parseInt(dateParts[0]), parseInt(dateParts[1]) - 1, parseInt(dateParts[2]), 23, 59, 59);
        } else {
            // Full datetime format
            target = new Date(iso);
        }

        const now = new Date();
        let diff = target - now;

        let overdue = diff < 0;
        diff = Math.abs(diff);

        const hrs = Math.floor(diff / (1000 * 60 * 60));
        const mins = Math.floor((diff / (1000 * 60)) % 60);
        const secs = Math.floor((diff / 1000) % 60);

        // Format display
        if (overdue) {
            el.textContent = `-${hrs}h ${mins}m`;
            el.classList.add("danger");
        } else {
            // Show hours and minutes, or just minutes if less than an hour
            if (hrs > 0) {
                el.textContent = `${hrs}h ${mins}m`;
            } else {
                el.textContent = `${mins}m ${secs}s`;
            }
            
            // Add danger class if less than 1 hour remaining
            if (hrs < 1) {
                el.classList.add("danger");
            } else {
                el.classList.remove("danger");
            }
        }
    });
}

/* ============================================================
   UTILS
============================================================ */
function safe(v) {
    if (!v) return "";
    return String(v).replace(/[&<>"]/g, m => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;"
    }[m]));
}
