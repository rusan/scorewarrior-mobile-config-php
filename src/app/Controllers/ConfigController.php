<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\DependencyNames;
use App\Config\HttpStatusCodes;
use App\Services\ConfigService;
use App\Services\FixturesService;
use App\Services\RequestParameterService;
use App\Services\ResolverService;
use App\Utils\Http;
use App\Utils\Log;
use Phalcon\Http\Request;
use Phalcon\Mvc\Micro;

final class ConfigController
{
    public function __construct(
        private ConfigService $configService,
        private FixturesService $fixturesService,
        private ResolverService $resolverService,
        private RequestParameterService $parameterService
    ) {}

    public function getConfig(Micro $app): \Phalcon\Http\Response
    {
        $request = $app->request;
        $params = $this->parameterService->extractConfigParameters($request);
        
        $platform = $params['platform'];
        $appVer = $params['appVersion'];
        $assetsVer = $params[DependencyNames::ASSETS];
        $defsVer = $params[DependencyNames::DEFINITIONS];

        Log::info('config_request', compact('platform', 'appVer', 'assetsVer', 'defsVer'));
        Log::incCounter('requests_total');

        try {
            $result = $this->configService->getConfig($appVer, $platform, $assetsVer, $defsVer);
            
            if ($result === null) {
                Log::info('config_not_found', compact('platform','appVer','assetsVer','defsVer'));
                Log::incCounter('config_not_found_total');
                return Http::error(HttpStatusCodes::NOT_FOUND, "Configuration not found for appVersion {$appVer} ({$platform})");
            }
            
            Log::info('config_resolved', [
                'platform' => $platform,
                'appVersion' => $appVer,
                'assetsVersion' => $result[DependencyNames::ASSETS]['version'],
                'definitionsVersion' => $result[DependencyNames::DEFINITIONS]['version'],
            ]);
            Log::incCounter('config_resolved_total');
            
            return Http::json(HttpStatusCodes::OK, $result);
            
        } catch (\Exception $e) {
            Log::error('config_error', [
                'message' => $e->getMessage(),
                'platform' => $platform,
                'appVer' => $appVer
            ]);
            Log::incCounter('config_error_total');
            
            return Http::error(HttpStatusCodes::INTERNAL_SERVER_ERROR, 'Internal server error');
        }
    }
}
