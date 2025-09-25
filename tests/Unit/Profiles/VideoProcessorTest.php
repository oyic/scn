<?php

namespace SCN\Membership\Tests\Unit\Profiles;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Profiles\VideoProcessor;

class VideoProcessorTest extends TestCase {
    private $videoProcessor;

    protected function setUp(): void {
        $this->videoProcessor = new VideoProcessor();
    }

    public function testValidateVideoUrl() {
        // Test valid YouTube URL
        $youtube_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $result = $this->videoProcessor->validateVideoUrl($youtube_url);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['valid']);
        $this->assertEquals('youtube', $result['platform']);
        $this->assertEquals('dQw4w9WgXcQ', $result['video_id']);

        // Test valid Vimeo URL
        $vimeo_url = 'https://vimeo.com/123456789';
        $result = $this->videoProcessor->validateVideoUrl($vimeo_url);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['valid']);
        $this->assertEquals('vimeo', $result['platform']);
        $this->assertEquals('123456789', $result['video_id']);

        // Test invalid URL
        $invalid_url = 'https://example.com/video';
        $result = $this->videoProcessor->validateVideoUrl($invalid_url);
        
        $this->assertFalse($result);
    }

    public function testGetVideoEmbedCode() {
        $youtube_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $embed_code = $this->videoProcessor->getVideoEmbedCode($youtube_url, 560, 315);
        
        $this->assertStringContainsString('iframe', $embed_code);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $embed_code);
        $this->assertStringContainsString('width="560"', $embed_code);
        $this->assertStringContainsString('height="315"', $embed_code);
    }

    public function testGetVideoThumbnailUrl() {
        $youtube_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $thumbnail_url = $this->videoProcessor->getVideoThumbnailUrl($youtube_url);
        
        $this->assertStringContainsString('img.youtube.com/vi/dQw4w9WgXcQ', $thumbnail_url);
    }

    public function testExtractVideoId() {
        $reflection = new \ReflectionClass($this->videoProcessor);
        $method = $reflection->getMethod('extractVideoId');
        $method->setAccessible(true);

        // Test YouTube ID extraction
        $youtube_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $youtube_id = $method->invoke($this->videoProcessor, $youtube_url, 'youtube');
        $this->assertEquals('dQw4w9WgXcQ', $youtube_id);

        // Test Vimeo ID extraction
        $vimeo_url = 'https://vimeo.com/123456789';
        $vimeo_id = $method->invoke($this->videoProcessor, $vimeo_url, 'vimeo');
        $this->assertEquals('123456789', $vimeo_id);
    }

    public function testGetVideoPlatform() {
        $reflection = new \ReflectionClass($this->videoProcessor);
        $method = $reflection->getMethod('getVideoPlatform');
        $method->setAccessible(true);

        // Test YouTube platform detection
        $youtube_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $platform = $method->invoke($this->videoProcessor, $youtube_url);
        $this->assertEquals('youtube', $platform);

        // Test Vimeo platform detection
        $vimeo_url = 'https://vimeo.com/123456789';
        $platform = $method->invoke($this->videoProcessor, $vimeo_url);
        $this->assertEquals('vimeo', $platform);

        // Test invalid platform
        $invalid_url = 'https://example.com/video';
        $platform = $method->invoke($this->videoProcessor, $invalid_url);
        $this->assertFalse($platform);
    }
}


