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
        add_action('wp_ajax_scn_get_profile_completion_details', [$this, 'handleProfileCompletionDetailsAjax']);
        add_action('wp_ajax_scn_get_profile_form', [$this, 'handleGetProfileFormAjax']);
        add_action('wp_ajax_scn_save_profile_completion', [$this, 'handleSaveProfileCompletionAjax']);
        add_action('wp_ajax_scn_upload_file', [$this, 'handleFileUploadAjax']);
        add_action('wp_ajax_scn_get_available_courses', [$this, 'handleGetAvailableCoursesAjax']);
        add_action('wp_ajax_scn_get_enrolled_courses', [$this, 'handleGetEnrolledCoursesAjax']);
        add_action('wp_ajax_scn_get_created_courses', [$this, 'handleGetCreatedCoursesAjax']);
        add_action('wp_ajax_scn_create_course', [$this, 'handleCreateCourseAjax']);
        add_action('wp_ajax_scn_enroll_course', [$this, 'handleEnrollCourseAjax']);
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
            
            // Enqueue full-width override CSS
            wp_enqueue_style(
                'scn-full-width-override',
                SCN_MEMBERSHIP_URL . 'assets/css/full-width-override.css',
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

    public function handleProfileCompletionDetailsAjax() {
        check_ajax_referer('scn_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in.', 'scn-membership'));
        }

        $user_id = get_current_user_id();
        $profile_id = intval($_POST['profile_id']);
        
        if (!$profile_id) {
            wp_send_json_error('Profile ID required');
        }

        // Verify the profile belongs to the current user
        $profile = get_post($profile_id);
        if (!$profile || $profile->post_type !== 'scn_profile') {
            wp_send_json_error('Invalid profile');
        }

        $profile_user_id = get_post_meta($profile_id, 'scn_user_id', true);
        if ($profile_user_id != $user_id) {
            wp_send_json_error('Unauthorized access');
        }

        $completion_details = $this->getProfileStats($profile_id);
        wp_send_json_success($completion_details);
    }

    public function handleGetProfileFormAjax() {
        check_ajax_referer('scn_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in.', 'scn-membership'));
        }

        $user_id = get_current_user_id();
        $profile_id = intval($_POST['profile_id']);
        
        if (!$profile_id) {
            wp_send_json_error('Profile ID required');
        }

        // Verify the profile belongs to the current user
        $profile = get_post($profile_id);
        if (!$profile || $profile->post_type !== 'scn_profile') {
            wp_send_json_error('Invalid profile');
        }

        $profile_user_id = get_post_meta($profile_id, 'scn_user_id', true);
        if ($profile_user_id != $user_id) {
            wp_send_json_error('Unauthorized access');
        }

        // Get current profile data
        $profile_data = $this->getProfileFormData($profile_id);
        $form_html = $this->renderProfileForm($profile_data);
        
        wp_send_json_success($form_html);
    }

    public function handleSaveProfileCompletionAjax() {
        check_ajax_referer('scn_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in.', 'scn-membership'));
        }

        $user_id = get_current_user_id();
        $profile_id = intval($_POST['profile_id']);
        
        if (!$profile_id) {
            wp_send_json_error('Profile ID required');
        }

        // Verify the profile belongs to the current user
        $profile = get_post($profile_id);
        if (!$profile || $profile->post_type !== 'scn_profile') {
            wp_send_json_error('Invalid profile');
        }

        $profile_user_id = get_post_meta($profile_id, 'scn_user_id', true);
        if ($profile_user_id != $user_id) {
            wp_send_json_error('Unauthorized access');
        }

        // Update profile fields
        $updated_fields = $this->updateProfileFields($profile_id, $_POST);
        
        if ($updated_fields) {
            wp_send_json_success('Profile updated successfully');
        } else {
            wp_send_json_error('Failed to update profile');
        }
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
        // Define all profile fields for completion calculation (excluding topics and badges)
        $profile_fields = [
            'scn_first_name' => [
                'label' => __('First Name', 'scn-membership'),
                'required' => true,
                'weight' => 2
            ],
            'scn_last_name' => [
                'label' => __('Last Name', 'scn-membership'),
                'required' => true,
                'weight' => 2
            ],
            'scn_credentials' => [
                'label' => __('Credentials', 'scn-membership'),
                'required' => false,
                'weight' => 1
            ],
            'scn_location' => [
                'label' => __('Location', 'scn-membership'),
                'required' => true,
                'weight' => 2
            ],
            'scn_main_url' => [
                'label' => __('Main Website URL', 'scn-membership'),
                'required' => false,
                'weight' => 1
            ],
            'scn_social_links' => [
                'label' => __('Social Media Links', 'scn-membership'),
                'required' => false,
                'weight' => 1
            ],
            'scn_bio' => [
                'label' => __('Biography', 'scn-membership'),
                'required' => true,
                'weight' => 3
            ],
            'scn_member_since' => [
                'label' => __('Member Since', 'scn-membership'),
                'required' => false,
                'weight' => 1
            ],
            'scn_gallery_images' => [
                'label' => __('Photo Gallery', 'scn-membership'),
                'required' => false,
                'weight' => 2
            ],
            'scn_featured_video_url' => [
                'label' => __('Featured Video', 'scn-membership'),
                'required' => false,
                'weight' => 1
            ],
            'scn_press_kit_files' => [
                'label' => __('Press Kit Files', 'scn-membership'),
                'required' => false,
                'weight' => 1
            ],
            'scn_services' => [
                'label' => __('Services Offered', 'scn-membership'),
                'required' => false,
                'weight' => 1
            ]
        ];

        // Add profile photo as a field
        $profile_fields['profile_photo'] = [
            'label' => __('Profile Photo', 'scn-membership'),
            'required' => false,
            'weight' => 2
        ];

        $total_weight = 0;
        $completed_weight = 0;
        $missing_fields = [];
        $field_status = [];

        foreach ($profile_fields as $field_key => $field_config) {
            $total_weight += $field_config['weight'];
            
            $is_completed = false;
            $value = '';

            if ($field_key === 'profile_photo') {
                $is_completed = has_post_thumbnail($profile_id);
            } elseif ($field_key === 'scn_social_links') {
                $social_links = get_post_meta($profile_id, $field_key, true) ?: [];
                $is_completed = !empty($social_links);
                $value = $social_links;
            } elseif (in_array($field_key, ['scn_gallery_images', 'scn_press_kit_files', 'scn_services'])) {
                $field_value = get_post_meta($profile_id, $field_key, true) ?: [];
                $is_completed = !empty($field_value);
                $value = $field_value;
            } else {
                $value = get_post_meta($profile_id, $field_key, true);
                $is_completed = !empty($value);
            }

            if ($is_completed) {
                $completed_weight += $field_config['weight'];
            } else {
                $missing_fields[] = [
                    'field' => $field_key,
                    'label' => $field_config['label'],
                    'required' => $field_config['required'],
                    'weight' => $field_config['weight']
                ];
            }

            $field_status[$field_key] = [
                'completed' => $is_completed,
                'label' => $field_config['label'],
                'required' => $field_config['required'],
                'weight' => $field_config['weight'],
                'value' => $value
            ];
        }

        $completion_percentage = $total_weight > 0 ? round(($completed_weight / $total_weight) * 100) : 0;

        return [
            'completion_percentage' => $completion_percentage,
            'completed_weight' => $completed_weight,
            'total_weight' => $total_weight,
            'missing_fields' => $missing_fields,
            'field_status' => $field_status,
            'is_complete' => $completion_percentage >= 100,
            'required_fields_missing' => array_filter($missing_fields, function($field) {
                return $field['required'];
            })
        ];
    }

    private function getProfileFormData($profile_id) {
        $social_links = get_post_meta($profile_id, 'scn_social_links', true) ?: [];
        $gallery_images = get_post_meta($profile_id, 'scn_gallery_images', true) ?: [];
        $press_kit_files = get_post_meta($profile_id, 'scn_press_kit_files', true) ?: [];
        $services = get_post_meta($profile_id, 'scn_services', true) ?: [];

        return [
            'scn_first_name' => get_post_meta($profile_id, 'scn_first_name', true),
            'scn_last_name' => get_post_meta($profile_id, 'scn_last_name', true),
            'scn_credentials' => get_post_meta($profile_id, 'scn_credentials', true),
            'scn_location' => get_post_meta($profile_id, 'scn_location', true),
            'scn_main_url' => get_post_meta($profile_id, 'scn_main_url', true),
            'scn_social_links' => $social_links,
            'scn_bio' => get_post_meta($profile_id, 'scn_bio', true),
            'scn_member_since' => get_post_meta($profile_id, 'scn_member_since', true),
            'scn_featured_video_url' => get_post_meta($profile_id, 'scn_featured_video_url', true),
            'scn_gallery_images' => $gallery_images,
            'scn_press_kit_files' => $press_kit_files,
            'scn_services' => $services,
            'profile_image_id' => get_post_thumbnail_id($profile_id),
        ];
    }

    private function renderProfileForm($profile_data) {
        ob_start();
        ?>
        <div class="scn-form-section">
            <h3><span class="dashicons dashicons-admin-users"></span>Basic Information</h3>
            <div class="scn-form-grid">
                <div class="scn-form-group">
                    <label for="scn_first_name">First Name <span class="required">*</span></label>
                    <input type="text" id="scn_first_name" name="scn_first_name" value="<?php echo esc_attr($profile_data['scn_first_name']); ?>" required>
                </div>
                <div class="scn-form-group">
                    <label for="scn_last_name">Last Name <span class="required">*</span></label>
                    <input type="text" id="scn_last_name" name="scn_last_name" value="<?php echo esc_attr($profile_data['scn_last_name']); ?>" required>
                </div>
                <div class="scn-form-group">
                    <label for="scn_credentials">Credentials</label>
                    <input type="text" id="scn_credentials" name="scn_credentials" value="<?php echo esc_attr($profile_data['scn_credentials']); ?>" placeholder="e.g., PhD, MBA, CSP">
                    <div class="scn-form-help">Professional credentials or degrees</div>
                </div>
                <div class="scn-form-group">
                    <label for="scn_location">Location <span class="required">*</span></label>
                    <input type="text" id="scn_location" name="scn_location" value="<?php echo esc_attr($profile_data['scn_location']); ?>" required placeholder="City, State/Country">
                </div>
                <div class="scn-form-group">
                    <label for="scn_member_since">Member Since</label>
                    <input type="date" id="scn_member_since" name="scn_member_since" value="<?php echo esc_attr($profile_data['scn_member_since']); ?>">
                    <div class="scn-form-help">When did you become a member?</div>
                </div>
                <div class="scn-form-group">
                    <label for="scn_main_url">Main Website URL</label>
                    <input type="url" id="scn_main_url" name="scn_main_url" value="<?php echo esc_attr($profile_data['scn_main_url']); ?>" placeholder="https://yourwebsite.com">
                    <div class="scn-form-help">Your personal or professional website</div>
                </div>
                <div class="scn-form-group full-width">
                    <label for="scn_bio">Biography <span class="required">*</span></label>
                    <textarea id="scn_bio" name="scn_bio" required placeholder="Tell us about yourself, your expertise, and what makes you unique..." rows="6"><?php echo esc_textarea($profile_data['scn_bio']); ?></textarea>
                    <div class="scn-form-help">A compelling bio helps others understand your expertise and background</div>
                </div>
            </div>
        </div>

        <div class="scn-form-section">
            <h3><span class="dashicons dashicons-share"></span>Social Media Links</h3>
            <div class="scn-form-grid">
                <?php
                $social_platforms = ['linkedin', 'twitter', 'facebook', 'instagram', 'youtube', 'website'];
                foreach ($social_platforms as $platform) {
                    $value = isset($profile_data['scn_social_links'][$platform]) ? $profile_data['scn_social_links'][$platform] : '';
                    ?>
                    <div class="scn-form-group">
                        <label for="scn_social_<?php echo esc_attr($platform); ?>"><?php echo esc_html(ucfirst($platform)); ?></label>
                        <input type="url" id="scn_social_<?php echo esc_attr($platform); ?>" name="scn_social_links[<?php echo esc_attr($platform); ?>]" value="<?php echo esc_attr($value); ?>" placeholder="https://<?php echo esc_attr($platform); ?>.com/yourprofile">
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>

        <div class="scn-form-section">
            <h3><span class="dashicons dashicons-camera"></span>Profile Image</h3>
            <div class="scn-form-grid">
                <div class="scn-form-group full-width">
                    <label for="scn_profile_image">Profile Image</label>
                    <div class="scn-image-upload-container">
                        <div class="scn-image-preview">
                            <?php if ($profile_data['profile_image_id']): ?>
                                <?php 
                                $image_url = wp_get_attachment_image_url($profile_data['profile_image_id'], 'medium');
                                if ($image_url): ?>
                                    <img src="<?php echo esc_url($image_url); ?>" class="scn-uploaded-image" alt="Profile Image" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="scn-no-image">Image not found</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="scn-no-image">No image selected</div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="scn-btn scn-btn-secondary scn-upload-image-btn">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Upload Image', 'scn-membership'); ?>
                        </button>
                        <input type="hidden" id="scn_profile_image" name="scn_profile_image" value="<?php echo esc_attr($profile_data['profile_image_id']); ?>">
                    </div>
                    <div class="scn-form-help">Upload a professional headshot for your profile</div>
                </div>
            </div>
        </div>

        <div class="scn-form-section">
            <h3><span class="dashicons dashicons-images-alt2"></span>Photo Gallery</h3>
            <div class="scn-form-grid">
                <div class="scn-form-group full-width">
                    <label>Gallery Images</label>
                    <div class="scn-gallery-upload-container">
                        <div class="scn-gallery-preview">
                            <?php if (!empty($profile_data['scn_gallery_images'])): ?>
                                <?php foreach ($profile_data['scn_gallery_images'] as $image_id): ?>
                                    <?php 
                                    $thumbnail_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                                    if ($thumbnail_url): ?>
                                        <div class="scn-gallery-item" data-image-id="<?php echo esc_attr($image_id); ?>">
                                            <img src="<?php echo esc_url($thumbnail_url); ?>" alt="Gallery Image" style="width: 80px; height: 80px; object-fit: cover;">
                                            <button type="button" class="scn-remove-gallery-image">×</button>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="scn-btn scn-btn-secondary scn-add-gallery-images">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Add Gallery Images', 'scn-membership'); ?>
                        </button>
                        <input type="hidden" id="scn_gallery_images" name="scn_gallery_images" value="<?php echo esc_attr(implode(',', $profile_data['scn_gallery_images'])); ?>">
                    </div>
                    <div class="scn-form-help">Upload multiple images to showcase your work and expertise</div>
                </div>
            </div>
        </div>

        <div class="scn-form-section">
            <h3><span class="dashicons dashicons-video-alt3"></span>Featured Content</h3>
            <div class="scn-form-grid">
                <div class="scn-form-group full-width">
                    <label for="scn_featured_video_url">Featured Video URL</label>
                    <input type="url" id="scn_featured_video_url" name="scn_featured_video_url" value="<?php echo esc_attr($profile_data['scn_featured_video_url']); ?>" placeholder="https://youtube.com/watch?v=...">
                    <div class="scn-form-help">YouTube, Vimeo, or other video platform link</div>
                </div>
            </div>
        </div>

        <div class="scn-form-section">
            <h3><span class="dashicons dashicons-portfolio"></span>Press Kit / Speaker Packet</h3>
            <div class="scn-form-grid">
                <div class="scn-form-group full-width">
                    <label>Press Kit Files</label>
                    <div class="scn-press-kit-upload-container">
                        <div class="scn-press-kit-preview">
                            <?php if (!empty($profile_data['scn_press_kit_files'])): ?>
                                <?php foreach ($profile_data['scn_press_kit_files'] as $file_id): ?>
                                    <div class="scn-press-kit-item" data-file-id="<?php echo esc_attr($file_id); ?>">
                                        <span class="dashicons dashicons-media-document"></span>
                                        <span><?php echo get_the_title($file_id); ?></span>
                                        <button type="button" class="scn-remove-press-kit-file">×</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="scn-btn scn-btn-secondary scn-add-press-kit-files">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Add Press Kit Files', 'scn-membership'); ?>
                        </button>
                        <input type="hidden" id="scn_press_kit_files" name="scn_press_kit_files" value="<?php echo esc_attr(implode(',', $profile_data['scn_press_kit_files'])); ?>">
                    </div>
                    <div class="scn-form-help">Upload speaker bios, headshots, and other promotional materials</div>
                </div>
            </div>
        </div>

        <div class="scn-form-section">
            <h3><span class="dashicons dashicons-businessman"></span>Services Offered</h3>
            <div class="scn-form-grid">
                <div class="scn-form-group full-width">
                    <label>Services</label>
                    <div class="scn-services-container">
                        <div class="scn-services-list">
                            <?php if (!empty($profile_data['scn_services'])): ?>
                                <?php foreach ($profile_data['scn_services'] as $index => $service): ?>
                                    <div class="scn-service-item">
                                        <input type="text" name="scn_services[<?php echo $index; ?>][title]" value="<?php echo esc_attr($service['title'] ?? ''); ?>" placeholder="Service Title">
                                        <textarea name="scn_services[<?php echo $index; ?>][description]" placeholder="Service Description"><?php echo esc_textarea($service['description'] ?? ''); ?></textarea>
                                        <button type="button" class="scn-remove-service">×</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="scn-btn scn-btn-secondary scn-add-service">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Add Service', 'scn-membership'); ?>
                        </button>
                    </div>
                    <div class="scn-form-help">List the services you offer to other members</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function updateProfileFields($profile_id, $form_data) {
        $updated_fields = [];
        
        $fields_to_update = [
            'scn_first_name',
            'scn_last_name', 
            'scn_credentials',
            'scn_location',
            'scn_main_url',
            'scn_bio',
            'scn_member_since',
            'scn_featured_video_url'
        ];

        foreach ($fields_to_update as $field) {
            if (isset($form_data[$field])) {
                $value = sanitize_text_field($form_data[$field]);
                if (update_post_meta($profile_id, $field, $value)) {
                    $updated_fields[] = $field;
                }
            }
        }

        // Handle social links array
        if (isset($form_data['scn_social_links']) && is_array($form_data['scn_social_links'])) {
            $social_links = [];
            foreach ($form_data['scn_social_links'] as $platform => $url) {
                if (!empty($url)) {
                    $social_links[sanitize_key($platform)] = esc_url_raw($url);
                }
            }
            if (update_post_meta($profile_id, 'scn_social_links', $social_links)) {
                $updated_fields[] = 'scn_social_links';
            }
        }

        // Handle profile image
        if (isset($form_data['scn_profile_image']) && !empty($form_data['scn_profile_image'])) {
            if (set_post_thumbnail($profile_id, intval($form_data['scn_profile_image']))) {
                $updated_fields[] = 'scn_profile_image';
            }
        }

        // Handle gallery images
        if (isset($form_data['scn_gallery_images'])) {
            $gallery_images = array_filter(explode(',', sanitize_text_field($form_data['scn_gallery_images'])));
            $gallery_images = array_map('intval', $gallery_images);
            if (update_post_meta($profile_id, 'scn_gallery_images', $gallery_images)) {
                $updated_fields[] = 'scn_gallery_images';
            }
        }

        // Handle press kit files
        if (isset($form_data['scn_press_kit_files'])) {
            $press_kit_files = array_filter(explode(',', sanitize_text_field($form_data['scn_press_kit_files'])));
            $press_kit_files = array_map('intval', $press_kit_files);
            if (update_post_meta($profile_id, 'scn_press_kit_files', $press_kit_files)) {
                $updated_fields[] = 'scn_press_kit_files';
            }
        }

        // Handle services
        if (isset($form_data['scn_services']) && is_array($form_data['scn_services'])) {
            $services = [];
            foreach ($form_data['scn_services'] as $service) {
                if (!empty($service['title'])) {
                    $services[] = [
                        'title' => sanitize_text_field($service['title']),
                        'description' => sanitize_textarea_field($service['description'] ?? '')
                    ];
                }
            }
            if (update_post_meta($profile_id, 'scn_services', $services)) {
                $updated_fields[] = 'scn_services';
            }
        }

        return $updated_fields;
    }

    public function handleFileUploadAjax() {
        check_ajax_referer('scn_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in.', 'scn-membership'));
        }

        $user_id = get_current_user_id();
        $profile_id = intval($_POST['profile_id']);
        $upload_type = sanitize_text_field($_POST['upload_type']);
        
        if (!$profile_id) {
            wp_send_json_error('Profile ID required');
        }

        // Verify the profile belongs to the current user
        $profile = get_post($profile_id);
        if (!$profile || $profile->post_type !== 'scn_profile') {
            wp_send_json_error('Invalid profile');
        }

        $profile_user_id = get_post_meta($profile_id, 'scn_user_id', true);
        if ($profile_user_id != $user_id) {
            wp_send_json_error('Unauthorized access');
        }

        // Handle file upload
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['file'];
        $upload_overrides = ['test_form' => false];
        
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            // Create attachment
            $attachment = [
                'post_mime_type' => $movefile['type'],
                'post_title' => sanitize_file_name($uploadedfile['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            ];
            
            $attachment_id = wp_insert_attachment($attachment, $movefile['file'], $profile_id);
            
            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
                
                // Handle different upload types
                if ($upload_type === 'profile_image') {
                    set_post_thumbnail($profile_id, $attachment_id);
                }
                
                $response_data = [
                    'id' => $attachment_id,
                    'url' => $movefile['url'],
                    'title' => get_the_title($attachment_id)
                ];
                
                // Add thumbnail URL for images
                if (strpos($movefile['type'], 'image/') === 0) {
                    $response_data['thumbnail_url'] = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                }
                
                wp_send_json_success($response_data);
            } else {
                wp_send_json_error('Failed to create attachment');
            }
        } else {
            wp_send_json_error($movefile['error']);
        }
    }

    public function handleGetAvailableCoursesAjax() {
        try {
            check_ajax_referer('scn_dashboard_nonce', 'nonce');
            
            if (!is_user_logged_in()) {
                wp_die(__('You must be logged in.', 'scn-membership'));
            }

            // Get available courses (published courses)
        // Get user's enrolled courses to exclude them from available courses
        $user_id = get_current_user_id();
        $enrolled_courses = get_user_meta($user_id, 'scn_enrolled_courses', true);
        $exclude_ids = is_array($enrolled_courses) ? $enrolled_courses : [];
        
        $courses = get_posts([
            'post_type' => 'scn_course',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'post__not_in' => $exclude_ids
        ]);

        $html = '';
        if (!empty($courses)) {
            foreach ($courses as $course) {
                $subtitle = get_post_meta($course->ID, 'scn_course_subtitle', true);
                // Force ensure taxonomy is registered
                if (!taxonomy_exists('scn_topic')) {
                    register_taxonomy('scn_topic', 'scn_course', [
                        'labels' => [
                            'name' => __('Topics', 'scn-membership'),
                            'singular_name' => __('Topic', 'scn-membership'),
                        ],
                        'hierarchical' => false,
                        'public' => true,
                        'show_in_rest' => true,
                        'show_admin_column' => true,
                    ]);
                }
                
                $topics = [];
                if (taxonomy_exists('scn_topic')) {
                    // Try multiple methods to get topics
                    $topics = wp_get_post_terms($course->ID, 'scn_topic', ['fields' => 'names']);
                    if (is_wp_error($topics)) {
                        $topics = [];
                    }
                    
                    // If no topics found, try getting all topics for this course
                    if (empty($topics)) {
                        $all_topics = wp_get_post_terms($course->ID, 'scn_topic');
                        if (!is_wp_error($all_topics) && !empty($all_topics)) {
                            $topics = wp_list_pluck($all_topics, 'name');
                        }
                    }
                } else {
                    // Debug: Log taxonomy not exists
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SCN Course Topics Debug - Taxonomy scn_topic does not exist');
                    }
                }
                
                // Ensure topics is an array
                if (!is_array($topics)) {
                    $topics = [];
                }
                
                // Debug: Log topics for debugging
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('SCN Course Topics Debug - Course ID: ' . $course->ID . ', Topics: ' . print_r($topics, true));
                }
                $author = get_userdata($course->post_author);
                if (!$author) {
                    $author = (object) ['display_name' => 'Unknown Author'];
                }
                $featured_image = get_the_post_thumbnail_url($course->ID, 'thumbnail');
                
                $html .= '<tr>';
                
                // Course Image
                $html .= '<td class="scn-course-image">';
                if ($featured_image) {
                    $html .= '<img src="' . esc_url($featured_image) . '" alt="' . esc_attr($course->post_title) . '">';
                } else {
                    $html .= '<div class="scn-no-image"><span class="dashicons dashicons-format-video"></span></div>';
                }
                $html .= '</td>';
                
                // Course Title & Subtitle
                $html .= '<td class="scn-course-title">';
                $html .= '<span class="scn-course-name">' . esc_html($course->post_title) . '</span>';
                if ($subtitle) {
                    $html .= '<span class="scn-course-subtitle">' . esc_html($subtitle) . '</span>';
                }
                $html .= '</td>';
                
                // Author
                $html .= '<td class="scn-course-author">' . esc_html($author->display_name) . '</td>';
                
                // Topics
                $html .= '<td class="scn-course-topics">';
                if (!empty($topics) && is_array($topics)) {
                    $html .= '<div class="scn-course-topic-tags">';
                    foreach (array_slice($topics, 0, 3) as $topic) {
                        $html .= '<span class="scn-course-topic-tag">' . esc_html($topic) . '</span>';
                    }
                    if (count($topics) > 3) {
                        $html .= '<span class="scn-course-topic-tag">+' . (count($topics) - 3) . ' more</span>';
                    }
                    $html .= '</div>';
                } else {
                    // Show debug info about topics
                    $debug_info = '';
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $debug_info = ' (Debug: Taxonomy exists: ' . (taxonomy_exists('scn_topic') ? 'Yes' : 'No') . ', Topics count: ' . count($topics) . ')';
                    }
                    $html .= '<span style="color: #8c8f94; font-style: italic;">No topics' . $debug_info . '</span>';
                }
                $html .= '</td>';
                
                // Status
                $html .= '<td class="scn-course-status">';
                $html .= '<span class="scn-course-status-badge status-' . esc_attr($course->post_status) . '">' . esc_html(ucfirst($course->post_status)) . '</span>';
                $html .= '</td>';
                
                // Actions
                $html .= '<td class="scn-course-actions">';
                $html .= '<a href="' . get_permalink($course->ID) . '" class="scn-btn scn-btn-primary">View</a>';
                $html .= '<button class="scn-btn scn-btn-secondary scn-enroll-btn" data-course-id="' . $course->ID . '">Enroll</button>';
                $html .= '</td>';
                
                $html .= '</tr>';
            }
        } else {
            $html = '<tr><td colspan="6" class="scn-no-courses">No courses available</td></tr>';
        }

        wp_send_json_success($html);
        } catch (\Exception $e) {
            error_log('SCN Courses Error: ' . $e->getMessage());
            wp_send_json_error('Error loading courses: ' . $e->getMessage());
        }
    }

    public function handleGetEnrolledCoursesAjax() {
        try {
            check_ajax_referer('scn_dashboard_nonce', 'nonce');

            if (!is_user_logged_in()) {
                wp_die(__('You must be logged in.', 'scn-membership'));
            }

        // Get user's enrolled courses
        // For now, we'll check if user has any enrollment records
        // This would typically be stored in user meta or a custom table
        $user_id = get_current_user_id();
        $enrolled_courses = get_user_meta($user_id, 'scn_enrolled_courses', true);
        
        if (empty($enrolled_courses) || !is_array($enrolled_courses)) {
            $courses = [];
        } else {
            $courses = get_posts([
                'post_type' => 'scn_course',
                'post_status' => 'publish',
                'post__in' => $enrolled_courses,
                'posts_per_page' => 10,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
        }

        $html = '';
        if (!empty($courses)) {
            foreach ($courses as $course) {
                $subtitle = get_post_meta($course->ID, 'scn_course_subtitle', true);
                // Force ensure taxonomy is registered
                if (!taxonomy_exists('scn_topic')) {
                    register_taxonomy('scn_topic', 'scn_course', [
                        'labels' => [
                            'name' => __('Topics', 'scn-membership'),
                            'singular_name' => __('Topic', 'scn-membership'),
                        ],
                        'hierarchical' => false,
                        'public' => true,
                        'show_in_rest' => true,
                        'show_admin_column' => true,
                    ]);
                }
                
                $topics = [];
                if (taxonomy_exists('scn_topic')) {
                    // Try multiple methods to get topics
                    $topics = wp_get_post_terms($course->ID, 'scn_topic', ['fields' => 'names']);
                    if (is_wp_error($topics)) {
                        $topics = [];
                    }
                    
                    // If no topics found, try getting all topics for this course
                    if (empty($topics)) {
                        $all_topics = wp_get_post_terms($course->ID, 'scn_topic');
                        if (!is_wp_error($all_topics) && !empty($all_topics)) {
                            $topics = wp_list_pluck($all_topics, 'name');
                        }
                    }
                } else {
                    // Debug: Log taxonomy not exists
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SCN Course Topics Debug - Taxonomy scn_topic does not exist');
                    }
                }
                
                // Ensure topics is an array
                if (!is_array($topics)) {
                    $topics = [];
                }
                
                // Debug: Log topics for debugging
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('SCN Course Topics Debug - Course ID: ' . $course->ID . ', Topics: ' . print_r($topics, true));
                }
                $author = get_userdata($course->post_author);
                if (!$author) {
                    $author = (object) ['display_name' => 'Unknown Author'];
                }
                $featured_image = get_the_post_thumbnail_url($course->ID, 'thumbnail');
                
                $html .= '<tr>';
                
                // Course Image
                $html .= '<td class="scn-course-image">';
                if ($featured_image) {
                    $html .= '<img src="' . esc_url($featured_image) . '" alt="' . esc_attr($course->post_title) . '">';
                } else {
                    $html .= '<div class="scn-no-image"><span class="dashicons dashicons-format-video"></span></div>';
                }
                $html .= '</td>';
                
                // Course Title & Subtitle
                $html .= '<td class="scn-course-title">';
                $html .= '<span class="scn-course-name">' . esc_html($course->post_title) . '</span>';
                if ($subtitle) {
                    $html .= '<span class="scn-course-subtitle">' . esc_html($subtitle) . '</span>';
                }
                $html .= '</td>';
                
                // Author
                $html .= '<td class="scn-course-author">' . esc_html($author->display_name) . '</td>';
                
                // Topics
                $html .= '<td class="scn-course-topics">';
                if (!empty($topics) && is_array($topics)) {
                    $html .= '<div class="scn-course-topic-tags">';
                    foreach (array_slice($topics, 0, 3) as $topic) {
                        $html .= '<span class="scn-course-topic-tag">' . esc_html($topic) . '</span>';
                    }
                    if (count($topics) > 3) {
                        $html .= '<span class="scn-course-topic-tag">+' . (count($topics) - 3) . ' more</span>';
                    }
                    $html .= '</div>';
                } else {
                    // Show debug info about topics
                    $debug_info = '';
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $debug_info = ' (Debug: Taxonomy exists: ' . (taxonomy_exists('scn_topic') ? 'Yes' : 'No') . ', Topics count: ' . count($topics) . ')';
                    }
                    $html .= '<span style="color: #8c8f94; font-style: italic;">No topics' . $debug_info . '</span>';
                }
                $html .= '</td>';
                
                // Status
                $html .= '<td class="scn-course-status">';
                $html .= '<span class="scn-course-status-badge status-enrolled">Enrolled</span>';
                $html .= '</td>';
                
                // Actions
                $html .= '<td class="scn-course-actions">';
                $html .= '<a href="' . get_permalink($course->ID) . '" class="scn-btn scn-btn-primary">Continue</a>';
                $html .= '</td>';
                
                $html .= '</tr>';
            }
        } else {
            $html = '<tr><td colspan="6" class="scn-no-courses">You are not enrolled in any courses yet. Browse available courses above to get started!</td></tr>';
        }

        wp_send_json_success($html);
        } catch (\Exception $e) {
            error_log('SCN Enrolled Courses Error: ' . $e->getMessage());
            wp_send_json_error('Error loading enrolled courses: ' . $e->getMessage());
        }
    }

    public function handleGetCreatedCoursesAjax() {
        try {
            check_ajax_referer('scn_dashboard_nonce', 'nonce');
            
            if (!is_user_logged_in()) {
                wp_die(__('You must be logged in.', 'scn-membership'));
            }

        $user_id = get_current_user_id();
        $profile_id = intval($_POST['profile_id']);
        
        // Get courses created by this user
        $courses = get_posts([
            'post_type' => 'scn_course',
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => 'course_author',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ]
        ]);

        $html = '';
        if (!empty($courses)) {
            foreach ($courses as $course) {
                $subtitle = get_post_meta($course->ID, 'scn_course_subtitle', true);
                // Force ensure taxonomy is registered
                if (!taxonomy_exists('scn_topic')) {
                    register_taxonomy('scn_topic', 'scn_course', [
                        'labels' => [
                            'name' => __('Topics', 'scn-membership'),
                            'singular_name' => __('Topic', 'scn-membership'),
                        ],
                        'hierarchical' => false,
                        'public' => true,
                        'show_in_rest' => true,
                        'show_admin_column' => true,
                    ]);
                }
                
                $topics = [];
                if (taxonomy_exists('scn_topic')) {
                    // Try multiple methods to get topics
                    $topics = wp_get_post_terms($course->ID, 'scn_topic', ['fields' => 'names']);
                    if (is_wp_error($topics)) {
                        $topics = [];
                    }
                    
                    // If no topics found, try getting all topics for this course
                    if (empty($topics)) {
                        $all_topics = wp_get_post_terms($course->ID, 'scn_topic');
                        if (!is_wp_error($all_topics) && !empty($all_topics)) {
                            $topics = wp_list_pluck($all_topics, 'name');
                        }
                    }
                } else {
                    // Debug: Log taxonomy not exists
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SCN Course Topics Debug - Taxonomy scn_topic does not exist');
                    }
                }
                
                // Ensure topics is an array
                if (!is_array($topics)) {
                    $topics = [];
                }
                
                // Debug: Log topics for debugging
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('SCN Course Topics Debug - Course ID: ' . $course->ID . ', Topics: ' . print_r($topics, true));
                }
                $author = get_userdata($course->post_author);
                if (!$author) {
                    $author = (object) ['display_name' => 'Unknown Author'];
                }
                $featured_image = get_the_post_thumbnail_url($course->ID, 'thumbnail');
                
                $html .= '<tr>';
                
                // Course Image
                $html .= '<td class="scn-course-image">';
                if ($featured_image) {
                    $html .= '<img src="' . esc_url($featured_image) . '" alt="' . esc_attr($course->post_title) . '">';
                } else {
                    $html .= '<div class="scn-no-image"><span class="dashicons dashicons-format-video"></span></div>';
                }
                $html .= '</td>';
                
                // Course Title & Subtitle
                $html .= '<td class="scn-course-title">';
                $html .= '<span class="scn-course-name">' . esc_html($course->post_title) . '</span>';
                if ($subtitle) {
                    $html .= '<span class="scn-course-subtitle">' . esc_html($subtitle) . '</span>';
                }
                $html .= '</td>';
                
                // Author
                $html .= '<td class="scn-course-author">' . esc_html($author->display_name) . '</td>';
                
                // Topics
                $html .= '<td class="scn-course-topics">';
                if (!empty($topics) && is_array($topics)) {
                    $html .= '<div class="scn-course-topic-tags">';
                    foreach (array_slice($topics, 0, 3) as $topic) {
                        $html .= '<span class="scn-course-topic-tag">' . esc_html($topic) . '</span>';
                    }
                    if (count($topics) > 3) {
                        $html .= '<span class="scn-course-topic-tag">+' . (count($topics) - 3) . ' more</span>';
                    }
                    $html .= '</div>';
                } else {
                    // Show debug info about topics
                    $debug_info = '';
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $debug_info = ' (Debug: Taxonomy exists: ' . (taxonomy_exists('scn_topic') ? 'Yes' : 'No') . ', Topics count: ' . count($topics) . ')';
                    }
                    $html .= '<span style="color: #8c8f94; font-style: italic;">No topics' . $debug_info . '</span>';
                }
                $html .= '</td>';
                
                // Status
                $html .= '<td class="scn-course-status">';
                $html .= '<span class="scn-course-status-badge status-' . esc_attr($course->post_status) . '">' . esc_html(ucfirst($course->post_status)) . '</span>';
                $html .= '</td>';
                
                // Actions
                $html .= '<td class="scn-course-actions">';
                $html .= '<a href="' . get_edit_post_link($course->ID) . '" class="scn-btn scn-btn-primary">Edit</a>';
                $html .= '<a href="' . get_permalink($course->ID) . '" class="scn-btn scn-btn-secondary">View</a>';
                $html .= '</td>';
                
                $html .= '</tr>';
            }
        } else {
            $html = '<tr><td colspan="6" class="scn-no-courses">No courses created yet</td></tr>';
        }

        wp_send_json_success($html);
        } catch (\Exception $e) {
            error_log('SCN Created Courses Error: ' . $e->getMessage());
            wp_send_json_error('Error loading created courses: ' . $e->getMessage());
        }
    }

    public function handleCreateCourseAjax() {
        check_ajax_referer('scn_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in.', 'scn-membership'));
        }

        $user_id = get_current_user_id();
        
        // Get form data
        $title = sanitize_text_field($_POST['scn_course_title']); // Course title from form
        $subtitle = sanitize_text_field($_POST['scn_course_subtitle']);
        $description = sanitize_textarea_field($_POST['scn_course_description']);
        $ce_enabled = isset($_POST['scn_course_ce_enabled']) ? 1 : 0;
        $ce_hours = floatval($_POST['scn_course_ce_hours']);
        $formats = isset($_POST['scn_course_formats']) ? array_map('sanitize_text_field', $_POST['scn_course_formats']) : [];
        $outcomes = isset($_POST['scn_course_outcomes']) ? array_filter(array_map('sanitize_text_field', $_POST['scn_course_outcomes'])) : [];
        $topics = isset($_POST['scn_course_topics']) ? array_map('intval', $_POST['scn_course_topics']) : [];
        $ondemand_title = sanitize_text_field($_POST['scn_ondemand_title']);
        $ondemand_school = sanitize_text_field($_POST['scn_ondemand_school']);
        $ondemand_link = esc_url_raw($_POST['scn_ondemand_link']);

        if (empty($title) || empty($description)) {
            wp_send_json_error('Title and description are required');
        }

        if (empty($outcomes)) {
            wp_send_json_error('At least one learning outcome is required');
        }

        // Create course post
        $course_data = [
            'post_title' => $title,
            'post_content' => $description,
            'post_type' => 'scn_course',
            'post_status' => 'draft',
            'post_author' => $user_id
        ];

        $course_id = wp_insert_post($course_data);

        if (is_wp_error($course_id)) {
            wp_send_json_error('Failed to create course');
        }

        // Save course meta fields
        update_post_meta($course_id, 'scn_course_subtitle', $subtitle);
        update_post_meta($course_id, 'scn_course_description', $description);
        update_post_meta($course_id, 'scn_course_ce_enabled', $ce_enabled);
        update_post_meta($course_id, 'scn_course_ce_hours', $ce_hours);
        update_post_meta($course_id, 'scn_course_formats', $formats);
        update_post_meta($course_id, 'scn_course_outcomes', $outcomes);
        
        // Save on-demand data
        $ondemand_data = [
            'title' => $ondemand_title,
            'school' => $ondemand_school,
            'link' => $ondemand_link
        ];
        update_post_meta($course_id, 'scn_course_ondemand', $ondemand_data);

        // Set topics taxonomy
        if (!empty($topics)) {
            wp_set_post_terms($course_id, $topics, 'scn_topic');
        }

        wp_send_json_success('Course created successfully');
    }

    public function handleEnrollCourseAjax() {
        try {
            check_ajax_referer('scn_dashboard_nonce', 'nonce');
            
            if (!is_user_logged_in()) {
                wp_die(__('You must be logged in.', 'scn-membership'));
            }

            $course_id = intval($_POST['course_id']);
            $user_id = get_current_user_id();

            if (!$course_id) {
                wp_send_json_error('Invalid course ID');
            }

            // Check if course exists
            $course = get_post($course_id);
            if (!$course || $course->post_type !== 'scn_course') {
                wp_send_json_error('Course not found');
            }

            // Get current enrolled courses
            $enrolled_courses = get_user_meta($user_id, 'scn_enrolled_courses', true);
            if (!is_array($enrolled_courses)) {
                $enrolled_courses = [];
            }

            // Check if already enrolled
            if (in_array($course_id, $enrolled_courses)) {
                wp_send_json_error('You are already enrolled in this course');
            }

            // Add course to enrolled list
            $enrolled_courses[] = $course_id;
            update_user_meta($user_id, 'scn_enrolled_courses', $enrolled_courses);

            wp_send_json_success('Successfully enrolled in course');
        } catch (\Exception $e) {
            error_log('SCN Enroll Course Error: ' . $e->getMessage());
            wp_send_json_error('Error enrolling in course: ' . $e->getMessage());
        }
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
