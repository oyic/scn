<?php

namespace SCN\Membership\Admin;

class AdminService {
    public function register() {
        add_action('admin_menu', [$this, 'addAdminMenus'], 20);
        add_action('admin_init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        
        // Register Profile meta boxes
        add_action('add_meta_boxes', [$this, 'addProfileMetaBoxes']);
    }

    public function addAdminMenus() {
        // Force register post types and taxonomies first
        $this->ensurePostTypesRegistered();
        
        add_menu_page(
            __('SCN Membership', 'scn-membership'),
            __('SCN Membership', 'scn-membership'),
            'manage_options',
            'scn-membership',
            [$this, 'adminPage'],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'scn-membership',
            __('Settings', 'scn-membership'),
            __('Settings', 'scn-membership'),
            'manage_options',
            'scn-membership-settings',
            [$this, 'settingsPage']
        );

        // Add post type submenus
        add_submenu_page(
            'scn-membership',
            __('Profiles', 'scn-membership'),
            __('Profiles', 'scn-membership'),
            'edit_posts',
            'edit.php?post_type=scn_profile'
        );

        add_submenu_page(
            'scn-membership',
            __('Courses', 'scn-membership'),
            __('Courses', 'scn-membership'),
            'edit_posts',
            'edit.php?post_type=scn_course'
        );

        add_submenu_page(
            'scn-membership',
            __('Events', 'scn-membership'),
            __('Events', 'scn-membership'),
            'edit_posts',
            'edit.php?post_type=scn_event'
        );

        // Add Events admin tools submenus
        add_submenu_page(
            'scn-membership',
            __('Event Aliases', 'scn-membership'),
            __('Event Aliases', 'scn-membership'),
            'manage_scn_events',
            'scn-event-aliases',
            [$this, 'aliasesPage']
        );

        add_submenu_page(
            'scn-membership',
            __('Event Field Locks', 'scn-membership'),
            __('Event Field Locks', 'scn-membership'),
            'lock_scn_events',
            'scn-event-locks',
            [$this, 'locksPage']
        );

        add_submenu_page(
            'scn-membership',
            __('Merge Events', 'scn-membership'),
            __('Merge Events', 'scn-membership'),
            'merge_scn_events',
            'scn-event-merge',
            [$this, 'mergePage']
        );

        // Also add these as submenus under the Events post type
        add_submenu_page(
            'edit.php?post_type=scn_event',
            __('Event Aliases', 'scn-membership'),
            __('Aliases', 'scn-membership'),
            'manage_scn_events',
            'scn-event-aliases',
            [$this, 'aliasesPage']
        );

        add_submenu_page(
            'edit.php?post_type=scn_event',
            __('Event Field Locks', 'scn-membership'),
            __('Field Locks', 'scn-membership'),
            'lock_scn_events',
            'scn-event-locks',
            [$this, 'locksPage']
        );

        add_submenu_page(
            'edit.php?post_type=scn_event',
            __('Merge Events', 'scn-membership'),
            __('Merge Events', 'scn-membership'),
            'merge_scn_events',
            'scn-event-merge',
            [$this, 'mergePage']
        );

        add_submenu_page(
            'scn-membership',
            __('Topics', 'scn-membership'),
            __('Topics', 'scn-membership'),
            'manage_categories',
            'edit-tags.php?taxonomy=scn_topic&post_type=scn_profile'
        );
    }

    private function ensurePostTypesRegistered() {
        // Ensure capabilities are added first
        $this->addCapabilities();
        
        // Register post types directly if they don't exist
        if (!post_type_exists('scn_profile')) {
            register_post_type('scn_profile', [
                'labels' => [
                    'name' => __('SCN Profiles', 'scn-membership'),
                    'singular_name' => __('Profile', 'scn-membership'),
                    'add_new' => __('Add New Profile', 'scn-membership'),
                    'add_new_item' => __('Add New Profile', 'scn-membership'),
                    'edit_item' => __('Edit Profile', 'scn-membership'),
                    'new_item' => __('New Profile', 'scn-membership'),
                    'view_item' => __('View Profile', 'scn-membership'),
                    'search_items' => __('Search Profiles', 'scn-membership'),
                    'not_found' => __('No profiles found', 'scn-membership'),
                    'not_found_in_trash' => __('No profiles found in trash', 'scn-membership'),
                ],
                'public' => true,
                'has_archive' => true,
                'rewrite' => ['slug' => 'profiles'],
                'supports' => ['title', 'editor', 'thumbnail'],
                'capability_type' => 'scn_profile',
                'map_meta_cap' => true,
                'show_in_rest' => true,
                'rest_base' => 'profiles',
                'rest_controller_class' => 'WP_REST_Posts_Controller',
                'show_in_menu' => false,
            ]);
        }

        if (!post_type_exists('scn_course')) {
            register_post_type('scn_course', [
                'labels' => [
                    'name' => __('Courses', 'scn-membership'),
                    'singular_name' => __('Course', 'scn-membership'),
                    'add_new' => __('Add New Course', 'scn-membership'),
                    'add_new_item' => __('Add New Course', 'scn-membership'),
                    'edit_item' => __('Edit Course', 'scn-membership'),
                    'new_item' => __('New Course', 'scn-membership'),
                    'view_item' => __('View Course', 'scn-membership'),
                    'search_items' => __('Search Courses', 'scn-membership'),
                    'not_found' => __('No courses found', 'scn-membership'),
                    'not_found_in_trash' => __('No courses found in trash', 'scn-membership'),
                ],
                'public' => true,
                'has_archive' => true,
                'rewrite' => ['slug' => 'courses'],
                'supports' => ['title', 'editor', 'thumbnail', 'author'],
                'capability_type' => 'scn_course',
                'map_meta_cap' => true,
                'show_in_rest' => true,
                'rest_base' => 'courses',
                'rest_controller_class' => 'WP_REST_Posts_Controller',
                'show_in_menu' => false,
            ]);
        }

        if (!post_type_exists('scn_event')) {
            register_post_type('scn_event', [
                'labels' => [
                    'name' => __('SCN Events', 'scn-membership'),
                    'singular_name' => __('Event', 'scn-membership'),
                    'add_new' => __('Add New Event', 'scn-membership'),
                    'add_new_item' => __('Add New Event', 'scn-membership'),
                    'edit_item' => __('Edit Event', 'scn-membership'),
                    'new_item' => __('New Event', 'scn-membership'),
                    'view_item' => __('View Event', 'scn-membership'),
                    'search_items' => __('Search Events', 'scn-membership'),
                    'not_found' => __('No events found', 'scn-membership'),
                    'not_found_in_trash' => __('No events found in trash', 'scn-membership'),
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'supports' => ['title', 'editor'],
                'capability_type' => 'scn_event',
                'map_meta_cap' => true,
                'show_in_rest' => true,
                'rest_base' => 'events',
                'rest_controller_class' => 'WP_REST_Posts_Controller',
            ]);
        }

        if (!taxonomy_exists('scn_topic')) {
            register_taxonomy('scn_topic', 'scn_profile', [
                'labels' => [
                    'name' => __('Topics', 'scn-membership'),
                    'singular_name' => __('Topic', 'scn-membership'),
                    'search_items' => __('Search Topics', 'scn-membership'),
                    'all_items' => __('All Topics', 'scn-membership'),
                    'edit_item' => __('Edit Topic', 'scn-membership'),
                    'update_item' => __('Update Topic', 'scn-membership'),
                    'add_new_item' => __('Add New Topic', 'scn-membership'),
                    'new_item_name' => __('New Topic Name', 'scn-membership'),
                    'menu_name' => __('Topics', 'scn-membership'),
                ],
                'hierarchical' => false,
                'public' => true,
                'show_in_rest' => true,
                'rest_base' => 'topics',
                'rest_controller_class' => 'WP_REST_Terms_Controller',
                'show_admin_column' => true,
            ]);
        }
        
        // Flush rewrite rules to ensure REST API routes are registered
        if (!get_option('scn_rest_routes_flushed')) {
            flush_rewrite_rules();
            update_option('scn_rest_routes_flushed', true);
        }
    }

    public function init() {
        // TODO: Initialize admin functionality
    }

    private function addCapabilities() {
        // Add capabilities for administrators
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $capabilities = [
                // Profile capabilities
                'edit_scn_profiles',
                'edit_others_scn_profiles',
                'publish_scn_profiles',
                'read_private_scn_profiles',
                'delete_scn_profiles',
                'delete_private_scn_profiles',
                'delete_published_scn_profiles',
                'delete_others_scn_profiles',
                'edit_private_scn_profiles',
                'edit_published_scn_profiles',
                
                // Course capabilities
                'edit_scn_courses',
                'edit_others_scn_courses',
                'publish_scn_courses',
                'read_private_scn_courses',
                'delete_scn_courses',
                'delete_private_scn_courses',
                'delete_published_scn_courses',
                'delete_others_scn_courses',
                'edit_private_scn_courses',
                'edit_published_scn_courses',
                
                // Event capabilities
                'edit_scn_events',
                'edit_others_scn_events',
                'publish_scn_events',
                'read_private_scn_events',
                'delete_scn_events',
                'delete_private_scn_events',
                'delete_published_scn_events',
                'delete_others_scn_events',
                'edit_private_scn_events',
                'edit_published_scn_events',
                'manage_scn_events',
                'merge_scn_events',
                'lock_scn_events',
            ];

            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }

        // Add capabilities for editors
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_capabilities = [
                'edit_scn_profiles',
                'edit_others_scn_profiles',
                'publish_scn_profiles',
                'read_private_scn_profiles',
                'delete_scn_profiles',
                'delete_others_scn_profiles',
                'delete_published_scn_profiles',
                'edit_published_scn_profiles',
                
                'edit_scn_courses',
                'edit_others_scn_courses',
                'publish_scn_courses',
                'read_private_scn_courses',
                'delete_scn_courses',
                'delete_others_scn_courses',
                'delete_published_scn_courses',
                'edit_published_scn_courses',
                
                'edit_scn_events',
                'edit_others_scn_events',
                'publish_scn_events',
                'read_private_scn_events',
                'delete_scn_events',
                'delete_others_scn_events',
                'delete_published_scn_events',
                'edit_published_scn_events',
            ];

            foreach ($editor_capabilities as $cap) {
                $editor_role->add_cap($cap);
            }
        }

        // Add capabilities for authors
        $author_role = get_role('author');
        if ($author_role) {
            $author_capabilities = [
                'edit_scn_profiles',
                'publish_scn_profiles',
                'delete_scn_profiles',
                'edit_published_scn_profiles',
                
                'edit_scn_courses',
                'publish_scn_courses',
                'delete_scn_courses',
                'edit_published_scn_courses',
                
                'edit_scn_events',
                'publish_scn_events',
                'delete_scn_events',
                'edit_published_scn_events',
            ];

            foreach ($author_capabilities as $cap) {
                $author_role->add_cap($cap);
            }
        }
    }

