<?php
/**
 * Fix ACF Field Group Metadata
 * Ensures all required metadata is properly stored
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

if (!class_exists('ACF')) {
    wp_die('ACF is not active!');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix ACF Metadata</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .success { color: #46b450; background: #ecf7ed; padding: 10px; margin: 5px 0; }
        .error { color: #dc3232; background: #fbeaea; padding: 10px; margin: 5px 0; }
        .info { color: #0073aa; background: #e5f5fa; padding: 10px; margin: 5px 0; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
        .button { background: #0073aa; color: white; padding: 12px 24px; border: none; cursor: pointer; font-size: 16px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Fix ACF Field Group Metadata</h1>
    
    <?php
    global $wpdb;
    
    // Get all ACF field group posts
    $field_groups = $wpdb->get_results(
        "SELECT ID, post_title, post_name 
         FROM {$wpdb->posts} 
         WHERE post_type = 'acf-field-group' 
         AND post_status = 'publish'"
    );
    
    if (empty($field_groups)) {
        echo '<div class="error">❌ No field groups found in database!</div>';
        echo '<p><a href="force-import-acf.php">Go back and import them first</a></p>';
        exit;
    }
    
    echo '<div class="success">Found ' . count($field_groups) . ' field groups in database</div>';
    
    if (isset($_POST['fix_metadata'])) {
        echo '<h2>Fixing Metadata...</h2>';
        
        $json_path = __DIR__ . '/acf-json';
        
        foreach ($field_groups as $fg) {
            echo "<hr><h3>Field Group: {$fg->post_title} (ID: {$fg->ID})</h3>";
            
            // Find corresponding JSON file
            $json_file = $json_path . '/' . $fg->post_name . '.json';
            
            if (!file_exists($json_file)) {
                echo "<div class='error'>❌ JSON file not found: " . basename($json_file) . "</div>";
                continue;
            }
            
            echo "<div class='info'>📄 Found JSON: " . basename($json_file) . "</div>";
            
            $json = json_decode(file_get_contents($json_file), true);
            
            if (!$json) {
                echo "<div class='error'>❌ Failed to parse JSON</div>";
                continue;
            }
            
            // Delete ALL existing meta for this field group
            $wpdb->delete($wpdb->postmeta, array('post_id' => $fg->ID));
            echo "<div class='info'>🗑️ Cleared existing metadata</div>";
            
            // Required ACF field group settings
            $required_fields = array(
                'key' => $json['key'],
                'title' => $json['title'],
                'fields' => $json['fields'] ?? array(),
                'location' => $json['location'] ?? array(),
                'menu_order' => $json['menu_order'] ?? 0,
                'position' => $json['position'] ?? 'normal',
                'style' => $json['style'] ?? 'default',
                'label_placement' => $json['label_placement'] ?? 'top',
                'instruction_placement' => $json['instruction_placement'] ?? 'label',
                'hide_on_screen' => $json['hide_on_screen'] ?? '',
                'active' => true,
                'description' => ''
            );
            
            // Add all metadata
            foreach ($required_fields as $meta_key => $meta_value) {
                update_post_meta($fg->ID, $meta_key, $meta_value);
                echo "<div class='info'>💾 Set meta: $meta_key</div>";
            }
            
            echo "<div class='success'>✅ Updated metadata for: {$fg->post_title}</div>";
        }
        
        echo '<hr><div class="success"><h2>✅ Metadata Fixed!</h2></div>';
        echo '<p><a href="' . admin_url('edit.php?post_type=acf-field-group') . '" style="font-size: 18px;">→ Try editing field groups now</a></p>';
        
    } else {
        echo '<h2>Current Database Status:</h2>';
        
        foreach ($field_groups as $fg) {
            echo "<h3>{$fg->post_title} (ID: {$fg->ID})</h3>";
            
            $meta = get_post_meta($fg->ID);
            
            echo '<p><strong>Current metadata keys:</strong> ';
            echo implode(', ', array_keys($meta));
            echo '</p>';
            
            // Check for required keys
            $has_key = isset($meta['key']);
            $has_location = isset($meta['location']);
            $has_fields = isset($meta['fields']);
            
            echo '<p>';
            echo $has_key ? '✅ Has "key" meta' : '❌ Missing "key" meta';
            echo ' | ';
            echo $has_location ? '✅ Has "location" meta' : '❌ Missing "location" meta';
            echo ' | ';
            echo $has_fields ? '✅ Has "fields" meta' : '❌ Missing "fields" meta';
            echo '</p>';
            
            if ($has_key) {
                echo '<p>Key value: ' . esc_html($meta['key'][0]) . '</p>';
            }
            
            echo '<details><summary>Full metadata</summary><pre>';
            print_r($meta);
            echo '</pre></details>';
            
            echo '<hr>';
        }
        
        echo '<form method="post">';
        echo '<button type="submit" name="fix_metadata" class="button">Fix All Field Group Metadata</button>';
        echo '</form>';
    }
    ?>
</div>
</body>
</html>

