<?php
/**
 * Check what post types exist in the system
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

echo "<h1>Available Post Types</h1>";

// Get all post types
$post_types = get_post_types([], 'objects');

echo "<h2>All Post Types:</h2>";
echo "<table border='1' cellpadding='10' cellspacing='0'>";
echo "<tr><th>Name</th><th>Label</th><th>Singular Name</th><th>Show in Menu</th><th>Menu Position</th><th>Created By</th></tr>";

foreach ($post_types as $post_type_name => $post_type_obj) {
    $created_by = 'WordPress';
    
    // Check if it's created by ACF
    if (function_exists('acf_get_post_type') && acf_get_post_type($post_type_name)) {
        $created_by = 'ACF';
    }
    
    // Check if it's created by our plugin
    if (strpos($post_type_name, 'scn_') === 0) {
        $created_by = 'SCN Plugin';
    }
    
    echo "<tr>";
    echo "<td><strong>{$post_type_name}</strong></td>";
    echo "<td>{$post_type_obj->label}</td>";
    echo "<td>{$post_type_obj->labels->singular_name}</td>";
    echo "<td>" . ($post_type_obj->show_in_menu ? '✅ Yes' : '❌ No') . "</td>";
    echo "<td>" . ($post_type_obj->menu_position ?? 'Not set') . "</td>";
    echo "<td>{$created_by}</td>";
    echo "</tr>";
}

echo "</table>";

// Check specifically for member-related post types
echo "<h2>Member-Related Post Types:</h2>";
$member_post_types = array_filter($post_types, function($post_type) {
    $name = strtolower($post_type->name);
    $label = strtolower($post_type->label);
    $singular = strtolower($post_type->labels->singular_name);
    
    return strpos($name, 'member') !== false || 
           strpos($label, 'member') !== false || 
           strpos($singular, 'member') !== false;
});

if (!empty($member_post_types)) {
    echo "<ul>";
    foreach ($member_post_types as $post_type_name => $post_type_obj) {
        echo "<li><strong>{$post_type_name}</strong>: {$post_type_obj->label} (Singular: {$post_type_obj->labels->singular_name})</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No member-related post types found.</p>";
}

// Check ACF post types specifically
echo "<h2>ACF Post Types:</h2>";
if (function_exists('acf_get_post_types')) {
    $acf_post_types = acf_get_post_types();
    if (!empty($acf_post_types)) {
        echo "<ul>";
        foreach ($acf_post_types as $post_type) {
            echo "<li><strong>{$post_type->post_name}</strong>: {$post_type->post_title}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No ACF post types found.</p>";
    }
} else {
    echo "<p>ACF function not available.</p>";
}

// Show admin menu structure
echo "<h2>Current Admin Menu Structure:</h2>";
global $menu, $submenu;

echo "<h3>Main Menu Items:</h3>";
echo "<ul>";
foreach ($menu as $menu_item) {
    if (!empty($menu_item[0]) && !empty($menu_item[2])) {
        echo "<li><strong>{$menu_item[0]}</strong> - {$menu_item[2]}</li>";
    }
}
echo "</ul>";

echo "<h3>SCN Membership Submenus:</h3>";
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
table { border-collapse: collapse; width: 100%; }
th, td { text-align: left; padding: 8px; }
th { background-color: #f2f2f2; }
</style>
