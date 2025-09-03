<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\ValidationException;
use App\Utils\Http;
use App\Utils\Log;
use Phalcon\Mvc\Micro;
use Exception;
use Throwable;

class ErrorHandlerMiddleware extends AbstractMiddleware
{
    public function handle(Micro $app): bool
    {
        $app->error(function ($exception) use ($app) {
            Log::error('uncaught_exception', [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
            
            $statusCode = 500;
            $message = 'Internal Server Error';
            
            if ($exception instanceof ValidationException) {
                $statusCode = $exception->getHttpCode();
                $message = $exception->getErrorMessage();
            } elseif ($exception instanceof Exception) {
                $statusCode = $exception->getCode() >= 400 && $exception->getCode() < 600 
                    ? $exception->getCode() 
                    : 500;
                $message = $exception->getMessage() ?: $message;
            }
            
            return Http::error($statusCode, $message);
        });
        
        register_shutdown_function(function () use ($app) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                Log::error('fatal_error', $error);
                
                if (!headers_sent()) {
                    Http::error(500, 'Fatal Error')->send();
                }
            }
        });
        
        return true;
    }
}
