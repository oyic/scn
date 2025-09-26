<?php
/**
 * Create Pages with Shortcodes
 * This creates actual WordPress pages for member-login and member-dashboard with shortcodes
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>📄 Creating Pages with Shortcodes</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Function to create or update page
function create_page_with_shortcode($slug, $title, $shortcode, $content = '') {
    // Check if page exists
    $page = get_page_by_path($slug);
    
    if ($page) {
        // Update existing page
        $page_id = wp_update_post([
            'ID' => $page->ID,
            'post_title' => $title,
            'post_content' => $content . "\n\n" . $shortcode,
            'post_status' => 'publish'
        ]);
        echo "<div class='success'>";
        echo "<p>✅ Updated page: $title (ID: $page_id)</p>";
        echo "</div>";
    } else {
        // Create new page
        $page_id = wp_insert_post([
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $content . "\n\n" . $shortcode,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1
        ]);
        echo "<div class='success'>";
        echo "<p>✅ Created page: $title (ID: $page_id)</p>";
        echo "</div>";
    }
    
    return $page_id;
}

// Create member login page
echo "<div class='info'>";
echo "<h2>🔐 Creating Member Login Page</h2>";
echo "</div>";

$login_content = "Use the form below to log in to your member account.";
$login_id = create_page_with_shortcode(
    'member-login', 
    'Member Login', 
    '[scn_member_login]',
    $login_content
);

// Create member dashboard page
echo "<div class='info'>";
echo "<h2>📊 Creating Member Dashboard Page</h2>";
echo "</div>";

$dashboard_content = "Welcome to your member dashboard. Here you can manage your profile and access member features.";
$dashboard_id = create_page_with_shortcode(
    'member-dashboard', 
    'Member Dashboard', 
    '[scn_member_dashboard]',
    $dashboard_content
);

// Create member register page
echo "<div class='info'>";
echo "<h2>📝 Creating Member Registration Page</h2>";
echo "</div>";

$register_content = "Create a new member account to access our member features.";
$register_id = create_page_with_shortcode(
    'member-register', 
    'Member Registration', 
    '[scn_member_register]',
    $register_content
);

// Create test auth page
echo "<div class='info'>";
echo "<h2>🧪 Creating Test Auth Page</h2>";
echo "</div>";

$test_content = "This is a test page for debugging authentication issues.";
$test_id = create_page_with_shortcode(
    'test-auth', 
    'Test Authentication', 
    '[scn_test_auth]',
    $test_content
);

echo "<div class='success'>";
echo "<h2>🎉 Pages Created Successfully!</h2>";
echo "<p>All pages have been created with their respective shortcodes.</p>";
echo "</div>";

// Show the URLs
echo "<div class='info'>";
echo "<h2>🌐 Page URLs</h2>";
echo "<p>You can now access:</p>";
echo "<ul>";
echo "<li><a href='" . get_permalink($login_id) . "' target='_blank'>" . get_permalink($login_id) . "</a> - Member Login</li>";
echo "<li><a href='" . get_permalink($dashboard_id) . "' target='_blank'>" . get_permalink($dashboard_id) . "</a> - Member Dashboard</li>";
echo "<li><a href='" . get_permalink($register_id) . "' target='_blank'>" . get_permalink($register_id) . "</a> - Member Registration</li>";
echo "<li><a href='" . get_permalink($test_id) . "' target='_blank'>" . get_permalink($test_id) . "</a> - Test Auth</li>";
echo "</ul>";
echo "</div>";

// Update the login template to redirect to the page instead of rewrite rule
echo "<div class='info'>";
echo "<h2>🔄 Updating Login Template Redirect</h2>";
echo "</div>";

$login_template_file = SCN_MEMBERSHIP_PATH . 'templates/auth/profile-login.php';
if (file_exists($login_template_file)) {
    $login_template_content = file_get_contents($login_template_file);
    
    // Update redirect URL to use page URL instead of rewrite rule
    $login_template_content = str_replace(
        "wp_redirect(home_url(\"/member-dashboard/\"));",
        "wp_redirect(get_permalink($dashboard_id));",
        $login_template_content
    );
    
    file_put_contents($login_template_file, $login_template_content);
    
    echo "<div class='success'>";
    echo "<p>✅ Updated login template to redirect to page URL</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ Login template file not found</p>";
    echo "</div>";
}

// Test the pages
echo "<div class='info'>";
echo "<h2>🧪 Test the Pages</h2>";
echo "<p>Try these URLs to test the new pages:</p>";
echo "<ul>";
echo "<li><a href='" . get_permalink($login_id) . "' target='_blank'>Login Page</a></li>";
echo "<li><a href='" . get_permalink($dashboard_id) . "' target='_blank'>Dashboard Page</a></li>";
echo "</ul>";
echo "<p><strong>Test Credentials:</strong></p>";
echo "<ul>";
echo "<li>ezekiel / Ezekiel123!</li>";
echo "<li>dd / Profile12!</li>";
echo "<li>testmemberprofile / Profile79!</li>";
echo "<li>testprofile / Profile18!</li>";
echo "</ul>";
echo "</div>";

echo "<div class='success'>";
echo "<h2>✅ Setup Complete!</h2>";
echo "<p>The member authentication system now uses actual WordPress pages with shortcodes instead of rewrite rules.</p>";
echo "<p>This approach is more reliable and easier to manage.</p>";
echo "</div>";

echo "<hr>";
echo "<p><em>Pages creation completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
