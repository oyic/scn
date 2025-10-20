<?php
/**
 * Template for displaying member profiles
 * Accepts profile_id parameter to display specific profile
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Helper function to detect social platform from URL
function detectSocialPlatform($url) {
    $url = strtolower($url);
    
    if (strpos($url, 'linkedin.com') !== false) return 'linkedin';
    if (strpos($url, 'twitter.com') !== false || strpos($url, 'x.com') !== false) return 'twitter';
    if (strpos($url, 'facebook.com') !== false) return 'facebook';
    if (strpos($url, 'instagram.com') !== false) return 'instagram';
    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) return 'youtube';
    if (strpos($url, 'tiktok.com') !== false) return 'tiktok';
    if (strpos($url, 'github.com') !== false) return 'github';
    if (strpos($url, 'pinterest.com') !== false) return 'pinterest';
    if (strpos($url, 'snapchat.com') !== false) return 'snapchat';
    
    return 'website'; // Default for other websites
}

// Helper function to get SVG icon by platform
function scn_get_social_icon_svg($platform) {
    $icons = [
        'linkedin' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'twitter' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>',
        'facebook' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'instagram' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm4.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
        'youtube' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        'tiktok' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>',
        'website' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>',
        'github' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>',
        'pinterest' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.746-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001 12.017.001z"/></svg>',
        'snapchat' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.746-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001 12.017.001z"/></svg>',
        'custom' => '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>'
    ];
    return $icons[$platform] ?? $icons['custom'];
}

// Get the current member post
$profile = get_post();

if (!$profile || $profile->post_type !== 'member' || $profile->post_status !== 'publish') {
    echo '<div class="scn-profile-error">';
    echo '<h2>Profile Not Found</h2>';
    echo '<p>The requested member profile could not be found or is not available.</p>';
    echo '<a href="' . home_url() . '" class="scn-btn scn-btn-primary">Return Home</a>';
    echo '</div>';
    return;
}

$profile_id = $profile->ID;

// Get member data using ACF fields
$basic_info = get_field('basic_info', $profile_id) ?: [];
$first_name = $basic_info['first_name'] ?? get_field('scn_first_name', $profile_id) ?: '';
$last_name = $basic_info['last_name'] ?? get_field('scn_last_name', $profile_id) ?: '';
$credentials = $basic_info['credentials'] ?? get_field('scn_credentials', $profile_id) ?: '';
$location = $basic_info['location'] ?? get_field('location', $profile_id) ?: get_field('scn_location', $profile_id) ?: '';
$main_url = $basic_info['main_url'] ?? get_field('main_url', $profile_id) ?: get_field('scn_main_url', $profile_id) ?: '';
$member_since = $basic_info['member_since'] ?? get_field('member_since', $profile_id) ?: '';
$bio = get_field('bio', $profile_id) ?: get_field('scn_bio', $profile_id) ?: '';


// Fallback to post title if no ACF data
if (empty($first_name) && empty($last_name)) {
    $post_title = $profile->post_title;
    $name_parts = explode(' ', $post_title, 2);
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[1] ?? '';
}

// Get social links using ACF repeater (no static fallback)
$social_links_raw = get_field('social_links', $profile_id) ?: [];

// Convert format if needed and normalize
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
            // New format - use as is, but fix missing platform/label data
            $social_links = [];
            foreach ($social_links_raw as $index => $link) {
                $url = $link['url'] ?? '';
                if (empty($url)) continue;
                
                // Detect platform from URL if not provided
                $platform = $link['platform'] ?? 'custom';
                $label = $link['label'] ?? '';
                
                if ($platform === 'custom' || empty($label)) {
                    // Try to detect platform from URL
                    if (str_contains($url, 'twitter.com') || strpos($url, 'x.com')) {
                        $platform = 'twitter';
                        $label = 'Twitter';
                    } elseif (strpos($url, 'facebook.com')) {
                        $platform = 'facebook';
                        $label = 'Facebook';
                    } elseif (strpos($url, 'instagram.com')) {
                        $platform = 'instagram';
                        $label = 'Instagram';
                    } elseif (strpos($url, 'linkedin.com')) {
                        $platform = 'linkedin';
                        $label = 'LinkedIn';
                    } elseif (strpos($url, 'youtube.com')) {
                        $platform = 'youtube';
                        $label = 'YouTube';
                    } elseif (strpos($url, 'tiktok.com')) {
                        $platform = 'tiktok';
                        $label = 'TikTok';
                    } else {
                        $platform = 'custom';
                        $label = $label ?: 'Website';
                    }
                }
                
                $social_links[] = [
                    'platform' => $platform,
                    'url' => $url,
                    'label' => $label,
                    'icon' => $platform,
                    'order' => $link['order'] ?? $index
                ];
            }
            
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
// Get profile image using ACF fields
$member_photo = get_field('member_photo', $profile_id) ?: get_field('scn_photo', $profile_id);
$profile_image_url = '';
    $profile_image_alt = 'Profile Photo of ' . $first_name . ' ' . $last_name;

if ($member_photo) {
    if (is_array($member_photo)) {
        $profile_image_url = $member_photo['url'] ?? '';
        $profile_image_alt = $member_photo['alt'] ?? $profile_image_alt;
    } else {
        $profile_image_url = wp_get_attachment_image_url($member_photo, 'large');
        $profile_image_alt = get_post_meta($member_photo, '_wp_attachment_image_alt', true) ?: $profile_image_alt;
    }
}

// Fallback to featured image if no ACF image
if (!$profile_image_url) {
    $profile_image_id = get_post_thumbnail_id($profile_id);
    $profile_image_url = $profile_image_id ? wp_get_attachment_image_url($profile_image_id, 'large') : '';
}

if ($credentials) {
    $profile_image_alt .= ', ' . $credentials;
}

// Get media data using ACF fields
$member_videos = get_field('member_videos', $profile_id) ?: [];
$gallery_images = get_field('gallery_images', $profile_id) ?: [];
$member_courses = get_field('courses', $profile_id) ?: [];
$press_kit_files = get_field('press_kit_files', $profile_id) ?: [];
$services_offered = get_field('services', $profile_id) ?: [];
$featured_video_url = get_field('featured_video_url', $profile_id) ?: '';
$featured_video_thumbnail = get_field('featured_video_thumbnail', $profile_id) ?: '';

// Ensure arrays
if (!is_array($member_videos)) $member_videos = [];
if (!is_array($gallery_images)) $gallery_images = [];
if (!is_array($member_courses)) $member_courses = [];
if (!is_array($press_kit_files)) $press_kit_files = [];
if (!is_array($services_offered)) $services_offered = [];

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

// Get the member's user ID from post meta (like in courses section)
$member_user_id = get_post_meta($profile_id, 'scn_user_id', true);

// If no scn_user_id meta, try ACF fields
if (!$member_user_id) {
$member_user_id = get_field('member_user_id', $profile_id);
}

// If no member_user_id field, try other possible field names
if (!$member_user_id) {
    $member_user_id = get_field('user_id', $profile_id);
}

// If still no user ID, try post author
if (!$member_user_id) {
    $member_user_id = $profile->post_author;
}

// Check if current user matches the member's user ID
if (is_user_logged_in()) {
    $current_user_id = get_current_user_id();
    
    // Debug: Log the values
    error_log("SCN Debug - Current User ID: " . $current_user_id);
    error_log("SCN Debug - Member User ID: " . $member_user_id);
    error_log("SCN Debug - Profile ID: " . $profile_id);
    error_log("SCN Debug - Profile Post Author: " . $profile->post_author);
    
    if ($current_user_id == $member_user_id) {
        $is_own_profile = true;
        $dashboard_url = home_url('/member-dashboard/');
        error_log("SCN Debug - Is own profile: TRUE");
    } else {
        // Fallback: Check if current user is the post author
        if ($current_user_id == $profile->post_author) {
            $is_own_profile = true;
            $dashboard_url = home_url('/member-dashboard/');
            error_log("SCN Debug - Is own profile: TRUE (fallback to post author)");
        } else {
            error_log("SCN Debug - Is own profile: FALSE - IDs don't match");
        }
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
    /* border-left: 4px solid #007cba; */
    position: relative;
    overflow: visible;
    box-sizing: border-box;
}

