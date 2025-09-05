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
        // Create a minimal config for validation during bootstrap
        $dependencyTypeRegistry = new \App\Services\DependencyTypeRegistry();
        $urlsServiceProvider = function () {
            throw new \RuntimeException('UrlsService not available during bootstrap');
        };
        
        $config = \App\Config\Config::fromEnv($dependencyTypeRegistry, $urlsServiceProvider);
        
        // Validate configuration
        $errors = $config->validate();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                error_log("Configuration error: $error");
            }
            throw new \RuntimeException('Invalid configuration: ' . implode(', ', $errors));
        }
        
        Log::setLevel($config->getLogLevel());

        if ($config->isTesting() || $config->isDevelopment()) {
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
        $requestValidator = $di->getShared('requestValidator');
        $parameterService = $di->getShared('requestParameterService');
        $logger = $di->getShared('logger');
        MiddlewareManager::createDefault($requestValidator, $parameterService, $logger)->register($app);
    }
    
    private static function registerRoutes(Micro $app): void
    {
        $app->get('/health', function () use ($app) {
            $service = $app->getDI()->getShared('healthService');
            $payload = $service->check();
            $payload['metrics'] = ['log_counters' => Log::getCounters()];
            return \App\Utils\Http::json(\App\Config\HttpStatusCodes::OK, $payload);
        });
        
        $app->get('/config', function () use ($app) {
            $controller = $app->getDI()->getShared('configController');
            return $controller->getConfig($app);
        });

        $app->get('/metrics', function () {
            $counters = Log::getCounters();
            $lines = [];
            foreach ($counters as $name => $value) {
                $metricName = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
                $lines[] = sprintf('%s %d', $metricName, (int) $value);
            }
            $body = implode("\n", $lines) . "\n";
            return \App\Utils\Http::text(\App\Config\HttpStatusCodes::OK, $body);
        });
    }
}
