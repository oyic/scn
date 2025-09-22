<?php

namespace SCN\Membership\Core;

class Plugin {
    private $modules = [];

    public function __construct() {
        $this->initializeModules();
    }

    public function register() {
        add_action('init', [$this, 'registerModules']);
    }

    private function initializeModules() {
        $this->modules = [
            'profiles' => new \SCN\Membership\Modules\Profiles\ProfilesModule(),
            'courses' => new \SCN\Membership\Modules\Courses\CoursesModule(),
            'events' => new \SCN\Membership\Modules\Events\EventsModule(),
            'directory' => new \SCN\Membership\Modules\Directory\DirectoryModule(),
            'resources' => new \SCN\Membership\Modules\Resources\ResourcesModule(),
            'widgets' => new \SCN\Membership\Modules\Widgets\WidgetsModule(),
        ];
    }

    public function registerModules() {
        foreach ($this->modules as $module) {
            if (method_exists($module, 'register')) {
                $module->register();
            }
        }
    }

    public function getModule($name) {
        return $this->modules[$name] ?? null;
    }
}



