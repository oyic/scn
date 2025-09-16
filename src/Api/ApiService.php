<?php

namespace SCN\Membership\Api;

class ApiService {
    public function register() {
        add_action('rest_api_init', [$this, 'registerRoutes']);
        add_action('init', [$this, 'init']);
    }

    public function init() {
        // TODO: Initialize API functionality
    }

    public function registerRoutes() {
        // TODO: Register REST API routes
    }
}



