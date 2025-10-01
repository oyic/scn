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

// Get social links (stored as array with order)
$social_links_raw = get_post_meta($profile_id, 'scn_social_links', true);

// Convert old format to new format if needed
    $social_links = [];
if (is_array($social_links_raw) && !empty($social_links_raw)) {
    // Check if it's old format (simple key-value pairs)
    if (isset($social_links_raw['linkedin']) || isset($social_links_raw['twitter'])) {
        // Old format - migrate it
        $order = 0;
        $platform_map = [
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'linkedin'],
            'twitter' => ['label' => 'Twitter / X', 'icon' => 'twitter'],
            'facebook' => ['label' => 'Facebook', 'icon' => 'facebook'],
            'instagram' => ['label' => 'Instagram', 'icon' => 'instagram'],
            'youtube' => ['label' => 'YouTube', 'icon' => 'youtube'],
            'website' => ['label' => 'Website', 'icon' => 'website']
        ];
        foreach ($platform_map as $platform => $info) {
            if (!empty($social_links_raw[$platform])) {
                $social_links[] = [
                    'platform' => $platform,
                    'url' => $social_links_raw[$platform],
                    'label' => $info['label'],
                    'icon' => $info['icon'],
                    'order' => $order++
                ];
            }
        }
    } else {
        // New format - use as is
        $social_links = $social_links_raw;
        // Ensure order is set
        usort($social_links, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
    }
}

// Extract for backward compatibility
$website = $main_url ?: '';
$linkedin = '';
$twitter = '';
$facebook = '';
$instagram = '';
$youtube = '';
foreach ($social_links as $link) {
    $platform = $link['platform'] ?? '';
    $url = $link['url'] ?? '';
    switch($platform) {
        case 'linkedin': $linkedin = $url; break;
        case 'twitter': $twitter = $url; break;
        case 'facebook': $facebook = $url; break;
        case 'instagram': $instagram = $url; break;
        case 'youtube': $youtube = $url; break;
        case 'website': if (!$website) $website = $url; break;
    }
}

// Get profile image
$profile_image_id = get_post_thumbnail_id($profile_id);
$profile_image_url = $profile_image_id ? wp_get_attachment_image_url($profile_image_id, 'large') : '';
$profile_image_alt = $profile_image_id ? get_post_meta($profile_image_id, '_wp_attachment_image_alt', true) : '';
if (!$profile_image_alt) {
    $profile_image_alt = 'Profile Photo of ' . $first_name . ' ' . $last_name;
    if ($credentials) {
        $profile_image_alt .= ', ' . $credentials;
    }
}

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

// Check if logged-in user is viewing their own profile
$is_own_profile = false;
$dashboard_url = '';
if (is_user_logged_in()) {
    $current_user_id = get_current_user_id();
    // Check if this profile belongs to the current user
    $profile_user_id = get_post_meta($profile_id, 'scn_user_id', true);
    
    if ($current_user_id == $profile_user_id) {
        $is_own_profile = true;
        $dashboard_url = home_url('/member-dashboard/');
    }
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

/* Force left alignment */
.entry-content {
    text-align: left !important;
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
    background: transparent;
    border-radius: 0;
    box-shadow: none;
    width: 100%;
    box-sizing: border-box;
}

.scn-profile-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    position: relative;
}

.scn-profile-image {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #a8d5f2;
    box-shadow: 0 2px 10px rgba(0, 115, 170, 0.15);
    display: block;
}

.scn-profile-image-placeholder {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 5px solid #a8d5f2;
    color: #6c757d;
    font-size: 72px;
    box-shadow: 0 2px 10px rgba(0, 115, 170, 0.15);
    position: relative;
}

.scn-profile-info h1 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 42px;
    font-weight: 700;
    text-transform: capitalize;
    line-height: 1.2;
}

.scn-profile-title {
    color: #6c757d;
    font-size: 18px;
    margin: 0 0 10px 0;
}

.scn-profile-website {
    margin: 10px 0 0 0;
}

.scn-profile-website a {
    color: #0073aa;
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    border-bottom: 1px solid transparent;
    transition: all 0.3s ease;
}

.scn-profile-website a:hover {
    border-bottom-color: #0073aa;
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
    position: relative;
    overflow: visible;
    box-sizing: border-box;
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

/* Two Column Layout */
.scn-profile-two-column {
    display: grid;
    grid-template-columns: 35% 65%;
    gap: 30px;
    margin-top: 30px;
    clear: both;
    align-items: start;
    position: relative;
    overflow: visible;
}

.scn-profile-sidebar-left {
    width: 100%;
    min-width: 0;
    overflow-wrap: break-word;
    position: relative;
}

.scn-profile-main-right {
    width: 100%;
    min-width: 0;
    overflow-wrap: break-word;
    position: relative;
}

/* Quick Facts Section */
.scn-quick-facts {
    background: transparent;
    border-radius: 0;
    padding: 0 !important;
    color: inherit;
}

.scn-quick-facts h3 {
    color: #2c3e50 !important;
    margin-bottom: 20px;
    font-size: 20px;
}

.scn-facts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.scn-fact-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.scn-fact-item:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.scn-fact-icon {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #0073aa;
    flex-shrink: 0;
}

.scn-fact-content {
    flex: 1;
}

.scn-fact-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    margin-bottom: 4px;
    font-weight: 600;
}

.scn-fact-value {
    font-size: 15px;
    font-weight: 500;
    color: #2c3e50;
}

.scn-fact-value a {
    color: #0073aa;
    text-decoration: none;
    border-bottom: 1px solid rgba(0, 115, 170, 0.3);
    transition: border-color 0.3s ease;
}

.scn-fact-value a:hover {
    border-bottom-color: #0073aa;
}

.scn-profile-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Social Icons Row - Below Name */
.scn-social-icons-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 15px 0;
}

.scn-social-icons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.scn-edit-social-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    text-decoration: none !important;
    transition: all 0.3s ease;
    opacity: 1 !important;
    visibility: visible !important;
    cursor: pointer;
}

.scn-edit-social-icon:hover {
    transform: scale(1.15);
}

.scn-edit-social-icon .scn-edit-icon-svg {
    width: 24px !important;
    height: 24px !important;
    display: inline-block !important;
}

.scn-edit-social-icon .scn-edit-icon-svg path {
    /* fill: #FFD700 !important; */
    transition: fill 0.3s ease;
}

.scn-social-icon-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: transparent;
    transition: all 0.3s ease;
    text-decoration: none;
}

.scn-social-icon-link:hover {
    transform: scale(1.15);
}

.scn-social-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    color: #8c8f94;
    transition: color 0.3s ease;
}

/* Social media brand colors on hover only */
.scn-social-icon-link:hover .scn-social-icon {
    color: #8c8f94;
}

.scn-social-icon-link[href*="linkedin.com"]:hover .scn-social-icon {
    color: #0077b5;
}

.scn-social-icon-link[href*="twitter.com"]:hover .scn-social-icon,
.scn-social-icon-link[href*="x.com"]:hover .scn-social-icon {
    color: #1da1f2;
}

