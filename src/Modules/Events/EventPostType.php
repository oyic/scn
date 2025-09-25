<?php

namespace SCN\Membership\Modules\Events;

class EventPostType {
    public function register() {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerMetaFields']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        // Save method is now handled by bootstrap to prevent conflicts
        // add_action('save_post_scn_event', [$this, 'saveMetaFields']);
        
        // Add custom columns to events list table
        add_filter('manage_scn_event_posts_columns', [$this, 'addCustomColumns'], 20);
        add_action('manage_scn_event_posts_custom_column', [$this, 'displayCustomColumns'], 10, 2);
        add_filter('manage_edit-scn_event_sortable_columns', [$this, 'makeSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleCustomSorting']);
        
        // Scripts are now handled by AdminService to prevent conflicts
        // add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
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

    public function registerMetaFields() {
        $meta_fields = [
            'scn_event_official_name',
            'scn_event_location_city',
            'scn_event_location_region',
            'scn_event_location_country',
            'scn_event_dates',
            'scn_event_website',
            'scn_event_year',
            'scn_event_locked_fields',
        ];

        foreach ($meta_fields as $field) {
            register_meta('post', $field, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => [$this, 'sanitizeMetaField'],
            ]);
        }

        register_meta('post', 'scn_event_dates', [
            'type' => 'object',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [$this, 'sanitizeDates'],
        ]);

        register_meta('post', 'scn_event_locked_fields', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [$this, 'sanitizeLockedFields'],
        ]);

        register_meta('post', 'scn_event_name', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }

    public function addMetaBoxes() {
        add_meta_box(
            'scn_event_basic_info',
            __('Event Information', 'scn-membership'),
            [$this, 'renderBasicInfoMetaBox'],
            'scn_event',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_event_location',
            __('Location', 'scn-membership'),
            [$this, 'renderLocationMetaBox'],
            'scn_event',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_event_dates',
            __('Event Dates', 'scn-membership'),
            [$this, 'renderDatesMetaBox'],
            'scn_event',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_event_website',
            __('Event Website', 'scn-membership'),
            [$this, 'renderWebsiteMetaBox'],
            'scn_event',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_event_year',
            __('Event Year', 'scn-membership'),
            [$this, 'renderYearMetaBox'],
            'scn_event',
            'normal',
            'high'
        );
    }

