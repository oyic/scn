<?php
/**
 * Email Debug and Configuration
 * Helps diagnose and fix email sending issues
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCNEmailDebug {
    
    public function __construct() {
        add_action('wp_ajax_scn_test_email', [$this, 'testEmail']);
        add_action('wp_ajax_nopriv_scn_test_email', [$this, 'testEmail']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
    }
    
    public function addAdminMenu() {
        add_submenu_page(
            'tools.php',
            'SCN Email Debug',
            'SCN Email Debug',
            'manage_options',
            'scn-email-debug',
            [$this, 'adminPage']
        );
    }
    
    public function adminPage() {
        ?>
        <div class="wrap">
            <h1>SCN Email Debug</h1>
            
            <div class="card">
                <h2>Email Configuration Test</h2>
                <p>This will test if WordPress can send emails.</p>
                <button id="test-email-btn" class="button button-primary">Test Email</button>
                <div id="test-result"></div>
            </div>
            
            <div class="card">
                <h2>Current Email Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>wp_mail() Function</th>
                        <td><?php echo function_exists('wp_mail') ? '✅ Available' : '❌ Not Available'; ?></td>
                    </tr>
                    <tr>
                        <th>SMTP Settings</th>
                        <td>
                            <?php
                            if (defined('SMTP_HOST')) {
                                echo '✅ SMTP Configured: ' . constant('SMTP_HOST');
                            } else {
                                echo '❌ No SMTP Configuration';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Mail Function</th>
                        <td><?php echo function_exists('mail') ? '✅ Available' : '❌ Not Available'; ?></td>
                    </tr>
                    <tr>
                        <th>Site URL</th>
                        <td><?php echo home_url(); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h2>Quick Fixes</h2>
                <h3>Option 1: Add SMTP Configuration</h3>
                <p>Add this to your wp-config.php file:</p>
                <textarea readonly style="width: 100%; height: 150px;">// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_FROM', 'your-email@gmail.com');
define('SMTP_FROMNAME', 'Your Site Name');</textarea>
                
                <h3>Option 2: Use Local Development</h3>
                <p>For local development, you can use a service like MailHog or Mailtrap.</p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-email-btn').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'scn_test_email',
                        nonce: '<?php echo wp_create_nonce('scn_test_email'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#test-result').html('<div class="notice notice-success"><p>✅ Email sent successfully!</p></div>');
                        } else {
                            $('#test-result').html('<div class="notice notice-error"><p>❌ Email failed: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#test-result').html('<div class="notice notice-error"><p>❌ AJAX request failed</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Email');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function testEmail() {
        if (!wp_verify_nonce($_POST['nonce'], 'scn_test_email')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $admin_email = get_option('admin_email');
        $subject = 'SCN Email Test';
        $message = 'This is a test email from SCN Membership plugin.';
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: SCN Test <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        ];
        
        $result = wp_mail($admin_email, $subject, $message, $headers);
        
        if ($result) {
            wp_send_json_success('Email sent successfully to ' . $admin_email);
        } else {
            wp_send_json_error('Failed to send email. Check your email configuration.');
        }
    }
}

// Initialize email debug
new SCNEmailDebug();
