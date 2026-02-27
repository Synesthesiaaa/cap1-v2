<?php
// This is includes/navbar.php to share for all views
?>
<div class="topbar">
    <div class="logo">
        <img src="../assets/img/logowithname.png" alt="ISC Logo">
        <span>Interconnect Solutions Company</span>
    </div>
    <div class="nav-links">
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
            <a href="../views/sla_weight_admin.php">SLA Weights</a>
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
        <?php if (in_array($role, ['admin', 'department_head', 'technician'])): ?>
            <a href="../views/reports.php">Reports</a>
        <?php endif; ?>
        <!-- <a href="../views/settings.php">Settings</a> -->
        <!-- <a href="../views/profile.php">Profile</a> -->
        <a href="../views/logout.php">Logout</a>
    </div>
</div>
<?php include(__DIR__ . '/loading_modal.php'); ?>
