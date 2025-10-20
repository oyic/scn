<?php
/**
 * Create Profile-Only Authentication System
 * This creates a custom authentication system that works directly with profiles
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔐 Creating Profile-Only Authentication System</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// First, let's add login credentials to existing profiles
echo "<div class='info'>";
echo "<h2>🔧 Adding Login Credentials to Profiles</h2>";
echo "</div>";

$profiles = get_posts([
    'post_type' => 'profile',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

foreach ($profiles as $profile) {
    $existing_username = get_post_meta($profile->ID, 'scn_username', true);
    $existing_password = get_post_meta($profile->ID, 'scn_password', true);
    
    if (!$existing_username || !$existing_password) {
        // Generate username from profile title
        $username = sanitize_user(strtolower(str_replace(' ', '', $profile->post_title)));
        if (empty($username)) {
            $username = 'profile_' . $profile->ID;
        }
        
        // Generate password
        $password = 'Profile' . $profile->ID . '!';
        
        // Store credentials
        update_post_meta($profile->ID, 'scn_username', $username);
        update_post_meta($profile->ID, 'scn_password', wp_hash_password($password));
        
        echo "<div class='success'>";
        echo "<h3>✅ Profile: " . $profile->post_title . " (ID: " . $profile->ID . ")</h3>";
        echo "<p><strong>Username:</strong> $username</p>";
        echo "<p><strong>Password:</strong> $password</p>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<h3>ℹ️ Profile: " . $profile->post_title . " (ID: " . $profile->ID . ")</h3>";
        echo "<p>Already has credentials</p>";
        echo "</div>";
    }
}

// Create custom authentication functions
echo "<div class='info'>";
echo "<h2>🔨 Creating Custom Authentication Functions</h2>";
echo "</div>";

$auth_functions = '<?php
/**
 * Profile-Only Authentication Functions
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
 * Set profile session
 */
function scn_set_profile_session($profile_id) {
    if (!session_id()) {
        session_start();
    }
    $_SESSION["profile_id"] = $profile_id;
    $_SESSION["profile_authenticated"] = true;
}

/**
 * Check if profile is authenticated
 */
function scn_is_profile_authenticated() {
    if (!session_id()) {
        session_start();
    }
    return isset($_SESSION["profile_authenticated"]) && $_SESSION["profile_authenticated"];
}

/**
 * Get current authenticated profile
 */
function scn_get_current_profile() {
    if (!scn_is_profile_authenticated()) {
        return null;
    }
    
    if (!session_id()) {
        session_start();
    }
    
    $profile_id = $_SESSION["profile_id"] ?? null;
    if (!$profile_id) {
        return null;
    }
    
    return get_post($profile_id);
}

/**
 * Logout profile
 */
