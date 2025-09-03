<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Config\DependencyNames;
use App\Utils\CacheKeyBuilder;
use App\Utils\Log;

class ConfigService
{
    public function __construct(
        private ResolverService $resolverService,
        private ConfigInterface $config,
        private CacheManager $cacheManager
    ) {}

    public function getConfig(string $appVersion, string $platform, ?string $assetsVersion, ?string $definitionsVersion): ?array
    {
        $cacheKey = CacheKeyBuilder::config($platform, $appVersion, $assetsVersion, $definitionsVersion);
        
        return $this->cacheManager->remember($cacheKey, function() use ($appVersion, $platform, $assetsVersion, $definitionsVersion) {
            $result = [
                'backend_entry_point' => ['jsonrpc_url' => $this->config->getBackendJsonRpcUrl()],
                'notifications' => ['jsonrpc_url' => $this->config->getNotificationsJsonRpcUrl()],
            ];
            
            $versions = [
                DependencyNames::ASSETS => $assetsVersion,
                DependencyNames::DEFINITIONS => $definitionsVersion,
            ];
            
            foreach ($versions as $typeName => $explicitVersion) {
                $dependency = $this->resolverService->resolveDependency($typeName, $appVersion, $platform, $explicitVersion);
                
                if ($dependency === null) {
                    Log::info('dependency_resolution_failed', [
                        'type' => $typeName,
                        'appVersion' => $appVersion,
                        'platform' => $platform
                    ]);
                    return null;
                }
                
                $result[$typeName] = [
                    'version' => $dependency['version'],
                    'hash' => $dependency['hash'],
                    'urls' => $this->getUrlsForType($typeName),
                ];
            }
            
            return $result;
        }, 'config', 3600);
    }
    
    private function getUrlsForType(string $type): array
    {
        return match($type) {
            DependencyNames::ASSETS => $this->config->getAssetsUrls(),
            DependencyNames::DEFINITIONS => $this->config->getDefinitionsUrls(),
            default => []
        };
    }
}