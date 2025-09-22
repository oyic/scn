<?php

namespace SCN\Membership\Tests\Integration\Events;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Events\EventsModule;
use SCN\Membership\Modules\Events\AliasesService;
use SCN\Membership\Modules\Events\LocksService;
use SCN\Membership\Modules\Events\MergeService;
use SCN\Membership\Modules\Events\EventsSearchService;

class EventsIntegrationTest extends TestCase {
    private $events_module;
    private $aliases_service;
    private $locks_service;
    private $merge_service;
    private $search_service;

    protected function setUp(): void {
        parent::setUp();
        $this->events_module = new EventsModule();
        $this->aliases_service = new AliasesService();
        $this->locks_service = new LocksService();
        $this->merge_service = new MergeService();
        $this->search_service = new EventsSearchService();
    }

    public function testEventsModuleRegistration() {
        $this->assertInstanceOf(EventsModule::class, $this->events_module);
    }

    public function testServicesInstantiation() {
        $this->assertInstanceOf(AliasesService::class, $this->aliases_service);
        $this->assertInstanceOf(LocksService::class, $this->locks_service);
        $this->assertInstanceOf(MergeService::class, $this->merge_service);
        $this->assertInstanceOf(EventsSearchService::class, $this->search_service);
    }

    public function testAliasesServiceMethods() {
        // Test that all required methods exist
        $this->assertTrue(method_exists($this->aliases_service, 'addAlias'));
        $this->assertTrue(method_exists($this->aliases_service, 'removeAlias'));
        $this->assertTrue(method_exists($this->aliases_service, 'getAliases'));
        $this->assertTrue(method_exists($this->aliases_service, 'getEventByAlias'));
        $this->assertTrue(method_exists($this->aliases_service, 'searchAliases'));
    }

    public function testLocksServiceMethods() {
        // Test that all required methods exist
        $this->assertTrue(method_exists($this->locks_service, 'lockField'));
        $this->assertTrue(method_exists($this->locks_service, 'unlockField'));
        $this->assertTrue(method_exists($this->locks_service, 'getLocks'));
        $this->assertTrue(method_exists($this->locks_service, 'isFieldLocked'));
        $this->assertTrue(method_exists($this->locks_service, 'getLockedFields'));
    }

    public function testMergeServiceMethods() {
        // Test that all required methods exist
        $this->assertTrue(method_exists($this->merge_service, 'mergeEvents'));
        $this->assertTrue(method_exists($this->merge_service, 'previewMerge'));
    }

    public function testSearchServiceMethods() {
        // Test that all required methods exist
        $this->assertTrue(method_exists($this->search_service, 'search'));
        $this->assertTrue(method_exists($this->search_service, 'searchExactAliases'));
        $this->assertTrue(method_exists($this->search_service, 'searchAliasesLike'));
        $this->assertTrue(method_exists($this->search_service, 'searchTitlesLike'));
        $this->assertTrue(method_exists($this->search_service, 'getEventByAlias'));
        $this->assertTrue(method_exists($this->search_service, 'getEventData'));
    }

    public function testLocksServiceLockableFields() {
        $fields = $this->locks_service->getLockableFields();
        $this->assertIsArray($fields);
        $this->assertContains('official_name', $fields);
        $this->assertContains('dates', $fields);
        $this->assertContains('website', $fields);
    }

    public function testSearchServiceWithEmptyQuery() {
        $results = $this->search_service->search('', 10);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testAliasesServiceValidation() {
        // Test addAlias with invalid data
        $result = $this->aliases_service->addAlias(0, '');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_data', $result->get_error_code());

        // Test removeAlias with invalid data
        $result = $this->aliases_service->removeAlias(0, '');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_data', $result->get_error_code());
    }

    public function testLocksServiceValidation() {
        // Test lockField with invalid field
        $result = $this->locks_service->lockField(123, 'invalid_field');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_field', $result->get_error_code());

        // Test unlockField with invalid field
        $result = $this->locks_service->unlockField(123, 'invalid_field');
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_field', $result->get_error_code());
    }
}






