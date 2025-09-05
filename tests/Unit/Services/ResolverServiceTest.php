<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\ConfigInterface;
use App\Services\CacheManager;
use App\Services\DependencyTypeRegistry;
use App\Services\FixturesService;
use App\Services\MtimeCacheService;
use App\Services\ResolverService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;


class ResolverServiceTest extends TestCase
{
    private ResolverService $resolverService;
    private FixturesService&MockObject $fixturesService;
    private ConfigInterface&MockObject $config;
    private CacheManager&MockObject $cacheManager;
    private DependencyTypeRegistry&MockObject $dependencyTypeRegistry;
    private MtimeCacheService&MockObject $mtimeCacheService;
    

    protected function setUp(): void
    {
        parent::setUp();
        
        
        $this->fixturesService = $this->createMock(FixturesService::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->config->method('getMtimeCacheTTLSettings')->willReturn(new \App\Config\MtimeTtlSettings(
            fixtures: 3600,
            urls: 60,
            general: 5,
        ));
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->dependencyTypeRegistry = $this->createMock(DependencyTypeRegistry::class);
        $this->mtimeCacheService = $this->createMock(MtimeCacheService::class);
        
        
        $logger = $this->createMock(\App\Contracts\LoggerInterface::class);
        $this->resolverService = new ResolverService(
            $this->fixturesService,
            $this->config,
            $this->cacheManager,
            $this->dependencyTypeRegistry,
            $this->mtimeCacheService,
            $logger
        );
    }
    

    public function testResolveAssetsFromCache(): void
    {
        
        $this->config->method('isDebugMode')->willReturn(false);
        
        
        $assetsType = $this->createMock(\App\Services\DependencyTypeInterface::class);
        $assetsType->method('isCompatible')->willReturn(true);
        $this->dependencyTypeRegistry->method('get')->with('assetsVersion')->willReturn($assetsType);
        
        $expectedAssets = ['version' => '1.0.0', 'hash' => 'hash123'];
        
        
        $this->cacheManager->method('get')
            ->willReturn($expectedAssets);
        
        
        $this->config->method('getFixturesPaths')->willReturn([
            'assetsVersion' => '/path/to/assets.json',
            'definitionsVersion' => '/path/to/definitions.json'
        ]);
        
        
        $this->mtimeCacheService->method('getMtime')->willReturn(1234567890);
        
        
        $this->fixturesService->expects($this->never())
            ->method('load');
        $this->fixturesService->expects($this->never())
            ->method('toMap');
        
        
        $result = $this->resolverService->resolveDependency('assetsVersion', '1.0.0', 'android', null);
        
        
        $this->assertSame($expectedAssets, $result);
    }
    

    public function testResolveAssetsWithoutCache(): void
    {
        
        $this->config->method('isDebugMode')->willReturn(false);
        
        
        $this->cacheManager->method('get')->willReturn(null);
        
        
        $assetsType = $this->createMock(\App\Services\DependencyTypeInterface::class);
        $assetsType->method('isCompatible')->willReturn(true);
        $this->dependencyTypeRegistry->method('get')->with('assetsVersion')->willReturn($assetsType);
        
        
        $this->config->method('getFixturesPaths')->willReturn([
            'assetsVersion' => '/path/to/assets.json',
            'definitionsVersion' => '/path/to/definitions.json'
        ]);
        
        
        $this->mtimeCacheService->method('getMtime')->willReturn(1234567890);
        
        $fixtures = [
            ['version' => '1.0.0', 'hash' => 'hash123'],
            ['version' => '1.1.0', 'hash' => 'hash456'],
        ];
        
        $map = [
            '1.0.0' => 'hash123',
            '1.1.0' => 'hash456',
        ];
        
        $this->fixturesService->method('load')
            ->with('assetsVersion', 'android')
            ->willReturn($fixtures);
        
        $this->fixturesService->method('toMap')
            ->with($fixtures)
            ->willReturn($map);
        
        
        $expectedAssets = ['version' => '1.1.0', 'hash' => 'hash456'];
        
        $this->cacheManager->expects($this->once())
            ->method('set');
        
        
        $result = $this->resolverService->resolveDependency('assetsVersion', '1.0.0', 'android', null);
        
        
        $this->assertSame($expectedAssets, $result);
    }
    

    public function testResolveDefinitionsFromCache(): void
    {
        
        $this->config->method('isDebugMode')->willReturn(false);
        
        
        $definitionsType = $this->createMock(\App\Services\DependencyTypeInterface::class);
        $definitionsType->method('isCompatible')->willReturn(true);
        $this->dependencyTypeRegistry->method('get')->with('definitionsVersion')->willReturn($definitionsType);
        
        $expectedDefinitions = ['version' => '1.0.0', 'hash' => 'hash123'];
        
        
        $this->cacheManager->method('get')
            ->willReturn($expectedDefinitions);
        
        
        $this->config->method('getFixturesPaths')->willReturn([
            'assetsVersion' => '/path/to/assets.json',
            'definitionsVersion' => '/path/to/definitions.json'
        ]);
        
        
        $this->mtimeCacheService->method('getMtime')->willReturn(1234567890);
        
        
        $this->fixturesService->expects($this->never())
            ->method('load');
        $this->fixturesService->expects($this->never())
            ->method('toMap');
        
        
        $result = $this->resolverService->resolveDependency('definitionsVersion', '1.0.0', 'android', null);
        
        
        $this->assertSame($expectedDefinitions, $result);
    }
    

    public function testResolveDefinitionsInDebugMode(): void
    {
        
        $this->config->method('isDebugMode')->willReturn(true);
        
        
        $definitionsType = $this->createMock(\App\Services\DependencyTypeInterface::class);
        $definitionsType->method('isCompatible')->willReturn(true);
        $this->dependencyTypeRegistry->method('get')->with('definitionsVersion')->willReturn($definitionsType);
        
        
        $this->config->method('getFixturesPaths')->willReturn([
            'assetsVersion' => '/path/to/assets.json',
            'definitionsVersion' => '/path/to/definitions.json'
        ]);
        
        
        $this->mtimeCacheService->method('getMtime')->willReturn(1234567890);
        
        $fixtures = [
            ['version' => '1.0.0', 'hash' => 'hash123'],
            ['version' => '1.0.1', 'hash' => 'hash456'],
        ];
        
        $map = [
            '1.0.0' => 'hash123',
            '1.0.1' => 'hash456',
        ];
        
        $this->fixturesService->method('load')
            ->with('definitionsVersion', 'android')
            ->willReturn($fixtures);
        
        $this->fixturesService->method('toMap')
            ->with($fixtures)
            ->willReturn($map);
        
        
        $this->cacheManager->expects($this->once())->method('get')->willReturn(null);
        $this->cacheManager->expects($this->once())->method('set');
        
        
        $result = $this->resolverService->resolveDependency('definitionsVersion', '1.0.0', 'android', null);
        
        
        $expectedDefinitions = ['version' => '1.0.1', 'hash' => 'hash456'];
        $this->assertSame($expectedDefinitions, $result);
    }
}