.scn-profile-section h3 {
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

/* Quick Facts Modal Styles */
.scn-quick-facts-sortable {
    min-height: 100px;
    padding: 10px;
    border: 2px dashed #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.scn-quick-fact-item {
    display: flex;
    align-items: center;
    padding: 15px;
    margin-bottom: 10px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    cursor: move;
    transition: all 0.3s ease;
}

.scn-quick-fact-item:hover {
    border-color: #007cba;
    box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
}

.scn-quick-fact-item.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.scn-quick-fact-handle {
    margin-right: 15px;
    cursor: grab;
    color: #999;
}

.scn-quick-fact-handle:active {
    cursor: grabbing;
}

.scn-quick-fact-content {
    flex: 1;
    margin-right: 15px;
}

.scn-quick-fact-text {
    font-size: 14px;
    line-height: 1.4;
    color: #333;
}

.scn-quick-fact-fields {
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.scn-field-group {
    flex: 1;
    min-width: 0;
}

.scn-field-group label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #666;
    margin-bottom: 4px;
}

.scn-field-group input[type="text"] {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.scn-field-group input[type="text"]:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
}

.scn-field-group input[type="text"]::placeholder {
    color: #999;
    font-style: italic;
}

.scn-quick-fact-actions {
    display: flex;
    gap: 8px;
}

.scn-edit-fact-btn,
.scn-delete-fact-btn {
    padding: 6px;
    border: none;
    background: transparent;
    color: #666;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.scn-edit-fact-btn:hover {
    background: #e3f2fd;
    color: #1976d2;
}

.scn-delete-fact-btn:hover {
    background: #ffebee;
    color: #d32f2f;
}

/* Topics Modal Styles */
.scn-topics-checkbox-container {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
    background: #f8f9fa;
}

.scn-topic-checkbox-item {
    margin-bottom: 8px;
}

.scn-topic-checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 4px;
    transition: background-color 0.2s ease;
}

.scn-topic-checkbox-label:hover {
    background-color: #e3f2fd;
}

.scn-topic-checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.scn-topic-name {
    font-size: 14px;
    color: #333;
    flex: 1;
}

.scn-topic-checkbox-item.hidden {
    display: none;
}

/* Topics Section */
.scn-topics-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.scn-topic-tag {
    display: inline-block;
    padding: 6px 12px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: default;
}

.scn-topic-tag:hover {
    background: #0073aa;
    border-color: #0073aa;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.3);
}

.scn-facts-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
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

/* Social Links Drag & Drop Styles */
.scn-social-links-sortable {
    min-height: 200px;
    border: 2px dashed #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    background: #fafafa;
}

.scn-social-link-item {
    display: flex;
    align-items: center;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: move;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.scn-social-link-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}

.scn-social-link-item.ui-sortable-helper {
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    transform: rotate(2deg);
}

.scn-social-link-item.ui-sortable-placeholder {
    background: #f0f0f0;
    border: 2px dashed #0073aa;
    height: 60px;
    margin-bottom: 10px;
}

.scn-social-link-handle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    margin-right: 15px;
    cursor: grab;
    color: #999;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.scn-social-link-handle:hover {
    background: #f0f0f0;
    color: #666;
}

.scn-social-link-handle:active {
    cursor: grabbing;
    background: #e0e0e0;
}

.scn-social-link-item[draggable="true"] {
    cursor: move;
}

.scn-social-link-item[draggable="true"]:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

/* Different styles for filled vs empty social links */
.scn-social-link-item.has-url {
    background: #e8f5e8;
    border-color: #28a745;
}

.scn-social-link-item.no-url {
    background: #f8f9fa;
    border-color: #e9ecef;
    opacity: 0.7;
}

.scn-social-link-item.no-url .scn-social-link-url {
    color: #999;
    font-style: italic;
}

.scn-social-link-content {
    flex: 1;
    display: flex;
    align-items: center;
}

.scn-social-link-icon {
    width: 40px;
    height: 40px;
    margin-right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 50%;
}

.scn-social-link-icon svg {
    width: 24px;
    height: 24px;
}

.scn-social-link-info {
    flex: 1;
}

.scn-social-link-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.scn-social-link-url {
    color: #666;
    font-size: 14px;
    word-break: break-all;
}

.scn-social-link-actions {
    display: flex;
    gap: 8px;
}

.scn-edit-link-btn,
.scn-delete-link-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.scn-edit-link-btn {
    background: #0073aa;
    color: white;
}

.scn-edit-link-btn:hover {
    background: #005a87;
}

.scn-delete-link-btn {
    background: #dc3545;
    color: white;
}

.scn-delete-link-btn:hover {
    background: #c82333;
}

.scn-social-links-sortable:empty::before {
    content: "No social links added yet. Click 'Add New Social Link' to get started.";
    display: block;
    text-align: center;
    color: #999;
    font-style: italic;
    padding: 40px 20px;
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

/* Course Accordion Styles */
.scn-courses-accordion {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.scn-course-accordion-item {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.scn-course-accordion-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #d1d5db;
}

.scn-course-accordion-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    cursor: pointer;
    background: #fff;
    transition: background 0.2s;
}

.scn-course-accordion-header:hover {
    background: #f9fafb;
}

.scn-course-header-left {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
}

.scn-course-thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f3f4f6;
}

.scn-course-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scn-course-header-info {
    flex: 1;
}

.scn-course-accordion-title {
    margin: 0 0 6px 0;
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    line-height: 1.3;
}

.scn-course-accordion-subtitle {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.4;
}

.scn-course-header-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.scn-course-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 500;
    border-radius: 6px;
    line-height: 1.4;
}

.scn-course-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.scn-badge-ce {
    background: #dbeafe;
    color: #1e40af;
}

.scn-badge-format {
    background: #f3e8ff;
    color: #6b21a8;
}

.scn-badge-topic {
    background: #d1fae5;
    color: #065f46;
}

.scn-badge-more {
    background: #f3f4f6;
    color: #6b7280;
}

.scn-course-accordion-toggle {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}

.scn-course-accordion-toggle:hover {
    background: #e5e7eb;
}

.scn-course-accordion-toggle .dashicons {
    transition: transform 0.3s ease;
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.scn-course-accordion-item.active .scn-course-accordion-toggle .dashicons {
    transform: rotate(180deg);
}

.scn-course-accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease;
}

.scn-course-accordion-item.active .scn-course-accordion-content {
    max-height: 5000px;
}

.scn-course-content-inner {
    padding: 0 24px 24px;
    border-top: 1px solid #f3f4f6;
}

.scn-course-section {
    margin-top: 24px;
}

.scn-course-section h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.scn-course-section h4 .dashicons {
    color: #6b7280;
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.scn-course-section p {
    margin: 0;
    color: #4b5563;
    line-height: 1.6;
}

.scn-course-full-content {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
}

.scn-course-content-text {
    color: #374151;
    line-height: 1.7;
}

.scn-course-content-text h1,
.scn-course-content-text h2,
.scn-course-content-text h3 {
    margin-top: 24px;
    margin-bottom: 12px;
    color: #111827;
}

.scn-course-content-text p {
    margin-bottom: 16px;
}

.scn-course-content-text ul,
.scn-course-content-text ol {
    margin: 16px 0;
    padding-left: 24px;
}

.scn-course-content-text li {
    margin-bottom: 8px;
}

.scn-course-outcomes-list {
    margin: 0;
    padding-left: 20px;
    list-style: none;
}

.scn-course-outcomes-list li {
    position: relative;
    padding-left: 28px;
    margin-bottom: 12px;
    color: #374151;
    line-height: 1.6;
}

.scn-course-outcomes-list li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10b981;
    font-weight: bold;
    font-size: 16px;
}

