<?php

namespace SCN\Membership\Modules\Events\Admin;

class AliasesPage {
    public function register() {
        // Admin menu is now handled by AdminService
    }

    public function renderPage() {
        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Event Aliases Management', 'scn-membership'); ?></h1>
            
            <div class="scn-aliases-container">
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

                <div class="scn-aliases-management" id="scn-aliases-management" style="display: none;">
                    <h2><?php _e('Manage Aliases', 'scn-membership'); ?></h2>
                    <div class="scn-selected-event">
                        <strong><?php _e('Selected Event:', 'scn-membership'); ?></strong>
                        <span id="scn-selected-event-name"></span>
                        <input type="hidden" id="scn-selected-event-id" />
                    </div>

                    <div class="scn-add-alias">
                        <h3><?php _e('Add New Alias', 'scn-membership'); ?></h3>
                        <div class="scn-add-alias-form">
                            <input type="text" 
                                   id="scn-new-alias" 
                                   placeholder="<?php _e('Enter alias...', 'scn-membership'); ?>" 
                                   class="regular-text" />
                            <button type="button" 
                                    id="scn-add-alias-btn" 
                                    class="button button-primary">
                                <?php _e('Add Alias', 'scn-membership'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="scn-existing-aliases">
                        <h3><?php _e('Existing Aliases', 'scn-membership'); ?></h3>
                        <div id="scn-aliases-list" class="scn-aliases-list">
                            <p class="scn-loading"><?php _e('Loading aliases...', 'scn-membership'); ?></p>
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
                $('#scn-aliases-management').show();
                $('#scn-event-search-results').empty();
                $('#scn-event-search').val('');
                loadAliases(eventId);
            }

            function loadAliases(eventId) {
                $.ajax({
                    url: scnEvents.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'scn_get_event_aliases',
                        nonce: scnEvents.nonce,
                        event_id: eventId
                    },
                    success: function(response) {
                        if (response.success) {
                            displayAliases(response.data);
                        }
                    }
                });
            }

            function displayAliases(aliases) {
                const $list = $('#scn-aliases-list');
                $list.empty();

                if (aliases.length === 0) {
                    $list.html('<p><?php _e('No aliases found for this event.', 'scn-membership'); ?></p>');
                    return;
                }

                aliases.forEach(function(alias) {
                    const $item = $('<div class="scn-alias-item">')
                        .html('<span class="scn-alias-text">' + alias.alias + '</span>' +
                              '<button type="button" class="button button-small scn-remove-alias" ' +
                              'data-alias="' + alias.alias + '"><?php _e('Remove', 'scn-membership'); ?></button>');
                    
                    $list.append($item);
                });
            }

            $('#scn-add-alias-btn').click(function() {
                const alias = $('#scn-new-alias').val().trim();
                if (!alias || !selectedEventId) {
                    return;
                }

                $.ajax({
                    url: scnEvents.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'scn_add_event_alias',
                        nonce: scnEvents.nonce,
                        event_id: selectedEventId,
                        alias: alias
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#scn-new-alias').val('');
                            loadAliases(selectedEventId);
                        } else {
                            alert(response.data);
                        }
                    }
                });
            });

            $(document).on('click', '.scn-remove-alias', function() {
                const alias = $(this).data('alias');
                if (!alias || !selectedEventId) {
                    return;
                }

                if (!confirm('<?php _e('Are you sure you want to remove this alias?', 'scn-membership'); ?>')) {
                    return;
                }

                $.ajax({
                    url: scnEvents.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'scn_remove_event_alias',
                        nonce: scnEvents.nonce,
                        event_id: selectedEventId,
                        alias: alias
                    },
                    success: function(response) {
                        if (response.success) {
                            loadAliases(selectedEventId);
                        } else {
                            alert(response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
}




