<?php
/**
 * Create Profile for ezekiel User
 * This will create a profile with credentials for the ezekiel WordPress user
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>👤 Creating Profile for ezekiel User</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Get the ezekiel user
$user = get_user_by('login', 'ezekiel');

if (!$user) {
    echo "<div class='error'>";
    echo "<h2>❌ User 'ezekiel' not found</h2>";
    echo "</div>";
    exit;
}

echo "<div class='success'>";
echo "<h2>✅ Found ezekiel user</h2>";
echo "<p>User ID: " . $user->ID . "</p>";
echo "<p>Username: " . $user->user_login . "</p>";
echo "<p>Email: " . $user->user_email . "</p>";
echo "<p>Display Name: " . $user->display_name . "</p>";
echo "</div>";

// Check if ezekiel already has a profile
$existing_profile = get_posts([
    'post_type' => 'profile',
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

if (!empty($existing_profile)) {
    echo "<div class='info'>";
    echo "<h2>ℹ️ ezekiel already has a profile</h2>";
    echo "<p>Profile ID: " . $existing_profile[0]->ID . "</p>";
    echo "<p>Profile Title: " . $existing_profile[0]->post_title . "</p>";
    echo "</div>";
    
    $profile = $existing_profile[0];
} else {
    // Create a profile for ezekiel
    echo "<div class='info'>";
    echo "<h2>🔨 Creating profile for ezekiel</h2>";
    echo "</div>";
    
    $profile_data = [
        'post_title' => $user->display_name ?: $user->user_login,
        'post_type' => 'profile',
        'post_status' => 'publish',
        'post_author' => $user->ID,
    ];
    
    $profile_id = wp_insert_post($profile_data);
    
    if ($profile_id) {
        // Set profile meta
        update_post_meta($profile_id, 'scn_user_id', $user->ID);
        update_post_meta($profile_id, 'member_since', current_time('mysql'));
        
        // Set basic profile info from user data
        if ($user->first_name) {
            update_post_meta($profile_id, 'scn_first_name', $user->first_name);
        }
        if ($user->last_name) {
            update_post_meta($profile_id, 'scn_last_name', $user->last_name);
        }
        
        echo "<div class='success'>";
        echo "<h2>✅ Profile created for ezekiel</h2>";
        echo "<p>Profile ID: $profile_id</p>";
        echo "<p>Profile Title: " . $profile_data['post_title'] . "</p>";
        echo "</div>";
        
        $profile = get_post($profile_id);
    } else {
        echo "<div class='error'>";
        echo "<h2>❌ Failed to create profile for ezekiel</h2>";
        echo "</div>";
        exit;
    }
}

// Add login credentials to the profile
echo "<div class='info'>";
echo "<h2>🔑 Adding login credentials to profile</h2>";
echo "</div>";

// Generate username and password
$username = 'ezekiel';
$password = 'Ezekiel123!';

// Store credentials
update_post_meta($profile->ID, 'scn_username', $username);
update_post_meta($profile->ID, 'scn_password', wp_hash_password($password));

echo "<div class='success'>";
echo "<h2>🎉 ezekiel Profile Setup Complete!</h2>";
echo "<p><strong>Profile Information:</strong></p>";
echo "<ul>";
echo "<li><strong>Profile ID:</strong> " . $profile->ID . "</li>";
echo "<li><strong>Profile Title:</strong> " . $profile->post_title . "</li>";
echo "<li><strong>Linked User:</strong> " . $user->user_login . " (ID: " . $user->ID . ")</li>";
echo "</ul>";
echo "<p><strong>Login Credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Username:</strong> $username</li>";
echo "<li><strong>Password:</strong> $password</li>";
echo "</ul>";
echo "</div>";

// Show all available login options
echo "<div class='info'>";
echo "<h2>🧪 All Available Login Credentials</h2>";
echo "<p>You can now log in with any of these accounts:</p>";
echo "<ul>";
echo "<li><strong>ezekiel</strong> / Ezekiel123!</li>";
echo "<li><strong>testmemberprofile</strong> / Profile79!</li>";
echo "<li><strong>testprofile</strong> / Profile18!</li>";
echo "<li><strong>dd</strong> / Profile12!</li>";
echo "</ul>";
echo "</div>";

echo "<div class='success'>";
echo "<h2>🚀 Ready to Test!</h2>";
echo "<p>Go to <a href='/member-login/' target='_blank'>/member-login/</a> and try logging in with:</p>";
echo "<p><strong>Username:</strong> ezekiel</p>";
echo "<p><strong>Password:</strong> Ezekiel123!</p>";
echo "</div>";

echo "<hr>";
echo "<p><em>Profile creation completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
