<?php

namespace SCN\Membership\Tests\Unit\Profiles;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Profiles\ImageProcessor;

class ImageProcessorTest extends TestCase {
    private $imageProcessor;

    protected function setUp(): void {
        $this->imageProcessor = new ImageProcessor();
    }

    public function testGenerateSEOFilename() {
        $first_name = 'John';
        $last_name = 'Doe';
        $attachment_id = 123;

        // Mock the method to test filename generation
        $reflection = new \ReflectionClass($this->imageProcessor);
        $method = $reflection->getMethod('generateSEOFilename');
        $method->setAccessible(true);

        $filename = $method->invoke($this->imageProcessor, $first_name, $last_name, $attachment_id);

        $this->assertStringStartsWith('john-doe-photo', $filename);
        $this->assertStringEndsWith('.jpg', $filename);
    }

    public function testNormalizeForFilename() {
        $reflection = new \ReflectionClass($this->imageProcessor);
        $method = $reflection->getMethod('normalizeForFilename');
        $method->setAccessible(true);

        // Test basic normalization
        $result = $method->invoke($this->imageProcessor, 'John Doe');
        $this->assertEquals('john-doe', $result);

        // Test with special characters
        $result = $method->invoke($this->imageProcessor, 'José María');
        $this->assertEquals('jose-maria', $result);

        // Test with numbers
        $result = $method->invoke($this->imageProcessor, 'John2');
        $this->assertEquals('john2', $result);
    }

    public function testGeneratePressKitFilename() {
        $first_name = 'Jane';
        $last_name = 'Smith';
        $original_filename = 'press-kit.pdf';

        $filename = $this->imageProcessor->generatePressKitFilename($first_name, $last_name, $original_filename);

        $this->assertStringStartsWith('jane-smith-presskit', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    public function testValidateImageUpload() {
        // Test valid image
        $valid_file = [
            'type' => 'image/jpeg',
            'size' => 1024 * 1024, // 1MB
            'name' => 'test.jpg'
        ];

        $result = $this->imageProcessor->validateImageUpload($valid_file);
        $this->assertEquals($valid_file, $result);

        // Test invalid file type
        $invalid_type = [
            'type' => 'text/plain',
            'size' => 1024,
            'name' => 'test.txt',
            'error' => null
        ];

        $result = $this->imageProcessor->validateImageUpload($invalid_type);
        $this->assertArrayHasKey('error', $result);

        // Test file too large
        $too_large = [
            'type' => 'image/jpeg',
            'size' => 11 * 1024 * 1024, // 11MB
            'name' => 'large.jpg',
            'error' => null
        ];

        $result = $this->imageProcessor->validateImageUpload($too_large);
        $this->assertArrayHasKey('error', $result);
    }
}


