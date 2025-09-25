<?php

namespace SCN\Membership\Modules\Events;

class EventYearPages {
    private $sessions_service;
    private $search_service;

    public function __construct() {
        $this->sessions_service = new SessionsService();
        $this->search_service = new EventsSearchService();
    }

    public function register() {
        add_action('init', [$this, 'addRewriteRules']);
        add_action('template_redirect', [$this, 'handleEventYearPage']);
        add_filter('query_vars', [$this, 'addQueryVars']);
        add_action('wp_head', [$this, 'addSchemaMarkup']);
    }

    public function addRewriteRules() {
        add_rewrite_rule(
            '^events/([^/]+)/([0-9]{4})/?$',
            'index.php?scn_event_slug=$matches[1]&scn_event_year=$matches[2]',
            'top'
        );
    }

    public function addQueryVars($vars) {
        $vars[] = 'scn_event_slug';
        $vars[] = 'scn_event_year';
        return $vars;
    }

    public function handleEventYearPage() {
        $event_slug = get_query_var('scn_event_slug');
        $event_year = get_query_var('scn_event_year');

        if (!$event_slug || !$event_year) {
            return;
        }

        $event = $this->getEventBySlugAndYear($event_slug, $event_year);
        if (!$event) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        $this->renderEventYearPage($event, $event_year);
        exit;
    }

    public function getEventBySlugAndYear($slug, $year) {
        global $wpdb;

        $event = get_page_by_path($slug, OBJECT, 'scn_event');
        if (!$event) {
            return null;
        }

        $event_year = get_post_meta($event->ID, 'scn_event_year', true);
        if ($event_year != $year) {
            return null;
        }

        return $event;
    }

