<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Config\DependencyNames;

class TTLConfigService
{
    public function __construct(
        private ConfigInterface $config
    ) {}

    public function getTTLForCacheType(string $cacheType): int
    {
        return match($cacheType) {
            DependencyNames::FIXTURES => $this->config->getMtimeCacheFixturesTtl(),
            DependencyNames::URLS => $this->config->getMtimeCacheUrlsTtl(),
            default => $this->config->getMtimeCacheGeneralTtl()
        };
    }

    public function getFixturesTTL(): int
    {
        return $this->config->getMtimeCacheFixturesTtl();
    }

    public function getUrlsTTL(): int
    {
        return $this->config->getMtimeCacheUrlsTtl();
    }

    public function getGeneralTTL(): int
    {
        return $this->config->getMtimeCacheGeneralTtl();
    }
}
