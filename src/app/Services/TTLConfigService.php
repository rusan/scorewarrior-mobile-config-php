<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\DependencyNames;

class TTLConfigService
{
    private const FIXTURES_TTL_DEFAULT = 3600;
    private const URLS_TTL_DEFAULT = 7200;
    private const GENERAL_TTL_DEFAULT = 1800;

    public function getTTLForCacheType(string $cacheType): int
    {
        return match($cacheType) {
            DependencyNames::FIXTURES => $this->getFixturesTTL(),
            DependencyNames::URLS => $this->getUrlsTTL(),
            default => $this->getGeneralTTL()
        };
    }

    public function getFixturesTTL(): int
    {
        return (int) getenv('MTIME_CACHE_FIXTURES_TTL') ?: self::FIXTURES_TTL_DEFAULT;
    }

    public function getUrlsTTL(): int
    {
        return (int) getenv('MTIME_CACHE_URLS_TTL') ?: self::URLS_TTL_DEFAULT;
    }

    public function getGeneralTTL(): int
    {
        return (int) getenv('MTIME_CACHE_GENERAL_TTL') ?: self::GENERAL_TTL_DEFAULT;
    }
}
