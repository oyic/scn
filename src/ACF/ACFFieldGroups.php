<?php

namespace SCN\Membership\ACF;

/**
 * ACF Field Groups
 * 
 * Simple check for ACF Pro availability.
 * JSON loading is temporarily disabled to allow database editing.
 */
class ACFFieldGroups
{
    public function __construct()
    {
        // Check if ACF is available
        if ($this->isACFAvailable()) {
            // Disable JSON loading - use database only
            // add_filter('acf/settings/save_json', [$this, 'setACFJsonSavePath']);
            // add_filter('acf/settings/load_json', [$this, 'setACFJsonLoadPath']);
            
            // Register bidirectional relationship fields
            add_action('acf/init', [$this, 'registerBidirectionalFields']);
            
            // Register event field group
            add_action('acf/init', [$this, 'registerEventFieldGroup']);
            
            // Sync bidirectional relationships manually
            add_action('acf/save_post', [$this, 'syncMemberCourseRelationship'], 20);
            
            error_log('SCN: ACF Pro is available (Database-only mode)');
        } else {
            error_log('SCN: ACF Pro is not available');
        }
    }
    
    /**
     * Check if ACF Pro is available
     */
    public function isACFAvailable()
    {
        return class_exists('ACF') && function_exists('acf_get_field_groups');
    }
    
    
    /**
     * Register bidirectional relationship fields for Member <-> Course
     * 
     * DISABLED: These fields already exist in the database (Member Information group)
     * If you need to re-enable programmatic registration, uncomment this method
     */
    public function registerBidirectionalFields()
    {
        // NOTE: Fields are already in database via ACF UI in "Member Information" group
        // No need to register programmatically - causes duplicates
        
        // ONLY register the Course Author field if it doesn't exist in database
        // This is the bidirectional target field on the Course CPT side
        
        $existing_group = acf_get_field_group('group_course_author_relationship');
        
        // Author field is now included in the main "Course Information" field group
        // No need to create a separate "Author" field group
    }
    
    /**
     * Sync bidirectional relationship between Member and Course
     * Ensures Member → Courses and Course → Author are always in sync
     */
    public function syncMemberCourseRelationship($post_id)
    {
        // Avoid infinite loops
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        
        $post_type = get_post_type($post_id);
        
        // If it's a member post, sync courses
        if ($post_type === 'member') {
            $this->syncMemberCourses($post_id);
        }
        
        // If it's a course post, sync author
        if ($post_type === 'course') {
            $this->syncCourseAuthor($post_id);
        }
    }
    
