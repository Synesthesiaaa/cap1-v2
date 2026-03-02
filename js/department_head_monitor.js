// Department Head Monitor JavaScript

let currentSortDept = {
    column: 'created_at',
    direction: 'DESC'
};

document.addEventListener('DOMContentLoaded', function() {
    loadTickets();
    loadSummary();

    // Sorting functionality
    initializeSorting();
});

function initializeSorting() {
    const headers = document.querySelectorAll('.sortable');
    headers.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.dataset.column;
            let direction = 'ASC';

            // Toggle direction if same column is clicked
            if (currentSortDept.column === column) {
                direction = currentSortDept.direction === 'ASC' ? 'DESC' : 'ASC';
            }

            currentSortDept.column = column;
            currentSortDept.direction = direction;

            updateSortArrows();
            loadTickets(1); // Reset to first page when sorting
        });
    });
}

function updateSortArrows() {
    const headers = document.querySelectorAll('.sortable');
    headers.forEach(header => {
        const column = header.dataset.column;
        const arrow = header.querySelector('.sort-arrow');

        if (column === currentSortDept.column) {
            arrow.textContent = currentSortDept.direction === 'ASC' ? '↑' : '↓';
            arrow.style.color = '#2563eb';
        } else {
            arrow.textContent = '↕';
            arrow.style.color = '#6b7280';
        }
    });
}

function loadSummary() {
    fetch('../php/get_department_head_summary.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('openCount').textContent = data.open || 0;
            document.getElementById('pendingCount').textContent = data.pending || 0;
            document.getElementById('assignedCount').textContent = data.assigned || 0;
            document.getElementById('completeCount').textContent = data.complete || 0;
        })
        .catch(error => console.error('Error loading summary:', error));
}

function loadTickets(page = 1) {
    const params = new URLSearchParams({
        page: page,
        sort: currentSortDept.column,
        direction: currentSortDept.direction
    });

    fetch(`../php/get_department_head_tickets.php?${params}`)
        .then(response => response.json())
        .then(data => {
            renderTickets(data.tickets);
            renderPagination(data.pagination);
        })
        .catch(error => console.error('Error loading tickets:', error));
}

function renderTickets(tickets) {
    const tbody = document.getElementById('ticketsBody');

    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-6 text-gray-500">No tickets found</td></tr>';
        return;
    }

    tbody.innerHTML = tickets.map(ticket => `
        <tr class="border-b hover:bg-gray-50">
            <td class="py-3 px-2">${ticket.reference_id}</td>
            <td class="py-3 px-2">${ticket.title}</td>
            <td class="py-3 px-2">${ticket.category || 'N/A'}</td>
            <td class="py-3 px-2">${ticket.type || 'N/A'}</td>
            <td class="py-3 px-2">${ticket.user_name}</td>
            <td class="py-3 px-2">
                <span class="px-2 py-1 rounded text-xs font-medium ${getUrgencyClass(ticket.urgency)}">
                    ${ticket.urgency.charAt(0).toUpperCase() + ticket.urgency.slice(1)}
                </span>
            </td>
            <td class="py-3 px-2">
                <span class="px-2 py-1 rounded text-xs font-medium ${getStatusClass(ticket.status)}">
                    ${ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1)}
                </span>
            </td>
            <td class="py-3 px-2">${new Date(ticket.created_at).toLocaleDateString()}</td>
            <td class="py-3 px-2">
                <span class="ticket-progress text-xs text-gray-600" data-ref="${ticket.reference_id}">--</span>
            </td>
            <td class="py-3 px-2">
                <button onclick="openTicketView('${ticket.reference_id}')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">View</button>
            </td>
        </tr>
    `).join('');

    loadProgressForVisibleRows();
}

function getUrgencyClass(urgency) {
    const classes = {
        'low': 'bg-gray-100 text-gray-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'high': 'bg-orange-100 text-orange-800',
        'critical': 'bg-red-100 text-red-800'
    };
    return classes[urgency] || 'bg-gray-100 text-gray-800';
}

function getStatusClass(status) {
    const classes = {
        'unassigned': 'bg-gray-100 text-gray-800',
        'pending': 'bg-blue-100 text-blue-800',
        'followup': 'bg-yellow-100 text-yellow-800',
        'complete': 'bg-green-100 text-green-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function renderPagination(pagination) {
    const paginationDiv = document.getElementById('pagination');
    if (!pagination || pagination.totalPages <= 1) {
        paginationDiv.innerHTML = '';
        return;
    }

    const { currentPage, totalPages } = pagination;

    let html = '';

    // Previous button
    if (currentPage > 1) {
        html += `<button onclick="loadTickets(${currentPage - 1})" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Previous</button>`;
    }

    // Calculate page range to show (max 10 pages)
    const maxPagesToShow = 10;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

    // Adjust start if we went too close to the end
    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    // Show first page if not in range
    if (startPage > 1) {
        html += `<button onclick="loadTickets(1)" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">1</button>`;
        if (startPage > 2) {
            html += `<span class="px-2">...</span>`;
        }
    }

    // Show page numbers
    for (let i = startPage; i <= endPage; i++) {
        html += `<button onclick="loadTickets(${i})" class="px-3 py-1 rounded ${i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}">${i}</button>`;
    }

    // Show last page if not in range
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<span class="px-2">...</span>`;
        }
        html += `<button onclick="loadTickets(${totalPages})" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">${totalPages}</button>`;
    }

    // Next button
    if (currentPage < totalPages) {
        html += `<button onclick="loadTickets(${currentPage + 1})" class="px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Next</button>`;
    }

    paginationDiv.innerHTML = html;
}

function openTicketView(ref) {
    window.location.href = `cust_ticket.php?ref=${encodeURIComponent(ref)}`;
}

async function loadProgressForVisibleRows() {
    const els = Array.from(document.querySelectorAll('.ticket-progress[data-ref]'));
    if (!els.length) return;

    const refs = els.map((el) => el.dataset.ref).filter(Boolean);
    if (!refs.length) return;

    try {
        const res = await fetch(`../php/get_ticket_progress.php?refs=${encodeURIComponent(refs.join(','))}`, { cache: 'no-store' });
        const payload = await res.json();
        if (!res.ok || !payload.ok) return;

        els.forEach((el) => {
            const p = payload.data?.[el.dataset.ref];
            if (!p) {
                el.textContent = '0/0 (0%)';
                return;
            }
            el.textContent = `${p.completed}/${p.total} (${p.percent}%)`;
        });
    } catch (err) {
        console.error('Error loading checklist progress:', err);
    }
}
