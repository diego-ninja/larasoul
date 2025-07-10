<?php

namespace Ninja\Larasoul\Exceptions;

/**
 * Exception thrown when verification level is insufficient
 */
class VerificationLevelRequiredException extends VerificationException
{
    public function __construct(
        string $message = 'Higher verification level is required',
        ?array $context = null
    ) {
        parent::__construct($message, 'verification_level_required', $context, 403);
    }

    public static function forLevel(string $requiredLevel, $user = null): self
    {
        $currentLevel = method_exists($user, 'getVerificationLevel')
            ? $user->getVerificationLevel()
            : 'none';

        $context = [
            'required_level' => $requiredLevel,
            'current_level' => $currentLevel,
            'user_id' => $user?->getAuthIdentifier(),
        ];

        $levelNames = [
            'basic' => 'Basic',
            'standard' => 'Standard',
            'premium' => 'Premium',
            'high_value' => 'High-Value',
        ];

        $requiredName = $levelNames[$requiredLevel] ?? ucfirst($requiredLevel);
        $currentName = $levelNames[$currentLevel] ?? ucfirst($currentLevel);

        $message = "This resource requires {$requiredName} verification level. Your current level is {$currentName}";

        return new self($message, $context);
    }
}
