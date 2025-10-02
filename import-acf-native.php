<?php
/**
 * Import ACF Field Groups using ACF's Native Import
 * This creates a proper import file that ACF can use
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

$json_path = __DIR__ . '/acf-json';
$json_files = glob($json_path . '/*.json');

// Combine all field groups into one import array
$import_data = array();

foreach ($json_files as $file) {
    $field_group = json_decode(file_get_contents($file), true);
    if ($field_group) {
        $import_data[] = $field_group;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Import ACF Field Groups</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        .info { color: #0073aa; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 0; }
        .button:hover { background: #005a87; }
        textarea { width: 100%; height: 400px; font-family: monospace; font-size: 12px; }
        .step { background: #f5f5f5; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
    </style>
</head>
<body>
    <h1>Import ACF Field Groups</h1>
    
    <div class="step">
        <h3>Method 1: Use ACF's Native Import (Recommended)</h3>
        <ol>
            <li>Go to <a href="<?php echo admin_url('edit.php?post_type=acf-field-group&page=acf-tools'); ?>" target="_blank"><strong>Custom Fields > Tools > Import Field Groups</strong></a></li>
            <li>Copy the JSON code below</li>
            <li>Paste it into the import field and click "Import JSON"</li>
        </ol>
        
        <h4>Copy this JSON:</h4>
        <textarea readonly onclick="this.select(); document.execCommand('copy'); alert('Copied to clipboard!');"><?php echo json_encode($import_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></textarea>
        
        <p><a href="<?php echo admin_url('edit.php?post_type=acf-field-group&page=acf-tools'); ?>" class="button" target="_blank">Open ACF Import Tool</a></p>
    </div>
    
    <div class="step">
        <h3>Method 2: Check for Sync Available</h3>
        <ol>
            <li>Go to <a href="<?php echo admin_url('edit.php?post_type=acf-field-group'); ?>" target="_blank"><strong>Custom Fields</strong></a></li>
            <li>Look for field groups with a "Sync available" button</li>
            <li>Click the "Sync available" button to import them</li>
        </ol>
        
        <p><a href="<?php echo admin_url('edit.php?post_type=acf-field-group'); ?>" class="button" target="_blank">Open Field Groups</a></p>
    </div>
    
    <hr>
    <p class="info"><strong>Found <?php echo count($import_data); ?> field groups:</strong></p>
    <ul>
        <?php foreach ($import_data as $fg): ?>
            <li><?php echo esc_html($fg['title']); ?> (<?php echo esc_html($fg['key']); ?>)</li>
        <?php endforeach; ?>
    </ul>
    
    <hr>
    <p><a href="<?php echo admin_url(); ?>">← Back to WordPress Admin</a></p>
</body>
</html>

