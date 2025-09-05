<?php
declare(strict_types=1);

namespace App\Utils;

class CacheKeyBuilder
{
    // Config response cache key
    public static function configResponse(string $platform, string $appVersion, ?string $assetsVersion, ?string $definitionsVersion): string
    {
        return implode('_', [
            'config',
            $platform,
            $appVersion,
            $assetsVersion ?? 'null',
            $definitionsVersion ?? 'null'
        ]);
    }

    // Fixtures content cache key (per kind/platform/mtime)
    public static function fixturesContent(string $kind, string $platform, int $mtime): string
    {
        return implode('_', ['fixtures', $kind, $platform, (string)$mtime]);
    }

    // Resolver result cache key (per platform/app/exp versions/mtime/type)
    public static function resolverResult(string $platform, string $appVersion, ?string $assetsVersion, ?string $definitionsVersion, int $mtime, string $type): string
    {
        return implode('_', [
            'resolver',
            $platform,
            $appVersion,
            $assetsVersion ?? 'null',
            $definitionsVersion ?? 'null',
            "M{$mtime}",
            $type
        ]);
    }

    // File mtime cache key (per path)
    public static function fileMtime(string $filePath): string
    {
        return 'mtime_' . md5($filePath);
    }

    // Local-only fixtures cache key (LRU in-process)
    public static function fixturesLocalKey(string $kind, string $platform): string
    {
        return implode('_', ['fixtures', $kind, $platform]);
    }

    // URLs config cache key (per mtime)
    public static function urlsConfig(int $mtime): string
    {
        return implode('_', ['urls', 'config', (string)$mtime]);
    }

    // Generic file cache key builder
    public static function fileCacheKey(string $cacheType, string $filePath, int $mtime, ?string $platform = null): string
    {
        $parts = [$cacheType, basename($filePath, '.json'), (string)$mtime];
        if ($platform !== null) {
            $parts[] = $platform;
        }
        return implode('_', $parts);
    }
}
