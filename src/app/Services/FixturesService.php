<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Config\DependencyNames;
use App\Utils\Log;

class FixturesService
{
    public function __construct(
        private array $paths,
        private FileCacheService $fileCacheService
    ) {}

    public function load(string $kind, string $platform): array
    {
        $path = $this->paths[$kind] ?? null;
        if ($path === null) {
            Log::warn('fixtures_path_missing', compact('kind','platform','path'));
            return [];
        }

        $json = $this->fileCacheService->loadJsonFile(DependencyNames::FIXTURES, $path);
        if (!is_array($json) || !isset($json[$platform]) || !is_array($json[$platform])) {
            Log::error('fixtures_platform_data_invalid', compact('kind','platform','path'));
            return [];
        }

        return $json[$platform];
    }

    public function toMap(array $list): array
    {
        $map = [];
        foreach ($list as $row) {
            $map[$row['version']] = $row['hash'];
        }
        return $map;
    }
}