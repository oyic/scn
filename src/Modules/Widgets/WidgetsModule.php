<?php

namespace SCN\Membership\Modules\Widgets;

class WidgetsModule {
    public function register() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }

    public function init() {
        // TODO: Initialize widgets module
    }

    public function enqueueScripts() {
        // TODO: Enqueue frontend scripts and styles
    }

    public function enqueueAdminScripts() {
        // TODO: Enqueue admin scripts and styles
    }
}



