<?php

namespace SCN\Membership\Modules\Events;

class MergeService {
    private $aliases_service;

    public function __construct() {
        $this->aliases_service = new AliasesService();
    }

    public function register() {
        add_action('wp_ajax_scn_merge_events', [$this, 'handleMergeEvents']);
        add_action('wp_ajax_scn_merge_events_preview', [$this, 'handleMergePreview']);
    }

    public function mergeEvents($source_ids, $target_id) {
        if (!current_user_can('merge_scn_events')) {
            return new \WP_Error('insufficient_permissions', __('Insufficient permissions to merge events.', 'scn-membership'));
        }

        $source_ids = array_map('intval', $source_ids);
        $target_id = intval($target_id);

        if (in_array($target_id, $source_ids)) {
            return new \WP_Error('invalid_target', __('Target event cannot be in source events list.', 'scn-membership'));
        }

        if (!$this->eventExists($target_id)) {
            return new \WP_Error('target_not_found', __('Target event not found.', 'scn-membership'));
        }

        foreach ($source_ids as $source_id) {
            if (!$this->eventExists($source_id)) {
                return new \WP_Error('source_not_found', sprintf(__('Source event %d not found.', 'scn-membership'), $source_id));
            }
        }

        do_action('scn/events/merge/before', $source_ids, $target_id);

        $report = [
            'aliases_moved' => 0,
            'aliases_skipped' => 0,
            'fields_copied' => 0,
            'fields_skipped' => 0,
            'sources_trashed' => 0,
            'errors' => [],
        ];

        $alias_result = $this->aliases_service->moveAliasesToEvent($source_ids, $target_id);
        if (is_wp_error($alias_result)) {
            $report['errors'][] = $alias_result->get_error_message();
        } else {
            $report['aliases_moved'] = $alias_result['moved'];
            $report['aliases_skipped'] = $alias_result['skipped'];
        }

        $field_result = $this->copyFields($source_ids, $target_id);
        $report['fields_copied'] = $field_result['copied'];
        $report['fields_skipped'] = $field_result['skipped'];

        foreach ($source_ids as $source_id) {
            $trash_result = wp_trash_post($source_id);
            if ($trash_result) {
                $report['sources_trashed']++;
            } else {
                $report['errors'][] = sprintf(__('Failed to trash source event %d.', 'scn-membership'), $source_id);
            }
        }

        do_action('scn/events/merge/after', $source_ids, $target_id, $report);

        return $report;
    }

    public function previewMerge($source_ids, $target_id) {
        $source_ids = array_map('intval', $source_ids);
        $target_id = intval($target_id);

        $preview = [
            'target_event' => $this->getEventData($target_id),
            'source_events' => [],
            'aliases_to_move' => [],
            'fields_to_copy' => [],
            'warnings' => [],
        ];

        if (!$preview['target_event']) {
            return new \WP_Error('target_not_found', __('Target event not found.', 'scn-membership'));
        }

        foreach ($source_ids as $source_id) {
            $source_event = $this->getEventData($source_id);
            if (!$source_event) {
                $preview['warnings'][] = sprintf(__('Source event %d not found.', 'scn-membership'), $source_id);
                continue;
            }

            $preview['source_events'][] = $source_event;

            $aliases = $this->aliases_service->getAliases($source_id);
            foreach ($aliases as $alias) {
                $preview['aliases_to_move'][] = [
                    'event_id' => $source_id,
                    'alias' => $alias->alias,
                ];
            }

            $fields_to_copy = $this->getFieldsToCopy($source_id, $target_id);
            if (!empty($fields_to_copy)) {
                $preview['fields_to_copy'][] = [
                    'event_id' => $source_id,
                    'fields' => $fields_to_copy,
                ];
            }
        }

        return $preview;
    }

    public function handleMergeEvents() {
        check_ajax_referer('scn_events_nonce', 'nonce');
        
        if (!current_user_can('merge_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $source_ids = array_map('intval', $_POST['source_ids'] ?? []);
        $target_id = intval($_POST['target_id'] ?? 0);

        if (empty($source_ids) || !$target_id) {
            wp_send_json_error(__('Source events and target event are required.', 'scn-membership'));
        }

        $result = $this->mergeEvents($source_ids, $target_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('Events merged successfully.', 'scn-membership'),
            'report' => $result,
        ]);
    }

    public function handleMergePreview() {
        check_ajax_referer('scn_events_nonce', 'nonce');
        
        if (!current_user_can('merge_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $source_ids = array_map('intval', $_POST['source_ids'] ?? []);
        $target_id = intval($_POST['target_id'] ?? 0);

        if (empty($source_ids) || !$target_id) {
            wp_send_json_error(__('Source events and target event are required.', 'scn-membership'));
        }

        $result = $this->previewMerge($source_ids, $target_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    private function copyFields($source_ids, $target_id) {
        $target_locks = get_post_meta($target_id, 'scn_event_locked_fields', true) ?: [];
        $target_post = get_post($target_id);
        
        $copied = 0;
        $skipped = 0;

        foreach ($source_ids as $source_id) {
            $source_post = get_post($source_id);
            if (!$source_post) {
                continue;
            }

            $fields_to_copy = $this->getFieldsToCopy($source_id, $target_id);
            
            foreach ($fields_to_copy as $field) {
                if (in_array($field, $target_locks)) {
                    $skipped++;
                    continue;
                }

                $value = $this->getFieldValue($source_id, $field);
                if ($value !== null) {
                    $this->setFieldValue($target_id, $field, $value);
                    $copied++;
                }
            }
        }

        return ['copied' => $copied, 'skipped' => $skipped];
    }

    private function getFieldsToCopy($source_id, $target_id) {
        $fields = [];
        $target_locks = get_post_meta($target_id, 'scn_event_locked_fields', true) ?: [];

        $copyable_fields = [
            'scn_event_location_city',
            'scn_event_location_region', 
            'scn_event_location_country',
            'scn_event_website',
        ];

        foreach ($copyable_fields as $field) {
            if (in_array($field, $target_locks)) {
                continue;
            }

            $target_value = get_post_meta($target_id, $field, true);
            if (empty($target_value)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    private function getFieldValue($event_id, $field) {
        return get_post_meta($event_id, $field, true);
    }

    private function setFieldValue($event_id, $field, $value) {
        update_post_meta($event_id, $field, $value);
    }

    private function getEventData($event_id) {
        $post = get_post($event_id);
        if (!$post || $post->post_type !== 'scn_event') {
            return null;
        }

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'city' => get_post_meta($event_id, 'scn_event_location_city', true),
            'region' => get_post_meta($event_id, 'scn_event_location_region', true),
            'country' => get_post_meta($event_id, 'scn_event_location_country', true),
            'website' => get_post_meta($event_id, 'scn_event_website', true),
            'year' => get_post_meta($event_id, 'scn_event_year', true),
        ];
    }

    private function eventExists($event_id) {
        $post = get_post($event_id);
        return $post && $post->post_type === 'scn_event';
    }
}




