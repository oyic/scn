<?php

namespace SCN\Membership\Admin;

class AdminService {
    private $settings_service;
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register();
    }

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
        
        // Debug logger for metabox registrations (dev only) - but not for ACF screens
        add_action('current_screen', function($screen) {
            // Skip ALL ACF admin screens
            $acf_post_types = ['acf-field-group', 'acf-field', 'acf-post-type', 'acf-taxonomy', 'acf-ui-options-page'];
            if ($screen && in_array($screen->post_type, $acf_post_types, true)) {
                return;
            }
            $this->debugMetaboxRegistrations($screen);
        });
        
        // Auto-create WordPress user when member is saved (new or updated)
        // Only creates if user doesn't already exist
        // Using acf/save_post to ensure ACF fields are available
        add_action('acf/save_post', [$this, 'createUserForMemberAfterAcf'], 20);
    }

    public function addAdminMenus() {
        // Prevent duplicate menu registration
        static $menus_registered = false;
        if ($menus_registered) {
            return;
        }
        $menus_registered = true;
        
        // Don't interfere with ANY ACF admin pages
        $acf_post_types = ['acf-field-group', 'acf-post-type', 'acf-taxonomy', 'acf-ui-options-page'];
        
        if (isset($_GET['post_type']) && in_array($_GET['post_type'], $acf_post_types)) {
            return;
        }
        if (isset($_GET['post']) && in_array(get_post_type($_GET['post']), $acf_post_types)) {
            return;
        }
        
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
            __('Members', 'scn-membership'),
            __('Members', 'scn-membership'),
            'edit_posts',
            'edit.php?post_type=member'
        );
        
        // Add any ACF-created member post types
        $this->addACFMemberPostTypes();
        
        // Add specific known member post types (add more as needed)
        $this->addSpecificMemberPostTypes();
        
        // Force classic editor for member post types and course CPT
        add_filter('use_block_editor_for_post_type', [$this, 'disableBlockEditorForMemberTypes'], 10, 2);
        add_filter('use_block_editor_for_post', [$this, 'disableBlockEditorForMemberPosts'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'ensureClassicEditorForMembers']);

        // Add course CPT submenu (moved from WordPress admin)
        add_submenu_page(
            'scn-membership',
            __('Courses', 'scn-membership'),
            __('Courses', 'scn-membership'),
            'edit_posts',
            'edit.php?post_type=course'
        );

        // Add events submenu (moved from WordPress admin)
        add_submenu_page(
            'scn-membership',
            __('Events', 'scn-membership'),
            __('Events', 'scn-membership'),
            'edit_posts',
            'edit.php?post_type=event'
        );

        add_submenu_page(
            'scn-membership',
            __('Topics', 'scn-membership'),
            __('Topics', 'scn-membership'),
            'manage_categories',
            'edit-tags.php?taxonomy=scn_topic&post_type=course'
        );
        
        // Hide Events from main WordPress admin menu
        add_action('admin_menu', [$this, 'hideEventsFromMainMenu'], 999);
    }

    public function hideEventsFromMainMenu() {
        remove_menu_page('edit.php?post_type=event');
    }

    private function addACFMemberPostTypes() {
        // Look for any post types that might be member-related
        $post_types = get_post_types([], 'objects');
        
        foreach ($post_types as $post_type_name => $post_type_obj) {
            // Skip our existing post types
            if (in_array($post_type_name, ['member', 'course'])) {
                continue;
            }
            
            // Check if this looks like a member post type
            $is_member_type = $this->isMemberPostType($post_type_name, $post_type_obj);
            
            if ($is_member_type) {
                // Add it as a submenu under SCN Membership
                add_submenu_page(
                    'scn-membership',
                    $post_type_obj->label,
                    $post_type_obj->labels->menu_name ?? $post_type_obj->label,
                    $post_type_obj->cap->edit_posts,
                    'edit.php?post_type=' . $post_type_name
                );
                
                // Hide it from the main menu if it's currently showing
                if ($post_type_obj->show_in_menu) {
                    $post_type_obj->show_in_menu = false;
                }
            }
        }
    }
    
    private function isMemberPostType($post_type_name, $post_type_obj) {
        // Check various indicators that this might be a member post type
        
        // Check post type name
        $name_lower = strtolower($post_type_name);
        if (strpos($name_lower, 'member') !== false) {
            return true;
        }
        
        // Check labels
        $label_lower = strtolower($post_type_obj->label);
        $singular_lower = strtolower($post_type_obj->labels->singular_name);
        
        if (strpos($label_lower, 'member') !== false || 
            strpos($singular_lower, 'member') !== false) {
            return true;
        }
        
        // Check if it's an ACF post type that might be member-related
        if (function_exists('acf_get_post_type') && acf_get_post_type($post_type_name)) {
            // If it's created by ACF and has member-like naming, include it
            if (strpos($name_lower, 'user') !== false || 
                strpos($name_lower, 'profile') !== false ||
                strpos($name_lower, 'person') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function addSpecificMemberPostTypes() {
        // Add specific member post types that you know exist
        // Add the post type names here as you discover them
        
        $known_member_post_types = [
            // 'member',           // Add your ACF-created member post type name here
            // 'member',       // Or any other member-related post types
            // 'acf_member',       // etc.
        ];
        
        foreach ($known_member_post_types as $post_type_name) {
            if (post_type_exists($post_type_name)) {
                $post_type_obj = get_post_type_object($post_type_name);
                
                // Add it as a submenu under SCN Membership
                add_submenu_page(
                    'scn-membership',
                    $post_type_obj->label,
                    $post_type_obj->labels->menu_name ?? $post_type_obj->label,
                    $post_type_obj->cap->edit_posts,
                    'edit.php?post_type=' . $post_type_name
                );
                
                // Hide it from the main menu if it's currently showing
                if ($post_type_obj->show_in_menu) {
                    $post_type_obj->show_in_menu = false;
                }
            }
        }
    }
    
    public function disableBlockEditorForMemberTypes($use_block_editor, $post_type) {
        // Define post types that should use classic editor
        $classic_editor_post_types = [
            'member',
            'member',
            'acf_member',
            'members',
            'course', // Add course CPT to classic editor
        ];
        
        // Check if this is a post type that should use classic editor
        if (in_array($post_type, $classic_editor_post_types)) {
            return false; // Use classic editor
        }
        
        // Also check by post type name pattern
        if (strpos($post_type, 'member') !== false) {
            return false; // Use classic editor
        }
        
        // Check if it's an ACF-created member-related post type
        if (function_exists('acf_get_post_type') && acf_get_post_type($post_type)) {
            $post_type_obj = get_post_type_object($post_type);
            if ($post_type_obj) {
                $name_lower = strtolower($post_type);
                $label_lower = strtolower($post_type_obj->label);
                $singular_lower = strtolower($post_type_obj->labels->singular_name);
                
                // If it looks like a member post type, use classic editor
                if (strpos($name_lower, 'member') !== false || 
                    strpos($label_lower, 'member') !== false || 
                    strpos($singular_lower, 'member') !== false ||
                    strpos($name_lower, 'user') !== false ||
                    strpos($name_lower, 'profile') !== false ||
                    strpos($name_lower, 'person') !== false) {
                    return false; // Use classic editor
                }
            }
        }
        
        return $use_block_editor; // Use default behavior for other post types
    }
    
    public function disableBlockEditorForMemberPosts($use_block_editor, $post) {
        if (!$post) {
            return $use_block_editor;
        }
        
        $post_type = get_post_type($post);
        
        // Force classic editor for course CPT
        if ($post_type === 'course') {
            return false;
        }
        
        // Use the same logic as the post type filter for member types
        return !$this->isMemberPostType($post_type, get_post_type_object($post_type));
    }
    
    public function ensureClassicEditorForMembers($hook) {
        global $post_type, $post;
        
        // Only run on post editing screens
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }
        
        // Check if we're editing a member post type
        $current_post_type = $post_type;
        if (!$current_post_type && $post) {
            $current_post_type = get_post_type($post);
        }
        
        // Check if we're editing a member post type or course CPT
        $should_use_classic = ($current_post_type && $this->isMemberPostType($current_post_type, get_post_type_object($current_post_type))) || $current_post_type === 'course';
        
        if ($should_use_classic) {
            // Ensure classic editor is loaded
            add_filter('user_can_richedit', '__return_true');
            
            // Add inline script to ensure classic editor
            wp_add_inline_script('jquery', "
                jQuery(document).ready(function($) {
                    // Force classic editor mode
                    if (typeof wp !== 'undefined' && wp.data) {
                        wp.data.dispatch('core/edit-post').switchEditorMode('text');
                    }
                });
            ");
        }
    }

    private function ensurePostTypesRegistered() {
        // Ensure capabilities are added first
        $this->addCapabilities();
        
        // Register member post type
        if (!post_type_exists('member')) {
            register_post_type('member', [
                'labels' => [
                    'name' => __('Members', 'scn-membership'),
                    'singular_name' => __('Member', 'scn-membership'),
                    'add_new' => __('Add New Member', 'scn-membership'),
                    'add_new_item' => __('Add New Member', 'scn-membership'),
                    'edit_item' => __('Edit Member', 'scn-membership'),
                    'new_item' => __('New Member', 'scn-membership'),
                    'view_item' => __('View Member', 'scn-membership'),
                    'search_items' => __('Search Members', 'scn-membership'),
                    'not_found' => __('No members found', 'scn-membership'),
                    'not_found_in_trash' => __('No members found in trash', 'scn-membership'),
                ],
                'public' => true,
                'has_archive' => true,
                'rewrite' => ['slug' => 'members'],
                'supports' => ['title', 'thumbnail', 'author'],
                'capability_type' => 'member',
                'map_meta_cap' => true,
                'show_in_rest' => true,
                'rest_base' => 'members',
                'rest_controller_class' => 'WP_REST_Posts_Controller',
                'show_in_menu' => false, // Hide from main menu, show only under SCN Membership
            ]);
        }
        
        // profile post type removed - using 'member' post type instead

        // Course post type is registered by CoursePostType.php - no need to duplicate here


        if (!taxonomy_exists('scn_topic')) {
            register_taxonomy('scn_topic', [], [ // Removed 'course' from object types
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
                // Member capabilities
                'edit_members',
                'edit_others_members',
                'publish_members',
                'read_private_members',
                'delete_members',
                'delete_private_members',
                'delete_published_members',
                'delete_others_members',
                'edit_private_members',
                'edit_published_members',
                
                // Course capabilities
                'edit_courses',
                'edit_others_courses',
                'publish_courses',
                'read_private_courses',
                'delete_courses',
                'delete_private_courses',
                'delete_published_courses',
                'delete_others_courses',
                'edit_private_courses',
                'edit_published_courses',
                
            ];

            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }

        // Add capabilities for subscribers (members)
        $subscriber_role = get_role('subscriber');
        if ($subscriber_role) {
            // Ensure subscribers can edit posts
            $subscriber_role->add_cap('edit_posts');
        }

        // Add capabilities for editors
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_capabilities = [
                'edit_members',
                'edit_others_members',
                'publish_members',
                'read_private_members',
                'delete_members',
                'delete_others_members',
                'delete_published_members',
                'edit_published_members',
                
                'edit_courses',
                'edit_others_courses',
                'publish_courses',
                'read_private_courses',
                'delete_courses',
                'delete_others_courses',
                'delete_published_courses',
                'edit_published_courses',
            ];

            foreach ($editor_capabilities as $cap) {
                $editor_role->add_cap($cap);
            }
        }

        // Add capabilities for authors
        $author_role = get_role('author');
        if ($author_role) {
            $author_capabilities = [
                'edit_members',
                'publish_members',
                'delete_members',
                'edit_published_members',
                
                'edit_courses',
                'publish_courses',
                'delete_courses',
                'edit_published_courses',
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

        // Don't interfere with ANY ACF post types
        $acf_post_types = ['acf-field-group', 'acf-field', 'acf-post-type', 'acf-taxonomy', 'acf-ui-options-page'];
        if (in_array($post->post_type, $acf_post_types, true)) {
            return;
        }

        $cpt_types = ['member', 'course'];
        
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
        
        // Don't interfere with ANY ACF screens
        $acf_post_types = ['acf-field-group', 'acf-field', 'acf-post-type', 'acf-taxonomy', 'acf-ui-options-page'];
        if ($screen && in_array($screen->post_type, $acf_post_types, true)) {
            return;
        }
        
        if (!$screen || !in_array($screen->post_type, ['member', 'course'], true)) {
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
        if (!$screen || !in_array($screen->post_type, ['member', 'course'], true)) {
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
        if (!in_array($post_type, ['member', 'course'])) {
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
            case 'member':
                $this->enqueueProfileScripts();
                break;
            case 'course':
                $this->enqueueCourseScripts();
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
            'nonce' => wp_create_nonce('members_admin'),
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
                        <a href="<?php echo admin_url('edit.php?post_type=member'); ?>" class="button button-primary">
                            <?php _e('View Profiles', 'scn-membership'); ?>
                        </a>
                    </div>
                    
                    <div class="scn-admin-card">
                        <h2><?php _e('Courses', 'scn-membership'); ?></h2>
                        <p><?php _e('Manage courses, CE hours, and learning outcomes.', 'scn-membership'); ?></p>
                        <div class="scn-admin-actions">
                            <a href="<?php echo admin_url('edit.php?post_type=course'); ?>" class="button button-primary">
                                <?php _e('View All Courses', 'scn-membership'); ?>
                            </a>
                            <a href="<?php echo admin_url('post-new.php?post_type=course'); ?>" class="button">
                                <?php _e('Add New Course', 'scn-membership'); ?>
                            </a>
                        </div>
                        
                        <?php
                        // Show recent courses
                        $recent_courses = get_posts([
                            'post_type' => 'course',
                            'posts_per_page' => 5,
                            'post_status' => 'publish'
                        ]);
                        
                        if (!empty($recent_courses)): ?>
                            <div class="scn-admin-recent">
                                <h4><?php _e('Recent Courses:', 'scn-membership'); ?></h4>
                                <ul>
                                    <?php foreach ($recent_courses as $course): ?>
                                        <li>
                                            <a href="<?php echo get_edit_post_link($course->ID); ?>">
                                                <?php echo esc_html($course->post_title); ?>
                                            </a>
                                            <span class="post-date"><?php echo get_the_date('M j, Y', $course->ID); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    
                    <div class="scn-admin-card">
                        <h2><?php _e('Topics', 'scn-membership'); ?></h2>
                        <p><?php _e('Manage topic categories for courses.', 'scn-membership'); ?></p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=scn_topic&post_type=course'); ?>" class="button button-primary">
                            <?php _e('Manage Topics', 'scn-membership'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="scn-admin-info">
                    <h3><?php _e('Quick Stats', 'scn-membership'); ?></h3>
                    <p>
                        <?php
                        $profiles_count = wp_count_posts('member');
                        $courses_count = wp_count_posts('course');
                        $topics_count = wp_count_terms('scn_topic');
                        
                        $profiles_published = isset($profiles_count->publish) ? $profiles_count->publish : 0;
                        $courses_published = isset($courses_count->publish) ? $courses_count->publish : 0;
                        $topics_count = is_wp_error($topics_count) ? 0 : $topics_count;
                        
                        printf(
                            __('%d Profiles, %d Courses, %d Topics', 'scn-membership'),
                            $profiles_published,
                            $courses_published,
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
        .scn-admin-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .scn-admin-recent {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .scn-admin-recent h4 {
            margin: 0 0 10px 0;
            color: #23282d;
            font-size: 14px;
        }
        .scn-admin-recent ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .scn-admin-recent li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        .scn-admin-recent li:last-child {
            border-bottom: none;
        }
        .scn-admin-recent li a {
            color: #0073aa;
            text-decoration: none;
        }
        .scn-admin-recent li a:hover {
            color: #005177;
        }
        .scn-admin-recent .post-date {
            color: #666;
            font-size: 12px;
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
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'membership_settings-options')) {
            $this->handleSettingsSave();
        }

        // Handle reset to defaults
        if (isset($_POST['reset']) && wp_verify_nonce($_POST['_wpnonce'], 'membership_settings-options')) {
            $this->handleSettingsReset();
        }

        $this->renderSettingsPage();
    }

    private function handleSettingsSave() {
        $settings = $this->settings_service->getSettings();
        $updated_settings = $this->settings_service->sanitizeSettings($_POST['membership_options'] ?? []);
        
        update_option('membership_options', $updated_settings);
        
        add_settings_error(
            'membership_settings',
            'settings_saved',
            __('Settings saved successfully!', 'scn-membership'),
            'updated'
        );
    }

    private function handleSettingsReset() {
        $default_settings = $this->settings_service->getDefaultSettings();
        update_option('membership_options', $default_settings);
        
        add_settings_error(
            'membership_settings',
            'settings_reset',
            __('Settings reset to defaults!', 'scn-membership'),
            'updated'
        );
    }

    private function renderSettingsPage() {
        ?>
        <div class="wrap scn-settings-page">
            <h1><?php _e('SCN Membership Settings', 'scn-membership'); ?></h1>
            
            <?php settings_errors('membership_settings'); ?>
            
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
                <?php wp_nonce_field('membership_settings-options'); ?>
                
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
                    <input type="text" name="membership_options[site_name]" value="<?php echo esc_attr($settings['site_name']); ?>" class="regular-text" placeholder="<?php esc_attr_e('SCN Membership', 'scn-membership'); ?>" />
                    <p class="description"><?php _e('The name of your SCN membership site.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Site Description', 'scn-membership'); ?></th>
                <td>
                    <textarea name="membership_options[site_description]" rows="3" class="large-text"><?php echo esc_textarea($settings['site_description']); ?></textarea>
                    <p class="description"><?php _e('A brief description of your SCN membership site.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Contact Email', 'scn-membership'); ?></th>
                <td>
                    <input type="email" name="membership_options[contact_email]" value="<?php echo esc_attr($settings['contact_email']); ?>" class="regular-text" />
                    <p class="description"><?php _e('Email address for general inquiries.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Enable Member Registration', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="membership_options[enable_registration]" value="1" <?php checked($settings['enable_registration']); ?> />
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
                    <input type="number" name="membership_options[max_image_size]" value="<?php echo esc_attr($settings['max_image_size']); ?>" min="1" max="50" step="1" class="small-text" />
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
                                '<label><input type="checkbox" name="membership_options[allowed_image_types][]" value="%s" %s /> %s</label><br>',
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
                        <input type="checkbox" name="membership_options[auto_optimize_images]" value="1" <?php checked($settings['auto_optimize_images']); ?> />
                        <?php _e('Automatically optimize uploaded images for web.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Generate WebP Versions', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="membership_options[generate_webp]" value="1" <?php checked($settings['generate_webp']); ?> />
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
                        <input type="checkbox" name="membership_options[force_https]" value="1" <?php checked($settings['force_https']); ?> />
                        <?php _e('Automatically convert HTTP URLs to HTTPS.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Validate URLs', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="membership_options[validate_urls]" value="1" <?php checked($settings['validate_urls']); ?> />
                        <?php _e('Validate URLs before saving to ensure they are accessible.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Allowed Domains', 'scn-membership'); ?></th>
                <td>
                    <textarea name="membership_options[allowed_domains]" rows="3" class="large-text" placeholder="example.com, another-site.com"><?php echo esc_textarea($settings['allowed_domains']); ?></textarea>
                    <p class="description"><?php _e('Comma-separated list of allowed domains for external links.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Open External Links in New Tab', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="membership_options[open_external_new_tab]" value="1" <?php checked($settings['open_external_new_tab']); ?> />
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
                        <input type="checkbox" name="membership_options[auto_approve_sessions]" value="1" <?php checked($settings['auto_approve_sessions']); ?> />
                        <?php _e('Automatically approve sessions for current and future events.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Session Approval Email', 'scn-membership'); ?></th>
                <td>
                    <input type="email" name="membership_options[session_approval_email]" value="<?php echo esc_attr($settings['session_approval_email']); ?>" class="regular-text" />
                    <p class="description"><?php _e('Email address to receive session approval notifications.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Widget Cache Duration (minutes)', 'scn-membership'); ?></th>
                <td>
                    <input type="number" name="membership_options[widget_cache_duration]" value="<?php echo esc_attr($settings['widget_cache_duration']); ?>" min="5" max="1440" step="5" class="small-text" />
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
                    <input type="text" name="membership_options[default_widget_title]" value="<?php echo esc_attr($settings['default_widget_title']); ?>" class="regular-text" placeholder="<?php esc_attr_e('Where Our Members Are Speaking', 'scn-membership'); ?>" />
                    <p class="description"><?php _e('Default title for the "Where Our Members Are Speaking" widget.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Default Widget Limit', 'scn-membership'); ?></th>
                <td>
                    <input type="number" name="membership_options[default_widget_limit]" value="<?php echo esc_attr($settings['default_widget_limit']); ?>" min="1" max="20" step="1" class="small-text" />
                    <p class="description"><?php _e('Default number of sessions to show in the widget.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Event Dates by Default', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="membership_options[widget_show_dates]" value="1" <?php checked($settings['widget_show_dates']); ?> />
                        <?php _e('Show event dates in the widget by default.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Event Names by Default', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="membership_options[widget_show_events]" value="1" <?php checked($settings['widget_show_events']); ?> />
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
                        <input type="checkbox" name="membership_options[debug_mode]" value="1" <?php checked($settings['debug_mode']); ?> />
                        <?php _e('Enable debug mode for troubleshooting.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Log Errors', 'scn-membership'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="membership_options[log_errors]" value="1" <?php checked($settings['log_errors']); ?> />
                        <?php _e('Log errors to the WordPress error log.', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Custom CSS', 'scn-membership'); ?></th>
                <td>
                    <textarea name="membership_options[custom_css]" rows="10" class="large-text code"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                    <p class="description"><?php _e('Custom CSS to be added to the frontend.', 'scn-membership'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Custom JavaScript', 'scn-membership'); ?></th>
                <td>
                    <textarea name="membership_options[custom_js]" rows="10" class="large-text code"><?php echo esc_textarea($settings['custom_js']); ?></textarea>
                    <p class="description"><?php _e('Custom JavaScript to be added to the frontend.', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }


    /**
     * Create WordPress user after ACF saves member post
     * Only creates user if one doesn't already exist for this member
     */
    public function createUserForMemberAfterAcf($post_id) {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check if this is a revision
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Only process member posts
        if (get_post_type($post_id) !== 'member') {
            return;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
        
        // Debug logging
        error_log("SCN Membership: createUserForMemberAfterAcf called for post ID: {$post_id}");
        
        // Check if user already exists for this member - if yes, skip creation
        $existing_user_id = get_post_meta($post_id, 'scn_user_id', true);
        if ($existing_user_id) {
            error_log("SCN Membership: Skipping - user already exists: {$existing_user_id}");
            return; // User already exists, no need to create
        }
        
        // Get basic info from ACF fields (now guaranteed to be saved)
        $basic_info = get_field('basic_info', $post_id);
        error_log("SCN Membership: ACF basic_info data: " . print_r($basic_info, true));
        
        if (!$basic_info) {
            error_log("SCN Membership: No basic_info ACF field found");
            return; // No basic info available
        }
        
        $first_name = $basic_info['first_name'] ?? '';
        $last_name = $basic_info['last_name'] ?? '';
        
        // Email is a separate field, not inside basic_info
        $email = get_field('email', $post_id);
        if (empty($email)) {
            // Try alternate field names
            $email = get_field('member_email', $post_id);
        }
        
        error_log("SCN Membership: Extracted data - First: '{$first_name}', Last: '{$last_name}', Email: '{$email}'");
        
        if (empty($first_name) || empty($last_name) || empty($email)) {
            error_log("SCN Membership: Missing required fields - First: '{$first_name}', Last: '{$last_name}', Email: '{$email}'");
            return; // Need first name, last name, and email
        }
        
        // Check if email is valid
        if (!is_email($email)) {
            error_log("SCN Membership: Invalid email '{$email}' for member '{$post->post_title}'");
            return;
        }
        
        // Check if user with this email already exists
        if (email_exists($email)) {
            error_log("SCN Membership: User with email '{$email}' already exists for member '{$post->post_title}' - skipping user creation");
            return;
        }
        
        error_log("SCN Membership: Proceeding with user creation for member '{$post->post_title}'");
        
        // Generate username from first and last name
        $username = strtolower($first_name . '.' . $last_name);
        $username = sanitize_user($username);
        
        // Make sure username is unique
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Generate random password
        $password = wp_generate_password(12, false);
        
        // Create WordPress user
        error_log("SCN Membership: Creating user with username: '{$username}', email: '{$email}'");
        $user_id = wp_create_user($username, $password, $email);
        
        if (!is_wp_error($user_id)) {
            error_log("SCN Membership: User created successfully with ID: {$user_id}");
            
            // Update user meta
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
            
            // Associate user with member post
            update_post_meta($post_id, 'scn_user_id', $user_id);
            
            // Set user role to subscriber by default
            $user = new \WP_User($user_id);
            $user->set_role('subscriber');
            
            // Send password via email
            $this->sendPasswordEmail($user_id, $email, $first_name, $username, $password);
            
            // Log the creation
            error_log("SCN Membership: Created user ID {$user_id} for member '{$post->post_title}' (ID: {$post_id})");
        } else {
            error_log("SCN Membership: Failed to create user for member '{$post->post_title}': " . $user_id->get_error_message());
        }
    }

    /**
     * Send password email to new member
     */
    private function sendPasswordEmail($user_id, $email, $first_name, $username, $password) {
        $subject = __('Your SCN Membership Account Details', 'scn-membership');
        
        $login_url = home_url('/member-login/');
        
        $message = sprintf(
            __('Hello %s,

Welcome to SCN! Your membership account has been created successfully.

Your account details:
Username: %s
Password: %s
Login URL: %s

Please keep this information secure and consider changing your password after your first login.

If you have any questions, please contact us.

Best regards,
The SCN Team', 'scn-membership'),
            $first_name,
            $username,
            $password,
            $login_url
        );
        
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: SCN <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        ];
        
        // Try wp_mail first
        $result = wp_mail($email, $subject, $message, $headers);
        
        // If wp_mail fails, try alternative methods
        if (!$result) {
            // Log the failure
            error_log('SCN Email: Failed to send password email to user ' . $user_id);
            
            // Try direct mail function as fallback
            $result = $this->sendEmailFallback($email, $subject, $message);
        }
        
        if ($result) {
            error_log("SCN Membership: Password email sent to {$email} for user ID {$user_id}");
        } else {
            error_log("SCN Membership: Failed to send password email to {$email} for user ID {$user_id}");
        }
        
        return $result;
    }

    /**
     * Fallback email sending method
     */
    private function sendEmailFallback($email, $subject, $message) {
        // For development, you can log the email instead of sending
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("SCN Email Fallback - To: $email, Subject: $subject");
            error_log("SCN Email Content: $message");
            
            // In development, consider this a success
            return true;
        }
        
        // Try using PHP's mail function directly
        $headers = "From: SCN <noreply@" . parse_url(home_url(), PHP_URL_HOST) . ">\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($email, $subject, $message, $headers);
    }
}



