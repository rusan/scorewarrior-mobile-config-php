<?php
declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\AppConfig;
use Tests\TestCase;

class AppConfigTest extends TestCase
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
            $config = AppConfig::fromEnv();

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

    public function testFromEnvWithDefaults(): void
    {
        // Clear all environment variables to test defaults
        $originalValues = [];
        $envKeys = [
            'APP_ENV', 'APP_LOG_LEVEL', 'DATA_PATH',
            'MTIME_CACHE_FIXTURES_TTL', 'MTIME_CACHE_URLS_TTL', 'MTIME_CACHE_GENERAL_TTL',
            'DEFAULT_CACHE_TTL', 'LOCAL_CACHE_MAX_SIZE'
        ];

        foreach ($envKeys as $key) {
            $originalValues[$key] = getenv($key);
            putenv($key);
        }

        try {
            $config = AppConfig::fromEnv();

            $this->assertEquals('dev', $config->getAppEnv());
            $this->assertEquals('info', $config->getLogLevel());
            $this->assertEquals('/local/data', $config->getDataPath());
            $this->assertEquals(3600, $config->getMtimeCacheFixturesTtl());
            $this->assertEquals(60, $config->getMtimeCacheUrlsTtl());
            $this->assertEquals(5, $config->getMtimeCacheGeneralTtl());
            $this->assertEquals(3600, $config->getDefaultCacheTtl());
            $this->assertEquals(1000, $config->getLocalCacheMaxSize());

            $this->assertFalse($config->isTesting());
            $this->assertFalse($config->isProduction());
            $this->assertTrue($config->isDevelopment());

        } finally {
            // Restore original values
            foreach ($originalValues as $key => $value) {
                if ($value !== false) {
                    putenv("$key=$value");
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
            $config = new AppConfig(
                appEnv: $env,
                logLevel: 'info',
                dataPath: '/test',
                mtimeCacheFixturesTtl: 3600,
                mtimeCacheUrlsTtl: 60,
                mtimeCacheGeneralTtl: 5,
                defaultCacheTtl: 3600,
                localCacheMaxSize: 1000
            );

            $this->assertEquals($isProd, $config->isProduction(), "Failed for environment: $env");
            $this->assertEquals($isDev, $config->isDevelopment(), "Failed for environment: $env");
            $this->assertEquals($isTesting, $config->isTesting(), "Failed for environment: $env");
        }
    }

    public function testPathMethods(): void
    {
        $config = new AppConfig(
            appEnv: 'test',
            logLevel: 'info',
            dataPath: '/custom/data',
            mtimeCacheFixturesTtl: 3600,
            mtimeCacheUrlsTtl: 60,
            mtimeCacheGeneralTtl: 5,
            defaultCacheTtl: 3600,
            localCacheMaxSize: 1000
        );

        $this->assertEquals('/custom/data/urls-config.json', $config->getUrlsConfigPath());
        $this->assertEquals('/custom/data/assets-fixtures.json', $config->getAssetsFixturesPath());
        $this->assertEquals('/custom/data/definitions-fixtures.json', $config->getDefinitionsFixturesPath());
    }

    public function testMtimeCacheTTLSettings(): void
    {
        $config = new AppConfig(
            appEnv: 'test',
            logLevel: 'info',
            dataPath: '/test',
            mtimeCacheFixturesTtl: 1800,
            mtimeCacheUrlsTtl: 120,
            mtimeCacheGeneralTtl: 10,
            defaultCacheTtl: 3600,
            localCacheMaxSize: 1000
        );

        $expected = [
            'fixtures' => 1800,
            'urls' => 120,
            'general' => 10,
        ];

        $this->assertEquals($expected, $config->getMtimeCacheTTLSettings());
    }

    public function testValidation(): void
    {
        // Valid configuration
        $validConfig = new AppConfig(
            appEnv: 'test',
            logLevel: 'info',
            dataPath: __DIR__, // Use current directory which exists
            mtimeCacheFixturesTtl: 3600,
            mtimeCacheUrlsTtl: 60,
            mtimeCacheGeneralTtl: 5,
            defaultCacheTtl: 3600,
            localCacheMaxSize: 1000
        );

        $this->assertEmpty($validConfig->validate());

        // Invalid configuration
        $invalidConfig = new AppConfig(
            appEnv: 'test',
            logLevel: 'invalid_level',
            dataPath: '/non/existent/path',
            mtimeCacheFixturesTtl: -1,
            mtimeCacheUrlsTtl: -1,
            mtimeCacheGeneralTtl: -1,
            defaultCacheTtl: 0,
            localCacheMaxSize: 0
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
        $config = new AppConfig(
            appEnv: 'test',
            logLevel: 'info',
            dataPath: '/test/data',
            mtimeCacheFixturesTtl: 1800,
            mtimeCacheUrlsTtl: 120,
            mtimeCacheGeneralTtl: 10,
            defaultCacheTtl: 7200,
            localCacheMaxSize: 500
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
        ];

        $this->assertEquals($expected, $config->toArray());
    }
}
