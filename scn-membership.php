<?php
/**
 * Plugin Name: SCN Membership
 * Plugin URI: https://scn.org
 * Description: A comprehensive membership management system for SCN.
 * Version: 1.0.0
 * Author: SCN Development Team
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: scn-membership
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 6.5
 * Requires PHP: 8.1
 * Network: false
 */

if (!defined('ABSPATH')) {
    exit;
}


define('SCN_MEMBERSHIP_VERSION', '1.0.0');
define('SCN_MEMBERSHIP_PATH', plugin_dir_path(__FILE__));
define('SCN_MEMBERSHIP_URL', plugin_dir_url(__FILE__));

if (file_exists(SCN_MEMBERSHIP_PATH . 'vendor/autoload.php')) {
    require_once SCN_MEMBERSHIP_PATH . 'vendor/autoload.php';
}

class SCN_Membership_Bootstrap {
    private static $instance = null;
    private $plugin = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        
        
        add_action('init', [$this, 'earlyInit'], 5);
        add_action('plugins_loaded', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        $this->ensureCourseMetaboxOnly();
    }

    public function init() {
        // Initialize ACF field groups (including bidirectional relationships)
        if (class_exists('SCN\\Membership\\ACF\\ACFFieldGroups')) {
            new SCN\Membership\ACF\ACFFieldGroups();
        }
        
        // Initialize admin functionality
        if (class_exists('SCN\\Membership\\Admin\\AdminService')) {
            SCN\Membership\Admin\AdminService::getInstance();
        }
        
        // Filter ACF relationship queries to filter courses by author
        add_filter('acf/fields/relationship/query', [$this, 'filterCoursesByAuthor'], 10, 3);
        add_filter('acf/fields/relationship/query/name=courses', [$this, 'filterCoursesByAuthorAdvanced'], 10, 3);
        
        // Add custom filter for courses field specifically
        
        // Add AJAX handler for custom course filtering
        add_action('wp_ajax_scn_get_filtered_courses', [$this, 'ajaxGetFilteredCourses']);
        
        // Add AJAX handler to check saved courses
        add_action('wp_ajax_scn_check_saved_courses', [$this, 'ajaxCheckSavedCourses']);
        
        // Add AJAX handler to get member courses
        add_action('wp_ajax_scn_get_member_courses', [$this, 'handleGetMemberCoursesAjax']);
        
        // Add JavaScript to refresh courses field when author changes
        add_action('admin_enqueue_scripts', function($hook) {
            global $post_type;
                
            // Only add script for course post type
            if ($post_type === 'course') {
                // Add inline script after jQuery is loaded
                $script = "
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('SCN: Script loaded');
                    
                    if (typeof acf !== 'undefined') {
                        
                        // Hook into ACF's conditional logic system
                        
                        
                        
                        // Target the ACF relationship field specifically
                        jQuery(document).on('click', '.acf-field-68ed3d9bd0b79 .acf-rel-item-add', function() {
                            var authorId = jQuery(this).attr('data-id');
                            console.log('SCN: Author selected:', authorId);
                            
                            // Filter courses based on selected author
                            if (authorId) {
                                filterCoursesByAuthor(authorId);
                            }
                        });
                        
                        // Also listen for changes to the hidden input values
                        jQuery(document).on('change', 'input[name=\"acf[field_68ed3d9bd0b79][]\"]', function() {
                            var authorValue = jQuery(this).val();
                            if (authorValue && authorValue !== '') {
                                console.log('SCN: Author selected:', authorValue);
                                filterCoursesByAuthor(authorValue);
                            }
                        });
                        
                        // Use MutationObserver to watch for changes in the values list
                        var observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.type === 'childList') {
                                    var target = jQuery(mutation.target);
                                    if (target.hasClass('values-list') || target.find('.values-list').length > 0) {
                                        var selectedIds = [];
                                        jQuery('.acf-field-68ed3d9bd0b79 .values-list input[type=\"hidden\"]').each(function() {
                                            var value = jQuery(this).val();
                                            if (value && value !== '') {
                                                selectedIds.push(value);
                                            }
                                        });
                                        
                                        if (selectedIds.length > 0) {
                                            console.log('SCN: Author selected:', selectedIds[0]);
                                            filterCoursesByAuthor(selectedIds[0]);
                                        } else {
                                            // No author selected - hide course field and clear values
                                            jQuery('.acf-field[data-key=\"field_68ee4dac55154\"], .acf-field[data-name=\"course\"]').hide();
                                            // Clear any existing course values
                                            jQuery('.acf-field[data-key=\"field_68ee4dac55154\"] input[type=\"hidden\"], .acf-field[data-name=\"course\"] input[type=\"hidden\"]').val('');
                                            jQuery('.acf-field[data-key=\"field_68ee4dac55154\"] select, .acf-field[data-name=\"course\"] select').val('');
                                        }
                                    }
                                }
                            });
                        });
                        
                        // Start observing
                        var valuesList = document.querySelector('.acf-field-68ed3d9bd0b79 .values-list');
                        if (valuesList) {
                            observer.observe(valuesList, { childList: true, subtree: true });
                        }
                        
                        // Function to filter courses based on author
                        function filterCoursesByAuthor(authorId) {
                            // Show the course field
                            jQuery('.acf-field[data-key=\"field_68ee4dac55154\"], .acf-field[data-name=\"course\"]').show();
                            
                            // Use ACF filter approach
                            updateCourseFieldOptions(authorId);
                        }
                        
