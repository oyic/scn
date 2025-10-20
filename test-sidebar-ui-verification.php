<?php
/**
 * SCN Membership - Sidebar UI Verification Test
 * 
 * This script verifies that no sidebar UIs are present for the CPTs
 * and all custom options appear in the main editor column.
 * 
 * @package SCN_Membership
 * @version 1.0.0
 */

// Load WordPress
require_once('wp-config.php');

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied. You must be logged in as an administrator.');
}

// Get current screen context
$post_types = ['course', 'event', 'profile'];
global $wp_meta_boxes;

echo '<h1>SCN Membership - Sidebar UI Verification Test</h1>';
echo '<p>This test verifies that all custom option UIs appear in the main editor column and not in the sidebar.</p>';

$has_sidebar_issues = false;

foreach ($post_types as $post_type) {
    echo '<h2>' . ucfirst(str_replace('scn_', '', $post_type)) . ' Post Type</h2>';
    
    if (empty($wp_meta_boxes[$post_type])) {
        echo '<p><em>No metaboxes registered for this post type.</em></p>';
        continue;
    }
    
    // Check for sidebar metaboxes
    if (!empty($wp_meta_boxes[$post_type]['side'])) {
        echo '<div style="background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 10px 0;">';
        echo '<strong>❌ ISSUE FOUND: Side metaboxes present for ' . $post_type . '</strong>';
        echo '<pre>' . print_r($wp_meta_boxes[$post_type]['side'], true) . '</pre>';
        echo '</div>';
        $has_sidebar_issues = true;
    } else {
        echo '<div style="background: #e8f5e8; padding: 10px; border-left: 4px solid #4caf50; margin: 10px 0;">';
        echo '<strong>✅ SUCCESS: No side metaboxes found for ' . $post_type . '</strong>';
        echo '</div>';
    }
    
    // Show normal metaboxes
    if (!empty($wp_meta_boxes[$post_type]['normal'])) {
        echo '<h3>Normal Context Metaboxes:</h3>';
        echo '<ul>';
        foreach ($wp_meta_boxes[$post_type]['normal'] as $priority => $metaboxes) {
            foreach ($metaboxes as $id => $metabox) {
                $title = $metabox['title'] ?? 'No Title';
                echo '<li><strong>' . esc_html($id) . '</strong> - ' . esc_html($title) . ' (Priority: ' . $priority . ')</li>';
            }
        }
        echo '</ul>';
    }
    
    echo '<hr>';
}

// Summary
echo '<h2>Verification Summary</h2>';
if ($has_sidebar_issues) {
    echo '<div style="background: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 10px 0;">';
    echo '<strong>❌ ISSUES FOUND</strong><br>';
    echo 'Some custom option UIs are still appearing in the sidebar. Check the details above and apply fixes.';
    echo '</div>';
} else {
    echo '<div style="background: #e8f5e8; padding: 15px; border-left: 4px solid #4caf50; margin: 10px 0;">';
    echo '<strong>✅ ALL CLEAR</strong><br>';
    echo 'No sidebar UIs found. All custom option UIs should appear in the main editor column.';
    echo '</div>';
}

echo '<h2>Next Steps</h2>';
echo '<ol>';
echo '<li>Edit a ' . implode(', ', $post_types) . ' post to verify the UI behavior</li>';
echo '<li>Check that all custom fields appear in the main editor column</li>';
echo '<li>Confirm no custom UI appears in the Gutenberg sidebar</li>';
echo '<li>Test that data saves correctly when editing</li>';
echo '</ol>';

echo '<h2>Debug Information</h2>';
echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
echo '<p><strong>Current User:</strong> ' . wp_get_current_user()->user_login . '</p>';
echo '<p><strong>WP_DEBUG:</strong> ' . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . '</p>';
echo '<p><strong>Test Time:</strong> ' . current_time('Y-m-d H:i:s') . '</p>';

// Show all metaboxes for debugging
echo '<h2>Full Metabox Registry (Debug)</h2>';
echo '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px;">';
echo esc_html(print_r($wp_meta_boxes, true));
echo '</pre>';
?>




