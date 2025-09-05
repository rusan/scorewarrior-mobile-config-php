<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Utils\Log;
use Phalcon\Cache\Cache;

class CacheManager
{
    /** @var array<string, mixed> */
    private array $localCache = [];
    
    /** @var array<string, float> */
    private array $accessTimes = [];
    
    private int $maxLocalCacheSize;

    public function __construct(
        private Cache $externalCache,
        private ?ConfigInterface $config = null,
        private ?int $defaultTtl = null
    ) {
        if ($this->config) {
            $this->defaultTtl = $this->config->getDefaultCacheTtl();
            $this->maxLocalCacheSize = $this->config->getLocalCacheMaxSize();
        } else {
            // Fallback for backward compatibility
            $this->defaultTtl = $defaultTtl ?? 3600;
            $this->maxLocalCacheSize = 1000;
        }
    }

    public function get(string $key, string $type = 'cache'): mixed
    {
        if (isset($this->localCache[$key])) {
            $this->updateAccessTime($key);
            Log::info("{$type}_local_cache_hit", ['key' => $key]);
            return $this->localCache[$key];
        }

        $value = $this->externalCache->get($key, null);
        if ($value !== null) {
            Log::info("{$type}_cache_hit", ['key' => $key]);
            $this->setLocalCache($key, $value);
            return $value;
        }

        return null;
    }

    public function set(string $key, $value, string $type = 'cache', ?int $ttl = null): void
    {
        $this->setLocalCache($key, $value);
        Log::info("{$type}_local_cache_set", ['key' => $key]);

        $this->externalCache->set($key, $value, $ttl ?? $this->defaultTtl);
        Log::info("{$type}_cache_set", ['key' => $key, 'ttl' => $ttl ?? $this->defaultTtl]);
    }

    public function delete(string $key, string $type = 'cache'): void
    {
        unset($this->localCache[$key]);
        unset($this->accessTimes[$key]);
        Log::info("{$type}_local_cache_cleared", ['key' => $key]);

        $this->externalCache->delete($key);
        Log::info("{$type}_cache_cleared", ['key' => $key]);
    }

    public function clearLocal(string $type = 'cache'): void
    {
        $count = count($this->localCache);
        $this->localCache = [];
        $this->accessTimes = [];
        Log::info("{$type}_local_cache_cleared_all", ['count' => $count]);
    }

    public function remember(string $key, callable $callback, string $type = 'cache', ?int $ttl = null)
    {
        $value = $this->get($key, $type);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $type, $ttl);

        return $value;
    }

    public function hasLocal(string $key): bool
    {
        return isset($this->localCache[$key]);
    }

    public function getLocalCount(): int
    {
        return count($this->localCache);
    }

    private function setLocalCache(string $key, mixed $value): void
    {
        $this->localCache[$key] = $value;
        $this->updateAccessTime($key);
        
        if (count($this->localCache) > $this->maxLocalCacheSize) {
            $this->evictLeastRecentlyUsed();
        }
    }

    private function updateAccessTime(string $key): void
    {
        $this->accessTimes[$key] = microtime(true);
    }

    private function evictLeastRecentlyUsed(): void
    {
        if (empty($this->accessTimes)) {
            return;
        }

        $oldestKey = array_key_first($this->accessTimes);
        $oldestTime = $this->accessTimes[$oldestKey];

        foreach ($this->accessTimes as $key => $time) {
            if ($time < $oldestTime) {
                $oldestTime = $time;
                $oldestKey = $key;
            }
        }

        unset($this->localCache[$oldestKey]);
        unset($this->accessTimes[$oldestKey]);
        
        Log::info('local_cache_evicted', [
            'key' => $oldestKey,
            'remaining_count' => count($this->localCache)
        ]);
    }
}
