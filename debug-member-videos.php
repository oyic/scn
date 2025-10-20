<?php
/**
 * Debug Member Videos Data
 */

require_once(__DIR__ . '/../../../wp-load.php');

echo "<h1>Debug Member Videos</h1>";

$member_id = 561;

// Check if ACF is active
if (!function_exists('get_field')) {
    echo "<p style='color:red;'>❌ ACF is not active</p>";
    exit;
}

// Get videos using different field names
echo "<h2>Testing Different Field Names</h2>";

$videos_test = get_field('videos', $member_id);
echo "<h3>Field: 'videos'</h3>";
echo "<pre>" . print_r($videos_test, true) . "</pre>";

$member_videos_test = get_field('member_videos', $member_id);
echo "<h3>Field: 'member_videos'</h3>";
echo "<pre>" . print_r($member_videos_test, true) . "</pre>";

// Check field groups
echo "<h2>ACF Field Groups for Member CPT</h2>";
if (function_exists('acf_get_field_groups')) {
    $field_groups = acf_get_field_groups(['post_type' => 'member']);
    
    foreach ($field_groups as $group) {
        echo "<h3>{$group['title']} (Key: {$group['key']})</h3>";
        
        $fields = acf_get_fields($group['key']);
        if ($fields) {
            echo "<ul>";
            foreach ($fields as $field) {
                echo "<li><strong>{$field['label']}</strong> (name: {$field['name']}, type: {$field['type']})</li>";
                
                // If it's a repeater, show sub-fields
                if ($field['type'] === 'repeater' && isset($field['sub_fields'])) {
                    echo "<ul>";
                    foreach ($field['sub_fields'] as $sub_field) {
                        echo "<li>↳ {$sub_field['label']} (name: {$sub_field['name']}, type: {$sub_field['type']})";
                        if ($sub_field['type'] === 'image') {
                            echo " - return: " . ($sub_field['return_format'] ?? 'array');
                        }
                        echo "</li>";
                    }
                    echo "</ul>";
                }
            }
            echo "</ul>";
        }
    }
} else {
    echo "<p>ACF field groups function not available</p>";
}

// Test actual video data structure
echo "<h2>Current Video Data for Member {$member_id}</h2>";
$videos = get_field('videos', $member_id);
if ($videos && is_array($videos)) {
    echo "<p>Found " . count($videos) . " videos</p>";
    foreach ($videos as $index => $video) {
        echo "<div style='background:#f8f9fa; padding:15px; margin:10px 0; border-radius:5px;'>";
        echo "<h4>Video #{$index}</h4>";
        echo "<p><strong>Keys:</strong> " . implode(', ', array_keys($video)) . "</p>";
        echo "<pre>" . print_r($video, true) . "</pre>";
        echo "</div>";
    }
} else {
    echo "<p style='color:orange;'>No videos found or not an array</p>";
}

echo "<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 30px; max-width: 1200px; margin: 0 auto; }
h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 15px; }
h2 { color: #34495e; margin-top: 40px; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
h3 { color: #555; margin-top: 25px; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow: auto; font-size: 12px; }
ul { line-height: 1.8; }
</style>";

