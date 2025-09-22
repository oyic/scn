jQuery(document).ready(function($) {
    'use strict';

    // Events Admin JavaScript functionality
    // This file provides enhanced functionality for the Events admin interface

    // Auto-hide search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.scn-search-events').length) {
            $('.scn-search-results').empty().hide();
        }
    });

    // Enhanced search with debouncing
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

    // Search events with improved UX
    function searchEvents(query, resultsContainer, onSelect) {
        if (query.length < 2) {
            $(resultsContainer).empty().hide();
            return;
        }

        $(resultsContainer).html('<div class="scn-loading"><?php _e('Searching...', 'scn-membership'); ?></div>').show();

        $.ajax({
            url: scnEvents.ajaxUrl,
            type: 'POST',
            data: {
                action: 'scn_search_events',
                nonce: scnEvents.nonce,
                query: query,
                limit: 10
            },
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data, resultsContainer, onSelect);
                } else {
                    $(resultsContainer).html('<p><?php _e('Search failed. Please try again.', 'scn-membership'); ?></p>');
                }
            },
            error: function() {
                $(resultsContainer).html('<p><?php _e('Search failed. Please try again.', 'scn-membership'); ?></p>');
            }
        });
    }

    function displaySearchResults(events, container, onSelect) {
        const $results = $(container);
        $results.empty();

        if (events.length === 0) {
            $results.html('<p><?php _e('No events found.', 'scn-membership'); ?></p>');
            return;
        }

        events.forEach(function(event) {
            const location = [];
            if (event.location.city) location.push(event.location.city);
            if (event.location.region) location.push(event.location.region);
            if (event.location.country) location.push(event.location.country);
            
            const locationStr = location.length > 0 ? location.join(', ') : '';
            const yearStr = event.year ? ' (' + event.year + ')' : '';
            
            const $item = $('<div class="scn-search-result-item" tabindex="0">')
                .html('<strong>' + event.official_name + '</strong><br>' +
                      (locationStr ? locationStr + '<br>' : '') +
                      '<small>' + event.match_type + '</small>')
                .data('event-id', event.id)
                .data('event-name', event.official_name)
                .click(function() {
                    onSelect($(this).data('event-id'), $(this).data('event-name'));
                    $results.hide();
                })
                .keypress(function(e) {
                    if (e.which === 13) { // Enter key
                        $(this).click();
                    }
                });
            
            $results.append($item);
        });
    }

    // Enhanced form validation
    function validateAlias(alias) {
        if (!alias || alias.trim().length === 0) {
            return '<?php _e('Alias cannot be empty.', 'scn-membership'); ?>';
        }
        
        if (alias.length > 191) {
            return '<?php _e('Alias is too long. Maximum 191 characters.', 'scn-membership'); ?>';
        }
        
        if (!/^[a-zA-Z0-9\s\-_]+$/.test(alias)) {
            return '<?php _e('Alias contains invalid characters. Only letters, numbers, spaces, hyphens, and underscores are allowed.', 'scn-membership'); ?>';
        }
        
        return null;
    }

    // Enhanced AJAX error handling
    function handleAjaxError(xhr, status, error) {
        console.error('AJAX Error:', status, error);
        
        let message = '<?php _e('An error occurred. Please try again.', 'scn-membership'); ?>';
        
        if (xhr.responseJSON && xhr.responseJSON.data) {
            message = xhr.responseJSON.data;
        } else if (xhr.status === 403) {
            message = '<?php _e('You do not have permission to perform this action.', 'scn-membership'); ?>';
        } else if (xhr.status === 404) {
            message = '<?php _e('The requested resource was not found.', 'scn-membership'); ?>';
        }
        
        return message;
    }

    // Enhanced notification system
    function showNotification(message, type = 'info') {
        const $notification = $('<div class="notice notice-' + type + ' is-dismissible">')
            .html('<p>' + message + '</p>')
            .prependTo('.wrap h1');
        
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Keyboard navigation for search results
    $(document).on('keydown', '.scn-search-result-item', function(e) {
        const $items = $(this).parent().find('.scn-search-result-item');
        const currentIndex = $items.index(this);
        
        switch(e.which) {
            case 38: // Up arrow
                e.preventDefault();
                if (currentIndex > 0) {
                    $items.eq(currentIndex - 1).focus();
                }
                break;
            case 40: // Down arrow
                e.preventDefault();
                if (currentIndex < $items.length - 1) {
                    $items.eq(currentIndex + 1).focus();
                }
                break;
            case 27: // Escape
                e.preventDefault();
                $(this).parent().empty().hide();
                break;
        }
    });

    // Auto-save functionality for forms
    function autoSave(formSelector, dataKey) {
        const $form = $(formSelector);
        if ($form.length === 0) return;
        
        let saveTimeout;
        
        $form.on('input change', 'input, textarea, select', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                const formData = $form.serialize();
                localStorage.setItem(dataKey, formData);
            }, 1000);
        });
        
        // Restore form data on page load
        const savedData = localStorage.getItem(dataKey);
        if (savedData) {
            const params = new URLSearchParams(savedData);
            params.forEach((value, key) => {
                const $field = $form.find('[name="' + key + '"]');
                if ($field.length > 0) {
                    if ($field.is(':checkbox, :radio')) {
                        $field.filter('[value="' + value + '"]').prop('checked', true);
                    } else {
                        $field.val(value);
                    }
                }
            });
        }
    }

    // Initialize auto-save for merge forms
    autoSave('.scn-merge-container', 'scn_merge_form_data');

    // Clear auto-save data on successful merge
    $(document).on('click', '#scn-execute-merge', function() {
        localStorage.removeItem('scn_merge_form_data');
    });

    // Enhanced loading states
    function setLoadingState(element, loading = true) {
        const $element = $(element);
        
        if (loading) {
            $element.prop('disabled', true);
            $element.data('original-text', $element.text());
            $element.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>' + 
                         '<?php _e('Loading...', 'scn-membership'); ?>');
        } else {
            $element.prop('disabled', false);
            $element.text($element.data('original-text') || $element.text());
        }
    }

    // Global error handler for AJAX requests
    $(document).ajaxError(function(event, xhr, settings) {
        if (settings.url && settings.url.includes('scn_events')) {
            const message = handleAjaxError(xhr, 'error', '');
            showNotification(message, 'error');
        }
    });

    // Accessibility improvements
    function enhanceAccessibility() {
        // Add ARIA labels to interactive elements
        $('.scn-search-result-item').attr('role', 'button');
        $('.scn-alias-item button').attr('aria-label', '<?php _e('Remove alias', 'scn-membership'); ?>');
        $('.scn-field-lock-toggle').attr('aria-label', function() {
            return '<?php _e('Toggle field lock for', 'scn-membership'); ?> ' + $(this).data('field');
        });
        
        // Add keyboard support for custom buttons
        $('[role="button"]').on('keypress', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).click();
            }
        });
    }

    // Initialize accessibility enhancements
    enhanceAccessibility();

    // Re-enhance accessibility after dynamic content changes
    $(document).on('DOMNodeInserted', function() {
        setTimeout(enhanceAccessibility, 100);
    });
});






