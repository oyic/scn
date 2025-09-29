<?php
/**
 * Email Confirmation System
 * Handles email confirmation for profile activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCNEmailConfirmation {
    
    public function __construct() {
        add_action('init', [$this, 'handleEmailConfirmation']);
        add_action('wp_ajax_scn_resend_confirmation', [$this, 'resendConfirmationEmail']);
        add_action('wp_ajax_nopriv_scn_resend_confirmation', [$this, 'resendConfirmationEmail']);
    }
    
    /**
     * Generate confirmation token
     */
    public function generateConfirmationToken($user_id) {
        $token = wp_generate_password(32, false);
        update_user_meta($user_id, 'scn_confirmation_token', $token);
        update_user_meta($user_id, 'scn_confirmation_expires', time() + (24 * 60 * 60)); // 24 hours
        return $token;
    }
    
    /**
     * Send confirmation email
     */
    public function sendConfirmationEmail($user_id, $email, $first_name) {
        $token = $this->generateConfirmationToken($user_id);
        $confirmation_url = add_query_arg([
            'scn_confirm' => '1',
            'token' => $token,
            'user_id' => $user_id
        ], home_url());
        
        $subject = __('Confirm Your SCN Membership', 'scn-membership');
        
        $message = sprintf(
            __('Hello %s,

Thank you for joining SCN! To complete your registration and activate your profile, please click the confirmation link below:

%s

This link will expire in 24 hours.

If you did not create an account with SCN, please ignore this email.

Best regards,
The SCN Team', 'scn-membership'),
            $first_name,
            $confirmation_url
        );
        
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: SCN <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        ];
        
        // Try wp_mail first
        $result = wp_mail($email, $subject, $message, $headers);
        
        // If wp_mail fails, try alternative methods
        if (!$result) {
            // Log the failure
            error_log('SCN Email: wp_mail failed for user ' . $user_id);
            
            // Try direct mail function as fallback
            $result = $this->sendEmailFallback($email, $subject, $message);
        }
        
        return $result;
    }
    
    /**
     * Fallback email sending method
     */
    private function sendEmailFallback($email, $subject, $message) {
        // For development, you can log the email instead of sending
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("SCN Email Fallback - To: $email, Subject: $subject");
            error_log("SCN Email Content: $message");
            
            // In development, consider this a success
            return true;
        }
        
        // Try using PHP's mail function directly
        $headers = "From: SCN <noreply@" . parse_url(home_url(), PHP_URL_HOST) . ">\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Handle email confirmation
     */
    public function handleEmailConfirmation() {
        if (!isset($_GET['scn_confirm']) || !isset($_GET['token']) || !isset($_GET['user_id'])) {
            return;
        }
        
        $token = sanitize_text_field($_GET['token']);
        $user_id = intval($_GET['user_id']);
        
        // Verify token
        $stored_token = get_user_meta($user_id, 'scn_confirmation_token', true);
        $expires = get_user_meta($user_id, 'scn_confirmation_expires', true);
        
        if (!$stored_token || $stored_token !== $token) {
            wp_redirect(add_query_arg('scn_error', 'invalid_token', home_url('/member-login/')));
            exit;
        }
        
        if ($expires && time() > $expires) {
            wp_redirect(add_query_arg('scn_error', 'expired_token', home_url('/member-login/')));
            exit;
        }
        
        // Activate user
        $this->activateUser($user_id);
        
        // Clear confirmation data
        delete_user_meta($user_id, 'scn_confirmation_token');
        delete_user_meta($user_id, 'scn_confirmation_expires');
        
        // Redirect to dashboard
        wp_redirect(add_query_arg('scn_success', 'confirmed', home_url('/member-dashboard/')));
        exit;
    }
    
    /**
     * Activate user account
     */
    private function activateUser($user_id) {
        // Update user status
        update_user_meta($user_id, 'scn_account_verified', true);
        update_user_meta($user_id, 'scn_verified_date', current_time('mysql'));
        
        // Auto-login user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        // Update profile status
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
            update_post_meta($profile_posts[0]->ID, 'scn_profile_verified', true);
            update_post_meta($profile_posts[0]->ID, 'scn_verified_date', current_time('mysql'));
        }
    }
    
    /**
     * Resend confirmation email
     */
    public function resendConfirmationEmail() {
        if (!isset($_POST['email'])) {
            wp_die('Invalid request');
        }
        
        $email = sanitize_email($_POST['email']);
        $user = get_user_by('email', $email);
        
        if (!$user) {
            wp_send_json_error(['message' => __('Email not found.', 'scn-membership')]);
        }
        
        $first_name = get_user_meta($user->ID, 'first_name', true);
        if (!$first_name) {
            $first_name = $user->display_name;
        }
        
        $sent = $this->sendConfirmationEmail($user->ID, $email, $first_name);
        
        if ($sent) {
            wp_send_json_success(['message' => __('Confirmation email sent!', 'scn-membership')]);
        } else {
            wp_send_json_error(['message' => __('Failed to send email. Please try again.', 'scn-membership')]);
        }
    }
    
    /**
     * Check if user is verified
     */
    public static function isUserVerified($user_id) {
        return get_user_meta($user_id, 'scn_account_verified', true) === '1';
    }
}

// Initialize email confirmation system
new SCNEmailConfirmation();
