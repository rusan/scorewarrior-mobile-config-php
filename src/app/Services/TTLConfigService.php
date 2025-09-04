<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use App\Config\DependencyNames;

class TTLConfigService
{
    public function __construct(
        private AppConfig $appConfig
    ) {}

    public function getTTLForCacheType(string $cacheType): int
    {
        return match($cacheType) {
            DependencyNames::FIXTURES => $this->appConfig->getMtimeCacheFixturesTtl(),
            DependencyNames::URLS => $this->appConfig->getMtimeCacheUrlsTtl(),
            default => $this->appConfig->getMtimeCacheGeneralTtl()
        };
    }

    public function getFixturesTTL(): int
    {
        return $this->appConfig->getMtimeCacheFixturesTtl();
    }

    public function getUrlsTTL(): int
    {
        return $this->appConfig->getMtimeCacheUrlsTtl();
    }

    public function getGeneralTTL(): int
    {
        return $this->appConfig->getMtimeCacheGeneralTtl();
    }
}
