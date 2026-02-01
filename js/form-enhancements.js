/**
 * Form Enhancements
 * 
 * Enhanced form interactions and validations
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        enhanceTicketForm();
        addFormValidation();
        addAutoSave();
    });

    /**
     * Enhance ticket creation form
     */
    function enhanceTicketForm() {
        const form = document.querySelector('.ticket-form');
        if (!form) return;

        // Add smooth category transition
        const typeSelect = document.getElementById('ticket_type');
        const categoryWrapper = document.getElementById('category-wrapper');
        
        if (typeSelect && categoryWrapper) {
            typeSelect.addEventListener('change', function() {
                if (this.value) {
                    Animations.slideDown(categoryWrapper, 300);
                } else {
                    Animations.slideUp(categoryWrapper, 300);
                }
            });
        }

        // Add character counter for description
        const description = form.querySelector('textarea[name="description"]');
        if (description) {
            const counter = document.createElement('div');
            counter.className = 'char-counter';
            counter.style.cssText = 'text-align: right; font-size: 12px; color: #A1B2C0; margin-top: 4px;';
            description.parentElement.appendChild(counter);
            
            description.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length} characters`;
                
                if (length > 1000) {
                    counter.style.color = '#ef4444';
                } else if (length > 500) {
                    counter.style.color = '#f59e0b';
                } else {
                    counter.style.color = '#A1B2C0';
                }
            });
        }

        // Add form submission enhancement
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.submit-btn');
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    }

    /**
     * Add real-time form validation
     */
    function addFormValidation() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        validateField(this);
                    }
                });
            });
        });
    }

    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Remove previous error
        field.classList.remove('error');
        const existingError = field.parentElement.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        // Required validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }

        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }

        // Show error
        if (!isValid) {
            field.classList.add('error');
            field.style.borderColor = '#ef4444';
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.cssText = 'color: #ef4444; font-size: 12px; margin-top: 4px;';
            errorDiv.textContent = errorMessage;
            field.parentElement.appendChild(errorDiv);
            
            Animations.shake(field);
        } else {
            field.style.borderColor = '';
        }

        return isValid;
    }

    /**
     * Add auto-save functionality (localStorage)
     */
    function addAutoSave() {
        const forms = document.querySelectorAll('form[data-autosave]');
        
        forms.forEach(form => {
            const formId = form.id || 'form-' + Math.random().toString(36).substr(2, 9);
            const storageKey = 'autosave_' + formId;
            
            // Load saved data
            const savedData = localStorage.getItem(storageKey);
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    Object.keys(data).forEach(key => {
                        const field = form.querySelector(`[name="${key}"]`);
                        if (field && field.type !== 'file') {
                            field.value = data[key];
                        }
                    });
                    
                    // Show restore notification
                    showAutoSaveNotification('Draft restored', 'info');
                } catch (e) {
                    console.error('Failed to restore autosave', e);
                }
            }
            
            // Save on input
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.type !== 'file' && input.type !== 'submit') {
                    input.addEventListener('input', debounce(function() {
                        saveFormData(form, storageKey);
                    }, 1000));
                }
            });
            
            // Clear on successful submit
            form.addEventListener('submit', function() {
                localStorage.removeItem(storageKey);
            });
        });
    }

    function saveFormData(form, storageKey) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (key !== 'attachment' && key !== 'reply_attachment') {
                data[key] = value;
            }
        }
        
        localStorage.setItem(storageKey, JSON.stringify(data));
    }

    function showAutoSaveNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `toast ${type}`;
        notification.textContent = message;
        notification.style.cssText += 'position: fixed; bottom: 20px; right: 20px; z-index: 1000;';
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 10);
        setTimeout(() => {
            notification.classList.add('hide');
            setTimeout(() => notification.remove(), 300);
        }, 2000);
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

})();
