<?php
declare(strict_types=1);

namespace App\Config;

final class Environment
{
    public const DEFAULT_ENV = 'dev';
    public const DEFAULT_LOG_LEVEL = 'info';

    /** @var array<string, bool> */
    public const PRODUCTION = ['prod' => true, 'production' => true];

    /** @var array<string, bool> */
    public const DEVELOPMENT = ['dev' => true, 'development' => true];

    /** @var array<string, bool> */
    public const TESTING = ['testing' => true];

    public static function isProduction(string $env): bool
    {
        $e = strtolower($env);
        return isset(self::PRODUCTION[$e]);
    }

    public static function isDevelopment(string $env): bool
    {
        $e = strtolower($env);
        return isset(self::DEVELOPMENT[$e]);
    }

    public static function isTesting(string $env): bool
    {
        $e = strtolower($env);
        return isset(self::TESTING[$e]);
    }

    public static function isNonProduction(string $env): bool
    {
        $e = strtolower($env);
        return isset(self::DEVELOPMENT[$e]) || isset(self::TESTING[$e]);
    }
}
