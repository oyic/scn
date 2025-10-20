<?php
/**
 * Debug template loading for single member posts
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Template Loading Debug</h1>\n";

// Check current query
global $wp_query, $post;
echo "<h2>Current Query Info</h2>\n";
echo "is_singular: " . (is_singular() ? 'true' : 'false') . "<br>\n";
echo "is_singular('profile'): " . (is_singular('profile') ? 'true' : 'false') . "<br>\n";
echo "get_post_type(): " . get_post_type() . "<br>\n";

if ($post) {
    echo "Current post ID: " . $post->ID . "<br>\n";
    echo "Current post type: " . $post->post_type . "<br>\n";
    echo "Current post title: " . $post->post_title . "<br>\n";
}

// Check template hierarchy
echo "<h2>Template Hierarchy Check</h2>\n";
$template_hierarchy = [
    'single-profile.php',
    'single.php',
    'singular.php',
    'index.php'
];

foreach ($template_hierarchy as $template_name) {
    $located = locate_template($template_name);
    echo "Template '$template_name': " . ($located ? $located : 'Not found in theme') . "<br>\n";
}

// Check plugin template
echo "<h2>Plugin Template Check</h2>\n";
$plugin_template = SCN_MEMBERSHIP_PATH . 'templates/profiles/single-member.php';
echo "Plugin template path: " . $plugin_template . "<br>\n";
echo "Plugin template exists: " . (file_exists($plugin_template) ? 'YES' : 'NO') . "<br>\n";

// Check if we have any member posts to test with
echo "<h2>Member Posts Available</h2>\n";
$members = get_posts([
    'post_type' => 'profile',
    'post_status' => 'publish',
    'numberposts' => 3
]);

if ($members) {
    echo "Found " . count($members) . " member posts:<br>\n";
    foreach ($members as $member) {
        echo "- <a href='" . get_permalink($member->ID) . "' target='_blank'>" . $member->post_title . "</a> (ID: " . $member->ID . ")<br>\n";
    }
} else {
    echo "No member posts found.<br>\n";
}

// Check template filters
echo "<h2>Template Filters</h2>\n";
global $wp_filter;
if (isset($wp_filter['template_include'])) {
    $template_hooks = $wp_filter['template_include'];
    echo "Found " . count($template_hooks->callbacks) . " template_include hooks:<br>\n";
    
    foreach ($template_hooks->callbacks as $priority => $callbacks) {
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
}

// Check template_redirect actions
echo "<h2>Template Redirect Actions</h2>\n";
if (isset($wp_filter['template_redirect'])) {
    $redirect_hooks = $wp_filter['template_redirect'];
    echo "Found " . count($redirect_hooks->callbacks) . " template_redirect hooks:<br>\n";
    
    foreach ($redirect_hooks->callbacks as $priority => $callbacks) {
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
}

echo "<h2>Test Instructions</h2>\n";
echo "1. Visit one of the member post links above<br>\n";
echo "2. Check if the debug content from single-member.php appears<br>\n";
echo "3. Check the WordPress debug.log for SCN template messages<br>\n";
echo "4. If the template doesn't load, the theme is likely overriding it<br>\n";
?>
