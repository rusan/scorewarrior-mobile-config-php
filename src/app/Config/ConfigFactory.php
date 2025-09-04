<?php
declare(strict_types=1);

namespace App\Config;

use App\Config\Environment\DevConfig;
use App\Config\Environment\ProdConfig;
use App\Services\DependencyTypeRegistry;
use Closure;

class ConfigFactory
{
    public static function create(DependencyTypeRegistry $dependencyTypeRegistry, Closure $urlsServiceProvider, AppConfig $appConfig): ConfigInterface
    {
        return match (true) {
            $appConfig->isProduction() => new ProdConfig($dependencyTypeRegistry, $urlsServiceProvider, $appConfig),
            default => new DevConfig($dependencyTypeRegistry, $urlsServiceProvider, $appConfig),
        };
    }
}
