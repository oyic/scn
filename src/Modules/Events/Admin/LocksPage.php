<?php

namespace SCN\Membership\Modules\Events\Admin;

use SCN\Membership\Modules\Events\LocksService;

class LocksPage {
    private $locks_service;

    public function __construct() {
        $this->locks_service = new LocksService();
    }

    public function register() {
        // Admin menu is now handled by AdminService
    }

    public function renderPage() {
        if (!current_user_can('lock_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $lockable_fields = $this->locks_service->getLockableFields();
        $field_labels = [
            'official_name' => __('Official Name', 'scn-membership'),
            'dates' => __('Event Dates', 'scn-membership'),
            'website' => __('Website URL', 'scn-membership'),
        ];

        ?>
        <div class="wrap">
            <h1><?php _e('Event Field Locks Management', 'scn-membership'); ?></h1>
            
            <div class="scn-locks-container">
                <div class="scn-event-selector">
                    <h2><?php _e('Select Event', 'scn-membership'); ?></h2>
                    <div class="scn-search-events">
                        <input type="text" 
                               id="scn-event-search" 
                               placeholder="<?php _e('Search events by name or alias...', 'scn-membership'); ?>" 
                               class="regular-text" />
                        <div id="scn-event-search-results" class="scn-search-results"></div>
                    </div>
                </div>

                <div class="scn-locks-management" id="scn-locks-management" style="display: none;">
                    <h2><?php _e('Manage Field Locks', 'scn-membership'); ?></h2>
                    <div class="scn-selected-event">
                        <strong><?php _e('Selected Event:', 'scn-membership'); ?></strong>
                        <span id="scn-selected-event-name"></span>
                        <input type="hidden" id="scn-selected-event-id" />
                    </div>

                    <div class="scn-field-locks">
                        <h3><?php _e('Lockable Fields', 'scn-membership'); ?></h3>
                        <div id="scn-locks-list" class="scn-locks-list">
                            <p class="scn-loading"><?php _e('Loading locks...', 'scn-membership'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let selectedEventId = null;
            let searchTimeout = null;

            $('#scn-event-search').on('input', function() {
                const query = $(this).val();
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (query.length >= 2) {
                        searchEvents(query);
                    } else {
                        $('#scn-event-search-results').empty();
                    }
                }, 300);
            });

            function searchEvents(query) {
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
                            displaySearchResults(response.data);
                        }
                    }
                });
            }

            function displaySearchResults(events) {
                const $results = $('#scn-event-search-results');
                $results.empty();

                if (events.length === 0) {
                    $results.html('<p><?php _e('No events found.', 'scn-membership'); ?></p>');
                    return;
                }

                events.forEach(function(event) {
                    const $item = $('<div class="scn-search-result-item">')
                        .html('<strong>' + event.official_name + '</strong><br>' +
                              (event.location.city ? event.location.city + ', ' : '') +
                              (event.location.region ? event.location.region : '') +
                              (event.location.country ? ', ' + event.location.country : '') +
                              (event.year ? ' (' + event.year + ')' : ''))
                        .data('event-id', event.id)
                        .data('event-name', event.official_name)
                        .click(function() {
                            selectEvent($(this).data('event-id'), $(this).data('event-name'));
                        });
                    
                    $results.append($item);
                });
            }

            function selectEvent(eventId, eventName) {
                selectedEventId = eventId;
                $('#scn-selected-event-id').val(eventId);
                $('#scn-selected-event-name').text(eventName);
                $('#scn-locks-management').show();
                $('#scn-event-search-results').empty();
                $('#scn-event-search').val('');
                loadLocks(eventId);
            }

            function loadLocks(eventId) {
                $.ajax({
                    url: scnEvents.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'scn_get_event_locks',
                        nonce: scnEvents.nonce,
                        event_id: eventId
                    },
                    success: function(response) {
                        if (response.success) {
                            displayLocks(response.data);
                        }
                    }
                });
            }

            function displayLocks(locks) {
                const $list = $('#scn-locks-list');
                $list.empty();

                const lockableFields = <?php echo json_encode($lockable_fields); ?>;
                const fieldLabels = <?php echo json_encode($field_labels); ?>;
                const lockedFields = locks.map(lock => lock.field);

                lockableFields.forEach(function(field) {
                    const isLocked = lockedFields.includes(field);
                    const lock = locks.find(l => l.field === field);
                    
                    const $item = $('<div class="scn-lock-item">')
                        .html('<label>' +
                              '<input type="checkbox" ' + (isLocked ? 'checked' : '') + ' ' +
                              'data-field="' + field + '" class="scn-field-lock-toggle" /> ' +
                              fieldLabels[field] + 
                              (isLocked && lock ? 
                                  ' <span class="scn-lock-info">(' + 
                                  '<?php _e('Locked by', 'scn-membership'); ?> ' + 
                                  lock.locked_by_name + ' ' + 
                                  '<?php _e('on', 'scn-membership'); ?> ' + 
                                  new Date(lock.locked_at).toLocaleDateString() + 
                                  ')</span>' : '') +
                              '</label>');
                    
                    $list.append($item);
                });
            }

            $(document).on('change', '.scn-field-lock-toggle', function() {
                const field = $(this).data('field');
                const isLocked = $(this).is(':checked');
                const action = isLocked ? 'scn_lock_event_field' : 'scn_unlock_event_field';

                if (!selectedEventId) {
                    return;
                }

                $.ajax({
                    url: scnEvents.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: action,
                        nonce: scnEvents.nonce,
                        event_id: selectedEventId,
                        field: field
                    },
                    success: function(response) {
                        if (response.success) {
                            loadLocks(selectedEventId);
                        } else {
                            alert(response.data);
                            $(this).prop('checked', !isLocked);
                        }
                    },
                    error: function() {
                        $(this).prop('checked', !isLocked);
                    }
                });
            });
        });
        </script>
        <?php
    }
}




