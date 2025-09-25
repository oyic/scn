<?php

namespace SCN\Membership\Modules\Events;

class SessionApprovalService {
    private $sessions_service;

    public function __construct() {
        $this->sessions_service = new SessionsService();
    }

    public function register() {
        add_action('wp_ajax_scn_approve_session', [$this, 'approveSession']);
        add_action('wp_ajax_scn_reject_session', [$this, 'rejectSession']);
        add_action('wp_ajax_scn_get_pending_sessions', [$this, 'getPendingSessions']);
        add_action('wp_ajax_scn_bulk_approve_sessions', [$this, 'bulkApproveSessions']);
    }

    public function approveSession($session_id) {
        global $wpdb;

        if (!current_user_can('manage_scn_events')) {
            return new \WP_Error('insufficient_permissions', __('Insufficient permissions.', 'scn-membership'));
        }

        $session = $this->sessions_service->getSession($session_id);
        if (!$session) {
            return new \WP_Error('session_not_found', __('Session not found.', 'scn-membership'));
        }

        if ($session->status === 'approved') {
            return new \WP_Error('already_approved', __('Session is already approved.', 'scn-membership'));
        }

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $result = $wpdb->update(
            $table_name,
            ['status' => 'approved'],
            ['id' => $session_id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            return new \WP_Error('database_error', __('Failed to approve session.', 'scn-membership'));
        }

        do_action('scn/events/session_status_changed', $session_id, 'approved', $session);

        $this->clearWidgetCache();

        return ['message' => __('Session approved successfully.', 'scn-membership')];
    }

    public function rejectSession($session_id, $reason = '') {
        global $wpdb;

        if (!current_user_can('manage_scn_events')) {
            return new \WP_Error('insufficient_permissions', __('Insufficient permissions.', 'scn-membership'));
        }

        $session = $this->sessions_service->getSession($session_id);
        if (!$session) {
            return new \WP_Error('session_not_found', __('Session not found.', 'scn-membership'));
        }

        if ($session->status === 'rejected') {
            return new \WP_Error('already_rejected', __('Session is already rejected.', 'scn-membership'));
        }

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $result = $wpdb->update(
            $table_name,
            ['status' => 'rejected'],
            ['id' => $session_id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            return new \WP_Error('database_error', __('Failed to reject session.', 'scn-membership'));
        }

        do_action('scn/events/session_status_changed', $session_id, 'rejected', $session);

        $this->notifyMemberOfRejection($session, $reason);

        return ['message' => __('Session rejected successfully.', 'scn-membership')];
    }

    public function getPendingSessions($limit = 50) {
        global $wpdb;

        if (!current_user_can('manage_scn_events')) {
            return new \WP_Error('insufficient_permissions', __('Insufficient permissions.', 'scn-membership'));
        }

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $sql = "SELECT s.*, 
                       p.post_title as profile_name,
                       p.guid as profile_url,
                       e.post_title as event_name,
                       e.post_name as event_slug,
                       c.post_title as course_title
                FROM $table_name s
                LEFT JOIN {$wpdb->posts} p ON s.profile_id = p.ID
                LEFT JOIN {$wpdb->posts} e ON s.event_id = e.ID
                LEFT JOIN {$wpdb->posts} c ON s.course_id = c.ID
                WHERE s.status = 'pending'
                ORDER BY s.created_at ASC
                LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }

    public function bulkApproveSessions($session_ids) {
        if (!current_user_can('manage_scn_events')) {
            return new \WP_Error('insufficient_permissions', __('Insufficient permissions.', 'scn-membership'));
        }

        if (empty($session_ids) || !is_array($session_ids)) {
            return new \WP_Error('invalid_data', __('Invalid session IDs.', 'scn-membership'));
        }

        $approved_count = 0;
        $errors = [];

        foreach ($session_ids as $session_id) {
            $result = $this->approveSession($session_id);
            if (is_wp_error($result)) {
                $errors[] = sprintf(__('Session %d: %s', 'scn-membership'), $session_id, $result->get_error_message());
            } else {
                $approved_count++;
            }
        }

        $this->clearWidgetCache();

        return [
            'approved_count' => $approved_count,
            'errors' => $errors,
            'message' => sprintf(__('%d sessions approved successfully.', 'scn-membership'), $approved_count)
        ];
    }

    public function getSessionStats() {
        global $wpdb;

        if (!current_user_can('manage_scn_events')) {
            return new \WP_Error('insufficient_permissions', __('Insufficient permissions.', 'scn-membership'));
        }

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
             FROM $table_name"
        );

        return $stats;
    }

    public function handleApproveSession() {
        check_ajax_referer('scn_events_nonce', 'nonce');

        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $session_id = intval($_POST['session_id'] ?? 0);
        $result = $this->approveSession($session_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    public function handleRejectSession() {
        check_ajax_referer('scn_events_nonce', 'nonce');

        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $session_id = intval($_POST['session_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        $result = $this->rejectSession($session_id, $reason);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    public function handleGetPendingSessions() {
        check_ajax_referer('scn_events_nonce', 'nonce');

        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $limit = intval($_GET['limit'] ?? 50);
        $sessions = $this->getPendingSessions($limit);

        if (is_wp_error($sessions)) {
            wp_send_json_error($sessions->get_error_message());
        }

        wp_send_json_success($sessions);
    }

    public function handleBulkApproveSessions() {
        check_ajax_referer('scn_events_nonce', 'nonce');

        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $session_ids = array_map('intval', $_POST['session_ids'] ?? []);
        $result = $this->bulkApproveSessions($session_ids);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    private function notifyMemberOfRejection($session, $reason) {
        $profile = get_post($session->profile_id);
        if (!$profile) {
            return;
        }

        $user = get_user_by('login', $profile->post_name);
        if (!$user) {
            return;
        }

        $event = get_post($session->event_id);
        $course = get_post($session->course_id);

        $subject = sprintf(__('Session Rejection: %s', 'scn-membership'), $session->session_title);
        $message = sprintf(
            __("Your session submission has been rejected.\n\nEvent: %s\nCourse: %s\nSession: %s\n\nReason: %s", 'scn-membership'),
            $event ? $event->post_title : '',
            $course ? $course->post_title : '',
            $session->session_title,
            $reason ?: __('No reason provided.', 'scn-membership')
        );

        wp_mail($user->user_email, $subject, $message);
    }

    private function clearWidgetCache() {
        delete_transient('scn_events_widget_upcoming_sessions');
    }
}




