<?php
declare(strict_types=1);

namespace App\Middleware;

use Phalcon\Mvc\Micro;

interface MiddlewareInterface
{
    public function handle(Micro $app): bool;
}
