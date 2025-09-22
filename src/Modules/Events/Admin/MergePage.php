<?php

namespace SCN\Membership\Modules\Events\Admin;

class MergePage {
    public function register() {
        // Admin menu is now handled by AdminService
    }

    public function renderPage() {
        if (!current_user_can('merge_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Merge Events', 'scn-membership'); ?></h1>
            
            <div class="scn-merge-container">
                <div class="scn-merge-step" id="scn-step-1">
                    <h2><?php _e('Step 1: Select Source Events', 'scn-membership'); ?></h2>
                    <div class="scn-search-events">
                        <input type="text" 
                               id="scn-source-search" 
                               placeholder="<?php _e('Search events to merge...', 'scn-membership'); ?>" 
                               class="regular-text" />
                        <div id="scn-source-search-results" class="scn-search-results"></div>
                    </div>
                    <div class="scn-selected-sources">
                        <h3><?php _e('Selected Source Events', 'scn-membership'); ?></h3>
                        <div id="scn-source-events-list" class="scn-selected-events-list">
                            <p><?php _e('No source events selected.', 'scn-membership'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="scn-merge-step" id="scn-step-2" style="display: none;">
                    <h2><?php _e('Step 2: Select Target Event', 'scn-membership'); ?></h2>
                    <div class="scn-search-events">
                        <input type="text" 
                               id="scn-target-search" 
                               placeholder="<?php _e('Search for target event...', 'scn-membership'); ?>" 
                               class="regular-text" />
                        <div id="scn-target-search-results" class="scn-search-results"></div>
                    </div>
                    <div class="scn-selected-target">
                        <h3><?php _e('Selected Target Event', 'scn-membership'); ?></h3>
                        <div id="scn-target-event-display" class="scn-selected-event-display">
                            <p><?php _e('No target event selected.', 'scn-membership'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="scn-merge-step" id="scn-step-3" style="display: none;">
                    <h2><?php _e('Step 3: Preview Merge', 'scn-membership'); ?></h2>
                    <div id="scn-merge-preview" class="scn-merge-preview">
                        <p><?php _e('Loading preview...', 'scn-membership'); ?></p>
                    </div>
                    <div class="scn-merge-actions">
                        <button type="button" 
                                id="scn-execute-merge" 
                                class="button button-primary">
                            <?php _e('Execute Merge', 'scn-membership'); ?>
                        </button>
                        <button type="button" 
                                id="scn-cancel-merge" 
                                class="button">
                            <?php _e('Cancel', 'scn-membership'); ?>
                        </button>
                    </div>
                </div>

                <div class="scn-merge-step" id="scn-step-4" style="display: none;">
                    <h2><?php _e('Merge Complete', 'scn-membership'); ?></h2>
                    <div id="scn-merge-results" class="scn-merge-results">
                        <p><?php _e('Processing merge results...', 'scn-membership'); ?></p>
                    </div>
                    <div class="scn-merge-actions">
                        <button type="button" 
                                id="scn-start-new-merge" 
                                class="button button-primary">
                            <?php _e('Start New Merge', 'scn-membership'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let sourceEventIds = [];
            let targetEventId = null;
            let searchTimeout = null;

            function searchEvents(query, resultsContainer, onSelect) {
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
                        }
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
                    const $item = $('<div class="scn-search-result-item">')
                        .html('<strong>' + event.official_name + '</strong><br>' +
                              (event.location.city ? event.location.city + ', ' : '') +
                              (event.location.region ? event.location.region : '') +
                              (event.location.country ? ', ' + event.location.country : '') +
                              (event.year ? ' (' + event.year + ')' : ''))
                        .data('event-id', event.id)
                        .data('event-name', event.official_name)
                        .click(function() {
                            onSelect($(this).data('event-id'), $(this).data('event-name'));
                        });
                    
                    $results.append($item);
                });
            }

            $('#scn-source-search').on('input', function() {
                const query = $(this).val();
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (query.length >= 2) {
                        searchEvents(query, '#scn-source-search-results', selectSourceEvent);
                    } else {
                        $('#scn-source-search-results').empty();
                    }
                }, 300);
            });

