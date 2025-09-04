<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Config\AppConfig;
use App\Config\DataFileNames;
use App\Config\DependencyNames;
use App\Services\CacheManager;
use App\Services\MtimeCacheService;
use App\Utils\Log;

class UrlsService
{
    private string $configPath;

    public function __construct(
        private FileCacheService $fileCacheService,
        private AppConfig $appConfig
    ) {
        $this->configPath = $this->appConfig->getUrlsConfigPath();
    }

    public function getBackendJsonRpcUrl(): string
    {
        return $this->getUrls()['backend_jsonrpc_url'] ?? '';
    }

    public function getNotificationsJsonRpcUrl(): string
    {
        return $this->getUrls()['notifications_jsonrpc_url'] ?? '';
    }

    public function getAssetsUrls(): array
    {
        return $this->getUrls()['assets_cdn_urls'] ?? [];
    }

    public function getDefinitionsUrls(): array
    {
        return $this->getUrls()['definitions_cdn_urls'] ?? [];
    }

    /** @return array<string, mixed> */
    private function getUrls(): array
    {
        return $this->fileCacheService->loadJsonFile(DependencyNames::URLS, $this->configPath);
    }
}
