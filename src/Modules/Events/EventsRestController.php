<?php

namespace SCN\Membership\Modules\Events;

class EventsRestController {
    private $search_service;
    private $sessions_service;
    private $session_approval_service;

    public function __construct() {
        $this->search_service = new EventsSearchService();
        $this->sessions_service = new SessionsService();
        $this->session_approval_service = new SessionApprovalService();
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

        register_rest_route('scn/v1', '/sessions', [
            'methods' => 'POST',
            'callback' => [$this, 'createSession'],
            'permission_callback' => [$this, 'checkSessionCreatePermissions'],
            'args' => [
                'event_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'course_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'session_title' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'session_datetime' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'room' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'type_override' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route('scn/v1', '/sessions/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'updateSession'],
            'permission_callback' => [$this, 'checkSessionUpdatePermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'session_title' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'session_datetime' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'room' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'type_override' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route('scn/v1', '/sessions/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'deleteSession'],
            'permission_callback' => [$this, 'checkSessionDeletePermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route('scn/v1', '/sessions', [
            'methods' => 'GET',
            'callback' => [$this, 'getSessions'],
            'permission_callback' => [$this, 'checkReadPermissions'],
            'args' => [
                'event_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'profile_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'status' => [
                    'required' => false,
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

        register_rest_route('scn/v1', '/sessions/approve/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'approveSession'],
            'permission_callback' => [$this, 'checkSessionApprovalPermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route('scn/v1', '/sessions/reject/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'rejectSession'],
            'permission_callback' => [$this, 'checkSessionApprovalPermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'reason' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        register_rest_route('scn/v1', '/sessions/pending', [
            'methods' => 'GET',
            'callback' => [$this, 'getPendingSessions'],
            'permission_callback' => [$this, 'checkSessionApprovalPermissions'],
            'args' => [
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 50,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route('scn/v1', '/sessions/bulk-approve', [
            'methods' => 'POST',
            'callback' => [$this, 'bulkApproveSessions'],
            'permission_callback' => [$this, 'checkSessionApprovalPermissions'],
            'args' => [
                'session_ids' => [
                    'required' => true,
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
            ],
        ]);

        register_rest_route('scn/v1', '/member-courses', [
            'methods' => 'GET',
            'callback' => [$this, 'getMemberCourses'],
            'permission_callback' => [$this, 'checkReadPermissions'],
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

    public function createSession($request) {
        $data = $request->get_params();
        $data['profile_id'] = get_current_user_id();
        
        $result = $this->sessions_service->createSession($data);
        
        if (is_wp_error($result)) {
            return new \WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                ['status' => 400]
            );
        }
        
        return rest_ensure_response($result);
    }

    public function updateSession($request) {
        $session_id = $request->get_param('id');
        $data = $request->get_params();
        
        $result = $this->sessions_service->updateSession($session_id, $data);
        
        if (is_wp_error($result)) {
            return new \WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                ['status' => 400]
            );
        }
        
        return rest_ensure_response($result);
    }

    public function deleteSession($request) {
        $session_id = $request->get_param('id');
        
        $result = $this->sessions_service->deleteSession($session_id);
        
        if (is_wp_error($result)) {
            return new \WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                ['status' => 400]
            );
        }
        
        return rest_ensure_response($result);
    }

    public function getSessions($request) {
        $event_id = $request->get_param('event_id');
        $profile_id = $request->get_param('profile_id');
        $status = $request->get_param('status');
        $limit = $request->get_param('limit');

        if ($event_id) {
            $sessions = $this->sessions_service->getSessionsByEvent($event_id, $status);
        } elseif ($profile_id) {
            $sessions = $this->sessions_service->getSessionsByProfile($profile_id, $status);
        } else {
            $sessions = $this->sessions_service->getUpcomingSessions($limit);
        }

        return rest_ensure_response($sessions);
    }

    public function approveSession($request) {
        $session_id = $request->get_param('id');
        
        $result = $this->session_approval_service->approveSession($session_id);
        
        if (is_wp_error($result)) {
            return new \WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                ['status' => 400]
            );
        }
        
        return rest_ensure_response($result);
    }

    public function rejectSession($request) {
        $session_id = $request->get_param('id');
        $reason = $request->get_param('reason');
        
        $result = $this->session_approval_service->rejectSession($session_id, $reason);
        
        if (is_wp_error($result)) {
            return new \WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                ['status' => 400]
            );
        }
        
        return rest_ensure_response($result);
    }

    public function getPendingSessions($request) {
        $limit = $request->get_param('limit');
        
        $sessions = $this->session_approval_service->getPendingSessions($limit);
        
        if (is_wp_error($sessions)) {
            return new \WP_Error(
                $sessions->get_error_code(),
                $sessions->get_error_message(),
                ['status' => 403]
            );
        }
        
        return rest_ensure_response($sessions);
    }

    public function bulkApproveSessions($request) {
        $session_ids = $request->get_param('session_ids');
        
        $result = $this->session_approval_service->bulkApproveSessions($session_ids);
        
        if (is_wp_error($result)) {
            return new \WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                ['status' => 400]
            );
        }
        
        return rest_ensure_response($result);
    }

    public function getMemberCourses($request) {
        $profile_id = get_current_user_id();
        $courses = $this->sessions_service->getMemberCourses($profile_id);
        
        return rest_ensure_response($courses);
    }

    public function checkSessionCreatePermissions($request) {
        return is_user_logged_in() && current_user_can('create_scn_sessions');
    }

    public function checkSessionUpdatePermissions($request) {
        $session_id = $request->get_param('id');
        $session = $this->sessions_service->getSession($session_id);
        
        if (!$session) {
            return false;
        }
        
        return current_user_can('manage_scn_sessions') || 
               (is_user_logged_in() && get_current_user_id() === $session->profile_id);
    }

    public function checkSessionDeletePermissions($request) {
        $session_id = $request->get_param('id');
        $session = $this->sessions_service->getSession($session_id);
        
        if (!$session) {
            return false;
        }
        
        return current_user_can('manage_scn_sessions') || 
               (is_user_logged_in() && get_current_user_id() === $session->profile_id);
    }

    public function checkSessionApprovalPermissions($request) {
        return current_user_can('approve_scn_sessions');
    }
}




