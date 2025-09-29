/**
 * SCN Member Dashboard JavaScript
 */

(function($) {
    'use strict';

    class SCNDashboard {
        constructor() {
            this.init();
        }

        init() {
            this.loadDashboardData();
            this.setupEventListeners();
        }

        setupEventListeners() {
            // Refresh button if exists
            $(document).on('click', '.scn-refresh-btn', () => {
                this.loadDashboardData();
            });

            // Auto-refresh every 5 minutes
            setInterval(() => {
                this.loadDashboardData();
            }, 300000);
        }

        loadDashboardData() {
            const $container = $('.scn-dashboard-container');
            const userId = $container.data('user-id');

            if (!userId) {
                console.error('User ID not found');
                return;
            }

            // Load profile completion
            this.loadProfileStats();
        }


        loadProfileStats() {
            const $completionContainer = $('#scn-profile-completion');
            
            if (!$completionContainer.length) return;

            $.ajax({
                url: scnDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'scn_dashboard_profile_stats',
                    nonce: scnDashboard.nonce
                },
                beforeSend: () => {
                    $completionContainer.html(`
                        <div class="scn-loading">
                            <span class="dashicons dashicons-update"></span>
                            ${scnDashboard.strings.loading}
                        </div>
                    `);
                },
                success: (response) => {
                    if (response.success) {
                        this.renderProfileCompletion(response.data);
                    } else {
                        this.showError($completionContainer, response.data || scnDashboard.strings.error);
                    }
                },
                error: () => {
                    this.showError($completionContainer, scnDashboard.strings.error);
                }
            });
        }


        renderProfileCompletion(stats) {
            const $completionContainer = $('#scn-profile-completion');
            const template = $('#scn-completion-template').html();
            
            if (!template) {
                console.error('Completion template not found');
                return;
            }

            const html = this.renderTemplate(template, stats);
            $completionContainer.html(html);
        }

        renderTemplate(template, data) {
            // Simple template engine using Handlebars-like syntax
            return template.replace(/\{\{([^}]+)\}\}/g, (match, key) => {
                const keys = key.trim().split('.');
                let value = data;
                
                for (let k of keys) {
                    if (value && typeof value === 'object' && k in value) {
                        value = value[k];
                    } else {
                        return '';
                    }
                }
                
                return value !== null && value !== undefined ? value : '';
            }).replace(/\{\{#if\s+([^}]+)\}\}([\s\S]*?)\{\{\/if\}\}/g, (match, condition, content) => {
                const keys = condition.trim().split('.');
                let value = data;
                
                for (let k of keys) {
                    if (value && typeof value === 'object' && k in value) {
                        value = value[k];
                    } else {
                        value = false;
                        break;
                    }
                }
                
                // Check if it's an array with length
                if (Array.isArray(value)) {
                    return value.length > 0 ? content : '';
                }
                
                // Check if it's truthy
                return value ? content : '';
            }).replace(/\{\{#each\s+([^}]+)\}\}([\s\S]*?)\{\{\/each\}\}/g, (match, arrayKey, content) => {
                const keys = arrayKey.trim().split('.');
                let value = data;
                
                for (let k of keys) {
                    if (value && typeof value === 'object' && k in value) {
                        value = value[k];
                    } else {
                        return '';
                    }
                }
                
                if (!Array.isArray(value)) {
                    return '';
                }
                
                return value.map(item => {
                    return content.replace(/\{\{([^}]+)\}\}/g, (subMatch, subKey) => {
                        const subKeys = subKey.trim().split('.');
                        let subValue = item;
                        
                        for (let k of subKeys) {
                            if (subValue && typeof subValue === 'object' && k in subValue) {
                                subValue = subValue[k];
                            } else {
                                return '';
                            }
                        }
                        
                        return subValue !== null && subValue !== undefined ? subValue : '';
                    });
                }).join('');
            });
        }

        showError($container, message) {
            $container.html(`
                <div class="scn-error-state">
                    <span class="dashicons dashicons-warning"></span>
                    <p>${message}</p>
                    <button class="scn-btn scn-btn-primary scn-retry-btn">Try Again</button>
                </div>
            `);

            // Add retry functionality
            $container.find('.scn-retry-btn').on('click', () => {
                this.loadDashboardData();
            });
        }

        // Utility method to format numbers
        formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }

        // Utility method to format dates
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Utility method to format time
        formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
    }

    // Initialize dashboard when document is ready
    $(document).ready(function() {
        if ($('.scn-dashboard-container').length) {
            new SCNDashboard();
        }
    });

    // Add some utility functions to global scope
    window.SCNDashboardUtils = {
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },
        
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        
        formatTime: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
    };

})(jQuery);
