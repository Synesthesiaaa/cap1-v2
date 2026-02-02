/**
 * UI Enhancements
 * 
 * Smooth transitions, animations, and interactive elements
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        ensureLoadingModalInBody();
        addPageTransition();
        enhanceForms();
        enhanceButtons();
        enhanceTables();
        addLoadingStates();
        enhanceModals();
        addSmoothScroll();
        enhanceFileUpload();
        addScrollIndicator();
        enhanceNavbar();
    }

    /** Ensure global loading modal is a direct child of body so it is never clipped */
    function ensureLoadingModalInBody() {
        var el = document.getElementById('global-loading-modal');
        if (el && el.parentNode !== document.body) {
            document.body.appendChild(el);
        }
    }

    /**
     * Add smooth page transitions (slide only, no opacity - avoids navbar hidden/flicker)
     */
    function addPageTransition() {
        document.body.classList.add('page-transition');
        const containers = document.querySelectorAll('.container, .monitor-wrap, .card');
        containers.forEach((container, index) => {
            if (!container.closest('.topbar')) {
                container.style.animationDelay = `${index * 0.05}s`;
                container.classList.add('fade-in');
            }
        });
    }

    /**
     * Enhance form inputs
     */
    function enhanceForms() {
        const inputs = document.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Add focus animation
            input.addEventListener('focus', function() {
                this.parentElement?.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement?.classList.remove('focused');
            });
            
            // Add floating label effect if needed
            if (input.placeholder && !input.value) {
                input.addEventListener('input', function() {
                    if (this.value) {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
            }
        });
    }

    /**
     * Enhance buttons with ripple effect
     */
    function enhanceButtons() {
        const buttons = document.querySelectorAll('button, .submit-btn, .btn-primary, .btn-secondary');
        
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Skip if disabled
                if (this.disabled) return;
                
                // Create ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    /**
     * Enhance tables with row animations
     */
    function enhanceTables() {
        const tables = document.querySelectorAll('table tbody');
        
        tables.forEach(tbody => {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
                row.classList.add('fade-in');
            });
        });
    }

    /**
     * Add loading states to buttons
     */
    function addLoadingStates() {
        // Intercept form submissions
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"], .submit-btn');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    submitBtn.textContent = submitBtn.textContent.replace('Submit', 'Submitting...');
                }
            });
        });
        
        // Intercept AJAX buttons
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('button[data-async]');
            if (btn && !btn.disabled) {
                btn.classList.add('loading');
                btn.disabled = true;
            }
        });
    }

    /**
     * Enhance modals with smooth animations
     */
    function enhanceModals() {
        const modals = document.querySelectorAll('.modal, [id*="Modal"], [id*="modal"]');
        
        modals.forEach(modal => {
            // Close on backdrop click
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this);
                }
            });
            
            // Close on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isModalVisible(modal)) {
                    closeModal(modal);
                }
            });
        });
    }

    function isModalVisible(modal) {
        if (!modal) return false;
        return modal.classList.contains('show') || !modal.classList.contains('hidden');
    }

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('show');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('show');
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    /**
     * Add smooth scroll behavior
     */
    function addSmoothScroll() {
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    /**
     * Enhance file upload with drag and drop
     */
    function enhanceFileUpload() {
        const uploadBoxes = document.querySelectorAll('.upload-box');
        
        uploadBoxes.forEach(box => {
            const input = box.querySelector('input[type="file"]');
            if (!input) return;
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                box.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                box.addEventListener(eventName, () => {
                    box.classList.add('drag-over');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                box.addEventListener(eventName, () => {
                    box.classList.remove('drag-over');
                }, false);
            });
            
            box.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length > 0) {
                    input.files = files;
                    // Trigger change event
                    const event = new Event('change', { bubbles: true });
                    input.dispatchEvent(event);
                    
                    // Show file name
                    showFileName(box, files[0].name);
                }
            }, false);
            
            input.addEventListener('change', function() {
                if (this.files.length > 0) {
                    showFileName(box, this.files[0].name);
                }
            });
        });
    }

    function showFileName(uploadBox, fileName) {
        let fileInfo = uploadBox.querySelector('.file-info');
        if (!fileInfo) {
            fileInfo = document.createElement('div');
            fileInfo.className = 'file-info';
            fileInfo.style.marginTop = '12px';
            fileInfo.style.padding = '8px 12px';
            fileInfo.style.background = 'rgba(67, 160, 222, 0.1)';
            fileInfo.style.borderRadius = '6px';
            fileInfo.style.fontSize = '13px';
            fileInfo.style.color = '#275F8E';
            uploadBox.appendChild(fileInfo);
        }
        fileInfo.textContent = `Selected: ${fileName}`;
        fileInfo.style.animation = 'fadeIn 0.3s ease-out';
    }

    /**
     * Show toast notification
     */
    window.showToast = function(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    /**
     * Show loading modal (global on all platforms).
     * @param {string} [message] - Optional message (e.g. 'Saving...', 'Loading…')
     */
    window.showLoading = function(message) {
        const globalModal = document.getElementById('global-loading-modal');
        if (globalModal) {
            const msgEl = document.getElementById('global-loading-message');
            if (msgEl) msgEl.textContent = message || 'Loading…';
            globalModal.classList.add('is-visible');
            globalModal.classList.remove('hidden');
            globalModal.style.setProperty('display', 'flex', 'important');
            globalModal.style.setProperty('visibility', 'visible', 'important');
            globalModal.style.setProperty('opacity', '1', 'important');
            globalModal.style.setProperty('z-index', '99999', 'important');
            globalModal.setAttribute('aria-hidden', 'false');
            return;
        }
        // Fallback: create overlay when global modal not present (e.g. login page)
        let overlay = document.getElementById('loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.cssText = `
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                backdrop-filter: blur(4px);
            `;
            overlay.innerHTML = `
                <div style="
                    width: 50px;
                    height: 50px;
                    border: 4px solid #43A0DE;
                    border-top-color: transparent;
                    border-radius: 50%;
                    animation: spin 0.8s linear infinite;
                "></div>
            `;
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    };

    window.hideLoading = function() {
        const globalModal = document.getElementById('global-loading-modal');
        if (globalModal) {
            globalModal.classList.remove('is-visible');
            globalModal.style.removeProperty('display');
            globalModal.style.removeProperty('visibility');
            globalModal.style.removeProperty('opacity');
            globalModal.style.removeProperty('z-index');
            globalModal.setAttribute('aria-hidden', 'true');
        }
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    };

    /**
     * Add scroll progress indicator
     */
    function addScrollIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'scroll-indicator';
        indicator.id = 'scroll-indicator';
        document.body.appendChild(indicator);
        
        window.addEventListener('scroll', function() {
            const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (window.scrollY / windowHeight) * 100;
            indicator.style.width = scrolled + '%';
        });
    }

    /**
     * Enhance navbar with scroll effects
     */
    function enhanceNavbar() {
        const navbar = document.querySelector('.topbar');
        if (!navbar) return;
        
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            
            // Add shadow on scroll
            if (currentScroll > 10) {
                navbar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
            } else {
                navbar.style.boxShadow = '';
            }
            
            lastScroll = currentScroll;
        });
    }

    // Export functions
    window.openModal = openModal;
    window.closeModal = closeModal;

})();

// Add ripple effect CSS
const style = document.createElement('style');
style.textContent = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