    public function addProfileMetaBoxes($post) {
        if (!$post || $post->post_type !== 'scn_profile') {
            return;
        }

        // Add test meta box
        add_meta_box(
            'scn_profile_test',
            __('SCN Profile Test', 'scn-membership'),
            function($post) {
                echo '<p><strong>✅ SCN Profile meta boxes are working!</strong></p>';
                echo '<p>Post ID: ' . $post->ID . '</p>';
                echo '<p>Post Type: ' . $post->post_type . '</p>';
                echo '<p>This confirms the meta box system is functioning correctly.</p>';
            },
            'scn_profile',
            'normal',
            'high'
        );

        // Add basic info meta box
        add_meta_box(
            'scn_profile_basic_info',
            __('Basic Information', 'scn-membership'),
            function($post) {
                wp_nonce_field('scn_profile_meta', 'scn_profile_meta_nonce');
                
                $first_name = get_post_meta($post->ID, 'scn_first_name', true);
                $last_name = get_post_meta($post->ID, 'scn_last_name', true);
                $credentials = get_post_meta($post->ID, 'scn_credentials', true);
                $location = get_post_meta($post->ID, 'scn_location', true);
                $main_url = get_post_meta($post->ID, 'scn_main_url', true);
                $bio = get_post_meta($post->ID, 'scn_bio', true);
                $member_since = get_post_meta($post->ID, 'scn_member_since', true);

                ?>
                <table class="form-table">
                    <tr>
                        <th><label for="scn_first_name"><?php _e('First Name', 'scn-membership'); ?></label></th>
                        <td><input type="text" id="scn_first_name" name="scn_first_name" value="<?php echo esc_attr($first_name); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="scn_last_name"><?php _e('Last Name', 'scn-membership'); ?></label></th>
                        <td><input type="text" id="scn_last_name" name="scn_last_name" value="<?php echo esc_attr($last_name); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="scn_credentials"><?php _e('Credentials', 'scn-membership'); ?></label></th>
                        <td><input type="text" id="scn_credentials" name="scn_credentials" value="<?php echo esc_attr($credentials); ?>" class="regular-text" placeholder="<?php _e('e.g., PhD, MBA, CSP', 'scn-membership'); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="scn_location"><?php _e('Location', 'scn-membership'); ?></label></th>
                        <td><input type="text" id="scn_location" name="scn_location" value="<?php echo esc_attr($location); ?>" class="regular-text" placeholder="<?php _e('City, State/Country', 'scn-membership'); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="scn_main_url"><?php _e('Main Website URL', 'scn-membership'); ?></label></th>
                        <td><input type="url" id="scn_main_url" name="scn_main_url" value="<?php echo esc_attr($main_url); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="scn_member_since"><?php _e('Member Since', 'scn-membership'); ?></label></th>
                        <td><input type="date" id="scn_member_since" name="scn_member_since" value="<?php echo esc_attr($member_since); ?>" /></td>
                    </tr>
                </table>

                <h4><?php _e('Bio', 'scn-membership'); ?></h4>
                <?php
                wp_editor($bio, 'scn_bio', [
                    'textarea_name' => 'scn_bio',
                    'media_buttons' => false,
                    'textarea_rows' => 10,
                ]);
                ?>
                <?php
            },
            'scn_profile',
            'normal',
            'high'
        );
    }

