<?php

namespace SCN\Membership\Modules\Courses;

class CoursePostType {
    public function register() {
        add_action('init', [$this, 'registerPostType']);
        
        // ACF handles meta fields now, so we only need columns and sorting
        add_filter('manage_scn_course_posts_columns', [$this, 'addCustomColumns'], 20);
        add_action('manage_scn_course_posts_custom_column', [$this, 'displayCustomColumns'], 10, 2);
        add_filter('manage_edit-scn_course_sortable_columns', [$this, 'makeSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleCustomSorting']);
        
        // Ensure taxonomy is connected
        add_action('init', [$this, 'ensureTaxonomyConnection'], 25);
        
        // Add admin styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
    }

    public function registerPostType() {
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
            'supports' => ['title', 'editor', 'author', 'excerpt', 'custom-fields'],
            'taxonomies' => ['scn_topic'],
            'capability_type' => 'scn_course',
            'map_meta_cap' => true,
            'show_in_rest' => true,
            'show_in_menu' => false, // We'll add it to our custom menu
        ]);
        
        // Flush rewrite rules if this is a new post type
        if (!get_option('scn_course_rewrite_rules_flushed')) {
            flush_rewrite_rules();
            update_option('scn_course_rewrite_rules_flushed', true);
        }
    }

    public function ensureTaxonomyConnection() {
        // Ensure the taxonomy is properly connected to the course post type
        if (taxonomy_exists('scn_topic') && post_type_exists('scn_course')) {
            register_taxonomy_for_object_type('scn_topic', 'scn_course');
            
            // Flush rewrite rules to ensure the connection is registered
            if (!get_option('scn_topic_course_connection_flushed')) {
                flush_rewrite_rules();
                update_option('scn_topic_course_connection_flushed', true);
            }
        }
    }

    // Meta fields and meta boxes are now handled by ACF

    public function addCustomColumns($columns) {
        $new_columns = [];
        
        // Add thumbnail column after title
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['thumbnail'] = __('Image', 'scn-membership');
            }
        }
        
        // Add custom columns
        $new_columns['scn_course_subtitle'] = __('Subtitle', 'scn-membership');
        $new_columns['scn_course_ce'] = __('CE Credits', 'scn-membership');
        $new_columns['scn_course_topics'] = __('Topics', 'scn-membership');
        
        return $new_columns;
    }

    public function displayCustomColumns($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [50, 50]);
                } else {
                    echo '<span class="dashicons dashicons-format-video"></span>';
                }
                break;
                
            case 'scn_course_subtitle':
                $subtitle = get_post_meta($post_id, 'scn_course_subtitle', true);
                echo $subtitle ? esc_html($subtitle) : '—';
                break;
                
            case 'scn_course_ce':
                $ce_enabled = get_post_meta($post_id, 'scn_course_ce_enabled', true);
                $ce_hours = get_post_meta($post_id, 'scn_course_ce_hours', true);
                if ($ce_enabled) {
                    echo $ce_hours ? $ce_hours . ' hours' : 'Yes';
                } else {
                    echo 'No';
                }
                break;
                
            case 'scn_course_topics':
                $topics = wp_get_post_terms($post_id, 'scn_topic', ['fields' => 'names']);
                if (!empty($topics) && !is_wp_error($topics)) {
                    echo implode(', ', $topics);
                } else {
                    echo '—';
                }
                break;
        }
    }

    public function makeSortableColumns($columns) {
        $columns['scn_course_subtitle'] = 'scn_course_subtitle';
        $columns['scn_course_ce'] = 'scn_course_ce_enabled';
        return $columns;
    }

    public function handleCustomSorting($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        switch ($orderby) {
            case 'scn_course_subtitle':
                $query->set('meta_key', 'scn_course_subtitle');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'scn_course_ce_enabled':
                $query->set('meta_key', 'scn_course_ce_enabled');
                $query->set('orderby', 'meta_value');
                break;
        }
    }

    public function enqueueAdminStyles($hook) {
        global $post_type;
        
        // Only load on course edit pages
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            if ($post_type === 'scn_course') {
                ?>
                <style>
                /* Ensure proper sidebar layout for courses */
                #postimagediv {
                    margin-bottom: 20px;
                }
                
                #scn_course_additional_info {
                    margin-bottom: 20px;
                }
                
                /* Style the course information metabox */
                #scn_course_additional_info .form-table th {
                    width: 30%;
                    padding: 10px 10px 10px 0;
                }
                
                #scn_course_additional_info .form-table td {
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