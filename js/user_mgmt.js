/**
 * User Management - List, add, edit, deactivate users
 */

(function() {
    'use strict';

    const searchInput = document.getElementById('searchInput');
    const filterUserType = document.getElementById('filterUserType');
    const filterRole = document.getElementById('filterRole');
    const filterStatus = document.getElementById('filterStatus');
    const usersTableBody = document.getElementById('usersTableBody');
    const visibleCount = document.getElementById('visibleCount');
    const totalCount = document.getElementById('totalCount');
    const paginationEl = document.getElementById('pagination');
    const userModal = document.getElementById('userModal');
    const deleteModal = document.getElementById('deleteModal');
    const userForm = document.getElementById('userForm');
    const modalTitle = document.getElementById('modalTitle');
    const passwordField = document.getElementById('passwordField');
    const userPassword = document.getElementById('userPassword');

    let currentPage = 1;
    let totalPages = 1;
    let deleteUserId = null;
    let departments = [];

    function debounce(fn, ms) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    function loadDepartments() {
        fetch('../php/get_departments.php')
            .then(r => r.json())
            .then(data => {
                departments = Array.isArray(data) ? data : [];
                const sel = document.getElementById('userDepartment');
                sel.innerHTML = '<option value="">— None —</option>';
                departments.forEach(d => {
                    sel.innerHTML += `<option value="${d.department_id}">${escapeHtml(d.department_name)}</option>`;
                });
            })
            .catch(() => {});
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function loadUsers(page = 1) {
        const q = searchInput?.value?.trim() || '';
        const userType = filterUserType?.value || 'all';
        const role = filterRole?.value || 'all';
        const status = filterStatus?.value || 'all';

        const params = new URLSearchParams({ q, user_type: userType, user_role: role, status, page, limit: 20 });
        fetch('../php/users_list.php?' + params)
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    usersTableBody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-red-600">${escapeHtml(data.error)}</td></tr>`;
                    return;
                }

                const users = data.users || [];
                const pag = data.pagination || {};

                visibleCount.textContent = users.length;
                totalCount.textContent = pag.total_count || 0;
                currentPage = pag.page || 1;
                totalPages = pag.total_pages || 1;

                if (users.length === 0) {
                    usersTableBody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-gray-500">No users found</td></tr>';
                } else {
                    usersTableBody.innerHTML = users.map(u => `
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4 align-middle">
                                <div class="font-medium text-gray-800">${escapeHtml(u.name || '—')}</div>
                                ${u.company ? `<div class="text-xs text-gray-500">${escapeHtml(u.company)}</div>` : ''}
                            </td>
                            <td class="py-3 px-4 text-sm align-middle">${escapeHtml(u.email)}</td>
                            <td class="py-3 px-4 align-middle"><span class="text-xs px-2 py-0.5 rounded-full ${u.user_type === 'internal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">${escapeHtml(u.user_type)}</span></td>
                            <td class="py-3 px-4 align-middle"><span class="text-xs px-2 py-0.5 rounded-full bg-slate-100">${escapeHtml(u.user_role)}</span></td>
                            <td class="py-3 px-4 align-middle"><span class="text-xs px-2 py-0.5 rounded-full ${u.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${escapeHtml(u.status)}</span></td>
                            <td class="py-3 px-4 text-right align-middle">
                                <div class="flex items-center justify-end gap-3">
                                    <button class="edit-user text-indigo-600 hover:underline text-sm whitespace-nowrap" data-id="${u.user_id}">Edit</button>
                                    ${u.status === 'active' ? `<button class="delete-user text-red-600 hover:underline text-sm whitespace-nowrap" data-id="${u.user_id}">Deactivate</button>` : ''}
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }

                renderPagination(pag);
            })
            .catch(err => {
                usersTableBody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-red-600">Failed to load users</td></tr>';
                console.error(err);
            });
    }

    function renderPagination(pag) {
        if (!pag || pag.total_pages <= 1) {
            paginationEl.innerHTML = '';
            return;
        }
        const prev = pag.page > 1 ? `<button class="pagination-prev px-4 py-2 h-10 rounded-lg border border-slate-200 hover:bg-gray-50 text-sm font-medium" data-page="${pag.page - 1}">Previous</button>` : '';
        const next = pag.page < pag.total_pages ? `<button class="pagination-next px-4 py-2 h-10 rounded-lg border border-slate-200 hover:bg-gray-50 text-sm font-medium" data-page="${pag.page + 1}">Next</button>` : '';
        paginationEl.innerHTML = `
            <span class="text-sm text-gray-600">Page ${pag.page} of ${pag.total_pages}</span>
            <div class="flex items-center gap-2">${prev} ${next}</div>
        `;
    }

    function openAddModal() {
        modalTitle.textContent = 'Add User';
        document.getElementById('userId').value = '';
        document.getElementById('userName').value = '';
        document.getElementById('userEmail').value = '';
        userPassword.value = '';
        userPassword.required = true;
        document.getElementById('userType').value = 'external';
        document.getElementById('userRole').value = 'customer';
        document.getElementById('userDepartment').value = '';
        document.getElementById('userCompany').value = '';
        document.getElementById('userPhone').value = '';
        document.getElementById('userStatus').value = 'active';
        passwordField.style.display = 'block';
        userModal.classList.remove('hidden');
    }

    function openEditModal(user) {
        modalTitle.textContent = 'Edit User';
        document.getElementById('userId').value = user.user_id;
        document.getElementById('userName').value = user.name || '';
        document.getElementById('userEmail').value = user.email || '';
        userPassword.value = '';
        userPassword.required = false;
        document.getElementById('userType').value = user.user_type || 'external';
        document.getElementById('userRole').value = user.user_role || 'customer';
        document.getElementById('userDepartment').value = user.department_id || '';
        document.getElementById('userCompany').value = user.company || '';
        document.getElementById('userPhone').value = user.phone || '';
        document.getElementById('userStatus').value = user.status || 'active';
        passwordField.style.display = 'block';
        userPassword.placeholder = 'Leave blank to keep current';
        userModal.classList.remove('hidden');
    }

    function closeUserModal() {
        userModal.classList.add('hidden');
    }

    function closeDeleteModal() {
        deleteModal.classList.add('hidden');
        deleteUserId = null;
    }

    function openDeleteModal(userId) {
        deleteUserId = userId;
        deleteModal.classList.remove('hidden');
    }

    function submitUser(e) {
        e.preventDefault();
        const userId = document.getElementById('userId').value;
        const isEdit = !!userId;

        const payload = {
            name: document.getElementById('userName').value.trim(),
            email: document.getElementById('userEmail').value.trim(),
            user_type: document.getElementById('userType').value,
            user_role: document.getElementById('userRole').value,
            department_id: document.getElementById('userDepartment').value || null,
            company: document.getElementById('userCompany').value.trim(),
            phone: document.getElementById('userPhone').value.trim(),
            status: document.getElementById('userStatus').value
        };

        if (!isEdit) {
            payload.password = userPassword.value;
        } else if (userPassword.value) {
            payload.password = userPassword.value;
        }
        payload.user_id = userId || undefined;

        const url = isEdit ? '../php/user_update.php' : '../php/user_create.php';
        const btn = document.getElementById('btnSave');
        btn.disabled = true;

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    closeUserModal();
                    loadUsers(currentPage);
                } else {
                    alert(data.error || 'Operation failed');
                }
            })
            .catch(err => {
                btn.disabled = false;
                alert('Request failed');
                console.error(err);
            });
    }

    function confirmDelete() {
        if (!deleteUserId) return;
        fetch('../php/user_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: deleteUserId })
        })
            .then(r => r.json())
            .then(data => {
                closeDeleteModal();
                if (data.success) {
                    loadUsers(currentPage);
                } else {
                    alert(data.error || 'Failed to deactivate');
                }
            })
            .catch(err => {
                alert('Request failed');
                console.error(err);
            });
    }

    // Event listeners
    document.getElementById('btnAddUser')?.addEventListener('click', openAddModal);
    document.getElementById('btnCancel')?.addEventListener('click', closeUserModal);
    document.getElementById('btnDeleteCancel')?.addEventListener('click', closeDeleteModal);
    document.getElementById('btnDeleteConfirm')?.addEventListener('click', confirmDelete);
    userForm?.addEventListener('submit', submitUser);

    searchInput?.addEventListener('input', debounce(() => loadUsers(1), 300));
    filterUserType?.addEventListener('change', () => loadUsers(1));
    filterRole?.addEventListener('change', () => loadUsers(1));
    filterStatus?.addEventListener('change', () => loadUsers(1));

    usersTableBody?.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-user');
        const deleteBtn = e.target.closest('.delete-user');
        if (editBtn) {
            const id = parseInt(editBtn.dataset.id, 10);
            fetch('../php/get_user.php?user_id=' + id)
                .then(r => r.json())
                .then(user => {
                    if (user && user.user_id) openEditModal(user);
                    else alert('Failed to load user');
                })
                .catch(() => alert('Failed to load user'));
        } else if (deleteBtn) {
            openDeleteModal(parseInt(deleteBtn.dataset.id, 10));
        }
    });

    paginationEl?.addEventListener('click', function(e) {
        const btn = e.target.closest('.pagination-prev, .pagination-next');
        if (btn) loadUsers(parseInt(btn.dataset.page, 10));
    });

    userModal?.addEventListener('click', function(e) {
        if (e.target === userModal) closeUserModal();
    });
    deleteModal?.addEventListener('click', function(e) {
        if (e.target === deleteModal) closeDeleteModal();
    });

    // Init
    loadDepartments();
    loadUsers(1);
})();
