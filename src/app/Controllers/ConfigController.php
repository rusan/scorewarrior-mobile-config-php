<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\RequestParameterNames;
use App\Config\HttpStatusCodes;
use App\Services\ConfigService;
use App\Utils\Http;
use App\Utils\Log;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Controller;
final class ConfigController extends Controller
{
    private ConfigService $configService;
    
    public function setConfigService(ConfigService $configService): void
    {
        $this->configService = $configService;
    }

    public function getConfig(Micro $app): \Phalcon\Http\Response
    {
        $request = $app->request;
        $platform = (string) $request->getQuery(RequestParameterNames::PLATFORM, null, '');
        $appVer = (string) $request->getQuery(RequestParameterNames::APP_VERSION, null, '');
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
