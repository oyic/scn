<?php

namespace SCN\Membership\Tests\Unit\Courses;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Courses\CoursesModule;

class CoursesModuleTest extends TestCase {
    private $courses_module;

    protected function setUp(): void {
        $this->courses_module = new CoursesModule();
    }

    public function testGetAllowedFormats() {
        $formats = $this->courses_module->getAllowedFormats([]);
        
        $this->assertIsArray($formats);
        $this->assertArrayHasKey('keynote', $formats);
        $this->assertArrayHasKey('lecture', $formats);
        $this->assertArrayHasKey('workshop', $formats);
        $this->assertArrayHasKey('panel', $formats);
        $this->assertEquals('Keynote', $formats['keynote']);
    }

    public function testNormalizeOnDemandLink() {
        // Test HTTP to HTTPS conversion
        $this->assertEquals('https://example.com', $this->courses_module->normalizeOnDemandLink('http://example.com'));
        $this->assertEquals('https://example.com', $this->courses_module->normalizeOnDemandLink('https://example.com'));
        
        // Test protocol addition
        $this->assertEquals('https://example.com', $this->courses_module->normalizeOnDemandLink('example.com'));
        $this->assertEquals('https://example.com', $this->courses_module->normalizeOnDemandLink('//example.com'));
        
        // Test empty link
        $this->assertEquals('', $this->courses_module->normalizeOnDemandLink(''));
        $this->assertEquals('', $this->courses_module->normalizeOnDemandLink(null));
        
        // Test invalid URL
        $this->assertEquals('', $this->courses_module->normalizeOnDemandLink('not-a-url'));
    }

    public function testGetPlaceholderImageId() {
        // Test default value
        $placeholder_id = $this->courses_module->getPlaceholderImageId();
        $this->assertIsInt($placeholder_id);
        $this->assertEquals(0, $placeholder_id);
    }

    public function testOnCourseCreated() {
        // Test that the method exists and can be called
        $post_id = 123;
        
        // This should not throw an exception
        $this->courses_module->onCourseCreated($post_id);
        
        $this->assertTrue(true); // If we get here, the method worked
    }

    public function testOnCourseUpdated() {
        // Test that the method exists and can be called
        $post_id = 456;
        
        // This should not throw an exception
        $this->courses_module->onCourseUpdated($post_id);
        
        $this->assertTrue(true); // If we get here, the method worked
    }
}
