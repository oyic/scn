<?php
/**
 * Test script to verify password email functionality
 * Run this from WordPress admin or via WP-CLI
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if we're in admin or can run this test
if (!current_user_can('manage_options') && !defined('WP_CLI')) {
    wp_die('You do not have permission to run this test.');
}

echo "<h1>SCN Membership - Password Email Test</h1>";

// Test the email functionality
function testPasswordEmail() {
    $test_email = 'test@example.com';
    $test_first_name = 'Test';
    $test_username = 'test.user';
    $test_password = 'testpass123';
    
    // Create a test user
    $user_id = wp_create_user($test_username, $test_password, $test_email);
    
    if (is_wp_error($user_id)) {
        echo "<div class='error'>❌ Failed to create test user: " . $user_id->get_error_message() . "</div>";
        return false;
    }
    
    echo "<div class='success'>✅ Test user created with ID: {$user_id}</div>";
    
    // Test the email sending
    $subject = 'Test SCN Membership Account Details';
    $login_url = home_url('/member-login/');
    
    $message = sprintf(
        'Hello %s,

Welcome to SCN! Your membership account has been created successfully.

Your account details:
Username: %s
Password: %s
Login URL: %s

Please keep this information secure and consider changing your password after your first login.

If you have any questions, please contact us.

Best regards,
The SCN Team',
        $test_first_name,
        $test_username,
        $test_password,
        $login_url
    );
    
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: SCN <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
    ];
    
    // Try wp_mail
    $result = wp_mail($test_email, $subject, $message, $headers);
    
    if ($result) {
        echo "<div class='success'>✅ Email sent successfully to {$test_email}</div>";
        echo "<div class='info'>📧 Check your email or WordPress debug logs for the message</div>";
    } else {
        echo "<div class='error'>❌ Failed to send email to {$test_email}</div>";
        echo "<div class='info'>🔍 Check WordPress mail configuration and debug logs</div>";
    }
    
    // Clean up test user
    wp_delete_user($user_id);
    echo "<div class='info'>🧹 Test user cleaned up</div>";
    
    return $result;
}

// Test email configuration
echo "<h2>Email Configuration Test</h2>";

// Check if wp_mail is available
if (function_exists('wp_mail')) {
    echo "<div class='success'>✅ wp_mail function is available</div>";
} else {
    echo "<div class='error'>❌ wp_mail function is not available</div>";
}

// Check mail configuration
$mail_config = [
    'SMTP' => defined('SMTP_HOST') ? SMTP_HOST : 'Not configured',
    'From Email' => get_option('admin_email'),
    'From Name' => get_option('blogname'),
];

echo "<h3>Current Mail Configuration:</h3>";
echo "<ul>";
foreach ($mail_config as $key => $value) {
    echo "<li><strong>{$key}:</strong> {$value}</li>";
}
echo "</ul>";

// Check if we're in debug mode
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo "<div class='info'>🔧 WordPress is in debug mode - emails will be logged instead of sent</div>";
}

echo "<h2>Running Email Test</h2>";

// Run the test
$test_result = testPasswordEmail();

echo "<h2>Test Results</h2>";
if ($test_result) {
    echo "<div class='success'>✅ Password email functionality is working correctly!</div>";
} else {
    echo "<div class='error'>❌ Password email functionality needs attention</div>";
    echo "<div class='info'>💡 Check your WordPress mail configuration and server settings</div>";
}

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li>Create a new member in the admin to test the full flow</li>";
echo "<li>Check WordPress debug logs for email delivery status</li>";
echo "<li>Verify email configuration if tests fail</li>";
echo "</ul>";

// Add some basic styling
echo "<style>
.success { color: green; background: #f0f8f0; padding: 10px; border: 1px solid green; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border: 1px solid red; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border: 1px solid blue; margin: 10px 0; }
</style>";
?>
