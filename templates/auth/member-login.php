<?php
/**
 * Member Login Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/member-dashboard/'));
    exit;
}

// Handle login form submission
if ($_POST && isset($_POST['scn_member_login'])) {
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['rememberme']) ? true : false;
    
    $creds = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    ];
    
    $user = wp_signon($creds, false);
    
    if (!is_wp_error($user)) {
        // Check if user has a profile
        $profile_posts = get_posts([
            'post_type' => 'scn_profile',
            'meta_query' => [
                [
                    'key' => 'scn_user_id',
                    'value' => $user->ID,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (!empty($profile_posts)) {
            // User has profile, redirect to dashboard
            wp_redirect(home_url('/member-dashboard/'));
        } else {
            // User doesn't have profile, redirect to profile creation
            wp_redirect(admin_url('post-new.php?post_type=scn_profile'));
        }
        exit;
    } else {
        $login_error = $user->get_error_message();
    }
}

get_header();
?>

<div class="scn-member-login-page">
    <div class="scn-login-container">
        <div class="scn-login-form-wrapper">
            <div class="scn-login-header">
                <h1><?php _e('SCN Member Login', 'scn-membership'); ?></h1>
                <p><?php _e('Sign in to access your member dashboard', 'scn-membership'); ?></p>
            </div>

            <?php if (isset($login_error)): ?>
                <div class="scn-login-error">
                    <span class="dashicons dashicons-warning"></span>
                    <?php echo esc_html($login_error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['scn_success']) && $_GET['scn_success'] === 'registration_complete'): ?>
                <div class="scn-login-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong><?php _e('Registration Successful!', 'scn-membership'); ?></strong><br>
                    <?php _e('Please check your email and click the verification link to activate your account.', 'scn-membership'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['scn_success']) && $_GET['scn_success'] === 'confirmed'): ?>
                <div class="scn-login-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong><?php _e('Email Verified!', 'scn-membership'); ?></strong><br>
                    <?php _e('Your account has been activated. You can now sign in.', 'scn-membership'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['scn_error']) && $_GET['scn_error'] === 'invalid_token'): ?>
                <div class="scn-login-error">
                    <span class="dashicons dashicons-warning"></span>
                    <strong><?php _e('Invalid Verification Link', 'scn-membership'); ?></strong><br>
                    <?php _e('The verification link is invalid or has already been used.', 'scn-membership'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['scn_error']) && $_GET['scn_error'] === 'expired_token'): ?>
                <div class="scn-login-error">
                    <span class="dashicons dashicons-warning"></span>
                    <strong><?php _e('Verification Link Expired', 'scn-membership'); ?></strong><br>
                    <?php _e('The verification link has expired. Please request a new one.', 'scn-membership'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_POST['scn_member_login'])): ?>
                <div class="scn-debug-info" style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; font-size: 12px;">
                    <strong>Debug Info:</strong><br>
                    Username: <?php echo esc_html($_POST['username'] ?? 'not set'); ?><br>
                    Password: <?php echo !empty($_POST['password']) ? '[SET]' : '[NOT SET]'; ?><br>
                    Remember: <?php echo isset($_POST['rememberme']) ? 'Yes' : 'No'; ?><br>
                    <?php if (function_exists('is_user_logged_in') && is_user_logged_in()): ?>
                        Current User: <?php echo wp_get_current_user()->user_login; ?><br>
                        <?php
                        // Get profile information instead of just user info
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
                        
                        if (!empty($profile_posts)) {
                            $profile = $profile_posts[0];
                            $first_name = get_post_meta($profile->ID, 'scn_first_name', true);
                            $last_name = get_post_meta($profile->ID, 'scn_last_name', true);
                            $credentials = get_post_meta($profile->ID, 'scn_credentials', true);
                            $location = get_post_meta($profile->ID, 'scn_location', true);
                            
                            echo "Profile Name: " . esc_html(trim($first_name . ' ' . $last_name)) . "<br>";
                            if ($credentials) echo "Credentials: " . esc_html($credentials) . "<br>";
                            if ($location) echo "Location: " . esc_html($location) . "<br>";
                            echo "Profile ID: " . $profile->ID . "<br>";
                        } else {
                            echo "No profile found for this user<br>";
                        }
                        ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Demo Login Section -->
            <div class="scn-demo-login" style="background: #e8f4fd; border: 1px solid #3498db; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                <h3 style="color: #2c3e50; margin: 0 0 15px 0; font-size: 18px;"><?php _e('Demo Login', 'scn-membership'); ?></h3>
                <p style="margin: 0 0 15px 0; color: #7f8c8d;"><?php _e('Use these credentials to test the system:', 'scn-membership'); ?></p>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center;">
                    <div>
                        <strong><?php _e('Username:', 'scn-membership'); ?></strong> testmember<br>
                        <strong><?php _e('Password:', 'scn-membership'); ?></strong> TestMember123!
                    </div>
                    <button type="button" id="scn-fill-demo" class="scn-btn" style="background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;">
                        <?php _e('Fill Demo Credentials', 'scn-membership'); ?>
                    </button>
                </div>
            </div>

            <form class="scn-login-form" method="post" action="">
                <div class="scn-form-group">
                    <label for="username"><?php _e('Username or Email', 'scn-membership'); ?></label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo esc_attr(isset($_POST['username']) ? $_POST['username'] : ''); ?>">
                    <span class="scn-input-icon">
                        <span class="dashicons dashicons-admin-users"></span>
                    </span>
                </div>

                <div class="scn-form-group">
                    <label for="password"><?php _e('Password', 'scn-membership'); ?></label>
                    <input type="password" id="password" name="password" required>
                    <span class="scn-input-icon">
                        <span class="dashicons dashicons-lock"></span>
                    </span>
                    <button type="button" class="scn-toggle-password" data-target="password">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
                </div>

                <div class="scn-form-options">
                    <label class="scn-checkbox-label">
                        <input type="checkbox" name="rememberme" value="1">
                        <span class="scn-checkbox-custom"></span>
                        <?php _e('Remember me', 'scn-membership'); ?>
                    </label>
                    
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="scn-forgot-password">
                        <?php _e('Forgot Password?', 'scn-membership'); ?>
                    </a>
                </div>

                <button type="submit" name="scn_member_login" class="scn-login-btn">
                    <span class="scn-btn-text"><?php _e('Sign In', 'scn-membership'); ?></span>
                    <span class="scn-btn-loading" style="display: none;">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Signing in...', 'scn-membership'); ?>
                    </span>
                </button>
            </form>

            <div class="scn-login-footer">
                <p><?php _e("Don't have an account?", 'scn-membership'); ?></p>
                <a href="<?php echo esc_url(home_url('/member-register/')); ?>" class="scn-register-link">
                    <?php _e('Register Account', 'scn-membership'); ?>
                </a>
            </div>
        </div>

        <div class="scn-login-info">
            <div class="scn-info-content">
                <h2><?php _e('Welcome to SCN', 'scn-membership'); ?></h2>
                <p><?php _e('Join our community of speakers, educators, and thought leaders.', 'scn-membership'); ?></p>
                
                <div class="scn-features-list">
                    <div class="scn-feature-item">
                        <span class="dashicons dashicons-admin-users"></span>
                        <div>
                            <h3><?php _e('Member Profile', 'scn-membership'); ?></h3>
                            <p><?php _e('Create and manage your professional profile', 'scn-membership'); ?></p>
                        </div>
                    </div>
                    
                    <div class="scn-feature-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <div>
                            <h3><?php _e('Event Management', 'scn-membership'); ?></h3>
                            <p><?php _e('Schedule and manage your speaking sessions', 'scn-membership'); ?></p>
                        </div>
                    </div>
                    
                    <div class="scn-feature-item">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <div>
                            <h3><?php _e('Course Creation', 'scn-membership'); ?></h3>
                            <p><?php _e('Share your knowledge through courses', 'scn-membership'); ?></p>
                        </div>
                    </div>
                    
                    <div class="scn-feature-item">
                        <span class="dashicons dashicons-groups"></span>
                        <div>
                            <h3><?php _e('Networking', 'scn-membership'); ?></h3>
                            <p><?php _e('Connect with other professionals', 'scn-membership'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.scn-member-login-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.scn-login-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    max-width: 1000px;
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 600px;
}

.scn-login-form-wrapper {
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.scn-login-header {
    text-align: center;
    margin-bottom: 40px;
}

.scn-login-header h1 {
    color: #2c3e50;
    font-size: 2.5em;
    margin: 0 0 10px 0;
    font-weight: 300;
}

.scn-login-header p {
    color: #7f8c8d;
    font-size: 1.1em;
    margin: 0;
}

.scn-login-error {
    background: #fee;
    border: 1px solid #fcc;
    color: #c33;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.scn-login-success {
    background: #efe;
    border: 1px solid #cfc;
    color: #3c3;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    border-left: 4px solid #27ae60;
}

.scn-login-success .dashicons {
    color: #27ae60;
    margin-top: 2px;
}

.scn-login-error .dashicons {
    color: #c33;
}

.scn-login-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.scn-form-group {
    position: relative;
}

.scn-form-group label {
    display: block;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.9em;
}

.scn-form-group input {
    width: 100%;
    padding: 15px 20px 15px 50px;
    border: 2px solid #ecf0f1;
    border-radius: 10px;
    font-size: 1em;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.scn-form-group input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.scn-input-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #bdc3c7;
    margin-top: 12px;
}

.scn-toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #bdc3c7;
    cursor: pointer;
    margin-top: 12px;
    padding: 5px;
}

.scn-toggle-password:hover {
    color: #3498db;
}

.scn-form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 10px 0;
}

.scn-checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    color: #7f8c8d;
    font-size: 0.9em;
}

.scn-checkbox-label input[type="checkbox"] {
    display: none;
}

.scn-checkbox-custom {
    width: 18px;
    height: 18px;
    border: 2px solid #bdc3c7;
    border-radius: 4px;
    position: relative;
    transition: all 0.3s ease;
}

.scn-checkbox-label input[type="checkbox"]:checked + .scn-checkbox-custom {
    background: #3498db;
    border-color: #3498db;
}

.scn-checkbox-label input[type="checkbox"]:checked + .scn-checkbox-custom::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.scn-forgot-password {
    color: #3498db;
    text-decoration: none;
    font-size: 0.9em;
    transition: color 0.3s ease;
}

.scn-forgot-password:hover {
    color: #2980b9;
    text-decoration: underline;
}

.scn-login-btn {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 18px;
    border-radius: 10px;
    font-size: 1.1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.scn-login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
}

.scn-login-btn:active {
    transform: translateY(0);
}

.scn-login-btn .scn-btn-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.scn-login-btn .scn-btn-loading .dashicons {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.scn-login-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #ecf0f1;
}

.scn-login-footer p {
    color: #7f8c8d;
    margin: 0 0 15px 0;
}

.scn-register-link {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
    padding: 12px 25px;
    border: 2px solid #3498db;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: inline-block;
}

.scn-register-link:hover {
    background: #3498db;
    color: white;
    text-decoration: none;
}

.scn-login-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 50px;
    color: white;
    display: flex;
    align-items: center;
}

.scn-info-content h2 {
    font-size: 2.2em;
    margin: 0 0 20px 0;
    font-weight: 300;
}

.scn-info-content > p {
    font-size: 1.1em;
    margin: 0 0 40px 0;
    opacity: 0.9;
    line-height: 1.6;
}

.scn-features-list {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.scn-feature-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.scn-feature-item .dashicons {
    font-size: 1.8em;
    margin-top: 5px;
    opacity: 0.9;
}

.scn-feature-item h3 {
    margin: 0 0 5px 0;
    font-size: 1.1em;
    font-weight: 600;
}

.scn-feature-item p {
    margin: 0;
    opacity: 0.8;
    font-size: 0.9em;
    line-height: 1.4;
}

/* Responsive Design */
@media (max-width: 768px) {
    .scn-login-container {
        grid-template-columns: 1fr;
        margin: 20px;
        border-radius: 15px;
    }
    
    .scn-login-form-wrapper {
        padding: 40px 30px;
    }
    
    .scn-login-info {
        padding: 40px 30px;
        order: -1;
    }
    
    .scn-login-header h1 {
        font-size: 2em;
    }
    
    .scn-info-content h2 {
        font-size: 1.8em;
    }
    
    .scn-form-options {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .scn-member-login-page {
        padding: 10px;
    }
    
    .scn-login-container {
        margin: 10px;
        border-radius: 10px;
    }
    
    .scn-login-form-wrapper,
    .scn-login-info {
        padding: 30px 20px;
    }
    
    .scn-login-header h1 {
        font-size: 1.8em;
    }
    
    .scn-form-group input {
        padding: 12px 15px 12px 45px;
    }
}
</style>

<script>
// Simple, error-free JavaScript
jQuery(document).ready(function($) {
    console.log('Login page loaded');
    
    // Toggle password visibility
    $('.scn-toggle-password').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var input = $('#' + target);
        var icon = $(this).find('.dashicons');
        
        if (input.length && input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else if (input.length) {
            input.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // Form submission with loading state
    $('.scn-login-form').on('submit', function() {
        var $btn = $(this).find('.scn-login-btn');
        var $btnText = $btn.find('.scn-btn-text');
        var $btnLoading = $btn.find('.scn-btn-loading');
        
        if ($btnText.length && $btnLoading.length) {
            $btnText.hide();
            $btnLoading.show();
            $btn.prop('disabled', true);
        }
    });
    
    // Auto-focus username field
    var $username = $('#username');
    if ($username.length) {
        $username.focus();
    }
    
    // Demo login fill functionality
    $('#scn-fill-demo').on('click', function(e) {
        e.preventDefault();
        $('#username').val('testmember');
        $('#password').val('TestMember123!');
        console.log('Demo credentials filled');
    });
    
    console.log('Login page JavaScript initialized');
});
</script>

<?php get_footer(); ?>
