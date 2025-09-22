<?php

namespace SCN\Membership\Modules\Events;

class EventsRestController {
    private $search_service;

    public function __construct() {
        $this->search_service = new EventsSearchService();
    }

    public function register() {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes() {
        register_rest_route('scn/v1', '/events/search', [
            'methods' => 'GET',
            'callback' => [$this, 'searchEvents'],
            'permission_callback' => [$this, 'checkSearchPermissions'],
            'args' => [
                'q' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route('scn/v1', '/events/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getEvent'],
            'permission_callback' => [$this, 'checkReadPermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route('scn/v1', '/events/by-alias/(?P<alias>[a-zA-Z0-9\-_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getEventByAlias'],
            'permission_callback' => [$this, 'checkReadPermissions'],
            'args' => [
                'alias' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    public function searchEvents($request) {
        $query = $request->get_param('q');
        $limit = $request->get_param('limit');

        $results = $this->search_service->search($query, $limit);
        $formatted_results = [];

        foreach ($results as $result) {
            $event_data = $this->search_service->getEventData($result->ID);
            if ($event_data) {
                $formatted_results[] = array_merge($event_data, [
                    'match_type' => $result->match_type,
                    'matched_text' => $result->matched_text,
                ]);
            }
        }

        return rest_ensure_response($formatted_results);
    }

    public function getEvent($request) {
        $event_id = $request->get_param('id');
        $event_data = $this->search_service->getEventData($event_id);

        if (!$event_data) {
            return new \WP_Error(
                'event_not_found',
                __('Event not found.', 'scn-membership'),
                ['status' => 404]
            );
        }

        return rest_ensure_response($event_data);
    }

    public function getEventByAlias($request) {
        $alias = $request->get_param('alias');
        $event_id = $this->search_service->getEventByAlias($alias);

        if (!$event_id) {
            return new \WP_Error(
                'event_not_found',
                __('Event not found for the given alias.', 'scn-membership'),
                ['status' => 404]
            );
        }

        $event_data = $this->search_service->getEventData($event_id);
        if (!$event_data) {
            return new \WP_Error(
                'event_not_found',
                __('Event not found.', 'scn-membership'),
                ['status' => 404]
            );
        }

        return rest_ensure_response($event_data);
    }

    public function checkSearchPermissions($request) {
        return current_user_can('read');
    }

    public function checkReadPermissions($request) {
        return current_user_can('read');
    }
}






