<?php
declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\Config;
use App\Services\DependencyTypeRegistry;
use App\Services\UrlsService;
use Tests\TestCase;

class ConfigTest extends TestCase
{
    public function testFromEnv(): void
    {
        // Set test environment variables
        $originalValues = [];
        $testValues = [
            'APP_ENV' => 'testing',
            'APP_LOG_LEVEL' => 'debug',
            'DATA_PATH' => '/test/data',
            'MTIME_CACHE_FIXTURES_TTL' => '1800',
            'MTIME_CACHE_URLS_TTL' => '120',
            'MTIME_CACHE_GENERAL_TTL' => '10',
            'DEFAULT_CACHE_TTL' => '7200',
            'LOCAL_CACHE_MAX_SIZE' => '500',
        ];

        foreach ($testValues as $key => $value) {
            $originalValues[$key] = getenv($key);
            putenv("$key=$value");
        }

        try {
            $dependencyTypeRegistry = new DependencyTypeRegistry();
            $urlsServiceProvider = function () {
                return $this->createMock(UrlsService::class);
            };

            $config = Config::fromEnv($dependencyTypeRegistry, $urlsServiceProvider);

            $this->assertEquals('testing', $config->getAppEnv());
            $this->assertEquals('debug', $config->getLogLevel());
            $this->assertEquals('/test/data', $config->getDataPath());
            $this->assertEquals(1800, $config->getMtimeCacheFixturesTtl());
            $this->assertEquals(120, $config->getMtimeCacheUrlsTtl());
            $this->assertEquals(10, $config->getMtimeCacheGeneralTtl());
            $this->assertEquals(7200, $config->getDefaultCacheTtl());
            $this->assertEquals(500, $config->getLocalCacheMaxSize());

            $this->assertTrue($config->isTesting());
            $this->assertFalse($config->isProduction());
            $this->assertFalse($config->isDevelopment());

        } finally {
            // Restore original values
            foreach ($originalValues as $key => $value) {
                if ($value !== false) {
                    putenv("$key=$value");
                } else {
                    putenv($key);
                }
            }
        }
    }

    public function testEnvironmentDetection(): void
    {
        $testCases = [
            ['prod', true, false, false],
            ['production', true, false, false],
            ['dev', false, true, false],
            ['development', false, true, false],
            ['testing', false, false, true],
            ['staging', false, false, false], // default to dev behavior
        ];

        foreach ($testCases as [$env, $isProd, $isDev, $isTesting]) {
            $dependencyTypeRegistry = new DependencyTypeRegistry();
            $urlsServiceProvider = function () {
                return $this->createMock(UrlsService::class);
            };

            $config = new Config(
                appEnv: $env,
                logLevel: 'info',
                dataPath: '/test',
                mtimeCacheFixturesTtl: 3600,
                mtimeCacheUrlsTtl: 60,
                mtimeCacheGeneralTtl: 5,
                defaultCacheTtl: 3600,
                localCacheMaxSize: 1000,
                dependencyTypeRegistry: $dependencyTypeRegistry,
                urlsServiceProvider: $urlsServiceProvider
            );

            $this->assertEquals($isProd, $config->isProduction(), "Failed for environment: $env");
            $this->assertEquals($isDev, $config->isDevelopment(), "Failed for environment: $env");
            $this->assertEquals($isTesting, $config->isTesting(), "Failed for environment: $env");
        }
    }

    public function testCacheSettings(): void
    {
        $dependencyTypeRegistry = new DependencyTypeRegistry();
        $urlsServiceProvider = function () {
            return $this->createMock(UrlsService::class);
        };

        // Test production cache settings
        $prodConfig = new Config(
            appEnv: 'production',
            logLevel: 'info',
            dataPath: '/test',
            mtimeCacheFixturesTtl: 3600,
            mtimeCacheUrlsTtl: 60,
            mtimeCacheGeneralTtl: 5,
            defaultCacheTtl: 3600,
            localCacheMaxSize: 1000,
            dependencyTypeRegistry: $dependencyTypeRegistry,
            urlsServiceProvider: $urlsServiceProvider
        );

        $prodCacheSettings = $prodConfig->getCacheSettings();
        $this->assertEquals('apcu', $prodCacheSettings->adapter);
        $this->assertEquals('prod_cache_', $prodCacheSettings->prefix);
        $this->assertFalse($prodConfig->isDebugMode());

        // Test development cache settings
        $devConfig = new Config(
            appEnv: 'dev',
            logLevel: 'info',
            dataPath: '/test',
            mtimeCacheFixturesTtl: 3600,
            mtimeCacheUrlsTtl: 60,
            mtimeCacheGeneralTtl: 5,
            defaultCacheTtl: 3600,
            localCacheMaxSize: 1000,
            dependencyTypeRegistry: $dependencyTypeRegistry,
            urlsServiceProvider: $urlsServiceProvider
        );

        $devCacheSettings = $devConfig->getCacheSettings();
        $this->assertEquals('memory', $devCacheSettings->adapter);
        $this->assertEquals('dev_cache_', $devCacheSettings->prefix);
        $this->assertTrue($devConfig->isDebugMode());
    }

