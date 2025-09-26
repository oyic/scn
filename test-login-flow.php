<?php
/**
 * Test Login Flow
 * This will test the complete login to dashboard flow
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🧪 Login Flow Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
.test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

// Test 1: Check if URLs are accessible
echo "<div class='test-section'>";
echo "<h2>1. URL Accessibility Test</h2>";

$test_urls = [
    '/member-login/' => 'Login Page',
    '/member-dashboard/' => 'Dashboard Page',
    '/test-auth/' => 'Test Auth Page'
];

foreach ($test_urls as $url => $name) {
    $full_url = home_url($url);
    echo "<p><strong>$name:</strong> <a href='$full_url' target='_blank'>$full_url</a></p>";
}
echo "</div>";

// Test 2: Check rewrite rules
echo "<div class='test-section'>";
echo "<h2>2. Rewrite Rules Test</h2>";

$rules = get_option('rewrite_rules');
if (is_array($rules)) {
    echo "<p class='success'>✓ Rewrite rules exist (" . count($rules) . " rules)</p>";
    
    $auth_rules = [
        '^member-login/?$' => 'index.php?scn_member_login=1',
        '^member-dashboard/?$' => 'index.php?scn_member_dashboard=1'
    ];
    
    foreach ($auth_rules as $pattern => $replacement) {
        if (isset($rules[$pattern]) && $rules[$pattern] === $replacement) {
            echo "<p class='success'>✓ $pattern → $replacement</p>";
        } else {
            echo "<p class='error'>✗ Missing: $pattern → $replacement</p>";
        }
    }
} else {
    echo "<p class='error'>✗ No rewrite rules found</p>";
}
echo "</div>";

// Test 3: Check template files
echo "<div class='test-section'>";
echo "<h2>3. Template Files Test</h2>";

$templates = [
    'Login' => 'templates/auth/no-js-login.php',
    'Dashboard' => 'templates/profiles/dashboard-simple.php',
    'Test Auth' => 'templates/auth/test-auth.php'
];

foreach ($templates as $name => $template_path) {
    $full_path = SCN_MEMBERSHIP_PATH . $template_path;
    if (file_exists($full_path)) {
        echo "<p class='success'>✓ $name template exists: $template_path</p>";
    } else {
        echo "<p class='error'>✗ $name template missing: $template_path</p>";
    }
}
echo "</div>";

// Test 4: Login simulation
echo "<div class='test-section'>";
echo "<h2>4. Login Simulation Test</h2>";

if (isset($_POST['test_login'])) {
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    
    echo "<p><strong>Testing login with:</strong> $username</p>";
    
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
        
        if (is_user_logged_in()) {
            echo "<p class='success'>✓ User is now logged in</p>";
            
            // Check profile
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
                echo "<p class='success'>✓ User has profile - should redirect to dashboard</p>";
                echo "<p><a href='" . home_url('/member-dashboard/') . "' target='_blank'>Go to Dashboard</a></p>";
            } else {
                echo "<p class='warning'>⚠ User has no profile - should redirect to profile creation</p>";
                echo "<p><a href='" . admin_url('post-new.php?post_type=scn_profile') . "' target='_blank'>Create Profile</a></p>";
            }
        } else {
            echo "<p class='error'>✗ User is not logged in after wp_signon</p>";
        }
    }
} else {
    echo "<form method='post'>";
    echo "<p><strong>Test Login:</strong></p>";
    echo "<p>Username: <input type='text' name='username' value='testmember' required></p>";
    echo "<p>Password: <input type='password' name='password' value='TestMember123!' required></p>";
    echo "<p><input type='submit' name='test_login' value='Test Login'></p>";
    echo "</form>";
}
echo "</div>";

// Test 5: Expected flow
echo "<div class='test-section'>";
echo "<h2>5. Expected Login Flow</h2>";
echo "<ol>";
echo "<li>User visits <code>/member-login/</code></li>";
echo "<li>Login form loads (no-js-login.php template)</li>";
echo "<li>User enters credentials and submits</li>";
echo "<li>PHP processes login with <code>wp_signon()</code></li>";
echo "<li>If successful, check if user has profile</li>";
echo "<li>If profile exists: redirect to <code>/member-dashboard/</code></li>";
echo "<li>If no profile: redirect to profile creation page</li>";
echo "<li>Dashboard loads (dashboard-simple.php template)</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
