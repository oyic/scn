<?php

namespace SCN\Membership\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Events\AliasesService;

class AliasesServiceTest extends TestCase {
    private $aliases_service;

    protected function setUp(): void {
        parent::setUp();
        $this->aliases_service = new AliasesService();
    }

    public function testAddAliasValidation() {
        // Test empty event ID
        $result = $this->aliases_service->addAlias(0, 'test-alias');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_data', $result->get_error_code());

        // Test empty alias
        $result = $this->aliases_service->addAlias(123, '');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_data', $result->get_error_code());
    }

    public function testRemoveAliasValidation() {
        // Test empty event ID
        $result = $this->aliases_service->removeAlias(0, 'test-alias');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_data', $result->get_error_code());

        // Test empty alias
        $result = $this->aliases_service->removeAlias(123, '');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_data', $result->get_error_code());
    }

    public function testGetAliasesWithValidId() {
        // Test with valid event ID
        $result = $this->aliases_service->getAliases(123);
        $this->assertIsArray($result);
    }

    public function testGetEventByAlias() {
        // Test with valid alias
        $result = $this->aliases_service->getEventByAlias('test-alias');
        $this->assertNull($result); // Should return null for non-existent alias
    }

    public function testSearchAliases() {
        // Test search with query
        $result = $this->aliases_service->searchAliases('test', 5);
        $this->assertIsArray($result);
    }
}




