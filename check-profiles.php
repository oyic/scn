<?php
/**
 * Check Profiles and Users
 * This will show us what profiles exist and their relationship to WordPress users
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 Profile and User Analysis</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

// Check all profiles
echo "<div class='info'>";
echo "<h2>📋 All SCN Profiles</h2>";
echo "</div>";

$profiles = get_posts([
    'post_type' => 'scn_profile',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

if (empty($profiles)) {
    echo "<div class='error'>";
    echo "<p>❌ No profiles found!</p>";
    echo "</div>";
} else {
    echo "<table>";
    echo "<tr><th>Profile ID</th><th>Title</th><th>Author</th><th>User ID (Meta)</th><th>Linked User</th><th>Actions</th></tr>";
    
    foreach ($profiles as $profile) {
        $user_id_meta = get_post_meta($profile->ID, 'scn_user_id', true);
        $linked_user = $user_id_meta ? get_userdata($user_id_meta) : null;
        
        echo "<tr>";
        echo "<td>" . $profile->ID . "</td>";
        echo "<td>" . $profile->post_title . "</td>";
        echo "<td>" . $profile->post_author . "</td>";
        echo "<td>" . ($user_id_meta ? $user_id_meta : 'None') . "</td>";
        echo "<td>" . ($linked_user ? $linked_user->user_login : 'No User') . "</td>";
        echo "<td>";
        echo "<a href='" . get_edit_post_link($profile->ID) . "' target='_blank'>Edit</a> | ";
        echo "<a href='" . get_permalink($profile->ID) . "' target='_blank'>View</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check all WordPress users
echo "<div class='info'>";
echo "<h2>👥 WordPress Users</h2>";
echo "</div>";

$users = get_users();
if (empty($users)) {
    echo "<div class='error'>";
    echo "<p>❌ No WordPress users found!</p>";
    echo "</div>";
} else {
    echo "<table>";
    echo "<tr><th>User ID</th><th>Username</th><th>Email</th><th>Display Name</th><th>Has Profile</th></tr>";
    
    foreach ($users as $user) {
        $has_profile = get_posts([
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
        
        echo "<tr>";
        echo "<td>" . $user->ID . "</td>";
        echo "<td>" . $user->user_login . "</td>";
        echo "<td>" . $user->user_email . "</td>";
        echo "<td>" . $user->display_name . "</td>";
        echo "<td>" . (!empty($has_profile) ? "✅ Yes (ID: " . $has_profile[0]->ID . ")" : "❌ No") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check if we can create a profile-only authentication system
echo "<div class='info'>";
echo "<h2>🔧 Profile-Only Authentication Options</h2>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>Option 1: Create WordPress User for Each Profile</h3>";
echo "<p>This would create a WordPress user for each profile that doesn't have one.</p>";
echo "<p><strong>Pros:</strong> Uses existing WordPress authentication</p>";
echo "<p><strong>Cons:</strong> Creates users that might not be needed</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>Option 2: Profile-Only Authentication System</h3>";
echo "<p>Create a custom authentication system that works directly with profiles.</p>";
echo "<p><strong>Pros:</strong> No need for WordPress users, profiles are self-contained</p>";
echo "<p><strong>Cons:</strong> Need to build custom login system</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>Option 3: Hybrid System</h3>";
echo "<p>Allow both WordPress users and profile-only members.</p>";
echo "<p><strong>Pros:</strong> Flexible, supports both use cases</p>";
echo "<p><strong>Cons:</strong> More complex authentication logic</p>";
echo "</div>";

// Quick actions
echo "<div class='info'>";
echo "<h2>🚀 Quick Actions</h2>";
echo "</div>";

echo "<p><a href='create-wordpress-user-for-profiles.php' target='_blank'>Create WordPress Users for All Profiles</a></p>";
echo "<p><a href='create-profile-only-auth.php' target='_blank'>Create Profile-Only Authentication System</a></p>";
echo "<p><a href='test-current-login.php' target='_blank'>Test Current Login System</a></p>";

echo "<hr>";
echo "<p><em>Profile analysis completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
