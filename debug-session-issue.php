<?php
/**
 * Debug Session Issues
 * This will help us understand what's wrong with session handling
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 Debug Session Issues</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Include authentication functions
require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions.php';

echo "<div class='info'>";
echo "<h2>🔍 Current Session Status</h2>";
echo "</div>";

// Check session status
if (session_status() === PHP_SESSION_NONE) {
    echo "<div class='warning'>";
    echo "<p>⚠️ No session started</p>";
    echo "</div>";
} elseif (session_status() === PHP_SESSION_DISABLED) {
    echo "<div class='error'>";
    echo "<p>❌ Sessions are disabled</p>";
    echo "</div>";
} elseif (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='success'>";
    echo "<p>✅ Session is active</p>";
    echo "</div>";
}

// Check if headers are sent
if (headers_sent()) {
    echo "<div class='warning'>";
    echo "<p>⚠️ Headers already sent</p>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<p>✅ Headers not sent yet</p>";
    echo "</div>";
}

// Check current session data
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='info'>";
    echo "<h3>Current Session Data:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
    print_r($_SESSION);
    echo "</pre>";
    echo "</div>";
}

// Check cookies
echo "<div class='info'>";
echo "<h3>Current Cookies:</h3>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
print_r($_COOKIE);
echo "</pre>";
echo "</div>";

// Test authentication status
echo "<div class='info'>";
echo "<h2>🧪 Test Authentication Status</h2>";
echo "</div>";

$is_authenticated = false;
$current_profile = null;

try {
    $is_authenticated = scn_is_profile_authenticated();
    echo "<div class='info'>";
    echo "<p>Authentication Status: " . ($is_authenticated ? 'Authenticated' : 'Not Authenticated') . "</p>";
    echo "</div>";
    
    if ($is_authenticated) {
        $current_profile = scn_get_current_profile();
        if ($current_profile) {
            echo "<div class='success'>";
            echo "<p>✅ Current Profile: " . $current_profile->post_title . " (ID: " . $current_profile->ID . ")</p>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<p>❌ Authentication is true but no profile found</p>";
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<p>❌ Error checking authentication: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Test session setting
echo "<div class='info'>";
echo "<h2>🧪 Test Session Setting</h2>";
echo "</div>";

// Get a test profile
$test_profile = get_posts([
    'post_type' => 'profile',
    'meta_query' => [
        [
            'key' => 'scn_username',
            'value' => 'ezekiel',
            'compare' => '='
        ]
    ],
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (!empty($test_profile)) {
    echo "<div class='info'>";
    echo "<h3>Setting test session for profile: " . $test_profile[0]->post_title . " (ID: " . $test_profile[0]->ID . ")</h3>";
    echo "</div>";
    
    try {
        scn_set_profile_session($test_profile[0]->ID);
        echo "<div class='success'>";
        echo "<p>✅ Session set successfully</p>";
        echo "</div>";
        
        // Check if it worked
        $test_auth = scn_is_profile_authenticated();
        $test_profile_result = scn_get_current_profile();
        
        echo "<div class='info'>";
        echo "<p>After setting session:</p>";
        echo "<p>Authenticated: " . ($test_auth ? 'Yes' : 'No') . "</p>";
        echo "<p>Current Profile: " . ($test_profile_result ? $test_profile_result->post_title : 'None') . "</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<p>❌ Error setting session: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "<p>❌ No test profile found</p>";
    echo "</div>";
}

// Check session configuration
echo "<div class='info'>";
echo "<h2>⚙️ Session Configuration</h2>";
echo "</div>";

$session_config = [
    'session.save_path' => ini_get('session.save_path'),
    'session.use_cookies' => ini_get('session.use_cookies'),
    'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'session.cookie_path' => ini_get('session.cookie_path'),
    'session.cookie_domain' => ini_get('session.cookie_domain'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite'),
];

echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background-color: #f2f2f2;'><th style='border: 1px solid #ddd; padding: 8px;'>Setting</th><th style='border: 1px solid #ddd; padding: 8px;'>Value</th></tr>";

foreach ($session_config as $setting => $value) {
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$setting</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($value ?: '(empty)') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test login simulation
echo "<div class='info'>";
echo "<h2>🧪 Test Login Simulation</h2>";
echo "</div>";

if (isset($_POST['test_login'])) {
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    
    echo "<div class='info'>";
    echo "<h3>Testing login with: $username</h3>";
    echo "</div>";
    
    $profile = scn_authenticate_profile($username, $password);
    
    if (is_wp_error($profile)) {
        echo "<div class='error'>";
        echo "<p>❌ Login failed: " . $profile->get_error_message() . "</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<p>✅ Login successful! Profile: " . $profile->post_title . " (ID: " . $profile->ID . ")</p>";
        echo "</div>";
        
        // Set session
        scn_set_profile_session($profile->ID);
        
        // Check session
        $session_check = scn_is_profile_authenticated();
        $session_profile = scn_get_current_profile();
        
        echo "<div class='info'>";
        echo "<p>Session after login:</p>";
        echo "<p>Authenticated: " . ($session_check ? 'Yes' : 'No') . "</p>";
        echo "<p>Profile: " . ($session_profile ? $session_profile->post_title : 'None') . "</p>";
        echo "</div>";
        
        if ($session_check && $session_profile) {
            echo "<div class='success'>";
            echo "<p>🎉 Session working correctly!</p>";
            echo "<p><a href='" . home_url('/member-dashboard/') . "' target='_blank'>Go to Dashboard</a></p>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<p>❌ Session not working after login</p>";
            echo "</div>";
        }
    }
} else {
    echo "<form method='post'>";
    echo "<p><strong>Test Login:</strong></p>";
    echo "<p>Username: <input type='text' name='username' value='ezekiel' required></p>";
    echo "<p>Password: <input type='password' name='password' value='Ezekiel123!' required></p>";
    echo "<p><input type='submit' name='test_login' value='Test Login'></p>";
    echo "</form>";
}

echo "<hr>";
echo "<p><em>Session debug completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
