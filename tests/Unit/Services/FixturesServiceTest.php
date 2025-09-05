<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\ConfigInterface;
use App\Services\CacheManager;
use App\Services\FixturesService;
use App\Services\MtimeCacheService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FixturesServiceTest extends TestCase
{
    private FixturesService $fixturesService;
    private ConfigInterface&MockObject $config;
    private CacheManager&MockObject $cacheManager;
    private MtimeCacheService $mtimeCache;
    private \App\Services\FileCacheService $fileCacheService;
    private string $tempFilePath;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFilePath = sys_get_temp_dir() . '/fixtures_test_' . uniqid() . '.json';
        file_put_contents($this->tempFilePath, json_encode([
            'android' => [
                ['version' => '1.0.0', 'hash' => 'hash123'],
                ['version' => '1.1.0', 'hash' => 'hash456'],
            ],
            'ios' => [
                ['version' => '1.0.0', 'hash' => 'hash789'],
                ['version' => '1.1.0', 'hash' => 'hash012'],
            ],
        ]));
        $this->config = $this->createMock(ConfigInterface::class);
        $this->config->method('getMtimeCacheTTLSettings')->willReturn(['fixtures' => 0, 'urls' => 60, 'general' => 5]);
        $this->config->method('getMtimeCachePathMap')->willReturn([
            $this->tempFilePath => 3600
        ]);
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->cacheManager->method('get')
            ->willReturnCallback(function($key, $type) {
                if ($type === 'mtime') {
                    return null;
                }
                return null;
            });
        $this->mtimeCache = new MtimeCacheService($this->config, $this->cacheManager);
        $configForTtl = $this->createMock(\App\Config\ConfigInterface::class);
        $configForTtl->method('getMtimeCacheTTLSettings')->willReturn([
            'fixtures' => 3600,
            'urls' => 60,
            'general' => 5,
        ]);
        $configForTtl->method('getMtimeCacheGeneralTtl')->willReturn(5);
        $logger = $this->createMock(\App\Contracts\LoggerInterface::class);
        $this->fileCacheService = new \App\Services\FileCacheService($configForTtl, $this->cacheManager, $this->mtimeCache, $logger);
        $fixturesLogger = $this->createMock(\App\Contracts\LoggerInterface::class);
        $this->fixturesService = new FixturesService(
            ['test' => $this->tempFilePath],
            $this->fileCacheService,
            $fixturesLogger
        );
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }
    
    public function testLoadFromFile(): void
    {
        $this->config->method('isDebugMode')->willReturn(true);
        $result = $this->fixturesService->load('test', 'android');
        $expected = [
            ['version' => '1.0.0', 'hash' => 'hash123'],
            ['version' => '1.1.0', 'hash' => 'hash456'],
        ];
        
        $this->assertSame($expected, $result);
    }
    
    public function testLoadFromCache(): void
    {
        $this->config->method('isDebugMode')->willReturn(false);
        
        $expected = [
            ['version' => '1.0.0', 'hash' => 'hash123'],
            ['version' => '1.1.0', 'hash' => 'hash456'],
        ];
        $this->cacheManager->method('get')
            ->willReturn($expected);
        $result = $this->fixturesService->load('test', 'android');
        $this->assertSame($expected, $result);
    }
    

    public function testToMap(): void
    {
        $fixtures = [
            ['version' => '1.0.0', 'hash' => 'hash123'],
            ['version' => '1.1.0', 'hash' => 'hash456'],
        ];
        
        $expected = [
            '1.0.0' => 'hash123',
            '1.1.0' => 'hash456',
        ];
        
        $result = $this->fixturesService->toMap($fixtures);
        
        $this->assertSame($expected, $result);
    }
    

    private function getMTime(): int
    {
        return (int) filemtime($this->tempFilePath);
    }
}
