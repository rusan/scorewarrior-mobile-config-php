<?php
declare(strict_types=1);

namespace App\Config;

/**
 * Centralized application configuration
 * Replaces scattered getenv() calls throughout the codebase
 */
class AppConfig
{
    public function __construct(
        private string $appEnv,
        private string $logLevel,
        private string $dataPath,
        private int $mtimeCacheFixturesTtl,
        private int $mtimeCacheUrlsTtl,
        private int $mtimeCacheGeneralTtl,
        private int $defaultCacheTtl,
        private int $localCacheMaxSize,
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            appEnv: getenv('APP_ENV') ?: 'dev',
            logLevel: getenv('APP_LOG_LEVEL') ?: 'info',
            dataPath: getenv('DATA_PATH') ?: '/local/data',
            mtimeCacheFixturesTtl: (int) (getenv('MTIME_CACHE_FIXTURES_TTL') ?: 3600),
            mtimeCacheUrlsTtl: (int) (getenv('MTIME_CACHE_URLS_TTL') ?: 60),
            mtimeCacheGeneralTtl: (int) (getenv('MTIME_CACHE_GENERAL_TTL') ?: 5),
            defaultCacheTtl: (int) (getenv('DEFAULT_CACHE_TTL') ?: 3600),
            localCacheMaxSize: (int) (getenv('LOCAL_CACHE_MAX_SIZE') ?: 1000),
        );
    }

    // Environment settings
    public function getAppEnv(): string
    {
        return $this->appEnv;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function isProduction(): bool
    {
        return in_array(strtolower($this->appEnv), ['prod', 'production'], true);
    }

    public function isDevelopment(): bool
    {
        return in_array(strtolower($this->appEnv), ['dev', 'development'], true);
    }

    public function isTesting(): bool
    {
        return strtolower($this->appEnv) === 'testing';
    }

    // Paths
    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    public function getUrlsConfigPath(): string
    {
        return $this->dataPath . '/' . DataFileNames::URLS_CONFIG;
    }

    public function getAssetsFixturesPath(): string
    {
        return $this->dataPath . '/' . DataFileNames::ASSETS_FIXTURES;
    }

    public function getDefinitionsFixturesPath(): string
    {
        return $this->dataPath . '/' . DataFileNames::DEFINITIONS_FIXTURES;
    }

    // Cache TTL settings
    public function getMtimeCacheFixturesTtl(): int
    {
        return $this->mtimeCacheFixturesTtl;
    }

    public function getMtimeCacheUrlsTtl(): int
    {
        return $this->mtimeCacheUrlsTtl;
    }

    public function getMtimeCacheGeneralTtl(): int
    {
        return $this->mtimeCacheGeneralTtl;
    }

    public function getDefaultCacheTtl(): int
    {
        return $this->defaultCacheTtl;
    }

    public function getLocalCacheMaxSize(): int
    {
        return $this->localCacheMaxSize;
    }

    // Structured configuration arrays for backward compatibility
    public function getMtimeCacheTTLSettings(): array
    {
        return [
            'fixtures' => $this->mtimeCacheFixturesTtl,
            'urls' => $this->mtimeCacheUrlsTtl,
            'general' => $this->mtimeCacheGeneralTtl,
        ];
    }

    // Validation
    public function validate(): array
    {
        $errors = [];

        if (!is_dir($this->dataPath)) {
            $errors[] = "DATA_PATH directory does not exist: {$this->dataPath}";
        }

        if ($this->mtimeCacheFixturesTtl < 0) {
            $errors[] = "MTIME_CACHE_FIXTURES_TTL must be >= 0";
        }

        if ($this->mtimeCacheUrlsTtl < 0) {
            $errors[] = "MTIME_CACHE_URLS_TTL must be >= 0";
        }

        if ($this->mtimeCacheGeneralTtl < 0) {
            $errors[] = "MTIME_CACHE_GENERAL_TTL must be >= 0";
        }

        if ($this->defaultCacheTtl <= 0) {
            $errors[] = "DEFAULT_CACHE_TTL must be > 0";
        }

        if ($this->localCacheMaxSize <= 0) {
            $errors[] = "LOCAL_CACHE_MAX_SIZE must be > 0";
        }

        if (!in_array($this->logLevel, ['silent', 'error', 'warn', 'info'], true)) {
            $errors[] = "APP_LOG_LEVEL must be one of: silent, error, warn, info";
        }

        return $errors;
    }

    public function toArray(): array
    {
        return [
            'app_env' => $this->appEnv,
            'log_level' => $this->logLevel,
            'data_path' => $this->dataPath,
            'mtime_cache_fixtures_ttl' => $this->mtimeCacheFixturesTtl,
            'mtime_cache_urls_ttl' => $this->mtimeCacheUrlsTtl,
            'mtime_cache_general_ttl' => $this->mtimeCacheGeneralTtl,
            'default_cache_ttl' => $this->defaultCacheTtl,
            'local_cache_max_size' => $this->localCacheMaxSize,
        ];
    }
}
