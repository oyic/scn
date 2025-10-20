<?php
/**
 * Setup bi-directional relationship between Member and Course CPTs
 */

require_once(__DIR__ . '/../../../wp-load.php');

echo "<h1>Setup Member-Course Bi-directional Relationship</h1>";

if (!function_exists('acf_add_local_field_group')) {
    echo "<p style='color:red;'>❌ ACF is not active</p>";
    exit;
}

// Add relationship field to Member CPT
acf_add_local_field_group([
    'key' => 'group_member_courses_relationship',
    'title' => 'Courses',
    'fields' => [
        [
            'key' => 'field_member_courses',
            'label' => 'Courses',
            'name' => 'member_courses',
            'type' => 'relationship',
            'instructions' => 'Select courses this member has created or teaches',
            'post_type' => ['course'],
            'filters' => ['search', 'taxonomy'],
            'return_format' => 'id',
            'bidirectional' => 1,
            'bidirectional_target' => ['field_course_author']
        ]
    ],
    'location' => [
        [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'member',
            ]
        ]
    ],
    'menu_order' => 100,
    'position' => 'normal',
    'style' => 'default',
]);

// Add relationship field to Course CPT
acf_add_local_field_group([
    'key' => 'group_course_author_relationship',
    'title' => 'Author',
    'fields' => [
        [
            'key' => 'field_course_author',
            'label' => 'Author',
            'name' => 'author',
            'type' => 'relationship',
            'instructions' => 'Select the member who created this course',
            'post_type' => ['member'],
            'filters' => ['search'],
            'return_format' => 'id',
            'max' => 1,
            'bidirectional' => 1,
            'bidirectional_target' => ['field_member_courses']
        ]
    ],
    'location' => [
        [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'course',
            ]
        ]
    ],
    'menu_order' => 50,
    'position' => 'side',
    'style' => 'default',
]);

echo "<p style='color:green;'>✅ Bi-directional relationship fields registered!</p>";
echo "<p><strong>What was created:</strong></p>";
echo "<ul>";
echo "<li><strong>Member CPT:</strong> 'Courses' field (member_courses) - select courses this member teaches</li>";
echo "<li><strong>Course CPT:</strong> 'Author' field (author) - select the member who created this course (max 1)</li>";
echo "<li><strong>Bi-directional:</strong> Adding a course to a member automatically adds that member as author to the course (and vice versa)</li>";
echo "</ul>";

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Edit a Member and you'll see the 'Courses' relationship field</li>";
echo "<li>Edit a Course and you'll see the 'Instructors / Authors' relationship field</li>";
echo "<li>Add a course to a member - it will automatically link both ways!</li>";
echo "</ol>";

echo "<p><a href='/wp-admin/post.php?post=561&action=edit'>Edit Member 561</a> | <a href='/member/boyic-alberto-mba/#courses'>View Member Profile</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; }
ul, ol { margin-left: 20px; line-height: 1.8; }
</style>";

