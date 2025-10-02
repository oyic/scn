<?php
/**
 * Check ACF Field Groups Status
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('You do not have permission.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ACF Status Check</title>
    <style>
        body { font-family: monospace; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        .info { color: #0073aa; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f5f5f5; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>ACF Field Groups Status Check</h1>
    
    <h2>1. ACF Available?</h2>
    <?php if (class_exists('ACF')): ?>
        <p class="success">✅ ACF is active</p>
        <p>ACF Version: <?php echo defined('ACF_VERSION') ? ACF_VERSION : 'Unknown'; ?></p>
        <p>ACF Pro: <?php echo defined('ACF_PRO') ? '✅ Yes' : '❌ No'; ?></p>
    <?php else: ?>
        <p class="error">❌ ACF is not active!</p>
    <?php endif; ?>
    
    <h2>2. JSON Files Check</h2>
    <?php
    $json_path = __DIR__ . '/acf-json';
    $json_files = glob($json_path . '/*.json');
    
    echo '<p class="info">Found ' . count($json_files) . ' JSON files in: ' . $json_path . '</p>';
    
    if (!empty($json_files)) {
        echo '<table>';
        echo '<tr><th>File</th><th>Valid JSON?</th><th>Has Key?</th><th>Title</th></tr>';
        foreach ($json_files as $file) {
            $content = file_get_contents($file);
            $json = json_decode($content, true);
            $valid = $json !== null;
            $has_key = isset($json['key']);
            
            echo '<tr>';
            echo '<td>' . basename($file) . '</td>';
            echo '<td>' . ($valid ? '✅' : '❌ ' . json_last_error_msg()) . '</td>';
            echo '<td>' . ($has_key ? '✅ ' . $json['key'] : '❌') . '</td>';
            echo '<td>' . ($json['title'] ?? 'N/A') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    ?>
    
    <h2>3. Database Check - ACF Field Group Posts</h2>
    <?php
    global $wpdb;
    
    $field_groups = $wpdb->get_results(
        "SELECT ID, post_title, post_name, post_status 
         FROM {$wpdb->posts} 
         WHERE post_type = 'acf-field-group'
         ORDER BY ID DESC"
    );
    
    if (empty($field_groups)) {
        echo '<p class="error">❌ No ACF field groups found in database!</p>';
    } else {
        echo '<p class="success">✅ Found ' . count($field_groups) . ' field groups in database</p>';
        echo '<table>';
        echo '<tr><th>ID</th><th>Title</th><th>Post Name (Key)</th><th>Status</th></tr>';
        foreach ($field_groups as $fg) {
            echo '<tr>';
            echo '<td>' . $fg->ID . '</td>';
            echo '<td>' . esc_html($fg->post_title) . '</td>';
            echo '<td>' . esc_html($fg->post_name) . '</td>';
            echo '<td>' . $fg->post_status . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    ?>
    
    <h2>4. ACF Functions Check</h2>
    <?php
    if (function_exists('acf_get_field_groups')) {
        $acf_field_groups = acf_get_field_groups();
        echo '<p class="info">acf_get_field_groups() returned ' . count($acf_field_groups) . ' field groups</p>';
        
        if (!empty($acf_field_groups)) {
            echo '<table>';
            echo '<tr><th>Key</th><th>Title</th><th>Local?</th></tr>';
            foreach ($acf_field_groups as $fg) {
                $is_local = !isset($fg['ID']) || empty($fg['ID']);
                echo '<tr>';
                echo '<td>' . esc_html($fg['key']) . '</td>';
                echo '<td>' . esc_html($fg['title']) . '</td>';
                echo '<td>' . ($is_local ? '📁 Local JSON' : '💾 Database (ID: ' . $fg['ID'] . ')') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } else {
        echo '<p class="error">❌ acf_get_field_groups() function not found!</p>';
    }
    ?>
    
    <h2>5. Load Paths Check</h2>
    <?php
    if (function_exists('acf_get_setting')) {
        $load_json = acf_get_setting('load_json');
        echo '<p><strong>ACF Load JSON paths:</strong></p>';
        echo '<pre>';
        print_r($load_json);
        echo '</pre>';
        
        $save_json = acf_get_setting('save_json');
        echo '<p><strong>ACF Save JSON path:</strong></p>';
        echo '<pre>' . $save_json . '</pre>';
    }
    ?>
    
    <h2>6. Post Meta for Field Groups</h2>
    <?php
    if (!empty($field_groups)) {
        foreach ($field_groups as $fg) {
            echo '<h3>Field Group: ' . esc_html($fg->post_title) . ' (ID: ' . $fg->ID . ')</h3>';
            $meta = get_post_meta($fg->ID);
            echo '<pre>';
            print_r($meta);
            echo '</pre>';
        }
    }
    ?>
    
    <hr>
    <p><a href="<?php echo admin_url('edit.php?post_type=acf-field-group'); ?>">View Field Groups in ACF</a></p>
    <p><a href="<?php echo admin_url(); ?>">Back to Admin</a></p>
</body>
</html>

