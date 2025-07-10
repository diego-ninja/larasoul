<?php

namespace Ninja\Larasoul\Exceptions;

/**
 * Exception thrown when specific verification type is required
 */
class VerificationTypeRequiredException extends VerificationException
{
    public function __construct(
        string $message = 'Specific verification type is required',
        ?array $context = null
    ) {
        parent::__construct($message, 'verification_type_required', $context, 403);
    }

    public static function forType(string $verificationType, $user = null): self
    {
        $context = [
            'required_type' => $verificationType,
            'user_id' => $user?->getAuthIdentifier(),
            'current_types' => method_exists($user, 'getVerifiedTypes')
                ? $user->getVerifiedTypes()
                : [],
        ];

        $typeNames = [
            'document' => 'Document verification',
            'face' => 'Face verification',
            'phone' => 'Phone verification',
            'identity' => 'Identity verification',
        ];

        $typeName = $typeNames[$verificationType] ?? ucfirst($verificationType).' verification';
        $message = "{$typeName} is required to access this resource";

        return new self($message, $context);
    }
}
