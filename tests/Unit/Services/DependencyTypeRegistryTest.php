<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\RequestParameterNames;
use App\Services\DependencyTypeInterface;
use App\Services\DependencyTypeRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class DependencyTypeRegistryTest extends TestCase
{
    private DependencyTypeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->registry = new DependencyTypeRegistry();
    }

    public function testConstructorRegistersDefaultTypes(): void
    {
        $names = $this->registry->getNames();
        
        $this->assertContains(RequestParameterNames::ASSETS_VERSION, $names);
        $this->assertContains(RequestParameterNames::DEFINITIONS_VERSION, $names);
        $this->assertCount(2, $names);
    }

    public function testGetExistingType(): void
    {
        $assetsType = $this->registry->get(RequestParameterNames::ASSETS_VERSION);
        $definitionsType = $this->registry->get(RequestParameterNames::DEFINITIONS_VERSION);
        
        $this->assertNotNull($assetsType);
        $this->assertNotNull($definitionsType);
        $this->assertEquals(RequestParameterNames::ASSETS_VERSION, $assetsType->getName());
        $this->assertEquals(RequestParameterNames::DEFINITIONS_VERSION, $definitionsType->getName());
    }

    public function testGetNonExistentType(): void
    {
        $result = $this->registry->get('nonexistent');
        
        $this->assertNull($result);
    }

    public function testRegisterNewType(): void
    {
        $mockType = $this->createMock(DependencyTypeInterface::class);
        $mockType->method('getName')->willReturn('testType');
        
        $this->registry->register($mockType);
        
        $result = $this->registry->get('testType');
        $this->assertSame($mockType, $result);
        
        $names = $this->registry->getNames();
        $this->assertContains('testType', $names);
        $this->assertCount(3, $names); // 2 default + 1 new
    }

    public function testRegisterOverwriteExistingType(): void
    {
        $mockType = $this->createMock(DependencyTypeInterface::class);
        $mockType->method('getName')->willReturn(RequestParameterNames::ASSETS_VERSION);
        
        $originalType = $this->registry->get(RequestParameterNames::ASSETS_VERSION);
        $this->registry->register($mockType);
        $newType = $this->registry->get(RequestParameterNames::ASSETS_VERSION);
        
        $this->assertNotSame($originalType, $newType);
        $this->assertSame($mockType, $newType);
        

        $this->assertCount(2, $this->registry->getNames());
    }

    public function testGetAll(): void
    {
        $allTypes = $this->registry->getAll();
        
        $this->assertIsArray($allTypes);
        $this->assertCount(2, $allTypes);
        $this->assertArrayHasKey(RequestParameterNames::ASSETS_VERSION, $allTypes);
        $this->assertArrayHasKey(RequestParameterNames::DEFINITIONS_VERSION, $allTypes);
        
        foreach ($allTypes as $type) {
            $this->assertInstanceOf(DependencyTypeInterface::class, $type);
        }
    }

    public function testGetNames(): void
    {
        $names = $this->registry->getNames();
        
        $this->assertIsArray($names);
        $this->assertEquals([RequestParameterNames::ASSETS_VERSION, RequestParameterNames::DEFINITIONS_VERSION], $names);
    }
}
