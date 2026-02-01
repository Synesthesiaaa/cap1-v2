/**
 * Main JavaScript Entry Point
 * 
 * This file can be used to initialize common functionality
 */

// Initialize CSRF token for AJAX requests
if (typeof jQuery !== 'undefined') {
    // Set up CSRF token for all AJAX requests
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (settings.type === 'POST' || settings.type === 'PUT' || settings.type === 'DELETE') {
                const token = document.querySelector('meta[name="csrf-token"]');
                if (token) {
                    xhr.setRequestHeader('X-CSRF-Token', token.getAttribute('content'));
                }
            }
        }
    });
}

// Export for use in other modules
export {};
