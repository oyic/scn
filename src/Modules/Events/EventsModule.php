<?php

namespace SCN\Membership\Modules\Events;

class EventsModule {
    private $post_type;
    private $aliases_service;
    private $locks_service;
    private $merge_service;
    private $search_service;
    private $rest_controller;
    private $sessions_service;
    private $session_approval_service;
    private $event_year_pages;
    private $homepage_widget;

    public function register() {
        $this->post_type = new EventPostType();
        $this->aliases_service = new AliasesService();
        $this->locks_service = new LocksService();
        $this->merge_service = new MergeService();
        $this->search_service = new EventsSearchService();
        $this->rest_controller = new EventsRestController();
        $this->sessions_service = new SessionsService();
        $this->session_approval_service = new SessionApprovalService();
        $this->event_year_pages = new EventYearPages();
        $this->homepage_widget = new HomepageWidget();
        
        $this->post_type->register();
        $this->aliases_service->register();
        $this->locks_service->register();
        $this->merge_service->register();
        $this->search_service->register();
        $this->rest_controller->register();
        $this->sessions_service->register();
        $this->session_approval_service->register();
        $this->event_year_pages->register();
        $this->homepage_widget->register();

        add_action('init', [$this, 'init']);
        add_action('init', [$this, 'addCapabilities']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }

    public function init() {
        $this->registerHooks();
    }

    public function addCapabilities() {
        $admin_role = \get_role('administrator');
        if ($admin_role) {
            $capabilities = [
                'manage_scn_events',
                'merge_scn_events',
                'lock_scn_events',
                'manage_scn_sessions',
                'approve_scn_sessions',
            ];

            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }

        $editor_role = \get_role('editor');
        if ($editor_role) {
            $editor_capabilities = [
                'manage_scn_events',
                'manage_scn_sessions',
                'approve_scn_sessions',
            ];
            
            foreach ($editor_capabilities as $cap) {
                $editor_role->add_cap($cap);
            }
        }

        $author_role = \get_role('author');
        if ($author_role) {
            $author_role->add_cap('create_scn_sessions');
        }

        $merge_cap = apply_filters('scn/events/merge_capability', 'merge_scn_events');
        $lock_cap = apply_filters('scn/events/lock_capability', 'lock_scn_events');

        if ($editor_role && current_user_can($merge_cap)) {
            $editor_role->add_cap($merge_cap);
        }

        if ($editor_role && current_user_can($lock_cap)) {
            $editor_role->add_cap($lock_cap);
        }
    }

    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'scn_event') !== false || strpos($hook, 'scn-events') !== false) {
            wp_enqueue_script(
                'scn-events-admin',
                SCN_MEMBERSHIP_URL . 'assets/js/events-admin.js',
                ['jquery'],
                SCN_MEMBERSHIP_VERSION,
                true
            );

            wp_enqueue_style(
                'scn-events-admin',
                SCN_MEMBERSHIP_URL . 'assets/css/events-admin.css',
                [],
                SCN_MEMBERSHIP_VERSION
            );

            wp_localize_script('scn-events-admin', 'scnEvents', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('scn_events_nonce'),
            ]);
        }
    }

    public function registerHooks() {
        add_action('scn/events/field_locked', [$this, 'handleFieldLocked'], 10, 3);
        add_action('scn/events/merge/before', [$this, 'handleMergeBefore'], 10, 2);
        add_action('scn/events/merge/after', [$this, 'handleMergeAfter'], 10, 3);
    }

    public function handleFieldLocked($event_id, $field, $locked) {
        $event = get_post($event_id);
        if ($event && $event->post_type === 'scn_event') {
            $locked_fields = get_post_meta($event_id, 'scn_event_locked_fields', true) ?: [];
            
            if ($locked) {
                if (!in_array($field, $locked_fields)) {
                    $locked_fields[] = $field;
                }
            } else {
                $locked_fields = array_diff($locked_fields, [$field]);
            }
            
            update_post_meta($event_id, 'scn_event_locked_fields', $locked_fields);
        }
    }

    public function handleMergeBefore($source_ids, $target_id) {
        do_action('scn/events/merge/before_validation', $source_ids, $target_id);
    }

    public function handleMergeAfter($source_ids, $target_id, $report) {
        do_action('scn/events/merge/after_cleanup', $source_ids, $target_id, $report);
    }
}