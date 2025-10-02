<?php
/**
 * Check ALL sources of ACF interference
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Check ALL ACF Interference</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; font-size: 12px; }
        .container { background: white; padding: 20px; max-width: 1400px; margin: 0 auto; }
        .success { color: #46b450; background: #ecf7ed; padding: 10px; margin: 5px 0; }
        .error { color: #dc3232; background: #fbeaea; padding: 10px; margin: 5px 0; }
        .warning { color: #f56e28; background: #fcf3e8; padding: 10px; margin: 5px 0; }
        .info { color: #0073aa; background: #e5f5fa; padding: 10px; margin: 5px 0; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; font-size: 11px; max-height: 300px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { padding: 5px; border: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Complete ACF Interference Check</h1>
    
    <h2>1. Active Plugins</h2>
    <?php
    $active_plugins = get_option('active_plugins');
    echo '<table>';
    echo '<tr><th>Plugin</th><th>ACF Related?</th></tr>';
    foreach ($active_plugins as $plugin) {
        $is_acf_related = (strpos($plugin, 'acf') !== false || strpos($plugin, 'advanced-custom-fields') !== false);
        echo '<tr>';
        echo '<td>' . esc_html($plugin) . '</td>';
        echo '<td>' . ($is_acf_related ? '<strong style="color:#46b450;">ACF Plugin</strong>' : '') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    ?>
    
    <h2>2. All Registered ACF-Related Hooks</h2>
    <?php
    global $wp_filter;
    
    $acf_hooks = array();
    foreach ($wp_filter as $hook_name => $hook) {
        if (strpos($hook_name, 'acf') !== false || strpos($hook_name, 'acf-field-group') !== false) {
            $acf_hooks[$hook_name] = $hook;
        }
    }
    
    echo '<p>Found ' . count($acf_hooks) . ' ACF-related hooks</p>';
    
    if (!empty($acf_hooks)) {
        echo '<details><summary>Show all ACF hooks</summary>';
        echo '<table>';
        echo '<tr><th>Hook Name</th><th>Callbacks</th></tr>';
        foreach ($acf_hooks as $hook_name => $hook) {
            echo '<tr><td>' . esc_html($hook_name) . '</td><td>';
            
            if (isset($hook->callbacks)) {
                foreach ($hook->callbacks as $priority => $functions) {
                    foreach ($functions as $func) {
                        $callback = is_array($func['function']) ? 
                            (is_object($func['function'][0]) ? get_class($func['function'][0]) : $func['function'][0]) . '::' . $func['function'][1] :
                            $func['function'];
                        echo 'Priority ' . $priority . ': ' . esc_html($callback) . '<br>';
                    }
                }
            }
            
            echo '</td></tr>';
        }
        echo '</table>';
        echo '</details>';
    }
    ?>
    
    <h2>3. Hooks That Might Block ACF Admin</h2>
    <?php
    $critical_hooks = array(
        'admin_init',
        'admin_enqueue_scripts', 
        'save_post',
        'wp_insert_post_data',
        'rest_api_init',
        'rest_pre_dispatch',
        'rest_authentication_errors',
        'current_screen',
    );
    
    echo '<table>';
    echo '<tr><th>Hook</th><th>Callback Count</th><th>Details</th></tr>';
    
    foreach ($critical_hooks as $hook_name) {
        if (isset($wp_filter[$hook_name])) {
            $callbacks = $wp_filter[$hook_name]->callbacks;
            $total = 0;
            $details = array();
            
            foreach ($callbacks as $priority => $functions) {
                $total += count($functions);
                foreach ($functions as $func) {
                    $callback = is_array($func['function']) ? 
                        (is_object($func['function'][0]) ? get_class($func['function'][0]) : $func['function'][0]) . '::' . $func['function'][1] :
                        $func['function'];
                    
                    // Check if it's from SCN plugin
                    if (strpos($callback, 'SCN') !== false || strpos($callback, 'scn') !== false) {
                        $details[] = '<strong style="color:#dc3232;">SCN: ' . $callback . '</strong>';
                    } else {
                        $details[] = $callback;
                    }
                }
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($hook_name) . '</td>';
            echo '<td>' . $total . '</td>';
            echo '<td><small>' . implode(', ', array_slice($details, 0, 3)) . ($total > 3 ? '...' : '') . '</small></td>';
            echo '</tr>';
        } else {
            echo '<tr><td>' . esc_html($hook_name) . '</td><td>0</td><td>-</td></tr>';
        }
    }
    echo '</table>';
    ?>
    
    <h2>4. ACF Settings</h2>
    <?php
    if (function_exists('acf_get_setting')) {
        echo '<table>';
        echo '<tr><th>Setting</th><th>Value</th></tr>';
        
        $settings = array('load_json', 'save_json', 'version', 'pro');
        foreach ($settings as $setting) {
            $value = acf_get_setting($setting);
            echo '<tr><td>' . esc_html($setting) . '</td><td><pre>' . print_r($value, true) . '</pre></td></tr>';
        }
        echo '</table>';
    }
    ?>
    
    <h2>5. Database ACF Posts</h2>
    <?php
    global $wpdb;
    $acf_posts = $wpdb->get_results(
        "SELECT post_type, post_status, COUNT(*) as count 
         FROM {$wpdb->posts} 
         WHERE post_type LIKE 'acf%'
         GROUP BY post_type, post_status"
    );
    
    if (empty($acf_posts)) {
        echo '<div class="success">✅ No ACF posts in database (clean slate)</div>';
    } else {
        echo '<table>';
        echo '<tr><th>Post Type</th><th>Status</th><th>Count</th></tr>';
        foreach ($acf_posts as $row) {
            echo '<tr><td>' . esc_html($row->post_type) . '</td><td>' . esc_html($row->post_status) . '</td><td>' . $row->count . '</td></tr>';
        }
        echo '</table>';
    }
    ?>
    
    <h2>6. Theme Functions Check</h2>
    <?php
    $theme = wp_get_theme();
    $theme_functions = get_stylesheet_directory() . '/functions.php';
    
    echo '<p>Active Theme: <strong>' . $theme->get('Name') . '</strong></p>';
    
    if (file_exists($theme_functions)) {
        $content = file_get_contents($theme_functions);
        if (preg_match('/(acf_|get_field|the_field)/i', $content)) {
            echo '<div class="warning">⚠️ Theme functions.php contains ACF code</div>';
            
            // Show matching lines
            $lines = explode("\n", $content);
            $matches = array();
            foreach ($lines as $num => $line) {
                if (preg_match('/(acf_|get_field|the_field)/i', $line)) {
                    $matches[] = ($num + 1) . ': ' . htmlspecialchars(trim($line));
                }
            }
            
            if (!empty($matches)) {
                echo '<details><summary>Show ACF code in theme</summary><pre>';
                echo implode("\n", array_slice($matches, 0, 20));
                if (count($matches) > 20) echo "\n... and " . (count($matches) - 20) . " more";
                echo '</pre></details>';
            }
        } else {
            echo '<div class="success">✅ Theme functions.php has no ACF code</div>';
        }
    }
    ?>
    
    <h2>7. Recommendations</h2>
    <?php
    $recommendations = array();
    
    // Check for excessive hooks
    if (count($acf_hooks) > 50) {
        $recommendations[] = '<div class="warning">Too many ACF hooks registered (' . count($acf_hooks) . ') - may indicate multiple ACF customizations</div>';
    }
    
    // Check for leftover ACF data
    if (!empty($acf_posts)) {
        $total_acf = array_sum(array_column($acf_posts, 'count'));
        if ($total_acf > 0) {
            $recommendations[] = '<div class="warning">Found ' . $total_acf . ' ACF posts in database. Consider deleting them for a fresh start.</div>';
        }
    }
    
    if (empty($recommendations)) {
        echo '<div class="success">✅ No issues detected</div>';
    } else {
        foreach ($recommendations as $rec) {
            echo $rec;
        }
    }
    ?>
    
    <h2>8. Final Test</h2>
    <div class="info">
        <h3>Try creating a field group now:</h3>
        <p><a href="<?php echo admin_url('post-new.php?post_type=acf-field-group'); ?>" target="_blank" style="background:#46b450;color:white;padding:15px 30px;text-decoration:none;display:inline-block;border-radius:4px;font-size:16px;">Create New ACF Field Group →</a></p>
        
        <h3>If it still doesn't work:</h3>
        <ol>
            <li>Deactivate ALL plugins except ACF Pro</li>
            <li>Switch to a default theme (Twenty Twenty-Five)</li>
            <li>Try creating a field group</li>
            <li>If it works = conflict with SCN plugin or theme</li>
            <li>If it fails = ACF Pro needs complete reinstall</li>
        </ol>
    </div>
</div>
</body>
</html>

