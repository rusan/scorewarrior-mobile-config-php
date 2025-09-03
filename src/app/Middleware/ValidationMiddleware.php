<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\ValidationException;
use App\Services\RequestParameterService;
use App\Utils\Log;
use App\Validators\RequestValidator;
use Phalcon\Mvc\Micro;

final class ValidationMiddleware extends AbstractMiddleware
{
    public function __construct(
        private RequestValidator $validator,
        private RequestParameterService $parameterService
    ) {}

    public function handle(Micro $app): bool
    {
        $request = $app->request;
        $path = $request->getURI();
        

        if (strpos($path, '/config') === 0) {
            try {
                $params = $this->parameterService->extractConfigParameters($request);
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
