<?php

namespace SCN\Membership\Modules\Profiles;

class ProfilesModule {
    private $post_type;
    private $image_processor;
    private $video_processor;
    private $admin_interface;
    private $frontend_templates;

    public function register() {
        $this->post_type = new ProfilePostType();
        $this->image_processor = new ImageProcessor();
        $this->video_processor = new VideoProcessor();
        $this->admin_interface = new AdminInterface();
        $this->frontend_templates = new FrontendTemplates();

        $this->post_type->register();
        $this->image_processor->register();
        $this->video_processor->register();
        $this->admin_interface->register();
        $this->frontend_templates->register();

        add_action('init', [$this, 'init']);
        add_action('init', [$this, 'registerTaxonomies']);
        add_action('init', [$this, 'addCapabilities']);
    }

    public function init() {
        // Initialize profiles module
        $this->registerHooks();
    }

    public function registerTaxonomies() {
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
            'show_admin_column' => true,
        ]);
    }

    public function addCapabilities() {
        $role = get_role('administrator');
        if ($role) {
            $capabilities = [
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
            ];

            foreach ($capabilities as $cap) {
                $role->add_cap($cap);
            }
        }

        // Add capabilities to editors
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
            ];

            foreach ($editor_capabilities as $cap) {
                $editor_role->add_cap($cap);
            }
        }

        // Add capabilities to authors
        $author_role = get_role('author');
        if ($author_role) {
            $author_capabilities = [
                'edit_scn_profiles',
                'publish_scn_profiles',
                'delete_scn_profiles',
                'edit_published_scn_profiles',
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
