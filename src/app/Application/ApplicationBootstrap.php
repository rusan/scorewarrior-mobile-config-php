<?php
declare(strict_types=1);

namespace App\Application;

use App\Middleware\MiddlewareManager;
use App\Providers\ControllerProvider;
use App\Providers\ServiceProvider;
use App\Providers\ValidatorProvider;
use App\Utils\Log;
use Phalcon\Di\Di;
use Phalcon\Mvc\Micro;

class ApplicationBootstrap
{
    public static function create(): Micro
    {
        self::initializeLogging();
        
        $di = new Di();
        self::registerProviders($di);
        
        $app = new Micro();
        $app->setDI($di);
        
        self::registerMiddleware($app, $di);
        self::registerRoutes($app);
        
        return $app;
    }
    
    private static function initializeLogging(): void
    {
        $logLevel = getenv('APP_LOG_LEVEL') ?: 'info';
        Log::setLevel($logLevel);
        

        $env = getenv('APP_ENV') ?: 'dev';
        if ($env === 'testing' || $env === 'dev') {
            error_reporting(E_ALL & ~E_NOTICE);
        }
    }
    
    private static function registerProviders(Di $di): void
    {
        ServiceProvider::register($di);
        ValidatorProvider::register($di);
        ControllerProvider::register($di);
    }
    
    private static function registerMiddleware(Micro $app, Di $di): void
    {
        MiddlewareManager::createDefault($di)->register($app);
    }
    
    private static function registerRoutes(Micro $app): void
    {
        $app->get('/health', function () use ($app) {
            $service = $app->getDI()->getShared('healthService');
            if ($service === null) {
                throw new \RuntimeException('healthService not found in DI container');
            }
            $payload = $service->check();
            $payload['metrics'] = ['log_counters' => Log::getCounters()];
            return \App\Utils\Http::json(\App\Config\HttpStatusCodes::OK, $payload);
        });
        
        $app->get('/config', function () use ($app) {
            $controller = $app->getDI()->get('configController');
            if ($controller === null) {
                throw new \RuntimeException('ConfigController not found in DI container');
            }
            return $controller->getConfig($app);
        });

        $app->get('/metrics', function () {
            $counters = \App\Utils\Log::getCounters();
            $lines = [];
            foreach ($counters as $name => $value) {
                $metricName = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
                $lines[] = sprintf('%s %d', $metricName, (int) $value);
            }
            $body = implode("\n", $lines) . "\n";
            $resp = new \Phalcon\Http\Response();
            $resp->setStatusCode(200, 'OK');
            $resp->setContentType('text/plain');
            $resp->setContent($body);
            return $resp;
        });
    }
}
