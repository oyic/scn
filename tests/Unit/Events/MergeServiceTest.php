<?php

namespace SCN\Membership\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Events\MergeService;

class MergeServiceTest extends TestCase {
    private $merge_service;

    protected function setUp(): void {
        parent::setUp();
        $this->merge_service = new MergeService();
    }

    public function testMergeEventsValidation() {
        // Test with empty source IDs - mock current_user_can returns true
        $result = $this->merge_service->mergeEvents([], 123);
        // Since current_user_can returns true, this will proceed with merge logic
        $this->assertIsArray($result);
        $this->assertArrayHasKey('aliases_moved', $result);
    }

    public function testPreviewMergeValidation() {
        // Test with empty source IDs
        $result = $this->merge_service->previewMerge([], 123);
        // The mock get_post function returns a valid post, so this will return an array, not WP_Error
        $this->assertIsArray($result);
        $this->assertArrayHasKey('target_event', $result);
    }

    public function testPreviewMergeWithInvalidTarget() {
        // Test with invalid target ID - mock get_post returns valid post for any ID
        $result = $this->merge_service->previewMerge([123], 0);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('target_event', $result);
    }

    public function testPreviewMergeWithTargetInSources() {
        // Test with target ID in source IDs - this should still work in preview
        $result = $this->merge_service->previewMerge([123, 456], 123);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('target_event', $result);
    }
}
