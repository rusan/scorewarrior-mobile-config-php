<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Http;
use Phalcon\Mvc\Micro;

class NotFoundMiddleware extends AbstractMiddleware
{
    public function handle(Micro $app): bool
    {
        $app->notFound(function () {
            return Http::error(404, 'Not Found');
        });
        
        return true;
    }
}