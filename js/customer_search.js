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
        console.log('Loading customers with query:', query, 'userType:', userType, 'slaStatus:', slaStatus, 'activityStatus:', activityStatus, 'page:', page);

        $.ajax({
            url: '../php/search_customers.php',
            type: 'GET',
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
                console.log('Customers loaded successfully:', data);
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
                renderCustomers([]);
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

        // SLA Status - use real SLA status from database
        const slaStatus = customer.sla_status || 'On Track';
        profileSLA.text(slaStatus);
        // Set color based on SLA status
        profileSLA.removeClass('text-green-600 text-red-600').addClass(
            slaStatus === 'On Track' ? 'text-green-600' :
            slaStatus === 'At Risk' ? 'text-red-600' : ''
        );

        // CSAT Score - use real CSAT score from database, convert to 5-point scale
        const csatScore = customer.csat_score ? (customer.csat_score / 20).toFixed(1) : '0.0';
        profileCSAT.html(`${csatScore}<span class="text-gray-500 text-sm">/5.0</span>`);

        // Assigned Staff - show department name or placeholder
        profileStaff.text(customer.department_name || 'Unassigned');

        // Staff avatar
        const staffSeed = customer.department_name ? customer.department_name.replace(/\s+/g, '') : 'Staff';
        const staffAvatarUrl = `https://api.dicebear.com/7.x/avataaars/svg?seed=${staffSeed}`;
        $('#staffAvatar').attr('src', staffAvatarUrl);

        // Product history - fetch real data from API
        fetch(`../php/get_customer_products.php?user_id=${customer.user_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.products && data.products.length > 0) {
                    let productsHtml = '';
                    data.products.forEach(product => {
                        const productName = product.product_name || 'Unknown Product';
                        const purchaseDate = product.purchase_date ? new Date(product.purchase_date).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'N/A';
                        const warrantyEnd = product.warranty_end ? new Date(product.warranty_end).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'N/A';
                        const status = product.status || 'active';
                        const statusClass = status === 'warranty_expired' ? 'text-red-600' : status === 'active' ? 'text-green-600' : 'text-gray-600';
                        
                        productsHtml += `
                            <div class="bg-gray-50 p-3 rounded-lg border">
                                <p class="font-medium text-gray-800">${escapeHtml(productName)}</p>
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

        // Notes - show company and other info
        const notes = [];
        if (customer.company) notes.push(`Company: ${customer.company}`);
        if (customer.ticket_count > 0) notes.push(`Total tickets: ${customer.ticket_count}`);
        if (customer.success_rate) notes.push(`Success rate: ${customer.success_rate}%`);
        notes.push(`Joined: ${new Date(customer.created_at).toLocaleDateString()}`);
        profileNotes.text(notes.join('. '));

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
        profileAvatar.text('NA');
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
        console.log('Loading filter options from database with context:', { searchQuery, userType });

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
                console.log('Filter options loaded successfully:', data);

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
                // Fallback: use static options if database fails
                console.log('Using fallback static filter options');
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

    // Load all customers initially - ensure we show ALL users (including admins/evaluators)
    loadCustomers('', 'all', 'all', 'all', 1);
});
