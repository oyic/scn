<?php

namespace SCN\Membership\Tests\Unit\Courses;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Courses\CoursePostType;

class CoursePostTypeTest extends TestCase {
    private $course_post_type;

    protected function setUp(): void {
        $this->course_post_type = new CoursePostType();
    }

    public function testSanitizeCeHours() {
        $this->assertEquals(0, $this->course_post_type->sanitizeCeHours('invalid'));
        $this->assertEquals(0, $this->course_post_type->sanitizeCeHours(-1));
        $this->assertEquals(0, $this->course_post_type->sanitizeCeHours(0.3));
        $this->assertEquals(0.5, $this->course_post_type->sanitizeCeHours(0.5));
        $this->assertEquals(1.0, $this->course_post_type->sanitizeCeHours(1.0));
        $this->assertEquals(2.5, $this->course_post_type->sanitizeCeHours(2.5));
    }

    public function testSanitizeFormats() {
        $allowed_formats = ['keynote', 'lecture', 'workshop', 'panel'];
        
        // Mock the filter
        add_filter('scn/course/allowed_formats', function() use ($allowed_formats) {
            return array_combine($allowed_formats, $allowed_formats);
        });

        $this->assertEquals(['keynote', 'lecture'], $this->course_post_type->sanitizeFormats(['keynote', 'lecture']));
        $this->assertEquals(['workshop'], $this->course_post_type->sanitizeFormats(['workshop', 'invalid']));
        $this->assertEquals([], $this->course_post_type->sanitizeFormats(['invalid', 'also_invalid']));
        $this->assertEquals([], $this->course_post_type->sanitizeFormats('not_array'));
    }

    public function testSanitizeOutcomes() {
        $outcomes = ['Learn something', '', '  ', 'Learn something else'];
        $result = $this->course_post_type->sanitizeOutcomes($outcomes);
        
        $this->assertCount(2, $result);
        $this->assertContains('Learn something', $result);
        $this->assertContains('Learn something else', $result);
        $this->assertNotContains('', $result);
        $this->assertNotContains('  ', $result);
    }

    public function testSanitizeOutcomesWithNonArray() {
        $this->assertEquals([], $this->course_post_type->sanitizeOutcomes('not_array'));
        $this->assertEquals([], $this->course_post_type->sanitizeOutcomes(null));
    }

    public function testSanitizeOnDemand() {
        $ondemand_data = [
            'title' => 'Test Course',
            'school' => 'Test University',
            'link' => 'http://example.com/course'
        ];

        // Mock the filter to normalize HTTP to HTTPS
        add_filter('scn/course/ondemand_link', function($link) {
            return str_replace('http://', 'https://', $link);
        });

        $result = $this->course_post_type->sanitizeOnDemand($ondemand_data);
        
        $this->assertEquals('Test Course', $result['title']);
        $this->assertEquals('Test University', $result['school']);
        $this->assertEquals('https://example.com/course', $result['link']);
    }

    public function testSanitizeOnDemandWithEmptyData() {
        $result = $this->course_post_type->sanitizeOnDemand([]);
        
        $this->assertEquals('', $result['title']);
        $this->assertEquals('', $result['school']);
        $this->assertEquals('', $result['link']);
    }

    public function testSanitizeOnDemandWithNonArray() {
        $result = $this->course_post_type->sanitizeOnDemand('not_array');
        
        $this->assertEquals('', $result['title']);
        $this->assertEquals('', $result['school']);
        $this->assertEquals('', $result['link']);
    }
}





