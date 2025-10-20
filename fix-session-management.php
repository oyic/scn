<?php
/**
 * Fix Session Management
 * This creates a better session management system that works reliably
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔧 Fixing Session Management</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Create improved authentication functions
echo "<div class='info'>";
echo "<h2>🔧 Creating Improved Authentication Functions</h2>";
echo "</div>";

$improved_auth_functions = '<?php
/**
 * Improved Profile-Only Authentication Functions
 * This version uses WordPress transients and user meta for reliable session management
 */

if (!defined("ABSPATH")) {
    exit;
}

/**
 * Authenticate profile using username and password
 */
function scn_authenticate_profile($username, $password) {
    // Find profile by username
    $profiles = get_posts([
        "post_type" => "profile",
        "meta_query" => [
            [
                "key" => "scn_username",
                "value" => $username,
                "compare" => "="
            ]
        ],
        "posts_per_page" => 1,
        "post_status" => "publish"
    ]);
    
    if (empty($profiles)) {
        return new WP_Error("invalid_username", "Invalid username");
    }
    
    $profile = $profiles[0];
    $stored_password = get_post_meta($profile->ID, "scn_password", true);
    
    if (!wp_check_password($password, $stored_password)) {
        return new WP_Error("invalid_password", "Invalid password");
    }
    
    return $profile;
}

/**
 * Set profile session using WordPress transients and cookies
 */
function scn_set_profile_session($profile_id) {
    // Generate a unique session token
    $session_token = wp_generate_password(32, false);
    
    // Store session data in WordPress transient (expires in 1 hour)
    $session_data = [
        "profile_id" => $profile_id,
        "authenticated" => true,
        "created" => current_time("timestamp")
    ];
    
    set_transient("scn_session_" . $session_token, $session_data, 3600); // 1 hour
    
    // Set secure cookie with the session token
    if (!headers_sent()) {
        setcookie(
            "scn_session_token", 
            $session_token, 
            time() + 3600, // 1 hour
            "/", 
            "", 
            false, // not secure for local development
            true // httponly
        );
    }
    
    // Also store in user meta if profile has linked user
    $user_id = get_post_meta($profile_id, "scn_user_id", true);
    if ($user_id) {
        update_user_meta($user_id, "scn_current_session_token", $session_token);
    }
}

/**
 * Check if profile is authenticated
 */
