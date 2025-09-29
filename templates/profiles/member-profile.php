<?php
/**
 * Template for displaying member profiles
 * Accepts profile_id parameter to display specific profile
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get profile ID from URL parameter
$profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;

if (!$profile_id) {
    echo '<div class="scn-profile-error">';
    echo '<h2>Profile Not Found</h2>';
    echo '<p>No profile ID provided or invalid profile ID.</p>';
    echo '<a href="' . home_url() . '" class="scn-btn scn-btn-primary">Return Home</a>';
    echo '</div>';
    return;
}

// Get the profile post
$profile = get_post($profile_id);

if (!$profile || $profile->post_type !== 'scn_profile' || $profile->post_status !== 'publish') {
    echo '<div class="scn-profile-error">';
    echo '<h2>Profile Not Found</h2>';
    echo '<p>The requested profile could not be found or is not available.</p>';
    echo '<a href="' . home_url() . '" class="scn-btn scn-btn-primary">Return Home</a>';
    echo '</div>';
    return;
}

// Get profile meta data using correct field names
$first_name = get_post_meta($profile_id, 'scn_first_name', true);
$last_name = get_post_meta($profile_id, 'scn_last_name', true);
$credentials = get_post_meta($profile_id, 'scn_credentials', true);
$bio = get_post_meta($profile_id, 'scn_bio', true);
$location = get_post_meta($profile_id, 'scn_location', true);
$main_url = get_post_meta($profile_id, 'scn_main_url', true);
$member_since = get_post_meta($profile_id, 'scn_member_since', true);

// Get social links (stored as array)
$social_links = get_post_meta($profile_id, 'scn_social_links', true);
if (!is_array($social_links)) {
    $social_links = [];
}

// Extract individual social links
$website = $main_url ?: ($social_links['website'] ?? '');
$linkedin = $social_links['linkedin'] ?? '';
$twitter = $social_links['twitter'] ?? '';
$facebook = $social_links['facebook'] ?? '';
$instagram = $social_links['instagram'] ?? '';
$youtube = $social_links['youtube'] ?? '';

// Get profile image
$profile_image_id = get_post_thumbnail_id($profile_id);
$profile_image_url = $profile_image_id ? wp_get_attachment_image_url($profile_image_id, 'large') : '';

// Get gallery images
$gallery_images = get_post_meta($profile_id, 'scn_gallery_images', true);
if (!is_array($gallery_images)) {
    $gallery_images = [];
}

// Get press kit files
$press_kit_files = get_post_meta($profile_id, 'scn_press_kit_files', true);
if (!is_array($press_kit_files)) {
    $press_kit_files = [];
}

// Get services offered
$services_offered = get_post_meta($profile_id, 'scn_services', true);
if (!is_array($services_offered)) {
    $services_offered = [];
}

// Get featured video
$featured_video_url = get_post_meta($profile_id, 'scn_featured_video_url', true);
$featured_video_thumbnail = get_post_meta($profile_id, 'scn_featured_video_thumbnail', true);

// Get user data
$user_id = $profile->post_author;
$user = get_userdata($user_id);
$user_email = $user ? $user->user_email : '';
$user_registered = $user ? $user->user_registered : '';

// Format member since date
$member_since_display = '';
if ($member_since) {
    $member_since_display = date('F Y', strtotime($member_since));
} elseif ($user_registered) {
    $member_since_display = date('F Y', strtotime($user_registered));
}
?>

<style>
/* Hide page title */
.entry-header,
.page-header,
.entry-title,
.page-title,
h1.entry-title,
h1.page-title {
    display: none !important;
}

.scn-profile-page-wrapper {
    width: 100%;
    max-width: 100vw;
    margin: 0;
    padding: 20px;
    box-sizing: border-box;
    overflow-x: hidden;
}

.scn-profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    width: 100%;
    box-sizing: border-box;
}

.scn-profile-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.scn-profile-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 30px;
    border: 4px solid #f8f9fa;
}

