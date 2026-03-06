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
    const activeProductsCount = $('#activeProductsCount');

    let allCustomers = [];
    let selectedUserId = null;
    let currentPagination = null;
    let customerXhr = null;
    let filterXhr = null;
    let customerRequestToken = 0;
    let filterRequestToken = 0;
    let productRequestToken = 0;

    // Cache per user and scope: "<userId>|active" / "<userId>|all"
    const productCache = new Map();

    function getCurrentFilters() {
        return {
            searchQuery: searchInput.val() || '',
            userType: filterUser.val() || 'all',
            slaValue: filterSLA.val() || 'all',
            activityValue: filterActivity.val() || 'all'
        };
    }

    function loadCustomers(query = '', userType = 'all', slaStatus = 'all', activityStatus = 'all', page = 1) {
        customerRequestToken += 1;
        const token = customerRequestToken;

        if (customerXhr && customerXhr.readyState !== 4) {
            customerXhr.abort();
        }

        customerXhr = $.ajax({
            url: '../php/search_customers.php',
            type: 'GET',
            timeout: 30000,
            data: {
                q: query,
                user_type: userType,
                sla_status: slaStatus,
                activity_status: activityStatus,
                page: page,
                limit: 20
            },
            dataType: 'json'
        }).done(function(data) {
            if (token !== customerRequestToken) return;

            allCustomers = Array.isArray(data.customers) ? data.customers : [];
            currentPagination = data.pagination || null;

            $('.static-customer').remove();
            renderCustomers(allCustomers);

            visibleCount.text(allCustomers.length);
            totalCount.text((currentPagination && currentPagination.total_count) ? currentPagination.total_count : 0);
            renderPagination(currentPagination || { page: 1, total_pages: 1, total_count: allCustomers.length });

            if (allCustomers.length > 0) {
                if (selectedUserId && allCustomers.some(c => String(c.user_id) === String(selectedUserId))) {
                    setActiveProfile(selectedUserId);
                } else {
                    selectedUserId = allCustomers[0].user_id;
                    setActiveProfile(selectedUserId);
                }
            } else {
                selectedUserId = null;
                resetProfile();
            }
        }).fail(function(xhr, status, error) {
            if (status === 'abort') return;
            if (token !== customerRequestToken) return;

            console.error('Error loading customers:', { status, error, response: xhr.responseText });
            allCustomers = [];
            currentPagination = null;

            let errorMsg = 'Unable to load customers. ';
            if (status === 'timeout') {
                errorMsg += 'Request timed out. Ensure summary table and indexes are up to date.';
            } else {
                errorMsg += 'Please try again.';
            }
            customerList.html('<div class="p-4 text-center text-red-600">' + escapeHtml(errorMsg) + '</div>');
            visibleCount.text('0');
            totalCount.text('0');
            resetProfile();
        });
    }

    function renderCustomers(customers) {
        if (!customers || customers.length === 0) {
            customerList.html('<div class="p-4 text-center text-gray-500 border rounded">No customers found for the current filters.</div>');
            return;
        }

        const html = customers.map(customer => {
            const initials = customer.name ? customer.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'NA';
            const statusBadge = getStatusBadge(customer.current_ticket_status);
            const isActive = String(selectedUserId) === String(customer.user_id) ? 'active' : '';
            const userLabel = escapeHtml(customer.user_type || 'n/a');
            const name = escapeHtml(customer.name || 'N/A');
            const email = escapeHtml(customer.email || 'N/A');
            const userId = escapeHtml(customer.user_id || 'N/A');

            return `
                <div class="flex items-center justify-between p-3 rounded border border-slate-100 customer-item ${isActive}" data-user-id="${customer.user_id}">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-semibold">${escapeHtml(initials)}</div>
                        <div class="min-w-0">
                            <div class="font-medium truncate">${name} <span class="text-xs text-gray-500">(${userLabel})</span></div>
                            <div class="text-xs text-gray-500 truncate">${email}</div>
                            <div class="text-xs text-gray-400">User ID: ${userId}</div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <div>${statusBadge}</div>
                        <div>${Number(customer.ticket_count || 0) > 0 ? '<button class="view-ticket-btn px-3 py-1 bg-slate-800 text-white rounded text-sm">View Ticket</button>' : '<span class="px-3 py-1 bg-gray-300 text-gray-600 rounded text-sm">No Tickets</span>'}</div>
                    </div>
                </div>
            `;
        }).join('');

        customerList.html(html);
    }

    function getStatusBadge(ticketStatus) {
        const statusMap = {
            assigning: '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">Assigning</span>',
            pending: '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Pending</span>',
            followup: '<span class="text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full">Follow-up</span>',
            complete: '<span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Complete</span>'
        };
        return statusMap[ticketStatus] || '<span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full">No Tickets</span>';
    }

    function ucFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function formatProductCard(product) {
        const productName = escapeHtml(product.product_name || 'Unknown Product');
        const purchaseDate = product.purchase_date ? new Date(product.purchase_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
        const warrantyEnd = product.warranty_end ? new Date(product.warranty_end).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
        const status = String(product.status || 'active');
        const model = product.model ? `<p class="text-xs text-gray-400">Model: ${escapeHtml(product.model)}</p>` : '';
        const sourceLabel = product.source === 'ticket' ? '<span class="text-xs text-gray-400">(ticket-linked)</span>' : '<span class="text-xs text-gray-400">(registered)</span>';
        const statusClass = status === 'active'
            ? 'text-green-700 bg-green-100'
            : (status === 'warranty_expired' ? 'text-red-700 bg-red-100' : 'text-gray-700 bg-gray-200');

        return `
            <div class="bg-gray-50 p-3 rounded-lg border">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-medium text-gray-800">${productName}</p>
                    ${sourceLabel}
                </div>
                <p class="text-xs text-gray-500">Purchased: ${escapeHtml(purchaseDate)} | Warranty End: ${escapeHtml(warrantyEnd)}</p>
                ${model}
                <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded-full ${statusClass}">${escapeHtml(status)}</span>
            </div>
        `;
    }

    function setProductsLoading(targetEl, message) {
        targetEl.html(`<div class="bg-gray-50 p-3 rounded-lg border text-sm text-gray-500">${escapeHtml(message)}</div>`);
    }

    function updateActiveProductCount(count) {
        if (!activeProductsCount.length) return;
        activeProductsCount.text(String(Number(count || 0)));
    }

    function fetchCustomerProducts(userId, scope = 'active') {
        const key = `${userId}|${scope}`;
        if (productCache.has(key)) {
            return Promise.resolve(productCache.get(key));
        }

        const url = `../php/get_customer_products.php?user_id=${encodeURIComponent(userId)}&scope=${encodeURIComponent(scope)}`;
        return fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data || !data.success) {
                    throw new Error(data && data.error ? data.error : 'Failed to load products');
                }
                productCache.set(key, data);
                return data;
            });
    }

    function setActiveProfile(userId) {
        let customer = allCustomers.find(c => String(c.user_id) === String(userId));
        if (!customer) {
            const staticEl = $(`[data-user-id="${userId}"][data-customer]`).first();
            if (staticEl.length) {
                customer = JSON.parse(staticEl.attr('data-customer'));
            }
        }
        if (!customer) {
            resetProfile();
            return;
        }

        const seed = customer.name || 'Default';
        const avatarUrl = `https://api.dicebear.com/7.x/avataaars/svg?seed=${encodeURIComponent(seed)}`;
        profileAvatar.attr('src', avatarUrl);

        profileName.text(customer.name || 'N/A');
        profileType.text(ucFirst(customer.user_type || 'external') + ' Customer');
        profileEmail.text(customer.email || 'N/A');
        profilePhone.text(customer.phone || 'N/A');

        const slaStatus = customer.sla_status || 'On Track';
        profileSLA.text(slaStatus);
        profileSLA.removeClass('text-green-600 text-amber-600 text-red-600 text-gray-600').addClass(
            slaStatus === 'On Track' ? 'text-green-600' :
            slaStatus === 'Approaching' ? 'text-amber-600' :
            slaStatus === 'At Risk' ? 'text-red-600' : 'text-gray-600'
        );

        const csatVal = parseFloat(customer.csat_score) || 0;
        profileCSAT.html(`${(csatVal / 20).toFixed(1)}<span class="text-gray-500 text-sm">/5.0</span>`);
        profileStaff.text(customer.department_name || 'Unassigned');

        const staffSeed = customer.department_name ? customer.department_name.replace(/\s+/g, '') : 'Staff';
        $('#staffAvatar').attr('src', `https://api.dicebear.com/7.x/avataaars/svg?seed=${encodeURIComponent(staffSeed)}`);

        updateActiveProductCount(0);
        setProductsLoading(profileProducts, 'Loading active products...');
        productRequestToken += 1;
        const localProductToken = productRequestToken;
        fetchCustomerProducts(customer.user_id, 'active')
            .then(data => {
                if (localProductToken !== productRequestToken) return;
                const products = Array.isArray(data.products) ? data.products : [];
                const meta = data.meta || {};

                updateActiveProductCount(meta.active_count ?? products.length);
                if (products.length === 0) {
                    profileProducts.html('<div class="bg-gray-50 p-3 rounded-lg border text-sm text-gray-500">No active products for this customer.</div>');
                    return;
                }
                profileProducts.html(products.map(formatProductCard).join(''));
            })
            .catch(error => {
                if (localProductToken !== productRequestToken) return;
                console.error('Error fetching active products:', error);
                profileProducts.html('<div class="bg-gray-50 p-3 rounded-lg border text-sm text-gray-500">Error loading active products.</div>');
            });

        if (customer.notes && String(customer.notes).trim()) {
            profileNotes.text(customer.notes);
        } else {
            const notes = [];
            if (customer.company) notes.push('Company: ' + customer.company);
            if (Number(customer.ticket_count || 0) > 0) notes.push('Total tickets: ' + customer.ticket_count);
            if (customer.success_rate) notes.push('Success rate: ' + customer.success_rate + '%');
            notes.push('Joined: ' + (customer.created_at ? new Date(customer.created_at).toLocaleDateString() : 'N/A'));
            profileNotes.text(notes.join('. '));
        }
        profileNotes.attr('data-user-id', customer.user_id);

        $('#viewTicketBtn').prop('disabled', false).removeClass('opacity-50');
        customerList.find('.customer-item').removeClass('active');
        customerList.find('.customer-item[data-user-id="' + userId + '"]').addClass('active');
        const panel = document.getElementById('customerProfilePanel');
        if (panel) panel.setAttribute('data-user-id', String(userId));
    }

    function resetProfile() {
        profileAvatar.attr('src', 'https://api.dicebear.com/7.x/avataaars/svg?seed=Default');
        profileName.text('Select a customer');
        profileType.text('N/A');
        profileEmail.text('N/A');
        profilePhone.text('N/A');
        profileSLA.text('N/A');
        profileCSAT.text('N/A');
        profileStaff.text('N/A');
        profileProducts.html('<div class="bg-gray-50 p-3 rounded-lg border text-sm text-gray-500">Select a customer to view active products.</div>');
        profileNotes.text('N/A');
        updateActiveProductCount(0);
        const panel = document.getElementById('customerProfilePanel');
        if (panel) panel.removeAttribute('data-user-id');
    }

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

    function updateFilterDescription(slaStatus, activityStatus) {
        const descriptions = {
            priority: 'Showing customers with 3+ tickets (Priority Clients)',
            recent: 'Showing customers by last contact date (Most Recent First)',
            success: 'Showing customers by success rate (Highest First)',
            active: 'Showing customers with open tickets',
            overdue: 'Showing customers with overdue tickets',
            churn_risk: 'Showing customers at risk of churning'
        };

        let description = 'Showing all customers';
        if (slaStatus !== 'all' && descriptions[slaStatus]) {
            description = descriptions[slaStatus];
        } else if (activityStatus !== 'all' && descriptions[activityStatus]) {
            description = descriptions[activityStatus];
        }
        $('#filterDescription').text(description);
    }

    function reloadCustomersPageOne() {
        const { searchQuery, userType, slaValue, activityValue } = getCurrentFilters();
        loadCustomers(searchQuery, userType, slaValue, activityValue, 1);
        updateFilterDescription(slaValue, activityValue);
    }

    const debouncedReloadCustomers = debounce(reloadCustomersPageOne, 300);
    const debouncedFilterOptions = debounce(function() {
        const { searchQuery, userType } = getCurrentFilters();
        loadFilterOptions(searchQuery, userType);
    }, 500);

    searchInput.on('input', function() {
        debouncedReloadCustomers();
        debouncedFilterOptions();
    });

    filterUser.on('change', function() {
        reloadCustomersPageOne();
        debouncedFilterOptions();
    });

    filterSLA.on('change', reloadCustomersPageOne);
    filterActivity.on('change', reloadCustomersPageOne);

    customerList.on('click', '.customer-item', function(e) {
        if ($(e.target).hasClass('view-ticket-btn')) return;
        const userId = $(this).data('user-id');
        selectedUserId = userId;
        setActiveProfile(userId);
        $('.customer-item').removeClass('active');
        $(this).addClass('active');
    });

    customerList.on('click', '.view-ticket-btn', function(e) {
        e.stopPropagation();
        const userId = $(this).closest('.customer-item').data('user-id');
        $.ajax({
            url: '../php/tickets_list.php',
            type: 'GET',
            data: { user_id: userId, sort: 'date_desc' },
            dataType: 'json',
            success: function(data) {
                if (data && data.data && data.data.length > 0) {
                    const ticketRef = data.data[0].reference_id;
                    window.location.href = `cust_ticket.php?ref=${encodeURIComponent(ticketRef)}`;
                } else {
                    alert('No tickets found for this customer');
                }
            },
            error: function() {
                alert('Error fetching tickets for this customer');
            }
        });
    });

    $(document).on('click', '#viewTicketBtn, #viewHistoryBtn, #viewProductsBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const btnId = this.id;
        const pn = profileName.text().trim();
        if (!selectedUserId || pn === '' || pn === 'Select a customer') return;
        const userId = String(selectedUserId);

        if (btnId === 'viewTicketBtn') {
            const content = document.getElementById('ticketModalContent');
            if (!content) return;
            content.innerHTML = '<div class="modal-loading-wrap"><div class="modal-spinner"></div><p class="text-sm text-gray-500">Loading ticket...</p></div>';
            if (typeof window.openCustomerModal === 'function') window.openCustomerModal('ticketModal');
            $.getJSON('../php/tickets_list.php?user_id=' + encodeURIComponent(userId) + '&sort=date_desc')
                .done(function(data) {
                    if (data && data.error) {
                        content.innerHTML = '<p class="text-red-600">' + escapeHtml(data.error || 'Unable to load ticket.') + '</p>';
                        return;
                    }
                    if (data && data.data && data.data.length > 0) {
                        const ticket = data.data[0];
                        content.innerHTML =
                            '<p><strong>Ticket:</strong> #' + escapeHtml(ticket.reference_id || '') + '</p>' +
                            '<p><strong>Title:</strong> ' + escapeHtml(ticket.title || '') + '</p>' +
                            '<p><strong>Status:</strong> ' + escapeHtml(ticket.status || '') + '</p>' +
                            '<p><strong>Priority:</strong> ' + escapeHtml(ticket.priority || '') + '</p>' +
                            '<p><strong>Created:</strong> ' + (ticket.created_at ? new Date(ticket.created_at).toLocaleDateString() : '') + '</p>' +
                            '<p class="mt-2"><a href="cust_ticket.php?ref=' + encodeURIComponent(ticket.reference_id) + '" class="text-blue-600 hover:underline">Open ticket -></a></p>';
                    } else {
                        content.innerHTML = '<p>No active tickets found for this customer.</p>';
                    }
                })
                .fail(function() {
                    content.innerHTML = '<p>Error loading ticket information.</p>';
                });
            return;
        }

        if (btnId === 'viewHistoryBtn') {
            const content = document.getElementById('historyModalContent');
            if (!content) return;
            content.innerHTML = '<li class="list-none"><div class="modal-loading-wrap"><div class="modal-spinner"></div><p class="text-sm text-gray-500">Loading history...</p></div></li>';
            if (typeof window.openCustomerModal === 'function') window.openCustomerModal('historyModal');
            $.getJSON('../php/tickets_list.php?user_id=' + encodeURIComponent(userId) + '&sort=date_desc&pageSize=50')
                .done(function(data) {
                    if (data && data.error) {
                        content.innerHTML = '<li class="text-red-600">' + escapeHtml(data.error || 'Unable to load history.') + '</li>';
                        return;
                    }
                    if (data && data.data && data.data.length > 0) {
                        content.innerHTML = data.data.map(function(t) {
                            return '<li>- ' + escapeHtml(t.title || '') + ' (' + escapeHtml(t.status || '') + ') - ' + (t.created_at ? new Date(t.created_at).toLocaleDateString() : '') + '</li>';
                        }).join('');
                    } else {
                        content.innerHTML = '<li>- No interaction history found</li>';
                    }
                })
                .fail(function() {
                    content.innerHTML = '<li>- Error loading interaction history</li>';
                });
            return;
        }

        if (btnId === 'viewProductsBtn') {
            const modalContent = document.getElementById('productsModalContent');
            if (!modalContent) return;
            modalContent.innerHTML = '<div class="modal-loading-wrap"><div class="modal-spinner"></div><p class="text-sm text-gray-500">Loading product history...</p></div>';
            if (typeof window.openCustomerModal === 'function') window.openCustomerModal('productsModal');

            fetchCustomerProducts(userId, 'all')
                .then(data => {
                    const products = Array.isArray(data.products) ? data.products : [];
                    if (!products.length) {
                        modalContent.innerHTML = '<div class="bg-gray-50 p-3 rounded-lg border"><p class="text-gray-600">No product history found for this customer.</p></div>';
                        return;
                    }
                    modalContent.innerHTML = products.map(formatProductCard).join('');
                })
                .catch(err => {
                    console.error('Error loading product history:', err);
                    modalContent.innerHTML = '<div class="bg-gray-50 p-3 rounded-lg border"><p class="text-gray-600">Error loading product history.</p></div>';
                });
        }
    });

    function renderPagination(pagination) {
        if (!pagination || !pagination.total_pages) {
            $('#paginationContainer').remove();
            return;
        }

        if (!$('#paginationContainer').length) {
            $('#customerList').after('<div id="paginationContainer" class="flex justify-center items-center gap-2 mt-4"></div>');
        }

        const container = $('#paginationContainer');
        container.empty();

        const page = Number(pagination.page || 1);
        const totalPages = Number(pagination.total_pages || 1);
        const total = Number(pagination.total_count || 0);

        if (totalPages <= 1) return;

        if (page > 1) {
            container.append(`<button class="pagination-btn px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50" data-page="${page - 1}">Previous</button>`);
        }

        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let p = startPage; p <= endPage; p++) {
            const isActive = p === page ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-gray-50';
            container.append(`<button class="pagination-btn px-3 py-2 border border-gray-300 rounded ${isActive}" data-page="${p}">${p}</button>`);
        }

        if (page < totalPages) {
            container.append(`<button class="pagination-btn px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50" data-page="${page + 1}">Next</button>`);
        }

        container.append(`<span class="text-sm text-gray-500 ml-4">Page ${page} of ${totalPages} (${total} total)</span>`);
    }

    $(document).on('click', '.pagination-btn', function() {
        const page = Number($(this).data('page') || 1);
        const { searchQuery, userType, slaValue, activityValue } = getCurrentFilters();
        loadCustomers(searchQuery, userType, slaValue, activityValue, page);
    });

    function loadFilterOptions(searchQuery = '', userType = 'all') {
        filterRequestToken += 1;
        const token = filterRequestToken;

        if (filterXhr && filterXhr.readyState !== 4) {
            filterXhr.abort();
        }

        filterXhr = $.ajax({
            url: '../php/get_filter_options.php',
            type: 'GET',
            data: { type: 'all', q: searchQuery, user_type: userType },
            dataType: 'json',
            timeout: 30000
        }).done(function(data) {
            if (token !== filterRequestToken) return;

            if (data.user_types) {
                populateSelect(filterUser, data.user_types);
            }
            if (data.sla_statuses) {
                populateSelect(filterSLA, data.sla_statuses);
            }
            if (data.activity_statuses) {
                populateSelect(filterActivity, data.activity_statuses);
            }
        }).fail(function(xhr, status, error) {
            if (status === 'abort') return;
            if (token !== filterRequestToken) return;
            console.error('Error loading filter options:', error);
        });
    }

    function populateSelect($select, options) {
        const currentValue = $select.val();
        $select.empty();

        options.forEach(option => {
            const selected = option.value === currentValue ? ' selected="selected"' : '';
            $select.append(`<option value="${escapeHtml(option.value)}"${selected}>${escapeHtml(option.label)}</option>`);
        });

        if ($select.val() === null && options.length > 0) {
            $select.val('all');
        }
    }

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
                if (res.success && typeof window.showToast === 'function') {
                    window.showToast('Notes saved.');
                }
            }
        });
    });

    resetProfile();
    loadFilterOptions('', 'all');
    loadCustomers('', 'all', 'all', 'all', 1);
});

