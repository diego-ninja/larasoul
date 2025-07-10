<?php

namespace Ninja\Larasoul\Exceptions;

use Exception;

/**
 * Base class for verification-related exceptions
 */
abstract class VerificationException extends Exception
{
    public function __construct(
        string $message = '',
        public readonly ?string $errorCode = null,
        public readonly ?array $context = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get error details for API responses
     */
    public function getErrorDetails(): array
    {
        return [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context' => $this->context,
        ];
    }
}
