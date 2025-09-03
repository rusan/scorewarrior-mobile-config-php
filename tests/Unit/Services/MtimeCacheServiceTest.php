<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\ConfigInterface;
use App\Services\CacheManager;
use App\Services\MtimeCacheService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class MtimeCacheServiceTest extends TestCase
{
    private MtimeCacheService $service;
    private ConfigInterface&MockObject $config;
    private CacheManager&MockObject $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = $this->createMock(ConfigInterface::class);
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->service = new MtimeCacheService($this->config, $this->cacheManager);
    }

    public function testGetMtimeWithoutCaching(): void
    {
        $filePath = '/test/file.json';
        
        
        $this->config->expects($this->once())
            ->method('getMtimeCachePathMap')
            ->willReturn([$filePath => 0]);

        
        $this->cacheManager->expects($this->never())
            ->method('get');
        $this->cacheManager->expects($this->never())
            ->method('set');

        
        $tempFile = tempnam(sys_get_temp_dir(), 'mtime_test_');
        file_put_contents($tempFile, 'test content');
        
        $result = $this->service->getMtime($tempFile);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
        $this->assertEquals(filemtime($tempFile), $result);
        
        
        unlink($tempFile);
    }

    public function testGetMtimeWithCachingCacheMiss(): void
    {
        
        $tempFile = tempnam(sys_get_temp_dir(), 'mtime_test_');
        file_put_contents($tempFile, 'test content');
        
        $ttl = 300; // 5 minutes
        
        
        $this->config->expects($this->once())
            ->method('getMtimeCachePathMap')
            ->willReturn([$tempFile => $ttl]);

        
        $this->cacheManager->expects($this->once())
            ->method('get')
            ->with($this->stringContains('mtime_'), 'mtime')
            ->willReturn(null);

        
        $this->cacheManager->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('mtime_'),
                $this->callback(function($data) {
                    return is_array($data) 
                        && isset($data['mtime']) 
                        && isset($data['timestamp'])
                        && is_int($data['mtime'])
                        && is_int($data['timestamp']);
                }),
                'mtime',
                $ttl
            );
        
        $result = $this->service->getMtime($tempFile);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
        $this->assertEquals(filemtime($tempFile), $result);
        
        
        unlink($tempFile);
    }

    public function testGetMtimeWithCachingCacheHitFresh(): void
    {
        $filePath = '/test/file.json';
        $ttl = 300; // 5 minutes
        $cachedMtime = 1234567890;
        $cachedTimestamp = time() - 60; // 1 minute ago (fresh)
        
        
        $this->config->expects($this->once())
            ->method('getMtimeCachePathMap')
            ->willReturn([$filePath => $ttl]);

        
        $this->cacheManager->expects($this->once())
            ->method('get')
            ->with($this->stringContains('mtime_'), 'mtime')
            ->willReturn([
                'mtime' => $cachedMtime,
                'timestamp' => $cachedTimestamp
            ]);

        
        $this->cacheManager->expects($this->never())
            ->method('set');

        $result = $this->service->getMtime($filePath);
        
        $this->assertEquals($cachedMtime, $result);
    }

    public function testGetMtimeWithCachingCacheHitStale(): void
    {
        
        $tempFile = tempnam(sys_get_temp_dir(), 'mtime_test_');
        file_put_contents($tempFile, 'test content');
        
        $ttl = 300; // 5 minutes
        $cachedMtime = 1234567890;
        $cachedTimestamp = time() - 400; // 400 seconds ago (stale)
        
        
        $this->config->expects($this->once())
            ->method('getMtimeCachePathMap')
            ->willReturn([$tempFile => $ttl]);

        
        $this->cacheManager->expects($this->once())
            ->method('get')
            ->with($this->stringContains('mtime_'), 'mtime')
            ->willReturn([
                'mtime' => $cachedMtime,
                'timestamp' => $cachedTimestamp
            ]);

        
        $this->cacheManager->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('mtime_'),
                $this->callback(function($data) {
                    return is_array($data) 
                        && isset($data['mtime']) 
                        && isset($data['timestamp'])
                        && is_int($data['mtime'])
                        && is_int($data['timestamp']);
                }),
                'mtime',
                $ttl
            );
        
        $result = $this->service->getMtime($tempFile);
        
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
        $this->assertEquals(filemtime($tempFile), $result);
        
        
        unlink($tempFile);
    }

    public function testGetMtimeWithNonExistentFile(): void
    {
        $filePath = '/non/existent/file.json';
        
        
        $this->config->expects($this->once())
            ->method('getMtimeCachePathMap')
            ->willReturn([$filePath => 0]);

        $result = $this->service->getMtime($filePath);
        
        
        $this->assertEquals(0, $result);
    }

    public function testGetMtimeWithFileNotInPathMap(): void
    {
        $filePath = '/unmapped/file.json';
        
        
        $this->config->expects($this->once())
            ->method('getMtimeCachePathMap')
            ->willReturn([]);

        
        $this->cacheManager->expects($this->never())
            ->method('get');
        $this->cacheManager->expects($this->never())
            ->method('set');

        $result = $this->service->getMtime($filePath);
        
        
        $this->assertEquals(0, $result);
    }
}
