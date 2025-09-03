<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    private const DEFAULT_HTTP_ERROR_CODE = 400;

    public function __construct(
        private string $errorMessage,
        private int $httpCode = self::DEFAULT_HTTP_ERROR_CODE
    ) {
        parent::__construct($errorMessage, $httpCode);
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