.scn-profile-image-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 30px;
    border: 4px solid #e9ecef;
    color: #6c757d;
    font-size: 48px;
}

.scn-profile-info h1 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 32px;
}

.scn-profile-title {
    color: #6c757d;
    font-size: 18px;
    margin: 0 0 10px 0;
}

.scn-profile-location {
    color: #6c757d;
    font-size: 16px;
    margin: 0 0 15px 0;
}

.scn-profile-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.scn-profile-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #6c757d;
    font-size: 14px;
}

.scn-profile-meta-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.scn-profile-content {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    margin-top: 30px;
    width: 100%;
    box-sizing: border-box;
}

.scn-profile-main {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.scn-profile-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #007cba;
}

.scn-profile-section h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 20px;
}

.scn-profile-bio {
    line-height: 1.6;
    color: #495057;
}

.scn-profile-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.scn-social-links {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.scn-social-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #fff;
    border-radius: 6px;
    text-decoration: none;
    color: #495057;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.scn-social-link:hover {
    background: #f8f9fa;
    border-color: #007cba;
    color: #007cba;
}

.scn-social-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* Social media brand colors */
.scn-social-link[href*="linkedin.com"] .scn-social-icon {
    color: #0077b5;
}

.scn-social-link[href*="twitter.com"] .scn-social-icon,
.scn-social-link[href*="x.com"] .scn-social-icon {
    color: #1da1f2;
}

.scn-social-link[href*="facebook.com"] .scn-social-icon {
    color: #1877f2;
}

.scn-social-link[href*="instagram.com"] .scn-social-icon {
    color: #e4405f;
}

.scn-social-link[href*="youtube.com"] .scn-social-icon {
    color: #ff0000;
}

.scn-social-link:hover .scn-social-icon {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

.scn-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.scn-gallery-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1;
}

