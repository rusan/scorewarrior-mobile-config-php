<?php
declare(strict_types=1);

namespace App\Config;

final class CacheConfig
{
    public const PROD_ADAPTER = 'apcu';
    public const DEV_ADAPTER = 'memory';
    public const PROD_DEFAULT_LIFETIME = 3600;
    public const DEV_DEFAULT_LIFETIME = 60;
    public const PROD_PREFIX = 'prod_cache_';
    public const DEV_PREFIX = 'dev_cache_';
    public const DEFAULT_SERIALIZER = 'Php';

    public function __construct(
        private int $mtimeCacheFixturesTtl,
        private int $mtimeCacheUrlsTtl,
        private int $mtimeCacheGeneralTtl,
        private int $defaultCacheTtl,
        private int $localCacheMaxSize
    ) {}

    public function getMtimeCacheFixturesTtl(): int
    {
        return $this->mtimeCacheFixturesTtl;
    }

    public function getMtimeCacheUrlsTtl(): int
    {
        return $this->mtimeCacheUrlsTtl;
    }

    public function getMtimeCacheGeneralTtl(): int
    {
        return $this->mtimeCacheGeneralTtl;
    }

    public function getDefaultCacheTtl(): int
    {
        return $this->defaultCacheTtl;
    }

    public function getLocalCacheMaxSize(): int
    {
        return $this->localCacheMaxSize;
    }

    public function getMtimeCacheTTLSettings(): MtimeTtlSettings
    {
        return new MtimeTtlSettings(
            fixtures: $this->mtimeCacheFixturesTtl,
            urls: $this->mtimeCacheUrlsTtl,
            general: $this->mtimeCacheGeneralTtl,
        );
    }

    public function getCacheSettings(bool $isProduction): CacheFactorySettings
    {
        if ($isProduction) {
            return new CacheFactorySettings(
                adapter: self::PROD_ADAPTER,
                options: [
                    'defaultSerializer' => self::DEFAULT_SERIALIZER,
                    'lifetime' => self::PROD_DEFAULT_LIFETIME,
                ],
                prefix: self::PROD_PREFIX,
            );
        }

        return new CacheFactorySettings(
            adapter: self::DEV_ADAPTER,
            options: [
                'lifetime' => self::DEV_DEFAULT_LIFETIME,
            ],
            prefix: self::DEV_PREFIX,
        );
    }
}


