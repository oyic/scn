<?php

namespace SCN\Membership\Modules\Events;

class SessionsService {
    private $search_service;

    public function __construct() {
        $this->search_service = new EventsSearchService();
    }

    public function register() {
        add_action('wp_ajax_scn_get_member_courses', [$this, 'getMemberCourses']);
        add_action('wp_ajax_scn_create_session', [$this, 'createSession']);
        add_action('wp_ajax_scn_update_session', [$this, 'updateSession']);
        add_action('wp_ajax_scn_delete_session', [$this, 'deleteSession']);
        add_action('wp_ajax_scn_get_sessions', [$this, 'getSessions']);
    }

    public function createSession($data) {
        global $wpdb;

        $event_id = intval($data['event_id'] ?? 0);
        $course_id = intval($data['course_id'] ?? 0);
        $profile_id = intval($data['profile_id'] ?? 0);
        $session_title = sanitize_text_field($data['session_title'] ?? '');
        $session_datetime = sanitize_text_field($data['session_datetime'] ?? '');
        $room = sanitize_text_field($data['room'] ?? '');
        $type_override = sanitize_text_field($data['type_override'] ?? '');

        if (!$event_id || !$course_id || !$profile_id || !$session_title || !$session_datetime) {
            return new \WP_Error('missing_required_fields', __('Missing required fields.', 'scn-membership'));
        }

        if (!current_user_can('edit_scn_profiles') || get_current_user_id() !== $profile_id) {
            return new \WP_Error('insufficient_permissions', __('Insufficient permissions.', 'scn-membership'));
        }

        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'scn_event') {
            return new \WP_Error('invalid_event', __('Invalid event.', 'scn-membership'));
        }

        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'scn_course') {
            return new \WP_Error('invalid_course', __('Invalid course.', 'scn-membership'));
        }

        $profile = get_post($profile_id);
        if (!$profile || $profile->post_type !== 'scn_profile') {
            return new \WP_Error('invalid_profile', __('Invalid profile.', 'scn-membership'));
        }

        $event_dates = get_post_meta($event_id, 'scn_event_dates', true) ?: ['start' => '', 'end' => ''];
        $session_date = new \DateTime($session_datetime);
        $event_start = !empty($event_dates['start']) ? new \DateTime($event_dates['start']) : null;
        $event_end = !empty($event_dates['end']) ? new \DateTime($event_dates['end']) : null;

        if ($event_start && $session_date < $event_start) {
            return new \WP_Error('session_before_event', __('Session date cannot be before event start date.', 'scn-membership'));
        }

        if ($event_end && $session_date > $event_end) {
            return new \WP_Error('session_after_event', __('Session date cannot be after event end date.', 'scn-membership'));
        }

        $is_past_event = $event_end && $event_end < new \DateTime();
        $status = ($is_past_event && !current_user_can('manage_scn_events')) ? 'pending' : 'approved';

        $session_data = apply_filters('scn/events/session_pre_insert', [
            'event_id' => $event_id,
            'course_id' => $course_id,
            'profile_id' => $profile_id,
            'session_title' => $session_title,
            'session_datetime' => $session_datetime,
            'room' => $room,
            'type_override' => $type_override,
            'status' => $status,
        ], $data);

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $result = $wpdb->insert($table_name, $session_data);

        if ($result === false) {
            return new \WP_Error('database_error', __('Failed to create session.', 'scn-membership'));
        }

        $session_id = $wpdb->insert_id;

        do_action('scn/events/session_created', $session_id, $session_data);

        return [
            'id' => $session_id,
            'status' => $status,
            'message' => $status === 'pending' 
                ? __('Session submitted for approval.', 'scn-membership')
                : __('Session created successfully.', 'scn-membership')
        ];
    }

    public function updateSession($session_id, $data) {
        global $wpdb;

        $session_id = intval($session_id);
        $session = $this->getSession($session_id);

        if (!$session) {
            return new \WP_Error('session_not_found', __('Session not found.', 'scn-membership'));
        }

        if (!current_user_can('manage_scn_events') && get_current_user_id() !== $session->profile_id) {
            return new \WP_Error('insufficient_permissions', __('Insufficient permissions.', 'scn-membership'));
        }

        $update_data = [];
        $allowed_fields = ['session_title', 'session_datetime', 'room', 'type_override'];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = sanitize_text_field($data[$field]);
            }
        }

        if (empty($update_data)) {
            return new \WP_Error('no_changes', __('No changes to update.', 'scn-membership'));
        }

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $session_id],
            array_fill(0, count($update_data), '%s'),
            ['%d']
        );

        if ($result === false) {
            return new \WP_Error('database_error', __('Failed to update session.', 'scn-membership'));
        }

        do_action('scn/events/session_updated', $session_id, $update_data);

        return ['message' => __('Session updated successfully.', 'scn-membership')];
    }

    public function deleteSession($session_id) {
        global $wpdb;

        $session_id = intval($session_id);
        $session = $this->getSession($session_id);

        if (!$session) {
            return new \WP_Error('session_not_found', __('Session not found.', 'scn-membership'));
        }

        if (!current_user_can('manage_scn_events') && get_current_user_id() !== $session->profile_id) {
            return new \WP_Error('insufficient_permissions', __('Insufficient permissions.', 'scn-membership'));
        }

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $result = $wpdb->delete($table_name, ['id' => $session_id], ['%d']);

        if ($result === false) {
            return new \WP_Error('database_error', __('Failed to delete session.', 'scn-membership'));
        }

        do_action('scn/events/session_deleted', $session_id);

        return ['message' => __('Session deleted successfully.', 'scn-membership')];
    }

    public function getSession($session_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $session_id
        ));
    }

    public function getSessionsByEvent($event_id, $status = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $where_clause = "WHERE event_id = %d";
        $params = [$event_id];

        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }

        $sql = "SELECT s.*, 
                       p.post_title as profile_name,
                       c.post_title as course_title
                FROM $table_name s
                LEFT JOIN {$wpdb->posts} p ON s.profile_id = p.ID
                LEFT JOIN {$wpdb->posts} c ON s.course_id = c.ID
                $where_clause
                ORDER BY s.session_datetime ASC";

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    public function getSessionsByProfile($profile_id, $status = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $where_clause = "WHERE profile_id = %d";
        $params = [$profile_id];

        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }

        $sql = "SELECT s.*, 
                       e.post_title as event_name,
                       c.post_title as course_title
                FROM $table_name s
                LEFT JOIN {$wpdb->posts} e ON s.event_id = e.ID
                LEFT JOIN {$wpdb->posts} c ON s.course_id = c.ID
                $where_clause
                ORDER BY s.session_datetime ASC";

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    public function getUpcomingSessions($limit = 20) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $now = current_time('mysql');

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
                WHERE s.session_datetime > %s 
                AND s.status = 'approved'
                ORDER BY s.session_datetime ASC
                LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($sql, $now, $limit));
    }

    public function getMemberCourses($profile_id) {
        $courses = get_posts([
            'post_type' => 'scn_course',
            'meta_query' => [
                [
                    'key' => 'scn_course_profile_id',
                    'value' => $profile_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $formatted_courses = [];
        foreach ($courses as $course) {
            $formatted_courses[] = [
                'id' => $course->ID,
                'title' => $course->post_title,
                'format' => get_post_meta($course->ID, 'scn_course_format', true),
            ];
        }

        return $formatted_courses;
    }

    public function handleCreateSession() {
        check_ajax_referer('scn_events_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to create sessions.', 'scn-membership'));
        }

        $data = $_POST;
        $data['profile_id'] = get_current_user_id();

        $result = $this->createSession($data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    public function handleUpdateSession() {
        check_ajax_referer('scn_events_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to update sessions.', 'scn-membership'));
        }

        $session_id = intval($_POST['session_id'] ?? 0);
        $data = $_POST;

        $result = $this->updateSession($session_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    public function handleDeleteSession() {
        check_ajax_referer('scn_events_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to delete sessions.', 'scn-membership'));
        }

        $session_id = intval($_POST['session_id'] ?? 0);

        $result = $this->deleteSession($session_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    public function handleGetSessions() {
        check_ajax_referer('scn_events_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to view sessions.', 'scn-membership'));
        }

        $event_id = intval($_GET['event_id'] ?? 0);
        $profile_id = intval($_GET['profile_id'] ?? 0);
        $status = sanitize_text_field($_GET['status'] ?? '');

        if ($event_id) {
            $sessions = $this->getSessionsByEvent($event_id, $status);
        } elseif ($profile_id) {
            $sessions = $this->getSessionsByProfile($profile_id, $status);
        } else {
            $sessions = $this->getUpcomingSessions();
        }

        wp_send_json_success($sessions);
    }

    public function handleGetMemberCourses() {
        check_ajax_referer('scn_events_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to view courses.', 'scn-membership'));
        }

        $profile_id = get_current_user_id();
        $courses = $this->getMemberCourses($profile_id);

        wp_send_json_success($courses);
    }
}




