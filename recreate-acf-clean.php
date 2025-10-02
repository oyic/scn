<?php
/**
 * Recreate ACF Field Groups in Database ONLY
 * No JSON interference - pure database creation
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

if (!class_exists('ACF')) {
    wp_die('ACF is not active!');
}

// DISABLE JSON LOADING TEMPORARILY
add_filter('acf/settings/load_json', function($paths) {
    return array(); // Empty array = no JSON loading
}, 999);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Recreate ACF Field Groups</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; max-width: 900px; margin: 0 auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #46b450; background: #ecf7ed; padding: 15px; margin: 10px 0; border-left: 4px solid #46b450; }
        .error { color: #dc3232; background: #fbeaea; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3232; }
        .info { color: #0073aa; background: #e5f5fa; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
        .button { background: #0073aa; color: white; padding: 15px 30px; border: none; cursor: pointer; font-size: 16px; border-radius: 4px; }
        .button:hover { background: #005a87; }
        .big-button { background: #46b450; font-size: 18px; padding: 20px 40px; margin: 20px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔄 Recreate ACF Field Groups (Database Only)</h1>
    
    <?php
    if (isset($_POST['recreate'])) {
        echo '<h2>Recreating Field Groups...</h2>';
        
        global $wpdb;
        
        // DELETE ALL existing ACF field groups and fields
        echo '<div class="info">🗑️ Deleting all existing ACF field groups...</div>';
        
        $existing_groups = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'acf-field-group'"
        );
        
        foreach ($existing_groups as $post_id) {
            wp_delete_post($post_id, true);
        }
        
        $existing_fields = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'acf-field'"
        );
        
        foreach ($existing_fields as $post_id) {
            wp_delete_post($post_id, true);
        }
        
        echo '<div class="success">✅ Cleared ' . count($existing_groups) . ' field groups and ' . count($existing_fields) . ' fields</div>';
        
        // Load JSON files
        $json_path = __DIR__ . '/acf-json';
        $json_files = glob($json_path . '/*.json');
        
        foreach ($json_files as $file) {
            $filename = basename($file);
            echo "<hr><h3>📄 Processing: $filename</h3>";
            
            $field_group = json_decode(file_get_contents($file), true);
            
            if (!$field_group) {
                echo '<div class="error">❌ Failed to parse JSON</div>';
                continue;
            }
            
            echo '<div class="info">📋 Title: ' . $field_group['title'] . '</div>';
            echo '<div class="info">🔑 Key: ' . $field_group['key'] . '</div>';
            
            // Use ACF's own import function
            if (function_exists('acf_import_field_group')) {
                echo '<div class="info">⚙️ Using acf_import_field_group()...</div>';
                
                $result = acf_import_field_group($field_group);
                
                if ($result && isset($result['ID'])) {
                    echo '<div class="success">✅ Successfully imported! Database ID: ' . $result['ID'] . '</div>';
                    echo '<div class="info">🔗 <a href="' . admin_url('post.php?post=' . $result['ID'] . '&action=edit') . '" target="_blank">Edit this field group →</a></div>';
                } else {
                    echo '<div class="error">❌ Import failed - trying alternative method...</div>';
                    
                    // Alternative: Use acf_update_field_group
                    unset($field_group['ID']);
                    $result = acf_update_field_group($field_group);
                    
                    if ($result && isset($result['ID'])) {
                        echo '<div class="success">✅ Successfully imported (alternative method)! Database ID: ' . $result['ID'] . '</div>';
                    } else {
                        echo '<div class="error">❌ Both import methods failed</div>';
                    }
                }
            } else {
                echo '<div class="error">❌ acf_import_field_group() function not found!</div>';
            }
        }
        
        echo '<hr>';
        echo '<div class="success" style="font-size: 18px;"><strong>✅ Recreation Complete!</strong></div>';
        echo '<div class="info"><strong>IMPORTANT:</strong> The field groups are now in the database ONLY. You can edit them in ACF UI.</div>';
        echo '<p><a href="' . admin_url('edit.php?post_type=acf-field-group') . '" class="button big-button">Open Custom Fields →</a></p>';
        
    } else {
        ?>
        <div class="info">
            <h3>ℹ️ What this does:</h3>
            <ol>
                <li><strong>Disables JSON loading</strong> (temporarily)</li>
                <li><strong>Deletes</strong> all existing ACF field groups from database</li>
                <li><strong>Imports</strong> field groups from JSON files into database</li>
                <li><strong>Makes them editable</strong> in the ACF UI</li>
            </ol>
        </div>
        
        <div class="info">
            <h3>📁 Found JSON Files:</h3>
            <?php
            $json_path = __DIR__ . '/acf-json';
            $json_files = glob($json_path . '/*.json');
            
            echo '<ul>';
            foreach ($json_files as $file) {
                $json = json_decode(file_get_contents($file), true);
                if ($json) {
                    echo '<li><strong>' . esc_html($json['title']) . '</strong> (' . esc_html($json['key']) . ')</li>';
                }
            }
            echo '</ul>';
            echo '<p>Total: ' . count($json_files) . ' field groups</p>';
            ?>
        </div>
        
        <form method="post">
            <button type="submit" name="recreate" class="button big-button">🔄 Recreate All Field Groups</button>
        </form>
        <?php
    }
    ?>
</div>
</body>
</html>

