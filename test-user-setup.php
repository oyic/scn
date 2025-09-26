<?php
/**
 * Test User Setup - Run this in WordPress admin or via WP-CLI
 * 
 * This creates a test member user for authentication testing.
 * Run this code in WordPress admin or use WP-CLI commands below.
 */

// WP-CLI Commands to create test user:
/*
wp user create testmember test@scn-membership.com --role=subscriber --first_name="Test" --last_name="Member" --user_pass="TestMember123!"
wp post create --post_type=scn_profile --post_title="Test Member" --post_status=publish --post_author=2 --meta_input='{"scn_user_id":2,"scn_first_name":"Test","scn_last_name":"Member","scn_bio":"This is a test member profile for SCN Membership authentication testing.","scn_location":"Test City, TC","scn_credentials":"Test Member, SCN","scn_member_since":"2024-01-01 00:00:00"}'
*/

// Alternative: Add this code to functions.php temporarily to create the user
function create_scn_test_user() {
    // Check if test user already exists
    if (username_exists('testmember')) {
        return 'Test user already exists.';
    }
    
    // Create user
    $user_id = wp_create_user('testmember', 'TestMember123!', 'test@scn-membership.com');
    
    if (is_wp_error($user_id)) {
        return 'Error creating user: ' . $user_id->get_error_message();
    }
    
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
        return "Test user created successfully! User ID: $user_id, Profile ID: $profile_id";
    } else {
        return 'Error creating profile.';
    }
}

// Uncomment the line below to run the function
// echo create_scn_test_user();

?>
