<?php

namespace SCN\Membership\Modules\Events;

class EventPostType {
    public function register() {
        add_action('init', [$this, 'registerPostType']);
        
        // ACF handles meta fields now, so we only need columns and sorting
        add_filter('manage_scn_event_posts_columns', [$this, 'addCustomColumns'], 20);
        add_action('manage_scn_event_posts_custom_column', [$this, 'displayCustomColumns'], 10, 2);
        add_filter('manage_edit-scn_event_sortable_columns', [$this, 'makeSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleCustomSorting']);
    }

    public function registerPostType() {
        // Only register if not already registered by AdminService
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
                'show_in_menu' => false, // We'll add it to our custom menu
                'supports' => ['thumbnail'],
                'capability_type' => 'scn_event',
                'map_meta_cap' => true,
                'show_in_rest' => true,
                'rest_base' => 'events',
            ]);
        }
    }

    // Meta fields are now handled by ACF field groups

    // All meta box functions removed - now handled by ACF

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
        // Prevent duplicate output with static flag
        static $displayed = [];
        $key = $post_id . '_' . $column;
        
        if (isset($displayed[$key])) {
            return;
        }
        
        $displayed[$key] = true;
        
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
