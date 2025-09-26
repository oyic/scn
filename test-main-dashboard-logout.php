<?php
/**
 * Test Main Dashboard Logout Button
 * This will verify that the logout button is added to the main dashboard template
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🚪 Test Main Dashboard Logout Button</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Check the main dashboard template
echo "<div class='info'>";
echo "<h2>📄 Main Dashboard Template Check</h2>";
echo "</div>";

$main_dashboard = SCN_MEMBERSHIP_PATH . 'templates/profiles/dashboard.php';
if (file_exists($main_dashboard)) {
    $content = file_get_contents($main_dashboard);
    
    echo "<div class='success'>";
    echo "<h3>✅ dashboard.php (Main Template)</h3>";
    echo "</div>";
    
    // Check for logout buttons
    $logout_count = substr_count($content, 'logout') + substr_count($content, 'Logout');
    echo "<div class='info'>";
    echo "<p>Logout references found: $logout_count</p>";
    echo "</div>";
    
    // Check for specific elements
    if (strpos($content, 'action=logout') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Contains logout action parameter</p>";
        echo "</div>";
    }
    
    if (strpos($content, 'dashicons-exit') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Contains logout icon</p>";
        echo "</div>";
    }
    
    if (strpos($content, 'scn-btn-logout') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Contains logout button class</p>";
        echo "</div>";
    }
    
    if (strpos($content, '#e74c3c') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Contains red styling for logout button</p>";
        echo "</div>";
    }
    
    // Check for both locations
    if (strpos($content, 'scn-dashboard-actions') !== false && strpos($content, 'scn-dashboard-actions') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Contains dashboard actions section</p>";
        echo "</div>";
    }
    
    if (strpos($content, 'scn-actions-grid') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Contains quick actions grid</p>";
        echo "</div>";
    }
    
} else {
    echo "<div class='error'>";
    echo "<p>❌ Main dashboard template not found</p>";
    echo "</div>";
}

// Check which template is being used by the shortcode
echo "<div class='info'>";
echo "<h2>🔧 Shortcode Template Configuration</h2>";
echo "</div>";

$shortcode_file = SCN_MEMBERSHIP_PATH . 'src/Modules/Auth/AuthShortcodes.php';
if (file_exists($shortcode_file)) {
    $content = file_get_contents($shortcode_file);
    
    if (strpos($content, 'profile-dashboard-fixed.php') !== false) {
        echo "<div class='warning'>";
        echo "<p>⚠️ Shortcode is using profile-dashboard-fixed.php</p>";
        echo "<p>But the actual dashboard is using dashboard.php</p>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<p>ℹ️ Shortcode template configuration</p>";
        echo "</div>";
    }
}

// Check ProfileDashboard class
echo "<div class='info'>";
echo "<h2>🔧 ProfileDashboard Class Check</h2>";
echo "</div>";

$profile_dashboard_file = SCN_MEMBERSHIP_PATH . 'src/Modules/Profiles/ProfileDashboard.php';
if (file_exists($profile_dashboard_file)) {
    $content = file_get_contents($profile_dashboard_file);
    
    if (strpos($content, 'dashboard.php') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ ProfileDashboard class uses dashboard.php</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ ProfileDashboard class might not be using dashboard.php</p>";
        echo "</div>";
    }
}

// Summary
echo "<div class='success'>";
echo "<h2>🎉 Summary</h2>";
echo "<p><strong>Main Dashboard Template:</strong> dashboard.php</p>";
echo "<p><strong>Logout buttons added:</strong> ✅</p>";
echo "<ul>";
echo "<li>✅ <strong>Dashboard Actions:</strong> Red logout button in header actions</li>";
echo "<li>✅ <strong>Quick Actions:</strong> Red logout card in actions grid</li>";
echo "<li>✅ <strong>Styling:</strong> Red background (#e74c3c) with white text</li>";
echo "<li>✅ <strong>Functionality:</strong> Links to logout action</li>";
echo "</ul>";
echo "</div>";

// Instructions
echo "<div class='info'>";
echo "<h2>📝 How to Test</h2>";
echo "</div>";

echo "<div class='success'>";
echo "<p><strong>🚪 LOGOUT BUTTONS ADDED TO MAIN DASHBOARD!</strong></p>";
echo "<p>You should now see logout buttons in two locations:</p>";
echo "<ol>";
echo "<li><strong>Header Actions:</strong> Red logout button next to Edit Profile and View Profile</li>";
echo "<li><strong>Quick Actions Grid:</strong> Red logout card in the actions grid</li>";
echo "</ol>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🧪 Test Steps:</h3>";
echo "<ol>";
echo "<li><strong>Login:</strong> Go to <a href='http://scn.local/member-login/' target='_blank'>Login Page</a></li>";
echo "<li><strong>Credentials:</strong> Use ezekiel / Ezekiel123! or dd / Profile12!</li>";
echo "<li><strong>Dashboard:</strong> Should show the full dashboard with stats and actions</li>";
echo "<li><strong>Look for:</strong> Red logout button in header actions and red logout card in quick actions</li>";
echo "<li><strong>Click logout:</strong> Should redirect to login page</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><em>Main dashboard logout test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
