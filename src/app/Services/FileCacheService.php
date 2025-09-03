<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\DependencyNames;
use App\Utils\CacheKeyBuilder;
use App\Utils\Log;

class FileCacheService
{
    /** @var array<string, mixed> */
    private array $localCache = [];

    public function __construct(
        private TTLConfigService $ttlConfig,
        private CacheManager $cacheManager,
        private MtimeCacheService $mtimeCacheService
    ) {}

    public function loadJsonFile(string $cacheType, string $filePath): array
    {
        $mtime = $this->mtimeCacheService->getMtime($filePath);
        $cacheKey = $this->buildCacheKey($cacheType, $filePath, $mtime);
        $localCache = $this->localCache[$cacheKey] ?? null;

        if ($localCache !== null) {
            Log::info('file_local_cache_hit', ['path' => $filePath, 'mtime' => $mtime]);
            return $localCache;
        }

        $cachedResult = $this->cacheManager->get($cacheKey, $cacheType);
        if ($cachedResult !== null) {
            $this->localCache[$cacheKey] = $cachedResult;
            return $cachedResult;
        }

        if (!is_file($filePath)) {
            Log::error('file_not_found', ['path' => $filePath]);
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            Log::error('file_read_failed', ['path' => $filePath]);
            return [];
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            Log::error('file_invalid_json', ['path' => $filePath]);
            return [];
        }

        $this->localCache[$cacheKey] = $decoded;
        $ttl = $this->ttlConfig->getTTLForCacheType($cacheType);
        $this->cacheManager->set($cacheKey, $decoded, $cacheType, $ttl);

        Log::info('file_loaded', ['path' => $filePath, 'mtime' => $mtime]);
        return $decoded;
    }

    private function buildCacheKey(string $cacheType, string $filePath, int $mtime): string
    {
        switch ($cacheType) {
            case DependencyNames::FIXTURES:
                return CacheKeyBuilder::file($cacheType, $filePath, $mtime);
            case DependencyNames::URLS:
                return CacheKeyBuilder::urls($mtime);
            default:
                return CacheKeyBuilder::file($cacheType, $filePath, $mtime);
        }
    }
}