                        // Function to populate course field with filtered courses
                        function populateCourseField(courseFieldElement, authorId) {
                            console.log('SCN: Populating course field for author:', authorId);
                            
                            jQuery.ajax({
                                url: scn_ajax.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'scn_get_filtered_courses',
                                    author_id: authorId,
                                    nonce: scn_ajax.nonce
                                },
                                success: function(response) {
                                    console.log('SCN: Course population response:', response);
                                    if (response.success && response.data.courses) {
                                        var selectField = courseFieldElement.find('select');
                                        if (selectField.length > 0) {
                                            // Get currently selected value to preserve it
                                            var currentValue = selectField.val();
                                            console.log('SCN: Current value before update:', currentValue);
                                            
                                            // If no current value, check for saved value in hidden inputs
                                            if (!currentValue) {
                                                var hiddenInput = courseFieldElement.find('input[type=\"hidden\"]');
                                                if (hiddenInput.length > 0) {
                                                    currentValue = hiddenInput.val();
                                                    console.log('SCN: Found saved value in hidden input:', currentValue);
                                                }
                                            }
                                            
                                            // Clear and repopulate the select field
                                            selectField.empty();
                                            selectField.append(\"<option value=\\\"\\\">Select Course</option>\");
                                            
                                            jQuery.each(response.data.courses, function(id, title) {
                                                var selected = (currentValue == id) ? ' selected' : '';
                                                selectField.append(\"<option value=\\\"\" + id + \"\\\"\" + selected + \">\" + title + \"</option>\");
                                            });
                                            
                                            // Restore the selected value if it exists
                                            if (currentValue) {
                                                selectField.val(currentValue);
                                                console.log('SCN: Restored selected value:', currentValue);
                                            }
                                            
                                            // Trigger multiple events to ensure ACF recognizes the change
                                            selectField.trigger('change');
                                            selectField.trigger('input');
                                            
                                            // Also trigger on the field element itself
                                            courseFieldElement.trigger('change');
                                            
                                            console.log('SCN: Populated course field with ' + response.data.courses.length + ' courses, selected value: ' + currentValue);
                                        }
                                    }
                                }
                            });
                        }
                        
                        // Function to update course field using ACF filter approach
                        function updateCourseFieldOptions(authorId) {
                            console.log('SCN: Filtering courses for author ID:', authorId);
                            
                            // Store the author ID globally so PHP can access it
                            window.scnCurrentAuthorId = authorId;
                            
                            // Find the ACF course field
                            var acfCourseField = acf.getField('field_68ee4dac55154');
                            if (acfCourseField) {
                                console.log('SCN: Course field found, setting filter data');
                                
                                // Set the author filter data for the field
                                acfCourseField.set('data', {
                                    'author_filter': authorId
                                });
                                
                                // Also set it on the field element as a data attribute
                                acfCourseField.\$el.attr('data-author-filter', authorId);
                                
                                // Try to set it in ACF's global data
                                acf.set('data', {
                                    'author_filter': authorId
                                });
                                
                                // Trigger refresh to reload with filtered data
                                acfCourseField.trigger('refresh');
                                
                                // Use the new populate function for all course fields
                                setTimeout(function() {
                                    var postId = jQuery('#post_ID').val();
                                    // Populate all existing course fields
                                    jQuery('.acf-field[data-key=\"field_68ee4dac55154\"]').each(function(index) {
                                        populateCourseField(jQuery(this), authorId, postId, index);
                                    });
                                }, 100);
                                
                                // Log the available options after refresh
                                setTimeout(function() {
                                    var options = acfCourseField.get('choices');
                                    console.log('SCN: Filtered courses available:', options);
                                    
                                    // Also log the select element options
                                    var selectElement = acfCourseField.\$el.find('select');
                                    if (selectElement.length > 0) {
                                        var selectOptions = selectElement.find('option');
                                        console.log('SCN: Course select options count:', selectOptions.length);
                                        selectOptions.each(function() {
                                            if (jQuery(this).val()) {
                                                console.log('SCN: Course option:', jQuery(this).val(), jQuery(this).text());
                                            }
                                        });
                                    }
                                }, 500);
                            } else {
                                console.log('SCN: Course field not found');
                            }
                        }
                        
                        // Specific listener for author field changes
                        acf.addAction('change', function(field) {
                            var fieldName = field.get('name');
                            var fieldKey = field.get('key');
                            var fieldType = field.get('type');
                            
                            // Check if this is specifically the author field
                            var isAuthorField = false;
                            
                            // Method 1: Check by exact name/key
                            if (fieldName === 'author' || fieldKey === 'field_68ed3d9bd0b79') {
                                isAuthorField = true;
                            }
                            // Method 2: Check if it's a relationship field with 'author' in the name
                            else if (fieldType === 'relationship' && fieldName && fieldName.toLowerCase().includes('author')) {
                                isAuthorField = true;
                            }
                            // Method 3: Check if it's a relationship field that targets members
                            else if (fieldType === 'relationship' && field.get('data') && field.get('data').post_type && field.get('data').post_type.includes('member')) {
                                isAuthorField = true;
                            }
                            
                            if (isAuthorField) {
                                var authorValue = field.val();
                                console.log('SCN: Author selected:', authorValue);
                                
                                if (authorValue && Array.isArray(authorValue) && authorValue.length > 0) {
                                    filterCoursesByAuthor(authorValue[0]);
                                } else if (authorValue && !Array.isArray(authorValue)) {
                                    filterCoursesByAuthor(authorValue);
                                } else {
                                    // Hide course field when no author selected
                                    jQuery('.acf-field[data-key=\"field_68ee4dac55154\"], .acf-field[data-name=\"course\"]').hide();
                                }
                            }
                        });
                        
                        // Listen for when fields are ready
                        acf.addAction('ready', function() {
                            // Check if there's already a selected author on page load
                            setTimeout(function() {
                                var selectedAuthorIds = [];
                                jQuery('.acf-field-68ed3d9bd0b79 .values-list input[type=\"hidden\"]').each(function() {
                                    var value = jQuery(this).val();
                                    if (value && value !== '') {
                                        selectedAuthorIds.push(value);
                                    }
                                });
                                
                                // Check existing course values on page load
                                console.log('SCN: Checking existing course values on page load...');
                                var courseValues = [];
                                jQuery('.acf-field[data-key=\"field_68ee4dac55154\"] select').each(function(index) {
                                    var selectField = jQuery(this);
                                    var value = selectField.val();
                                    var text = selectField.find('option:selected').text();
                                    courseValues.push({
                                        index: index,
                                        value: value,
                                        text: text,
                                        optionsCount: selectField.find('option').length
                                    });
                                    console.log('SCN: Course field ' + index + ' - Value:', value, 'Text:', text, 'Options:', selectField.find('option').length);
                                });
                                
                                // Also check hidden inputs for course values
                                jQuery('input[name*=\"course\"]').each(function() {
                                    var hiddenInput = jQuery(this);
                                    var name = hiddenInput.attr('name');
                                    var value = hiddenInput.val();
                                    if (value && value !== '') {
                                        console.log('SCN: Hidden course input found - Name:', name, 'Value:', value);
                                    }
                                });
                                
                                console.log('SCN: All course values on load:', courseValues);
                                
                                // Alert the current course field values
                                var fieldAlert = 'CURRENT COURSE FIELD VALUES:\\n\\n';
                                fieldAlert += 'Course Fields Found: ' + courseValues.length + '\\n\\n';
                                for (var i = 0; i < courseValues.length; i++) {
                                    fieldAlert += 'Field ' + i + ': Value=' + courseValues[i].value + ', Text=' + courseValues[i].text + ', Options=' + courseValues[i].optionsCount + '\\n';
                                }
                                alert(fieldAlert);
                                
                                // Check what's actually saved in the database
                                var postId = jQuery('#post_ID').val();
                                if (postId) {
                                    console.log('SCN: Checking saved courses in database for post:', postId);
                                    jQuery.ajax({
                                        url: scn_ajax.ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'scn_check_saved_courses',
                                            post_id: postId,
                                            nonce: scn_ajax.nonce
                                        },
                                        success: function(response) {
                                            console.log('SCN: Database check response:', response);
                                            if (response.success && response.data.saved_courses && response.data.saved_courses.length > 0) {
                                                console.log('SCN: Found saved courses:', response.data.saved_courses);
                                                
                                                // Get the courses repeater field
                                                var coursesField = acf.getField('field_68ee4c4abafb8');
                                                if (coursesField) {
                                                    console.log('SCN: Found courses repeater field, populating with saved courses');
                                                    
                                                    // Clear existing rows first (except clone row)
                                                    coursesField.find('.acf-row').not('.acf-clone').remove();
                                                    
                                                    // Add saved courses as new rows
                                                    response.data.saved_courses.forEach(function(courseData, index) {
                                                        if (courseData.course_id) {
                                                            console.log('SCN: Adding course row ' + (index + 1) + ' with course ID: ' + courseData.course_id);
                                                            
                                                            // Add new row to repeater
                                                            coursesField.add();
                                                            
                                                            // Get the newly added row
                                                            var newRow = coursesField.find('.acf-row').not('.acf-clone').eq(index);
                                                            
                                                            // Set the course value in the new row
                                                            var courseSelect = newRow.find('select[name*=\"[course]\"]');
                                                            if (courseSelect.length > 0) {
                                                                courseSelect.val(courseData.course_id);
                                                                courseSelect.trigger('change');
                                                                console.log('SCN: Set course select value to: ' + courseData.course_id);
                                                            }
                                                        }
                                                    });
                                                    
                                                    console.log('SCN: Successfully populated ' + response.data.saved_courses.length + ' saved courses in repeater');
                                                } else {
                                                    console.log('SCN: Courses repeater field not found');
                                                }
                                            } else {
                                                console.log('SCN: No saved courses found or error in response');
                                            }
                                        }
                                    });
                                }
                                
                                if (selectedAuthorIds.length > 0) {
                                    console.log('SCN: Author selected on page load:', selectedAuthorIds[0]);
                                    // Store the author ID globally
                                    window.scnCurrentAuthorId = selectedAuthorIds[0];
                                    filterCoursesByAuthor(selectedAuthorIds[0]);
                                } else {
                                    console.log('SCN: No author selected on page load - keeping existing course values');
                                    // Don't clear existing course values, just hide the field
                                    jQuery('.acf-field[data-key=\"field_68ee4dac55154\"], .acf-field[data-name=\"course\"]').hide();
                                }
                            }, 1000); // Wait 1 second for fields to fully initialize
                        });
                        
                        // Listen for when new repeater rows are added
                        acf.addAction('new_field/name=courses', function(field) {
                            console.log('SCN: New courses repeater row added');
                            
                            // Wait a bit for the field to be fully initialized
                            setTimeout(function() {
                                // Find the course field in the new row
                                var courseField = field.find('[data-key=\"field_68ee4dac55154\"]');
                                if (courseField.length > 0 && window.scnCurrentAuthorId) {
                                    console.log('SCN: Repopulating course field for new row with author:', window.scnCurrentAuthorId);
                                    populateCourseField(courseField, window.scnCurrentAuthorId);
                                }
                            }, 500);
                        });
                        
