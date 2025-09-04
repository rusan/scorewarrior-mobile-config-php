<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\CacheTypes;
use App\Contracts\LoggerInterface;
use App\Utils\CacheKeyBuilder;

class FileCacheService
{

    public function __construct(
        private TTLConfigService $ttlConfig,
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

        $ttl = $this->ttlConfig->getTTLForCacheType($cacheType);
        $this->cacheManager->set($cacheKey, $decoded, $cacheType, $ttl);

        $this->logger->logFileLoaded($filePath, $mtime, strlen($content));
        return $decoded;
    }

    private function buildCacheKey(string $cacheType, string $filePath, int $mtime): string
    {
        switch ($cacheType) {
            case CacheTypes::FIXTURES:
                return CacheKeyBuilder::file($cacheType, $filePath, $mtime);
            case CacheTypes::URLS:
                return CacheKeyBuilder::urls($mtime);
            default:
                return CacheKeyBuilder::file($cacheType, $filePath, $mtime);
        }
    }
}
