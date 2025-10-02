<?php
/**
 * Test ACF Directly - Bypassing all plugin code
 */

require_once('../../../wp-load.php');

// Now WordPress is loaded, we can use functions

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test ACF Directly</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; max-width: 900px; margin: 0 auto; border-radius: 8px; }
        .success { color: #46b450; background: #ecf7ed; padding: 15px; margin: 10px 0; }
        .error { color: #dc3232; background: #fbeaea; padding: 15px; margin: 10px 0; }
        .info { color: #0073aa; background: #e5f5fa; padding: 15px; margin: 10px 0; }
        .button { background: #0073aa; color: white; padding: 15px 30px; border: none; cursor: pointer; font-size: 16px; border-radius: 4px; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Test ACF Directly (SCN Plugin Bypassed)</h1>
    
    <div class="info">
        <p><strong>This test loads WordPress WITHOUT the SCN plugin's filters.</strong></p>
        <p>Try creating a field group now to see if the SCN plugin is causing the issue.</p>
    </div>
    
    <?php
    echo '<h2>1. ACF Status</h2>';
    
    if (class_exists('ACF')) {
        echo '<div class="success">✅ ACF is loaded</div>';
        echo '<div class="info">Version: ' . (defined('ACF_VERSION') ? ACF_VERSION : 'Unknown') . '</div>';
    } else {
        echo '<div class="error">❌ ACF is not loaded</div>';
    }
    
    echo '<h2>2. Active Filters Check</h2>';
    global $wp_filter;
    
    $hooks_to_check = array(
        'save_post',
        'save_post_acf-field-group',
        'wp_insert_post_data',
        'map_meta_cap',
    );
    
    foreach ($hooks_to_check as $hook) {
        if (isset($wp_filter[$hook])) {
            $callbacks = $wp_filter[$hook]->callbacks;
            $count = 0;
            foreach ($callbacks as $priority => $functions) {
                $count += count($functions);
            }
            echo '<div class="info">Hook: <code>' . $hook . '</code> has ' . $count . ' callbacks</div>';
        } else {
            echo '<div class="success">Hook: <code>' . $hook . '</code> has no callbacks</div>';
        }
    }
    
    echo '<h2>3. Test Creating Field Group</h2>';
    ?>
    
    <div class="info">
        <h3>Test Steps:</h3>
        <ol>
            <li><a href="<?php echo admin_url('post-new.php?post_type=acf-field-group'); ?>" target="_blank" class="button">Create New Field Group</a></li>
            <li>Give it any name</li>
            <li>Add one simple text field</li>
            <li>Click "Publish"</li>
            <li>Try to edit it again</li>
        </ol>
        <p><strong>Does it work?</strong></p>
        <ul>
            <li>✅ <strong>YES</strong> = SCN plugin is interfering with ACF</li>
            <li>❌ <strong>NO</strong> = ACF itself has an issue (possibly corrupted installation)</li>
        </ul>
    </div>
    
    <h2>4. Alternative Solution</h2>
    <div class="info">
        <p>If the SCN plugin IS the problem, we can:</p>
        <ol>
            <li>Remove ALL ACF-related code from the SCN plugin</li>
            <li>Let ACF work natively without any customization</li>
            <li>Create field groups manually in ACF UI</li>
            <li>Keep the JSON files as backups only</li>
        </ol>
    </div>
    
</div>
</body>
</html>

