<?php
declare(strict_types=1);

namespace App\Providers;
use App\Config\ConfigInterface;
use App\Services\CacheManager;
use App\Services\ConfigService;
use App\Services\FileCacheService;
use App\Services\FixturesService;
use App\Services\MtimeCacheService;
use App\Services\HealthService;
use App\Services\RequestParameterService;
use App\Services\ResolverService;
use App\Services\UrlsService;
use Phalcon\Cache\AdapterFactory;
use Phalcon\Cache\CacheFactory;
use Phalcon\Di\Di;
use Phalcon\Filter\Filter;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\Mvc\Router;
use Phalcon\Storage\SerializerFactory;

class ServiceProvider
{
    public static function register(Di $di): void
    {
        self::registerCoreServices($di);
        self::registerApplicationServices($di);
        self::registerConfigServices($di);
        self::registerCacheServices($di);
    }

    private static function registerCoreServices(Di $di): void
    {
        $di->setShared('dependencyTypeRegistry', function () {
            return new \App\Services\DependencyTypeRegistry();
        });

        $di->setShared('router', function () {
            $router = new Router(false);
            $router->removeExtraSlashes(true);
            return $router;
        });

        $di->setShared('request', function () {
            return new Request();
        });

        $di->setShared('response', function () {
            return new Response();
        });

        $di->setShared('filter', function () {
            return new Filter();
        });
    }

    private static function registerConfigServices(Di $di): void
    {
        $di->setShared('config', function () use ($di) {
            $dependencyTypeRegistry = $di->getShared('dependencyTypeRegistry');
            $urlsServiceProvider = function () use ($di) {
                return $di->getShared('urlsService');
            };
            return \App\Config\Config::fromEnv($dependencyTypeRegistry, $urlsServiceProvider);
        });

        // ttlConfigService removed: FileCacheService uses ConfigInterface directly
    }

    private static function registerCacheServices(Di $di): void
    {
        $di->setShared('cache', function () use ($di) {
            /** @var ConfigInterface $config */
            $config = $di->getShared('config');
            $cacheSettings = $config->getCacheSettings();

            $serializerFactory = new SerializerFactory();
            $adapterFactory = new AdapterFactory($serializerFactory);
            $cacheFactory = new CacheFactory($adapterFactory);
            return $cacheFactory->newInstance(
                $cacheSettings['adapter'],
                $cacheSettings['options']
            );
        });

        $di->setShared('cacheManager', function () use ($di): CacheManager {
            $cache = $di->getShared('cache');
            $config = $di->getShared('config');
            return new CacheManager($cache, $config);
        });

        $di->setShared('mtimeCacheService', function () use ($di) {
            /** @var ConfigInterface $config */
            $config = $di->getShared('config');
            $cacheManager = $di->getShared('cacheManager');
            return new MtimeCacheService($config, $cacheManager);
        });

        $di->setShared('fileCacheService', function () use ($di) {
            $config = $di->getShared('config');
            $cacheManager = $di->getShared('cacheManager');
            $mtimeCache = $di->getShared('mtimeCacheService');
            $logger = $di->getShared('logger');
            return new FileCacheService($config, $cacheManager, $mtimeCache, $logger);
        });
    }

    private static function registerApplicationServices(Di $di): void
    {
        $di->setShared('urlsService', function () use ($di) {
            $fileCacheService = $di->getShared('fileCacheService');
            $config = $di->getShared('config');
            return new UrlsService($fileCacheService, $config);
        });

        $di->setShared('fixturesService', function () use ($di) {
            /** @var ConfigInterface $config */
            $config = $di->getShared('config');
            $fileCacheService = $di->getShared('fileCacheService');
            $logger = $di->getShared('logger');
            return new FixturesService($config->getFixturesPaths(), $fileCacheService, $logger);
        });

        $di->setShared('resolverService', function () use ($di) {
            $fixturesService = $di->getShared('fixturesService');
            $config = $di->getShared('config');
            $cacheManager = $di->getShared('cacheManager');
            $dependencyTypeRegistry = $di->getShared('dependencyTypeRegistry');
            $mtimeCacheService = $di->getShared('mtimeCacheService');
            $logger = $di->getShared('logger');
            return new ResolverService(
                $fixturesService,
                $config,
                $cacheManager,
                $dependencyTypeRegistry,
                $mtimeCacheService,
                $logger
            );
        });

        $di->setShared('configService', function () use ($di) {
            $resolverService = $di->getShared('resolverService');
            $config = $di->getShared('config');
            $cacheManager = $di->getShared('cacheManager');
            $logger = $di->getShared('logger');
            return new ConfigService($resolverService, $config, $cacheManager, $logger);
        });

        // requestParameterService removed — parameters are extracted directly where needed

        // AppConfig is now part of UnifiedConfig, no separate registration needed

        $di->setShared('logger', function () {
            return new \App\Services\StructuredLogger();
        });

        $di->setShared('healthService', function () use ($di) {
            /** @var \App\Config\ConfigInterface $config */
            $config = $di->getShared('config');
            $fileCacheService = $di->getShared('fileCacheService');
            $mtimeCacheService = $di->getShared('mtimeCacheService');
            return new HealthService($config, $fileCacheService, $mtimeCacheService);
        });
    }
}
