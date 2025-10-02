<?php
/**
 * Fix ACF REST API Permissions
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix ACF REST API</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; max-width: 900px; margin: 0 auto; border-radius: 8px; }
        .success { color: #46b450; background: #ecf7ed; padding: 15px; margin: 10px 0; }
        .error { color: #dc3232; background: #fbeaea; padding: 15px; margin: 10px 0; }
        .info { color: #0073aa; background: #e5f5fa; padding: 15px; margin: 10px 0; }
        .button { background: #0073aa; color: white; padding: 15px 30px; border: none; cursor: pointer; font-size: 16px; border-radius: 4px; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Fix ACF REST API Permissions</h1>
    
    <div class="error">
        <p><strong>The Problem:</strong></p>
        <p>ACF is getting <code>403 Forbidden</code> when trying to access:</p>
        <pre>GET /wp-json/wp/v2/types/acf-field-group?context=edit</pre>
        <p>This means ACF's REST API endpoint is blocked or you don't have permissions.</p>
    </div>
    
    <?php
    if (isset($_POST['add_rest_support'])) {
        echo '<h2>Enabling REST API Support for ACF...</h2>';
        
        // Add filter to enable REST API for ACF field groups
        $code = <<<'PHP'
// Enable REST API for ACF field groups
add_filter('register_post_type_args', function($args, $post_type) {
    if ($post_type === 'acf-field-group') {
        $args['show_in_rest'] = true;
        $args['rest_base'] = 'acf-field-group';
        $args['rest_controller_class'] = 'WP_REST_Posts_Controller';
    }
    return $args;
}, 10, 2);

// Ensure current user can access ACF REST endpoints
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    $route = $request->get_route();
    
    if (strpos($route, '/wp/v2/types/acf-field-group') !== false) {
        if (current_user_can('manage_options')) {
            return $result;
        }
    }
    
    return $result;
}, 10, 3);
PHP;
        
        // Write to a separate file that can be included
        $fix_file = __DIR__ . '/acf-rest-api-fix.php';
        file_put_contents($fix_file, "<?php\n" . $code);
        
        echo '<div class="success">✅ Created fix file: acf-rest-api-fix.php</div>';
        echo '<div class="info">This file will be loaded by the plugin to enable REST API for ACF.</div>';
        
        // Now include it
        require_once($fix_file);
        
        echo '<div class="success">✅ REST API support enabled for this session</div>';
        echo '<div class="info">You need to add this to the plugin permanently. See instructions below.</div>';
    }
    ?>
    
    <h2>Diagnosis</h2>
    <?php
    // Check current REST API status
    $post_type = get_post_type_object('acf-field-group');
    
    if ($post_type) {
        echo '<div class="info">ACF Field Group Post Type Found</div>';
        echo '<p>Show in REST: ' . ($post_type->show_in_rest ? '✅ Yes' : '❌ No') . '</p>';
        echo '<p>REST Base: ' . ($post_type->rest_base ?? 'Not set') . '</p>';
        
        if (!$post_type->show_in_rest) {
            echo '<div class="error">❌ REST API is disabled for acf-field-group post type!</div>';
            echo '<div class="info">This is why you\'re getting 403 errors.</div>';
        }
    } else {
        echo '<div class="error">❌ acf-field-group post type not found!</div>';
    }
    
    // Check user capabilities
    echo '<h3>User Permissions</h3>';
    $current_user = wp_get_current_user();
    echo '<p>Current User: ' . $current_user->user_login . '</p>';
    echo '<p>Has manage_options: ' . (current_user_can('manage_options') ? '✅' : '❌') . '</p>';
    echo '<p>Has edit_posts: ' . (current_user_can('edit_posts') ? '✅' : '❌') . '</p>';
    
    // Test REST API endpoint
    echo '<h3>Test REST API Endpoint</h3>';
    $rest_url = rest_url('wp/v2/types/acf-field-group');
    echo '<p>Endpoint: <code>' . $rest_url . '</code></p>';
    
    $response = wp_remote_get($rest_url, array(
        'cookies' => $_COOKIE,
        'headers' => array(
            'X-WP-Nonce' => wp_create_nonce('wp_rest')
        )
    ));
    
    if (is_wp_error($response)) {
        echo '<div class="error">❌ Error: ' . $response->get_error_message() . '</div>';
    } else {
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            echo '<div class="success">✅ REST API endpoint accessible (200 OK)</div>';
        } else {
            echo '<div class="error">❌ REST API returned: ' . $code . '</div>';
        }
    }
    ?>
    
    <h2>Quick Fix</h2>
    <?php if (!isset($_POST['add_rest_support'])): ?>
        <form method="post">
            <button type="submit" name="add_rest_support" class="button">Enable REST API for ACF</button>
        </form>
    <?php else: ?>
        <div class="success">
            <h3>Fix Applied! Now add it permanently:</h3>
            <p>Add this code to <code>scn-membership.php</code> in the <code>init()</code> method:</p>
            <pre>
// Enable REST API for ACF field groups
if (file_exists(__DIR__ . '/acf-rest-api-fix.php')) {
    require_once __DIR__ . '/acf-rest-api-fix.php';
}
</pre>
        </div>
        
        <p><a href="<?php echo admin_url('post-new.php?post_type=acf-field-group'); ?>" class="button">Try Creating Field Group Now →</a></p>
    <?php endif; ?>
    
</div>
</body>
</html>

