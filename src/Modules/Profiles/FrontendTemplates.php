<?php

namespace SCN\Membership\Modules\Profiles;

class FrontendTemplates {
    public function register() {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        // Template handling moved to main plugin file to avoid conflicts
        // add_filter('template_include', [$this, 'templateInclude']);
        add_action('init', [$this, 'addRewriteRules']);
        add_filter('query_vars', [$this, 'addQueryVars']);
    }

    public function enqueueScripts() {
        if (is_singular('member')) {
            $deps = ['jquery'];
            
            // Enqueue WordPress media library and jQuery UI Sortable for gallery management
            if (is_user_logged_in()) {
                $profile_id = get_the_ID();
                $profile_user_id = get_post_meta($profile_id, 'scn_user_id', true);
                $is_owner = (get_current_user_id() == $profile_user_id);
                $is_admin = current_user_can('edit_members') || current_user_can('edit_others_posts') || current_user_can('administrator');
                
                // Enqueue media for profile owner or admin
                if ($is_owner || $is_admin) {
                    wp_enqueue_media();
                    wp_enqueue_script('jquery-ui-sortable');
                    $deps[] = 'media-editor';
                    $deps[] = 'jquery-ui-sortable';
                }
            }
            
            wp_enqueue_script(
                'scn-profiles-frontend',
                SCN_MEMBERSHIP_URL . 'assets/js/profiles-frontend.js',
                $deps,
                SCN_MEMBERSHIP_VERSION,
                true
            );

            // Note: profiles.css doesn't exist - all styles are inline in the template
            // wp_enqueue_style(
            //     'scn-profiles-frontend',
            //     SCN_MEMBERSHIP_URL . 'assets/css/profiles.css',
            //     [],
            //     SCN_MEMBERSHIP_VERSION
            // );

            // Only localize script if it hasn't been localized already
            static $scn_profiles_localized = false;
            if (!$scn_profiles_localized) {
                wp_localize_script('scn-profiles-frontend', 'scnProfiles', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('profiles_frontend'),
                    'isLoggedIn' => is_user_logged_in(),
                ]);
                $scn_profiles_localized = true;
            }
        }
    }

    public function addRewriteRules() {
        add_rewrite_rule(
            '^profiles/([^/]+)/?$',
            'index.php?member=$matches[1]',
            'top'
        );
    }

    public function addQueryVars($vars) {
        $vars[] = 'member';
        return $vars;
    }

    public function templateInclude($template) {
        if (get_query_var('member')) {
            $profile_slug = get_query_var('member');
            $profile = get_page_by_path($profile_slug, OBJECT, 'member');
            
            if ($profile) {
                return SCN_MEMBERSHIP_PATH . 'templates/profiles/single-profile.php';
            }
        }

        return $template;
    }

    public function renderProfileGallery($post_id) {
        $gallery_images = get_post_meta($post_id, 'scn_gallery_images', true) ?: [];
        
        if (empty($gallery_images)) {
            return;
        }

        ?>
        <div class="scn-profile-gallery">
            <h3><?php _e('Photo Gallery', 'scn-membership'); ?></h3>
            <div class="scn-gallery-grid">
                <?php foreach ($gallery_images as $image_id): ?>
                    <?php
                    $image = wp_get_attachment_image($image_id, 'medium');
                    $full_image = wp_get_attachment_image_url($image_id, 'full');
                    $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                    ?>
                    <div class="scn-gallery-item">
                        <a href="<?php echo esc_url($full_image); ?>" data-lightbox="profile-gallery" data-title="<?php echo esc_attr($alt_text); ?>">
                            <?php echo $image; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function renderFeaturedVideo($post_id) {
        $video_url = get_post_meta($post_id, 'scn_featured_video_url', true);
        $video_thumbnail = get_post_meta($post_id, 'scn_featured_video_thumbnail', true);
        
        if (empty($video_url)) {
            return;
        }

        $video_processor = new VideoProcessor();
        $video_info = $video_processor->getVideoInfo($video_url);

        ?>
        <div class="scn-featured-video">
            <h3><?php _e('Featured Video', 'scn-membership'); ?></h3>
            <?php if ($video_info && $video_info['embed_code']): ?>
                <div class="scn-video-embed">
                    <?php echo $video_info['embed_code']; ?>
                </div>
            <?php elseif ($video_thumbnail): ?>
                <div class="scn-video-thumbnail">
                    <a href="<?php echo esc_url($video_url); ?>" target="_blank" rel="noopener">
                        <img src="<?php echo esc_url($video_thumbnail); ?>" alt="<?php _e('Video thumbnail', 'scn-membership'); ?>" />
                        <div class="scn-play-button">
                            <span class="dashicons dashicons-play"></span>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function renderPressKit($post_id) {
        $press_kit_files = get_post_meta($post_id, 'scn_press_kit_files', true) ?: [];
        
        if (empty($press_kit_files)) {
            return;
        }

        ?>
        <div class="scn-press-kit">
            <h3><?php _e('Press Kit / Speaker Packet', 'scn-membership'); ?></h3>
            <ul class="scn-press-kit-list">
                <?php foreach ($press_kit_files as $file_id): ?>
                    <?php
                    $file = get_post($file_id);
                    if (!$file) continue;
                    
                    $file_url = wp_get_attachment_url($file_id);
                    $file_type = get_post_mime_type($file_id);
                    $file_size = size_format(filesize(get_attached_file($file_id)));
                    ?>
                    <li class="scn-press-kit-item">
                        <a href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener" class="scn-download-link">
                            <span class="scn-file-icon dashicons dashicons-media-document"></span>
                            <span class="scn-file-name"><?php echo esc_html($file->post_title); ?></span>
                            <span class="scn-file-size">(<?php echo esc_html($file_size); ?>)</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    public function renderServices($post_id) {
        $services = get_post_meta($post_id, 'scn_services', true) ?: [];
        
        if (empty($services)) {
            return;
        }

        ?>
        <div class="scn-services">
            <h3><?php _e('Services Offered', 'scn-membership'); ?></h3>
            <ul class="scn-services-list">
                <?php foreach ($services as $service): ?>
                    <li class="scn-service-item">
                        <strong><?php echo esc_html($service['name']); ?></strong>
                        <?php if (!empty($service['description'])): ?>
                            <p><?php echo esc_html($service['description']); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    public function renderBadges($post_id) {
        $badges = get_post_meta($post_id, 'scn_badges', true) ?: [];
        
        if (empty($badges)) {
            return;
        }

        $badge_labels = [
            'spotlight_winner' => __('Spotlight on Speaking Winner', 'scn-membership'),
            'spirit_award' => __('Spirit of SCN Award Winner', 'scn-membership'),
            'verified_member' => __('Verified Member', 'scn-membership'),
        ];

        // Allow filtering of badges
        $badges = apply_filters('scn/profile/badges_list', $badges, $post_id);

        ?>
        <div class="scn-badges">
            <h3><?php _e('Recognition & Awards', 'scn-membership'); ?></h3>
            <div class="scn-badges-list">
                <?php foreach ($badges as $badge_slug): ?>
                    <?php if (isset($badge_labels[$badge_slug])): ?>
                        <span class="scn-badge scn-badge-<?php echo esc_attr($badge_slug); ?>">
                            <span class="scn-badge-icon dashicons dashicons-awards"></span>
                            <span class="scn-badge-label"><?php echo esc_html($badge_labels[$badge_slug]); ?></span>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function renderProfileSidebar($post_id) {
        ?>
        <div class="scn-profile-sidebar">
            <?php $this->renderServices($post_id); ?>
            <?php $this->renderBadges($post_id); ?>
        </div>
        <?php
    }
}