function scn_logout_profile() {
    if (!session_id()) {
        session_start();
    }
    unset($_SESSION["profile_id"]);
    unset($_SESSION["profile_authenticated"]);
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
';

file_put_contents(SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions.php', $auth_functions);

echo "<div class='success'>";
echo "<h3>✅ Authentication functions created!</h3>";
echo "<p>File: " . SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions.php</p>";
echo "</div>";

// Create profile-only login template
echo "<div class='info'>";
echo "<h2>📄 Creating Profile-Only Login Template</h2>";
echo "</div>";

$profile_login_template = '<?php
/**
 * Profile-Only Login Template
 */

if (!defined("ABSPATH")) {
    exit;
}

// Include authentication functions
require_once SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions.php";

// Redirect if already authenticated
if (scn_is_profile_authenticated()) {
    wp_redirect(home_url("/member-dashboard/"));
    exit;
}

// Handle login form submission
if ($_POST && isset($_POST["profile_login"])) {
    $username = sanitize_text_field($_POST["username"]);
    $password = $_POST["password"];
    
    $profile = scn_authenticate_profile($username, $password);
    
    if (!is_wp_error($profile)) {
        scn_set_profile_session($profile->ID);
        wp_redirect(home_url("/member-dashboard/"));
        exit;
    } else {
        $login_error = $profile->get_error_message();
    }
}

get_header();
?>

<div class="scn-profile-login-page">
    <div class="scn-login-container">
        <div class="scn-login-form-wrapper">
            <div class="scn-login-header">
                <h1><?php _e("SCN Member Login", "scn-membership"); ?></h1>
                <p><?php _e("Sign in with your profile credentials", "scn-membership"); ?></p>
            </div>

            <?php if (isset($login_error)): ?>
                <div class="scn-login-error">
                    <span class="dashicons dashicons-warning"></span>
                    <?php echo esc_html($login_error); ?>
                </div>
            <?php endif; ?>

            <form class="scn-login-form" method="post" action="">
                <div class="scn-form-group">
                    <label for="username"><?php _e("Username", "scn-membership"); ?></label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="scn-form-group">
                    <label for="password"><?php _e("Password", "scn-membership"); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" name="profile_login" class="scn-login-btn">
                    <?php _e("Sign In", "scn-membership"); ?>
                </button>
            </form>
            
            <div class="scn-test-info" style="background: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <h3>Test Credentials:</h3>
                <?php
                $profiles = get_posts([
                    "post_type" => "profile",
                    "posts_per_page" => -1,
                    "post_status" => "publish"
                ]);
                
                foreach ($profiles as $profile) {
                    $username = get_post_meta($profile->ID, "scn_username", true);
                    if ($username) {
                        echo "<p><strong>" . $profile->post_title . ":</strong> $username / Profile" . $profile->ID . "!</p>";
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
.scn-profile-login-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.scn-login-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    max-width: 500px;
    width: 100%;
}

.scn-login-form-wrapper {
    padding: 60px 50px;
}

.scn-login-header {
    text-align: center;
    margin-bottom: 40px;
}

.scn-login-header h1 {
    color: #2c3e50;
    font-size: 2.5em;
    margin: 0 0 10px 0;
    font-weight: 300;
}

.scn-login-header p {
    color: #7f8c8d;
    font-size: 1.1em;
    margin: 0;
}

.scn-login-error {
    background: #fee;
    border: 1px solid #fcc;
    color: #c33;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.scn-login-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.scn-form-group {
    position: relative;
}

.scn-form-group label {
    display: block;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.9em;
}

.scn-form-group input {
    width: 100%;
    padding: 15px;
    border: 2px solid #ecf0f1;
    border-radius: 10px;
    font-size: 1em;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.scn-form-group input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.scn-login-btn {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 18px;
    border-radius: 10px;
    font-size: 1.1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.scn-login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
}
</style>

<?php get_footer(); ?>';

file_put_contents(SCN_MEMBERSHIP_PATH . 'templates/auth/profile-login.php', $profile_login_template);

echo "<div class='success'>";
echo "<h3>✅ Profile-only login template created!</h3>";
echo "<p>File: " . SCN_MEMBERSHIP_PATH . "templates/auth/profile-login.php</p>";
echo "</div>";

// Create profile-only dashboard template
echo "<div class='info'>";
echo "<h2>📊 Creating Profile-Only Dashboard Template</h2>";
echo "</div>";

$profile_dashboard_template = '<?php
/**
 * Profile-Only Dashboard Template
 */

if (!defined("ABSPATH")) {
    exit;
}

// Include authentication functions
require_once SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions.php";

// Check if profile is authenticated
if (!scn_is_profile_authenticated()) {
    wp_redirect(home_url("/member-login/"));
    exit;
}

$profile = scn_get_current_profile();

if (!$profile) {
    wp_redirect(home_url("/member-login/"));
    exit;
}

get_header();
?>

<div class="scn-profile-dashboard">
    <div class="scn-dashboard-container">
        <div class="scn-dashboard-header">
            <h1>🎉 Profile Dashboard</h1>
            <p>Welcome, <?php echo esc_html($profile->post_title); ?>!</p>
            <a href="<?php echo home_url("/member-logout/"); ?>" class="scn-logout-btn">Logout</a>
        </div>
        
        <div class="scn-dashboard-content">
            <div class="scn-profile-info">
                <h2>Profile Information</h2>
                <p><strong>Profile ID:</strong> <?php echo $profile->ID; ?></p>
                <p><strong>Profile Title:</strong> <?php echo esc_html($profile->post_title); ?></p>
                <p><strong>Created:</strong> <?php echo date("F j, Y", strtotime($profile->post_date)); ?></p>
                
                <?php
                $username = get_post_meta($profile->ID, "scn_username", true);
                if ($username) {
                    echo "<p><strong>Username:</strong> " . esc_html($username) . "</p>";
                }
                ?>
            </div>
            
            <div class="scn-profile-actions">
                <h2>Quick Actions</h2>
                <p><a href="<?php echo get_edit_post_link($profile->ID); ?>" target="_blank">Edit Profile</a></p>
                <p><a href="<?php echo get_permalink($profile->ID); ?>" target="_blank">View Profile</a></p>
                <p><a href="<?php echo home_url("/member-login/"); ?>">Back to Login</a></p>
            </div>
        </div>
    </div>
</div>

<style>
.scn-profile-dashboard {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px;
}

.scn-dashboard-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.scn-dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    text-align: center;
    position: relative;
}

.scn-dashboard-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    font-weight: 300;
}

.scn-dashboard-header p {
    margin: 0 0 20px 0;
    font-size: 1.2em;
    opacity: 0.9;
}

.scn-logout-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.scn-logout-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    text-decoration: none;
}

.scn-dashboard-content {
    padding: 40px;
}

.scn-profile-info,
.scn-profile-actions {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 20px;
}

.scn-profile-info h2,
.scn-profile-actions h2 {
    color: #2c3e50;
    margin: 0 0 20px 0;
    font-size: 1.5em;
}

.scn-profile-info p,
.scn-profile-actions p {
    margin: 10px 0;
    color: #555;
}

.scn-profile-actions a {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
}

.scn-profile-actions a:hover {
    text-decoration: underline;
}
</style>

<?php get_footer(); ?>';

file_put_contents(SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard.php', $profile_dashboard_template);

echo "<div class='success'>";
echo "<h3>✅ Profile-only dashboard template created!</h3>";
echo "<p>File: " . SCN_MEMBERSHIP_PATH . "templates/profiles/profile-dashboard.php</p>";
echo "</div>";

// Update AuthService to use profile-only templates
echo "<div class='info'>";
echo "<h2>🔄 Updating Authentication Service</h2>";
echo "</div>";

// Read current AuthService
$auth_service_file = SCN_MEMBERSHIP_PATH . 'src/Modules/Auth/AuthService.php';
$auth_service_content = file_get_contents($auth_service_file);

// Update template paths
$auth_service_content = str_replace(
    "return SCN_MEMBERSHIP_PATH . 'templates/auth/no-js-login.php';",
    "return SCN_MEMBERSHIP_PATH . 'templates/auth/profile-login.php';",
    $auth_service_content
);

$auth_service_content = str_replace(
    "return SCN_MEMBERSHIP_PATH . 'templates/profiles/dashboard-simple.php';",
    "return SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard.php';",
    $auth_service_content
);

file_put_contents($auth_service_file, $auth_service_content);

echo "<div class='success'>";
echo "<h3>✅ Authentication service updated!</h3>";
echo "</div>";

echo "<div class='success'>";
echo "<h2>🎉 Profile-Only Authentication System Created!</h2>";
echo "<p>Now you can log in using profile credentials instead of WordPress users.</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🧪 Test the New System</h2>";
echo "<p>Try these URLs:</p>";
echo "<ul>";
echo "<li><a href='/member-login/' target='_blank'>/member-login/</a> - Profile-only login</li>";
echo "<li><a href='/member-dashboard/' target='_blank'>/member-dashboard/</a> - Profile dashboard</li>";
echo "</ul>";
echo "<p><strong>Test Credentials:</strong></p>";
foreach ($profiles as $profile) {
    $username = get_post_meta($profile->ID, 'scn_username', true);
    if ($username) {
        echo "<p><strong>" . $profile->post_title . ":</strong> $username / Profile" . $profile->ID . "!</p>";
    }
}
echo "</div>";

echo "<hr>";
echo "<p><em>Profile-only authentication system created at " . date('Y-m-d H:i:s') . "</em></p>";
?>
