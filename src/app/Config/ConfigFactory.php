<?php
declare(strict_types=1);

namespace App\Config;

use App\Config\Environment\DevConfig;
use App\Config\Environment\ProdConfig;
use App\Services\DependencyTypeRegistry;
use Closure;

class ConfigFactory
{
    public static function create(DependencyTypeRegistry $dependencyTypeRegistry, Closure $urlsServiceProvider): ConfigInterface
    {
        $environment = getenv('APP_ENV') ?: 'dev';
        
        return match (strtolower($environment)) {
            'prod', 'production' => new ProdConfig($dependencyTypeRegistry, $urlsServiceProvider),
            default => new DevConfig($dependencyTypeRegistry, $urlsServiceProvider),
        };
    }
}
