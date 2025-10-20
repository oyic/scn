<?php
/**
 * Member Registration Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/member-dashboard/'));
    exit;
}

// Handle registration form submission
if ($_POST && isset($_POST['member_register'])) {
    $username = sanitize_text_field($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $terms = isset($_POST['terms']) ? true : false;
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = __('Username is required.', 'scn-membership');
    } elseif (username_exists($username)) {
        $errors[] = __('Username already exists.', 'scn-membership');
    }
    
    if (empty($email)) {
        $errors[] = __('Email is required.', 'scn-membership');
    } elseif (!is_email($email)) {
        $errors[] = __('Please enter a valid email address.', 'scn-membership');
    } elseif (email_exists($email)) {
        $errors[] = __('Email already exists.', 'scn-membership');
    }
    
    if (empty($password)) {
        $errors[] = __('Password is required.', 'scn-membership');
    } elseif (strlen($password) < 6) {
        $errors[] = __('Password must be at least 6 characters long.', 'scn-membership');
    }
    
    if ($password !== $confirm_password) {
        $errors[] = __('Passwords do not match.', 'scn-membership');
    }
    
    if (empty($first_name)) {
        $errors[] = __('First name is required.', 'scn-membership');
    }
    
    if (empty($last_name)) {
        $errors[] = __('Last name is required.', 'scn-membership');
    }
    
    if (!$terms) {
        $errors[] = __('You must agree to the terms and conditions.', 'scn-membership');
    }
    
    if (empty($errors)) {
        $user_id = wp_create_user($username, $password, $email);
        
        if (!is_wp_error($user_id)) {
            // Update user meta
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
            
            // Create profile post
            $profile_data = [
                'post_title' => $first_name . ' ' . $last_name,
                'post_type' => 'profile',
                'post_status' => 'publish',
                'post_author' => $user_id,
            ];
            
            $profile_id = wp_insert_post($profile_data);
            
            if ($profile_id) {
                // Set profile meta
                update_post_meta($profile_id, 'scn_user_id', $user_id);
                update_post_meta($profile_id, 'scn_first_name', $first_name);
                update_post_meta($profile_id, 'scn_last_name', $last_name);
                update_post_meta($profile_id, 'member_since', current_time('mysql'));
                
                // Send email verification instead of auto-login
                require_once plugin_dir_path(__FILE__) . '../../includes/email-confirmation.php';
                $email_confirmation = new SCNEmailConfirmation();
                $email_sent = $email_confirmation->sendConfirmationEmail($user_id, $email, $first_name);
                
                if ($email_sent) {
                    // Set success message and redirect to login with verification notice
                    wp_redirect(add_query_arg('scn_success', 'registration_complete', home_url('/member-login/')));
                    exit;
                } else {
                    // For development: if email fails, auto-verify the user
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        // Auto-verify user for development
                        update_user_meta($user_id, 'scn_account_verified', true);
                        update_user_meta($user_id, 'scn_verified_date', current_time('mysql'));
                        
                        // Auto-login user
                        wp_set_current_user($user_id);
                        wp_set_auth_cookie($user_id);
                        
                        // Redirect to dashboard
                        wp_redirect(home_url('/member-dashboard/'));
                        exit;
                    } else {
                        $errors[] = __('Account created but failed to send verification email. Please contact support.', 'scn-membership');
                    }
                }
            } else {
                $errors[] = __('Failed to create profile. Please try again.', 'scn-membership');
            }
        } else {
            $errors[] = $user_id->get_error_message();
        }
    }
}

get_header();
?>

<div class="scn-member-register-page">
    <div class="scn-register-container">
        <div class="scn-register-form-wrapper">
            <div class="scn-register-header">
                <h1><?php _e('Join SCN', 'scn-membership'); ?></h1>
                <p><?php _e('Create your member account to get started', 'scn-membership'); ?></p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="scn-register-errors">
                    <?php foreach ($errors as $error): ?>
                        <div class="scn-error-item">
                            <span class="dashicons dashicons-warning"></span>
                            <?php echo esc_html($error); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="scn-register-form" method="post" action="">
                <div class="scn-form-row">
                    <div class="scn-form-group">
                        <label for="first_name"><?php _e('First Name', 'scn-membership'); ?> <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required 
                               value="<?php echo esc_attr(isset($_POST['first_name']) ? $_POST['first_name'] : ''); ?>">
                        <span class="scn-input-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </span>
                    </div>

                    <div class="scn-form-group">
                        <label for="last_name"><?php _e('Last Name', 'scn-membership'); ?> <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required 
                               value="<?php echo esc_attr(isset($_POST['last_name']) ? $_POST['last_name'] : ''); ?>">
                        <span class="scn-input-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </span>
                    </div>
                </div>

                <div class="scn-form-group">
                    <label for="username"><?php _e('Username', 'scn-membership'); ?> <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo esc_attr(isset($_POST['username']) ? $_POST['username'] : ''); ?>">
                    <span class="scn-input-icon">
                        <span class="dashicons dashicons-admin-users"></span>
                    </span>
                </div>

                <div class="scn-form-group">
                    <label for="email"><?php _e('Email Address', 'scn-membership'); ?> <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo esc_attr(isset($_POST['email']) ? $_POST['email'] : ''); ?>">
                    <span class="scn-input-icon">
                        <span class="dashicons dashicons-email-alt"></span>
                    </span>
                </div>

                <div class="scn-form-row">
                    <div class="scn-form-group">
                        <label for="password"><?php _e('Password', 'scn-membership'); ?> <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required>
                        <span class="scn-input-icon">
                            <span class="dashicons dashicons-lock"></span>
                        </span>
                        <button type="button" class="scn-toggle-password" data-target="password">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>

                    <div class="scn-form-group">
                        <label for="confirm_password"><?php _e('Confirm Password', 'scn-membership'); ?> <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <span class="scn-input-icon">
                            <span class="dashicons dashicons-lock"></span>
                        </span>
                        <button type="button" class="scn-toggle-password" data-target="confirm_password">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                </div>

                <div class="scn-form-group">
                    <label class="scn-checkbox-label">
                        <input type="checkbox" name="terms" value="1" required>
                        <span class="scn-checkbox-custom"></span>
                        <?php printf(__('I agree to the %s and %s', 'scn-membership'), 
                            '<a href="#" target="_blank">' . __('Terms of Service', 'scn-membership') . '</a>',
                            '<a href="#" target="_blank">' . __('Privacy Policy', 'scn-membership') . '</a>'); ?>
                    </label>
                </div>

                <button type="submit" name="member_register" class="scn-register-btn">
                    <span class="scn-btn-text"><?php _e('Create Account', 'scn-membership'); ?></span>
                    <span class="scn-btn-loading" style="display: none;">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Creating Account...', 'scn-membership'); ?>
                    </span>
                </button>
            </form>

            <div class="scn-register-footer">
                <p><?php _e('Already have an account?', 'scn-membership'); ?></p>
                <a href="<?php echo esc_url(home_url('/member-login/')); ?>" class="scn-login-link">
                    <?php _e('Sign In', 'scn-membership'); ?>
                </a>
            </div>
        </div>

        <div class="scn-register-info">
            <div class="scn-info-content">
                <h2><?php _e('Why Join SCN?', 'scn-membership'); ?></h2>
                
                <div class="scn-benefits-list">
                    <div class="scn-benefit-item">
                        <span class="dashicons dashicons-awards"></span>
                        <div>
                            <h3><?php _e('Professional Profile', 'scn-membership'); ?></h3>
                            <p><?php _e('Showcase your expertise and achievements', 'scn-membership'); ?></p>
                        </div>
                    </div>
                    
                    <div class="scn-benefit-item">
                        <span class="dashicons dashicons-microphone"></span>
                        <div>
                            <h3><?php _e('Speaking Opportunities', 'scn-membership'); ?></h3>
                            <p><?php _e('Connect with event organizers and speakers', 'scn-membership'); ?></p>
                        </div>
                    </div>
                    
                    <div class="scn-benefit-item">
                        <span class="dashicons dashicons-networking"></span>
                        <div>
                            <h3><?php _e('Networking', 'scn-membership'); ?></h3>
                            <p><?php _e('Build relationships with industry professionals', 'scn-membership'); ?></p>
                        </div>
                    </div>
                    
                    <div class="scn-benefit-item">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <div>
                            <h3><?php _e('Knowledge Sharing', 'scn-membership'); ?></h3>
                            <p><?php _e('Share your knowledge through courses and content', 'scn-membership'); ?></p>
                        </div>
                    </div>
                    
                    <div class="scn-benefit-item">
                        <span class="dashicons dashicons-groups"></span>
                        <div>
                            <h3><?php _e('Community', 'scn-membership'); ?></h3>
                            <p><?php _e('Join a vibrant community of thought leaders', 'scn-membership'); ?></p>
                        </div>
                    </div>
                    
                    <div class="scn-benefit-item">
                        <span class="dashicons dashicons-chart-line"></span>
                        <div>
                            <h3><?php _e('Growth', 'scn-membership'); ?></h3>
                            <p><?php _e('Track your professional development and impact', 'scn-membership'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.scn-member-register-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.scn-register-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    max-width: 1100px;
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 700px;
}

.scn-register-form-wrapper {
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    max-height: 100vh;
    overflow-y: auto;
}

.scn-register-header {
    text-align: center;
    margin-bottom: 40px;
}

.scn-register-header h1 {
    color: #2c3e50;
    font-size: 2.5em;
    margin: 0 0 10px 0;
    font-weight: 300;
}

.scn-register-header p {
    color: #7f8c8d;
    font-size: 1.1em;
    margin: 0;
}

.scn-register-errors {
    margin-bottom: 25px;
}

.scn-error-item {
    background: #fee;
    border: 1px solid #fcc;
    color: #c33;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.scn-error-item .dashicons {
    color: #c33;
}

.scn-register-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.scn-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
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

.scn-form-group label .required {
    color: #e74c3c;
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

.scn-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    color: #7f8c8d;
    font-size: 0.9em;
    line-height: 1.4;
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
    flex-shrink: 0;
    margin-top: 2px;
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

.scn-checkbox-label a {
    color: #3498db;
    text-decoration: none;
}

.scn-checkbox-label a:hover {
    text-decoration: underline;
}

.scn-register-btn {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
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
    margin-top: 10px;
}

.scn-register-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(39, 174, 96, 0.3);
}

.scn-register-btn .scn-btn-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.scn-register-btn .scn-btn-loading .dashicons {
    animation: spin 1s linear infinite;
}

.scn-register-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #ecf0f1;
}

.scn-register-footer p {
    color: #7f8c8d;
    margin: 0 0 15px 0;
}

.scn-login-link {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
    padding: 12px 25px;
    border: 2px solid #3498db;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: inline-block;
}

.scn-login-link:hover {
    background: #3498db;
    color: white;
    text-decoration: none;
}

.scn-register-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 50px;
    color: white;
    display: flex;
    align-items: center;
    max-height: 100vh;
    overflow-y: auto;
}

.scn-info-content h2 {
    font-size: 2.2em;
    margin: 0 0 30px 0;
    font-weight: 300;
}

.scn-benefits-list {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.scn-benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.scn-benefit-item .dashicons {
    font-size: 1.8em;
    margin-top: 5px;
    opacity: 0.9;
}

.scn-benefit-item h3 {
    margin: 0 0 5px 0;
    font-size: 1.1em;
    font-weight: 600;
}

.scn-benefit-item p {
    margin: 0;
    opacity: 0.8;
    font-size: 0.9em;
    line-height: 1.4;
}

/* Responsive Design */
@media (max-width: 768px) {
    .scn-register-container {
        grid-template-columns: 1fr;
        margin: 20px;
        border-radius: 15px;
    }
    
    .scn-register-form-wrapper {
        padding: 40px 30px;
    }
    
    .scn-register-info {
        padding: 40px 30px;
        order: -1;
    }
    
    .scn-form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .scn-register-header h1 {
        font-size: 2em;
    }
    
    .scn-info-content h2 {
        font-size: 1.8em;
    }
}

