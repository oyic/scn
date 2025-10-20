<?php

namespace SCN\Membership\Modules\Profiles;

class ProfilesModule {
    private $post_type;
    private $image_processor;
    private $video_processor;
    private $admin_interface;
    private $frontend_templates;
    private $dashboard;

    public function register() {
        $this->post_type = new ProfilePostType();
        $this->image_processor = new ImageProcessor();
        $this->video_processor = new VideoProcessor();
        $this->admin_interface = new AdminInterface();
        $this->frontend_templates = new FrontendTemplates();
        $this->dashboard = new ProfileDashboard();

        // Register components immediately
        $this->post_type->register();
        $this->image_processor->register();
        $this->video_processor->register();
        $this->admin_interface->register();
        $this->frontend_templates->register();
        $this->dashboard->register();

        // Register hooks
        add_action('init', [$this, 'init']);
        add_action('init', [$this, 'registerTaxonomies'], 20);
        add_action('init', [$this, 'addCapabilities']);
        add_action('init', [$this, 'connectTaxonomyToPostType'], 30);
    }

    public function init() {
        // Initialize profiles module
        $this->registerHooks();
    }

    public function registerTaxonomies() {
        \register_taxonomy('scn_topic', [], [ // Removed 'course' from object types
            'labels' => [
                'name' => __('Course Topics', 'scn-membership'),
                'singular_name' => __('Course Topic', 'scn-membership'),
                'search_items' => __('Search Course Topics', 'scn-membership'),
                'all_items' => __('All Course Topics', 'scn-membership'),
                'parent_item' => __('Parent Course Topic', 'scn-membership'),
                'parent_item_colon' => __('Parent Course Topic:', 'scn-membership'),
                'edit_item' => __('Edit Course Topic', 'scn-membership'),
                'update_item' => __('Update Course Topic', 'scn-membership'),
                'add_new_item' => __('Add New Course Topic', 'scn-membership'),
                'new_item_name' => __('New Course Topic Name', 'scn-membership'),
                'menu_name' => __('Course Topics', 'scn-membership'),
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
        ]);
        
        // Flush rewrite rules if this is a new taxonomy
        if (!get_option('scn_topic_rewrite_rules_flushed')) {
            flush_rewrite_rules();
            update_option('scn_topic_rewrite_rules_flushed', true);
        }
    }

    public function connectTaxonomyToPostType() {
        // Ensure the taxonomy is properly connected to the post type
        // Taxonomy connection removed - using ACF field instead of native taxonomy
        // if (taxonomy_exists('scn_topic') && post_type_exists('course')) {
        //     register_taxonomy_for_object_type('scn_topic', 'course');
        // }
    }

    public function addCapabilities() {
        $role = \get_role('administrator');
        if ($role) {
            $capabilities = [
                'edit_profiles',
                'edit_others_profiles',
                'publish_profiles',
                'read_private_profiles',
                'delete_profiles',
                'delete_private_profiles',
                'delete_published_profiles',
                'delete_others_profiles',
                'edit_private_profiles',
                'edit_published_profiles',
            ];

            foreach ($capabilities as $cap) {
                $role->add_cap($cap);
            }
        }

        // Add capabilities to editors
        $editor_role = \get_role('editor');
        if ($editor_role) {
            $editor_capabilities = [
                'edit_profiles',
                'edit_others_profiles',
                'publish_profiles',
                'read_private_profiles',
                'delete_profiles',
                'delete_others_profiles',
                'delete_published_profiles',
                'edit_published_profiles',
            ];

            foreach ($editor_capabilities as $cap) {
                $editor_role->add_cap($cap);
            }
        }

        // Add capabilities to authors
        $author_role = \get_role('author');
        if ($author_role) {
            $author_capabilities = [
                'edit_profiles',
                'publish_profiles',
                'delete_profiles',
                'edit_published_profiles',
            ];

            foreach ($author_capabilities as $cap) {
                $author_role->add_cap($cap);
            }
        }
    }

    public function registerHooks() {
        // Add custom hooks and filters
        add_filter('scn/profile/gallery_alt_text', [$this, 'filterGalleryAltText'], 10, 3);
        add_filter('scn/profile/featured_video_thumbnail', [$this, 'filterVideoThumbnail'], 10, 2);
        add_filter('scn/profile/badges_list', [$this, 'filterBadgesList'], 10, 2);
    }

    public function filterGalleryAltText($alt_text, $attachment_id, $post_id) {
        // Allow customization of gallery alt text
        return $alt_text;
    }

    public function filterVideoThumbnail($thumbnail_url, $post_id) {
        // Allow customization of video thumbnail
        return $thumbnail_url;
    }

    public function filterBadgesList($badges, $post_id) {
        // Allow customization of badges list
        return $badges;
    }
}
