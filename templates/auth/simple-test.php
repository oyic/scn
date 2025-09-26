<?php
/**
 * Simple Authentication Test Page
 * Access this directly to test the authentication system
 */

// Basic WordPress setup
if (!defined('ABSPATH')) {
    // Try to find WordPress
    $wp_config_paths = [
        '../../../wp-config.php',
        '../../../../wp-config.php',
        '../../../../../wp-config.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_config_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once(__DIR__ . '/' . $path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress not found. Please check file paths.');
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>SCN Authentication Test</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .test-section h2 {
            color: #3498db;
            margin-top: 0;
        }
        .url-list {
            list-style: none;
            padding: 0;
        }
        .url-list li {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .url-list a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        .url-list a:hover {
            text-decoration: underline;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #2980b9;
            color: white;
            text-decoration: none;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .test-user-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 SCN Authentication System Test</h1>
        
        <?php
        // Check WordPress status
        if (function_exists('is_plugin_active')) {
            echo '<div class="status success">✓ WordPress is loaded</div>';
        } else {
            echo '<div class="status error">✗ WordPress not properly loaded</div>';
        }
        
        // Check if plugin is active
        if (function_exists('is_plugin_active')) {
            $plugin_active = is_plugin_active('scn-membership/scn-membership.php');
            if ($plugin_active) {
                echo '<div class="status success">✓ SCN Membership plugin is active</div>';
            } else {
                echo '<div class="status warning">⚠ SCN Membership plugin may not be active</div>';
            }
        }
        
        // Check if user is logged in
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            $current_user = wp_get_current_user();
            echo '<div class="status success">✓ You are logged in as: ' . esc_html($current_user->user_login) . '</div>';
        } else {
            echo '<div class="status warning">⚠ You are not logged in</div>';
        }
        ?>
        
        <div class="test-section">
            <h2>🔗 Authentication URLs</h2>
            <p>Try accessing these URLs to test the authentication system:</p>
            
            <ul class="url-list">
                <li>
                    <strong>Member Login:</strong> 
                    <a href="<?php echo home_url('/member-login/'); ?>" target="_blank">
                        <?php echo home_url('/member-login/'); ?>
                    </a>
                </li>
                <li>
                    <strong>Member Registration:</strong> 
                    <a href="<?php echo home_url('/member-register/'); ?>" target="_blank">
                        <?php echo home_url('/member-register/'); ?>
                    </a>
                </li>
                <li>
                    <strong>Member Dashboard:</strong> 
                    <a href="<?php echo home_url('/member-dashboard/'); ?>" target="_blank">
                        <?php echo home_url('/member-dashboard/'); ?>
                    </a>
                </li>
                <li>
                    <strong>Test Authentication:</strong> 
                    <a href="<?php echo home_url('/test-auth/'); ?>" target="_blank">
                        <?php echo home_url('/test-auth/'); ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="test-section">
            <h2>👤 Test User Information</h2>
            <p>Use these credentials to test the login system:</p>
            
            <div class="test-user-info">
                <strong>Username:</strong> testmember<br>
                <strong>Email:</strong> test@scn-membership.com<br>
                <strong>Password:</strong> TestMember123!<br>
                <strong>Name:</strong> Test Member
            </div>
            
            <p><strong>Note:</strong> The test user needs to be created first. You can create it via the test page or WP-CLI.</p>
        </div>
        
        <div class="test-section">
            <h2>🛠️ Quick Actions</h2>
            
            <?php if (function_exists('is_user_logged_in')): ?>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo home_url('/member-dashboard/'); ?>" class="btn">
                        Go to Dashboard
                    </a>
                    <a href="<?php echo wp_logout_url(home_url('/member-login/')); ?>" class="btn btn-secondary">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="<?php echo home_url('/member-login/'); ?>" class="btn">
                        Member Login
                    </a>
                    <a href="<?php echo home_url('/member-register/'); ?>" class="btn btn-secondary">
                        Member Register
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            
            <a href="<?php echo home_url('/test-auth/'); ?>" class="btn btn-secondary">
                Create Test User
            </a>
        </div>
        
        <div class="test-section">
            <h2>🔧 Troubleshooting</h2>
            
            <p><strong>If the URLs return 404 errors:</strong></p>
            <ol>
                <li>Go to <strong>WordPress Admin > Settings > Permalinks</strong></li>
                <li>Click <strong>"Save Changes"</strong> (don't change anything)</li>
                <li>Or deactivate and reactivate the SCN Membership plugin</li>
            </ol>
            
            <p><strong>If you can't create a test user:</strong></p>
            <ol>
                <li>Make sure you're logged in as an administrator</li>
                <li>Go to the <strong>Test Authentication</strong> page</li>
                <li>Click <strong>"Create Test User"</strong></li>
            </ol>
            
            <p><strong>WordPress Admin URLs:</strong></p>
            <ul class="url-list">
                <li><a href="<?php echo admin_url('options-permalink.php'); ?>" target="_blank">Permalinks Settings</a></li>
                <li><a href="<?php echo admin_url('plugins.php'); ?>" target="_blank">Plugins Page</a></li>
                <li><a href="<?php echo admin_url('users.php'); ?>" target="_blank">Users Page</a></li>
            </ul>
        </div>
        
        <div class="test-section">
            <h2>📋 Current Status</h2>
            <ul>
                <li><strong>WordPress:</strong> <?php echo function_exists('get_bloginfo') ? get_bloginfo('version') : 'Unknown'; ?></li>
                <li><strong>Site URL:</strong> <?php echo home_url(); ?></li>
                <li><strong>Admin URL:</strong> <?php echo admin_url(); ?></li>
                <li><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
            </ul>
        </div>
    </div>
</body>
</html>
