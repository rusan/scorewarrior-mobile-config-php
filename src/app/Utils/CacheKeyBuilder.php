<?php
declare(strict_types=1);

namespace App\Utils;

class CacheKeyBuilder
{
    public static function config(string $platform, string $appVersion, ?string $assetsVersion, ?string $definitionsVersion): string
    {
        return implode('_', [
            'config',
            $platform,
            $appVersion,
            $assetsVersion ?? 'null',
            $definitionsVersion ?? 'null'
        ]);
    }

    public static function fixtures(string $kind, string $platform, int $mtime): string
    {
        return implode('_', ['fixtures', $kind, $platform, (string)$mtime]);
    }

    public static function resolver(string $platform, string $appVersion, ?string $assetsVersion, ?string $definitionsVersion, int $mtime, string $type): string
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

    public static function mtime(string $filePath): string
    {
        return 'mtime_' . md5($filePath);
    }

    public static function fixturesLocal(string $kind, string $platform): string
    {
        return implode('_', ['fixtures', $kind, $platform]);
    }

    public static function urls(int $mtime): string
    {
        return implode('_', ['urls', 'config', (string)$mtime]);
    }

    public static function file(string $cacheType, string $filePath, int $mtime, ?string $platform = null): string
    {
        $parts = [$cacheType, basename($filePath, '.json'), (string)$mtime];
        if ($platform !== null) {
            $parts[] = $platform;
        }
        return implode('_', $parts);
    }
}
