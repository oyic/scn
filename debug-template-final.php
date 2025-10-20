<?php
/**
 * Comprehensive template debugging script
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Template Debug - Final Test</h1>\n";

// Check current query
global $wp_query, $post;
echo "<h2>Current Query Info</h2>\n";
echo "is_singular: " . (is_singular() ? 'true' : 'false') . "<br>\n";
echo "is_singular('member'): " . (is_singular('member') ? 'true' : 'false') . "<br>\n";
echo "is_singular('profile'): " . (is_singular('profile') ? 'true' : 'false') . "<br>\n";
echo "get_post_type(): " . get_post_type() . "<br>\n";

if ($post) {
    echo "Current post ID: " . $post->ID . "<br>\n";
    echo "Current post type: " . $post->post_type . "<br>\n";
    echo "Current post title: " . $post->post_title . "<br>\n";
    echo "Current post slug: " . $post->post_name . "<br>\n";
}

// Check template files
echo "<h2>Template Files Check</h2>\n";
$template_paths = [
    'templates/member/single-member.php',
    'templates/single-member.php',
    'templates/profiles/single-member.php'
];

foreach ($template_paths as $path) {
    $full_path = SCN_MEMBERSHIP_PATH . $path;
    echo "Template: $path -> " . ($full_path) . "<br>\n";
    echo "Exists: " . (file_exists($full_path) ? 'YES' : 'NO') . "<br>\n";
    if (file_exists($full_path)) {
        echo "Size: " . filesize($full_path) . " bytes<br>\n";
    }
    echo "<br>\n";
}

// Check if we have any member posts to test with
echo "<h2>Member Posts Available</h2>\n";
$member_posts = get_posts([
    'post_type' => 'member',
    'post_status' => 'publish',
    'numberposts' => 3
]);

if ($member_posts) {
    echo "Found " . count($member_posts) . " member posts:<br>\n";
    foreach ($member_posts as $member_post) {
        echo "- <a href='" . get_permalink($member_post->ID) . "' target='_blank'>" . $member_post->post_title . "</a> (ID: " . $member_post->ID . ", Slug: " . $member_post->post_name . ")<br>\n";
    }
} else {
    echo "No member posts found.<br>\n";
}

// Check profile posts too
$profile_posts = get_posts([
    'post_type' => 'profile',
    'post_status' => 'publish',
    'numberposts' => 3
]);

if ($profile_posts) {
    echo "Found " . count($profile_posts) . " profile posts:<br>\n";
    foreach ($profile_posts as $profile_post) {
        echo "- <a href='" . get_permalink($profile_post->ID) . "' target='_blank'>" . $profile_post->post_title . "</a> (ID: " . $profile_post->ID . ", Slug: " . $profile_post->post_name . ")<br>\n";
    }
} else {
    echo "No profile posts found.<br>\n";
}

// Check template filters
echo "<h2>Template Filters</h2>\n";
global $wp_filter;

$filters_to_check = ['template_include', 'single_template', 'template_redirect', 'get_template_part'];
foreach ($filters_to_check as $filter_name) {
    if (isset($wp_filter[$filter_name])) {
        $hooks = $wp_filter[$filter_name];
        echo "Filter '$filter_name' has " . count($hooks->callbacks) . " hooks:<br>\n";
        
        foreach ($hooks->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                    $method = $callback['function'][1];
                    echo "- Priority $priority: $class::$method<br>\n";
                } else {
                    echo "- Priority $priority: " . $callback['function'] . "<br>\n";
                }
            }
        }
        echo "<br>\n";
    } else {
        echo "Filter '$filter_name': No hooks found<br>\n";
    }
}

// Test direct template inclusion
echo "<h2>Direct Template Test</h2>\n";
$test_template = SCN_MEMBERSHIP_PATH . 'templates/member/single-member.php';
if (file_exists($test_template)) {
    echo "Testing direct inclusion of: $test_template<br>\n";
    echo "<div style='border: 2px solid red; padding: 10px; margin: 10px;'>";
    echo "<strong>Direct Template Inclusion Test:</strong><br>\n";
    
    // Capture output
    ob_start();
    include($test_template);
    $template_output = ob_get_clean();
    
    echo "Template output length: " . strlen($template_output) . " characters<br>\n";
    echo "First 200 chars: " . htmlspecialchars(substr($template_output, 0, 200)) . "...<br>\n";
    echo "</div>";
} else {
    echo "Template file does not exist: $test_template<br>\n";
}

echo "<h2>Test Instructions</h2>\n";
echo "1. Visit one of the member post links above<br>\n";
echo "2. Check if the debug content from single-member.php appears<br>\n";
echo "3. Check the WordPress debug.log for SCN template messages<br>\n";
echo "4. If still not working, the theme is likely overriding with higher priority<br>\n";

echo "<h2>Current Theme</h2>\n";
$theme = wp_get_theme();
echo "Active theme: " . $theme->get('Name') . " v" . $theme->get('Version') . "<br>\n";
echo "Template directory: " . get_template_directory() . "<br>\n";

// Check if theme has single templates that might conflict
$theme_single_templates = [
    'single-member.php',
    'single.php',
    'singular.php'
];

echo "Theme template check:<br>\n";
foreach ($theme_single_templates as $template_name) {
    $located = locate_template($template_name);
    echo "- $template_name: " . ($located ? $located : 'Not found in theme') . "<br>\n";
}
?>
