<?php
/**
 * Debug Dashboard Template
 * This will help identify which template is being used and why logout button might not show
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 Debug Dashboard Template</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Test shortcode rendering directly
echo "<div class='info'>";
echo "<h2>🧪 Test Shortcode Rendering</h2>";
echo "</div>";

// Include authentication functions
require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions-improved.php';

// Check if we can render the shortcode
if (class_exists('SCN\\Membership\\Modules\\Auth\\AuthShortcodes')) {
    $shortcodes = new \SCN\Membership\Modules\Auth\AuthShortcodes();
    
    echo "<div class='success'>";
    echo "<p>✅ AuthShortcodes class exists</p>";
    echo "</div>";
    
    // Test dashboard rendering
    echo "<div class='info'>";
    echo "<h3>Dashboard Shortcode Output:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #dee2e6;'>";
    
    // Capture output
    ob_start();
    try {
        $dashboard_output = $shortcodes->renderDashboardPage([]);
        echo $dashboard_output;
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<p>❌ Error rendering dashboard: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    $output = ob_get_clean();
    
    // Check if logout button is in output
    if (strpos($output, 'scn-logout-btn') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Logout button found in output!</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Logout button NOT found in output</p>";
        echo "</div>";
    }
    
    // Show first 500 characters of output
    echo "<div style='background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;'>";
    echo htmlspecialchars(substr($output, 0, 500)) . "...";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ AuthShortcodes class not found</p>";
    echo "</div>";
}

// Check template files directly
echo "<div class='info'>";
echo "<h2>📄 Template File Analysis</h2>";
echo "</div>";

$template_files = [
    'dashboard-simple.php' => SCN_MEMBERSHIP_PATH . 'templates/profiles/dashboard-simple.php',
    'profile-dashboard-fixed.php' => SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard-fixed.php'
];

foreach ($template_files as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        echo "<div class='success'>";
        echo "<h3>$name</h3>";
        echo "</div>";
        
        // Check for logout button
        if (strpos($content, 'logout') !== false || strpos($content, 'Logout') !== false) {
            echo "<div class='success'>";
            echo "<p>✅ Contains logout functionality</p>";
            echo "</div>";
        } else {
            echo "<div class='warning'>";
            echo "<p>⚠️ No logout functionality found</p>";
            echo "</div>";
        }
        
        // Check for logout button class
        if (strpos($content, 'scn-logout-btn') !== false) {
            echo "<div class='success'>";
            echo "<p>✅ Contains logout button with proper class</p>";
            echo "</div>";
        } else {
            echo "<div class='warning'>";
            echo "<p>⚠️ No logout button class found</p>";
            echo "</div>";
        }
        
        // Show relevant lines
        $lines = explode("\n", $content);
        $logout_lines = [];
        foreach ($lines as $i => $line) {
            if (strpos($line, 'logout') !== false || strpos($line, 'Logout') !== false) {
                $logout_lines[] = ($i + 1) . ": " . trim($line);
            }
        }
        
        if (!empty($logout_lines)) {
            echo "<div class='info'>";
            echo "<p><strong>Logout-related lines:</strong></p>";
            echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px;'>";
            foreach ($logout_lines as $line) {
                echo htmlspecialchars($line) . "<br>";
            }
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<p>❌ $name not found</p>";
        echo "</div>";
    }
}

// Instructions
echo "<div class='info'>";
echo "<h2>📝 Next Steps</h2>";
echo "</div>";

echo "<div class='success'>";
echo "<p>✅ <strong>Debugging Complete!</strong> Based on the results above:</p>";
echo "<ul>";
echo "<li>If logout button is found in output → Template is working correctly</li>";
echo "<li>If logout button is NOT found → There's a template or authentication issue</li>";
echo "<li>Check the template file analysis for any missing logout functionality</li>";
echo "</ul>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ If Logout Button Still Not Visible:</h3>";
echo "<ul>";
echo "<li>Clear browser cache completely</li>";
echo "<li>Check browser developer tools for JavaScript errors</li>";
echo "<li>Try logging in with different credentials</li>";
echo "<li>Check if you're actually logged in (look for admin bar)</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Dashboard template debug completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
