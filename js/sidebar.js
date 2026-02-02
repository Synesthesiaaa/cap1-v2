// Sidebar toggle functionality
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop') || createBackdrop();

    sidebar.classList.toggle('open');
    backdrop.classList.toggle('show');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');

    if (sidebar) sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('show');
}

function createBackdrop() {
    const backdrop = document.createElement('div');
    backdrop.classList.add('sidebar-backdrop');
    document.body.appendChild(backdrop);
    return backdrop;
}

// Initialize sidebar on page load
document.addEventListener('DOMContentLoaded', function() {
    // Create backdrop if it doesn't exist
    if (!document.querySelector('.sidebar-backdrop')) {
        createBackdrop();
    }

    // Close sidebar only when clicking on backdrop (not on navbar links)
    const backdrop = document.querySelector('.sidebar-backdrop');
    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }
});
