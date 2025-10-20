<?php
/**
 * Fix Member 561 Course Relationships and Populate Course Fields
 */

require_once(__DIR__ . '/../../../wp-load.php');

echo "<h1>Fix Member 561 Course Relationships</h1>";

$member_id = 561;

// Get member info
$member = get_post($member_id);
if (!$member) {
    echo "<p style='color:red;'>❌ Member 561 not found</p>";
    exit;
}

echo "<h2>Member: {$member->post_title}</h2>";

// Get user ID from member
$member_user_id = get_post_meta($member_id, 'scn_user_id', true);
echo "<p>Member User ID: {$member_user_id}</p>";

// Check current ACF relationship field
$current_member_courses = get_field('member_courses', $member_id);
echo "<h3>Current ACF Relationship Field (member_courses):</h3>";
if ($current_member_courses) {
    echo "<pre>" . print_r($current_member_courses, true) . "</pre>";
} else {
    echo "<p style='color:orange;'>⚠️ No courses in ACF relationship field</p>";
}

// Get courses by author (WordPress author field)
$courses_by_author = get_posts([
    'post_type' => 'course',
    'author' => $member_user_id,
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

echo "<h3>Courses by Author (WordPress author field):</h3>";
if ($courses_by_author) {
    echo "<ul>";
    foreach ($courses_by_author as $course) {
        echo "<li>ID: {$course->ID} - {$course->post_title}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No courses found by author</p>";
}

// Get all courses to find those that should be linked
$all_courses = get_posts([
    'post_type' => 'course',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

echo "<h3>All Courses:</h3>";
echo "<ul>";
$member_561_courses = [];
foreach ($all_courses as $course) {
    $course_author_field = get_field('author', $course->ID);
    $is_linked = $course_author_field && in_array($member_id, (array)$course_author_field);
    
    echo "<li>ID: {$course->ID} - {$course->post_title} ";
    echo "| WP Author: {$course->post_author} ";
    echo "| ACF Author: " . ($course_author_field ? print_r($course_author_field, true) : 'none');
    
    if ($is_linked) {
        echo " <strong style='color:green;'>✓ Linked to Member 561</strong>";
        $member_561_courses[] = $course->ID;
    }
    
    echo "</li>";
}
echo "</ul>";

// Fix the bi-directional relationship
echo "<h2>Fixing Relationships...</h2>";

// If there are courses by author but not in ACF field, sync them
$course_ids_to_sync = [];

if ($courses_by_author) {
    foreach ($courses_by_author as $course) {
        $course_ids_to_sync[] = $course->ID;
    }
}

// Also include any courses that already reference member 561
$course_ids_to_sync = array_unique(array_merge($course_ids_to_sync, $member_561_courses));

if (!empty($course_ids_to_sync)) {
    // Update member's courses field
    update_field('member_courses', $course_ids_to_sync, $member_id);
    echo "<p style='color:green;'>✅ Updated member_courses field with " . count($course_ids_to_sync) . " courses</p>";
    
    // Update each course's author field to point to member 561
    foreach ($course_ids_to_sync as $course_id) {
        update_field('author', [$member_id], $course_id);
        echo "<p style='color:green;'>✅ Updated course {$course_id} author field</p>";
    }
} else {
    echo "<p style='color:orange;'>⚠️ No courses to sync</p>";
}

// Now populate course images and other fields
echo "<h2>Populating Course Images and Fields</h2>";

// Sample course images (dental-related stock images)
$sample_images = [
    'https://images.unsplash.com/photo-1606811841689-23dfddce3e95?w=800',  // Dental tools
    'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=800',  // Dentist working
    'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=800',  // Dental chair
    'https://images.unsplash.com/photo-1598256989800-fe5f95da9787?w=800',  // Dental equipment
    'https://images.unsplash.com/photo-1609961835077-31e9c5a221b7?w=800',  // Dental clinic
];

foreach ($course_ids_to_sync as $index => $course_id) {
    $course = get_post($course_id);
    echo "<h3>Course: {$course->post_title} (ID: {$course_id})</h3>";
    
    // Check if course already has a featured image
    $has_thumbnail = has_post_thumbnail($course_id);
    
    if (!$has_thumbnail && isset($sample_images[$index])) {
        // Try to upload image from URL
        $image_url = $sample_images[$index];
        
        // Download image
        $tmp = download_url($image_url);
        
        if (!is_wp_error($tmp)) {
            $file_array = [
                'name' => 'course-' . $course_id . '.jpg',
                'tmp_name' => $tmp
            ];
            
            // Upload to media library
            $attachment_id = media_handle_sideload($file_array, $course_id);
            
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($course_id, $attachment_id);
                echo "<p style='color:green;'>✅ Added course image</p>";
            } else {
                @unlink($tmp);
                echo "<p style='color:orange;'>⚠️ Could not attach image: " . $attachment_id->get_error_message() . "</p>";
            }
        } else {
            echo "<p style='color:orange;'>⚠️ Could not download image</p>";
        }
    } else if ($has_thumbnail) {
        echo "<p>✓ Course already has featured image</p>";
    }
    
    // Ensure other course fields are populated
    $subtitle = get_post_meta($course_id, 'course_subtitle', true);
    $description = get_post_meta($course_id, 'course_description', true);
    $ce_enabled = get_post_meta($course_id, 'course_ce_enabled', true);
    $ce_hours = get_post_meta($course_id, 'course_ce_hours', true);
    $formats = get_post_meta($course_id, 'course_formats', true);
    
    echo "<ul>";
    echo "<li>Subtitle: " . ($subtitle ?: '<em>not set</em>') . "</li>";
    echo "<li>Description: " . ($description ? 'set (' . strlen($description) . ' chars)' : '<em>not set</em>') . "</li>";
    echo "<li>CE Enabled: " . ($ce_enabled ? 'yes' : 'no') . "</li>";
    echo "<li>CE Hours: " . ($ce_hours ?: '<em>not set</em>') . "</li>";
    echo "<li>Formats: " . ($formats ? implode(', ', (array)$formats) : '<em>not set</em>') . "</li>";
    echo "</ul>";
    
    // Fill in missing fields with defaults if needed
    if (!$subtitle) {
        update_post_meta($course_id, 'course_subtitle', 'Professional Development Course');
        echo "<p style='color:blue;'>→ Added default subtitle</p>";
    }
    
    if (!$ce_enabled) {
        update_post_meta($course_id, 'course_ce_enabled', 1);
        echo "<p style='color:blue;'>→ Enabled CE credits</p>";
    }
    
    if (!$ce_hours) {
        update_post_meta($course_id, 'course_ce_hours', rand(2, 8));
        echo "<p style='color:blue;'>→ Added CE hours</p>";
    }
    
    if (!$formats) {
        update_post_meta($course_id, 'course_formats', ['live', 'ondemand']);
        echo "<p style='color:blue;'>→ Added course formats</p>";
    }
}

// Verify the fix
echo "<h2>Verification</h2>";
$updated_member_courses = get_field('member_courses', $member_id);
echo "<p><strong>Member 561 now has " . count((array)$updated_member_courses) . " courses in ACF field</strong></p>";

if ($updated_member_courses) {
    echo "<ul>";
    foreach ($updated_member_courses as $course_id) {
        $course = get_post($course_id);
        $course_author = get_field('author', $course_id);
        echo "<li>{$course->post_title} (ID: {$course_id}) - Author field: " . print_r($course_author, true) . "</li>";
    }
    echo "</ul>";
}

echo "<h2>✅ Complete!</h2>";
echo "<p><a href='/wp-admin/post.php?post=561&action=edit'>Edit Member 561 in Admin</a></p>";
echo "<p><a href='/member/boyic-alberto-mba/#courses'>View Member 561 Profile (Courses Tab)</a></p>";
echo "<p><a href='/wp-admin/edit.php?post_type=course'>View All Courses</a></p>";

echo "<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 30px; max-width: 1200px; margin: 0 auto; }
h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 15px; }
h2 { color: #34495e; margin-top: 40px; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
h3 { color: #555; margin-top: 25px; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow: auto; }
ul { line-height: 1.8; }
p { line-height: 1.6; }
a { color: #3498db; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>";

