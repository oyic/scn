<?php
/**
 * Member Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/member-dashboard/')));
    exit;
}

// Get current user's profile
$user_id = get_current_user_id();
$profile_posts = get_posts([
    'post_type' => 'scn_profile',
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
    wp_redirect(admin_url('post-new.php?post_type=scn_profile'));
    exit;
}

$profile = $profile_posts[0];
$first_name = get_post_meta($profile->ID, 'scn_first_name', true);
$last_name = get_post_meta($profile->ID, 'scn_last_name', true);
$member_since = get_post_meta($profile->ID, 'scn_member_since', true);

get_header();
?>

<style>
.scn-dashboard-page {
    background: #f8f9fa;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.scn-dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
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

.scn-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.scn-dashboard-stats,
.scn-dashboard-upcoming,
.scn-dashboard-activity,
.scn-dashboard-quick-actions {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.scn-dashboard-stats h2,
.scn-dashboard-upcoming h2,
.scn-dashboard-activity h2,
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
    <div class="scn-dashboard-container" data-user-id="<?php echo esc_attr($user_id); ?>">
        <div class="scn-dashboard-header">
            <div class="scn-dashboard-welcome">
                <h1><?php printf(__('Welcome back, %s!', 'scn-membership'), esc_html($first_name . ' ' . $last_name)); ?></h1>
                <?php if ($member_since): ?>
                    <p class="scn-member-since">
                        <?php printf(__('Member since %s', 'scn-membership'), date('F Y', strtotime($member_since))); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="scn-dashboard-actions">
                <a href="<?php echo esc_url(get_edit_post_link($profile->ID)); ?>" class="scn-btn scn-btn-primary">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Edit Profile', 'scn-membership'); ?>
                </a>
                <a href="<?php echo esc_url(get_permalink($profile->ID)); ?>" class="scn-btn scn-btn-secondary">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('View Profile', 'scn-membership'); ?>
                </a>
                <a href="<?php echo get_permalink(81); ?>?action=logout" class="scn-btn scn-btn-logout">
                    <span class="dashicons dashicons-exit"></span>
                    <?php _e('Logout', 'scn-membership'); ?>
                </a>
            </div>
        </div>

        <div class="scn-dashboard-grid">
            <!-- Statistics Section -->
            <div class="scn-dashboard-stats">
                <h2><?php _e('Your Statistics', 'scn-membership'); ?></h2>
                <div class="scn-stats-grid" id="scn-stats-container">
                    <div class="scn-loading">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Loading statistics...', 'scn-membership'); ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Sessions Section -->
            <div class="scn-dashboard-upcoming">
                <h2><?php _e('Upcoming Sessions', 'scn-membership'); ?></h2>
                <div id="scn-upcoming-sessions">
                    <div class="scn-loading">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Loading sessions...', 'scn-membership'); ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="scn-dashboard-activity">
                <h2><?php _e('Recent Activity', 'scn-membership'); ?></h2>
                <div id="scn-recent-activity">
                    <div class="scn-loading">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Loading activity...', 'scn-membership'); ?>
                    </div>
                </div>
            </div>

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

            <!-- Quick Actions Section -->
            <div class="scn-dashboard-quick-actions">
                <h2><?php _e('Quick Actions', 'scn-membership'); ?></h2>
                <div class="scn-actions-grid">
                    <a href="<?php echo esc_url(get_edit_post_link($profile->ID)); ?>" class="scn-action-card">
                        <span class="dashicons dashicons-admin-users"></span>
                        <h3><?php _e('Edit Profile', 'scn-membership'); ?></h3>
                        <p><?php _e('Update your profile information, bio, and media', 'scn-membership'); ?></p>
                    </a>
                    
                    <a href="<?php echo esc_url(home_url('/events/')); ?>" class="scn-action-card">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <h3><?php _e('Browse Events', 'scn-membership'); ?></h3>
                        <p><?php _e('View upcoming events and sessions', 'scn-membership'); ?></p>
                    </a>
                    
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
                    
                    <a href="<?php echo get_permalink(81); ?>?action=logout" class="scn-action-card" style="background: #fee; border-color: #e74c3c;">
                        <span class="dashicons dashicons-exit"></span>
                        <h3><?php _e('Logout', 'scn-membership'); ?></h3>
                        <p><?php _e('Sign out of your account', 'scn-membership'); ?></p>
                    </a>

                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=scn_course')); ?>" class="scn-action-card">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <h3><?php _e('Create Course', 'scn-membership'); ?></h3>
                        <p><?php _e('Add a new course to your profile', 'scn-membership'); ?></p>
                    </a>

                    <a href="<?php echo esc_url(admin_url('admin.php?page=scn-events')); ?>" class="scn-action-card">
                        <span class="dashicons dashicons-calendar"></span>
                        <h3><?php _e('Manage Sessions', 'scn-membership'); ?></h3>
                        <p><?php _e('Create and manage your speaking sessions', 'scn-membership'); ?></p>
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

<script type="text/template" id="scn-stats-template">
    <div class="scn-stat-card">
        <div class="scn-stat-icon">
            <span class="dashicons dashicons-calendar-alt"></span>
        </div>
        <div class="scn-stat-content">
            <div class="scn-stat-number">{{total_sessions}}</div>
            <div class="scn-stat-label"><?php _e('Total Sessions', 'scn-membership'); ?></div>
        </div>
    </div>
    
    <div class="scn-stat-card">
        <div class="scn-stat-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="scn-stat-content">
            <div class="scn-stat-number">{{approved_sessions}}</div>
            <div class="scn-stat-label"><?php _e('Approved Sessions', 'scn-membership'); ?></div>
        </div>
    </div>
    
    <div class="scn-stat-card">
        <div class="scn-stat-icon">
            <span class="dashicons dashicons-clock"></span>
        </div>
        <div class="scn-stat-content">
            <div class="scn-stat-number">{{upcoming_sessions}}</div>
            <div class="scn-stat-label"><?php _e('Upcoming Sessions', 'scn-membership'); ?></div>
        </div>
    </div>
    
    <div class="scn-stat-card">
        <div class="scn-stat-icon">
            <span class="dashicons dashicons-welcome-learn-more"></span>
        </div>
        <div class="scn-stat-content">
            <div class="scn-stat-number">{{courses_created}}</div>
            <div class="scn-stat-label"><?php _e('Courses Created', 'scn-membership'); ?></div>
        </div>
    </div>
    
    <div class="scn-stat-card">
        <div class="scn-stat-icon">
            <span class="dashicons dashicons-visibility"></span>
        </div>
        <div class="scn-stat-content">
            <div class="scn-stat-number">{{profile_views}}</div>
            <div class="scn-stat-label"><?php _e('Profile Views', 'scn-membership'); ?></div>
        </div>
    </div>
</script>

<script type="text/template" id="scn-sessions-template">
    {{#if sessions.length}}
        {{#each sessions}}
        <div class="scn-session-item">
            <div class="scn-session-info">
                <h4>{{title}}</h4>
                <p class="scn-session-meta">
                    <span class="scn-session-event">{{event_name}}</span>
                    <span class="scn-session-date">{{formatted_date}}</span>
                    <span class="scn-session-time">{{formatted_time}}</span>
                    {{#if room}}<span class="scn-session-room">{{room}}</span>{{/if}}
                </p>
            </div>
            <div class="scn-session-actions">
                <a href="<?php echo esc_url(home_url('/events/{{event_slug}}/')); ?>" class="scn-btn scn-btn-small">
                    <?php _e('View Event', 'scn-membership'); ?>
                </a>
            </div>
        </div>
        {{/each}}
    {{else}}
        <div class="scn-empty-state">
            <span class="dashicons dashicons-calendar-alt"></span>
            <p><?php _e('No upcoming sessions scheduled', 'scn-membership'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=scn-events')); ?>" class="scn-btn scn-btn-primary">
                <?php _e('Create Session', 'scn-membership'); ?>
            </a>
        </div>
    {{/if}}
</script>

<script type="text/template" id="scn-activity-template">
    {{#if activity.length}}
        {{#each activity}}
        <div class="scn-activity-item">
            <div class="scn-activity-icon">
                <span class="dashicons dashicons-{{icon}}"></span>
            </div>
            <div class="scn-activity-content">
                <h4>{{title}}</h4>
                <p>{{description}}</p>
                <span class="scn-activity-date">{{formatted_date}}</span>
            </div>
        </div>
        {{/each}}
    {{else}}
        <div class="scn-empty-state">
            <span class="dashicons dashicons-clock"></span>
            <p><?php _e('No recent activity', 'scn-membership'); ?></p>
        </div>
    {{/if}}
</script>

<script type="text/template" id="scn-completion-template">
    <div class="scn-completion-progress">
        <div class="scn-progress-bar">
            <div class="scn-progress-fill" style="width: {{completion_percentage}}%"></div>
        </div>
        <div class="scn-progress-text">{{completion_percentage}}% <?php _e('Complete', 'scn-membership'); ?></div>
    </div>
    
    <div class="scn-completion-checklist">
        <div class="scn-completion-item {{#if has_photo}}scn-completed{{/if}}">
            <span class="dashicons dashicons-{{#if has_photo}}yes-alt{{else}}no-alt{{/if}}"></span>
            <?php _e('Profile Photo', 'scn-membership'); ?>
        </div>
        <div class="scn-completion-item {{#if has_gallery}}scn-completed{{/if}}">
            <span class="dashicons dashicons-{{#if has_gallery}}yes-alt{{else}}no-alt{{/if}}"></span>
            <?php _e('Gallery Images', 'scn-membership'); ?>
        </div>
        <div class="scn-completion-item {{#if has_press_kit}}scn-completed{{/if}}">
            <span class="dashicons dashicons-{{#if has_press_kit}}yes-alt{{else}}no-alt{{/if}}"></span>
            <?php _e('Press Kit Files', 'scn-membership'); ?>
        </div>
    </div>
    
    <div class="scn-completion-actions">
        <a href="<?php echo esc_url(get_edit_post_link($profile->ID)); ?>" class="scn-btn scn-btn-primary">
            <?php _e('Complete Profile', 'scn-membership'); ?>
        </a>
    </div>
</script>

<?php get_footer(); ?>
