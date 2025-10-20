<?php
/**
 * Test User Creation Script
 * Run this script to create a test member user for authentication testing
 */

// Include WordPress
require_once('../../../wp-config.php');
require_once('../../../wp-includes/wp-db.php');
require_once('../../../wp-includes/pluggable.php');

// Test user data
$test_user_data = [
    'user_login' => 'testmember',
    'user_email' => 'test@scn-membership.com',
    'user_pass' => 'TestMember123!',
    'first_name' => 'Test',
    'last_name' => 'Member',
    'display_name' => 'Test Member',
    'role' => 'subscriber'
];

echo "Creating test member user...\n";

// Check if user already exists
if (username_exists($test_user_data['user_login'])) {
    echo "User '{$test_user_data['user_login']}' already exists.\n";
    $user = get_user_by('login', $test_user_data['user_login']);
    $user_id = $user->ID;
} else {
    // Create the user
    $user_id = wp_create_user(
        $test_user_data['user_login'],
        $test_user_data['user_pass'],
        $test_user_data['user_email']
    );
    
    if (is_wp_error($user_id)) {
        echo "Error creating user: " . $user_id->get_error_message() . "\n";
        exit;
    }
    
    echo "User created successfully with ID: $user_id\n";
}

// Update user meta
update_user_meta($user_id, 'first_name', $test_user_data['first_name']);
update_user_meta($user_id, 'last_name', $test_user_data['last_name']);
update_user_meta($user_id, 'display_name', $test_user_data['display_name']);

// Create profile post
$profile_posts = get_posts([
    'post_type' => 'profile',
    'meta_query' => [
        [
            'key' => 'scn_user_id',
            'value' => $user_id,
            'compare' => '='
        ]
    ],
    'posts_per_page' => 1,
    'post_status' => 'any'
]);

if (empty($profile_posts)) {
    $profile_data = [
        'post_title' => $test_user_data['first_name'] . ' ' . $test_user_data['last_name'],
        'post_type' => 'profile',
        'post_status' => 'publish',
        'post_author' => $user_id,
        'post_content' => 'This is a test member profile created for authentication testing.',
    ];
    
    $profile_id = wp_insert_post($profile_data);
    
    if ($profile_id) {
        // Set profile meta
        update_post_meta($profile_id, 'scn_user_id', $user_id);
        update_post_meta($profile_id, 'scn_first_name', $test_user_data['first_name']);
        update_post_meta($profile_id, 'scn_last_name', $test_user_data['last_name']);
        update_post_meta($profile_id, 'scn_bio', 'This is a test member profile for SCN Membership authentication testing.');
        update_post_meta($profile_id, 'scn_location', 'Test City, TC');
        update_post_meta($profile_id, 'scn_credentials', 'Test Member, SCN');
        update_post_meta($profile_id, 'member_since', current_time('mysql'));
        
        // Add some test topics
        $topics = ['Technology', 'Testing', 'Authentication'];
        update_post_meta($profile_id, 'scn_topics', $topics);
        
        echo "Profile created successfully with ID: $profile_id\n";
        
        // Create some test sessions
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'scn_sessions';
        
        // Check if sessions table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") == $sessions_table) {
            $test_sessions = [
                [
                    'profile_id' => $profile_id,
                    'event_id' => 1, // Assuming event with ID 1 exists
                    'course_id' => 1, // Assuming course with ID 1 exists
                    'session_title' => 'Test Session 1',
                    'session_datetime' => date('Y-m-d H:i:s', strtotime('+1 week')),
                    'room' => 'Room A',
                    'status' => 'approved',
                    'created_at' => current_time('mysql')
                ],
                [
                    'profile_id' => $profile_id,
                    'event_id' => 1,
                    'course_id' => 1,
                    'session_title' => 'Test Session 2',
                    'session_datetime' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
                    'room' => 'Room B',
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ]
            ];
            
            foreach ($test_sessions as $session) {
                $wpdb->insert($sessions_table, $session);
                if ($wpdb->insert_id) {
                    echo "Test session '{$session['session_title']}' created successfully\n";
                }
            }
        }
        
    } else {
        echo "Error creating profile.\n";
    }
} else {
    echo "Profile already exists for user.\n";
}

echo "\n=== Test User Created Successfully ===\n";
echo "Username: {$test_user_data['user_login']}\n";
echo "Email: {$test_user_data['user_email']}\n";
echo "Password: {$test_user_data['user_pass']}\n";
echo "User ID: $user_id\n";
echo "Profile ID: " . (isset($profile_id) ? $profile_id : 'Already exists') . "\n";
echo "\nYou can now login at: " . home_url('/member-login/') . "\n";
echo "Dashboard will be available at: " . home_url('/member-dashboard/') . "\n";
echo "\n=== Login Instructions ===\n";
echo "1. Go to: " . home_url('/member-login/') . "\n";
echo "2. Username: {$test_user_data['user_login']}\n";
echo "3. Password: {$test_user_data['user_pass']}\n";
echo "4. After login, you'll be redirected to the dashboard\n";
?>
