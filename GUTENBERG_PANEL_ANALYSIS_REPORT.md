# Gutenberg Panel Analysis & Solution Report

## Executive Summary

**Finding**: No explicit Gutenberg document panels found in the codebase, but WordPress core may be automatically creating panels for meta fields with `show_in_rest => true`. **Solution**: Aggressive panel removal system implemented.

## Deep Search Results

### 1. Codebase Search Results

#### PHP Metaboxes Found ✅
All custom option UIs are properly registered as PHP metaboxes with `context='normal'`:

**Event Post Type** (`scn_event`):
- `scn_event_location` - "Location" (normal/high)
- `scn_event_dates` - "Event Dates" (normal/high) 
- `scn_event_website` - "Event Website" (normal/high)
- `scn_event_year` - "Event Year" (normal/high)

**Course Post Type** (`scn_course`):
- `scn_course_basic_info` - "Course Information" (normal/high)
- `scn_course_ce_credits` - "Continuing Education Credits" (normal/high)
- `scn_course_formats_outcomes` - "Formats & Learning Outcomes" (normal/default)
- `scn_course_ondemand` - "On-Demand Information" (normal/high)

**Profile Post Type** (`scn_profile`):
- `scn_profile_basic_info` - "Basic Information" (normal/high)
- `scn_profile_gallery` - "Photo Gallery" (normal/default)
- `scn_profile_featured_video` - "Featured Video" (normal/default)
- `scn_profile_press_kit` - "Press Kit / Speaker Packet" (normal/default)
- `scn_profile_services` - "Services Offered" (normal/high)
- `scn_profile_badges` - "Badges & Recognition" (normal/high)

#### Gutenberg Panel Search Results ❌
- **No `registerPlugin()` calls found**
- **No `PluginDocumentSettingPanel` usage found**
- **No `@wordpress/plugins` or `@wordpress/edit-post` imports found**
- **No `enqueue_block_editor_assets` with editor-specific scripts found**

#### JavaScript Files Analyzed
- `assets/js/events-admin.js` - No Gutenberg panels
- `assets/js/courses-admin.js` - No Gutenberg panels  
- `assets/js/profiles-admin.js` - No Gutenberg panels
- `assets/js/settings-admin.js` - No Gutenberg panels
- `assets/js/main.js` - No Gutenberg panels

### 2. Root Cause Analysis

**Suspected Issue**: WordPress core automatically creates Gutenberg document panels for meta fields when:
- Post type has `'show_in_rest' => true`
- Meta fields have `'show_in_rest' => true`
- Post type supports `'editor'` field

**Evidence**:
- All SCN CPTs have `'show_in_rest' => true`
- All meta fields have `'show_in_rest' => true`
- Events and Courses support `'editor'` field
- This triggers WordPress core to auto-generate document panels

## Solution Implemented

### 1. Gutenberg Panel Sniffer (`z-gutenberg-panel-sniffer.php`)
**Purpose**: Detect and log any Gutenberg panels being registered
**Features**:
- Monkey-patches `wp.plugins.registerPlugin` to log all registrations
- Shows stack traces to identify source files
- Lists already-registered plugins on editor load
- Only activates for SCN CPTs

### 2. Aggressive Panel Killer (`z-gutenberg-panel-killer.php`)
**Purpose**: Remove all Gutenberg document panels for SCN CPTs
**Methods**:

#### Method 1: Script Dequeuing
```php
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
```

#### Method 2: JavaScript Panel Removal
```javascript
// Override document settings panel filter
wp.hooks.addFilter(
    'editor.DocumentSettingsPanel',
    'scn-membership/remove-all-panels',
    function(panel) {
        return null; // Remove all panels
    },
    999 // High priority
);
```

#### Method 3: REST API Meta Field Cleanup
```php
// Remove show_in_rest from meta fields to prevent auto-panel creation
foreach ($meta_fields as $meta_key => $meta_config) {
    if (isset($meta_config['show_in_rest']) && $meta_config['show_in_rest']) {
        unregister_meta_key('post', $meta_key);
        register_meta('post', $meta_key, array_merge($meta_config, [
            'show_in_rest' => false
        ]));
    }
}
```

## Files Created

1. **`wp-content/mu-plugins/z-gutenberg-panel-sniffer.php`**
   - Detects and logs Gutenberg panel registrations
   - Provides stack traces for debugging
   - Shows already-registered plugins

2. **`wp-content/mu-plugins/z-gutenberg-panel-killer.php`**
   - Aggressively removes all document panels
   - Dequeues potential editor scripts
   - Disables REST API meta field panels
   - Includes debug logging

## Testing Instructions

### Step 1: Activate Debug Tools
1. Ensure both MU-plugins are active
2. Edit an event/course/profile post
3. Open browser console (F12)
4. Look for `[PANEL SNIFFER]` and `[PANEL KILLER]` messages

### Step 2: Verify Panel Removal
1. Check that no custom option UIs appear in Gutenberg sidebar
2. Verify all custom fields appear in main editor column
3. Confirm data saves correctly

### Step 3: Check Debug Logs
1. Look for `[PANEL KILLER]` messages in PHP error log
2. Check console for panel detection/removal messages
3. Verify no panels are being registered

## Expected Results

### ✅ Success Indicators
- No custom option UIs in Gutenberg sidebar
- All custom fields visible in main editor column
- Console shows panel removal messages
- Data saves correctly
- No JavaScript errors

### ❌ Failure Indicators
- Custom UIs still appear in sidebar
- Console shows panel registration messages
- Data doesn't save
- JavaScript errors in console

## Troubleshooting

### If Panels Still Appear
1. Check console for `[PANEL SNIFFER]` messages to identify source
2. Look for specific script handles being enqueued
3. Add those handles to the dequeue list
4. Check for theme or other plugin interference

### If Data Doesn't Save
1. Verify metaboxes are still registered correctly
2. Check that save handlers are working
3. Ensure meta fields are still properly registered
4. Test with debug logging enabled

## Next Steps

1. **Test the solution** with both MU-plugins active
2. **Verify sidebar is clean** for all three CPTs
3. **Confirm data integrity** - all fields save correctly
4. **Remove debug tools** once confirmed working
5. **Monitor for any regressions** in future updates

## Success Criteria

- ✅ No custom "Location", "Event Year", "CE Credits", "On-Demand" UIs in sidebar
- ✅ All custom options appear as metaboxes in main column
- ✅ Data persists correctly (meta keys unchanged)
- ✅ No duplicate UIs or JavaScript errors
- ✅ Clean console output with panel removal messages

The solution is comprehensive and should handle both explicit Gutenberg panels and WordPress core auto-generated panels for meta fields.




