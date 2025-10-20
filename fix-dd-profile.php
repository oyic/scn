<?php
/**
 * Fix dd Profile
 * This will add the missing meta data to the dd profile to make it work like ezekiel
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔧 Fix dd Profile</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Get the dd profile
$dd_profile = get_posts([
    'post_type' => 'profile',
    'meta_query' => [
        [
            'key' => 'scn_username',
            'value' => 'dd',
            'compare' => '='
        ]
    ],
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (empty($dd_profile)) {
    echo "<div class='error'>";
    echo "<h2>❌ dd profile not found</h2>";
    echo "</div>";
    exit;
}

$profile = $dd_profile[0];

echo "<div class='success'>";
echo "<h2>✅ Found dd profile</h2>";
echo "<p>Profile ID: " . $profile->ID . "</p>";
echo "<p>Title: " . $profile->post_title . "</p>";
echo "</div>";

// Add missing meta data
echo "<div class='info'>";
echo "<h2>🔧 Adding missing meta data</h2>";
echo "</div>";

// Add member_since if missing
$member_since = get_post_meta($profile->ID, 'member_since', true);
if (empty($member_since)) {
    update_post_meta($profile->ID, 'member_since', $profile->post_date);
    echo "<div class='success'>";
    echo "<p>✅ Added member_since: " . $profile->post_date . "</p>";
    echo "</div>";
} else {
    echo "<div class='info'>";
    echo "<p>ℹ️ member_since already exists: " . $member_since . "</p>";
    echo "</div>";
}

// Check if we should link to a WordPress user
$user_id = get_post_meta($profile->ID, 'scn_user_id', true);
if (empty($user_id)) {
    // Check if there's a WordPress user with username 'dd'
    $user = get_user_by('login', 'dd');
    if ($user) {
        update_post_meta($profile->ID, 'scn_user_id', $user->ID);
        echo "<div class='success'>";
        echo "<p>✅ Linked to WordPress user 'dd' (ID: " . $user->ID . ")</p>";
        echo "</div>";
    } else {
        // Create a WordPress user for dd
        $user_id = wp_create_user('dd', 'Profile12!', 'dd@example.com');
        
        if (!is_wp_error($user_id)) {
            update_post_meta($profile->ID, 'scn_user_id', $user_id);
            echo "<div class='success'>";
            echo "<p>✅ Created WordPress user 'dd' (ID: $user_id) and linked to profile</p>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<p>❌ Failed to create WordPress user: " . $user_id->get_error_message() . "</p>";
            echo "</div>";
        }
    }
} else {
    echo "<div class='info'>";
    echo "<p>ℹ️ Profile already linked to WordPress user ID: " . $user_id . "</p>";
    echo "</div>";
}

// Verify all meta data is present
echo "<div class='info'>";
echo "<h2>📋 Final Meta Data Check</h2>";
echo "</div>";

$required_meta = [
    'scn_username' => 'Username',
    'scn_password' => 'Password',
    'scn_user_id' => 'WordPress User ID',
    'member_since' => 'Member Since'
];

echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background-color: #f2f2f2;'><th style='border: 1px solid #ddd; padding: 8px;'>Meta Key</th><th style='border: 1px solid #ddd; padding: 8px;'>Status</th><th style='border: 1px solid #ddd; padding: 8px;'>Value</th></tr>";

foreach ($required_meta as $key => $label) {
    $value = get_post_meta($profile->ID, $key, true);
    $status = !empty($value) ? '✅ Present' : '❌ Missing';
    $display_value = ($key === 'scn_password') ? '[HIDDEN]' : $value;
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$label ($key)</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$status</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>$display_value</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div class='success'>";
echo "<h2>🎉 dd Profile Fixed!</h2>";
echo "<p>The dd profile now has all the required meta data and should work the same as ezekiel.</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🧪 Test the Fixed Profile</h2>";
echo "<p>Try logging in with:</p>";
echo "<ul>";
echo "<li><strong>Username:</strong> dd</li>";
echo "<li><strong>Password:</strong> Profile12!</li>";
echo "</ul>";
echo "<p>Go to: <a href='/member-login/' target='_blank'>/member-login/</a></p>";
echo "</div>";

echo "<hr>";
echo "<p><em>dd profile fix completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
