<?php

namespace SCN\Membership\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Events\LocksService;

class LocksServiceTest extends TestCase {
    private $locks_service;

    protected function setUp(): void {
        parent::setUp();
        $this->locks_service = new LocksService();
    }

    public function testLockFieldValidation() {
        // Test invalid field
        $result = $this->locks_service->lockField(123, 'invalid_field');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_field', $result->get_error_code());
    }

    public function testUnlockFieldValidation() {
        // Test invalid field
        $result = $this->locks_service->unlockField(123, 'invalid_field');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_field', $result->get_error_code());
    }

    public function testGetLockableFields() {
        $fields = $this->locks_service->getLockableFields();
        $this->assertIsArray($fields);
        $this->assertContains('official_name', $fields);
        $this->assertContains('dates', $fields);
        $this->assertContains('website', $fields);
    }

    public function testIsFieldLocked() {
        // Test with non-existent event
        $result = $this->locks_service->isFieldLocked(999999, 'official_name');
        $this->assertFalse($result);
    }

    public function testGetLocks() {
        // Test with valid event ID
        $result = $this->locks_service->getLocks(123);
        $this->assertIsArray($result);
    }

    public function testGetLockedFields() {
        // Test with valid event ID
        $result = $this->locks_service->getLockedFields(123);
        $this->assertIsArray($result);
    }
}






