<?php
/**
 * SCN Membership - Metabox Context Override
 * 
 * This file provides a comprehensive solution to ensure all custom option UIs
 * for course, event, and profile CPTs appear in the main editor column
 * (metaboxes with context=normal, priority=high) and not in the Gutenberg right sidebar.
 * 
 * This is a drop-in solution that can be placed in your theme's functions.php
 * or as a standalone plugin. It runs late to override any existing registrations.
 * 
 * @package SCN_Membership
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Force all side metaboxes for specified CPTs into main column (normal/high).
 * Runs late to override earlier registrations.
 */
add_action('add_meta_boxes', function() {
    $post_types = ['course', 'event', 'profile'];
    global $wp_meta_boxes;

    foreach ($post_types as $pt) {
        // If nothing registered yet, skip.
        if (empty($wp_meta_boxes[$pt]['side'])) {
            continue;
        }

        // Copy all side boxes (all priorities) and re-add them in 'normal'/'high'
        foreach ($wp_meta_boxes[$pt]['side'] as $priority => $boxes) {
            if (empty($boxes) || !is_array($boxes)) continue;

            foreach ($boxes as $id => $data) {
                if (empty($data) || !is_array($data)) continue;

                $title    = $data['title']    ?? '';
                $callback = $data['callback'] ?? null;
                $args     = $data['args']     ?? null;

                // Remove from side
                remove_meta_box($id, $pt, 'side');

                // Re-add in main column; keep same callback & args
                // Use 'high' so they float near top (adjust if you want a different order)
                if (is_callable($callback)) {
                    add_meta_box($id, $title, $callback, $pt, 'normal', 'high', $args);
                }
            }
        }

        // Clean up any residual structure to avoid confusion
        unset($wp_meta_boxes[$pt]['side']);
    }
}, 100); // run late so we beat any other add_meta_boxes

/**
 * Gutenberg sidebar panels are no longer disabled - using proper editor switching
 */

/**
 * Debug logger to confirm what's registered (dev-only)
 * Uncomment the lines below to enable debugging
 */
/*
add_action('current_screen', function($screen) {
    if (!$screen || !in_array($screen->post_type, ['course', 'event', 'profile'], true)) {
        return;
    }
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    error_log('SCN MEMBERSHIP - META BOXES MAP for ' . $screen->post_type . ' >>>');
    global $wp_meta_boxes;
    error_log(print_r($wp_meta_boxes[$screen->post_type] ?? [], true));
    
    // Check specifically for any remaining sidebar metaboxes
    if (!empty($wp_meta_boxes[$screen->post_type]['side'])) {
        error_log('WARNING: Side metaboxes still present for ' . $screen->post_type . ':');
        error_log(print_r($wp_meta_boxes[$screen->post_type]['side'], true));
    } else {
        error_log('SUCCESS: No side metaboxes found for ' . $screen->post_type);
    }
});
*/


