<?php
/**
 * Direct Dashboard Test
 * This bypasses WordPress template loading to test if the issue is with template loading
 */

// Include WordPress
require_once('../../../wp-config.php');

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Not Logged In</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .error { background: #fee; border: 1px solid #fcc; color: #c33; padding: 15px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ Not Logged In</h2>
            <p>You need to log in first.</p>
            <p><a href="<?php echo home_url('/member-login/'); ?>">Go to Login</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$user_id = get_current_user_id();
$current_user = wp_get_current_user();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Dashboard Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { background: #efe; border: 1px solid #cfc; color: #363; padding: 15px; border-radius: 5px; }
        .info { background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff8f0; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #fee; border: 1px solid #fcc; color: #c33; padding: 15px; border-radius: 5px; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="success">
        <h1>🎉 Direct Dashboard Test - SUCCESS!</h1>
        <p>This proves that WordPress authentication and basic functionality works.</p>
    </div>
    
    <div class="info">
        <h2>User Information</h2>
        <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
        <p><strong>Username:</strong> <?php echo $current_user->user_login; ?></p>
        <p><strong>Email:</strong> <?php echo $current_user->user_email; ?></p>
        <p><strong>Display Name:</strong> <?php echo $current_user->display_name; ?></p>
    </div>
    
    <div class="info">
        <h2>Profile Status</h2>
        <?php
        $profile_posts = get_posts([
            'post_type' => 'scn_profile',
            'meta_query' => [
                [
                    'key' => 'scn_user_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (!empty($profile_posts)) {
            $profile = $profile_posts[0];
            echo "<p style='color: green;'>✓ You have a profile!</p>";
            echo "<p><strong>Profile ID:</strong> " . $profile->ID . "</p>";
            echo "<p><strong>Profile Title:</strong> " . $profile->post_title . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠ You don't have a profile yet.</p>";
        }
        ?>
    </div>
    
    <div class="info">
        <h2>URL Tests</h2>
        <p><a href="<?php echo home_url('/member-login/'); ?>" target="_blank">Member Login</a></p>
        <p><a href="<?php echo home_url('/member-dashboard/'); ?>" target="_blank">Member Dashboard</a></p>
        <p><a href="<?php echo home_url('/test-auth/'); ?>" target="_blank">Test Auth</a></p>
        <p><a href="<?php echo wp_logout_url(home_url('/member-login/')); ?>">Logout</a></p>
    </div>
    
    <div class="warning">
        <h2>Next Steps</h2>
        <p>If you can see this page, it means:</p>
        <ul>
            <li>✅ WordPress authentication works</li>
            <li>✅ Database connection works</li>
            <li>✅ Plugin files are accessible</li>
            <li>✅ User profile queries work</li>
        </ul>
        <p><strong>The issue is likely with WordPress template loading or rewrite rules.</strong></p>
    </div>
    
    <div class="info">
        <h2>Debug Information</h2>
        <p><strong>Current Time:</strong> <?php echo current_time('mysql'); ?></p>
        <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
        <p><strong>Plugin Path:</strong> <?php echo SCN_MEMBERSHIP_PATH; ?></p>
        <p><strong>Home URL:</strong> <?php echo home_url(); ?></p>
        <p><strong>Site URL:</strong> <?php echo site_url(); ?></p>
    </div>
</body>
</html>
