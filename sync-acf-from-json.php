<?php
/**
 * Sync ACF Field Groups from JSON to Database
 * This makes them editable in the ACF UI
 * 
 * Access: /wp-content/plugins/scn-membership/sync-acf-from-json.php
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

if (!class_exists('ACF')) {
    wp_die('ACF Pro is not installed or activated.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync ACF from JSON</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        .info { color: #0073aa; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .button:hover { background: #005a87; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Sync ACF Field Groups from JSON to Database</h1>
    
    <?php
    $json_path = __DIR__ . '/acf-json';
    
    if (!is_dir($json_path)) {
        echo '<p class="error">❌ acf-json directory not found!</p>';
        exit;
    }
    
    $json_files = glob($json_path . '/*.json');
    
    if (empty($json_files)) {
        echo '<p class="error">❌ No JSON files found!</p>';
        exit;
    }
    
    echo '<p class="success">✅ Found ' . count($json_files) . ' JSON files</p>';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync'])) {
        echo '<h2>Syncing Field Groups...</h2>';
        
        foreach ($json_files as $file) {
            $filename = basename($file);
            echo '<p><strong>Processing: ' . $filename . '</strong></p>';
            
            // Read and decode JSON
            $json = json_decode(file_get_contents($file), true);
            
            if (!$json || !isset($json['key'])) {
                echo '<p class="error">❌ Invalid JSON format in ' . $filename . '</p>';
                continue;
            }
            
            // Check if field group exists in database
            $post = acf_get_field_group_post($json['key']);
            
            if ($post) {
                echo '<p class="info">ℹ️ Field group exists in database (ID: ' . $post->ID . ')</p>';
                
                // Update it
                $json['ID'] = $post->ID;
                $result = acf_update_field_group($json);
                
                echo '<p class="success">✅ Updated: ' . $json['title'] . '</p>';
            } else {
                echo '<p class="info">ℹ️ Creating new field group in database</p>';
                
                // Create new field group post
                $post_data = array(
                    'post_title'   => $json['title'],
                    'post_type'    => 'acf-field-group',
                    'post_status'  => 'publish',
                    'post_name'    => $json['key'],
                );
                
                $post_id = wp_insert_post($post_data);
                
                if (is_wp_error($post_id)) {
                    echo '<p class="error">❌ Failed to create post: ' . $post_id->get_error_message() . '</p>';
                    continue;
                }
                
                echo '<p class="info">ℹ️ Created post ID: ' . $post_id . '</p>';
                
                // Update with full field group data
                $json['ID'] = $post_id;
                
                // Save the field group settings
                foreach ($json as $key => $value) {
                    if ($key !== 'fields') {
                        update_post_meta($post_id, $key, $value);
                    }
                }
                
                // Import fields
                if (isset($json['fields']) && is_array($json['fields'])) {
                    foreach ($json['fields'] as $field) {
                        $field['parent'] = $json['key'];
                        acf_update_field($field);
                    }
                    echo '<p class="info">ℹ️ Imported ' . count($json['fields']) . ' fields</p>';
                }
                
                echo '<p class="success">✅ Created: ' . $json['title'] . ' (ID: ' . $post_id . ')</p>';
            }
        }
        
        echo '<h3 class="success">✅ Sync Complete!</h3>';
        echo '<p><a href="' . admin_url('edit.php?post_type=acf-field-group') . '" class="button">View Field Groups in ACF</a></p>';
        
    } else {
        echo '<h2>Available Field Groups:</h2>';
        echo '<table>';
        echo '<tr><th>Field Group</th><th>Key</th><th>Status</th></tr>';
        
        foreach ($json_files as $file) {
            $json = json_decode(file_get_contents($file), true);
            if ($json && isset($json['key'])) {
                $post = acf_get_field_group_post($json['key']);
                $status = $post ? '<span class="success">In Database (ID: ' . $post->ID . ')</span>' : '<span class="error">Not in Database</span>';
                
                echo '<tr>';
                echo '<td><strong>' . esc_html($json['title']) . '</strong></td>';
                echo '<td>' . esc_html($json['key']) . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }
        }
        
        echo '</table>';
        
        echo '<form method="post">';
        echo '<button type="submit" name="sync" class="button">Sync All Field Groups to Database</button>';
        echo '</form>';
    }
    ?>
    
    <hr>
    <p><a href="<?php echo admin_url(); ?>">← Back to WordPress Admin</a></p>
</body>
</html>

