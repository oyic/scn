<?php
/**
 * Test script to verify classic editor is enabled for member post types
 */

// Load WordPress
$wp_load_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        require_once __DIR__ . '/' . $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Could not load WordPress. Make sure this file is in the plugin directory.');
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

echo "<h1>Classic Editor Test for Member Post Types</h1>";

// Test 1: Check post types and their editor settings
echo "<h2>1. Post Type Editor Settings</h2>";
$post_types = get_post_types([], 'objects');
$member_post_types = [];

foreach ($post_types as $post_type_name => $post_type_obj) {
    // Check if this is a member post type
    $is_member = false;
    $name_lower = strtolower($post_type_name);
    $label_lower = strtolower($post_type_obj->label);
    $singular_lower = strtolower($post_type_obj->labels->singular_name);
    
    if (strpos($name_lower, 'member') !== false || 
        strpos($label_lower, 'member') !== false || 
        strpos($singular_lower, 'member') !== false ||
        $post_type_name === 'profile') {
        $is_member = true;
        $member_post_types[] = $post_type_name;
    }
    
    if ($is_member) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 4px; margin: 10px 0;'>";
        echo "<h3>{$post_type_obj->label} ({$post_type_name})</h3>";
        
        // Check if block editor is disabled for this post type
        $use_block_editor = use_block_editor_for_post_type($post_type_name);
        echo "<p><strong>Block Editor Enabled:</strong> " . ($use_block_editor ? '❌ Yes (should be disabled)' : '✅ No (classic editor)') . "</p>";
        
        // Check REST API support
        echo "<p><strong>REST API Support:</strong> " . ($post_type_obj->show_in_rest ? '❌ Yes (should be disabled)' : '✅ No (classic editor)') . "</p>";
        
        // Check if post type supports editor
        $supports_editor = post_type_supports($post_type_name, 'editor');
        echo "<p><strong>Supports Editor:</strong> " . ($supports_editor ? '✅ Yes' : '❌ No') . "</p>";
        
        echo "<p><a href='" . admin_url('post-new.php?post_type=' . $post_type_name) . "' target='_blank' class='button'>Test Edit Screen</a></p>";
        echo "</div>";
    }
}

// Test 2: Check filter functions
echo "<h2>2. Filter Function Tests</h2>";

// Test the disableBlockEditorForMemberTypes filter
echo "<h3>Testing disableBlockEditorForMemberTypes filter:</h3>";
foreach ($member_post_types as $post_type) {
    $result = apply_filters('use_block_editor_for_post_type', true, $post_type);
    echo "<p><strong>{$post_type}:</strong> " . ($result ? '❌ Block editor enabled' : '✅ Classic editor enabled') . "</p>";
}

// Test 3: Check admin scripts
echo "<h2>3. Admin Script Status</h2>";
echo "<p><strong>Current Hook:</strong> " . (isset($_GET['page']) ? $_GET['page'] : 'N/A') . "</p>";
echo "<p><strong>Current Screen:</strong> " . (function_exists('get_current_screen') ? get_current_screen()->id : 'N/A') . "</p>";

// Test 4: Show expected behavior
echo "<h2>4. Expected Behavior</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
echo "<h3>✅ What Should Happen:</h3>";
echo "<ul>";
echo "<li><strong>Member post types</strong> should use the classic editor (TinyMCE)</li>";
echo "<li><strong>Block editor</strong> should be disabled for member post types</li>";
echo "<li><strong>REST API support</strong> should be disabled for member post types</li>";
echo "<li><strong>ACF fields</strong> should display properly in classic editor</li>";
echo "</ul>";

echo "<h3>❌ What Should NOT Happen:</h3>";
echo "<ul>";
echo "<li>Block editor should NOT appear for member post types</li>";
echo "<li>Gutenberg blocks should NOT be available</li>";
echo "<li>REST API should NOT be enabled for member post types</li>";
echo "</ul>";
echo "</div>";

// Test 5: Direct links to test editing
echo "<h2>5. Test Links</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
echo "<h3>Click these links to test the editor:</h3>";
echo "<ul>";
foreach ($member_post_types as $post_type) {
    $post_type_obj = get_post_type_object($post_type);
    echo "<li>";
    echo "<a href='" . admin_url('post-new.php?post_type=' . $post_type) . "' target='_blank' class='button'>";
    echo "Create New {$post_type_obj->labels->singular_name}";
    echo "</a>";
    echo " - Should open with classic editor";
    echo "</li>";
}
echo "</ul>";
echo "</div>";

// Test 6: Check if Classic Editor plugin is active
echo "<h2>6. Classic Editor Plugin Status</h2>";
if (is_plugin_active('classic-editor/classic-editor.php')) {
    echo "<p>✅ Classic Editor plugin is active</p>";
} else {
    echo "<p>⚠️ Classic Editor plugin is not active (not required, but helpful)</p>";
}

// Test 7: Check WordPress version and block editor status
echo "<h2>7. WordPress & Block Editor Status</h2>";
global $wp_version;
echo "<p><strong>WordPress Version:</strong> {$wp_version}</p>";
echo "<p><strong>Block Editor Available:</strong> " . (function_exists('register_block_type') ? '✅ Yes' : '❌ No') . "</p>";

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.button { 
    display: inline-block; 
    padding: 10px 20px; 
    background: #0073aa; 
    color: white; 
    text-decoration: none; 
    border-radius: 4px; 
    margin: 5px;
}
.button:hover { background: #005a87; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>
