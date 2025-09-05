<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;

final class HealthService
{
    public function __construct(
        private ConfigInterface $config,
        private MtimeCacheService $mtimeCacheService,
    ) {}

    public function check(): array
    {
        $paths = $this->config->getFixturesPaths();
        $filesOk = true;
        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }
            if (!file_exists($path)) {
                $filesOk = false;
                break;
            }
            $mtime = $this->mtimeCacheService->getMtime($path);
            if ($mtime <= 0) {
                $filesOk = false;
                break;
            }
        }

        $status = $filesOk ? 'ok' : 'degraded';

        return [
            'status' => $status,
            'checks' => [
                'fixtures_files' => $filesOk,
                'fixtures_count' => count($paths),
            ],
        ];
    }
}


