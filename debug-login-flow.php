<?php
/**
 * Debug Login Flow
 * This will help us understand what's happening with the login process
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 Debug Login Flow</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Check what's happening with the login process
echo "<div class='info'>";
echo "<h2>🔍 Current Login System Status</h2>";
echo "</div>";

// Check if profile authentication functions exist
if (file_exists(SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions.php')) {
    echo "<div class='success'>";
    echo "<p>✅ Profile authentication functions exist</p>";
    echo "</div>";
    
    // Include the functions
    require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions.php';
} else {
    echo "<div class='error'>";
    echo "<p>❌ Profile authentication functions missing</p>";
    echo "</div>";
}

// Check current template being used
echo "<div class='info'>";
echo "<h2>📄 Current Templates</h2>";
echo "</div>";

$auth_service_file = SCN_MEMBERSHIP_PATH . 'src/Modules/Auth/AuthService.php';
if (file_exists($auth_service_file)) {
    $content = file_get_contents($auth_service_file);
    
    if (strpos($content, 'profile-login.php') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ AuthService is using profile-login.php template</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ AuthService is NOT using profile-login.php template</p>";
        echo "</div>";
    }
    
    if (strpos($content, 'profile-dashboard.php') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ AuthService is using profile-dashboard.php template</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ AuthService is NOT using profile-dashboard.php template</p>";
        echo "</div>";
    }
}

// Test profile authentication
echo "<div class='info'>";
echo "<h2>🧪 Test Profile Authentication</h2>";
echo "</div>";

if (function_exists('scn_authenticate_profile')) {
    // Test with existing profile credentials
    $test_credentials = [
        ['testmemberprofile', 'Profile79!'],
        ['testprofile', 'Profile18!'],
        ['dd', 'Profile12!']
    ];
    
    foreach ($test_credentials as $cred) {
        $username = $cred[0];
        $password = $cred[1];
        
        echo "<div class='info'>";
        echo "<h3>Testing: $username</h3>";
        echo "</div>";
        
        $result = scn_authenticate_profile($username, $password);
        
        if (is_wp_error($result)) {
            echo "<div class='error'>";
            echo "<p>❌ Authentication failed: " . $result->get_error_message() . "</p>";
            echo "</div>";
        } else {
            echo "<div class='success'>";
            echo "<p>✅ Authentication successful!</p>";
            echo "<p>Profile ID: " . $result->ID . "</p>";
            echo "<p>Profile Title: " . $result->post_title . "</p>";
            echo "</div>";
        }
    }
} else {
    echo "<div class='error'>";
    echo "<p>❌ scn_authenticate_profile function not available</p>";
    echo "</div>";
}

// Test WordPress user authentication
echo "<div class='info'>";
echo "<h2>👥 Test WordPress User Authentication</h2>";
echo "</div>";

// Test with ezekiel
$user = get_user_by('login', 'ezekiel');
if ($user) {
    echo "<div class='success'>";
    echo "<p>✅ WordPress user 'ezekiel' exists (ID: " . $user->ID . ")</p>";
    echo "</div>";
    
    // Check if ezekiel has a profile
    $profile = get_posts([
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
    
    if (!empty($profile)) {
        echo "<div class='success'>";
        echo "<p>✅ User 'ezekiel' has a profile (ID: " . $profile[0]->ID . ")</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ User 'ezekiel' does NOT have a profile</p>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "<p>❌ WordPress user 'ezekiel' not found</p>";
    echo "</div>";
}

// Check what happens when we visit the URLs
echo "<div class='info'>";
echo "<h2>🌐 URL Testing</h2>";
echo "</div>";

$test_urls = [
    '/member-login/' => 'Login Page',
    '/member-dashboard/' => 'Dashboard Page'
];

foreach ($test_urls as $url => $name) {
    $full_url = home_url($url);
    echo "<div class='info'>";
    echo "<h3>$name: <a href='$full_url' target='_blank'>$full_url</a></h3>";
    echo "</div>";
}

// Check if we need to create a profile for ezekiel
echo "<div class='info'>";
echo "<h2>🔧 Quick Fix Options</h2>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>Option 1: Create Profile for ezekiel</h3>";
echo "<p>Create a profile with credentials for the ezekiel user.</p>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>Option 2: Use Existing Profile Credentials</h3>";
echo "<p>Use one of the existing profile credentials instead:</p>";
echo "<ul>";
echo "<li><strong>testmemberprofile</strong> / Profile79!</li>";
echo "<li><strong>testprofile</strong> / Profile18!</li>";
echo "<li><strong>dd</strong> / Profile12!</li>";
echo "</ul>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>Option 3: Create Profile Credentials for ezekiel</h3>";
echo "<p>Add profile credentials to the ezekiel user's profile (if they have one).</p>";
echo "</div>";

echo "<hr>";
echo "<p><em>Login flow debug completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
