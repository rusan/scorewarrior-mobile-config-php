<?php
declare(strict_types=1);

namespace App\Contracts;

interface LoggerInterface
{
    // Core logging methods
    public function info(string $event, array $context = []): void;
    public function warn(string $event, array $context = []): void;
    public function error(string $event, array $context = []): void;

    // Domain-specific structured logging
    public function logRequestReceived(string $method, string $path, array $params): void;
    public function logResponseSent(int $statusCode, float $duration): void;
    
    public function logCacheHit(string $key, string $source): void;
    public function logCacheMiss(string $key, string $source): void;
    public function logCacheSet(string $key, string $source, int $ttl = null): void;
    public function logCacheInvalidated(string $key, string $reason): void;
    
    public function logFileLoaded(string $path, int $mtime, int $size = null): void;
    public function logFileNotFound(string $path): void;
    public function logFileInvalid(string $path, string $reason): void;
    
    public function logConfigResolved(string $appVersion, string $platform, array $result): void;
    public function logConfigNotFound(string $appVersion, string $platform, string $reason): void;
    
    public function logValidationFailed(string $field, string $value, string $reason): void;
    
    // Counter access for metrics
    public function getCounters(): array;
}
