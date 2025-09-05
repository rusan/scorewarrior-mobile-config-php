<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\RequestParameterNames;
use App\Config\HttpStatusCodes;
use App\Services\ConfigService;
use App\Services\FixturesService;
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
        private ResolverService $resolverService
    ) {}

    public function getConfig(Micro $app): \Phalcon\Http\Response
    {
        $request = $app->request;
        $platform = $request->getQuery(RequestParameterNames::PLATFORM, null, '');
        $appVer = $request->getQuery(RequestParameterNames::APP_VERSION, null, '');
        $assetsVer = $request->getQuery(RequestParameterNames::ASSETS_VERSION, null, null);
        $defsVer = $request->getQuery(RequestParameterNames::DEFINITIONS_VERSION, null, null);

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
