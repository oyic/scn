<?php
/**
 * Single Profile Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$first_name = get_post_meta($post_id, 'scn_first_name', true);
$last_name = get_post_meta($post_id, 'scn_last_name', true);
$credentials = get_post_meta($post_id, 'scn_credentials', true);
$location = get_post_meta($post_id, 'scn_location', true);
$main_url = get_post_meta($post_id, 'scn_main_url', true);
$social_links = get_post_meta($post_id, 'scn_social_links', true) ?: [];
$bio = get_post_meta($post_id, 'scn_bio', true);
$member_since = get_post_meta($post_id, 'member_since', true);
$topics = get_post_meta($post_id, 'scn_topics', true) ?: [];

$frontend_templates = new \SCN\Membership\Modules\Profiles\FrontendTemplates();

get_header();
?>

<div class="scn-profile-container">
    <div class="scn-profile-header">
        <div class="scn-profile-photo">
            <?php if (has_post_thumbnail()): ?>
                <?php the_post_thumbnail('medium'); ?>
            <?php else: ?>
                <div class="scn-placeholder-photo">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="scn-profile-info">
            <h1 class="scn-profile-name">
                <?php echo esc_html($first_name . ' ' . $last_name); ?>
                <?php if ($credentials): ?>
                    <span class="scn-credentials"><?php echo esc_html($credentials); ?></span>
                <?php endif; ?>
            </h1>
            
            <?php if ($location): ?>
                <p class="scn-location">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($location); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($member_since): ?>
                <p class="scn-member-since">
                    <?php printf(__('Member since %s', 'scn-membership'), date('F Y', strtotime($member_since))); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($main_url): ?>
                <p class="scn-main-url">
                    <a href="<?php echo esc_url($main_url); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html($main_url); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($social_links)): ?>
                <div class="scn-social-links">
                    <?php foreach ($social_links as $platform => $url): ?>
                        <?php if (!empty($url)): ?>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="scn-social-link scn-social-<?php echo esc_attr($platform); ?>">
                                <span class="dashicons dashicons-<?php echo esc_attr($platform === 'website' ? 'admin-site' : $platform); ?>"></span>
                                <?php echo esc_html(ucfirst($platform)); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="scn-profile-content">
        <div class="scn-profile-main">
            <?php if ($bio): ?>
                <div class="scn-profile-bio">
                    <h2><?php _e('About', 'scn-membership'); ?></h2>
                    <?php echo wp_kses_post($bio); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($topics)): ?>
                <div class="scn-profile-topics">
                    <h2><?php _e('Expertise', 'scn-membership'); ?></h2>
                    <div class="scn-topics-list">
                        <?php foreach ($topics as $topic_id): ?>
                            <?php $topic = get_term($topic_id, 'scn_topic'); ?>
                            <?php if ($topic): ?>
                                <span class="scn-topic-pill"><?php echo esc_html($topic->name); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php $frontend_templates->renderFeaturedVideo($post_id); ?>
            <?php $frontend_templates->renderProfileGallery($post_id); ?>
            <?php $frontend_templates->renderPressKit($post_id); ?>
        </div>
        
        <div class="scn-profile-sidebar">
            <?php $frontend_templates->renderProfileSidebar($post_id); ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>


