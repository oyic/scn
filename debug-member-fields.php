<?php
/**
 * Debug Member ACF Fields
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Member Fields</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; }
        .info { color: #0073aa; background: #e5f5fa; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Member Post Type ACF Fields</h1>
    
    <?php
    if (function_exists('acf_get_field_groups')) {
        $field_groups = acf_get_field_groups(['post_type' => 'member']);
        
        if (empty($field_groups)) {
            echo '<div class="info">❌ No ACF field groups found for "member" post type</div>';
            
            // Check all field groups
            $all_groups = acf_get_field_groups();
            echo '<h2>All ACF Field Groups:</h2>';
            echo '<ul>';
            foreach ($all_groups as $group) {
                echo '<li><strong>' . $group['title'] . '</strong> (Key: ' . $group['key'] . ')';
                if (isset($group['location'])) {
                    echo '<br>Location: <pre>' . print_r($group['location'], true) . '</pre>';
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="info">✅ Found ' . count($field_groups) . ' field group(s) for member post type</div>';
            
            foreach ($field_groups as $group) {
                echo '<h2>' . $group['title'] . ' (' . $group['key'] . ')</h2>';
                
                $fields = acf_get_fields($group['key']);
                if ($fields) {
                    echo '<table border="1" cellpadding="5">';
                    echo '<tr><th>Label</th><th>Name</th><th>Type</th><th>Key</th></tr>';
                    
                    foreach ($fields as $field) {
                        echo '<tr>';
                        echo '<td>' . $field['label'] . '</td>';
                        echo '<td><strong>' . $field['name'] . '</strong></td>';
                        echo '<td>' . $field['type'] . '</td>';
                        echo '<td>' . $field['key'] . '</td>';
                        echo '</tr>';
                        
                        // If it's a group, show sub_fields
                        if ($field['type'] === 'group' && isset($field['sub_fields'])) {
                            foreach ($field['sub_fields'] as $sub) {
                                echo '<tr style="background:#f9f9f9;">';
                                echo '<td>&nbsp;&nbsp;↳ ' . $sub['label'] . '</td>';
                                echo '<td><strong>' . $sub['name'] . '</strong></td>';
                                echo '<td>' . $sub['type'] . '</td>';
                                echo '<td>' . $sub['key'] . '</td>';
                                echo '</tr>';
                            }
                        }
                    }
                    echo '</table>';
                }
            }
        }
    } else {
        echo '<div class="info">❌ ACF is not active</div>';
    }
    ?>
    
    <h2>JavaScript Field Selector Test</h2>
    <div class="info">
        <p>Based on the fields above, the JavaScript should look for:</p>
        <p>For direct fields: <code>input[name="acf[field_scn_first_name]"]</code></p>
        <p>For group fields: <code>input[name="acf[field_basic_info][first_name]"]</code></p>
    </div>
</body>
</html>

