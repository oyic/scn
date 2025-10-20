<?php
/**
 * Debug script to check member creation and user creation
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if we're in admin or can run this test
if (!current_user_can('manage_options') && !defined('WP_CLI')) {
    wp_die('You do not have permission to run this test.');
}

echo "<h1>SCN Membership - Member Creation Debug</h1>";

// Check if member post type exists
echo "<h2>Post Type Check</h2>";
if (post_type_exists('member')) {
    echo "<div class='success'>✅ 'member' post type exists</div>";
    
    $member_posts = get_posts([
        'post_type' => 'member',
        'posts_per_page' => 5,
        'post_status' => 'any'
    ]);
    
    echo "<h3>Recent Member Posts:</h3>";
    if (empty($member_posts)) {
        echo "<div class='info'>No member posts found</div>";
    } else {
        echo "<ul>";
        foreach ($member_posts as $post) {
            $user_id = get_post_meta($post->ID, 'scn_user_id', true);
            echo "<li>";
            echo "<strong>ID:</strong> {$post->ID} | ";
            echo "<strong>Title:</strong> {$post->post_title} | ";
            echo "<strong>Status:</strong> {$post->post_status} | ";
            echo "<strong>User ID:</strong> " . ($user_id ? $user_id : 'None');
            echo "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<div class='error'>❌ 'member' post type does NOT exist</div>";
}

// Check ACF field groups for member
echo "<h2>ACF Field Groups for Member</h2>";
if (function_exists('acf_get_field_groups')) {
    $field_groups = acf_get_field_groups(['post_type' => 'member']);
    
    if (empty($field_groups)) {
        echo "<div class='error'>❌ No ACF field groups found for 'member' post type</div>";
    } else {
        echo "<div class='success'>✅ Found " . count($field_groups) . " field group(s) for member post type</div>";
        foreach ($field_groups as $group) {
            echo "<div class='info'>📋 Field Group: {$group['title']} (ID: {$group['ID']})</div>";
        }
    }
} else {
    echo "<div class='error'>❌ ACF is not active</div>";
}

// Check if the save_post_member hook is registered
echo "<h2>Hook Registration Check</h2>";
global $wp_filter;

if (isset($wp_filter['save_post_member'])) {
    echo "<div class='success'>✅ 'save_post_member' hook is registered</div>";
    
    $callbacks = $wp_filter['save_post_member']->callbacks;
    foreach ($callbacks as $priority => $hooks) {
        foreach ($hooks as $hook) {
            if (is_array($hook['function']) && is_object($hook['function'][0])) {
                $class = get_class($hook['function'][0]);
                $method = $hook['function'][1];
                echo "<div class='info'>🔗 Priority {$priority}: {$class}::{$method}</div>";
            } else {
                echo "<div class='info'>🔗 Priority {$priority}: " . print_r($hook['function'], true) . "</div>";
            }
        }
    }
} else {
    echo "<div class='error'>❌ 'save_post_member' hook is NOT registered</div>";
}

// Test creating a member post manually
echo "<h2>Manual Member Creation Test</h2>";

if (isset($_GET['test_create']) && $_GET['test_create'] === '1') {
    echo "<div class='info'>Creating test member...</div>";
    
    // Create a test member post
    $post_data = [
        'post_title' => 'Test Member ' . time(),
        'post_type' => 'member',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id) {
        echo "<div class='success'>✅ Test member created with ID: {$post_id}</div>";
        
        // Add some test ACF data
        if (function_exists('update_field')) {
            update_field('basic_info', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test' . time() . '@example.com'
            ], $post_id);
            echo "<div class='success'>✅ Test ACF data added</div>";
        }
        
        // Check if user was created
        $user_id = get_post_meta($post_id, 'scn_user_id', true);
        if ($user_id) {
            echo "<div class='success'>✅ User was created with ID: {$user_id}</div>";
            
            $user = get_user_by('id', $user_id);
            if ($user) {
                echo "<div class='info'>👤 User details: {$user->user_login} ({$user->user_email})</div>";
            }
        } else {
            echo "<div class='error'>❌ No user was created for this member</div>";
        }
        
        // Clean up
        wp_delete_post($post_id, true);
        if ($user_id) {
            wp_delete_user($user_id);
        }
        echo "<div class='info'>🧹 Test data cleaned up</div>";
        
    } else {
        echo "<div class='error'>❌ Failed to create test member</div>";
    }
} else {
    echo "<div class='info'>💡 <a href='?test_create=1'>Click here to test member creation</a></div>";
}

// Check WordPress users
echo "<h2>Recent WordPress Users</h2>";
$users = get_users([
    'number' => 5,
    'orderby' => 'registered',
    'order' => 'DESC'
]);

if (empty($users)) {
    echo "<div class='info'>No users found</div>";
} else {
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li>";
        echo "<strong>ID:</strong> {$user->ID} | ";
        echo "<strong>Login:</strong> {$user->user_login} | ";
        echo "<strong>Email:</strong> {$user->user_email} | ";
        echo "<strong>Role:</strong> " . implode(', ', $user->roles);
        echo "</li>";
    }
    echo "</ul>";
}

// Add some basic styling
echo "<style>
.success { color: green; background: #f0f8f0; padding: 10px; border: 1px solid green; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border: 1px solid red; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border: 1px solid blue; margin: 10px 0; }
</style>";
?>
