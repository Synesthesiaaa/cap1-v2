/**
 * Ticket Interactions
 * 
 * Enhanced interactions for ticket-related pages
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        enhanceTicketCards();
        enhanceTicketTable();
        addTicketFilters();
        enhanceTicketActions();
    });

    /**
     * Enhance ticket cards with hover effects
     */
    function enhanceTicketCards() {
        const cards = document.querySelectorAll('.card, .stat-card, .summary-card');
        
        cards.forEach(card => {
            card.style.transition = 'all 0.3s ease';
            
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
                this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
            });
        });
    }

    /**
     * Enhance ticket table rows
     */
    function enhanceTicketTable() {
        const tableRows = document.querySelectorAll('table tbody tr');
        
        tableRows.forEach((row, index) => {
            // Stagger animation
            row.style.animationDelay = `${index * 0.05}s`;
            row.classList.add('fade-in');
            
            // Add click effect
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                // Don't trigger if clicking a link or button
                if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
                    return;
                }
                
                const link = this.querySelector('a.details-link');
                if (link) {
                    link.click();
                }
            });
            
            // Add hover effect
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(67, 160, 222, 0.05)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    }

    /**
     * Add smooth filter transitions
     */
    function addTicketFilters() {
        const filters = document.querySelectorAll('.filters select, .filters input, #search, #priority, #status, #sort');
        
        filters.forEach(filter => {
            filter.addEventListener('change', function() {
                // Add loading state
                const container = document.getElementById('tickets-body') || 
                                document.getElementById('tickets-table') ||
                                document.querySelector('table tbody');
                
                if (container) {
                    container.style.opacity = '0.5';
                    container.style.transition = 'opacity 0.3s';
                    
                    setTimeout(() => {
                        container.style.opacity = '1';
                    }, 300);
                }
            });
        });
    }

    /**
     * Enhance ticket action buttons
     */
    function enhanceTicketActions() {
        // Resolve button
        const resolveBtn = document.getElementById('resolve-ticket-btn');
        if (resolveBtn) {
            resolveBtn.addEventListener('click', function(e) {
                if (confirm('Mark this ticket as resolved?')) {
                    this.classList.add('loading');
                    showLoading();
                } else {
                    e.preventDefault();
                }
            });
        }

        // Edit button
        const editBtn = document.getElementById('edit-ticket-btn');
        if (editBtn) {
            editBtn.addEventListener('click', function() {
                const modal = document.getElementById('editTicketModal');
                if (modal) {
                    Animations.fadeIn(modal, 300);
                    modal.classList.add('show');
                }
            });
        }

        // Escalate button
        const escalateBtn = document.getElementById('escalate-ticket-btn');
        if (escalateBtn) {
            escalateBtn.addEventListener('click', function() {
                const modal = document.getElementById('escalationModal');
                if (modal) {
                    Animations.fadeIn(modal, 300);
                    modal.classList.add('show');
                }
            });
        }

        // Reply button
        const replyBtn = document.getElementById('send-reply-button');
        if (replyBtn) {
            replyBtn.addEventListener('click', function() {
                const textarea = document.getElementById('replyText');
                if (textarea && !textarea.value.trim()) {
                    Animations.shake(textarea);
                    textarea.focus();
                    return false;
                }
                
                this.classList.add('loading');
            });
        }
    }

    /**
     * Add smooth pagination transitions
     */
    const paginationLinks = document.querySelectorAll('.pagination a, .pagination button');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href !== '#' && !this.classList.contains('disabled')) {
                showLoading();
            }
        });
    });

    /**
     * Enhance status badges with animations
     */
    const statusBadges = document.querySelectorAll('.status, .priority');
    statusBadges.forEach(badge => {
        badge.style.transition = 'all 0.3s ease';
        
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

})();
