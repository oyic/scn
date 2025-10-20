<?php
/**
 * Compare Profiles
 * This will help us understand why ezekiel works but dd doesn't
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 Compare Profiles - ezekiel vs dd</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

// Include authentication functions
require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions.php';

// Get both profiles
$ezekiel_profile = get_posts([
    'post_type' => 'profile',
    'meta_query' => [
        [
            'key' => 'scn_username',
            'value' => 'ezekiel',
            'compare' => '='
        ]
    ],
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

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

echo "<div class='info'>";
echo "<h2>📊 Profile Comparison</h2>";
echo "</div>";

if (empty($ezekiel_profile)) {
    echo "<div class='error'>";
    echo "<p>❌ ezekiel profile not found</p>";
    echo "</div>";
} else {
    $ezekiel = $ezekiel_profile[0];
    echo "<div class='success'>";
    echo "<h3>✅ ezekiel Profile Found</h3>";
    echo "<p>Profile ID: " . $ezekiel->ID . "</p>";
    echo "<p>Title: " . $ezekiel->post_title . "</p>";
    echo "<p>Author: " . $ezekiel->post_author . "</p>";
    echo "<p>Status: " . $ezekiel->post_status . "</p>";
    echo "<p>Date: " . $ezekiel->post_date . "</p>";
    echo "</div>";
}

if (empty($dd_profile)) {
    echo "<div class='error'>";
    echo "<p>❌ dd profile not found</p>";
    echo "</div>";
} else {
    $dd = $dd_profile[0];
    echo "<div class='success'>";
    echo "<h3>✅ dd Profile Found</h3>";
    echo "<p>Profile ID: " . $dd->ID . "</p>";
    echo "<p>Title: " . $dd->post_title . "</p>";
    echo "<p>Author: " . $dd->post_author . "</p>";
    echo "<p>Status: " . $dd->post_status . "</p>";
    echo "<p>Date: " . $dd->post_date . "</p>";
    echo "</div>";
}

// Compare meta data
echo "<div class='info'>";
echo "<h2>🔍 Meta Data Comparison</h2>";
echo "</div>";

$meta_keys = ['scn_username', 'scn_password', 'scn_user_id', 'member_since', 'scn_first_name', 'scn_last_name'];

echo "<table>";
echo "<tr><th>Meta Key</th><th>ezekiel</th><th>dd</th><th>Difference</th></tr>";

foreach ($meta_keys as $key) {
    $ezekiel_value = !empty($ezekiel) ? get_post_meta($ezekiel->ID, $key, true) : 'N/A';
    $dd_value = !empty($dd) ? get_post_meta($dd->ID, $key, true) : 'N/A';
    
    $difference = ($ezekiel_value === $dd_value) ? '✅ Same' : '❌ Different';
    
    echo "<tr>";
    echo "<td>$key</td>";
    echo "<td>" . ($key === 'scn_password' ? '[HIDDEN]' : $ezekiel_value) . "</td>";
    echo "<td>" . ($key === 'scn_password' ? '[HIDDEN]' : $dd_value) . "</td>";
    echo "<td>$difference</td>";
    echo "</tr>";
}

echo "</table>";

// Test authentication for both
echo "<div class='info'>";
echo "<h2>🧪 Authentication Test</h2>";
echo "</div>";

// Test ezekiel authentication
if (!empty($ezekiel)) {
    echo "<div class='info'>";
    echo "<h3>Testing ezekiel authentication</h3>";
    echo "</div>";
    
    $ezekiel_auth = scn_authenticate_profile('ezekiel', 'Ezekiel123!');
    
    if (is_wp_error($ezekiel_auth)) {
        echo "<div class='error'>";
        echo "<p>❌ ezekiel authentication failed: " . $ezekiel_auth->get_error_message() . "</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<p>✅ ezekiel authentication successful</p>";
        echo "<p>Profile ID: " . $ezekiel_auth->ID . "</p>";
        echo "</div>";
    }
}

// Test dd authentication
if (!empty($dd)) {
    echo "<div class='info'>";
    echo "<h3>Testing dd authentication</h3>";
    echo "</div>";
    
    $dd_auth = scn_authenticate_profile('dd', 'Profile12!');
    
    if (is_wp_error($dd_auth)) {
        echo "<div class='error'>";
        echo "<p>❌ dd authentication failed: " . $dd_auth->get_error_message() . "</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<p>✅ dd authentication successful</p>";
        echo "<p>Profile ID: " . $dd_auth->ID . "</p>";
        echo "</div>";
    }
}

// Test session simulation for both
echo "<div class='info'>";
echo "<h2>🔄 Session Simulation Test</h2>";
echo "</div>";

if (!empty($ezekiel)) {
    echo "<div class='info'>";
    echo "<h3>Simulating ezekiel session</h3>";
    echo "</div>";
    
    // Clear any existing session
    if (!headers_sent()) {
        if (!session_id()) {
            session_start();
        }
        unset($_SESSION['profile_id']);
        unset($_SESSION['profile_authenticated']);
    }
    
    // Set ezekiel session
    scn_set_profile_session($ezekiel->ID);
    
    // Check if authenticated
    $is_auth = scn_is_profile_authenticated();
    $current_profile = scn_get_current_profile();
    
    echo "<div class='info'>";
    echo "<p>Authenticated: " . ($is_auth ? 'Yes' : 'No') . "</p>";
    echo "<p>Current profile: " . ($current_profile ? $current_profile->post_title . " (ID: " . $current_profile->ID . ")" : 'None') . "</p>";
    echo "</div>";
}

if (!empty($dd)) {
    echo "<div class='info'>";
    echo "<h3>Simulating dd session</h3>";
    echo "</div>";
    
    // Clear any existing session
    if (!headers_sent()) {
        if (!session_id()) {
            session_start();
        }
        unset($_SESSION['profile_id']);
        unset($_SESSION['profile_authenticated']);
    }
    
    // Set dd session
    scn_set_profile_session($dd->ID);
    
    // Check if authenticated
    $is_auth = scn_is_profile_authenticated();
    $current_profile = scn_get_current_profile();
    
    echo "<div class='info'>";
    echo "<p>Authenticated: " . ($is_auth ? 'Yes' : 'No') . "</p>";
    echo "<p>Current profile: " . ($current_profile ? $current_profile->post_title . " (ID: " . $current_profile->ID . ")" : 'None') . "</p>";
    echo "</div>";
}

// Check if there are any differences in the profiles that could cause issues
echo "<div class='info'>";
echo "<h2>🔍 Potential Issues</h2>";
echo "</div>";

if (!empty($ezekiel) && !empty($dd)) {
    $issues = [];
    
    // Check post status
    if ($ezekiel->post_status !== $dd->post_status) {
        $issues[] = "Different post status: ezekiel=" . $ezekiel->post_status . ", dd=" . $dd->post_status;
    }
    
    // Check if dd has a WordPress user linked
    $dd_user_id = get_post_meta($dd->ID, 'scn_user_id', true);
    $ezekiel_user_id = get_post_meta($ezekiel->ID, 'scn_user_id', true);
    
    if ($dd_user_id && $ezekiel_user_id) {
        // Both have users
        $dd_user = get_userdata($dd_user_id);
        $ezekiel_user = get_userdata($ezekiel_user_id);
        
        if (!$dd_user || !$ezekiel_user) {
            $issues[] = "One or both linked users don't exist";
        }
    } elseif ($dd_user_id && !$ezekiel_user_id) {
        $issues[] = "dd has WordPress user linked, ezekiel doesn't";
    } elseif (!$dd_user_id && $ezekiel_user_id) {
        $issues[] = "ezekiel has WordPress user linked, dd doesn't";
    }
    
    if (empty($issues)) {
        echo "<div class='success'>";
        echo "<p>✅ No obvious differences found</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Potential Issues Found:</h3>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

echo "<div class='info'>";
echo "<h2>🧪 Test URLs</h2>";
echo "<p>Try these URLs to test both profiles:</p>";
echo "<ul>";
echo "<li><a href='/member-login/' target='_blank'>/member-login/</a> - Login page</li>";
echo "<li><a href='/member-dashboard/' target='_blank'>/member-dashboard/</a> - Dashboard</li>";
echo "</ul>";
echo "<p><strong>Test both:</strong></p>";
echo "<ul>";
echo "<li>ezekiel / Ezekiel123!</li>";
echo "<li>dd / Profile12!</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Profile comparison completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
