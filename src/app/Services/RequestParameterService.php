<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\RequestParameterNames;
use Phalcon\Http\Request;

class RequestParameterService
{
    public function extractConfigParameters(Request $request): array
    {
        return [
            RequestParameterNames::PLATFORM => $request->getQuery(RequestParameterNames::PLATFORM, null, ''),
            RequestParameterNames::APP_VERSION => $request->getQuery(RequestParameterNames::APP_VERSION, null, ''),
            RequestParameterNames::ASSETS_VERSION => $request->getQuery(RequestParameterNames::ASSETS_VERSION, null, null),
            RequestParameterNames::DEFINITIONS_VERSION => $request->getQuery(RequestParameterNames::DEFINITIONS_VERSION, null, null),
        ];
    }
}
