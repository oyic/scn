<?php

namespace SCN\Membership\Cli;

use WP_CLI;
use WP_CLI_Command;
use SCN\Membership\Modules\Events\AliasesService;
use SCN\Membership\Modules\Events\LocksService;

class EventsCommand extends WP_CLI_Command {
    private $aliases_service;
    private $locks_service;

    public function __construct() {
        $this->aliases_service = new AliasesService();
        $this->locks_service = new LocksService();
    }

    /**
     * Create a new event
     *
     * ## OPTIONS
     *
     * --name=<name>
     * : Event name (required)
     *
     * --city=<city>
     * : Event city
     *
     * --region=<region>
     * : Event region/state
     *
     * --start=<start>
     * : Start date (YYYY-MM-DD format)
     *
     * --end=<end>
     * : End date (YYYY-MM-DD format, optional)
     *
     * --website=<website>
     * : Event website URL
     *
     * ## EXAMPLES
     *
     *     wp scn events create --name="DemoConf" --city="Austin" --region="TX" --start="2026-01-10" --end="2026-01-12" --website="example.com"
     *
     * @when after_wp_load
     */
    public function create($args, $assoc_args) {
        $name = $assoc_args['name'] ?? '';
        $city = $assoc_args['city'] ?? '';
        $region = $assoc_args['region'] ?? '';
        $start = $assoc_args['start'] ?? '';
        $end = $assoc_args['end'] ?? '';
        $website = $assoc_args['website'] ?? '';

        if (empty($name)) {
            WP_CLI::error('Event name is required. Use --name="Event Name"');
        }

        if (empty($start)) {
            WP_CLI::error('Start date is required. Use --start="YYYY-MM-DD"');
        }

        // Validate start date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            WP_CLI::error('Start date must be in YYYY-MM-DD format');
        }

        // Validate end date if provided
        if (!empty($end) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            WP_CLI::error('End date must be in YYYY-MM-DD format');
        }

        if (!empty($end) && $end < $start) {
            WP_CLI::error('End date must be on or after start date');
        }

        // Create the event post
        $post_data = [
            'post_title' => $name,
            'post_type' => 'scn_event',
            'post_status' => 'publish',
        ];

        $event_id = wp_insert_post($post_data);

        if (is_wp_error($event_id)) {
            WP_CLI::error('Failed to create event: ' . $event_id->get_error_message());
        }

        // Set meta fields
        update_post_meta($event_id, 'scn_event_official_name', $name);
        
        if (!empty($city)) {
            update_post_meta($event_id, 'scn_event_location_city', $city);
        }
        
        if (!empty($region)) {
            update_post_meta($event_id, 'scn_event_location_region', $region);
        }

        $dates = ['start' => $start, 'end' => $end];
        update_post_meta($event_id, 'scn_event_dates', $dates);

        $year = date('Y', strtotime($start));
        update_post_meta($event_id, 'scn_event_year', $year);

        if (!empty($website)) {
            // Normalize website URL
            if (!preg_match('/^https?:\/\//', $website)) {
                $website = 'https://' . $website;
            } elseif (preg_match('/^http:\/\//', $website)) {
                $website = str_replace('http://', 'https://', $website);
            }
            update_post_meta($event_id, 'scn_event_website', $website);
        }

        WP_CLI::success("Event created successfully with ID: {$event_id}");
    }

    /**
     * Add an alias to an event
     *
     * ## OPTIONS
     *
     * <event_id>
     * : Event ID
     *
     * <alias>
     * : Alias to add
     *
     * ## EXAMPLES
     *
     *     wp scn events alias add 123 "DemoConf" "DC" "Demo Conference"
     *
     * @when after_wp_load
     */
    public function alias($args, $assoc_args) {
        if (empty($args[0]) || empty($args[1])) {
            WP_CLI::error('Usage: wp scn events alias add <EVENT_ID> <ALIAS>');
        }

        $event_id = intval($args[0]);
        $alias = $args[1];

        $result = $this->aliases_service->addAlias($event_id, $alias);

        if (is_wp_error($result)) {
            WP_CLI::error('Failed to add alias: ' . $result->get_error_message());
        }

        WP_CLI::success("Alias '{$alias}' added to event {$event_id}");
    }

    /**
     * Lock event fields
     *
     * ## OPTIONS
     *
     * <event_id>
     * : Event ID
     *
     * <fields>
     * : Comma-separated list of fields to lock (name, dates, website)
     *
     * ## EXAMPLES
     *
     *     wp scn events lock 123 name,dates,website
     *
     * @when after_wp_load
     */
    public function lock($args, $assoc_args) {
        if (empty($args[0]) || empty($args[1])) {
            WP_CLI::error('Usage: wp scn events lock <EVENT_ID> <FIELDS>');
        }

        $event_id = intval($args[0]);
        $fields = explode(',', $args[1]);

        $lockable_fields = $this->locks_service->getLockableFields();
        $locked_count = 0;

        foreach ($fields as $field) {
            $field = trim($field);
            
            if (!in_array($field, $lockable_fields)) {
                WP_CLI::warning("Field '{$field}' is not lockable. Skipping.");
                continue;
            }

            $result = $this->locks_service->lockField($event_id, $field);

            if (is_wp_error($result)) {
                WP_CLI::warning("Failed to lock field '{$field}': " . $result->get_error_message());
            } else {
                $locked_count++;
                WP_CLI::log("Locked field: {$field}");
            }
        }

        WP_CLI::success("Locked {$locked_count} field(s) for event {$event_id}");
    }
}




