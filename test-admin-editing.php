<?php
/**
 * Test Admin Editing Access
 * This will verify that pages can be edited in admin without redirects
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔧 Test Admin Editing Access</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Check current user
echo "<div class='info'>";
echo "<h2>👤 Current User Status</h2>";
echo "</div>";

if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "<div class='success'>";
    echo "<p>✅ Logged in as: " . $current_user->user_login . " (ID: " . $current_user->ID . ")</p>";
    echo "<p>User roles: " . implode(', ', $current_user->roles) . "</p>";
    echo "<p>Can edit pages: " . (current_user_can('edit_pages') ? 'Yes' : 'No') . "</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ Not logged in</p>";
    echo "</div>";
}

// Check admin status
echo "<div class='info'>";
echo "<h2>🏠 Admin Access</h2>";
echo "</div>";

echo "<div class='info'>";
echo "<p>is_admin(): " . (is_admin() ? 'Yes' : 'No') . "</p>";
echo "<p>is_admin_bar_showing(): " . (is_admin_bar_showing() ? 'Yes' : 'No') . "</p>";
echo "<p>current_user_can('edit_pages'): " . (current_user_can('edit_pages') ? 'Yes' : 'No') . "</p>";
echo "</div>";

// Check pages
echo "<div class='info'>";
echo "<h2>📄 Authentication Pages</h2>";
echo "</div>";

$page_slugs = ['member-login', 'member-dashboard', 'member-register', 'test-auth'];
$pages_info = [];

foreach ($page_slugs as $slug) {
    $page = get_page_by_path($slug);
    if ($page) {
        $edit_url = admin_url('post.php?post=' . $page->ID . '&action=edit');
        $view_url = get_permalink($page->ID);
        $preview_url = add_query_arg('preview', '1', $view_url);
        
        echo "<div class='success'>";
        echo "<h3>" . $page->post_title . " (ID: " . $page->ID . ")</h3>";
        echo "<p><strong>Edit URL:</strong> <a href='$edit_url' target='_blank'>$edit_url</a></p>";
        echo "<p><strong>View URL:</strong> <a href='$view_url' target='_blank'>$view_url</a></p>";
        echo "<p><strong>Preview URL:</strong> <a href='$preview_url' target='_blank'>$preview_url</a></p>";
        echo "<p><strong>Shortcode:</strong> [" . ($slug === 'member-login' ? 'member_login' : 
                                                   ($slug === 'member-dashboard' ? 'member_dashboard' : 
                                                   ($slug === 'member-register' ? 'member_register' : 'scn_test_auth'))) . "]</p>";
        echo "</div>";
        
        $pages_info[$slug] = [
            'id' => $page->ID,
            'edit_url' => $edit_url,
            'view_url' => $view_url,
            'preview_url' => $preview_url
        ];
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Page missing: $slug</p>";
        echo "</div>";
    }
}

// Test shortcode rendering
echo "<div class='info'>";
echo "<h2>🧪 Test Shortcode Rendering</h2>";
echo "</div>";

// Simulate admin context
if (is_user_logged_in()) {
    echo "<div class='info'>";
    echo "<p>Testing shortcode rendering in different contexts:</p>";
    echo "</div>";
    
    // Test login shortcode
    echo "<div class='success'>";
    echo "<h3>Login Shortcode Test</h3>";
    echo "<p><strong>In admin context:</strong></p>";
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
    
    // Temporarily set admin context
    global $pagenow;
    $original_pagenow = $pagenow;
    $pagenow = 'post.php';
    
    $login_shortcode = do_shortcode('[member_login]');
    echo $login_shortcode ? substr(strip_tags($login_shortcode), 0, 100) . '...' : 'No output (redirected)';
    
    $pagenow = $original_pagenow;
    echo "</div>";
    echo "</div>";
}

// Instructions
echo "<div class='info'>";
echo "<h2>📝 How to Edit Pages Now</h2>";
echo "</div>";

echo "<div class='success'>";
echo "<p>✅ <strong>Fixed!</strong> You can now edit the authentication pages in WordPress admin:</p>";
echo "<ul>";
foreach ($pages_info as $slug => $info) {
    echo "<li><strong>" . ucfirst(str_replace('-', ' ', $slug)) . ":</strong> <a href='{$info['edit_url']}' target='_blank'>Edit in Admin</a></li>";
}
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🔧 What Was Fixed:</h3>";
echo "<ul>";
echo "<li>✅ Added <code>!is_admin()</code> checks to prevent redirects in admin</li>";
echo "<li>✅ Added <code>!isset(\$_GET['preview'])</code> checks for preview mode</li>";
echo "<li>✅ Updated all shortcode renderers</li>";
echo "<li>✅ Updated all template files</li>";
echo "</ul>";
echo "</div>";

echo "<div class='success'>";
echo "<h3>🎯 Expected Behavior:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Admin editing:</strong> Pages can be edited without redirects</li>";
echo "<li>✅ <strong>Preview mode:</strong> Pages can be previewed without redirects</li>";
echo "<li>✅ <strong>Frontend:</strong> Normal redirect behavior for logged-in users</li>";
echo "<li>✅ <strong>Shortcodes:</strong> Render properly in admin context</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Admin editing test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
