<?php
// This is includes/navbar.php to share for all views
?>
<style>
.notif-bell-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    margin-left: 16px;
    cursor: pointer;
}
.notif-bell-btn {
    background: none;
    border: none;
    color: #fff;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    font-size: 18px;
    line-height: 1;
    position: relative;
}
.notif-bell-btn:hover { color: #43A0DE; }
.notif-bell-badge {
    position: absolute;
    top: -4px;
    right: -6px;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
    line-height: 1;
    pointer-events: none;
}
.notif-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 320px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    z-index: 9999;
    overflow: hidden;
}
.notif-dropdown.open { display: block; }
.notif-dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 13px;
    font-weight: 600;
    color: #111827;
}
.notif-dropdown-body { max-height: 340px; overflow-y: auto; }
.notif-dropdown-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 14px;
    border-bottom: 1px solid #f9fafb;
    font-size: 12px;
    color: #374151;
    text-decoration: none;
    transition: background 0.15s;
}
.notif-dropdown-item:hover { background: #f9fafb; }
.notif-dropdown-item.unread { background: #eff6ff; border-left: 3px solid #3b82f6; }
.notif-dropdown-item .notif-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
.notif-dropdown-item .notif-text-wrap .notif-title { font-weight: 600; color: #111827; }
.notif-dropdown-item .notif-text-wrap .notif-msg { margin-top: 2px; color: #6b7280; }
.notif-dropdown-item .notif-text-wrap .notif-time { margin-top: 4px; color: #9ca3af; font-size: 11px; }
.notif-dropdown-empty { padding: 20px 14px; text-align: center; color: #9ca3af; font-size: 12px; }
.notif-dropdown-footer {
    padding: 8px 14px;
    text-align: center;
    border-top: 1px solid #f3f4f6;
    font-size: 12px;
}
.notif-dropdown-footer a { color: #3b82f6; text-decoration: none; }
.notif-dropdown-footer a:hover { text-decoration: underline; }
.notif-mark-all-btn {
    background: none;
    border: none;
    color: #3b82f6;
    cursor: pointer;
    font-size: 12px;
    padding: 0;
}
.notif-mark-all-btn:hover { text-decoration: underline; }
</style>

<div class="topbar">
    <div class="logo">
        <img src="../assets/img/logowithname.png" alt="ISC Logo">
        <span>Interconnect Solutions Company</span>
    </div>
    <div class="nav-links" style="display:flex;align-items:center;">
        <?php
            $role = $_SESSION['role'] ?? '';
            $userType = $_SESSION['user_type'] ?? '';
            $isInternalUser = ($userType === 'internal');
        ?>
        <?php if ($isInternalUser || in_array($role, ['technician','admin','department_head'])): ?>
            <!-- Internal users & staff: land on dashboard and see Customer Management -->
            <a href="../views/dashboard.php">Home</a>
            <a href="../views/cust_mgmt.php">Customer</a>
            <?php if ($role === 'admin'): ?>
            <a href="../views/user_mgmt.php">Users</a>
           <!-- <a href="../views/sla_weight_admin.php">SLA Weights</a> -->
            <?php endif; ?>
        <?php else: ?>
            <!-- External customers: land on ticket creation -->
            <a href="../views/create_ticket.php">Home</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'technician'): ?>
            <a href="../views/tech_ticket_monitor.php">Tickets</a>
        <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'department_head'): ?>
            <a href="../views/department_head_monitor.php">Ticket Monitor</a>
        <?php else: ?>
            <a href="../views/user_ticket_monitor.php">Tickets</a>
        <?php endif; ?>
        <!-- <?php if (in_array($role, ['admin', 'department_head', 'technician'])): ?>
            <a href="../views/reports.php">Reports</a>
        <?php endif; ?> -->
        <!-- <a href="../views/settings.php">Settings</a> -->
        <!-- <a href="../views/profile.php">Profile</a> -->

        <!-- Notification Bell -->
       <!-- <div class="notif-bell-wrap" id="navbarNotifWrap">
            <button class="notif-bell-btn" id="navbarBellBtn" aria-label="Notifications" title="Notifications">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="notif-bell-badge" id="navbarBellBadge" style="display:none;"></span>
            </button>
            <div class="notif-dropdown" id="navbarNotifDropdown">
                <div class="notif-dropdown-header">
                    <span>Notifications</span>
                    <button class="notif-mark-all-btn" id="navbarMarkAllBtn" style="display:none;">Mark all read</button>
                </div>
                <div class="notif-dropdown-body" id="navbarNotifBody">
                    <div class="notif-dropdown-empty">Loading…</div>
                </div>
                <div class="notif-dropdown-footer">
                    <a href="../views/dashboard.php">View all on dashboard →</a>
                </div>
            </div>
        </div> -->

        <a href="../views/logout.php">Logout</a>
    </div>
</div>

<script>
(function() {
    const bellBtn    = document.getElementById('navbarBellBtn');
    const dropdown   = document.getElementById('navbarNotifDropdown');
    const badge      = document.getElementById('navbarBellBadge');
    const body       = document.getElementById('navbarNotifBody');
    const markAllBtn = document.getElementById('navbarMarkAllBtn');

    if (!bellBtn || !dropdown) return;

    // Determine recipient type from session-injected role
    const navRole = <?php echo json_encode($role); ?>;
    const recipientType = navRole === 'technician' ? 'technician' : 'user';

    function escH(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function fetchUnread() {
        fetch('../php/notifications_api.php?action=get_unread')
            .then(r => r.ok ? r.json() : Promise.reject(r.status))
            .then(data => {
                const count = data.count || 0;
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
                if (markAllBtn) {
                    markAllBtn.style.display = count > 0 ? 'inline' : 'none';
                }
                renderDropdownNotifs(data.notifications || []);
            })
            .catch(() => {});
    }

    function renderDropdownNotifs(notifs) {
        if (!body) return;
        if (!notifs.length) {
            body.innerHTML = '<div class="notif-dropdown-empty">No unread notifications.</div>';
            return;
        }
        const icons = { reply: '💬', status_change: '🔄', assignment: '👤', alert: '⚠️', info: 'ℹ️' };
        body.innerHTML = notifs.map(n => {
            const icon = icons[n.type] || 'ℹ️';
            const tag  = n.link ? 'a' : 'div';
            const href = n.link ? `href="${escH(n.link)}"` : '';
            return `<${tag} ${href} class="notif-dropdown-item unread" data-nid="${escH(n.notification_id)}"
                onclick="navbarMarkNotifRead(${escH(n.notification_id)}, this)">
                <span class="notif-icon">${icon}</span>
                <div class="notif-text-wrap">
                    <div class="notif-title">${escH(n.title)}</div>
                    <div class="notif-msg">${escH(n.message)}</div>
                    <div class="notif-time">${escH(n.created_at)}</div>
                </div>
            </${tag}>`;
        }).join('');
    }

    window.navbarMarkNotifRead = function(notifId, el) {
        fetch('../php/notifications_api.php?action=mark_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notifId })
        }).then(() => {
            if (el) {
                el.classList.remove('unread');
            }
            fetchUnread();
        }).catch(() => {});
    };

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fetch('../php/notifications_api.php?action=mark_all_read', { method: 'POST' })
                .then(() => fetchUnread())
                .catch(() => {});
        });
    }

    // Toggle dropdown
    bellBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            fetchUnread();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!document.getElementById('navbarNotifWrap')?.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    // Initial fetch + poll every 60 seconds
    fetchUnread();
    setInterval(fetchUnread, 60000);
})();
</script>

<?php include(__DIR__ . '/loading_modal.php'); ?>
