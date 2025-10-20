<?php
/**
 * Debug Course Relationship - Check if bidirectional fields are working
 */

require_once(__DIR__ . '/../../../wp-load.php');

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Course Relationship</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #0073aa; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>

<h1>🔍 Debug Course Relationship</h1>

<?php

$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 561;

echo "<div class='info'>";
echo "<strong>Testing Member ID:</strong> {$member_id}<br>";
echo "<strong>Current URL:</strong> " . home_url() . "<br>";
echo "<strong>Change Member:</strong> <a href='?member_id={$member_id}'>Refresh</a> | ";
echo "<a href='?member_id=561'>Member 561</a><br>";
echo "</div>";

// Check if ACF is available
if (!function_exists('get_field')) {
    echo "<p class='error'>❌ ACF is not available!</p>";
    exit;
}
echo "<p class='success'>✅ ACF is available</p>";

// Get member
$member = get_post($member_id);
if (!$member || $member->post_type !== 'member') {
    echo "<p class='error'>❌ Member {$member_id} not found or not a member post type</p>";
    exit;
}

echo "<h2>Member: {$member->post_title}</h2>";

// Get member_courses field (with cache bypass)
echo "<h2>Testing get_field('member_courses', {$member_id}, false)</h2>";
$course_ids = get_field('member_courses', $member_id, false);

echo "<p><strong>Result Type:</strong> " . gettype($course_ids) . "</p>";
echo "<p><strong>Result Value:</strong></p>";
echo "<pre>";
var_dump($course_ids);
echo "</pre>";

// Check what's in raw post meta
echo "<h2>Raw Post Meta</h2>";
$raw_meta = get_post_meta($member_id, 'member_courses', true);
echo "<p><strong>get_post_meta('member_courses'):</strong></p>";
echo "<pre>";
var_dump($raw_meta);
echo "</pre>";

// Get all meta keys related to courses
echo "<h2>All Course-Related Meta</h2>";
$all_meta = get_post_meta($member_id);
echo "<table>";
echo "<tr><th>Meta Key</th><th>Value</th></tr>";
foreach ($all_meta as $key => $value) {
    if (strpos($key, 'course') !== false || strpos($key, 'member_courses') !== false || $key === '_member_courses') {
        echo "<tr>";
        echo "<td>" . esc_html($key) . "</td>";
        echo "<td><pre>" . esc_html(print_r($value, true)) . "</pre></td>";
        echo "</tr>";
    }
}
echo "</table>";

// Test the logic from templates
echo "<h2>Template Logic Test</h2>";
$member_courses = [];
$member_user_id = get_post_meta($member_id, 'scn_user_id', true);

if ($course_ids !== false && is_array($course_ids) && !empty($course_ids)) {
    echo "<p class='success'>✅ Field is set and has courses</p>";
    $member_courses = get_posts([
        'post_type' => 'course',
        'post__in' => $course_ids,
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'post__in',
    ]);
    echo "<p>Found " . count($member_courses) . " courses via relationship field</p>";
} elseif ($course_ids === false) {
    echo "<p class='warning'>⚠️ Field never been set - using author fallback</p>";
    if ($member_user_id) {
        $member_courses = get_posts([
            'post_type' => 'course',
            'author' => $member_user_id,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        echo "<p>Found " . count($member_courses) . " courses by author ({$member_user_id})</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Field was set but cleared (empty array) - showing no courses</p>";
}

// Display courses
if (!empty($member_courses)) {
    echo "<h2>Courses Display</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Author ID</th></tr>";
    foreach ($member_courses as $course) {
        echo "<tr>";
        echo "<td>" . $course->ID . "</td>";
        echo "<td>" . esc_html($course->post_title) . "</td>";
        echo "<td>" . $course->post_status . "</td>";
        echo "<td>" . $course->post_author . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>No courses to display</p>";
}

// Check bidirectional field groups
echo "<h2>ACF Field Groups Status</h2>";
$field_groups = acf_get_field_groups();
echo "<table>";
echo "<tr><th>Key</th><th>Title</th><th>Location</th></tr>";
foreach ($field_groups as $group) {
    if (strpos($group['key'], 'course') !== false || strpos($group['key'], 'member') !== false) {
        echo "<tr>";
        echo "<td>" . esc_html($group['key']) . "</td>";
        echo "<td>" . esc_html($group['title']) . "</td>";
        echo "<td><pre>" . esc_html(print_r($group['location'], true)) . "</pre></td>";
        echo "</tr>";
    }
}
echo "</table>";

// Check if our programmatic field groups exist
$member_courses_group = acf_get_field_group('group_member_courses_relationship');
$course_author_group = acf_get_field_group('group_course_author_relationship');

if ($member_courses_group) {
    echo "<p class='success'>✅ Member Courses field group registered</p>";
} else {
    echo "<p class='error'>❌ Member Courses field group NOT registered</p>";
}

if ($course_author_group) {
    echo "<p class='success'>✅ Course Author field group registered</p>";
} else {
    echo "<p class='error'>❌ Course Author field group NOT registered</p>";
}

?>

<h2>Actions</h2>
<p>
    <a href="/wp-admin/post.php?post=<?php echo $member_id; ?>&action=edit" target="_blank">Edit Member <?php echo $member_id; ?> in Admin</a> |
    <a href="/member/<?php echo $member->post_name; ?>/#courses" target="_blank">View Member Profile</a>
</p>

</body>
</html>

