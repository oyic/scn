<?php
/**
 * Test script to verify Members menu is properly configured under SCN
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

echo "<h1>SCN Members Menu Test</h1>";

// Test 1: Check post type registration
echo "<h2>1. Post Type Registration</h2>";
if (post_type_exists('scn_profile')) {
    $post_type_obj = get_post_type_object('scn_profile');
    echo "<p>✅ scn_profile post type is registered</p>";
    echo "<ul>";
    echo "<li><strong>Label:</strong> {$post_type_obj->label}</li>";
    echo "<li><strong>Singular Name:</strong> {$post_type_obj->labels->singular_name}</li>";
    echo "<li><strong>Menu Position:</strong> " . ($post_type_obj->menu_position ?? 'Not set') . "</li>";
    echo "<li><strong>Show in Menu:</strong> " . ($post_type_obj->show_in_menu ? '✅ Yes' : '❌ No') . "</li>";
    echo "</ul>";
} else {
    echo "<p>❌ scn_profile post type is not registered</p>";
}

// Test 2: Check admin menu structure
echo "<h2>2. Admin Menu Structure</h2>";
global $menu, $submenu;

// Find SCN Membership main menu
$scn_main_menu = null;
foreach ($menu as $menu_item) {
    if (isset($menu_item[2]) && $menu_item[2] === 'scn-membership') {
        $scn_main_menu = $menu_item;
        break;
    }
}

if ($scn_main_menu) {
    echo "<p>✅ SCN Membership main menu found</p>";
    echo "<ul>";
    echo "<li><strong>Title:</strong> {$scn_main_menu[0]}</li>";
    echo "<li><strong>Slug:</strong> {$scn_main_menu[2]}</li>";
    echo "<li><strong>Position:</strong> {$scn_main_menu[4]}</li>";
    echo "</ul>";
    
    // Check submenus
    if (isset($submenu['scn-membership'])) {
        echo "<p><strong>Submenus under SCN Membership:</strong></p>";
        echo "<ul>";
        foreach ($submenu['scn-membership'] as $submenu_item) {
            $title = $submenu_item[0];
            $slug = $submenu_item[2];
            echo "<li><strong>{$title}</strong> - {$slug}</li>";
            
            if (strpos($slug, 'edit.php?post_type=scn_profile') !== false) {
                echo "<span style='color: green;'> ✅ This is the Members submenu!</span>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No submenus found under SCN Membership</p>";
    }
} else {
    echo "<p>❌ SCN Membership main menu not found</p>";
}

// Test 3: Check direct access to Members
echo "<h2>3. Direct Access Links</h2>";
$admin_links = [
    'SCN Membership Main' => admin_url('admin.php?page=scn-membership'),
    'Members List' => admin_url('edit.php?post_type=scn_profile'),
    'Add New Member' => admin_url('post-new.php?post_type=scn_profile'),
    'Member Field Groups' => admin_url('edit.php?post_type=acf-field-group'),
];

echo "<ul>";
foreach ($admin_links as $name => $url) {
    echo "<li><a href='{$url}' target='_blank'>{$name}</a></li>";
}
echo "</ul>";

// Test 4: Check ACF field group
echo "<h2>4. ACF Field Group</h2>";
if (function_exists('acf_get_field_groups')) {
    $field_groups = acf_get_field_groups();
    $member_field_group = null;
    
    foreach ($field_groups as $group) {
        if (strpos($group['key'], 'scn_profile') !== false || strpos($group['title'], 'Member') !== false) {
            $member_field_group = $group;
            break;
        }
    }
    
    if ($member_field_group) {
        echo "<p>✅ Member field group found: <strong>{$member_field_group['title']}</strong></p>";
        echo "<ul>";
        echo "<li><strong>Key:</strong> {$member_field_group['key']}</li>";
        echo "<li><strong>Title:</strong> {$member_field_group['title']}</li>";
        echo "</ul>";
    } else {
        echo "<p>⚠️ Member field group not found</p>";
    }
} else {
    echo "<p>❌ ACF function not available</p>";
}

// Test 5: Check existing members
echo "<h2>5. Existing Members</h2>";
$members = get_posts([
    'post_type' => 'scn_profile',
    'posts_per_page' => 5,
    'post_status' => 'any',
]);

if (!empty($members)) {
    echo "<p>✅ Found " . count($members) . " member(s):</p>";
    echo "<ul>";
    foreach ($members as $member) {
        echo "<li><a href='" . admin_url('post.php?post=' . $member->ID . '&action=edit') . "' target='_blank'>{$member->post_title}</a> (ID: {$member->ID})</li>";
    }
    echo "</ul>";
} else {
    echo "<p>⚠️ No members found. <a href='" . admin_url('post-new.php?post_type=scn_profile') . "' target='_blank'>Create a test member</a></p>";
}

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
echo "<h3>Expected Menu Structure:</h3>";
echo "<ul>";
echo "<li><strong>SCN Membership</strong> (main menu)</li>";
echo "<li>├── Settings</li>";
echo "<li>├── Members ← This should link to scn_profile post type</li>";
echo "<li>├── Courses</li>";
echo "<li>├── Events</li>";
echo "<li>└── Other submenus...</li>";
echo "</ul>";
echo "</div>";

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
a { color: #0073aa; }
a:hover { color: #005a87; }
</style>
