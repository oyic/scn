<?php

namespace SCN\Membership\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use SCN\Membership\Modules\Events\EventsSearchService;

class EventsSearchServiceTest extends TestCase {
    private $search_service;

    protected function setUp(): void {
        parent::setUp();
        $this->search_service = new EventsSearchService();
    }

    public function testSearchWithEmptyQuery() {
        $result = $this->search_service->search('', 10);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSearchWithValidQuery() {
        $result = $this->search_service->search('test', 10);
        $this->assertIsArray($result);
    }

    public function testSearchExactAliases() {
        $result = $this->search_service->searchExactAliases('test', 10);
        $this->assertIsArray($result);
    }

    public function testSearchAliasesLike() {
        $result = $this->search_service->searchAliasesLike('test', 10);
        $this->assertIsArray($result);
    }

    public function testSearchTitlesLike() {
        $result = $this->search_service->searchTitlesLike('test', 10);
        $this->assertIsArray($result);
    }

    public function testGetEventByAlias() {
        $result = $this->search_service->getEventByAlias('non-existent-alias');
        $this->assertNull($result);
    }

    public function testGetEventData() {
        $result = $this->search_service->getEventData(999999);
        // The mock get_post function returns a valid post object, so this won't be null
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }
}
