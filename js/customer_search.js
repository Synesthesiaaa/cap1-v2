// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

$(document).ready(function() {
    const searchInput = $('#searchInput');
    const customerList = $('#customerList');
    const filterUser = $('#filterUser');
    const filterSLA = $('#filterSLA');
    const filterActivity = $('#filterActivity');
    const visibleCount = $('#visibleCount');
    const totalCount = $('#totalCount');

    // Profile elements
    const profileAvatar = $('#profileAvatar');
    const profileName = $('#profileName');
    const profileType = $('#profileType');
    const profileEmail = $('#profileEmail');
    const profilePhone = $('#profilePhone');
    const profileSLA = $('#profileSLA');
    const profileCSAT = $('#profileCSAT');
    const profileStaff = $('#profileStaff');
    const profileProducts = $('#profileProducts');
    const profileNotes = $('#profileNotes');

    // Global customer data
    let allCustomers = [];
    let selectedUserId = null;
    let currentPagination = null;

    // Global chart references
    let chartPieInstance = null;
    let chartLineInstance = null;
    let chartBarInstance = null;

    // Function to load customers with support for both SLA and Activity filters and pagination
    function loadCustomers(query = '', userType = 'all', slaStatus = 'all', activityStatus = 'all', page = 1) {
        // #region agent log
        fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'customer_search.js:46',message:'loadCustomers entry',data:{query,userType,slaStatus,activityStatus,page},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
        // #endregion
        const requestStartTime = Date.now();
        $.ajax({
            url: '../php/search_customers.php',
            type: 'GET',
            timeout: 120000, // Increased to 120 seconds to handle slow queries when summary table is missing
            data: {
                q: query,
                user_type: userType,
                sla_status: slaStatus,
                activity_status: activityStatus,
                page: page,
                limit: 20
            },
            dataType: 'json',
            success: function(data) {
                // #region agent log
                const requestTime = Date.now() - requestStartTime;
                fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'customer_search.js:59',message:'AJAX success',data:{customersCount:data.customers?.length||0,totalCount:data.pagination?.total_count||0,requestTime},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
                // #endregion
                allCustomers = data.customers;
                currentPagination = data.pagination;

                // Clear any static/fallback customers before rendering dynamic ones
                $('.static-customer').remove();
                renderCustomers(data.customers);

                // Update counts
                $('#visibleCount').text(data.customers.length);
                $('#totalCount').text(data.pagination.total_count);

                // Render pagination controls
                renderPagination(data.pagination);

                // Maintain previous selection if possible
                if (data.customers.length > 0) {
                    if (selectedUserId && data.customers.some(c => c.user_id == selectedUserId)) {
                        // Keep the previous selection
                        setActiveProfile(selectedUserId);
                    } else {
                        // Auto-select first result if no valid previous selection
                        selectedUserId = data.customers[0].user_id;
                        setActiveProfile(selectedUserId);
                    }
                } else {
                    selectedUserId = null;
                    resetProfile();
                }
                // Defer filter options so list appears first (helps with large datasets)
                if (page === 1) {
                    loadFilterOptions(query, userType);
                }
            },
            error: function(xhr, status, error) {
                // #region agent log
                const requestTime = Date.now() - requestStartTime;
                fetch('http://127.0.0.1:1024/ingest/5d971f71-17f9-47f1-b0db-558281b6e241',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'customer_search.js:93',message:'AJAX error',data:{status,error,requestTime,statusCode:xhr.status,responseText:xhr.responseText?.substring(0,200)},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
                // #endregion
                console.error('Error loading customers:', {
                    xhr: xhr,
                    status: status,
                    error: error,
                    url: '../php/search_customers.php',
                    data: {
                        q: query,
                        user_type: userType,
                        sla_status: slaStatus,
                        activity_status: activityStatus,
                        page: page,
                        limit: 20
                    }
                });
                allCustomers = [];
                currentPagination = null;
                
                // Show user-friendly error message
                let errorMsg = 'Unable to load customers. ';
                if (status === 'timeout') {
                    errorMsg += 'The request is taking longer than expected. This may be due to a large dataset. ';
                    errorMsg += 'Please ensure the summary table (tbl_user_ticket_summary) exists by running: php archive/migrations/add_user_ticket_summary.php';
                } else {
                    errorMsg += 'Please try again or contact support if the issue persists.';
                }
                
                renderCustomers([]);
                customerList.html('<div class="p-4 text-center text-red-600">' + errorMsg + '</div>');
                resetProfile();
                $('#totalCount').text('0');
            }
        });
    }

    // Function to render customer list
    function renderCustomers(customers) {
        customerList.empty();
        customers.forEach(customer => {
            const initials = customer.name ? customer.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'NA';
            const statusBadge = getStatusBadge(customer.current_ticket_status);
            const isActive = selectedUserId == customer.user_id ? 'active' : '';



            const item = $(`
                <div class="flex items-center justify-between p-3 rounded border border-slate-100 customer-item ${isActive}" data-user-id="${customer.user_id}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-semibold">${initials}</div>
                        <div>
                            <div class="font-medium">${customer.name || 'N/A'} <span class="text-xs text-gray-500">(${customer.user_type})</span></div>
                            <div class="text-xs text-gray-500">${customer.email || 'N/A'}</div>
                            <div class="text-xs text-gray-400">User ID: ${customer.user_id || 'N/A'}</div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <div>${statusBadge}</div>
                        <div>${customer.ticket_count > 0 ? '<button class="view-ticket-btn px-3 py-1 bg-slate-800 text-white rounded text-sm">View Ticket</button>' : '<span class="px-3 py-1 bg-gray-300 text-gray-600 rounded text-sm">No Tickets</span>'}</div>
                    </div>
                </div>
            `);
            customerList.append(item);
        });
        visibleCount.text(customers.length);
    }

    // Helper function to get SLA badge (user status)
    function getSLABadge(status) {
        const statusMap = {
            'active': '<span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Active</span>',
            'inactive': '<span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">Inactive</span>'
        };
        return statusMap[status] || '<span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full">N/A</span>';
    }

    // Helper function to get ticket status badge
    function getStatusBadge(ticketStatus) {
        const statusMap = {
            'assigning': '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">Assigning</span>',
            'pending': '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Pending</span>',
            'followup': '<span class="text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full">Follow-up</span>',
            'complete': '<span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Complete</span>'
        };
        return statusMap[ticketStatus] || '<span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full">No Tickets</span>';
    }

    // Function to set active profile
    function setActiveProfile(userId) {
        let customer = allCustomers.find(c => c.user_id == userId);
        if (!customer) {
            // Try from static element data
            const staticEl = $(`[data-user-id="${userId}"][data-customer]`).first();
            if (staticEl.length) {
                customer = JSON.parse(staticEl.attr('data-customer'));
            }
        }
        if (!customer) {
            resetProfile();
            return;
        }

        // Update avatar with Dicebear
        const seed = customer.name || 'Default';
        const avatarUrl = `https://api.dicebear.com/7.x/avataaars/svg?seed=${seed}`;
        $('#profileAvatar').attr('src', avatarUrl);

        profileName.text(customer.name || 'N/A');
        profileType.text(ucFirst(customer.user_type || 'external') + ' Customer');
        profileEmail.text(customer.email || 'N/A');
        profilePhone.text(customer.phone || 'N/A');

        // SLA Status - from database (ticket-based: On Track, Approaching, At Risk)
        const slaStatus = customer.sla_status || 'On Track';
        profileSLA.text(slaStatus);
        profileSLA.removeClass('text-green-600 text-amber-600 text-red-600').addClass(
            slaStatus === 'On Track' ? 'text-green-600' :
            slaStatus === 'Approaching' ? 'text-amber-600' :
            slaStatus === 'At Risk' ? 'text-red-600' : 'text-gray-600'
        );

        // CSAT Score - from database (success_rate 0-100 as proxy, displayed as 0-5 scale)
        const csatVal = parseFloat(customer.csat_score) || 0;
        const csatScore = (csatVal / 20).toFixed(1);
        profileCSAT.html(`${csatScore}<span class="text-gray-500 text-sm">/5.0</span>`);

        // Assigned Department - from tbl_user.department_id -> tbl_department
        profileStaff.text(customer.department_name || 'Unassigned');

        // Staff avatar
        const staffSeed = customer.department_name ? customer.department_name.replace(/\s+/g, '') : 'Staff';
        const staffAvatarUrl = `https://api.dicebear.com/7.x/avataaars/svg?seed=${staffSeed}`;
        $('#staffAvatar').attr('src', staffAvatarUrl);

        // Product History - fetch from database (tbl_customer_product + tbl_ticket_product)
        fetch(`../php/get_customer_products.php?user_id=${encodeURIComponent(customer.user_id)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.products && data.products.length > 0) {
                    let productsHtml = '';
                    data.products.forEach(product => {
                        const productName = product.product_name || 'Unknown Product';
                        const purchaseDate = product.purchase_date ? new Date(product.purchase_date).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'N/A';
                        const warrantyEnd = product.warranty_end ? new Date(product.warranty_end).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : (product.warranty_start ? 'See details' : 'N/A');
                        const status = product.status || 'active';
                        const statusClass = status === 'warranty_expired' ? 'text-red-600' : status === 'active' ? 'text-green-600' : 'text-gray-600';
                        const sourceLabel = product.source === 'ticket' ? ' <span class="text-xs text-gray-400">(from ticket)</span>' : '';
                        productsHtml += `
                            <div class="bg-gray-50 p-3 rounded-lg border">
                                <p class="font-medium text-gray-800">${escapeHtml(productName)}${sourceLabel}</p>
                                <p class="text-xs text-gray-500">Purchased: ${purchaseDate} | Warranty: ${warrantyEnd}</p>
                                ${product.model ? `<p class="text-xs text-gray-400">Model: ${escapeHtml(product.model)}</p>` : ''}
                                <p class="text-xs ${statusClass}">Status: ${status}</p>
                            </div>
                        `;
                    });
                    profileProducts.html(productsHtml);
                } else {
                    profileProducts.html('<div class="bg-gray-50 p-3 rounded-lg border text-sm text-gray-500">No products found for this customer.</div>');
                }
            })
            .catch(error => {
                console.error('Error fetching customer products:', error);
                profileProducts.html('<div class="bg-gray-50 p-3 rounded-lg border text-sm text-gray-500">Error loading products.</div>');
            });

        // Notes - from database (tbl_user.notes) with fallback to summary info
        if (customer.notes && String(customer.notes).trim()) {
            profileNotes.text(customer.notes);
        } else {
            const notes = [];
            if (customer.company) notes.push('Company: ' + customer.company);
            if (customer.ticket_count > 0) notes.push('Total tickets: ' + customer.ticket_count);
            if (customer.success_rate) notes.push('Success rate: ' + customer.success_rate + '%');
            notes.push('Joined: ' + (customer.created_at ? new Date(customer.created_at).toLocaleDateString() : 'N/A'));
            profileNotes.text(notes.join('. '));
        }
        profileNotes.attr('data-user-id', customer.user_id);

        // Enable View Ticket button always (per user request)
        const viewTicketBtn = $('#viewTicketBtn');
        viewTicketBtn.prop('disabled', false).removeClass('opacity-50');

        // Keep the matching list row marked active so profile panel buttons (View Ticket, View History, Products) work
        customerList.find('.customer-item').removeClass('active');
        customerList.find('.customer-item[data-user-id="' + userId + '"]').addClass('active');
        // Store selected user id on profile panel so button handler can read it even if .active row is missing
        const panel = document.getElementById('customerProfilePanel');
        if (panel) panel.setAttribute('data-user-id', String(userId));
    }

    // Function to reset profile
    function resetProfile() {
        profileAvatar.attr('src', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Default');
        profileName.text('Select a customer');
        profileType.text('N/A');
        profileEmail.text('N/A');
        profilePhone.text('N/A');
        profileSLA.text('N/A');
        profileCSAT.text('N/A');
        profileStaff.text('N/A');
        profileProducts.html('');
        profileNotes.text('N/A');
        const panel = document.getElementById('customerProfilePanel');
        if (panel) panel.removeAttribute('data-user-id');
    }

    // Helper function to capitalize first letter
    function ucFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Update filter description function
    function updateFilterDescription(slaStatus, activityStatus) {
        const descriptions = {
            'priority': 'Showing customers with 3+ tickets (Priority Clients)',
            'recent': 'Showing customers by last contact date (Most Recent First)',
            'success': 'Showing customers by success rate (Highest First)',
            'active': 'Showing customers with open tickets',
            'overdue': 'Showing customers with overdue tickets',
            'churn_risk': 'Showing customers at risk of churning'
        };

        let description = 'Showing all customers';
        if (slaStatus !== 'all' && descriptions[slaStatus]) {
            description = descriptions[slaStatus];
        } else if (activityStatus !== 'all' && descriptions[activityStatus]) {
            description = descriptions[activityStatus];
        }

        $('#filterDescription').text(description);
    }

    // Debounced search
    const debouncedFilterUpdate = debounce(function() {
        const searchQuery = searchInput.val();
        const userType = filterUser.val();
        const slaValue = filterSLA.val();
        const activityValue = filterActivity.val();

        // Update filter options based on current search context
        loadFilterOptions(searchQuery, userType);

        // Pass both filters to the loadCustomers function
        loadCustomers(searchQuery, userType, slaValue, activityValue);
        updateFilterDescription(slaValue, activityValue);
    }, 300);

    // Event listeners
    searchInput.on('input', debouncedFilterUpdate);
    filterUser.on('change', debouncedFilterUpdate);
    // SLA and Activity filters trigger customer loading directly since they don't change the filter options
    filterSLA.on('change', function() {
        debouncedFilterUpdate();
    });
    filterActivity.on('change', function() {
        debouncedFilterUpdate();
    });

    // Customer item click handler
    customerList.on('click', '.customer-item', function(e) {
        if ($(e.target).hasClass('view-ticket-btn')) return; // Don't trigger profile change if View Ticket is clicked

        const userId = $(this).data('user-id');
        selectedUserId = userId;
        setActiveProfile(userId);

        // Remove active class from all items and add to clicked
        $('.customer-item').removeClass('active');
        $(this).addClass('active');
    });

    // View ticket button handler (inline in list row)
    customerList.on('click', '.view-ticket-btn', function(e) {
        e.stopPropagation();
        const userId = $(this).closest('.customer-item').data('user-id');

        // Get the latest ticket for this user (sorted by date_desc)
        $.ajax({
            url: '../php/tickets_list.php',
            type: 'GET',
            data: { user_id: userId, sort: 'date_desc' },
            dataType: 'json',
            success: function(data) {
                if (data && data.data && data.data.length > 0) {
                    const ticketRef = data.data[0].reference_id; // Get first ticket (latest due to date_desc sort)
                    window.location.href = `cust_ticket.php?ref=${ticketRef}`;
                } else {
                    alert('No tickets found for this customer');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching tickets:', error);
                alert('Error fetching tickets for this customer');
            }
        });
    });

    // Profile panel buttons: View Ticket, View History, Products (use selectedUserId from this module)
    $(document).on('click', '#viewTicketBtn, #viewHistoryBtn, #viewProductsBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const btnId = this.id;
        const pn = profileName.text().trim();
        
        if (!selectedUserId || pn === '' || pn === 'Select a customer') {
            return;
        }
        const userId = String(selectedUserId);

        if (btnId === 'viewTicketBtn') {
            const content = document.getElementById('ticketModalContent');
            if (!content) return;
            content.innerHTML = '<div class="modal-loading-wrap"><div class="modal-spinner"></div><p class="text-sm text-gray-500">Loading ticket…</p></div>';
            if (typeof window.openCustomerModal === 'function') window.openCustomerModal('ticketModal');
            $.getJSON('../php/tickets_list.php?user_id=' + encodeURIComponent(userId) + '&sort=date_desc')
                .done(function(data) {
                    if (data && data.error) {
                        content.innerHTML = '<p class="text-red-600">' + (data.error || 'Unable to load ticket.') + '</p>';
                        return;
                    }
                    if (data && data.data && data.data.length > 0) {
                        const ticket = data.data[0];
                        content.innerHTML = '<p><strong>Ticket:</strong> #' + escapeHtml(ticket.reference_id) + '</p>' +
                            '<p><strong>Title:</strong> ' + escapeHtml(ticket.title || '') + '</p>' +
                            '<p><strong>Status:</strong> ' + escapeHtml(ticket.status || '') + '</p>' +
                            '<p><strong>Priority:</strong> ' + (ticket.priority && ticket.priority.toLowerCase() === 'critical' ? 'Urgent' : (ticket.priority && ticket.priority.toLowerCase() === 'regular' ? 'Medium' : (ticket.priority || ''))) + '</p>' +
                            '<p><strong>Created:</strong> ' + (ticket.created_at ? new Date(ticket.created_at).toLocaleDateString() : '') + '</p>' +
                            '<p class="mt-2"><a href="cust_ticket.php?ref=' + encodeURIComponent(ticket.reference_id) + '" class="text-blue-600 hover:underline">Open ticket →</a></p>';
                    } else {
                        content.innerHTML = '<p>No active tickets found for this customer.</p>';
                    }
                })
                .fail(function() {
                    content.innerHTML = '<p>Error loading ticket information.</p>';
                });
        } else if (btnId === 'viewHistoryBtn') {
            const content = document.getElementById('historyModalContent');
            if (!content) return;
            content.innerHTML = '<li class="list-none"><div class="modal-loading-wrap"><div class="modal-spinner"></div><p class="text-sm text-gray-500">Loading history…</p></div></li>';
            if (typeof window.openCustomerModal === 'function') window.openCustomerModal('historyModal');
            $.getJSON('../php/tickets_list.php?user_id=' + encodeURIComponent(userId) + '&sort=date_desc&pageSize=50')
                .done(function(data) {
                    if (data && data.error) {
                        content.innerHTML = '<li class="text-red-600">' + (data.error || 'Unable to load history.') + '</li>';
                        return;
                    }
                    if (data && data.data && data.data.length > 0) {
                        content.innerHTML = data.data.map(function(t) {
                            return '<li>• ' + escapeHtml(t.title || '') + ' (' + escapeHtml(t.status || '') + ') - ' + (t.created_at ? new Date(t.created_at).toLocaleDateString() : '') + '</li>';
                        }).join('');
                    } else {
                        content.innerHTML = '<li>• No interaction history found</li>';
                    }
                })
                .fail(function() {
                    content.innerHTML = '<li>• Error loading interaction history</li>';
                });
        } else if (btnId === 'viewProductsBtn') {
            const modalContent = document.getElementById('productsModalContent');
            const profileProductsContent = document.getElementById('profileProducts');
            if (!modalContent) return;
            modalContent.innerHTML = '<div class="modal-loading-wrap"><div class="modal-spinner"></div><p class="text-sm text-gray-500">Loading products…</p></div>';
            if (typeof window.openCustomerModal === 'function') window.openCustomerModal('productsModal');
            if (profileProductsContent && profileProductsContent.innerHTML && profileProductsContent.innerHTML.trim() !== '') {
                modalContent.innerHTML = profileProductsContent.innerHTML;
            } else {
                modalContent.innerHTML = '<div class="bg-gray-50 p-3 rounded-lg border"><p class="text-gray-600">No products found for this customer.</p></div>';
            }
        }
    });

    // Function to render pagination controls
    function renderPagination(pagination) {
        const paginationContainer = $('#paginationContainer');
        if (!paginationContainer.length) {
            // Create pagination container if it doesn't exist
            $('#customerList').after('<div id="paginationContainer" class="flex justify-center items-center gap-2 mt-4"></div>');
        }

        const container = $('#paginationContainer');
        container.empty();

        const { page, total_pages, total_count } = pagination;

        if (total_pages <= 1) {
            return; // No pagination needed
        }

        // Previous button
        if (page > 1) {
            container.append(`<button class="pagination-btn px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50" data-page="${page - 1}">Previous</button>`);
        }

        // Page numbers (show max 5 pages around current)
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(total_pages, startPage + 4);

        for (let p = startPage; p <= endPage; p++) {
            const isActive = p === page ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50';
            container.append(`<button class="pagination-btn px-3 py-2 border border-gray-300 rounded ${isActive}" data-page="${p}">${p}</button>`);
        }

        // Next button
        if (page < total_pages) {
            container.append(`<button class="pagination-btn px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50" data-page="${page + 1}">Next</button>`);
        }

        // Page info
        container.append(`<span class="text-sm text-gray-500 ml-4">Page ${page} of ${total_pages} (${total_count} total)</span>`);
    }

    // Pagination click handler
    $(document).on('click', '.pagination-btn', function() {
        const page = $(this).data('page');
        const slaValue = filterSLA.val();
        const activityValue = filterActivity.val();
        loadCustomers(searchInput.val(), filterUser.val(), slaValue, activityValue, page);
    });

    // Function to load filter options from database, with search context
    function loadFilterOptions(searchQuery = '', userType = 'all') {
        $.ajax({
            url: '../php/get_filter_options.php',
            type: 'GET',
            data: {
                type: 'all',
                q: searchQuery,
                user_type: userType
            },
            dataType: 'json',
            success: function(data) {
                // Populate user types
                if (data.user_types) {
                    populateSelect($('#filterUser'), data.user_types);
                }

                // Populate SLA statuses
                if (data.sla_statuses) {
                    populateSelect($('#filterSLA'), data.sla_statuses);
                }

                // Populate activity statuses
                if (data.activity_statuses) {
                    populateSelect($('#filterActivity'), data.activity_statuses);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading filter options:', error);
            }
        });
    }

    // Helper function to populate select elements while preserving current selection
    function populateSelect($select, options) {
        const currentValue = $select.val(); // Store current selection
        $select.empty(); // Clear existing options

        options.forEach(option => {
            const selected = (option.value === currentValue) ? ' selected="selected"' : '';
            $select.append(`<option value="${option.value}"${selected}>${option.label}</option>`);
        });

        // If previous selection is no longer available, fall back to 'all'
        if ($select.val() === null && options.length > 0) {
            $select.val('all');
        }
    }

    // Save notes on blur (when user edits and leaves the field)
    profileNotes.on('blur', function() {
        const uid = $(this).attr('data-user-id');
        if (!uid || $(this).attr('contenteditable') === 'false') return;
        const notes = $(this).text().trim();
        $.ajax({
            url: '../php/save_customer_notes.php',
            type: 'POST',
            data: { user_id: uid, notes: notes },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    if (typeof window.showToast === 'function') window.showToast('Notes saved.');
                } else if (res.error) {
                    console.warn('Notes save:', res.error);
                }
            },
            error: function() { console.warn('Notes save failed'); }
        });
    });

    // Load all customers initially - ensure we show ALL users (including admins)
    loadCustomers('', 'all', 'all', 'all', 1);
});
