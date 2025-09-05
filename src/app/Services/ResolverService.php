<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Services\DependencyTypeRegistry;
use App\Contracts\LoggerInterface;
use App\Utils\CacheKeyBuilder;
use App\Utils\Semver;

class ResolverService
{

    public function __construct(
        private FixturesService $fixturesService,
        private ConfigInterface $config,
        private CacheManager $cacheManager,
        private DependencyTypeRegistry $dependencyTypeRegistry,
        private MtimeCacheService $mtimeCacheService,
        private LoggerInterface $logger,
    ) {}

    private function getMtimeForType(string $type, string $platform): int
    {
        $path = $this->config->getFixturesPaths()[$type] ?? null;
        return $path ? $this->mtimeCacheService->getMtime($path) : 0;
    }

    public function resolveDependency(string $dependencyType, string $appVersion, string $platform, ?string $explicitVersion): ?array
    {
        $type = $this->dependencyTypeRegistry->get($dependencyType);
        
        if ($type === null) {
            $this->logger->warn('unknown_dependency_type', compact('dependencyType'));
            return null;
        }
        
        $mtime = $this->getMtimeForType($dependencyType, $platform);
        $cacheKey = CacheKeyBuilder::resolverResult($platform, $appVersion, null, null, $mtime, $dependencyType);

        $cachedResult = $this->cacheManager->get($cacheKey, 'resolver');
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Load raw fixtures and simple map for compatibility with tests and generic dependency types
        $rows = $this->fixturesService->load($dependencyType, $platform);
        $map = $this->fixturesService->toMap($rows);
        $versions = array_keys($map);

        if ($explicitVersion !== null) {
            if (!isset($map[$explicitVersion]) || !$type->isCompatible($appVersion, $explicitVersion)) {
                $this->logger->logConfigNotFound($appVersion, $platform, "explicit_{$dependencyType}_not_found_or_incompatible");
                return null;
            }
            return ['version' => $explicitVersion, 'hash' => $map[$explicitVersion]];
        }

        // Use indexed fast path only for known concrete types; fallback to generic selection for others
        if ($type instanceof \App\Services\DependencyTypes\AssetsType || $type instanceof \App\Services\DependencyTypes\DefinitionsType) {
            $index = \App\Utils\VersionIndex::build($rows);
            $best = $this->pickBestCompatibleIndexed($appVersion, $index, $type);
        } else {
            $best = $this->pickBestCompatible($appVersion, $versions, $type);
        }
        if (!$best) {
            return null;
        }
        $res = ['version' => $best, 'hash' => $map[$best]];

        $ttlSettings = $this->config->getMtimeCacheTTLSettings();
        $this->logger->logCacheSet($cacheKey, 'resolver', $ttlSettings->general);
        $this->cacheManager->set($cacheKey, $res, 'resolver', $ttlSettings->general);

        return $res;
    }
    
    private function pickBestCompatibleIndexed(string $appVersion, array $index, DependencyTypeInterface $type): ?string
    {
        $p = Semver::parse($appVersion);
        $maj = $p['major'];
        $min = $p['minor'];

        if ($type instanceof \App\Services\DependencyTypes\AssetsType) {
            $arr = $index['byMajor'][$maj] ?? [];
        } elseif ($type instanceof \App\Services\DependencyTypes\DefinitionsType) {
            $key = $maj . '.' . $min;
            $arr = $index['byMajorMinor'][$key] ?? [];
        } else {
            // Fallback: filter generically by isCompatible
            $versions = array_keys($index['versionToHash']);
            $arr = [];
            foreach ($versions as $v) {
                if ($type->isCompatible($appVersion, $v)) {
                    $arr[] = $v;
                }
            }
            usort($arr, fn(string $a, string $b) => Semver::compare($a, $b));
        }

        if (!$arr) return null;
        // Pick the highest compatible version (arrays sorted ascending)
        return $arr[count($arr) - 1] ?? null;
    }

    private function pickBestCompatible(string $appVersion, array $versions, DependencyTypeInterface $type): ?string
    {
        $compatible = [];
        foreach ($versions as $version) {
            if ($type->isCompatible($appVersion, $version)) {
                $compatible[] = $version;
            }
        }
        if (empty($compatible)) {
            return null;
        }
        return Semver::pickBest($compatible);
    }
}