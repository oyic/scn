<?php

namespace SCN\Membership\Infra;


class Database {
    private $migrations = [];

    public function register() {
        add_action('init', [$this, 'init']);
        add_action('admin_init', [$this, 'checkMigrations']);
        register_activation_hook(SCN_MEMBERSHIP_PATH . 'scn-membership.php', [$this, 'createTables']);
        register_deactivation_hook(SCN_MEMBERSHIP_PATH . 'scn-membership.php', [$this, 'dropTables']);
    }

    public function init() {
        $this->registerMigrations();
    }

    private function registerMigrations() {
        $this->migrations = [];
    }

    public function checkMigrations() {
        $current_version = get_option('membership_db_version', '0.0.0');
        
        foreach ($this->migrations as $migration) {
            if (version_compare($current_version, $migration->getVersion(), '<')) {
                $migration->up();
            }
        }
    }

    public function createTables() {
        foreach ($this->migrations as $migration) {
            $migration->up();
        }
    }

    public function dropTables() {
        foreach ($this->migrations as $migration) {
            $migration->down();
        }
    }
}



