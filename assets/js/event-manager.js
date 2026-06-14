/**
 * Event Manager - JavaScript
 * All functions in EventManager namespace to avoid conflicts
 * NO MODIFICATIONS to existing JavaScript
 */

const EventManager = {
    /**
     * Initialize Event Manager
     */
    init() {
        console.log('Event Manager initialized');
        this.setupEventListeners();
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Add any global event listeners here
        document.addEventListener('DOMContentLoaded', () => {
            this.initTooltips();
            this.initConfirmDialogs();
        });
    },

    /**
     * Initialize Bootstrap tooltips
     */
    initTooltips() {
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    /**
     * Initialize confirm dialogs
     */
    initConfirmDialogs() {
        document.querySelectorAll('[data-confirm]').forEach(element => {
            element.addEventListener('click', (e) => {
                const message = element.getAttribute('data-confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    },

    /**
     * Show loading spinner
     */
    showLoading(container) {
        const loadingHtml = `
            <div class="em-loading">
                <div class="em-spinner"></div>
                <p class="mt-3">Loading...</p>
            </div>
        `;
        container.innerHTML = loadingHtml;
    },

    /**
     * Show empty state
     */
    showEmptyState(container, icon, title, message) {
        const emptyHtml = `
            <div class="em-empty-state">
                <i class="fas fa-${icon}"></i>
                <h3>${title}</h3>
                <p>${message}</p>
            </div>
        `;
        container.innerHTML = emptyHtml;
    },

    /**
     * Show error message
     */
    showError(message) {
        alert('Error: ' + message);
    },

    /**
     * Show success message
     */
    showSuccess(message) {
        alert('Success: ' + message);
    },

    /**
     * Format date
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    },

    /**
     * Format number with commas
     */
    formatNumber(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Copy to clipboard
     */
    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showSuccess('Copied to clipboard');
        }).catch(() => {
            this.showError('Failed to copy');
        });
    }
};

// Initialize on page load
EventManager.init();