                        // Listen for course field changes to ensure ACF tracks the value
                        acf.addAction('change/name=course', function(field) {
                            console.log('SCN: Course field changed, value:', field.val());
                            
                            // Ensure the field value is properly set in ACF's internal state
                            var fieldValue = field.val();
                            if (fieldValue) {
                                // Update the field's internal value
                                field.set('value', fieldValue);
                                console.log('SCN: Updated ACF field value to:', fieldValue);
                            }
                        });
                        
                        // Listen for form submission to ensure all course values are properly set
                        jQuery(document).on('submit', '#post', function(e) {
                            console.log('SCN: Form submission detected, ensuring course values are set');
                            
                            // Find all course fields and ensure their values are properly set
                            jQuery('.acf-field[data-key=\"field_68ee4dac55154\"] select').each(function() {
                                var selectField = jQuery(this);
                                var fieldValue = selectField.val();
                                
                                if (fieldValue) {
                                    console.log('SCN: Course field has value on submit:', fieldValue);
                                    
                                    // Trigger change event to ensure ACF processes the value
                                    selectField.trigger('change');
                                    
                                    // Also set the value in any hidden inputs that ACF might use
                                    var fieldContainer = selectField.closest('.acf-field');
                                    var hiddenInputs = fieldContainer.find('input[type=\"hidden\"]');
                                    hiddenInputs.each(function() {
                                        if (jQuery(this).attr('name') && jQuery(this).attr('name').indexOf('course') !== -1) {
                                            jQuery(this).val(fieldValue);
                                            console.log('SCN: Set hidden input value:', fieldValue);
                                        }
                                    });
                                }
                            });
                        });
                    }
                });
                ";
                
                // Add this script on all admin pages for now (to test)
                if (is_admin()) {
                    // Check if we're on a post edit page and log saved course values
                    global $post;
                    if ($post && $post->ID) {
                        error_log('SCN: Post ID on load: ' . $post->ID);
                        
                        // Get the saved courses field value
                        $saved_courses = get_field('courses', $post->ID);
                        error_log('SCN: Saved courses field value: ' . print_r($saved_courses, true));
                        
                        // Also check raw post meta
                        $raw_courses_meta = get_post_meta($post->ID, 'courses', true);
                        error_log('SCN: Raw courses meta: ' . print_r($raw_courses_meta, true));
                        
                        // Check if there are any course sub-field values
                        if (is_array($saved_courses)) {
                            foreach ($saved_courses as $index => $course_row) {
                                if (is_array($course_row) && isset($course_row['course'])) {
                                    error_log('SCN: Course row ' . $index . ' - Course value: ' . print_r($course_row['course'], true));
                                }
                            }
                        }
                    }
                    
                    wp_localize_script('jquery', 'scn_ajax', [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('scn_filter_nonce')
                    ]);
                    wp_add_inline_script('jquery', $script);
                }
            }
        });
        
        // Filter ACF relationship queries to filter courses by author
        add_filter('acf/fields/relationship/query', [$this, 'filterCoursesByAuthor'], 10, 3);
        add_filter('acf/fields/relationship/query/name=courses', [$this, 'filterCoursesByAuthorAdvanced'], 10, 3);
        
        // Add custom filter for courses field specifically
        
        
        // Add AJAX handler for custom course filtering
        add_action('wp_ajax_scn_get_filtered_courses', [$this, 'ajaxGetFilteredCourses']);
        
        // Add AJAX handler to check saved courses
        add_action('wp_ajax_scn_check_saved_courses', [$this, 'ajaxCheckSavedCourses']);
        
        // Also try filtering at the WP_Query level
        add_action('pre_get_posts', [$this, 'filterCourseQueryForEvents']);
        
        // Try a more direct approach - filter the posts query directly
        add_filter('posts_where', [$this, 'filterCoursesInWhere'], 10, 2);
        
        
        // Add AJAX endpoint to test the filter
        add_action('wp_ajax_test_course_filter', function() {
            $post_id = intval($_POST['post_id']);
            $author_id = intval($_POST['author_id']);
            
            
            // Get courses by this author
            $courses = get_posts([
                'post_type' => 'course',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'author',
                        'value' => $author_id,
                        'compare' => 'LIKE'
                    ]
                ]
            ]);
            
            echo "Found " . count($courses) . " courses for author $author_id:\n";
            foreach ($courses as $course) {
                echo "- " . $course->post_title . " (ID: " . $course->ID . ")\n";
            }
            
            wp_die();
        });
        
        add_action('wp_ajax_scn_get_member_courses', [$this, 'handleGetMemberCoursesAjax']);
        
        
        
        
        if (class_exists('SCN\\Membership\\Core\\Plugin')) {
            $this->checkVersion();
            
            error_log("SCN Membership: Creating Plugin instance");
            $this->plugin = new SCN\Membership\Core\Plugin();
            $this->plugin->register();
            error_log("SCN Membership: Plugin registered");
            
            // Initialize database
            $database = new SCN\Membership\Infra\Database();
            $database->register();
            
            // Initialize admin service
            $admin_service = SCN\Membership\Admin\AdminService::getInstance();
            
            // Initialize email confirmation system
            require_once SCN_MEMBERSHIP_PATH . 'includes/email-confirmation.php';
            
            // Initialize email debug system
            require_once SCN_MEMBERSHIP_PATH . 'includes/email-debug.php';
            
            // Initialize file renamer for member uploads
            require_once SCN_MEMBERSHIP_PATH . 'includes/rename-uploaded-files.php';
            
            // Initialize media AJAX handlers
            require_once SCN_MEMBERSHIP_PATH . 'includes/media-ajax-handlers.php';
            
            // Initialize admin fix tools
            if (is_admin()) {
                require_once SCN_MEMBERSHIP_PATH . 'admin-fix-member-561.php';
            }
            
            // Enqueue global full-width override CSS
            add_action('wp_enqueue_scripts', [$this, 'enqueueGlobalStyles']);
            
            
            // Remove WordPress admin bar from frontend
            add_action('init', [$this, 'removeAdminBar']);
            
            // Ensure sample topics exist
            add_action('init', [$this, 'ensureSampleTopics'], 20);
        add_action('init', [$this, 'createMembersProfilePage'], 30);
            
        }
    }

    public function earlyInit() {
        if (class_exists('SCN\\Membership\\Core\\Plugin')) {
            // Force early registration of post types and taxonomies
            $this->plugin = new SCN\Membership\Core\Plugin();
            $this->plugin->register();
        }
        $this->ensureRewriteRules(); // Re-enabled for template handling
    }

    public function activate() {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        $this->checkRequirements();
        $this->setupDatabase();
        $this->addCapabilities();
        $this->flushRewriteRules();
        $this->setActivationFlag();
    }

    public function deactivate() {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        $this->flushRewriteRules();
        $this->clearActivationFlag();
    }

    private function checkRequirements() {
        global $wp_version;
        
        if (version_compare($wp_version, '6.5', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('SCN Membership requires WordPress 6.5 or higher.', 'scn-membership'),
                __('Plugin Activation Error', 'scn-membership'),
                ['back_link' => true]
            );
        }

        if (version_compare(PHP_VERSION, '8.1', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('SCN Membership requires PHP 8.1 or higher.', 'scn-membership'),
                __('Plugin Activation Error', 'scn-membership'),
                ['back_link' => true]
            );
        }
    }

    private function setupDatabase() {
        try {
            if (class_exists('SCN\\Membership\\Infra\\Database')) {
                $database = new SCN\Membership\Infra\Database();
                $database->createTables();
            }
        } catch (Exception $e) {
            error_log('SCN Membership: Database setup failed - ' . $e->getMessage());
            wp_die(
                __('Database setup failed. Please check error logs.', 'scn-membership'),
                __('Plugin Activation Error', 'scn-membership'),
                ['back_link' => true]
            );
        }
    }

    private function addCapabilities() {
        try {
            $roles = ['administrator', 'editor', 'author'];
            
            foreach ($roles as $role_name) {
                $role = get_role($role_name);
                if (!$role) continue;

                $capabilities = $this->getCapabilitiesForRole($role_name);
                foreach ($capabilities as $cap) {
                    $role->add_cap($cap);
                }
            }
        } catch (Exception $e) {
            error_log('SCN Membership: Capability assignment failed - ' . $e->getMessage());
        }
    }

    private function getCapabilitiesForRole($role_name) {
        $base_capabilities = [
            'administrator' => [
                'edit_members', 'edit_others_members', 'publish_members',
                'read_private_members', 'delete_members', 'delete_private_members',
                'delete_published_members', 'delete_others_members',
                'edit_private_members', 'edit_published_members',
                'edit_courses', 'edit_others_courses', 'publish_courses',
                'read_private_courses', 'delete_courses', 'delete_private_courses',
                'delete_published_courses', 'delete_others_courses',
                'edit_private_courses', 'edit_published_courses',
                'manage_scn_sessions', 'approve_scn_sessions'
            ],
            'editor' => [
                'edit_members', 'edit_others_members', 'publish_members',
                'read_private_members', 'delete_members', 'delete_others_members',
                'delete_published_members', 'edit_published_members',
                'edit_courses', 'edit_others_courses', 'publish_courses',
                'read_private_courses', 'delete_courses', 'delete_others_courses',
                'delete_published_courses', 'edit_published_courses',
                'manage_scn_sessions', 'approve_scn_sessions'
            ],
            'author' => [
                'edit_members', 'publish_members', 'delete_members',
                'edit_published_members', 'edit_courses', 'publish_courses',
                'delete_courses', 'edit_published_courses', 'create_scn_sessions'
            ]
        ];

        return $base_capabilities[$role_name] ?? [];
    }

    private function flushRewriteRules() {
        flush_rewrite_rules();
    }

    private function ensureRewriteRules() {
        // Ensure authentication rewrite rules are registered
        add_rewrite_rule(
            '^member-login/?$',
            'index.php?member_login=1',
            'top'
        );
        
        add_rewrite_rule(
            '^member-register/?$',
            'index.php?member_register=1',
            'top'
        );
        
        add_rewrite_rule(
            '^member-dashboard/?$',
            'index.php?member_dashboard=1',
            'top'
        );
        
        add_rewrite_rule(
            '^member-logout/?$',
            'index.php?member_logout=1',
            'top'
        );
        
        add_rewrite_rule(
            '^test-auth/?$',
            'index.php?scn_test_auth=1',
            'top'
        );
        
        // Add query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'member_login';
            $vars[] = 'member_register';
            $vars[] = 'member_dashboard';
            $vars[] = 'member_logout';
            $vars[] = 'scn_test_auth';
            return $vars;
        });
        
        // FRONTEND ONLY: Template handlers should NEVER run on admin pages
        // Handle template inclusion with template_redirect action for better control
        add_action('template_redirect', function() {
            // CRITICAL: Only run on frontend, NEVER on admin
            if (is_admin()) {
                return;
            }
            
            // Single member post template - check by post type directly
            if (get_post_type() === 'member' && is_singular()) {
                $custom_template = SCN_MEMBERSHIP_PATH . 'templates/member/single-member.php';
                error_log('SCN Template: Looking for member template (by post_type) ' . $custom_template . ' | Exists: ' . (file_exists($custom_template) ? 'true' : 'false'));
                if (file_exists($custom_template)) {
                    error_log('SCN Template: Using member template ' . $custom_template);
                    include($custom_template);
                    exit;
                } else {
                    error_log('SCN Template: Member template not found at ' . $custom_template);
                }
            }
            
            // Also try is_singular('member') as backup
            if (is_singular('member')) {
                $custom_template = SCN_MEMBERSHIP_PATH . 'templates/member/single-member.php';
                error_log('SCN Template: Looking for member template (by is_singular) ' . $custom_template . ' | Exists: ' . (file_exists($custom_template) ? 'true' : 'false'));
                if (file_exists($custom_template)) {
                    error_log('SCN Template: Using member template ' . $custom_template);
                    include($custom_template);
                    exit;
                } else {
                    error_log('SCN Template: Member template not found at ' . $custom_template);
                }
            }
        }, 20);
        
        // FRONTEND ONLY: single_template filter
        add_filter('single_template', function($template) {
            // CRITICAL: Only run on frontend, NEVER on admin
            if (is_admin()) {
                return $template;
            }
            
            // Check by post type directly first
            if (get_post_type() === 'member' && is_singular()) {
                $plugin_template = SCN_MEMBERSHIP_PATH . 'templates/member/single-member.php';
                if (file_exists($plugin_template)) {
                    error_log('SCN Template: Using single_template filter for member (by post_type)');
                    return $plugin_template;
                }
            }
            
            // Also try is_singular('member') as backup
            if (is_singular('member')) {
                $plugin_template = SCN_MEMBERSHIP_PATH . 'templates/member/single-member.php';
                if (file_exists($plugin_template)) {
                    error_log('SCN Template: Using single_template filter for member (by is_singular)');
                    return $plugin_template;
                }
            }
            
            return $template;
        });
        
        // FRONTEND ONLY: get_template_part filter
        add_filter('get_template_part', function($slug, $name) {
            // CRITICAL: Only run on frontend, NEVER on admin
            if (is_admin()) {
                return null;
            }
            
            if ($slug === 'single' && $name === 'member') {
                $custom_template = SCN_MEMBERSHIP_PATH . 'templates/member/single-member.php';
                if (file_exists($custom_template)) {
                    error_log('SCN Template: Using get_template_part filter for member');
                    include($custom_template);
                    exit;
                }
            }
            
            return null;
        }, 20, 2);
        
        // FRONTEND ONLY: template_include filter
        add_filter('template_include', function($template) {
            // CRITICAL: Only run on frontend, NEVER on admin
            if (is_admin()) {
                return $template;
            }
            
            // Debug: Log template loading
            error_log('SCN Template Check: ' . $template . ' | is_singular(member): ' . (is_singular('member') ? 'true' : 'false'));
            error_log('SCN Template Check: get_post_type(): ' . get_post_type() . ' | is_singular(): ' . (is_singular() ? 'true' : 'false'));
            
            // Single member post template - check by post type directly
            if (get_post_type() === 'member' && is_singular()) {
                $custom_template = SCN_MEMBERSHIP_PATH . 'templates/member/single-member.php';
                if (file_exists($custom_template)) {
                    error_log('SCN Template: Using fallback template_include filter for member (by post_type)');
                    return $custom_template;
                }
            }
            
            // Also try is_singular('member') as backup
            if (is_singular('member')) {
                $custom_template = SCN_MEMBERSHIP_PATH . 'templates/member/single-member.php';
                if (file_exists($custom_template)) {
                    error_log('SCN Template: Using fallback template_include filter for member (by is_singular)');
                    return $custom_template;
                }
            }
            
            if (get_query_var('member_login')) {
                return SCN_MEMBERSHIP_PATH . 'templates/auth/member-login.php';
            }
            if (get_query_var('member_register')) {
                return SCN_MEMBERSHIP_PATH . 'templates/auth/member-register.php';
            }
        if (get_query_var('member_dashboard')) {
            return SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard-fixed.php';
        }
            if (get_query_var('member_logout')) {
                if (is_user_logged_in()) {
                    wp_logout();
                }
                wp_redirect(home_url('/member-login/'));
                exit;
            }
            if (get_query_var('scn_test_auth')) {
                return SCN_MEMBERSHIP_PATH . 'templates/auth/test-auth.php';
            }
            return $template;
        }, 20); // Lower priority to avoid interfering with admin pages
    }

    private function setActivationFlag() {
        update_option('membership_activated', time());
        update_option('membership_version', SCN_MEMBERSHIP_VERSION);
    }

    private function clearActivationFlag() {
        delete_option('membership_activated');
    }

    private function checkVersion() {
        $installed_version = get_option('membership_version', '0.0.0');
        
        if (version_compare($installed_version, SCN_MEMBERSHIP_VERSION, '<')) {
            $this->upgrade($installed_version, SCN_MEMBERSHIP_VERSION);
        }
    }

    private function upgrade($from_version, $to_version) {
        if (version_compare($from_version, '1.0.0', '<')) {
            $this->upgradeToV1();
        }
        
        update_option('membership_version', $to_version);
    }

    private function upgradeToV1() {
        $this->setupDatabase();
        $this->addCapabilities();
        flush_rewrite_rules();
    }

    private function ensureCourseMetaboxOnly() {
        add_action('admin_init', function() {
            if (post_type_exists('course')) {
                // Keep title and editor support for course post type
                // ACF fields are used for content, but WordPress fields are available as backup
            }
            
            // Debug: Check current user capabilities
            $current_user = wp_get_current_user();
            error_log('SCN Debug: Current user ID: ' . $current_user->ID . ', roles: ' . implode(', ', $current_user->roles));
            error_log('SCN Debug: Can edit courses: ' . (current_user_can('edit_courses') ? 'YES' : 'NO'));
            error_log('SCN Debug: Can delete courses: ' . (current_user_can('delete_courses') ? 'YES' : 'NO'));
            error_log('SCN Debug: Can edit posts: ' . (current_user_can('edit_posts') ? 'YES' : 'NO'));
            
            // Check if capabilities exist in database
            $user_caps = $current_user->allcaps;
            $scn_caps = array_filter($user_caps, function($key) {
                return strpos($key, 'scn_') === 0;
            }, ARRAY_FILTER_USE_KEY);
            error_log('SCN Debug: User SCN capabilities: ' . print_r($scn_caps, true));
        });

        add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
            if ($post_type === 'course') {
                return false;
            }
            return $use_block_editor;
        }, 10, 2);

        // Single consolidated save_post hook to prevent infinite loops
        add_action('save_post_course', [$this, 'handleCourseSave'], 20, 3);

        // Removed single-column layout enforcement for course CPT
        $this->fixValidationMessages();
        $this->renameFeaturedImageMetabox();
        $this->ensureProfileMetaboxOnly();
    }

    public function handleCourseSave($post_id, $post, $update) {
        // Prevent infinite loops
        static $processing = false;
        if ($processing) {
            error_log('SCN Course Save: Already processing, preventing loop');
            return;
        }
        $processing = true;
        
        error_log('SCN Course Save: Starting handleCourseSave for post_id: ' . $post_id . ', status: ' . $post->post_status);
        error_log('SCN Course Save: POST data keys: ' . implode(', ', array_keys($_POST)));
        error_log('SCN Course Save: POST status: ' . ($_POST['post_status'] ?? 'not set'));

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log('SCN Course Save: Autosave detected, skipping');
            $processing = false;
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            error_log('SCN Course Save: User lacks edit_post capability');
            $processing = false;
            return;
        }

        // Auto-generate title from subtitle
        $subtitle = '';
        if (isset($_POST['course_subtitle'])) {
            $subtitle = trim((string) $_POST['course_subtitle']);
        } else {
            // Fallback to get from meta if not in POST
            $subtitle = trim((string) get_post_meta($post_id, 'course_subtitle', true));
        }
        
        error_log('SCN Course Save: Subtitle found: "' . $subtitle . '", Current title: "' . $post->post_title . '"');
        
        if ($subtitle && ('' === $post->post_title || $post->post_title === 'Auto Draft')) {
            error_log('SCN Course Save: Updating title to: "' . $subtitle . '"');
            wp_update_post([
                'ID' => $post_id,
                'post_title' => wp_strip_all_tags($subtitle),
                'post_name' => sanitize_title($subtitle),
            ]);
        }

        // Validate course image - only when trying to publish
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (empty($thumbnail_id)) {
            // Only validate if trying to publish (not draft or auto-draft)
            if ($post->post_status === 'publish' || (isset($_POST['post_status']) && $_POST['post_status'] === 'publish')) {
                error_log('SCN Course Save: No course image found, preventing publish');
                set_transient('course_image_error_' . $post_id, 'Course Image is required to publish.', 60);
                // Prevent publish and revert to draft
                wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
            }
        }

        $processing = false;
    }



    private function fixValidationMessages() {
        // Clear validation transients on new post page load
        add_action('admin_init', function() {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || !in_array($screen->post_type, ['course', 'course'], true)) return;
            if ($screen->base === 'post-new') {
                delete_transient('course_validation_error');
            }
        });

        // Server-side validation guard - only show after failed save attempts
        add_action('admin_notices', function() {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || !in_array($screen->post_type, ['course', 'course'], true)) return;
            if (!in_array($screen->base, ['post', 'post-new'], true)) return;

            global $post;
            if (!$post) return;

            // Check for course image error
            $image_error = get_transient('course_image_error_' . $post->ID);
            if ($image_error) {
                delete_transient('course_image_error_' . $post->ID);
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($image_error) . '</p></div>';
            }
        });

        // Client-side validation guard
        add_action('admin_enqueue_scripts', function() {
            $s = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$s || !in_array($s->post_type, ['course', 'course'], true)) return;
            if (!in_array($s->base, ['post', 'post-new'], true)) return;

            wp_register_script(
                'scn-course-image-validate',
                false,
                ['jquery'],
                '1.0',
                true
            );
            wp_enqueue_script('scn-course-image-validate');
            wp_add_inline_script('scn-course-image-validate', <<<JS
(function($){
    if (window.__scnCourseImageValidationBound) return;
    window.__scnCourseImageValidationBound = true;

    // Clear any existing validation errors on page load
    $(document).ready(function() {
        $('#scn-image-error').remove();
    });

    function hasCourseImage(){
        return $('#set-post-thumbnail img').length > 0 || $('#postimagediv .inside img').length > 0;
    }

    // Validate on submit only
    $('#post').on('submit', function(e){
        if (!hasCourseImage()) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (!$('#scn-image-error').length) {
                $('<div id="scn-image-error" class="notice notice-error"><p>Course Image is required to publish.</p></div>')
                    .insertBefore('#poststuff');
            }
        }
    });

    // Clear notice when user sets an image
    $(document).on('click', '#set-post-thumbnail-link, #remove-post-thumbnail', function(){
        setTimeout(function(){
            $('#scn-image-error').remove();
        }, 500);
    });
})(jQuery);
JS);
        });
    }

    private function renameFeaturedImageMetabox() {
        // Rename featured image metabox for courses with very high priority
        add_action('add_meta_boxes_course', function() {
            remove_meta_box('postimagediv', 'course', 'side');
            add_meta_box(
                'postimagediv',
                __('Course Image', 'scn-membership'),
                'post_thumbnail_meta_box',
                'course',
                'side',
                'high'
            );
        }, 99);
        
        // Force rename via JavaScript as fallback for courses
        add_action('admin_head-post.php', function() {
            global $post_type;
            if ($post_type === 'course') {
                echo '<script>
                jQuery(document).ready(function($) {
                    $("#postimagediv h2, #postimagediv .hndle").text("Course Image");
                    $("#set-post-thumbnail").attr("aria-label", "Set course image");
                });
                </script>';
            }
        });
        
        add_action('admin_head-post-new.php', function() {
            global $post_type;
            if ($post_type === 'course') {
                echo '<script>
                jQuery(document).ready(function($) {
                    $("#postimagediv h2, #postimagediv .hndle").text("Course Image");
                    $("#set-post-thumbnail").attr("aria-label", "Set course image");
                });
                </script>';
            }
        });
        
        // Rename featured image metabox for profiles
        add_action('add_meta_boxes_member', function() {
            remove_meta_box('postimagediv', 'member', 'side');
            add_meta_box(
                'postimagediv',
                __('Profile Image', 'scn-membership'),
                'post_thumbnail_meta_box',
                'member',
                'side',
                'high'
            );
        }, 99);
        
    }

    private function ensureProfileMetaboxOnly() {
        add_action('admin_init', function() {
            if (post_type_exists('member')) {
                // Keep title support but make it readonly
                // remove_post_type_support('member', 'title');
                remove_post_type_support('member', 'editor');
            }
        });
        
        // Make title field readonly and add real-time auto-generation
        add_action('admin_head-post.php', function() {
            global $post_type;
            if ($post_type === 'member') {
                echo '<style>
                    #title-prompt-text { display: none !important; }
                    #title { 
                        background-color: #f0f0f0 !important; 
                        cursor: not-allowed !important;
                        pointer-events: none !important;
                    }
                </style>';
                echo '<script>
                    jQuery(document).ready(function($) {
                        $("#title").attr("readonly", true);
                        $("#title").attr("placeholder", "Auto-generated from First Name, Last Name, Credentials");
                        
                        // Function to generate title from ACF fields in basic_info group
                        function updateMemberTitle() {
                            var firstName = "";
                            var lastName = "";
                            var credentials = "";
                            
                            // Fields are in basic_info group: acf[field_basic_info][first_name]
                            var firstNameField = $("input[name*=\'basic_info\'][name*=\'first_name\']");
                            var lastNameField = $("input[name*=\'basic_info\'][name*=\'last_name\']");
                            var credentialsField = $("input[name*=\'basic_info\'][name*=\'credentials\']");
                            
                            console.log("First Name Field Found:", firstNameField.length);
                            console.log("Last Name Field Found:", lastNameField.length);
                            console.log("Credentials Field Found:", credentialsField.length);
                            
                            if (firstNameField.length) {
                                firstName = $.trim(firstNameField.val() || "");
                                lastName = $.trim(lastNameField.val() || "");
                                credentials = $.trim(credentialsField.val() || "");
                                
                                console.log("Values - First:", firstName, "Last:", lastName, "Creds:", credentials);
                            }
                            
                            // Generate title: Firstname Lastname, credentials
                            if (firstName && lastName) {
                                var titleFirstName = firstName.charAt(0).toUpperCase() + firstName.slice(1).toLowerCase();
                                var titleLastName = lastName.charAt(0).toUpperCase() + lastName.slice(1).toLowerCase();
                                var title = titleFirstName + " " + titleLastName;
                                if (credentials) {
                                    title += ", " + credentials;
                                }
                                console.log("Generated Title:", title);
                                $("#title").val(title);
                                $("#title-prompt-text").hide();
                            } else {
                                console.log("Not enough data to generate title");
                            }
                        }
                        
                        // Watch for changes on ACF fields in basic_info group
                        $(document).on("input change blur keyup", "input[name*=\'basic_info\'][name*=\'first_name\'], input[name*=\'basic_info\'][name*=\'last_name\'], input[name*=\'basic_info\'][name*=\'credentials\']", function() {
                            console.log("Field changed:", $(this).attr("name"));
                            updateMemberTitle();
                        });
                        
                        // Also trigger on initial load
                        setTimeout(updateMemberTitle, 500);
                    });
                </script>';
            }
        });
        
        add_action('admin_head-post-new.php', function() {
            global $post_type;
            if ($post_type === 'member') {
                echo '<style>
                    #title-prompt-text { display: none !important; }
                    #title { 
                        background-color: #f0f0f0 !important; 
                        cursor: not-allowed !important;
                        pointer-events: none !important;
                    }
                </style>';
                echo '<script>
                    jQuery(document).ready(function($) {
                        $("#title").attr("readonly", true);
                        $("#title").attr("placeholder", "Auto-generated from First Name, Last Name, Credentials");
                        
                        // Function to generate title from ACF fields in basic_info group
                        function updateMemberTitle() {
                            var firstName = "";
                            var lastName = "";
                            var credentials = "";
                            
                            // Fields are in basic_info group: acf[field_basic_info][first_name]
                            var firstNameField = $("input[name*=\'basic_info\'][name*=\'first_name\']");
                            var lastNameField = $("input[name*=\'basic_info\'][name*=\'last_name\']");
                            var credentialsField = $("input[name*=\'basic_info\'][name*=\'credentials\']");
                            
                            console.log("First Name Field Found:", firstNameField.length);
                            console.log("Last Name Field Found:", lastNameField.length);
                            console.log("Credentials Field Found:", credentialsField.length);
                            
                            if (firstNameField.length) {
                                firstName = $.trim(firstNameField.val() || "");
                                lastName = $.trim(lastNameField.val() || "");
                                credentials = $.trim(credentialsField.val() || "");
                                
                                console.log("Values - First:", firstName, "Last:", lastName, "Creds:", credentials);
                            }
                            
                            // Generate title: Firstname Lastname, credentials (capitalize first letter only)
                            if (firstName && lastName) {
                                var titleFirstName = firstName.charAt(0).toUpperCase() + firstName.slice(1).toLowerCase();
                                var titleLastName = lastName.charAt(0).toUpperCase() + lastName.slice(1).toLowerCase();
                                var title = titleFirstName + " " + titleLastName;
                                if (credentials) {
                                    title += ", " + credentials;
                                }
                                console.log("Generated Title:", title);
                                $("#title").val(title);
                                $("#title-prompt-text").hide();
                            } else {
                                console.log("Not enough data to generate title");
                            }
                        }
                        
                        // Watch for changes on ACF fields in basic_info group
                        $(document).on("input change blur keyup", "input[name*=\'basic_info\'][name*=\'first_name\'], input[name*=\'basic_info\'][name*=\'last_name\'], input[name*=\'basic_info\'][name*=\'credentials\']", function() {
                            console.log("Field changed:", $(this).attr("name"));
                            updateMemberTitle();
                        });
                        
                        // Also trigger on initial load
                        setTimeout(updateMemberTitle, 500);
                    });
                </script>';
            }
        });
        
        // Rename Featured Image to Profile Image
        add_filter('post_type_labels_member', function($labels) {
            $labels->featured_image = 'Profile Image';
            $labels->set_featured_image = 'Set profile image';
            $labels->remove_featured_image = 'Remove profile image';
            $labels->use_featured_image = 'Use as profile image';
            return $labels;
        });

        add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
            if ($post_type === 'member') {
                return false;
            }
            return $use_block_editor;
        }, 10, 2);

        // Single consolidated save_post hook for profiles
        add_action('save_post_member', [$this, 'handleProfileSave'], 20, 3);

        // Removed single column layout enforcement for member post type
    }

    public function handleProfileSave($post_id, $post, $update) {
        // Prevent infinite loops
        static $processing = false;
        if ($processing) {
            error_log('SCN Profile Save: Already processing, preventing loop');
            return;
        }
        $processing = true;
        
        error_log('SCN Profile Save: Starting handleProfileSave for post_id: ' . $post_id . ', status: ' . $post->post_status);

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log('SCN Profile Save: Autosave detected, skipping');
            $processing = false;
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            error_log('SCN Profile Save: User lacks edit_post capability');
            $processing = false;
            return;
        }

        // Auto-generate title and slug from name + credentials
        $first_name = '';
        $last_name = '';
        $credentials = '';
        
        // Try to get from ACF fields (works after ACF has saved)
        if (function_exists('get_field')) {
            // Try basic_info group structure first
            $basic_info = get_field('basic_info', $post_id);
            if ($basic_info && is_array($basic_info)) {
                $first_name = trim((string) ($basic_info['first_name'] ?? ''));
                $last_name = trim((string) ($basic_info['last_name'] ?? ''));
                $credentials = trim((string) ($basic_info['credentials'] ?? ''));
            }
            
            // Try direct fields if basic_info didn't work
            if (empty($first_name) && empty($last_name)) {
                $first_name = trim((string) get_field('scn_first_name', $post_id));
                $last_name = trim((string) get_field('scn_last_name', $post_id));
                $credentials = trim((string) get_field('scn_credentials', $post_id));
            }
        }
        
        // Fallback to $_POST if ACF fields not yet saved
        if (empty($first_name) && isset($_POST['acf'])) {
            // ACF stores data in $_POST['acf'] array with field keys
            foreach ($_POST['acf'] as $key => $value) {
                if (is_array($value)) {
                    // Check if it's the basic_info group
                    if (isset($value['first_name'])) {
                        $first_name = trim((string) $value['first_name']);
                        $last_name = trim((string) ($value['last_name'] ?? ''));
                        $credentials = trim((string) ($value['credentials'] ?? ''));
                        break;
                    }
                }
            }
        }
        
        error_log('SCN Profile Save: First: "' . $first_name . '", Last: "' . $last_name . '", Credentials: "' . $credentials . '"');
        
        // Build the formatted title and slug
        if ($first_name && $last_name) {
            // Title format: Firstname Lastname, credentials (capitalize first letter only)
            $title = ucfirst(strtolower($first_name)) . ' ' . ucfirst(strtolower($last_name));
            if ($credentials) {
                $title .= ', ' . $credentials;
            }
            
            // Slug format: firstname-lastname-credentials
            $slug_parts = [strtolower($first_name), strtolower($last_name)];
            if ($credentials) {
                $slug_parts[] = strtolower($credentials);
            }
            $slug = sanitize_title(implode('-', $slug_parts));
            
            error_log('SCN Profile Save: Generated title: "' . $title . '", slug: "' . $slug . '"');
            
            // Always update if we have name data
            remove_action('save_post_member', [$this, 'handleProfileSave'], 20);
            wp_update_post([
                'ID' => $post_id,
                'post_title' => wp_strip_all_tags($title),
                'post_name' => $slug,
            ]);
            add_action('save_post_member', [$this, 'handleProfileSave'], 20, 3);
            
            error_log('SCN Profile Save: Title and slug updated');
        }

        $processing = false;
    }




    private function sanitizeWebsite($url) {
        if (empty($url)) {
            return '';
        }

        $url = trim($url);
        
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        $normalized_url = filter_var($url, FILTER_SANITIZE_URL);
        
        if (!filter_var($normalized_url, FILTER_VALIDATE_URL)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Please enter a valid website URL.', 'scn-membership') . '</p></div>';
            });
            return '';
        }

        return $normalized_url;
    }

    
    public function enqueueGlobalStyles() {
        wp_enqueue_style(
            'scn-full-width-override',
            SCN_MEMBERSHIP_URL . 'assets/css/full-width-override.css',
            [],
            SCN_MEMBERSHIP_VERSION
        );
        
        // Enqueue profile styles for single member posts
        if (is_singular('member')) {
            // Enqueue Dashicons for member pages
            wp_enqueue_style('dashicons');
            
            wp_enqueue_style(
                'scn-profile-styles',
                SCN_MEMBERSHIP_URL . 'assets/css/style.css',
                [],
                SCN_MEMBERSHIP_VERSION
            );
            // Script enqueued in FrontendTemplates.php to avoid duplication
        }
        
        // Add CSS to hide admin bar
        wp_add_inline_style('scn-full-width-override', '
            #wpadminbar { display: none !important; }
            html { margin-top: 0 !important; }
            body.admin-bar { padding-top: 0 !important; }
        ');
    }

    public function removeAdminBar() {
        // Remove admin bar for all users on frontend
        add_filter('show_admin_bar', '__return_false');
        
        // Remove admin bar CSS and JS
        remove_action('wp_head', '_admin_bar_bump_cb');
        remove_action('wp_head', 'wp_admin_bar_header');
        remove_action('wp_head', 'wp_admin_bar_render', 1000);
        
        // Additional admin bar removal
        add_filter('show_admin_bar', '__return_false', 999);
        
        // Remove admin bar from body classes
        add_filter('body_class', function($classes) {
            return array_diff($classes, ['admin-bar']);
        });
    }

    public function ensureSampleTopics() {
        // Only run if no topics exist
        $existing_topics = get_terms([
            'taxonomy' => 'scn_topic',
            'hide_empty' => false,
            'number' => 1
        ]);
        
        if (is_wp_error($existing_topics) || empty($existing_topics)) {
            $sample_topics = [
                'Leadership',
                'Communication', 
                'Project Management',
                'Digital Marketing',
                'Data Analysis',
                'Public Speaking',
                'Team Building',
                'Strategic Planning',
                'Customer Service',
                'Innovation'
            ];
            
            foreach ($sample_topics as $topic_name) {
                wp_insert_term(
                    $topic_name,
                    'scn_topic',
                    [
                        'description' => "Course topic: {$topic_name}",
                        'slug' => sanitize_title($topic_name)
                    ]
                );
            }
        }
    }

    public function createMembersProfilePage() {
        // Check if the page already exists
        $existing_page = get_page_by_path('members-profile');
        
        if (!$existing_page) {
            // Create the members profile page
            $page_id = wp_insert_post([
                'post_title' => 'Members Profile',
                'post_name' => 'members-profile',
                'post_content' => '[member_profile]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1
            ]);
            
            if ($page_id && !is_wp_error($page_id)) {
                // Set page template if needed
                update_post_meta($page_id, '_wp_page_template', 'default');
            }
        }
    }

    /**
     * Filter courses by author in ACF relationship field
     */
    public function filterCoursesByAuthor($args, $field, $post_id) {
        // Only filter for courses relationship field in events
        if ($field['name'] !== 'courses') {
            return $args;
        }
        
        error_log('SCN: Courses filter called for field: ' . $field['name'] . ', post_id: ' . $post_id);
        
        // Try multiple ways to get the author ID
        $author_id = null;
        
        // Method 1: From field data (set by JavaScript)
        if (isset($field['data']['author_filter']) && !empty($field['data']['author_filter'])) {
            $author_id = intval($field['data']['author_filter']);
            error_log('SCN: Got author ID from field data: ' . $author_id);
        }
        // Method 2: From POST data (when form is being submitted)
        elseif (isset($_POST['acf']['field_event_author']) && !empty($_POST['acf']['field_event_author'])) {
            $author_id = intval($_POST['acf']['field_event_author']);
            error_log('SCN: Got author ID from POST: ' . $author_id);
        }
        // Method 3: From existing field value
        elseif ($post_id && function_exists('get_field')) {
            $author_value = get_field('author', $post_id);
            if (is_array($author_value) && !empty($author_value)) {
                $author_id = $author_value[0];
                error_log('SCN: Got author ID from existing field: ' . $author_id);
            }
        }
        
        if ($author_id) {
            error_log('SCN: Filtering courses by author ID: ' . $author_id);
            
            // Add meta query to filter courses by author
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = [];
            }
            
            $args['meta_query'][] = [
                'key' => 'author',
                'value' => $author_id,
                'compare' => 'LIKE'
            ];
            
            error_log('SCN: Updated query args: ' . print_r($args, true));
        } else {
            error_log('SCN: No author ID found, not filtering courses');
        }
        
        return $args;
    }
    
    /**
     * Advanced filter for courses relationship field
     */
    public function filterCoursesByAuthorAdvanced($args, $field, $post_id) {
        error_log('SCN: Advanced courses filter called for post ID: ' . $post_id);
        
        // Get the current author value from POST data (from the form)
        if (isset($_POST['acf']['field_author']) && !empty($_POST['acf']['field_author'])) {
            $author_id = intval($_POST['acf']['field_author']);
            error_log('SCN: Advanced filter - Author ID from POST: ' . $author_id);
            
            // Add meta query to filter courses by author
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = [];
            }
            
            $args['meta_query'][] = [
                'key' => 'author',
                'value' => $author_id,
                'compare' => 'LIKE'
            ];
            
            error_log('SCN: Advanced filter - Updated query args: ' . print_r($args, true));
        }
        
        return $args;
    }
    
    /**
     * Filter event courses by author - specific to field_event_courses
     */
    public function filterEventCoursesByAuthor($args, $field, $post_id) {
        error_log('SCN: Event courses filter called for post ID: ' . $post_id);
        
        // Try to get author ID from multiple sources
        $author_id = null;
        
        // Method 1: From field data (set by JavaScript)
        if (isset($field['data']['author_filter']) && !empty($field['data']['author_filter'])) {
            $author_id = intval($field['data']['author_filter']);
            error_log('SCN: Event courses - Got author ID from field data: ' . $author_id);
        }
        // Method 2: From POST data
        elseif (isset($_POST['acf']['field_event_author']) && !empty($_POST['acf']['field_event_author'])) {
            $author_id = intval($_POST['acf']['field_event_author']);
            error_log('SCN: Event courses - Got author ID from POST: ' . $author_id);
        }
        // Method 3: From existing field value
        elseif ($post_id && function_exists('get_field')) {
            $author_value = get_field('author', $post_id);
            if (is_array($author_value) && !empty($author_value)) {
                $author_id = $author_value[0];
                error_log('SCN: Event courses - Got author ID from existing field: ' . $author_id);
            }
        }
        
        if ($author_id) {
            error_log('SCN: Event courses - Filtering by author ID: ' . $author_id);
            
            // Add meta query to filter courses by author
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = [];
            }
            
            $args['meta_query'][] = [
                'key' => 'author',
                'value' => $author_id,
                'compare' => 'LIKE'
            ];
            
            error_log('SCN: Event courses - Updated query args: ' . print_r($args, true));
        } else {
            error_log('SCN: Event courses - No author ID found, not filtering');
        }
        
        return $args;
    }
    
    /**
     * Filter event course sub-field by author - for the course field within the repeater
     */
    public function filterEventCourseSubFieldByAuthor($args, $field, $post_id) {
        error_log('SCN: Event course sub-field filter called for post ID: ' . $post_id);
        error_log('SCN: Field data received: ' . print_r($field, true));
        error_log('SCN: POST data: ' . print_r($_POST, true));
        
        // Try to get author ID from multiple sources
        $author_id = null;
        
        // Method 1: From field data (set by JavaScript)
        if (isset($field['data']['author_filter']) && !empty($field['data']['author_filter'])) {
            $author_id = intval($field['data']['author_filter']);
            error_log('SCN: Event course sub-field - Got author ID from field data: ' . $author_id);
        }
        // Method 2: From POST data
        elseif (isset($_POST['acf']['field_event_author']) && !empty($_POST['acf']['field_event_author'])) {
            $author_id = intval($_POST['acf']['field_event_author']);
            error_log('SCN: Event course sub-field - Got author ID from POST: ' . $author_id);
        }
        // Method 3: From existing field value
        elseif ($post_id && function_exists('get_field')) {
            $author_value = get_field('author', $post_id);
            if (is_array($author_value) && !empty($author_value)) {
                $author_id = $author_value[0];
                error_log('SCN: Event course sub-field - Got author ID from existing field: ' . $author_id);
            }
        }
        // Method 4: From AJAX request headers or global variable
        elseif (isset($_POST['action']) && strpos($_POST['action'], 'acf/fields') !== false) {
            // This is an ACF AJAX request, try to get author from current page context
            if ($post_id && function_exists('get_field')) {
                $author_value = get_field('author', $post_id);
                if (is_array($author_value) && !empty($author_value)) {
                    $author_id = $author_value[0];
                    error_log('SCN: Event course sub-field - Got author ID from AJAX context: ' . $author_id);
                }
            }
        }
        
        // Always test what courses exist first
        $all_courses = get_posts([
            'post_type' => 'course',
            'numberposts' => 5,
            'post_status' => 'publish'
        ]);
        error_log('SCN: Total courses available: ' . count($all_courses));
        foreach ($all_courses as $course) {
            $author_meta = get_post_meta($course->ID, 'author', true);
            error_log('SCN: Course ' . $course->ID . ' (' . $course->post_title . ') - Author meta: ' . ($author_meta ? $author_meta : 'none'));
        }
        
        if ($author_id) {
            error_log('SCN: Event course sub-field - Filtering by author ID: ' . $author_id);
            
            // Test: Check if there are any courses with this author
            $test_courses = get_posts([
                'post_type' => 'course',
                'meta_query' => [
                    [
                        'key' => 'author',
                        'value' => $author_id,
                        'compare' => 'LIKE'
                    ]
                ],
                'numberposts' => 5
            ]);
            error_log('SCN: Found ' . count($test_courses) . ' courses with author ID ' . $author_id);
            foreach ($test_courses as $course) {
                error_log('SCN: Course found: ' . $course->ID . ' - ' . $course->post_title);
            }
            
            // If no courses found with LIKE, try exact match
            if (count($test_courses) == 0) {
                $test_courses_exact = get_posts([
                    'post_type' => 'course',
                    'meta_query' => [
                        [
                            'key' => 'author',
                            'value' => $author_id,
                            'compare' => '='
                        ]
                    ],
                    'numberposts' => 5
                ]);
                error_log('SCN: Found ' . count($test_courses_exact) . ' courses with exact author ID match');
                
                // Use exact match instead
                if (count($test_courses_exact) > 0) {
                    if (!isset($args['meta_query'])) {
                        $args['meta_query'] = [];
                    }
                    
                    $args['meta_query'][] = [
                        'key' => 'author',
                        'value' => $author_id,
                        'compare' => '='
                    ];
                }
            } else {
                // Add meta query to filter courses by author using LIKE
                if (!isset($args['meta_query'])) {
                    $args['meta_query'] = [];
                }
                
                $args['meta_query'][] = [
                    'key' => 'author',
                    'value' => $author_id,
                    'compare' => 'LIKE'
                ];
            }
            
            error_log('SCN: Event course sub-field - Updated query args: ' . print_r($args, true));
        } else {
            error_log('SCN: Event course sub-field - No author ID found, not filtering');
        }
        
        return $args;
    }
    
    /**
     * Filter course queries when in event edit context
     */
    public function filterCourseQueryForEvents($query) {
        // Only filter in admin and for course post type
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Check if this is an ACF AJAX request for courses
        if (isset($_POST['action']) && strpos($_POST['action'], 'acf/fields') !== false) {
            if (isset($_POST['field_key']) && $_POST['field_key'] === 'field_event_courses') {
                error_log('SCN: Filtering course query for event courses field');
                
                // Get the current author from the form
                if (isset($_POST['acf']['field_event_author']) && !empty($_POST['acf']['field_event_author'])) {
                    $author_id = intval($_POST['acf']['field_event_author']);
                    error_log('SCN: Adding author filter to course query: ' . $author_id);
                    
                    $meta_query = $query->get('meta_query');
                    if (!is_array($meta_query)) {
                        $meta_query = [];
                    }
                    
                    $meta_query[] = [
                        'key' => 'author',
                        'value' => $author_id,
                        'compare' => 'LIKE'
                    ];
                    
                    $query->set('meta_query', $meta_query);
                }
            }
        }
    }
    
    /**
     * Filter courses in WHERE clause for ACF relationship fields
     */
    public function filterCoursesInWhere($where, $query) {
        // Only in admin and for course post type
        if (!is_admin() || !isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'course') {
            return $where;
        }
        
        // Check if this is an ACF AJAX request
        if (isset($_POST['action']) && strpos($_POST['action'], 'acf/fields') !== false) {
            if (isset($_POST['field_key']) && $_POST['field_key'] === 'field_event_courses') {
                error_log('SCN: Filtering courses in WHERE clause');
                
                // Get the current author from the form
                if (isset($_POST['acf']['field_event_author']) && !empty($_POST['acf']['field_event_author'])) {
                    $author_id = intval($_POST['acf']['field_event_author']);
                    error_log('SCN: Adding author filter to WHERE clause: ' . $author_id);
                    
                    // Add a subquery to filter by author meta
                    $where .= " AND ID IN (
                        SELECT post_id FROM {$GLOBALS['wpdb']->postmeta} 
                        WHERE meta_key = 'author' 
                        AND meta_value LIKE '%\"$author_id\"%'
                    )";
                }
            }
        }
        
        return $where;
    }

    
    public function handleGetMemberCoursesAjax() {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_send_json_error('User not logged in');
                return;
            }
            
            // Check user capabilities
            if (!current_user_can('edit_posts')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }
            
        // Get profile ID from request
        $profile_id = intval($_POST['profile_id'] ?? 0);
        if (empty($profile_id)) {
                wp_send_json_error('Profile ID is required');
                return;
            }
            
        // Get courses for this profile
            $courses = get_posts([
                'post_type' => 'course',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'author',
                        'value' => $profile_id,
                        'compare' => 'LIKE'
                    ]
                ]
            ]);
        
        $course_data = [];
        foreach ($courses as $course) {
            $course_data[] = [
                'id' => $course->ID,
                'title' => $course->post_title,
                'url' => get_permalink($course->ID)
            ];
        }
        
        wp_send_json_success([
            'courses' => $course_data,
            'count' => count($course_data)
        ]);
    }
}

// Initialize the plugin
SCN_Membership_Bootstrap::getInstance();