    public function renderLocationMetaBox($post) {
        wp_nonce_field('scn_event_meta', 'scn_event_meta_nonce');
        
        $city = get_post_meta($post->ID, 'scn_event_location_city', true);
        $region = get_post_meta($post->ID, 'scn_event_location_region', true);
        $country = get_post_meta($post->ID, 'scn_event_location_country', true);
        $locked_fields = get_post_meta($post->ID, 'scn_event_locked_fields', true) ?: [];

        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_event_location_city"><?php _e('City', 'scn-membership'); ?></label></th>
                <td>
                    <input type="text" 
                           id="scn_event_location_city" 
                           name="scn_event_location_city" 
                           value="<?php echo esc_attr($city); ?>" 
                           class="regular-text" 
                           <?php disabled(in_array('location_city', $locked_fields)); ?> />
                    <?php if (in_array('location_city', $locked_fields)): ?>
                        <p class="description"><?php _e('This field is locked.', 'scn-membership'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="scn_event_location_region"><?php _e('Region/State', 'scn-membership'); ?></label></th>
                <td>
                    <input type="text" 
                           id="scn_event_location_region" 
                           name="scn_event_location_region" 
                           value="<?php echo esc_attr($region); ?>" 
                           class="regular-text" 
                           <?php disabled(in_array('location_region', $locked_fields)); ?> />
                    <?php if (in_array('location_region', $locked_fields)): ?>
                        <p class="description"><?php _e('This field is locked.', 'scn-membership'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="scn_event_location_country"><?php _e('Country', 'scn-membership'); ?></label></th>
                <td>
                    <input type="text" 
                           id="scn_event_location_country" 
                           name="scn_event_location_country" 
                           value="<?php echo esc_attr($country); ?>" 
                           class="regular-text" 
                           <?php disabled(in_array('location_country', $locked_fields)); ?> />
                    <?php if (in_array('location_country', $locked_fields)): ?>
                        <p class="description"><?php _e('This field is locked.', 'scn-membership'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    public function renderBasicInfoMetaBox($post) {
        wp_nonce_field('scn_event_meta', 'scn_event_meta_nonce');
        
        $event_name = get_post_meta($post->ID, 'scn_event_name', true);
        $locked_fields = get_post_meta($post->ID, 'scn_event_locked_fields', true) ?: [];

        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_event_name"><?php _e('Event Name', 'scn-membership'); ?></label></th>
                <td>
                    <input type="text" 
                           id="scn_event_name" 
                           name="scn_event_name" 
                           value="<?php echo esc_attr($event_name); ?>" 
                           class="regular-text"
                           <?php disabled(in_array('official_name', $locked_fields)); ?> />
                    <?php if (in_array('official_name', $locked_fields)): ?>
                        <p class="description"><?php _e('This field is locked.', 'scn-membership'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    public function renderDatesMetaBox($post) {
        $dates = get_post_meta($post->ID, 'scn_event_dates', true) ?: ['start' => '', 'end' => ''];
        $locked_fields = get_post_meta($post->ID, 'scn_event_locked_fields', true) ?: [];

        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_event_start_date"><?php _e('Start Date', 'scn-membership'); ?></label></th>
                <td>
                    <input type="date" 
                           id="scn_event_start_date" 
                           name="scn_event_start_date" 
                           value="<?php echo esc_attr($dates['start']); ?>" 
                           required 
                           <?php disabled(in_array('dates', $locked_fields)); ?> />
                    <?php if (in_array('dates', $locked_fields)): ?>
                        <p class="description"><?php _e('This field is locked.', 'scn-membership'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="scn_event_end_date"><?php _e('End Date', 'scn-membership'); ?></label></th>
                <td>
                    <input type="date" 
                           id="scn_event_end_date" 
                           name="scn_event_end_date" 
                           value="<?php echo esc_attr($dates['end']); ?>" 
                           <?php disabled(in_array('dates', $locked_fields)); ?> />
                    <p class="description"><?php _e('Optional. Leave blank for single-day events.', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function renderWebsiteMetaBox($post) {
        $website = get_post_meta($post->ID, 'scn_event_website', true);
        $locked_fields = get_post_meta($post->ID, 'scn_event_locked_fields', true) ?: [];

        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_event_website"><?php _e('Website URL', 'scn-membership'); ?></label></th>
                <td>
                    <input type="url" 
                           id="scn_event_website" 
                           name="scn_event_website" 
                           value="<?php echo esc_attr($website); ?>" 
                           class="regular-text" 
                           placeholder="https://example.com"
                           <?php disabled(in_array('website', $locked_fields)); ?> />
                    <?php if (in_array('website', $locked_fields)): ?>
                        <p class="description"><?php _e('This field is locked.', 'scn-membership'); ?></p>
                    <?php endif; ?>
                    <p class="description"><?php _e('Must be a valid HTTPS URL.', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function renderYearMetaBox($post) {
        $year = get_post_meta($post->ID, 'scn_event_year', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_event_year"><?php _e('Event Year', 'scn-membership'); ?></label></th>
                <td>
                    <input type="number" 
                           id="scn_event_year" 
                           name="scn_event_year" 
                           value="<?php echo esc_attr($year); ?>" 
                           class="small-text" 
                           readonly />
                    <p class="description"><?php _e('Auto-generated from start date.', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function saveMetaFields($post_id) {
        if (!isset($_POST['scn_event_meta_nonce']) || !wp_verify_nonce($_POST['scn_event_meta_nonce'], 'scn_event_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== 'scn_event') {
            return;
        }

        $locked_fields = get_post_meta($post_id, 'scn_event_locked_fields', true) ?: [];

        // Save event name
        if (isset($_POST['scn_event_name'])) {
            update_post_meta($post_id, 'scn_event_name', sanitize_text_field($_POST['scn_event_name']));
        }

        if (!in_array('official_name', $locked_fields)) {
            // Get official name from event name field instead of post_title
            $official_name = '';
            if (isset($_POST['scn_event_name'])) {
                $official_name = sanitize_text_field($_POST['scn_event_name']);
            } else {
                // Fallback to post title if event name not available
                $official_name = sanitize_text_field($_POST['post_title'] ?? '');
            }
            update_post_meta($post_id, 'scn_event_official_name', $official_name);
        }

        if (!in_array('location_city', $locked_fields) && isset($_POST['scn_event_location_city'])) {
            update_post_meta($post_id, 'scn_event_location_city', sanitize_text_field($_POST['scn_event_location_city']));
        }

        if (!in_array('location_region', $locked_fields) && isset($_POST['scn_event_location_region'])) {
            update_post_meta($post_id, 'scn_event_location_region', sanitize_text_field($_POST['scn_event_location_region']));
        }

        if (!in_array('location_country', $locked_fields) && isset($_POST['scn_event_location_country'])) {
            update_post_meta($post_id, 'scn_event_location_country', sanitize_text_field($_POST['scn_event_location_country']));
        }

        if (!in_array('dates', $locked_fields)) {
            $start_date = sanitize_text_field($_POST['scn_event_start_date'] ?? '');
            $end_date = sanitize_text_field($_POST['scn_event_end_date'] ?? '');
            
            if ($start_date) {
                $dates = ['start' => $start_date, 'end' => $end_date];
                
                if ($end_date && $end_date < $start_date) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('End date must be on or after start date.', 'scn-membership') . '</p></div>';
                    });
                    return;
                }
                
                update_post_meta($post_id, 'scn_event_dates', $dates);
                
                $year = date('Y', strtotime($start_date));
                update_post_meta($post_id, 'scn_event_year', $year);
            }
        }

        if (!in_array('website', $locked_fields) && isset($_POST['scn_event_website'])) {
            $website = $this->normalizeWebsite($_POST['scn_event_website']);
            if ($website) {
                update_post_meta($post_id, 'scn_event_website', $website);
            }
        }
    }

    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'scn_event') !== false) {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui-datepicker', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        }
    }

    public function sanitizeMetaField($value) {
        return sanitize_text_field($value);
    }

    public function sanitizeDates($value) {
        if (!is_array($value)) {
            return ['start' => '', 'end' => ''];
        }
        
        return [
            'start' => sanitize_text_field($value['start'] ?? ''),
            'end' => sanitize_text_field($value['end'] ?? ''),
        ];
    }

    public function sanitizeLockedFields($value) {
        if (!is_array($value)) {
            return [];
        }
        
        return array_map('sanitize_text_field', $value);
    }

    private function normalizeWebsite($url) {
        if (empty($url)) {
            return '';
        }

        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        } elseif (preg_match('/^http:\/\//', $url)) {
            $url = str_replace('http://', 'https://', $url);
        }

        $normalized_url = apply_filters('scn/events/validate_website', $url);
        
        if (!filter_var($normalized_url, FILTER_VALIDATE_URL)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Please enter a valid website URL.', 'scn-membership') . '</p></div>';
            });
            return '';
        }

        return $normalized_url;
    }

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
