<?php
declare(strict_types=1);

use App\Application\ApplicationBootstrap;

$rootPath = dirname(__DIR__);
require $rootPath . '/vendor/autoload.php';

return ApplicationBootstrap::create();