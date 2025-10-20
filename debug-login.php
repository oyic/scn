<?php
/**
 * Login Debug Script
 * This will help us identify what's happening with the login process
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 SCN Login Debug</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.debug-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
</style>";

// Check WordPress status
echo "<div class='debug-section'>";
echo "<h2>WordPress Status</h2>";
if (function_exists('get_bloginfo')) {
    echo "<p class='success'>✓ WordPress loaded successfully</p>";
    echo "<p>Version: " . get_bloginfo('version') . "</p>";
    echo "<p>Site URL: " . home_url() . "</p>";
} else {
    echo "<p class='error'>✗ WordPress not loaded</p>";
}
echo "</div>";

// Check plugin status
echo "<div class='debug-section'>";
echo "<h2>Plugin Status</h2>";
if (function_exists('is_plugin_active')) {
    $plugin_active = is_plugin_active('scn-membership/scn-membership.php');
    if ($plugin_active) {
        echo "<p class='success'>✓ SCN Membership plugin is active</p>";
    } else {
        echo "<p class='error'>✗ SCN Membership plugin is NOT active</p>";
    }
} else {
    echo "<p class='warning'>⚠ Cannot check plugin status</p>";
}
echo "</div>";

// Check if test user exists
echo "<div class='debug-section'>";
echo "<h2>Test User Status</h2>";
if (function_exists('username_exists')) {
    if (username_exists('testmember')) {
        echo "<p class='success'>✓ Test user 'testmember' exists</p>";
        
        $user = get_user_by('login', 'testmember');
        if ($user) {
            echo "<p>User ID: " . $user->ID . "</p>";
            echo "<p>Email: " . $user->user_email . "</p>";
            echo "<p>Status: " . ($user->user_status == 0 ? 'Active' : 'Inactive') . "</p>";
            
            // Test password
            if (wp_check_password('TestMember123!', $user->user_pass)) {
                echo "<p class='success'>✓ Password is correct</p>";
            } else {
                echo "<p class='error'>✗ Password is incorrect</p>";
            }
            
            // Check if user has profile
            $profile_posts = get_posts([
                'post_type' => 'profile',
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
                echo "<p class='success'>✓ User has profile (ID: " . $profile_posts[0]->ID . ")</p>";
            } else {
                echo "<p class='warning'>⚠ User does not have a profile</p>";
            }
        }
    } else {
        echo "<p class='error'>✗ Test user 'testmember' does not exist</p>";
        echo "<p><a href='create-test-user-simple.php'>Create Test User</a></p>";
    }
} else {
    echo "<p class='error'>✗ Cannot check user status</p>";
}
echo "</div>";

// Check rewrite rules
echo "<div class='debug-section'>";
echo "<h2>Rewrite Rules Status</h2>";
$rules = get_option('rewrite_rules');
if (is_array($rules)) {
    echo "<p class='success'>✓ Rewrite rules exist (" . count($rules) . " rules)</p>";
    
    $auth_rules = [
        '^member-login/?$' => 'index.php?member_login=1',
        '^member-register/?$' => 'index.php?member_register=1',
        '^member-dashboard/?$' => 'index.php?member_dashboard=1',
        '^test-auth/?$' => 'index.php?scn_test_auth=1'
    ];
    
    foreach ($auth_rules as $pattern => $replacement) {
        if (isset($rules[$pattern]) && $rules[$pattern] === $replacement) {
            echo "<p class='success'>✓ $pattern</p>";
        } else {
            echo "<p class='error'>✗ Missing: $pattern</p>";
        }
    }
} else {
    echo "<p class='error'>✗ No rewrite rules found</p>";
}
echo "</div>";

// Test login functionality
echo "<div class='debug-section'>";
echo "<h2>Login Test</h2>";
if (isset($_POST['test_login'])) {
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    
    echo "<p><strong>Testing login with:</strong></p>";
    echo "<p>Username: $username</p>";
    echo "<p>Password: " . (empty($password) ? '[EMPTY]' : '[SET]') . "</p>";
    
    $creds = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => false,
    ];
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        echo "<p class='error'>✗ Login failed: " . $user->get_error_message() . "</p>";
    } else {
        echo "<p class='success'>✓ Login successful!</p>";
        echo "<p>User ID: " . $user->ID . "</p>";
        echo "<p>Username: " . $user->user_login . "</p>";
        
        // Check if user is now logged in
        if (is_user_logged_in()) {
            echo "<p class='success'>✓ User is logged in</p>";
        } else {
            echo "<p class='error'>✗ User is not logged in after wp_signon</p>";
        }
    }
} else {
    echo "<form method='post'>";
    echo "<p><strong>Test Login Directly:</strong></p>";
    echo "<p>Username: <input type='text' name='username' value='testmember' required></p>";
    echo "<p>Password: <input type='password' name='password' value='TestMember123!' required></p>";
    echo "<p><input type='submit' name='test_login' value='Test Login'></p>";
    echo "</form>";
}
echo "</div>";

// Check current session
echo "<div class='debug-section'>";
echo "<h2>Current Session</h2>";
if (function_exists('is_user_logged_in') && is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "<p class='success'>✓ User is logged in</p>";
    echo "<p>Current User: " . $current_user->user_login . " (ID: " . $current_user->ID . ")</p>";
} else {
    echo "<p class='info'>ℹ No user is currently logged in</p>";
}

// Check cookies
echo "<p><strong>Cookies:</strong></p>";
$cookies = $_COOKIE;
if (empty($cookies)) {
    echo "<p class='warning'>⚠ No cookies found</p>";
} else {
    foreach ($cookies as $name => $value) {
        if (strpos($name, 'wordpress') !== false || strpos($name, 'wp') !== false) {
            echo "<p>$name: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "</p>";
        }
    }
}
echo "</div>";

// URL accessibility test
echo "<div class='debug-section'>";
echo "<h2>URL Accessibility Test</h2>";
$test_urls = [
    '/member-login/' => 'Member Login',
    '/member-register/' => 'Member Registration', 
    '/member-dashboard/' => 'Member Dashboard',
    '/test-auth/' => 'Test Authentication'
];

foreach ($test_urls as $url => $name) {
    $full_url = home_url($url);
    echo "<p><strong>$name:</strong> <a href='$full_url' target='_blank'>$full_url</a></p>";
}
echo "</div>";

// Quick actions
echo "<div class='debug-section'>";
echo "<h2>Quick Actions</h2>";
echo "<p><a href='create-test-user-simple.php'>Create/Check Test User</a></p>";
echo "<p><a href='fix-urls-wp.php'>Fix Rewrite Rules</a></p>";
echo "<p><a href='" . admin_url('options-permalink.php') . "' target='_blank'>WordPress Permalinks Settings</a></p>";
echo "<p><a href='" . admin_url('plugins.php') . "' target='_blank'>WordPress Plugins Page</a></p>";
echo "</div>";

echo "<hr>";
echo "<p><em>Debug completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
