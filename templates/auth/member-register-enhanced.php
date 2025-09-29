<?php
/**
 * Enhanced Member Registration Page Template
 * Streamlined form with only necessary fields
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
if ($_POST && isset($_POST['scn_member_register'])) {
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']) ? true : false;
    
    $errors = [];
    
    // Validation
    if (empty($first_name)) {
        $errors[] = __('First name is required.', 'scn-membership');
    }
    
    if (empty($last_name)) {
        $errors[] = __('Last name is required.', 'scn-membership');
    }
    
    if (empty($email)) {
        $errors[] = __('Email address is required.', 'scn-membership');
    } elseif (!is_email($email)) {
        $errors[] = __('Please enter a valid email address.', 'scn-membership');
    } elseif (email_exists($email)) {
        $errors[] = __('An account with this email already exists.', 'scn-membership');
    }
    
    if (empty($password)) {
        $errors[] = __('Password is required.', 'scn-membership');
    } elseif (strlen($password) < 8) {
        $errors[] = __('Password must be at least 8 characters long.', 'scn-membership');
    }
    
    if ($password !== $confirm_password) {
        $errors[] = __('Passwords do not match.', 'scn-membership');
    }
    
    if (!$terms) {
        $errors[] = __('You must agree to the terms and conditions.', 'scn-membership');
    }
    
    if (empty($errors)) {
        // Generate username from email
        $username = sanitize_user($email);
        $original_username = $username;
        $counter = 1;
        
        // Ensure username is unique
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (!is_wp_error($user_id)) {
            // Update user meta
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
            update_user_meta($user_id, 'display_name', $first_name . ' ' . $last_name);
            
            // Create profile post
            $profile_data = [
                'post_title' => $first_name . ' ' . $last_name,
                'post_type' => 'scn_profile',
                'post_status' => 'publish',
                'post_author' => $user_id,
            ];
            
            $profile_id = wp_insert_post($profile_data);
            
            if ($profile_id) {
                // Set profile meta
                update_post_meta($profile_id, 'scn_user_id', $user_id);
                update_post_meta($profile_id, 'scn_first_name', $first_name);
                update_post_meta($profile_id, 'scn_last_name', $last_name);
                update_post_meta($profile_id, 'scn_email', $email);
                update_post_meta($profile_id, 'scn_member_since', current_time('mysql'));
                
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

<style>
.scn-member-register-page {
    min-height: 100vh;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.scn-register-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    overflow: hidden;
    max-width: 500px;
    width: 100%;
}

.scn-register-form-wrapper {
    padding: 50px 40px;
}

.scn-register-header {
    text-align: center;
    margin-bottom: 40px;
}

.scn-register-header h1 {
    color: #2c3e50;
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 10px 0;
}

.scn-register-header p {
    color: #7f8c8d;
    font-size: 16px;
    margin: 0;
}

.scn-register-errors {
    margin-bottom: 25px;
}

.scn-error-item {
    background: #fee;
    color: #c0392b;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-left: 4px solid #e74c3c;
}

.scn-register-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.scn-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.scn-form-group {
    position: relative;
}

.scn-form-group label {
    display: block;
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}

.scn-form-group input {
    width: 100%;
    padding: 15px;
    border: 2px solid #ecf0f1;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.scn-form-group input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.scn-password-group {
    position: relative;
}

.scn-password-group input {
    padding-right: 50px;
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
    padding: 5px;
    font-size: 18px;
}

.scn-toggle-password:hover {
    color: #3498db;
}

.scn-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    color: #7f8c8d;
    font-size: 14px;
    line-height: 1.4;
    width: 100%;
}

.scn-checkbox-label input[type="checkbox"] {
    display: none;
}

.scn-checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #bdc3c7;
    border-radius: 4px;
    position: relative;
    transition: all 0.3s ease;
    flex-shrink: 0;
    margin-top: 2px;
    display: inline-block;
    min-width: 20px;
    min-height: 20px;
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
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 18px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.scn-register-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
}

.scn-register-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
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

.scn-password-strength {
    margin-top: 5px;
    font-size: 12px;
}

.scn-password-strength.weak {
    color: #e74c3c;
}

.scn-password-strength.medium {
    color: #f39c12;
}

.scn-password-strength.strong {
    color: #27ae60;
}

@media (max-width: 480px) {
    .scn-register-form-wrapper {
        padding: 40px 30px;
    }
    
    .scn-form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .scn-register-header h1 {
        font-size: 28px;
    }
}
</style>

<div class="scn-member-register-page">
    <div class="scn-register-container">
        <div class="scn-register-form-wrapper">
            <div class="scn-register-header">
                <h1><?php _e('Join SCN', 'scn-membership'); ?></h1>
                <p><?php _e('Create your member account', 'scn-membership'); ?></p>
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
                        <label for="first_name"><?php _e('First Name', 'scn-membership'); ?> <span style="color: #e74c3c;">*</span></label>
                        <input type="text" id="first_name" name="first_name" required 
                               value="<?php echo esc_attr(isset($_POST['first_name']) ? $_POST['first_name'] : ''); ?>"
                               placeholder="<?php _e('Enter your first name', 'scn-membership'); ?>">
                    </div>

                    <div class="scn-form-group">
                        <label for="last_name"><?php _e('Last Name', 'scn-membership'); ?> <span style="color: #e74c3c;">*</span></label>
                        <input type="text" id="last_name" name="last_name" required 
                               value="<?php echo esc_attr(isset($_POST['last_name']) ? $_POST['last_name'] : ''); ?>"
                               placeholder="<?php _e('Enter your last name', 'scn-membership'); ?>">
                    </div>
                </div>

                <div class="scn-form-group">
                    <label for="email"><?php _e('Email Address', 'scn-membership'); ?> <span style="color: #e74c3c;">*</span></label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo esc_attr(isset($_POST['email']) ? $_POST['email'] : ''); ?>"
                           placeholder="<?php _e('Enter your email address', 'scn-membership'); ?>">
                </div>

                <div class="scn-form-group scn-password-group">
                    <label for="password"><?php _e('Password', 'scn-membership'); ?> <span style="color: #e74c3c;">*</span></label>
                    <input type="password" id="password" name="password" required
                           placeholder="<?php _e('Create a strong password', 'scn-membership'); ?>">
                    <button type="button" class="scn-toggle-password" data-target="password">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
                    <div class="scn-password-strength" id="password-strength"></div>
                </div>

                <div class="scn-form-group scn-password-group">
                    <label for="confirm_password"><?php _e('Confirm Password', 'scn-membership'); ?> <span style="color: #e74c3c;">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="<?php _e('Confirm your password', 'scn-membership'); ?>">
                    <button type="button" class="scn-toggle-password" data-target="confirm_password">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
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

                <button type="submit" name="scn_member_register" class="scn-register-btn" id="register-btn">
                    <?php _e('Create Account', 'scn-membership'); ?>
                </button>
            </form>

            <div class="scn-register-footer">
                <p><?php _e('Already have an account?', 'scn-membership'); ?></p>
                <a href="<?php echo esc_url(home_url('/member-login/')); ?>" class="scn-login-link">
                    <?php _e('Sign In', 'scn-membership'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

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
    
    // Password strength indicator
    $('#password').on('input', function() {
        const password = $(this).val();
        const strength = getPasswordStrength(password);
        const $strength = $('#password-strength');
        
        if (password.length === 0) {
            $strength.text('').removeClass('weak medium strong');
            return;
        }
        
        if (strength < 3) {
            $strength.text('Weak password').removeClass('medium strong').addClass('weak');
        } else if (strength < 5) {
            $strength.text('Medium password').removeClass('weak strong').addClass('medium');
        } else {
            $strength.text('Strong password').removeClass('weak medium').addClass('strong');
        }
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
    
    // Form submission
    $('.scn-register-form').on('submit', function() {
        const $btn = $('#register-btn');
        $btn.prop('disabled', true).text('Creating Account...');
    });
    
    // Auto-focus first name field
    $('#first_name').focus();
    
    function getPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        return strength;
    }
});
</script>

<?php get_footer(); ?>
