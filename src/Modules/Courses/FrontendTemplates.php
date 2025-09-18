<?php

namespace SCN\Membership\Modules\Courses;

class FrontendTemplates {
    public function register() {
        add_action('template_redirect', [$this, 'loadTemplates']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendScripts']);
        add_action('scn/profile/overview_content', [$this, 'addCoursesToProfile'], 10, 1);
    }

    public function loadTemplates() {
        if (is_singular('scn_course')) {
            add_filter('template_include', [$this, 'loadSingleCourseTemplate']);
        } elseif (is_post_type_archive('scn_course')) {
            add_filter('template_include', [$this, 'loadArchiveCourseTemplate']);
        }
    }

    public function loadSingleCourseTemplate($template) {
        $custom_template = locate_template(['single-scn_course.php']);
        if ($custom_template) {
            return $custom_template;
        }
        
        $plugin_template = plugin_dir_path(__FILE__) . '../../templates/courses/single-course.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return $template;
    }

    public function loadArchiveCourseTemplate($template) {
        $custom_template = locate_template(['archive-scn_course.php']);
        if ($custom_template) {
            return $custom_template;
        }
        
        $plugin_template = plugin_dir_path(__FILE__) . '../../templates/courses/archive-course.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return $template;
    }

    public function enqueueFrontendScripts() {
        if (is_singular('scn_course') || is_post_type_archive('scn_course')) {
            wp_enqueue_style('scn-courses-frontend', plugin_dir_url(__FILE__) . '../../../assets/css/courses.css', [], '1.0.0');
        }
    }

    public function addCoursesToProfile($profile_id) {
        $courses = get_posts([
            'post_type' => 'scn_course',
            'author' => get_post_field('post_author', $profile_id),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);

        if (empty($courses)) {
            return;
        }

        ?>
        <section class="scn-profile-courses">
            <h3><?php _e('Courses by this Member', 'scn-membership'); ?></h3>
            <div class="scn-courses-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="scn-course-card">
                        <div class="scn-course-image">
                            <?php
                            $image_id = get_post_meta($course->ID, 'scn_course_image_id', true);
                            if ($image_id) {
                                echo wp_get_attachment_image($image_id, 'medium', false, ['alt' => get_the_title($course->ID)]);
                            } else {
                                $placeholder_id = apply_filters('scn/course/placeholder_image_id', 0);
                                if ($placeholder_id) {
                                    echo wp_get_attachment_image($placeholder_id, 'medium', false, ['alt' => get_the_title($course->ID)]);
                                } else {
                                    echo '<div class="scn-course-placeholder">' . esc_html__('No image available', 'scn-membership') . '</div>';
                                }
                            }
                            ?>
                        </div>
                        <div class="scn-course-content">
                            <h4><a href="<?php echo get_permalink($course->ID); ?>"><?php echo get_the_title($course->ID); ?></a></h4>
                            <?php
                            $subtitle = get_post_meta($course->ID, 'scn_course_subtitle', true);
                            if ($subtitle) {
                                echo '<p class="scn-course-subtitle">' . esc_html($subtitle) . '</p>';
                            }
                            
                            $topics = wp_get_post_terms($course->ID, 'scn_topic');
                            if (!empty($topics)) {
                                echo '<div class="scn-course-topics">';
                                foreach ($topics as $topic) {
                                    echo '<span class="scn-topic-pill">' . esc_html($topic->name) . '</span>';
                                }
                                echo '</div>';
                            }
                            
                            $ce_enabled = get_post_meta($course->ID, 'scn_course_ce_enabled', true);
                            if ($ce_enabled) {
                                $ce_hours = get_post_meta($course->ID, 'scn_course_ce_hours', true);
                                echo '<div class="scn-ce-badge">' . sprintf(__('Provides %s CE hours', 'scn-membership'), $ce_hours) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    public static function getCourseImage($course_id, $size = 'medium') {
        $image_id = get_post_meta($course_id, 'scn_course_image_id', true);
        
        if ($image_id) {
            return wp_get_attachment_image($image_id, $size, false, ['alt' => get_the_title($course_id)]);
        }
        
        $placeholder_id = apply_filters('scn/course/placeholder_image_id', 0);
        if ($placeholder_id) {
            return wp_get_attachment_image($placeholder_id, $size, false, ['alt' => get_the_title($course_id)]);
        }
        
        return '<div class="scn-course-placeholder">' . esc_html__('No image available', 'scn-membership') . '</div>';
    }

    public static function getCourseTopics($course_id) {
        $topics = wp_get_post_terms($course_id, 'scn_topic');
        if (empty($topics)) {
            return '';
        }
        
        $output = '<div class="scn-course-topics">';
        foreach ($topics as $topic) {
            $output .= '<span class="scn-topic-pill">' . esc_html($topic->name) . '</span>';
        }
        $output .= '</div>';
        
        return $output;
    }

    public static function getCeBadge($course_id) {
        $ce_enabled = get_post_meta($course_id, 'scn_course_ce_enabled', true);
        if (!$ce_enabled) {
            return '';
        }
        
        $ce_hours = get_post_meta($course_id, 'scn_course_ce_hours', true);
        return '<div class="scn-ce-badge">' . sprintf(__('Provides %s CE hours', 'scn-membership'), $ce_hours) . '</div>';
    }

    public static function getCourseFormats($course_id) {
        $formats = get_post_meta($course_id, 'scn_course_formats', true) ?: [];
        if (empty($formats)) {
            return '';
        }
        
        $allowed_formats = apply_filters('scn/course/allowed_formats', []);
        $format_labels = [];
        
        foreach ($formats as $format) {
            if (isset($allowed_formats[$format])) {
                $format_labels[] = $allowed_formats[$format];
            }
        }
        
        return '<div class="scn-course-formats">' . implode(', ', $format_labels) . '</div>';
    }

    public static function getCourseOutcomes($course_id) {
        $outcomes = get_post_meta($course_id, 'scn_course_outcomes', true) ?: [];
        if (empty($outcomes)) {
            return '';
        }
        
        $output = '<div class="scn-course-outcomes"><h4>' . __('Learning Outcomes', 'scn-membership') . '</h4><ul>';
        foreach ($outcomes as $outcome) {
            $output .= '<li>' . esc_html($outcome) . '</li>';
        }
        $output .= '</ul></div>';
        
        return $output;
    }

    public static function getOnDemandInfo($course_id) {
        $ondemand = get_post_meta($course_id, 'scn_course_ondemand', true) ?: [];
        if (empty($ondemand['link'])) {
            return '';
        }
        
        $title = $ondemand['title'] ?: __('On-Demand Course', 'scn-membership');
        $school = $ondemand['school'] ?: '';
        $link = apply_filters('scn/course/ondemand_link', $ondemand['link']);
        
        $output = '<div class="scn-ondemand-info">';
        $output .= '<h4>' . esc_html($title) . '</h4>';
        if ($school) {
            $output .= '<p class="scn-ondemand-school">' . esc_html($school) . '</p>';
        }
        $output .= '<a href="' . esc_url($link) . '" target="_blank" rel="noopener" class="scn-ondemand-link">';
        $output .= __('Access Course', 'scn-membership') . '</a>';
        $output .= '</div>';
        
        return $output;
    }
}
