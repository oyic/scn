<?php

namespace SCN\Membership\Modules\Courses;

class CoursesModule {
    private $post_type;
    private $admin_interface;
    private $frontend_templates;

    public function register() {
        $this->post_type = new CoursePostType();
        $this->admin_interface = new AdminInterface();
        $this->frontend_templates = new FrontendTemplates();

        $this->post_type->register();
        $this->admin_interface->register();
        $this->frontend_templates->register();

        add_action('init', [$this, 'init']);
        add_action('init', [$this, 'addCapabilities']);
    }

    public function init() {
        $this->registerHooks();
    }


    public function addCapabilities() {
        $role = get_role('administrator');
        if ($role) {
            $capabilities = [
                'edit_courses',
                'edit_others_courses',
                'publish_courses',
                'read_private_courses',
                'delete_courses',
                'delete_private_courses',
                'delete_published_courses',
                'delete_others_courses',
                'edit_private_courses',
                'edit_published_courses',
            ];

            foreach ($capabilities as $cap) {
                $role->add_cap($cap);
            }
        }

        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_capabilities = [
                'edit_courses',
                'edit_others_courses',
                'publish_courses',
                'read_private_courses',
                'delete_courses',
                'delete_others_courses',
                'delete_published_courses',
                'edit_published_courses',
            ];

            foreach ($editor_capabilities as $cap) {
                $editor_role->add_cap($cap);
            }
        }

        $author_role = get_role('author');
        if ($author_role) {
            $author_capabilities = [
                'edit_courses',
                'publish_courses',
                'delete_courses',
                'edit_published_courses',
            ];

            foreach ($author_capabilities as $cap) {
                $author_role->add_cap($cap);
            }
        }
    }

    public function registerHooks() {
        add_filter('scn/course/allowed_formats', [$this, 'getAllowedFormats']);
        add_filter('scn/course/ondemand_link', [$this, 'normalizeOnDemandLink'], 10, 1);
        add_filter('scn/course/placeholder_image_id', [$this, 'getPlaceholderImageId']);
        
        add_action('scn/course/created', [$this, 'onCourseCreated'], 10, 1);
        add_action('scn/course/updated', [$this, 'onCourseUpdated'], 10, 1);
    }

    public function getAllowedFormats($formats) {
        return apply_filters('course_allowed_formats', [
            'keynote' => __('Keynote', 'scn-membership'),
            'lecture' => __('Lecture', 'scn-membership'),
            'workshop' => __('Workshop', 'scn-membership'),
            'panel' => __('Panel', 'scn-membership'),
        ]);
    }

    public function normalizeOnDemandLink($link) {
        if (empty($link)) {
            return '';
        }

        $link = esc_url_raw($link);
        
        if (!wp_http_validate_url($link)) {
            return '';
        }

        if (strpos($link, 'http://') === 0) {
            $link = str_replace('http://', 'https://', $link);
        } elseif (strpos($link, '//') === 0) {
            $link = 'https:' . $link;
        } elseif (!preg_match('/^https?:\/\//', $link)) {
            $link = 'https://' . $link;
        }

        return apply_filters('course_ondemand_link', $link);
    }

    public function getPlaceholderImageId() {
        $placeholder_id = get_option('course_placeholder_image_id', 0);
        return apply_filters('course_placeholder_image_id', $placeholder_id);
    }

    public function onCourseCreated($post_id) {
        do_action('course_created', $post_id);
    }

    public function onCourseUpdated($post_id) {
        do_action('course_updated', $post_id);
    }
}