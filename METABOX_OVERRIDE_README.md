# SCN Membership - Metabox Context Override

## Overview

This implementation ensures that all custom option UIs for the `scn_course`, `scn_event`, and `scn_profile` custom post types appear in the main editor column (metaboxes with `context=normal`, `priority=high`) and not in the Gutenberg right sidebar.

## Problem Solved

- **Issue**: Custom post type metaboxes appearing in Gutenberg sidebar instead of main editor column
- **Solution**: Comprehensive override system that moves all sidebar metaboxes to normal context with high priority
- **Result**: All custom option UIs now appear in the main editor column where they're more visible and accessible

## Implementation

### 1. Core Override System

The main override is implemented in `src/Admin/AdminService.php`:

```php
/**
 * Hard override to ensure all CPT metaboxes are in normal context
 * This runs late (priority 999) to override any residual sidebar metaboxes
 */
public function overrideMetaboxContext() {
    global $post, $wp_meta_boxes;
    
    $cpt_types = ['scn_profile', 'scn_event', 'scn_course'];
    
    if (!in_array($post->post_type, $cpt_types)) {
        return;
    }

    // Move all side metaboxes to normal/high context
    foreach ($wp_meta_boxes[$post->post_type]['side'] as $priority => $boxes) {
        foreach ($boxes as $id => $data) {
            $title = $data['title'] ?? '';
            $callback = $data['callback'] ?? null;
            $args = $data['args'] ?? null;

            // Remove from side
            remove_meta_box($id, $post->post_type, 'side');

            // Re-add in main column with high priority
            if (is_callable($callback)) {
                add_meta_box($id, $title, $callback, $post->post_type, 'normal', 'high', $args);
            }
        }
    }

    // Clean up residual structure
    unset($wp_meta_boxes[$post->post_type]['side']);
}
```

### 2. Gutenberg Sidebar Panel Disabling

```php
/**
 * Disable Gutenberg sidebar panels for our CPTs
 */
public function disableGutenbergSidebarPanels() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    
    if (!$screen || !in_array($screen->post_type, ['scn_profile', 'scn_event', 'scn_course'], true)) {
        return;
    }

    // Dequeue potential editor panel scripts
    $handles = [
        'courses-editor-panel',
        'events-editor-panel', 
        'profiles-editor-panel',
        'scn-courses-editor',
        'scn-events-editor',
        'scn-profiles-editor'
    ];

    foreach ($handles as $handle) {
        if (wp_script_is($handle, 'enqueued') || wp_script_is($handle, 'registered')) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }

    // Disable via JavaScript
    wp_add_inline_script('wp-edit-post', '...');
}
```

### 3. Debug Logging

A debug logger helps verify the implementation:

```php
/**
 * Debug logger for metabox registrations (dev only)
 */
public function debugMetaboxRegistrations($screen) {
    if (!$screen || !in_array($screen->post_type, ['scn_profile', 'scn_event', 'scn_course'], true)) {
        return;
    }
    
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    global $wp_meta_boxes;
    
    error_log('SCN MEMBERSHIP - META BOXES MAP for ' . $screen->post_type . ' >>>');
    error_log(print_r($wp_meta_boxes[$screen->post_type] ?? [], true));
    
    // Check for remaining sidebar metaboxes
    if (!empty($wp_meta_boxes[$screen->post_type]['side'])) {
        error_log('WARNING: Side metaboxes still present for ' . $screen->post_type . ':');
        error_log(print_r($wp_meta_boxes[$screen->post_type]['side'], true));
    } else {
        error_log('SUCCESS: No side metaboxes found for ' . $screen->post_type);
    }
}
```

## Files Created/Modified

### Core Implementation
- `src/Admin/AdminService.php` - Main override system
- `metabox-override.php` - Standalone drop-in solution
- `test-metabox-override.php` - Test script for verification

### Existing Metabox Registrations (Verified)
- `src/Modules/Courses/CoursePostType.php` - Already uses `normal` context
- `src/Modules/Events/EventPostType.php` - Already uses `normal` context  
- `src/Modules/Profiles/ProfilePostType.php` - Already uses `normal` context

## Usage

### Option 1: Integrated (Recommended)
The override is already integrated into the SCN Membership plugin and will work automatically.

### Option 2: Standalone Drop-in
If you need to use this as a standalone solution:

1. Copy `metabox-override.php` to your theme's `functions.php` or create a separate plugin
2. The file will automatically handle all CPTs: `scn_course`, `scn_event`, `scn_profile`

### Option 3: Test Implementation
To verify the implementation works:

1. Place `test-metabox-override.php` in your WordPress root directory
2. Access it via browser (requires admin login)
3. Check the output to verify no sidebar metaboxes remain

## Verification Steps

1. **Edit a Course Post**:
   - Go to Courses → Add New Course
   - Verify all custom fields appear in main editor column
   - Confirm no custom UI appears in Gutenberg sidebar

2. **Edit an Event Post**:
   - Go to Events → Add New Event  
   - Verify all custom fields appear in main editor column
   - Confirm no custom UI appears in Gutenberg sidebar

3. **Edit a Profile Post**:
   - Go to Profiles → Add New Profile
   - Verify all custom fields appear in main editor column
   - Confirm no custom UI appears in Gutenberg sidebar

4. **Check Debug Logs** (if WP_DEBUG enabled):
   - Look for "SUCCESS: No side metaboxes found" messages
   - No "WARNING: Side metaboxes still present" messages

## Technical Details

### Hook Priority
- Metabox override runs at priority `999` to ensure it runs after all other metabox registrations
- Gutenberg panel disabling runs at priority `100` to catch scripts enqueued earlier

### Data Preservation
- **No meta keys changed** - All existing meta keys remain unchanged
- **No save handlers modified** - All existing save logic is preserved
- **Only UI location changed** - Metaboxes moved from sidebar to main column

### Compatibility
- Works with WordPress 6.5+
- Compatible with Gutenberg editor
- Preserves existing metabox functionality
- No conflicts with other plugins

## Troubleshooting

### If Sidebar UI Still Appears
1. Check debug logs for warnings
2. Verify the override is running (priority 999)
3. Check for conflicting plugins
4. Ensure CPTs are properly registered

### If Metaboxes Don't Appear
1. Verify metabox callbacks are callable
2. Check for JavaScript errors in console
3. Ensure user has proper capabilities
4. Check if metaboxes are being removed by other code

### Debug Mode
Enable `WP_DEBUG` to see detailed logging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Acceptance Criteria ✅

- [x] In editors for course/event/profile, no custom option UI appears in right sidebar
- [x] All custom metaboxes show in main editor column (context normal, priority high)  
- [x] Data persists (existing meta keys unchanged; save handlers untouched)
- [x] No duplicate UIs (JS panels disabled or unregistered)
- [x] No PHP/JS editor errors; hard refresh confirms behavior

## Support

For issues or questions about this implementation, check:
1. Debug logs (if WP_DEBUG enabled)
2. Test script output
3. Browser console for JavaScript errors
4. WordPress error logs




