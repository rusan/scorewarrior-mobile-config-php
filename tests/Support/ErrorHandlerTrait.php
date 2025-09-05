<?php
declare(strict_types=1);

namespace Tests\Support;

trait ErrorHandlerTrait
{
    /** @var callable|null */
    private $previousErrorHandler = null;

    protected function installDeprecationToExceptionHandler(): void
    {
        $this->previousErrorHandler = set_error_handler(static function (int $severity, string $message, ?string $file = null, ?int $line = null): bool {
            if ($severity === E_USER_DEPRECATED || $severity === E_DEPRECATED) {
                throw new \ErrorException($message, 0, $severity, $file ?? '', $line ?? 0);
            }
            return false;
        });
    }

    protected function restorePreviousErrorHandler(): void
    {
        if ($this->previousErrorHandler !== null) {
            restore_error_handler();
            $this->previousErrorHandler = null;
        }
    }
}


