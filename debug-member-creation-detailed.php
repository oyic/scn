<?php
/**
 * Detailed debug script to check member creation and user creation
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if we're in admin or can run this test
if (!current_user_can('manage_options') && !defined('WP_CLI')) {
    wp_die('You do not have permission to run this test.');
}

echo "<h1>SCN Membership - Detailed Member Creation Debug</h1>";

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
            }
        }
    }
} else {
    echo "<div class='error'>❌ 'save_post_member' hook is NOT registered</div>";
}

// Check ACF field groups for member
echo "<h2>ACF Field Groups for Member</h2>";
if (function_exists('acf_get_field_groups')) {
    $field_groups = acf_get_field_groups(['post_type' => 'member']);
    
    if (empty($field_groups)) {
        echo "<div class='error'>❌ No ACF field groups found for 'member' post type</div>";
        echo "<div class='info'>💡 You need to create an ACF field group for the 'member' post type with a 'basic_info' field containing first_name, last_name, and email sub-fields</div>";
    } else {
        echo "<div class='success'>✅ Found " . count($field_groups) . " field group(s) for member post type</div>";
        foreach ($field_groups as $group) {
            echo "<div class='info'>📋 Field Group: {$group['title']} (ID: {$group['ID']})</div>";
            
            // Get fields for this group
            $fields = acf_get_fields($group['ID']);
            if ($fields) {
                echo "<div class='info'>Fields in this group:</div>";
                echo "<ul>";
                foreach ($fields as $field) {
                    echo "<li><strong>{$field['name']}</strong> ({$field['type']})</li>";
                    if ($field['type'] === 'group' && isset($field['sub_fields'])) {
                        echo "<ul>";
                        foreach ($field['sub_fields'] as $sub_field) {
                            echo "<li>└─ {$sub_field['name']} ({$sub_field['type']})</li>";
                        }
                        echo "</ul>";
                    }
                }
                echo "</ul>";
            }
        }
    }
} else {
    echo "<div class='error'>❌ ACF is not active</div>";
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
            $basic_info_data = [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test' . time() . '@example.com'
            ];
            
            $result = update_field('basic_info', $basic_info_data, $post_id);
            echo "<div class='info'>ACF update result: " . ($result ? 'Success' : 'Failed') . "</div>";
            
            // Verify the data was saved
            $saved_data = get_field('basic_info', $post_id);
            echo "<div class='info'>Saved ACF data: " . print_r($saved_data, true) . "</div>";
        }
        
        // Check if user was created
        $user_id = get_post_meta($post_id, 'scn_user_id', true);
        if ($user_id) {
            echo "<div class='success'>✅ User was created with ID: {$user_id}</div>";
            
            $user = get_user_by('id', $user_id);
            if ($user) {
                echo "<div class='info'>👤 User details: {$user->user_login} ({$user->user_email})</div>";
                echo "<div class='info'>🔑 User role: " . implode(', ', $user->roles) . "</div>";
            }
        } else {
            echo "<div class='error'>❌ No user was created for this member</div>";
            echo "<div class='info'>💡 Check the WordPress debug log for error messages</div>";
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

// Check WordPress debug log
echo "<h2>Debug Log Check</h2>";
$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    $log_content = file_get_contents($debug_log);
    $scn_logs = array_filter(explode("\n", $log_content), function($line) {
        return strpos($line, 'SCN Membership') !== false;
    });
    
    if (!empty($scn_logs)) {
        echo "<div class='info'>Recent SCN Membership log entries:</div>";
        echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;'>";
        echo implode("\n", array_slice($scn_logs, -10));
        echo "</pre>";
    } else {
        echo "<div class='info'>No SCN Membership log entries found</div>";
    }
} else {
    echo "<div class='info'>Debug log file not found at: {$debug_log}</div>";
}

// Add some basic styling
echo "<style>
.success { color: green; background: #f0f8f0; padding: 10px; border: 1px solid green; margin: 10px 0; }
.error { color: red; background: #f8f0f0; padding: 10px; border: 1px solid red; margin: 10px 0; }
.info { color: blue; background: #f0f0f8; padding: 10px; border: 1px solid blue; margin: 10px 0; }
</style>";
?>
