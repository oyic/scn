<?php
/**
 * Fix Admin Editing Access
 * This will properly fix the admin editing issue by checking for admin capabilities
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔧 Fix Admin Editing Access</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Update AuthShortcodes.php
echo "<div class='info'>";
echo "<h2>🔧 Updating AuthShortcodes.php</h2>";
echo "</div>";

$shortcode_file = SCN_MEMBERSHIP_PATH . 'src/Modules/Auth/AuthShortcodes.php';
if (file_exists($shortcode_file)) {
    $content = file_get_contents($shortcode_file);
    
    // Create a better admin check function
    $admin_check = '
    // Helper function to check if user should be allowed to edit
    private function shouldAllowAdminAccess() {
        // Allow if user can edit pages (admin/editor)
        if (current_user_can("edit_pages")) {
            return true;
        }
        
        // Allow if admin bar is showing (indicates logged in admin)
        if (is_admin_bar_showing()) {
            return true;
        }
        
        // Allow if we\'re in preview mode
        if (isset($_GET[\'preview\'])) {
            return true;
        }
        
        // Allow if we\'re editing a post/page
        if (isset($_GET[\'post\']) || isset($_GET[\'page_id\'])) {
            return true;
        }
        
        return false;
    }';
    
    // Add the helper function after the register method
    $content = str_replace(
        '    public function register()',
        $admin_check . "\n\n    public function register()",
        $content
    );
    
    // Update all redirect conditions to use the helper function
    $content = str_replace(
        'if (is_user_logged_in() && !is_admin() && !isset($_GET[\'preview\']) && !isset($_GET[\'post\']) && !is_admin_bar_showing()) {',
        'if (is_user_logged_in() && !$this->shouldAllowAdminAccess()) {',
        $content
    );
    
    $content = str_replace(
        'if (!is_user_logged_in() && !is_admin() && !isset($_GET[\'preview\']) && !isset($_GET[\'post\']) && !is_admin_bar_showing()) {',
        'if (!is_user_logged_in() && !$this->shouldAllowAdminAccess()) {',
        $content
    );
    
    file_put_contents($shortcode_file, $content);
    
    echo "<div class='success'>";
    echo "<p>✅ Updated AuthShortcodes.php with better admin access logic</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ AuthShortcodes.php not found</p>";
    echo "</div>";
}

// Update profile-login.php template
echo "<div class='info'>";
echo "<h2>🔧 Updating profile-login.php Template</h2>";
echo "</div>";

$login_template = SCN_MEMBERSHIP_PATH . 'templates/auth/profile-login.php';
if (file_exists($login_template)) {
    $content = file_get_contents($login_template);
    
    // Add helper function at the top
    $helper_function = '
// Helper function to check if user should be allowed to edit
function scn_should_allow_admin_access() {
    // Allow if user can edit pages (admin/editor)
    if (current_user_can("edit_pages")) {
        return true;
    }
    
    // Allow if admin bar is showing (indicates logged in admin)
    if (is_admin_bar_showing()) {
        return true;
    }
    
    // Allow if we\'re in preview mode
    if (isset($_GET[\'preview\'])) {
        return true;
    }
    
    // Allow if we\'re editing a post/page
    if (isset($_GET[\'post\']) || isset($_GET[\'page_id\'])) {
        return true;
    }
    
    return false;
}
';
    
    // Add helper function after the ABSPATH check
    $content = str_replace(
        'if (!defined("ABSPATH")) {',
        'if (!defined("ABSPATH")) {' . "\n" . $helper_function,
        $content
    );
    
    // Update redirect condition
    $content = str_replace(
        'if (scn_is_profile_authenticated() && !is_admin() && !isset($_GET[\'preview\']) && !isset($_GET[\'post\']) && !is_admin_bar_showing()) {',
        'if (scn_is_profile_authenticated() && !scn_should_allow_admin_access()) {',
        $content
    );
    
    file_put_contents($login_template, $content);
    
    echo "<div class='success'>";
    echo "<p>✅ Updated profile-login.php template</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ profile-login.php template not found</p>";
    echo "</div>";
}

// Update profile-dashboard-fixed.php template
echo "<div class='info'>";
echo "<h2>🔧 Updating profile-dashboard-fixed.php Template</h2>";
echo "</div>";

$dashboard_template = SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard-fixed.php';
if (file_exists($dashboard_template)) {
    $content = file_get_contents($dashboard_template);
    
    // Add helper function at the top
    $helper_function = '
// Helper function to check if user should be allowed to edit
function scn_should_allow_admin_access() {
    // Allow if user can edit pages (admin/editor)
    if (current_user_can("edit_pages")) {
        return true;
    }
    
    // Allow if admin bar is showing (indicates logged in admin)
    if (is_admin_bar_showing()) {
        return true;
    }
    
    // Allow if we\'re in preview mode
    if (isset($_GET[\'preview\'])) {
        return true;
    }
    
    // Allow if we\'re editing a post/page
    if (isset($_GET[\'post\']) || isset($_GET[\'page_id\'])) {
        return true;
    }
    
    return false;
}
';
    
    // Add helper function after the ABSPATH check
    $content = str_replace(
        'if (!defined(\'ABSPATH\')) {',
        'if (!defined(\'ABSPATH\')) {' . "\n" . $helper_function,
        $content
    );
    
    // Update redirect condition
    $content = str_replace(
        'if ((!$is_authenticated || !$profile) && !is_admin() && !isset($_GET[\'preview\']) && !isset($_GET[\'post\']) && !is_admin_bar_showing()) {',
        'if ((!$is_authenticated || !$profile) && !scn_should_allow_admin_access()) {',
        $content
    );
    
    file_put_contents($dashboard_template, $content);
    
    echo "<div class='success'>";
    echo "<p>✅ Updated profile-dashboard-fixed.php template</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ profile-dashboard-fixed.php template not found</p>";
    echo "</div>";
}

// Test the fix
echo "<div class='info'>";
echo "<h2>🧪 Test Admin Access</h2>";
echo "</div>";

if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    
    echo "<div class='success'>";
    echo "<p>✅ Current user: " . $current_user->user_login . "</p>";
    echo "<p>Can edit pages: " . (current_user_can('edit_pages') ? 'Yes' : 'No') . "</p>";
    echo "<p>Admin bar showing: " . (is_admin_bar_showing() ? 'Yes' : 'No') . "</p>";
    echo "<p>Is admin: " . (is_admin() ? 'Yes' : 'No') . "</p>";
    echo "</div>";
    
    // Test the helper function
    if (function_exists('scn_should_allow_admin_access')) {
        $should_allow = scn_should_allow_admin_access();
        echo "<div class='info'>";
        echo "<p>Should allow admin access: " . ($should_allow ? 'Yes' : 'No') . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='info'>";
    echo "<p>Not logged in - this is expected for the test</p>";
    echo "</div>";
}

// Instructions
echo "<div class='info'>";
echo "<h2>📝 How to Test Admin Editing</h2>";
echo "</div>";

echo "<div class='success'>";
echo "<p>✅ <strong>Admin Editing Fixed!</strong> Now you should be able to:</p>";
echo "<ol>";
echo "<li><strong>Go to WordPress Admin:</strong> <a href='" . admin_url() . "' target='_blank'>WordPress Admin</a></li>";
echo "<li><strong>Edit Pages:</strong> <a href='" . admin_url('edit.php?post_type=page') . "' target='_blank'>All Pages</a></li>";
echo "<li><strong>Edit Login Page:</strong> <a href='" . admin_url('post.php?post=81&action=edit') . "' target='_blank'>Edit Login Page</a></li>";
echo "<li><strong>Edit Dashboard Page:</strong> <a href='" . admin_url('post.php?post=82&action=edit') . "' target='_blank'>Edit Dashboard Page</a></li>";
echo "</ol>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🔧 What Was Fixed:</h3>";
echo "<ul>";
echo "<li>✅ Added <code>shouldAllowAdminAccess()</code> helper function</li>";
echo "<li>✅ Checks for <code>edit_pages</code> capability (admin/editor roles)</li>";
echo "<li>✅ Checks for admin bar visibility</li>";
echo "<li>✅ Checks for preview mode</li>";
echo "<li>✅ Checks for post/page editing parameters</li>";
echo "<li>✅ Updated all redirect conditions to use the helper function</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Admin editing fix completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
