<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\CacheTypes;
use App\Services\TTLConfigService;
use Tests\TestCase;

class TTLConfigServiceTest extends TestCase
{
    private TTLConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = $this->createMock(\App\Config\ConfigInterface::class);
        $config->method('getMtimeCacheFixturesTtl')->willReturn(3600);
        $config->method('getMtimeCacheUrlsTtl')->willReturn(60);
        $config->method('getMtimeCacheGeneralTtl')->willReturn(5);
        
        $this->service = new TTLConfigService($config);
    }

    public function testGetTTLForCacheTypeFixtures(): void
    {
        $result = $this->service->getTTLForCacheType(CacheTypes::FIXTURES);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
        $this->assertEquals($this->service->getFixturesTTL(), $result);
    }

    public function testGetTTLForCacheTypeUrls(): void
    {
        $result = $this->service->getTTLForCacheType(CacheTypes::URLS);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
        $this->assertEquals($this->service->getUrlsTTL(), $result);
    }

    public function testGetTTLForCacheTypeUnknown(): void
    {
        $result = $this->service->getTTLForCacheType('unknown');
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
        $this->assertEquals($this->service->getGeneralTTL(), $result);
    }

    public function testGetFixturesTTLDefaultValue(): void
    {
        $result = $this->service->getFixturesTTL();
        
        $this->assertIsInt($result);
        $this->assertEquals(3600, $result); // Mocked value
    }

    public function testGetUrlsTTLDefaultValue(): void
    {
        $result = $this->service->getUrlsTTL();
        
        $this->assertIsInt($result);
        $this->assertEquals(60, $result); // Mocked value
    }

    public function testGetGeneralTTLDefaultValue(): void
    {
        $result = $this->service->getGeneralTTL();
        
        $this->assertIsInt($result);
        $this->assertEquals(5, $result); // Mocked value
    }

    public function testAllTTLsArePositive(): void
    {
        $this->assertGreaterThan(0, $this->service->getFixturesTTL());
        $this->assertGreaterThan(0, $this->service->getUrlsTTL());
        $this->assertGreaterThan(0, $this->service->getGeneralTTL());
    }
}
