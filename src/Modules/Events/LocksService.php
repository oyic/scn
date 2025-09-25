<?php

namespace SCN\Membership\Modules\Events;

class LocksService {
    private $table_name;
    private $lockable_fields = ['official_name', 'dates', 'website'];

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'scn_event_locks';
    }

    public function register() {
        add_action('wp_ajax_scn_lock_event_field', [$this, 'handleLockField']);
        add_action('wp_ajax_scn_unlock_event_field', [$this, 'handleUnlockField']);
        add_action('wp_ajax_scn_get_event_locks', [$this, 'handleGetLocks']);
    }

    public function lockField($event_id, $field, $locked_by = null) {
        global $wpdb;

        if (!$this->isLockableField($field)) {
            return new \WP_Error('invalid_field', __('Field is not lockable.', 'scn-membership'));
        }

        if (!$this->eventExists($event_id)) {
            return new \WP_Error('event_not_found', __('Event not found.', 'scn-membership'));
        }

        if ($locked_by === null) {
            $locked_by = get_current_user_id();
        }

        $event_id = intval($event_id);
        $field = sanitize_text_field($field);
        $locked_by = intval($locked_by);

        $result = $wpdb->replace(
            $this->table_name,
            [
                'event_id' => $event_id,
                'field' => $field,
                'locked_by' => $locked_by,
            ],
            ['%d', '%s', '%d']
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to lock field.', 'scn-membership'));
        }

        $this->updateLockedFieldsMeta($event_id);
        do_action('scn/events/field_locked', $event_id, $field, true);

        return true;
    }

    public function unlockField($event_id, $field) {
        global $wpdb;

        if (!$this->isLockableField($field)) {
            return new \WP_Error('invalid_field', __('Field is not lockable.', 'scn-membership'));
        }

        $event_id = intval($event_id);
        $field = sanitize_text_field($field);

        $result = $wpdb->delete(
            $this->table_name,
            [
                'event_id' => $event_id,
                'field' => $field,
            ],
            ['%d', '%s']
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to unlock field.', 'scn-membership'));
        }

        $this->updateLockedFieldsMeta($event_id);
        do_action('scn/events/field_locked', $event_id, $field, false);

        return true;
    }

    public function getLocks($event_id) {
        global $wpdb;

        $event_id = intval($event_id);
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.field, l.locked_by, l.locked_at, u.display_name as locked_by_name 
                 FROM {$this->table_name} l 
                 LEFT JOIN {$wpdb->users} u ON l.locked_by = u.ID 
                 WHERE l.event_id = %d 
                 ORDER BY l.locked_at ASC",
                $event_id
            )
        );

        return $results ?: [];
    }

    public function isFieldLocked($event_id, $field) {
        global $wpdb;

        $event_id = intval($event_id);
        $field = sanitize_text_field($field);

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE event_id = %d AND field = %s",
                $event_id,
                $field
            )
        );

        return $count > 0;
    }

    public function getLockedFields($event_id) {
        $locks = $this->getLocks($event_id);
        return array_column($locks, 'field');
    }

    public function canUserLockField($user_id, $event_id, $field) {
        if (!current_user_can('lock_scn_events')) {
            return false;
        }

        if ($this->isFieldLocked($event_id, $field)) {
            $locks = $this->getLocks($event_id);
            foreach ($locks as $lock) {
                if ($lock->field === $field) {
                    return intval($lock->locked_by) === intval($user_id);
                }
            }
        }

        return true;
    }

    public function handleLockField() {
        check_ajax_referer('scn_events_nonce', 'nonce');
        
        if (!current_user_can('lock_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $event_id = intval($_POST['event_id'] ?? 0);
        $field = sanitize_text_field($_POST['field'] ?? '');

        $result = $this->lockField($event_id, $field);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('Field locked successfully.', 'scn-membership'),
        ]);
    }

    public function handleUnlockField() {
        check_ajax_referer('scn_events_nonce', 'nonce');
        
        if (!current_user_can('lock_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $event_id = intval($_POST['event_id'] ?? 0);
        $field = sanitize_text_field($_POST['field'] ?? '');

        $result = $this->unlockField($event_id, $field);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('Field unlocked successfully.', 'scn-membership'),
        ]);
    }

    public function handleGetLocks() {
        check_ajax_referer('scn_events_nonce', 'nonce');
        
        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $event_id = intval($_POST['event_id'] ?? 0);
        $locks = $this->getLocks($event_id);

        wp_send_json_success($locks);
    }

    private function isLockableField($field) {
        return in_array($field, $this->lockable_fields, true);
    }

    private function eventExists($event_id) {
        return get_post($event_id) && get_post_type($event_id) === 'scn_event';
    }

    private function updateLockedFieldsMeta($event_id) {
        $locked_fields = $this->getLockedFields($event_id);
        update_post_meta($event_id, 'scn_event_locked_fields', $locked_fields);
    }

    public function getLockableFields() {
        return $this->lockable_fields;
    }
}




