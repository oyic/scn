<?php
/**
 * Check Database for ACF Field Groups - Direct DB Query
 */

require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

?>
<!DOCTYPE html>
<html>
<head>
    <title>ACF Field Groups in Database</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1400px; margin: 0 auto; }
        h1 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #0073aa; color: white; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .highlight { background-color: yellow !important; }
    </style>
</head>
<body>

<h1>ACF Field Groups in Database</h1>

<?php

// Get all ACF field group posts
$field_groups = $wpdb->get_results("
    SELECT ID, post_title, post_name, post_content
    FROM {$wpdb->posts}
    WHERE post_type = 'acf-field-group'
    AND post_status = 'publish'
    ORDER BY menu_order, post_title
");

echo "<h2>Field Groups</h2>";
echo "<table>";
echo "<tr><th>ID</th><th>Title</th><th>Key (post_name)</th><th>Fields</th></tr>";

foreach ($field_groups as $group) {
    // Get fields for this group
    $fields = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_title, post_excerpt, post_content, post_name
        FROM {$wpdb->posts}
        WHERE post_type = 'acf-field'
        AND post_parent = %d
        AND post_status = 'publish'
        ORDER BY menu_order
    ", $group->ID));
    
    $has_member_courses = false;
    $fields_list = '';
    
    foreach ($fields as $field) {
        $field_name = $field->post_excerpt; // ACF stores field name in post_excerpt
        $field_data = maybe_unserialize($field->post_content);
        $field_type = is_array($field_data) && isset($field_data['type']) ? $field_data['type'] : 'unknown';
        
        if ($field_name === 'member_courses') {
            $has_member_courses = true;
            $fields_list .= "<strong style='color: red;'>→ {$field_name} ({$field_type}) ← FOUND!</strong><br>";
        } else {
            $fields_list .= "- {$field_name} ({$field_type})<br>";
        }
        
        // Check for sub_fields
        if (is_array($field_data) && isset($field_data['sub_fields']) && is_array($field_data['sub_fields'])) {
            foreach ($field_data['sub_fields'] as $sub) {
                $sub_name = $sub['name'] ?? 'unknown';
                $sub_type = $sub['type'] ?? 'unknown';
                
                if ($sub_name === 'member_courses') {
                    $has_member_courses = true;
                    $fields_list .= "  <strong style='color: red;'>→ {$sub_name} ({$sub_type}) ← FOUND!</strong><br>";
                } else {
                    $fields_list .= "  └─ {$sub_name} ({$sub_type})<br>";
                }
            }
        }
    }
    
    echo "<tr" . ($has_member_courses ? " class='highlight'" : "") . ">";
    echo "<td>{$group->ID}</td>";
    echo "<td>{$group->post_title}</td>";
    echo "<td>{$group->post_name}</td>";
    echo "<td>{$fields_list}</td>";
    echo "</tr>";
}

echo "</table>";

// Check location rules for field groups
echo "<h2>Field Group Location Rules</h2>";
echo "<table>";
echo "<tr><th>Field Group</th><th>Location Rules</th></tr>";

foreach ($field_groups as $group) {
    $location = get_post_meta($group->ID, 'rule', false);
    
    echo "<tr>";
    echo "<td>{$group->post_title}</td>";
    echo "<td><pre>" . htmlspecialchars(print_r($location, true)) . "</pre></td>";
    echo "</tr>";
}

echo "</table>";

// Check for member_courses in post meta directly
echo "<h2>Member 561 Post Meta (course related)</h2>";
$meta = $wpdb->get_results($wpdb->prepare("
    SELECT meta_key, meta_value
    FROM {$wpdb->postmeta}
    WHERE post_id = %d
    AND (meta_key LIKE '%%course%%' OR meta_key LIKE '%%member_courses%%')
    ORDER BY meta_key
", 561));

echo "<table>";
echo "<tr><th>Meta Key</th><th>Meta Value</th></tr>";
foreach ($meta as $m) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($m->meta_key) . "</td>";
    echo "<td><pre>" . htmlspecialchars(print_r(maybe_unserialize($m->meta_value), true)) . "</pre></td>";
    echo "</tr>";
}
echo "</table>";

?>

</body>
</html>

