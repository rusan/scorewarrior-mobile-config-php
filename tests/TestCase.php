<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Phalcon\Di\Di;
use App\Utils\Log;


abstract class TestCase extends PHPUnitTestCase
{
    protected Di $di;
    /** @var callable|null */
    private $previousErrorHandler = null;
    
    protected function setUp(): void
    {
        Log::setLevel('silent');

        parent::setUp();

        $this->previousErrorHandler = set_error_handler(static function (int $severity, string $message, ?string $file = null, ?int $line = null): bool {
            if ($severity === E_USER_DEPRECATED || $severity === E_DEPRECATED) {
                throw new \ErrorException($message, 0, $severity, $file ?? '', $line ?? 0);
            }
            return false;
        });

        $this->di = new Di();

        $this->registerServices();
    }

    protected function tearDown(): void
    {
        // Restore previous error handler to avoid risky test warnings in PHPUnit 11
        if ($this->previousErrorHandler !== null) {
            // restore_error_handler() restores the previous handler installed by set_error_handler()
            restore_error_handler();
            $this->previousErrorHandler = null;
        }

        parent::tearDown();
    }

    protected function registerServices(): void
    {
        \App\Providers\ServiceProvider::register($this->di);
    }
}