.scn-gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scn-services-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.scn-service-item {
    background: #fff;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.scn-service-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.scn-service-description {
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

.scn-press-kit {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.scn-press-kit-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #fff;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    text-decoration: none;
    color: #495057;
    transition: all 0.3s ease;
}

.scn-press-kit-item:hover {
    background: #f8f9fa;
    border-color: #007cba;
    color: #007cba;
}

.scn-press-kit-item .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.scn-featured-content {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.scn-featured-item {
    background: #fff;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.scn-featured-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.scn-featured-description {
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

.scn-profile-error {
    max-width: 600px;
    margin: 50px auto;
    padding: 40px;
    text-align: center;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.scn-profile-error h2 {
    color: #dc3545;
    margin-bottom: 15px;
}

.scn-profile-error p {
    color: #6c757d;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .scn-profile-content {
        grid-template-columns: 1fr;
    }
    
    .scn-profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .scn-profile-image,
    .scn-profile-image-placeholder {
        margin-right: 0;
        margin-bottom: 20px;
    }
    
    .scn-profile-meta {
        justify-content: center;
    }
    
    .scn-profile-page-wrapper {
        padding: 10px;
    }
    
    .scn-profile-container {
        padding: 15px;
    }
}

/* Ensure no horizontal overflow */
* {
    box-sizing: border-box;
}

.scn-profile-page-wrapper * {
    max-width: 100%;
}
</style>

<div class="scn-profile-page-wrapper">
<div class="scn-profile-container">
    <div class="scn-profile-header">
        <?php if ($profile_image_url): ?>
            <img src="<?php echo esc_url($profile_image_url); ?>" alt="<?php echo esc_attr($first_name . ' ' . $last_name); ?>" class="scn-profile-image">
        <?php else: ?>
            <div class="scn-profile-image-placeholder">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
        <?php endif; ?>
        
        <div class="scn-profile-info">
            <h1><?php echo esc_html($first_name . ' ' . $last_name); ?></h1>
            <?php if ($credentials): ?>
                <p class="scn-profile-title"><?php echo esc_html($credentials); ?></p>
            <?php endif; ?>
            <?php if ($location): ?>
                <p class="scn-profile-location">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($location); ?>
                </p>
            <?php endif; ?>
            
            <div class="scn-profile-meta">
                <?php if ($member_since_display): ?>
                    <div class="scn-profile-meta-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        Member since <?php echo esc_html($member_since_display); ?>
                    </div>
                <?php endif; ?>
                <?php if ($user_email): ?>
                    <div class="scn-profile-meta-item">
                        <span class="dashicons dashicons-email"></span>
                        <?php echo esc_html($user_email); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="scn-profile-content">
        <div class="scn-profile-main">
            <?php if ($bio): ?>
                <div class="scn-profile-section">
                    <h3>About</h3>
                    <div class="scn-profile-bio"><?php echo wp_kses_post($bio); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($gallery_images)): ?>
                <div class="scn-profile-section">
                    <h3>Photo Gallery</h3>
                    <div class="scn-gallery">
                        <?php foreach ($gallery_images as $image_id): ?>
                            <?php $image_url = wp_get_attachment_image_url($image_id, 'medium'); ?>
                            <?php if ($image_url): ?>
                                <div class="scn-gallery-item">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="Gallery image">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($featured_video_url): ?>
                <div class="scn-profile-section">
                    <h3>Featured Video</h3>
                    <div class="scn-featured-video">
                        <?php if ($featured_video_thumbnail): ?>
                            <img src="<?php echo esc_url($featured_video_thumbnail); ?>" alt="Featured Video Thumbnail" style="max-width: 100%; height: auto;">
                        <?php endif; ?>
                        <p><a href="<?php echo esc_url($featured_video_url); ?>" target="_blank" class="scn-btn scn-btn-primary">Watch Video</a></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="scn-profile-sidebar">
            <?php if ($website || $linkedin || $twitter || $facebook || $instagram || $youtube): ?>
                <div class="scn-profile-section">
                    <h3>Social Links</h3>
                    <div class="scn-social-links">
                        <?php if ($website): ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" class="scn-social-link">
                                <svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                                Website
                            </a>
                        <?php endif; ?>
                        <?php if ($linkedin): ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="scn-social-link">
                                <svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                                LinkedIn
                            </a>
                        <?php endif; ?>
                        <?php if ($twitter): ?>
                            <a href="<?php echo esc_url($twitter); ?>" target="_blank" class="scn-social-link">
                                <svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                                Twitter
                            </a>
                        <?php endif; ?>
                        <?php if ($facebook): ?>
                            <a href="<?php echo esc_url($facebook); ?>" target="_blank" class="scn-social-link">
                                <svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                Facebook
                            </a>
                        <?php endif; ?>
                        <?php if ($instagram): ?>
                            <a href="<?php echo esc_url($instagram); ?>" target="_blank" class="scn-social-link">
                                <svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                                Instagram
                            </a>
                        <?php endif; ?>
                        <?php if ($youtube): ?>
                            <a href="<?php echo esc_url($youtube); ?>" target="_blank" class="scn-social-link">
                                <svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                                YouTube
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($services_offered)): ?>
                <div class="scn-profile-section">
                    <h3>Services Offered</h3>
                    <div class="scn-services-list">
                        <?php foreach ($services_offered as $service): ?>
                            <div class="scn-service-item">
                                <div class="scn-service-name"><?php echo esc_html($service['title'] ?? $service['name'] ?? ''); ?></div>
                                <div class="scn-service-description"><?php echo esc_html($service['description'] ?? ''); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($press_kit_files)): ?>
                <div class="scn-profile-section">
                    <h3>Press Kit</h3>
                    <div class="scn-press-kit">
                        <?php foreach ($press_kit_files as $file_id): ?>
                            <?php $file_url = wp_get_attachment_url($file_id); ?>
                            <?php $file_name = get_the_title($file_id); ?>
                            <?php if ($file_url): ?>
                                <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="scn-press-kit-item">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php echo esc_html($file_name); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
