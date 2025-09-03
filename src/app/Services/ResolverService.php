<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Config\DependencyNames;
use App\Services\DependencyTypeRegistry;
use App\Utils\CacheKeyBuilder;
use App\Utils\Log;
use App\Utils\Semver;

class ResolverService
{
    /** @var array<string, array{version:string,hash:string}> */
    private array $localCache = [];

    public function __construct(
        private FixturesService $fixturesService,
        private ConfigInterface $config,
        private CacheManager $cacheManager,
        private DependencyTypeRegistry $dependencyTypeRegistry,
        private MtimeCacheService $mtimeCacheService,
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
            Log::warn('unknown_dependency_type', compact('dependencyType'));
            return null;
        }
        
        $mtime = $this->getMtimeForType($dependencyType, $platform);
        $cacheKey = CacheKeyBuilder::resolver($platform, $appVersion, null, null, $mtime, $dependencyType);

        if (isset($this->localCache[$cacheKey])) {
            Log::info('resolver_local_cache_hit', ['kind' => $dependencyType, 'key' => $cacheKey]);
            return $this->localCache[$cacheKey];
        }

        $cachedResult = $this->cacheManager->get($cacheKey, 'resolver');
        if ($cachedResult !== null) {
            $this->localCache[$cacheKey] = $cachedResult;
            return $cachedResult;
        }

        $rows = $this->fixturesService->load($dependencyType, $platform);
        $map = $this->fixturesService->toMap($rows);
        $versions = array_keys($map);

        if ($explicitVersion !== null) {
            if (!isset($map[$explicitVersion]) || !$type->isCompatible($appVersion, $explicitVersion)) {
                Log::info("{$dependencyType}_explicit_not_found_or_incompatible", compact('platform', 'appVersion', 'explicitVersion'));
                return null;
            }
            return ['version' => $explicitVersion, 'hash' => $map[$explicitVersion]];
        }

        $best = $this->pickBestCompatible($appVersion, $versions, $type);
        if (!$best) {
            return null;
        }
        $res = ['version' => $best, 'hash' => $map[$best]];

        $this->localCache[$cacheKey] = $res;
        Log::info('resolver_local_cache_set', ['kind' => $dependencyType, 'key' => $cacheKey, 'version' => $best]);
        $ttlSettings = $this->config->getMtimeCacheTTLSettings();
        $this->cacheManager->set($cacheKey, $res, 'resolver', $ttlSettings['general']);

        return $res;
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