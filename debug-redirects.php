<?php
/**
 * Debug Redirect Issues
 * This will help us understand what's happening with the login redirects
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 Login Redirect Debug</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.debug-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
</style>";

// Check current user status
echo "<div class='debug-section'>";
echo "<h2>Current User Status</h2>";
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "<p class='success'>✓ User is logged in</p>";
    echo "<p>User: " . $current_user->user_login . " (ID: " . $current_user->ID . ")</p>";
    
    // Check if user has profile
    $profile_posts = get_posts([
        'post_type' => 'profile',
        'meta_query' => [
            [
                'key' => 'scn_user_id',
                'value' => $current_user->ID,
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ]);
    
    if (!empty($profile_posts)) {
        echo "<p class='success'>✓ User has profile (ID: " . $profile_posts[0]->ID . ")</p>";
        echo "<p>Profile URL: <a href='" . get_permalink($profile_posts[0]->ID) . "' target='_blank'>" . get_permalink($profile_posts[0]->ID) . "</a></p>";
    } else {
        echo "<p class='warning'>⚠ User does not have a profile</p>";
    }
} else {
    echo "<p class='info'>ℹ No user is currently logged in</p>";
}
echo "</div>";

// Test URL accessibility
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
    
    // Test what template would be used
    $query_vars = [];
    if ($url === '/member-login/') {
        $query_vars['member_login'] = '1';
    } elseif ($url === '/member-register/') {
        $query_vars['member_register'] = '1';
    } elseif ($url === '/member-dashboard/') {
        $query_vars['member_dashboard'] = '1';
    } elseif ($url === '/test-auth/') {
        $query_vars['scn_test_auth'] = '1';
    }
    
    if (!empty($query_vars)) {
        echo "<p style='margin-left: 20px; font-size: 0.9em; color: #666;'>Query vars: " . implode(', ', array_keys($query_vars)) . "</p>";
    }
}
echo "</div>";

// Test template loading
echo "<div class='debug-section'>";
echo "<h2>Template Loading Test</h2>";

// Simulate the template loading logic
$templates = [
    'member_login' => 'templates/auth/no-js-login.php',
    'member_register' => 'templates/auth/member-register.php',
    'member_dashboard' => 'templates/profiles/dashboard.php',
    'scn_test_auth' => 'templates/auth/test-auth.php'
];

foreach ($templates as $query_var => $template_path) {
    $full_path = SCN_MEMBERSHIP_PATH . $template_path;
    if (file_exists($full_path)) {
        echo "<p class='success'>✓ $query_var → $template_path (exists)</p>";
    } else {
        echo "<p class='error'>✗ $query_var → $template_path (missing)</p>";
    }
}
echo "</div>";

// Test login simulation
echo "<div class='debug-section'>";
echo "<h2>Login Simulation Test</h2>";

if (isset($_POST['simulate_login'])) {
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    
    echo "<p><strong>Simulating login with:</strong></p>";
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
        
        // Check if user is logged in
        if (is_user_logged_in()) {
            echo "<p class='success'>✓ User is now logged in</p>";
            
            // Check profile
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
                echo "<p class='success'>✓ User has profile - should redirect to dashboard</p>";
                echo "<p>Dashboard URL: <a href='" . home_url('/member-dashboard/') . "' target='_blank'>" . home_url('/member-dashboard/') . "</a></p>";
            } else {
                echo "<p class='warning'>⚠ User has no profile - should redirect to profile creation</p>";
                echo "<p>Profile creation URL: <a href='" . admin_url('post-new.php?post_type=profile') . "' target='_blank'>" . admin_url('post-new.php?post_type=profile') . "</a></p>";
            }
        } else {
            echo "<p class='error'>✗ User is not logged in after wp_signon</p>";
        }
    }
} else {
    echo "<form method='post'>";
    echo "<p><strong>Simulate Login:</strong></p>";
    echo "<p>Username: <input type='text' name='username' value='testmember' required></p>";
    echo "<p>Password: <input type='password' name='password' value='TestMember123!' required></p>";
    echo "<p><input type='submit' name='simulate_login' value='Simulate Login'></p>";
    echo "</form>";
}
echo "</div>";

// Check rewrite rules
echo "<div class='debug-section'>";
echo "<h2>Rewrite Rules Check</h2>";
$rules = get_option('rewrite_rules');
if (is_array($rules)) {
    echo "<p class='success'>✓ Rewrite rules exist (" . count($rules) . " rules)</p>";
    
    $auth_rules = [
        '^member-login/?$' => 'index.php?member_login=1',
        '^member-dashboard/?$' => 'index.php?member_dashboard=1'
    ];
    
    foreach ($auth_rules as $pattern => $replacement) {
        if (isset($rules[$pattern]) && $rules[$pattern] === $replacement) {
            echo "<p class='success'>✓ $pattern → $replacement</p>";
        } else {
            echo "<p class='error'>✗ Missing: $pattern → $replacement</p>";
            if (isset($rules[$pattern])) {
                echo "<p style='margin-left: 20px;'>Found: $pattern → " . $rules[$pattern] . "</p>";
            }
        }
    }
} else {
    echo "<p class='error'>✗ No rewrite rules found</p>";
}
echo "</div>";

// Current page info
echo "<div class='debug-section'>";
echo "<h2>Current Page Info</h2>";
echo "<p><strong>Current URL:</strong> " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'Unknown') . "</p>";
echo "<p><strong>HTTP Host:</strong> " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'Unknown') . "</p>";
echo "<p><strong>Script Name:</strong> " . (isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'Unknown') . "</p>";
echo "<p><strong>Query String:</strong> " . (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : 'None') . "</p>";
echo "</div>";

echo "<hr>";
echo "<p><em>Debug completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
