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
}
