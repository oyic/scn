<?php

namespace SCN\Membership\Modules\Profiles;

class ProfilePostType {
    private static $registered = false;
    
    public function register() {
        // Prevent duplicate registration
        if (self::$registered) {
            return;
        }
        self::$registered = true;
        
        add_action('init', [$this, 'registerPostType']);
        
        // ACF handles meta fields now, so we only need columns and sorting
        // Use higher priority (999) to ensure our filter runs LAST and removes all duplicate columns
        add_filter('manage_member_posts_columns', [$this, 'addCustomColumns'], 999);
        add_action('manage_member_posts_custom_column', [$this, 'displayCustomColumns'], 10, 2);
        add_filter('manage_edit-member_sortable_columns', [$this, 'makeSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleCustomSorting']);
        
        // Move publish box to sidebar for profiles
        add_action('add_meta_boxes', [$this, 'movePublishBoxToSidebar'], 999);
        
        // Add admin styles to hide duplicate image columns
        add_action('admin_head', [$this, 'hideDefaultImageColumns']);
    }
    
    public function hideDefaultImageColumns() {
        global $pagenow, $post_type;
        
        if ($pagenow === 'edit.php' && $post_type === 'member') {
            echo '<style>
                /* Hide any duplicate image columns that might be added by other plugins/themes */
                .column-featured_image:not(.column-profile_image),
                .column-thumbnail,
                .column-image,
                .column-post-thumbnail {
                    display: none !important;
                }
            </style>';
        }
    }

    public function registerPostType() {
        // Member post type registration is handled elsewhere
    }

    // Meta fields are now handled by ACF field groups

    // Meta boxes are now handled by ACF field groups

    public function movePublishBoxToSidebar() {
        global $post_type, $pagenow;
        
        // Only apply to member edit pages
        if ($post_type === 'member' && ($pagenow === 'post.php' || $pagenow === 'post-new.php')) {
            // Remove the default publish box from normal position
            remove_meta_box('submitdiv', 'member', 'normal');
            
            // Add it back to the sidebar
            add_meta_box(
                'submitdiv',
                __('Publish', 'scn-membership'),
                'post_submit_meta_box',
                'member',
                'side',
                'high'
            );
        }
    }

    // Meta box render functions removed - now handled by ACF

    // All meta box render functions and save functions removed - now handled by ACF

    public function addCustomColumns($columns) {
        // Remove ALL possible duplicate image columns
        unset($columns['featured_image']);
        unset($columns['thumbnail']);
        unset($columns['image']);
        unset($columns['post-thumbnail']);
        
        // Insert our custom columns after title
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['profile_image'] = __('Profile Image', 'scn-membership');
                $new_columns['member_topics'] = __('Topics', 'scn-membership');
                $new_columns['member_courses'] = __('Courses', 'scn-membership');
            }
        }
        
        return $new_columns;
    }

    public function displayCustomColumns($column, $post_id) {
        switch ($column) {
            case 'profile_image':
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
                
            case 'member_topics':
                // Get topics from scn_topic taxonomy
                $topics = get_the_terms($post_id, 'scn_topic');
                
                if (!empty($topics) && !is_wp_error($topics)) {
                    $topic_links = [];
                    foreach (array_slice($topics, 0, 3) as $topic) {
                        $topic_links[] = sprintf(
                            '<a href="%s" style="color: #0073aa; text-decoration: none;">%s</a>',
                            esc_url(admin_url('edit.php?post_type=member&scn_topic=' . $topic->slug)),
                            esc_html($topic->name)
                        );
                    }
                    echo implode(', ', $topic_links);
                    
                    if (count($topics) > 3) {
                        echo ' <span style="color: #999;">+' . (count($topics) - 3) . '</span>';
                    }
                } else {
                    echo '<span style="color: #999; font-style: italic;">—</span>';
                }
                break;
                
            case 'member_courses':
                // Get courses from ACF relationship field
                $courses = get_field('member_courses', $post_id);
                
                $count = is_array($courses) ? count($courses) : 0;
                if ($count > 0) {
                    echo '<strong>' . $count . '</strong>';
                } else {
                    echo '<span style="color: #999;">0</span>';
                }
                break;
        }
    }

    public function makeSortableColumns($columns) {
        // Make columns sortable
        $columns['profile_image'] = '_thumbnail_id';
        $columns['member_courses'] = 'courses_count';
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
        
        if ('courses_count' === $orderby) {
            // Sort by number of courses (custom query needed)
            $query->set('orderby', 'meta_value_num');
            $query->set('meta_key', '_courses_count');
        }
    }
}