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
        // ACF code completely removed - let ACF run natively
        
        // Force classic editor for member post types
        add_filter('use_block_editor_for_post_type', [$this, 'forceClassicEditorForMembers'], 10, 2);
        
        if (class_exists('SCN\\Membership\\Core\\Plugin')) {
            $this->checkVersion();
            
            $this->plugin = new SCN\Membership\Core\Plugin();
            $this->plugin->register();
            
            // Initialize database
            $database = new SCN\Membership\Infra\Database();
            $database->register();
            
            // Initialize admin service
            $admin_service = new SCN\Membership\Admin\AdminService();
            $admin_service->register();
            
            // Initialize email confirmation system
            require_once SCN_MEMBERSHIP_PATH . 'includes/email-confirmation.php';
            
            // Initialize email debug system
            require_once SCN_MEMBERSHIP_PATH . 'includes/email-debug.php';
            
            // Enqueue global full-width override CSS
            add_action('wp_enqueue_scripts', [$this, 'enqueueGlobalStyles']);
            
            // Remove WordPress admin bar from frontend
            add_action('init', [$this, 'removeAdminBar']);
            
            // Ensure sample topics exist
            add_action('init', [$this, 'ensureSampleTopics'], 20);
        add_action('init', [$this, 'createMembersProfilePage'], 30);
            
                    // Register WP-CLI commands
                    if (defined('WP_CLI') && constant('WP_CLI') && class_exists('WP_CLI')) {
                        \WP_CLI::add_command('scn events', 'SCN\\Membership\\Cli\\EventsCommand');
                    }
        }
    }

    public function earlyInit() {
        if (class_exists('SCN\\Membership\\Core\\Plugin')) {
            // Force early registration of post types and taxonomies
            $this->plugin = new SCN\Membership\Core\Plugin();
            $this->plugin->register();
        }
        // $this->ensureRewriteRules(); // Disabled - using pages instead
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
                'edit_scn_profiles', 'edit_others_scn_profiles', 'publish_scn_profiles',
                'read_private_scn_profiles', 'delete_scn_profiles', 'delete_private_scn_profiles',
                'delete_published_scn_profiles', 'delete_others_scn_profiles',
                'edit_private_scn_profiles', 'edit_published_scn_profiles',
                'edit_scn_courses', 'edit_others_scn_courses', 'publish_scn_courses',
                'read_private_scn_courses', 'delete_scn_courses', 'delete_private_scn_courses',
                'delete_published_scn_courses', 'delete_others_scn_courses',
                'edit_private_scn_courses', 'edit_published_scn_courses',
                'edit_scn_events', 'edit_others_scn_events', 'publish_scn_events',
                'read_private_scn_events', 'delete_scn_events', 'delete_private_scn_events',
                'delete_published_scn_events', 'delete_others_scn_events',
                'edit_private_scn_events', 'edit_published_scn_events',
                'manage_scn_events', 'merge_scn_events', 'lock_scn_events',
                'manage_scn_sessions', 'approve_scn_sessions'
            ],
            'editor' => [
                'edit_scn_profiles', 'edit_others_scn_profiles', 'publish_scn_profiles',
                'read_private_scn_profiles', 'delete_scn_profiles', 'delete_others_scn_profiles',
                'delete_published_scn_profiles', 'edit_published_scn_profiles',
                'edit_scn_courses', 'edit_others_scn_courses', 'publish_scn_courses',
                'read_private_scn_courses', 'delete_scn_courses', 'delete_others_scn_courses',
                'delete_published_scn_courses', 'edit_published_scn_courses',
                'manage_scn_events', 'manage_scn_sessions', 'approve_scn_sessions'
            ],
            'author' => [
                'edit_scn_profiles', 'publish_scn_profiles', 'delete_scn_profiles',
                'edit_published_scn_profiles', 'edit_scn_courses', 'publish_scn_courses',
                'delete_scn_courses', 'edit_published_scn_courses', 'create_scn_sessions'
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
            'index.php?scn_member_login=1',
            'top'
        );
        
        add_rewrite_rule(
            '^member-register/?$',
            'index.php?scn_member_register=1',
            'top'
        );
        
        add_rewrite_rule(
            '^member-dashboard/?$',
            'index.php?scn_member_dashboard=1',
            'top'
        );
        
        add_rewrite_rule(
            '^member-logout/?$',
            'index.php?scn_member_logout=1',
            'top'
        );
        
        add_rewrite_rule(
            '^test-auth/?$',
            'index.php?scn_test_auth=1',
            'top'
        );
        
        // Add query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'scn_member_login';
            $vars[] = 'scn_member_register';
            $vars[] = 'scn_member_dashboard';
            $vars[] = 'scn_member_logout';
            $vars[] = 'scn_test_auth';
            return $vars;
        });
        
        // Handle template inclusion
        add_filter('template_include', function($template) {
            if (get_query_var('scn_member_login')) {
                return SCN_MEMBERSHIP_PATH . 'templates/auth/member-login.php';
            }
            if (get_query_var('scn_member_register')) {
                return SCN_MEMBERSHIP_PATH . 'templates/auth/member-register.php';
            }
        if (get_query_var('scn_member_dashboard')) {
            return SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard-fixed.php';
        }
            if (get_query_var('scn_member_logout')) {
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
        });
    }

    private function setActivationFlag() {
        update_option('scn_membership_activated', time());
        update_option('scn_membership_version', SCN_MEMBERSHIP_VERSION);
    }

    private function clearActivationFlag() {
        delete_option('scn_membership_activated');
    }

    private function checkVersion() {
        $installed_version = get_option('scn_membership_version', '0.0.0');
        
        if (version_compare($installed_version, SCN_MEMBERSHIP_VERSION, '<')) {
            $this->upgrade($installed_version, SCN_MEMBERSHIP_VERSION);
        }
    }

    private function upgrade($from_version, $to_version) {
        if (version_compare($from_version, '1.0.0', '<')) {
            $this->upgradeToV1();
        }
        
        update_option('scn_membership_version', $to_version);
    }

    private function upgradeToV1() {
        $this->setupDatabase();
        $this->addCapabilities();
        flush_rewrite_rules();
    }

    private function ensureCourseMetaboxOnly() {
        add_action('admin_init', function() {
            if (post_type_exists('scn_course')) {
                remove_post_type_support('scn_course', 'title');
                remove_post_type_support('scn_course', 'editor');
            }
            
            // Debug: Check current user capabilities
            $current_user = wp_get_current_user();
            error_log('SCN Debug: Current user ID: ' . $current_user->ID . ', roles: ' . implode(', ', $current_user->roles));
            error_log('SCN Debug: Can edit courses: ' . (current_user_can('edit_scn_courses') ? 'YES' : 'NO'));
            error_log('SCN Debug: Can delete courses: ' . (current_user_can('delete_scn_courses') ? 'YES' : 'NO'));
            error_log('SCN Debug: Can edit posts: ' . (current_user_can('edit_posts') ? 'YES' : 'NO'));
            
            // Check if capabilities exist in database
            $user_caps = $current_user->allcaps;
            $scn_caps = array_filter($user_caps, function($key) {
                return strpos($key, 'scn_') === 0;
            }, ARRAY_FILTER_USE_KEY);
            error_log('SCN Debug: User SCN capabilities: ' . print_r($scn_caps, true));
        });

        add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
            if ($post_type === 'scn_course') {
                return false;
            }
            return $use_block_editor;
        }, 10, 2);

        // Single consolidated save_post hook to prevent infinite loops
        add_action('save_post_scn_course', [$this, 'handleCourseSave'], 20, 3);

        $this->forceSingleColumnLayout();
        $this->preventOverflowAndEnforceSingleColumn();
        $this->fixValidationMessages();
        $this->renameFeaturedImageMetabox();
        $this->ensureProfileMetaboxOnly();
        $this->ensureEventMetaboxOnly();
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
        if (isset($_POST['scn_course_subtitle'])) {
            $subtitle = trim((string) $_POST['scn_course_subtitle']);
        } else {
            // Fallback to get from meta if not in POST
            $subtitle = trim((string) get_post_meta($post_id, 'scn_course_subtitle', true));
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

        // Validate outcomes - only when trying to publish
        $outcomes = [];
        if (isset($_POST['scn_course_outcomes'])) {
            $outcomes = $_POST['scn_course_outcomes'];
        } else {
            // Fallback to get from meta if not in POST
            $outcomes = get_post_meta($post_id, 'scn_course_outcomes', true);
        }
        
        error_log('SCN Course Save: Outcomes found: ' . print_r($outcomes, true));
        
        if (empty($outcomes) || (is_array($outcomes) && count(array_filter($outcomes)) === 0)) {
            // Only validate if trying to publish (not draft or auto-draft)
            if ($post->post_status === 'publish' || (isset($_POST['post_status']) && $_POST['post_status'] === 'publish')) {
                error_log('SCN Course Save: No outcomes found, preventing publish');
                set_transient('scn_course_validation_error', 1, 60);
                // Prevent publish and revert to draft
                wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
            }
        }

        $processing = false;
    }

    private function forceSingleColumnLayout() {
        // Force single column layout for Course CPT
        add_filter('get_user_option_screen_layout_scn_course', function() { return 1; });
        add_filter('get_user_option_screen_layout_course', function() { return 1; });

        add_action('admin_init', function() {
            // Defensive: remove 'side' column support just in case
            global $current_user;
            // Clear user meta that could keep multiple columns
            if ($current_user && method_exists($current_user, 'ID')) {
                delete_user_meta($current_user->ID, 'screen_layout_scn_course');
                delete_user_meta($current_user->ID, 'screen_layout_course');
            }
        });
    }

    private function preventOverflowAndEnforceSingleColumn() {
        add_action('admin_enqueue_scripts', function($hook) {
            if (!function_exists('get_current_screen')) return;
            $s = get_current_screen();
            if (!$s || !in_array($s->base, ['post', 'post-new'], true)) return;
            if (!in_array($s->post_type, ['scn_course', 'course'], true)) return;

            wp_register_style(
                'scn-course-admin-fixes',
                false,
                [],
                '1.0'
            );
            wp_enqueue_style('scn-course-admin-fixes');

            $css = <<<CSS
/* Kill any unintended two-column grids inside metabox content */
#poststuff .postbox .inside { overflow-x: hidden; }
#wpbody-content { overflow-x: hidden; } /* belt & suspenders */

/* If our UI uses a custom two-col class, flatten it to one column in admin */
.scn-two-col,
.scn-grid-2,
.scn-admin-grid-2,
.scn-fields-grid-2 {
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 16px !important;
}

/* Make all inputs respect container width and avoid min-width leaks */
.postbox .inside input[type="text"],
.postbox .inside input[type="number"],
.postbox .inside input[type="url"],
.postbox .inside textarea,
.postbox .inside select {
    max-width: 100% !important;
    width: 100%;
    box-sizing: border-box;
}

/* Ensure metabox containers don't push layout horizontally */
.postbox,
.metabox-holder .postbox-container {
    max-width: 100%;
}

/* Force single column layout */
#poststuff {
    display: block !important;
}

#post-body.columns-2 #post-body-content {
    margin-right: 0 !important;
}

#post-body.columns-2 #postbox-container-1 {
    display: none !important;
}
CSS;

            wp_add_inline_style('scn-course-admin-fixes', $css);
        });
    }

    private function fixValidationMessages() {
        // Clear validation transients on new post page load
        add_action('admin_init', function() {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || !in_array($screen->post_type, ['scn_course', 'course'], true)) return;
            if ($screen->base === 'post-new') {
                delete_transient('scn_course_validation_error');
            }
        });

        // Server-side validation guard - only show after failed save attempts
        add_action('admin_notices', function() {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || !in_array($screen->post_type, ['scn_course', 'course'], true)) return;
            if (!in_array($screen->base, ['post', 'post-new'], true)) return;

            // Only show when we explicitly set a transient/flag on a failed save
            $flag = get_transient('scn_course_validation_error');
            if (!$flag) return;

            // Additional check: only show if this is NOT a new post (post-new.php)
            if ($screen->base === 'post-new') {
                delete_transient('scn_course_validation_error');
                return;
            }

            delete_transient('scn_course_validation_error');
            echo '<div class="notice notice-error"><p>At least one learning outcome is required.</p></div>';
        });

        // Client-side validation guard
        add_action('admin_enqueue_scripts', function() {
            $s = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$s || !in_array($s->post_type, ['scn_course', 'course'], true)) return;
            if (!in_array($s->base, ['post', 'post-new'], true)) return;

            wp_register_script(
                'scn-course-validate',
                false,
                ['jquery'],
                '1.0',
                true
            );
            wp_enqueue_script('scn-course-validate');
            wp_add_inline_script('scn-course-validate', <<<JS
(function($){
    if (window.__scnCourseValidationBound) return;
    window.__scnCourseValidationBound = true;

    // Clear any existing validation errors on page load
    $(document).ready(function() {
        $('.notice.notice-error').remove();
        $('#scn-outcome-error').remove();
    });

    function hasOutcome(){
        var ok = false;
        $('[name^="scn_course_outcomes"]').each(function(){
            if ($(this).val().trim() !== '') { ok = true; return false; }
        });
        return ok;
    }

    // Validate on submit only
    $('#post').on('submit', function(e){
        if (!hasOutcome()) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (!$('#scn-outcome-error').length) {
                $('<div id="scn-outcome-error" class="notice notice-error"><p>At least one learning outcome is required.</p></div>')
                    .insertBefore('#poststuff');
            }
        }
    });

    // Clear notice when user adds something
    $(document).on('input', '[name^="scn_course_outcomes"]', function(){
        $('#scn-outcome-error').remove();
    });
})(jQuery);
JS);
        });
    }

    private function renameFeaturedImageMetabox() {
        // Use add_meta_boxes hook with higher priority to ensure our metabox runs after WordPress core
        add_action('add_meta_boxes', function() {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen) return;
            
            // Rename featured image metabox for courses
            if ($screen->post_type === 'scn_course') {
                remove_meta_box('postimagediv', 'scn_course', 'side');
                add_meta_box(
                    'postimagediv',
                    __('Course Image', 'scn-membership'),
                    'post_thumbnail_meta_box',
                    'scn_course',
                    'side',
                    'high'
                );
            }
            
            // Rename featured image metabox for profiles
            if ($screen->post_type === 'scn_profile') {
                remove_meta_box('postimagediv', 'scn_profile', 'side');
                add_meta_box(
                    'postimagediv',
                    __('Profile Image', 'scn-membership'),
                    'post_thumbnail_meta_box',
                    'scn_profile',
                    'side',
                    'high'
                );
            }
            
            // Rename featured image metabox for events
            if ($screen->post_type === 'scn_event') {
                remove_meta_box('postimagediv', 'scn_event', 'side');
                add_meta_box(
                    'postimagediv',
                    __('Event Image', 'scn-membership'),
                    'post_thumbnail_meta_box',
                    'scn_event',
                    'side',
                    'high'
                );
            }
        }, 20);
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
                            
                            // Generate title: FIRSTNAME LASTNAME, credentials
                            if (firstName && lastName) {
                                var title = firstName.toUpperCase() + " " + lastName.toUpperCase();
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

        $this->forceSingleColumnLayoutForProfiles();
        $this->preventOverflowAndEnforceSingleColumnForProfiles();
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

    private function forceSingleColumnLayoutForProfiles() {
        add_filter('get_user_option_screen_layout_scn_profile', function($result) {
            return 1; // Force single column
        });
        
        add_action('admin_init', function() {
            $current_user = wp_get_current_user();
            delete_user_meta($current_user->ID, 'screen_layout_scn_profile');
        });
    }

    private function preventOverflowAndEnforceSingleColumnForProfiles() {
        add_action('admin_enqueue_scripts', function() {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || !in_array($screen->post_type, ['scn_profile'], true)) return;
            if (!in_array($screen->base, ['post', 'post-new'], true)) return;

            wp_register_style('scn-profile-admin-fixes', false, [], '1.0');
            wp_enqueue_style('scn-profile-admin-fixes');
            
            $css = <<<CSS
#poststuff {
    overflow-x: hidden !important;
}

#post-body-content {
    overflow-x: hidden !important;
}

.metabox-holder {
    overflow-x: hidden !important;
}

.postbox .inside {
    overflow-x: hidden !important;
}

.scn-two-col,
.scn-grid-2,
.scn-profile-two-col {
    display: block !important;
}

.scn-two-col > *,
.scn-grid-2 > *,
.scn-profile-two-col > * {
    width: 100% !important;
    margin-right: 0 !important;
    margin-bottom: 10px !important;
}

.form-table th,
.form-table td {
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
    box-sizing: border-box;
}

.form-table input,
.form-table textarea,
.form-table select {
    max-width: 100%;
    box-sizing: border-box;
}

.form-table {
    table-layout: fixed;
}
CSS;

            wp_add_inline_style('scn-profile-admin-fixes', $css);
        });
    }

    private function ensureEventMetaboxOnly() {
        add_action('admin_init', function() {
            if (post_type_exists('scn_event')) {
                remove_post_type_support('scn_event', 'title');
                remove_post_type_support('scn_event', 'editor');
            }
        });

        add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
            if ($post_type === 'scn_event') {
                return false;
            }
            return $use_block_editor;
        }, 10, 2);

        // Single consolidated save_post hook for events
        add_action('save_post_scn_event', [$this, 'handleEventSave'], 10, 3);

        $this->forceSingleColumnLayoutForEvents();
        $this->preventOverflowAndEnforceSingleColumnForEvents();
    }

    public function handleEventSave($post_id, $post, $update) {
        // Prevent infinite loops
        static $processing = false;
        if ($processing) {
            return;
        }
        $processing = true;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            $processing = false;
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            $processing = false;
            return;
        }

        // Check nonce for event meta fields
        if (!isset($_POST['scn_event_meta_nonce']) || !wp_verify_nonce($_POST['scn_event_meta_nonce'], 'scn_event_meta')) {
            $processing = false;
            return;
        }

        if (get_post_type($post_id) !== 'scn_event') {
            $processing = false;
            return;
        }

        $locked_fields = get_post_meta($post_id, 'scn_event_locked_fields', true) ?: [];

        // Save event name
        if (isset($_POST['scn_event_name'])) {
            update_post_meta($post_id, 'scn_event_name', sanitize_text_field($_POST['scn_event_name']));
        }

        if (!in_array('official_name', $locked_fields)) {
            // Get official name from event name field instead of post_title
            $official_name = '';
            if (isset($_POST['scn_event_name'])) {
                $official_name = sanitize_text_field($_POST['scn_event_name']);
            } else {
                // Fallback to post title if event name not available
                $official_name = sanitize_text_field($_POST['post_title'] ?? '');
            }
            update_post_meta($post_id, 'scn_event_official_name', $official_name);
        }

        if (!in_array('location_city', $locked_fields) && isset($_POST['scn_event_location_city'])) {
            update_post_meta($post_id, 'scn_event_location_city', sanitize_text_field($_POST['scn_event_location_city']));
        }

        if (!in_array('location_region', $locked_fields) && isset($_POST['scn_event_location_region'])) {
            update_post_meta($post_id, 'scn_event_location_region', sanitize_text_field($_POST['scn_event_location_region']));
        }

        if (!in_array('location_country', $locked_fields) && isset($_POST['scn_event_location_country'])) {
            update_post_meta($post_id, 'scn_event_location_country', sanitize_text_field($_POST['scn_event_location_country']));
        }

        if (!in_array('dates', $locked_fields)) {
            $start_date = sanitize_text_field($_POST['scn_event_start_date'] ?? '');
            $end_date = sanitize_text_field($_POST['scn_event_end_date'] ?? '');
            
            $dates = [
                'start' => $start_date,
                'end' => $end_date
            ];
            update_post_meta($post_id, 'scn_event_dates', $dates);
            
            // Auto-generate year from start date
            if ($start_date) {
                $year = date('Y', strtotime($start_date));
                update_post_meta($post_id, 'scn_event_year', $year);
            }
        }

        if (!in_array('website', $locked_fields) && isset($_POST['scn_event_website'])) {
            $website = $this->sanitizeWebsite($_POST['scn_event_website']);
            update_post_meta($post_id, 'scn_event_website', $website);
        }

        // Auto-generate title from event name
        $event_name = '';
        if (isset($_POST['scn_event_name'])) {
            $event_name = trim((string) $_POST['scn_event_name']);
        }
        
        if ($event_name && ('' === $post->post_title || $post->post_title === 'Auto Draft')) {
            wp_update_post([
                'ID' => $post_id,
                'post_title' => wp_strip_all_tags($event_name),
                'post_name' => sanitize_title($event_name),
            ]);
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

    private function forceSingleColumnLayoutForEvents() {
        add_filter('get_user_option_screen_layout_scn_event', function($result) {
            return 1; // Force single column
        });
        
        add_action('admin_init', function() {
            $current_user = wp_get_current_user();
            delete_user_meta($current_user->ID, 'screen_layout_scn_event');
        });
    }

    private function preventOverflowAndEnforceSingleColumnForEvents() {
        add_action('admin_enqueue_scripts', function() {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || !in_array($screen->post_type, ['scn_event'], true)) return;
            if (!in_array($screen->base, ['post', 'post-new'], true)) return;

            wp_register_style('scn-event-admin-fixes', false, [], '1.0');
            wp_enqueue_style('scn-event-admin-fixes');
            
            $css = <<<CSS
#poststuff {
    overflow-x: hidden !important;
}

#post-body-content {
    overflow-x: hidden !important;
}

.metabox-holder {
    overflow-x: hidden !important;
}

.postbox .inside {
    overflow-x: hidden !important;
}

.scn-two-col,
.scn-grid-2,
.scn-event-two-col {
    display: block !important;
}

.scn-two-col > *,
.scn-grid-2 > *,
.scn-event-two-col > * {
    width: 100% !important;
    margin-right: 0 !important;
    margin-bottom: 10px !important;
}

.form-table th,
.form-table td {
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
    box-sizing: border-box;
}

.form-table input,
.form-table textarea,
.form-table select {
    max-width: 100%;
    box-sizing: border-box;
}

.form-table {
    table-layout: fixed;
}
CSS;

            wp_add_inline_style('scn-event-admin-fixes', $css);
        });
    }
    
    public function enqueueGlobalStyles() {
        wp_enqueue_style(
            'scn-full-width-override',
            SCN_MEMBERSHIP_URL . 'assets/css/full-width-override.css',
            [],
            SCN_MEMBERSHIP_VERSION
        );
        
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
                'post_content' => '[scn_member_profile]',
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

    
    public function forceClassicEditorForMembers($use_block_editor, $post_type)
    {
        // Define member post types that should use classic editor
        $member_post_types = ['scn_profile', 'scn_course', 'scn_event'];
        
        if (in_array($post_type, $member_post_types)) {
            return false; // Use classic editor
        }
        
        return $use_block_editor;
    }

}

