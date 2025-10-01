<?php

namespace SCN\Membership\Modules\Profiles;

class ProfilePostType {
    public function register() {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerMetaFields']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post_scn_profile', [$this, 'saveMetaFields']);
        
        // Add custom columns to profiles list table
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

    public function registerMetaFields() {
        $meta_fields = [
            'scn_first_name',
            'scn_last_name',
            'scn_credentials',
            'scn_location',
            'scn_main_url',
            'scn_social_links',
            'scn_bio',
            'scn_member_since',
            'scn_topics',
            'scn_gallery_images',
            'scn_featured_video_url',
            'scn_featured_video_thumbnail',
            'scn_press_kit_files',
            'scn_services',
            'scn_badges',
        ];

        foreach ($meta_fields as $field) {
            register_meta('post', $field, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ]);
        }

        // Special handling for array fields
        register_meta('post', 'scn_gallery_images', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [$this, 'sanitizeGalleryImages'],
        ]);

        register_meta('post', 'scn_social_links', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [$this, 'sanitizeSocialLinks'],
        ]);

        register_meta('post', 'scn_services', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [$this, 'sanitizeServices'],
        ]);

        register_meta('post', 'scn_badges', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [$this, 'sanitizeBadges'],
        ]);
    }

    public function addMetaBoxes($post) {
        // Handle both post object and post type string parameters
        $post_type = '';
        if (is_object($post) && isset($post->post_type)) {
            $post_type = $post->post_type;
        } elseif (is_string($post)) {
            $post_type = $post;
        } else {
            global $post_type;
        }
        
        // Only add meta boxes for scn_profile post type
        if ($post_type !== 'scn_profile') {
            return;
        }

        // Add featured image metabox to sidebar
        add_meta_box(
            'postimagediv',
            __('Profile Image', 'scn-membership'),
            'post_thumbnail_meta_box',
            'scn_profile',
            'side',
            'high'
        );

        // Add topics metabox to sidebar
        add_meta_box(
            'scn_profile_topics',
            __('Profile Topics', 'scn-membership'),
            [$this, 'renderTopicsMetaBox'],
            'scn_profile',
            'side',
            'default'
        );

        // Add badges metabox to sidebar
        add_meta_box(
            'scn_profile_badges',
            __('Badges & Recognition', 'scn-membership'),
            [$this, 'renderBadgesMetaBox'],
            'scn_profile',
            'side',
            'default'
        );

        // Main content area metaboxes
        add_meta_box(
            'scn_profile_basic_info',
            __('Basic Information', 'scn-membership'),
            [$this, 'renderBasicInfoMetaBox'],
            'scn_profile',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_profile_gallery',
            __('Photo Gallery', 'scn-membership'),
            [$this, 'renderGalleryMetaBox'],
            'scn_profile',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_profile_featured_video',
            __('Featured Video', 'scn-membership'),
            [$this, 'renderFeaturedVideoMetaBox'],
            'scn_profile',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_profile_press_kit',
            __('Press Kit / Speaker Packet', 'scn-membership'),
            [$this, 'renderPressKitMetaBox'],
            'scn_profile',
            'normal',
            'high'
        );

        add_meta_box(
            'scn_profile_services',
            __('Services Offered', 'scn-membership'),
            [$this, 'renderServicesMetaBox'],
            'scn_profile',
            'normal',
            'high'
        );
    }

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

    public function renderBasicInfoMetaBox($post) {
        wp_nonce_field('scn_profile_meta', 'scn_profile_meta_nonce');
        
        $first_name = get_post_meta($post->ID, 'scn_first_name', true);
        $last_name = get_post_meta($post->ID, 'scn_last_name', true);
        $credentials = get_post_meta($post->ID, 'scn_credentials', true);
        $location = get_post_meta($post->ID, 'scn_location', true);
        $main_url = get_post_meta($post->ID, 'scn_main_url', true);
        $social_links = get_post_meta($post->ID, 'scn_social_links', true) ?: [];
        $bio = get_post_meta($post->ID, 'scn_bio', true);
        $member_since = get_post_meta($post->ID, 'scn_member_since', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_first_name"><?php _e('First Name', 'scn-membership'); ?></label></th>
                <td><input type="text" id="scn_first_name" name="scn_first_name" value="<?php echo esc_attr($first_name); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="scn_last_name"><?php _e('Last Name', 'scn-membership'); ?></label></th>
                <td><input type="text" id="scn_last_name" name="scn_last_name" value="<?php echo esc_attr($last_name); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="scn_credentials"><?php _e('Credentials', 'scn-membership'); ?></label></th>
                <td><input type="text" id="scn_credentials" name="scn_credentials" value="<?php echo esc_attr($credentials); ?>" class="regular-text" placeholder="<?php _e('e.g., PhD, MBA, CSP', 'scn-membership'); ?>" /></td>
            </tr>
            <tr>
                <th><label for="scn_location"><?php _e('Location', 'scn-membership'); ?></label></th>
                <td><input type="text" id="scn_location" name="scn_location" value="<?php echo esc_attr($location); ?>" class="regular-text" placeholder="<?php _e('City, State/Country', 'scn-membership'); ?>" /></td>
            </tr>
            <tr>
                <th><label for="scn_main_url"><?php _e('Main Website URL', 'scn-membership'); ?></label></th>
                <td><input type="url" id="scn_main_url" name="scn_main_url" value="<?php echo esc_attr($main_url); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="scn_member_since"><?php _e('Member Since', 'scn-membership'); ?></label></th>
                <td><input type="date" id="scn_member_since" name="scn_member_since" value="<?php echo esc_attr($member_since); ?>" /></td>
            </tr>
        </table>

        <h4><?php _e('Social Links', 'scn-membership'); ?></h4>
        <div id="scn-social-links">
            <?php
            $social_platforms = ['linkedin', 'twitter', 'facebook', 'instagram', 'youtube', 'website'];
            foreach ($social_platforms as $platform) {
                $value = isset($social_links[$platform]) ? $social_links[$platform] : '';
                ?>
                <p>
                    <label for="scn_social_<?php echo esc_attr($platform); ?>"><?php echo esc_html(ucfirst($platform)); ?>:</label><br>
                    <input type="url" id="scn_social_<?php echo esc_attr($platform); ?>" name="scn_social_links[<?php echo esc_attr($platform); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
                </p>
                <?php
            }
            ?>
        </div>

        <h4><?php _e('Bio', 'scn-membership'); ?></h4>
        <?php
        wp_editor($bio, 'scn_bio', [
            'textarea_name' => 'scn_bio',
            'media_buttons' => false,
            'textarea_rows' => 10,
        ]);
        ?>
        <?php
    }

    public function renderTopicsMetaBox($post) {
        $topics = get_post_meta($post->ID, 'scn_topics', true) ?: [];
        ?>
        <p><?php _e('Select topics that describe your expertise:', 'scn-membership'); ?></p>
        <?php
        $topic_terms = get_terms(['taxonomy' => 'scn_topic', 'hide_empty' => false]);
        if (!empty($topic_terms)) {
            foreach ($topic_terms as $term) {
                $checked = in_array($term->term_id, $topics) ? 'checked' : '';
                ?>
                <label>
                    <input type="checkbox" name="scn_topics[]" value="<?php echo esc_attr($term->term_id); ?>" <?php echo $checked; ?> />
                    <?php echo esc_html($term->name); ?>
                </label><br>
                <?php
            }
        } else {
            echo '<p>' . __('No topics available. ', 'scn-membership') . '<a href="' . admin_url('edit-tags.php?taxonomy=scn_topic&post_type=scn_course') . '">' . __('Add topics', 'scn-membership') . '</a></p>';
        }
        ?>
        <?php
    }

    public function renderGalleryMetaBox($post) {
        $gallery_images = get_post_meta($post->ID, 'scn_gallery_images', true) ?: [];
        ?>
        <div id="scn-gallery-container">
            <p><?php _e('Upload and manage your photo gallery. Images will be automatically renamed for SEO.', 'scn-membership'); ?></p>
            
            <div id="scn-gallery-uploader">
                <button type="button" id="scn-add-gallery-images" class="button button-secondary">
                    <?php _e('Add Gallery Images', 'scn-membership'); ?>
                </button>
            </div>

            <div id="scn-gallery-preview" class="scn-gallery-sortable">
                <?php
                if (!empty($gallery_images)) {
                    foreach ($gallery_images as $image_id) {
                        $image = wp_get_attachment_image($image_id, 'thumbnail');
                        if ($image) {
                            ?>
                            <div class="scn-gallery-item" data-image-id="<?php echo esc_attr($image_id); ?>">
                                <?php echo $image; ?>
                                <button type="button" class="scn-remove-gallery-image"><?php _e('Remove', 'scn-membership'); ?></button>
                            </div>
                            <?php
                        }
                    }
                }
                ?>
            </div>

            <input type="hidden" id="scn_gallery_images" name="scn_gallery_images" value="<?php echo esc_attr(implode(',', $gallery_images)); ?>" />
        </div>
        <?php
    }

    public function renderFeaturedVideoMetaBox($post) {
        $video_url = get_post_meta($post->ID, 'scn_featured_video_url', true);
        $video_thumbnail = get_post_meta($post->ID, 'scn_featured_video_thumbnail', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="scn_featured_video_url"><?php _e('Video URL', 'scn-membership'); ?></label></th>
                <td>
                    <input type="url" id="scn_featured_video_url" name="scn_featured_video_url" value="<?php echo esc_attr($video_url); ?>" class="regular-text" placeholder="<?php _e('YouTube or Vimeo URL', 'scn-membership'); ?>" />
                    <p class="description"><?php _e('Enter a YouTube or Vimeo URL', 'scn-membership'); ?></p>
                </td>
            </tr>
        </table>

        <?php if ($video_thumbnail): ?>
        <div id="scn-video-preview">
            <h4><?php _e('Video Preview', 'scn-membership'); ?></h4>
            <img src="<?php echo esc_url($video_thumbnail); ?>" alt="<?php _e('Video thumbnail', 'scn-membership'); ?>" style="max-width: 300px;" />
        </div>
        <?php endif; ?>
        <?php
    }

    public function renderPressKitMetaBox($post) {
        $press_kit_files = get_post_meta($post->ID, 'scn_press_kit_files', true) ?: [];
        ?>
        <div id="scn-press-kit-container">
            <p><?php _e('Upload press kit files (PDF, DOC, DOCX). Maximum 20MB per file.', 'scn-membership'); ?></p>
            
            <div id="scn-press-kit-uploader">
                <button type="button" id="scn-add-press-kit-files" class="button button-secondary">
                    <?php _e('Add Press Kit Files', 'scn-membership'); ?>
                </button>
            </div>

            <div id="scn-press-kit-list">
                <?php
                if (!empty($press_kit_files)) {
                    foreach ($press_kit_files as $file_id) {
                        $file = get_post($file_id);
                        if ($file) {
                            $file_url = wp_get_attachment_url($file_id);
                            ?>
                            <div class="scn-press-kit-item" data-file-id="<?php echo esc_attr($file_id); ?>">
                                <a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html($file->post_title); ?></a>
                                <button type="button" class="scn-remove-press-kit-file"><?php _e('Remove', 'scn-membership'); ?></button>
                            </div>
                            <?php
                        }
                    }
                }
                ?>
            </div>

            <input type="hidden" id="scn_press_kit_files" name="scn_press_kit_files" value="<?php echo esc_attr(implode(',', $press_kit_files)); ?>" />
        </div>
        <?php
    }

    public function renderServicesMetaBox($post) {
        $services = get_post_meta($post->ID, 'scn_services', true) ?: [];
        ?>
        <div id="scn-services-container">
            <p><?php _e('Add services offered by this member:', 'scn-membership'); ?></p>
            
            <div id="scn-services-list">
                <?php
                if (!empty($services)) {
                    foreach ($services as $index => $service) {
                        ?>
                        <div class="scn-service-item">
                            <input type="text" name="scn_services[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($service['name'] ?? ''); ?>" placeholder="<?php _e('Service name', 'scn-membership'); ?>" class="regular-text" />
                            <textarea name="scn_services[<?php echo esc_attr($index); ?>][description]" placeholder="<?php _e('Short description (optional)', 'scn-membership'); ?>" rows="2" class="large-text"><?php echo esc_textarea($service['description'] ?? ''); ?></textarea>
                            <button type="button" class="scn-remove-service"><?php _e('Remove', 'scn-membership'); ?></button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>

            <button type="button" id="scn-add-service" class="button button-secondary">
                <?php _e('Add Service', 'scn-membership'); ?>
            </button>
        </div>
        <?php
    }

    public function renderBadgesMetaBox($post) {
        $badges = get_post_meta($post->ID, 'scn_badges', true) ?: [];
        $available_badges = [
            'spotlight_winner' => __('Spotlight on Speaking Winner', 'scn-membership'),
            'spirit_award' => __('Spirit of SCN Award Winner', 'scn-membership'),
            'verified_member' => __('Verified Member', 'scn-membership'),
        ];
        ?>
        <div id="scn-badges-container">
            <p><?php _e('Select badges for this member:', 'scn-membership'); ?></p>
            
            <?php foreach ($available_badges as $badge_slug => $badge_name): ?>
                <label>
                    <input type="checkbox" name="scn_badges[]" value="<?php echo esc_attr($badge_slug); ?>" <?php checked(in_array($badge_slug, $badges)); ?> />
                    <?php echo esc_html($badge_name); ?>
                </label><br>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public function saveMetaFields($post_id) {
        if (!isset($_POST['scn_profile_meta_nonce']) || !wp_verify_nonce($_POST['scn_profile_meta_nonce'], 'scn_profile_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = [
            'scn_first_name',
            'scn_last_name',
            'scn_credentials',
            'scn_location',
            'scn_main_url',
            'scn_bio',
            'scn_member_since',
            'scn_featured_video_url',
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Handle array fields
        if (isset($_POST['scn_social_links'])) {
            update_post_meta($post_id, 'scn_social_links', $this->sanitizeSocialLinks($_POST['scn_social_links']));
        }

        if (isset($_POST['scn_topics'])) {
            update_post_meta($post_id, 'scn_topics', array_map('intval', $_POST['scn_topics']));
        }

        if (isset($_POST['scn_gallery_images'])) {
            $gallery_images = array_filter(explode(',', sanitize_text_field($_POST['scn_gallery_images'])));
            update_post_meta($post_id, 'scn_gallery_images', array_map('intval', $gallery_images));
        }

        if (isset($_POST['scn_press_kit_files'])) {
            $press_kit_files = array_filter(explode(',', sanitize_text_field($_POST['scn_press_kit_files'])));
            update_post_meta($post_id, 'scn_press_kit_files', array_map('intval', $press_kit_files));
        }

        if (isset($_POST['scn_services'])) {
            update_post_meta($post_id, 'scn_services', $this->sanitizeServices($_POST['scn_services']));
        }

        if (isset($_POST['scn_badges'])) {
            update_post_meta($post_id, 'scn_badges', array_map('sanitize_text_field', $_POST['scn_badges']));
        }

        // Handle featured video thumbnail
        if (isset($_POST['scn_featured_video_url'])) {
            $video_url = sanitize_text_field($_POST['scn_featured_video_url']);
            if ($video_url) {
                $thumbnail_url = $this->getVideoThumbnail($video_url);
                if ($thumbnail_url) {
                    update_post_meta($post_id, 'scn_featured_video_thumbnail', $thumbnail_url);
                }
            }
        }
    }

    public function sanitizeGalleryImages($value) {
        if (!is_array($value)) {
            return [];
        }
        return array_map('intval', $value);
    }

    public function sanitizeSocialLinks($value) {
        if (!is_array($value)) {
            return [];
        }
        $sanitized = [];
        foreach ($value as $platform => $url) {
            if (!empty($url)) {
                $sanitized[sanitize_text_field($platform)] = esc_url_raw($url);
            }
        }
        return $sanitized;
    }

    public function sanitizeServices($value) {
        if (!is_array($value)) {
            return [];
        }
        $sanitized = [];
        foreach ($value as $service) {
            if (!empty($service['name'])) {
                $sanitized[] = [
                    'name' => sanitize_text_field($service['name']),
                    'description' => sanitize_textarea_field($service['description'] ?? ''),
                ];
            }
        }
        return $sanitized;
    }

    public function sanitizeBadges($value) {
        if (!is_array($value)) {
            return [];
        }
        return array_map('sanitize_text_field', $value);
    }

    public function getVideoThumbnail($url) {
        $video_id = $this->extractVideoId($url);
        if (!$video_id) {
            return false;
        }

        $platform = $this->getVideoPlatform($url);
        if ($platform === 'youtube') {
            return "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
        } elseif ($platform === 'vimeo') {
            $response = wp_remote_get("https://vimeo.com/api/v2/video/{$video_id}.json");
            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($data[0]['thumbnail_large'])) {
                    return $data[0]['thumbnail_large'];
                }
            }
        }

        return false;
    }

    private function extractVideoId($url) {
        $patterns = [
            'youtube' => '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/',
            'vimeo' => '/(?:vimeo\.com\/)([0-9]+)/',
        ];

        foreach ($patterns as $platform => $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }

    private function getVideoPlatform($url) {
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return 'youtube';
        } elseif (strpos($url, 'vimeo.com') !== false) {
            return 'vimeo';
        }
        return false;
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