.scn-social-icon-link[href*="facebook.com"]:hover .scn-social-icon {
    color: #1877f2;
}

.scn-social-icon-link[href*="instagram.com"]:hover .scn-social-icon {
    color: #e4405f;
}

.scn-social-icon-link[href*="youtube.com"]:hover .scn-social-icon {
    color: #ff0000;
}

/* Website/other links */
.scn-social-icon-link:not([href*="linkedin"]):not([href*="twitter"]):not([href*="facebook"]):not([href*="instagram"]):not([href*="youtube"]):hover .scn-social-icon {
    color: #0073aa;
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
    
    .scn-profile-two-column {
        grid-template-columns: 1fr;
    }
    
    .scn-profile-header {
        flex-direction: column;
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

/* Edit Profile Icon */
.scn-edit-profile-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    margin-left: 10px;
    text-decoration: none !important;
    transition: all 0.3s ease;
    vertical-align: middle;
    opacity: 1 !important;
    visibility: visible !important;
    cursor: pointer;
}

.scn-edit-profile-icon:hover {
    transform: scale(1.15);
}

/* Modal Styles */
.scn-modal {
    display: none;
    position: fixed;
    z-index: 999999 !important;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s ease;
}

.scn-modal.active {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

/* Lightbox Styles */
.scn-lightbox {
    display: none;
    position: fixed;
    z-index: 999999 !important;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    animation: fadeIn 0.3s ease;
}

.scn-lightbox.active {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

.scn-lightbox-content {
    max-width: 90%;
    max-height: 90%;
    position: relative;
    animation: zoomIn 0.3s ease;
}

.scn-lightbox-content img {
    max-width: 100%;
    max-height: 90vh;
    width: auto;
    height: auto;
    display: block;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
}

.scn-lightbox-close {
    position: absolute;
    top: -40px;
    right: -40px;
    color: #fff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    background: transparent;
    border: none;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border-radius: 50%;
}

.scn-lightbox-close:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: scale(1.1);
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.scn-modal-content {
    background-color: #fff;
    border-radius: 8px;
    padding: 30px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    position: relative;
    z-index: 1000000 !important;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.scn-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.scn-modal-header h2 {
    margin: 0;
    color: #2c3e50;
    font-size: 24px;
}

.scn-modal-close {
    background: transparent;
    border: none;
    font-size: 28px;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.scn-modal-close:hover {
    background-color: #f8f9fa;
    color: #2c3e50;
}

.scn-modal-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.scn-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.scn-form-group label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.scn-form-group input {
    padding: 12px 15px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.scn-form-group input:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
}

.scn-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.scn-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.scn-btn {
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-family: inherit;
}

.scn-btn-primary {
    background-color: #0073aa;
    color: #fff;
}

.scn-btn-primary:hover {
    background-color: #005a87;
}

.scn-btn-secondary {
    background-color: #e9ecef;
    color: #495057;
}

.scn-btn-secondary:hover {
    background-color: #dee2e6;
}

.scn-upload-btn {
    display: inline-block;
    padding: 40px 60px;
    border: 2px dashed #bdc3c7;
    border-radius: 8px;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.scn-upload-btn:hover {
    border-color: #0073aa;
    background: #e8f4fd;
}

@media (max-width: 600px) {
    .scn-form-row {
        grid-template-columns: 1fr;
    }
    
    .scn-modal-content {
        padding: 20px;
    }
}

.scn-edit-icon-svg {
    width: 24px !important;
    height: 24px !important;
    display: inline-block !important;
    vertical-align: middle;
}

.scn-edit-icon-svg path {
    fill: #003366 !important;
    transition: fill 0.3s ease;
}

.scn-edit-profile-icon:hover .scn-edit-icon-svg path {
    fill: #0073aa !important;
}

/* Profile Tabs */
.scn-profile-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #e9ecef;
    margin: 30px 0 20px 0;
    background: transparent;
}

.scn-tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 15px 25px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #6c757d;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    outline: none;
}

.scn-tab-btn:focus {
    outline: none;
    box-shadow: none;
}

.scn-tab-btn:focus-visible {
    outline: none;
}

.scn-tab-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.scn-tab-btn:hover {
    color: #495057;
    outline: none;
}

.scn-tab-btn.active {
    color: #0073aa;
    border-bottom-color: #0073aa;
    font-weight: 600;
    outline: none;
}

/* Profile Image Container */
.scn-profile-image-container {
    position: relative;
    display: inline-block;
    width: 180px;
    height: 180px;
    margin-right: 30px;
}

.scn-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    cursor: pointer;
    color: #2c3e50;
    z-index: 10;
}

.scn-profile-image-container:hover .scn-image-overlay {
    opacity: 1;
}

.scn-camera-icon {
    width: 40px;
    height: 40px;
    margin-bottom: 8px;
}

.scn-image-overlay .dashicons {
    font-size: 36px;
    width: 36px;
    height: 36px;
    margin-bottom: 6px;
    color: #0073aa;
}

.scn-overlay-text {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0073aa;
}

/* Logout Button */
.scn-logout-btn {
    position: absolute;
    bottom: 20px;
    right: 20px;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: transparent;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    color: #6c757d;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.scn-logout-btn:hover {
    background: #f8f9fa;
    border-color: #dc3545;
    color: #dc3545;
}

.scn-logout-icon {
    width: 16px;
    height: 16px;
}

.scn-logout-btn:hover .scn-logout-icon {
    color: #dc3545;
}

.scn-tab-content {
    display: none;
    animation: fadeIn 0.3s ease-in;
}

.scn-tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.scn-media-content,
.scn-courses-content,
.scn-events-content {
    padding: 20px 0;
}
</style>

<script>
// Set ajaxurl for AJAX requests
const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Load Cropper.js library
if (!document.getElementById('cropperjs-css')) {
    const cropperCSS = document.createElement('link');
    cropperCSS.id = 'cropperjs-css';
    cropperCSS.rel = 'stylesheet';
    cropperCSS.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css';
    document.head.appendChild(cropperCSS);
}

if (!document.getElementById('cropperjs-js')) {
    const cropperJS = document.createElement('script');
    cropperJS.id = 'cropperjs-js';
    cropperJS.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js';
    document.head.appendChild(cropperJS);
}

document.addEventListener('DOMContentLoaded', function() {
    // Debug: Check if modals exist
    console.log('Profile page loaded');
    console.log('Upload modal exists:', !!document.getElementById('scnProfileImageModal'));
    console.log('Cropper modal exists:', !!document.getElementById('scnImageCropperModal'));
    console.log('Crop image element exists:', !!document.getElementById('scn-crop-image'));
    console.log('Is own profile: <?php echo $is_own_profile ? "true" : "false"; ?>');
    const tabBtns = document.querySelectorAll('.scn-tab-btn');
    const tabContents = document.querySelectorAll('.scn-tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            const activeContent = document.querySelector('[data-content="' + tabName + '"]');
            if (activeContent) {
                activeContent.classList.add('active');
            }
        });
    });
});

