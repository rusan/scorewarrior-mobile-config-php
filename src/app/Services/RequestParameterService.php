<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\DependencyNames;
use App\Config\RequestParameterNames;
use Phalcon\Http\Request;

class RequestParameterService
{
    public function extractConfigParameters(Request $request): array
    {
        return [
            RequestParameterNames::PLATFORM => $request->getQuery(RequestParameterNames::PLATFORM, null, ''),
            RequestParameterNames::APP_VERSION => $request->getQuery(RequestParameterNames::APP_VERSION, null, ''),
            DependencyNames::ASSETS => $request->getQuery(DependencyNames::ASSETS, null, null),
            DependencyNames::DEFINITIONS => $request->getQuery(DependencyNames::DEFINITIONS, null, null),
        ];
    }
}
