<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\CacheTypes;
use App\Config\ConfigInterface;
use App\Contracts\LoggerInterface;
use App\Utils\CacheKeyBuilder;

class FileCacheService
{

    public function __construct(
        private ConfigInterface $config,
        private CacheManager $cacheManager,
        private MtimeCacheService $mtimeCacheService,
        private LoggerInterface $logger
    ) {}

    public function loadJsonFile(string $cacheType, string $filePath): array
    {
        $mtime = $this->mtimeCacheService->getMtime($filePath);
        $cacheKey = $this->buildCacheKey($cacheType, $filePath, $mtime);

        $cachedResult = $this->cacheManager->get($cacheKey, $cacheType);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        if (!is_file($filePath)) {
            $this->logger->logFileNotFound($filePath);
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->logger->logFileInvalid($filePath, 'read_failed');
            return [];
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            $this->logger->logFileInvalid($filePath, 'invalid_json');
            return [];
        }

        $ttl = $this->getTTLForCacheType($cacheType);
        $this->cacheManager->set($cacheKey, $decoded, $cacheType, $ttl);

        $this->logger->logFileLoaded($filePath, $mtime, strlen($content));
        return $decoded;
    }

    private function buildCacheKey(string $cacheType, string $filePath, int $mtime): string
    {
        switch ($cacheType) {
            case CacheTypes::FIXTURES:
                return CacheKeyBuilder::fileCacheKey($cacheType, $filePath, $mtime);
            case CacheTypes::URLS:
                return CacheKeyBuilder::urlsConfig($mtime);
            default:
                return CacheKeyBuilder::fileCacheKey($cacheType, $filePath, $mtime);
        }
    }

    private function getTTLForCacheType(string $cacheType): int
    {
        $settings = $this->config->getMtimeCacheTTLSettings();
        return match ($cacheType) {
            CacheTypes::FIXTURES => $settings['fixtures'] ?? $this->config->getMtimeCacheGeneralTtl(),
            CacheTypes::URLS => $settings['urls'] ?? $this->config->getMtimeCacheGeneralTtl(),
            default => $settings['general'] ?? $this->config->getMtimeCacheGeneralTtl(),
        };
    }
}
