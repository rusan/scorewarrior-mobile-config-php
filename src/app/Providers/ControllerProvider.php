<?php
declare(strict_types=1);

namespace App\Providers;

use App\Controllers\ConfigController;
use Phalcon\Di\Di;

class ControllerProvider
{
    public static function register(Di $di): void
    {
        $di->setShared('configController', function () use ($di) {
            return new ConfigController(
                $di->getShared('configService'),
                $di->getShared('fixturesService'),
                $di->getShared('resolverService')
            );
        });
    }
}
