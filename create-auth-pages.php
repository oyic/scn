<?php
/**
 * Create Authentication Pages
 * This creates actual WordPress pages instead of relying on rewrite rules
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>📄 Creating Authentication Pages</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
</style>";

// Function to create or update page
function create_auth_page($slug, $title, $content) {
    // Check if page exists
    $page = get_page_by_path($slug);
    
    if ($page) {
        // Update existing page
        $page_id = wp_update_post([
            'ID' => $page->ID,
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish'
        ]);
        echo "<p class='success'>✓ Updated page: $title (ID: $page_id)</p>";
    } else {
        // Create new page
        $page_id = wp_insert_post([
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1
        ]);
        echo "<p class='success'>✓ Created page: $title (ID: $page_id)</p>";
    }
    
    return $page_id;
}

// Create login page
$login_content = '[scn_member_login]';
$login_id = create_auth_page('member-login', 'Member Login', $login_content);

// Create dashboard page
$dashboard_content = '[scn_member_dashboard]';
$dashboard_id = create_auth_page('member-dashboard', 'Member Dashboard', $dashboard_content);

// Create register page
$register_content = '[scn_member_register]';
$register_id = create_auth_page('member-register', 'Member Registration', $register_content);

// Create test page
$test_content = '[scn_test_auth]';
$test_id = create_auth_page('test-auth', 'Test Authentication', $test_content);

echo "<h2>✅ Pages Created!</h2>";
echo "<p>Now you can access:</p>";
echo "<ul>";
echo "<li><a href='" . get_permalink($login_id) . "' target='_blank'>" . get_permalink($login_id) . "</a></li>";
echo "<li><a href='" . get_permalink($dashboard_id) . "' target='_blank'>" . get_permalink($dashboard_id) . "</a></li>";
echo "<li><a href='" . get_permalink($register_id) . "' target='_blank'>" . get_permalink($register_id) . "</a></li>";
echo "<li><a href='" . get_permalink($test_id) . "' target='_blank'>" . get_permalink($test_id) . "</a></li>";
echo "</ul>";

echo "<h2>📝 Next Steps</h2>";
echo "<ol>";
echo "<li>Test the URLs above</li>";
echo "<li>If they work, we'll add shortcode handlers</li>";
echo "<li>If they don't work, we'll try a different approach</li>";
echo "</ol>";

echo "<hr>";
echo "<p><em>Pages created at " . date('Y-m-d H:i:s') . "</em></p>";
?>
