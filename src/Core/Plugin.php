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
        error_log("SCN Membership: Initializing modules");
        
        $this->modules = [
            'auth' => new \SCN\Membership\Modules\Auth\AuthModule(),
            'profiles' => new \SCN\Membership\Modules\Profiles\ProfilesModule(),
            'courses' => new \SCN\Membership\Modules\Courses\CoursesModule(),
            'directory' => new \SCN\Membership\Modules\Directory\DirectoryModule(),
            'resources' => new \SCN\Membership\Modules\Resources\ResourcesModule(),
            'widgets' => new \SCN\Membership\Modules\Widgets\WidgetsModule(),
        ];
        
        error_log("SCN Membership: Modules initialized, count: " . count($this->modules));
    }

    public function registerModules() {
        foreach ($this->modules as $name => $module) {
            if (method_exists($module, 'register')) {
                error_log("SCN Membership: Registering module: $name");
                $module->register();
            }
        }
    }

    public function getModule($name) {
        return $this->modules[$name] ?? null;
    }
}



