<?php

namespace SCN\Membership\Admin;

class AdminService {
    private $settings_service;

    public function register() {
        $this->settings_service = new SettingsService();
        $this->settings_service->register();

        add_action('admin_menu', [$this, 'addAdminMenus'], 20);
        add_action('admin_init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        
        // Profile meta boxes are handled by ProfilePostType
        
        // Hard override to ensure all CPT metaboxes are in normal context
        add_action('add_meta_boxes', [$this, 'overrideMetaboxContext'], 999);
        
        // Disable Gutenberg sidebar panels for our CPTs
        add_action('enqueue_block_editor_assets', [$this, 'disableGutenbergSidebarPanels']);
        
        // Debug logger for metabox registrations (dev only)
        add_action('current_screen', [$this, 'debugMetaboxRegistrations']);
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
            'edit-tags.php?taxonomy=scn_topic&post_type=scn_course'
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
            register_taxonomy('scn_topic', 'scn_course', [
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

    /**
     * Hard override to ensure all CPT metaboxes are in normal context
     * This runs late (priority 999) to override any residual sidebar metaboxes
     */
    public function overrideMetaboxContext() {
        global $post, $wp_meta_boxes;
        
        if (!$post) {
            return;
        }

        $cpt_types = ['scn_profile', 'scn_event', 'scn_course'];
        
        if (!in_array($post->post_type, $cpt_types)) {
            return;
        }

        // If nothing registered yet, skip
        if (empty($wp_meta_boxes[$post->post_type]['side'])) {
            return;
        }

        // Copy all side boxes (all priorities) and re-add them in 'normal'/'high'
        foreach ($wp_meta_boxes[$post->post_type]['side'] as $priority => $boxes) {
            if (empty($boxes) || !is_array($boxes)) continue;

            foreach ($boxes as $id => $data) {
                if (empty($data) || !is_array($data)) continue;

                $title    = $data['title']    ?? '';
                $callback = $data['callback'] ?? null;
                $args     = $data['args']     ?? null;

                // Remove from side
                remove_meta_box($id, $post->post_type, 'side');

                // Re-add in main column; keep same callback & args
                // Use 'high' so they float near top
                if (is_callable($callback)) {
                    add_meta_box($id, $title, $callback, $post->post_type, 'normal', 'high', $args);
                }
            }
        }

        // Clean up any residual structure to avoid confusion
        unset($wp_meta_boxes[$post->post_type]['side']);
    }

    /**
     * Disable Gutenberg sidebar panels for our CPTs
     * This ensures only our classic metaboxes are used
     */
    public function disableGutenbergSidebarPanels() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        
        if (!$screen || !in_array($screen->post_type, ['scn_profile', 'scn_event', 'scn_course'], true)) {
            return;
        }

        // Dequeue any potential editor panel scripts
        $handles = [
            'courses-editor-panel',
            'events-editor-panel', 
            'profiles-editor-panel',
            'scn-courses-editor',
            'scn-events-editor',
            'scn-profiles-editor'
        ];

        foreach ($handles as $handle) {
            if (wp_script_is($handle, 'enqueued') || wp_script_is($handle, 'registered')) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
            }
        }

        // Disable Gutenberg sidebar panels via JavaScript
        wp_add_inline_script(
            'wp-edit-post',
            "
            (function() {
                if (typeof wp !== 'undefined' && wp.plugins) {
                    // Unregister any custom document setting panels
                    wp.hooks.addFilter(
                        'editor.DocumentSettingsPanel',
                        'scn-membership/disable-sidebar-panels',
                        function(panel) {
                            return null;
                        }
                    );
                    
                    // Disable the default document settings panel
                    wp.hooks.addFilter(
                        'editor.DocumentSettingsPanel',
                        'scn-membership/disable-default-panel',
                        function(panel) {
                            if (panel && panel.name === 'document-panel') {
                                return null;
                            }
                            return panel;
                        }
                    );

                    // Unregister any custom plugins that might add sidebar panels
                    const pluginSlugs = [
                        'scn-courses-editor-panel',
                        'scn-events-editor-panel',
                        'scn-profiles-editor-panel'
                    ];
                    
                    pluginSlugs.forEach(function(slug) {
                        try {
                            if (wp.plugins.getPlugin && wp.plugins.getPlugin(slug)) {
                                wp.plugins.unregisterPlugin(slug);
                            }
                        } catch(e) {
                            // Plugin might not be registered, ignore
                            console.log('Plugin ' + slug + ' was not registered, skipping unregister');
                        }
                    });
                }
            })();
            "
        );
    }

    /**
     * Debug logger for metabox registrations (dev only)
     * Logs all registered metaboxes for our CPTs to help verify no sidebar UI remains
     */
    public function debugMetaboxRegistrations($screen) {
        if (!$screen || !in_array($screen->post_type, ['scn_profile', 'scn_event', 'scn_course'], true)) {
            return;
        }
        
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        global $wp_meta_boxes;
        
        error_log('SCN MEMBERSHIP - META BOXES MAP for ' . $screen->post_type . ' >>>');
        error_log(print_r($wp_meta_boxes[$screen->post_type] ?? [], true));
        
        // Check specifically for any remaining sidebar metaboxes
        if (!empty($wp_meta_boxes[$screen->post_type]['side'])) {
            error_log('WARNING: Side metaboxes still present for ' . $screen->post_type . ':');
            error_log(print_r($wp_meta_boxes[$screen->post_type]['side'], true));
        } else {
            error_log('SUCCESS: No side metaboxes found for ' . $screen->post_type);
        }
    }

    public function enqueueScripts($hook) {
        global $post_type;
        
        // Only enqueue scripts on our CPT edit screens
        if (!in_array($post_type, ['scn_profile', 'scn_course', 'scn_event'])) {
            return;
        }
        
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }
        
        // Ensure jQuery is loaded first
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();
        
        // Enqueue scripts based on post type
        switch ($post_type) {
            case 'scn_profile':
                $this->enqueueProfileScripts();
                break;
            case 'scn_course':
                $this->enqueueCourseScripts();
                break;
            case 'scn_event':
                $this->enqueueEventScripts();
                break;
        }
    }
    
    private function enqueueProfileScripts() {
        wp_enqueue_script(
            'scn-profiles-admin',
            SCN_MEMBERSHIP_URL . 'assets/js/profiles-admin.js',
            ['jquery', 'jquery-ui-sortable', 'media-upload'],
            SCN_MEMBERSHIP_VERSION,
            true
        );
        
        wp_enqueue_style(
            'scn-profiles-admin',
            SCN_MEMBERSHIP_URL . 'assets/css/profiles-admin.css',
            [],
            SCN_MEMBERSHIP_VERSION
        );
        
        wp_localize_script('scn-profiles-admin', 'scnProfilesAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scn_profiles_admin'),
            'strings' => [
                'selectImages' => __('Select Images', 'scn-membership'),
                'selectFiles' => __('Select Files', 'scn-membership'),
                'removeImage' => __('Remove Image', 'scn-membership'),
                'removeFile' => __('Remove File', 'scn-membership'),
                'uploading' => __('Uploading...', 'scn-membership'),
                'uploadError' => __('Upload failed. Please try again.', 'scn-membership'),
                'serviceName' => __('Service name', 'scn-membership'),
                'serviceDescription' => __('Short description (optional)', 'scn-membership'),
                'removeService' => __('Remove', 'scn-membership'),
                'videoPreview' => __('Video Preview', 'scn-membership'),
                'videoThumbnail' => __('Video thumbnail', 'scn-membership'),
            ],
        ]);
    }
    
    private function enqueueCourseScripts() {
        wp_enqueue_script(
            'scn-courses-admin',
            SCN_MEMBERSHIP_URL . 'assets/js/courses-admin.js',
            ['jquery', 'media-upload'],
            SCN_MEMBERSHIP_VERSION,
            true
        );
        
        wp_enqueue_style(
            'scn-courses-admin',
            SCN_MEMBERSHIP_URL . 'assets/css/courses-admin.css',
            [],
            SCN_MEMBERSHIP_VERSION
        );
        
        wp_localize_script('scn-courses-admin', 'scnCoursesAdmin', [
            'selectImageTitle' => __('Select Course Image', 'scn-membership'),
            'useImageText' => __('Use this image', 'scn-membership'),
            'outcomePlaceholder' => __('Learning outcome', 'scn-membership'),
            'removeText' => __('Remove', 'scn-membership'),
            'ceHoursError' => __('CE hours must be at least 0.5 when CE credits are enabled', 'scn-membership'),
            'outcomesError' => __('At least one learning outcome is required', 'scn-membership'),
            'linkError' => __('On-demand link must be a valid URL', 'scn-membership'),
        ]);
    }
    
    private function enqueueEventScripts() {
        wp_enqueue_script(
            'scn-events-admin',
            SCN_MEMBERSHIP_URL . 'assets/js/events-admin.js',
            ['jquery'],
            SCN_MEMBERSHIP_VERSION,
            true
        );
        
        wp_enqueue_style(
            'scn-events-admin',
            SCN_MEMBERSHIP_URL . 'assets/css/events-admin.css',
            [],
            SCN_MEMBERSHIP_VERSION
        );
        
        wp_localize_script('scn-events-admin', 'scnEvents', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scn_events_nonce'),
        ]);
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
                        <p><?php _e('Manage topic categories for courses.', 'scn-membership'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=scn_topic&post_type=scn_course'); ?>" class="button button-primary">
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
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'scn_membership_settings-options')) {
            $this->handleSettingsSave();
        }

        // Handle reset to defaults
        if (isset($_POST['reset']) && wp_verify_nonce($_POST['_wpnonce'], 'scn_membership_settings-options')) {
            $this->handleSettingsReset();
        }

        $this->renderSettingsPage();
    }

    private function handleSettingsSave() {
        $settings = $this->settings_service->getSettings();
        $updated_settings = $this->settings_service->sanitizeSettings($_POST['scn_membership_options'] ?? []);
        
        update_option('scn_membership_options', $updated_settings);
        
        add_settings_error(
            'scn_membership_settings',
            'settings_saved',
            __('Settings saved successfully!', 'scn-membership'),
            'updated'
        );
    }

    private function handleSettingsReset() {
        $default_settings = $this->settings_service->getDefaultSettings();
        update_option('scn_membership_options', $default_settings);
        
        add_settings_error(
            'scn_membership_settings',
            'settings_reset',
            __('Settings reset to defaults!', 'scn-membership'),
            'updated'
        );
    }

    private function renderSettingsPage() {
        ?>
        <div class="wrap scn-settings-page">
            <h1><?php _e('SCN Membership Settings', 'scn-membership'); ?></h1>
            
            <?php settings_errors('scn_membership_settings'); ?>
            
            <div class="scn-settings-header">
                <div class="scn-settings-actions">
                    <button type="button" class="button" id="test-image-upload">
                        <?php _e('Test Image Upload', 'scn-membership'); ?>
                    </button>
                    <button type="button" class="button" id="clear-cache">
                        <?php _e('Clear Cache', 'scn-membership'); ?>
                    </button>
                    <button type="button" class="button" id="export-settings">
                        <?php _e('Export Settings', 'scn-membership'); ?>
                    </button>
                    <button type="button" class="button" id="import-settings">
                        <?php _e('Import Settings', 'scn-membership'); ?>
                    </button>
                </div>
            </div>

            <form method="post" action="" id="scn-settings-form">
                <?php wp_nonce_field('scn_membership_settings-options'); ?>
                
                <div class="scn-settings-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'scn-membership'); ?></a>
                        <a href="#images" class="nav-tab"><?php _e('Images', 'scn-membership'); ?></a>
                        <a href="#urls" class="nav-tab"><?php _e('URLs', 'scn-membership'); ?></a>
                        <a href="#events" class="nav-tab"><?php _e('Events', 'scn-membership'); ?></a>
                        <a href="#widgets" class="nav-tab"><?php _e('Widgets', 'scn-membership'); ?></a>
                        <a href="#advanced" class="nav-tab"><?php _e('Advanced', 'scn-membership'); ?></a>
                    </nav>

                    <div class="scn-settings-content">
                        <div id="general" class="scn-settings-tab-content active">
                            <?php $this->renderSettingsSection('general'); ?>
                        </div>
                        <div id="images" class="scn-settings-tab-content">
                            <?php $this->renderSettingsSection('images'); ?>
                        </div>
                        <div id="urls" class="scn-settings-tab-content">
                            <?php $this->renderSettingsSection('urls'); ?>
                        </div>
                        <div id="events" class="scn-settings-tab-content">
                            <?php $this->renderSettingsSection('events'); ?>
                        </div>
                        <div id="widgets" class="scn-settings-tab-content">
                            <?php $this->renderSettingsSection('widgets'); ?>
                        </div>
                        <div id="advanced" class="scn-settings-tab-content">
                            <?php $this->renderSettingsSection('advanced'); ?>
                        </div>
                    </div>
                </div>

                <div class="scn-settings-footer">
                    <?php submit_button(__('Save Settings', 'scn-membership'), 'primary', 'submit', false); ?>
                    <input type="submit" name="reset" class="button button-secondary" value="<?php esc_attr_e('Reset to Defaults', 'scn-membership'); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to reset all settings to defaults?', 'scn-membership'); ?>');" />
                </div>
            </form>

            <!-- Import Settings Modal -->
            <div id="import-modal" class="scn-modal" style="display: none;">
                <div class="scn-modal-content">
                    <div class="scn-modal-header">
                        <h3><?php _e('Import Settings', 'scn-membership'); ?></h3>
                        <span class="scn-modal-close">&times;</span>
                    </div>
                    <div class="scn-modal-body">
                        <form id="import-form" enctype="multipart/form-data">
                            <?php wp_nonce_field('scn_settings_nonce', 'nonce'); ?>
                            <p><?php _e('Select a settings file to import:', 'scn-membership'); ?></p>
                            <input type="file" name="settings_file" accept=".json" required />
                            <p class="description"><?php _e('Only JSON files exported from this plugin are supported.', 'scn-membership'); ?></p>
                        </form>
                    </div>
                    <div class="scn-modal-footer">
                        <button type="button" class="button button-primary" id="import-submit"><?php _e('Import', 'scn-membership'); ?></button>
                        <button type="button" class="button scn-modal-close"><?php _e('Cancel', 'scn-membership'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderSettingsSection($section) {
        switch ($section) {
            case 'general':
                $this->renderGeneralSettings();
                break;
            case 'images':
                $this->renderImageSettings();
                break;
            case 'urls':
                $this->renderUrlSettings();
                break;
            case 'events':
                $this->renderEventSettings();
                break;
            case 'widgets':
                $this->renderWidgetSettings();
                break;
            case 'advanced':
                $this->renderAdvancedSettings();
                break;
        }
    }

    private function renderGeneralSettings() {
        $settings = $this->settings_service->getSettings();
        ?>
        <h2><?php _e('General Settings', 'scn-membership'); ?></h2>
        <p><?php _e('Configure general settings for your SCN membership site.', 'scn-membership'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Site Name', 'scn-membership'); ?></th>
                <td>
                    <input type="text" name="scn_membership_options[site_name]" value="<?php echo esc_attr($settings['site_name']); ?>" class="regular-text" placeholder="<?php esc_attr_e('SCN Membership', 'scn-membership'); ?>" />
                    <p class="description"><?php _e('The name of your SCN membership site.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Site Description', 'scn-membership'); ?></th>
                <td>
                    <textarea name="scn_membership_options[site_description]" rows="3" class="large-text"><?php echo esc_textarea($settings['site_description']); ?></textarea>
                    <p class="description"><?php _e('A brief description of your SCN membership site.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Contact Email', 'scn-membership'); ?></th>
                <td>
                    <input type="email" name="scn_membership_options[contact_email]" value="<?php echo esc_attr($settings['contact_email']); ?>" class="regular-text" />
                    <p class="description"><?php _e('Email address for general inquiries.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Enable Member Registration', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[enable_registration]" value="1" <?php checked($settings['enable_registration']); ?> />
                        <?php _e('Allow new members to register and create profiles.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    private function renderImageSettings() {
        $settings = $this->settings_service->getSettings();
        ?>
        <h2><?php _e('Image Upload Settings', 'scn-membership'); ?></h2>
        <p><?php _e('Configure image upload settings and optimization options.', 'scn-membership'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Maximum Image Size (MB)', 'scn-membership'); ?></th>
                <td>
                    <input type="number" name="scn_membership_options[max_image_size]" value="<?php echo esc_attr($settings['max_image_size']); ?>" min="1" max="50" step="1" class="small-text" />
                    <p class="description"><?php _e('Maximum file size for image uploads.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Allowed Image Types', 'scn-membership'); ?></th>
                <td>
                    <fieldset>
                        <?php
                        $allowed_types = [
                            'jpg' => 'JPEG (.jpg)',
                            'jpeg' => 'JPEG (.jpeg)',
                            'png' => 'PNG (.png)',
                            'gif' => 'GIF (.gif)',
                            'webp' => 'WebP (.webp)'
                        ];
                        $selected_types = $settings['allowed_image_types'];
                        foreach ($allowed_types as $type => $label) {
                            printf(
                                '<label><input type="checkbox" name="scn_membership_options[allowed_image_types][]" value="%s" %s /> %s</label><br>',
                                esc_attr($type),
                                checked(in_array($type, $selected_types), true, false),
                                esc_html($label)
                            );
                        }
                        ?>
                    </fieldset>
                    <p class="description"><?php _e('Select which image file types are allowed for upload.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Auto-optimize Images', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[auto_optimize_images]" value="1" <?php checked($settings['auto_optimize_images']); ?> />
                        <?php _e('Automatically optimize uploaded images for web.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Generate WebP Versions', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[generate_webp]" value="1" <?php checked($settings['generate_webp']); ?> />
                        <?php _e('Generate WebP versions of uploaded images for better performance.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    private function renderUrlSettings() {
        $settings = $this->settings_service->getSettings();
        ?>
        <h2><?php _e('URL & Link Settings', 'scn-membership'); ?></h2>
        <p><?php _e('Configure URL validation and link handling options.', 'scn-membership'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Force HTTPS', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[force_https]" value="1" <?php checked($settings['force_https']); ?> />
                        <?php _e('Automatically convert HTTP URLs to HTTPS.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Validate URLs', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[validate_urls]" value="1" <?php checked($settings['validate_urls']); ?> />
                        <?php _e('Validate URLs before saving to ensure they are accessible.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Allowed Domains', 'scn-membership'); ?></th>
                <td>
                    <textarea name="scn_membership_options[allowed_domains]" rows="3" class="large-text" placeholder="example.com, another-site.com"><?php echo esc_textarea($settings['allowed_domains']); ?></textarea>
                    <p class="description"><?php _e('Comma-separated list of allowed domains for external links.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Open External Links in New Tab', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[open_external_new_tab]" value="1" <?php checked($settings['open_external_new_tab']); ?> />
                        <?php _e('Automatically open external links in a new tab.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    private function renderEventSettings() {
        $settings = $this->settings_service->getSettings();
        ?>
        <h2><?php _e('Event Settings', 'scn-membership'); ?></h2>
        <p><?php _e('Configure event and session management settings.', 'scn-membership'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Auto-approve Sessions', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[auto_approve_sessions]" value="1" <?php checked($settings['auto_approve_sessions']); ?> />
                        <?php _e('Automatically approve sessions for current and future events.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Session Approval Email', 'scn-membership'); ?></th>
                <td>
                    <input type="email" name="scn_membership_options[session_approval_email]" value="<?php echo esc_attr($settings['session_approval_email']); ?>" class="regular-text" />
                    <p class="description"><?php _e('Email address to receive session approval notifications.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Widget Cache Duration (minutes)', 'scn-membership'); ?></th>
                <td>
                    <input type="number" name="scn_membership_options[widget_cache_duration]" value="<?php echo esc_attr($settings['widget_cache_duration']); ?>" min="5" max="1440" step="5" class="small-text" />
                    <p class="description"><?php _e('How long to cache widget data before refreshing.', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function renderWidgetSettings() {
        $settings = $this->settings_service->getSettings();
        ?>
        <h2><?php _e('Widget Settings', 'scn-membership'); ?></h2>
        <p><?php _e('Configure widget display and behavior settings.', 'scn-membership'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Default Widget Title', 'scn-membership'); ?></th>
                <td>
                    <input type="text" name="scn_membership_options[default_widget_title]" value="<?php echo esc_attr($settings['default_widget_title']); ?>" class="regular-text" placeholder="<?php esc_attr_e('Where Our Members Are Speaking', 'scn-membership'); ?>" />
                    <p class="description"><?php _e('Default title for the "Where Our Members Are Speaking" widget.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Default Widget Limit', 'scn-membership'); ?></th>
                <td>
                    <input type="number" name="scn_membership_options[default_widget_limit]" value="<?php echo esc_attr($settings['default_widget_limit']); ?>" min="1" max="20" step="1" class="small-text" />
                    <p class="description"><?php _e('Default number of sessions to show in the widget.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Event Dates by Default', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[widget_show_dates]" value="1" <?php checked($settings['widget_show_dates']); ?> />
                        <?php _e('Show event dates in the widget by default.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Event Names by Default', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[widget_show_events]" value="1" <?php checked($settings['widget_show_events']); ?> />
                        <?php _e('Show event names in the widget by default.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    private function renderAdvancedSettings() {
        $settings = $this->settings_service->getSettings();
        ?>
        <h2><?php _e('Advanced Settings', 'scn-membership'); ?></h2>
        <p><?php _e('Advanced configuration options for developers and power users.', 'scn-membership'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Debug Mode', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[debug_mode]" value="1" <?php checked($settings['debug_mode']); ?> />
                        <?php _e('Enable debug mode for troubleshooting.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Log Errors', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="scn_membership_options[log_errors]" value="1" <?php checked($settings['log_errors']); ?> />
                        <?php _e('Log errors to the WordPress error log.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Custom CSS', 'scn-membership'); ?></th>
                <td>
                    <textarea name="scn_membership_options[custom_css]" rows="10" class="large-text code"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                    <p class="description"><?php _e('Custom CSS to be added to the frontend.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Custom JavaScript', 'scn-membership'); ?></th>
                <td>
                    <textarea name="scn_membership_options[custom_js]" rows="10" class="large-text code"><?php echo esc_textarea($settings['custom_js']); ?></textarea>
                    <p class="description"><?php _e('Custom JavaScript to be added to the frontend.', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>
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



