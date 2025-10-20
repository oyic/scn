<?php
/**
 * Add Title Field to Videos Repeater
 */

require_once(__DIR__ . '/../../../wp-load.php');

echo "<h1>Add Title Field to Videos Repeater</h1>";

if (!function_exists('acf_add_local_field')) {
    echo "<p style='color:red;'>❌ ACF is not active</p>";
    exit;
}

// Get the existing videos field group
$field_groups = acf_get_field_groups(['post_type' => 'member']);

$videos_field_group = null;
$videos_field = null;

foreach ($field_groups as $group) {
    $fields = acf_get_fields($group['key']);
    foreach ($fields as $field) {
        if ($field['name'] === 'videos' && $field['type'] === 'repeater') {
            $videos_field_group = $group;
            $videos_field = $field;
            break 2;
        }
    }
}

if (!$videos_field) {
    echo "<p style='color:red;'>❌ Videos field not found</p>";
    exit;
}

echo "<h2>Current Videos Field Structure</h2>";
echo "<p>Field Group: {$videos_field_group['title']}</p>";
echo "<p>Current Sub-fields:</p>";
echo "<ul>";
foreach ($videos_field['sub_fields'] as $sub) {
    echo "<li>{$sub['label']} ({$sub['name']}) - Type: {$sub['type']}</li>";
}
echo "</ul>";

// Check if title field already exists
$has_title = false;
foreach ($videos_field['sub_fields'] as $sub) {
    if ($sub['name'] === 'title') {
        $has_title = true;
        break;
    }
}

if ($has_title) {
    echo "<p style='color:green;'>✅ Title field already exists!</p>";
} else {
    echo "<p style='color:orange;'>⚠️ Title field is missing. Adding it now...</p>";
    
    // Add title field to the repeater
    $title_field = [
        'key' => 'field_video_title_' . time(),
        'label' => 'Video Title',
        'name' => 'title',
        'type' => 'text',
        'instructions' => 'Enter a descriptive title for this video',
        'required' => 0,
        'parent' => $videos_field['key'],
        'wrapper' => [
            'width' => '',
            'class' => '',
            'id' => '',
        ],
        'default_value' => '',
        'placeholder' => 'e.g., Introduction to Dental Implants',
        'prepend' => '',
        'append' => '',
        'maxlength' => '',
    ];
    
    // This would need to be done in ACF settings, but we can update the database directly
    echo "<p style='color:blue;'>ℹ️ To add the title field properly:</p>";
    echo "<ol>";
    echo "<li>Go to <a href='/wp-admin/edit.php?post_type=acf-field-group'>Custom Fields</a></li>";
    echo "<li>Find the field group containing 'Videos' repeater</li>";
    echo "<li>Click Edit</li>";
    echo "<li>Click on 'Videos' repeater field</li>";
    echo "<li>Click '+ Add Field' to add a sub-field</li>";
    echo "<li>Set Field Label: <strong>Video Title</strong></li>";
    echo "<li>Set Field Name: <strong>title</strong></li>";
    echo "<li>Set Field Type: <strong>Text</strong></li>";
    echo "<li>Click 'Update' to save</li>";
    echo "</ol>";
}

// Now let's check member 561 videos and try to extract titles from URLs
echo "<h2>Member 561 Current Videos</h2>";
$member_id = 561;
$videos = get_field('videos', $member_id);

if ($videos && is_array($videos)) {
    echo "<p>Found " . count($videos) . " videos</p>";
    
    echo "<h3>Suggested Titles from URLs:</h3>";
    foreach ($videos as $index => $video) {
        $url = $video['url'] ?? '';
        $current_title = $video['title'] ?? '';
        
        echo "<div style='background:#f8f9fa; padding:15px; margin:10px 0; border-radius:5px;'>";
        echo "<strong>Video #{$index}:</strong><br>";
        echo "URL: {$url}<br>";
        echo "Current Title: " . ($current_title ?: '<em>none</em>') . "<br>";
        
        // Try to extract video title from platform
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $url, $matches);
            if (!empty($matches[1])) {
                $video_id = $matches[1];
                echo "YouTube Video ID: {$video_id}<br>";
                echo "<em>Suggested: Fetch from YouTube API or set manually</em>";
            }
        } elseif (strpos($url, 'vimeo.com') !== false) {
            preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
            if (!empty($matches[1])) {
                $video_id = $matches[1];
                echo "Vimeo Video ID: {$video_id}<br>";
                echo "<em>Suggested: Fetch from Vimeo API or set manually</em>";
            }
        }
        echo "</div>";
    }
} else {
    echo "<p style='color:orange;'>No videos found</p>";
}

// Provide manual update form
echo "<h2>Manual Update (After Adding Title Field)</h2>";
echo "<p>Once you've added the title field in ACF, you can manually update video titles here:</p>";

if ($videos && is_array($videos)) {
    echo "<form method='post'>";
    echo wp_nonce_field('update_video_titles', 'video_titles_nonce', true, false);
    
    foreach ($videos as $index => $video) {
        $url = $video['url'] ?? '';
        $current_title = $video['title'] ?? '';
        
        echo "<div style='background:#fff; padding:15px; margin:10px 0; border:1px solid #ddd; border-radius:5px;'>";
        echo "<label><strong>Video #{$index}</strong></label><br>";
        echo "<small>{$url}</small><br>";
        echo "<input type='text' name='video_titles[{$index}]' value='" . esc_attr($current_title) . "' placeholder='Enter video title' style='width:100%; margin-top:10px; padding:8px;'>";
        echo "</div>";
    }
    
    echo "<button type='submit' name='update_titles' style='background:#3498db; color:white; padding:12px 24px; border:none; border-radius:5px; cursor:pointer; font-size:16px; margin-top:20px;'>Update Video Titles</button>";
    echo "</form>";
}

// Process form submission
if (isset($_POST['update_titles']) && wp_verify_nonce($_POST['video_titles_nonce'], 'update_video_titles')) {
    $new_titles = $_POST['video_titles'] ?? [];
    $updated_videos = [];
    
    foreach ($videos as $index => $video) {
        $video['title'] = $new_titles[$index] ?? '';
        $updated_videos[] = $video;
    }
    
    update_field('videos', $updated_videos, $member_id);
    
    echo "<div style='background:#d4edda; color:#155724; padding:15px; margin:20px 0; border-radius:5px; border:1px solid #c3e6cb;'>";
    echo "<strong>✅ Video titles updated successfully!</strong><br>";
    echo "<a href='" . $_SERVER['PHP_SELF'] . "'>Refresh page</a> | <a href='/member/boyic-alberto-mba/#media'>View Member Profile</a>";
    echo "</div>";
}

echo "<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 30px; max-width: 1000px; margin: 0 auto; }
h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 15px; }
h2 { color: #34495e; margin-top: 40px; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
h3 { color: #555; margin-top: 25px; }
ul, ol { line-height: 1.8; }
a { color: #3498db; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>";

