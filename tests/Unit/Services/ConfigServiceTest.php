<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\ConfigInterface;
use App\Services\CacheManager;
use App\Services\ConfigService;
use App\Services\FixturesService;
use App\Services\ResolverService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;


class ConfigServiceTest extends TestCase
{
    private ConfigService $configService;
    private ResolverService&MockObject $resolverService;
    private ConfigInterface&MockObject $config;
    private CacheManager&MockObject $cacheManager;
    
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolverService = $this->createMock(ResolverService::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->configService = new ConfigService(
            $this->resolverService,
            $this->config,
            $this->cacheManager
        );
    }
    

    public function testGetConfigFromCache(): void
    {
        $expectedConfig = [
            'backend_entry_point' => ['jsonrpc_url' => 'test.com/api'],
            'notifications' => ['jsonrpc_url' => 'test.com/notifications'],
            'assetsVersion' => [
                'version' => '1.0.0',
                'hash' => 'hash123',
                'urls' => ['cdn1.test.com', 'cdn2.test.com'],
            ],
            'definitionsVersion' => [
                'version' => '1.0.0',
                'hash' => 'hash456',
                'urls' => ['cdn3.test.com', 'cdn4.test.com'],
            ],
        ];
        

        

        $this->cacheManager->method('remember')
            ->willReturn($expectedConfig);
        

        $this->resolverService->expects($this->never())
            ->method('resolveDependency');
        
        
        $result = $this->configService->getConfig('1.0.0', 'android', null, null);
        
        
        $this->assertSame($expectedConfig, $result);
    }
    

    public function testGetConfigWithoutCache(): void
    {
        
        $this->config->method('getBackendJsonRpcUrl')->willReturn('test.com/api');
        $this->config->method('getNotificationsJsonRpcUrl')->willReturn('test.com/notifications');
        $this->config->method('getAssetsUrls')->willReturn(['cdn1.test.com', 'cdn2.test.com']);
        $this->config->method('getDefinitionsUrls')->willReturn(['cdn3.test.com', 'cdn4.test.com']);
        

        
        
        $this->resolverService->method('resolveDependency')
            ->willReturnMap([
                ['assetsVersion', '1.0.0', 'android', null, ['version' => '1.0.0', 'hash' => 'hash123']],
                ['definitionsVersion', '1.0.0', 'android', null, ['version' => '1.0.0', 'hash' => 'hash456']]
            ]);
        
        $expectedConfig = [
            'backend_entry_point' => ['jsonrpc_url' => 'test.com/api'],
            'notifications' => ['jsonrpc_url' => 'test.com/notifications'],
            'assetsVersion' => [
                'version' => '1.0.0',
                'hash' => 'hash123',
                'urls' => ['cdn1.test.com', 'cdn2.test.com'],
            ],
            'definitionsVersion' => [
                'version' => '1.0.0',
                'hash' => 'hash456',
                'urls' => ['cdn3.test.com', 'cdn4.test.com'],
            ],
        ];
        
        
        $this->cacheManager->method('remember')
            ->willReturnCallback(function($key, $callback) {
                return $callback();
            });
        
        
        $result = $this->configService->getConfig('1.0.0', 'android', null, null);
        
        
        $this->assertSame($expectedConfig, $result);
    }
    

    public function testGetConfigInDebugMode(): void
    {
        
        $this->config->method('getBackendJsonRpcUrl')->willReturn('test.com/api');
        $this->config->method('getNotificationsJsonRpcUrl')->willReturn('test.com/notifications');
        $this->config->method('getAssetsUrls')->willReturn(['cdn1.test.com', 'cdn2.test.com']);
        $this->config->method('getDefinitionsUrls')->willReturn(['cdn3.test.com', 'cdn4.test.com']);
        

        
        
        $this->resolverService->method('resolveDependency')
            ->willReturnMap([
                ['assetsVersion', '1.0.0', 'android', null, ['version' => '1.0.0', 'hash' => 'hash123']],
                ['definitionsVersion', '1.0.0', 'android', null, ['version' => '1.0.0', 'hash' => 'hash456']]
            ]);
        
        
        $this->cacheManager->method('remember')
            ->willReturnCallback(function($key, $callback) {
                return $callback();
            });
        
        
        $result = $this->configService->getConfig('1.0.0', 'android', null, null);
        
        
        $expectedConfig = [
            'backend_entry_point' => ['jsonrpc_url' => 'test.com/api'],
            'notifications' => ['jsonrpc_url' => 'test.com/notifications'],
            'assetsVersion' => [
                'version' => '1.0.0',
                'hash' => 'hash123',
                'urls' => ['cdn1.test.com', 'cdn2.test.com'],
            ],
            'definitionsVersion' => [
                'version' => '1.0.0',
                'hash' => 'hash456',
                'urls' => ['cdn3.test.com', 'cdn4.test.com'],
            ],
        ];
        
        $this->assertSame($expectedConfig, $result);
    }
}
