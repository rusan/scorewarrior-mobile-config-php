<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\RequestParameterNames;
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
        
        $platform = $params[RequestParameterNames::PLATFORM];
        $appVer = $params[RequestParameterNames::APP_VERSION];
        $assetsVer = $params[RequestParameterNames::ASSETS_VERSION];
        $defsVer = $params[RequestParameterNames::DEFINITIONS_VERSION];

        Log::info('config_request', compact('platform', 'appVer', 'assetsVer', 'defsVer'));
        Log::incCounter('requests_total');

        $result = $this->configService->getConfig($appVer, $platform, $assetsVer, $defsVer);

        if ($result === null) {
            Log::info('config_not_found', compact('platform','appVer','assetsVer','defsVer'));
            Log::incCounter('config_not_found_total');
            return Http::error(HttpStatusCodes::NOT_FOUND, "Configuration not found for appVersion {$appVer} ({$platform})");
        }

        Log::info('config_resolved', [
            'platform' => $platform,
            'appVersion' => $appVer,
            'assetsVersion' => $result[RequestParameterNames::ASSETS_VERSION]['version'],
            'definitionsVersion' => $result[RequestParameterNames::DEFINITIONS_VERSION]['version'],
        ]);
        Log::incCounter('config_resolved_total');

        return Http::json(HttpStatusCodes::OK, $result);
    }
}
