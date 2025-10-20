<?php
/**
 * Test script to verify single member template setup
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Single Member Template Test</h1>\n";

// Check if template file exists
$template_path = SCN_MEMBERSHIP_PATH . 'templates/profiles/single-member.php';
echo "<h2>Template File Check</h2>\n";
echo "Template path: " . $template_path . "<br>\n";
echo "Template exists: " . (file_exists($template_path) ? "YES" : "NO") . "<br>\n";

// Check post type registration
echo "<h2>Post Type Registration</h2>\n";
echo "profile post type exists: " . (post_type_exists('profile') ? "YES" : "NO") . "<br>\n";

// Check if there are any member posts
echo "<h2>Member Posts</h2>\n";
$members = get_posts([
    'post_type' => 'profile',
    'post_status' => 'publish',
    'numberposts' => 5
]);

if ($members) {
    echo "Found " . count($members) . " member posts:<br>\n";
    foreach ($members as $member) {
        echo "- <a href='" . get_permalink($member->ID) . "'>" . $member->post_title . "</a> (ID: " . $member->ID . ")<br>\n";
    }
} else {
    echo "No member posts found.<br>\n";
}

// Test template loading logic
echo "<h2>Template Loading Test</h2>\n";
if ($members) {
    $test_member = $members[0];
    echo "Testing template for: " . $test_member->post_title . "<br>\n";
    
    // Simulate the template loading logic
    global $post;
    $post = $test_member;
    setup_postdata($post);
    
    if (is_singular('profile')) {
        echo "✓ is_singular('profile') returns true<br>\n";
        $custom_template = SCN_MEMBERSHIP_PATH . 'templates/profiles/single-member.php';
        if (file_exists($custom_template)) {
            echo "✓ Template file exists and would be loaded<br>\n";
        } else {
            echo "✗ Template file does not exist<br>\n";
        }
    } else {
        echo "✗ is_singular('profile') returns false<br>\n";
    }
    
    wp_reset_postdata();
} else {
    echo "Cannot test template loading - no member posts available.<br>\n";
}

// Check for template conflicts
echo "<h2>Template Conflicts Check</h2>\n";
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
} else {
    echo "No template_include hooks found.<br>\n";
}

echo "<h2>Test Complete</h2>\n";
echo "If you see this page, the basic WordPress setup is working.<br>\n";
echo "Check the output above to verify template configuration.<br>\n";
?>
