<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Config\CacheTypes;
use App\Contracts\LoggerInterface;

class FixturesService
{
    public function __construct(
        private array $paths,
        private FileCacheService $fileCacheService,
        private LoggerInterface $logger
    ) {}

    public function load(string $kind, string $platform): array
    {
        $path = $this->paths[$kind] ?? null;
        if ($path === null) {
            $this->logger->warn('fixtures_path_missing', compact('kind','platform','path'));
            return [];
        }

        $json = $this->fileCacheService->loadJsonFile(CacheTypes::FIXTURES, $path);
        if (!is_array($json) || !isset($json[$platform]) || !is_array($json[$platform])) {
            $this->logger->error('fixtures_platform_data_invalid', compact('kind','platform','path'));
            return [];
        }

        return $json[$platform];
    }

    /**
     * Build fast lookup indexes for fixtures list of a single platform.
     * Returns structure with:
     * - versionToHash: string(version) => string(hash)
     * - byMajor: int(major) => string[] sorted asc (only for assets)
     * - byMajorMinor: string("major.minor") => string[] sorted asc (only for definitions)
     *
     * @return array{versionToHash: array<string,string>, byMajor: array<int,array<int,string>>, byMajorMinor: array<string,array<int,string>>}
     */
    public function buildIndex(array $list): array
    {
        return \App\Utils\VersionIndex::build($list);
    }

    /**
     * Load fixtures and return pre-indexed structure for fast lookups.
     */
    public function loadIndex(string $kind, string $platform): array
    {
        $list = $this->load($kind, $platform);
        return $this->buildIndex($list);
    }

    // Backward-compatible helper used by existing tests
    public function toMap(array $list): array
    {
        $map = [];
        foreach ($list as $row) {
            $map[(string)$row['version']] = (string)$row['hash'];
        }
        return $map;
    }
}