    public function testPathMethods(): void
    {
        $dependencyTypeRegistry = new DependencyTypeRegistry();
        $urlsServiceProvider = function () {
            return $this->createMock(UrlsService::class);
        };

        $config = new Config(
            appEnv: 'test',
            logLevel: 'info',
            dataPath: '/custom/data',
            mtimeCacheFixturesTtl: 3600,
            mtimeCacheUrlsTtl: 60,
            mtimeCacheGeneralTtl: 5,
            defaultCacheTtl: 3600,
            localCacheMaxSize: 1000,
            dependencyTypeRegistry: $dependencyTypeRegistry,
            urlsServiceProvider: $urlsServiceProvider
        );

        $this->assertEquals('/custom/data/urls-config.json', $config->getUrlsConfigPath());
        $this->assertEquals('/custom/data/assets-fixtures.json', $config->getAssetsFixturesPath());
        $this->assertEquals('/custom/data/definitions-fixtures.json', $config->getDefinitionsFixturesPath());
    }

    public function testMtimeCacheTTLSettings(): void
    {
        $dependencyTypeRegistry = new DependencyTypeRegistry();
        $urlsServiceProvider = function () {
            return $this->createMock(UrlsService::class);
        };

        $config = new Config(
            appEnv: 'test',
            logLevel: 'info',
            dataPath: '/test',
            mtimeCacheFixturesTtl: 1800,
            mtimeCacheUrlsTtl: 120,
            mtimeCacheGeneralTtl: 10,
            defaultCacheTtl: 3600,
            localCacheMaxSize: 1000,
            dependencyTypeRegistry: $dependencyTypeRegistry,
            urlsServiceProvider: $urlsServiceProvider
        );

        $ttlSettings = $config->getMtimeCacheTTLSettings();
        $this->assertInstanceOf(\App\Config\MtimeTtlSettings::class, $ttlSettings);
        $this->assertEquals(1800, $ttlSettings->fixtures);
        $this->assertEquals(120, $ttlSettings->urls);
        $this->assertEquals(10, $ttlSettings->general);
    }

    public function testValidation(): void
    {
        $dependencyTypeRegistry = new DependencyTypeRegistry();
        $urlsServiceProvider = function () {
            return $this->createMock(UrlsService::class);
        };

        // Valid configuration
        $validConfig = new Config(
            appEnv: 'test',
            logLevel: 'info',
            dataPath: __DIR__, // Use current directory which exists
            mtimeCacheFixturesTtl: 3600,
            mtimeCacheUrlsTtl: 60,
            mtimeCacheGeneralTtl: 5,
            defaultCacheTtl: 3600,
            localCacheMaxSize: 1000,
            dependencyTypeRegistry: $dependencyTypeRegistry,
            urlsServiceProvider: $urlsServiceProvider
        );

        $this->assertEmpty($validConfig->validate());

        // Invalid configuration
        $invalidConfig = new Config(
            appEnv: 'test',
            logLevel: 'invalid_level',
            dataPath: '/non/existent/path',
            mtimeCacheFixturesTtl: -1,
            mtimeCacheUrlsTtl: -1,
            mtimeCacheGeneralTtl: -1,
            defaultCacheTtl: 0,
            localCacheMaxSize: 0,
            dependencyTypeRegistry: $dependencyTypeRegistry,
            urlsServiceProvider: $urlsServiceProvider
        );

        $errors = $invalidConfig->validate();
        $this->assertNotEmpty($errors);
        $this->assertContains('DATA_PATH directory does not exist: /non/existent/path', $errors);
        $this->assertContains('MTIME_CACHE_FIXTURES_TTL must be >= 0', $errors);
        $this->assertContains('DEFAULT_CACHE_TTL must be > 0', $errors);
        $this->assertContains('LOCAL_CACHE_MAX_SIZE must be > 0', $errors);
        $this->assertContains('APP_LOG_LEVEL must be one of: silent, error, warn, info', $errors);
    }

    public function testToArray(): void
    {
        $dependencyTypeRegistry = new DependencyTypeRegistry();
        $urlsServiceProvider = function () {
            return $this->createMock(UrlsService::class);
        };

        $config = new Config(
            appEnv: 'test',
            logLevel: 'info',
            dataPath: '/test/data',
            mtimeCacheFixturesTtl: 1800,
            mtimeCacheUrlsTtl: 120,
            mtimeCacheGeneralTtl: 10,
            defaultCacheTtl: 7200,
            localCacheMaxSize: 500,
            dependencyTypeRegistry: $dependencyTypeRegistry,
            urlsServiceProvider: $urlsServiceProvider
        );

        $expected = [
            'app_env' => 'test',
            'log_level' => 'info',
            'data_path' => '/test/data',
            'mtime_cache_fixtures_ttl' => 1800,
            'mtime_cache_urls_ttl' => 120,
            'mtime_cache_general_ttl' => 10,
            'default_cache_ttl' => 7200,
            'local_cache_max_size' => 500,
            'is_production' => false,
            'is_debug_mode' => true,
        ];

        $this->assertEquals($expected, $config->toArray());
    }
}
