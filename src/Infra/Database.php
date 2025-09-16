<?php

namespace SCN\Membership\Infra;

class Database {
    public function register() {
        add_action('init', [$this, 'init']);
    }

    public function init() {
        // TODO: Initialize database functionality
    }

    public function createTables() {
        // TODO: Create custom database tables
    }

    public function dropTables() {
        // TODO: Drop custom database tables
    }
}