    public function renderEventYearPage($event, $year) {
        $event_data = $this->search_service->getEventData($event->ID);
        $sessions = $this->sessions_service->getSessionsByEvent($event->ID, 'approved');
        $speakers = $this->getEventSpeakers($sessions);

        $template_data = apply_filters('scn/events/event_year_template_data', [
            'event' => $event,
            'event_data' => $event_data,
            'year' => $year,
            'sessions' => $sessions,
            'speakers' => $speakers,
        ], $event, $year);

        $template_path = SCN_MEMBERSHIP_PATH . 'templates/events/event-year.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->renderDefaultTemplate($template_data);
        }
    }

    public function getEventSpeakers($sessions) {
        $speakers = [];
        $speaker_ids = [];

        foreach ($sessions as $session) {
            if (!in_array($session->profile_id, $speaker_ids)) {
                $speaker_ids[] = $session->profile_id;
                $profile = get_post($session->profile_id);
                if ($profile) {
                    $speakers[] = [
                        'id' => $profile->ID,
                        'name' => $profile->post_title,
                        'url' => get_permalink($profile->ID),
                        'headshot' => $this->getProfileHeadshot($profile->ID),
                        'bio' => $this->getProfileBio($profile->ID),
                    ];
                }
            }
        }

        usort($speakers, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $speakers;
    }

    public function getProfileHeadshot($profile_id) {
        $headshot_id = get_post_meta($profile_id, 'scn_profile_headshot', true);
        if ($headshot_id) {
            return wp_get_attachment_image_url($headshot_id, 'medium');
        }
        return get_avatar_url($profile_id, ['size' => 300]);
    }

    public function getProfileBio($profile_id) {
        $bio = get_post_meta($profile_id, 'scn_profile_bio', true);
        if ($bio) {
            return wp_trim_words($bio, 50);
        }
        return '';
    }

    public function addSchemaMarkup() {
        $event_slug = get_query_var('scn_event_slug');
        $event_year = get_query_var('scn_event_year');

        if (!$event_slug || !$event_year) {
            return;
        }

        $event = $this->getEventBySlugAndYear($event_slug, $event_year);
        if (!$event) {
            return;
        }

        $event_data = $this->search_service->getEventData($event->ID);
        $sessions = $this->sessions_service->getSessionsByEvent($event->ID, 'approved');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event_data['official_name'],
            'description' => $event->post_content,
            'startDate' => $event_data['dates']['start'],
            'endDate' => $event_data['dates']['end'],
            'location' => [
                '@type' => 'Place',
                'name' => $event_data['location']['city'] . ', ' . $event_data['location']['region'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $event_data['location']['city'],
                    'addressRegion' => $event_data['location']['region'],
                    'addressCountry' => $event_data['location']['country'],
                ],
            ],
            'url' => $event_data['website'],
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        ];

        if (!empty($sessions)) {
            $schema['subEvent'] = [];
            foreach ($sessions as $session) {
                $profile = get_post($session->profile_id);
                $course = get_post($session->course_id);
                
                $schema['subEvent'][] = [
                    '@type' => 'Event',
                    'name' => $session->session_title,
                    'startDate' => $session->session_datetime,
                    'performer' => [
                        '@type' => 'Person',
                        'name' => $profile ? $profile->post_title : '',
                        'url' => $profile ? get_permalink($profile->ID) : '',
                    ],
                    'about' => [
                        '@type' => 'Course',
                        'name' => $course ? $course->post_title : '',
                    ],
                ];
            }
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_PRETTY_PRINT) . '</script>';
    }

    private function renderDefaultTemplate($data) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($data['event']->post_title . ' ' . $data['year']); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>
            <div class="scn-event-year-page">
                <header class="event-header">
                    <h1><?php echo esc_html($data['event']->post_title); ?></h1>
                    <div class="event-meta">
                        <p class="event-year"><?php echo esc_html($data['year']); ?></p>
                        <?php if (!empty($data['event_data']['dates']['start'])): ?>
                            <p class="event-dates">
                                <?php echo esc_html(date('F j, Y', strtotime($data['event_data']['dates']['start']))); ?>
                                <?php if (!empty($data['event_data']['dates']['end'])): ?>
                                    - <?php echo esc_html(date('F j, Y', strtotime($data['event_data']['dates']['end']))); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($data['event_data']['location']['city'])): ?>
                            <p class="event-location">
                                <?php echo esc_html($data['event_data']['location']['city']); ?>
                                <?php if (!empty($data['event_data']['location']['region'])): ?>
                                    , <?php echo esc_html($data['event_data']['location']['region']); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($data['event_data']['website'])): ?>
                            <p class="event-website">
                                <a href="<?php echo esc_url($data['event_data']['website']); ?>" target="_blank" rel="noopener">
                                    <?php _e('Event Website', 'scn-membership'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if (!empty($data['speakers'])): ?>
                    <section class="event-speakers">
                        <h2><?php _e('Speakers', 'scn-membership'); ?></h2>
                        <div class="speakers-grid">
                            <?php foreach ($data['speakers'] as $speaker): ?>
                                <div class="speaker-card">
                                    <?php if ($speaker['headshot']): ?>
                                        <img src="<?php echo esc_url($speaker['headshot']); ?>" 
                                             alt="<?php echo esc_attr($speaker['name']); ?>" 
                                             class="speaker-headshot">
                                    <?php endif; ?>
                                    <h3><a href="<?php echo esc_url($speaker['url']); ?>"><?php echo esc_html($speaker['name']); ?></a></h3>
                                    <?php if ($speaker['bio']): ?>
                                        <p class="speaker-bio"><?php echo esc_html($speaker['bio']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if (!empty($data['sessions'])): ?>
                    <section class="event-sessions">
                        <h2><?php _e('Sessions', 'scn-membership'); ?></h2>
                        <div class="sessions-list">
                            <?php foreach ($data['sessions'] as $session): ?>
                                <div class="session-item">
                                    <h3><?php echo esc_html($session->session_title); ?></h3>
                                    <div class="session-meta">
                                        <p class="session-speaker">
                                            <strong><?php _e('Speaker:', 'scn-membership'); ?></strong>
                                            <a href="<?php echo esc_url($session->profile_url); ?>">
                                                <?php echo esc_html($session->profile_name); ?>
                                            </a>
                                        </p>
                                        <p class="session-course">
                                            <strong><?php _e('Course:', 'scn-membership'); ?></strong>
                                            <?php echo esc_html($session->course_title); ?>
                                        </p>
                                        <p class="session-datetime">
                                            <strong><?php _e('Date & Time:', 'scn-membership'); ?></strong>
                                            <?php echo esc_html(date('F j, Y \a\t g:i A', strtotime($session->session_datetime))); ?>
                                        </p>
                                        <?php if ($session->room): ?>
                                            <p class="session-room">
                                                <strong><?php _e('Room:', 'scn-membership'); ?></strong>
                                                <?php echo esc_html($session->room); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
}




