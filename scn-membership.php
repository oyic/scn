<?php
/**
 * Plugin Name: SCN Membership
 * Plugin URI: https://scn.org
 * Description: A comprehensive membership management system for SCN.
 * Version: 1.0.0
 * Author: SCN Development Team
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: scn-membership
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 6.5
 * Requires PHP: 8.1
 * Network: false
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SCN_MEMBERSHIP_VERSION', '1.0.0');
define('SCN_MEMBERSHIP_PATH', plugin_dir_path(__FILE__));
define('SCN_MEMBERSHIP_URL', plugin_dir_url(__FILE__));

if (file_exists(SCN_MEMBERSHIP_PATH . 'vendor/autoload.php')) {
    require_once SCN_MEMBERSHIP_PATH . 'vendor/autoload.php';
}

class SCN_Membership_Bootstrap {
    private static $instance = null;
    private $plugin = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        if (class_exists('SCN\\Membership\\Core\\Plugin')) {
            $this->plugin = new SCN\Membership\Core\Plugin();
            $this->plugin->register();
        }
    }

    public function activate() {
        // TODO: Implement activation logic
    }

    public function deactivate() {
        // TODO: Implement deactivation logic
    }
}

SCN_Membership_Bootstrap::getInstance();



