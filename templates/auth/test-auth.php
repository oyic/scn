<?php
/**
 * Authentication Test Page
 * This page demonstrates the authentication flow
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="scn-auth-test-page">
    <div class="scn-test-container">
        <div class="scn-test-header">
            <h1><?php _e('SCN Authentication Test', 'scn-membership'); ?></h1>
            <p><?php _e('Test the member authentication and dashboard functionality', 'scn-membership'); ?></p>
        </div>

        <div class="scn-test-sections">
            <!-- Current Status -->
            <div class="scn-test-section">
                <h2><?php _e('Current Status', 'scn-membership'); ?></h2>
                <div class="scn-status-grid">
                    <div class="scn-status-item">
                        <span class="scn-status-label"><?php _e('Logged In:', 'scn-membership'); ?></span>
                        <span class="scn-status-value <?php echo is_user_logged_in() ? 'scn-status-yes' : 'scn-status-no'; ?>">
                            <?php echo is_user_logged_in() ? __('Yes', 'scn-membership') : __('No', 'scn-membership'); ?>
                        </span>
                    </div>
                    
                    <?php if (is_user_logged_in()): ?>
                        <div class="scn-status-item">
                            <span class="scn-status-label"><?php _e('User ID:', 'scn-membership'); ?></span>
                            <span class="scn-status-value"><?php echo get_current_user_id(); ?></span>
                        </div>
                        
                        <div class="scn-status-item">
                            <span class="scn-status-label"><?php _e('Username:', 'scn-membership'); ?></span>
                            <span class="scn-status-value"><?php echo wp_get_current_user()->user_login; ?></span>
                        </div>
                        
                        <div class="scn-status-item">
                            <span class="scn-status-label"><?php _e('Has Profile:', 'scn-membership'); ?></span>
                            <span class="scn-status-value">
                                <?php
                                $profile_posts = get_posts([
                                    'post_type' => 'scn_profile',
                                    'meta_query' => [
                                        [
                                            'key' => 'scn_user_id',
                                            'value' => get_current_user_id(),
                                            'compare' => '='
                                        ]
                                    ],
                                    'posts_per_page' => 1,
                                    'post_status' => 'publish'
                                ]);
                                echo !empty($profile_posts) ? '<span class="scn-status-yes">' . __('Yes', 'scn-membership') . '</span>' : '<span class="scn-status-no">' . __('No', 'scn-membership') . '</span>';
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Test User Creation -->
            <div class="scn-test-section">
                <h2><?php _e('Create Test User', 'scn-membership'); ?></h2>
                <div class="scn-test-info">
                    <p><?php _e('Create a test member user for authentication testing:', 'scn-membership'); ?></p>
                    <div class="scn-user-details">
                        <strong><?php _e('Username:', 'scn-membership'); ?></strong> testmember<br>
                        <strong><?php _e('Email:', 'scn-membership'); ?></strong> test@scn-membership.com<br>
                        <strong><?php _e('Password:', 'scn-membership'); ?></strong> TestMember123!<br>
                        <strong><?php _e('Name:', 'scn-membership'); ?></strong> Test Member
                    </div>
                </div>
                
                <?php if (current_user_can('create_users')): ?>
                    <form method="post" action="">
                        <input type="hidden" name="scn_create_test_user" value="1">
                        <button type="submit" class="scn-test-btn">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php _e('Create Test User', 'scn-membership'); ?>
                        </button>
                    </form>
                    
                    <?php
                    if ($_POST && isset($_POST['scn_create_test_user'])) {
                        // Create test user
                        if (username_exists('testmember')) {
                            echo '<div class="scn-test-result scn-test-warning">Test user already exists.</div>';
                        } else {
                            $user_id = wp_create_user('testmember', 'TestMember123!', 'test@scn-membership.com');
                            
                            if (is_wp_error($user_id)) {
                                echo '<div class="scn-test-result scn-test-error">Error: ' . $user_id->get_error_message() . '</div>';
                            } else {
                                // Update user meta
                                update_user_meta($user_id, 'first_name', 'Test');
                                update_user_meta($user_id, 'last_name', 'Member');
                                
                                // Create profile
                                $profile_id = wp_insert_post([
                                    'post_title' => 'Test Member',
                                    'post_type' => 'scn_profile',
                                    'post_status' => 'publish',
                                    'post_author' => $user_id,
                                    'meta_input' => [
                                        'scn_user_id' => $user_id,
                                        'scn_first_name' => 'Test',
                                        'scn_last_name' => 'Member',
                                        'scn_bio' => 'This is a test member profile for SCN Membership authentication testing.',
                                        'scn_location' => 'Test City, TC',
                                        'scn_credentials' => 'Test Member, SCN',
                                        'scn_member_since' => current_time('mysql')
                                    ]
                                ]);
                                
                                if ($profile_id) {
                                    echo '<div class="scn-test-result scn-test-success">Test user created successfully! User ID: ' . $user_id . ', Profile ID: ' . $profile_id . '</div>';
                                } else {
                                    echo '<div class="scn-test-result scn-test-error">User created but profile creation failed.</div>';
                                }
                            }
                        }
                    }
                    ?>
                <?php else: ?>
                    <div class="scn-test-info">
                        <p><?php _e('You need administrator privileges to create test users.', 'scn-membership'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Authentication Links -->
            <div class="scn-test-section">
                <h2><?php _e('Authentication Links', 'scn-membership'); ?></h2>
                <div class="scn-auth-links">
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(home_url('/member-dashboard/')); ?>" class="scn-test-btn scn-test-btn-primary">
                            <span class="dashicons dashicons-dashboard"></span>
                            <?php _e('Go to Dashboard', 'scn-membership'); ?>
                        </a>
                        
                        <a href="<?php echo esc_url(home_url('/member-logout/')); ?>" class="scn-test-btn scn-test-btn-secondary">
                            <span class="dashicons dashicons-exit"></span>
                            <?php _e('Logout', 'scn-membership'); ?>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/member-login/')); ?>" class="scn-test-btn scn-test-btn-primary">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php _e('Member Login', 'scn-membership'); ?>
                        </a>
                        
                        <a href="<?php echo esc_url(home_url('/member-register/')); ?>" class="scn-test-btn scn-test-btn-secondary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Member Register', 'scn-membership'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Test Instructions -->
            <div class="scn-test-section">
                <h2><?php _e('Test Instructions', 'scn-membership'); ?></h2>
                <div class="scn-test-instructions">
                    <ol>
                        <li><?php _e('Create a test user using the button above (admin only)', 'scn-membership'); ?></li>
                        <li><?php _e('Go to the Member Login page', 'scn-membership'); ?></li>
                        <li><?php _e('Login with credentials: testmember / TestMember123!', 'scn-membership'); ?></li>
                        <li><?php _e('You should be redirected to the member dashboard', 'scn-membership'); ?></li>
                        <li><?php _e('Test the dashboard functionality and profile management', 'scn-membership'); ?></li>
                    </ol>
                    
                    <div class="scn-test-urls">
                        <h3><?php _e('Test URLs:', 'scn-membership'); ?></h3>
                        <ul>
                            <li><strong><?php _e('Login:', 'scn-membership'); ?></strong> <a href="<?php echo esc_url(home_url('/member-login/')); ?>" target="_blank"><?php echo esc_url(home_url('/member-login/')); ?></a></li>
                            <li><strong><?php _e('Register:', 'scn-membership'); ?></strong> <a href="<?php echo esc_url(home_url('/member-register/')); ?>" target="_blank"><?php echo esc_url(home_url('/member-register/')); ?></a></li>
                            <li><strong><?php _e('Dashboard:', 'scn-membership'); ?></strong> <a href="<?php echo esc_url(home_url('/member-dashboard/')); ?>" target="_blank"><?php echo esc_url(home_url('/member-dashboard/')); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.scn-auth-test-page {
    padding: 40px 0;
    background: #f8f9fa;
    min-height: 100vh;
}

.scn-test-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px;
}

.scn-test-header {
    text-align: center;
    margin-bottom: 40px;
}

.scn-test-header h1 {
    color: #2c3e50;
    font-size: 2.5em;
    margin: 0 0 10px 0;
}

.scn-test-header p {
    color: #7f8c8d;
    font-size: 1.1em;
    margin: 0;
}

.scn-test-sections {
    display: grid;
    gap: 30px;
}

.scn-test-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.scn-test-section h2 {
    color: #2c3e50;
    margin: 0 0 20px 0;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.scn-status-grid {
    display: grid;
    gap: 15px;
}

.scn-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

.scn-status-label {
    font-weight: 600;
    color: #2c3e50;
}

.scn-status-value {
    font-weight: 600;
}

.scn-status-yes {
    color: #27ae60;
}

.scn-status-no {
    color: #e74c3c;
}

.scn-test-info {
    margin-bottom: 20px;
}

.scn-user-details {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    font-family: monospace;
    font-size: 0.9em;
}

.scn-test-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 5px;
}

.scn-test-btn-primary {
    background: #3498db;
    color: white;
}

.scn-test-btn-primary:hover {
    background: #2980b9;
    color: white;
    text-decoration: none;
}

.scn-test-btn-secondary {
    background: transparent;
    color: #3498db;
    border: 2px solid #3498db;
}

.scn-test-btn-secondary:hover {
    background: #3498db;
    color: white;
    text-decoration: none;
}

.scn-auth-links {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.scn-test-result {
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
}

.scn-test-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.scn-test-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.scn-test-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.scn-test-instructions {
    line-height: 1.6;
}

.scn-test-instructions ol {
    margin-bottom: 20px;
}

.scn-test-instructions li {
    margin-bottom: 8px;
}

.scn-test-urls {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.scn-test-urls h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.scn-test-urls ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.scn-test-urls li {
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #ecf0f1;
}

.scn-test-urls li:last-child {
    border-bottom: none;
}

.scn-test-urls a {
    color: #3498db;
    text-decoration: none;
    word-break: break-all;
}

.scn-test-urls a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .scn-test-container {
        padding: 0 15px;
    }
    
    .scn-test-section {
        padding: 20px;
    }
    
    .scn-auth-links {
        flex-direction: column;
    }
    
    .scn-test-btn {
        justify-content: center;
    }
}
</style>

<?php get_footer(); ?>