    /**
     * Sync courses when member is saved
     * Updates the 'author' field on all linked courses
     */
    private function syncMemberCourses($member_id)
    {
        // Get courses linked to this member
        $course_ids = get_field('courses', $member_id, false);
        
        if (!is_array($course_ids)) {
            $course_ids = [];
        }
        
        // Get all courses that currently link to this member
        $existing_courses = get_posts([
            'post_type' => 'course',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'author',
                    'value' => '"' . $member_id . '"',
                    'compare' => 'LIKE'
                ]
            ],
            'fields' => 'ids'
        ]);
        
        // Add member to courses that should have it
        foreach ($course_ids as $course_id) {
            $course_author = get_field('author', $course_id, false);
            
            // If course doesn't have this member as author, add it
            if (!is_array($course_author)) {
                $course_author = [];
            }
            
            if (!in_array($member_id, $course_author)) {
                // For single author field, just set it
                update_field('author', [$member_id], $course_id);
            }
        }
        
        // Remove member from courses that shouldn't have it
        foreach ($existing_courses as $course_id) {
            if (!in_array($course_id, $course_ids)) {
                // This course should no longer link to this member
                $course_author = get_field('author', $course_id, false);
                if (is_array($course_author)) {
                    $course_author = array_diff($course_author, [$member_id]);
                    update_field('author', $course_author, $course_id);
                } else {
                    update_field('author', [], $course_id);
                }
            }
        }
    }
    
    /**
     * Sync author when course is saved
     * Updates the 'courses' field on the linked member
     */
    private function syncCourseAuthor($course_id)
    {
        // Get author (member) linked to this course
        $author_ids = get_field('author', $course_id, false);
        
        if (!is_array($author_ids)) {
            $author_ids = !empty($author_ids) ? [$author_ids] : [];
        }
        
        $member_id = !empty($author_ids) ? $author_ids[0] : null;
        
        if (!$member_id) {
            return; // No author set
        }
        
        // Get current courses for this member
        $member_courses = get_field('courses', $member_id, false);
        
        if (!is_array($member_courses)) {
            $member_courses = [];
        }
        
        // Add this course if not already there
        if (!in_array($course_id, $member_courses)) {
            $member_courses[] = $course_id;
            update_field('courses', $member_courses, $member_id);
        }
    }
    
    /**
     * Set the folder where ACF will save JSON files
     */
    public function setACFJsonSavePath($path)
    {
        return dirname(dirname(__DIR__)) . '/acf-json';
    }
    
    /**
     * Register the event field group programmatically
     */
    public function registerEventFieldGroup()
    {
        // Force registration - database only mode
        error_log('SCN: Registering event field group programmatically (database-only mode)');
        
        $field_group_data = [
            'key' => 'group_scn_event_fields',
            'title' => 'Event Information',
            'fields' => [
                [
                    'key' => 'field_scn_event_website',
                    'label' => 'Event Website',
                    'name' => 'scn_event_website',
                    'type' => 'url',
                    'instructions' => 'Enter the event website URL',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => ''
                    ],
                    'default_value' => '',
                    'placeholder' => 'https://example.com'
                ],
                [
                    'key' => 'field_scn_address',
                    'label' => 'Address',
                    'name' => 'scn_address',
                    'type' => 'text',
                    'instructions' => 'Enter the street address or building name',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => ''
                    ],
                    'default_value' => '',
                    'placeholder' => 'Street address, building, etc.'
                ],
                [
                    'key' => 'field_event_location_city',
                    'label' => 'City',
                    'name' => 'event_location_city',
                    'type' => 'text',
                    'instructions' => 'Enter the city',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => ''
                    ],
                    'default_value' => '',
                    'placeholder' => 'City name'
                ],
                [
                    'key' => 'field_event_location_region',
                    'label' => 'Region',
                    'name' => 'event_location_region',
                    'type' => 'text',
                    'instructions' => 'Enter the region or state',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => ''
                    ],
                    'default_value' => '',
                    'placeholder' => 'Region or state'
                ],
                [
                    'key' => 'field_event_location_country',
                    'label' => 'Country',
                    'name' => 'event_location_country',
                    'type' => 'text',
                    'instructions' => 'Enter the country',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => ''
                    ],
                    'default_value' => '',
                    'placeholder' => 'Country name'
                ],
                [
                    'key' => 'field_start_date',
                    'label' => 'Start Date',
                    'name' => 'start_date',
                    'type' => 'date_picker',
                    'instructions' => 'Select the event start date',
                    'required' => 1,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => ''
                    ],
                    'display_format' => 'd/m/Y',
                    'return_format' => 'd/m/Y',
                    'first_day' => 1
                ],
                [
                    'key' => 'field_end_date',
                    'label' => 'End Date',
                    'name' => 'end_date',
                    'type' => 'date_picker',
                    'instructions' => 'Select the event end date',
                    'required' => 1,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => ''
                    ],
                    'display_format' => 'd/m/Y',
                    'return_format' => 'd/m/Y',
                    'first_day' => 1
                ],
                [
                    'key' => 'field_event_author',
                    'label' => 'Author',
                    'name' => 'author',
                    'type' => 'relationship',
                    'instructions' => 'Select the member who created this event',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => ''
                    ],
                    'post_type' => ['member'],
                    'taxonomy' => '',
                    'filters' => ['search'],
                    'elements' => '',
                    'min' => '',
                    'max' => 1,
                    'return_format' => 'id',
                    'bidirectional' => 1,
                    'bidirectional_target' => ['field_member_events']
                ],
                [
                    'key' => 'field_event_courses',
                    'label' => 'Related Courses',
                    'name' => 'courses',
                    'type' => 'relationship',
                    'instructions' => 'Select courses that will be presented at this event',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => ''
                    ],
                    'post_type' => ['course'],
                    'taxonomy' => '',
                    'filters' => ['search'],
                    'elements' => '',
                    'min' => '',
                    'max' => '',
                    'return_format' => 'id'
                ]
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'event'
                    ]
                ]
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => 'Event information fields'
        ];
        
        // Try to update existing field group first
        if (function_exists('acf_update_field_group')) {
            $existing = acf_get_field_group('group_scn_event_fields');
            if ($existing) {
                $field_group_data['ID'] = $existing['ID'];
                $result = acf_update_field_group($field_group_data);
                if ($result) {
                    error_log('SCN: Updated existing event field group');
                    return;
                }
            }
        }
        
        // Fallback to add new field group
        acf_add_local_field_group($field_group_data);
    }

    /**
     * Set the folders where ACF will load JSON files from
     */
    public function setACFJsonLoadPath($paths)
    {
        // Add our plugin's acf-json folder
        $paths[] = __DIR__ . '/acf-json';
        // Also include the backup directory
        $paths[] = dirname(__DIR__) . '/acf-json.backup';
        return $paths;
    }
}

