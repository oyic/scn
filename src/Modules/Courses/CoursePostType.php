<?php

namespace SCN\Membership\Modules\Courses;

class CoursePostType {
    public function register() {
        add_action('init', [$this, 'registerPostType']);
        
        // ACF handles meta fields now, so we only need columns and sorting
        add_filter('manage_course_posts_columns', [$this, 'addCustomColumns'], 999);
        add_action('manage_course_posts_custom_column', [$this, 'displayCustomColumns'], 10, 2);
        add_filter('manage_edit-course_sortable_columns', [$this, 'makeSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleCustomSorting']);
        
        // Ensure taxonomy is connected
        add_action('init', [$this, 'ensureTaxonomyConnection'], 25);
        
        // Add admin styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
    }

    public function registerPostType() {
        register_post_type('course', [
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
                'featured_image' => __('Course Image', 'scn-membership'),
                'set_featured_image' => __('Set course image', 'scn-membership'),
                'remove_featured_image' => __('Remove course image', 'scn-membership'),
                'use_featured_image' => __('Use as course image', 'scn-membership'),
            ],
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'courses'],
            'supports' => ['title', 'editor', 'excerpt', 'custom-fields'],
            // 'taxonomies' => ['scn_topic'], // Removed - using ACF field instead
            'capability_type' => 'course',
            'map_meta_cap' => true,
            'show_in_rest' => true,
            'show_in_menu' => false, // We'll add it to our custom menu
        ]);
        
        // Flush rewrite rules if this is a new post type
        if (!get_option('course_rewrite_rules_flushed')) {
            flush_rewrite_rules();
            update_option('course_rewrite_rules_flushed', true);
        }
        
        // Remove any taxonomy metaboxes that might still be registered
        add_action('add_meta_boxes', [$this, 'removeTaxonomyMetaboxes'], 999);
    }

    public function ensureTaxonomyConnection() {
        // Taxonomy connection removed - using ACF field instead of native taxonomy
        // The scn_topic taxonomy still exists for other purposes, but not connected to course post type
    }

    // Meta fields and meta boxes are now handled by ACF
    
    /**
     * Remove taxonomy metaboxes for course post type
     */
    public function removeTaxonomyMetaboxes() {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'course') {
            // Remove all possible taxonomy metabox IDs
            remove_meta_box('scn_topicdiv', 'course', 'side');
            remove_meta_box('scn_topicdiv', 'course', 'normal');
            remove_meta_box('scn_topicdiv', 'course', 'advanced');
            remove_meta_box('tagsdiv-scn_topic', 'course', 'side');
            remove_meta_box('tagsdiv-scn_topic', 'course', 'normal');
            remove_meta_box('tagsdiv-scn_topic', 'course', 'advanced');
            remove_meta_box('scn_topic', 'course', 'side');
            remove_meta_box('scn_topic', 'course', 'normal');
            remove_meta_box('scn_topic', 'course', 'advanced');
        }
    }

    public function addCustomColumns($columns) {
        // Remove default featured image column to avoid duplication
        unset($columns['featured_image']);
        
        $new_columns = [];
        
        // Add Course Image column after title
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['course_image'] = __('Course Image', 'scn-membership');
            }
        }
        
        // Add Topics column
        $new_columns['course_topics'] = __('Topics', 'scn-membership');
        
        return $new_columns;
    }

    public function displayCustomColumns($column, $post_id) {
        // Prevent duplicate output by checking if we've already processed this column
        static $processed = [];
        $key = $post_id . '_' . $column;
        
        if (isset($processed[$key])) {
            return;
        }
        $processed[$key] = true;
        
        switch ($column) {
            case 'course_image':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [50, 50]);
                } else {
                    echo '<span class="dashicons dashicons-format-video"></span>';
                }
                break;
                
            case 'course_topics':
                // Get topics from ACF field
                $topic_ids = get_field('scn_course_topics', $post_id);
                if (!empty($topic_ids)) {
                    $topic_links = [];
                    foreach ($topic_ids as $topic_id) {
                        $term = get_term($topic_id, 'scn_topic');
                        if ($term && !is_wp_error($term)) {
                            $edit_link = admin_url('edit-tags.php?taxonomy=scn_topic&post_type=course&tag_ID=' . $topic_id);
                            $topic_links[] = '<a href="' . esc_url($edit_link) . '">' . esc_html($term->name) . '</a>';
                        }
                    }
                    echo implode(', ', $topic_links);
                } else {
                    echo '—';
                }
                break;
        }
    }

    public function makeSortableColumns($columns) {
        // No sortable columns for now
        return $columns;
    }

    public function handleCustomSorting($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        switch ($orderby) {
            case 'course_subtitle':
                $query->set('meta_key', 'course_subtitle');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'course_ce_enabled':
                $query->set('meta_key', 'course_ce_enabled');
                $query->set('orderby', 'meta_value');
                break;
        }
    }

    public function enqueueAdminStyles($hook) {
        global $post_type;
        
        // Only load on course edit pages
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            if ($post_type === 'course') {
                ?>
                <style>
                /* Ensure proper sidebar layout for courses */
                #postimagediv {
                    margin-bottom: 20px;
                }
                
                #course_additional_info {
                    margin-bottom: 20px;
                }
                
                /* Style the course information metabox */
                #course_additional_info .form-table th {
                    width: 30%;
                    padding: 10px 10px 10px 0;
                }
                
                #course_additional_info .form-table td {
                    padding: 10px 0;
                }
                
                /* Make topics hierarchical in the sidebar */
                #scn_topicdiv .categorydiv {
                    max-height: 200px;
                    overflow-y: auto;
                }
                
                /* Style the hierarchical topic checkboxes */
                #scn_topicdiv .categorydiv ul {
                    margin: 0;
                    padding: 0;
                }
                
                #scn_topicdiv .categorydiv li {
                    list-style: none;
                    margin: 0;
                    padding: 0;
                }
                
                #scn_topicdiv .categorydiv li label {
                    display: block;
                    padding: 2px 0;
                    font-weight: normal;
                }
                
                #scn_topicdiv .categorydiv li ul {
                    margin-left: 20px;
                }
                
                /* Ensure publish box is at the top of sidebar */
                #submitdiv {
                    margin-top: 0;
                }
                </style>
                <?php
            }
        }
    }
}