SCN_Membership_Bootstrap::getInstance();

register_uninstall_hook(__FILE__, 'scn_membership_uninstall');

function scn_membership_uninstall() {
    if (!current_user_can('delete_plugins')) {
        return;
    }

    global $wpdb;

    $tables = [
        $wpdb->prefix . 'scn_event_aliases',
        $wpdb->prefix . 'scn_event_locks',
        $wpdb->prefix . 'scn_event_sessions'
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }

    $options = [
        'scn_membership_version',
        'scn_membership_activated',
        'scn_events_db_version'
    ];

    foreach ($options as $option) {
        delete_option($option);
    }

    $roles = ['administrator', 'editor', 'author'];
    $capabilities = [
        'edit_scn_profiles', 'edit_others_scn_profiles', 'publish_scn_profiles',
        'read_private_scn_profiles', 'delete_scn_profiles', 'delete_private_scn_profiles',
        'delete_published_scn_profiles', 'delete_others_scn_profiles',
        'edit_private_scn_profiles', 'edit_published_scn_profiles',
        'edit_scn_courses', 'edit_others_scn_courses', 'publish_scn_courses',
        'read_private_scn_courses', 'delete_scn_courses', 'delete_private_scn_courses',
        'delete_published_scn_courses', 'delete_others_scn_courses',
        'edit_private_scn_courses', 'edit_published_scn_courses',
        'edit_scn_events', 'edit_others_scn_events', 'publish_scn_events',
        'read_private_scn_events', 'delete_scn_events', 'delete_private_scn_events',
        'delete_published_scn_events', 'delete_others_scn_events',
        'edit_private_scn_events', 'edit_published_scn_events',
        'manage_scn_events', 'merge_scn_events', 'lock_scn_events',
        'manage_scn_sessions', 'approve_scn_sessions', 'create_scn_sessions'
    ];

    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }



}

SCN_Membership_Bootstrap::getInstance();

