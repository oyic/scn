# SCN Membership - Sidebar UI Source Analysis Report

## Executive Summary

**Finding**: All custom option UIs are already properly registered as PHP metaboxes with `context='normal'` and `priority='high'`. **No sidebar UIs were found** in the codebase. The issue appears to be resolved or was a false positive.

## Detailed Analysis

### 1. PHP Metabox Inventory

#### Event Post Type (`scn_event`)
**File**: `src/Modules/Events/EventPostType.php`
**Hook**: `add_action('add_meta_boxes', [$this, 'addMetaBoxes'])` (default priority 10)

| ID | Title | Context | Priority | Callback |
|---|---|---|---|---|
| `scn_event_location` | "Location" | normal | high | `renderLocationMetaBox` |
| `scn_event_dates` | "Event Dates" | normal | high | `renderDatesMetaBox` |
| `scn_event_website` | "Event Website" | normal | high | `renderWebsiteMetaBox` |
| `scn_event_year` | "Event Year" | normal | high | `renderYearMetaBox` |

#### Course Post Type (`scn_course`)
**File**: `src/Modules/Courses/CoursePostType.php`
**Hook**: `add_action('add_meta_boxes', [$this, 'addMetaBoxes'])` (default priority 10)

| ID | Title | Context | Priority | Callback |
|---|---|---|---|---|
| `scn_course_basic_info` | "Course Information" | normal | high | `renderBasicInfoMetaBox` |
| `scn_course_ce_credits` | "Continuing Education Credits" | normal | high | `renderCeCreditsMetaBox` |
| `scn_course_formats_outcomes` | "Formats & Learning Outcomes" | normal | default | `renderFormatsOutcomesMetaBox` |
| `scn_course_ondemand` | "On-Demand Information" | normal | high | `renderOnDemandMetaBox` |

#### Profile Post Type (`scn_profile`)
**File**: `src/Modules/Profiles/ProfilePostType.php`
**Hook**: `add_action('add_meta_boxes', [$this, 'addMetaBoxes'])` (default priority 10)

| ID | Title | Context | Priority | Callback |
|---|---|---|---|---|
| `scn_profile_debug` | "SCN Profile Debug" | normal | high | anonymous function |
| `scn_profile_basic_info` | "Basic Information" | normal | high | `renderBasicInfoMetaBox` |
| `scn_profile_gallery` | "Photo Gallery" | normal | default | `renderGalleryMetaBox` |
| `scn_profile_featured_video` | "Featured Video" | normal | default | `renderFeaturedVideoMetaBox` |
| `scn_profile_press_kit` | "Press Kit / Speaker Packet" | normal | default | `renderPressKitMetaBox` |
| `scn_profile_services` | "Services Offered" | normal | high | `renderServicesMetaBox` |
| `scn_profile_badges` | "Badges & Recognition" | normal | high | `renderBadgesMetaBox` |

### 2. Gutenberg Editor Panel Search

**Result**: No Gutenberg editor panels found
- No `registerPlugin()` calls
- No `PluginDocumentSettingPanel` usage
- No `enqueue_block_editor_assets` with editor-specific scripts
- No `@wordpress/plugins` or `@wordpress/edit-post` imports

### 3. Late Hook Analysis

**Result**: No late-running hooks found
- No `registerMetaBoxesOnMenu` functions
- No post-type specific `add_meta_boxes_*` hooks
- No additional metabox registrations after initial setup

### 4. Current Override System

**File**: `src/Admin/AdminService.php`
- `overrideMetaboxContext()` runs at priority 999
- `disableGutenbergSidebarPanels()` runs at priority 100
- Debug logging available when `WP_DEBUG` is enabled

## Debug MU-Plugin Created

**File**: `wp-content/mu-plugins/z-debug-metabox-inspector.php`

This plugin will:
1. Log all metabox registrations to PHP error log
2. Display registered Gutenberg plugins in browser console
3. Only activate for `scn_event`, `scn_course`, `scn_profile` post types

## Key Findings

### ✅ All Metaboxes Are Properly Configured
- **Context**: All metaboxes use `'normal'` context (not `'side'`)
- **Priority**: Most use `'high'` priority for visibility
- **Post Types**: Correctly scoped to respective CPTs
- **Callbacks**: All callbacks are properly defined and callable

### ✅ No Gutenberg Sidebar Panels
- No JavaScript-based sidebar panels found
- No `registerPlugin()` calls for document settings
- No editor-specific script enqueuing

### ✅ No Late-Running Hooks
- No additional metabox registrations after initial setup
- No post-type specific hooks that might add sidebar metaboxes

## Conclusion

**The sidebar UI issue appears to be resolved or was a false positive.** All custom option UIs are properly registered as PHP metaboxes in the main editor column with appropriate context and priority.

## Recommendations

1. **Test the Debug MU-Plugin**: 
   - Edit an event/course/profile post
   - Check PHP error log for metabox registrations
   - Check browser console for Gutenberg plugins

2. **Verify Current Behavior**:
   - All custom fields should appear in main editor column
   - No custom UI should appear in Gutenberg sidebar
   - Data should save correctly

3. **If Issues Persist**:
   - The problem may be coming from:
     - WordPress core behavior
     - Other plugins
     - Theme interference
     - Browser caching

## Files Modified

- `wp-content/mu-plugins/z-debug-metabox-inspector.php` - Debug plugin created
- `SIDEBAR_UI_SOURCE_REPORT.md` - This report

## Next Steps

1. Test with debug plugin active
2. Verify no sidebar UIs appear
3. If issues found, use debug output to identify source
4. Apply targeted fix based on actual findings
