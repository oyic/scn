<?php
/**
 * Test script for SCN Membership Metabox Override
 * 
 * This script can be used to test that the metabox override is working correctly.
 * Place this in your WordPress root directory and access it via browser to see
 * the current metabox registrations for each CPT.
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
$post_types = ['scn_course', 'scn_event', 'scn_profile'];
global $wp_meta_boxes;

echo '<h1>SCN Membership - Metabox Override Test</h1>';
echo '<p>This page shows the current metabox registrations for each CPT.</p>';

foreach ($post_types as $post_type) {
    echo '<h2>' . ucfirst(str_replace('scn_', '', $post_type)) . ' Post Type</h2>';
    
    if (empty($wp_meta_boxes[$post_type])) {
        echo '<p><em>No metaboxes registered for this post type.</em></p>';
        continue;
    }
    
    foreach ($wp_meta_boxes[$post_type] as $context => $priorities) {
        echo '<h3>Context: ' . $context . '</h3>';
        
        if (empty($priorities)) {
            echo '<p><em>No metaboxes in this context.</em></p>';
            continue;
        }
        
        foreach ($priorities as $priority => $metaboxes) {
            echo '<h4>Priority: ' . $priority . '</h4>';
            
            if (empty($metaboxes)) {
                echo '<p><em>No metaboxes at this priority.</em></p>';
                continue;
            }
            
            echo '<ul>';
            foreach ($metaboxes as $id => $metabox) {
                $title = $metabox['title'] ?? 'No Title';
                $callback = $metabox['callback'] ?? 'No Callback';
                $callback_name = is_callable($callback) ? 'Callable' : 'Not Callable';
                
                echo '<li><strong>' . esc_html($id) . '</strong> - ' . esc_html($title) . ' (' . $callback_name . ')</li>';
            }
            echo '</ul>';
        }
    }
    
    // Check specifically for sidebar metaboxes
    if (!empty($wp_meta_boxes[$post_type]['side'])) {
        echo '<div style="background: #ffebee; padding: 10px; border-left: 4px solid #f44336; margin: 10px 0;">';
        echo '<strong>⚠️ WARNING: Side metaboxes still present for ' . $post_type . '</strong>';
        echo '<pre>' . print_r($wp_meta_boxes[$post_type]['side'], true) . '</pre>';
        echo '</div>';
    } else {
        echo '<div style="background: #e8f5e8; padding: 10px; border-left: 4px solid #4caf50; margin: 10px 0;">';
        echo '<strong>✅ SUCCESS: No side metaboxes found for ' . $post_type . '</strong>';
        echo '</div>';
    }
    
    echo '<hr>';
}

echo '<h2>Instructions</h2>';
echo '<ol>';
echo '<li>Edit a ' . implode(', ', $post_types) . ' post to see the metaboxes in action</li>';
echo '<li>Verify that all custom option UIs appear in the main editor column</li>';
echo '<li>Confirm that no custom option UIs appear in the Gutenberg right sidebar</li>';
echo '<li>Check that data persists correctly when saving</li>';
echo '</ol>';

echo '<h2>Debug Information</h2>';
echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
echo '<p><strong>Current User:</strong> ' . wp_get_current_user()->user_login . '</p>';
echo '<p><strong>WP_DEBUG:</strong> ' . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . '</p>';
echo '<p><strong>Test Time:</strong> ' . current_time('Y-m-d H:i:s') . '</p>';
?>




