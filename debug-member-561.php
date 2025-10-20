<?php
/**
 * Debug script to check member 561 and test user creation
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

echo "<h1>Debug Member 561 - User Creation</h1>";

$post_id = 561;

// Check if post exists
$post = get_post($post_id);
if (!$post) {
    echo "<p style='color:red;'>❌ Post {$post_id} does not exist</p>";
    exit;
}

echo "<h2>Post Details</h2>";
echo "<p><strong>ID:</strong> {$post->ID}</p>";
echo "<p><strong>Type:</strong> {$post->post_type}</p>";
echo "<p><strong>Title:</strong> {$post->post_title}</p>";
echo "<p><strong>Status:</strong> {$post->post_status}</p>";

// Check existing user
echo "<h2>Existing User Check</h2>";
$existing_user_id = get_post_meta($post_id, 'scn_user_id', true);
if ($existing_user_id) {
    echo "<p style='color:orange;'>⚠️ User already exists: {$existing_user_id}</p>";
    $user = get_user_by('id', $existing_user_id);
    if ($user) {
        echo "<p><strong>Username:</strong> {$user->user_login}</p>";
        echo "<p><strong>Email:</strong> {$user->user_email}</p>";
    } else {
        echo "<p style='color:red;'>❌ User ID {$existing_user_id} not found (orphaned meta)</p>";
    }
} else {
    echo "<p style='color:green;'>✅ No user exists yet</p>";
}

// Get ACF fields
echo "<h2>ACF Fields</h2>";
if (function_exists('get_field')) {
    // Get basic_info group
    $basic_info = get_field('basic_info', $post_id);
    
    echo "<h3>Basic Info Group</h3>";
    if ($basic_info) {
        echo "<pre>";
        print_r($basic_info);
        echo "</pre>";
        
        $first_name = $basic_info['first_name'] ?? '';
        $last_name = $basic_info['last_name'] ?? '';
        
        echo "<p><strong>First Name:</strong> " . ($first_name ?: '<em>empty</em>') . "</p>";
        echo "<p><strong>Last Name:</strong> " . ($last_name ?: '<em>empty</em>') . "</p>";
    } else {
        echo "<p style='color:red;'>❌ No basic_info field found</p>";
    }
    
    // Get email separately
    echo "<h3>Email Field (Separate)</h3>";
    $email = get_field('email', $post_id);
    echo "<p><strong>Email:</strong> " . ($email ?: '<em>empty</em>') . "</p>";
    
    if (empty($email)) {
        // Try alternate field names
        $email = get_field('member_email', $post_id);
        echo "<p><strong>Member Email:</strong> " . ($email ?: '<em>empty</em>') . "</p>";
    }
    
    // List all ACF fields for this post
    echo "<h3>All ACF Fields for this Post</h3>";
    $fields = get_fields($post_id);
    if ($fields) {
        echo "<pre>";
        print_r($fields);
        echo "</pre>";
    } else {
        echo "<p><em>No ACF fields found</em></p>";
    }
    
    // Validation checks
    echo "<h2>Validation Checks</h2>";
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        echo "<p style='color:red;'>❌ Missing required fields</p>";
        echo "<ul>";
        if (empty($first_name)) echo "<li>First name is empty</li>";
        if (empty($last_name)) echo "<li>Last name is empty</li>";
        if (empty($email)) echo "<li>Email is empty</li>";
        echo "</ul>";
    } else {
        echo "<p style='color:green;'>✅ All required fields present</p>";
    }
    
    if ($email && !is_email($email)) {
        echo "<p style='color:red;'>❌ Invalid email format: {$email}</p>";
    } else if ($email) {
        echo "<p style='color:green;'>✅ Valid email format</p>";
    }
    
    if ($email && email_exists($email)) {
        $existing = get_user_by('email', $email);
        echo "<p style='color:orange;'>⚠️ Email already exists for user: {$existing->user_login} (ID: {$existing->ID})</p>";
    } else if ($email) {
        echo "<p style='color:green;'>✅ Email available</p>";
    }
    
} else {
    echo "<p style='color:red;'>❌ ACF get_field() function not available</p>";
}

// Check if acf/save_post hook is registered
echo "<h2>Hook Check</h2>";
global $wp_filter;
if (isset($wp_filter['acf/save_post'])) {
    echo "<p style='color:green;'>✅ 'acf/save_post' hook is registered</p>";
    $callbacks = $wp_filter['acf/save_post']->callbacks;
    foreach ($callbacks as $priority => $hooks) {
        foreach ($hooks as $hook_id => $hook) {
            if (is_array($hook['function']) && isset($hook['function'][0])) {
                if (is_object($hook['function'][0])) {
                    $class = get_class($hook['function'][0]);
                    $method = $hook['function'][1];
                    echo "<p>Priority {$priority}: {$class}::{$method}</p>";
                }
            }
        }
    }
} else {
    echo "<p style='color:red;'>❌ 'acf/save_post' hook is NOT registered</p>";
}

// Manual trigger test
echo "<h2>Manual User Creation Test</h2>";
echo "<form method='post'>";
echo "<button type='submit' name='create_user' value='1'>Create User Manually</button>";
echo "</form>";

if (isset($_POST['create_user'])) {
    echo "<h3>Creating User...</h3>";
    
    // Get AdminService instance and call the method
    $admin_service = new \SCN\Membership\Admin\AdminService();
    $admin_service->createUserForMemberAfterAcf($post_id);
    
    echo "<p>Function called. Check results above.</p>";
    echo "<p><a href='?'>Reload to see results</a></p>";
}

