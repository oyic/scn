<?php
/**
 * Check member 561 user status and create/update as needed
 */

require_once(__DIR__ . '/../../../wp-load.php');

echo "<h1>Member 561 User Check & Creation</h1>";

$member_id = 561;

// Get member post
$member = get_post($member_id);
if (!$member) {
    echo "<p style='color:red;'>❌ Member {$member_id} not found</p>";
    exit;
}

echo "<h2>Member Details</h2>";
echo "<p><strong>ID:</strong> {$member->ID}</p>";
echo "<p><strong>Title:</strong> {$member->post_title}</p>";
echo "<p><strong>Type:</strong> {$member->post_type}</p>";

// Check for existing user
$existing_user_id = get_post_meta($member_id, 'scn_user_id', true);
echo "<h2>User Status</h2>";

if ($existing_user_id) {
    echo "<p style='color:orange;'>⚠️ User ID in meta: {$existing_user_id}</p>";
    $user = get_user_by('id', $existing_user_id);
    if ($user) {
        echo "<p style='color:green;'>✅ User exists</p>";
        echo "<p><strong>Username:</strong> {$user->user_login}</p>";
        echo "<p><strong>Email:</strong> {$user->user_email}</p>";
        echo "<p><strong>Roles:</strong> " . implode(', ', $user->roles) . "</p>";
        
        // Check capabilities
        echo "<h3>Capabilities</h3>";
        echo "<ul>";
        echo "<li>Can edit courses: " . ($user->has_cap('edit_courses') ? '✅ Yes' : '❌ No') . "</li>";
        echo "<li>Can publish courses: " . ($user->has_cap('publish_courses') ? '✅ Yes' : '❌ No') . "</li>";
        echo "<li>Can delete courses: " . ($user->has_cap('delete_courses') ? '✅ Yes' : '❌ No') . "</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color:red;'>❌ User ID {$existing_user_id} not found (orphaned meta)</p>";
        echo "<p><a href='?clear_orphan=1'>Click to clear orphaned user ID</a></p>";
    }
} else {
    echo "<p style='color:red;'>❌ No user associated with this member</p>";
}

// Get ACF fields
echo "<h2>ACF Fields</h2>";
$basic_info = get_field('basic_info', $member_id);
$email = get_field('email', $member_id);

echo "<p><strong>First Name:</strong> " . ($basic_info['first_name'] ?? '<em>empty</em>') . "</p>";
echo "<p><strong>Last Name:</strong> " . ($basic_info['last_name'] ?? '<em>empty</em>') . "</p>";
echo "<p><strong>Email:</strong> " . ($email ?: '<em>empty</em>') . "</p>";

// Manual actions
echo "<h2>Actions</h2>";

if (isset($_GET['clear_orphan'])) {
    delete_post_meta($member_id, 'scn_user_id');
    echo "<p style='color:green;'>✅ Cleared orphaned user ID</p>";
    echo "<p><a href='?'>Refresh</a></p>";
}

if (isset($_GET['create_user'])) {
    $first_name = $basic_info['first_name'] ?? '';
    $last_name = $basic_info['last_name'] ?? '';
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        echo "<p style='color:red;'>❌ Cannot create user - missing required fields</p>";
    } else if (email_exists($email)) {
        echo "<p style='color:red;'>❌ Email already exists</p>";
    } else {
        $username = strtolower($first_name . '.' . $last_name);
        $username = sanitize_user($username);
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        $password = wp_generate_password(12, false);
        $user_id = wp_create_user($username, $password, $email);
        
        if (!is_wp_error($user_id)) {
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
            update_post_meta($member_id, 'scn_user_id', $user_id);
            
            // Set role to contributor (can create/edit own posts)
            $user = new WP_User($user_id);
            $user->set_role('contributor');
            
            // Add course capabilities
            $user->add_cap('edit_courses');
            $user->add_cap('publish_courses');
            $user->add_cap('delete_courses');
            
            echo "<p style='color:green;'>✅ User created successfully!</p>";
            echo "<p><strong>User ID:</strong> {$user_id}</p>";
            echo "<p><strong>Username:</strong> {$username}</p>";
            echo "<p><strong>Password:</strong> {$password}</p>";
            echo "<p><strong>Role:</strong> contributor (with course capabilities)</p>";
            echo "<p><a href='?'>Refresh</a></p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to create user: " . $user_id->get_error_message() . "</p>";
        }
    }
}

if (!$existing_user_id || !get_user_by('id', $existing_user_id)) {
    echo "<p><a href='?create_user=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>Create User for This Member</a></p>";
}

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; }
ul { margin-left: 20px; }
</style>";

