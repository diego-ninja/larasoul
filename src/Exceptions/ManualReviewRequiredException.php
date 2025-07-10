<?php

namespace Ninja\Larasoul\Exceptions;

/**
 * Exception thrown when manual review is required
 */
class ManualReviewRequiredException extends VerificationException
{
    public function __construct(
        string $message = 'Your account requires manual review',
        ?array $context = null
    ) {
        parent::__construct($message, 'manual_review_required', $context, 403);
    }

    public static function forUser($user, ?string $reason = null): self
    {
        $context = [
            'user_id' => $user?->getAuthIdentifier(),
            'review_reason' => $reason,
            'requires_manual_review' => method_exists($user, 'requiresManualReview')
                ? $user->requiresManualReview()
                : false,
        ];

        $message = $reason
            ? "Manual review required: {$reason}"
            : 'Your account is under manual review and access is temporarily restricted';

        return new self($message, $context);
    }
}
