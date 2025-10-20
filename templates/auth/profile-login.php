<?php
/**
 * Profile-Only Login Template
 */

if (!defined("ABSPATH")) {


    exit;
}

// Include authentication functions
require_once SCN_MEMBERSHIP_PATH . "includes/profile-auth-functions-improved.php";

// Handle logout action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (function_exists('scn_logout_profile')) {
        scn_logout_profile();
    }
    // Redirect to login page without action parameter
    wp_redirect(get_permalink(81));
    exit;
}

// Redirect if already authenticated (but allow admin/editor access)
if (scn_is_profile_authenticated() && !current_user_can('edit_pages')) {
    wp_redirect(get_permalink(82));
    exit;
}

// Handle login form submission
if ($_POST && isset($_POST["profile_login"])) {
    $username = sanitize_text_field($_POST["username"]);
    $password = $_POST["password"];
    
    $profile = scn_authenticate_profile($username, $password);
    
    if (!is_wp_error($profile)) {
        scn_set_profile_session($profile->ID);
        wp_redirect(get_permalink(82));
        exit;
    } else {
        $login_error = $profile->get_error_message();
    }
}

get_header();
?>

<style>
.scn-profile-login-page {
    min-height: 100vh;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.scn-login-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    overflow: hidden;
    max-width: 500px;
    width: 100%;
}

.scn-login-form-wrapper {
    padding: 50px 40px;
}

.scn-login-header {
    text-align: center;
    margin-bottom: 30px;
}

.scn-login-header h1 {
    color: #2c3e50;
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 10px 0;
}

.scn-login-header p {
    color: #7f8c8d;
    font-size: 16px;
    margin: 0;
}

.scn-login-error {
    background: #fee;
    color: #c0392b;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-left: 4px solid #e74c3c;
}

.scn-form-group {
    margin-bottom: 20px;
    position: relative;
}

.scn-form-group label {
    display: block;
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}

.scn-form-group input {
    width: 100%;
    padding: 15px;
    border: 2px solid #ecf0f1;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.scn-form-group input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.scn-login-btn {
    width: 100%;
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 18px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.scn-login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
}

.scn-test-info {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    margin-top: 30px;
}

.scn-test-info h3 {
    color: #2c3e50;
    margin: 0 0 15px 0;
    font-size: 16px;
}

.scn-test-info p {
    margin: 8px 0;
    font-size: 14px;
    color: #555;
    font-family: monospace;
    background: white;
    padding: 8px 12px;
    border-radius: 5px;
    border-left: 3px solid #3498db;
}

.scn-login-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #ecf0f1;
}

.scn-login-footer p {
    color: #7f8c8d;
    margin: 0 0 15px 0;
}

.scn-register-link {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
    padding: 12px 25px;
    border: 2px solid #3498db;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: inline-block;
}

.scn-register-link:hover {
    background: #3498db;
    color: white;
    text-decoration: none;
}

@media (max-width: 480px) {
    .scn-login-form-wrapper {
        padding: 40px 30px;
    }
    
    .scn-login-header h1 {
        font-size: 28px;
    }
}
</style>

<div class="scn-profile-login-page">
    <div class="scn-login-container">
        <div class="scn-login-form-wrapper">
            <div class="scn-login-header">
                <h1><?php _e("Welcome Back", "scn-membership"); ?></h1>
                <p><?php _e("Sign in to your SCN account", "scn-membership"); ?></p>
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
            

            <div class="scn-login-footer">
                <p><?php _e("Don't have an account?", "scn-membership"); ?></p>
                <a href="<?php echo esc_url(home_url('/member-register/')); ?>" class="scn-register-link">
                    <?php _e("Create Account", "scn-membership"); ?>
                </a>
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

<?php get_footer(); ?>