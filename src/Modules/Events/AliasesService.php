<?php

namespace SCN\Membership\Modules\Events;

class AliasesService {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'scn_event_aliases';
    }

    public function register() {
        add_action('wp_ajax_scn_add_event_alias', [$this, 'handleAddAlias']);
        add_action('wp_ajax_scn_remove_event_alias', [$this, 'handleRemoveAlias']);
        add_action('wp_ajax_scn_get_event_aliases', [$this, 'handleGetAliases']);
    }

    public function addAlias($event_id, $alias) {
        global $wpdb;

        if (empty($alias) || empty($event_id)) {
            return new \WP_Error('invalid_data', __('Event ID and alias are required.', 'scn-membership'));
        }

        $alias = sanitize_text_field($alias);
        $event_id = intval($event_id);

        if (!$this->eventExists($event_id)) {
            return new \WP_Error('event_not_found', __('Event not found.', 'scn-membership'));
        }

        if ($this->aliasExists($event_id, $alias)) {
            return new \WP_Error('alias_exists', __('This alias already exists for this event.', 'scn-membership'));
        }

        $result = $wpdb->insert(
            $this->table_name,
            [
                'event_id' => $event_id,
                'alias' => $alias,
            ],
            ['%d', '%s']
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to add alias.', 'scn-membership'));
        }

        do_action('scn/events/alias_added', $event_id, $alias);

        return $wpdb->insert_id;
    }

    public function removeAlias($event_id, $alias) {
        global $wpdb;

        if (empty($alias) || empty($event_id)) {
            return new \WP_Error('invalid_data', __('Event ID and alias are required.', 'scn-membership'));
        }

        $alias = sanitize_text_field($alias);
        $event_id = intval($event_id);

        $result = $wpdb->delete(
            $this->table_name,
            [
                'event_id' => $event_id,
                'alias' => $alias,
            ],
            ['%d', '%s']
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to remove alias.', 'scn-membership'));
        }

        do_action('scn/events/alias_removed', $event_id, $alias);

        return true;
    }

    public function getAliases($event_id) {
        global $wpdb;

        $event_id = intval($event_id);
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, alias, created_at FROM {$this->table_name} WHERE event_id = %d ORDER BY alias ASC",
                $event_id
            )
        );

        return $results ?: [];
    }

    public function getEventByAlias($alias) {
        global $wpdb;

        $alias = sanitize_text_field($alias);
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT event_id FROM {$this->table_name} WHERE alias = %s LIMIT 1",
                $alias
            )
        );

        return $result ? intval($result->event_id) : null;
    }

    public function searchAliases($query, $limit = 10) {
        global $wpdb;

        $query = sanitize_text_field($query);
        $limit = intval($limit);
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ea.event_id, ea.alias, p.post_title 
                 FROM {$this->table_name} ea 
                 JOIN {$wpdb->posts} p ON ea.event_id = p.ID 
                 WHERE ea.alias LIKE %s 
                 AND p.post_type = 'scn_event' 
                 AND p.post_status = 'publish'
                 ORDER BY 
                     CASE WHEN ea.alias = %s THEN 1 ELSE 2 END,
                     ea.alias ASC
                 LIMIT %d",
                '%' . $wpdb->esc_like($query) . '%',
                $query,
                $limit
            )
        );

        return $results ?: [];
    }

    public function moveAliasesToEvent($source_event_ids, $target_event_id) {
        global $wpdb;

        $source_event_ids = array_map('intval', $source_event_ids);
        $target_event_id = intval($target_event_id);

        if (!$this->eventExists($target_event_id)) {
            return new \WP_Error('target_not_found', __('Target event not found.', 'scn-membership'));
        }

        $placeholders = implode(',', array_fill(0, count($source_event_ids), '%d'));
        
        $aliases_to_move = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_id, alias FROM {$this->table_name} WHERE event_id IN ($placeholders)",
                ...$source_event_ids
            )
        );

        $moved_count = 0;
        $skipped_count = 0;

        foreach ($aliases_to_move as $alias_data) {
            if ($this->aliasExists($target_event_id, $alias_data->alias)) {
                $skipped_count++;
                continue;
            }

            $result = $wpdb->update(
                $this->table_name,
                ['event_id' => $target_event_id],
                [
                    'event_id' => $alias_data->event_id,
                    'alias' => $alias_data->alias,
                ],
                ['%d'],
                ['%d', '%s']
            );

            if ($result !== false) {
                $moved_count++;
            }
        }

        return [
            'moved' => $moved_count,
            'skipped' => $skipped_count,
        ];
    }

    public function handleAddAlias() {
        check_ajax_referer('scn_events_nonce', 'nonce');
        
        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $event_id = intval($_POST['event_id'] ?? 0);
        $alias = sanitize_text_field($_POST['alias'] ?? '');

        $result = $this->addAlias($event_id, $alias);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('Alias added successfully.', 'scn-membership'),
            'alias_id' => $result,
        ]);
    }

    public function handleRemoveAlias() {
        check_ajax_referer('scn_events_nonce', 'nonce');
        
        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $event_id = intval($_POST['event_id'] ?? 0);
        $alias = sanitize_text_field($_POST['alias'] ?? '');

        $result = $this->removeAlias($event_id, $alias);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('Alias removed successfully.', 'scn-membership'),
        ]);
    }

    public function handleGetAliases() {
        check_ajax_referer('scn_events_nonce', 'nonce');
        
        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $event_id = intval($_POST['event_id'] ?? 0);
        $aliases = $this->getAliases($event_id);

        wp_send_json_success($aliases);
    }

    private function eventExists($event_id) {
        return get_post($event_id) && get_post_type($event_id) === 'scn_event';
    }

    private function aliasExists($event_id, $alias) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE event_id = %d AND alias = %s",
                $event_id,
                $alias
            )
        );

        return $count > 0;
    }
}






