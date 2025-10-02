<?php
/**
 * Diagnose ACF Edit Issue
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ACF Edit Diagnosis</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; font-size: 13px; }
        .container { background: white; padding: 20px; max-width: 1400px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 12px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        .info { color: #0073aa; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; font-size: 11px; }
        .test-box { background: #e5f5fa; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 ACF Edit Diagnosis</h1>
    
    <?php
    global $wpdb;
    
    // Get all field group posts
    $field_groups = $wpdb->get_results(
        "SELECT ID, post_title, post_name, post_status, post_type
         FROM {$wpdb->posts} 
         WHERE post_type = 'acf-field-group'
         ORDER BY ID DESC"
    );
    
    echo "<h2>1. Database Posts (Found: " . count($field_groups) . ")</h2>";
    
    if (empty($field_groups)) {
        echo '<p class="error">❌ NO FIELD GROUPS IN DATABASE!</p>';
        echo '<p>You need to create them first. Go to Custom Fields > Add New</p>';
    } else {
        echo '<table>';
        echo '<tr><th>ID</th><th>Title</th><th>Post Name</th><th>Status</th><th>Edit URL</th><th>Test Load</th></tr>';
        
        foreach ($field_groups as $fg) {
            $edit_url = admin_url('post.php?post=' . $fg->ID . '&action=edit');
            
            echo '<tr>';
            echo '<td>' . $fg->ID . '</td>';
            echo '<td>' . esc_html($fg->post_title) . '</td>';
            echo '<td>' . esc_html($fg->post_name) . '</td>';
            echo '<td>' . $fg->post_status . '</td>';
            echo '<td><a href="' . $edit_url . '" target="_blank">Edit →</a></td>';
            
            // Test if ACF can load this field group
            if (function_exists('acf_get_field_group')) {
                $loaded = acf_get_field_group($fg->ID);
                if ($loaded) {
                    echo '<td class="success">✅ Loads OK</td>';
                } else {
                    echo '<td class="error">❌ Cannot load</td>';
                }
            } else {
                echo '<td class="error">Function missing</td>';
            }
            
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo "<h2>2. Post Meta Check</h2>";
    
    foreach ($field_groups as $fg) {
        echo "<h3>Field Group: {$fg->post_title} (ID: {$fg->ID})</h3>";
        
        $all_meta = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
            $fg->ID
        ));
        
        if (empty($all_meta)) {
            echo '<p class="error">❌ NO META DATA AT ALL!</p>';
        } else {
            echo '<p class="info">Found ' . count($all_meta) . ' meta entries</p>';
            echo '<table>';
            echo '<tr><th>Meta Key</th><th>Meta Value (truncated)</th></tr>';
            foreach ($all_meta as $meta) {
                $value = $meta->meta_value;
                if (strlen($value) > 100) {
                    $value = substr($value, 0, 100) . '...';
                }
                echo '<tr><td>' . esc_html($meta->meta_key) . '</td><td>' . esc_html($value) . '</td></tr>';
            }
            echo '</table>';
        }
        
        // Check for specific required keys
        $required = array('key', 'location', 'fields');
        $missing = array();
        foreach ($required as $key) {
            if (!metadata_exists('post', $fg->ID, $key)) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            echo '<p class="error">❌ Missing required meta keys: ' . implode(', ', $missing) . '</p>';
        } else {
            echo '<p class="success">✅ Has all required meta keys</p>';
        }
    }
    
    echo "<h2>3. ACF Functions Test</h2>";
    
    if (function_exists('acf_get_field_groups')) {
        $acf_groups = acf_get_field_groups();
        echo '<p class="info">acf_get_field_groups() returned: ' . count($acf_groups) . ' groups</p>';
        
        if (!empty($acf_groups)) {
            echo '<table>';
            echo '<tr><th>Key</th><th>Title</th><th>Has ID?</th><th>ID Value</th></tr>';
            foreach ($acf_groups as $group) {
                $has_id = isset($group['ID']);
                echo '<tr>';
                echo '<td>' . esc_html($group['key']) . '</td>';
                echo '<td>' . esc_html($group['title']) . '</td>';
                echo '<td>' . ($has_id ? '✅' : '❌') . '</td>';
                echo '<td>' . ($has_id ? $group['ID'] : 'N/A') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
    
    echo "<h2>4. Manual Edit Test</h2>";
    echo '<div class="test-box">';
    echo '<p><strong>Try creating a NEW field group manually:</strong></p>';
    echo '<ol>';
    echo '<li><a href="' . admin_url('post-new.php?post_type=acf-field-group') . '" target="_blank">Click here to create a new field group</a></li>';
    echo '<li>Give it a simple name like "Test Group"</li>';
    echo '<li>Add one simple text field</li>';
    echo '<li>Save it</li>';
    echo '<li>Try to edit it again</li>';
    echo '</ol>';
    echo '<p>Does the manually created one work? If YES, the database is fine and the import method is the problem.</p>';
    echo '</div>';
    
    echo "<h2>5. Recommended Fix</h2>";
    
    if (!empty($field_groups)) {
        echo '<div class="test-box">';
        echo '<p class="error"><strong>Diagnosis:</strong> Field groups exist in database but ACF cannot load them properly.</p>';
        echo '<p><strong>Solution:</strong> Delete all imported field groups and create them manually in ACF UI, or fix the import process.</p>';
        echo '</div>';
    }
    
    ?>
    
    <h2>6. Next Steps</h2>
    <ol>
        <li>Review the data above</li>
        <li>Try creating a test field group manually (link in section 4)</li>
        <li>If manual creation works, delete imported ones and recreate in UI</li>
        <li>Copy the field definitions from JSON files to manually recreate them</li>
    </ol>
    
</div>
</body>
</html>