// Profile Image Upload Functions
let currentProfileId = <?php echo $profile_id; ?>;
let currentProfileImageUrl = '<?php echo $profile_image_url ? esc_js($profile_image_url) : ''; ?>';
let selectedImageFile = null;

function openProfileImageModal() {
    const modal = document.getElementById('scnProfileImageModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function cropCurrentImage() {
    console.log('cropCurrentImage called');
    console.log('currentProfileImageUrl:', currentProfileImageUrl);
    
    if (!currentProfileImageUrl) {
        alert('No current image to crop. Please upload an image first.');
        return;
    }
    
    console.log('Cropping current image:', currentProfileImageUrl);
    
    // Set a dummy file for cropping existing image
    selectedImageFile = { name: 'profile-image.jpg' };
    
    // Close the upload modal and open cropper with current image
    // Add cache buster to force reload of current image
    const imageUrlWithCacheBuster = currentProfileImageUrl + (currentProfileImageUrl.includes('?') ? '&' : '?') + 't=' + Date.now();
    console.log('Image URL with cache buster:', imageUrlWithCacheBuster);
    
    closeProfileImageModal();
    openImageCropper(imageUrlWithCacheBuster);
}

function closeProfileImageModal() {
    const modal = document.getElementById('scnProfileImageModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        // Reset the file input
        const fileInput = document.getElementById('scn-profile-image-input');
        if (fileInput) {
            fileInput.value = '';
        }
        selectedImageFile = null;
    }
}

function closeImageCropperModal() {
    const modal = document.getElementById('scnImageCropperModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // Destroy cropper instance
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
    }
}

function zoomCropper(delta) {
    if (cropperInstance) {
        cropperInstance.zoom(delta);
    }
}

function setCropperZoom(value) {
    if (cropperInstance) {
        cropperInstance.zoomTo(parseFloat(value));
    }
}

function resetCropper() {
    if (cropperInstance) {
        cropperInstance.reset();
        document.getElementById('scn-zoom-slider').value = 0;
    }
}

function saveCroppedProfileImage() {
    if (!cropperInstance) {
        alert('Cropper not initialized');
        return;
    }
    
    console.log('Saving cropped image from Cropper.js');
    
    // Get cropped canvas
    const canvas = cropperInstance.getCroppedCanvas({
        width: 500,
        height: 500,
        imageSmoothingQuality: 'high'
    });
    
    if (!canvas) {
        alert('Failed to get cropped image');
        return;
    }
    
    console.log('Got cropped canvas');
    
    // Convert canvas to blob
    canvas.toBlob(function(blob) {
        console.log('Blob created:', blob.size, 'bytes');
        
        // Create filename (will be renamed on server)
        const filename = selectedImageFile ? selectedImageFile.name : 'profile-image.jpg';
        
        // Create a new file from the blob
        const croppedFile = new File([blob], filename, {
            type: 'image/jpeg',
            lastModified: Date.now()
        });
        
        // Close cropper modal
        closeImageCropperModal();
        
        // Upload the cropped image
        const formData = new FormData();
        formData.append('file', croppedFile);
        formData.append('action', 'upload_profile_image_direct');
        formData.append('profile_id', currentProfileId);
        formData.append('_wpnonce', '<?php echo wp_create_nonce('upload_profile_image_direct'); ?>');
        
        // Show loading indicator
        const overlay = document.querySelector('.scn-image-overlay');
        if (overlay) {
            overlay.innerHTML = '<div style="color: #0073aa; font-weight: bold;">Uploading...</div>';
        }
        
        console.log('Sending request to:', ajaxurl);
        console.log('FormData entries:');
        for (let pair of formData.entries()) {
            console.log(pair[0], pair[1]);
        }
        
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                }
            });
        })
        .then(result => {
            console.log('Parsed result:', result);
            if (result.success) {
                console.log('Success! Image uploaded.');
                
                // Update the profile image immediately instead of reloading
                if (result.data && result.data.image_url) {
                    const profileImg = document.querySelector('.scn-profile-image-container img');
                    if (profileImg) {
                        // Add cache buster to force reload
                        profileImg.src = result.data.image_url + '?t=' + Date.now();
                        console.log('Updated image src to:', profileImg.src);
                    }
                    
                    // Update current profile image URL for future crops
                    currentProfileImageUrl = result.data.image_url;
                    
                    // Reset overlay
                    if (overlay) {
                        overlay.innerHTML = '<svg class="scn-camera-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15.2C13.7673 15.2 15.2 13.7673 15.2 12C15.2 10.2327 13.7673 8.8 12 8.8C10.2327 8.8 8.8 10.2327 8.8 12C8.8 13.7673 10.2327 15.2 12 15.2Z" fill="#0073aa" stroke="#0073aa" stroke-width="1.5"/><path d="M3 16.8V9.2C3 8.0799 3 7.51984 3.21799 7.09202C3.40973 6.71569 3.71569 6.40973 4.09202 6.21799C4.51984 6 5.0799 6 6.2 6H7.23607C7.68256 6 7.9058 6 8.11568 5.94524C8.30179 5.89638 8.47873 5.81533 8.63824 5.70591C8.81856 5.58155 8.96823 5.40551 9.26756 5.05342L10.7324 3.29464C11.0318 2.94255 11.1814 2.76651 11.3618 2.64215C11.5213 2.53273 11.6982 2.45168 11.8843 2.40282C12.0942 2.34806 12.3174 2.34806 12.7639 2.34806H13.2361C13.6826 2.34806 13.9058 2.34806 14.1157 2.40282C14.3018 2.45168 14.4787 2.53273 14.6382 2.64215C14.8186 2.76651 14.9682 2.94255 15.2676 3.29464L16.7324 5.05342C17.0318 5.40551 17.1814 5.58155 17.3618 5.70591C17.5213 5.81533 17.6982 5.89638 17.8843 5.94524C18.0942 6 18.3174 6 18.7639 6H19.8C20.9201 6 21.4802 6 21.908 6.21799C22.2843 6.40973 22.5903 6.71569 22.782 7.09202C23 7.51984 23 8.0799 23 9.2V16.8C23 17.9201 23 18.4802 22.782 18.908C22.5903 19.2843 22.2843 19.5903 21.908 19.782C21.4802 20 20.9201 20 19.8 20H6.2C5.0799 20 4.51984 20 4.09202 19.782C3.71569 19.5903 3.40973 19.2843 3.21799 18.908C3 18.4802 3 17.9201 3 16.8Z" stroke="#0073aa" stroke-width="1.5"></path></svg><span class="scn-overlay-text">Change Picture</span>';
                    }
                } else {
                    console.warn('No image_url in response, reloading page...');
                    window.location.reload();
                }
            } else {
                const errorMsg = result.data || 'Failed to upload image';
                console.error('Upload failed:', errorMsg);
                alert('Error: ' + errorMsg);
                if (overlay) {
                    overlay.innerHTML = '<svg class="scn-camera-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15.2C13.7673 15.2 15.2 13.7673 15.2 12C15.2 10.2327 13.7673 8.8 12 8.8C10.2327 8.8 8.8 10.2327 8.8 12C8.8 13.7673 10.2327 15.2 12 15.2Z" fill="#0073aa" stroke="#0073aa" stroke-width="1.5"/><path d="M3 16.8V9.2C3 8.0799 3 7.51984 3.21799 7.09202C3.40973 6.71569 3.71569 6.40973 4.09202 6.21799C4.51984 6 5.0799 6 6.2 6H7.23607C7.68256 6 7.9058 6 8.11568 5.94524C8.30179 5.89638 8.47873 5.81533 8.63824 5.70591C8.81856 5.58155 8.96823 5.40551 9.26756 5.05342L10.7324 3.29464C11.0318 2.94255 11.1814 2.76651 11.3618 2.64215C11.5213 2.53273 11.6982 2.45168 11.8843 2.40282C12.0942 2.34806 12.3174 2.34806 12.7639 2.34806H13.2361C13.6826 2.34806 13.9058 2.34806 14.1157 2.40282C14.3018 2.45168 14.4787 2.53273 14.6382 2.64215C14.8186 2.76651 14.9682 2.94255 15.2676 3.29464L16.7324 5.05342C17.0318 5.40551 17.1814 5.58155 17.3618 5.70591C17.5213 5.81533 17.6982 5.89638 17.8843 5.94524C18.0942 6 18.3174 6 18.7639 6H19.8C20.9201 6 21.4802 6 21.908 6.21799C22.2843 6.40973 22.5903 6.71569 22.782 7.09202C23 7.51984 23 8.0799 23 9.2V16.8C23 17.9201 23 18.4802 22.782 18.908C22.5903 19.2843 22.2843 19.5903 21.908 19.782C21.4802 20 20.9201 20 19.8 20H6.2C5.0799 20 4.51984 20 4.09202 19.782C3.71569 19.5903 3.40973 19.2843 3.21799 18.908C3 18.4802 3 17.9201 3 16.8Z" stroke="#0073aa" stroke-width="1.5"></path></svg><span class="scn-overlay-text">Change Picture</span>';
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('An error occurred while uploading the image: ' + error.message);
            if (overlay) {
                overlay.innerHTML = '<svg class="scn-camera-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15.2C13.7673 15.2 15.2 13.7673 15.2 12C15.2 10.2327 13.7673 8.8 12 8.8C10.2327 8.8 8.8 10.2327 8.8 12C8.8 13.7673 10.2327 15.2 12 15.2Z" fill="#0073aa" stroke="#0073aa" stroke-width="1.5"/><path d="M3 16.8V9.2C3 8.0799 3 7.51984 3.21799 7.09202C3.40973 6.71569 3.71569 6.40973 4.09202 6.21799C4.51984 6 5.0799 6 6.2 6H7.23607C7.68256 6 7.9058 6 8.11568 5.94524C8.30179 5.89638 8.47873 5.81533 8.63824 5.70591C8.81856 5.58155 8.96823 5.40551 9.26756 5.05342L10.7324 3.29464C11.0318 2.94255 11.1814 2.76651 11.3618 2.64215C11.5213 2.53273 11.6982 2.45168 11.8843 2.40282C12.0942 2.34806 12.3174 2.34806 12.7639 2.34806H13.2361C13.6826 2.34806 13.9058 2.34806 14.1157 2.40282C14.3018 2.45168 14.4787 2.53273 14.6382 2.64215C14.8186 2.76651 14.9682 2.94255 15.2676 3.29464L16.7324 5.05342C17.0318 5.40551 17.1814 5.58155 17.3618 5.70591C17.5213 5.81533 17.6982 5.89638 17.8843 5.94524C18.0942 6 18.3174 6 18.7639 6H19.8C20.9201 6 21.4802 6 21.908 6.21799C22.2843 6.40973 22.5903 6.71569 22.782 7.09202C23 7.51984 23 8.0799 23 9.2V16.8C23 17.9201 23 18.4802 22.782 18.908C22.5903 19.2843 22.2843 19.5903 21.908 19.782C21.4802 20 20.9201 20 19.8 20H6.2C5.0799 20 4.51984 20 4.09202 19.782C3.71569 19.5903 3.40973 19.2843 3.21799 18.908C3 18.4802 3 17.9201 3 16.8Z" stroke="#0073aa" stroke-width="1.5"></path></svg><span class="scn-overlay-text">Change Picture</span>';
            }
        });
    }, 'image/jpeg', 0.9);
}

