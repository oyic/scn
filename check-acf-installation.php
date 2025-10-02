<?php
/**
 * Check ACF Installation and Conflicts
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ACF Installation Check</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; max-width: 900px; margin: 0 auto; border-radius: 8px; }
        .success { color: #46b450; background: #ecf7ed; padding: 15px; margin: 10px 0; }
        .error { color: #dc3232; background: #fbeaea; padding: 15px; margin: 10px 0; }
        .warning { color: #f56e28; background: #fcf3e8; padding: 15px; margin: 10px 0; }
        .info { color: #0073aa; background: #e5f5fa; padding: 15px; margin: 10px 0; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
        .button { background: #0073aa; color: white; padding: 12px 24px; border: none; cursor: pointer; text-decoration: none; display: inline-block; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 ACF Installation & Conflict Check</h1>
    
    <h2>1. ACF Installation</h2>
    <?php
    if (class_exists('ACF')) {
        echo '<div class="success">✅ ACF class exists</div>';
        
        if (defined('ACF_VERSION')) {
            echo '<div class="info">📦 ACF Version: ' . ACF_VERSION . '</div>';
        }
        
        if (defined('ACF_PRO')) {
            echo '<div class="success">✅ ACF Pro is active</div>';
        } else {
            echo '<div class="error">❌ ACF Pro not detected - you need ACF PRO, not free version!</div>';
        }
        
        if (defined('ACF_PATH')) {
            echo '<div class="info">📁 ACF Path: ' . ACF_PATH . '</div>';
            
            if (file_exists(ACF_PATH)) {
                echo '<div class="success">✅ ACF directory exists</div>';
            } else {
                echo '<div class="error">❌ ACF directory not found!</div>';
            }
        }
        
    } else {
        echo '<div class="error">❌ ACF is NOT installed or activated!</div>';
        echo '<div class="warning">You need to install Advanced Custom Fields PRO plugin.</div>';
    }
    ?>
    
    <h2>2. Required Functions</h2>
    <?php
    $functions = array(
        'acf_get_field_groups',
        'acf_get_field_group',
        'acf_update_field_group',
        'acf_add_local_field_group',
        'acf_import_field_group',
    );
    
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo '<div class="success">✅ ' . $func . '()</div>';
        } else {
            echo '<div class="error">❌ ' . $func . '() - MISSING!</div>';
        }
    }
    ?>
    
    <h2>3. Database Capabilities</h2>
    <?php
    $current_user = wp_get_current_user();
    echo '<div class="info">Current User: ' . $current_user->user_login . ' (ID: ' . $current_user->ID . ')</div>';
    
    $caps_to_check = array(
        'manage_options',
        'edit_posts',
        'edit_published_posts',
    );
    
    foreach ($caps_to_check as $cap) {
        if (current_user_can($cap)) {
            echo '<div class="success">✅ Has capability: ' . $cap . '</div>';
        } else {
            echo '<div class="error">❌ Missing capability: ' . $cap . '</div>';
        }
    }
    ?>
    
    <h2>4. Active Plugins</h2>
    <?php
    $active_plugins = get_option('active_plugins');
    
    echo '<div class="info">Total active plugins: ' . count($active_plugins) . '</div>';
    
    $acf_found = false;
    echo '<ul>';
    foreach ($active_plugins as $plugin) {
        echo '<li>' . esc_html($plugin);
        if (strpos($plugin, 'advanced-custom-fields') !== false) {
            echo ' <strong style="color: #46b450;">← ACF Plugin</strong>';
            $acf_found = true;
        }
        echo '</li>';
    }
    echo '</ul>';
    
    if (!$acf_found) {
        echo '<div class="error">❌ ACF Pro plugin not found in active plugins list!</div>';
        echo '<div class="warning">Go to Plugins and activate "Advanced Custom Fields PRO"</div>';
    }
    ?>
    
    <h2>5. Post Type Registration</h2>
    <?php
    $acf_post_types = array('acf-field-group', 'acf-field');
    
    foreach ($acf_post_types as $pt) {
        if (post_type_exists($pt)) {
            echo '<div class="success">✅ Post type registered: ' . $pt . '</div>';
            
            $pt_obj = get_post_type_object($pt);
            if ($pt_obj) {
                echo '<div class="info">Show UI: ' . ($pt_obj->show_ui ? 'Yes' : 'No') . '</div>';
                echo '<div class="info">Public: ' . ($pt_obj->public ? 'Yes' : 'No') . '</div>';
            }
        } else {
            echo '<div class="error">❌ Post type NOT registered: ' . $pt . '</div>';
        }
    }
    ?>
    
    <h2>6. Admin Menu Check</h2>
    <?php
    global $menu, $submenu;
    
    $found_acf_menu = false;
    if (is_array($menu)) {
        foreach ($menu as $item) {
            if (isset($item[0]) && strpos(strtolower($item[0]), 'custom fields') !== false) {
                $found_acf_menu = true;
                echo '<div class="success">✅ Found "Custom Fields" menu</div>';
                break;
            }
        }
    }
    
    if (!$found_acf_menu) {
        echo '<div class="error">❌ "Custom Fields" menu not found in admin</div>';
    }
    ?>
    
    <h2>7. Deactivate SCN Plugin Filter</h2>
    <div class="warning">
        <p><strong>TEST:</strong> The SCN plugin might be interfering with ACF.</p>
        <p>Try this:</p>
        <ol>
            <li>Go to <a href="<?php echo admin_url('plugins.php'); ?>" target="_blank">Plugins</a></li>
            <li>Deactivate "SCN Membership" plugin temporarily</li>
            <li>Go to <a href="<?php echo admin_url('edit.php?post_type=acf-field-group'); ?>" target="_blank">Custom Fields</a></li>
            <li>Try creating a new field group</li>
            <li>Does it work now?</li>
        </ol>
        <p>If YES = conflict with SCN plugin. If NO = ACF installation issue.</p>
    </div>
    
    <h2>8. Diagnosis</h2>
    <?php
    if (!class_exists('ACF')) {
        echo '<div class="error"><strong>PROBLEM:</strong> ACF is not installed or not activated properly.</div>';
        echo '<div class="info"><strong>FIX:</strong> Go to Plugins and make sure "Advanced Custom Fields PRO" is activated.</div>';
    } elseif (!defined('ACF_PRO')) {
        echo '<div class="error"><strong>PROBLEM:</strong> You have ACF Free, but need ACF PRO.</div>';
        echo '<div class="info"><strong>FIX:</strong> Install and activate ACF PRO (premium version).</div>';
    } else {
        echo '<div class="warning"><strong>POSSIBLE PROBLEM:</strong> Plugin conflict or permissions issue.</div>';
        echo '<div class="info"><strong>NEXT STEP:</strong> Try the deactivation test in section 7 above.</div>';
    }
    ?>
    
    <h2>9. Quick Links</h2>
    <p>
        <a href="<?php echo admin_url('plugins.php'); ?>" class="button">Plugins</a>
        <a href="<?php echo admin_url('edit.php?post_type=acf-field-group'); ?>" class="button">Custom Fields</a>
        <a href="<?php echo admin_url('post-new.php?post_type=acf-field-group'); ?>" class="button">Add New Field Group</a>
    </p>
</div>
</body>
</html>

