<?php
/**
 * Test if ACF works with SCN plugin disabled
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ACF Clean Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; max-width: 900px; margin: 0 auto; border-radius: 8px; }
        .success { color: #46b450; background: #ecf7ed; padding: 15px; margin: 10px 0; border-left: 4px solid #46b450; }
        .error { color: #dc3232; background: #fbeaea; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3232; }
        .warning { color: #f56e28; background: #fcf3e8; padding: 15px; margin: 10px 0; border-left: 4px solid #f56e28; }
        .info { color: #0073aa; background: #e5f5fa; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
        .button { background: #0073aa; color: white; padding: 15px 30px; border: none; cursor: pointer; font-size: 16px; border-radius: 4px; text-decoration: none; display: inline-block; margin: 5px; }
        .button-big { background: #46b450; font-size: 18px; padding: 20px 40px; }
        .button-danger { background: #dc3232; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧪 ACF Clean Test</h1>
    
    <div class="success">
        <h3>✅ SCN Plugin ACF Code Disabled</h3>
        <p>All ACF customizations from the SCN plugin have been disabled:</p>
        <ul>
            <li>✅ ACF classes directory renamed to <code>src/ACF.disabled</code></li>
            <li>✅ acf-json directory renamed to <code>acf-json.backup</code></li>
            <li>✅ ACF initialization removed from plugin</li>
        </ul>
        <p><strong>ACF should now run completely natively without any SCN interference.</strong></p>
    </div>
    
    <h2>Step 1: Deactivate & Reactivate SCN Plugin</h2>
    <div class="warning">
        <p>To ensure all hooks are cleared:</p>
        <ol>
            <li><a href="<?php echo admin_url('plugins.php'); ?>" class="button">Go to Plugins</a></li>
            <li>Deactivate "SCN Membership"</li>
            <li>Activate it again</li>
            <li>Come back here</li>
        </ol>
    </div>
    
    <h2>Step 2: Test ACF Completely Fresh</h2>
    <div class="info">
        <h3>Option A: Delete ALL ACF Data and Start Fresh</h3>
        <?php
        global $wpdb;
        
        $acf_groups_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'acf-field-group'");
        $acf_fields_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'acf-field'");
        
        echo "<p>Current ACF data in database:</p>";
        echo "<ul>";
        echo "<li>Field Groups: $acf_groups_count</li>";
        echo "<li>Fields: $acf_fields_count</li>";
        echo "</ul>";
        
        if (isset($_POST['delete_all_acf'])) {
            echo '<h3>Deleting ALL ACF Data...</h3>';
            
            // Delete all field groups
            $groups = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'acf-field-group'");
            foreach ($groups as $id) {
                wp_delete_post($id, true);
            }
            
            // Delete all fields
            $fields = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'acf-field'");
            foreach ($fields as $id) {
                wp_delete_post($id, true);
            }
            
            // Delete all ACF options
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'acf_%'");
            
            echo '<div class="success">✅ All ACF data deleted!</div>';
            echo '<p><a href="" class="button">Refresh this page</a></p>';
        } else {
            ?>
            <form method="post" onsubmit="return confirm('Are you sure? This will DELETE ALL ACF field groups and fields!');">
                <button type="submit" name="delete_all_acf" class="button button-danger">⚠️ Delete ALL ACF Data</button>
            </form>
            <p><em>This will give you a completely clean slate to test ACF.</em></p>
            <?php
        }
        ?>
    </div>
    
    <h2>Step 3: Test Creating New Field Group</h2>
    <div class="info">
        <p>After clearing the data (or even without clearing), try creating a brand new field group:</p>
        <p><a href="<?php echo admin_url('post-new.php?post_type=acf-field-group'); ?>" class="button button-big" target="_blank">Create New Field Group in ACF →</a></p>
        
        <h3>What to test:</h3>
        <ol>
            <li>Click the button above</li>
            <li>Give the field group a name like "Test Group"</li>
            <li>Add one simple text field</li>
            <li>Click "Publish"</li>
            <li>Try to edit it again</li>
        </ol>
        
        <h3>Results:</h3>
        <ul>
            <li>✅ <strong>If it works:</strong> The SCN plugin was interfering - keep it disabled</li>
            <li>❌ <strong>If it still fails:</strong> ACF Pro itself needs to be reinstalled</li>
        </ul>
    </div>
    
    <h2>Step 4: If Still Not Working</h2>
    <div class="error">
        <h3>ACF Pro May Need Reinstallation</h3>
        <p>If ACF still doesn't work with all SCN code disabled, the ACF Pro plugin itself may be corrupted.</p>
        <p><strong>Solution:</strong></p>
        <ol>
            <li>Download a fresh copy of ACF Pro</li>
            <li>Deactivate current ACF Pro</li>
            <li>Delete the ACF Pro plugin folder</li>
            <li>Upload and activate fresh ACF Pro</li>
            <li>Test again</li>
        </ol>
    </div>
    
    <h2>Quick Links</h2>
    <p>
        <a href="<?php echo admin_url('plugins.php'); ?>" class="button">Plugins</a>
        <a href="<?php echo admin_url('edit.php?post_type=acf-field-group'); ?>" class="button">ACF Field Groups</a>
        <a href="<?php echo admin_url('post-new.php?post_type=acf-field-group'); ?>" class="button button-big">Create New Field Group</a>
    </p>
</div>
</body>
</html>

