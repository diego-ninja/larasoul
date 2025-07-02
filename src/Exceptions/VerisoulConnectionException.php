<?php

namespace Ninja\Larasoul\Exceptions;

class VerisoulConnectionException extends VerisoulApiException
{
    public static function timeout(string $endpoint, int $timeout): self
    {
        return new self(
            message: "Connection to Verisoul API timed out after {$timeout} seconds",
            statusCode: 0,
            endpoint: $endpoint,
        );
    }

    public static function networkError(string $endpoint, string $error): self
    {
        return new self(
            message: "Network error connecting to Verisoul API: {$error}",
            statusCode: 0,
            endpoint: $endpoint,
        );
    }
}
