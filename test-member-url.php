<?php
/**
 * Test script to debug the member URL structure
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Member URL Debug</h1>\n";

// Check if we can access the specific post
$post_slug = 'chris-alberto-phd';
echo "<h2>Post Lookup for: $post_slug</h2>\n";

// Try to find the post by slug
$post_by_slug = get_page_by_path($post_slug, OBJECT, 'member');
if ($post_by_slug) {
    echo "✓ Found post by slug: " . $post_by_slug->post_title . " (ID: " . $post_by_slug->ID . ")<br>\n";
    echo "Post type: " . $post_by_slug->post_type . "<br>\n";
    echo "Post status: " . $post_by_slug->post_status . "<br>\n";
    echo "Permalink: " . get_permalink($post_by_slug->ID) . "<br>\n";
} else {
    echo "✗ No post found with slug: $post_slug<br>\n";
}

// Try profile post type too
$scn_post_by_slug = get_page_by_path($post_slug, OBJECT, 'profile');
if ($scn_post_by_slug) {
    echo "✓ Found profile post by slug: " . $scn_post_by_slug->post_title . " (ID: " . $scn_post_by_slug->ID . ")<br>\n";
    echo "Post type: " . $scn_post_by_slug->post_type . "<br>\n";
    echo "Post status: " . $scn_post_by_slug->post_status . "<br>\n";
    echo "Permalink: " . get_permalink($scn_post_by_slug->ID) . "<br>\n";
} else {
    echo "✗ No profile post found with slug: $post_slug<br>\n";
}

// Check post types
echo "<h2>Available Post Types</h2>\n";
$post_types = get_post_types([], 'objects');
foreach ($post_types as $post_type_name => $post_type_obj) {
    if (strpos($post_type_name, 'member') !== false || strpos($post_type_name, 'scn') !== false) {
        echo "- $post_type_name: " . $post_type_obj->label . " (rewrite: " . (isset($post_type_obj->rewrite['slug']) ? $post_type_obj->rewrite['slug'] : 'default') . ")<br>\n";
    }
}

// Check rewrite rules
echo "<h2>Rewrite Rules</h2>\n";
$rules = get_option('rewrite_rules');
if (is_array($rules)) {
    foreach ($rules as $pattern => $replacement) {
        if (strpos($pattern, 'member') !== false) {
            echo "Pattern: $pattern -> $replacement<br>\n";
        }
    }
} else {
    echo "No rewrite rules found<br>\n";
}

// Test template loading
echo "<h2>Template Loading Test</h2>\n";
if ($post_by_slug) {
    global $post;
    $post = $post_by_slug;
    setup_postdata($post);
    
    echo "Testing template for post: " . $post->post_title . "<br>\n";
    echo "is_singular(): " . (is_singular() ? 'true' : 'false') . "<br>\n";
    echo "is_singular('member'): " . (is_singular('member') ? 'true' : 'false') . "<br>\n";
    echo "is_singular('profile'): " . (is_singular('profile') ? 'true' : 'false') . "<br>\n";
    
    wp_reset_postdata();
} else {
    echo "Cannot test template loading - no post found<br>\n";
}

echo "<h2>Template File Check</h2>\n";
$template_path = SCN_MEMBERSHIP_PATH . 'templates/profiles/single-member.php';
echo "Template path: " . $template_path . "<br>\n";
echo "Template exists: " . (file_exists($template_path) ? "YES" : "NO") . "<br>\n";

echo "<h2>Instructions</h2>\n";
echo "1. Visit <a href='http://scn-v1.local/member/chris-alberto-phd/' target='_blank'>http://scn-v1.local/member/chris-alberto-phd/</a><br>\n";
echo "2. Check if the debug content from single-member.php appears<br>\n";
echo "3. Check WordPress debug.log for SCN template messages<br>\n";
?>
