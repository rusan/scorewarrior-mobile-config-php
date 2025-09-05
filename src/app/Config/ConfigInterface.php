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

    public function getCacheSettings(): CacheFactorySettings;

    public function isDebugMode(): bool;

    public function getMtimeCacheTTLSettings(): MtimeTtlSettings;

    public function getMtimeCachePathMap(): array;

    public function getDefaultCacheTtl(): int;

    public function getLocalCacheMaxSize(): int;

    public function getUrlsConfigPath(): string;
}
