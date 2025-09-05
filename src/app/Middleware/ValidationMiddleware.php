<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\ValidationException;
use App\Utils\Log;
use App\Validators\RequestValidator;
use Phalcon\Mvc\Micro;

final class ValidationMiddleware extends AbstractMiddleware
{
    public function __construct(
        private RequestValidator $validator
    ) {}

    public function handle(Micro $app): bool
    {
        $request = $app->request;
        $path = $request->getURI();
        

        if (strpos($path, '/config') === 0) {
            try {
                $params = [
                    \App\Config\RequestParameterNames::PLATFORM => $request->getQuery(\App\Config\RequestParameterNames::PLATFORM, null, ''),
                    \App\Config\RequestParameterNames::APP_VERSION => $request->getQuery(\App\Config\RequestParameterNames::APP_VERSION, null, ''),
                    \App\Config\RequestParameterNames::ASSETS_VERSION => $request->getQuery(\App\Config\RequestParameterNames::ASSETS_VERSION, null, null),
                    \App\Config\RequestParameterNames::DEFINITIONS_VERSION => $request->getQuery(\App\Config\RequestParameterNames::DEFINITIONS_VERSION, null, null),
                ];
                $this->validator->validateConfigRequest($params);
            } catch (ValidationException $e) {
                Log::warn('validation_failed', [
                    'message' => $e->getMessage(),
                    'path' => $path,
                    'query' => $request->getQuery()
                ]);
                throw $e;
            }
        }
        
        return true; // Continue processing
    }
}