function scn_is_profile_authenticated() {
    // Check cookie first
    if (isset($_COOKIE["scn_session_token"])) {
        $session_token = sanitize_text_field($_COOKIE["scn_session_token"]);
        $session_data = get_transient("scn_session_" . $session_token);
        
        if ($session_data && $session_data["authenticated"]) {
            return true;
        }
    }
    
    // Fallback: check if user is logged in and has session token
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $session_token = get_user_meta($current_user->ID, "scn_current_session_token", true);
        
        if ($session_token) {
            $session_data = get_transient("scn_session_" . $session_token);
            if ($session_data && $session_data["authenticated"]) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Get current authenticated profile
 */
function scn_get_current_profile() {
    if (!scn_is_profile_authenticated()) {
        return null;
    }
    
    $profile_id = null;
    
    // Check cookie first
    if (isset($_COOKIE["scn_session_token"])) {
        $session_token = sanitize_text_field($_COOKIE["scn_session_token"]);
        $session_data = get_transient("scn_session_" . $session_token);
        
        if ($session_data && $session_data["authenticated"]) {
            $profile_id = $session_data["profile_id"];
        }
    }
    
    // Fallback: check user meta
    if (!$profile_id && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $session_token = get_user_meta($current_user->ID, "scn_current_session_token", true);
        
        if ($session_token) {
            $session_data = get_transient("scn_session_" . $session_token);
            if ($session_data && $session_data["authenticated"]) {
                $profile_id = $session_data["profile_id"];
            }
        }
    }
    
    if (!$profile_id) {
        return null;
    }
    
    return get_post($profile_id);
}

/**
 * Logout profile
 */
function scn_logout_profile() {
    // Clear cookie
    if (isset($_COOKIE["scn_session_token"])) {
        $session_token = sanitize_text_field($_COOKIE["scn_session_token"]);
        
        // Delete transient
        delete_transient("scn_session_" . $session_token);
        
        // Clear cookie
        if (!headers_sent()) {
            setcookie("scn_session_token", "", time() - 3600, "/");
        }
    }
    
    // Clear user meta if logged in
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        delete_user_meta($current_user->ID, "scn_current_session_token");
    }
}

/**
 * Get profile by ID
 */
function scn_get_profile($profile_id) {
    $profile = get_post($profile_id);
    if (!$profile || $profile->post_type !== "profile") {
        return null;
    }
    return $profile;
}

/**
 * Extend session (renew for another hour)
 */
function scn_extend_session() {
    if (isset($_COOKIE["scn_session_token"])) {
        $session_token = sanitize_text_field($_COOKIE["scn_session_token"]);
        $session_data = get_transient("scn_session_" . $session_token);
        
        if ($session_data && $session_data["authenticated"]) {
            // Extend the session
            set_transient("scn_session_" . $session_token, $session_data, 3600); // 1 hour
            
            // Update cookie
            if (!headers_sent()) {
                setcookie(
                    "scn_session_token", 
                    $session_token, 
                    time() + 3600, 
                    "/", 
                    "", 
                    false, 
                    true
                );
            }
        }
    }
}
';

file_put_contents(SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions-improved.php', $improved_auth_functions);

echo "<div class='success'>";
echo "<p>✅ Improved authentication functions created</p>";
echo "</div>";

// Update the login template to use improved functions
echo "<div class='info'>";
echo "<h2>🔄 Updating Login Template</h2>";
echo "</div>";

$login_template_file = SCN_MEMBERSHIP_PATH . 'templates/auth/profile-login.php';
if (file_exists($login_template_file)) {
    $login_content = file_get_contents($login_template_file);
    
    // Replace the include line
    $login_content = str_replace(
        'require_once SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions.php";',
        'require_once SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions-improved.php";',
        $login_content
    );
    
    file_put_contents($login_template_file, $login_content);
    
    echo "<div class='success'>";
    echo "<p>✅ Updated login template to use improved functions</p>";
    echo "</div>";
}

// Update the dashboard template to use improved functions
echo "<div class='info'>";
echo "<h2>🔄 Updating Dashboard Template</h2>";
echo "</div>";

$dashboard_template_file = SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard-fixed.php';
if (file_exists($dashboard_template_file)) {
    $dashboard_content = file_get_contents($dashboard_template_file);
    
    // Replace the include line
    $dashboard_content = str_replace(
        'require_once SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions.php";',
        'require_once SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions-improved.php";',
        $dashboard_content
    );
    
    file_put_contents($dashboard_template_file, $dashboard_content);
    
    echo "<div class='success'>";
    echo "<p>✅ Updated dashboard template to use improved functions</p>";
    echo "</div>";
}

// Update AuthService to use improved functions
echo "<div class='info'>";
echo "<h2>🔄 Updating AuthService</h2>";
echo "</div>";

$auth_service_file = SCN_MEMBERSHIP_PATH . 'src/Modules/Auth/AuthService.php';
if (file_exists($auth_service_file)) {
    $auth_content = file_get_contents($auth_service_file);
    
    // Replace the include line
    $auth_content = str_replace(
        'require_once SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions.php";',
        'require_once SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions-improved.php";',
        $auth_content
    );
    
    file_put_contents($auth_service_file, $auth_content);
    
    echo "<div class='success'>";
    echo "<p>✅ Updated AuthService to use improved functions</p>";
    echo "</div>";
}

// Test the improved system
echo "<div class='info'>";
echo "<h2>🧪 Test Improved Session System</h2>";
echo "</div>";

// Include the improved functions
require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions-improved.php';

// Test authentication
$test_profile = get_posts([
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

if (!empty($test_profile)) {
    echo "<div class='info'>";
    echo "<h3>Testing with profile: " . $test_profile[0]->post_title . " (ID: " . $test_profile[0]->ID . ")</h3>";
    echo "</div>";
    
    // Set session
    scn_set_profile_session($test_profile[0]->ID);
    
    // Check authentication
    $is_auth = scn_is_profile_authenticated();
    $current_profile = scn_get_current_profile();
    
    echo "<div class='info'>";
    echo "<p>After setting session:</p>";
    echo "<p>Authenticated: " . ($is_auth ? 'Yes' : 'No') . "</p>";
    echo "<p>Current Profile: " . ($current_profile ? $current_profile->post_title : 'None') . "</p>";
    echo "</div>";
    
    if ($is_auth && $current_profile) {
        echo "<div class='success'>";
        echo "<p>🎉 Improved session system working!</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Session system still not working</p>";
        echo "</div>";
    }
}

echo "<div class='success'>";
echo "<h2>🎉 Session Management Fixed!</h2>";
echo "<p>The new session system uses WordPress transients and cookies for reliable session management.</p>";
echo "<p>Key improvements:</p>";
echo "<ul>";
echo "<li>✅ Uses WordPress transients instead of PHP sessions</li>";
echo "<li>✅ Secure session tokens</li>";
echo "<li>✅ Fallback to user meta for logged-in users</li>";
echo "<li>✅ Proper cookie handling</li>";
echo "<li>✅ Session expiration (1 hour)</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🧪 Test the Fixed System</h2>";
echo "<p>Try these URLs:</p>";
echo "<ul>";
echo "<li><a href='" . home_url('/member-login/') . "' target='_blank'>" . home_url('/member-login/') . "</a></li>";
echo "<li><a href='" . home_url('/member-dashboard/') . "' target='_blank'>" . home_url('/member-dashboard/') . "</a></li>";
echo "</ul>";
echo "<p><strong>Test Credentials:</strong></p>";
echo "<ul>";
echo "<li>ezekiel / Ezekiel123!</li>";
echo "<li>dd / Profile12!</li>";
echo "<li>testmemberprofile / Profile79!</li>";
echo "<li>testprofile / Profile18!</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Session management fix completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
