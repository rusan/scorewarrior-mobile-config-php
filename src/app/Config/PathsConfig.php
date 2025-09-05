<?php
declare(strict_types=1);

namespace App\Config;

use App\Services\DependencyTypeRegistry;

final class PathsConfig
{
    private array $mtimeCachePathMap = [];
    private array $fixturesPaths = [];

    public function __construct(
        private string $dataPath,
        private DependencyTypeRegistry $dependencyTypeRegistry
    ) {
        $this->initializePaths();
    }

    private function initializePaths(): void
    {
        $this->fixturesPaths = [];
        foreach ($this->dependencyTypeRegistry->getAll() as $type) {
            $filePath = $this->dataPath . '/' . $type->getFileName();
            $this->fixturesPaths[$type->getName()] = $filePath;
        }
        $this->mtimeCachePathMap = [];
    }

    public function setMtimePathTtl(string $path, int $ttl): void
    {
        $this->mtimeCachePathMap[$path] = $ttl;
    }

    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    public function getUrlsConfigPath(): string
    {
        return $this->dataPath . '/' . DataFileNames::URLS_CONFIG;
    }

    public function getFixturesPaths(): array
    {
        return $this->fixturesPaths;
    }

    public function getMtimeCachePathMap(): array
    {
        return $this->mtimeCachePathMap;
    }

    public function getAssetsFixturesPath(): string
    {
        return $this->dataPath . '/' . DataFileNames::ASSETS_FIXTURES;
    }

    public function getDefinitionsFixturesPath(): string
    {
        return $this->dataPath . '/' . DataFileNames::DEFINITIONS_FIXTURES;
    }
}
