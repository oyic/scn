<?php
// Enable REST API for ACF field groups
add_filter('register_post_type_args', function($args, $post_type) {
    if ($post_type === 'acf-field-group') {
        $args['show_in_rest'] = true;
        $args['rest_base'] = 'acf-field-group';
        $args['rest_controller_class'] = 'WP_REST_Posts_Controller';
    }
    return $args;
}, 10, 2);

// Ensure current user can access ACF REST endpoints
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    $route = $request->get_route();
    
    if (strpos($route, '/wp/v2/types/acf-field-group') !== false) {
        if (current_user_can('manage_options')) {
            return $result;
        }
    }
    
    return $result;
}, 10, 3);