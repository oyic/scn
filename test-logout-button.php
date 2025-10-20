<?php
/**
 * Test Logout Button Visibility
 * This will check if the logout button is properly displayed
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

// Check which dashboard template is being used
echo "<div class='info'>";
echo "<h2>📄 Dashboard Template Status</h2>";
echo "</div>";

$dashboard_simple = SCN_MEMBERSHIP_PATH . 'templates/profiles/dashboard-simple.php';
$dashboard_fixed = SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard-fixed.php';

if (file_exists($dashboard_simple)) {
    echo "<div class='success'>";
    echo "<p>✅ dashboard-simple.php exists</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ dashboard-simple.php missing</p>";
    echo "</div>";
}

if (file_exists($dashboard_fixed)) {
    echo "<div class='success'>";
    echo "<p>✅ profile-dashboard-fixed.php exists</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ profile-dashboard-fixed.php missing</p>";
    echo "</div>";
}

// Check shortcode configuration
echo "<div class='info'>";
echo "<h2>🔧 Shortcode Configuration</h2>";
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
} else {
    echo "<div class='error'>";
    echo "<p>❌ AuthShortcodes.php missing</p>";
    echo "</div>";
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
    
    $logout_url = get_permalink($login_page->ID) . '?action=logout';
    echo "<div class='info'>";
    echo "<p><strong>Logout URL:</strong> <a href='$logout_url' target='_blank'>$logout_url</a></p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ Missing authentication pages</p>";
    echo "</div>";
}

// Test shortcode rendering
echo "<div class='info'>";
echo "<h2>🧪 Test Dashboard Shortcode</h2>";
echo "</div>";

if ($dashboard_page) {
    echo "<div class='success'>";
    echo "<p>✅ Dashboard page exists</p>";
    echo "<p><strong>Page Content:</strong> [" . get_post_meta($dashboard_page->ID, '_content', true) . "]</p>";
    echo "</div>";
    
    // Check if shortcode is in page content
    $page_content = get_post_field('post_content', $dashboard_page->ID);
    if (strpos($page_content, '[member_dashboard]') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Page contains [member_dashboard] shortcode</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ Page might not contain the shortcode</p>";
        echo "</div>";
    }
}

// Instructions
echo "<div class='info'>";
echo "<h2>📝 How to See the Logout Button</h2>";
echo "</div>";

echo "<div class='success'>";
echo "<p>✅ <strong>Logout Button Should Be Visible!</strong> Here's how to see it:</p>";
echo "<ol>";
echo "<li><strong>Login:</strong> Go to <a href='" . get_permalink($login_page->ID) . "' target='_blank'>Login Page</a></li>";
echo "<li><strong>Use credentials:</strong> ezekiel / Ezekiel123! or dd / Profile12!</li>";
echo "<li><strong>Dashboard:</strong> Should redirect to dashboard</li>";
echo "<li><strong>Look for:</strong> Red logout button in top-right corner</li>";
echo "</ol>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🔧 If You Don't See the Button:</h3>";
echo "<ul>";
echo "<li>Check if you're logged in properly</li>";
echo "<li>Clear browser cache</li>";
echo "<li>Check browser console for errors</li>";
echo "<li>Try a different browser</li>";
echo "</ul>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Troubleshooting:</h3>";
echo "<p>If the logout button still doesn't appear:</p>";
echo "<ul>";
echo "<li>The dashboard might be using the simple template instead of the fixed one</li>";
echo "<li>There might be a CSS issue hiding the button</li>";
echo "<li>The shortcode might not be rendering properly</li>";
echo "</ul>";
echo "</div>";

// Show logout button HTML for reference
echo "<div class='info'>";
echo "<h3>🎨 Logout Button HTML Reference:</h3>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace;'>";
echo "&lt;a href='$logout_url' class='scn-logout-btn'&gt;<br>";
echo "&nbsp;&nbsp;&lt;span class='dashicons dashicons-exit'&gt;&lt;/span&gt;<br>";
echo "&nbsp;&nbsp;Logout<br>";
echo "&lt;/a&gt;";
echo "</div>";
echo "</div>";

echo "<hr>";
echo "<p><em>Logout button test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
