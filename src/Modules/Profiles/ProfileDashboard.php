<?php

namespace SCN\Membership\Modules\Profiles;

class ProfileDashboard {
    private $sessions_service;
    private $events_search_service;

    public function register() {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_scn_dashboard_stats', [$this, 'handleDashboardStats']);
        add_action('wp_ajax_scn_dashboard_recent_activity', [$this, 'handleRecentActivity']);
        add_action('wp_ajax_scn_dashboard_upcoming_sessions', [$this, 'handleUpcomingSessions']);
        add_action('wp_ajax_scn_dashboard_profile_stats', [$this, 'handleProfileStats']);
        // add_shortcode('scn_member_dashboard', [$this, 'renderDashboardShortcode']); // Removed - handled by AuthShortcodes
        add_action('init', [$this, 'addRewriteRules']);
        add_filter('query_vars', [$this, 'addQueryVars']);
        add_filter('template_include', [$this, 'templateInclude']);
    }

    public function enqueueScripts() {
        if ($this->isDashboardPage()) {
            wp_enqueue_script(
                'scn-dashboard',
                SCN_MEMBERSHIP_URL . 'assets/js/dashboard.js',
                ['jquery'],
                SCN_MEMBERSHIP_VERSION,
                true
            );

            wp_enqueue_style(
                'scn-dashboard',
                SCN_MEMBERSHIP_URL . 'assets/css/dashboard.css',
                [],
                SCN_MEMBERSHIP_VERSION
            );

            wp_localize_script('scn-dashboard', 'scnDashboard', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('scn_dashboard_nonce'),
                'strings' => [
                    'loading' => __('Loading...', 'scn-membership'),
                    'error' => __('Error loading data', 'scn-membership'),
                    'noUpcomingSessions' => __('No upcoming sessions', 'scn-membership'),
                    'noRecentActivity' => __('No recent activity', 'scn-membership'),
                ],
            ]);
        }
    }

    public function addRewriteRules() {
        add_rewrite_rule(
            '^member-dashboard/?$',
            'index.php?scn_dashboard=1',
            'top'
        );
    }

    public function addQueryVars($vars) {
        $vars[] = 'scn_dashboard';
        return $vars;
    }

    public function templateInclude($template) {
        if (get_query_var('scn_dashboard')) {
            return SCN_MEMBERSHIP_PATH . 'templates/profiles/dashboard.php';
        }
        return $template;
    }

    private function isDashboardPage() {
        return get_query_var('scn_dashboard') || 
               (isset($_GET['scn_dashboard']) && $_GET['scn_dashboard'] == '1') ||
               is_page('member-dashboard');
    }

    public function renderDashboardShortcode($atts) {
        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'show_welcome' => 'true',
            'show_stats' => 'true',
            'show_recent_activity' => 'true',
            'show_upcoming_sessions' => 'true',
            'show_profile_actions' => 'true',
        ], $atts);

        if (!is_user_logged_in() && $atts['user_id'] == get_current_user_id()) {
            return '<p>' . __('Please log in to view your dashboard.', 'scn-membership') . '</p>';
        }

        $user_id = intval($atts['user_id']);
        $profile = $this->getUserProfile($user_id);

        if (!$profile) {
            return '<p>' . __('Profile not found.', 'scn-membership') . '</p>';
        }

        ob_start();
        $this->renderDashboardContent($profile, $atts);
        return ob_get_clean();
    }

    private function getUserProfile($user_id) {
        $profile_posts = get_posts([
            'post_type' => 'scn_profile',
            'meta_query' => [
                [
                    'key' => 'scn_user_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);

        return !empty($profile_posts) ? $profile_posts[0] : null;
    }

    private function renderDashboardContent($profile, $atts) {
        $user_id = get_post_meta($profile->ID, 'scn_user_id', true);
        $first_name = get_post_meta($profile->ID, 'scn_first_name', true);
        $last_name = get_post_meta($profile->ID, 'scn_last_name', true);
        $member_since = get_post_meta($profile->ID, 'scn_member_since', true);

        ?>
        <div class="scn-dashboard-container" data-user-id="<?php echo esc_attr($user_id); ?>">
            <?php if ($atts['show_welcome'] === 'true'): ?>
                <div class="scn-dashboard-header">
                    <div class="scn-dashboard-welcome">
                        <h1><?php printf(__('Welcome back, %s!', 'scn-membership'), esc_html($first_name . ' ' . $last_name)); ?></h1>
                        <?php if ($member_since): ?>
                            <p class="scn-member-since">
                                <?php printf(__('Member since %s', 'scn-membership'), date('F Y', strtotime($member_since))); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="scn-dashboard-actions">
                        <a href="<?php echo esc_url(get_edit_post_link($profile->ID)); ?>" class="scn-btn scn-btn-primary">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Edit Profile', 'scn-membership'); ?>
                        </a>
                        <a href="<?php echo esc_url(get_permalink($profile->ID)); ?>" class="scn-btn scn-btn-secondary">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('View Profile', 'scn-membership'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="scn-dashboard-grid">
                <?php if ($atts['show_stats'] === 'true'): ?>
                    <div class="scn-dashboard-stats">
                        <h2><?php _e('Your Statistics', 'scn-membership'); ?></h2>
                        <div class="scn-stats-grid" id="scn-stats-container">
                            <div class="scn-loading"><?php _e('Loading...', 'scn-membership'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_upcoming_sessions'] === 'true'): ?>
                    <div class="scn-dashboard-upcoming">
                        <h2><?php _e('Upcoming Sessions', 'scn-membership'); ?></h2>
                        <div id="scn-upcoming-sessions">
                            <div class="scn-loading"><?php _e('Loading...', 'scn-membership'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_recent_activity'] === 'true'): ?>
                    <div class="scn-dashboard-activity">
                        <h2><?php _e('Recent Activity', 'scn-membership'); ?></h2>
                        <div id="scn-recent-activity">
                            <div class="scn-loading"><?php _e('Loading...', 'scn-membership'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_profile_actions'] === 'true'): ?>
                    <div class="scn-dashboard-quick-actions">
                        <h2><?php _e('Quick Actions', 'scn-membership'); ?></h2>
                        <div class="scn-actions-grid">
                            <a href="<?php echo esc_url(get_edit_post_link($profile->ID)); ?>" class="scn-action-card">
                                <span class="dashicons dashicons-admin-users"></span>
                                <h3><?php _e('Edit Profile', 'scn-membership'); ?></h3>
                                <p><?php _e('Update your profile information, bio, and media', 'scn-membership'); ?></p>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/events/')); ?>" class="scn-action-card">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <h3><?php _e('Browse Events', 'scn-membership'); ?></h3>
                                <p><?php _e('View upcoming events and sessions', 'scn-membership'); ?></p>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/courses/')); ?>" class="scn-action-card">
                                <span class="dashicons dashicons-welcome-learn-more"></span>
                                <h3><?php _e('Browse Courses', 'scn-membership'); ?></h3>
                                <p><?php _e('Explore available courses and learning materials', 'scn-membership'); ?></p>
                            </a>
                            
                            <a href="<?php echo esc_url(home_url('/profiles/')); ?>" class="scn-action-card">
                                <span class="dashicons dashicons-groups"></span>
                                <h3><?php _e('Member Directory', 'scn-membership'); ?></h3>
                                <p><?php _e('Connect with other SCN members', 'scn-membership'); ?></p>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function handleDashboardStats() {
        check_ajax_referer('scn_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in.', 'scn-membership'));
        }

        $user_id = get_current_user_id();
        $profile = $this->getUserProfile($user_id);

        if (!$profile) {
            wp_send_json_error(__('Profile not found.', 'scn-membership'));
        }

        $stats = $this->getMemberStats($profile->ID, $user_id);
        wp_send_json_success($stats);
    }

    public function handleProfileStats() {
        check_ajax_referer('scn_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in.', 'scn-membership'));
        }

        $user_id = get_current_user_id();
        $profile = $this->getUserProfile($user_id);

        if (!$profile) {
            wp_send_json_error(__('Profile not found.', 'scn-membership'));
        }

        $profile_stats = $this->getProfileStats($profile->ID);
        wp_send_json_success($profile_stats);
    }

    public function handleUpcomingSessions() {
        check_ajax_referer('scn_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in.', 'scn-membership'));
        }

        $user_id = get_current_user_id();
        $profile = $this->getUserProfile($user_id);

        if (!$profile) {
            wp_send_json_error(__('Profile not found.', 'scn-membership'));
        }

        $sessions = $this->getUpcomingSessions($profile->ID);
        wp_send_json_success($sessions);
    }

    public function handleRecentActivity() {
        check_ajax_referer('scn_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in.', 'scn-membership'));
        }

        $user_id = get_current_user_id();
        $profile = $this->getUserProfile($user_id);

        if (!$profile) {
            wp_send_json_error(__('Profile not found.', 'scn-membership'));
        }

        $activity = $this->getRecentActivity($profile->ID, $user_id);
        wp_send_json_success($activity);
    }

    private function getMemberStats($profile_id, $user_id) {
        global $wpdb;

        // Get sessions count
        $sessions_table = $wpdb->prefix . 'scn_sessions';
        $sessions_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE profile_id = %d",
            $profile_id
        ));

        // Get approved sessions count
        $approved_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE profile_id = %d AND status = 'approved'",
            $profile_id
        ));

        // Get upcoming sessions count
        $upcoming_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE profile_id = %d AND status = 'approved' AND session_datetime > NOW()",
            $profile_id
        ));

        // Get courses count
        $courses_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'scn_course' AND post_author = %d AND post_status = 'publish'",
            $user_id
        ));

        // Get profile views (if tracking is implemented)
        $profile_views = get_post_meta($profile_id, 'scn_profile_views', true) ?: 0;

        return [
            'total_sessions' => intval($sessions_count),
            'approved_sessions' => intval($approved_sessions),
            'upcoming_sessions' => intval($upcoming_sessions),
            'courses_created' => intval($courses_count),
            'profile_views' => intval($profile_views),
        ];
    }

    private function getProfileStats($profile_id) {
        // Get profile completion percentage
        $required_fields = ['scn_first_name', 'scn_last_name', 'scn_bio', 'scn_location'];
        $completed_fields = 0;

        foreach ($required_fields as $field) {
            $value = get_post_meta($profile_id, $field, true);
            if (!empty($value)) {
                $completed_fields++;
            }
        }

        $completion_percentage = ($completed_fields / count($required_fields)) * 100;

        // Check if profile has photo
        $has_photo = has_post_thumbnail($profile_id);

        // Check if profile has gallery
        $gallery_images = get_post_meta($profile_id, 'scn_gallery_images', true) ?: [];
        $has_gallery = !empty($gallery_images);

        // Check if profile has press kit
        $press_kit_files = get_post_meta($profile_id, 'scn_press_kit_files', true) ?: [];
        $has_press_kit = !empty($press_kit_files);

        return [
            'completion_percentage' => round($completion_percentage),
            'has_photo' => $has_photo,
            'has_gallery' => $has_gallery,
            'has_press_kit' => $has_press_kit,
            'completed_fields' => $completed_fields,
            'total_fields' => count($required_fields),
        ];
    }

    private function getUpcomingSessions($profile_id, $limit = 5) {
        global $wpdb;

        $sessions_table = $wpdb->prefix . 'scn_sessions';
        $now = current_time('mysql');

        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, 
                    e.post_title as event_name,
                    e.post_name as event_slug,
                    c.post_title as course_title
             FROM $sessions_table s
             LEFT JOIN {$wpdb->posts} e ON s.event_id = e.ID
             LEFT JOIN {$wpdb->posts} c ON s.course_id = c.ID
             WHERE s.profile_id = %d 
             AND s.session_datetime > %s 
             AND s.status = 'approved'
             ORDER BY s.session_datetime ASC
             LIMIT %d",
            $profile_id, $now, $limit
        ));

        $formatted_sessions = [];
        foreach ($sessions as $session) {
            $formatted_sessions[] = [
                'id' => $session->id,
                'title' => $session->session_title,
                'event_name' => $session->event_name,
                'event_slug' => $session->event_slug,
                'course_title' => $session->course_title,
                'datetime' => $session->session_datetime,
                'room' => $session->room,
                'formatted_date' => date_i18n('F j, Y', strtotime($session->session_datetime)),
                'formatted_time' => date_i18n('g:i A', strtotime($session->session_datetime)),
            ];
        }

        return $formatted_sessions;
    }

    private function getRecentActivity($profile_id, $user_id, $limit = 10) {
        $activity = [];

        // Get recent profile updates
        $profile_updates = get_posts([
            'post_type' => 'scn_profile',
            'p' => $profile_id,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);

        if (!empty($profile_updates)) {
            $profile = $profile_updates[0];
            $activity[] = [
                'type' => 'profile_update',
                'title' => __('Profile updated', 'scn-membership'),
                'description' => __('Your profile information was updated', 'scn-membership'),
                'date' => $profile->post_modified,
                'formatted_date' => human_time_diff(strtotime($profile->post_modified), current_time('timestamp')) . ' ' . __('ago', 'scn-membership'),
                'icon' => 'admin-users',
            ];
        }

        // Get recent sessions created
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'scn_sessions';
        $recent_sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $sessions_table WHERE profile_id = %d ORDER BY created_at DESC LIMIT %d",
            $profile_id, 5
        ));

        foreach ($recent_sessions as $session) {
            $activity[] = [
                'type' => 'session_created',
                'title' => sprintf(__('Session created: %s', 'scn-membership'), $session->session_title),
                'description' => sprintf(__('Session scheduled for %s', 'scn-membership'), date_i18n('F j, Y g:i A', strtotime($session->session_datetime))),
                'date' => $session->created_at,
                'formatted_date' => human_time_diff(strtotime($session->created_at), current_time('timestamp')) . ' ' . __('ago', 'scn-membership'),
                'icon' => 'calendar-alt',
                'status' => $session->status,
            ];
        }

        // Get recent courses created
        $recent_courses = get_posts([
            'post_type' => 'scn_course',
            'author' => $user_id,
            'posts_per_page' => 3,
            'post_status' => 'publish'
        ]);

        foreach ($recent_courses as $course) {
            $activity[] = [
                'type' => 'course_created',
                'title' => sprintf(__('Course created: %s', 'scn-membership'), $course->post_title),
                'description' => __('A new course was added to your profile', 'scn-membership'),
                'date' => $course->post_date,
                'formatted_date' => human_time_diff(strtotime($course->post_date), current_time('timestamp')) . ' ' . __('ago', 'scn-membership'),
                'icon' => 'welcome-learn-more',
            ];
        }

        // Sort by date and limit
        usort($activity, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activity, 0, $limit);
    }
}
