<?php
declare(strict_types=1);

namespace App\Providers;

use App\Validators\RequestValidator;
use Phalcon\Di\Di;

class ValidatorProvider
{
    public static function register(Di $di): void
    {
        $di->setShared('requestValidator', function () {
            return new RequestValidator();
        });
    }
}
