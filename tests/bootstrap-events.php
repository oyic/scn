<?php
/**
 * Test bootstrap for Events module
 * Mocks WordPress functions and classes for testing
 */

// Mock WordPress functions
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

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
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

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        return $value;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // Mock implementation
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        // Mock implementation
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        // Mock implementation
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('get_post')) {
    function get_post($post_id) {
        return (object) [
            'ID' => $post_id,
            'post_type' => 'scn_event',
            'post_title' => 'Test Event',
            'post_status' => 'publish'
        ];
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($post_id) {
        return 'scn_event';
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key, $single = true) {
        return '';
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value) {
        return true;
    }
}

if (!function_exists('wp_trash_post')) {
    function wp_trash_post($post_id) {
        return true;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return true;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test_nonce';
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message) {
        throw new Exception($message);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        return ['success' => true, 'data' => $data];
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($message = null) {
        return ['success' => false, 'data' => $message];
    }
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($data) {
        return $data;
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args) {
        // Mock implementation
    }
}

if (!function_exists('register_post_type')) {
    function register_post_type($post_type, $args) {
        // Mock implementation
    }
}

if (!function_exists('register_meta')) {
    function register_meta($object_type, $meta_key, $args) {
        // Mock implementation
    }
}

if (!function_exists('add_meta_box')) {
    function add_meta_box($id, $title, $callback, $screen, $context, $priority) {
        // Mock implementation
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = false, $deps = [], $ver = false, $in_footer = false) {
        // Mock implementation
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = false, $deps = [], $ver = false, $media = 'all') {
        // Mock implementation
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        // Mock implementation
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action, $query_arg = '_ajax_nonce', $die = true) {
        return true;
    }
}

if (!function_exists('get_role')) {
    function get_role($role) {
        return (object) [
            'add_cap' => function($cap) { return true; }
        ];
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        return true;
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        return ['body' => '{}'];
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'] ?? '';
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('wp_get_attachment_url')) {
    function wp_get_attachment_url($attachment_id) {
        return 'http://example.com/wp-content/uploads/test.jpg';
    }
}

if (!function_exists('wp_get_attachment_image')) {
    function wp_get_attachment_image($attachment_id, $size = 'thumbnail') {
        return '<img src="test.jpg" alt="test">';
    }
}

if (!function_exists('wp_editor')) {
    function wp_editor($content, $editor_id, $settings = []) {
        echo '<textarea id="' . $editor_id . '">' . $content . '</textarea>';
    }
}

if (!function_exists('get_terms')) {
    function get_terms($args) {
        return [];
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data) {
        return $data;
    }
}

if (!function_exists('wp_http_validate_url')) {
    function wp_http_validate_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
}

if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs(intval($maybeint));
    }
}

if (!function_exists('version_compare')) {
    function version_compare($version1, $version2, $operator = null) {
        return \version_compare($version1, $version2, $operator);
    }
}

if (!function_exists('dbDelta')) {
    function dbDelta($queries) {
        return true;
    }
}

// require_once is a language construct, not a function

if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

if (!defined('SCN_MEMBERSHIP_VERSION')) {
    define('SCN_MEMBERSHIP_VERSION', '1.0.0');
}

if (!defined('SCN_MEMBERSHIP_URL')) {
    define('SCN_MEMBERSHIP_URL', 'http://example.com/wp-content/plugins/scn-membership/');
}

// Mock WP_Error class
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $errors = [];
        private $error_data = [];

        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->errors[$code][] = $message;
                if (!empty($data)) {
                    $this->error_data[$code] = $data;
                }
            }
        }

        public function get_error_code() {
            $codes = array_keys($this->errors);
            return empty($codes) ? '' : $codes[0];
        }

        public function get_error_message($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return isset($this->errors[$code]) ? $this->errors[$code][0] : '';
        }

        public function get_error_data($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return isset($this->error_data[$code]) ? $this->error_data[$code] : '';
        }
    }
}

// Mock $wpdb global
global $wpdb;

class MockWpdb {
    public $prefix = 'wp_';
    public $posts = 'wp_posts';
    public $users = 'wp_users';
    
    public function get_results($query) {
        return [];
    }
    
    public function get_row($query) {
        return null;
    }
    
    public function get_var($query) {
        return 0;
    }
    
    public function insert($table, $data, $format) {
        return 1;
    }
    
    public function update($table, $data, $where, $format, $where_format) {
        return 1;
    }
    
    public function delete($table, $where, $where_format) {
        return 1;
    }
    
    public function replace($table, $data, $format) {
        return 1;
    }
    
    public function prepare($query, ...$args) {
        return $query;
    }
    
    public function esc_like($text) {
        return addcslashes($text, '_%\\');
    }
    
    public function query($query) {
        return true;
    }
}

$wpdb = new MockWpdb();
