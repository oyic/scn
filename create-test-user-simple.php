<?php
/**
 * Simple Test User Creation
 * Run this in your browser to create a test user
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>SCN Test User Creation</h1>";

// Check if user already exists
if (username_exists('testmember')) {
    echo "<p style='color: orange;'>⚠ Test user 'testmember' already exists.</p>";
    
    $user = get_user_by('login', 'testmember');
    if ($user) {
        echo "<p>User ID: " . $user->ID . "</p>";
        echo "<p>Email: " . $user->user_email . "</p>";
        
        // Check if user has a profile
        $profile_posts = get_posts([
            'post_type' => 'scn_profile',
            'meta_query' => [
                [
                    'key' => 'scn_user_id',
                    'value' => $user->ID,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (!empty($profile_posts)) {
            echo "<p style='color: green;'>✓ User has a profile (ID: " . $profile_posts[0]->ID . ")</p>";
        } else {
            echo "<p style='color: red;'>✗ User does not have a profile</p>";
            
            // Create profile
            $profile_id = wp_insert_post([
                'post_title' => 'Test Member',
                'post_type' => 'scn_profile',
                'post_status' => 'publish',
                'post_author' => $user->ID,
                'meta_input' => [
                    'scn_user_id' => $user->ID,
                    'scn_first_name' => 'Test',
                    'scn_last_name' => 'Member',
                    'scn_bio' => 'This is a test member profile for SCN Membership authentication testing.',
                    'scn_location' => 'Test City, TC',
                    'scn_credentials' => 'Test Member, SCN',
                    'scn_member_since' => current_time('mysql')
                ]
            ]);
            
            if ($profile_id) {
                echo "<p style='color: green;'>✓ Profile created successfully (ID: $profile_id)</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to create profile</p>";
            }
        }
    }
} else {
    echo "<p>Creating test user...</p>";
    
    // Create user
    $user_id = wp_create_user('testmember', 'TestMember123!', 'test@scn-membership.com');
    
    if (is_wp_error($user_id)) {
        echo "<p style='color: red;'>✗ Error creating user: " . $user_id->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>✓ User created successfully (ID: $user_id)</p>";
        
        // Update user meta
        update_user_meta($user_id, 'first_name', 'Test');
        update_user_meta($user_id, 'last_name', 'Member');
        
        // Create profile
        $profile_id = wp_insert_post([
            'post_title' => 'Test Member',
            'post_type' => 'scn_profile',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'meta_input' => [
                'scn_user_id' => $user_id,
                'scn_first_name' => 'Test',
                'scn_last_name' => 'Member',
                'scn_bio' => 'This is a test member profile for SCN Membership authentication testing.',
                'scn_location' => 'Test City, TC',
                'scn_credentials' => 'Test Member, SCN',
                'scn_member_since' => current_time('mysql')
            ]
        ]);
        
        if ($profile_id) {
            echo "<p style='color: green;'>✓ Profile created successfully (ID: $profile_id)</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create profile</p>";
        }
    }
}

echo "<hr>";
echo "<h2>Test User Information</h2>";
echo "<p><strong>Username:</strong> testmember</p>";
echo "<p><strong>Email:</strong> test@scn-membership.com</p>";
echo "<p><strong>Password:</strong> TestMember123!</p>";

echo "<hr>";
echo "<h2>Test URLs</h2>";
echo "<p><a href='" . home_url('/member-login/') . "' target='_blank'>Member Login</a></p>";
echo "<p><a href='" . home_url('/member-dashboard/') . "' target='_blank'>Member Dashboard</a></p>";
echo "<p><a href='" . home_url('/test-auth/') . "' target='_blank'>Test Authentication Page</a></p>";

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Go to the <a href='" . home_url('/member-login/') . "' target='_blank'>Member Login</a> page</li>";
echo "<li>Login with the test credentials above</li>";
echo "<li>You should be redirected to the dashboard</li>";
echo "</ol>";

echo "<hr>";
echo "<p><em>Script completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