@media (max-width: 480px) {
    .scn-member-register-page {
        padding: 10px;
    }
    
    .scn-register-container {
        margin: 10px;
        border-radius: 10px;
    }
    
    .scn-register-form-wrapper,
    .scn-register-info {
        padding: 30px 20px;
    }
    
    .scn-register-header h1 {
        font-size: 1.8em;
    }
    
    .scn-form-group input {
        padding: 12px 15px 12px 45px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle password visibility
    $('.scn-toggle-password').on('click', function() {
        const target = $(this).data('target');
        const input = $('#' + target);
        const icon = $(this).find('.dashicons');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            input.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // Form submission with loading state
    $('.scn-register-form').on('submit', function() {
        const $btn = $(this).find('.scn-register-btn');
        const $btnText = $btn.find('.scn-btn-text');
        const $btnLoading = $btn.find('.scn-btn-loading');
        
        $btnText.hide();
        $btnLoading.show();
        $btn.prop('disabled', true);
    });
    
    // Password confirmation validation
    $('#confirm_password').on('input', function() {
        const password = $('#password').val();
        const confirm = $(this).val();
        
        if (password !== confirm && confirm.length > 0) {
            $(this).css('border-color', '#e74c3c');
        } else {
            $(this).css('border-color', '#ecf0f1');
        }
    });
    
    // Auto-focus first name field
    $('#first_name').focus();
});
</script>

<?php get_footer(); ?>
