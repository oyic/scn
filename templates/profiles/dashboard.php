<?php
/**
 * Member Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if this is being rendered as a shortcode (no full HTML structure)
$is_shortcode = (ob_get_level() > 0);

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/member-dashboard/')));
    exit;
}

// Get current user's profile
$user_id = get_current_user_id();
$profile_posts = get_posts([
    'post_type' => 'profile',
    'meta_query' => [
        [
            'key' => 'scn_user_id',
            'value' => $user_id,
            'compare' => '='
        ]
    ],
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (empty($profile_posts)) {
    // No profile found, redirect to create one
    wp_redirect(admin_url('post-new.php?post_type=profile'));
    exit;
}

$profile = $profile_posts[0];
$first_name = get_post_meta($profile->ID, 'scn_first_name', true);
$last_name = get_post_meta($profile->ID, 'scn_last_name', true);
$member_since = get_post_meta($profile->ID, 'member_since', true);

?>
<?php if (!$is_shortcode): ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($first_name . ' ' . $last_name . ' - SCN Dashboard'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php else: ?>
<style>
/* Hide theme header and footer when dashboard is rendered as shortcode */
.site-header,
header,
.header,
#header,
.site-footer,
footer,
.footer,
#footer,
nav,
.navigation,
.main-navigation,
.site-branding,
.site-title,
.site-description,
.entry-header,
.entry-footer,
.page-header,
.content-header,
.content-footer {
    display: none !important;
}

/* Hide any other common header/footer elements */
.wp-block-template-part,
.wp-block-group:has(.site-header),
.wp-block-group:has(.site-footer),
.wp-block-group:has(header),
.wp-block-group:has(footer) {
    display: none !important;
}

/* Ensure dashboard content takes full width */
.scn-dashboard-page {
    margin: 0 !important;
    padding: 0 !important;
}

/* Hide only specific theme elements, not the dashboard content */
.entry-header,
.page-header,
.content-header,
.entry-footer,
.content-footer {
    display: none !important;
}
</style>

<script>
// JavaScript to hide any remaining header/footer elements
document.addEventListener('DOMContentLoaded', function() {
    // Hide common header elements
    const headerSelectors = [
        'header', '.site-header', '#header', '.header',
        'nav', '.navigation', '.main-navigation',
        '.site-branding', '.site-title', '.site-description',
        '.entry-header', '.page-header', '.content-header'
    ];
    
    headerSelectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            if (el && !el.closest('.scn-dashboard-page')) {
                el.style.display = 'none';
            }
        });
    });
    
    // Hide common footer elements
    const footerSelectors = [
        'footer', '.site-footer', '#footer', '.footer',
        '.entry-footer', '.content-footer'
    ];
    
    footerSelectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            if (el && !el.closest('.scn-dashboard-page')) {
                el.style.display = 'none';
            }
        });
    });
    
    // Hide any elements that contain common header/footer text (but not dashboard content)
    const allElements = document.querySelectorAll('*');
    allElements.forEach(el => {
        if (el && !el.closest('.scn-dashboard-page') && !el.closest('.scn-dashboard-container')) {
            const text = el.textContent.toLowerCase();
            // Only hide if it's clearly a header/footer element, not content
            if ((text.includes('menu') || text.includes('navigation') || 
                text.includes('header') || text.includes('footer') ||
                text.includes('copyright') || text.includes('©')) &&
                (el.tagName === 'HEADER' || el.tagName === 'FOOTER' || 
                 el.tagName === 'NAV' || el.classList.contains('site-header') ||
                 el.classList.contains('site-footer') || el.classList.contains('navigation'))) {
                el.style.display = 'none';
            }
        }
    });
});
</script>
<?php endif; ?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f8f9fa;
    min-height: 100vh;
}

.scn-dashboard-page {
    background: #f8f9fa;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    width: 100%;
}

.scn-dashboard-container {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 20px;
}

.scn-dashboard-header {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.scn-dashboard-welcome {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
}

.scn-profile-image {
    flex-shrink: 0;
}

.scn-profile-image img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #ecf0f1;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.scn-welcome-content {
    flex: 1;
}

.scn-dashboard-welcome h1 {
    color: #2c3e50;
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 10px 0;
}

.scn-member-since {
    color: #7f8c8d;
    font-size: 16px;
    margin: 0;
}

.scn-dashboard-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.scn-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.scn-btn-primary {
    background: #3498db;
    color: white;
}

.scn-btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.scn-btn-secondary {
    background: #95a5a6;
    color: white;
}

.scn-btn-secondary:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(149, 165, 166, 0.3);
}

.scn-btn-logout {
    background: #e74c3c;
    color: white;
}

.scn-btn-logout:hover {
    background: #c0392b;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.scn-dashboard-content {
    display: block;
    width: 100%;
    margin-bottom: 30px;
}

.scn-dashboard-completion,
.scn-dashboard-courses,
.scn-dashboard-quick-actions {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    width: 100%;
    margin-bottom: 30px;
}

.scn-courses-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.scn-courses-header h2 {
    margin: 0;
    color: #2c3e50;
    font-size: 24px;
}

.scn-courses-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.scn-courses-section {
    margin-bottom: 40px;
}

.scn-courses-section h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 20px;
    font-weight: 600;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.scn-courses-table-wrapper {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.scn-courses-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.scn-courses-table thead th {
    background: #f6f7f7;
    border-bottom: 1px solid #c3c4c7;
    padding: 12px 8px;
    text-align: left;
    font-weight: 600;
    color: #1d2327;
    font-size: 14px;
    line-height: 1.4;
}

.scn-courses-table tbody td {
    padding: 12px 8px;
    border-bottom: 1px solid #c3c4c7;
    vertical-align: top;
    font-size: 14px;
    line-height: 1.4;
}

.scn-courses-table tbody tr:hover {
    background: #f6f7f7;
}

.scn-courses-table tbody tr:last-child td {
    border-bottom: none;
}

.scn-course-image {
    width: 60px;
    text-align: center;
}

.scn-course-image img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #c3c4c7;
}

