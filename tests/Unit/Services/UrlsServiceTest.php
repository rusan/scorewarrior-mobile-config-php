<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\CacheTypes;
use App\Services\FileCacheService;
use App\Services\UrlsService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UrlsServiceTest extends TestCase
{
    private UrlsService $service;
    private FileCacheService&MockObject $fileCacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fileCacheService = $this->createMock(FileCacheService::class);
        $config = $this->createMock(\App\Config\ConfigInterface::class);
        $config->method('getUrlsConfigPath')->willReturn('/test/path/urls-config.json');
        
        $this->service = new UrlsService($this->fileCacheService, $config);
    }

    public function testGetBackendJsonRpcUrl(): void
    {
        $urlsData = [
            'backend_jsonrpc_url' => 'https://api.application.com/jsonrpc/v2',
            'notifications_jsonrpc_url' => 'https://notifications.application.com/jsonrpc/v1',
        ];

        $this->fileCacheService->expects($this->once())
            ->method('loadJsonFile')
            ->with(CacheTypes::URLS, '/test/path/urls-config.json')
            ->willReturn($urlsData);

        $result = $this->service->getBackendJsonRpcUrl();

        $this->assertEquals('https://api.application.com/jsonrpc/v2', $result);
    }

    public function testGetNotificationsJsonRpcUrl(): void
    {
        $urlsData = [
            'backend_jsonrpc_url' => 'https://api.application.com/jsonrpc/v2',
            'notifications_jsonrpc_url' => 'https://notifications.application.com/jsonrpc/v1',
        ];

        $this->fileCacheService->expects($this->once())
            ->method('loadJsonFile')
            ->with(CacheTypes::URLS, '/test/path/urls-config.json')
            ->willReturn($urlsData);

        $result = $this->service->getNotificationsJsonRpcUrl();

        $this->assertEquals('https://notifications.application.com/jsonrpc/v1', $result);
    }

    public function testGetAssetsUrls(): void
    {
        $urlsData = [
            'assets_cdn_urls' => ['https://dhm.cdn.application.com', 'https://ehz.cdn.application.com'],
            'definitions_cdn_urls' => ['https://fmp.cdn.application.com', 'https://eau.cdn.application.com'],
        ];

        $this->fileCacheService->expects($this->once())
            ->method('loadJsonFile')
            ->with(CacheTypes::URLS, '/test/path/urls-config.json')
            ->willReturn($urlsData);

        $result = $this->service->getAssetsUrls();

        $this->assertEquals(['https://dhm.cdn.application.com', 'https://ehz.cdn.application.com'], $result);
    }

    public function testGetDefinitionsUrls(): void
    {
        $urlsData = [
            'assets_cdn_urls' => ['https://dhm.cdn.application.com', 'https://ehz.cdn.application.com'],
            'definitions_cdn_urls' => ['https://fmp.cdn.application.com', 'https://eau.cdn.application.com'],
        ];

        $this->fileCacheService->expects($this->once())
            ->method('loadJsonFile')
            ->with(CacheTypes::URLS, '/test/path/urls-config.json')
            ->willReturn($urlsData);

        $result = $this->service->getDefinitionsUrls();

        $this->assertEquals(['https://fmp.cdn.application.com', 'https://eau.cdn.application.com'], $result);
    }

    public function testGetBackendJsonRpcUrlWithMissingKey(): void
    {
        $urlsData = [
            'notifications_jsonrpc_url' => 'https://notifications.application.com/jsonrpc/v1',
        ];

        $this->fileCacheService->expects($this->once())
            ->method('loadJsonFile')
            ->willReturn($urlsData);

        $result = $this->service->getBackendJsonRpcUrl();

        $this->assertEquals('', $result);
    }

    public function testGetNotificationsJsonRpcUrlWithMissingKey(): void
    {
        $urlsData = [
            'backend_jsonrpc_url' => 'https://api.application.com/jsonrpc/v2',
        ];

        $this->fileCacheService->expects($this->once())
            ->method('loadJsonFile')
            ->willReturn($urlsData);

        $result = $this->service->getNotificationsJsonRpcUrl();

        $this->assertEquals('', $result);
    }

    public function testGetAssetsUrlsWithMissingKey(): void
    {
        $urlsData = [
            'definitions_cdn_urls' => ['https://fmp.cdn.application.com'],
        ];

        $this->fileCacheService->expects($this->once())
            ->method('loadJsonFile')
            ->willReturn($urlsData);

        $result = $this->service->getAssetsUrls();

        $this->assertEquals([], $result);
    }

    public function testGetDefinitionsUrlsWithMissingKey(): void
    {
        $urlsData = [
            'assets_cdn_urls' => ['https://dhm.cdn.application.com'],
        ];

        $this->fileCacheService->expects($this->once())
            ->method('loadJsonFile')
            ->willReturn($urlsData);

        $result = $this->service->getDefinitionsUrls();

        $this->assertEquals([], $result);
    }

    public function testMultipleCallsUseSameData(): void
    {
        $urlsData = [
            'backend_jsonrpc_url' => 'https://api.application.com/jsonrpc/v2',
            'notifications_jsonrpc_url' => 'https://notifications.application.com/jsonrpc/v1',
            'assets_cdn_urls' => ['https://dhm.cdn.application.com'],
            'definitions_cdn_urls' => ['https://fmp.cdn.application.com'],
        ];


        $this->fileCacheService->expects($this->exactly(4))
            ->method('loadJsonFile')
            ->with(CacheTypes::URLS, '/test/path/urls-config.json')
            ->willReturn($urlsData);


        $this->assertEquals('https://api.application.com/jsonrpc/v2', $this->service->getBackendJsonRpcUrl());
        $this->assertEquals('https://notifications.application.com/jsonrpc/v1', $this->service->getNotificationsJsonRpcUrl());
        $this->assertEquals(['https://dhm.cdn.application.com'], $this->service->getAssetsUrls());
        $this->assertEquals(['https://fmp.cdn.application.com'], $this->service->getDefinitionsUrls());
    }
}
