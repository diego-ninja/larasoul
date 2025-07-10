<?php

namespace Ninja\Larasoul\Enums;

enum RiskStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';
    case Expired = 'expired';
    case ManualReview = 'manual_review';

    public static function values(): array
    {
        return [
            self::Pending->value,
            self::Verified->value,
            self::Failed->value,
            self::Expired->value,
            self::ManualReview->value,
        ];
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    public function isExpired(): bool
    {
        return $this === self::Expired;
    }

    public function requiresManualReview(): bool
    {
        return $this === self::ManualReview;
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::Verified, self::Failed, self::Expired], true);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Pending => in_array($status, [self::Verified, self::Failed, self::ManualReview], true),
            self::ManualReview => in_array($status, [self::Verified, self::Failed], true),
            self::Verified => $status === self::Expired,
            default => false,
        };
    }
}
