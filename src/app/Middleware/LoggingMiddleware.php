<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Clock;
use App\Utils\Log;
use App\Contracts\LoggerInterface;
use Phalcon\Mvc\Micro;

class LoggingMiddleware extends AbstractMiddleware
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function handle(Micro $app): bool
    {
        $t0 = Clock::now();
        $app->getDI()->setShared('reqStart', fn() => $t0);

        $request = $app->request;
        $rid = $request->getHeader('X-Request-Id') ?: uniqid('req_', true);
        Log::setRequestId($rid);

        $this->logger->logRequestReceived(
            $request->getMethod(),
            $request->getURI(),
            array_merge($_GET, ['clientIp' => $request->getClientAddress()])
        );
        
        $logger = $this->logger;
        $app->after(function () use ($app, $t0, $logger) {
            $response = $app->response;
            $statusCode = 200; // Default
            
            if ($response && method_exists($response, 'getStatusCode')) {
                $code = $response->getStatusCode();
                if (is_int($code) && $code > 0) {
                    $statusCode = $code;
                }
            }
            
            $logger->logResponseSent(
                $statusCode,
                Clock::sinceMs($t0) / 1000 // Convert to seconds for consistency
            );
        });
        
        return true;
    }
}