    public function enqueueScripts() {
        // TODO: Enqueue admin scripts and styles
    }

    public function registerRestRoutes() {
        // Ensure post types are registered for REST API
        $this->ensurePostTypesRegistered();
        
        // Register REST API endpoints for media uploads
        register_rest_route('wp/v2', '/media', [
            'methods' => 'POST',
            'callback' => [$this, 'handleMediaUpload'],
            'permission_callback' => [$this, 'checkMediaUploadPermission'],
        ]);
    }

    public function handleMediaUpload($request) {
        // Let WordPress handle the media upload
        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => 0,
                'url' => '',
                'message' => 'Media upload handled by WordPress core'
            ]
        ]);
    }

    public function checkMediaUploadPermission($request) {
        return current_user_can('upload_files');
    }

    public function adminPage() {
        ?>
        <div class="wrap">
            <h1><?php _e('SCN Membership', 'scn-membership'); ?></h1>
            <div class="scn-admin-dashboard">
                <div class="scn-admin-cards">
                    <div class="scn-admin-card">
                        <h2><?php _e('Profiles', 'scn-membership'); ?></h2>
                        <p><?php _e('Manage member profiles, photos, videos, and press kits.', 'scn-membership'); ?></p>
                        <a href="<?php echo admin_url('edit.php?post_type=scn_profile'); ?>" class="button button-primary">
                            <?php _e('View Profiles', 'scn-membership'); ?>
                        </a>
                    </div>
                    
                    <div class="scn-admin-card">
                        <h2><?php _e('Courses', 'scn-membership'); ?></h2>
                        <p><?php _e('Manage courses, CE hours, and learning outcomes.', 'scn-membership'); ?></p>
                        <a href="<?php echo admin_url('edit.php?post_type=scn_course'); ?>" class="button button-primary">
                            <?php _e('View Courses', 'scn-membership'); ?>
                        </a>
                    </div>
                    
                    <div class="scn-admin-card">
                        <h2><?php _e('Events', 'scn-membership'); ?></h2>
                        <p><?php _e('Manage speaking engagements and events.', 'scn-membership'); ?></p>
                        <a href="<?php echo admin_url('edit.php?post_type=scn_event'); ?>" class="button button-primary">
                            <?php _e('View Events', 'scn-membership'); ?>
                        </a>
                    </div>
                    
                    <div class="scn-admin-card">
                        <h2><?php _e('Topics', 'scn-membership'); ?></h2>
                        <p><?php _e('Manage topic categories for profiles and courses.', 'scn-membership'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=scn_topic&post_type=scn_profile'); ?>" class="button button-primary">
                            <?php _e('Manage Topics', 'scn-membership'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="scn-admin-info">
                    <h3><?php _e('Quick Stats', 'scn-membership'); ?></h3>
                    <p>
                        <?php
                        $profiles_count = wp_count_posts('scn_profile');
                        $courses_count = wp_count_posts('scn_course');
                        $events_count = wp_count_posts('scn_event');
                        $topics_count = wp_count_terms('scn_topic');
                        
                        $profiles_published = isset($profiles_count->publish) ? $profiles_count->publish : 0;
                        $courses_published = isset($courses_count->publish) ? $courses_count->publish : 0;
                        $events_published = isset($events_count->publish) ? $events_count->publish : 0;
                        $topics_count = is_wp_error($topics_count) ? 0 : $topics_count;
                        
                        printf(
                            __('%d Profiles, %d Courses, %d Events, %d Topics', 'scn-membership'),
                            $profiles_published,
                            $courses_published,
                            $events_published,
                            $topics_count
                        );
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .scn-admin-dashboard {
            margin-top: 20px;
        }
        .scn-admin-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .scn-admin-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .scn-admin-card h2 {
            margin-top: 0;
            color: #23282d;
        }
        .scn-admin-card p {
            color: #666;
            margin-bottom: 15px;
        }
        .scn-admin-info {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
        }
        .scn-admin-info h3 {
            margin-top: 0;
        }
        </style>
        <?php
    }

    public function settingsPage() {
        ?>
        <div class="wrap">
            <h1><?php _e('SCN Membership Settings', 'scn-membership'); ?></h1>
            <p><?php _e('Settings page coming soon. This will include configuration options for image uploads, URL rules, and other plugin settings.', 'scn-membership'); ?></p>
        </div>
        <?php
    }

    public function aliasesPage() {
        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        // Include the aliases page from the Events module
        $aliases_page = new \SCN\Membership\Modules\Events\Admin\AliasesPage();
        $aliases_page->renderPage();
    }

    public function locksPage() {
        if (!current_user_can('lock_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        // Include the locks page from the Events module
        $locks_page = new \SCN\Membership\Modules\Events\Admin\LocksPage();
        $locks_page->renderPage();
    }

    public function mergePage() {
        if (!current_user_can('merge_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        // Include the merge page from the Events module
        $merge_page = new \SCN\Membership\Modules\Events\Admin\MergePage();
        $merge_page->renderPage();
    }
}



