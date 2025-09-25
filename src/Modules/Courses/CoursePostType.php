<?php

namespace SCN\Membership\Modules\Courses;

class CoursePostType {
    public function register() {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerMetaFields']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post_scn_course', [$this, 'saveMetaFields']);
        
        // Add custom columns to courses list table
        add_filter('manage_scn_course_posts_columns', [$this, 'addCustomColumns'], 20);
        add_action('manage_scn_course_posts_custom_column', [$this, 'displayCustomColumns'], 10, 2);
        add_filter('manage_edit-scn_course_sortable_columns', [$this, 'makeSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleCustomSorting']);
        
        // Validation is now handled by AdminService to prevent conflicts
        // add_action('save_post', [$this, 'validateCourseData']);
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
            'supports' => ['thumbnail', 'author'],
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

    public function registerMetaFields() {
        $meta_fields = [
            'scn_course_subtitle' => [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'scn_course_description' => [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
            'scn_course_ce_enabled' => [
                'type' => 'boolean',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
            'scn_course_ce_hours' => [
                'type' => 'number',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => [$this, 'sanitizeCeHours'],
            ],
            'scn_course_formats' => [
                'type' => 'array',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => [$this, 'sanitizeFormats'],
            ],
            'scn_course_outcomes' => [
                'type' => 'array',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => [$this, 'sanitizeOutcomes'],
            ],
            'scn_course_ondemand' => [
                'type' => 'object',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => [$this, 'sanitizeOnDemand'],
            ],
        ];

        foreach ($meta_fields as $field => $args) {
            register_meta('post', $field, $args);
        }
    }

    public function addMetaBoxes() {
        add_meta_box(
            'scn_course_basic_info',
            __('Course Information', 'scn-membership'),
            [$this, 'renderBasicInfoMetaBox'],
            'scn_course',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_course_ce_credits',
            __('Continuing Education Credits', 'scn-membership'),
            [$this, 'renderCeCreditsMetaBox'],
            'scn_course',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_course_formats_outcomes',
            __('Formats & Learning Outcomes', 'scn-membership'),
            [$this, 'renderFormatsOutcomesMetaBox'],
            'scn_course',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_course_ondemand',
            __('On-Demand Information', 'scn-membership'),
            [$this, 'renderOnDemandMetaBox'],
            'scn_course',
            'normal',
            'high'
        );
    }

    public function renderBasicInfoMetaBox($post) {
        wp_nonce_field('scn_course_meta', 'scn_course_meta_nonce');
        
        $subtitle = get_post_meta($post->ID, 'scn_course_subtitle', true);
        $description = get_post_meta($post->ID, 'scn_course_description', true);
        $topics = wp_get_post_terms($post->ID, 'scn_topic', ['fields' => 'ids']);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_course_subtitle"><?php _e('Subtitle', 'scn-membership'); ?></label></th>
                <td><input type="text" id="scn_course_subtitle" name="scn_course_subtitle" value="<?php echo esc_attr($subtitle); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="scn_course_description"><?php _e('Description', 'scn-membership'); ?></label></th>
                <td>
                    <textarea id="scn_course_description" name="scn_course_description" rows="5" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                    <p class="description"><?php _e('Brief description of the course content and objectives', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>

        <h4><?php _e('Topics', 'scn-membership'); ?></h4>
        <p><?php _e('Select topics that this course covers:', 'scn-membership'); ?></p>
        <?php
        $topic_terms = get_terms(['taxonomy' => 'scn_topic', 'hide_empty' => false]);
        if (!empty($topic_terms)) {
            foreach ($topic_terms as $term) {
                $checked = in_array($term->term_id, $topics) ? 'checked' : '';
                ?>
                <label>
                    <input type="checkbox" name="scn_course_topics[]" value="<?php echo esc_attr($term->term_id); ?>" <?php echo $checked; ?> />
                    <?php echo esc_html($term->name); ?>
                </label><br>
                <?php
            }
        }
        ?>
        <?php
    }

    public function renderCeCreditsMetaBox($post) {
        $ce_enabled = get_post_meta($post->ID, 'scn_course_ce_enabled', true);
        $ce_hours = get_post_meta($post->ID, 'scn_course_ce_hours', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_course_ce_enabled"><?php _e('CE Credits Available', 'scn-membership'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="scn_course_ce_enabled" name="scn_course_ce_enabled" value="1" <?php checked($ce_enabled); ?> />
                        <?php _e('This course provides continuing education credits', 'scn-membership'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="scn_course_ce_hours"><?php _e('CE Hours', 'scn-membership'); ?></label></th>
                <td>
                    <input type="number" id="scn_course_ce_hours" name="scn_course_ce_hours" value="<?php echo esc_attr($ce_hours); ?>" min="0.5" step="0.5" class="small-text" <?php echo $ce_enabled ? '' : 'disabled'; ?> />
                    <p class="description"><?php _e('Number of continuing education hours (minimum 0.5, step 0.5)', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ceToggle = document.getElementById('scn_course_ce_enabled');
            const ceHours = document.getElementById('scn_course_ce_hours');
            
            ceToggle.addEventListener('change', function() {
                ceHours.disabled = !this.checked;
                if (!this.checked) {
                    ceHours.value = '';
                }
            });
        });
        </script>
        <?php
    }

    public function renderFormatsOutcomesMetaBox($post) {
        $formats = get_post_meta($post->ID, 'scn_course_formats', true) ?: [];
        $outcomes = get_post_meta($post->ID, 'scn_course_outcomes', true) ?: [];
        $allowed_formats = apply_filters('scn/course/allowed_formats', []);
        ?>
        <h4><?php _e('Course Formats', 'scn-membership'); ?></h4>
        <p><?php _e('Select the format(s) for this course:', 'scn-membership'); ?></p>
        <?php foreach ($allowed_formats as $format_key => $format_label): ?>
            <label>
                <input type="checkbox" name="scn_course_formats[]" value="<?php echo esc_attr($format_key); ?>" <?php checked(in_array($format_key, $formats)); ?> />
                <?php echo esc_html($format_label); ?>
            </label><br>
        <?php endforeach; ?>

        <h4><?php _e('Learning Outcomes', 'scn-membership'); ?></h4>
        <p><?php _e('What will participants learn from this course? (At least one outcome required)', 'scn-membership'); ?></p>
        <div id="scn-outcomes-container">
            <?php
            if (!empty($outcomes)) {
                foreach ($outcomes as $index => $outcome) {
                    ?>
                    <div class="scn-outcome-item">
                        <input type="text" name="scn_course_outcomes[<?php echo esc_attr($index); ?>]" value="<?php echo esc_attr($outcome); ?>" class="regular-text" placeholder="<?php _e('Learning outcome', 'scn-membership'); ?>" />
                        <button type="button" class="scn-remove-outcome button"><?php _e('Remove', 'scn-membership'); ?></button>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="scn-outcome-item">
                    <input type="text" name="scn_course_outcomes[0]" value="" class="regular-text" placeholder="<?php _e('Learning outcome', 'scn-membership'); ?>" />
                    <button type="button" class="scn-remove-outcome button" style="display:none;"><?php _e('Remove', 'scn-membership'); ?></button>
                </div>
                <?php
            }
            ?>
        </div>
        <button type="button" id="scn-add-outcome" class="button button-secondary"><?php _e('Add Another Outcome', 'scn-membership'); ?></button>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addButton = document.getElementById('scn-add-outcome');
            const container = document.getElementById('scn-outcomes-container');
            let outcomeIndex = container.children.length;

            addButton.addEventListener('click', function() {
                const newItem = document.createElement('div');
                newItem.className = 'scn-outcome-item';
                newItem.innerHTML = `
                    <input type="text" name="scn_course_outcomes[${outcomeIndex}]" value="" class="regular-text" placeholder="<?php _e('Learning outcome', 'scn-membership'); ?>" />
                    <button type="button" class="scn-remove-outcome button"><?php _e('Remove', 'scn-membership'); ?></button>
                `;
                container.appendChild(newItem);
                outcomeIndex++;
                updateRemoveButtons();
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('scn-remove-outcome')) {
                    e.target.parentElement.remove();
                    updateRemoveButtons();
                }
            });

            function updateRemoveButtons() {
                const items = container.querySelectorAll('.scn-outcome-item');
                items.forEach((item, index) => {
                    const removeBtn = item.querySelector('.scn-remove-outcome');
                    removeBtn.style.display = items.length > 1 ? 'inline-block' : 'none';
                });
            }

            updateRemoveButtons();
        });
        </script>
        <?php
    }

    public function renderOnDemandMetaBox($post) {
        $ondemand = get_post_meta($post->ID, 'scn_course_ondemand', true) ?: [];
        $title = $ondemand['title'] ?? '';
        $link = $ondemand['link'] ?? '';
        $school = $ondemand['school'] ?? '';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_ondemand_title"><?php _e('Title', 'scn-membership'); ?></label></th>
                <td><input type="text" id="scn_ondemand_title" name="scn_ondemand_title" value="<?php echo esc_attr($title); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="scn_ondemand_school"><?php _e('School/Platform', 'scn-membership'); ?></label></th>
                <td><input type="text" id="scn_ondemand_school" name="scn_ondemand_school" value="<?php echo esc_attr($school); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="scn_ondemand_link"><?php _e('Link', 'scn-membership'); ?></label></th>
                <td>
                    <input type="url" id="scn_ondemand_link" name="scn_ondemand_link" value="<?php echo esc_attr($link); ?>" class="regular-text" placeholder="https://" />
                    <p class="description"><?php _e('Must be a valid HTTPS URL', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function saveMetaFields($post_id) {
        // Debug logging
        error_log('SCN Course Save: Starting saveMetaFields for post_id: ' . $post_id);
        
        if (!isset($_POST['scn_course_meta_nonce']) || !wp_verify_nonce($_POST['scn_course_meta_nonce'], 'scn_course_meta')) {
            error_log('SCN Course Save: Nonce verification failed');
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log('SCN Course Save: Autosave detected, skipping');
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            error_log('SCN Course Save: User lacks edit_post capability');
            return;
        }
        
        error_log('SCN Course Save: All checks passed, proceeding with save');

        $fields = [
            'scn_course_subtitle',
            'scn_course_description',
            'scn_course_ce_enabled',
            'scn_course_ce_hours',
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        if (isset($_POST['scn_course_formats'])) {
            update_post_meta($post_id, 'scn_course_formats', $this->sanitizeFormats($_POST['scn_course_formats']));
        }

        if (isset($_POST['scn_course_outcomes'])) {
            update_post_meta($post_id, 'scn_course_outcomes', $this->sanitizeOutcomes($_POST['scn_course_outcomes']));
        }

        if (isset($_POST['scn_course_topics'])) {
            wp_set_post_terms($post_id, array_map('intval', $_POST['scn_course_topics']), 'scn_topic');
        }

        $ondemand = [
            'title' => sanitize_text_field($_POST['scn_ondemand_title'] ?? ''),
            'school' => sanitize_text_field($_POST['scn_ondemand_school'] ?? ''),
            'link' => esc_url_raw($_POST['scn_ondemand_link'] ?? ''),
        ];
        update_post_meta($post_id, 'scn_course_ondemand', $ondemand);
    }

    public function validateCourseData($post_id) {
        if (get_post_type($post_id) !== 'scn_course') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $errors = [];

        $ce_enabled = get_post_meta($post_id, 'scn_course_ce_enabled', true);
        $ce_hours = get_post_meta($post_id, 'scn_course_ce_hours', true);

        if ($ce_enabled && (empty($ce_hours) || $ce_hours < 0.5)) {
            $errors[] = __('CE hours must be at least 0.5 when CE credits are enabled', 'scn-membership');
        }

        $outcomes = get_post_meta($post_id, 'scn_course_outcomes', true) ?: [];
        $valid_outcomes = array_filter($outcomes, function($outcome) {
            return !empty(trim($outcome));
        });

        if (empty($valid_outcomes)) {
            $errors[] = __('At least one learning outcome is required', 'scn-membership');
        }

        $ondemand = get_post_meta($post_id, 'scn_course_ondemand', true) ?: [];
        if (!empty($ondemand['link']) && !wp_http_validate_url($ondemand['link'])) {
            $errors[] = __('On-demand link must be a valid URL', 'scn-membership');
        }

        if (!empty($errors)) {
            remove_action('save_post', [$this, 'validateCourseData']);
            wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
            add_action('save_post', [$this, 'validateCourseData']);

            foreach ($errors as $error) {
                add_action('admin_notices', function() use ($error) {
                    echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
                });
            }
        }
    }

    public function sanitizeCeHours($value) {
        $value = floatval($value);
        return $value >= 0.5 ? $value : 0;
    }

    public function sanitizeFormats($value) {
        if (!is_array($value)) {
            return [];
        }
        
        $allowed_formats = array_keys(apply_filters('scn/course/allowed_formats', []));
        return array_intersect($value, $allowed_formats);
    }

    public function sanitizeOutcomes($value) {
        if (!is_array($value)) {
            return [];
        }
        
        return array_filter(array_map('sanitize_text_field', $value), function($outcome) {
            return !empty(trim($outcome));
        });
    }

    public function sanitizeOnDemand($value) {
        if (!is_array($value)) {
            return [];
        }
        
        return [
            'title' => sanitize_text_field($value['title'] ?? ''),
            'school' => sanitize_text_field($value['school'] ?? ''),
            'link' => apply_filters('scn/course/ondemand_link', $value['link'] ?? ''),
        ];
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
        
        // Debug: Log what columns we're working with
        error_log('SCN Course Columns: ' . print_r(array_keys($new_columns), true));
        
        return $new_columns;
    }

    public function displayCustomColumns($column, $post_id) {
        // Debug: Log what column is being displayed
        error_log('SCN Course Display Column: ' . $column . ' for post_id: ' . $post_id);
        
        // Prevent duplicate output with static flag
        static $displayed = [];
        $key = $post_id . '_' . $column;
        
        if (isset($displayed[$key])) {
            error_log('SCN Course: Preventing duplicate display for ' . $key);
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





