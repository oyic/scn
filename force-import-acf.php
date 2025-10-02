<?php
/**
 * Force Import ACF Field Groups to Database
 * This will make them editable in the ACF UI
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
    <title>Force Import ACF</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; max-width: 900px; margin: 0 auto; }
        .success { color: #46b450; background: #ecf7ed; padding: 10px; margin: 5px 0; }
        .error { color: #dc3232; background: #fbeaea; padding: 10px; margin: 5px 0; }
        .info { color: #0073aa; background: #e5f5fa; padding: 10px; margin: 5px 0; }
        .button { background: #0073aa; color: white; padding: 12px 24px; border: none; cursor: pointer; font-size: 16px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Force Import ACF Field Groups</h1>
    
    <?php
    if (isset($_POST['force_import'])) {
        echo '<h2>Importing...</h2>';
        
        $json_path = __DIR__ . '/acf-json';
        $json_files = glob($json_path . '/*.json');
        
        foreach ($json_files as $file) {
            $filename = basename($file);
            echo "<div class='info'>📄 Processing: $filename</div>";
            
            $json = json_decode(file_get_contents($file), true);
            
            if (!$json) {
                echo "<div class='error'>❌ Failed to parse JSON</div>";
                continue;
            }
            
            $key = $json['key'];
            $title = $json['title'];
            
            // Delete existing field group with same key
            $existing_posts = get_posts(array(
                'post_type' => 'acf-field-group',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'key',
                        'value' => $key,
                        'compare' => '='
                    )
                )
            ));
            
            if (!empty($existing_posts)) {
                foreach ($existing_posts as $post) {
                    wp_delete_post($post->ID, true);
                    echo "<div class='info'>🗑️ Deleted existing field group (ID: {$post->ID})</div>";
                }
            }
            
            // Also check by post_name
            global $wpdb;
            $duplicate = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'acf-field-group'",
                $key
            ));
            
            if ($duplicate) {
                wp_delete_post($duplicate, true);
                echo "<div class='info'>🗑️ Deleted duplicate by post_name (ID: {$duplicate})</div>";
            }
            
            // Import using ACF's function
            if (function_exists('acf_import_field_group')) {
                $result = acf_import_field_group($json);
                if ($result) {
                    echo "<div class='success'>✅ Imported via acf_import_field_group: $title</div>";
                    continue;
                }
            }
            
            // Fallback: Manual import
            echo "<div class='info'>⚙️ Using manual import method...</div>";
            
            // Create the post
            $post_id = wp_insert_post(array(
                'post_title'   => $title,
                'post_name'    => $key,
                'post_type'    => 'acf-field-group',
                'post_status'  => 'publish',
                'post_content' => ''
            ));
            
            if (is_wp_error($post_id)) {
                echo "<div class='error'>❌ Failed to create post: " . $post_id->get_error_message() . "</div>";
                continue;
            }
            
            echo "<div class='success'>✅ Created post ID: $post_id</div>";
            
            // Add the field group data as post meta
            $field_group_data = $json;
            unset($field_group_data['fields']); // Don't store fields in the field group meta
            
            foreach ($field_group_data as $meta_key => $meta_value) {
                update_post_meta($post_id, $meta_key, $meta_value);
            }
            
            echo "<div class='info'>💾 Saved field group metadata</div>";
            
            // Import fields if they exist
            if (isset($json['fields']) && is_array($json['fields'])) {
                $field_count = 0;
                foreach ($json['fields'] as $field) {
                    $field['parent'] = $key;
                    
                    if (function_exists('acf_update_field')) {
                        acf_update_field($field);
                        $field_count++;
                    }
                }
                echo "<div class='success'>✅ Imported $field_count fields</div>";
            }
            
            echo "<div class='success'>✅ Successfully imported: $title (ID: $post_id)</div>";
            echo "<hr>";
        }
        
        echo '<div class="success"><h2>✅ Import Complete!</h2></div>';
        echo '<p><a href="' . admin_url('edit.php?post_type=acf-field-group') . '" style="font-size: 18px;">→ Go to Custom Fields</a></p>';
        
    } else {
        $json_path = __DIR__ . '/acf-json';
        $json_files = glob($json_path . '/*.json');
        
        echo '<p>This will import all JSON field groups into the database, making them editable in ACF.</p>';
        echo '<p><strong>Found ' . count($json_files) . ' JSON files:</strong></p>';
        echo '<ul>';
        foreach ($json_files as $file) {
            $json = json_decode(file_get_contents($file), true);
            if ($json) {
                echo '<li>' . esc_html($json['title']) . ' (' . esc_html($json['key']) . ')</li>';
            }
        }
        echo '</ul>';
        
        echo '<form method="post">';
        echo '<button type="submit" name="force_import" class="button">Force Import All Field Groups</button>';
        echo '</form>';
    }
    ?>
</div>
</body>
</html>

