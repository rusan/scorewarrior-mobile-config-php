<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CacheManager;
use App\Services\FileCacheService;
use App\Services\MtimeCacheService;
use App\Services\TTLConfigService;
use App\Utils\Log;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class FileCacheServiceTest extends TestCase
{
    private FileCacheService $fileCacheService;
    private TTLConfigService&MockObject $ttlConfig;
    private CacheManager&MockObject $cacheManager;
    private MtimeCacheService&MockObject $mtimeCacheService;
    private string $tempFilePath;

    protected function setUp(): void
    {
        $this->tempFilePath = sys_get_temp_dir() . '/test_file_cache_' . uniqid() . '.json';
        file_put_contents($this->tempFilePath, json_encode([
            'test_key' => 'test_value',
            'nested' => ['key' => 'value']
        ]));
        $this->ttlConfig = $this->createMock(TTLConfigService::class);
        $this->ttlConfig->method('getTTLForCacheType')->willReturnMap([
            ['fixtures', 3600],
            ['urls', 7200],
            ['unknown', 1800]
        ]);

        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->mtimeCacheService = $this->createMock(MtimeCacheService::class);
        $this->mtimeCacheService->method('getMtime')->willReturn(1234567890);
        $logger = $this->createMock(\App\Contracts\LoggerInterface::class);
        $this->fileCacheService = new FileCacheService(
            $this->ttlConfig,
            $this->cacheManager,
            $this->mtimeCacheService,
            $logger
        );
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }


    public function testLoadFromFile(): void
    {
        $this->cacheManager->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->cacheManager->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('fixtures_test_file_cache_'),
                $this->isType('array'),
                'fixtures',
                3600
            );

        $result = $this->fileCacheService->loadJsonFile('fixtures', $this->tempFilePath);

        $this->assertIsArray($result);
        $this->assertEquals('test_value', $result['test_key']);
        $this->assertEquals(['key' => 'value'], $result['nested']);
    }


    public function testLoadFromCache(): void
    {
        $cachedData = ['cached' => 'data'];
        
        $this->cacheManager->expects($this->once())
            ->method('get')
            ->willReturn($cachedData);

        $this->cacheManager->expects($this->never())
            ->method('set');

        $result = $this->fileCacheService->loadJsonFile('fixtures', $this->tempFilePath);

        $this->assertEquals($cachedData, $result);
    }

    public function testLocalCacheRemoved(): void
    {
        $data = ['from' => 'cache'];
        // First call: miss external cache, read file, set cache
        $this->cacheManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(null, $data);

        $this->cacheManager->expects($this->once())
            ->method('set');

        $this->fileCacheService->loadJsonFile('fixtures', $this->tempFilePath);
        // Second call: should hit external cache (since local cache removed)
        $result2 = $this->fileCacheService->loadJsonFile('fixtures', $this->tempFilePath);
        $this->assertEquals($data, $result2);
    }


    public function testNonExistentFile(): void
    {
        $nonExistentPath = '/non/existent/file.json';
        
        $this->mtimeCacheService->expects($this->once())
            ->method('getMtime')
            ->with($nonExistentPath)
            ->willReturn(0);

        $this->cacheManager->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->cacheManager->expects($this->never())
            ->method('set');

        $result = $this->fileCacheService->loadJsonFile('fixtures', $nonExistentPath);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }


    public function testInvalidJson(): void
    {
        $invalidJsonPath = sys_get_temp_dir() . '/invalid_json_' . uniqid() . '.json';
        file_put_contents($invalidJsonPath, 'invalid json content');

        $this->mtimeCacheService->expects($this->once())
            ->method('getMtime')
            ->with($invalidJsonPath)
            ->willReturn(1234567890);

        $this->cacheManager->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->cacheManager->expects($this->never())
            ->method('set');

        $result = $this->fileCacheService->loadJsonFile('fixtures', $invalidJsonPath);

        $this->assertIsArray($result);
        $this->assertEmpty($result);

        unlink($invalidJsonPath);
    }


    public function testDifferentCacheTypes(): void
    {
        $this->cacheManager->expects($this->exactly(2))
            ->method('get')
            ->willReturn(null);

        $this->cacheManager->expects($this->exactly(2))
            ->method('set')
            ->with(
                $this->stringContains('_'),
                $this->isType('array'),
                $this->logicalOr('fixtures', 'urls'),
                $this->logicalOr(3600, 7200)
            );

        $this->fileCacheService->loadJsonFile('fixtures', $this->tempFilePath);
        $this->fileCacheService->loadJsonFile('urls', $this->tempFilePath);
    }


    public function testFallbackTTL(): void
    {
        $this->cacheManager->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->cacheManager->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('unknown_'),
                $this->isType('array'),
                'unknown',
                1800 // general TTL
            );

        $this->fileCacheService->loadJsonFile('unknown', $this->tempFilePath);
    }
}
