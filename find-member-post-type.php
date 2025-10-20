<?php
/**
 * Script to help find the ACF-created member post type
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

echo "<h1>Find Your ACF Member Post Type</h1>";

// Get all post types
$post_types = get_post_types([], 'objects');

echo "<h2>All Post Types in System:</h2>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f2f2f2;'>";
echo "<th>Post Type Name</th><th>Label</th><th>Singular Name</th><th>Created By</th><th>Show in Menu</th><th>Action</th>";
echo "</tr>";

$member_candidates = [];

foreach ($post_types as $post_type_name => $post_type_obj) {
    $created_by = 'WordPress';
    
    // Check if it's created by ACF
    if (function_exists('acf_get_post_type') && acf_get_post_type($post_type_name)) {
        $created_by = '<span style="color: #0073aa; font-weight: bold;">ACF</span>';
    }
    
    // Check if it's created by our plugin
    if (strpos($post_type_name, 'scn_') === 0) {
        $created_by = '<span style="color: #28a745; font-weight: bold;">SCN Plugin</span>';
    }
    
    // Check if this might be a member post type
    $is_member_candidate = false;
    $member_indicators = [];
    
    $name_lower = strtolower($post_type_name);
    $label_lower = strtolower($post_type_obj->label);
    $singular_lower = strtolower($post_type_obj->labels->singular_name);
    
    if (strpos($name_lower, 'member') !== false) {
        $is_member_candidate = true;
        $member_indicators[] = 'Name contains "member"';
    }
    if (strpos($label_lower, 'member') !== false) {
        $is_member_candidate = true;
        $member_indicators[] = 'Label contains "member"';
    }
    if (strpos($singular_lower, 'member') !== false) {
        $is_member_candidate = true;
        $member_indicators[] = 'Singular name contains "member"';
    }
    
    // Highlight potential member post types
    $row_style = '';
    if ($is_member_candidate) {
        $row_style = 'style="background: #fff3cd;"';
        $member_candidates[] = [
            'name' => $post_type_name,
            'label' => $post_type_obj->label,
            'indicators' => $member_indicators
        ];
    }
    
    echo "<tr {$row_style}>";
    echo "<td><strong>{$post_type_name}</strong></td>";
    echo "<td>{$post_type_obj->label}</td>";
    echo "<td>{$post_type_obj->labels->singular_name}</td>";
    echo "<td>{$created_by}</td>";
    echo "<td>" . ($post_type_obj->show_in_menu ? '✅ Yes' : '❌ No') . "</td>";
    
    // Add action buttons
    echo "<td>";
    echo "<a href='" . admin_url('edit.php?post_type=' . $post_type_name) . "' target='_blank' style='background: #0073aa; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;'>View Posts</a>";
    if ($created_by === '<span style="color: #0073aa; font-weight: bold;">ACF</span>') {
        echo "<span style='background: #28a745; color: white; padding: 5px 10px; border-radius: 3px;'>ACF Created</span>";
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Show member candidates
if (!empty($member_candidates)) {
    echo "<h2>🎯 Potential Member Post Types:</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
    foreach ($member_candidates as $candidate) {
        echo "<div style='margin-bottom: 15px; padding: 10px; background: white; border-radius: 4px;'>";
        echo "<h3 style='margin: 0 0 10px 0;'>{$candidate['label']} ({$candidate['name']})</h3>";
        echo "<p><strong>Why it might be a member post type:</strong></p>";
        echo "<ul>";
        foreach ($candidate['indicators'] as $indicator) {
            echo "<li>{$indicator}</li>";
        }
        echo "</ul>";
        echo "<p><strong>To add this to SCN menu, add this line to AdminService.php:</strong></p>";
        echo "<code style='background: #f8f9fa; padding: 10px; display: block; border-radius: 4px;'>";
        echo "// '{$candidate['name']}',";
        echo "</code>";
        echo "</div>";
    }
    echo "</div>";
}

// Show instructions
echo "<h2>📝 Instructions:</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
echo "<ol>";
echo "<li><strong>Look for ACF-created post types</strong> in the table above (they'll be marked in blue)</li>";
echo "<li><strong>Find the one that represents members</strong> - it might be called 'member', 'members', or similar</li>";
echo "<li><strong>Copy the post type name</strong> (the first column)</li>";
echo "<li><strong>Edit the AdminService.php file</strong> and add the post type name to the \$known_member_post_types array</li>";
echo "<li><strong>Refresh your WordPress admin</strong> and check the SCN Membership menu</li>";
echo "</ol>";

echo "<h3>Example:</h3>";
echo "<p>If your member post type is called 'member', you would edit this section in AdminService.php:</p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>";
echo '$known_member_post_types = [
    \'member\',           // Add your ACF-created member post type name here
    // \'member\',       // Or any other member-related post types
    // \'acf_member\',       // etc.
];';
echo "</pre>";
echo "</div>";

// Show current SCN menu structure
echo "<h2>Current SCN Menu Structure:</h2>";
global $submenu;
if (isset($submenu['scn-membership'])) {
    echo "<ul>";
    foreach ($submenu['scn-membership'] as $submenu_item) {
        echo "<li><strong>{$submenu_item[0]}</strong> - {$submenu_item[2]}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No SCN Membership submenus found.</p>";
}

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { text-align: left; padding: 8px; border: 1px solid #ddd; }
th { background-color: #f2f2f2; font-weight: bold; }
a { color: #0073aa; text-decoration: none; }
a:hover { text-decoration: underline; }
code { font-family: monospace; background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
pre { font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>
