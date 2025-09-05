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
        $di->setShared(\App\Services\DependencyTypeRegistry::class, function () {
            return new \App\Services\DependencyTypeRegistry();
        });

        $di->setShared(\App\Config\DiServiceIds::ROUTER, function () {
            $router = new Router(false);
            $router->removeExtraSlashes(true);
            return $router;
        });

        $di->setShared(\Phalcon\Mvc\Router::class, function () use ($di) { return $di->getShared(\App\Config\DiServiceIds::ROUTER); });

        $di->setShared(\App\Config\DiServiceIds::REQUEST, function () { return new Request(); });
        $di->setShared(\Phalcon\Http\Request::class, function () use ($di) { return $di->getShared(\App\Config\DiServiceIds::REQUEST); });

        $di->setShared(\App\Config\DiServiceIds::RESPONSE, function () { return new Response(); });
        $di->setShared(\Phalcon\Http\Response::class, function () use ($di) { return $di->getShared(\App\Config\DiServiceIds::RESPONSE); });

        $di->setShared(\App\Config\DiServiceIds::FILTER, function () { return new Filter(); });
        $di->setShared(\Phalcon\Filter\Filter::class, function () use ($di) { return $di->getShared(\App\Config\DiServiceIds::FILTER); });
    }

    private static function registerConfigServices(Di $di): void
    {
        $di->setShared(\App\Config\ConfigInterface::class, function () use ($di) {
            $dependencyTypeRegistry = $di->getShared(\App\Services\DependencyTypeRegistry::class);
            $urlsServiceProvider = function () use ($di) {
                return $di->getShared(\App\Services\UrlsService::class);
            };
            return \App\Config\Config::fromEnv($dependencyTypeRegistry, $urlsServiceProvider);
        });

        // ttlConfigService removed: FileCacheService uses ConfigInterface directly
    }

    private static function registerCacheServices(Di $di): void
    {
        $di->setShared(\App\Config\DiServiceIds::CACHE, function () use ($di) {
            /** @var ConfigInterface $config */
            $config = $di->getShared(\App\Config\ConfigInterface::class);
            $cacheSettings = $config->getCacheSettings();

            $serializerFactory = new SerializerFactory();
            $adapterFactory = new AdapterFactory($serializerFactory);
            $cacheFactory = new CacheFactory($adapterFactory);
            return $cacheFactory->newInstance(
                $cacheSettings->adapter,
                $cacheSettings->options
            );
        });

        $di->setShared(\Phalcon\Cache\Cache::class, function () use ($di) { return $di->getShared(\App\Config\DiServiceIds::CACHE); });

        $di->setShared(\App\Services\CacheManager::class, function () use ($di): CacheManager {
            $cache = $di->getShared(\Phalcon\Cache\Cache::class);
            $config = $di->getShared(\App\Config\ConfigInterface::class);
            return new CacheManager($cache, $config);
        });

        $di->setShared(\App\Services\MtimeCacheService::class, function () use ($di) {
            /** @var ConfigInterface $config */
            $config = $di->getShared(\App\Config\ConfigInterface::class);
            $cacheManager = $di->getShared(\App\Services\CacheManager::class);
            return new MtimeCacheService($config, $cacheManager);
        });

        $di->setShared(\App\Services\FileCacheService::class, function () use ($di) {
            $config = $di->getShared(\App\Config\ConfigInterface::class);
            $cacheManager = $di->getShared(\App\Services\CacheManager::class);
            $mtimeCache = $di->getShared(\App\Services\MtimeCacheService::class);
            $logger = $di->getShared(\App\Contracts\LoggerInterface::class);
            return new FileCacheService($config, $cacheManager, $mtimeCache, $logger);
        });
    }

    private static function registerApplicationServices(Di $di): void
    {
        $di->setShared(\App\Services\UrlsService::class, function () use ($di) {
            $fileCacheService = $di->getShared(\App\Services\FileCacheService::class);
            $config = $di->getShared(\App\Config\ConfigInterface::class);
            return new UrlsService($fileCacheService, $config);
        });

        $di->setShared(\App\Services\FixturesService::class, function () use ($di) {
            /** @var ConfigInterface $config */
            $config = $di->getShared(\App\Config\ConfigInterface::class);
            $fileCacheService = $di->getShared(\App\Services\FileCacheService::class);
            $logger = $di->getShared(\App\Contracts\LoggerInterface::class);
            return new FixturesService($config->getFixturesPaths(), $fileCacheService, $logger);
        });

        $di->setShared(\App\Services\ResolverService::class, function () use ($di) {
            $fixturesService = $di->getShared(\App\Services\FixturesService::class);
            $config = $di->getShared(\App\Config\ConfigInterface::class);
            $cacheManager = $di->getShared(\App\Services\CacheManager::class);
            $dependencyTypeRegistry = $di->getShared(\App\Services\DependencyTypeRegistry::class);
            $mtimeCacheService = $di->getShared(\App\Services\MtimeCacheService::class);
            $logger = $di->getShared(\App\Contracts\LoggerInterface::class);
            return new ResolverService(
                $fixturesService,
                $config,
                $cacheManager,
                $dependencyTypeRegistry,
                $mtimeCacheService,
                $logger
            );
        });

        $di->setShared(\App\Services\ConfigService::class, function () use ($di) {
            $resolverService = $di->getShared(\App\Services\ResolverService::class);
            $config = $di->getShared(\App\Config\ConfigInterface::class);
            $cacheManager = $di->getShared(\App\Services\CacheManager::class);
            $logger = $di->getShared(\App\Contracts\LoggerInterface::class);
            return new ConfigService($resolverService, $config, $cacheManager, $logger);
        });

        // requestParameterService removed — parameters are extracted directly where needed

        // AppConfig is now part of UnifiedConfig, no separate registration needed

        $di->setShared(\App\Contracts\LoggerInterface::class, function () {
            return new \App\Services\StructuredLogger();
        });

        $di->setShared(\App\Services\HealthService::class, function () use ($di) {
            /** @var \App\Config\ConfigInterface $config */
            $config = $di->getShared(\App\Config\ConfigInterface::class);
            $mtimeCacheService = $di->getShared(\App\Services\MtimeCacheService::class);
            return new HealthService($config, $mtimeCacheService);
        });
    }
}
