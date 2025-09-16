<?php
/**
 * Test bootstrap file
 */

// Mock WordPress functions for testing
if (!function_exists('remove_accents')) {
    function remove_accents($string) {
        return str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $string);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return sanitize_text_field($str);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs(intval($maybeint));
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url) {
        return ['body' => '{"0":{"thumbnail_large":"https://example.com/thumb.jpg"}}'];
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

if (!function_exists('get_role')) {
    function get_role($role) {
        return new class($role) {
            private $capabilities = [];
            public function __construct($role) {
                if ($role === 'administrator') {
                    $this->capabilities = ['edit_scn_profiles', 'publish_scn_profiles', 'delete_scn_profiles'];
                }
            }
            public function add_cap($cap) {
                $this->capabilities[] = $cap;
            }
            public function has_cap($cap) {
                return in_array($cap, $this->capabilities);
            }
        };
    }
}

if (!function_exists('get_taxonomy')) {
    function get_taxonomy($taxonomy) {
        if ($taxonomy === 'scn_topic') {
            return new class {
                public $name = 'scn_topic';
                public $object_type = ['scn_profile'];
            };
        }
        return null;
    }
}

if (!function_exists('get_post_types')) {
    function get_post_types($args, $output) {
        return ['scn_profile', 'post', 'page'];
    }
}

if (!function_exists('get_post_type_object')) {
    function get_post_type_object($post_type) {
        if ($post_type === 'scn_profile') {
            return new class {
                public $name = 'scn_profile';
                public $public = true;
                public $show_in_rest = true;
            };
        }
        return null;
    }
}

if (!function_exists('get_registered_meta')) {
    function get_registered_meta($object_type, $meta_key) {
        return ['type' => 'string', 'single' => true];
    }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post($post_data) {
        return 123; // Mock post ID
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post($post_id, $force) {
        return true;
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value) {
        return true;
    }
}

// Global mock data storage
if (!isset($GLOBALS['mock_meta_data'])) {
    $GLOBALS['mock_meta_data'] = [];
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $meta_key, $single) {
        $key = $post_id . '_' . $meta_key;
        return $GLOBALS['mock_meta_data'][$key] ?? [];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value) {
        $key = $post_id . '_' . $meta_key;
        $GLOBALS['mock_meta_data'][$key] = $meta_value;
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        return $value;
    }
}

if (!function_exists('get_post')) {
    function get_post($post_id) {
        return new class($post_id) {
            public $ID;
            public $post_parent;
            public $post_type;
            public function __construct($id) {
                $this->ID = $id;
                $this->post_parent = 0;
                $this->post_type = 'scn_profile';
            }
        };
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        return 123;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('register_taxonomy')) {
    function register_taxonomy($taxonomy, $object_type, $args) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('wp_get_post_parent_id')) {
    function wp_get_post_parent_id($post_id) {
        return 0;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data) {
        return strip_tags($data, '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><h4><h5><h6>');
    }
}


