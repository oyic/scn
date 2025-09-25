<?php

namespace SCN\Membership\Modules\Events;

class HomepageWidget {
    private $sessions_service;

    public function __construct() {
        $this->sessions_service = new SessionsService();
    }

    public function register() {
        add_action('widgets_init', [$this, 'registerWidget']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('scn/events/session_created', [$this, 'clearCache']);
        add_action('scn/events/session_updated', [$this, 'clearCache']);
        add_action('scn/events/session_deleted', [$this, 'clearCache']);
        add_action('scn/events/session_status_changed', [$this, 'clearCache']);
    }

    public function registerWidget() {
        register_widget('SCN\\Membership\\Modules\\Events\\WhereMembersSpeakingWidget');
    }

    public function enqueueScripts() {
        if (is_active_widget(false, false, 'scn_where_members_speaking')) {
            wp_enqueue_style(
                'scn-events-widget',
                SCN_MEMBERSHIP_URL . 'assets/css/events-widget.css',
                [],
                SCN_MEMBERSHIP_VERSION
            );
        }
    }

    public function clearCache() {
        delete_transient('scn_events_widget_upcoming_sessions');
    }

    public function getUpcomingSessions($limit = 6) {
        $cache_key = 'scn_events_widget_upcoming_sessions';
        $sessions = get_transient($cache_key);

        if ($sessions === false) {
            $sessions = $this->sessions_service->getUpcomingSessions($limit);
            set_transient($cache_key, $sessions, 15 * MINUTE_IN_SECONDS);
        }

        return $sessions;
    }

    public function renderWidget($args, $instance) {
        $limit = intval($instance['limit'] ?? 6);
        $sessions = $this->getUpcomingSessions($limit);

        if (empty($sessions)) {
            return;
        }

        $widget_data = apply_filters('scn/events/widget_output', [
            'sessions' => $sessions,
            'title' => $instance['title'] ?? __('Where Our Members Are Speaking', 'scn-membership'),
            'show_dates' => $instance['show_dates'] ?? true,
            'show_events' => $instance['show_events'] ?? true,
        ], $instance);

        $this->renderWidgetTemplate($widget_data, $args);
    }

    private function renderWidgetTemplate($data, $args) {
        echo $args['before_widget'];
        
        if (!empty($data['title'])) {
            echo $args['before_title'] . esc_html($data['title']) . $args['after_title'];
        }

        echo '<div class="scn-members-speaking-widget">';
        echo '<div class="members-speaking-grid">';

        foreach ($data['sessions'] as $session) {
            $this->renderSessionCard($session, $data);
        }

        echo '</div>';
        echo '</div>';
        echo $args['after_widget'];
    }

    private function renderSessionCard($session, $data) {
        $profile_url = get_permalink($session->profile_id);
        $event_url = $this->getEventYearUrl($session->event_slug, $session->session_datetime);
        $headshot = $this->getProfileHeadshot($session->profile_id);
        $event_date = date('M j, Y', strtotime($session->session_datetime));

        echo '<div class="member-speaking-card">';
        
        if ($headshot) {
            echo '<div class="member-photo">';
            echo '<a href="' . esc_url($profile_url) . '">';
            echo '<img src="' . esc_url($headshot) . '" alt="' . esc_attr($session->profile_name) . '">';
            echo '</a>';
            echo '</div>';
        }

        echo '<div class="member-info">';
        echo '<h4><a href="' . esc_url($profile_url) . '">' . esc_html($session->profile_name) . '</a></h4>';
        
        if ($data['show_events']) {
            echo '<p class="event-name">';
            echo '<a href="' . esc_url($event_url) . '">' . esc_html($session->event_name) . '</a>';
            echo '</p>';
        }
        
        if ($data['show_dates']) {
            echo '<p class="event-date">' . esc_html($event_date) . '</p>';
        }
        
        echo '</div>';
        echo '</div>';
    }

    private function getProfileHeadshot($profile_id) {
        $headshot_id = get_post_meta($profile_id, 'scn_profile_headshot', true);
        if ($headshot_id) {
            return wp_get_attachment_image_url($headshot_id, 'thumbnail');
        }
        return get_avatar_url($profile_id, ['size' => 150]);
    }

    private function getEventYearUrl($event_slug, $session_datetime) {
        $year = date('Y', strtotime($session_datetime));
        return home_url("/events/{$event_slug}/{$year}/");
    }
}

class WhereMembersSpeakingWidget extends \WP_Widget {
    public function __construct() {
        parent::__construct(
            'scn_where_members_speaking',
            __('Where Our Members Are Speaking', 'scn-membership'),
            [
                'description' => __('Display upcoming speaking sessions by SCN members.', 'scn-membership'),
                'classname' => 'scn-where-members-speaking-widget',
            ]
        );
    }

    public function widget($args, $instance) {
        $widget = new HomepageWidget();
        $widget->renderWidget($args, $instance);
    }

    public function form($instance) {
        $title = $instance['title'] ?? __('Where Our Members Are Speaking', 'scn-membership');
        $limit = intval($instance['limit'] ?? 6);
        $show_dates = $instance['show_dates'] ?? true;
        $show_events = $instance['show_events'] ?? true;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'scn-membership'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Number of sessions to show:', 'scn-membership'); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>" 
                   name="<?php echo $this->get_field_name('limit'); ?>" 
                   type="number" value="<?php echo esc_attr($limit); ?>" min="1" max="20">
        </p>
        <p>
            <input class="checkbox" type="checkbox" 
                   <?php checked($show_dates); ?> 
                   id="<?php echo $this->get_field_id('show_dates'); ?>" 
                   name="<?php echo $this->get_field_name('show_dates'); ?>">
            <label for="<?php echo $this->get_field_id('show_dates'); ?>"><?php _e('Show event dates', 'scn-membership'); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" 
                   <?php checked($show_events); ?> 
                   id="<?php echo $this->get_field_id('show_events'); ?>" 
                   name="<?php echo $this->get_field_name('show_events'); ?>">
            <label for="<?php echo $this->get_field_id('show_events'); ?>"><?php _e('Show event names', 'scn-membership'); ?></label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['limit'] = intval($new_instance['limit'] ?? 6);
        $instance['show_dates'] = !empty($new_instance['show_dates']);
        $instance['show_events'] = !empty($new_instance['show_events']);
        
        return $instance;
    }
}




