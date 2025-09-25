<?php

namespace SCN\Membership\Modules\Events;

class EventsSearchService {
    private $aliases_service;

    public function __construct() {
        $this->aliases_service = new AliasesService();
    }

    public function register() {
        add_action('wp_ajax_scn_search_events', [$this, 'handleSearchEvents']);
    }

    public function search($query, $limit = 20) {
        global $wpdb;

        $query = sanitize_text_field($query);
        $limit = intval($limit);

        if (empty($query)) {
            return [];
        }

        $results = [];

        $exact_alias_matches = $this->searchExactAliases($query, $limit);
        $results = array_merge($results, $exact_alias_matches);

        $alias_like_matches = $this->searchAliasesLike($query, $limit - count($results));
        $results = array_merge($results, $alias_like_matches);

        $title_like_matches = $this->searchTitlesLike($query, $limit - count($results));
        $results = array_merge($results, $title_like_matches);

        $results = array_slice($results, 0, $limit);
        $results = $this->removeDuplicates($results);

        return apply_filters('scn/events/fuzzy_results', $results, $query);
    }

    public function searchExactAliases($query, $limit) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, 'exact_alias' as match_type, ea.alias as matched_text
                 FROM {$wpdb->posts} p
                 JOIN {$wpdb->prefix}scn_event_aliases ea ON p.ID = ea.event_id
                 WHERE ea.alias = %s
                 AND p.post_type = 'scn_event'
                 AND p.post_status = 'publish'
                 ORDER BY p.post_title ASC
                 LIMIT %d",
                $query,
                $limit
            )
        );

        return $results ?: [];
    }

    public function searchAliasesLike($query, $limit) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, 'alias_like' as match_type, ea.alias as matched_text
                 FROM {$wpdb->posts} p
                 JOIN {$wpdb->prefix}scn_event_aliases ea ON p.ID = ea.event_id
                 WHERE ea.alias LIKE %s
                 AND p.post_type = 'scn_event'
                 AND p.post_status = 'publish'
                 ORDER BY ea.alias ASC
                 LIMIT %d",
                '%' . $wpdb->esc_like($query) . '%',
                $limit
            )
        );

        return $results ?: [];
    }

    public function searchTitlesLike($query, $limit) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, 'title_like' as match_type, p.post_title as matched_text
                 FROM {$wpdb->posts} p
                 WHERE p.post_title LIKE %s
                 AND p.post_type = 'scn_event'
                 AND p.post_status = 'publish'
                 ORDER BY p.post_title ASC
                 LIMIT %d",
                '%' . $wpdb->esc_like($query) . '%',
                $limit
            )
        );

        return $results ?: [];
    }

    public function getEventByAlias($alias) {
        return $this->aliases_service->getEventByAlias($alias);
    }

    public function getEventData($event_id) {
        $post = get_post($event_id);
        if (!$post || $post->post_type !== 'scn_event') {
            return null;
        }

        $dates = get_post_meta($event_id, 'scn_event_dates', true) ?: ['start' => '', 'end' => ''];

        return [
            'id' => $post->ID,
            'official_name' => get_post_meta($event_id, 'scn_event_official_name', true) ?: $post->post_title,
            'location' => [
                'city' => get_post_meta($event_id, 'scn_event_location_city', true),
                'region' => get_post_meta($event_id, 'scn_event_location_region', true),
                'country' => get_post_meta($event_id, 'scn_event_location_country', true),
            ],
            'dates' => $dates,
            'website' => get_post_meta($event_id, 'scn_event_website', true),
            'year' => get_post_meta($event_id, 'scn_event_year', true),
        ];
    }

    public function handleSearchEvents() {
        check_ajax_referer('scn_events_nonce', 'nonce');
        
        if (!current_user_can('manage_scn_events')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $query = sanitize_text_field($_POST['query'] ?? '');
        $limit = intval($_POST['limit'] ?? 20);

        if (empty($query)) {
            wp_send_json_success([]);
        }

        $results = $this->search($query, $limit);
        $formatted_results = [];

        foreach ($results as $result) {
            $event_data = $this->getEventData($result->ID);
            if ($event_data) {
                $formatted_results[] = array_merge($event_data, [
                    'match_type' => $result->match_type,
                    'matched_text' => $result->matched_text,
                ]);
            }
        }

        wp_send_json_success($formatted_results);
    }

    private function removeDuplicates($results) {
        $seen = [];
        $unique = [];

        foreach ($results as $result) {
            $key = $result->ID;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $result;
            }
        }

        return $unique;
    }
}




