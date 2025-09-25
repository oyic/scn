<?php

namespace SCN\Membership\Infra\Migrations;

class CreateEventsTables {
    private $version = '1.0.0';

    public function up() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $aliases_table = $wpdb->prefix . 'scn_event_aliases';
        $locks_table = $wpdb->prefix . 'scn_event_locks';
        $sessions_table = $wpdb->prefix . 'scn_event_sessions';

        $aliases_sql = "CREATE TABLE $aliases_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            alias varchar(191) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_event_alias (event_id, alias),
            KEY event_id (event_id),
            KEY alias (alias)
        ) $charset_collate;";

        $locks_sql = "CREATE TABLE $locks_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            field varchar(64) NOT NULL,
            locked_by bigint(20) NOT NULL,
            locked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_event_field (event_id, field),
            KEY event_id (event_id),
            KEY locked_by (locked_by)
        ) $charset_collate;";

        $sessions_sql = "CREATE TABLE $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            profile_id bigint(20) NOT NULL,
            session_title varchar(191) NOT NULL,
            session_datetime datetime NOT NULL,
            room varchar(64) DEFAULT NULL,
            type_override varchar(64) DEFAULT NULL,
            status enum('pending', 'approved') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY course_id (course_id),
            KEY profile_id (profile_id),
            KEY session_datetime (session_datetime),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($aliases_sql);
        dbDelta($locks_sql);
        dbDelta($sessions_sql);

        update_option('scn_events_db_version', $this->version);
    }

    public function down() {
        global $wpdb;

        $aliases_table = $wpdb->prefix . 'scn_event_aliases';
        $locks_table = $wpdb->prefix . 'scn_event_locks';
        $sessions_table = $wpdb->prefix . 'scn_event_sessions';

        $wpdb->query("DROP TABLE IF EXISTS $sessions_table");
        $wpdb->query("DROP TABLE IF EXISTS $locks_table");
        $wpdb->query("DROP TABLE IF EXISTS $aliases_table");

        delete_option('scn_events_db_version');
    }

    public function getVersion() {
        return $this->version;
    }
}




