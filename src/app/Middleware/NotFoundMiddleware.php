<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Http;
use App\Config\HttpStatusCodes;
use Phalcon\Mvc\Micro;

class NotFoundMiddleware extends AbstractMiddleware
{
    public function handle(Micro $app): bool
    {
        $app->notFound(function () {
            return Http::error(HttpStatusCodes::NOT_FOUND, 'Not Found');
        });
        
        return true;
    }
}