.scn-course-topics-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.scn-topic-pill {
    padding: 6px 14px;
    background: #f3f4f6;
    color: #374151;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}

.scn-course-actions {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.scn-course-actions .scn-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.scn-course-actions .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.scn-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.scn-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.scn-empty-state p {
    margin: 0;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .scn-course-header-left {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .scn-course-thumbnail {
        width: 100%;
        height: 180px;
    }
    
    .scn-course-accordion-header {
        padding: 16px;
    }
    
    .scn-course-content-inner {
        padding: 0 16px 16px;
    }
}
</style>

<script>
// Set ajaxurl for AJAX requests
const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Test AJAX functionality
function testQuickFactsAjax() {
    console.log('Testing AJAX...');
    const params = new URLSearchParams();
    params.append('action', 'update_quick_facts');
    params.append('test', '1');
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => response.json())
    .then(result => {
        console.log('Test result:', result);
        alert('Test result: ' + JSON.stringify(result));
    })
    .catch(error => {
        console.error('Test error:', error);
        alert('Test error: ' + error.message);
    });
}

// Filter topics based on search input
function filterTopics(searchTerm) {
    const topicItems = document.querySelectorAll('.scn-topic-checkbox-item');
    const searchLower = searchTerm.toLowerCase().trim();
    
    topicItems.forEach(item => {
        const topicName = item.getAttribute('data-topic-name');
        const topicDisplay = item.querySelector('.scn-topic-name').textContent.toLowerCase();
        
        if (searchLower === '' || topicName.includes(searchLower) || topicDisplay.includes(searchLower)) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
}


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
    console.log('Current User ID: <?php echo get_current_user_id(); ?>');
    console.log('Member User ID: <?php echo $member_user_id; ?>');
    console.log('Profile Post Author: <?php echo $profile->post_author; ?>');
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
        
        // Small delay to ensure modal is fully rendered
        setTimeout(() => {
            initSocialLinksSortable();
        }, 100);
    }
}

function closeSocialLinksModal() {
    const modal = document.getElementById('scnSocialLinksModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Initialize drag and drop for social links
function initSocialLinksSortable() {
    // Try jQuery UI Sortable first
    if (typeof jQuery !== 'undefined' && typeof jQuery.ui !== 'undefined' && typeof jQuery.ui.sortable !== 'undefined') {
        initJQuerySortable();
        return;
    }
    
    // Load jQuery UI Sortable if not already loaded
    if (typeof jQuery !== 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js';
        script.onload = function() {
            initJQuerySortable();
        };
        script.onerror = function() {
            console.log('jQuery UI failed to load, using native drag and drop');
            initNativeDragDrop();
        };
        document.head.appendChild(script);
    } else {
        console.log('jQuery not available, using native drag and drop');
        initNativeDragDrop();
    }
    
    function initJQuerySortable() {
        jQuery('#scn-social-links-container').sortable({
            handle: '.scn-social-link-handle',
            placeholder: 'scn-social-link-item ui-sortable-placeholder',
            helper: 'clone',
            opacity: 0.8,
            tolerance: 'pointer',
            cursor: 'move',
            update: function(event, ui) {
                updateSocialLinksOrder();
            }
        });
        console.log('jQuery UI sortable initialized');
    }
    
    function initNativeDragDrop() {
        const container = document.getElementById('scn-social-links-container');
        if (!container) return;
        
        let draggedElement = null;
        let draggedIndex = null;
        
        // Add drag and drop event listeners to all items
        const items = container.querySelectorAll('.scn-social-link-item');
        items.forEach((item, index) => {
            item.draggable = true;
            item.setAttribute('data-index', index);
            
            item.addEventListener('dragstart', function(e) {
                draggedElement = this;
                draggedIndex = parseInt(this.getAttribute('data-index'));
                this.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.outerHTML);
            });
            
            item.addEventListener('dragend', function(e) {
                this.style.opacity = '1';
                draggedElement = null;
                draggedIndex = null;
            });
            
            item.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });
            
            item.addEventListener('drop', function(e) {
                e.preventDefault();
                const dropIndex = parseInt(this.getAttribute('data-index'));
                
                if (draggedIndex !== null && draggedIndex !== dropIndex) {
                    // Move the element
                    if (draggedIndex < dropIndex) {
                        this.parentNode.insertBefore(draggedElement, this.nextSibling);
                    } else {
                        this.parentNode.insertBefore(draggedElement, this);
                    }
                    
                    // Update indices
                    const allItems = container.querySelectorAll('.scn-social-link-item');
                    allItems.forEach((item, index) => {
                        item.setAttribute('data-index', index);
                    });
                    
                    updateSocialLinksOrder();
                }
            });
        });
        
        console.log('Native drag and drop initialized');
    }
}

// Update social links order after drag and drop
function updateSocialLinksOrder() {
    const container = document.getElementById('scn-social-links-container');
    const items = container.querySelectorAll('.scn-social-link-item');
    
    items.forEach((item, index) => {
        item.setAttribute('data-order', index);
    });
}

// Edit social link
function editSocialLink(platform, url, label) {
    const modal = document.getElementById('scnSocialLinkEditModal');
    const form = document.getElementById('scn-social-link-edit-form');
    
    // Set current values
    document.getElementById('social-link-platform').value = platform;
    document.getElementById('social-link-url').value = url;
    document.getElementById('social-link-label').value = label;
    
    // Show/hide custom label field
    toggleCustomLabelField();
    
    // Store current platform for update
    form.setAttribute('data-current-platform', platform);
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close social link edit modal
function closeSocialLinkEditModal() {
    const modal = document.getElementById('scnSocialLinkEditModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Add new social link
function addNewSocialLink() {
    const modal = document.getElementById('scnSocialLinkEditModal');
    const form = document.getElementById('scn-social-link-edit-form');
    
    // Reset form
    form.reset();
    form.removeAttribute('data-current-platform');
    
    // Show/hide custom label field
    toggleCustomLabelField();
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Delete social link
function deleteSocialLink(platform) {
    if (confirm('Are you sure you want to delete this social link?')) {
        const item = document.querySelector(`[data-platform="${platform}"]`);
        if (item) {
            item.remove();
            updateSocialLinksOrder();
        }
    }
}

// Toggle custom label field visibility
function toggleCustomLabelField() {
    const platformSelect = document.getElementById('social-link-platform');
    const customLabelGroup = document.getElementById('custom-label-group');
    
    if (platformSelect.value === 'custom') {
        customLabelGroup.style.display = 'block';
    } else {
        customLabelGroup.style.display = 'none';
    }
}

// Save social links
function saveSocialLinks() {
    const container = document.getElementById('scn-social-links-container');
    const items = container.querySelectorAll('.scn-social-link-item');
    // Build payload as PHP array (not JSON) so server receives social_links[]
    const params = new URLSearchParams();
    params.append('action', 'update_social_links');
    params.append('profile_id', '<?php echo esc_js($profile_id); ?>');
    params.append('_wpnonce', '<?php echo wp_create_nonce('update_social_links'); ?>');

    items.forEach((item, index) => {
        const urlElement = item.querySelector('.scn-social-link-url');
        if (!urlElement) return;
        const url = urlElement.textContent.trim();
        if (!url || url === 'Not set') return;
        params.append(`social_links[${index}][url]`, url);
        params.append(`social_links[${index}][order]`, String(index));
    });

    fetch(ajaxurl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            window.location.reload();
        } else {
            alert('Error: ' + (result.data || 'Failed to update social links'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating social links.');
    });
}

// Quick Facts Modal Functions
function openQuickFactsModal() {
    const modal = document.getElementById('scnQuickFactsModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Initialize sortable functionality
        initializeSortable();
    }
}

function initializeSortable() {
    const container = document.getElementById('scn-quick-facts-container');
    if (!container) return;
    
    // Make items sortable
    container.addEventListener('dragstart', handleDragStart);
    container.addEventListener('dragover', handleDragOver);
    container.addEventListener('drop', handleDrop);
    container.addEventListener('dragend', handleDragEnd);
    
    // Add draggable attribute to all items
    const items = container.querySelectorAll('.scn-quick-fact-item');
    items.forEach(item => {
        item.draggable = true;
        item.addEventListener('dragstart', function(e) {
            this.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.outerHTML);
            e.dataTransfer.setData('text/plain', this.getAttribute('data-order'));
        });
    });
}

function handleDragStart(e) {
    this.style.opacity = '0.5';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.outerHTML);
    e.dataTransfer.setData('text/plain', this.getAttribute('data-order'));
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    
    const draggedOrder = e.dataTransfer.getData('text/plain');
    const target = e.target.closest('.scn-quick-fact-item');
    
    if (target && draggedOrder !== target.getAttribute('data-order')) {
        // Swap the items
        const dragged = document.querySelector(`[data-order="${draggedOrder}"]`);
        const targetOrder = target.getAttribute('data-order');
        
        // Update data-order attributes
        dragged.setAttribute('data-order', targetOrder);
        target.setAttribute('data-order', draggedOrder);
        
        // Reorder in DOM
        if (dragged.nextSibling === target) {
            target.parentNode.insertBefore(target, dragged);
        } else {
            target.parentNode.insertBefore(dragged, target);
        }
    }
    
    return false;
}

function handleDragEnd(e) {
    const items = document.querySelectorAll('.scn-quick-fact-item');
    items.forEach(item => {
        item.style.opacity = '';
    });
}

// Add a new quick fact row in the modal
function addNewQuickFact() {
    const container = document.getElementById('scn-quick-facts-container');
    if (!container) return;
    const order = container.querySelectorAll('.scn-quick-fact-item').length;
    const index = order; // Use the same index for form field names
    const wrapper = document.createElement('div');
    wrapper.className = 'scn-quick-fact-item';
    wrapper.setAttribute('data-order', String(order));
    wrapper.innerHTML = `
        <div class="scn-quick-fact-handle">
            <svg style="width: 16px; height: 16px; color: #999;" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 15h18v-2H3v2zm0 4h18v-2H3v2zm0-8h18V9H3v2zm0-6v2h18V5H3z"/>
            </svg>
        </div>
        <div class="scn-quick-fact-content">
            <div class="scn-quick-fact-fields">
                <div class="scn-field-group">
                    <label>Title</label>
                    <input type="text" name="quick_facts[${index}][title]" value="" placeholder="e.g., PhD">
                </div>
                <div class="scn-field-group">
                    <label>Description</label>
                    <input type="text" name="quick_facts[${index}][description]" value="" placeholder="e.g., University of the East">
                </div>
                <div class="scn-field-group">
                    <label>Year</label>
                    <input type="text" name="quick_facts[${index}][year]" value="" placeholder="e.g., 2001">
                </div>
                <input type="hidden" name="quick_facts[${index}][order]" value="${order}">
            </div>
        </div>
        <div class="scn-quick-fact-actions">
            <button type="button" class="scn-delete-fact-btn" onclick="this.closest('.scn-quick-fact-item').remove()">
                <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>
    `;
    container.appendChild(wrapper);
}

function closeQuickFactsModal() {
    const modal = document.getElementById('scnQuickFactsModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Topics Modal Functions
function openTopicsModal() {
    const modal = document.getElementById('scnTopicsModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeTopicsModal() {
    const modal = document.getElementById('scnTopicsModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Course Modal Functions
function openCourseModal(action, courseId = null) {
    const modal = document.getElementById('scnCourseModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Set form action and course ID if editing
        const form = modal.querySelector('form');
        if (form) {
            form.setAttribute('data-action', action);
            if (courseId) {
                form.setAttribute('data-course-id', courseId);
            }
        }
    }
}

function closeCourseModal() {
    const modal = document.getElementById('scnCourseModal');
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

// Handle Quick Facts form submission
function handleQuickFactsSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Debug: Log the form data being sent
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    // Build params manually like social links does
    const params = new URLSearchParams();
    params.append('action', 'update_quick_facts');
    params.append('profile_id', formData.get('profile_id'));
    params.append('_wpnonce', formData.get('_wpnonce'));
    
    // Add quick facts data
    const factItems = document.querySelectorAll('#scn-quick-facts-container .scn-quick-fact-item');
    factItems.forEach((item, index) => {
        const titleInput = item.querySelector('input[name*="[title]"]');
        const descInput = item.querySelector('input[name*="[description]"]');
        const yearInput = item.querySelector('input[name*="[year]"]');
        const orderInput = item.querySelector('input[name*="[order]"]');
        
        if (titleInput && titleInput.value.trim()) {
            params.append(`quick_facts[${index}][title]`, titleInput.value.trim());
            params.append(`quick_facts[${index}][description]`, descInput ? descInput.value.trim() : '');
            params.append(`quick_facts[${index}][year]`, yearInput ? yearInput.value.trim() : '');
            params.append(`quick_facts[${index}][order]`, orderInput ? orderInput.value : index);
        }
    });
    
    console.log('Final params being sent:');
    for (let [key, value] of params.entries()) {
        console.log(key, value);
    }
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        console.log('Server response:', result);
        if (result.success) {
            window.location.reload();
        } else {
            alert('Error: ' + (result.data || 'Failed to update Quick Facts'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating Quick Facts: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Handle Topics form submission
function handleTopicsSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Get selected topic IDs from checkboxes
    const selectedTopicIds = [];
    const checkedBoxes = event.target.querySelectorAll('input[name="topics[]"]:checked');
    checkedBoxes.forEach(checkbox => {
        selectedTopicIds.push(parseInt(checkbox.value));
    });
    
    // Build URLSearchParams manually to handle array properly
    const params = new URLSearchParams();
    params.append('action', 'update_topics');
    params.append('profile_id', formData.get('profile_id'));
    params.append('_wpnonce', formData.get('_wpnonce'));
    
    // Add each topic ID as topics[] parameter
    selectedTopicIds.forEach(topicId => {
        params.append('topics[]', topicId);
    });
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            window.location.reload();
        } else {
            alert('Error: ' + (result.data || 'Failed to update Topics'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating Topics.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Handle Course form submission
function handleCourseSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        action: 'update_course',
        profile_id: formData.get('profile_id'),
        course_id: formData.get('course_id'),
        course_title: formData.get('course_title'),
        course_description: formData.get('course_description'),
        course_url: formData.get('course_url'),
        _wpnonce: formData.get('_wpnonce')
    };
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
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
            alert('Error: ' + (result.data || 'Failed to save Course'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving Course.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Handle social link edit form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('scn-social-link-edit-form');
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            handleSocialLinkEditSubmit(event);
        });
    }
    
    // Platform change handler
    const platformSelect = document.getElementById('social-link-platform');
    if (platformSelect) {
        platformSelect.addEventListener('change', toggleCustomLabelField);
    }
});

function handleSocialLinkEditSubmit(event) {
    const form = event.target;
    const formData = new FormData(form);
    const platform = formData.get('platform');
    const url = formData.get('url');
    const label = formData.get('label') || getDefaultLabel(platform);
    const currentPlatform = form.getAttribute('data-current-platform');
    
    // Update or add social link
    if (currentPlatform) {
        // Update existing link
        updateSocialLinkItem(currentPlatform, platform, url, label);
    } else {
        // Add new link
        addSocialLinkItem(platform, url, label);
    }
    
    closeSocialLinkEditModal();
}

function getDefaultLabel(platform) {
    const labels = {
        'linkedin': 'LinkedIn',
        'twitter': 'Twitter / X',
        'facebook': 'Facebook',
        'instagram': 'Instagram',
        'youtube': 'YouTube',
        'website': 'Website'
    };
    return labels[platform] || platform;
}

function updateSocialLinkItem(oldPlatform, newPlatform, url, label) {
    const item = document.querySelector(`[data-platform="${oldPlatform}"]`);
    if (item) {
        item.setAttribute('data-platform', newPlatform);
        item.querySelector('.scn-social-link-label').textContent = label;
        item.querySelector('.scn-social-link-url').textContent = url || 'Not set';
        
        // Update icon
        const iconContainer = item.querySelector('.scn-social-link-icon');
        iconContainer.innerHTML = getSocialIconSVG(newPlatform);
        
        // Update onclick handlers
        const editBtn = item.querySelector('.scn-edit-link-btn');
        const deleteBtn = item.querySelector('.scn-delete-link-btn');
        editBtn.setAttribute('onclick', `editSocialLink('${newPlatform}', '${url}', '${label}')`);
        deleteBtn.setAttribute('onclick', `deleteSocialLink('${newPlatform}')`);
    }
}

function addSocialLinkItem(platform, url, label) {
    const container = document.getElementById('scn-social-links-container');
    const order = container.children.length;
    
    const itemHTML = `
        <div class="scn-social-link-item" data-platform="${platform}" data-order="${order}">
            <div class="scn-social-link-handle">
                <svg style="width: 16px; height: 16px; color: #999;" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 15h18v-2H3v2zm0 4h18v-2H3v2zm0-8h18V9H3v2zm0-6v2h18V5H3z"/>
                </svg>
            </div>
            <div class="scn-social-link-content">
                <div class="scn-social-link-icon">
                    ${getSocialIconSVG(platform)}
                </div>
                <div class="scn-social-link-info">
                    <div class="scn-social-link-label">${label}</div>
                    <div class="scn-social-link-url">${url || 'Not set'}</div>
                </div>
            </div>
            <div class="scn-social-link-actions">
                <button type="button" class="scn-edit-link-btn" onclick="editSocialLink('${platform}', '${url}', '${label}')">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
                    </svg>
                </button>
                <button type="button" class="scn-delete-link-btn" onclick="deleteSocialLink('${platform}')">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', itemHTML);
    updateSocialLinksOrder();
    
    // Re-initialize drag and drop for the new item
    initSocialLinksSortable();
}

function getSocialIconSVG(platform) {
    const icons = {
        'linkedin': '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'twitter': '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>',
        'facebook': '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'instagram': '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm4.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
        'youtube': '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        'website': '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>',
        'custom': '<svg class="scn-social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>'
    };
    return icons[platform] || icons['custom'];
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

// Course Accordion Functionality
document.addEventListener('DOMContentLoaded', function() {
    const accordionItems = document.querySelectorAll('.scn-course-accordion-item');
    
    accordionItems.forEach(item => {
        const header = item.querySelector('.scn-course-accordion-header');
        const toggle = item.querySelector('.scn-course-accordion-toggle');
        
        // Handle click on header or toggle button
        const handleToggle = (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Close other items (optional - remove if you want multiple items open)
            accordionItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.scn-course-accordion-toggle').setAttribute('aria-expanded', 'false');
                }
            });
            
            // Toggle current item
            const isActive = item.classList.toggle('active');
            toggle.setAttribute('aria-expanded', isActive ? 'true' : 'false');
        };
        
        header.addEventListener('click', handleToggle);
        toggle.addEventListener('click', handleToggle);
    });
});
</script>

<div class="scn-profile-page-wrapper">
<div class="scn-profile-container">
    <!-- Debug Info (remove after fixing) -->
    <?php if (current_user_can('administrator')): ?>
    <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; font-size: 12px;">
        <strong>Debug Info:</strong><br>
        Is Own Profile: <?php echo $is_own_profile ? 'TRUE' : 'FALSE'; ?><br>
        Current User ID: <?php echo get_current_user_id(); ?><br>
        Member User ID: <?php echo $member_user_id; ?><br>
        Profile Post Author: <?php echo $profile->post_author; ?><br>
        Profile ID: <?php echo $profile_id; ?>
    </div>
    <?php endif; ?>
    
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
            
            <?php // Always show edit icon for owner, even if there are no social links ?>
            <?php if ($is_own_profile && empty($social_links)): ?>
                <div class="scn-social-icons-wrapper">
                    <span class="scn-edit-social-icon" title="Edit Social Links" onclick="openSocialLinksModal()">
                        <svg class="scn-edit-icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z" fill="#FFD700"/>
                        </svg>
                    </span>
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
                        <?php 
                        $host = parse_url($main_url, PHP_URL_HOST);
                        echo esc_html($host ? 'www.' . $host : $main_url);
                        ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($social_links)): ?>
                <div class="scn-social-icons-wrapper">
                <div class="scn-social-icons">
                    <?php 
                    // Display links in order
                    foreach ($social_links as $link): 
                        $platform = $link['platform'] ?? 'custom';
                        $url = $link['url'] ?? '';
                        $label = $link['label'] ?? ucfirst($platform);
                        
                        // Skip if URL is empty or matches main_url
                        if (empty($url) || $url === $main_url) continue;
                    ?>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" class="scn-social-icon-link" title="<?php echo esc_attr($label); ?>">
                                <?php echo scn_get_social_icon_svg($platform); ?>
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
                <h3>Quick Facts
                    <?php if ($is_own_profile): ?>
                        <span class="scn-edit-icon" title="Edit Quick Facts" onclick="openQuickFactsModal()" style="cursor: pointer; margin-left: 10px;">
                            <svg class="scn-edit-icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; vertical-align: middle;">
                                <path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z" fill="#FFD700"></path>
                            </svg>
                        </span>
                    <?php endif; ?>
                </h3>
                <div class="scn-facts-list">
                    <?php 
                    // First, show location if available
                    if (!empty($location)): ?>
                        <div class="scn-fact-item">
                            <svg class="scn-fact-icon" viewBox="0 0 24 24" fill="currentColor" style="width: 20px; height: 20px; flex-shrink: 0; color: #6b7280;">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path>
                            </svg>
                            <div class="scn-fact-content">
                                <div class="scn-fact-value"><?php echo esc_html($location); ?></div>
                            </div>
                        </div>
                    <?php endif;
                    
                    // Then show quick facts
                    $quick_facts = get_field('quick_facts', $profile_id) ?: [];
                    
                    // Only show facts that have content
                    $filled_facts = array_filter($quick_facts, function($fact) {
                        return !empty($fact['title']);
                    });
                    
                    // Sort by order
                    usort($filled_facts, function($a, $b) {
                        return ($a['order'] ?? 0) - ($b['order'] ?? 0);
                    });
                    
                    foreach ($filled_facts as $index => $fact): 
                        $title = $fact['title'] ?? '';
                        $description = $fact['description'] ?? '';
                        $year = $fact['year'] ?? '';
                        
                        // Build display text combining all fields
                        $display_text = $title;
                        if (!empty($description)) {
                            $display_text .= ' - ' . $description;
                        }
                        if (!empty($year)) {
                            $display_text .= ' (' . $year . ')';
                        }
                    ?>
                        <div class="scn-fact-item">
                            <svg class="scn-fact-icon" viewBox="0 0 24 24" fill="currentColor" style="width: 20px; height: 20px; flex-shrink: 0; color: #6b7280;">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <div class="scn-fact-content">
                            <div class="scn-fact-value"><?php echo wp_kses($display_text, ['b' => [], 'strong' => [], 'em' => [], 'i' => []]); ?></div>
                                </div>
                            </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Topics Section -->
                <div class="scn-profile-section">
                    <h3>Topics
                        <?php if ($is_own_profile): ?>
                            <span class="scn-edit-icon" title="Edit Topics" onclick="openTopicsModal()" style="cursor: pointer; margin-left: 10px;">
                                <svg class="scn-edit-icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; vertical-align: middle;">
                                    <path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z" fill="#FFD700"></path>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </h3>
                    <div class="scn-topics-list">
                        <?php 
                        // Get saved topics for display - try multiple methods like in modal
                        $display_saved_topics = get_field('topics', $profile_id) ?: [];
                        $display_topic_ids = [];
                        
                        // Method 1: Direct ACF field
                        if (is_array($display_saved_topics)) {
                            $display_topic_ids = array_map('intval', $display_saved_topics);
                        }
                        
                        // Method 2: If ACF field is empty, try taxonomy terms
                        if (empty($display_topic_ids)) {
                            $display_taxonomy_terms = wp_get_post_terms($profile_id, 'scn_topic', array('fields' => 'ids'));
                            if (!is_wp_error($display_taxonomy_terms)) {
                                $display_topic_ids = array_map('intval', $display_taxonomy_terms);
                            }
                        }
                        
                        // Method 3: Try field_topics
                        if (empty($display_topic_ids)) {
                            $display_field_topics = get_field('field_topics', $profile_id) ?: [];
                            if (is_array($display_field_topics)) {
                                $display_topic_ids = array_map('intval', $display_field_topics);
                            }
                        }
                        
                        // Get topic names from IDs
                        if (!empty($display_topic_ids)) {
                            $topic_terms = get_terms(array(
                                'taxonomy' => 'scn_topic',
                                'include' => $display_topic_ids,
                                'hide_empty' => false
                            ));
                            
                            if (!empty($topic_terms) && !is_wp_error($topic_terms)) {
                                foreach ($topic_terms as $topic) {
                                    echo '<span class="scn-topic-tag">' . esc_html($topic->name) . '</span>';
                                }
                            }
                        }
                        
                        // Fallback to hardcoded topics if no saved topics found
                        if (empty($display_topic_ids)) {
                            echo '<span class="scn-topic-tag">Bruxism Management</span>';
                            echo '<span class="scn-topic-tag">Ceramic Crowns</span>';
                            echo '<span class="scn-topic-tag">Dental Fillings</span>';
                        }
                        ?>
                    </div>
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
                            <?php 
                                    $image_url = wp_get_attachment_image_url($image_id, 'medium');
                                // Get alt text
                                $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                                if (!$image_alt) {
                                    $image_alt = 'Gallery Photo of ' . $first_name . ' ' . $last_name;
                                    if ($credentials) {
                                        $image_alt .= ', ' . $credentials;
                                    }
                                }
                            ?>
                            <?php if ($image_url): ?>
                                <div class="scn-gallery-item">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>">
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
        <?php
        // Debug: Log that we're entering the courses section
        error_log('SCN: ENTERING COURSES SECTION - Profile ID: ' . $profile_id);
        echo '<!-- DEBUG: Courses section is loading in member-profile.php -->';
        echo '<div style="background: yellow; padding: 10px; margin: 10px 0;">DEBUG: Courses section is being processed</div>';
        
        // Get member's user ID
        $member_user_id = get_post_meta($profile_id, 'scn_user_id', true);
        error_log('SCN: Member user ID: ' . $member_user_id);
        
        // Force fresh load of courses field - bypass ALL caches
        wp_cache_delete($profile_id, 'post_meta');
        if (function_exists('acf_get_store')) {
            acf_get_store('values')->remove($profile_id);
        }
        
        // Get courses from ACF repeater field
        $courses_repeater = get_field('courses', $profile_id, false);
        error_log('SCN: Courses repeater field result: ' . print_r($courses_repeater, true));
        
        // Fallback to member_courses if courses doesn't exist
        if (!$courses_repeater) {
            $courses_repeater = get_field('member_courses', $profile_id, false);
            error_log('SCN: Member courses field result: ' . print_r($courses_repeater, true));
        }
        
        $member_courses = [];
        
        // Handle repeater field structure
        if ($courses_repeater !== false && $courses_repeater !== null && $courses_repeater !== '' && is_array($courses_repeater) && !empty($courses_repeater)) {
            // Extract course IDs from repeater structure
            $course_ids = [];
            foreach ($courses_repeater as $course_row) {
                if (is_array($course_row) && isset($course_row['course'])) {
                    $course_id = $course_row['course'];
                    
                    // Only include actual course post types
                    if (get_post_type($course_id) === 'course') {
                    $course_ids[] = $course_id;
                    }
                }
            }
            
            if (!empty($course_ids)) {
                $member_courses = get_posts([
                    'post_type' => 'course',
                    'post__in' => $course_ids,
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'orderby' => 'post__in',
                ]);
            }
        } elseif ($courses_repeater === false || $courses_repeater === null || $courses_repeater === '') {
            // Field never been set - fallback to courses by author
            error_log('SCN: Courses repeater is empty, trying author fallback');
            if ($member_user_id) {
                error_log('SCN: Searching for courses by author ID: ' . $member_user_id);
                $member_courses = get_posts([
                    'post_type' => 'course',
                    'author' => $member_user_id,
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                error_log('SCN: Found ' . count($member_courses) . ' courses by author');
            } else {
                error_log('SCN: No member user ID found');
            }
        }
        // If $course_ids is an empty array, field was set but cleared - show no courses
        
        if (!empty($member_courses)):
            $current_user_id = get_current_user_id();
            $is_own_profile = ($current_user_id && $current_user_id == $member_user_id);
        ?>
            <div class="scn-courses-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Courses</h3>
                        <?php if ($is_own_profile): ?>
                    <button class="scn-add-course-btn" onclick="openCourseModal('add')" style="background: #0073aa; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                        <span class="dashicons dashicons-plus"></span> Add Course
                    </button>
                                    <?php endif; ?>
                                </div>
            <div class="scn-courses-list">
                    <?php foreach ($member_courses as $index => $course):
                        $course_id = $course->ID;
                        $subtitle = get_field('scn_course_subtitle', $course_id);
                        $description = get_field('scn_course_description', $course_id);
                        $ce_enabled = get_field('scn_course_ce_enabled', $course_id);
                        $ce_hours = get_field('scn_course_ce_hours', $course_id);
                        $formats = get_field('scn_course_formats', $course_id);
                        $outcomes = get_field('scn_course_outcomes', $course_id);
                        $thumbnail_id = get_post_thumbnail_id($course_id);
                        
                        if (!$thumbnail_id) {
                            $default_image = get_posts([
                                'post_type' => 'attachment',
                                'meta_key' => 'scn_default_course_image',
                                'meta_value' => '1',
                                'posts_per_page' => 1
                            ]);
                            $thumbnail_id = !empty($default_image) ? $default_image[0]->ID : null;
                        }
                        
                        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : '';
                        $topics = wp_get_post_terms($course_id, 'scn_topic', ['fields' => 'names']);
                    ?>
                    <div class="scn-course-accordion-item" data-course-id="<?php echo $course_id; ?>">
                        <div class="scn-course-accordion-header">
                            <div class="scn-course-header-left">
                                    <?php if ($thumbnail_url): ?>
                                    <div class="scn-course-thumbnail">
                                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($course->post_title); ?>">
                                </div>
                                <?php endif; ?>
                                <div class="scn-course-header-info">
                                    <h3 class="scn-course-accordion-title"><?php echo esc_html($course->post_title); ?></h3>
                                            <?php if ($subtitle): ?>
                                        <p class="scn-course-accordion-subtitle"><?php echo esc_html($subtitle); ?></p>
                                            <?php endif; ?>
                                    <div class="scn-course-header-meta">
                                        <?php if ($ce_enabled && $ce_hours): ?>
                                            <span class="scn-course-badge scn-badge-ce">
                                                <span class="dashicons dashicons-awards"></span>
                                                <?php echo $ce_hours; ?> CE Hours
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($formats) && is_array($formats)): ?>
                                            <?php foreach ($formats as $format): ?>
                                                <span class="scn-course-badge scn-badge-format">
                                                    <span class="dashicons dashicons-<?php echo $format === 'live' ? 'video-alt3' : 'video-alt2'; ?>"></span>
                                                    <?php echo ucfirst($format); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if (!empty($topics) && !is_wp_error($topics)): ?>
                                            <?php foreach (array_slice($topics, 0, 2) as $topic): ?>
                                                <span class="scn-course-badge scn-badge-topic"><?php echo esc_html($topic); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($topics) > 2): ?>
                                                <span class="scn-course-badge scn-badge-more">+<?php echo count($topics) - 2; ?> more</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <button class="scn-course-accordion-toggle" aria-expanded="false">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                            </div>
                            
                        <div class="scn-course-accordion-content">
                            <div class="scn-course-content-inner">
                                <?php if ($description): ?>
                                    <div class="scn-course-section">
                                        <h4><span class="dashicons dashicons-info"></span> Description</h4>
                                        <p><?php echo esc_html($description); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($course->post_content): ?>
                                    <div class="scn-course-section scn-course-full-content">
                                        <h4><span class="dashicons dashicons-text-page"></span> Course Content</h4>
                                        <div class="scn-course-content-text">
                                            <?php echo apply_filters('the_content', $course->post_content); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($outcomes) && is_array($outcomes)): ?>
                                    <div class="scn-course-section">
                                        <h4><span class="dashicons dashicons-yes-alt"></span> Learning Outcomes</h4>
                                        <ul class="scn-course-outcomes-list">
                                            <?php foreach ($outcomes as $outcome): ?>
                                                <?php if (!empty($outcome)): ?>
                                                    <li><?php echo esc_html($outcome); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($topics) && !is_wp_error($topics)): ?>
                                    <div class="scn-course-section">
                                        <h4><span class="dashicons dashicons-tag"></span> Topics Covered</h4>
                                        <div class="scn-course-topics-list">
                                            <?php foreach ($topics as $topic): ?>
                                                <span class="scn-topic-pill"><?php echo esc_html($topic); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="scn-course-actions">
                                    <a href="<?php echo get_permalink($course_id); ?>" class="scn-btn scn-btn-primary">
                                        <span class="dashicons dashicons-welcome-learn-more"></span>
                                        View Full Course
                                    </a>
                            </div>
                        </div>
                </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="scn-profile-section scn-empty-state">
                <span class="dashicons dashicons-welcome-learn-more"></span>
                <p>No courses created yet.</p>
            </div>
        <?php endif; ?>
    </div>
    <!-- End Courses Tab -->
    
    <!-- Tab Content: Events -->
    <div class="scn-tab-content" data-content="events">
        <?php
        // Get events for this member
        $member_user_id = get_post_meta($profile_id, 'scn_user_id', true);
        $events = get_posts([
            'post_type' => 'event',
            'author' => $member_user_id,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'meta_value',
            'meta_key' => 'scn_event_start_date',
            'order' => 'DESC'
        ]);
        
        if (!empty($events)):
        ?>
            <div class="scn-events-content">
                <?php foreach ($events as $event): 
                            $event_id = $event->ID;
                    $event_title = $event->post_title;
                    $event_description = $event->post_content;
                    $start_date = get_field('scn_event_start_date', $event_id);
                    $end_date = get_field('scn_event_end_date', $event_id);
                    $location = get_field('scn_event_location', $event_id);
                    $website = get_field('scn_event_website', $event_id);
                ?>
                    <div class="scn-event-item" style="margin-bottom: 30px; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div class="scn-event-header">
                            <h3 style="margin: 0 0 10px 0; color: #2c3e50;">
                                <?php echo esc_html($event_title); ?>
                            </h3>
                            <div class="scn-event-meta" style="display: flex; gap: 20px; margin-bottom: 15px; color: #6b7280; font-size: 14px;">
                                    <?php if ($start_date): ?>
                                    <span><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html(date('F j, Y', strtotime($start_date))); ?></span>
                                    <?php endif; ?>
                                <?php if ($location): ?>
                                    <span><span class="dashicons dashicons-location"></span> <?php echo esc_html($location); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        
                        <?php if ($event_description): ?>
                            <div class="scn-event-description" style="margin-bottom: 15px; color: #374151; line-height: 1.6;">
                                <?php echo wp_trim_words($event_description, 50); ?>
                </div>
                        <?php endif; ?>
                                    
                                    <?php
                                    // Get courses from event repeater field
                                    $event_courses = get_field('courses', $event_id, false);
                                    
                                    if ($event_courses && is_array($event_courses) && !empty($event_courses)) {
                                        $course_ids = [];
                                        foreach ($event_courses as $course_row) {
                                            if (is_array($course_row) && isset($course_row['course'])) {
                                                $course_id = $course_row['course'];
                                    if (get_post_type($course_id) === 'course') {
                                                    $course_ids[] = $course_id;
                                                }
                                            }
                                        }
                                        
                                        if (!empty($course_ids)) {
                                            $courses = get_posts([
                                                'post_type' => 'course',
                                                'post__in' => $course_ids,
                                                'posts_per_page' => -1,
                                                'post_status' => 'publish',
                                                'orderby' => 'post__in',
                                            ]);
                                            
                                            if (!empty($courses)) {
                                    ?>
                                        <div class="scn-event-courses" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                        <h4 style="margin: 0 0 15px 0; color: #374151; font-size: 16px;">
                                                <span class="dashicons dashicons-book-alt"></span> 
                                                Courses at this Event
                                            </h4>
                                        <div class="scn-event-courses-list" style="display: grid; gap: 10px;">
                                                <?php foreach ($courses as $course): ?>
                                                <div class="scn-event-course-item" style="padding: 12px; background: #f9fafb; border-radius: 6px; border-left: 3px solid #0073aa;">
                                                    <strong style="color: #111827; display: block; margin-bottom: 5px;">
                                                        <?php echo esc_html($course->post_title); ?>
                                                    </strong>
                                                        <?php 
                                                        $course_description = get_field('scn_course_description', $course->ID);
                                                        if ($course_description): ?>
                                                        <div style="font-size: 14px; color: #6b7280; line-height: 1.5;">
                                                                <?php echo esc_html(wp_trim_words($course_description, 20)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php
                                            }
                                        }
                                    }
                                    ?>
                        
                        <?php if ($website): ?>
                            <div class="scn-event-actions" style="margin-top: 15px;">
                                <a href="<?php echo esc_url($website); ?>" target="_blank" class="scn-btn scn-btn-primary" style="display: inline-flex; align-items: center; gap: 5px;">
                                    <span class="dashicons dashicons-admin-site"></span>
                                    Visit Event Website
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="scn-profile-section scn-empty-state">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p>No events created yet.</p>
                    </div>
                <?php endif; ?>
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
        <div class="scn-modal-content" style="max-width: 800px;">
            <div class="scn-modal-header">
                <h2>Edit Social Links</h2>
                <button type="button" class="scn-modal-close" onclick="closeSocialLinksModal()">&times;</button>
            </div>
            
            <div class="scn-modal-body">
                <p style="margin-bottom: 20px; color: #6c757d;">Drag and drop to reorder your social links. Click the edit icon to modify or delete links.</p>
                
                <!-- Social Links Sortable Container -->
                <div id="scn-social-links-container" class="scn-social-links-sortable">
                    <?php 
                    // Load ONLY from ACF repeater used for social networks
                    $social_links_raw = get_field('social_links', $profile_id) ?: [];
                    
                    // Convert old format to new format if needed
                    $social_links = [];
                    if (is_array($social_links_raw) && !empty($social_links_raw)) {
                        // Normalize ACF repeater rows
                        $order = 0;
                        foreach ($social_links_raw as $row) {
                            $url = $row['url'] ?? '';
                            if (empty($url)) { continue; }
                            $platform = $row['platform'] ?? detectSocialPlatform($url);
                            $label = $row['label'] ?? ucfirst($platform);
                            $social_links[] = [
                                'platform' => $platform,
                                'url' => $url,
                                'label' => $label,
                                'order' => $row['order'] ?? $order++,
                            ];
                        }
                    }
                    
                    // Only show links that have URLs
                    $filled_links = array_filter($social_links, function($link) {
                        return !empty($link['url']);
                    });
                    
                    // Sort by order
                    usort($filled_links, function($a, $b) {
                        return ($a['order'] ?? 0) - ($b['order'] ?? 0);
                    });
                    
                    if (empty($filled_links)) {
                        echo '<div class="scn-empty-state" style="text-align: center; padding: 40px 20px; color: #999; font-style: italic;">No social links added yet. Click "Add New Social Link" to get started.</div>';
                    } else {
                        foreach ($filled_links as $index => $link): 
                            $platform = $link['platform'] ?? '';
                            $url = $link['url'] ?? '';
                            $label = $link['label'] ?? ucfirst($platform);
                            $order = $link['order'] ?? $index;
                            
                            // Auto-detect platform from URL if not set
                            if (empty($platform)) {
                                $platform = detectSocialPlatform($url);
                            }
                        ?>
                        <div class="scn-social-link-item has-url" data-platform="<?php echo esc_attr($platform); ?>" data-order="<?php echo esc_attr($order); ?>">
                            <div class="scn-social-link-handle">
                                <svg style="width: 16px; height: 16px; color: #999;" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 15h18v-2H3v2zm0 4h18v-2H3v2zm0-8h18V9H3v2zm0-6v2h18V5H3z"/>
                        </svg>
                </div>
                            <div class="scn-social-link-content">
                                <div class="scn-social-link-icon">
                                    <?php echo scn_get_social_icon_svg($platform); ?>
                                </div>
                                <div class="scn-social-link-info">
                                    <div class="scn-social-link-label"><?php echo esc_html($label); ?></div>
                                    <div class="scn-social-link-url"><?php echo esc_html($url); ?></div>
                                </div>
                            </div>
                            <div class="scn-social-link-actions">
                                <button type="button" class="scn-edit-link-btn" onclick="editSocialLink('<?php echo esc_js($platform); ?>', '<?php echo esc_js($url); ?>', '<?php echo esc_js($label); ?>')">
                                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
                        </svg>
                                </button>
                                <button type="button" class="scn-delete-link-btn" onclick="deleteSocialLink('<?php echo esc_js($platform); ?>')">
                                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; 
                    } ?>
                </div>
                
                <!-- Add New Social Link Button -->
                <div style="margin-top: 20px; text-align: center;">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="addNewSocialLink()">
                        <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        Add New Social Link
                    </button>
                </div>
                </div>
                
            <div class="scn-modal-footer">
                <button type="button" class="scn-btn scn-btn-secondary" onclick="closeSocialLinksModal()">Cancel</button>
                <button type="button" class="scn-btn scn-btn-primary" onclick="saveSocialLinks()">Save Changes</button>
            </div>
        </div>
                </div>
                
    <!-- Social Link Edit Modal -->
    <div id="scnSocialLinkEditModal" class="scn-modal">
        <div class="scn-modal-content" style="max-width: 500px;">
            <div class="scn-modal-header">
                <h2>Edit Social Link</h2>
                <button type="button" class="scn-modal-close" onclick="closeSocialLinkEditModal()">&times;</button>
            </div>
            
            <form id="scn-social-link-edit-form" class="scn-modal-form">
                <div class="scn-form-group">
                    <label for="social-link-platform">Platform</label>
                    <select id="social-link-platform" name="platform" required>
                        <option value="linkedin">LinkedIn</option>
                        <option value="twitter">Twitter / X</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="youtube">YouTube</option>
                        <option value="website">Website</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                
                <div class="scn-form-group" id="custom-label-group" style="display: none;">
                    <label for="social-link-label">Custom Label</label>
                    <input type="text" id="social-link-label" name="label" placeholder="Enter custom label">
                </div>
                
                <div class="scn-form-group">
                    <label for="social-link-url">URL</label>
                    <input type="url" id="social-link-url" name="url" placeholder="https://example.com" required>
                </div>
                
                <div class="scn-modal-footer">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="closeSocialLinkEditModal()">Cancel</button>
                    <button type="submit" class="scn-btn scn-btn-primary">Save Link</button>
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
    
    <!-- Quick Facts Edit Modal -->
    <?php if ($is_own_profile): ?>
    <div id="scnQuickFactsModal" class="scn-modal">
        <div class="scn-modal-content">
            <div class="scn-modal-header">
                <h2>Quick Facts</h2>
                <button type="button" class="scn-modal-close" onclick="closeQuickFactsModal()">&times;</button>
            </div>
            
            <form class="scn-modal-form" onsubmit="handleQuickFactsSubmit(event)">
                <?php wp_nonce_field('update_quick_facts', '_wpnonce'); ?>
                <input type="hidden" name="profile_id" value="<?php echo esc_attr($profile_id); ?>">
            
            <div class="scn-modal-body">
                <p style="margin-bottom: 20px; color: #6c757d;">Drag and drop to reorder your quick facts. Click the edit icon to modify or delete facts.</p>
                
                <!-- Quick Facts Sortable Container -->
                <div id="scn-quick-facts-container" class="scn-quick-facts-sortable">
                    <?php 
                    $quick_facts = get_field('quick_facts', $profile_id) ?: [];
                    
                    // Only show facts that have content
                    $filled_facts = array_filter($quick_facts, function($fact) {
                        return !empty($fact['title']);
                    });
                    
                    // Sort by order
                    usort($filled_facts, function($a, $b) {
                        return ($a['order'] ?? 0) - ($b['order'] ?? 0);
                    });
                    
                    if (empty($filled_facts)) {
                        echo '<div class="scn-empty-state" style="text-align: center; padding: 40px 20px; color: #999; font-style: italic;">No quick facts added yet. Click "Add New Quick Fact" to get started.</div>';
                    } else {
                        foreach ($filled_facts as $index => $fact): 
                            $title = $fact['title'] ?? '';
                            $description = $fact['description'] ?? '';
                            $year = $fact['year'] ?? '';
                            $order = $fact['order'] ?? $index;
                            
                            // Build display text combining all fields
                            $display_text = $title;
                            if (!empty($description)) {
                                $display_text .= ' - ' . $description;
                            }
                            if (!empty($year)) {
                                $display_text .= ' (' . $year . ')';
                            }
                        ?>
                        <div class="scn-quick-fact-item" data-order="<?php echo esc_attr($order); ?>">
                            <div class="scn-quick-fact-handle">
                                <svg style="width: 16px; height: 16px; color: #999;" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 15h18v-2H3v2zm0 4h18v-2H3v2zm0-8h18V9H3v2zm0-6v2h18V5H3z"/>
                                </svg>
                            </div>
                            <div class="scn-quick-fact-content">
                                <div class="scn-quick-fact-fields">
                                    <div class="scn-field-group">
                                        <label>Title</label>
                                        <input type="text" name="quick_facts[<?php echo $index; ?>][title]" value="<?php echo esc_attr($title); ?>" placeholder="e.g., PhD">
                                    </div>
                                    <div class="scn-field-group">
                                        <label>Description</label>
                                        <input type="text" name="quick_facts[<?php echo $index; ?>][description]" value="<?php echo esc_attr($description); ?>" placeholder="e.g., University of the East">
                                    </div>
                                    <div class="scn-field-group">
                                        <label>Year</label>
                                        <input type="text" name="quick_facts[<?php echo $index; ?>][year]" value="<?php echo esc_attr($year); ?>" placeholder="e.g., 2001">
                                    </div>
                                    <input type="hidden" name="quick_facts[<?php echo $index; ?>][order]" value="<?php echo esc_attr($order); ?>">
                                </div>
                            </div>
                            <div class="scn-quick-fact-actions">
                                <button type="button" class="scn-delete-fact-btn" onclick="this.closest('.scn-quick-fact-item').remove()">
                                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; 
                    } ?>
                </div>
                
                <!-- Add New Quick Fact Button -->
                <div style="margin-top: 20px; text-align: center;">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="addNewQuickFact()">
                        <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        Add New Quick Fact
                    </button>
                </div>
                    
                    <!-- Test and Save Buttons -->
                    <div style="margin-top: 30px; text-align: center; padding-top: 20px; border-top: 1px solid #e9ecef;">
                        <button type="button" class="scn-btn scn-btn-secondary" onclick="testQuickFactsAjax()" style="margin-right: 10px;">
                            Test AJAX
                        </button>
                        <button type="submit" class="scn-btn scn-btn-primary">
                            <svg style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                            </svg>
                            Save Quick Facts
                        </button>
            </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Topics Edit Modal -->
    <?php if ($is_own_profile): ?>
    <div id="scnTopicsModal" class="scn-modal">
        <div class="scn-modal-content">
            <div class="scn-modal-header">
                <h2>Edit Topics</h2>
                <button type="button" class="scn-modal-close" onclick="closeTopicsModal()">&times;</button>
            </div>
            
            <form class="scn-modal-form" onsubmit="handleTopicsSubmit(event)">
                <?php wp_nonce_field('update_topics', '_wpnonce'); ?>
                <input type="hidden" name="profile_id" value="<?php echo esc_attr($profile_id); ?>">
                
                <div class="scn-modal-body">
                    <p style="margin-bottom: 20px; color: #6c757d;">Select topics that apply to your expertise. Use the search box to filter available topics.</p>
                    
                    <!-- Search Box -->
                <div class="scn-form-group">
                        <label for="topic-search">Search Topics</label>
                        <input type="text" id="topic-search" placeholder="Type to search topics..." onkeyup="filterTopics(this.value)">
                    </div>
                    
                    <!-- Topics Checkboxes Container -->
                    <div id="topics-container" class="scn-topics-checkbox-container">
                        <?php 
                        // Get all available topics from taxonomy
                        $all_topics = get_terms(array(
                            'taxonomy' => 'scn_topic',
                            'hide_empty' => false,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ));
                        
                        // Get saved topics for this profile - try multiple methods
                        $saved_topics = get_field('topics', $profile_id) ?: [];
                        $saved_topic_ids = [];
                        
                        // Method 1: Direct ACF field
                        if (is_array($saved_topics)) {
                            $saved_topic_ids = array_map('intval', $saved_topics);
                        }
                        
                        // Method 2: If ACF field is empty, try taxonomy terms
                        if (empty($saved_topic_ids)) {
                            $taxonomy_terms = wp_get_post_terms($profile_id, 'scn_topic', array('fields' => 'ids'));
                            if (!is_wp_error($taxonomy_terms)) {
                                $saved_topic_ids = array_map('intval', $taxonomy_terms);
                            }
                        }
                        
                        // Method 3: Try field_topics (if that's where it's actually saved)
                        if (empty($saved_topic_ids)) {
                            $field_topics = get_field('field_topics', $profile_id) ?: [];
                            if (is_array($field_topics)) {
                                $saved_topic_ids = array_map('intval', $field_topics);
                            }
                        }
                        
                        
                        if (!empty($all_topics) && !is_wp_error($all_topics)) {
                            foreach ($all_topics as $topic) {
                                $topic_name = $topic->name;
                                $topic_id = $topic->term_id;
                                $is_checked = in_array($topic_id, $saved_topic_ids);
                        ?>
                        <div class="scn-topic-checkbox-item" data-topic-name="<?php echo esc_attr(strtolower($topic_name)); ?>" data-topic-id="<?php echo esc_attr($topic_id); ?>">
                            <label class="scn-topic-checkbox-label">
                                <input type="checkbox" 
                                       name="topics[]" 
                                       value="<?php echo esc_attr($topic_id); ?>"
                                       <?php echo $is_checked ? 'checked' : ''; ?>>
                                <span class="scn-topic-name"><?php echo esc_html($topic_name); ?></span>
                            </label>
                        </div>
                        <?php 
                            }
                        } else {
                            echo '<p>No topics available. Please contact an administrator to add topics.</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="scn-form-actions">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="closeTopicsModal()">Cancel</button>
                    <button type="submit" class="scn-btn scn-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Course Edit Modal -->
    <?php if ($is_own_profile): ?>
    <div id="scnCourseModal" class="scn-modal">
        <div class="scn-modal-content">
            <div class="scn-modal-header">
                <h2 id="scnCourseModalTitle">Add Course</h2>
                <button type="button" class="scn-modal-close" onclick="closeCourseModal()">&times;</button>
            </div>
            
            <form class="scn-modal-form" onsubmit="handleCourseSubmit(event)">
                <?php wp_nonce_field('update_course', '_wpnonce'); ?>
                <input type="hidden" name="profile_id" value="<?php echo esc_attr($profile_id); ?>">
                <input type="hidden" name="course_id" id="course_id" value="">
                
                <div class="scn-form-group">
                    <label for="course_title">Course Title *</label>
                    <input type="text" id="course_title" name="course_title" required placeholder="Enter course title">
                </div>
                
                <div class="scn-form-group">
                    <label for="course_description">Description</label>
                    <textarea id="course_description" name="course_description" rows="4" placeholder="Describe the course..."></textarea>
                </div>
                
                <div class="scn-form-group">
                    <label for="course_url">Course URL</label>
                    <input type="url" id="course_url" name="course_url" placeholder="https://example.com/course">
                </div>
                
                <div class="scn-form-actions">
                    <button type="button" class="scn-btn scn-btn-secondary" onclick="closeCourseModal()">Cancel</button>
                    <button type="submit" class="scn-btn scn-btn-primary">Save Course</button>
                </div>
            </form>
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
