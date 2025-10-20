<?php
/**
 * Cleanup Duplicate Courses Field
 * Removes the standalone "Courses" field group if it was created programmatically
 */

require_once(__DIR__ . '/../../../wp-load.php');

if (!function_exists('acf_delete_field_group')) {
    die('ACF is not available');
}

echo "<h1>Cleanup Duplicate Courses Field</h1>";

// Find the standalone Courses field group
$all_groups = acf_get_field_groups();

echo "<h2>All ACF Field Groups</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Key</th><th>Title</th><th>Location</th><th>Action</th></tr>";

foreach ($all_groups as $group) {
    echo "<tr>";
    echo "<td>{$group['key']}</td>";
    echo "<td>{$group['title']}</td>";
    echo "<td><pre>" . print_r($group['location'], true) . "</pre></td>";
    
    // Check if this is the duplicate Courses group
    if ($group['key'] === 'group_member_courses_relationship' && $group['title'] === 'Courses') {
        echo "<td>";
        
        if (isset($_GET['delete']) && $_GET['delete'] === $group['key']) {
            // Delete the group
            $result = acf_delete_field_group($group['ID']);
            if ($result) {
                echo "<strong style='color: green;'>✅ DELETED</strong>";
            } else {
                echo "<strong style='color: red;'>❌ Failed to delete</strong>";
            }
        } else {
            echo "<a href='?delete={$group['key']}' style='color: red; font-weight: bold;'>DELETE THIS DUPLICATE</a>";
        }
        
        echo "</td>";
    } else {
        echo "<td>Keep</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

echo "<h2>Summary</h2>";
echo "<p>The correct field should be in the 'Member Information' field group (group_profile_main).</p>";
echo "<p>The duplicate 'Courses' standalone group (group_member_courses_relationship) should be deleted.</p>";

if (isset($_GET['delete'])) {
    echo "<p><a href='?'>Refresh page</a></p>";
}
?>

