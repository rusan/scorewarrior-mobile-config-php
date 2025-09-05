<?php
declare(strict_types=1);

namespace App\Providers;

use App\Controllers\ConfigController;
use Phalcon\Di\Di;

class ControllerProvider
{
    public static function register(Di $di): void
    {
        $di->setShared(ConfigController::class, function () use ($di) {
            return new ConfigController(
                $di->getShared(\App\Services\ConfigService::class),
                $di->getShared(\App\Services\FixturesService::class),
                $di->getShared(\App\Services\ResolverService::class)
            );
        });
    }
}
