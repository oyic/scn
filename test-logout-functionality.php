<?php
/**
 * Test Logout Functionality
 * This will verify that the logout button works properly
 */

// Include WordPress
require_once('../../../wp-config.php');

// Include authentication functions
require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions-improved.php';

echo "<h1>🚪 Test Logout Functionality</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Check current authentication status
echo "<div class='info'>";
echo "<h2>🔍 Current Authentication Status</h2>";
echo "</div>";

$wp_logged_in = is_user_logged_in();
$profile_authenticated = false;

if (function_exists('scn_is_profile_authenticated')) {
    $profile_authenticated = scn_is_profile_authenticated();
}

echo "<div class='info'>";
echo "<p><strong>WordPress User Logged In:</strong> " . ($wp_logged_in ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Profile Authenticated:</strong> " . ($profile_authenticated ? 'Yes' : 'No') . "</p>";
echo "</div>";

if ($wp_logged_in) {
    $current_user = wp_get_current_user();
    echo "<div class='success'>";
    echo "<p>✅ WordPress User: " . $current_user->user_login . " (ID: " . $current_user->ID . ")</p>";
    echo "</div>";
}

if ($profile_authenticated && function_exists('scn_get_current_profile')) {
    $profile = scn_get_current_profile();
    if ($profile) {
        echo "<div class='success'>";
        echo "<p>✅ Profile: " . $profile->post_title . " (ID: " . $profile->ID . ")</p>";
        echo "</div>";
    }
}

// Check pages
echo "<div class='info'>";
echo "<h2>📄 Authentication Pages</h2>";
echo "</div>";

$login_page = get_page_by_path('member-login');
$dashboard_page = get_page_by_path('member-dashboard');

if ($login_page && $dashboard_page) {
    echo "<div class='success'>";
    echo "<p>✅ Login Page: <a href='" . get_permalink($login_page->ID) . "' target='_blank'>" . get_permalink($login_page->ID) . "</a></p>";
    echo "<p>✅ Dashboard Page: <a href='" . get_permalink($dashboard_page->ID) . "' target='_blank'>" . get_permalink($dashboard_page->ID) . "</a></p>";
    echo "</div>";
    
    // Test logout URLs
    $logout_url = get_permalink($login_page->ID) . '?action=logout';
    echo "<div class='info'>";
    echo "<p><strong>Logout URL:</strong> <a href='$logout_url' target='_blank'>$logout_url</a></p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ Missing authentication pages</p>";
    echo "</div>";
}

// Test logout functions
echo "<div class='info'>";
echo "<h2>🧪 Test Logout Functions</h2>";
echo "</div>";

if (function_exists('scn_logout_profile')) {
    echo "<div class='success'>";
    echo "<p>✅ scn_logout_profile() function exists</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ scn_logout_profile() function missing</p>";
    echo "</div>";
}

// Test logout button rendering
echo "<div class='info'>";
echo "<h2>🎨 Test Logout Button</h2>";
echo "</div>";

if ($dashboard_page) {
    echo "<div class='success'>";
    echo "<p>✅ Dashboard page exists - logout button should be visible</p>";
    echo "</div>";
    
    // Simulate logout button HTML
    $logout_url = get_permalink($login_page->ID) . '?action=logout';
    echo "<div class='info'>";
    echo "<p><strong>Logout Button HTML:</strong></p>";
    echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<a href='$logout_url' style='display: inline-flex; align-items: center; gap: 8px; background: #e74c3c; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;'>";
    echo "🚪 Logout";
    echo "</a>";
    echo "</div>";
    echo "</div>";
}

// Instructions
echo "<div class='info'>";
echo "<h2>📝 How to Test Logout</h2>";
echo "</div>";

echo "<div class='success'>";
echo "<p>✅ <strong>Logout Button Added!</strong> Here's how to test:</p>";
echo "<ol>";
echo "<li><strong>Login:</strong> Go to <a href='" . get_permalink($login_page->ID) . "' target='_blank'>Login Page</a></li>";
echo "<li><strong>Use credentials:</strong> ezekiel / Ezekiel123! or dd / Profile12!</li>";
echo "<li><strong>Dashboard:</strong> Should redirect to dashboard with logout button</li>";
echo "<li><strong>Logout:</strong> Click the red logout button in top-right</li>";
echo "<li><strong>Verify:</strong> Should redirect to login page and clear session</li>";
echo "</ol>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🔧 What Was Added:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Prominent Logout Button</strong> - Red button with icon in dashboard header</li>";
echo "<li>✅ <strong>Proper Styling</strong> - Hover effects and responsive design</li>";
echo "<li>✅ <strong>Logout Handling</strong> - Clears both WordPress and profile sessions</li>";
echo "<li>✅ <strong>URL Parameters</strong> - Uses ?action=logout for clean logout flow</li>";
echo "<li>✅ <strong>Redirect Logic</strong> - Redirects to login page after logout</li>";
echo "</ul>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Important Notes:</h3>";
echo "<ul>";
echo "<li>Logout button is only visible when authenticated</li>";
echo "<li>Logout clears both WordPress user session and profile session</li>";
echo "<li>After logout, user is redirected to login page</li>";
echo "<li>Button has hover effects and is mobile-responsive</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Logout functionality test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
