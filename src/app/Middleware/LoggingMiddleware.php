<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Clock;
use App\Utils\Log;
use Phalcon\Mvc\Micro;

class LoggingMiddleware extends AbstractMiddleware
{
    public function handle(Micro $app): bool
    {
        $t0 = Clock::now();
        $app->getDI()->setShared('reqStart', fn() => $t0);

        $request = $app->request;
        $rid = $request->getHeader('X-Request-Id') ?: uniqid('req_', true);
        Log::setRequestId($rid);

        Log::info('request_received', [
            'method'   => $request->getMethod(),
            'path'     => $request->getURI(),
            'query'    => $_GET,
            'clientIp' => $request->getClientAddress(),
        ]);
        
        $app->after(function () use ($app, $t0) {
            $response = $app->response;
            
            Log::info('response_sent', [
                'status'   => $response->getStatusCode(),
                'duration_ms' => round(Clock::sinceMs($t0), 3),
            ]);
        });
        
        return true;
    }
}