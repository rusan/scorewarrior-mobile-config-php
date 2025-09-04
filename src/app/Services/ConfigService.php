<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Config\RequestParameterNames;
use App\Utils\CacheKeyBuilder;
use App\Contracts\LoggerInterface;

class ConfigService
{
    public function __construct(
        private ResolverService $resolverService,
        private ConfigInterface $config,
        private CacheManager $cacheManager,
        private LoggerInterface $logger
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
                RequestParameterNames::ASSETS_VERSION => $assetsVersion,
                RequestParameterNames::DEFINITIONS_VERSION => $definitionsVersion,
            ];
            
            foreach ($versions as $typeName => $explicitVersion) {
                $dependency = $this->resolverService->resolveDependency($typeName, $appVersion, $platform, $explicitVersion);
                
                if ($dependency === null) {
                    $this->logger->logConfigNotFound($appVersion, $platform, "dependency_resolution_failed: {$typeName}");
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
            RequestParameterNames::ASSETS_VERSION => $this->config->getAssetsUrls(),
            RequestParameterNames::DEFINITIONS_VERSION => $this->config->getDefinitionsUrls(),
            default => []
        };
    }
}