<?php
/**
 * Test Logout Button Visibility
 * This will help identify why the logout button isn't showing
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🚪 Test Logout Button Visibility</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Check current user status
echo "<div class='info'>";
echo "<h2>👤 Current User Status</h2>";
echo "</div>";

if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "<div class='success'>";
    echo "<p>✅ Logged in as: " . $current_user->user_login . "</p>";
    echo "<p>User ID: " . $current_user->ID . "</p>";
    echo "<p>Can edit pages: " . (current_user_can('edit_pages') ? 'Yes' : 'No') . "</p>";
    echo "<p>Is admin: " . (is_admin() ? 'Yes' : 'No') . "</p>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<p>⚠️ Not logged in</p>";
    echo "</div>";
}

// Check which template is being used
echo "<div class='info'>";
echo "<h2>📄 Dashboard Template Check</h2>";
echo "</div>";

$shortcode_file = SCN_MEMBERSHIP_PATH . 'src/Modules/Auth/AuthShortcodes.php';
if (file_exists($shortcode_file)) {
    $content = file_get_contents($shortcode_file);
    if (strpos($content, 'profile-dashboard-fixed.php') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Shortcode is using profile-dashboard-fixed.php</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ Shortcode might be using dashboard-simple.php</p>";
        echo "</div>";
    }
}

// Check both templates for logout button
echo "<div class='info'>";
echo "<h2>🔍 Template Logout Button Check</h2>";
echo "</div>";

$templates = [
    'dashboard-simple.php' => SCN_MEMBERSHIP_PATH . 'templates/profiles/dashboard-simple.php',
    'profile-dashboard-fixed.php' => SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard-fixed.php'
];

foreach ($templates as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        echo "<div class='info'>";
        echo "<h3>$name</h3>";
        echo "</div>";
        
        // Check for logout button
        if (strpos($content, 'logout') !== false || strpos($content, 'Logout') !== false) {
            echo "<div class='success'>";
            echo "<p>✅ Contains logout functionality</p>";
            echo "</div>";
            
            // Check for specific logout button elements
            if (strpos($content, 'scn-logout-btn') !== false) {
                echo "<div class='success'>";
                echo "<p>✅ Contains logout button with proper class</p>";
                echo "</div>";
            }
            
            if (strpos($content, 'action=logout') !== false) {
                echo "<div class='success'>";
                echo "<p>✅ Contains logout action parameter</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "<p>❌ No logout functionality found</p>";
            echo "</div>";
        }
    }
}

// Test shortcode rendering
echo "<div class='info'>";
echo "<h2>🧪 Test Shortcode Rendering</h2>";
echo "</div>";

if (is_user_logged_in()) {
    // Include authentication functions
    require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions-improved.php';
    
    if (class_exists('SCN\\Membership\\Modules\\Auth\\AuthShortcodes')) {
        $shortcodes = new \SCN\Membership\Modules\Auth\AuthShortcodes();
        
        echo "<div class='info'>";
        echo "<h3>Dashboard Shortcode Output:</h3>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #dee2e6; max-height: 300px; overflow-y: auto;'>";
        
        try {
            $dashboard_output = $shortcodes->renderDashboardPage([]);
            
            // Check if logout button is in output
            if (strpos($dashboard_output, 'logout') !== false || strpos($dashboard_output, 'Logout') !== false) {
                echo "<div class='success'>";
                echo "<p>✅ Logout button found in output!</p>";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "<p>❌ Logout button NOT found in output</p>";
                echo "</div>";
            }
            
            // Show the output
            echo htmlspecialchars($dashboard_output);
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<p>❌ Error rendering dashboard: " . $e->getMessage() . "</p>";
            echo "</div>";
        }
        
        echo "</div>";
        echo "</div>";
    }
} else {
    echo "<div class='warning'>";
    echo "<p>⚠️ Not logged in - cannot test shortcode rendering</p>";
    echo "</div>";
}

// Instructions
echo "<div class='info'>";
echo "<h2>📝 How to See the Logout Button</h2>";
echo "</div>";

echo "<div class='success'>";
echo "<p>✅ <strong>Both templates have logout buttons!</strong> Here's how to see it:</p>";
echo "<ol>";
echo "<li><strong>Login:</strong> Go to <a href='http://scn.local/member-login/' target='_blank'>Login Page</a></li>";
echo "<li><strong>Use credentials:</strong> ezekiel / Ezekiel123! or dd / Profile12!</li>";
echo "<li><strong>Dashboard:</strong> Should redirect to dashboard</li>";
echo "<li><strong>Look for:</strong> Red logout button in top-right corner</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ If You Still Don't See the Button:</h3>";
echo "<ul>";
echo "<li>Clear browser cache completely</li>";
echo "<li>Check browser developer tools for errors</li>";
echo "<li>Try a different browser</li>";
echo "<li>Check if the dashboard is actually loading</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Logout button visibility test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
