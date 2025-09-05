<?php
declare(strict_types=1);

namespace App\Middleware;

use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\MiddlewareInterface as PhalconMiddlewareInterface;

abstract class AbstractMiddleware implements PhalconMiddlewareInterface
{
    abstract public function handle(Micro $app): bool;
    
    public function call(?Micro $app = null): bool
    {
        if ($app === null) {
            $app = \Phalcon\Di\Di::getDefault()->get('application');
        }
        return $this->handle($app);
    }
}