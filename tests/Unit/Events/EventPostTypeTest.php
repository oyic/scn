<?php

namespace SCN\Membership\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Events\EventPostType;

class EventPostTypeTest extends TestCase {
    private $event_post_type;

    protected function setUp(): void {
        parent::setUp();
        $this->event_post_type = new EventPostType();
    }

    public function testWebsiteNormalization() {
        $reflection = new \ReflectionClass($this->event_post_type);
        $method = $reflection->getMethod('normalizeWebsite');
        $method->setAccessible(true);

        // Test adding https to http URLs
        $this->assertEquals('https://example.com', $method->invoke($this->event_post_type, 'http://example.com'));
        
        // Test adding https to URLs without protocol
        $this->assertEquals('https://example.com', $method->invoke($this->event_post_type, 'example.com'));
        
        // Test keeping https URLs
        $this->assertEquals('https://example.com', $method->invoke($this->event_post_type, 'https://example.com'));
        
        // Test invalid URLs - these will be normalized but should fail validation
        $result = $method->invoke($this->event_post_type, 'not-a-url');
        $this->assertTrue(empty($result) || $result === 'https://not-a-url');
        $this->assertEquals('', $method->invoke($this->event_post_type, ''));
    }

    public function testDatesValidation() {
        $reflection = new \ReflectionClass($this->event_post_type);
        $method = $reflection->getMethod('sanitizeDates');
        $method->setAccessible(true);

        // Test valid dates
        $valid_dates = ['start' => '2024-01-01', 'end' => '2024-01-02'];
        $result = $method->invoke($this->event_post_type, $valid_dates);
        $this->assertEquals($valid_dates, $result);

        // Test empty dates
        $empty_dates = ['start' => '', 'end' => ''];
        $result = $method->invoke($this->event_post_type, $empty_dates);
        $this->assertEquals($empty_dates, $result);

        // Test invalid input
        $result = $method->invoke($this->event_post_type, 'not-an-array');
        $this->assertEquals(['start' => '', 'end' => ''], $result);
    }

    public function testLockedFieldsSanitization() {
        $reflection = new \ReflectionClass($this->event_post_type);
        $method = $reflection->getMethod('sanitizeLockedFields');
        $method->setAccessible(true);

        // Test valid locked fields
        $valid_fields = ['official_name', 'dates', 'website'];
        $result = $method->invoke($this->event_post_type, $valid_fields);
        $this->assertEquals($valid_fields, $result);

        // Test empty array
        $result = $method->invoke($this->event_post_type, []);
        $this->assertEquals([], $result);

        // Test invalid input
        $result = $method->invoke($this->event_post_type, 'not-an-array');
        $this->assertEquals([], $result);
    }
}