function handleImageSelect(event) {
    const file = event.target.files[0];
    if (!file) {
        console.log('No file selected');
        return;
    }
    
    console.log('File selected:', file.name, 'Type:', file.type, 'Size:', file.size);
    
    // Check if it's an image
    if (!file.type.match('image.*')) {
        alert('Please select an image file.');
        return;
    }
    
    selectedImageFile = file;
    
    // Show preview and open cropper
    const reader = new FileReader();
    reader.onload = function(e) {
        console.log('FileReader loaded, data length:', e.target.result.length);
        console.log('Opening cropper...');
        openImageCropper(e.target.result);
    };
    reader.onerror = function(e) {
        console.error('FileReader error:', e);
    };
    reader.readAsDataURL(file);
}

let cropperInstance = null;

function openImageCropper(imageSrc) {
    console.log('=== OPENING CROPPER ===');
    console.log('imageSrc length:', imageSrc ? imageSrc.length : 0);
    
    // Close upload modal first
    closeProfileImageModal();
    console.log('Upload modal closed');
    
    const modal = document.getElementById('scnImageCropperModal');
    console.log('Modal element:', modal);
    console.log('Modal display:', modal ? window.getComputedStyle(modal).display : 'N/A');
    
    if (!modal) {
        console.error('Cropper modal not found');
        alert('Cropper modal not found in page!');
        return;
    }
    
    const image = document.getElementById('scn-crop-image');
    console.log('Image element:', image);
    
    if (!image) {
        console.error('Image element not found');
        alert('Image element not found!');
        return;
    }
    
    // Destroy existing cropper if any
    if (cropperInstance) {
        console.log('Destroying existing cropper');
        cropperInstance.destroy();
        cropperInstance = null;
    }
    
    // Show modal IMMEDIATELY (before cropper loads)
    console.log('Adding active class to modal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    console.log('Modal should now be visible. Display:', window.getComputedStyle(modal).display);
    
    // Set the image source
    console.log('Setting image src...');
    image.src = imageSrc;
    
    // Wait for image to load, then init cropper
    image.onload = function() {
        console.log('Image loaded! Size:', image.width, 'x', image.height);
        
        // Wait for Cropper.js to load
        function initCropper() {
            if (typeof Cropper === 'undefined') {
                console.log('Waiting for Cropper.js library...');
                setTimeout(initCropper, 100);
                return;
            }
            
            console.log('Cropper.js library found, initializing...');
            
            try {
                // Initialize Cropper.js
                cropperInstance = new Cropper(image, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    ready: function() {
                        console.log('✓ Cropper is ready and interactive!');
                        console.log('You should now see the crop box and be able to drag/resize');
                    }
                });
                console.log('Cropper instance created:', cropperInstance);
            } catch(e) {
                console.error('Error creating Cropper:', e);
                alert('Error initializing cropper: ' + e.message);
            }
        }
        
        initCropper();
    };
    
    image.onerror = function() {
        console.error('Failed to load image!');
        alert('Failed to load image for cropping');
    };
}

