<?php
/**
 * Debug script to check why editing tools are not showing up
 * Run this by visiting: /wp-content/plugins/scn-membership/debug-editing-tools.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in
if (!is_user_logged_in()) {
    die('Please log in first to test this script.');
}

$current_user_id = get_current_user_id();
$current_user = wp_get_current_user();

echo "<h2>Debug: Editing Tools Issue</h2>";
echo "<p><strong>Current User ID:</strong> " . $current_user_id . "</p>";
echo "<p><strong>Current User Login:</strong> " . $current_user->user_login . "</p>";
echo "<p><strong>Current User Email:</strong> " . $current_user->user_email . "</p>";

// Get the current member profile
$member_posts = get_posts(array(
    'post_type' => 'member',
    'author' => $current_user_id,
    'posts_per_page' => 1,
    'post_status' => 'publish'
));

if (empty($member_posts)) {
    echo "<p style='color: red;'><strong>ERROR:</strong> No member profile found for current user!</p>";
    echo "<p>This means the member profile post doesn't exist or isn't authored by the current user.</p>";
    
    // Let's check if there are any member posts at all
    $all_member_posts = get_posts(array(
        'post_type' => 'member',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    echo "<h3>All Member Posts:</h3>";
    foreach ($all_member_posts as $post) {
        $author = get_userdata($post->post_author);
        echo "<p>Post ID: {$post->ID}, Title: {$post->post_title}, Author: {$author->user_login} (ID: {$post->post_author})</p>";
    }
} else {
    $member_post = $member_posts[0];
    echo "<p style='color: green;'><strong>SUCCESS:</strong> Found member profile!</p>";
    echo "<p><strong>Member Post ID:</strong> " . $member_post->ID . "</p>";
    echo "<p><strong>Member Post Title:</strong> " . $member_post->post_title . "</p>";
    echo "<p><strong>Member Post Author:</strong> " . $member_post->post_author . "</p>";
    
    // Check post meta and ACF fields
    $scn_user_id_meta = get_post_meta($member_post->ID, 'scn_user_id', true);
    $member_user_id = get_field('member_user_id', $member_post->ID);
    $user_id_field = get_field('user_id', $member_post->ID);
    
    echo "<h3>Post Meta and ACF Field Values:</h3>";
    echo "<p><strong>scn_user_id meta:</strong> " . ($scn_user_id_meta ? $scn_user_id_meta : 'NOT SET') . "</p>";
    echo "<p><strong>member_user_id field:</strong> " . ($member_user_id ? $member_user_id : 'NOT SET') . "</p>";
    echo "<p><strong>user_id field:</strong> " . ($user_id_field ? $user_id_field : 'NOT SET') . "</p>";
    
    // Test the logic from the template (updated version)
    $is_own_profile = false;
    $member_user_id = get_post_meta($member_post->ID, 'scn_user_id', true);
    
    if (!$member_user_id) {
        $member_user_id = get_field('member_user_id', $member_post->ID);
    }
    
    if (!$member_user_id) {
        $member_user_id = get_field('user_id', $member_post->ID);
    }
    
    if (!$member_user_id) {
        $member_user_id = $member_post->post_author;
    }
    
    echo "<h3>Template Logic Test:</h3>";
    echo "<p><strong>Final member_user_id:</strong> " . $member_user_id . "</p>";
    echo "<p><strong>Current user ID:</strong> " . $current_user_id . "</p>";
    echo "<p><strong>Match check:</strong> " . ($current_user_id == $member_user_id ? 'MATCH' : 'NO MATCH') . "</p>";
    
    if ($current_user_id == $member_user_id) {
        $is_own_profile = true;
        echo "<p style='color: green;'><strong>is_own_profile would be: TRUE</strong></p>";
    } else {
        // Fallback check
        if ($current_user_id == $member_post->post_author) {
            $is_own_profile = true;
            echo "<p style='color: orange;'><strong>is_own_profile would be: TRUE (fallback to post author)</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>is_own_profile would be: FALSE</strong></p>";
        }
    }
}

echo "<hr>";
echo "<p><a href='" . home_url() . "'>← Back to Site</a></p>";
?>
