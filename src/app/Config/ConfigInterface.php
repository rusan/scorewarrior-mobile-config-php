<?php
declare(strict_types=1);

namespace App\Config;

interface ConfigInterface
{
    public function getBackendJsonRpcUrl(): string;

    public function getNotificationsJsonRpcUrl(): string;

    public function getAssetsUrls(): array;

    public function getDefinitionsUrls(): array;

    public function getFixturesPaths(): array;

    public function getCacheSettings(): array;

    public function isDebugMode(): bool;

    public function getMtimeCacheTTLSettings(): array;

    public function getMtimeCachePathMap(): array;

    // Additional methods from AppConfig
    public function getMtimeCacheFixturesTtl(): int;

    public function getMtimeCacheUrlsTtl(): int;

    public function getMtimeCacheGeneralTtl(): int;

    public function getDefaultCacheTtl(): int;

    public function getLocalCacheMaxSize(): int;

    public function getUrlsConfigPath(): string;
}