            $('#scn-target-search').on('input', function() {
                const query = $(this).val();
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (query.length >= 2) {
                        searchEvents(query, '#scn-target-search-results', selectTargetEvent);
                    } else {
                        $('#scn-target-search-results').empty();
                    }
                }, 300);
            });

            function selectSourceEvent(eventId, eventName) {
                if (!sourceEventIds.includes(eventId)) {
                    sourceEventIds.push(eventId);
                    updateSourceEventsList();
                }
                $('#scn-source-search-results').empty();
                $('#scn-source-search').val('');
            }

            function selectTargetEvent(eventId, eventName) {
                targetEventId = eventId;
                updateTargetEventDisplay(eventName);
                $('#scn-target-search-results').empty();
                $('#scn-target-search').val('');
            }

            function updateSourceEventsList() {
                const $list = $('#scn-source-events-list');
                $list.empty();

                if (sourceEventIds.length === 0) {
                    $list.html('<p><?php _e('No source events selected.', 'scn-membership'); ?></p>');
                    return;
                }

                sourceEventIds.forEach(function(eventId) {
                    const $item = $('<div class="scn-selected-event-item">')
                        .html('<span class="event-name">Event ID: ' + eventId + '</span>' +
                              '<button type="button" class="button button-small scn-remove-source" ' +
                              'data-event-id="' + eventId + '"><?php _e('Remove', 'scn-membership'); ?></button>');
                    
                    $list.append($item);
                });

                if (sourceEventIds.length > 0) {
                    $('#scn-step-2').show();
                }
            }

            function updateTargetEventDisplay(eventName) {
                const $display = $('#scn-target-event-display');
                $display.html('<strong>' + eventName + '</strong>');

                if (sourceEventIds.length > 0 && targetEventId) {
                    $('#scn-step-3').show();
                    loadMergePreview();
                }
            }

            function loadMergePreview() {
                $.ajax({
                    url: scnEvents.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'scn_merge_events_preview',
                        nonce: scnEvents.nonce,
                        source_ids: sourceEventIds,
                        target_id: targetEventId
                    },
                    success: function(response) {
                        if (response.success) {
                            displayMergePreview(response.data);
                        }
                    }
                });
            }

            function displayMergePreview(preview) {
                const $preview = $('#scn-merge-preview');
                let html = '<h3><?php _e('Merge Preview', 'scn-membership'); ?></h3>';
                
                html += '<div class="scn-preview-section">';
                html += '<h4><?php _e('Target Event', 'scn-membership'); ?></h4>';
                html += '<p><strong>' + preview.target_event.title + '</strong></p>';
                html += '</div>';

                html += '<div class="scn-preview-section">';
                html += '<h4><?php _e('Source Events', 'scn-membership'); ?></h4>';
                preview.source_events.forEach(function(event) {
                    html += '<p>' + event.title + '</p>';
                });
                html += '</div>';

                if (preview.aliases_to_move.length > 0) {
                    html += '<div class="scn-preview-section">';
                    html += '<h4><?php _e('Aliases to Move', 'scn-membership'); ?></h4>';
                    html += '<ul>';
                    preview.aliases_to_move.forEach(function(alias) {
                        html += '<li>' + alias.alias + '</li>';
                    });
                    html += '</ul>';
                    html += '</div>';
                }

                if (preview.fields_to_copy.length > 0) {
                    html += '<div class="scn-preview-section">';
                    html += '<h4><?php _e('Fields to Copy', 'scn-membership'); ?></h4>';
                    html += '<ul>';
                    preview.fields_to_copy.forEach(function(fieldGroup) {
                        html += '<li>Event ' + fieldGroup.event_id + ': ' + fieldGroup.fields.join(', ') + '</li>';
                    });
                    html += '</ul>';
                    html += '</div>';
                }

                if (preview.warnings.length > 0) {
                    html += '<div class="scn-preview-section scn-warnings">';
                    html += '<h4><?php _e('Warnings', 'scn-membership'); ?></h4>';
                    html += '<ul>';
                    preview.warnings.forEach(function(warning) {
                        html += '<li>' + warning + '</li>';
                    });
                    html += '</ul>';
                    html += '</div>';
                }

                $preview.html(html);
            }

            $(document).on('click', '.scn-remove-source', function() {
                const eventId = parseInt($(this).data('event-id'));
                sourceEventIds = sourceEventIds.filter(id => id !== eventId);
                updateSourceEventsList();
                
                if (sourceEventIds.length === 0) {
                    $('#scn-step-2').hide();
                    $('#scn-step-3').hide();
                }
            });

            $('#scn-execute-merge').click(function() {
                if (!confirm('<?php _e('Are you sure you want to execute this merge? This action cannot be undone.', 'scn-membership'); ?>')) {
                    return;
                }

                $.ajax({
                    url: scnEvents.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'scn_merge_events',
                        nonce: scnEvents.nonce,
                        source_ids: sourceEventIds,
                        target_id: targetEventId
                    },
                    success: function(response) {
                        if (response.success) {
                            displayMergeResults(response.data.report);
                            $('#scn-step-4').show();
                        } else {
                            alert(response.data);
                        }
                    }
                });
            });

            function displayMergeResults(report) {
                const $results = $('#scn-merge-results');
                let html = '<h3><?php _e('Merge Results', 'scn-membership'); ?></h3>';
                
                html += '<ul>';
                html += '<li><?php _e('Aliases moved:', 'scn-membership'); ?> ' + report.aliases_moved + '</li>';
                html += '<li><?php _e('Aliases skipped:', 'scn-membership'); ?> ' + report.aliases_skipped + '</li>';
                html += '<li><?php _e('Fields copied:', 'scn-membership'); ?> ' + report.fields_copied + '</li>';
                html += '<li><?php _e('Fields skipped:', 'scn-membership'); ?> ' + report.fields_skipped + '</li>';
                html += '<li><?php _e('Source events trashed:', 'scn-membership'); ?> ' + report.sources_trashed + '</li>';
                html += '</ul>';

                if (report.errors.length > 0) {
                    html += '<h4><?php _e('Errors', 'scn-membership'); ?></h4>';
                    html += '<ul>';
                    report.errors.forEach(function(error) {
                        html += '<li class="scn-error">' + error + '</li>';
                    });
                    html += '</ul>';
                }

                $results.html(html);
            }

            $('#scn-cancel-merge').click(function() {
                sourceEventIds = [];
                targetEventId = null;
                $('#scn-step-1').show();
                $('#scn-step-2').hide();
                $('#scn-step-3').hide();
                $('#scn-step-4').hide();
                updateSourceEventsList();
                $('#scn-target-event-display').html('<p><?php _e('No target event selected.', 'scn-membership'); ?></p>');
            });

            $('#scn-start-new-merge').click(function() {
                $('#scn-cancel-merge').click();
            });
        });
        </script>
        <?php
    }
}




