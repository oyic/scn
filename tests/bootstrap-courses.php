<?php
/**
 * Bootstrap file for Courses module tests
 */

// Define WordPress constants
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}

// Mock WordPress functions that are commonly used
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return esc_html($text);
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return $url;
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return $url;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim($str);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim($str);
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return esc_html($text);
    }
}

if (!function_exists('wp_http_validate_url')) {
    function wp_http_validate_url($url) {
        // More lenient validation for testing
        return !empty($url) && (filter_var($url, FILTER_VALIDATE_URL) !== false || strpos($url, 'example.com') !== false);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        // Handle specific filters for courses
        if ($hook === 'scn/course/allowed_formats') {
            return [
                'keynote' => 'Keynote',
                'lecture' => 'Lecture', 
                'workshop' => 'Workshop',
                'panel' => 'Panel'
            ];
        }
        if ($hook === 'scn/course/ondemand_link') {
            return str_replace('http://', 'https://', $value);
        }
        if ($hook === 'scn/course/placeholder_image_id') {
            return 0;
        }
        return $value;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('post_type_exists')) {
    function post_type_exists($post_type) {
        return true;
    }
}

if (!function_exists('get_post_type_object')) {
    function get_post_type_object($post_type) {
        return (object) [
            'name' => $post_type,
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
        ];
    }
}

if (!function_exists('registered_meta_key_exists')) {
    function registered_meta_key_exists($object_type, $meta_key) {
        return true;
    }
}

if (!function_exists('get_taxonomy')) {
    function get_taxonomy($taxonomy) {
        return (object) [
            'name' => $taxonomy,
            'object_type' => ['scn_course', 'scn_profile'],
        ];
    }
}

if (!function_exists('get_role')) {
    function get_role($role) {
        return new class($role) {
            private $capabilities = [];
            public function __construct($role) {
                if ($role === 'administrator') {
                    $this->capabilities = ['edit_scn_courses', 'publish_scn_courses', 'delete_scn_courses', 'edit_others_scn_courses', 'edit_scn_profiles', 'publish_scn_profiles', 'delete_scn_profiles'];
                } elseif ($role === 'author') {
                    $this->capabilities = ['edit_scn_courses', 'publish_scn_courses', 'edit_others_scn_courses'];
                }
            }
            public function has_cap($cap) {
                return in_array($cap, $this->capabilities);
            }
            public function add_cap($cap) {
                $this->capabilities[] = $cap;
                return true;
            }
        };
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key, $single = false) {
        // Return test data for specific meta keys
        $test_data = [
            'scn_course_ce_enabled' => '1',
            'scn_course_ce_hours' => '2.5',
            'scn_course_formats' => ['keynote', 'workshop'],
            'scn_course_outcomes' => ['Learn leadership skills', 'Improve communication'],
            'scn_course_ondemand' => [
                'title' => 'Advanced Leadership',
                'school' => 'University of Excellence',
                'link' => 'https://example.com/course'
            ]
        ];
        
        return $test_data[$key] ?? '';
    }
}

if (!function_exists('wp_get_post_terms')) {
    function wp_get_post_terms($post_id, $taxonomy, $args = []) {
        return [];
    }
}

if (!function_exists('wp_get_attachment_image')) {
    function wp_get_attachment_image($attachment_id, $size = 'thumbnail', $icon = false, $attr = []) {
        return '<img src="placeholder.jpg" alt="' . ($attr['alt'] ?? '') . '" />';
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args = []) {
        return [];
    }
}

if (!function_exists('get_post_field')) {
    function get_post_field($field, $post_id) {
        return 1;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id) {
        return 'https://example.com/course/' . $post_id;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post_id = 0) {
        return 'Test Course Title';
    }
}

if (!function_exists('get_terms')) {
    function get_terms($args = []) {
        return [];
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('remove_action')) {
    function remove_action($hook, $callback, $priority = 10) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post($post_data) {
        return $post_data['ID'];
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($post_id) {
        return 'scn_course';
    }
}

if (!function_exists('defined')) {
    function defined($constant) {
        return false;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return true;
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value) {
        return true;
    }
}

if (!function_exists('wp_set_post_terms')) {
    function wp_set_post_terms($post_id, $terms, $taxonomy) {
        return true;
    }
}

if (!function_exists('rest_sanitize_boolean')) {
    function rest_sanitize_boolean($value) {
        return (bool) $value;
    }
}

if (!function_exists('absint')) {
    function absint($value) {
        return abs((int) $value);
    }
}

if (!function_exists('is_singular')) {
    function is_singular($post_type = '') {
        return true;
    }
}

if (!function_exists('is_post_type_archive')) {
    function is_post_type_archive($post_type = '') {
        return true;
    }
}

if (!function_exists('locate_template')) {
    function locate_template($template_names) {
        return false;
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'https://example.com/wp-content/plugins/scn-membership/';
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        return true;
    }
}

if (!function_exists('wp_enqueue_media')) {
    function wp_enqueue_media($args = []) {
        return true;
    }
}

if (!function_exists('get_header')) {
    function get_header($name = null) {
        return '';
    }
}

if (!function_exists('get_footer')) {
    function get_footer($name = null) {
        return '';
    }
}

if (!function_exists('post_type_archive_title')) {
    function post_type_archive_title($prefix = '', $display = true) {
        return 'Courses';
    }
}

if (!function_exists('get_the_archive_description')) {
    function get_the_archive_description() {
        return '';
    }
}

if (!function_exists('have_posts')) {
    function have_posts() {
        return false;
    }
}

if (!function_exists('the_post')) {
    function the_post() {
        return true;
    }
}

if (!function_exists('the_permalink')) {
    function the_permalink() {
        return 'https://example.com/course/123';
    }
}

if (!function_exists('the_title')) {
    function the_title($before = '', $after = '', $echo = true) {
        return 'Test Course Title';
    }
}

if (!function_exists('the_content')) {
    function the_content($more_link_text = null, $strip_teaser = false) {
        return 'Test course content';
    }
}

if (!function_exists('the_excerpt')) {
    function the_excerpt() {
        return 'Test course excerpt';
    }
}

if (!function_exists('the_posts_pagination')) {
    function the_posts_pagination($args = []) {
        return '';
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($key, $value, $url = '') {
        return $url . '?' . $key . '=' . $value;
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words($text, $num_words = 55, $more = null) {
        return $text;
    }
}

// Additional WordPress functions needed for Profiles tests
if (!function_exists('remove_accents')) {
    function remove_accents($string) {
        $accents = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ñ' => 'n', 'ç' => 'c'
        ];
        return strtr($string, $accents);
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        return ['body' => '{"thumbnail_large": "https://example.com/thumb.jpg"}'];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'] ?? '';
    }
}

if (!function_exists('register_taxonomy')) {
    function register_taxonomy($taxonomy, $object_type, $args = []) {
        return true;
    }
}

if (!function_exists('get_post_types')) {
    function get_post_types($args = [], $output = 'names', $operator = 'and') {
        return ['scn_profile', 'scn_course'];
    }
}

if (!function_exists('get_registered_meta')) {
    function get_registered_meta($object_type, $meta_key = '', $object_subtype = '') {
        if ($object_type === 'post') {
            $profile_meta_fields = [
                'scn_first_name', 'scn_last_name', 'scn_credentials', 'scn_location',
                'scn_main_url', 'scn_social_links', 'scn_bio', 'scn_member_since',
                'scn_topics', 'scn_gallery_images', 'scn_featured_video_url',
                'scn_featured_video_thumbnail', 'scn_press_kit_files', 'scn_services', 'scn_badges'
            ];
            
            if (in_array($meta_key, $profile_meta_fields)) {
                return ['type' => 'string', 'single' => true];
            }
        }
        return [];
    }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post($postarr, $wp_error = false, $fire_after_hooks = true) {
        return 123;
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post($postarr, $wp_error = false, $fire_after_hooks = true) {
        return $postarr['ID'] ?? 123;
    }
}

if (!function_exists('get_post')) {
    function get_post($post = null, $output = OBJECT, $filter = 'raw') {
        return (object) ['ID' => 123, 'post_title' => 'Test Post'];
    }
}

if (!function_exists('wp_get_attachment_url')) {
    function wp_get_attachment_url($attachment_id) {
        return 'https://example.com/file.pdf';
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir($time = null, $create_dir = true, $refresh_cache = false) {
        return [
            'path' => '/uploads/',
            'url' => 'https://example.com/uploads/',
            'subdir' => '',
            'basedir' => '/uploads',
            'baseurl' => 'https://example.com/uploads',
            'error' => false
        ];
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        return true;
    }
}

if (!function_exists('wp_generate_attachment_metadata')) {
    function wp_generate_attachment_metadata($attachment_id, $file) {
        return [];
    }
}

if (!function_exists('wp_update_attachment_metadata')) {
    function wp_update_attachment_metadata($attachment_id, $data) {
        return true;
    }
}

if (!function_exists('wp_handle_upload')) {
    function wp_handle_upload($file, $overrides = false, $time = null) {
        return [
            'file' => '/uploads/test.jpg',
            'url' => 'https://example.com/uploads/test.jpg',
            'type' => 'image/jpeg',
            'error' => false
        ];
    }
}

if (!function_exists('wp_check_filetype')) {
    function wp_check_filetype($filename, $mimes = null) {
        return ['ext' => 'jpg', 'type' => 'image/jpeg'];
    }
}

if (!function_exists('wp_upload_bits')) {
    function wp_upload_bits($name, $deprecated, $bits, $time = null) {
        return [
            'file' => '/uploads/' . $name,
            'url' => 'https://example.com/uploads/' . $name,
            'error' => false
        ];
    }
}

if (!function_exists('wp_get_post_parent_id')) {
    function wp_get_post_parent_id($post) {
        return 0;
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        return 123;
    }
}

if (!function_exists('register_meta')) {
    function register_meta($object_type, $meta_key, $args) {
        return true;
    }
}
