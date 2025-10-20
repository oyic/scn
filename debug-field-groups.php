<?php
/**
 * Debug ACF Field Groups - Find where member_courses is defined
 */

require_once(__DIR__ . '/../../../wp-load.php');

if (!function_exists('acf_get_field_groups')) {
    die('ACF is not available');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug ACF Field Groups</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1400px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: top; }
        th { background-color: #0073aa; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .highlight { background-color: yellow !important; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; margin: 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h1>🔍 Debug ACF Field Groups - Find member_courses</h1>

<?php

$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 561;

echo "<h2>All Field Groups for Member Post Type</h2>";

$all_groups = acf_get_field_groups(['post_type' => 'member']);

echo "<table>";
echo "<tr><th>Key</th><th>Title</th><th>Fields</th><th>Location</th></tr>";

$found_member_courses = false;
$member_courses_info = [];

foreach ($all_groups as $group) {
    $is_highlight = false;
    
    // Get fields in this group
    $fields = acf_get_fields($group['key']);
    
    $fields_display = '';
    if ($fields) {
        foreach ($fields as $field) {
            $fields_display .= "- {$field['name']} ({$field['type']})";
            
            // Check if this is member_courses
            if ($field['name'] === 'member_courses') {
                $is_highlight = true;
                $found_member_courses = true;
                $member_courses_info = [
                    'group' => $group,
                    'field' => $field
                ];
                $fields_display .= " <strong style='color: red;'>← MEMBER_COURSES FOUND!</strong>";
            }
            
            $fields_display .= "\n";
            
            // Also check sub_fields
            if (isset($field['sub_fields']) && is_array($field['sub_fields'])) {
                foreach ($field['sub_fields'] as $sub_field) {
                    $fields_display .= "  └─ {$sub_field['name']} ({$sub_field['type']})";
                    
                    if ($sub_field['name'] === 'member_courses') {
                        $is_highlight = true;
                        $found_member_courses = true;
                        $member_courses_info = [
                            'group' => $group,
                            'field' => $sub_field,
                            'parent_field' => $field
                        ];
                        $fields_display .= " <strong style='color: red;'>← MEMBER_COURSES FOUND!</strong>";
                    }
                    
                    $fields_display .= "\n";
                }
            }
        }
    }
    
    echo "<tr" . ($is_highlight ? " class='highlight'" : "") . ">";
    echo "<td>{$group['key']}</td>";
    echo "<td>{$group['title']}</td>";
    echo "<td><pre>" . htmlspecialchars($fields_display) . "</pre></td>";
    echo "<td><pre>" . htmlspecialchars(print_r($group['location'], true)) . "</pre></td>";
    echo "</tr>";
}

echo "</table>";

if ($found_member_courses) {
    echo "<h2 class='success'>✅ Found member_courses field!</h2>";
    echo "<h3>Field Details:</h3>";
    echo "<pre>" . htmlspecialchars(print_r($member_courses_info, true)) . "</pre>";
} else {
    echo "<h2 class='error'>❌ member_courses field NOT found in any field group!</h2>";
}

// Now check actual data for a member
echo "<h2>Actual Data for Member {$member_id}</h2>";

$member = get_post($member_id);
if ($member) {
    echo "<p><strong>Member:</strong> {$member->post_title}</p>";
    
    // Try get_field
    $course_ids = get_field('member_courses', $member_id, false);
    echo "<h3>get_field('member_courses', {$member_id}, false)</h3>";
    echo "<p><strong>Type:</strong> " . gettype($course_ids) . "</p>";
    echo "<p><strong>Value:</strong></p>";
    echo "<pre>" . htmlspecialchars(print_r($course_ids, true)) . "</pre>";
    
    // Check all meta
    echo "<h3>All Post Meta (filtered for 'course')</h3>";
    $all_meta = get_post_meta($member_id);
    echo "<table>";
    echo "<tr><th>Meta Key</th><th>Value</th></tr>";
    foreach ($all_meta as $key => $value) {
        if (stripos($key, 'course') !== false || stripos($key, 'member_courses') !== false) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($key) . "</td>";
            echo "<td><pre>" . htmlspecialchars(print_r($value, true)) . "</pre></td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // Show what courses would be displayed
    echo "<h3>Courses That Would Display</h3>";
    if ($course_ids !== false && is_array($course_ids) && !empty($course_ids)) {
        echo "<p class='success'>Using relationship field (count: " . count($course_ids) . ")</p>";
        echo "<ul>";
        foreach ($course_ids as $cid) {
            $course = get_post($cid);
            if ($course) {
                echo "<li>ID: {$cid} - {$course->post_title}</li>";
            } else {
                echo "<li>ID: {$cid} - <em>Course not found</em></li>";
            }
        }
        echo "</ul>";
    } elseif ($course_ids === false) {
        echo "<p class='error'>Field never set - would use author fallback</p>";
        $user_id = get_post_meta($member_id, 'scn_user_id', true);
        if ($user_id) {
            $author_courses = get_posts([
                'post_type' => 'course',
                'author' => $user_id,
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ]);
            echo "<p>Would show " . count($author_courses) . " courses by author {$user_id}</p>";
        }
    } else {
        echo "<p class='error'>Field is empty array - show no courses</p>";
    }
}

?>

<h2>Actions</h2>
<p>
    <a href="?member_id=<?php echo $member_id; ?>">Refresh</a> |
    <a href="/wp-admin/post.php?post=<?php echo $member_id; ?>&action=edit" target="_blank">Edit Member <?php echo $member_id; ?></a> |
    <a href="/wp-admin/edit.php?post_type=acf-field-group" target="_blank">View ACF Field Groups</a>
</p>

</body>
</html>

