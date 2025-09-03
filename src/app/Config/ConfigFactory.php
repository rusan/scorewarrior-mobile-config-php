<?php
declare(strict_types=1);

namespace App\Config;

use App\Config\Environment\DevConfig;
use App\Config\Environment\ProdConfig;
use App\Services\DependencyTypeRegistry;
use App\Services\UrlsService;

class ConfigFactory
{
    public static function create(DependencyTypeRegistry $dependencyTypeRegistry): ConfigInterface
    {
        $environment = getenv('APP_ENV') ?: 'dev';
        
        return match (strtolower($environment)) {
            'prod', 'production' => new ProdConfig($dependencyTypeRegistry),
            default => new DevConfig($dependencyTypeRegistry),
        };
    }
}
