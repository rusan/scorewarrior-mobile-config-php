<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\DependencyNames;
use App\Services\TTLConfigService;
use Tests\TestCase;

class TTLConfigServiceTest extends TestCase
{
    private TTLConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new TTLConfigService();
    }

    public function testGetTTLForCacheTypeFixtures(): void
    {
        $result = $this->service->getTTLForCacheType(DependencyNames::FIXTURES);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
        $this->assertEquals($this->service->getFixturesTTL(), $result);
    }

    public function testGetTTLForCacheTypeUrls(): void
    {
        $result = $this->service->getTTLForCacheType(DependencyNames::URLS);
        
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
        
        $originalValue = getenv('MTIME_CACHE_FIXTURES_TTL');
        putenv('MTIME_CACHE_FIXTURES_TTL=');
        
        $result = $this->service->getFixturesTTL();
        
        $this->assertIsInt($result);
        $this->assertEquals(3600, $result); // Default value
        
        
        if ($originalValue !== false) {
            putenv("MTIME_CACHE_FIXTURES_TTL={$originalValue}");
        }
    }

    public function testGetUrlsTTLDefaultValue(): void
    {
        
        $originalValue = getenv('MTIME_CACHE_URLS_TTL');
        putenv('MTIME_CACHE_URLS_TTL=');
        
        $result = $this->service->getUrlsTTL();
        
        $this->assertIsInt($result);
        $this->assertEquals(7200, $result); // Default value
        
        
        if ($originalValue !== false) {
            putenv("MTIME_CACHE_URLS_TTL={$originalValue}");
        }
    }

    public function testGetGeneralTTLDefaultValue(): void
    {
        
        $originalValue = getenv('MTIME_CACHE_GENERAL_TTL');
        putenv('MTIME_CACHE_GENERAL_TTL=');
        
        $result = $this->service->getGeneralTTL();
        
        $this->assertIsInt($result);
        $this->assertEquals(1800, $result); // Default value
        
        
        if ($originalValue !== false) {
            putenv("MTIME_CACHE_GENERAL_TTL={$originalValue}");
        }
    }

    public function testAllTTLsArePositive(): void
    {
        $this->assertGreaterThan(0, $this->service->getFixturesTTL());
        $this->assertGreaterThan(0, $this->service->getUrlsTTL());
        $this->assertGreaterThan(0, $this->service->getGeneralTTL());
    }
}
