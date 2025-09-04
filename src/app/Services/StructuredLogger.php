<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\LoggerInterface;
use App\Utils\Log;

class StructuredLogger implements LoggerInterface
{
    public function info(string $event, array $context = []): void
    {
        Log::info($event, $context);
    }

    public function warn(string $event, array $context = []): void
    {
        Log::warn($event, $context);
    }

    public function error(string $event, array $context = []): void
    {
        Log::error($event, $context);
    }

    public function logRequestReceived(string $method, string $path, array $params): void
    {
        $this->info('request_received', [
            'method' => $method,
            'path' => $path,
            'params' => $params,
        ]);
    }

    public function logResponseSent(int $statusCode, float $duration): void
    {
        $this->info('response_sent', [
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
        ]);
    }

    public function logCacheHit(string $key, string $source): void
    {
        $this->info('cache_hit', [
            'key' => $key,
            'source' => $source,
        ]);
    }

    public function logCacheMiss(string $key, string $source): void
    {
        $this->info('cache_miss', [
            'key' => $key,
            'source' => $source,
        ]);
    }

    public function logCacheSet(string $key, string $source, int $ttl = null): void
    {
        $context = [
            'key' => $key,
            'source' => $source,
        ];
        if ($ttl !== null) {
            $context['ttl'] = $ttl;
        }
        $this->info('cache_set', $context);
    }

    public function logCacheInvalidated(string $key, string $reason): void
    {
        $this->info('cache_invalidated', [
            'key' => $key,
            'reason' => $reason,
        ]);
    }

    public function logFileLoaded(string $path, int $mtime, int $size = null): void
    {
        $context = [
            'path' => $path,
            'mtime' => $mtime,
        ];
        if ($size !== null) {
            $context['size_bytes'] = $size;
        }
        $this->info('file_loaded', $context);
    }

    public function logFileNotFound(string $path): void
    {
        $this->error('file_not_found', [
            'path' => $path,
        ]);
    }

    public function logFileInvalid(string $path, string $reason): void
    {
        $this->error('file_invalid', [
            'path' => $path,
            'reason' => $reason,
        ]);
    }

    public function logConfigResolved(string $appVersion, string $platform, array $result): void
    {
        $this->info('config_resolved', [
            'app_version' => $appVersion,
            'platform' => $platform,
            'assets_version' => $result['assets']['version'] ?? null,
            'definitions_version' => $result['definitions']['version'] ?? null,
        ]);
    }

    public function logConfigNotFound(string $appVersion, string $platform, string $reason): void
    {
        $this->info('config_not_found', [
            'app_version' => $appVersion,
            'platform' => $platform,
            'reason' => $reason,
        ]);
    }

    public function logValidationFailed(string $field, string $value, string $reason): void
    {
        $this->warn('validation_failed', [
            'field' => $field,
            'value' => $value,
            'reason' => $reason,
        ]);
    }

    public function getCounters(): array
    {
        return Log::getCounters();
    }
}
