<?php
/**
 * Gutenberg Panel Killer - Removes all custom document panels for SCN CPTs
 * 
 * This MU-plugin aggressively removes any Gutenberg document panels that might
 * be automatically created by WordPress core for our custom post types.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove Gutenberg document panels for SCN CPTs
 */
add_action('enqueue_block_editor_assets', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    
    // Only run on editor pages, not list pages
    if (!$screen || !in_array($screen->post_type, ['scn_event', 'scn_course', 'scn_profile'], true) || $screen->base !== 'post') {
        return;
    }

    // Method 1: Dequeue any potential editor panel scripts
    $potential_handles = [
        'scn-events-editor',
        'scn-courses-editor', 
        'scn-profiles-editor',
        'events-editor-panel',
        'courses-editor-panel',
        'profiles-editor-panel',
        'scn-membership-editor',
        'scn-editor-panels'
    ];

    foreach ($potential_handles as $handle) {
        if (wp_script_is($handle, 'enqueued') || wp_script_is($handle, 'registered')) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }

    // Method 2: Disable all document panels via JavaScript
    wp_add_inline_script(
        'wp-edit-post',
        "
        (function() {
            // Wait for WordPress to be available
            function initPanelKiller() {
                if (typeof wp === 'undefined' || !wp.plugins) {
                    setTimeout(initPanelKiller, 100);
                    return;
                }
                
                // Override the document settings panel filter to remove all panels
                wp.hooks.addFilter(
                    'editor.DocumentSettingsPanel',
                    'scn-membership/remove-all-panels',
                    function(panel) {
                        console.log('[PANEL KILLER] Blocking panel:', panel?.name || 'unknown');
                        return null; // Remove all panels
                    },
                    999 // High priority to override everything
                );
                
                // Also try to unregister any existing panels
                if (typeof wp.plugins.getPlugins === 'function') {
                    const plugins = wp.plugins.getPlugins() || [];
                    plugins.forEach(function(plugin) {
                        if (plugin && plugin.name) {
                            console.log('[PANEL KILLER] Found plugin:', plugin.name);
                            try {
                                wp.plugins.unregisterPlugin(plugin.name);
                                console.log('[PANEL KILLER] Unregistered:', plugin.name);
                            } catch(e) {
                                console.log('[PANEL KILLER] Failed to unregister:', plugin.name, e);
                            }
                        }
                    });
                }
                
                // Additional cleanup after editor loads
                if (wp.domReady) {
                    wp.domReady(function() {
                        // Remove any document panels that might have been added
                        const documentPanels = document.querySelectorAll('[data-wp-panel]');
                        documentPanels.forEach(function(panel) {
                            console.log('[PANEL KILLER] Removing DOM panel:', panel);
                            panel.remove();
                        });
                    });
                }
            }
            
            // Start the panel killer
            initPanelKiller();
        })();
        "
    );
}, 1); // Run early

/**
 * Additional cleanup for meta field panels
 */
add_action('rest_api_init', function() {
    // Remove REST API meta field registrations that might create panels
    $post_types = ['scn_event', 'scn_course', 'scn_profile'];
    
    foreach ($post_types as $post_type) {
        // Get all registered meta fields for this post type
        $meta_fields = get_registered_meta_keys('post', $post_type);
        
        foreach ($meta_fields as $meta_key => $meta_config) {
            if (isset($meta_config['show_in_rest']) && $meta_config['show_in_rest']) {
                // Unregister the REST API registration to prevent panel creation
                unregister_meta_key('post', $meta_key);
                
                // Re-register without show_in_rest
                register_meta('post', $meta_key, array_merge($meta_config, [
                    'show_in_rest' => false
                ]));
            }
        }
    }
}, 20);

/**
 * Debug logging for panel detection
 */
add_action('current_screen', function($screen) {
    // Only run on editor pages, not list pages
    if (!$screen || !in_array($screen->post_type, ['scn_event', 'scn_course', 'scn_profile'], true) || $screen->base !== 'post') {
        return;
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[PANEL KILLER] Active for post type: ' . $screen->post_type);
        
        // Log registered meta fields
        $meta_fields = get_registered_meta_keys('post', $screen->post_type);
        error_log('[PANEL KILLER] Meta fields: ' . print_r(array_keys($meta_fields), true));
    }
});
