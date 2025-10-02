<?php

namespace SCN\Membership\Modules\Profiles;

class ProfilePostType {
    public function register() {
        add_action('init', [$this, 'registerPostType']);
        
        // ACF handles meta fields now, so we only need columns and sorting
        add_filter('manage_scn_profile_posts_columns', [$this, 'addCustomColumns'], 20);
        add_action('manage_scn_profile_posts_custom_column', [$this, 'displayCustomColumns'], 10, 2);
        add_filter('manage_edit-scn_profile_sortable_columns', [$this, 'makeSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleCustomSorting']);
        
        // Move publish box to sidebar for profiles
        add_action('add_meta_boxes', [$this, 'movePublishBoxToSidebar'], 999);
    }

    public function registerPostType() {
        // Only register if not already registered by AdminService
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
                'supports' => ['title', 'editor', 'thumbnail', 'author', 'excerpt', 'custom-fields'],
                'capability_type' => 'scn_profile',
                'map_meta_cap' => true,
                'show_in_rest' => true,
                'show_in_menu' => false, // We'll add it to our custom menu
            ]);
        }
    }

    // Meta fields are now handled by ACF field groups

    // Meta boxes are now handled by ACF field groups

    public function movePublishBoxToSidebar() {
        global $post_type, $pagenow;
        
        // Only apply to profile edit pages
        if ($post_type === 'scn_profile' && ($pagenow === 'post.php' || $pagenow === 'post-new.php')) {
            // Remove the default publish box from normal position
            remove_meta_box('submitdiv', 'scn_profile', 'normal');
            
            // Add it back to the sidebar
            add_meta_box(
                'submitdiv',
                __('Publish', 'scn-membership'),
                'post_submit_meta_box',
                'scn_profile',
                'side',
                'high'
            );
        }
    }

    // Meta box render functions removed - now handled by ACF

    // All meta box render functions and save functions removed - now handled by ACF

    public function addCustomColumns($columns) {
        // Remove the default featured image column to avoid duplication
        unset($columns['featured_image']);
        
        // Also remove any other image-related columns that might exist
        unset($columns['thumbnail']);
        
        // Insert our custom image column after title
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['featured_image'] = __('Image', 'scn-membership');
            }
        }
        
        return $new_columns;
    }

    public function displayCustomColumns($column, $post_id) {
        switch ($column) {
            case 'featured_image':
                $image_id = get_post_thumbnail_id($post_id);
                if ($image_id) {
                    $image = wp_get_attachment_image($image_id, [50, 50], false, [
                        'style' => 'max-width: 50px; height: auto; border-radius: 4px;'
                    ]);
                    echo $image;
                } else {
                    echo '<span style="color: #999; font-style: italic;">No image</span>';
                }
                break;
        }
    }

    public function makeSortableColumns($columns) {
        // Make featured image column sortable by thumbnail ID
        $columns['featured_image'] = '_thumbnail_id';
        return $columns;
    }

    public function handleCustomSorting($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');
        
        if ('_thumbnail_id' === $orderby) {
            $query->set('meta_key', '_thumbnail_id');
            $query->set('orderby', 'meta_value_num');
        }
    }
}