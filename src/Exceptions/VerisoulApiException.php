<?php

namespace Ninja\Larasoul\Exceptions;

use Exception;

class VerisoulApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly array $response = [],
        public readonly ?string $endpoint = null,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function connectionFailed(string $endpoint, Exception $previous): self
    {
        return new self(
            message: "Failed to connect to Verisoul API at endpoint: {$endpoint}",
            statusCode: 0,
            endpoint: $endpoint,
            previous: $previous,
        );
    }

    public static function authenticationFailed(string $endpoint): self
    {
        return new self(
            message: 'Authentication failed for Verisoul API',
            statusCode: 401,
            endpoint: $endpoint,
        );
    }

    public static function badRequest(string $endpoint, array $response): self
    {
        $message = $response['error']['message'] ?? 'Bad request to Verisoul API';

        return new self(
            message: $message,
            statusCode: 400,
            response: $response,
            endpoint: $endpoint,
        );
    }

    public static function serverError(string $endpoint, int $statusCode, array $response): self
    {
        return new self(
            message: 'Verisoul API server error',
            statusCode: $statusCode,
            response: $response,
            endpoint: $endpoint,
        );
    }

    public static function rateLimitExceeded(string $endpoint, array $response): self
    {
        return new self(
            message: 'Rate limit exceeded for Verisoul API',
            statusCode: 429,
            response: $response,
            endpoint: $endpoint,
        );
    }

    public static function invalidResponse(string $endpoint, string $reason): self
    {
        return new self(
            message: "Invalid response from Verisoul API: {$reason}",
            statusCode: 0,
            endpoint: $endpoint,
        );
    }

    /**
     * Get error details for logging
     */
    public function getErrorDetails(): array
    {
        return [
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
            'endpoint' => $this->endpoint,
            'response' => $this->response,
        ];
    }
}