function uploadProfileImage() {
    if (!selectedImageFile) {
        alert('Please select an image first.');
        return;
    }
    
    console.log('Starting upload for file:', selectedImageFile.name);
    console.log('Profile ID:', currentProfileId);
    console.log('Ajax URL:', ajaxurl);
    
    const formData = new FormData();
    formData.append('file', selectedImageFile);
    formData.append('action', 'upload_profile_image_direct');
    formData.append('profile_id', currentProfileId);
    formData.append('_wpnonce', '<?php echo wp_create_nonce('upload_profile_image_direct'); ?>');
    
    console.log('FormData prepared, closing modal...');
    closeProfileImageModal();
    
    // Show loading indicator
    const overlay = document.querySelector('.scn-image-overlay');
    if (overlay) {
        overlay.innerHTML = '<div style="color: #0073aa; font-weight: bold;">Uploading...</div>';
    }
    
    console.log('Sending AJAX request...');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response.status, response.statusText);
        return response.json();
    })
    .then(result => {
        console.log('Upload result:', result);
        if (result.success) {
            console.log('Success! Reloading page...');
            window.location.reload();
        } else {
            console.error('Upload failed:', result.data);
            alert('Error: ' + (result.data || 'Failed to upload image'));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred while uploading the image.');
    });
}

function saveCroppedImage(attachmentId, cropData) {
    const data = {
        action: 'update_profile_image',
        profile_id: currentProfileId,
        attachment_id: attachmentId,
        crop_x: cropData.x,
        crop_y: cropData.y,
        crop_width: cropData.width,
        crop_height: cropData.height,
        _wpnonce: '<?php echo wp_create_nonce('update_profile_image'); ?>'
    };
    
    // Send AJAX request
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            window.location.reload();
        } else {
            alert('Error: ' + (result.data || 'Failed to update profile image'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating your profile image.');
    });
}

// Modal Functions
function openBasicInfoModal() {
    const modal = document.getElementById('scnBasicInfoModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeBasicInfoModal() {
    const modal = document.getElementById('scnBasicInfoModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('scnBasicInfoModal');
    if (event.target === modal) {
        closeBasicInfoModal();
    }
});

// Close modal on Escape key
window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeBasicInfoModal();
        closeSocialLinksModal();
        closeProfileImageModal();
        closeImageCropperModal();
    }
});

