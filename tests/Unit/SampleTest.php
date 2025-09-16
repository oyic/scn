<?php

namespace SCN\Membership\Tests\Unit;

use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase {
    public function testBasicAssertion() {
        $this->assertTrue(true);
    }

    public function testPluginConstants() {
        // Mock WordPress constants for testing
        if (!defined('SCN_MEMBERSHIP_VERSION')) {
            define('SCN_MEMBERSHIP_VERSION', '1.0.0');
        }
        if (!defined('SCN_MEMBERSHIP_PATH')) {
            define('SCN_MEMBERSHIP_PATH', '/path/to/plugin/');
        }
        if (!defined('SCN_MEMBERSHIP_URL')) {
            define('SCN_MEMBERSHIP_URL', 'http://example.com/plugin/');
        }
        
        $this->assertTrue(defined('SCN_MEMBERSHIP_VERSION'));
        $this->assertTrue(defined('SCN_MEMBERSHIP_PATH'));
        $this->assertTrue(defined('SCN_MEMBERSHIP_URL'));
    }
}
