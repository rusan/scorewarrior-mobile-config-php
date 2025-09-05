<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Phalcon\Di\Di;
use App\Utils\Log;
use Tests\Support\ErrorHandlerTrait;


abstract class TestCase extends PHPUnitTestCase
{
    use ErrorHandlerTrait;

    protected Di $di;
    
    protected function setUp(): void
    {
        Log::setLevel('silent');

        parent::setUp();

        $this->installDeprecationToExceptionHandler();

        $this->di = new Di();

        $this->registerServices();
    }

    protected function tearDown(): void
    {
        $this->restorePreviousErrorHandler();
        parent::tearDown();
    }

    protected function registerServices(): void
    {
        \App\Providers\ServiceProvider::register($this->di);
    }
}