// Handle form submission
function handleBasicInfoSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        action: 'update_basic_info',
        profile_id: formData.get('profile_id'),
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        credentials: formData.get('credentials'),
        location: formData.get('location'),
        main_url: formData.get('main_url'),
        _wpnonce: formData.get('_wpnonce')
    };
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    // Send AJAX request
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Reload page to show updated info
            window.location.reload();
        } else {
            alert('Error: ' + (result.data || 'Failed to update profile'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating your profile.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Social Links Modal Functions
function openSocialLinksModal() {
    const modal = document.getElementById('scnSocialLinksModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeSocialLinksModal() {
    const modal = document.getElementById('scnSocialLinksModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Handle social links form submission
function handleSocialLinksSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        action: 'update_social_links',
        profile_id: formData.get('profile_id'),
        linkedin: formData.get('linkedin'),
        twitter: formData.get('twitter'),
        facebook: formData.get('facebook'),
        instagram: formData.get('instagram'),
        youtube: formData.get('youtube'),
        website: formData.get('website'),
        _wpnonce: formData.get('_wpnonce')
    };
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    // Send AJAX request
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Reload page to show updated info
            window.location.reload();
        } else {
            alert('Error: ' + (result.data || 'Failed to update social links'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating your social links.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Close social links modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('scnSocialLinksModal');
    if (event.target === modal) {
        closeSocialLinksModal();
    }
});

// Lightbox Functions
function openImageLightbox(imageUrl) {
    const lightbox = document.getElementById('scnImageLightbox');
    const lightboxImg = document.getElementById('scnLightboxImage');
    
    if (lightbox && lightboxImg) {
        lightboxImg.src = imageUrl;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeImageLightbox() {
    const lightbox = document.getElementById('scnImageLightbox');
    if (lightbox) {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close lightbox when clicking outside the image
window.addEventListener('click', function(event) {
    const lightbox = document.getElementById('scnImageLightbox');
    if (event.target === lightbox) {
        closeImageLightbox();
    }
});

// Close lightbox on Escape key
window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageLightbox();
    }
});
</script>

<div class="scn-profile-page-wrapper">
<div class="scn-profile-container">
    <div class="scn-profile-header">
        <div class="scn-profile-image-container">
            <?php if ($profile_image_url): ?>
                <img src="<?php echo esc_url($profile_image_url); ?>" alt="<?php echo esc_attr($profile_image_alt); ?>" class="scn-profile-image" <?php if (!$is_own_profile): ?>onclick="openImageLightbox('<?php echo esc_js($profile_image_url); ?>')" style="cursor: pointer;"<?php endif; ?>>
                <?php if ($is_own_profile): ?>
                    <div class="scn-image-overlay" onclick="openProfileImageModal()">
                        <svg class="scn-camera-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 15.2C13.7673 15.2 15.2 13.7673 15.2 12C15.2 10.2327 13.7673 8.8 12 8.8C10.2327 8.8 8.8 10.2327 8.8 12C8.8 13.7673 10.2327 15.2 12 15.2Z" fill="#0073aa" stroke="#0073aa" stroke-width="1.5"/>
                            <path d="M3 16.8V9.2C3 8.0799 3 7.51984 3.21799 7.09202C3.40973 6.71569 3.71569 6.40973 4.09202 6.21799C4.51984 6 5.0799 6 6.2 6H7.23607C7.68256 6 7.9058 6 8.11568 5.94524C8.30179 5.89638 8.47873 5.81533 8.63824 5.70591C8.81856 5.58155 8.96823 5.40551 9.26756 5.05342L10.7324 3.29464C11.0318 2.94255 11.1814 2.76651 11.3618 2.64215C11.5213 2.53273 11.6982 2.45168 11.8843 2.40282C12.0942 2.34806 12.3174 2.34806 12.7639 2.34806H13.2361C13.6826 2.34806 13.9058 2.34806 14.1157 2.40282C14.3018 2.45168 14.4787 2.53273 14.6382 2.64215C14.8186 2.76651 14.9682 2.94255 15.2676 3.29464L16.7324 5.05342C17.0318 5.40551 17.1814 5.58155 17.3618 5.70591C17.5213 5.81533 17.6982 5.89638 17.8843 5.94524C18.0942 6 18.3174 6 18.7639 6H19.8C20.9201 6 21.4802 6 21.908 6.21799C22.2843 6.40973 22.5903 6.71569 22.782 7.09202C23 7.51984 23 8.0799 23 9.2V16.8C23 17.9201 23 18.4802 22.782 18.908C22.5903 19.2843 22.2843 19.5903 21.908 19.782C21.4802 20 20.9201 20 19.8 20H6.2C5.0799 20 4.51984 20 4.09202 19.782C3.71569 19.5903 3.40973 19.2843 3.21799 18.908C3 18.4802 3 17.9201 3 16.8Z" stroke="#0073aa" stroke-width="1.5"/>
                        </svg>
                        <span class="scn-overlay-text">Change Picture</span>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="scn-profile-image-placeholder">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php if ($is_own_profile): ?>
                        <div class="scn-image-overlay" onclick="openProfileImageModal()">
                            <svg class="scn-camera-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 15.2C13.7673 15.2 15.2 13.7673 15.2 12C15.2 10.2327 13.7673 8.8 12 8.8C10.2327 8.8 8.8 10.2327 8.8 12C8.8 13.7673 10.2327 15.2 12 15.2Z" fill="#0073aa" stroke="#0073aa" stroke-width="1.5"/>
                                <path d="M3 16.8V9.2C3 8.0799 3 7.51984 3.21799 7.09202C3.40973 6.71569 3.71569 6.40973 4.09202 6.21799C4.51984 6 5.0799 6 6.2 6H7.23607C7.68256 6 7.9058 6 8.11568 5.94524C8.30179 5.89638 8.47873 5.81533 8.63824 5.70591C8.81856 5.58155 8.96823 5.40551 9.26756 5.05342L10.7324 3.29464C11.0318 2.94255 11.1814 2.76651 11.3618 2.64215C11.5213 2.53273 11.6982 2.45168 11.8843 2.40282C12.0942 2.34806 12.3174 2.34806 12.7639 2.34806H13.2361C13.6826 2.34806 13.9058 2.34806 14.1157 2.40282C14.3018 2.45168 14.4787 2.53273 14.6382 2.64215C14.8186 2.76651 14.9682 2.94255 15.2676 3.29464L16.7324 5.05342C17.0318 5.40551 17.1814 5.58155 17.3618 5.70591C17.5213 5.81533 17.6982 5.89638 17.8843 5.94524C18.0942 6 18.3174 6 18.7639 6H19.8C20.9201 6 21.4802 6 21.908 6.21799C22.2843 6.40973 22.5903 6.71569 22.782 7.09202C23 7.51984 23 8.0799 23 9.2V16.8C23 17.9201 23 18.4802 22.782 18.908C22.5903 19.2843 22.2843 19.5903 21.908 19.782C21.4802 20 20.9201 20 19.8 20H6.2C5.0799 20 4.51984 20 4.09202 19.782C3.71569 19.5903 3.40973 19.2843 3.21799 18.908C3 18.4802 3 17.9201 3 16.8Z" stroke="#0073aa" stroke-width="1.5"/>
                            </svg>
                            <span class="scn-overlay-text">Add Picture</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="scn-profile-info">
            <h1>
                <?php 
                echo esc_html($first_name . ' ' . $last_name);
                if ($credentials) {
                    echo ', ' . esc_html($credentials);
                }
                
                // Show edit icon if user is viewing their own profile
                if ($is_own_profile) {
                    echo ' <span class="scn-edit-profile-icon" title="Edit Basic Information" onclick="openBasicInfoModal()">';
                    echo '<svg class="scn-edit-icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
                    echo '<path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z" fill="#003366"/>';
                    echo '</svg>';
                    echo '</span>';
                }
                ?>
            </h1>
            
            <?php if ($main_url): ?>
                <p class="scn-profile-website">
                    <a href="<?php echo esc_url($main_url); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html(parse_url($main_url, PHP_URL_HOST)); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($social_links)): ?>
                <div class="scn-social-icons-wrapper">
                <div class="scn-social-icons">
                    <?php 
                    // Helper function to get SVG icon by platform
                    function get_social_icon_svg($platform) {
                        $icons = [
                            'linkedin' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                            'twitter' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>',
                            'facebook' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                            'instagram' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm4.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
                            'youtube' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
                            'website' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>',
                            'custom' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>'
                        ];
                        return $icons[$platform] ?? $icons['custom'];
                    }
                    
                    // Display links in order
                    foreach ($social_links as $link): 
                        $platform = $link['platform'] ?? 'custom';
                        $url = $link['url'] ?? '';
                        $label = $link['label'] ?? ucfirst($platform);
                        
                        // Skip if URL is empty or matches main_url
                        if (empty($url) || $url === $main_url) continue;
                    ?>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" class="scn-social-icon-link" title="<?php echo esc_attr($label); ?>">
                            <?php echo get_social_icon_svg($platform); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($is_own_profile): ?>
                    <span class="scn-edit-social-icon" title="Edit Social Links" onclick="openSocialLinksModal()">
                        <svg class="scn-edit-icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z" fill="#FFD700"/>
                        </svg>
                    </span>
                <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (is_user_logged_in()): ?>
            <a href="<?php echo wp_logout_url(home_url('/member-login/')); ?>" class="scn-logout-btn">
                <svg class="scn-logout-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 7L15.59 8.41L18.17 11H8V13H18.17L15.59 15.58L17 17L22 12L17 7ZM4 5H12V3H4C2.9 3 2 3.9 2 5V19C2 20.1 2.9 21 4 21H12V19H4V5Z" fill="currentColor"/>
                </svg>
                <span>Logout</span>
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Profile Tabs -->
    <div class="scn-profile-tabs">
        <button class="scn-tab-btn active" data-tab="overview">
            <span class="dashicons dashicons-admin-home"></span>
            Overview
        </button>
        <button class="scn-tab-btn" data-tab="media">
            <span class="dashicons dashicons-format-gallery"></span>
            Media
        </button>
        <button class="scn-tab-btn" data-tab="courses">
            <span class="dashicons dashicons-welcome-learn-more"></span>
            Courses
        </button>
        <button class="scn-tab-btn" data-tab="events">
            <span class="dashicons dashicons-calendar-alt"></span>
            Events
        </button>
    </div>
    
    <!-- Tab Content: Overview -->
    <div class="scn-tab-content active" data-content="overview">
    <!-- Two Column Layout: Quick Facts + About -->
    <div class="scn-profile-two-column">
        <!-- Quick Facts (Left - 35%) -->
        <div class="scn-profile-sidebar-left">
            <div class="scn-profile-section scn-quick-facts">
                <h3>Quick Facts</h3>
                <div class="scn-facts-grid">
                    <?php if ($location): ?>
                        <div class="scn-fact-item">
                            <span class="scn-fact-icon dashicons dashicons-location"></span>
                            <div class="scn-fact-content">
                                <div class="scn-fact-label">Location</div>
                                <div class="scn-fact-value"><?php echo esc_html($location); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($member_since_display): ?>
                        <div class="scn-fact-item">
                            <span class="scn-fact-icon dashicons dashicons-calendar-alt"></span>
                            <div class="scn-fact-content">
                                <div class="scn-fact-label">Member Since</div>
                                <div class="scn-fact-value"><?php echo esc_html($member_since_display); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($credentials): ?>
                        <div class="scn-fact-item">
                            <span class="scn-fact-icon dashicons dashicons-awards"></span>
                            <div class="scn-fact-content">
                                <div class="scn-fact-label">Credentials</div>
                                <div class="scn-fact-value"><?php echo esc_html($credentials); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($main_url): ?>
                        <div class="scn-fact-item">
                            <span class="scn-fact-icon dashicons dashicons-admin-links"></span>
                            <div class="scn-fact-content">
                                <div class="scn-fact-label">Website</div>
                                <div class="scn-fact-value">
                                    <a href="<?php echo esc_url($main_url); ?>" target="_blank" rel="noopener"><?php echo esc_html(parse_url($main_url, PHP_URL_HOST)); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Count services, gallery images, etc.
                    $services_count = !empty($services_offered) ? count($services_offered) : 0;
                    $gallery_count = !empty($gallery_images) ? count($gallery_images) : 0;
                    ?>
                    
                    <?php if ($services_count > 0): ?>
                        <div class="scn-fact-item">
                            <span class="scn-fact-icon dashicons dashicons-portfolio"></span>
                            <div class="scn-fact-content">
                                <div class="scn-fact-label">Services Offered</div>
                                <div class="scn-fact-value"><?php echo esc_html($services_count); ?> <?php echo $services_count === 1 ? 'Service' : 'Services'; ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($gallery_count > 0): ?>
                        <div class="scn-fact-item">
                            <span class="scn-fact-icon dashicons dashicons-format-gallery"></span>
                            <div class="scn-fact-content">
                                <div class="scn-fact-label">Gallery Images</div>
                                <div class="scn-fact-value"><?php echo esc_html($gallery_count); ?> <?php echo $gallery_count === 1 ? 'Image' : 'Images'; ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- About + Other Sections (Right - 65%) -->
        <div class="scn-profile-main-right">
            <?php if ($bio): ?>
                <div class="scn-profile-section">
                    <h3>About</h3>
                    <div class="scn-profile-bio"><?php echo wp_kses_post($bio); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <!-- End Overview Tab -->
    
    <!-- Tab Content: Media -->
    <div class="scn-tab-content" data-content="media">
        <div class="scn-media-content">
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
    <!-- End Media Tab -->
    
    <!-- Tab Content: Courses -->
    <div class="scn-tab-content" data-content="courses">
        <div class="scn-courses-content">
            <p>Courses content coming soon...</p>
        </div>
    </div>
    <!-- End Courses Tab -->
    
    <!-- Tab Content: Events -->
    <div class="scn-tab-content" data-content="events">
        <div class="scn-events-content">
            <p>Events content coming soon...</p>
        </div>
    </div>
    <!-- End Events Tab -->
        
        <div class="scn-profile-sidebar" style="display: none;">
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
    
    <!-- Basic Information Edit Modal -->
    <?php if ($is_own_profile): ?>
    <div id="scnBasicInfoModal" class="scn-modal">
        <div class="scn-modal-content">
            <div class="scn-modal-header">
                <h2>Edit Basic Information</h2>
                <button type="button" class="scn-modal-close" onclick="closeBasicInfoModal()">&times;</button>
            </div>
            
            <form class="scn-modal-form" onsubmit="handleBasicInfoSubmit(event)">
                <?php wp_nonce_field('update_basic_info', '_wpnonce'); ?>
                <input type="hidden" name="profile_id" value="<?php echo esc_attr($profile_id); ?>">
                
                <div class="scn-form-row">
                    <div class="scn-form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" required>
                    </div>
                    
                    <div class="scn-form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" required>
                    </div>
                </div>
                
                <div class="scn-form-group">
                    <label for="credentials">Credentials</label>
                    <input type="text" id="credentials" name="credentials" value="<?php echo esc_attr($credentials); ?>" placeholder="e.g., PhD, MD, RN">
                </div>
                
                <div class="scn-form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo esc_attr($location); ?>" placeholder="City, State">
                </div>
                
                <div class="scn-form-group">
                    <label for="main_url">Website</label>
                    <input type="url" id="main_url" name="main_url" value="<?php echo esc_attr($main_url); ?>" placeholder="https://example.com">
                </div>
                
                <div class="scn-modal-footer">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="closeBasicInfoModal()">Cancel</button>
                    <button type="submit" class="scn-btn scn-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Social Links Edit Modal -->
    <?php if ($is_own_profile): ?>
    <div id="scnSocialLinksModal" class="scn-modal">
        <div class="scn-modal-content">
            <div class="scn-modal-header">
                <h2>Edit Social Links</h2>
                <button type="button" class="scn-modal-close" onclick="closeSocialLinksModal()">&times;</button>
            </div>
            
            <form class="scn-modal-form" onsubmit="handleSocialLinksSubmit(event)">
                <?php wp_nonce_field('update_social_links', '_wpnonce'); ?>
                <input type="hidden" name="profile_id" value="<?php echo esc_attr($profile_id); ?>">
                
                <div class="scn-form-group">
                    <label for="linkedin">
                        <svg style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" viewBox="0 0 24 24" fill="#0077b5">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                        LinkedIn
                    </label>
                    <input type="url" id="linkedin" name="linkedin" value="<?php echo esc_attr($linkedin); ?>" placeholder="https://linkedin.com/in/username">
                </div>
                
                <div class="scn-form-group">
                    <label for="twitter">
                        <svg style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" viewBox="0 0 24 24" fill="#1da1f2">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                        Twitter / X
                    </label>
                    <input type="url" id="twitter" name="twitter" value="<?php echo esc_attr($twitter); ?>" placeholder="https://twitter.com/username">
                </div>
                
                <div class="scn-form-group">
                    <label for="facebook">
                        <svg style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" viewBox="0 0 24 24" fill="#1877f2">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </label>
                    <input type="url" id="facebook" name="facebook" value="<?php echo esc_attr($facebook); ?>" placeholder="https://facebook.com/username">
                </div>
                
                <div class="scn-form-group">
                    <label for="instagram">
                        <svg style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" viewBox="0 0 24 24" fill="#e4405f">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm4.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                        Instagram
                    </label>
                    <input type="url" id="instagram" name="instagram" value="<?php echo esc_attr($instagram); ?>" placeholder="https://instagram.com/username">
                </div>
                
                <div class="scn-form-group">
                    <label for="youtube">
                        <svg style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" viewBox="0 0 24 24" fill="#ff0000">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        YouTube
                    </label>
                    <input type="url" id="youtube" name="youtube" value="<?php echo esc_attr($youtube); ?>" placeholder="https://youtube.com/@username">
                </div>
                
                <div class="scn-form-group">
                    <label for="website">
                        <svg style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" viewBox="0 0 24 24" fill="#6c757d">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                        Other Website
                    </label>
                    <input type="url" id="website" name="website" value="<?php echo esc_attr($website && $website !== $main_url ? $website : ''); ?>" placeholder="https://example.com">
                </div>
                
                <div class="scn-modal-footer">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="closeSocialLinksModal()">Cancel</button>
                    <button type="submit" class="scn-btn scn-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Profile Image Upload Modal -->
    <?php if ($is_own_profile): ?>
    <div id="scnProfileImageModal" class="scn-modal">
        <div class="scn-modal-content" style="max-width: 600px;">
            <div class="scn-modal-header">
                <h2><?php echo $profile_image_url ? 'Update Profile Image' : 'Upload Profile Image'; ?></h2>
                <button type="button" class="scn-modal-close" onclick="closeProfileImageModal()">&times;</button>
            </div>
            
            <div style="padding: 20px 0;">
                <?php if ($profile_image_url): ?>
                    <!-- Show current image with options -->
                    <div style="text-align: center; margin-bottom: 30px;">
                        <p style="margin-bottom: 15px; color: #6c757d; font-weight: 600;">Current Profile Image</p>
                        <img src="<?php echo esc_url($profile_image_url); ?>" alt="Current profile" style="max-width: 200px; border-radius: 50%; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <button type="button" class="scn-btn scn-btn-primary" onclick="cropCurrentImage()" style="width: 100%;">
                            <span style="margin-right: 8px;">✂️</span> Re-crop Current Image
                        </button>
                        <label for="scn-profile-image-input" class="scn-btn scn-btn-secondary" style="margin: 0; text-align: center; line-height: 1.4; padding: 12px 24px; cursor: pointer;">
                            <span style="margin-right: 8px;">📤</span> Upload New Image
                        </label>
                    </div>
                    
                    <input 
                        type="file" 
                        id="scn-profile-image-input" 
                        accept="image/*" 
                        onchange="handleImageSelect(event)"
                        style="display: none;">
                <?php else: ?>
                    <!-- Show upload area when no image exists -->
                    <p style="margin-bottom: 20px; color: #6c757d;">Choose an image from your computer. You'll be able to crop it after uploading.</p>
                    
                    <div style="text-align: center;">
                        <label for="scn-profile-image-input" class="scn-upload-btn">
                            <svg style="width: 48px; height: 48px; margin-bottom: 10px;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 15.2C13.7673 15.2 15.2 13.7673 15.2 12C15.2 10.2327 13.7673 8.8 12 8.8C10.2327 8.8 8.8 10.2327 8.8 12C8.8 13.7673 10.2327 15.2 12 15.2Z" fill="#0073aa" stroke="#0073aa" stroke-width="1.5"/>
                                <path d="M3 16.8V9.2C3 8.0799 3 7.51984 3.21799 7.09202C3.40973 6.71569 3.71569 6.40973 4.09202 6.21799C4.51984 6 5.0799 6 6.2 6H7.23607C7.68256 6 7.9058 6 8.11568 5.94524C8.30179 5.89638 8.47873 5.81533 8.63824 5.70591C8.81856 5.58155 8.96823 5.40551 9.26756 5.05342L10.7324 3.29464C11.0318 2.94255 11.1814 2.76651 11.3618 2.64215C11.5213 2.53273 11.6982 2.45168 11.8843 2.40282C12.0942 2.34806 12.3174 2.34806 12.7639 2.34806H13.2361C13.6826 2.34806 13.9058 2.34806 14.1157 2.40282C14.3018 2.45168 14.4787 2.53273 14.6382 2.64215C14.8186 2.76651 14.9682 2.94255 15.2676 3.29464L16.7324 5.05342C17.0318 5.40551 17.1814 5.58155 17.3618 5.70591C17.5213 5.81533 17.6982 5.89638 17.8843 5.94524C18.0942 6 18.3174 6 18.7639 6H19.8C20.9201 6 21.4802 6 21.908 6.21799C22.2843 6.40973 22.5903 6.71569 22.782 7.09202C23 7.51984 23 8.0799 23 9.2V16.8C23 17.9201 23 18.4802 22.782 18.908C22.5903 19.2843 22.2843 19.5903 21.908 19.782C21.4802 20 20.9201 20 19.8 20H6.2C5.0799 20 4.51984 20 4.09202 19.782C3.71569 19.5903 3.40973 19.2843 3.21799 18.908C3 18.4802 3 17.9201 3 16.8Z" stroke="#0073aa" stroke-width="1.5"/>
                            </svg>
                            <div style="font-size: 16px; font-weight: 600; color: #0073aa; margin-bottom: 5px;">Choose Image</div>
                            <div style="font-size: 13px; color: #6c757d;">or drag and drop here</div>
                        </label>
                        <input 
                            type="file" 
                            id="scn-profile-image-input" 
                            accept="image/*" 
                            onchange="handleImageSelect(event)"
                            style="display: none;">
                    </div>
                    
                    <p style="margin-top: 20px; font-size: 12px; color: #6c757d; text-align: center;">
                        Recommended: Square image, at least 500x500px<br>
                        Maximum file size: 5MB
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Image Cropper Modal -->
    <?php if ($is_own_profile): ?>
    <div id="scnImageCropperModal" class="scn-modal">
        <div class="scn-modal-content" style="max-width: 95vw; width: 1200px;">
            <div class="scn-modal-header">
                <h2>Crop Profile Image</h2>
                <button type="button" class="scn-modal-close" onclick="closeImageCropperModal()">&times;</button>
            </div>
            
            <div style="padding: 20px 0;">
                <p style="margin-bottom: 15px; color: #6c757d;">Drag to reposition, use corners to resize, and zoom with the slider below.</p>
                
                <div style="height: 70vh; min-height: 500px; max-height: 800px; overflow: hidden; margin-bottom: 20px; display: flex; align-items: center; justify-content: center;">
                    <img id="scn-crop-image" style="max-width: 100%; max-height: 100%; display: block;">
                </div>
                
                <!-- Zoom Controls -->
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="zoomCropper(-0.1)" style="padding: 8px 12px;">
                        <span style="font-size: 18px;">−</span>
                    </button>
                    <input type="range" id="scn-zoom-slider" min="0" max="2" step="0.01" value="0" style="flex: 1;" oninput="setCropperZoom(this.value)">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="zoomCropper(0.1)" style="padding: 8px 12px;">
                        <span style="font-size: 18px;">+</span>
                    </button>
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="resetCropper()" style="padding: 8px 16px; font-size: 13px;">
                        Reset
                    </button>
                </div>
                
                <div class="scn-modal-footer">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="closeImageCropperModal()">Cancel</button>
                    <button type="button" class="scn-btn scn-btn-primary" onclick="saveCroppedProfileImage()">
                        <span>💾</span> Save Cropped Image
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Image Lightbox (for non-owners) -->
    <div id="scnImageLightbox" class="scn-lightbox" onclick="closeImageLightbox()">
        <div class="scn-lightbox-content" onclick="event.stopPropagation()">
            <button type="button" class="scn-lightbox-close" onclick="closeImageLightbox()">&times;</button>
            <img id="scnLightboxImage" src="" alt="Profile Image">
        </div>
    </div>
</div>
</div>
