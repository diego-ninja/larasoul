<?php

namespace Ninja\Larasoul\Exceptions;

use Exception;

class VerisoulValidationException extends VerisoulApiException
{
    public function __construct(
        string $message,
        public readonly string $field,
        public readonly mixed $value,
        ?Exception $previous = null
    ) {
        parent::__construct(
            message: $message,
            statusCode: 422,
            response: ['field' => $field, 'value' => $value],
            previous: $previous
        );
    }

    public static function invalidField(string $field, mixed $value, string $reason): self
    {
        return new self(
            message: "Invalid {$field}: {$reason}",
            field: $field,
            value: $value
        );
    }

    public static function missingField(string $field): self
    {
        return new self(
            message: "Missing required field: {$field}",
            field: $field,
            value: null
        );
    }
}