.scn-course-image .scn-no-image {
    width: 50px;
    height: 50px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #8c8f94;
    font-size: 20px;
}

.scn-course-title {
    width: 30%;
    min-width: 200px;
}

.scn-course-title .scn-course-name {
    font-weight: 600;
    color: #1d2327;
    margin-bottom: 4px;
    display: block;
}

.scn-course-title .scn-course-subtitle {
    color: #646970;
    font-size: 13px;
    font-style: italic;
}

.scn-course-author {
    width: 15%;
    min-width: 120px;
    color: #646970;
}

.scn-course-topics {
    width: 20%;
    min-width: 150px;
}

.scn-course-topic-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.scn-course-topic-tag {
    background: #f0f0f1;
    color: #1d2327;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    border: 1px solid #c3c4c7;
}

.scn-course-status {
    width: 10%;
    min-width: 80px;
}

.scn-course-status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.scn-course-status-badge.status-publish {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.scn-course-status-badge.status-draft {
    background: #fff3cd;
    color: #664d03;
    border: 1px solid #ffecb5;
}

.scn-course-status-badge.status-pending {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c2c7;
}

.scn-course-status-badge.status-enrolled {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.scn-course-actions {
    width: 15%;
    min-width: 100px;
}

.scn-course-actions .scn-btn {
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 3px;
    text-decoration: none;
    display: inline-block;
    margin-right: 4px;
    border: 1px solid;
    transition: all 0.2s ease;
}

.scn-course-actions .scn-btn-primary {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

.scn-course-actions .scn-btn-primary:hover {
    background: #135e96;
    border-color: #135e96;
    color: #fff;
}

.scn-course-actions .scn-btn-secondary {
    background: #f6f7f7;
    color: #1d2327;
    border-color: #c3c4c7;
}

.scn-course-actions .scn-btn-secondary:hover {
    background: #f0f0f1;
    border-color: #8c8f94;
}

.scn-course-actions .scn-btn:disabled {
    background: #f6f7f7;
    color: #8c8f94;
    border-color: #c3c4c7;
    cursor: not-allowed;
    opacity: 0.6;
}

.scn-loading {
    text-align: center;
    padding: 20px;
    color: #646970;
    font-style: italic;
}

.scn-loading .dashicons {
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.scn-no-courses {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
    font-style: italic;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.scn-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

.scn-checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
    cursor: pointer;
}

.scn-checkbox-group input[type="checkbox"] {
    margin: 0;
}

.scn-outcome-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.scn-outcome-item input {
    flex: 1;
}

.scn-remove-outcome {
    background: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.scn-remove-outcome:hover {
    background: #c82333;
}

.scn-form-tabs {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 20px;
    gap: 0;
}

.scn-tab-btn {
    background: #f8f9fa;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
    font-weight: 500;
    color: #6c757d;
}

.scn-tab-btn:hover {
    background: #e9ecef;
    color: #495057;
}

.scn-tab-btn.active {
    background: #fff;
    color: #2c3e50;
    border-bottom-color: #007cba;
}

.scn-tab-content {
    display: none;
    animation: fadeIn 0.3s ease;
}

.scn-tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.scn-formats-grid,
.scn-topics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.scn-formats-grid label,
.scn-topics-grid label {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.scn-formats-grid label:hover,
.scn-topics-grid label:hover {
    background: #e9ecef;
    border-color: #007cba;
}

.scn-formats-grid input[type="checkbox"],
.scn-topics-grid input[type="checkbox"] {
    margin: 0;
    flex-shrink: 0;
}

.scn-formats-grid input[type="checkbox"]:checked + span,
.scn-topics-grid input[type="checkbox"]:checked + span {
    color: #007cba;
    font-weight: 600;
}

.scn-formats-grid label:has(input:checked),
.scn-topics-grid label:has(input:checked) {
    background: #e3f2fd;
    border-color: #007cba;
    color: #007cba;
}

.scn-topic-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    cursor: pointer;
}

.scn-topic-item:hover {
    background: #e9ecef;
    border-color: #007cba;
}

.scn-topic-item input[type="checkbox"] {
    margin: 0;
    flex-shrink: 0;
}

.scn-topic-item input[type="checkbox"]:checked + span {
    color: #007cba;
    font-weight: 600;
}

.scn-topic-item:has(input:checked) {
    background: #e3f2fd;
    border-color: #007cba;
    color: #007cba;
}

.scn-no-topics {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px dashed #dee2e6;
}

.scn-no-topics p {
    margin: 0;
    color: #6c757d;
}

.scn-no-topics a {
    color: #007cba;
    text-decoration: none;
    font-weight: 500;
}

.scn-no-topics a:hover {
    text-decoration: underline;
}

.scn-dashboard-completion h2,
.scn-dashboard-quick-actions h2 {
    color: #2c3e50;
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #ecf0f1;
}

.scn-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.scn-action-card {
    background: #f8f9fa;
    border: 2px solid #ecf0f1;
    border-radius: 12px;
    padding: 20px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.3s ease;
    display: block;
}

.scn-action-card:hover {
    border-color: #3498db;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.scn-action-card span {
    font-size: 24px;
    color: #3498db;
    margin-bottom: 10px;
    display: block;
}

.scn-action-card h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #2c3e50;
}

.scn-action-card p {
    font-size: 14px;
    color: #7f8c8d;
    margin: 0;
    line-height: 1.4;
}

.scn-loading {
    text-align: center;
    padding: 40px;
    color: #7f8c8d;
}

.scn-error-state {
    text-align: center;
    padding: 40px;
    color: #e74c3c;
}

.scn-retry-btn {
    background: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 15px;
}

@media (max-width: 768px) {
    .scn-dashboard-header {
        flex-direction: column;
        text-align: center;
    }
    
    .scn-dashboard-actions {
        justify-content: center;
    }
    
    .scn-dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="scn-dashboard-page">
    <div class="scn-dashboard-container" data-user-id="<?php echo esc_attr($user_id); ?>" data-profile-id="<?php echo esc_attr($profile->ID); ?>">
        <div class="scn-dashboard-header">
            <div class="scn-dashboard-welcome">
                <?php 
                $profile_image = get_the_post_thumbnail_url($profile->ID, 'medium');
                if ($profile_image): 
                ?>
                    <div class="scn-profile-image">
                        <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($first_name . ' ' . $last_name); ?>" />
                    </div>
                <?php endif; ?>
                <div class="scn-welcome-content">
                <h1><?php printf(__('Welcome back, %s!', 'scn-membership'), esc_html($first_name . ' ' . $last_name)); ?></h1>
                <?php if ($member_since): ?>
                    <p class="scn-member-since">
                        <?php printf(__('Member since %s', 'scn-membership'), date('F Y', strtotime($member_since))); ?>
                    </p>
                <?php endif; ?>
                </div>
            </div>
            
            <div class="scn-dashboard-actions">
               <button type="button" class="scn-btn scn-btn-primary scn-edit-profile-btn">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Edit Profile', 'scn-membership'); ?>
               </button>
                <a href="<?php echo esc_url(home_url('/members-profile/?profile_id=' . $profile->ID)); ?>" class="scn-btn scn-btn-secondary">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('View Profile', 'scn-membership'); ?>
                </a>
                <a href="<?php echo get_permalink(81); ?>?action=logout" class="scn-btn scn-btn-logout">
                    <span class="dashicons dashicons-exit"></span>
                    <?php _e('Logout', 'scn-membership'); ?>
                </a>
            </div>
        </div>

        <div class="scn-dashboard-content">

            <!-- Profile Completion Section -->
            <div class="scn-dashboard-completion">
                <h2><?php _e('Profile Completion', 'scn-membership'); ?></h2>
                <div id="scn-profile-completion">
                    <div class="scn-loading">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Loading profile stats...', 'scn-membership'); ?>
                    </div>
                </div>
            </div>

            <!-- Courses Section -->
            <div class="scn-dashboard-courses">
                <div class="scn-courses-section">
                    <div class="scn-courses-section-header">
                        <h3><?php _e('My Created Courses', 'scn-membership'); ?></h3>
                        <button type="button" class="scn-btn scn-btn-primary scn-add-course-btn">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Create Course', 'scn-membership'); ?>
                        </button>
                    </div>
                    <div class="scn-courses-table-wrapper">
                        <table class="scn-courses-table">
                            <thead>
                                <tr>
                                    <th class="scn-course-image"><?php _e('Image', 'scn-membership'); ?></th>
                                    <th class="scn-course-title"><?php _e('Course', 'scn-membership'); ?></th>
                                    <th class="scn-course-author"><?php _e('Author', 'scn-membership'); ?></th>
                                    <th class="scn-course-topics"><?php _e('Topics', 'scn-membership'); ?></th>
                                    <th class="scn-course-status"><?php _e('Status', 'scn-membership'); ?></th>
                                    <th class="scn-course-actions"><?php _e('Actions', 'scn-membership'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="scn-created-courses">
                                <tr>
                                    <td colspan="6" class="scn-loading">
                        <span class="dashicons dashicons-update"></span>
                                        <?php _e('Loading your created courses...', 'scn-membership'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <!-- Quick Actions Section -->
            <div class="scn-dashboard-quick-actions">
                <h2><?php _e('Quick Actions', 'scn-membership'); ?></h2>
                <div class="scn-actions-grid">
                    
                    <a href="<?php echo esc_url(home_url('/courses/')); ?>" class="scn-action-card">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <h3><?php _e('Browse Courses', 'scn-membership'); ?></h3>
                        <p><?php _e('Explore available courses and learning materials', 'scn-membership'); ?></p>
                    </a>
                    
                    <a href="<?php echo esc_url(home_url('/profiles/')); ?>" class="scn-action-card">
                        <span class="dashicons dashicons-groups"></span>
                        <h3><?php _e('Member Directory', 'scn-membership'); ?></h3>
                        <p><?php _e('Connect with other SCN members', 'scn-membership'); ?></p>
                    </a>
                </div>
            </div>

            <!-- Resources Section -->
            <div class="scn-dashboard-resources">
                <h2><?php _e('Resources & Support', 'scn-membership'); ?></h2>
                <div class="scn-resources-grid">
                    <div class="scn-resource-item">
                        <span class="dashicons dashicons-book-alt"></span>
                        <h3><?php _e('Documentation', 'scn-membership'); ?></h3>
                        <p><?php _e('Learn how to use the SCN platform effectively', 'scn-membership'); ?></p>
                        <a href="#" class="scn-resource-link"><?php _e('View Docs', 'scn-membership'); ?></a>
                    </div>
                    
                    <div class="scn-resource-item">
                        <span class="dashicons dashicons-sos"></span>
                        <h3><?php _e('Support', 'scn-membership'); ?></h3>
                        <p><?php _e('Get help with your account or technical issues', 'scn-membership'); ?></p>
                        <a href="#" class="scn-resource-link"><?php _e('Contact Support', 'scn-membership'); ?></a>
                    </div>
                    
                    <div class="scn-resource-item">
                        <span class="dashicons dashicons-format-chat"></span>
                        <h3><?php _e('Community', 'scn-membership'); ?></h3>
                        <p><?php _e('Connect with other members and share experiences', 'scn-membership'); ?></p>
                        <a href="#" class="scn-resource-link"><?php _e('Join Community', 'scn-membership'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Course Modal -->
<div id="scn-add-course-modal" class="scn-modal" style="display: none;">
    <div class="scn-modal-overlay"></div>
    <div class="scn-modal-content scn-modal-large">
        <div class="scn-modal-header">
            <h2 id="scn-course-modal-title"><?php _e('Add New Course', 'scn-membership'); ?></h2>
            <button type="button" class="scn-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="scn-modal-body">
            <form id="scn-add-course-form" class="scn-course-form">
                <div class="scn-form-tabs">
                    <button type="button" class="scn-tab-btn active" data-tab="basic">Basic Info</button>
                    <button type="button" class="scn-tab-btn" data-tab="formats">Formats & Outcomes</button>
                    <button type="button" class="scn-tab-btn" data-tab="ondemand">On-Demand</button>
                    <button type="button" class="scn-tab-btn" data-tab="topics">Topics</button>
    </div>
    
                <div class="scn-tab-content active" id="tab-basic">
                    <div class="scn-form-section">
                        <h3><span class="dashicons dashicons-welcome-learn-more"></span>Basic Information</h3>
                        <div class="scn-form-grid">
                            <div class="scn-form-group full-width">
                                <label for="course_title">Course Title <span class="required">*</span></label>
                                <input type="text" id="course_title" name="course_title" required placeholder="Enter the main course title">
        </div>
                            <div class="scn-form-group full-width">
                                <label for="course_subtitle">Subtitle</label>
                                <input type="text" id="course_subtitle" name="course_subtitle" placeholder="Brief subtitle for the course">
        </div>
                            <div class="scn-form-group full-width">
                                <label for="course_description">Description <span class="required">*</span></label>
                                <textarea id="course_description" name="course_description" required placeholder="Brief description of the course content and objectives..." rows="5"></textarea>
                            </div>
        </div>
    </div>
    
                    <div class="scn-form-section">
                        <h3><span class="dashicons dashicons-awards"></span>Continuing Education Credits</h3>
                        <div class="scn-form-grid">
                            <div class="scn-form-group">
                                <label>
                                    <input type="checkbox" id="course_ce_enabled" name="course_ce_enabled" value="1">
                                    This course provides continuing education credits
                                </label>
        </div>
                            <div class="scn-form-group">
                                <label for="course_ce_hours">CE Hours</label>
                                <input type="number" id="course_ce_hours" name="course_ce_hours" min="0.5" step="0.5" disabled>
                                <div class="scn-form-help">Number of continuing education hours (minimum 0.5, step 0.5)</div>
                            </div>
                        </div>
        </div>
    </div>
    
                <div class="scn-tab-content" id="tab-formats">
                    <div class="scn-form-section">
                        <h3><span class="dashicons dashicons-calendar-alt"></span>Course Formats</h3>
                        <div class="scn-form-grid">
                            <div class="scn-form-group full-width">
                                <label>Select the format(s) for this course:</label>
                                <div class="scn-checkbox-group scn-formats-grid">
                                    <label class="scn-format-item">
                                        <input type="checkbox" name="course_formats[]" value="in-person" id="format_in_person">
                                        <span>In-Person</span>
                                    </label>
                                    <label class="scn-format-item">
                                        <input type="checkbox" name="course_formats[]" value="virtual" id="format_virtual">
                                        <span>Virtual</span>
                                    </label>
                                    <label class="scn-format-item">
                                        <input type="checkbox" name="course_formats[]" value="hybrid" id="format_hybrid">
                                        <span>Hybrid</span>
                                    </label>
                                    <label class="scn-format-item">
                                        <input type="checkbox" name="course_formats[]" value="self-paced" id="format_self_paced">
                                        <span>Self-Paced</span>
                                    </label>
        </div>
                            </div>
        </div>
    </div>
    
                    <div class="scn-form-section">
                        <h3><span class="dashicons dashicons-list-view"></span>Learning Outcomes</h3>
                        <div class="scn-form-grid">
                            <div class="scn-form-group full-width">
                                <label>What will participants learn from this course? <span class="required">*</span></label>
                                <div id="scn-outcomes-container">
                                    <div class="scn-outcome-item">
                                        <input type="text" name="course_outcomes[0]" placeholder="Learning outcome" class="regular-text" required>
                                        <button type="button" class="scn-remove-outcome" style="display: none;">Remove</button>
        </div>
        </div>
                                <button type="button" id="scn-add-outcome" class="scn-btn scn-btn-secondary">
                                    <span class="dashicons dashicons-plus"></span>
                                    Add Outcome
                                </button>
    </div>
                        </div>
        </div>
    </div>
    
                <div class="scn-tab-content" id="tab-ondemand">
                    <div class="scn-form-section">
                        <h3><span class="dashicons dashicons-video-alt3"></span>On-Demand Course Information</h3>
                        <div class="scn-form-grid">
                            <div class="scn-form-group">
                                <label for="scn_ondemand_title">Title</label>
                                <input type="text" id="scn_ondemand_title" name="scn_ondemand_title" placeholder="On-demand course title">
        </div>
                            <div class="scn-form-group">
                                <label for="scn_ondemand_school">School/Platform</label>
                                <input type="text" id="scn_ondemand_school" name="scn_ondemand_school" placeholder="e.g., Coursera, Udemy">
        </div>
                            <div class="scn-form-group full-width">
                                <label for="scn_ondemand_link">Link</label>
                                <input type="url" id="scn_ondemand_link" name="scn_ondemand_link" placeholder="https://" class="regular-text">
                                <div class="scn-form-help">Must be a valid HTTPS URL</div>
    </div>
        </div>
                    </div>
                </div>

                <div class="scn-tab-content" id="tab-topics">
                    <div class="scn-form-section">
                        <h3><span class="dashicons dashicons-tag"></span>Course Topics</h3>
                        <div class="scn-form-grid">
                            <div class="scn-form-group full-width">
                                <label>Select topics that this course covers:</label>
                                <div class="scn-checkbox-group scn-topics-grid">
                                    <?php
                                    // Ensure taxonomy is registered
                                    if (!taxonomy_exists('scn_topic')) {
                                        // Register taxonomy if not exists (fallback)
                                        register_taxonomy('scn_topic', [], [ // Removed 'course' from object types
                                            'labels' => [
                                                'name' => __('Topics', 'scn-membership'),
                                                'singular_name' => __('Topic', 'scn-membership'),
                                            ],
                                            'hierarchical' => false,
                                            'public' => true,
                                            'show_in_rest' => true,
                                            'show_admin_column' => true,
                                        ]);
                                    }
                                    
                                    // Get topics for courses specifically
                                    $topic_terms = get_terms([
                                        'taxonomy' => 'scn_topic', 
                                        'hide_empty' => false,
                                        'suppress_filters' => true,
                                        'orderby' => 'name',
                                        'order' => 'ASC'
                                    ]);
                                    
                                    // Debug: Log the results
                                    if (defined('WP_DEBUG') && WP_DEBUG) {
                                        error_log('SCN Topics Debug: Found ' . (is_array($topic_terms) ? count($topic_terms) : 0) . ' topics');
                                        if (is_wp_error($topic_terms)) {
                                            error_log('SCN Topics Error: ' . $topic_terms->get_error_message());
                                        }
                                    }
                                    
                                    if (!is_wp_error($topic_terms) && !empty($topic_terms)) {
                                        foreach ($topic_terms as $term) {
                                            echo '<label class="scn-topic-item">';
                                            echo '<input type="checkbox" name="course_topics[]" value="' . esc_attr($term->term_id) . '" id="topic_' . esc_attr($term->term_id) . '">';
                                            echo '<span>' . esc_html($term->name) . '</span>';
                                            echo '</label>';
                                        }
                                    } else {
                                        echo '<div class="scn-no-topics">';
                                        echo '<p>No topics available. <a href="' . admin_url('edit-tags.php?taxonomy=scn_topic&post_type=course') . '" target="_blank">Add topics in admin</a></p>';
                                        if (defined('WP_DEBUG') && WP_DEBUG) {
                                            echo '<p><small>Debug: ' . (is_wp_error($topic_terms) ? $topic_terms->get_error_message() : 'No terms found') . '</small></p>';
                                        }
                                        echo '</div>';
                                    }
                                    ?>
            </div>
            </div>
        </div>
        </div>
                </div>
            </form>
        </div>
        <div class="scn-modal-footer">
            <button type="submit" form="scn-add-course-form" class="scn-btn scn-btn-primary scn-save-course">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Create Course', 'scn-membership'); ?>
            </button>
            <button type="button" class="scn-btn scn-btn-secondary scn-modal-close">
                <?php _e('Cancel', 'scn-membership'); ?>
            </button>
        </div>
    </div>
</div>


<script type="text/template" id="scn-completion-template">
    {{#unless is_complete}}
    <div class="scn-completion-progress">
        <div class="scn-progress-bar">
            <div class="scn-progress-fill" style="width: {{completion_percentage}}%"></div>
        </div>
        <div class="scn-progress-text">{{completion_percentage}}% <?php _e('Complete', 'scn-membership'); ?></div>
    </div>
    
    <div class="scn-completion-description">
        <div class="scn-completion-warning">
            <span class="dashicons dashicons-warning"></span>
            <strong><?php _e('Profile Incomplete', 'scn-membership'); ?></strong>
            <p><?php _e('Complete your profile to increase visibility and credibility.', 'scn-membership'); ?></p>
        </div>
    </div>
    
    <div class="scn-completion-actions">
        <button type="button" class="scn-btn scn-btn-primary scn-completion-details-btn">
            <span class="dashicons dashicons-edit"></span>
            <?php _e('Complete your profile', 'scn-membership'); ?>
        </button>
    </div>
    {{/unless}}
</script>

<!-- Profile Completion Form Modal -->
<div id="scn-completion-modal" class="scn-modal" style="display: none;">
    <div class="scn-modal-overlay"></div>
    <div class="scn-modal-content scn-modal-large">
               <div class="scn-modal-header">
                   <h2 id="scn-modal-title"><?php _e('Complete Your Profile', 'scn-membership'); ?></h2>
                   <button type="button" class="scn-modal-close">
                       <span class="dashicons dashicons-no-alt"></span>
                   </button>
            </div>
        <div class="scn-modal-body">
            <form id="scn-profile-completion-form" class="scn-completion-form">
                <div id="scn-completion-form-content">
                    <div class="scn-loading">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Loading profile form...', 'scn-membership'); ?>
            </div>
        </div>
            </form>
        </div>
               <div class="scn-modal-footer">
                   <button type="submit" form="scn-profile-completion-form" class="scn-btn scn-btn-primary scn-save-profile">
                       <span class="dashicons dashicons-yes-alt"></span>
                       <span id="scn-save-button-text"><?php _e('Save Profile', 'scn-membership'); ?></span>
                   </button>
            <button type="button" class="scn-btn scn-btn-secondary scn-modal-close">
                <?php _e('Cancel', 'scn-membership'); ?>
            </button>
            </div>
            </div>
        </div>

<style>
/* Modal Styles */
.scn-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
}

.scn-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(2px);
}

.scn-modal-content {
    position: relative;
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    margin: 10vh auto;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.scn-modal-large {
    max-width: 800px;
    width: 95%;
}

.scn-modal-header {
    padding: 25px 30px 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.scn-modal-header h2 {
    color: #2c3e50;
    font-size: 24px;
    font-weight: 600;
    margin: 0;
}

.scn-modal-close {
    background: none;
    border: none;
    color: #7f8c8d;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.scn-modal-close:hover {
    background: #ecf0f1;
    color: #2c3e50;
}

.scn-modal-body {
    padding: 30px;
    flex: 1;
    overflow-y: auto;
}

.scn-modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #ecf0f1;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

/* Completion Details Styles */
.scn-completion-details {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.scn-completion-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
}

.scn-completion-section h3 {
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.scn-completion-section h3 .dashicons {
    color: #3498db;
}

.scn-field-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.scn-field-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.scn-field-item:hover {
    border-color: #3498db;
    box-shadow: 0 2px 8px rgba(52, 152, 219, 0.1);
}

.scn-field-item.completed {
    border-color: #27ae60;
    background: #f8fff8;
}

.scn-field-item.missing {
    border-color: #e74c3c;
    background: #fff8f8;
}

.scn-field-status {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    flex-shrink: 0;
}

.scn-field-status.completed {
    background: #27ae60;
    color: white;
}

.scn-field-status.missing {
    background: #e74c3c;
    color: white;
}

.scn-field-info {
    flex: 1;
}

.scn-field-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 2px;
}

.scn-field-required {
    font-size: 12px;
    color: #e74c3c;
    font-weight: 500;
}

.scn-field-weight {
    font-size: 12px;
    color: #7f8c8d;
    background: #ecf0f1;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.scn-completion-summary {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
}

.scn-completion-summary h3 {
    color: white;
    margin: 0 0 10px 0;
}

.scn-completion-summary .scn-progress-bar {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    height: 8px;
    margin: 15px 0;
    overflow: hidden;
}

.scn-completion-summary .scn-progress-fill {
    background: white;
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease;
}

.scn-completion-description {
    margin-bottom: 20px;
}

.scn-completion-success {
    background: #d5f4e6;
    border: 1px solid #27ae60;
    border-radius: 10px;
    padding: 15px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.scn-completion-success .dashicons {
    color: #27ae60;
    margin-top: 2px;
}

.scn-completion-warning {
    background: #fef5e7;
    border: 1px solid #f39c12;
    border-radius: 10px;
    padding: 15px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.scn-completion-warning .dashicons {
    color: #f39c12;
    margin-top: 2px;
}

.scn-completion-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.scn-completion-details-btn {
    background: #95a5a6;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.scn-completion-details-btn:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(149, 165, 166, 0.3);
}

/* Form Styles */
.scn-completion-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.scn-form-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
}

.scn-form-section h3 {
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.scn-form-section h3 .dashicons {
    color: #3498db;
}

.scn-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.scn-form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.scn-form-group.full-width {
    grid-column: 1 / -1;
}

.scn-form-group label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.scn-form-group .required {
    color: #e74c3c;
}

.scn-form-group input,
.scn-form-group textarea,
.scn-form-group select {
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.scn-form-group input:focus,
.scn-form-group textarea:focus,
.scn-form-group select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.scn-form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.scn-form-help {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 5px;
}

.scn-form-error {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 5px;
}

.scn-form-success {
    background: #d5f4e6;
    border: 1px solid #27ae60;
    color: #27ae60;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.scn-save-profile {
    position: relative;
}

.scn-save-profile.loading {
    opacity: 0.7;
    pointer-events: none;
}

.scn-save-profile.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Image Upload Styles */
.scn-image-upload-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.scn-image-preview {
    border: 2px dashed #e9ecef;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.scn-uploaded-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
}

.scn-no-image {
    color: #7f8c8d;
    font-style: italic;
}

/* Gallery Styles */
.scn-gallery-upload-container,
.scn-press-kit-upload-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.scn-gallery-preview,
.scn-press-kit-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    min-height: 60px;
    border: 2px dashed #e9ecef;
    border-radius: 8px;
    padding: 15px;
}

.scn-gallery-item,
.scn-press-kit-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f9fa;
    border-radius: 6px;
    padding: 8px 12px;
    border: 1px solid #e9ecef;
}

.scn-gallery-item img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.scn-remove-gallery-image,
.scn-remove-press-kit-file {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.scn-remove-gallery-image:hover,
.scn-remove-press-kit-file:hover {
    background: #c0392b;
}

/* Services Styles */
.scn-services-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.scn-services-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.scn-service-item {
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #e9ecef;
    position: relative;
}

.scn-service-item input,
.scn-service-item textarea {
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 10px;
    font-size: 14px;
}

.scn-service-item textarea {
    min-height: 60px;
    resize: vertical;
}

.scn-remove-service {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.scn-remove-service:hover {
    background: #c0392b;
}

/* Upload Button Styles */
.scn-upload-image-btn,
.scn-add-gallery-images,
.scn-add-press-kit-files,
.scn-add-service {
    align-self: flex-start;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Debug: Check if WordPress media is available
    console.log('WordPress media available:', typeof wp !== 'undefined' && typeof wp.media !== 'undefined');
    console.log('jQuery version:', $.fn.jquery);
    
    // Modal functionality
    $(document).on('click', '.scn-completion-details-btn', function() {
        $('#scn-modal-title').text('Complete Your Profile');
        $('#scn-save-button-text').text('Save Profile');
        $('#scn-completion-modal').fadeIn(300);
        loadCompletionDetails();
    });

    // Handle edit profile button
    $(document).on('click', '.scn-edit-profile-btn', function() {
        $('#scn-modal-title').text('Edit Your Profile');
        $('#scn-save-button-text').text('Update Profile');
        $('#scn-completion-modal').fadeIn(300);
        loadCompletionDetails();
    });

    // Handle add course button
    $(document).on('click', '.scn-add-course-btn', function() {
        $('#scn-add-course-modal').fadeIn(300);
        // Reset form and show first tab
        $('#scn-add-course-form')[0].reset();
        $('.scn-tab-content').removeClass('active');
        $('.scn-tab-btn').removeClass('active');
        $('#tab-basic').addClass('active');
        $('.scn-tab-btn[data-tab="basic"]').addClass('active');
    });

    // Handle tab switching
    $(document).on('click', '.scn-tab-btn', function() {
        const tab = $(this).data('tab');
        
        // Remove active class from all tabs and contents
        $('.scn-tab-btn').removeClass('active');
        $('.scn-tab-content').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('active');
        $('#tab-' + tab).addClass('active');
    });

    $('.scn-modal-close, .scn-modal-overlay').on('click', function() {
        $('#scn-completion-modal').fadeOut(300);
        $('#scn-add-course-modal').fadeOut(300);
    });

    // Handle CE toggle
    $(document).on('change', '#course_ce_enabled', function() {
        const ceHours = $('#course_ce_hours');
        if (this.checked) {
            ceHours.prop('disabled', false);
        } else {
            ceHours.prop('disabled', true).val('');
        }
    });

    // Handle add outcome
    $(document).on('click', '#scn-add-outcome', function() {
        const container = $('#scn-outcomes-container');
        const outcomeIndex = container.find('.scn-outcome-item').length;
        const outcomeItem = $('<div class="scn-outcome-item">' +
            '<input type="text" name="course_outcomes[' + outcomeIndex + ']" placeholder="Learning outcome" class="regular-text">' +
            '<button type="button" class="scn-remove-outcome">Remove</button>' +
            '</div>');
        container.append(outcomeItem);
        updateRemoveButtons();
    });

    // Handle remove outcome
    $(document).on('click', '.scn-remove-outcome', function() {
        $(this).closest('.scn-outcome-item').remove();
        updateRemoveButtons();
    });

    function updateRemoveButtons() {
        const items = $('.scn-outcome-item');
        items.each(function(index) {
            const removeBtn = $(this).find('.scn-remove-outcome');
            removeBtn.toggle(items.length > 1);
        });
    }

    // Handle course form submission
    $(document).on('submit', '#scn-add-course-form', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $saveBtn = $('.scn-save-course');
        const $modal = $('#scn-add-course-modal');
        
        $saveBtn.addClass('loading').prop('disabled', true);
        
        const formData = new FormData(this);
        formData.append('action', 'scn_create_course');
        formData.append('nonce', scnDashboard.nonce);
        formData.append('profile_id', $('.scn-dashboard-container').data('profile-id'));
        
        $.ajax({
            url: scnDashboard.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $modal.fadeOut(300);
                    // Reload courses
                    loadCourses();
                    // Show success message
                    alert('Course created successfully!');
                } else {
                    alert('Error creating course: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error creating course. Please try again.');
            },
            complete: function() {
                $saveBtn.removeClass('loading').prop('disabled', false);
            }
        });
    });

    // Load courses function
    function loadCourses() {
        // Load created courses
        $.ajax({
            url: scnDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'scn_get_created_courses',
                nonce: scnDashboard.nonce,
                profile_id: $('.scn-dashboard-container').data('profile-id')
            },
            success: function(response) {
                if (response.success) {
                    $('#scn-created-courses').html(response.data);
                } else {
                    $('#scn-created-courses').html('<div class="scn-no-courses">No courses created yet</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error loading created courses:', error);
                $('#scn-created-courses').html('<div class="scn-no-courses">Error loading created courses: ' + error + '</div>');
            }
        });
    }

    // Load courses on page load
    loadCourses();

    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // Escape key
            $('#scn-completion-modal').fadeOut(300);
        }
    });

    function loadCompletionDetails() {
        const $formContainer = $('#scn-completion-form-content');
        $formContainer.html('<div class="scn-loading"><span class="dashicons dashicons-update"></span> Loading profile form...</div>');

        // Load the profile form
        $.ajax({
            url: scnDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'scn_get_profile_form',
                nonce: scnDashboard.nonce,
                profile_id: $('.scn-dashboard-container').data('profile-id')
            },
            success: function(response) {
                if (response.success) {
                    $formContainer.html(response.data);
                    console.log('Profile form loaded successfully');
                    // Debug: Check if images are present
                    const $profileImage = $formContainer.find('.scn-image-preview img');
                    const $galleryImages = $formContainer.find('.scn-gallery-preview .scn-gallery-item');
                    console.log('Profile image found:', $profileImage.length > 0);
                    console.log('Gallery images found:', $galleryImages.length);
                } else {
                    $formContainer.html('<div class="scn-error">Error loading profile form.</div>');
                }
            },
            error: function() {
                $formContainer.html('<div class="scn-error">Error loading profile form.</div>');
            }
        });
    }

    // Handle form submission
    $(document).on('submit', '#scn-profile-completion-form', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $saveBtn = $('.scn-save-profile');
        const $formContainer = $('#scn-completion-form-content');
        
        // Show loading state
        $saveBtn.addClass('loading').prop('disabled', true);
        
        // Clear previous messages
        $formContainer.find('.scn-form-success, .scn-form-error').remove();
        
        // Collect form data
        const formData = $form.serialize();
        
        $.ajax({
            url: scnDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'scn_save_profile_completion',
                nonce: scnDashboard.nonce,
                profile_id: $('.scn-dashboard-container').data('profile-id'),
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $formContainer.prepend('<div class="scn-form-success">' +
                        '<span class="dashicons dashicons-yes-alt"></span>' +
                        'Profile updated successfully!' +
                        '</div>');
                    
                    // Close modal after a short delay
                    setTimeout(() => {
                        $('#scn-completion-modal').fadeOut(300);
                        // Reload the page to show updated completion status
                        location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    $formContainer.prepend('<div class="scn-form-error">' +
                        (response.data || 'Error updating profile. Please try again.') +
                        '</div>');
                }
            },
            error: function() {
                $formContainer.prepend('<div class="scn-form-error">Error updating profile. Please try again.</div>');
            },
            complete: function() {
                $saveBtn.removeClass('loading').prop('disabled', false);
            }
        });
    });

    // Handle image upload
    $(document).on('click', '.scn-upload-image-btn', function() {
        const fileInput = $('<input type="file" accept="image/*" style="display:none;">');
        fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                uploadFile(file, 'profile_image');
            }
        });
        fileInput.click();
    });

    // Handle gallery images upload
    $(document).on('click', '.scn-add-gallery-images', function() {
        const fileInput = $('<input type="file" accept="image/*" multiple style="display:none;">');
        fileInput.on('change', function(e) {
            const files = e.target.files;
            if (files.length > 0) {
                Array.from(files).forEach(file => {
                    uploadFile(file, 'gallery');
                });
            }
        });
        fileInput.click();
    });

    // Handle press kit files upload
    $(document).on('click', '.scn-add-press-kit-files', function() {
        const fileInput = $('<input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple style="display:none;">');
        fileInput.on('change', function(e) {
            const files = e.target.files;
            if (files.length > 0) {
                Array.from(files).forEach(file => {
                    uploadFile(file, 'press_kit');
                });
            }
        });
        fileInput.click();
    });

    // Handle service addition
    $(document).on('click', '.scn-add-service', function() {
        const serviceIndex = $('.scn-service-item').length;
        const $serviceItem = $('<div class="scn-service-item">' +
            '<input type="text" name="scn_services[' + serviceIndex + '][title]" placeholder="Service Title">' +
            '<textarea name="scn_services[' + serviceIndex + '][description]" placeholder="Service Description"></textarea>' +
            '<button type="button" class="scn-remove-service">×</button>' +
            '</div>');
        $('.scn-services-list').append($serviceItem);
    });

    // Handle removal of items
    $(document).on('click', '.scn-remove-gallery-image', function() {
        const $item = $(this).closest('.scn-gallery-item');
        const imageId = $item.data('image-id');
        const currentImages = $('#scn_gallery_images').val().split(',').filter(id => id && id != imageId);
        $('#scn_gallery_images').val(currentImages.join(','));
        $item.remove();
    });

    $(document).on('click', '.scn-remove-press-kit-file', function() {
        const $item = $(this).closest('.scn-press-kit-item');
        const fileId = $item.data('file-id');
        const currentFiles = $('#scn_press_kit_files').val().split(',').filter(id => id && id != fileId);
        $('#scn_press_kit_files').val(currentFiles.join(','));
        $item.remove();
    });

    $(document).on('click', '.scn-remove-service', function() {
        $(this).closest('.scn-service-item').remove();
    });

    // File upload function
    function uploadFile(file, type) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'scn_upload_file');
        formData.append('nonce', scnDashboard.nonce);
        formData.append('upload_type', type);
        formData.append('profile_id', $('.scn-dashboard-container').data('profile-id'));

        $.ajax({
            url: scnDashboard.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    handleUploadSuccess(response.data, type);
                } else {
                    alert('Upload failed: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Upload failed. Please try again.');
            }
        });
    }

    function handleUploadSuccess(data, type) {
        if (type === 'profile_image') {
            $('.scn-image-preview').html('<img src="' + data.url + '" class="scn-uploaded-image" alt="Profile Image">');
            $('#profile_image').val(data.id);
        } else if (type === 'gallery') {
            const currentImages = $('#scn_gallery_images').val().split(',').filter(id => id);
            if (!currentImages.includes(data.id.toString())) {
                const $item = $('<div class="scn-gallery-item" data-image-id="' + data.id + '">' +
                    '<img src="' + data.thumbnail_url + '" alt="Gallery Image">' +
                    '<button type="button" class="scn-remove-gallery-image">×</button>' +
                    '</div>');
                $('.scn-gallery-preview').append($item);
                currentImages.push(data.id);
                $('#scn_gallery_images').val(currentImages.join(','));
            }
        } else if (type === 'press_kit') {
            const currentFiles = $('#scn_press_kit_files').val().split(',').filter(id => id);
            if (!currentFiles.includes(data.id.toString())) {
                const $item = $('<div class="scn-press-kit-item" data-file-id="' + data.id + '">' +
                    '<span class="dashicons dashicons-media-document"></span>' +
                    '<span>' + data.title + '</span>' +
                    '<button type="button" class="scn-remove-press-kit-file">×</button>' +
                    '</div>');
                $('.scn-press-kit-preview').append($item);
                currentFiles.push(data.id);
                $('#scn_press_kit_files').val(currentFiles.join(','));
            }
        }
    }
});
</script>

<?php if (!$is_shortcode): ?>
<?php wp_footer(); ?>
</body>
</html>
<?php endif; ?>

