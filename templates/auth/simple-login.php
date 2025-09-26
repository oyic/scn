<?php
/**
 * Simple Login Test Page
 * This bypasses the complex authentication system for testing
 */

// Include WordPress
if (!defined('ABSPATH')) {
    require_once('../../../../wp-config.php');
}

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/member-dashboard/'));
    exit;
}

// Handle login form submission
if ($_POST && isset($_POST['simple_login'])) {
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    
    $creds = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => false,
    ];
    
    $user = wp_signon($creds, false);
    
    if (!is_wp_error($user)) {
        // Simple redirect to dashboard
        wp_redirect(home_url('/member-dashboard/'));
        exit;
    } else {
        $login_error = $user->get_error_message();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 600;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #3498db;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #fcc;
        }
        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 12px;
            font-family: monospace;
        }
        .test-credentials {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🔐 Simple Login Test</h1>
        
        <div class="test-credentials">
            <strong>Test Credentials:</strong><br>
            Username: testmember<br>
            Password: TestMember123!
        </div>
        
        <?php if (isset($login_error)): ?>
            <div class="error">
                <strong>Login Error:</strong> <?php echo esc_html($login_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_POST['simple_login'])): ?>
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Username: <?php echo esc_html($_POST['username'] ?? 'not set'); ?><br>
                Password: <?php echo !empty($_POST['password']) ? '[SET]' : '[NOT SET]'; ?><br>
                WordPress Loaded: <?php echo function_exists('wp_signon') ? 'Yes' : 'No'; ?><br>
                Current User: <?php echo is_user_logged_in() ? wp_get_current_user()->user_login : 'Not logged in'; ?><br>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="testmember" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="TestMember123!" required>
            </div>
            
            <button type="submit" name="simple_login" class="btn">Login</button>
        </form>
        
        <div style="margin-top: 30px; text-align: center;">
            <p><a href="<?php echo home_url('/member-login/'); ?>">Go to Full Login Page</a></p>
            <p><a href="debug-login.php">Debug Login Issues</a></p>
            <p><a href="create-test-user-simple.php">Create Test User</a></p>
        </div>
    </div>
</body>
</html>
