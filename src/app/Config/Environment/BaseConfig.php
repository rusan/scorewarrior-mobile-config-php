<?php
declare(strict_types=1);

namespace App\Config\Environment;

use App\Config\ConfigInterface;
use App\Config\DataFileNames;
use App\Config\DependencyNames;
use App\Services\DependencyTypeRegistry;
use App\Services\UrlsService;
use Closure;

abstract class BaseConfig implements ConfigInterface
{
    private array $mtimeCachePathMap;
    private array $fixturesPaths;
    private array $mtimeCacheTTLSettings;

    public function __construct(
        private DependencyTypeRegistry $dependencyTypeRegistry,
        private Closure $urlsServiceProvider
    ) {
        $dataPath = getenv('DATA_PATH');
        $ttlSettings = [
            DependencyNames::FIXTURES => $this->getFixturesMtimeTTL(),
            DependencyNames::URLS => $this->getUrlsMtimeTTL(),
            'general' => $this->getGeneralMtimeTTL(),
        ];

        $this->mtimeCachePathMap = [];
        $this->fixturesPaths = [];
        $this->mtimeCacheTTLSettings = $ttlSettings;
        
        $this->mtimeCachePathMap[$dataPath . '/' . DataFileNames::URLS_CONFIG] = $ttlSettings[DependencyNames::URLS];
        
        foreach ($this->dependencyTypeRegistry->getAll() as $type) {
            $filePath = $dataPath . '/' . $type->getFileName();
            $this->mtimeCachePathMap[$filePath] = $ttlSettings[DependencyNames::FIXTURES];
            $this->fixturesPaths[$type->getName()] = $filePath;
        }
    }

    private function getUrlsService(): UrlsService
    {
        /** @var UrlsService $service */
        $service = ($this->urlsServiceProvider)();
        return $service;
    }

    public function getBackendJsonRpcUrl(): string
    {
        return $this->getUrlsService()->getBackendJsonRpcUrl();
    }

    public function getNotificationsJsonRpcUrl(): string
    {
        return $this->getUrlsService()->getNotificationsJsonRpcUrl();
    }

    public function getAssetsUrls(): array
    {
        return $this->getUrlsService()->getAssetsUrls();
    }

    public function getDefinitionsUrls(): array
    {
        return $this->getUrlsService()->getDefinitionsUrls();
    }

    public function getFixturesPaths(): array
    {
        return $this->fixturesPaths;
    }

    public function getMtimeCacheTTLSettings(): array
    {
        return $this->mtimeCacheTTLSettings;
    }

    public function getMtimeCachePathMap(): array
    {
        return $this->mtimeCachePathMap;
    }

    protected function getFixturesMtimeTTL(): int
    {
        return (int) getenv('MTIME_CACHE_FIXTURES_TTL');
    }

    protected function getUrlsMtimeTTL(): int
    {
        return (int) getenv('MTIME_CACHE_URLS_TTL');
    }

    protected function getGeneralMtimeTTL(): int
    {
        return (int) getenv('MTIME_CACHE_GENERAL_TTL');
    }

    public function getDefaultCacheTTL(): int
    {
        return (int) getenv('DEFAULT_CACHE_TTL');
    }
}
